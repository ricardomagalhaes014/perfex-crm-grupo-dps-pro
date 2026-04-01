<?php
// Diagnóstico temporário - APAGAR APÓS USO
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connect failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

$result = [];

// Verificar tabelas dps_teams e dps_team_members
$tables_to_check = ['tbldps_teams', 'tbldps_team_members', 'tblstaff'];
foreach ($tables_to_check as $tbl) {
    $r = $conn->query("SHOW TABLES LIKE '$tbl'");
    $result['tables'][$tbl] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'NOT FOUND';
}

// Se tbldps_teams existe, ver as colunas
if ($result['tables']['tbldps_teams'] === 'EXISTS') {
    $r = $conn->query("SHOW COLUMNS FROM tbldps_teams");
    while ($row = $r->fetch_assoc()) {
        $result['tbldps_teams_columns'][] = $row['Field'] . ' (' . $row['Type'] . ')';
    }
    // Ver os dados
    $r = $conn->query("SELECT id, name, area FROM tbldps_teams LIMIT 10");
    while ($row = $r->fetch_assoc()) {
        $result['tbldps_teams_data'][] = $row;
    }
}

// Se tbldps_team_members existe, ver as colunas
if ($result['tables']['tbldps_team_members'] === 'EXISTS') {
    $r = $conn->query("SHOW COLUMNS FROM tbldps_team_members");
    while ($row = $r->fetch_assoc()) {
        $result['tbldps_team_members_columns'][] = $row['Field'] . ' (' . $row['Type'] . ')';
    }
    // Ver os dados
    $r = $conn->query("SELECT * FROM tbldps_team_members LIMIT 20");
    while ($row = $r->fetch_assoc()) {
        $result['tbldps_team_members_data'][] = $row;
    }
}

// Verificar colunas landing_ na tabela staff
$r = $conn->query("SHOW COLUMNS FROM tblstaff LIKE 'landing%'");
while ($row = $r->fetch_assoc()) {
    $result['staff_landing_columns'][] = $row['Field'] . ' (' . $row['Type'] . ')';
}

// Verificar staff activos e os seus slugs
$r = $conn->query("SELECT staffid, firstname, lastname, landing_slug, landing_foto, profile_image FROM tblstaff WHERE active = 1 LIMIT 10");
while ($row = $r->fetch_assoc()) {
    $result['staff_active'][] = $row;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$conn->close();
