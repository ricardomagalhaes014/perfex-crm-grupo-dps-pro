<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Crédito
Description: Questionário de crédito obrigatório ao fechar uma lead e gestão dos processos de crédito daí resultantes.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_CREDITO_MODULE_NAME', 'dps_credito');
define('DPS_CREDITO_VERSION', '1.0.0');
define('DPS_CREDITO_UPLOAD_PATH', 'uploads/dps_credito/');

register_activation_hook(DPS_CREDITO_MODULE_NAME, 'dps_credito_activate');

get_instance()->load->helper(DPS_CREDITO_MODULE_NAME . '/dps_credito');

hooks()->add_action('admin_init', 'dps_credito_ensure_historico');
hooks()->add_action('admin_init', 'dps_credito_setup_estados');
hooks()->add_action('admin_init', 'dps_credito_menu');
hooks()->add_action('admin_init', 'dps_credito_permissions');

// A obrigatoriedade é imposta do lado do cliente (ver footer_assets.php):
// intercetamos as funções globais do Perfex que mudam o estado da lead
// (lead_profile_form_handler e leads_kanban_update) ANTES de submeterem. Assim
// nunca travamos um POST a meio — o que evita os "419 página expirada" e o
// "só grava depois de refrescar" que o bloqueio no servidor provocava.

/**
 * O questionário está a perguntar?
 *
 * SUSPENSO a 10/08/2026, por decisão do dono: o negócio do crédito ainda não
 * arrancou e toda a gente respondia "não", o que só gerava cliques e enchia a
 * base de respostas sem valor nenhum para a análise.
 *
 * Suspende-se a PERGUNTA, não o módulo: a página do DPS Crédito, os processos
 * já abertos e as respostas dadas até aqui ficam todos intactos. O que deixa
 * de acontecer é a coluna na tabela de leads, o separador na ficha e — o que
 * mais incomodava — a obrigatoriedade de responder antes de mudar o estado.
 *
 * Para voltar a ligar: pôr a opção 'dps_credito_questionario_ativo' a '1'.
 */
function dps_credito_questionario_ativo()
{
    // Sem opção gravada assume-se SUSPENSO: quem quiser a pergunta liga-a.
    return get_option('dps_credito_questionario_ativo') === '1';
}

if (dps_credito_questionario_ativo()) {
    // Coluna "DPS Crédito" na listagem de leads
    hooks()->add_filter('leads_table_columns', 'dps_credito_coluna_cabecalho');
    hooks()->add_filter('leads_table_additional_columns_sql', 'dps_credito_coluna_sql');
    hooks()->add_filter('leads_table_row_data', 'dps_credito_coluna_celula', 10, 2);

    // Separador dentro da ficha da lead + modal global (é o footer que impõe
    // a obrigatoriedade, interceptando as funções do Perfex antes do submit).
    hooks()->add_action('after_lead_tabs_content', 'dps_credito_painel_lead');
    hooks()->add_action('app_admin_footer', 'dps_credito_footer');
}

function dps_credito_activate()
{
    require_once __DIR__ . '/install.php';
}

/**
 * Configura, uma única vez, os estados de lead que passam a exigir a resposta
 * de crédito (Sim/Não) para poderem ser aplicados — e cria o estado "Crédito",
 * onde ficam as leads a trabalhar só para crédito.
 *
 * Corre no admin_init mas sai de imediato quando já correu (guardada por
 * opção), por isso aplica-se sozinha depois de um deploy só de ficheiros, sem
 * reactivar o módulo. Os IDs são resolvidos por NOME (não fixados), para
 * aguentar diferenças de numeração entre instalações.
 */
/**
 * Tabela de histórico das respostas de crédito.
 * A tabela de respostas guarda só o estado actual; sem histórico não é
 * possível medir quantas vezes um comercial passou uma lead a "sim".
 */
function dps_credito_ensure_historico()
{
    if (get_option('dps_credito_historico_v') === '1') {
        return;
    }

    try {
        $CI  = &get_instance();
        $tbl = db_prefix() . 'dps_credito_historico';

        if (!$CI->db->table_exists($tbl)) {
            $CI->db->query('CREATE TABLE `' . $tbl . "` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `lead_id` INT NOT NULL,
                `staff_id` INT NULL,
                `abordado` VARCHAR(10) NULL,
                `abordado_anterior` VARCHAR(10) NULL,
                `mudou` TINYINT(1) NOT NULL DEFAULT 0,
                `interessado_proposta` VARCHAR(10) NULL,
                `montante` DECIMAL(15,2) NULL,
                `dateadded` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `lead_id` (`lead_id`),
                KEY `staff_id` (`staff_id`),
                KEY `dateadded` (`dateadded`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }
    } catch (\Throwable $e) {
        log_activity('dps_credito_ensure_historico falhou: ' . $e->getMessage());
    }

    update_option('dps_credito_historico_v', '1');
}

function dps_credito_setup_estados()
{
    $marca = '2026-07-22';
    if (get_option('dps_credito_setup_estados') === $marca) {
        return;
    }

    // Blindagem: isto corre no admin_init, em TODAS as páginas de admin. Uma
    // exceção aqui bloquearia o CRM inteiro. Marca-se como feito ANTES e todo
    // o trabalho vai dentro de try/catch — na pior das hipóteses o setup fica
    // por aplicar, mas nunca deita o admin abaixo. (Se falhar, o admin
    // reactiva/repete manualmente.)
    update_option('dps_credito_setup_estados', $marca);

    try {
        $CI  = &get_instance();
        $tbl = db_prefix() . 'leads_status';

        // 1) Criar o estado "Crédito" se ainda não existir.
        $credito    = $CI->db->where('name', 'Crédito')->get($tbl)->row_array();
        $credito_id = $credito ? (int) $credito['id'] : null;

        if (!$credito_id) {
            $CI->load->model('leads_model');
            $novo = $CI->leads_model->add_status(['name' => 'Crédito', 'color' => '#8e44ad']);
            $credito_id = $novo ?: null;
        }

        // 2) Estados que passam a pedir o crédito: VIP 1/2/3, Necessidades,
        //    Outras Oportunidades e o próprio Crédito. Casados por nome.
        $todos = $CI->db->get($tbl)->result_array();
        $ids   = [];

        foreach ($todos as $s) {
            $nome = strtoupper($s['name']);
            if (strpos($nome, 'VIP') !== false
                || strpos($nome, 'NECESSIDADE') !== false
                || strpos($nome, 'OPORTUNIDADE') !== false) {
                $ids[] = (int) $s['id'];
            }
        }

        if ($credito_id) {
            $ids[] = (int) $credito_id;
        }

        $ids = array_values(array_unique(array_filter($ids)));

        if (!empty($ids)) {
            update_option('dps_credito_estados_fecho', implode(',', $ids));
        }

        // Garantir que o bloqueio está ligado.
        $ativo = get_option('dps_credito_bloqueio_ativo');
        if ($ativo === false || $ativo === '' || $ativo === null) {
            update_option('dps_credito_bloqueio_ativo', '1');
        }
    } catch (\Throwable $e) {
        log_activity('dps_credito_setup_estados falhou: ' . $e->getMessage());
    }
}

function dps_credito_menu()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('dps_credito', [
        'slug'     => 'dps_credito',
        'name'     => 'DPS Crédito',
        'icon'     => 'fa fa-university',
        'position' => 27,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_credito', [
        'slug'     => 'dps_credito_processos',
        'name'     => 'Processos',
        'href'     => admin_url('dps_credito'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_credito', [
        'slug'     => 'dps_credito_comissoes',
        'name'     => 'Comissões',
        'href'     => admin_url('dps_credito/comissoes'),
        'position' => 3,
    ]);

    /*
     * "Propostas": as leads em que o comercial respondeu SIM.
     *
     * Fica a seguir aos Processos porque é o passo anterior a eles — a lead
     * seguiu para o parceiro, e ainda não há processo nenhum aberto. Antes
     * isto não se via em lado nenhum: sabia-se que tinha sido dito "sim"
     * abrindo a lead uma a uma. Pedido do dono (19/08/2026).
     */
    $CI->app_menu->add_sidebar_children_item('dps_credito', [
        'slug'     => 'dps_credito_propostas',
        'name'     => 'Propostas',
        'href'     => admin_url('dps_credito/propostas'),
        'position' => 2,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_credito', [
        'slug'     => 'dps_credito_analise',
        'name'     => 'Análise Comercial',
        'href'     => admin_url('dps_credito/analise'),
        'position' => 4,
    ]);
}

function dps_credito_permissions()
{
    register_staff_capabilities('dps_credito', [
        'view'          => 'Ver todos os processos de crédito',
        'edit'          => 'Editar processos de crédito',
        'delete'        => 'Eliminar processos de crédito',
        'download_docs' => 'Descarregar documentos de crédito',
        'definicoes'    => 'Gerir definições do módulo',
    ], 'DPS Crédito');
}

/* -------------------------------------------------------------------------
 * Coluna na listagem de leads
 * ---------------------------------------------------------------------- */

function dps_credito_coluna_cabecalho($colunas)
{
    $colunas[] = 'DPS Crédito';

    return $colunas;
}

/**
 * Trazemos a resposta na própria consulta da tabela. Fazer uma query por linha
 * seria 25 idas à base de dados só para desenhar uma coluna.
 */
function dps_credito_coluna_sql($colunas)
{
    $t = db_prefix() . 'dps_credito_respostas';

    $colunas[] = '(SELECT abordado FROM ' . $t . ' WHERE lead_id = ' . db_prefix() . 'leads.id LIMIT 1) as dps_credito_abordado';
    $colunas[] = '(SELECT interessado_proposta FROM ' . $t . ' WHERE lead_id = ' . db_prefix() . 'leads.id LIMIT 1) as dps_credito_interessado';
    $colunas[] = db_prefix() . 'leads.source as dps_credito_source';

    return $colunas;
}

function dps_credito_coluna_celula($row, $aRow)
{
    $lead_id     = (int) $aRow['id'];
    $abordado    = $aRow['dps_credito_abordado'] ?? null;
    $interessado = $aRow['dps_credito_interessado'] ?? null;

    // Só as leads de fora de Portugal ficam de fora do questionário. As que
    // têm a fonte por preencher entram (antes apareciam como "—" e não havia
    // forma de responder).
    if (!dps_credito_fonte_entra($aRow['dps_credito_source'] ?? 0)) {
        $row[] = '<span class="text-muted">—</span>';

        return $row;
    }

    /*
     * A coluna é sempre editável na própria tabela — o objectivo é responder
     * (e corrigir) sem abrir a lead. Mostra o estado actual e os dois botões:
     *  - "Não" grava directamente por AJAX;
     *  - "Sim" abre o questionário, porque a resposta afirmativa exige
     *    situação/banco/montante/proposta — gravar só o "sim" deixaria o
     *    processo incompleto.
     * Sem resposta = "Indefinido" (distinto de "não abordou", que é o que a
     * análise de direcção precisa de medir).
     */
    if ($abordado === 'nao') {
        $estado = '<span class="label label-default">Não abordado</span>';
    } elseif ($abordado === 'sim') {
        $estado = '<span class="label label-success">Abordado</span>';
        if ($interessado === 'sim') {
            $estado .= ' <span class="label label-info">Quer proposta</span>';
        }
    } else {
        $estado = '<span class="label label-warning">Indefinido</span>';
    }

    $btnSim = '<button type="button" class="btn btn-xs ' . ($abordado === 'sim' ? 'btn-success' : 'btn-default')
        . ' dps-credito-sim" data-lead="' . $lead_id . '" title="Crédito abordado — abre para completar">Sim</button>';
    $btnNao = '<button type="button" class="btn btn-xs ' . ($abordado === 'nao' ? 'btn-success' : 'btn-default')
        . ' dps-credito-nao" data-lead="' . $lead_id . '" title="Crédito não abordado">Não</button>';

    $row[] = '<span class="dps-credito-inline" data-lead="' . $lead_id . '" style="white-space:nowrap;">'
        . $estado . ' ' . $btnSim . ' ' . $btnNao
        . ' <button type="button" class="btn btn-default btn-xs dps-credito-abrir" data-lead="' . $lead_id . '" '
        . 'title="Abrir questionário completo"><i class="fa fa-pencil"></i></button>'
        . '</span>';

    return $row;
}

/* -------------------------------------------------------------------------
 * Interface
 * ---------------------------------------------------------------------- */

/**
 * Painel dentro da ficha da lead. O hook dispara já dentro do modal da lead,
 * carregado por AJAX, por isso o <script> inline aqui executa.
 */
function dps_credito_painel_lead($lead)
{
    if (!$lead || empty($lead->id)) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('dps_credito/dps_credito_model');

    $resposta = $CI->dps_credito_model->get_resposta_por_lead($lead->id);
    $processo = $CI->dps_credito_model->get_processo_por_lead($lead->id);

    $CI->load->view('dps_credito/painel_lead', [
        'lead'     => $lead,
        'resposta' => $resposta,
        'processo' => $processo,
    ]);
}

/**
 * Modal global + o JS que apanha o bloqueio do kanban.
 * Carregado em todas as páginas de admin porque a listagem de leads, o kanban
 * e a ficha da lead precisam todos dele.
 */
function dps_credito_footer()
{
    get_instance()->load->view('dps_credito/footer_assets');
}
