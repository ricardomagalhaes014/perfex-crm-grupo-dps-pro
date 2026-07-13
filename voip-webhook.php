<?php
/**
 * DPS VoIP — Webhooks públicos do Twilio (fora do login do CRM).
 * O controlador do módulo é AdminController (exige login), pelo que o Twilio
 * não o alcança. Este endpoint replica a lógica de forma pública, com token.
 *
 *   TwiML App VoiceUrl : https://crm.grupo-dps.com/voip-webhook.php?token=dps-voip-2026&a=outbound
 *   Número (entrada)   : https://crm.grupo-dps.com/voip-webhook.php?token=dps-voip-2026&a=inbound
 *   Status callback    : https://crm.grupo-dps.com/voip-webhook.php?token=dps-voip-2026&a=status
 */

$token = $_GET['token'] ?? '';
if ($token !== 'dps-voip-2026') {
    http_response_code(403);
    header('Content-Type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><Response><Reject/></Response>';
    exit;
}
$a = $_GET['a'] ?? 'outbound';

// --- BD a partir do app-config.php (sem hardcode) ---
$cfg = @file_get_contents(__DIR__ . '/application/config/app-config.php');
function _v_cfg($c, $k) { return preg_match('/' . $k . "['\"]\s*,\s*['\"](.*?)['\"]/", $c, $m) ? $m[1] : null; }
$mysqli = @new mysqli(_v_cfg($cfg, 'APP_DB_HOSTNAME'), _v_cfg($cfg, 'APP_DB_USERNAME'), _v_cfg($cfg, 'APP_DB_PASSWORD'), _v_cfg($cfg, 'APP_DB_NAME'));
$prefix = _v_cfg($cfg, 'APP_DB_PREFIX') ?: 'tbl';
if ($mysqli && !$mysqli->connect_errno) { $mysqli->set_charset('utf8mb4'); } else { $mysqli = null; }

function v_opt($mysqli, $prefix, $name) {
    if (!$mysqli) return null;
    $v = null;
    if ($st = $mysqli->prepare("SELECT value FROM {$prefix}options WHERE name=? LIMIT 1")) {
        $st->bind_param('s', $name); $st->execute();
        $r = $st->get_result()->fetch_assoc(); $v = $r ? $r['value'] : null; $st->close();
    }
    return $v;
}
function v_log($mysqli, $prefix, $data) {
    if (!$mysqli) return;
    $st = $mysqli->prepare("INSERT INTO {$prefix}dps_voip_calls (call_sid, direction, from_number, to_number, staff_id, status, started_at, created_at) VALUES (?,?,?,?,?,?,NOW(),NOW())");
    if (!$st) return;
    $sid = $data['call_sid'] ?? ''; $dir = $data['direction']; $fr = $data['from'] ?? ''; $to = $data['to'] ?? '';
    $staff = $data['staff_id'] ?? null; $status = $data['status'] ?? '';
    $st->bind_param('ssssis', $sid, $dir, $fr, $to, $staff, $status);
    $st->execute(); $st->close();
}
function xml_esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_XML1); }

$P = function($k) { return $_POST[$k] ?? $_GET[$k] ?? ''; };

if ($a === 'status') {
    // Atualizar o registo da chamada
    $call_sid = $P('CallSid');
    $status   = $P('CallStatus');
    $duration = (int) $P('CallDuration');
    $rec      = $P('RecordingUrl');
    if ($mysqli && $call_sid) {
        if ($st = $mysqli->prepare("UPDATE {$prefix}dps_voip_calls SET status=?, duration=?, recording_url=?, ended_at=NOW() WHERE call_sid=? ORDER BY id DESC LIMIT 1")) {
            $st->bind_param('sisi', $status, $duration, $rec, $call_sid);
            @$st->execute(); $st->close();
        }
    }
    http_response_code(204);
    exit;
}

header('Content-Type: text/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>';

if ($a === 'inbound') {
    $from = $P('From'); $to = $P('To'); $call_sid = $P('CallSid');
    $staff_id = null;
    if ($mysqli) {
        if ($st = $mysqli->prepare("SELECT staff_id FROM {$prefix}dps_voip_numbers WHERE twilio_number=? AND is_active=1 LIMIT 1")) {
            $st->bind_param('s', $to); $st->execute();
            $r = $st->get_result()->fetch_assoc(); $staff_id = $r ? $r['staff_id'] : null; $st->close();
        }
    }
    v_log($mysqli, $prefix, ['call_sid' => $call_sid, 'direction' => 'inbound', 'from' => $from, 'to' => $to, 'staff_id' => $staff_id, 'status' => 'ringing']);
    echo '<Response>';
    if ($staff_id) {
        echo '<Dial timeout="30"><Client>' . xml_esc('staff_' . $staff_id) . '</Client></Dial>';
    } else {
        echo '<Say language="pt-PT">Obrigado por contactar o Grupo DPS. Nao foi possivel encaminhar a sua chamada. Por favor tente mais tarde.</Say>';
    }
    echo '</Response>';
    exit;
}

// --- outbound (default) ---
$to = $P('To'); $from = $P('From'); $staff_id = $P('staff_id'); $call_sid = $P('CallSid');
if ($to === '') {
    echo '<Response><Say language="pt-PT">Numero de destino nao especificado.</Say></Response>';
    exit;
}
v_log($mysqli, $prefix, ['call_sid' => $call_sid, 'direction' => 'outbound', 'from' => $from, 'to' => $to, 'staff_id' => ($staff_id !== '' ? (int)$staff_id : null), 'status' => 'initiated']);
$record  = v_opt($mysqli, $prefix, 'dps_voip_record_calls') === '1';
$timeout = (int) (v_opt($mysqli, $prefix, 'dps_voip_default_timeout') ?: 30);
echo '<Response>';
echo '<Dial callerId="' . xml_esc($from) . '" timeout="' . $timeout . '"' . ($record ? ' record="record-from-ringing"' : '') . '>';
echo '<Number>' . xml_esc($to) . '</Number>';
echo '</Dial></Response>';
exit;
