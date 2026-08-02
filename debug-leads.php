<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }

header('Content-Type: application/json');

// Passo 1: Verificar se o app-config.php carrega
try {
    require_once __DIR__ . '/application/config/app-config.php';
    $step1 = 'OK - APP_DB_HOSTNAME=' . (defined('APP_DB_HOSTNAME') ? APP_DB_HOSTNAME : 'NOT_DEFINED');
} catch (Exception $e) {
    echo json_encode(['step' => 1, 'error' => $e->getMessage()]);
    exit;
}

// Passo 2: Ligar à BD
try {
    $pdo = new PDO(
        "mysql:host=".APP_DB_HOSTNAME.";dbname=".APP_DB_NAME.";charset=utf8mb4",
        APP_DB_USERNAME,
        APP_DB_PASSWORD,
        [PDO::ATTR_TIMEOUT => 5]
    );
    $step2 = 'OK';
} catch (Exception $e) {
    echo json_encode(['step' => 1, 'ok' => $step1, 'step2_error' => $e->getMessage()]);
    exit;
}

// Passo 3: Query simples
$stmt = $pdo->query("SELECT COUNT(*) as total FROM tblleads");
$count = $stmt->fetch(PDO::FETCH_ASSOC);

// Passo 4: Ver 2 leads com tag MV
$stmt2 = $pdo->query("SELECT l.id, l.name, l.description FROM tblleads l INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = 52 ORDER BY l.id DESC LIMIT 2");
$leads = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'config' => $step1,
    'db' => $step2,
    'total_leads' => $count,
    'mv_leads_sample' => $leads
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
