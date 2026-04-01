<?php
// Activar todos os erros para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');
define('CRM_URL', 'https://crm.grupo-dps.com');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

$result = ['steps' => []];

// Passo 1: query teams
$result['steps'][] = 'Passo 1: query teams';
$sql_teams = "SELECT t.id, t.name, t.area FROM tbldps_teams t ORDER BY t.id ASC";
$r = $conn->query($sql_teams);
if (!$r) {
    $result['error'] = 'Teams query error: ' . $conn->error;
    echo json_encode($result);
    exit;
}
$teams = [];
while ($row = $r->fetch_assoc()) {
    $teams[] = $row;
}
$result['teams_count'] = count($teams);
$result['teams'] = $teams;

// Passo 2: query team_members
$result['steps'][] = 'Passo 2: query team_members';
$sql_members = "SELECT m.team_id, m.staff_id, m.role FROM tbldps_team_members m LIMIT 10";
$r2 = $conn->query($sql_members);
if (!$r2) {
    $result['error'] = 'Members query error: ' . $conn->error;
    echo json_encode($result);
    exit;
}
$members = [];
while ($row = $r2->fetch_assoc()) {
    $members[] = $row;
}
$result['members_count'] = count($members);
$result['members'] = $members;

// Passo 3: query staff
$result['steps'][] = 'Passo 3: query staff';
$sql_staff = "SELECT s.staffid, s.firstname, s.lastname, s.landing_slug, s.landing_foto, s.profile_image FROM tblstaff s WHERE s.active = 1 ORDER BY s.staffid ASC";
$r3 = $conn->query($sql_staff);
if (!$r3) {
    $result['error'] = 'Staff query error: ' . $conn->error;
    echo json_encode($result);
    exit;
}
$staff = [];
while ($row = $r3->fetch_assoc()) {
    $staff[] = $row;
}
$result['staff_count'] = count($staff);
$result['staff'] = $staff;

// Passo 4: construir URLs de fotos sem curl
$result['steps'][] = 'Passo 4: construir URLs de fotos';
foreach ($staff as &$s) {
    $base = CRM_URL . '/';
    if (!empty($s['landing_foto'])) {
        $s['foto_url'] = $base . 'uploads/staff_landing_photos/' . $s['staffid'] . '/' . $s['landing_foto'];
    } elseif (!empty($s['profile_image'])) {
        $s['foto_url'] = $base . 'uploads/staff_profile_images/' . $s['staffid'] . '/small_' . $s['profile_image'];
    } else {
        $s['foto_url'] = null;
    }
}

$result['success'] = true;
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$conn->close();
