<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Sofia Calls
Description: Automação de chamadas telefónicas com a Sofia (ElevenLabs AI) para leads do CRM. Permite criar campanhas de chamadas automáticas por estado de lead, com foco/contexto personalizável.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/
define('DPS_SOFIA_CALLS_MODULE_NAME', 'dps_sofia_calls');
define('DPS_SOFIA_CALLS_VERSION', '1.0.0');

// Registar menu lateral
hooks()->add_action('admin_init', 'dps_sofia_calls_menu');
function dps_sofia_calls_menu()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_sofia_calls', [
        'name'     => 'Sofia Calls',
        'href'     => admin_url('dps_sofia_calls'),
        'icon'     => 'fa fa-phone',
        'position' => 49,
        'badge'    => [],
    ]);
}

// Cron: processar chamadas pendentes
hooks()->add_action('perfex_cron', 'dps_sofia_calls_process_cron');
function dps_sofia_calls_process_cron()
{
    $CI = &get_instance();
    $CI->load->model('dps_sofia_calls/Dps_sofia_calls_model');
    $CI->Dps_sofia_calls_model->process_pending_calls();
}
