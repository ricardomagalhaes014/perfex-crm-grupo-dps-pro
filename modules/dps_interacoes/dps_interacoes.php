<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Interacções por Comercial
Description: Relatório de interacções por comercial com filtros de período e status de lead.
Version: 1.0.0
Requires at least: 2.3.*
Author: DPS Imobiliário
*/
define('DPS_INTERACOES_MODULE_NAME', 'dps_interacoes');
register_activation_hook(DPS_INTERACOES_MODULE_NAME, 'dps_interacoes_activate');
register_language_files(DPS_INTERACOES_MODULE_NAME, ['dps_interacoes']);
hooks()->add_action('admin_init', 'dps_interacoes_init_menu_items');
hooks()->add_action('admin_init', 'dps_interacoes_register_permissions');

function dps_interacoes_activate()
{
    // Sem tabelas adicionais necessárias
}

function dps_interacoes_init_menu_items()
{
    $CI = &get_instance();
    if (!is_admin() && !staff_can('view', DPS_INTERACOES_MODULE_NAME)) {
        return;
    }
    $CI->app_menu->add_sidebar_menu_item('dps-interacoes', [
        'name'     => 'Interacções Comercial',
        'icon'     => 'fa fa-bar-chart',
        'position' => 36,
        'href'     => admin_url('dps_interacoes'),
    ]);
}

function dps_interacoes_register_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view' => _l('permission_view') ?: 'View',
        ],
    ];
    register_staff_capabilities(DPS_INTERACOES_MODULE_NAME, $capabilities, 'Interacções Comercial');
}
