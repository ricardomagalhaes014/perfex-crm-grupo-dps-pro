<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps-samara-check-2026') { http_response_code(403); die('Forbidden'); }
require_once __DIR__ . '/application/config/app-config.php';
$pdo = new PDO("mysql:host=".APP_DB_HOSTNAME.";dbname=".APP_DB_NAME.";charset=utf8mb4", APP_DB_USERNAME, APP_DB_PASSWORD);

$lead_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($lead_id) {
    $stmt = $pdo->prepare("SELECT l.id, l.name, l.email, l.phonenumber, l.assigned, l.status, l.date_added,
        s.firstname, s.lastname,
        GROUP_CONCAT(t.name SEPARATOR ',') as tags
        FROM tblleads l
        LEFT JOIN tblstaff s ON s.staffid = l.assigned
        LEFT JOIN tbltags_rel tr ON tr.relid = l.id AND tr.type = 'lead'
        LEFT JOIN tbltags t ON t.id = tr.tagid
        WHERE l.id = ?
        GROUP BY l.id");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['lead' => $lead], JSON_PRETTY_PRINT);
    exit;
}

$stmt2 = $pdo->query("SELECT l.id, l.name, l.assigned, l.date_added,
    s.firstname, s.lastname,
    GROUP_CONCAT(t.name SEPARATOR ',') as tags
    FROM tblleads l
    LEFT JOIN tblstaff s ON s.staffid = l.assigned
    LEFT JOIN tbltags_rel tr ON tr.relid = l.id AND tr.type = 'lead'
    LEFT JOIN tbltags t ON t.id = tr.tagid
    GROUP BY l.id ORDER BY l.id DESC LIMIT 20");
$recent = $stmt2->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode(['recent' => $recent], JSON_PRETTY_PRINT);
