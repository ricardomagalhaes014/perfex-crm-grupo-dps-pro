<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Promotores
Description: Gestão de promotores imobiliários no Perfex CRM, com fases de contacto, filtro por distrito e base inicial pré-carregada.
Version: 1.1.0
Requires at least: 2.3.*
Author: DPS Imobiliário
*/

define('PROMOTORES_MODULE_NAME', 'promotores');

register_activation_hook(PROMOTORES_MODULE_NAME, 'promotores_module_activate');
register_uninstall_hook(PROMOTORES_MODULE_NAME, 'promotores_module_uninstall');
register_language_files(PROMOTORES_MODULE_NAME, [PROMOTORES_MODULE_NAME]);

hooks()->add_action('admin_init', 'promotores_module_init_menu_items');
hooks()->add_action('admin_init', 'promotores_module_permissions');

$CI = &get_instance();
$CI->load->helper(PROMOTORES_MODULE_NAME . '/promotores');

function promotores_module_activate()
{
    require_once __DIR__ . '/install.php';
}

function promotores_module_uninstall()
{
    require_once __DIR__ . '/uninstall.php';
}

function promotores_module_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
        'view_own' => _l('permission_view_own'),
        'create'   => _l('permission_create'),
        'edit'     => _l('permission_edit'),
        'delete'   => _l('permission_delete'),
        'import'   => _l('permission_create'),
    ];

    register_staff_capabilities('promotores', $capabilities, _l('promotores'));
}

function promotores_module_init_menu_items()
{
    $CI = &get_instance();

    if (staff_can('view', 'promotores') || staff_can('view_own', 'promotores') || is_admin()) {
        $CI->app_menu->add_sidebar_menu_item('promotores', [
            'slug'     => 'promotores',
            'name'     => _l('promotores'),
            'icon'     => 'fa fa-building',
            'href'     => admin_url('promotores'),
            'position' => 18,
        ]);
    }
}
