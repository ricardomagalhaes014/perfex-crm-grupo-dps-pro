<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }

require __DIR__ . '/application/config/app-config.php';
try {
    $pdo = new PDO(
        "mysql:host=" . APP_DB_HOSTNAME . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
        APP_DB_USERNAME, APP_DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]); exit;
}

// Últimas 10 leads com tag MV
$stmt = $pdo->query("
    SELECT l.id, l.name, l.email, l.description, l.dateadded, l.source
    FROM tblleads l
    INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead'
    INNER JOIN tbltags tg ON tg.id = t.tag_id AND tg.name = 'MV'
    ORDER BY l.dateadded DESC
    LIMIT 10
");
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Últimas 10 leads com tag AURA
$stmt2 = $pdo->query("
    SELECT l.id, l.name, l.email, l.description, l.dateadded, l.source
    FROM tblleads l
    INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead'
    INNER JOIN tbltags tg ON tg.id = t.tag_id AND tg.name = 'AURA'
    ORDER BY l.dateadded DESC
    LIMIT 10
");
$aura_leads = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'last_mv_leads' => $leads,
    'last_aura_leads' => $aura_leads
], JSON_PRETTY_PRINT);
