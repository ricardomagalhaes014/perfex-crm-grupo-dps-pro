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
    /*
     * Pedidos de apoio à direcção.
     *
     * O botão "Suporte" já criava uma tarefa para o Cláudio, mas uma tarefa
     * não guarda a resposta nem o estado do pedido, e o comercial ficava sem
     * saber em que pé estava o seu. Isto é o registo do pedido em si: quem
     * pediu, o contexto, o que a direcção respondeu e onde está.
     */
    $suporte = db_prefix() . 'dps_suporte';
    $CI->db->query("CREATE TABLE IF NOT EXISTS `{$suporte}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `lead_id` INT(11) NOT NULL,
        `pedinte` INT(11) NOT NULL,
        `destino` INT(11) NOT NULL,
        `contexto` TEXT NULL,
        `estado` VARCHAR(20) NOT NULL DEFAULT 'novo',
        `resposta` TEXT NULL,
        `respondido_por` INT(11) NULL,
        `respondido_em` DATETIME NULL,
        `tarefa_id` INT(11) NULL,
        `criado_em` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `dps_sup_destino` (`destino`, `estado`),
        KEY `dps_sup_pedinte` (`pedinte`),
        KEY `dps_sup_lead` (`lead_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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
 * Suporte no menu principal, entre o Funil e o DPS Crédito.
 *
 * Estava dentro das Automações e ninguém lá ia: um pedido de ajuda para
 * fechar um negócio é gente à espera de resposta, não uma listagem que se
 * consulta quando calha. Fica à vista, com o contador do que falta responder.
 *
 * Registado à parte do resto do menu do módulo porque é um item de topo e não
 * um filho das Automações.
 */
hooks()->add_action('admin_init', 'dps_automacao_menu_suporte');
function dps_automacao_menu_suporte()
{
    $CI = &get_instance();

    $por_responder = 0;

    if ($CI->db->table_exists(db_prefix() . 'dps_suporte')) {
        $eu = (int) get_staff_user_id();
        // O contador conta o que ESTE utilizador tem por tratar: os pedidos
        // que lhe foram dirigidos e os que ele próprio fez e continuam sem
        // resposta. Um número que não é meu não me diz nada.
        $por_responder = (int) $CI->db->query(
            'SELECT COUNT(*) AS n FROM ' . db_prefix() . 'dps_suporte'
            . ' WHERE estado = "novo" AND (destino = ? OR pedinte = ?)',
            [$eu, $eu]
        )->row()->n;
    }

    $CI->app_menu->add_sidebar_menu_item('dps-suporte', [
        'name'     => 'Suporte',
        'href'     => admin_url('dps_automacao/suporte'),
        'icon'     => 'fa fa-life-ring menu-icon',
        'position' => 18,
        // O aside lê 'value' e 'type' — com 'name'/'class' o contador nunca
        // chegava a aparecer (application/views/admin/includes/aside.php).
        'badge'    => $por_responder > 0
            ? ['value' => $por_responder, 'type' => 'danger']
            : [],
    ]);
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
/*
 * 0 = SEM LIMITE. Decisão do dono (06/08/2026): as caixas de todos os
 * comerciais foram aumentadas e o tecto deixou de fazer sentido — retirado de
 * todas as automações.
 *
 * Se voltarem a aparecer recusas do SMTP ao fim do dia, é aqui que se põe um
 * número outra vez: basta trocar o 0 pelo tecto desejado e tudo volta a
 * respeitá-lo, sem mexer em mais nada.
 */
defined('DPS_AUTOMACAO_LIMITE_DIA') || define('DPS_AUTOMACAO_LIMITE_DIA', 0);

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

/** Estados que já existiam no CRM. Não se cria nenhum novo. */
define('DPS_AUTOMACAO_ESTADO_NOVOS',   4);
define('DPS_AUTOMACAO_ESTADO_RELIGAR', 7);
define('DPS_AUTOMACAO_ESTADO_VIP1',   17);
defined('DPS_AUTOMACAO_ESTADO_SEM_INTERESSE') || define('DPS_AUTOMACAO_ESTADO_SEM_INTERESSE', 5);
defined('DPS_AUTOMACAO_ESTADO_CONCRETIZADO')  || define('DPS_AUTOMACAO_ESTADO_CONCRETIZADO', 13);

/**
 * A nota diz que se enviaram as disponibilidades?
 *
 * Apanha "enviadas disponíveis", "enviei as disponibilidades", "enviada
 * disponibilidade" e o que estiver pelo meio. O envio pelo botão já promove a
 * lead sozinho; isto é para quando o comercial o faz por fora e escreve a nota
 * à mão. Pedido do dono (11/08/2026).
 */
function dps_automacao_nota_diz_disponiveis($texto)
{
    $t = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES, 'UTF-8');
    $t = str_replace("\xC2\xA0", ' ', $t);

    /*
     * O radical é "envi", não "envia": "enviei" não tem o 'a', e era assim que
     * "enviei as disponibilidades" escapava à regra.
     */
    return (bool) preg_match('/\benvi\w*\s+(?:as?\s+)?dispon/iu', $t);
}

/**
 * Estados que já estão em VIP 1 ou à frente dele.
 *
 * Enviar disponibilidades é um passo em frente, mas quem já tem proposta
 * enviada ou contrato está mais adiante — descê-lo a VIP 1 seria estragar o
 * funil para registar um progresso.
 */
/**
 * A nota diz que o cliente não tem interesse?
 *
 * Apanha as formas como isto aparece escrito de verdade nas notas —
 * "sem interesse", "não tem interesse", "nao tem interesse", "sem qualquer
 * interesse", "não está interessado". Não apanha "sem interesse no Boavista,
 * sugeri Gaia": aí há uma alternativa em cima da mesa e a lead está viva.
 */
function dps_automacao_nota_diz_sem_interesse($texto)
{
    $t = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES, 'UTF-8');
    $t = str_replace("\xC2\xA0", ' ', $t);
    $t = mb_strtolower($t, 'UTF-8');

    // "sem interesse no X, sugeri Y" — mudou de produto, não desistiu.
    if (preg_match('/interesse\s+(?:n[oa]s?|em|pelo|pela)\s+\S+.*\b(sugeri|suger|alternativ|propus|mostrei|indiquei)/iu', $t)) {
        return false;
    }

    return (bool) preg_match(
        '/\b(?:sem\s+(?:qualquer\s+)?interesse'
        . '|n[ãa]o\s+(?:tem|tinha|ten[hd]o|demonstrou)\s+interesse'
        . '|n[ãa]o\s+(?:est[áa]|estava|se\s+mostrou)\s+interessad[oa]?'
        . '|desistiu|n[ãa]o\s+quer\s+(?:avan[çc]ar|nada|saber))\b/iu',
        $t
    );
}

function dps_automacao_estados_a_frente_de_vip1()
{
    return [17, 14, 18, 20, 21, 10, 13];
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
    if (! $lead_id) {
        return;
    }

    /*
     * Este teste estava aqui a exigir "não atendeu" ANTES de se olhar para as
     * outras regras — e por isso a nota "enviadas disponibilidades" saía por
     * esta porta e nunca chegava à regra do VIP 1. Só funcionava pelo botão,
     * que promove por outro caminho. Cada regra decide por si.
     */
    $diz_nao_atendeu  = dps_automacao_nota_diz_nao_atendeu($nota->description);
    $diz_disponiveis  = dps_automacao_nota_diz_disponiveis($nota->description);
    $diz_sem_interesse = dps_automacao_nota_diz_sem_interesse($nota->description);

    if (! $diz_nao_atendeu && ! $diz_disponiveis && ! $diz_sem_interesse) {
        return;
    }

    $lead = $CI->db->select('id, status, lastcontact')
        ->where('id', $lead_id)
        ->get(db_prefix() . 'leads')
        ->row();

    if (!$lead) {
        return;
    }

    /*
     * "Enviadas disponibilidades" promove a lead a VIP 1.
     *
     * Vem antes da regra do "não atendeu" porque são coisas diferentes: uma
     * puxa a lead para a frente, a outra devolve-a à fila de reconctacto.
     */
    if ($diz_disponiveis) {
        $actual = (int) $lead->status;

        if (! in_array($actual, dps_automacao_estados_a_frente_de_vip1(), true)) {
            $CI->db->where('id', $lead_id);
            $CI->db->update(db_prefix() . 'leads', [
                'status'             => DPS_AUTOMACAO_ESTADO_VIP1,
                'last_status_change' => date('Y-m-d H:i:s'),
                'lastcontact'        => date('Y-m-d H:i:s'),
            ]);

            $CI->load->model('leads_model');
            $de   = $CI->db->select('name')->where('id', $actual)->get(db_prefix() . 'leads_status')->row();
            $para = $CI->db->select('name')->where('id', DPS_AUTOMACAO_ESTADO_VIP1)
                ->get(db_prefix() . 'leads_status')->row();

            $CI->leads_model->log_lead_activity($lead_id, 'not_lead_activity_status_updated', false, serialize([
                get_staff_full_name(),
                $de ? $de->name : $actual,
                $para ? $para->name : DPS_AUTOMACAO_ESTADO_VIP1,
            ]));

            hooks()->do_action('lead_status_changed', [
                'lead_id'    => $lead_id,
                'old_status' => $actual,
                'new_status' => DPS_AUTOMACAO_ESTADO_VIP1,
            ]);
        }

        return;   // uma nota não é as duas coisas ao mesmo tempo
    }

    /*
     * "Sem interesse" escrito na nota fecha a lead.
     *
     * Ao contrário do "não atendeu", esta vale a partir de QUALQUER estado: um
     * cliente que diz que não quer, não quer — venha ele de Novos ou de
     * proposta enviada. Pedido do dono (13/08/2026).
     *
     * Só não mexe em quem já concretizou: essa lead já é venda e o "sem
     * interesse" só pode ser de outra coisa.
     */
    if ($diz_sem_interesse) {
        $actual = (int) $lead->status;

        if ($actual !== DPS_AUTOMACAO_ESTADO_SEM_INTERESSE
            && $actual !== DPS_AUTOMACAO_ESTADO_CONCRETIZADO) {

            $CI->db->where('id', $lead_id);
            $CI->db->update(db_prefix() . 'leads', [
                'status'             => DPS_AUTOMACAO_ESTADO_SEM_INTERESSE,
                'last_status_change' => date('Y-m-d H:i:s'),
                'lastcontact'        => date('Y-m-d H:i:s'),
            ]);

            $CI->load->model('leads_model');
            $de   = $CI->db->select('name')->where('id', $actual)->get(db_prefix() . 'leads_status')->row();
            $para = $CI->db->select('name')->where('id', DPS_AUTOMACAO_ESTADO_SEM_INTERESSE)
                ->get(db_prefix() . 'leads_status')->row();

            $CI->leads_model->log_lead_activity($lead_id, 'not_lead_activity_status_updated', false, serialize([
                get_staff_full_name(),
                $de ? $de->name : $actual,
                $para ? $para->name : DPS_AUTOMACAO_ESTADO_SEM_INTERESSE,
            ]));

            hooks()->do_action('lead_status_changed', [
                'lead_id'    => $lead_id,
                'old_status' => $actual,
                'new_status' => DPS_AUTOMACAO_ESTADO_SEM_INTERESSE,
            ]);
        }

        return;
    }

    /*
     * SÓ a partir de "Novos". Regra do dono (06/08/2026).
     *
     * A lead que já andou para a frente — em conversação, VIP, proposta
     * enviada — não recua por causa de uma nota. Aí "não atendeu" é o relato
     * de uma chamada dentro de um acompanhamento que continua, e despromovê-la
     * apagava o trabalho feito e estragava o funil. A regra existe para a lead
     * nova em que se tentou o primeiro contacto e ninguém atendeu.
     */
    $de = (int) $lead->status;
    if ($de !== DPS_AUTOMACAO_ESTADO_NOVOS) {
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

/* =====================================================================
 * Botão "Agenda" na coluna Funções + aviso 30 minutos antes
 *
 * O comercial liga, não atende, e quer voltar a ligar amanhã às 10h. Antes
 * tinha de abrir a lead, ir ao separador dos lembretes e preencher o
 * formulário do Perfex. Agora marca-o na linha da tabela.
 *
 * O lembrete é um lembrete do Perfex e não uma invenção nossa: aparece na
 * ficha da lead, na lista de lembretes, e o módulo do Google leva-o ao
 * calendário do telemóvel de quem o marcou.
 *
 * Pedido do dono (06/08/2026).
 * ================================================================== */

hooks()->add_action('admin_init', 'dps_automacao_lembrete_schema');

function dps_automacao_lembrete_schema()
{
    static $feito = false;
    if ($feito) {
        return;
    }
    $feito = true;

    $CI = &get_instance();
    if (!$CI->db->table_exists(db_prefix() . 'dps_lembrete_avisos')) {
        /*
         * Tabela própria em vez de uma coluna nova na tblreminders: a tabela
         * dos lembretes é do Perfex e uma actualização dele apagaria a coluna.
         */
        $CI->db->query('CREATE TABLE `' . db_prefix() . 'dps_lembrete_avisos` (
            `reminder_id` INT(11) NOT NULL,
            `tipo`        VARCHAR(10) NOT NULL DEFAULT "30min",
            `avisado_em`  DATETIME NOT NULL,
            PRIMARY KEY (`reminder_id`, `tipo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
    }

    /*
     * A tabela nasceu só com o aviso dos 30 minutos. Passou a haver também o
     * aviso do próprio dia, e os dois têm de poder existir para o mesmo
     * lembrete — daí a coluna `tipo` fazer parte da chave.
     */
    $campos = $CI->db->list_fields(db_prefix() . 'dps_lembrete_avisos');
    if (! in_array('tipo', $campos, true)) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'dps_lembrete_avisos`
            ADD COLUMN `tipo` VARCHAR(10) NOT NULL DEFAULT "30min"');
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'dps_lembrete_avisos`
            DROP PRIMARY KEY, ADD PRIMARY KEY (`reminder_id`, `tipo`)');
    }
}

/** Quantos minutos antes do lembrete é que o aviso aparece no CRM. */
define('DPS_AUTOMACAO_AVISO_MINUTOS', 30);

hooks()->add_action('after_cron_run', 'dps_automacao_aviso_lembretes');

function dps_automacao_aviso_lembretes()
{
    $CI = &get_instance();
    dps_automacao_lembrete_schema();

    /*
     * Janela: do momento actual até 30 minutos à frente.
     *
     * O cron corre de 10 em 10 minutos, portanto qualquer lembrete é apanhado
     * dentro da janela. A tabela de avisos garante que cada um só avisa uma
     * vez — sem ela, o mesmo lembrete apitava a cada passagem do cron durante
     * meia hora.
     */
    $agora = date('Y-m-d H:i:s');
    $ate   = date('Y-m-d H:i:s', time() + DPS_AUTOMACAO_AVISO_MINUTOS * 60);

    $lembretes = $CI->db->query(
        'SELECT r.id, r.description, r.date, r.staff, r.rel_id, r.rel_type
           FROM ' . db_prefix() . 'reminders r
           LEFT JOIN ' . db_prefix() . 'dps_lembrete_avisos a ON a.reminder_id = r.id AND a.tipo = "30min"
          WHERE r.date >= ? AND r.date <= ?
            AND (r.isnotified IS NULL OR r.isnotified = 0)
            AND (r.is_complete IS NULL OR r.is_complete <> "1")
            AND a.reminder_id IS NULL',
        [$agora, $ate]
    )->result_array();

    foreach ($lembretes as $l) {
        $texto = trim(strip_tags((string) $l['description']));
        $texto = mb_substr($texto, 0, 120) ?: 'Lembrete';

        $link = '';
        if ($l['rel_type'] === 'lead' && !empty($l['rel_id'])) {
            $link = 'leads/index/' . (int) $l['rel_id'];
        } elseif ($l['rel_type'] === 'customer' && !empty($l['rel_id'])) {
            $link = 'clients/client/' . (int) $l['rel_id'];
        }

        add_notification([
            'description' => '⏰ Daqui a ' . DPS_AUTOMACAO_AVISO_MINUTOS . ' minutos ('
                             . date('H:i', strtotime($l['date'])) . '): ' . $texto,
            'touserid'    => (int) $l['staff'],
            'fromcompany' => true,
            'link'        => $link,
        ]);

        $CI->db->query(
            'INSERT INTO ' . db_prefix() . 'dps_lembrete_avisos (reminder_id, tipo, avisado_em)
             VALUES (?, "30min", ?) ON DUPLICATE KEY UPDATE avisado_em = VALUES(avisado_em)',
            [(int) $l['id'], date('Y-m-d H:i:s')]
        );
    }
}

hooks()->add_action('app_admin_footer', 'dps_automacao_js_agenda');

function dps_automacao_js_agenda()
{
    // Só na listagem de leads, que é onde o botão vive.
    if (strpos((string) uri_string(), 'leads') === false) {
        return;
    }
    ?>
    <div class="modal fade" id="dps-modal-agenda" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Agendar chamada</h4>
          </div>
          <div class="modal-body">
            <p class="bold" id="dps-agenda-nome" style="margin-bottom:12px;"></p>
            <div class="form-group">
              <label for="dps-agenda-quando">Quando ligar</label>
              <input type="datetime-local" class="form-control" id="dps-agenda-quando">
            </div>
            <div class="form-group">
              <label for="dps-agenda-nota">Nota (opcional)</label>
              <input type="text" class="form-control" id="dps-agenda-nota"
                     placeholder="ex.: pediu para ligar de manhã">
            </div>
            <p class="text-muted" style="margin-bottom:0;font-size:12px;">
              Recebe o aviso no CRM 30 minutos antes.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-info" id="dps-agenda-gravar">Marcar</button>
          </div>
        </div>
      </div>
    </div>
    <script>
    (function () {
      var leadId = null;

      window.dpsAgendarLembrete = function (id, nome) {
        leadId = id;
        document.getElementById('dps-agenda-nome').textContent = nome || ('Lead #' + id);
        document.getElementById('dps-agenda-nota').value = '';

        /*
         * Por omissão: amanhã à mesma hora, arredondada. Poupa o caso mais
         * comum — "não atendeu, ligo amanhã" — a mexer no seletor de datas.
         */
        var d = new Date(Date.now() + 86400000);
        d.setMinutes(0, 0, 0);
        var p = function (n) { return (n < 10 ? '0' : '') + n; };
        document.getElementById('dps-agenda-quando').value =
          d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate())
          + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());

        $('#dps-modal-agenda').modal('show');
      };

      $(document).on('click', '#dps-agenda-gravar', function () {
        var botao  = this;
        var quando = document.getElementById('dps-agenda-quando').value;
        if (!leadId || !quando) {
          alert_float('warning', 'Escolha a data e a hora.');
          return;
        }

        var envio = {
          lead_id: leadId,
          quando:  quando,
          nota:    document.getElementById('dps-agenda-nota').value
        };
        if (typeof csrfData !== 'undefined') { envio[csrfData.token_name] = csrfData.hash; }

        $(botao).prop('disabled', true).text('A marcar...');

        $.post(admin_url + 'dps_automacao/agendar_lembrete', envio)
          .done(function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) { r = null; }
            $('#dps-modal-agenda').modal('hide');
            alert_float(r && r.sucesso ? 'success' : 'danger',
                        (r && r.mensagem) || 'Não foi possível marcar o lembrete.');
          })
          .fail(function (xhr) {
            alert_float('danger', 'Falha ao marcar o lembrete (erro ' + xhr.status + ').');
          })
          .always(function () { $(botao).prop('disabled', false).text('Marcar'); });
      });
    })();
    </script>
    <?php
}


/* =====================================================================
 * Pedido de SUPORTE à direcção, a partir da linha da lead
 *
 * O comercial que não consegue fechar pede ajuda sem sair da lista: escreve o
 * contexto, e nasce uma tarefa para a direcção ligar ao cliente e forçar o
 * fecho. Pedido do dono (11/08/2026).
 *
 * A tarefa fica LIGADA à lead (rel_type=lead), e é isso que faz o circuito
 * fechar: tudo o que a direcção escrever na tarefa aparece na ficha da lead do
 * comercial, sem ser preciso construir um sistema de respostas à parte.
 * ================================================================== */

/** A quem vão os pedidos de suporte. */
function dps_automacao_staff_suporte()
{
    $id = (int) get_option('dps_automacao_staff_suporte');

    return $id > 0 ? $id : 46;   // Cláudio Carvalho
}

hooks()->add_action('app_admin_footer', 'dps_automacao_js_suporte');

function dps_automacao_js_suporte()
{
    if (strpos((string) uri_string(), 'leads') === false) {
        return;
    }
    ?>
    <div class="modal fade" id="dps-modal-suporte" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><i class="fa fa-life-ring"></i> Pedir apoio para fechar</h4>
          </div>
          <div class="modal-body">
            <p class="bold" id="dps-suporte-nome" style="margin-bottom:12px;"></p>
            <div class="form-group">
              <label for="dps-suporte-texto">Contexto e o que precisa</label>
              <textarea class="form-control" id="dps-suporte-texto" rows="5"
                        placeholder="Em que ponto está a conversa, o que já ofereceu, e onde está a travar."></textarea>
            </div>
            <p class="text-muted" style="margin-bottom:0;font-size:12px;">
              A direcção recebe uma tarefa para ligar ao cliente. O que lá for escrito
              aparece na ficha desta lead.
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
            <button type="button" class="btn" id="dps-suporte-enviar"
                    style="background:#8e44ad;color:#fff;">Pedir apoio</button>
          </div>
        </div>
      </div>
    </div>
    <script>
    (function () {
      var leadId = null;

      window.dpsPedirSuporte = function (id, nome) {
        leadId = id;
        document.getElementById('dps-suporte-nome').textContent = nome || ('Lead #' + id);
        document.getElementById('dps-suporte-texto').value = '';
        $('#dps-modal-suporte').modal('show');
        setTimeout(function () { document.getElementById('dps-suporte-texto').focus(); }, 400);
      };

      $(document).on('click', '#dps-suporte-enviar', function () {
        var botao = this;
        var texto = document.getElementById('dps-suporte-texto').value.trim();

        // Sem contexto o pedido não serve de nada a quem o recebe.
        if (!texto) {
          alert_float('warning', 'Escreva o contexto — é isso que a direcção precisa de ler antes de ligar.');
          return;
        }

        var envio = { lead_id: leadId, contexto: texto };
        if (typeof csrfData !== 'undefined') { envio[csrfData.token_name] = csrfData.hash; }

        $(botao).prop('disabled', true).text('A enviar...');

        $.post(admin_url + 'dps_automacao/pedir_suporte', envio)
          .done(function (r) {
            try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) { r = null; }
            $('#dps-modal-suporte').modal('hide');
            alert_float(r && r.sucesso ? 'success' : 'danger',
                        (r && r.mensagem) || 'Não foi possível enviar o pedido.');
          })
          .fail(function (xhr) {
            alert_float('danger', 'Falha ao enviar o pedido (erro ' + xhr.status + ').');
          })
          .always(function () { $(botao).prop('disabled', false).text('Pedir apoio'); });
      });
    })();
    </script>
    <?php
}

/**
 * Caixa da agenda: abrir a lead ou eliminar o lembrete.
 *
 * Eliminar só existia na lista de lembretes. Quem trabalha a partir da
 * agenda não tinha como limpar o que já não faz sentido — o cliente comprou,
 * desistiu, ou o lembrete foi marcado por engano — e ficava com a agenda
 * cheia de coisas mortas que já ninguém vai fazer.
 *
 * O modelo do calendário (application/models/Utilities_model.php) põe em cada
 * lembrete de lead um onclick a chamar dpsLembreteAgenda().
 */
hooks()->add_action('app_admin_footer', 'dps_automacao_js_lembrete_agenda');
function dps_automacao_js_lembrete_agenda()
{
    if (! is_staff_member()) {
        return;
    }
    ?>
    <div class="modal fade" id="dpsLembreteModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Lembrete</h4>
          </div>
          <div class="modal-body">
            <p id="dps-lembrete-texto" class="bold" style="margin-bottom:4px;"></p>
            <p class="text-muted" style="font-size:12px;">O que quer fazer?</p>
          </div>
          <div class="modal-footer">
            <a href="#" id="dps-lembrete-abrir" class="btn btn-info">Abrir a lead</a>
            <button type="button" id="dps-lembrete-apagar" class="btn btn-danger">Eliminar</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
          </div>
        </div>
      </div>
    </div>
    <script>
    (function () {
      var actual = { id: 0, lead: 0 };

      window.dpsLembreteAgenda = function (id, leadId, texto) {
        actual = { id: id, lead: leadId };
        document.getElementById('dps-lembrete-texto').textContent = texto || 'Lembrete';
        document.getElementById('dps-lembrete-abrir').setAttribute(
          'href', '<?php echo admin_url('leads/index/'); ?>' + leadId
        );
        $('#dpsLembreteModal').modal('show');
      };

      $(document).on('click', '#dps-lembrete-apagar', function () {
        if (!confirm('Eliminar este lembrete? Não há forma de o recuperar.')) { return; }
        var btn = this;
        btn.disabled = true;

        // A rota do Perfex quer rel_id, id e rel_type — por esta ordem.
        $.get('<?php echo admin_url('misc/delete_reminder/'); ?>' + actual.lead + '/' + actual.id + '/lead',
          function (r) {
            try { r = typeof r === 'string' ? JSON.parse(r) : r; } catch (e) { r = null; }
            $('#dpsLembreteModal').modal('hide');
            btn.disabled = false;
            if (r && r.alert_type === 'success') {
              alert_float('success', r.message || 'Lembrete eliminado.');
              // Recarregar: o calendário guarda os eventos em memória e o
              // lembrete apagado continuaria desenhado até se sair da página.
              setTimeout(function () { location.reload(); }, 700);
            } else {
              alert_float('danger', (r && r.message) ? r.message : 'Não foi possível eliminar.');
            }
          });
      });
    })();
    </script>
    <?php
}

/**
 * Quando uma lead muda de dono, as tarefas dela vão atrás.
 *
 * Uma tarefa fica na fila de quem a recebeu, não de quem tem a lead. Passa a
 * lead para outro comercial e as tarefas continuam na fila do anterior: ele
 * vê trabalho que já não é dele e o novo dono não vê nada. Foi assim que a
 * 13/08/2026 havia 32 tarefas na pessoa errada.
 *
 * Só as tarefas POR FECHAR: as fechadas são história e reescrevê-las não
 * ajuda ninguém.
 *
 * E nunca as de SUPORTE. Essas são dirigidas de propósito a quem vai ajudar
 * a fechar o negócio — mandá-las para o dono da lead era devolvê-las a quem
 * pediu ajuda, e o pedido morria aí.
 */
hooks()->add_action('after_lead_updated', 'dps_automacao_tarefas_seguem_a_lead');
function dps_automacao_tarefas_seguem_a_lead($lead_id)
{
    $CI      = &get_instance();
    $lead_id = (int) $lead_id;

    if ($lead_id <= 0) {
        return;
    }

    $lead = $CI->db->select('assigned')->where('id', $lead_id)
        ->get(db_prefix() . 'leads')->row();

    if (! $lead || (int) $lead->assigned <= 0) {
        return;
    }

    $dono = (int) $lead->assigned;

    $tarefas = $CI->db->query(
        'SELECT a.id, a.taskid, a.staffid
           FROM ' . db_prefix() . 'task_assigned a
           JOIN ' . db_prefix() . 'tasks t ON t.id = a.taskid
          WHERE t.rel_type = "lead" AND t.rel_id = ?
            AND t.status <> 5
            AND a.staffid <> ?
            AND t.name NOT LIKE "%Apoio para fechar%"
            AND t.name NOT LIKE "%Suporte%"',
        [$lead_id, $dono]
    )->result_array();

    if (empty($tarefas)) {
        return;
    }

    foreach ($tarefas as $t) {
        // Se o novo dono já lá estiver, tira-se o antigo em vez de duplicar.
        $ja = $CI->db->where('taskid', (int) $t['taskid'])->where('staffid', $dono)
            ->count_all_results(db_prefix() . 'task_assigned');

        if ($ja > 0) {
            $CI->db->where('id', (int) $t['id'])->delete(db_prefix() . 'task_assigned');
        } else {
            $CI->db->where('id', (int) $t['id'])
                ->update(db_prefix() . 'task_assigned', ['staffid' => $dono]);
        }
    }

    log_activity('dps_automacao: lead #' . $lead_id . ' mudou de dono — '
        . count($tarefas) . ' tarefa(s) por fechar passaram para o staff #' . $dono . '.');
}

/**
 * Aviso no próprio dia, de manhã.
 *
 * O aviso dos 30 minutos serve para não falhar a hora, mas chega tarde para
 * organizar o dia: quem tem cinco chamadas marcadas quer saber disso quando
 * se senta, não trinta minutos antes de cada uma. Pedido do dono
 * (13/08/2026).
 *
 * Um aviso por lembrete e por dia, com a hora e o nome do cliente, e a
 * ligação para a ficha da lead. Fica no sino do CRM, que é a indicação que
 * toda a gente vê.
 */
hooks()->add_action('after_cron_run', 'dps_automacao_aviso_lembretes_do_dia');
function dps_automacao_aviso_lembretes_do_dia()
{
    $CI = &get_instance();
    dps_automacao_lembrete_schema();

    /*
     * Só a partir das 7h. O cron corre a toda a hora e sem isto o aviso do
     * dia saía à meia-noite e um, quando ninguém o vê — e de manhã já estava
     * lido e enterrado nas notificações antigas.
     */
    if ((int) date('G') < 7) {
        return;
    }

    $lembretes = $CI->db->query(
        'SELECT r.id, r.description, r.date, r.staff, r.rel_id, r.rel_type
           FROM ' . db_prefix() . 'reminders r
           LEFT JOIN ' . db_prefix() . 'dps_lembrete_avisos a
                  ON a.reminder_id = r.id AND a.tipo = "dia"
          WHERE DATE(r.date) = CURDATE()
            AND (r.is_complete IS NULL OR r.is_complete <> "1")
            AND a.reminder_id IS NULL'
    )->result_array();

    foreach ($lembretes as $l) {
        dps_automacao_avisar_lembrete_do_dia($l);
    }

    if (count($lembretes) > 0) {
        log_activity('dps_automacao: aviso do dia enviado para ' . count($lembretes) . ' lembrete(s).');
    }
}

/**
 * Tudo o que entra na agenda passa a ter lembrete.
 *
 * Um evento do calendário desenha-se e mais nada: não entra na lista de
 * lembretes e não avisa ninguém. Quem marcava uma reunião na agenda contava
 * com um aviso que nunca existiu.
 *
 * Cria-se um lembrete espelho, para o dono do evento e à hora dele. A partir
 * daí segue as regras dos outros: aviso de manhã e aviso 30 minutos antes.
 * Pedido do dono (13/08/2026).
 */
hooks()->add_action('dps_evento_criado', 'dps_automacao_lembrete_do_evento');
function dps_automacao_lembrete_do_evento($event_id)
{
    $CI       = &get_instance();
    $event_id = (int) $event_id;

    if ($event_id <= 0) {
        return;
    }

    $evento = $CI->db->where('eventid', $event_id)
        ->get(db_prefix() . 'events')->row();

    if (! $evento || empty($evento->start)) {
        return;
    }

    // Compromisso que já passou não tem aviso a dar.
    if (strtotime($evento->start) < time()) {
        return;
    }

    $texto = trim(strip_tags((string) $evento->title));
    if ($texto === '') {
        $texto = 'Compromisso na agenda';
    }

    $CI->db->insert(db_prefix() . 'reminders', [
        'description'     => $texto,
        'date'            => $evento->start,
        'isnotified'      => 0,
        // Sem isto nasce concluído — ver a armadilha do enum em
        // dps_automacao/controllers/Dps_automacao.php.
        'is_complete'     => '0',
        'staff'           => (int) $evento->userid,
        'rel_id'          => $event_id,
        'rel_type'        => 'event',
        'creator'         => (int) $evento->userid,
        'notify_by_email' => 0,
    ]);
}

/**
 * O aviso do dia também corre quando o comercial abre o CRM.
 *
 * O cron trata do caso geral, mas depende de ele ter corrido — e quem chega
 * às 8h de um dia em que o cron falhou não vê aviso nenhum. Isto garante que
 * o aviso está lá mal se abre o computador: corre no primeiro carregamento
 * de página do dia, só para o próprio, e é barato porque a tabela de
 * controlo diz logo que já foi avisado.
 */
hooks()->add_action('admin_init', 'dps_automacao_aviso_dia_ao_entrar');
function dps_automacao_aviso_dia_ao_entrar()
{
    static $feito = false;

    if ($feito || ! is_staff_member()) {
        return;
    }
    $feito = true;

    $CI  = &get_instance();
    $eu  = (int) get_staff_user_id();

    if ($eu <= 0) {
        return;
    }

    dps_automacao_lembrete_schema();

    $lembretes = $CI->db->query(
        'SELECT r.id, r.description, r.date, r.staff, r.rel_id, r.rel_type
           FROM ' . db_prefix() . 'reminders r
           LEFT JOIN ' . db_prefix() . 'dps_lembrete_avisos a
                  ON a.reminder_id = r.id AND a.tipo = "dia"
          WHERE DATE(r.date) = CURDATE()
            AND r.staff = ?
            AND (r.is_complete IS NULL OR r.is_complete <> "1")
            AND a.reminder_id IS NULL',
        [$eu]
    )->result_array();

    foreach ($lembretes as $l) {
        dps_automacao_avisar_lembrete_do_dia($l);
    }
}

/**
 * Manda o aviso do dia de um lembrete e marca-o como avisado.
 *
 * Partilhada pelo cron e pelo aviso de entrada no CRM: dois caminhos para o
 * mesmo aviso, e a tabela de controlo garante que só sai uma vez.
 */
function dps_automacao_avisar_lembrete_do_dia(array $l)
{
    $CI = &get_instance();

    $texto = trim(strip_tags((string) $l['description']));
    $texto = mb_substr($texto, 0, 100) ?: 'Lembrete';

    $link = '';
    $quem = '';

    if ($l['rel_type'] === 'lead' && ! empty($l['rel_id'])) {
        $link = 'leads/index/' . (int) $l['rel_id'];
        $lead = $CI->db->select('name')->where('id', (int) $l['rel_id'])
            ->get(db_prefix() . 'leads')->row();
        if ($lead) {
            $quem = ' — ' . $lead->name;
        }
    } elseif ($l['rel_type'] === 'customer' && ! empty($l['rel_id'])) {
        $link = 'clients/client/' . (int) $l['rel_id'];
    } elseif ($l['rel_type'] === 'event') {
        $link = 'utilities/calendar';
    }

    add_notification([
        'description' => '📅 Hoje às ' . date('H:i', strtotime($l['date'])) . $quem . ': ' . $texto,
        'touserid'    => (int) $l['staff'],
        'fromcompany' => true,
        'link'        => $link,
    ]);

    $CI->db->query(
        'INSERT INTO ' . db_prefix() . 'dps_lembrete_avisos (reminder_id, tipo, avisado_em)
         VALUES (?, "dia", ?) ON DUPLICATE KEY UPDATE avisado_em = VALUES(avisado_em)',
        [(int) $l['id'], date('Y-m-d H:i:s')]
    );
}
