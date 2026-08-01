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
    /*
     * Vive dentro de "Automações", ao lado do envio em massa por estado de
     * LEAD. São a mesma ideia aplicada a coisas diferentes — separá-los em
     * dois sítios do menu obrigava a lembrar onde estava cada um.
     *
     * O menu pai ('sms-central') é criado pelo módulo dps_automacao. Se por
     * alguma razão não existir, o Perfex ignora o filho e o item some-se sem
     * dar erro; daí a alternativa em baixo.
     */
    if (isset($CI->app_menu) && method_exists($CI->app_menu, 'add_sidebar_children_item')) {
        $CI->app_menu->add_sidebar_children_item('sms-central', [
            'slug'     => 'dps-envio-massa',
            'name'     => 'Envio Massa Tarefa',
            'href'     => admin_url('dps_envio_massa'),
            'position' => 8,
        ]);
    } else {
        $CI->app_menu->add_sidebar_menu_item('dps-envio-massa', [
            'name'     => 'Envio Massa Tarefa',
            'icon'     => 'fa fa-paper-plane',
            'position' => 37,
            'href'     => admin_url('dps_envio_massa'),
        ]);
    }
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
