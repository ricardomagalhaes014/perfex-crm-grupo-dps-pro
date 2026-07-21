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

// A obrigatoriedade é imposta do lado do cliente (ver footer_assets.php):
// intercetamos as funções globais do Perfex que mudam o estado da lead
// (lead_profile_form_handler e leads_kanban_update) ANTES de submeterem. Assim
// nunca travamos um POST a meio — o que evita os "419 página expirada" e o
// "só grava depois de refrescar" que o bloqueio no servidor provocava.

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

    // Leads de fora do imobiliário Portugal não entram no questionário —
    // a coluna fica vazia para não sugerir uma acção que não se aplica.
    $aplicaveis = dps_credito_fontes_aplicaveis();
    if (!empty($aplicaveis) && !in_array((int) ($aRow['dps_credito_source'] ?? 0), $aplicaveis, true)) {
        $row[] = '<span class="text-muted">—</span>';

        return $row;
    }

    if ($abordado === null) {
        // Sem resposta ainda: mostra-se como "Não" (por omissão), clicável para
        // preencher. Continua a ser o estado que pede o questionário ao fechar.
        $row[] = '<button type="button" class="btn btn-default btn-xs dps-credito-abrir" data-lead="' . $lead_id . '">'
            . 'Não</button>';

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
