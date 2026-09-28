<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: CAPI Meta - DPS
Description: Envia o funil de leads para a Meta Conversions API via Make (funil cumulativo + VIP + Google Offline Conversions)
Version: 1.4.1
Requires at least: 2.3.*
Author: DPS Imobiliario
*/

define('CAPI_META_WEBHOOK', 'https://hook.eu1.make.com/w14e5b8einkrdv49sc8l8lubgb0kudkb');
define('CAPI_META_CF_SLUG', 'leads_facebook_lead_id');

/**
 * Estados que contam como conversão. Quando a lead entra num destes,
 * a Meta recebe UM evento: lead_qualified. Mais nenhum evento é enviado.
 */
function capi_meta_qualifying_statuses()
{
    return [
        'proposta enviada',
        'vip 1',
        'vip 2',
        'vip 3',
    ];
}

register_activation_hook('capi_meta', 'capi_meta_activate');
hooks()->add_action('lead_status_changed', 'capi_meta_on_lead_status_changed');

function capi_meta_activate()
{
    $CI = &get_instance();

    $exists = $CI->db->select('id')
        ->from(db_prefix() . 'customfields')
        ->where('fieldto', 'leads')
        ->group_start()
            ->where('slug', CAPI_META_CF_SLUG)
            ->or_like('name', 'Facebook Lead')
        ->group_end()
        ->get()->row();

    if (!$exists) {
        $CI->db->insert(db_prefix() . 'customfields', [
            'fieldto'                => 'leads',
            'name'                   => 'Facebook Lead ID',
            'slug'                   => CAPI_META_CF_SLUG,
            'type'                   => 'input',
            'active'                 => 1,
            'required'               => 0,
            'field_order'            => 0,
            'display_inline'         => 0,
            'show_on_pdf'            => 0,
            'show_on_ticket_form'    => 0,
            'only_admin'             => 0,
            'show_on_table'          => 0,
            'show_on_client_portal'  => 0,
            'disalow_client_to_edit' => 0,
            'bs_column'              => 12,
        ]);
    }
}

function capi_meta_on_lead_status_changed($data)
{
    $CI = &get_instance();

    $leadId    = isset($data['lead_id']) ? (int) $data['lead_id'] : 0;
    $newStatus = isset($data['new_status']) ? (int) $data['new_status'] : 0;

    if (!$leadId || !$newStatus) {
        return;
    }

    $lead = $CI->db->get_where(db_prefix() . 'leads', ['id' => $leadId])->row();
    if (!$lead) {
        return;
    }

    $statusRow  = $CI->db->get_where(db_prefix() . 'leads_status', ['id' => $newStatus])->row();
    $statusName = $statusRow ? $statusRow->name : ('status_' . $newStatus);

    // Só os estados configurados disparam, e só lead_qualified.
    // O event_id determinístico ("pfx{id}_lead_qualified") garante que a
    // Meta conta o evento uma única vez por lead, mesmo que passe por
    // Proposta Enviada e depois pelos VIPs.
    $s = trim(mb_strtolower($statusName));
    if (!in_array($s, capi_meta_qualifying_statuses(), true)) {
        return;
    }

    $fbLeadId = capi_meta_get_fb_lead_id($CI, $leadId);
    capi_meta_send($leadId, $fbLeadId, $lead, 'lead_qualified', $statusName);
}

function capi_meta_send($leadId, $fbLeadId, $lead, $eventName, $statusName)
{
    $payload = [
        'lead_id'        => $fbLeadId ?: '',
        'event_id'       => 'pfx' . $leadId . '_' . $eventName,
        'event_name'     => $eventName,
        'email'          => $lead->email ?? '',
        'phone'          => $lead->phonenumber ?? '',
        'status_name'    => $statusName,
        'perfex_lead_id' => $leadId,
    ];

    capi_meta_post_json(CAPI_META_WEBHOOK, $payload);

    // v1.4: despacho paralelo para Google Ads Offline Conversions (se a lead tiver GCLID
    // e o webhook estiver configurado em Configuracao > Definicoes: capi_google_webhook_url)
    $googleWebhook = function_exists('get_option') ? trim((string) get_option('capi_google_webhook_url')) : '';
    if ($googleWebhook === '') { $googleWebhook = 'https://hook.eu1.make.com/jpyrlhe64zx3xkeijhvxxvjnci4o9rb8'; }
    if ($googleWebhook !== '') {
        $CI =& get_instance();
        $gclid = capi_meta_get_gclid($CI, $leadId);
        if (!empty($gclid)) {
            capi_meta_post_json($googleWebhook, [
                'gclid'          => $gclid,
                'event_name'     => $eventName,
                'status_name'    => $statusName,
                'perfex_lead_id' => $leadId,
                'email'          => $lead->email ?? '',
                'phone'          => $lead->phonenumber ?? '',
                'event_time'     => date('c'),
            ]);
        }
    }
}

function capi_meta_get_gclid($CI, $leadId)
{
    $row = $CI->db->select('cv.value')
        ->from(db_prefix() . 'customfieldsvalues cv')
        ->join(db_prefix() . 'customfields cf', 'cf.id = cv.fieldid')
        ->where('cv.relid', $leadId)
        ->where('cv.fieldto', 'leads')
        ->group_start()
            ->where('cf.slug', 'leads_gclid')
            ->or_where('cf.name', 'GCLID')
        ->group_end()
        ->get()->row();

    return ($row && !empty($row->value)) ? trim($row->value) : null;
}

function capi_meta_get_fb_lead_id($CI, $leadId)
{
    $row = $CI->db->select('cv.value')
        ->from(db_prefix() . 'customfieldsvalues cv')
        ->join(db_prefix() . 'customfields cf', 'cf.id = cv.fieldid')
        ->where('cv.relid', $leadId)
        ->where('cv.fieldto', 'leads')
        ->group_start()
            ->where('cf.slug', CAPI_META_CF_SLUG)
            ->or_like('cf.slug', 'lead_id')
            ->or_like('cf.name', 'Facebook Lead')
        ->group_end()
        ->get()->row();

    return ($row && !empty($row->value)) ? trim($row->value) : null;
}

function capi_meta_post_json($url, $payload)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error && function_exists('log_activity')) {
        log_activity('CAPI Meta: falha ao enviar evento - ' . $error);
    }

    return $response;
}
