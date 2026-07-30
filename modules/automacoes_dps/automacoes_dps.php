<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Automações DPS — SMS, Email & Sofia
Description: Central de automações por estado de lead com três canais: SMS (gateway Android/SIM do comercial), Email (SMTP do Perfex) e Chamada Sofia (agente ElevenLabs à escolha, com contexto personalizado). Envio em massa por estado, fila de follow-ups, email enviado do endereço do próprio comercial, guiões Sofia pré-definidos pelo admin e tarefa criada para o comercial que dispara as chamadas.
Version: 2.1.1
Author: Grupo DPS
Requires at least: 2.3.*
*/

define('SMS_CENTRAL_MODULE', 'automacoes_dps');

register_activation_hook(SMS_CENTRAL_MODULE, 'sms_central_activation_hook');

function sms_central_activation_hook()
{
    require __DIR__ . '/install.php';
}

// ============================================================
// MENU
// ============================================================

hooks()->add_action('admin_init', 'smsc_init_menu');
hooks()->add_action('admin_init', 'smsc_maybe_upgrade');

function smsc_init_menu()
{
    // O item de menu vive agora no módulo dps_automacao: parent "Automações"
    // (slug sms-central) com o filho "Envio" a apontar para esta página.
    // Registar aqui outra vez duplicava o item na lateral.
    return;
}

// Escrita robusta de options (update_option do Perfex não cria se não existir)
function smsc_set_option($name, $value)
{
    $CI = &get_instance();
    $CI->db->where('name', $name);
    if ($CI->db->count_all_results(db_prefix() . 'options') > 0) {
        $CI->db->where('name', $name)->update(db_prefix() . 'options', ['value' => $value]);
    } else {
        $CI->db->insert(db_prefix() . 'options', ['name' => $name, 'value' => $value, 'autoload' => 1]);
    }
}

// ============================================================
// UPGRADE DE SCHEMA (corre uma vez, cria o que faltar)
// ============================================================

function smsc_maybe_upgrade()
{
    if (smsc_get_option('smsc_schema_v') === '4') {
        return;
    }
    $CI = &get_instance();
    if (!$CI->db->table_exists(db_prefix() . 'smsc_sofia_scripts')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . "smsc_sofia_scripts` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(191) NOT NULL,
            `text` TEXT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    // Migração de tabelas criadas pela v2.0
    $autoCols = [
        'channel'   => "VARCHAR(16) NOT NULL DEFAULT 'sms'",
        'subject'   => 'VARCHAR(255) NULL DEFAULT NULL',
        'agent_id'  => 'VARCHAR(128) NULL DEFAULT NULL',
        'script_id' => 'INT(11) NULL DEFAULT NULL',
    ];
    if ($CI->db->table_exists(db_prefix() . 'smsc_automations')) {
        foreach ($autoCols as $col => $def) {
            if (!$CI->db->field_exists($col, db_prefix() . 'smsc_automations')) {
                $CI->db->query('ALTER TABLE `' . db_prefix() . 'smsc_automations` ADD COLUMN `' . $col . '` ' . $def);
            }
        }
    }
    if ($CI->db->table_exists(db_prefix() . 'smsc_queue')) {
        $queueCols = [
            'channel'      => "VARCHAR(16) NOT NULL DEFAULT 'sms'",
            'destination'  => 'VARCHAR(191) NOT NULL DEFAULT \'\'',
            'requested_by' => 'INT(11) NOT NULL DEFAULT 0',
        ];
        foreach ($queueCols as $col => $def) {
            if (!$CI->db->field_exists($col, db_prefix() . 'smsc_queue')) {
                $CI->db->query('ALTER TABLE `' . db_prefix() . 'smsc_queue` ADD COLUMN `' . $col . '` ' . $def);
            }
        }
        if ($CI->db->field_exists('phone', db_prefix() . 'smsc_queue')) {
            $CI->db->query('UPDATE `' . db_prefix() . "smsc_queue` SET destination = phone WHERE destination = ''");
        }
    }
    smsc_set_option('smsc_schema_v', '4');
}

// ============================================================
// CONFIG (options do Perfex)
// ============================================================

function smsc_ensure_schema()
{
    $CI = &get_instance();
    if (!$CI->db->table_exists(db_prefix() . 'smsc_scripts')) {
        $CI->db->query('CREATE TABLE `' . db_prefix() . "smsc_scripts` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(191) NOT NULL,
            `text` TEXT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
    if (!$CI->db->field_exists('script_id', db_prefix() . 'smsc_automations')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'smsc_automations` ADD COLUMN `script_id` INT(11) NULL DEFAULT NULL AFTER `agent_id`');
    }
}

function smsc_api_url()
{
    $url = smsc_get_option('smsc_api_url');
    return $url ?: 'https://api.sms-gate.app/3rdparty/v1/message';
}

function smsc_get_option($name)
{
    $CI = &get_instance();
    $CI->db->select('value')->where('name', $name);
    $row = $CI->db->get(db_prefix() . 'options')->row();
    return $row ? $row->value : false;
}

function smsc_users()
{
    $raw = smsc_get_option('smsc_users');
    $arr = $raw ? json_decode($raw, true) : [];
    return is_array($arr) ? $arr : [];
}

function smsc_user_for_staff($staffId)
{
    $users = smsc_users();
    if (isset($users[$staffId])) {
        return $users[$staffId] + ['staff_id' => $staffId];
    }
    return null; // sem fallback: cada comercial so envia pelo seu gateway
}

function smsc_eleven_key()
{
    return (string) smsc_get_option('smsc_eleven_key');
}

function smsc_eleven_phone_id()
{
    return (string) smsc_get_option('smsc_eleven_phone_id');
}

// Lista de agentes ElevenLabs (cache 5 min)
function smsc_eleven_agents()
{
    $key = smsc_eleven_key();
    if ($key === '') {
        return [];
    }
    $CI = &get_instance();
    $cached = $CI->app_object_cache->get('smsc_agents');
    if ($cached) {
        return $cached;
    }
    $ch = curl_init('https://api.elevenlabs.io/v1/convai/agents?page_size=50');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['xi-api-key: ' . $key],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $agents = [];
    if ($code === 200 && $resp) {
        $data = json_decode($resp, true);
        foreach (($data['agents'] ?? []) as $a) {
            $agents[] = ['id' => $a['agent_id'] ?? '', 'name' => $a['name'] ?? ($a['agent_id'] ?? '')];
        }
    }
    $CI->app_object_cache->add('smsc_agents', $agents);
    return $agents;
}

// ============================================================
// AGENDAMENTO — mudança de estado / criação de lead
// ============================================================

hooks()->add_action('lead_status_changed', 'smsc_on_status_changed');
hooks()->add_action('lead_created', 'smsc_on_lead_created');
hooks()->add_action('after_lead_added', 'smsc_on_lead_created');

function smsc_on_lead_created($param)
{
    $leadId = is_array($param) ? ($param['lead_id'] ?? ($param['id'] ?? 0)) : (int) $param;
    if (!$leadId) {
        return;
    }
    $CI = &get_instance();
    $cacheKey = 'smsc_created_' . $leadId;
    if ($CI->app_object_cache->get($cacheKey)) {
        return;
    }
    $CI->app_object_cache->add($cacheKey, true);

    $CI->db->where('id', $leadId);
    $lead = $CI->db->get(db_prefix() . 'leads')->row();
    if ($lead) {
        smsc_schedule_for_lead($lead, (int) $lead->status);
    }
}

function smsc_on_status_changed($data)
{
    if (!is_array($data)) {
        return;
    }
    $leadId    = (int) ($data['lead_id'] ?? 0);
    $newStatus = (int) ($data['new_status'] ?? 0);
    if (!$leadId) {
        return;
    }
    $CI = &get_instance();

    $CI->db->where('lead_id', $leadId)->where('status', 'Pendente');
    $CI->db->update(db_prefix() . 'smsc_queue', ['status' => 'Cancelado']);

    $CI->db->where('id', $leadId);
    $lead = $CI->db->get(db_prefix() . 'leads')->row();
    if ($lead) {
        smsc_schedule_for_lead($lead, $newStatus);
    }
}

function smsc_schedule_for_lead($lead, $statusId)
{
    $CI = &get_instance();
    $CI->db->where('status_id', $statusId)->where('active', 1);
    $autos = $CI->db->get(db_prefix() . 'smsc_automations')->result();

    foreach ($autos as $auto) {
        $dest = smsc_destination_for($auto->channel, $lead);
        if (!$dest) {
            continue; // lead sem telefone/email para este canal
        }
        $CI->db->where('lead_id', $lead->id)
               ->where('automation_id', $auto->id)
               ->where('status', 'Pendente');
        if ($CI->db->count_all_results(db_prefix() . 'smsc_queue') > 0) {
            continue;
        }
        $CI->db->insert(db_prefix() . 'smsc_queue', [
            'lead_id'       => $lead->id,
            'automation_id' => $auto->id,
            'channel'       => $auto->channel,
            'destination'   => $dest,
            'staff_id'      => (int) ($lead->assigned ?? 0),
            'scheduled_at'  => date('Y-m-d H:i:s', time() + ((int) $auto->days * 86400)),
            'status'        => 'Pendente',
        ]);
    }
}

function smsc_destination_for($channel, $lead)
{
    if ($channel === 'email') {
        $email = trim((string) ($lead->email ?? ''));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
    return smsc_normalize_phone($lead->phonenumber ?? '');
}

// ============================================================
// CRON — processa a fila
// ============================================================

hooks()->add_action('after_cron_run', 'smsc_process_queue_cron');

function smsc_process_queue_cron()
{
    smsc_process_queue(50);
}

function smsc_process_queue($limit = 50)
{
    $CI = &get_instance();
    $CI->db->where('status', 'Pendente')
           ->where('scheduled_at <=', date('Y-m-d H:i:s'))
           ->order_by('scheduled_at', 'ASC')
           ->limit($limit);
    $items = $CI->db->get(db_prefix() . 'smsc_queue')->result();

    $sent = 0;
    $failed = 0;
    foreach ($items as $item) {
        $CI->db->where('id', $item->automation_id);
        $auto = $CI->db->get(db_prefix() . 'smsc_automations')->row();
        $CI->db->where('id', $item->lead_id);
        $lead = $CI->db->get(db_prefix() . 'leads')->row();

        if (!$auto || !$lead) {
            $CI->db->where('id', $item->id);
            $CI->db->update(db_prefix() . 'smsc_queue', ['status' => 'Cancelado']);
            continue;
        }

        $user = smsc_user_for_staff((int) $item->staff_id) ?: ['name' => get_option('companyname'), 'phone' => '', 'login' => '', 'password' => ''];

        $ok = false;
        switch ($item->channel) {
            case 'sms':
                if (!empty($user['login'])) {
                    $ok = smsc_send_sms($user, $item->destination, smsc_render($auto->message, $lead, $user));
                }
                break;
            case 'email':
                $ok = smsc_send_email($item->destination, smsc_render($auto->subject ?: $auto->name, $lead, $user), smsc_render($auto->message, $lead, $user), (int) $item->staff_id);
                break;
            case 'sofia':
                $scriptText = $auto->message;
                if (!empty($auto->script_id)) {
                    $CI->db->where('id', (int) $auto->script_id);
                    $script = $CI->db->get(db_prefix() . 'smsc_scripts')->row();
                    if ($script) {
                        $scriptText = $script->text;
                    }
                }
                $ok = smsc_send_sofia($auto->agent_id, $item->destination, smsc_render($scriptText, $lead, $user), $lead);
                if ($ok) {
                    smsc_create_task_for_staff($lead, (int) $item->staff_id, $auto->name);
                }
                break;
        }

        $CI->db->where('id', $item->id);
        $CI->db->update(db_prefix() . 'smsc_queue', [
            'status'  => $ok ? 'Enviado' : 'Falhado',
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        $canal = ['sms' => 'SMS', 'email' => 'Email', 'sofia' => 'Chamada Sofia'][$item->channel] ?? $item->channel;
        smsc_log($lead->id, $ok
            ? $canal . ' automático "' . $auto->name . '" (' . $user['name'] . ') para ' . $item->destination . '.'
            : 'FALHA em ' . $canal . ' "' . $auto->name . '" para ' . $item->destination . '.');

        $ok ? $sent++ : $failed++;
        usleep($item->channel === 'sofia' ? 1000000 : 400000);
    }
    return ['sent' => $sent, 'failed' => $failed, 'processed' => count($items)];
}

// ============================================================
// RENDER
// ============================================================

function smsc_render($template, $lead, array $user)
{
    $firstName = trim(explode(' ', trim($lead->name ?? ''))[0] ?? '');
    $waLink = !empty($user['phone'])
        ? 'https://wa.me/' . $user['phone'] . '?text=' . rawurlencode('Olá! Recebi a sua mensagem e quero continuar por aqui.')
        : '';
    return strtr((string) $template, [
        '{{nome}}'      => $firstName !== '' ? $firstName : 'caro cliente',
        '{nome}'        => $firstName !== '' ? $firstName : 'caro cliente',
        '{{whatsapp}}'  => $waLink,
        '{whatsapp}'    => $waLink,
        '{{comercial}}' => $user['name'] ?? '',
        '{comercial}'   => $user['name'] ?? '',
    ]);
}

// ============================================================
// CANAIS DE ENVIO
// ============================================================

function smsc_send_sms(array $user, $phone, $message)
{
    $payload = json_encode([
        'textMessage'  => ['text' => $message],
        'phoneNumbers' => [$phone],
    ]);
    $ch = curl_init(smsc_api_url());
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_USERPWD        => $user['login'] . ':' . $user['password'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    @file_put_contents(__DIR__ . '/sms_debug.log', date('H:i:s') . ' | dest=' . $phone . ' | HTTP ' . $code . ' | curl_err=' . curl_error($ch) . ' | resp: ' . substr((string) $response, 0, 500) . PHP_EOL, FILE_APPEND);
    if ($code >= 200 && $code < 300) {
        return true;
    }
    log_message('error', '[Automacoes DPS][SMS] HTTP ' . $code . ' — ' . substr((string) $response, 0, 400));
    return false;
}

function smsc_send_email($to, $subject, $body, $staffId = 0)
{
    $CI = &get_instance();
    try {
        $CI->load->library('email');
        $CI->email->clear(true);
        $CI->email->initialize([
            'protocol'    => get_option('mail_engine') === 'codeigniter' && get_option('email_protocol') ? get_option('email_protocol') : 'smtp',
            'smtp_host'   => get_option('smtp_host'),
            'smtp_user'   => get_option('smtp_username'),
            'smtp_pass'   => get_option('smtp_password'),
            'smtp_port'   => (int) get_option('smtp_port'),
            'smtp_crypto' => get_option('smtp_encryption'),
            'mailtype'    => 'html',
            'charset'     => 'utf-8',
            'newline'     => "\r\n",
        ]);
        $fromEmail = get_option('smtp_email') ?: get_option('companyemail');
        $fromName  = get_option('companyname');
        $replyTo   = null;
        if ($staffId) {
            $CI->db->where('staffid', (int) $staffId);
            $staff = $CI->db->get(db_prefix() . 'staff')->row();
            if ($staff && filter_var($staff->email, FILTER_VALIDATE_EMAIL)) {
                $fromEmail = $staff->email;
                $fromName  = trim($staff->firstname . ' ' . $staff->lastname);
                $replyTo   = $staff->email;
            }
        }
        $CI->email->from($fromEmail, $fromName);
        if ($replyTo) {
            $CI->email->reply_to($replyTo, $fromName);
        }
        $CI->email->to($to);
        $CI->email->subject($subject);
        $CI->email->message(nl2br($body));
        if ($CI->email->send()) {
            return true;
        }
        log_message('error', '[Automacoes DPS][Email] ' . $CI->email->print_debugger(['headers']));
        return false;
    } catch (\Throwable $e) {
        log_message('error', '[Automacoes DPS][Email] ' . $e->getMessage());
        return false;
    }
}

// Chamada outbound via ElevenLabs Conversational AI (Twilio)
function smsc_send_sofia($agentId, $phone, $contextText, $lead)
{
    $key     = smsc_eleven_key();
    $phoneId = smsc_eleven_phone_id();
    if ($key === '' || $phoneId === '' || !$agentId) {
        log_message('error', '[Automacoes DPS][Sofia] Config em falta (API key / phone id / agente).');
        return false;
    }
    $firstName = trim(explode(' ', trim($lead->name ?? ''))[0] ?? '');
    $payload = json_encode([
        'agent_id'               => $agentId,
        'agent_phone_number_id'  => $phoneId,
        'to_number'              => $phone,
        'conversation_initiation_client_data' => [
            'dynamic_variables' => [
                'nome_lead' => $firstName,
                'contexto'  => $contextText,
            ],
            'conversation_config_override' => [
                'agent' => [
                    'prompt' => ['prompt' => $contextText],
                ],
            ],
        ],
    ]);
    $ch = curl_init('https://api.elevenlabs.io/v1/convai/twilio/outbound-call');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'xi-api-key: ' . $key],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code >= 200 && $code < 300) {
        return true;
    }
    log_message('error', '[Automacoes DPS][Sofia] HTTP ' . $code . ' — ' . substr((string) $response, 0, 400));
    return false;
}

// Texto da chamada Sofia: guião pré-definido pelo admin
function smsc_sofia_text($auto)
{
    if (!empty($auto->script_id)) {
        $CI = &get_instance();
        $CI->db->where('id', (int) $auto->script_id);
        $s = $CI->db->get(db_prefix() . 'smsc_sofia_scripts')->row();
        if ($s) {
            return $s->text;
        }
    }
    return $auto->message;
}

// Tarefa no Perfex para o comercial que pediu a chamada
function smsc_create_sofia_task($lead, $auto, $staffId)
{
    if (!$staffId) {
        return;
    }
    $CI = &get_instance();
    $CI->db->insert(db_prefix() . 'tasks', [
        'name'        => 'Sofia ligou: ' . ($lead->name ?: ('Lead #' . $lead->id)) . ' — verificar interesse',
        'description' => 'Chamada automática da Sofia (automação "' . $auto->name . '"). Verificar resultado e interesse do cliente. Telefone: ' . ($lead->phonenumber ?? ''),
        'startdate'   => date('Y-m-d'),
        'duedate'     => date('Y-m-d', time() + 86400),
        'priority'    => 3,
        'status'      => 1,
        'dateadded'   => date('Y-m-d H:i:s'),
        'addedfrom'   => $staffId,
        'is_public'   => 0,
        'billable'    => 0,
        'rel_id'      => $lead->id,
        'rel_type'    => 'lead',
    ]);
    $taskId = $CI->db->insert_id();
    if ($taskId) {
        $CI->db->insert(db_prefix() . 'task_assigned', [
            'taskid'        => $taskId,
            'staffid'       => $staffId,
            'assigned_from' => $staffId,
        ]);
    }
}

// ============================================================
// HELPERS
// ============================================================

function smsc_create_task_for_staff($lead, $staffId, $autoName)
{
    if (!$staffId) {
        $staffId = (int) ($lead->assigned ?? 0);
    }
    if (!$staffId) {
        return;
    }
    $CI = &get_instance();
    $CI->db->insert(db_prefix() . 'tasks', [
        'name'              => 'Sofia ligou: ' . ($lead->name ?: ('Lead #' . $lead->id)) . ' — seguir interesse',
        'description'       => 'Chamada automática Sofia (automação "' . $autoName . '"). Verificar resultado e dar seguimento se o cliente mostrou interesse.',
        'startdate'         => date('Y-m-d'),
        'duedate'           => date('Y-m-d', strtotime('+1 day')),
        'dateadded'         => date('Y-m-d H:i:s'),
        'addedfrom'         => $staffId,
        'status'            => 1,
        'priority'          => 3,
        'rel_type'          => 'lead',
        'rel_id'            => $lead->id,
        'is_public'         => 0,
        'billable'          => 0,
        'visible_to_client' => 0,
    ]);
    $taskId = $CI->db->insert_id();
    if ($taskId) {
        $CI->db->insert(db_prefix() . 'task_assigned', [
            'taskid'        => $taskId,
            'staffid'       => $staffId,
            'assigned_from' => $staffId,
        ]);
    }
}

function smsc_normalize_phone($raw)
{
    $phone = preg_replace('/[^\d+]/', '', trim((string) $raw));
    if ($phone === '' || strlen($phone) < 9) {
        return null;
    }
    if ($phone[0] !== '+') {
        if (strlen($phone) === 9) {
            $phone = '+351' . $phone;
        } elseif (strpos($phone, '00') === 0) {
            $phone = '+' . substr($phone, 2);
        } else {
            $phone = '+' . $phone;
        }
    }
    return $phone;
}

function smsc_log($leadId, $description)
{
    $CI = &get_instance();
    $CI->db->insert(db_prefix() . 'lead_activity_log', [
        'leadid'          => $leadId,
        'description'     => $description,
        'additional_data' => '',
        'date'            => date('Y-m-d H:i:s'),
        'staffid'         => function_exists('get_staff_user_id') ? (get_staff_user_id() ?: 0) : 0,
        'full_name'       => 'Automações DPS',
        'custom_activity' => 1,
    ]);
}
