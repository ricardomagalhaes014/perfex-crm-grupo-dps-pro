<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }

require_once __DIR__ . '/application/config/app-config.php';
$pdo = new PDO("mysql:host=".APP_DB_HOSTNAME.";dbname=".APP_DB_NAME.";charset=utf8mb4", APP_DB_USERNAME, APP_DB_PASSWORD);

// Ver 3 leads recentes com tag MV (id=52)
$stmt = $pdo->query("
    SELECT l.id, l.name, l.email, l.description, l.dateadded
    FROM tblleads l
    INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = 52
    ORDER BY l.dateadded DESC
    LIMIT 3
");
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['leads' => $leads], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
