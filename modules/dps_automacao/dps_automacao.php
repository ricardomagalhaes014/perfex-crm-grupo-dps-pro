<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Automação
Description: Envio em massa (WhatsApp/Email/SMS) por estado de lead, propostas em massa com PDF anexado, guiões da Sofia e follow-ups automáticos por inatividade.
Version: 1.1.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_AUTOMACAO_MODULE_NAME', 'dps_automacao');
define('DPS_AUTOMACAO_VERSION', '1.2.0');

register_activation_hook(DPS_AUTOMACAO_MODULE_NAME, 'dps_automacao_activate');

// O helper é partilhado entre o controller, as vistas e o cron de follow-ups —
// tem de estar disponível antes de qualquer um deles correr.
get_instance()->load->helper(DPS_AUTOMACAO_MODULE_NAME . '/dps_automacao');

hooks()->add_action('admin_init', 'dps_automacao_ensure_schema');
hooks()->add_action('admin_init', 'dps_automacao_menu');
hooks()->add_action('admin_init', 'dps_automacao_permissions');
hooks()->add_action('after_cron_run', 'dps_automacao_cron_followups');
hooks()->add_action('before_task_description_section', 'dps_automacao_botao_converter_lead');

function dps_automacao_activate()
{
    require_once __DIR__ . '/install.php';
}

/**
 * Criação idempotente das tabelas do módulo. Partilhada entre a ativação
 * (install.php) e a migração automática (ensure_schema) para não haver duas
 * cópias do SQL a divergir com o tempo.
 */
function dps_automacao_criar_tabelas()
{
    $CI = &get_instance();

    $envios    = db_prefix() . 'dps_automacao_envios';
    $guioes    = db_prefix() . 'dps_automacao_guioes';
    $escolhas  = db_prefix() . 'dps_automacao_guiao_escolhas';
    $propostas = db_prefix() . 'dps_automacao_propostas';

    $CI->db->query("CREATE TABLE IF NOT EXISTS `{$envios}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `lead_id` INT NOT NULL,
        `staff_id` INT NOT NULL DEFAULT 0,
        `canal` VARCHAR(10) NOT NULL COMMENT 'whatsapp|email|sms',
        `tipo` VARCHAR(20) NOT NULL DEFAULT 'massa' COMMENT 'massa|followup|teste|proposta_massa',
        `marco` TINYINT UNSIGNED NULL COMMENT '7|15|30 — só para followups',
        `estado_lead` INT NULL COMMENT 'id do estado da lead no momento do envio',
        `mensagem` TEXT NOT NULL,
        `ok` TINYINT(1) NOT NULL DEFAULT 0,
        `detalhe` VARCHAR(255) NULL,
        `dateadded` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `lead_id` (`lead_id`),
        KEY `staff_id` (`staff_id`),
        KEY `followup_guarda` (`tipo`,`marco`,`lead_id`),
        KEY `dateadded` (`dateadded`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $CI->db->query("CREATE TABLE IF NOT EXISTS `{$guioes}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `nome` VARCHAR(150) NOT NULL,
        `descricao` TEXT NULL COMMENT 'o que a Sofia fará — visível ao comercial',
        `instrucoes` TEXT NULL COMMENT 'texto/instruções de execução',
        `ativo` TINYINT(1) NOT NULL DEFAULT 1,
        `created_by` INT NOT NULL DEFAULT 0,
        `dateadded` DATETIME NOT NULL,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        KEY `ativo` (`ativo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $CI->db->query("CREATE TABLE IF NOT EXISTS `{$escolhas}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `guiao_id` INT UNSIGNED NOT NULL,
        `staff_id` INT NOT NULL,
        `dateadded` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `staff_id` (`staff_id`) COMMENT 'uma escolha ativa por comercial — o upsert substitui',
        KEY `guiao_id` (`guiao_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // PDFs de propostas carregados pelos comerciais para envio em massa.
    // O ficheiro físico vive em uploads/dps_automacao/propostas/<filename>;
    // original_name é o nome que a lead vê (fileName no WhatsApp / anexo no email).
    $CI->db->query("CREATE TABLE IF NOT EXISTS `{$propostas}` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT NOT NULL COMMENT 'quem carregou — cada comercial só vê as suas',
        `filename` VARCHAR(64) NOT NULL COMMENT 'nome hex aleatório no disco',
        `original_name` VARCHAR(255) NOT NULL,
        `tamanho` INT UNSIGNED NOT NULL DEFAULT 0,
        `dateadded` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Instalações 1.0.0 criaram tipo como VARCHAR(10) — curto demais para
    // 'proposta_massa' (14 chars, ficaria truncado e a guarda de deduplicação
    // nunca encontraria o registo). O MODIFY é inócuo quando a coluna já está
    // certa, e isto só corre quando a versão do esquema muda.
    $CI->db->query("ALTER TABLE `{$envios}`
        MODIFY `tipo` VARCHAR(20) NOT NULL DEFAULT 'massa' COMMENT 'massa|followup|teste|proposta_massa'");

    /*
     * ÍNDICE CRÍTICO — não remover.
     *
     * leads_para_followup() calcula a última interação de cada lead com
     * MAX(date) sobre tbllead_activity_log. Essa tabela vem do Perfex apenas
     * com a chave primária: sem índice em leadid, cada lead obriga a varrer as
     * ~43.000 linhas do histórico. Com ~6.000 leads candidatas são ~257
     * milhões de leituras por execução.
     *
     * A 29/07/2026 isso demorava 12,5 SEGUNDOS por passagem e a Hostinger
     * classificou a conta como abuso de base de dados: aplicou null-routing e
     * revogou permissões, deixando os dois sites em baixo duas vezes no mesmo
     * dia. Com o índice, a mesma consulta passou a 14 ms (892x mais rápida) e
     * torna-se covering index — nem chega a ler a tabela.
     *
     * O índice é do Perfex, não nosso, por isso não pode viver num CREATE
     * TABLE: tem de ser acrescentado aqui, e verificado antes para o ALTER não
     * rebentar quando já existe.
     */
    $log = db_prefix() . 'lead_activity_log';
    $tem = $CI->db->query(
        "SHOW INDEX FROM `{$log}` WHERE Key_name = 'dps_leadid_date'"
    )->num_rows();

    if (!$tem) {
        $CI->db->query("ALTER TABLE `{$log}` ADD INDEX `dps_leadid_date` (`leadid`, `date`)");
        log_activity('dps_automacao: índice dps_leadid_date criado em ' . $log);
    }

    // O table_exists() de outros módulos pode ter deixado a lista de tabelas em
    // cache SEM as recém-criadas — e o resto deste mesmo pedido consulta-a.
    // (Armadilha documentada em dps_vendas_ensure_schema.)
    $CI->db->data_cache = [];
}

/**
 * Opções do módulo, semeadas só se ainda não existirem (add_option não
 * sobrepõe valores já gravados pelo utilizador).
 */
function dps_automacao_semear_opcoes()
{
    // Interruptores DESLIGADOS por omissão: nada é enviado até o admin decidir.
    add_option('dps_automacao_ativo', '0');
    add_option('dps_automacao_followups_ativo', '0');

    add_option(
        'dps_automacao_msg_followup_7',
        "Olá {nome},\n\nJá passou uma semana desde o nosso último contacto e não queríamos deixar de saber se ainda tem interesse. Se surgiu alguma dúvida, estou inteiramente à sua disposição.\n\nCom os melhores cumprimentos,\n{comercial}"
    );
    add_option(
        'dps_automacao_msg_followup_15',
        "Olá {nome},\n\nContinuamos à sua inteira disposição para o ajudar a encontrar a solução certa. Entretanto surgiram novidades que podem ser do seu interesse — diga-nos quando podemos falar.\n\nCom os melhores cumprimentos,\n{comercial}"
    );
    add_option(
        'dps_automacao_msg_followup_30',
        "Olá {nome},\n\nJá há algum tempo que não falamos e não queríamos que perdesse as oportunidades que temos neste momento. Se ainda fizer sentido para si, basta responder a este email e retomamos a conversa.\n\nCom os melhores cumprimentos,\n{comercial}"
    );
}

/**
 * Migração automática de esquema, sem obrigar a desativar/reativar o módulo.
 *
 * Corre no admin_init de TODAS as páginas do admin, por isso: sai já se a
 * versão bater certo, marca a opção ANTES do trabalho e embrulha tudo em
 * try/catch — uma exceção aqui bloquearia o CRM inteiro (padrão
 * dps_credito_setup_estados).
 */
function dps_automacao_ensure_schema()
{
    if (get_option('dps_automacao_schema_version') === DPS_AUTOMACAO_VERSION) {
        return;
    }

    // Marcar antes do trabalho: se algo falhar, falha uma vez e fica no log,
    // em vez de rebentar em todos os pedidos seguintes.
    update_option('dps_automacao_schema_version', DPS_AUTOMACAO_VERSION);

    try {
        dps_automacao_criar_tabelas();
        dps_automacao_semear_opcoes();
    } catch (\Throwable $e) {
        log_activity('dps_automacao_ensure_schema falhou: ' . $e->getMessage());
    }
}

/**
 * Menu lateral: parent "Automações" (slug sms-central) logo abaixo do
 * Sofia Calls (posição 49), com o Envio clássico e o módulo novo lá dentro.
 */
function dps_automacao_menu()
{
    // Sem portão nenhum, como o "Sofia Calls": o admin_init só corre para
    // staff autenticado, e é o padrão dos itens que aparecem a toda a gente.
    // (is_staff_member() chegou a esconder isto até ao admin — nunca mais.)
    $CI = &get_instance();

    // Parent "Automações", logo abaixo do Sofia Calls (posição 49).
    $CI->app_menu->add_sidebar_menu_item('sms-central', [
        'slug'     => 'sms-central',
        'name'     => 'Automações',
        'icon'     => 'fa fa-bolt',
        'position' => 50,
    ]);

    // O item "Envio" abre a página clássica das Automações DPS.
    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_sms',
        'name'     => 'Envio',
        'href'     => admin_url('automacoes_dps'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_envio_massa',
        'name'     => 'Envio em Massa',
        'href'     => admin_url('dps_automacao/envio_massa'),
        'position' => 2,
    ]);

    /*
     * O irmão do de cima, mas por estado de TAREFA. Fica aqui, ao lado dele:
     * é a mesma ideia aplicada a coisas diferentes, e em dois sítios do menu
     * obrigava a lembrar onde estava cada um.
     */
    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_envio_massa_tarefa',
        'name'     => 'Envio Massa Tarefa',
        'href'     => admin_url('dps_automacao/envio_massa_tarefa'),
        'position' => 3,
    ]);

    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_registo_tarefa',
        'name'     => 'Registo Envio Tarefa',
        'href'     => admin_url('dps_automacao/registo_envio_tarefa'),
        'position' => 4,
    ]);

    /*
     * O terceiro da família, e o único cujos destinatários já compraram:
     * acompanhamento de obra por empreendimento. Fica ao lado dos outros dois
     * porque a pergunta que leva a ele é a mesma — "quero escrever a um
     * conjunto de pessoas de uma vez".
     */
    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_envio_massa_cliente',
        'name'     => 'Envio Massa Cliente',
        'href'     => admin_url('dps_automacao/envio_massa_cliente'),
        'position' => 5,
    ]);

    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_proposta_massa',
        'name'     => 'Proposta em Massa',
        'href'     => admin_url('dps_automacao/proposta_massa'),
        'position' => 3,
    ]);

    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_guioes',
        'name'     => 'Guiões Sofia',
        'href'     => admin_url('dps_automacao/guioes'),
        'position' => 4,
    ]);

    $CI->app_menu->add_sidebar_children_item('sms-central', [
        'slug'     => 'dps_automacao_envios',
        'name'     => 'Registo de Envios',
        'href'     => admin_url('dps_automacao/envios'),
        'position' => 5,
    ]);

    if (is_admin()) {
        $CI->app_menu->add_sidebar_children_item('sms-central', [
            'slug'     => 'dps_automacao_definicoes',
            'name'     => 'Definições',
            'href'     => admin_url('dps_automacao/definicoes'),
            'position' => 6,
        ]);
    }
}

/**
 * Capacidades do módulo. A fronteira real entre comerciais é o filtro
 * assigned=staffid imposto no servidor; estas capacidades servem para o admin
 * poder retirar o acesso ao módulo a alguém, se um dia fizer falta.
 */
function dps_automacao_permissions()
{
    register_staff_capabilities('dps_automacao', [
        'view' => 'Aceder à Automação (envio em massa, guiões e registo)',
    ], 'DPS Automação');
}

/**
 * Follow-ups automáticos aos 7/15/30 dias sem interação, via cron do Perfex.
 *
 * Corre sem sessão. Nada sai sem os DOIS interruptores ligados. Cada marco
 * apanha só a sua janela (>= marco e < marco seguinte) para uma lead antiga
 * não receber os três emails de rajada quando a funcionalidade for ligada.
 * O registo em tbldps_automacao_envios é inserido ANTES do envio: é a guarda
 * de idempotência contra corridas de cron concorrentes.
 */
function dps_automacao_cron_followups($manualmente = null)
{
    if (get_option('dps_automacao_ativo') !== '1' || get_option('dps_automacao_followups_ativo') !== '1') {
        return;
    }

    $CI     = &get_instance();
    $envios = db_prefix() . 'dps_automacao_envios';

    // O cron pode correr antes da ativação manual ter criado as tabelas.
    if (!$CI->db->table_exists($envios)) {
        return;
    }

    $CI->load->model(DPS_AUTOMACAO_MODULE_NAME . '/dps_automacao_model');

    // Limite por corrida: proteger o SMTP e repartir listas grandes por várias
    // corridas do cron em vez de um pico único.
    $limite_total = 50;
    $enviados     = 0;

    // [marco, marco seguinte] — o último não tem teto.
    $janelas = [[7, 15], [15, 30], [30, null]];

    foreach ($janelas as $janela) {
        $marco    = $janela[0];
        $seguinte = $janela[1];

        if ($enviados >= $limite_total) {
            break;
        }

        $template = (string) get_option('dps_automacao_msg_followup_' . $marco);
        if (trim($template) === '') {
            continue;
        }

        try {
            $leads = $CI->dps_automacao_model->leads_para_followup($marco, $limite_total - $enviados, $seguinte);
        } catch (\Throwable $e) {
            log_activity('dps_automacao follow-ups (marco ' . $marco . ') falhou: ' . $e->getMessage());
            continue;
        }

        foreach ($leads as $lead) {
            /*
             * A CAIXA É A DO COMERCIAL DA LEAD.
             *
             * Esta chamada ia sem staff_id, e sem ele o envio caía no SMTP
             * geral do CRM — mas o registo era gravado em nome do comercial.
             * Resultado: milhares de "falhas do Breno" que nunca saíram da
             * caixa dele, e uma única caixa a aguentar os follow-ups de toda
             * a gente. Corrigido a 04/08/2026.
             */
            $dono = (int) ($lead['assigned'] ?? 0);

            // Quota cheia nesta caixa: fica para a passagem seguinte do cron.
            // NÃO se marca como falhado — não falhou, ainda não foi tentado.
            if ($dono > 0 && !dps_automacao_pode_enviar($dono)) {
                continue;
            }

            $comercial = trim((string) ($lead['comercial'] ?? ''));
            if ($comercial === '') {
                $comercial = get_option('companyname') ?: 'A nossa equipa';
            }

            // Substituir variáveis sobre texto simples; o escape para HTML
            // vem DEPOIS — na ordem inversa, um nome com HTML injetaria markup.
            $texto = dps_automacao_render_vars($template, $lead['name'], $comercial);

            // Guarda inserida ANTES do envio: uma segunda corrida concorrente
            // do cron já vê este registo e salta a lead.
            $registo_id = $CI->dps_automacao_model->registar_envio([
                'lead_id'     => (int) $lead['id'],
                'staff_id'    => (int) $lead['assigned'],
                'canal'       => 'email',
                'tipo'        => 'followup',
                'marco'       => $marco,
                'estado_lead' => isset($lead['status']) ? (int) $lead['status'] : null,
                'mensagem'    => $texto,
                'ok'          => 0,
                'detalhe'     => 'Em processamento',
            ]);

            $assunto = 'Continuamos à sua disposição — ' . (get_option('companyname') ?: 'Grupo DPS');
            $corpo   = nl2br(html_escape($texto));

            $ok = dps_automacao_enviar_email_lead($lead['email'], $assunto, $corpo, $dono ?: null);

            $CI->db->where('id', $registo_id)->update($envios, [
                'ok'      => $ok ? 1 : 0,
                'detalhe' => $ok
                    ? 'Follow-up de ' . $marco . ' dias enviado'
                    : 'Falha no envio SMTP',
            ]);

            $enviados++;
            if ($enviados >= $limite_total) {
                break;
            }
        }
    }
}

/**
 * Fila do Envio Massa Tarefa.
 *
 * O fornecedor de email não deixa passar mais de 100 mensagens por envio. Um
 * estado com 1.756 destinatários não cabe num disparo: manda-se o primeiro
 * lote e o resto fica aqui, agendado de 24 em 24 horas, até acabar.
 *
 * A alternativa — mandar tudo e ver o que passa — queima a reputação da caixa
 * e faz o fornecedor recusar o lote inteiro, não só o excedente.
 */
function dps_automacao_criar_fila_tarefa()
{
    $CI = &get_instance();
    $t  = db_prefix() . 'dps_envio_tarefa_fila';

    if ($CI->db->table_exists($t)) {
        return;
    }

    $CI->db->query("CREATE TABLE IF NOT EXISTS `{$t}` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `lote` VARCHAR(40) NOT NULL,
        `staff_id` INT NOT NULL,
        `email` VARCHAR(190) NOT NULL,
        `nome` VARCHAR(190) NULL,
        `assunto` VARCHAR(255) NOT NULL,
        `mensagem` TEXT NOT NULL,
        `anexo` VARCHAR(255) NULL,
        `anexo_nome` VARCHAR(190) NULL,
        `agendado_para` DATETIME NOT NULL,
        `enviado_em` DATETIME NULL,
        `estado` VARCHAR(20) NOT NULL DEFAULT 'pendente',
        `detalhe` VARCHAR(255) NULL,
        KEY `por_enviar` (`estado`, `agendado_para`),
        KEY `por_lote` (`lote`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

hooks()->add_action('admin_init', 'dps_automacao_criar_fila_tarefa');

/**
 * Despacha a fila do Envio Massa Tarefa: 100 por corrida, o tecto do fornecedor.
 *
 * Corre no cron do Perfex. Cada linha guarda o seu próprio texto e o seu
 * próprio remetente, por isso um lote continua a sair como foi aprovado
 * mesmo que entretanto alguém mude o ecrã ou saia da empresa.
 */
function dps_automacao_fila_tarefa_cron()
{
    $CI = &get_instance();
    $CI->load->model('dps_automacao/dps_automacao_model');

    $linhas = $CI->dps_automacao_model->fila_tarefa_por_enviar(100);
    if (empty($linhas)) {
        return;
    }

    $lotes = [];

    foreach ($linhas as $l) {
        /*
         * Quota da caixa deste comercial. Se estiver cheia, a linha fica
         * PENDENTE e sai na passagem seguinte — o cron corre de 5 em 5
         * minutos, por isso um lote grande escoa sozinho ao ritmo que a
         * Hostinger aceita, em vez de falhar de uma vez.
         */
        if (!dps_automacao_pode_enviar((int) $l['staff_id'])) {
            continue;
        }

        $nome_com = get_staff_full_name((int) $l['staff_id'])
            ?: (get_option('companyname') ?: 'A nossa equipa');

        $texto = dps_automacao_render_vars($l['mensagem'], (string) $l['nome'], $nome_com);

        if (!empty($l['anexo']) && is_file($l['anexo'])) {
            $ok = dps_automacao_enviar_email_proposta(
                $l['email'], (string) $l['nome'], $l['assunto'], $texto,
                $l['anexo'], (string) $l['anexo_nome'], (int) $l['staff_id']
            );
        } else {
            $ok = dps_automacao_enviar_email_lead(
                $l['email'], $l['assunto'], nl2br(html_escape($texto)), (int) $l['staff_id']
            );
        }

        $CI->dps_automacao_model->fila_tarefa_marcar($l['id'], $ok, $ok ? '' : 'envio recusado');

        $lotes[$l['lote']] = $l['anexo'];

        // Mesma pausa do envio manual: um lote seguido sem respirar contra o
        // SMTP e a caixa passa a ser tratada como spam.
        usleep(200000);
    }

    /*
     * Anexo apagado só quando o lote inteiro acabou. Enquanto houver linhas
     * pendentes, o ficheiro tem de continuar lá — foi por isso que o envio
     * manual deixou de o apagar quando fica coisa agendada.
     */
    foreach ($lotes as $lote => $anexo) {
        if ($anexo && is_file($anexo) && !$CI->dps_automacao_model->fila_tarefa_lote_pendente($lote)) {
            @unlink($anexo);
        }
    }
}

hooks()->add_action('after_cron_run', 'dps_automacao_fila_tarefa_cron');

/*
 * MATURAÇÃO DOS VIP — o interesse arrefece sozinho.
 *
 * Regra do dono (05/08/2026): sem contacto, a lead desce de degrau.
 *
 *   VIP 1  →  VIP 2   ao fim de 2 semanas
 *   VIP 2  →  VIP 3   ao fim de 3 semanas
 *   VIP 3  →  MORNO   ao fim de 4 semanas
 *
 * O RELÓGIO É O ÚLTIMO CONTACTO, não a data em que entrou no estado: basta um
 * telefonema para a lead voltar ao princípio, que é o comportamento que se
 * quer — o que faz a lead descer é o silêncio, não o calendário.
 *
 * Por isso os limiares são absolutos e contados sempre da mesma origem (14,
 * 21, 28 dias). Uma lead nunca contactada atravessa os três degraus sozinha,
 * um por semana, sem precisar de saber por onde passou.
 *
 * Nunca contactada: conta-se desde a criação. Sem isto, as leads sem
 * lastcontact ficavam eternamente em VIP 1 — que são precisamente as mais
 * esquecidas.
 */
if (!function_exists('dps_automacao_maturacao_regras')) {
    function dps_automacao_maturacao_regras()
    {
        return [
            ['de' => 17, 'para' => 14, 'dias' => 14, 'nome' => 'VIP 1 → VIP 2'],
            ['de' => 14, 'para' => 18, 'dias' => 21, 'nome' => 'VIP 2 → VIP 3'],
            ['de' => 18, 'para' => 2,  'dias' => 28, 'nome' => 'VIP 3 → Morno'],
        ];
    }
}

if (!function_exists('dps_automacao_maturar_vips')) {
    /**
     * @param  bool $aplicar  false devolve o que MUDARIA, sem escrever nada
     * @return array  ['linhas' => [...], 'total' => n]
     */
    function dps_automacao_maturar_vips($aplicar = false)
    {
        $CI = &get_instance();
        $CI->load->model('leads_model');

        $regras = dps_automacao_maturacao_regras();
        $origem = array_column($regras, 'de');

        /*
         * COALESCE: sem último contacto vale a data de criação. Sem isto, as
         * leads que nunca foram contactadas — precisamente as mais esquecidas —
         * ficavam eternamente em VIP 1.
         */
        $leads = $CI->db->query(
            'SELECT id, name, status, assigned,
                    DATEDIFF(NOW(), COALESCE(lastcontact, dateadded)) AS dias
               FROM ' . db_prefix() . 'leads
              WHERE status IN (' . implode(',', array_map('intval', $origem)) . ')
                AND lost = 0 AND junk = 0
                AND date_converted IS NULL
           ORDER BY id ASC'
        )->result_array();

        $nomes = [];
        foreach ($CI->db->select('id, name')->get(db_prefix() . 'leads_status')->result_array() as $e) {
            $nomes[(int) $e['id']] = $e['name'];
        }

        $linhas = [];

        foreach ($leads as $l) {
            $estado = (int) $l['status'];
            $dias   = (int) $l['dias'];
            $inicio = $estado;

            /*
             * DESCE A ESCADA TODA DE UMA VEZ, e grava só o destino.
             *
             * Uma lead parada há 200 dias pertence ao Morno, não ao degrau
             * seguinte — fazê-la esperar três semanas por cada degrau seria
             * fingir que o tempo ainda não passou. Mas gravar cada degrau
             * intermédio enchia o histórico de linhas que ninguém pediu e
             * multiplicava as escritas por três. Calcula-se o fim, escreve-se
             * uma vez.
             */
            $seguro = 0;

            do {
                $mudou = false;

                foreach ($regras as $r) {
                    if ($estado === (int) $r['de'] && $dias >= (int) $r['dias']) {
                        $estado = (int) $r['para'];
                        $mudou  = true;
                        break;
                    }
                }
            } while ($mudou && ++$seguro < 10);

            if ($estado === $inicio) {
                continue;
            }

            $linhas[] = [
                'lead_id'  => (int) $l['id'],
                'nome'     => $l['name'],
                'de'       => $inicio,
                'para'     => $estado,
                'regra'    => ($nomes[$inicio] ?? $inicio) . ' → ' . ($nomes[$estado] ?? $estado),
                'dias'     => $dias,
                'assigned' => (int) $l['assigned'],
            ];

            if (!$aplicar) {
                continue;
            }

            /*
             * Escreve-se pelo modelo do Perfex — é o que dispara o
             * lead_status_changed e mantém o histórico coerente. Um UPDATE à
             * mão punha o estado certo e deixava tudo o resto por trás.
             */
            $CI->leads_model->update_lead_status([
                'status' => $estado,
                'leadid' => (int) $l['id'],
            ]);

            $CI->leads_model->log_lead_activity(
                (int) $l['id'],
                '⏳ Maturação automática: ' . ($nomes[$inicio] ?? $inicio) . ' → '
                    . ($nomes[$estado] ?? $estado) . ' — ' . $dias . ' dias sem contacto.'
            );
        }

        return ['linhas' => $linhas, 'total' => count($linhas)];
    }
}

if (!function_exists('dps_automacao_maturacao_cron')) {
    function dps_automacao_maturacao_cron()
    {
        /*
         * Uma vez por dia chega: os limiares são semanas, e correr a cada
         * passagem do cron só multiplicava escritas sem mudar resultado.
         */
        if (get_option('dps_automacao_maturacao_ultima') === date('Y-m-d')) {
            return;
        }

        update_option('dps_automacao_maturacao_ultima', date('Y-m-d'));

        $r = dps_automacao_maturar_vips(true);

        if ($r['total'] > 0) {
            log_activity('DPS Automação: maturação de VIP — ' . $r['total'] . ' lead(s) mudaram de estado.');
        }
    }
}

hooks()->add_action('after_cron_run', 'dps_automacao_maturacao_cron');

/*
 * Estados de tarefa do Perfex. 1 = Não iniciada, 4 = Em progresso.
 * Ver Dps_automacao_model::tarefa_em_progresso().
 */
/*
 * Tecto de envios por DIA e por caixa de correio.
 *
 * 100 desde 05/08/2026: o dono subiu o limite das caixas de toda a gente e
 * pediu o mesmo tecto em todo o lado. Era 90 — dez de margem para os avisos e
 * emails avulsos que saem pela mesma caixa fora do módulo.
 *
 * Se voltarem a aparecer falhas de SMTP ao fim do dia, é aqui que se baixa:
 * este número é o travão, e a razão de existir é não descobrir o limite do
 * fornecedor com meia campanha por entregar.
 * Ver dps_automacao_quota_restante().
 */
defined('DPS_AUTOMACAO_LIMITE_DIA') || define('DPS_AUTOMACAO_LIMITE_DIA', 100);

defined('DPS_AUTOMACAO_TAREFA_NAO_INICIADA') || define('DPS_AUTOMACAO_TAREFA_NAO_INICIADA', 1);
defined('DPS_AUTOMACAO_TAREFA_EM_PROGRESSO') || define('DPS_AUTOMACAO_TAREFA_EM_PROGRESSO', 4);

hooks()->add_action('admin_init', 'dps_automacao_coluna_task_id');

/**
 * A fila passou a guardar a que tarefa pertence cada envio.
 *
 * CREATE TABLE IF NOT EXISTS não acrescenta colunas a uma tabela que já
 * exista — daí o ALTER explícito, feito só quando a coluna falta.
 */
function dps_automacao_coluna_task_id()
{
    static $feito = false;
    if ($feito) {
        return;
    }
    $feito = true;

    $CI = &get_instance();
    $t  = db_prefix() . 'dps_envio_tarefa_fila';

    if (!$CI->db->table_exists($t)) {
        return;
    }

    foreach ($CI->db->field_data($t) as $f) {
        if ($f->name === 'task_id') {
            return;
        }
    }

    $CI->db->query("ALTER TABLE `{$t}` ADD `task_id` INT(11) NULL DEFAULT NULL AFTER `staff_id`");
}

/**
 * Botão "Converter tarefa em lead", dentro da janela da tarefa.
 *
 * As tarefas da Sofia nascem de uma chamada e ficam sem lead: não entram nos
 * funis, nos filtros nem nas automações. Este botão passa-as para lá.
 *
 * Só aparece onde faz sentido. Se a tarefa já veio de uma lead, o que se
 * mostra é o caminho de volta — converter outra vez só criava uma segunda
 * ficha da mesma pessoa.
 *
 * Vai um POST por JavaScript e não um formulário: aqui dentro já há formulários
 * do Perfex, e um formulário dentro de outro é descartado pelo browser sem
 * dizer nada.
 */
function dps_automacao_botao_converter_lead($tarefa)
{
    if (empty($tarefa) || !is_staff_logged_in() || !staff_can('create', 'leads')) {
        return;
    }

    $tem_lead = (isset($tarefa->rel_type) && $tarefa->rel_type === 'lead' && (int) $tarefa->rel_id > 0);
    ?>
    <div class="mbot15" id="dps-converter-lead-<?php echo (int) $tarefa->id; ?>">
        <?php if ($tem_lead) { ?>
            <a href="<?php echo admin_url('leads/index/' . (int) $tarefa->rel_id); ?>"
               class="text-muted" style="font-size:13px;">
                <i class="fa fa-user tw-mr-1"></i> Ver a lead desta tarefa
            </a>
        <?php } else { ?>
            <button type="button" class="btn btn-default btn-sm"
                    data-tarefa="<?php echo (int) $tarefa->id; ?>"
                    onclick="dpsConverterTarefaEmLead(this);">
                <i class="fa fa-user-plus tw-mr-1"></i> Converter tarefa em lead
            </button>
        <?php } ?>
    </div>
    <script>
    window.dpsConverterTarefaEmLead = function (botao) {
        var tarefa = $(botao).data('tarefa');
        if (!confirm('Criar uma lead a partir desta tarefa?')) { return; }

        var texto = $(botao).html();
        $(botao).prop('disabled', true).html('A converter...');

        var envio = { taskid: tarefa };
        if (typeof csrfData !== 'undefined') { envio[csrfData.token_name] = csrfData.hash; }

        $.post(admin_url + 'dps_automacao/converter_em_lead/' + tarefa, envio)
            .done(function (r) {
                try { r = JSON.parse(r); } catch (e) { r = null; }
                if (r && r.sucesso) { window.location = r.url; return; }
                alert_float('danger', (r && r.mensagem) || 'Não foi possível converter a tarefa.');
                if (r && r.url) { window.location = r.url; return; }
                $(botao).prop('disabled', false).html(texto);
            })
            .fail(function (xhr) {
                alert_float('danger', 'Falha ao converter a tarefa (erro ' + xhr.status + ').');
                $(botao).prop('disabled', false).html(texto);
            });
    };
    </script>
    <?php
}

/* =====================================================================
 * "Não atendeu" escrito numa nota muda o estado da lead
 *
 * O comercial liga, ninguém atende, e escreve-o na nota da lead. O estado
 * ficava na mesma e a lead continuava a contar como se estivesse viva —
 * havia 2384 notas a dizer "não atendeu" e leads em "Propostas Enviadas"
 * por baixo delas. Pedido do dono (06/08/2026): escrever "não atendeu"
 * passa a lead de imediato para "Nao atendeu. Religar" (estado #7, que já
 * existia).
 * ================================================================== */

/** O estado que já existia no CRM. Não se cria nenhum novo. */
define('DPS_AUTOMACAO_ESTADO_RELIGAR', 7);

/*
 * Estados de negócio fechado. Uma nota não despromove um negócio ganho:
 * se alguém escrever "não atendeu" numa lead que já está em contrato ou
 * concretizada, é de outra coisa que está a falar.
 */
function dps_automacao_estados_intocaveis()
{
    return [10, 13]; // PARA CONTRATO, CONCRETIZADO
}

/**
 * A nota diz que não atenderam?
 *
 * O texto vem por nl2br() e pode trazer HTML, entidades e espaços duros
 * colados do WhatsApp — daí limpar tudo antes de comparar. Apanha "não
 * atendeu", "nao atende", "não atenderam" e o hífen pelo meio.
 */
function dps_automacao_nota_diz_nao_atendeu($texto)
{
    $t = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES, 'UTF-8');
    $t = str_replace(["\xC2\xA0", "\xE2\x80\x91", "\xE2\x80\x93"], ' ', $t);

    return (bool) preg_match('/n[ãaáâ]o\s*[-–]?\s*atende(u|ram|)\b/iu', $t);
}

hooks()->add_action('note_created', 'dps_automacao_nota_muda_estado');

/*
 * ATENÇÃO: o do_action() do Perfex (application/third_party/action_hooks.php)
 * só entrega UM argumento ao callback, mesmo quando quem dispara passa dois.
 * O Misc_model chama do_action('note_created', $insert_id, $data) mas o $data
 * fica pelo caminho — daí ir buscar a nota à base de dados em vez de a
 * receber. Escrever a assinatura com dois parâmetros compilava e nunca
 * funcionava.
 */
function dps_automacao_nota_muda_estado($note_id)
{
    $CI = &get_instance();

    $nota = $CI->db->select('rel_type, rel_id, description')
        ->where('id', (int) $note_id)
        ->get(db_prefix() . 'notes')
        ->row();

    if (!$nota || $nota->rel_type !== 'lead') {
        return;
    }

    $lead_id = (int) $nota->rel_id;
    if (!$lead_id || !dps_automacao_nota_diz_nao_atendeu($nota->description)) {
        return;
    }

    $lead = $CI->db->select('id, status, lastcontact')
        ->where('id', $lead_id)
        ->get(db_prefix() . 'leads')
        ->row();

    if (!$lead) {
        return;
    }

    $de = (int) $lead->status;
    if ($de === DPS_AUTOMACAO_ESTADO_RELIGAR || in_array($de, dps_automacao_estados_intocaveis(), true)) {
        return;
    }

    $CI->db->where('id', $lead_id);
    $CI->db->update(db_prefix() . 'leads', [
        'status'             => DPS_AUTOMACAO_ESTADO_RELIGAR,
        'last_status_change' => date('Y-m-d H:i:s'),
        /*
         * A tentativa de contacto conta como contacto: sem isto, a
         * maturação dos VIP (que conta a partir do último contacto)
         * continuava a envelhecer uma lead a quem se acabou de ligar.
         */
        'lastcontact'        => date('Y-m-d H:i:s'),
    ]);

    // Mesma escrituração que o Perfex faz quando o estado muda à mão,
    // para a ficha da lead mostrar a alteração no histórico.
    $CI->load->model('leads_model');

    $nome_de   = $CI->db->select('name')->where('id', $de)->get(db_prefix() . 'leads_status')->row();
    $nome_para = $CI->db->select('name')->where('id', DPS_AUTOMACAO_ESTADO_RELIGAR)
        ->get(db_prefix() . 'leads_status')->row();

    $CI->leads_model->log_lead_activity($lead_id, 'not_lead_activity_status_updated', false, serialize([
        get_staff_full_name(),
        $nome_de ? $nome_de->name : $de,
        $nome_para ? $nome_para->name : DPS_AUTOMACAO_ESTADO_RELIGAR,
    ]));

    hooks()->do_action('lead_status_changed', [
        'lead_id'    => $lead_id,
        'old_status' => $de,
        'new_status' => DPS_AUTOMACAO_ESTADO_RELIGAR,
    ]);
}
