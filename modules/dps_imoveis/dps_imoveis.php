<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Imóveis
Description: Gestão de imóveis para DPS Imobiliário - registo, aprovação e publicação no site dpsimobiliario.pt
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_IMOVEIS_MODULE_NAME', 'dps_imoveis');
define('DPS_IMOVEIS_VERSION', '1.0.0');
define('DPS_IMOVEIS_UPLOAD_PATH', 'modules/dps_imoveis/uploads/');

// Criar pasta de uploads se não existir
if (!file_exists(FCPATH . DPS_IMOVEIS_UPLOAD_PATH)) {
    mkdir(FCPATH . DPS_IMOVEIS_UPLOAD_PATH, 0755, true);
    mkdir(FCPATH . DPS_IMOVEIS_UPLOAD_PATH . 'fotos/', 0755, true);
    mkdir(FCPATH . DPS_IMOVEIS_UPLOAD_PATH . 'documentos/', 0755, true);
}

// Hooks
hooks()->add_action('admin_init', 'dps_imoveis_menu');
hooks()->add_action('admin_init', 'dps_imoveis_permissions');

function dps_imoveis_menu()
{
    $CI = &get_instance();
    if (!is_admin() && !has_permission('dps_imoveis', '', 'view')) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('dps_imoveis', [
        'slug'     => 'dps_imoveis',
        'name'     => 'DPS Imóveis',
        'icon'     => 'fa fa-home',
        'href'     => admin_url('dps_imoveis'),
        'position' => 25,
    ]);
}

function dps_imoveis_permissions()
{
    $capabilities = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('dps_imoveis', $capabilities, 'DPS Imóveis');
}
