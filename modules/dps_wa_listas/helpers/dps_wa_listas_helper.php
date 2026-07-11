<?php

defined('BASEPATH') or exit('No direct script access allowed');

/* -------------------------------------------------------------------------
 *  Configuração / Evolution API (reutiliza as opções do módulo dps_whatsapp)
 * ---------------------------------------------------------------------- */

function dps_wa_listas_evo_url()
{
    $u = get_option('dps_whatsapp_evolution_url');
    return rtrim($u ?: '', '/');
}

function dps_wa_listas_evo_key()
{
    return get_option('dps_whatsapp_evolution_api_key');
}

/**
 * Pedido à Evolution API. Timeouts curtos para nunca prender o cron.
 *
 * @return array [ok(bool), http(int), data(mixed), error(string)]
 */
function dps_wa_listas_evo_request($method, $path, $body = null)
{
    $url = dps_wa_listas_evo_url();
    $key = dps_wa_listas_evo_key();
    if (empty($url) || empty($key)) {
        return ['ok' => false, 'http' => 0, 'data' => null, 'error' => 'Evolution URL/key em falta'];
    }

    $ch = curl_init($url . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'apikey: ' . $key,
        ],
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'http' => $http, 'data' => null, 'error' => $err ?: 'sem resposta'];
    }

    $data = json_decode($raw, true);
    return ['ok' => ($http >= 200 && $http < 300), 'http' => $http, 'data' => ($data === null ? $raw : $data), 'error' => ''];
}

function dps_wa_listas_instance_name($staff_id)
{
    return 'staff-' . (int) $staff_id;
}

/**
 * Etiquetas existentes numa instância → array [ nome_minusculas => id ].
 */
function dps_wa_listas_find_labels($instance)
{
    $res = dps_wa_listas_evo_request('GET', '/label/findLabels/' . rawurlencode($instance));
    $map = [];
    if ($res['ok'] && is_array($res['data'])) {
        $list = isset($res['data']['labels']) ? $res['data']['labels'] : $res['data'];
        if (is_array($list)) {
            foreach ($list as $lbl) {
                if (! is_array($lbl)) {
                    continue;
                }
                $name = isset($lbl['name']) ? $lbl['name'] : (isset($lbl['label']) ? $lbl['label'] : null);
                $id   = isset($lbl['id']) ? $lbl['id'] : (isset($lbl['labelId']) ? $lbl['labelId'] : null);
                if ($name !== null && $id !== null) {
                    $map[mb_strtolower(trim($name))] = (string) $id;
                }
            }
        }
    }
    return $map;
}

/**
 * Adiciona ou remove uma etiqueta a uma conversa (número) numa instância.
 */
function dps_wa_listas_handle_label($instance, $number, $label_id, $action)
{
    return dps_wa_listas_evo_request('POST', '/label/handleLabel/' . rawurlencode($instance), [
        'number'  => $number,
        'labelId' => (string) $label_id,
        'action'  => $action, // 'add' | 'remove'
    ]);
}

/**
 * Envia uma mensagem de texto por uma instância.
 */
function dps_wa_listas_send_text($instance, $number, $text)
{
    return dps_wa_listas_evo_request('POST', '/message/sendText/' . rawurlencode($instance), [
        'number'      => $number,
        'textMessage' => ['text' => $text],
    ]);
}

/**
 * Número (só dígitos) do dono da instância — para notificar o próprio comercial.
 */
function dps_wa_listas_instance_owner($instance)
{
    $res = dps_wa_listas_evo_request('GET', '/instance/fetchInstances?instanceName=' . rawurlencode($instance));
    if ($res['ok'] && is_array($res['data'])) {
        $d    = $res['data'];
        $inst = isset($d['instance']) ? $d['instance'] : (isset($d[0]['instance']) ? $d[0]['instance'] : $d);
        $owner = is_array($inst) && isset($inst['owner']) ? $inst['owner'] : null;
        if ($owner) {
            $parts = explode('@', $owner);
            return preg_replace('/[^0-9]/', '', $parts[0]);
        }
    }
    return null;
}

/**
 * Estados que disparam notificação ao comercial (por omissão: os VIP).
 */
function dps_wa_listas_notify_statuses()
{
    $v = get_option('dps_wa_listas_notify_statuses');
    if ($v === '' || $v === null) {
        return [17, 14, 18];
    }
    return array_values(array_filter(array_map('intval', explode(',', $v))));
}

/* -------------------------------------------------------------------------
 *  Fila
 * ---------------------------------------------------------------------- */

function dps_wa_listas_resolve_status_id($value)
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_numeric($value)) {
        return (int) $value;
    }
    $CI  = &get_instance();
    $row = $CI->db->select('id')->where('name', $value)->get(db_prefix() . 'leads_status')->row();
    return $row ? (int) $row->id : null;
}

/**
 * Handler do hook lead_status_changed — apenas enfileira (rápido).
 */
function dps_wa_listas_enqueue($data)
{
    if (get_option('dps_wa_listas_enabled') == '0') {
        return $data;
    }

    $CI = &get_instance();

    // Segurança: se a tabela da fila ainda não existir, não faz nada
    // (evita partir a mudança de estado da lead).
    if (! $CI->db->table_exists(db_prefix() . 'dps_wa_label_queue')) {
        return $data;
    }

    $lead_id = null;
    if (is_array($data)) {
        $lead_id = isset($data['lead_id']) ? $data['lead_id'] : (isset($data[0]) ? $data[0] : null);
    } else {
        $lead_id = $data;
    }
    $lead_id = (int) $lead_id;
    if (! $lead_id) {
        return $data;
    }

    $lead = $CI->db->select('id, status, assigned')->where('id', $lead_id)->get(db_prefix() . 'leads')->row();
    if (! $lead) {
        return $data;
    }

    $old_id = is_array($data) && isset($data['old_status']) ? dps_wa_listas_resolve_status_id($data['old_status']) : null;

    $CI->db->insert(db_prefix() . 'dps_wa_label_queue', [
        'lead_id'       => $lead->id,
        'staff_id'      => $lead->assigned ? (int) $lead->assigned : null,
        'old_status_id' => $old_id,
        'new_status_id' => (int) $lead->status,
        'status'        => 'pending',
        'created_at'    => date('Y-m-d H:i:s'),
    ]);
    $qid = $CI->db->insert_id();

    // Como o cron do Perfex não é fiável aqui, processamos JÁ (síncrono) a
    // entrada acabada de inserir — mas com um limite por request para não
    // prender ações em massa (o resto fica pendente para o cron/retry).
    static $sync_done = 0;
    if ($qid && $sync_done < 2) {
        $sync_done++;
        $row = $CI->db->where('id', $qid)->get(db_prefix() . 'dps_wa_label_queue')->row();
        if ($row) {
            dps_wa_listas_process_one($row);
        }
    }

    return $data;
}

/**
 * Processa a fila (chamado pelo cron do Perfex).
 */
function dps_wa_listas_process_queue()
{
    $CI = &get_instance();

    if (! $CI->db->table_exists(db_prefix() . 'dps_wa_label_queue')) {
        return;
    }

    $rows = $CI->db->where('status', 'pending')
        ->where('attempts <', 5)
        ->order_by('id', 'ASC')
        ->limit(25)
        ->get(db_prefix() . 'dps_wa_label_queue')
        ->result();

    foreach ($rows as $r) {
        dps_wa_listas_process_one($r);
    }
}

function dps_wa_listas_process_one($r)
{
    $CI  = &get_instance();
    $now = date('Y-m-d H:i:s');

    $set = function ($status, $error = null) use ($CI, $r, $now) {
        $CI->db->where('id', $r->id)->update(db_prefix() . 'dps_wa_label_queue', [
            'status'       => $status,
            'attempts'     => (int) $r->attempts + 1,
            'last_error'   => $error,
            'processed_at' => $now,
        ]);
    };

    // Precisa de comercial atribuído.
    if (empty($r->staff_id)) {
        return $set('skipped', 'sem comercial atribuído');
    }

    // A instância desse comercial tem de estar ligada.
    $cfg = $CI->db->select('is_connected')->where('staff_id', (int) $r->staff_id)
        ->get(db_prefix() . 'dps_whatsapp_config')->row();
    if (! $cfg || (int) $cfg->is_connected !== 1) {
        return $set('skipped', 'WhatsApp do comercial não ligado');
    }

    // Dados da lead.
    $lead     = $CI->db->select('name, phonenumber')->where('id', (int) $r->lead_id)
        ->get(db_prefix() . 'leads')->row();
    $number   = $lead ? preg_replace('/[^0-9]/', '', (string) $lead->phonenumber) : '';
    $new_name = dps_wa_listas_status_name($r->new_status_id);
    $old_name = dps_wa_listas_status_name($r->old_status_id);
    $instance = dps_wa_listas_instance_name($r->staff_id);

    $notes     = [];
    $notified  = false;

    // (1) NOTIFICAÇÃO ao comercial — fiável, não depende de etiquetas.
    if (get_option('dps_wa_listas_notify_agent') == '1'
        && in_array((int) $r->new_status_id, dps_wa_listas_notify_statuses(), true)) {
        $owner = dps_wa_listas_instance_owner($instance);
        if ($owner) {
            $msg = '🔔 *' . ($new_name ?: 'Novo estado') . "*\n"
                 . 'Lead: ' . ($lead && $lead->name ? $lead->name : ('#' . (int) $r->lead_id)) . "\n"
                 . ($number !== '' ? ('Tel: ' . $number . "\n") : '')
                 . 'Abrir: ' . admin_url('leads/index/' . (int) $r->lead_id);
            $resN = dps_wa_listas_send_text($instance, $owner, $msg);
            if ($resN['ok']) {
                $notified = true;
                $notes[]  = 'notificado';
            } else {
                $notes[] = 'notif falhou: ' . ($resN['error'] ?: ('HTTP ' . $resN['http']));
            }
        } else {
            $notes[] = 'notif: sem número do comercial';
        }
    }

    // (2) ETIQUETA nativa — best-effort (requer WhatsApp Business com etiquetas).
    $labelled = false;
    if ($number === '') {
        $notes[] = 'etiqueta: lead sem telefone';
    } else {
        $labels = dps_wa_listas_find_labels($instance);
        if (empty($labels)) {
            $notes[] = 'etiqueta: conta sem etiquetas (criar no WhatsApp Business)';
        } else {
            if ($new_name) {
                $k = mb_strtolower(trim($new_name));
                if (isset($labels[$k])) {
                    $res = dps_wa_listas_handle_label($instance, $number, $labels[$k], 'add');
                    if ($res['ok']) {
                        $labelled = true;
                    } else {
                        $notes[] = 'add "' . $new_name . '": ' . ($res['error'] ?: ('HTTP ' . $res['http']));
                    }
                } else {
                    $notes[] = 'etiqueta "' . $new_name . '" não existe';
                }
            }
            if ($old_name && $old_name !== $new_name) {
                $k = mb_strtolower(trim($old_name));
                if (isset($labels[$k])) {
                    $res = dps_wa_listas_handle_label($instance, $number, $labels[$k], 'remove');
                    if (! $res['ok']) {
                        $notes[] = 'remove "' . $old_name . '": ' . ($res['error'] ?: ('HTTP ' . $res['http']));
                    }
                }
            }
        }
    }

    $done = $notified || $labelled;
    return $set($done ? 'done' : 'skipped', implode(' | ', $notes) ?: null);
}

function dps_wa_listas_status_name($status_id)
{
    if (! $status_id) {
        return null;
    }
    $CI  = &get_instance();
    $row = $CI->db->select('name')->where('id', (int) $status_id)->get(db_prefix() . 'leads_status')->row();
    return $row ? $row->name : null;
}

/**
 * Mapa escalonado estado -> evento Meta (CAPI). Só marcos de qualidade.
 */
function dps_capi_event_map()
{
    return [
        17 => 'lead_qualified', // VIP 1  (nível 2 - qualificado)
        14 => 'opportunity',    // VIP 2  (nível 3 - oportunidade)
        18 => 'Purchase',       // VIP 3  (nível 4 - convertido)
        13 => 'Purchase',       // Concretizado (nível 4 - convertido)
    ];
}

/**
 * POST fire-and-forget ao webhook do Make (que reencaminha para a Meta CAPI).
 */
function dps_capi_send($event_name, $status_name, $email, $phone, $lead_id)
{
    $url = 'https://hook.eu1.make.com/w14e5b8einkrdv49sc8l8lubgb0kudkb';
    // NB: não enviamos 'lead_id' — o cenário coloca-o em user_data, onde a Meta exige um
    // lead ID real do Facebook; o ID do CRM daria 400. O match é por email+telefone.
    $payload = json_encode([
        'event_name'  => $event_name,
        'event_id'    => $event_name . '_' . (int) $lead_id, // dedup por lead+marco
        'status_name' => (string) $status_name,
        'email'       => (string) $email,
        'phone'       => (string) $phone,
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 6,
    ]);
    @curl_exec($ch);
    @curl_close($ch);
}

/**
 * Handler do hook lead_status_changed: dispara o evento CAPI conforme o estado atual.
 */
function dps_capi_status_changed($data)
{
    $CI = &get_instance();
    $lead_id = null;
    if (is_array($data)) {
        $lead_id = isset($data['lead_id']) ? $data['lead_id'] : (isset($data[0]) ? $data[0] : null);
    } else {
        $lead_id = $data;
    }
    $lead_id = (int) $lead_id;
    if (! $lead_id) {
        return $data;
    }
    $lead = $CI->db->select('id, status, email, phonenumber')->where('id', $lead_id)->get(db_prefix() . 'leads')->row();
    if (! $lead) {
        return $data;
    }
    $map = dps_capi_event_map();
    $sid = (int) $lead->status;
    if (! isset($map[$sid])) {
        return $data; // não é marco de qualidade -> não envia
    }
    $st = $CI->db->select('name')->where('id', $sid)->get(db_prefix() . 'leads_status')->row();
    dps_capi_send($map[$sid], $st ? $st->name : ('status_' . $sid), $lead->email, $lead->phonenumber, $lead_id);
    return $data;
}
