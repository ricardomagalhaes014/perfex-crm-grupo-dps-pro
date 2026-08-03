<?php
define('BASEPATH', __DIR__ . '/');
$token = $_GET['token'] ?? '';
if ($token !== 'dps-samara-check-2026') { http_response_code(403); die('Forbidden'); }

$cfg = __DIR__ . '/application/config/app-config.php';
if (!file_exists($cfg)) { die(json_encode(['error' => 'config not found: '.$cfg])); }
include_once $cfg;

$dsn = "mysql:host=".APP_DB_HOSTNAME.";dbname=".APP_DB_NAME.";charset=utf8mb4";
$pdo = new PDO($dsn, APP_DB_USERNAME, APP_DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$id = intval($_GET['id'] ?? 0);
if ($id) {
    $s = $pdo->prepare("SELECT l.id,l.name,l.email,l.phonenumber,l.assigned,l.date_added,
        s.firstname,s.lastname,GROUP_CONCAT(t.name SEPARATOR ',') tags
        FROM tblleads l
        LEFT JOIN tblstaff s ON s.staffid=l.assigned
        LEFT JOIN tbltags_rel tr ON tr.relid=l.id AND tr.type='lead'
        LEFT JOIN tbltags t ON t.id=tr.tagid
        WHERE l.id=? GROUP BY l.id");
    $s->execute([$id]);
    header('Content-Type: application/json');
    echo json_encode($s->fetch(PDO::FETCH_ASSOC));
    exit;
}
$s = $pdo->query("SELECT l.id,l.name,l.assigned,l.date_added,
    s.firstname,s.lastname,GROUP_CONCAT(t.name SEPARATOR ',') tags
    FROM tblleads l
    LEFT JOIN tblstaff s ON s.staffid=l.assigned
    LEFT JOIN tbltags_rel tr ON tr.relid=l.id AND tr.type='lead'
    LEFT JOIN tbltags t ON t.id=tr.tagid
    GROUP BY l.id ORDER BY l.id DESC LIMIT 20");
header('Content-Type: application/json');
echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
