<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: CAPI Meta - DPS
Description: Envia o funil de leads para a Meta Conversions API via Make (funil cumulativo + estados VIP)
Version: 1.3.0
Requires at least: 2.3.*
Author: DPS Imobiliario
*/

define('CAPI_META_WEBHOOK', 'https://hook.eu1.make.com/w14e5b8einkrdv49sc8l8lubgb0kudkb');
define('CAPI_META_CF_SLUG', 'leads_facebook_lead_id');

/**
 * ESTADOS VIP — substituir pelos nomes EXATOS dos 3 estados no Perfex.
 * Quando um lead entra num destes estados, a Meta recebe o funil completo
 * até ao nível indicado (incluindo os eventos das fases anteriores).
 */
function capi_meta_vip_statuses()
{
    return [
        // 'nome exato do estado' => nível do funil (1-4, ver capi_meta_funnel)
        'VIP 1' => 2,
        'VIP 2' => 2,
        'VIP 3' => 2,
    ];
}

/**
 * Funil ordenado. Atingir o nível N envia TODOS os eventos de 1 até N.
 */
function capi_meta_funnel()
{
    return [
        1 => 'lead_qualified',
        2 => 'opportunity',
        3 => 'meeting_scheduled',
        4 => 'converted_lead',
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

    // 1. Estado VIP? -> nível definido na configuração
    $level = capi_meta_vip_level($statusName);

    // 2. Senão, mapear pelo nome
    if ($level === null) {
        $level = capi_meta_map_level($statusName);
    }

    if ($level === null) {
        return; // estado neutro (ex: Novo) ou desconhecido
    }

    $fbLeadId = capi_meta_get_fb_lead_id($CI, $leadId);
    $funnel   = capi_meta_funnel();

    if ($level === 0) {
        // Desqualificado: evento único, não cumulativo
        capi_meta_send($leadId, $fbLeadId, $lead, 'lead_disqualified', $statusName);
        return;
    }

    // FUNIL CUMULATIVO: envia todos os eventos de 1 até $level.
    // O event_id determinístico ("pfx{id}_{evento}") evita duplicados na Meta.
    foreach ($funnel as $lvl => $eventName) {
        if ($lvl > $level) {
            break;
        }
        capi_meta_send($leadId, $fbLeadId, $lead, $eventName, $statusName);
    }
}

function capi_meta_vip_level($statusName)
{
    $s = trim(mb_strtolower($statusName));
    foreach (capi_meta_vip_statuses() as $vip => $level) {
        if (trim(mb_strtolower($vip)) === $s) {
            return (int) $level;
        }
    }
    return null;
}

/**
 * Mapeia estados normais para o nível do funil.
 * Devolve 0 para desqualificado, null para estados neutros.
 */
function capi_meta_map_level($statusName)
{
    $s = mb_strtolower($statusName);

    if (strpos($s, 'convert') !== false || strpos($s, 'client') !== false || strpos($s, 'ganho') !== false || strpos($s, 'vend') !== false || strpos($s, 'cpcv') !== false) {
        return 4;
    }
    if (strpos($s, 'agend') !== false || strpos($s, 'visita') !== false || strpos($s, 'reuni') !== false) {
        return 3;
    }
    if (strpos($s, 'proposta') !== false || strpos($s, 'quente') !== false || strpos($s, 'interess') !== false || strpos($s, 'oportunidade') !== false) {
        return 2;
    }
    if (strpos($s, 'qualific') !== false && strpos($s, 'desqualific') === false) {
        return 1;
    }
    if (strpos($s, 'frio') !== false || strpos($s, 'perdid') !== false || strpos($s, 'desqualific') !== false) {
        return 0;
    }

    return null;
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
