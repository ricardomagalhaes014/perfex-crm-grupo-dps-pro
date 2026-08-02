<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }
require_once __DIR__ . '/application/config/app-config.php';
$pdo = new PDO("mysql:host=" . APP_DB_HOSTNAME . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4", APP_DB_USERNAME, APP_DB_PASSWORD);
$stmt = $pdo->query("SELECT staffid, firstname, lastname, email FROM tblstaff ORDER BY staffid");
$staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($staff, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
