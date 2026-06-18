<?php
defined('BASEPATH') or exit('No direct script access allowed');

define('DPS_SOFIA_CALLS_MODULE_NAME', 'dps_sofia_calls');

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
