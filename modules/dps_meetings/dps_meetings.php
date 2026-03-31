<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Meetings
Description: Reuniões online com registo no Perfex CRM (Jitsi/Meet/Zoom/link customizado), associadas a leads e clientes.
Version: 1.0.0
Requires at least: 2.3.*
Author: OpenAI for DPS Imobiliário
*/

define('DPS_MEETINGS_MODULE_NAME', 'dps_meetings');


register_activation_hook(DPS_MEETINGS_MODULE_NAME, 'dps_meetings_activate');
register_deactivation_hook(DPS_MEETINGS_MODULE_NAME, 'dps_meetings_deactivate');
register_uninstall_hook(DPS_MEETINGS_MODULE_NAME, 'dps_meetings_uninstall');

hooks()->add_action('admin_init', 'dps_meetings_module_init_menu_items');
hooks()->add_action('admin_init', 'dps_meetings_register_permissions');

register_language_files(DPS_MEETINGS_MODULE_NAME, ['dps_meetings']);


$CI = &get_instance();
$CI->load->helper(DPS_MEETINGS_MODULE_NAME . '/dps_meetings');

function dps_meetings_activate()
{
    require_once(__DIR__ . '/install.php');
}

function dps_meetings_deactivate()
{
    // Mantemos os dados.
}

function dps_meetings_uninstall()
{
    require_once(__DIR__ . '/uninstall.php');
}

function dps_meetings_module_init_menu_items()
{
    $CI = &get_instance();

    if (!is_admin() && !staff_can('view', DPS_MEETINGS_MODULE_NAME)) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('dps-meetings', [
        'name'     => _l('dps_meetings'),
        'icon'     => 'fa fa-video-camera',
        'position' => 36,
        'href'     => admin_url('dps_meetings'),
    ]);
}

function dps_meetings_register_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view'   => _l('permission_view') ?: 'View',
            'create' => _l('permission_create') ?: 'Create',
            'edit'   => _l('permission_edit') ?: 'Edit',
            'delete' => _l('permission_delete') ?: 'Delete',
        ],
    ];

    register_staff_capabilities(DPS_MEETINGS_MODULE_NAME, $capabilities, _l('dps_meetings'));
}
