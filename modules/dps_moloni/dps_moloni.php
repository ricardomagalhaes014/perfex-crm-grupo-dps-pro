<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Moloni
Description: Integracao do Painel do Negocio com a Moloni: conciliacao de documentos, emissao em rascunho, sincronizacao de entidades e gestao financeira.
Version: 1.3.0
Requires at least: 3.4.0
Author: GRUPO DPS
*/

define('DPS_MOLONI_MODULE_NAME', 'dps_moloni');

hooks()->add_action('admin_init', 'dps_moloni_register_capabilities');
hooks()->add_action('admin_init', 'dps_moloni_module_init_menu_items');
hooks()->add_action('app_admin_head', 'dps_moloni_head_assets');
hooks()->add_action('app_admin_footer', 'dps_moloni_footer_assets');

register_activation_hook(DPS_MOLONI_MODULE_NAME, 'dps_moloni_activation_hook');
register_language_files(DPS_MOLONI_MODULE_NAME, [DPS_MOLONI_MODULE_NAME]);

/**
 * Executa o install.php ao activar o modulo.
 */
function dps_moloni_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

/**
 * Permissoes do modulo, para poderem ser atribuidas por funcao no Perfex.
 *
 * Tem de correr no admin_init (nao existe hook 'staff_role_permissions') e o
 * segundo argumento e um array com a chave 'capabilities'.
 */
function dps_moloni_register_capabilities()
{
    $config = [
        'capabilities' => [
            'view'   => _l('permission_view') . ' (' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
        ],
    ];

    register_staff_capabilities(DPS_MOLONI_MODULE_NAME, $config, _l('dps_moloni'));
}

function dps_moloni_module_init_menu_items()
{
    $ci = &get_instance();

    if (!has_permission(DPS_MOLONI_MODULE_NAME, '', 'view') && !is_admin()) {
        return;
    }

    $ci->app_menu->add_sidebar_menu_item('dps-moloni', [
        'name'     => _l('dps_moloni'),
        'icon'     => 'fa fa-plug',
        'position' => 36,
        'collapse' => true,
    ]);

    $ci->app_menu->add_sidebar_children_item('dps-moloni', [
        'slug'     => 'dps-moloni-financeiro',
        'name'     => _l('dps_moloni_financeiro'),
        'href'     => admin_url('dps_moloni'),
        'position' => 1,
    ]);

    $ci->app_menu->add_sidebar_children_item('dps-moloni', [
        'slug'     => 'dps-moloni-conciliacao',
        'name'     => _l('dps_moloni_conciliacao'),
        'href'     => admin_url('dps_moloni/conciliacao'),
        'position' => 2,
    ]);

    $ci->app_menu->add_sidebar_children_item('dps-moloni', [
        'slug'     => 'dps-moloni-overrides',
        'name'     => _l('dps_moloni_overrides'),
        'href'     => admin_url('dps_moloni/overrides'),
        'position' => 3,
    ]);

    $ci->app_menu->add_sidebar_children_item('dps-moloni', [
        'slug'     => 'dps-moloni-entidades',
        'name'     => _l('dps_moloni_entidades'),
        'href'     => admin_url('dps_moloni/entidades'),
        'position' => 3,
    ]);

    $ci->app_menu->add_sidebar_children_item('dps-moloni', [
        'slug'     => 'dps-moloni-definicoes',
        'name'     => _l('dps_moloni_definicoes'),
        'href'     => admin_url('dps_moloni/definicoes'),
        'position' => 4,
    ]);

    $ci->app_menu->add_sidebar_children_item('dps-moloni', [
        'slug'     => 'dps-moloni-logs',
        'name'     => _l('dps_moloni_logs'),
        'href'     => admin_url('dps_moloni/logs'),
        'position' => 5,
    ]);
}

function dps_moloni_head_assets()
{
    if (strpos(uri_string(), 'dps_moloni') === false) {
        return;
    }

    echo '<link href="' . module_dir_url(DPS_MOLONI_MODULE_NAME, 'assets/css/dps_moloni.css') . '" rel="stylesheet" type="text/css">';
}

function dps_moloni_footer_assets()
{
    if (strpos(uri_string(), 'dps_moloni') === false) {
        return;
    }

    echo '<script src="' . module_dir_url(DPS_MOLONI_MODULE_NAME, 'assets/js/dps_moloni.js') . '"></script>';
}
