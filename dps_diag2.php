<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

$result = [];

// Teste 1: query staff com landing_whatsapp
$r = $conn->query("SELECT staffid, firstname, lastname, landing_slug, landing_foto, landing_whatsapp, profile_image, is_admin FROM tblstaff WHERE active = 1 LIMIT 3");
if (!$r) {
    $result['staff_query_error'] = $conn->error;
} else {
    while ($row = $r->fetch_assoc()) {
        $result['staff'][] = $row;
    }
}

// Teste 2: query teams com join
$r2 = $conn->query("SELECT t.id, t.name, t.area, m.staff_id, m.role FROM tbldps_teams t LEFT JOIN tbldps_team_members m ON m.team_id = t.id WHERE t.area = 'imo' ORDER BY t.id ASC LIMIT 10");
if (!$r2) {
    $result['teams_query_error'] = $conn->error;
} else {
    while ($row = $r2->fetch_assoc()) {
        $result['teams'][] = $row;
    }
}

// Teste 3: verificar se curl funciona para landing_foto do Cláudio
$ch = curl_init('https://crm.grupo-dps.com/uploads/staff_landing_photos/46/landing_1774987779.jpeg');
curl_setopt_array($ch, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_FOLLOWLOCATION => true]);
curl_exec($ch);
$result['curl_landing_foto_46'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Teste 4: verificar foto de perfil do Ricardo (id=1)
$ch2 = curl_init('https://crm.grupo-dps.com/uploads/staff_profile_images/1/small_IMG_1174.jpeg');
curl_setopt_array($ch2, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_FOLLOWLOCATION => true]);
curl_exec($ch2);
$result['curl_profile_foto_1'] = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

// Teste 5: simular get_equipa sem curl (sem verificar foto)
$staff_sql = "SELECT s.staffid AS id, s.firstname AS nome, s.lastname AS apelido, s.profile_image AS foto, s.landing_foto, s.landing_slug, s.is_admin FROM tblstaff s WHERE s.active = 1 ORDER BY s.is_admin DESC, s.staffid ASC";
$sr = $conn->query($staff_sql);
if (!$sr) {
    $result['staff_equipa_error'] = $conn->error;
} else {
    $result['staff_equipa_count'] = $sr->num_rows;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$conn->close();
