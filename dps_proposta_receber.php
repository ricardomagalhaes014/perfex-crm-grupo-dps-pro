<?php
/**
 * Endpoint público (autenticado por HMAC) que recebe uma proposta gerada no
 * simuladorportugal e a envia pelo WhatsApp do comercial + regista no CRM.
 * Segredo em /home/u172337921/.dps_proposta_secret (fora do repositório).
 */

header('Access-Control-Allow-Origin: https://dpsimobiliario.pt');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (! is_array($in)) {
    $in = $_POST;
}

$lead_id  = (int) ($in['lead_id'] ?? 0);
$staff_id = (int) ($in['staff_id'] ?? 0);
$token    = (string) ($in['token'] ?? '');
$emp      = trim((string) ($in['empreendimento'] ?? ''));
$unidade  = trim((string) ($in['unidade'] ?? ''));
$fname    = trim((string) ($in['file_name'] ?? 'Proposta.pdf'));
$pdf_b64  = (string) ($in['pdf_base64'] ?? '');

if (! $lead_id || ! $staff_id || $token === '') {
    echo json_encode(['success' => false, 'error' => 'Parâmetros em falta']);
    exit;
}

// --- Validar token HMAC (aceita hoje e ontem, por causa da meia-noite) ---
$secret = @file_get_contents('/home/u172337921/.dps_proposta_secret');
$secret = $secret !== false ? trim($secret) : '';
if ($secret === '') {
    echo json_encode(['success' => false, 'error' => 'Config']);
    exit;
}
$valid = false;
foreach ([date('Y-m-d'), date('Y-m-d', time() - 86400)] as $day) {
    $calc = hash_hmac('sha256', $lead_id . '|' . $staff_id . '|' . $day, $secret);
    if (hash_equals($calc, $token)) {
        $valid = true;
        break;
    }
}
if (! $valid) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

// --- Credenciais da BD a partir do app-config.php (sem hardcode) ---
$cfg = @file_get_contents(__DIR__ . '/application/config/app-config.php');
function _dps_cfg($c, $k)
{
    return preg_match('/' . $k . "['\"]\s*,\s*['\"](.*?)['\"]/", $c, $m) ? $m[1] : null;
}
$db_host = _dps_cfg($cfg, 'APP_DB_HOSTNAME');
$db_user = _dps_cfg($cfg, 'APP_DB_USERNAME');
$db_pass = _dps_cfg($cfg, 'APP_DB_PASSWORD');
$db_name = _dps_cfg($cfg, 'APP_DB_NAME');
$prefix  = _dps_cfg($cfg, 'APP_DB_PREFIX');
if (! $prefix) {
    $prefix = 'tbl';
}

$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'error' => 'DB']);
    exit;
}
$mysqli->set_charset('utf8mb4');

// Lead (telefone + estado atual)
$lead = null;
if ($st = $mysqli->prepare("SELECT name, phonenumber, status FROM {$prefix}leads WHERE id=?")) {
    $st->bind_param('i', $lead_id);
    $st->execute();
    $lead = $st->get_result()->fetch_assoc();
    $st->close();
}
if (! $lead) {
    echo json_encode(['success' => false, 'error' => 'Lead não encontrada']);
    exit;
}
$number = preg_replace('/[^0-9]/', '', (string) $lead['phonenumber']);

// Estado (nome)
$status_nome = null;
if ($st = $mysqli->prepare("SELECT name FROM {$prefix}leads_status WHERE id=?")) {
    $st->bind_param('i', $lead['status']);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $status_nome = $r ? $r['name'] : null;
    $st->close();
}

// Opções Evolution
function _dps_opt($mysqli, $prefix, $name)
{
    $v = null;
    if ($st = $mysqli->prepare("SELECT value FROM {$prefix}options WHERE name=? LIMIT 1")) {
        $st->bind_param('s', $name);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();
        $v = $r ? $r['value'] : null;
        $st->close();
    }
    return $v;
}
$evo_url = rtrim((string) _dps_opt($mysqli, $prefix, 'dps_whatsapp_evolution_url'), '/');
$evo_key = (string) _dps_opt($mysqli, $prefix, 'dps_whatsapp_evolution_api_key');

// --- Enviar o PDF por WhatsApp (Evolution sendMedia, base64) ---
$wa_ok = false;
$wa_err = '';
if ($number !== '' && $pdf_b64 !== '' && $evo_url && $evo_key) {
    // jsPDF: "data:application/pdf;filename=generated.pdf;base64,..." — cortar tudo até "base64,".
    $pos   = strpos($pdf_b64, 'base64,');
    $media = $pos !== false ? substr($pdf_b64, $pos + 7) : $pdf_b64;
    $media = preg_replace('/\s+/', '', $media);
    // Link do site do empreendimento (para acrescentar à legenda).
    $sites = [
        'boavista towers' => 'https://dpsimobiliario.pt/boavistatowers/',
        'belo horizonte'  => 'https://dpsimobiliario.pt/belohorizonte/',
        'raízes'          => 'https://dpsimobiliario.pt/raizes/',
        'raizes'          => 'https://dpsimobiliario.pt/raizes/',
    ];
    $site = $sites[mb_strtolower(trim((string) $emp))] ?? '';
    $caption = 'Proposta' . ($emp ? ' — ' . $emp : '') . ($unidade ? ' — Unidade ' . $unidade : '');
    if ($site !== '') {
        $caption .= "\n\n🌐 Mais informação:\n" . $site;
    }
    $payload = json_encode([
        'number'       => $number,
        'mediaMessage' => [
            'mediatype' => 'document',
            'fileName'  => $fname,
            'media'     => $media,
            'caption'   => $caption,
        ],
    ]);
    $ch = curl_init($evo_url . '/message/sendMedia/staff-' . $staff_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $evo_key],
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $wa_err = curl_error($ch) ?: (string) $resp;
    curl_close($ch);
    $wa_ok = ($code >= 200 && $code < 300) && strpos((string) $resp, '"key"') !== false;
}

// --- Registar na aba Propostas ---
if ($st = $mysqli->prepare("INSERT INTO {$prefix}dps_propostas
    (lead_id, staff_id, tipo, empreendimento, unidade, lead_status_id, lead_status_nome, ficheiro, detalhe, wa_ok, created_at)
    VALUES (?, ?, 'proposta', ?, ?, ?, ?, ?, ?, ?, NOW())")) {
    $lsid    = (int) $lead['status'];
    $ficheiro = $fname;
    $detalhe  = 'Enviada via simulador';
    $waint    = $wa_ok ? 1 : 0;
    $st->bind_param('iississsi', $lead_id, $staff_id, $emp, $unidade, $lsid, $status_nome, $ficheiro, $detalhe, $waint);
    $st->execute();
    $st->close();
}

echo json_encode([
    'success' => $wa_ok,
    'message' => $wa_ok ? 'Proposta enviada e registada.' : ('Registada, mas o envio WhatsApp falhou. ' . $wa_err),
]);
