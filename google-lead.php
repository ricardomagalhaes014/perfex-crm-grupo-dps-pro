<?php
/**
 * Google Lead Endpoint
 * Recebe leads das landing pages DPS vindas de Google Ads (Search/YouTube)
 * Cria lead no Perfex CRM com tag GOOGLE e guarda o GCLID num campo customizado
 * para posterior upload de conversoes offline ao Google Ads (via modulo capi_meta).
 * SEM envio de WhatsApp automatico.
 */

define('BASEPATH', __DIR__ . '/');
define('GOOGLE_TOKEN', 'dps-google-2026');

$log_file = __DIR__ . '/google-lead-debug.log';
function log_debug($message, $data = null) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    $log_msg = "[$date] $message";
    if ($data !== null) {
        $log_msg .= " | Data: " . (is_string($data) ? $data : json_encode($data));
    }
    file_put_contents($log_file, $log_msg . "\n", FILE_APPEND);
}

log_debug("--- NOVO PEDIDO RECEBIDO ---", $_POST);

// Verificar token
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== GOOGLE_TOKEN) {
    log_debug("Erro: Token invalido", $token);
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/application/config/app-config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . APP_DB_HOSTNAME . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
        APP_DB_USERNAME,
        APP_DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    log_debug("Erro de ligacao a BD", $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// Dados do formulario
$name      = isset($_POST['name'])        ? trim($_POST['name'])        : '';
$email     = isset($_POST['email'])       ? trim($_POST['email'])       : '';
$phone     = isset($_POST['phonenumber']) ? trim($_POST['phonenumber']) : '';
$gclid     = isset($_POST['gclid'])       ? trim($_POST['gclid'])       : '';
$campaign  = isset($_POST['campaign'])    ? trim($_POST['campaign'])    : '';
$dev       = isset($_POST['empreendimento']) ? trim($_POST['empreendimento']) : '';
$status_id = isset($_POST['status'])      ? intval($_POST['status'])    : 4;
$assigned  = isset($_POST['assigned'])    ? intval($_POST['assigned'])  : 1;

if ($name === '' || ($email === '' && $phone === '')) {
    log_debug("Erro: dados insuficientes");
    http_response_code(400);
    echo json_encode(['error' => 'name + (email ou phonenumber) obrigatorios']);
    exit;
}

if ($phone !== '' && !preg_match('/^\+|^00|^351/', $phone) && preg_match('/^9\d{8}$/', $phone)) {
    $phone = '351' . $phone;
}

$src_stmt = $pdo->prepare("SELECT id FROM tblleads_sources WHERE name = ?");
$src_stmt->execute(['Google Ads']);
$src_row = $src_stmt->fetch(PDO::FETCH_ASSOC);
if ($src_row) {
    $source_id = (int) $src_row['id'];
} else {
    $pdo->prepare("INSERT INTO tblleads_sources (name) VALUES (?)")->execute(['Google Ads']);
    $source_id = (int) $pdo->lastInsertId();
}

$dup = null;
if ($email !== '' || $phone !== '') {
    $q = "SELECT id FROM tblleads WHERE dateadded > DATE_SUB(NOW(), INTERVAL 1 DAY) AND (";
    $conds = []; $vals = [];
    if ($email !== '') { $conds[] = "email = ?"; $vals[] = $email; }
    if ($phone !== '') { $conds[] = "phonenumber = ?"; $vals[] = $phone; }
    $q .= implode(' OR ', $conds) . ") LIMIT 1";
    $st = $pdo->prepare($q); $st->execute($vals);
    $dup = $st->fetch(PDO::FETCH_ASSOC);
}

if ($dup) {
    log_debug("Lead duplicada nas ultimas 24h, id=" . $dup['id']);
    echo json_encode(['status' => 'duplicate', 'lead_id' => $dup['id']]);
    exit;
}

$descricao = "Lead Google Ads";
if ($campaign !== '') $descricao .= " | Campanha: " . $campaign;
if ($dev !== '')      $descricao .= " | Empreendimento: " . $dev;

$stmt = $pdo->prepare("
    INSERT INTO tblleads
        (status, source, assigned, name, email, phonenumber, description, dateadded, lastcontact, addedfrom, is_public, date_converted)
    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NULL, 0, 0, NULL)
");
$stmt->execute([$status_id, $source_id, $assigned, $name, $email, $phone, $descricao]);
$lead_id = (int) $pdo->lastInsertId();
log_debug("Lead criada", $lead_id);

try {
    $tag_stmt = $pdo->prepare("SELECT id FROM tbltags WHERE name = ?");
    $tag_stmt->execute(['GOOGLE']);
    $tag_row = $tag_stmt->fetch(PDO::FETCH_ASSOC);
    if ($tag_row) {
        $tag_id = (int) $tag_row['id'];
    } else {
        $pdo->prepare("INSERT INTO tbltags (name) VALUES (?)")->execute(['GOOGLE']);
        $tag_id = (int) $pdo->lastInsertId();
    }
    $pdo->prepare("INSERT IGNORE INTO tbltaggables (tag_id, rel_id, rel_type, tag_order) VALUES (?, ?, 'lead', 0)")
        ->execute([$tag_id, $lead_id]);
} catch (PDOException $e) {
    log_debug("Erro tag", $e->getMessage());
}

$gclid_saved = false;
if ($gclid !== '') {
    try {
        $cf_stmt = $pdo->prepare("SELECT id FROM tblcustomfields WHERE fieldto = 'leads' AND (slug = 'leads_gclid' OR name = 'GCLID') LIMIT 1");
        $cf_stmt->execute();
        $cf = $cf_stmt->fetch(PDO::FETCH_ASSOC);
        if ($cf) {
            $field_id = (int) $cf['id'];
        } else {
            $pdo->prepare("INSERT INTO tblcustomfields (fieldto, name, slug, type, active, show_on_table, only_admin, required, field_order, bs_column) VALUES ('leads', 'GCLID', 'leads_gclid', 'input', 1, 0, 0, 0, 100, 12)")->execute();
            $field_id = (int) $pdo->lastInsertId();
            log_debug("Campo customizado GCLID criado", $field_id);
        }
        $pdo->prepare("INSERT INTO tblcustomfieldsvalues (relid, fieldid, fieldto, value) VALUES (?, ?, 'leads', ?)")
            ->execute([$lead_id, $field_id, $gclid]);
        $gclid_saved = true;
    } catch (PDOException $e) {
        log_debug("Erro GCLID", $e->getMessage());
    }
}

log_debug("Concluido com sucesso", $lead_id);
echo json_encode([
    'status'      => 'success',
    'lead_id'     => (string) $lead_id,
    'name'        => $name,
    'email'       => $email,
    'phone'       => $phone,
    'tag'         => 'GOOGLE',
    'gclid_saved' => $gclid_saved
]);
