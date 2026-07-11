<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: WhatsApp Listas | DPS
Description: Replica o estado da lead como etiqueta/lista no WhatsApp do comercial atribuído. Quando o estado muda (ex.: para VIP Porto), move a etiqueta no WhatsApp. Processado por fila + cron para não bloquear o CRM.
Version: 1.0.0
Requires at least: 2.3.2
Author: Grupo DPS
*/

define('DPS_WA_LISTAS_MODULE_NAME', 'dps_wa_listas');

$CI = &get_instance();

register_activation_hook(DPS_WA_LISTAS_MODULE_NAME, 'dps_wa_listas_activation');

function dps_wa_listas_activation()
{
    require __DIR__ . '/install.php';
}

$CI->load->helper(DPS_WA_LISTAS_MODULE_NAME . '/dps_wa_listas');

/**
 * Quando o estado de uma lead muda, enfileira o pedido de sincronização.
 * NÃO chama o WhatsApp aqui (seria bloqueante em ações em massa).
 */
hooks()->add_action('lead_status_changed', 'dps_wa_listas_enqueue');

/**
 * CAPI Meta: quando o estado muda para um marco de qualidade (VIP 1/2/3, Concretizado),
 * envia o evento ao webhook do Make (que reencaminha para a Meta Conversions API).
 */
hooks()->add_action('lead_status_changed', 'dps_capi_status_changed');

/**
 * O cron do Perfex processa a fila (poucos por execução) e fala com a Evolution API.
 * Hook correto do Perfex = after_cron_run (não 'perfex_cron').
 */
hooks()->add_action('after_cron_run', 'dps_wa_listas_process_queue');

/**
 * Item na barra lateral (setup / estado da sincronização).
 */
hooks()->add_action('admin_init', 'dps_wa_listas_register_menu');

function dps_wa_listas_register_menu()
{
    $CI = &get_instance();
    if (! (function_exists('is_admin') && is_admin())) {
        return;
    }
    $CI->app_menu->add_setup_menu_item('dps-wa-listas', [
        'name'     => 'WhatsApp Listas (DPS)',
        'href'     => admin_url('dps_wa_listas'),
        'position' => 40,
    ]);
}
