<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS VoIP - Central Telefónica
Description: Central telefónica integrada com Twilio. Permite chamadas de saída e entrada pelo browser ou telemóvel, com números por comercial e caller ID masking.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_VOIP_MODULE_NAME', 'dps_voip');
define('DPS_VOIP_VERSION', '1.0.0');

// Registar menu lateral
hooks()->add_action('admin_init', 'dps_voip_menu');
function dps_voip_menu()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_voip', [
        'name'     => 'VoIP Central',
        'href'     => admin_url('dps_voip'),
        'icon'     => 'fa fa-phone-square',
        'position' => 50,
        'badge'    => [],
    ]);
}

// Hook para adicionar botão de chamada na ficha de lead
hooks()->add_action('after_lead_saved', 'dps_voip_lead_hook');
function dps_voip_lead_hook($params)
{
    // Nada por agora - futuro: log automático
}

// Incluir assets no admin (softphone widget)
hooks()->add_action('app_admin_head', 'dps_voip_head_assets');
function dps_voip_head_assets()
{
    $module_url = module_dir_url(DPS_VOIP_MODULE_NAME);
    echo '<link rel="stylesheet" href="' . $module_url . 'assets/css/voip.css?v=' . DPS_VOIP_VERSION . '">';
}

hooks()->add_action('app_admin_footer', 'dps_voip_footer_assets');
function dps_voip_footer_assets()
{
    $CI = &get_instance();
    // Só carregar o softphone se o utilizador tiver um número atribuído
    $staff_id = get_staff_user_id();
    if (!$staff_id) return;

    $CI->load->model('dps_voip/Dps_voip_model');
    $staff_number = $CI->Dps_voip_model->get_staff_number($staff_id);
    if (!$staff_number) return;

    $module_url = module_dir_url(DPS_VOIP_MODULE_NAME);
    $token_url  = admin_url('dps_voip/get_token');
    echo '<script src="//sdk.twilio.com/js/client/v1.14/twilio.min.js"></script>';
    echo '<script src="' . $module_url . 'assets/js/softphone.js?v=' . DPS_VOIP_VERSION . '"></script>';
    echo '<script>DPS_VOIP_TOKEN_URL="' . $token_url . '"; DPS_VOIP_NUMBER="' . htmlspecialchars($staff_number['twilio_number']) . '";</script>';
    echo '<div id="dps-softphone-widget" style="display:none;"></div>';
}
