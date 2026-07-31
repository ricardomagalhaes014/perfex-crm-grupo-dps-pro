<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Envio em Massa
Description: Envio de documentos e emails em massa para contactos das tarefas, filtrado por estado. Cada comercial vê apenas as suas tarefas.
Version: 1.0.0
Requires at least: 2.3.*
Author: DPS Imobiliário
*/

define('DPS_ENVIO_MASSA_MODULE_NAME', 'dps_envio_massa');

register_activation_hook(DPS_ENVIO_MASSA_MODULE_NAME, 'dps_envio_massa_activate');
register_language_files(DPS_ENVIO_MASSA_MODULE_NAME, ['dps_envio_massa']);

hooks()->add_action('admin_init', 'dps_envio_massa_init_menu_items');
hooks()->add_action('admin_init', 'dps_envio_massa_register_permissions');

function dps_envio_massa_activate()
{
    $CI = &get_instance();
    // Criar pasta de uploads do módulo se não existir
    $upload_dir = FCPATH . 'uploads/modules/dps_envio_massa/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
}

function dps_envio_massa_init_menu_items()
{
    $CI = &get_instance();
    if (!is_admin() && !staff_can('view', DPS_ENVIO_MASSA_MODULE_NAME)) {
        return;
    }
    $CI->app_menu->add_sidebar_menu_item('dps-envio-massa', [
        'name'     => 'Envio em Massa',
        'icon'     => 'fa fa-paper-plane',
        'position' => 37,
        'href'     => admin_url('dps_envio_massa'),
    ]);
}

function dps_envio_massa_register_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view'   => _l('permission_view') ?: 'View',
            'create' => _l('permission_create') ?: 'Create',
        ],
    ];
    register_staff_capabilities(DPS_ENVIO_MASSA_MODULE_NAME, $capabilities, 'Envio em Massa');
}
