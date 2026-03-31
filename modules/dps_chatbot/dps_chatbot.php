<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Chatbot Interno
Description: Chatbot interno para comunicação entre funcionários e grupos públicos/privados criados por administradores.
Version: 1.0.0
Requires at least: 2.3.*
Author: OpenAI for DPS Imobiliário
*/

define('DPS_CHATBOT_MODULE_NAME', 'dps_chatbot');

register_activation_hook(DPS_CHATBOT_MODULE_NAME, 'dps_chatbot_activate');
register_deactivation_hook(DPS_CHATBOT_MODULE_NAME, 'dps_chatbot_deactivate');
register_uninstall_hook(DPS_CHATBOT_MODULE_NAME, 'dps_chatbot_uninstall');

hooks()->add_action('admin_init', 'dps_chatbot_module_init_menu_items');
hooks()->add_action('admin_init', 'dps_chatbot_register_permissions');

register_language_files(DPS_CHATBOT_MODULE_NAME, ['dps_chatbot']);

function dps_chatbot_activate()
{
    require_once(__DIR__ . '/install.php');
}

function dps_chatbot_deactivate()
{
    // Mantemos os dados.
}

function dps_chatbot_uninstall()
{
    require_once(__DIR__ . '/uninstall.php');
}

function dps_chatbot_module_init_menu_items()
{
    $CI = &get_instance();

    if (!is_admin() && !staff_can('view', DPS_CHATBOT_MODULE_NAME)) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('dps-chatbot', [
        'name'     => _l('dps_chatbot'),
        'icon'     => 'fa fa-comments',
        'position' => 37,
        'href'     => admin_url('dps_chatbot'),
    ]);
}

function dps_chatbot_register_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view'   => _l('permission_view') ?: 'View',
            'create' => _l('permission_create') ?: 'Create',
            'edit'   => _l('permission_edit') ?: 'Edit',
            'delete' => _l('permission_delete') ?: 'Delete',
            'manage_groups' => _l('dps_chatbot_permission_manage_groups') ?: 'Manage Groups',
        ],
    ];

    register_staff_capabilities(DPS_CHATBOT_MODULE_NAME, $capabilities, _l('dps_chatbot'));
}
