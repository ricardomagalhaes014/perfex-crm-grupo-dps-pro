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

hooks()->add_action('admin_init', 'dps_credito_menu');
hooks()->add_action('admin_init', 'dps_credito_permissions');

// A guarda tem de correr antes do controller. O admin_init dispara no
// construtor do AdminController, ou seja, antes de qualquer método — é o único
// ponto onde ainda dá para travar a escrita.
hooks()->add_action('admin_init', 'dps_credito_guarda_fecho', 1);

// Coluna "DPS Crédito" na listagem de leads
hooks()->add_filter('leads_table_columns', 'dps_credito_coluna_cabecalho');
hooks()->add_filter('leads_table_additional_columns_sql', 'dps_credito_coluna_sql');
hooks()->add_filter('leads_table_row_data', 'dps_credito_coluna_celula', 10, 2);

// Separador dentro da ficha da lead + modal global
hooks()->add_action('after_lead_tabs_content', 'dps_credito_painel_lead');
hooks()->add_action('app_admin_footer', 'dps_credito_footer');

function dps_credito_activate()
{
    require_once __DIR__ . '/install.php';
}

function dps_credito_menu()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('dps_credito', [
        'slug'     => 'dps_credito',
        'name'     => 'DPS Crédito',
        'icon'     => 'fa fa-university',
        'href'     => admin_url('dps_credito'),
        'position' => 27,
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
 * A guarda
 * ---------------------------------------------------------------------- */

/**
 * Impede que uma lead seja fechada sem o questionário de crédito respondido.
 *
 * Porque é feito aqui e não num hook próprio de leads: o Perfex dispara
 * `lead_status_changed` DEPOIS da escrita, e é um do_action — o valor de
 * retorno é ignorado. Não existe nenhum filtro `before_lead_updated`. Além
 * disso, `convert_to_customer` e a acção em massa escrevem o estado em SQL
 * cru, sem hook nenhum. Logo, o único sítio onde ainda dá para travar é antes
 * do controller arrancar.
 */
function dps_credito_guarda_fecho()
{
    if (get_option('dps_credito_bloqueio_ativo') != '1') {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $CI = &get_instance();

    // Só nos metemos no caminho das leads
    $uri = $CI->uri->uri_string();
    if (strpos($uri, 'leads/') === false) {
        return;
    }

    $lead_id     = null;
    $novo_estado = null;

    if (strpos($uri, 'leads/update_lead_status') !== false) {
        // Arrastar no kanban
        $lead_id     = $CI->input->post('leadid') ?: $CI->input->post('lead_id');
        $novo_estado = $CI->input->post('status');
    } elseif (preg_match('#leads/lead/(\d+)#', $uri, $m)) {
        // Formulário da ficha da lead
        $lead_id     = $m[1];
        $novo_estado = $CI->input->post('status');
    }

    if (!$lead_id || !$novo_estado) {
        return;
    }

    if (!dps_credito_estado_e_fecho($novo_estado)) {
        return;
    }

    // Já respondeu? Então segue.
    if (dps_credito_lead_tem_resposta($lead_id)) {
        return;
    }

    $mensagem = 'Antes de fechar esta lead tem de responder ao questionário de crédito '
        . '(Crédito abordado? Sim/Não). Abra o separador "DPS Crédito" na ficha da lead, '
        . 'ou use o botão da coluna DPS Crédito na listagem.';

    // O kanban ignora o corpo da resposta, por isso marcamos a resposta com uma
    // bandeira que o JS do módulo apanha para avisar e recarregar a página.
    if (dps_credito_pedido_ajax()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'                 => false,
            'dps_credito_bloqueado'   => true,
            'lead_id'                 => (int) $lead_id,
            'message'                 => $mensagem,
        ]);
        exit;
    }

    set_alert('danger', $mensagem);
    redirect(admin_url('leads'));
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

    return $colunas;
}

function dps_credito_coluna_celula($row, $aRow)
{
    $lead_id     = (int) $aRow['id'];
    $abordado    = $aRow['dps_credito_abordado'] ?? null;
    $interessado = $aRow['dps_credito_interessado'] ?? null;

    if ($abordado === null) {
        // Sem resposta: é isto que trava o fecho, por isso mostra-se como acção.
        $row[] = '<button type="button" class="btn btn-warning btn-xs dps-credito-abrir" data-lead="' . $lead_id . '">'
            . 'Por responder</button>';

        return $row;
    }

    if ($abordado === 'nao') {
        $html = '<span class="label label-default">Não abordado</span>';
    } else {
        $html = '<span class="label label-success">Abordado</span>';

        if ($interessado === 'sim') {
            $html .= ' <span class="label label-info">Quer proposta</span>';
        }
    }

    $html .= ' <button type="button" class="btn btn-default btn-xs dps-credito-abrir" data-lead="' . $lead_id . '">'
        . '<i class="fa fa-pencil"></i></button>';

    $row[] = $html;

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

    $CI->load->view('dps_credito/painel_lead', [
        'lead'     => $lead,
        'resposta' => $resposta,
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
