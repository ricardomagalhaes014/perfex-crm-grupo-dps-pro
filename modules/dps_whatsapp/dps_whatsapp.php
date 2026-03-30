<?php
defined('BASEPATH') or exit('No direct script access allowed');

define('DPS_WHATSAPP_MODULE_NAME', 'dps_whatsapp');

// Registar menu lateral
hooks()->add_action('admin_init', 'dps_whatsapp_menu');
function dps_whatsapp_menu()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_whatsapp', [
        'name'     => 'WhatsApp',
        'href'     => admin_url('dps_whatsapp'),
        'icon'     => 'fa fa-whatsapp',
        'position' => 48,
        'badge'    => [],
    ]);
}

// Hook: quando uma lead é criada → agendar follow-up de 7 dias
hooks()->add_action('lead_created', 'dps_whatsapp_lead_created');
function dps_whatsapp_lead_created($lead_id)
{
    $CI = &get_instance();
    $CI->load->model('dps_whatsapp/Dps_whatsapp_model');
    $CI->Dps_whatsapp_model->schedule_followup($lead_id);
}

// Hook: quando o estado de uma lead muda → actualizar/reagendar follow-up
hooks()->add_action('lead_status_changed', 'dps_whatsapp_status_changed');
function dps_whatsapp_status_changed($data)
{
    $CI = &get_instance();
    $CI->load->model('dps_whatsapp/Dps_whatsapp_model');
    $lead_id = is_array($data) ? ($data['lead_id'] ?? $data[0] ?? null) : $data;
    if ($lead_id) {
        $CI->Dps_whatsapp_model->reschedule_followup($lead_id);
    }
}

// Cron: processar follow-ups pendentes (chamado pelo cron do Perfex)
hooks()->add_action('perfex_cron', 'dps_whatsapp_process_cron');
function dps_whatsapp_process_cron()
{
    $CI = &get_instance();
    $CI->load->model('dps_whatsapp/Dps_whatsapp_model');
    $CI->Dps_whatsapp_model->process_pending_followups();
}
