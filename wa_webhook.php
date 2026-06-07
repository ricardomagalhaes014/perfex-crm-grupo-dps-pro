<?php
/**
 * DPS WhatsApp Webhook v5 â Evolution API
 * Regista mensagens WhatsApp nas notas das leads do CRM
 * Tabela correcta: tblnotes (rel_id, rel_type='lead', description, addedfrom, dateadded)
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=utf-8');
define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');
$log_file = __DIR__ . '/wa_webhook.log';
function wh_log($msg) {
    global $log_file;
    $line = date('Y-m-d H:i:s') . "  " . $msg . "\n";
    file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

// SÃ³ aceitar POST
$input = file_get_contents('php://input');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($input)) {
    echo json_encode(['ok' => true, 'status' => 'webhook active']);
    exit;
}

$data = json_decode($input, true);
if (!is_array($data)) {
    wh_log("JSON invÃ¡lido: " . substr($input, 0, 200));
    echo json_encode(['ok' => false, 'error' => 'invalid_json']);
    exit;
}

// Filtrar apenas MESSAGES_UPSETRT)$type = isset($data['event']) ? $data['event'] : '';
if ($type !== 'MESSAGES_UPSERT') {
    echo json_encode(['ok' => true, 'skipped' => 'event: ' . $type]);
    exit;
}

// Extrair remoteJid
$remoteJid = isset($data['key']) && isset($data['key']['remoteJid'])
    ? $data['key']['remoteJid']
    : (isset($data['data']) && isset($data['data']['key']) && isset($data['data']['key']['remoteJid'])
        ? $data['data']['key']['remoteJid']
        : '');

// DirecÃ§Ã£o (enviada ou recebida)
$fromMe = isset($data['key']) && isset($data['key']['fromMe'])
    ? (bool)$data['key']['fromMe']
    : (isset($data['data']) && isset($data['data']['key']) && isset($data['data']['key']['fromMe'])
        ? (bool)$data['data']['key']['fromMe']
        : false);
$direction = $fromMe ? 'Enviada' : 'Recebida';

// Filtrar mensagens de grupo (@g.us) e @lid
if (strpos($remoteJid, '@g.us') !== false || strpos($remoteJid, '@lid') !== false) {
    echo json_encode(['ok' => true, 'skipped' => 'group/lid']);
    exit;
}

// Extrair nÃºmero de telefone
$phone_raw = preg_replace('/@.*/', '', $remoteJid);	
$phone_digits = preg_replace('/[^0-9]/', '', $phone_raw);

if (empty($phone_digits)) {
    wh_log("NÃºmero vazio para remoteJid: " . $remoteJid);
    echo json_encode(['ok' => true, 'skipped' => 'no phone']);
    exit;
}

// Extrair message
$message = isset($data['message']) ? $data['message'] : array();
if (empty($message) && isset($data['data']) && isset($data['data']['message'])) {
    $message = $data['data']['message'];
}
$msg_type = isset($data['messageType']) ? $data['messageType'] : 'unknown';
if (empty($msg_type) && isset($data['data']) && isset($data['data']['messageType'])) {
    $msg_type = $data['data']['messageType'];
}
$text = '';

if (isset($message['conversation'])) {
    $text = $message['conversation'];
} elseif (isset($message['extendedTextMessage']['text'])) {
    $text = $message['extendedTextMessage']['text'];
} elseif (isset($message['imageMessage'])) {
    $caption = isset($message['imageMessage']['caption']) ? $message['imageMessage']['caption'] : '';
    $text = '[Imagem]' . ($caption ? ': ' . $caption : '');
} elseif (isset($message['videoMessage'])) {
    $caption = isset($message['videoMessage']['caption']) ? $message['videoMessage']['caption'] : '';
    $text = '[Video]' . ($caption ? ': ' . $caption : '');
} elseif (isset($message['audioMessage'])) {
    $text = '[Audio]';
} elseif (isset($message['documentMessage'])) {
    $title = isset($message['documentMessage']['title']) ? $message['documentMessage']['title'] : 'documento';
    $text = '[Doc: ' . $title . ']';
} elseif (isset($message['stickerMessage'])) {
    $text = '[Sticker]';
} else {
    $text = '[' . $msg_type . ']';
}

if (trim($text) === '') {
    echo json_encode(['ok' => true, 'skipped' => 'empty text']);
    exit;
}

// Ligar Ã  BD
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    wh_log("ERRO BD connect: " . $conn->connect_error);
    echo json_encode(['ok' => false, 'error' => 'db_connect_failed']);
    exit;
}
$conn->set_charset('utf8mb4');

// Encontrar lead pelo nÃºmero (Ãºltimos 9 dÃ­gitos)
$phone_suffix = substr($phone_digits, -9);
$sql = "SELECT id, name FROM tblleads 
        WHERE REPLACE(REPLACE(REPLACE(phonenumber, ' ', ''), '-', ''), '+', '') LIKE ?
        ORDER BY dteadded DESC LIMIT 1";
$like = '%' . $phone_suffix;
$stmt = $conn->prepare($sql);
if (!$stmt) {
    wh_log("ERRO prepare: " . $conn->error);
    $conn->close();
    echo json_encode(['ok' => false, 'error' => 'prepare_failed']);
    exit;
}
$stmt->bind_param('s', $like);
$stmt->execute();
$result = $stmt->get_result();
$lead = $result->fetch_assoc();
$stmt->close();

if (!$lead) {
    wh_log("Lead nao encontrada: phone=" . $phone_digits . " suffix=" . $phone_suffix);
    $conn->close();
    echo json_encode(['ok' => true, 'skipped' => 'lead_not_found', 'phone' => $phone_digits]);
    exit;
}

$lead_id = (int)$lead['id'];
$lead_name = trim($lead['name']);

// Inserir nota na tabela tblnotes (rel_type='lead')
$note_text = "[WhatsApp " . $direction . "]\n" . $text;
$now = date('Y-m-d H:i:s');
$sql_note = "INSERT INTO tblnotes (rel_id, rel_type, description, addedfrom, dateadded) VALUES (?, 'lead', ?, 0, ?)";
$stmt2 = $conn->prepare($sql_note);
if (!$stmt2) {
    wh_log("ERRO prepare nota: " . $conn->error);
    $conn->close();
    echo json_encode(['ok' => false, 'error' => 'prepare_note_failed']);
    exit;
}
$stmt2->bind_param('iss', $lead_id, $note_text, $now);
$ok = $stmt2->execute();
$stmt2->close();
$conn->close();

if ($ok) {
    wh_log("OK: Lead #" . $lead_id . " (" . $lead_name . ") | " . $direction . " | " . substr($text, 0, 80));
    echo json_encode(['ok' => true, 'lead_id' => $lead_id, 'lead_name' => $lead_name]);
} else {
    wh_log("ERRO inserir nota lead #" . $lead_id);
    echo json_encode(['ok' => false, 'error' => 'insert_failed']);
}
