<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Propostas & Envios | DPS
Description: Na ficha da lead, botões para enviar informação (dossier + unidades disponíveis) e propostas por WhatsApp, com aba de registo de propostas.
Version: 1.0.0
Requires at least: 2.3.2
Author: Grupo DPS
*/

define('DPS_PROPOSTAS_MODULE_NAME', 'dps_propostas');

$CI = &get_instance();

register_activation_hook(DPS_PROPOSTAS_MODULE_NAME, 'dps_propostas_activation');

function dps_propostas_activation()
{
    require __DIR__ . '/install.php';
}

$CI->load->helper(DPS_PROPOSTAS_MODULE_NAME . '/dps_propostas');

/**
 * Injeta a aba "Propostas" (com os botões) na ficha da lead.
 */
hooks()->add_action('after_lead_tabs_content', 'dps_propostas_render_lead_tab');

/**
 * Item de menu "Propostas Enviadas" (lista global, com filtro por comercial).
 */
hooks()->add_action('admin_init', 'dps_propostas_register_menu');

function dps_propostas_register_menu()
{
    $CI = &get_instance();
    if (! (function_exists('is_staff_member') && is_staff_member())) {
        return;
    }
    $CI->app_menu->add_sidebar_menu_item('dps-propostas-enviadas', [
        'name'     => 'Propostas Enviadas',
        'href'     => admin_url('dps_propostas/todas'),
        'icon'     => 'fa fa-file-pdf-o menu-icon',
        'position' => 17,
    ]);
}
