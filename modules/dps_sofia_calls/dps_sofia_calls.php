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

    // A chave da ElevenLabs escreve-se aqui, nao no codigo.
    $CI->app_menu->add_sidebar_children_item('dps_sofia_calls', [
        'slug'     => 'dps_sofia_calls_definicoes',
        'name'     => 'Definições',
        'href'     => admin_url('dps_sofia_calls/definicoes'),
        'position' => 5,
    ]);
}

// Cron: processar chamadas pendentes
// Usa o hook 'after_cron_run' que é disparado pelo Perfex core após cada execução do cron.
hooks()->add_action('after_cron_run', 'dps_sofia_calls_process_cron');
function dps_sofia_calls_process_cron()
{
    $CI = &get_instance();
    $CI->load->model('dps_sofia_calls/Dps_sofia_calls_model');
    $CI->Dps_sofia_calls_model->process_pending_calls();
}
