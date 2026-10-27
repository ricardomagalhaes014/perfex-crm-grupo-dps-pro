<?php
/**
 * DPS - Sofia Campanha ISCAP / Custóias
 * Chamado pela ferramenta "registar_resposta_campanha" do agente ElevenLabs
 * "Sofia - Campanha ISCAP e Custóias (Saída)".
 *
 * POST JSON: { "resposta": "sim"|"nao", "telefone": "+3519..." }
 * URL:       /dps_sofia_campanha.php?token=XXX
 *
 * Se resposta = "sim":
 *   1. Procura a lead existente pelo telefone (últimos 9 dígitos)
 *   2. Lê o comercial a quem pertence (assigned)
 *   3. Cria uma lead NOVA atribuída a esse comercial, com a tag "Sofia"
 *   4. Notifica o comercial no CRM
 * Se resposta = "nao": só regista em log.
 *
 * Diagnóstico: GET ?token=XXX&check=1  → últimas 10 leads com tag Sofia
 */

define('BASEPATH', __DIR__ . '/');
define('SOFIA_CAMP_TOKEN', '593b034e64301c3da1dafcb0');
define('SOFIA_CAMP_TAG', 'Sofia');
define('SOFIA_CAMP_SOURCE', 'Sofia IA - Campanha');
define('SOFIA_CAMP_FALLBACK_STAFF', 1); // se o número não existir no CRM

header('Content-Type: application/json; charset=utf-8');

$log_file = __DIR__ . '/dps_sofia_campanha.log';
function sc_log($msg, $data = null) {
    global $log_file;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    if ($data !== null) { $line .= ' | ' . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE)); }
    @file_put_contents($log_file, $line . "\n", FILE_APPEND);
}
function sc_out($code, $arr) { http_response_code($code); echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

if (($_GET['token'] ?? '') !== SOFIA_CAMP_TOKEN) { sc_out(401, ['ok' => false, 'error' => 'unauthorized']); }

require_once __DIR__ . '/application/config/app-config.php';
try {
    $pdo = new PDO('mysql:host=' . APP_DB_HOSTNAME . ';dbname=' . APP_DB_NAME . ';charset=utf8mb4', APP_DB_USERNAME, APP_DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    sc_log('Erro BD', $e->getMessage());
    sc_out(500, ['ok' => false, 'error' => 'db']);
}

// Fuso horário do Perfex (as datas têm de bater certo com as outras leads)
$tz = $pdo->query("SELECT value FROM tbloptions WHERE name = 'default_timezone' LIMIT 1")->fetchColumn();
if ($tz) { @date_default_timezone_set($tz); }
$agora = date('Y-m-d H:i:s');

if (isset($_GET['horas'])) {
    sc_out(200, ['php' => $agora, 'mysql' => $pdo->query('SELECT NOW()')->fetchColumn(), 'tz' => $tz]);
}

// ---------- Diagnóstico de uma lead ----------
if (isset($_GET['lead'])) {
    $q = $pdo->prepare("SELECT l.id,l.name,l.phonenumber,l.status,s.name AS status_name,l.source,l.assigned,CONCAT(st.firstname,' ',st.lastname) AS comercial,l.junk,l.lost,l.dateadded FROM tblleads l LEFT JOIN tblleads_status s ON s.id=l.status LEFT JOIN tblstaff st ON st.staffid=l.assigned WHERE l.id=?");
    $q->execute([(int)$_GET['lead']]);
    sc_out(200, ['ok' => true, 'lead' => $q->fetch(PDO::FETCH_ASSOC)]);
}

// ---------- Diagnóstico ----------
if (isset($_GET['check'])) {
    $rows = $pdo->query("
        SELECT l.id, l.name, l.phonenumber, l.assigned, l.dateadded
        FROM tblleads l
        JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead'
        JOIN tbltags tg ON tg.id = t.tag_id AND tg.name = '" . SOFIA_CAMP_TAG . "'
        ORDER BY l.dateadded DESC LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    sc_out(200, ['ok' => true, 'last_sofia_leads' => $rows]);
}

// ---------- Input ----------
$raw = file_get_contents('php://input');
$in = json_decode($raw, true);
if (!is_array($in)) { $in = $_POST; }
sc_log('Pedido', $in);

$resposta = strtolower(trim((string)($in['resposta'] ?? '')));
$resposta = str_replace(['ã', 'Ã'], 'a', $resposta);
$telefone = preg_replace('/[^0-9+]/', '', (string)($in['telefone'] ?? ''));
$digits   = preg_replace('/\D/', '', $telefone);
$last9    = strlen($digits) >= 9 ? substr($digits, -9) : $digits;

if ($resposta !== 'sim') {
    sc_log('Resposta negativa/outra — nada a criar', ['resposta' => $resposta, 'telefone' => $telefone]);
    sc_out(200, ['ok' => true, 'acao' => 'nenhuma']);
}
if (strlen($last9) < 9) {
    sc_log('Telefone inválido', $telefone);
    sc_out(200, ['ok' => false, 'error' => 'telefone_invalido']);
}

// ---------- Evitar duplicados (mesmo número, tag Sofia, últimas 24h) ----------
$dup = $pdo->prepare("
    SELECT l.id FROM tblleads l
    JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead'
    JOIN tbltags tg ON tg.id = t.tag_id AND tg.name = ?
    WHERE RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(l.phonenumber,' ',''),'+',''),'-',''),'.',''), 9) = ?
      AND l.dateadded > DATE_SUB(?, INTERVAL 1 DAY)
    LIMIT 1
");
$dup->execute([SOFIA_CAMP_TAG, $last9, $agora]);
if ($d = $dup->fetch(PDO::FETCH_ASSOC)) {
    sc_log('Duplicado ignorado', $d['id']);
    sc_out(200, ['ok' => true, 'acao' => 'duplicado', 'lead_id' => (int)$d['id']]);
}

// ---------- Lead original ----------
$orig = $pdo->prepare("
    SELECT id, name, email, phonenumber, assigned, company, country, city
    FROM tblleads
    WHERE RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(phonenumber,' ',''),'+',''),'-',''),'.',''), 9) = ?
    ORDER BY (assigned > 0) DESC, dateadded DESC
    LIMIT 1
");
$orig->execute([$last9]);
$o = $orig->fetch(PDO::FETCH_ASSOC);

$assigned = ($o && (int)$o['assigned'] > 0) ? (int)$o['assigned'] : SOFIA_CAMP_FALLBACK_STAFF;
$name     = ($o && trim($o['name']) !== '') ? $o['name'] : ('Contacto Sofia ' . $telefone);
$email    = $o['email'] ?? '';
$phone    = '+' . (strlen($digits) == 9 ? '351' . $digits : $digits);

// Estado por defeito do CRM
$st = $pdo->query("SELECT value FROM tbloptions WHERE name = 'leads_default_status' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$status_id = ($st && (int)$st['value'] > 0) ? (int)$st['value'] : (int)$pdo->query("SELECT id FROM tblleads_status ORDER BY statusorder ASC LIMIT 1")->fetchColumn();

// Fonte "Sofia IA - Campanha"
$s = $pdo->prepare("SELECT id FROM tblleads_sources WHERE name = ? LIMIT 1");
$s->execute([SOFIA_CAMP_SOURCE]);
$source_id = $s->fetchColumn();
if (!$source_id) {
    $pdo->prepare("INSERT INTO tblleads_sources (name) VALUES (?)")->execute([SOFIA_CAMP_SOURCE]);
    $source_id = $pdo->lastInsertId();
}

$desc = "Campanha Sofia (ElevenLabs) — empreendimentos ISCAP e Custóias.\n"
      . "O cliente disse SIM: quer que o gestor o contacte para apresentar mais informações.\n"
      . "Data da chamada: " . date('d/m/Y H:i') . "\n"
      . ($o ? "Lead original: #" . $o['id'] : "Número não encontrado no CRM — atribuída por defeito.");

try {
    $pdo->beginTransaction();
    $ins = $pdo->prepare("
        INSERT INTO tblleads
            (status, source, assigned, name, email, phonenumber, company, country, city,
             description, dateadded, dateassigned, lastcontact, addedfrom, is_public, date_converted)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NULL)
    ");
    $ins->execute([
        $status_id, $source_id, $assigned, $name, $email, $phone,
        $o['company'] ?? '', (int)($o['country'] ?? 0), $o['city'] ?? '', $desc,
        $agora, substr($agora, 0, 10), $agora,
    ]);
    $lead_id = (int)$pdo->lastInsertId();

    $t = $pdo->prepare("SELECT id FROM tbltags WHERE name = ? LIMIT 1");
    $t->execute([SOFIA_CAMP_TAG]);
    $tag_id = $t->fetchColumn();
    if (!$tag_id) {
        $pdo->prepare("INSERT INTO tbltags (name) VALUES (?)")->execute([SOFIA_CAMP_TAG]);
        $tag_id = $pdo->lastInsertId();
    }
    $pdo->prepare("INSERT IGNORE INTO tbltaggables (tag_id, rel_id, rel_type, tag_order) VALUES (?, ?, 'lead', 1)")
        ->execute([$tag_id, $lead_id]);
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    sc_log('Erro ao criar lead', $e->getMessage());
    sc_out(500, ['ok' => false, 'error' => 'insert_failed']);
}

// Notificação no CRM para o comercial (não bloqueia)
try {
    $pdo->prepare("
        INSERT INTO tblnotifications (isread, isread_inline, date, description, fromuserid, fromclientid, from_fullname, touserid, fromcompany, link, additional_data)
        VALUES (0, 0, ?, ?, 0, 0, '', ?, 1, ?, ?)
    ")->execute([
        $agora,
        'not_assigned_lead_to_you',
        $assigned,
        '#leadid=' . $lead_id,
        serialize([$name . ' (Sofia)']),
    ]);
} catch (PDOException $e) { sc_log('Aviso notificação', $e->getMessage()); }

sc_log('Lead criada', ['lead_id' => $lead_id, 'assigned' => $assigned, 'original' => $o['id'] ?? null]);
sc_out(200, ['ok' => true, 'acao' => 'lead_criada', 'lead_id' => $lead_id, 'assigned' => $assigned]);
