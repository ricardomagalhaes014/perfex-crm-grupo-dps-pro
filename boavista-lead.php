<?php
/**
 * Boavista Tower — Lead Gateway para a assistente virtual Sofia (ElevenLabs).
 * A Sofia chama este endpoint quando deteta interesse numa conversa.
 * Cria a lead no CRM: fonte "DPS Portugal" (10), estado "Novos" (4),
 * tags "Boavista Towers" (58) + "INTERESSADO BOAVISTA" (63).
 *
 * Uso: POST https://crm.grupo-dps.com/boavista-lead.php?token=dps-boavista-2026
 *   Body (JSON ou form): name, phone, email, message
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

// --- Auth ---
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
if ($token !== 'dps-boavista-2026') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// --- Ler input (JSON ou form) ---
$in = json_decode(file_get_contents('php://input'), true);
if (! is_array($in)) { $in = $_POST; }
$name    = trim((string) ($in['name'] ?? $in['nome'] ?? ''));
$phone   = trim((string) ($in['phone'] ?? $in['telefone'] ?? $in['phonenumber'] ?? ''));
$email   = trim((string) ($in['email'] ?? ''));
$message = trim((string) ($in['message'] ?? $in['mensagem'] ?? $in['notes'] ?? $in['interesse'] ?? ''));

if ($name === '' && $phone === '' && $email === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltam dados (nome/telefone/email).']);
    exit;
}
if ($name === '') { $name = $phone !== '' ? $phone : $email; }

// --- Credenciais da BD a partir do app-config.php (sem hardcode) ---
$cfg = @file_get_contents(__DIR__ . '/application/config/app-config.php');
function _bl_cfg($c, $k) { return preg_match('/' . $k . "['\"]\s*,\s*['\"](.*?)['\"]/", $c, $m) ? $m[1] : null; }
$mysqli = @new mysqli(_bl_cfg($cfg, 'APP_DB_HOSTNAME'), _bl_cfg($cfg, 'APP_DB_USERNAME'), _bl_cfg($cfg, 'APP_DB_PASSWORD'), _bl_cfg($cfg, 'APP_DB_NAME'));
if ($mysqli->connect_errno) { http_response_code(500); echo json_encode(['success' => false, 'error' => 'DB']); exit; }
$mysqli->set_charset('utf8mb4');
$p = _bl_cfg($cfg, 'APP_DB_PREFIX') ?: 'tbl';

// --- Evitar duplicado recente (mesmo telefone/email nas últimas 24h) ---
if ($phone !== '' || $email !== '') {
    $ph = preg_replace('/[^0-9]/', '', $phone);
    $q = $mysqli->prepare("SELECT id FROM {$p}leads WHERE ((phonenumber<>'' AND REPLACE(REPLACE(phonenumber,' ',''),'+','') LIKE ?) OR (email<>'' AND email=?)) AND dateadded >= (NOW() - INTERVAL 1 DAY) LIMIT 1");
    $like = '%' . $ph . '%'; $q->bind_param('ss', $like, $email); $q->execute();
    if ($q->get_result()->fetch_assoc()) {
        $q->close();
        echo json_encode(['success' => true, 'duplicate' => true, 'message' => 'Lead já existente nas últimas 24h.']);
        exit;
    }
    $q->close();
}

// --- Criar a lead ---
$hash = md5(uniqid('bt', true));
$now  = date('Y-m-d H:i:s');
$source = 10; $status = 4; $assigned = 1; // DPS Portugal / Novos / (equipa reatribui)
$desc = 'Lead criada pela assistente virtual Sofia (Boavista Tower).' . ($message !== '' ? "\n\nInteresse: " . $message : '');

$lead_id = 0;
if ($st = $mysqli->prepare("INSERT INTO {$p}leads (hash, name, email, phonenumber, source, status, assigned, addedfrom, dateadded, is_public, description, lastcontact) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 1, ?, ?)")) {
    $st->bind_param('ssssiiisss', $hash, $name, $email, $phone, $source, $status, $assigned, $now, $desc, $now);
    $st->execute();
    $lead_id = $st->insert_id;
    $st->close();
}
if (! $lead_id) { http_response_code(500); echo json_encode(['success' => false, 'error' => 'Falha ao criar a lead.']); exit; }

// --- Tags: Boavista Towers (58) + INTERESSADO BOAVISTA (63) ---
foreach ([58, 63] as $i => $tag_id) {
    if ($st = $mysqli->prepare("INSERT INTO {$p}taggables (tag_id, rel_id, rel_type, tag_order) VALUES (?, ?, 'lead', ?)")) {
        $ord = $i + 1;
        $st->bind_param('iii', $tag_id, $lead_id, $ord);
        $st->execute();
        $st->close();
    }
}

// --- Nota + registo de atividade ---
if ($message !== '') {
    if ($st = $mysqli->prepare("INSERT INTO {$p}notes (rel_id, rel_type, description, dateadded, addedfrom) VALUES (?, 'lead', ?, ?, 0)")) {
        $notetext = 'Sofia — interesse: ' . $message;
        $st->bind_param('iss', $lead_id, $notetext, $now);
        $st->execute();
        $st->close();
    }
}
if ($st = $mysqli->prepare("INSERT INTO {$p}lead_activity_log (leadid, description, date, staffid, full_name, additional_data) VALUES (?, ?, ?, 0, 'Sofia', '')")) {
    $act = '🤖 Lead criada pela Sofia (Boavista Tower)';
    $st->bind_param('iss', $lead_id, $act, $now);
    $st->execute();
    $st->close();
}

echo json_encode(['success' => true, 'lead_id' => $lead_id, 'message' => 'Lead criada no CRM.']);
