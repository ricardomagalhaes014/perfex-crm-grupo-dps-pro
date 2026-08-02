<?php
/**
 * Debug - Ver formato real das leads com tag MV
 * Uso: https://crm.grupo-dps.com/debug-leads.php?token=dps2026deploy
 */
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }

require_once __DIR__ . '/application/config/app-config.php';
$pdo = new PDO("mysql:host=".APP_DB_HOSTNAME.";dbname=".APP_DB_NAME.";charset=utf8mb4", APP_DB_USERNAME, APP_DB_PASSWORD);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ver as 10 leads mais recentes com tag MV
$stmt = $pdo->prepare("
    SELECT l.id, l.name, l.email, l.description, l.dateadded, l.source
    FROM tblleads l
    INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = 52
    ORDER BY l.dateadded DESC
    LIMIT 10
");
$stmt->execute();
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ver também as notas/actividades dessas leads para encontrar o form_id
$lead_ids = array_column($leads, 'id');
$notes = [];
if ($lead_ids) {
    $placeholders = implode(',', array_fill(0, count($lead_ids), '?'));
    $stmt2 = $pdo->prepare("SELECT * FROM tbllead_activity_log WHERE leadid IN ($placeholders) ORDER BY dateadded DESC LIMIT 30");
    $stmt2->execute($lead_ids);
    $notes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode([
    'total_mv_leads' => count($leads),
    'recent_leads' => $leads,
    'activity_log' => $notes
], JSON_PRETTY_PRINT);
