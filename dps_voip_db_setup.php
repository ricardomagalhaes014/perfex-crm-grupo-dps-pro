<?php
/**
 * DPS VoIP - Setup DB - Criar tabelas
 * Aceder via: https://crm.grupo-dps.com/dps_voip_db_setup.php
 */
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'u172337921_crmgrupopds', '3AF5_ZCiqQ7:=At', 'u172337921_crmgrupopds');
if ($conn->connect_error) { die(json_encode(['error' => $conn->connect_error])); }
$results = ['db' => 'OK'];
// Descobrir prefixo
$r = $conn->query("SHOW TABLES LIKE '%modules'");
$prefix = 'tbl';
if ($r && $r->num_rows > 0) { $row = $r->fetch_row(); $prefix = str_replace('modules', '', $row[0]); }
$results['prefix'] = $prefix;
// Criar tabelas
$conn->query("CREATE TABLE IF NOT EXISTS `{$prefix}dps_voip_numbers` (`id` int(11) NOT NULL AUTO_INCREMENT, `twilio_number` varchar(20) NOT NULL, `friendly_name` varchar(100) DEFAULT NULL, `twilio_sid` varchar(50) DEFAULT NULL, `staff_id` int(11) DEFAULT NULL, `is_active` tinyint(1) NOT NULL DEFAULT 1, `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), UNIQUE KEY `twilio_number` (`twilio_number`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$results['numbers'] = $conn->errno ? $conn->error : 'OK';
$conn->query("CREATE TABLE IF NOT EXISTS `{$prefix}dps_voip_calls` (`id` int(11) NOT NULL AUTO_INCREMENT, `call_sid` varchar(50) DEFAULT NULL, `direction` enum('outbound','inbound') NOT NULL DEFAULT 'outbound', `from_number` varchar(20) DEFAULT NULL, `to_number` varchar(20) DEFAULT NULL, `staff_id` int(11) DEFAULT NULL, `lead_id` int(11) DEFAULT NULL, `status` varchar(30) NOT NULL DEFAULT 'initiated', `duration` int(11) DEFAULT NULL, `recording_url` varchar(500) DEFAULT NULL, `notes` text DEFAULT NULL, `started_at` datetime DEFAULT NULL, `ended_at` datetime DEFAULT NULL, `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$results['calls'] = $conn->errno ? $conn->error : 'OK';
// Verificar tabelas
$r = $conn->query("SHOW TABLES LIKE '{$prefix}dps_voip_numbers'");
$results['table_numbers'] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'MISSING';
$r = $conn->query("SHOW TABLES LIKE '{$prefix}dps_voip_calls'");
$results['table_calls'] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'MISSING';
// Garantir módulo ativo
$r = $conn->query("SELECT id FROM `{$prefix}modules` WHERE module_name='dps_voip' LIMIT 1");
if (!$r || $r->num_rows === 0) { $conn->query("INSERT INTO `{$prefix}modules` (module_name, active) VALUES ('dps_voip', 1)"); $results['module'] = 'REGISTERED'; }
else { $conn->query("UPDATE `{$prefix}modules` SET active=1 WHERE module_name='dps_voip'"); $results['module'] = 'ACTIVE'; }
// Inserir opções Twilio
foreach (['dps_voip_twilio_account_sid'=>'','dps_voip_twilio_auth_token'=>'','dps_voip_twilio_app_sid'=>'','dps_voip_record_calls'=>'0','dps_voip_default_timeout'=>'30'] as $n=>$v) {
    $r = $conn->query("SELECT id FROM `{$prefix}options` WHERE name='$n'");
    if (!$r || $r->num_rows === 0) { $conn->query("INSERT INTO `{$prefix}options` (name, value) VALUES ('$n', '$v')"); $results[$n] = 'CREATED'; }
    else { $results[$n] = 'EXISTS'; }
}
$conn->close();

// Deploy de ficheiros do GitHub se pedido
if (isset($_GET['deploy'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main/';
    $files_to_deploy = [
        'dps_voip_diag2.php',
        'modules/dps_voip/controllers/Dps_voip.php',
        'modules/dps_voip/models/Dps_voip_model.php',
        'modules/dps_voip/views/dps_voip/index.php',
        'modules/dps_voip/dps_voip.php',
        'dps_voip_test.php',
        'dps_voip_check2.php',
    ];
    foreach ($files_to_deploy as $f) {
        $ch = curl_init($raw . $f . '?t=' . time());
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
        $fc = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $dest = __DIR__ . '/' . $f;
        if ($fc && $http === 200 && strlen($fc) > 100) {
            @mkdir(dirname($dest), 0755, true);
            file_put_contents($dest, $fc);
            if (function_exists('opcache_invalidate')) opcache_invalidate($dest, true);
            $results['deploy_' . basename($f)] = 'OK (' . strlen($fc) . ' bytes)';
        } else {
            $results['deploy_' . basename($f)] = 'FAIL (HTTP ' . $http . ')';
        }
    }
    if (function_exists('opcache_reset')) opcache_reset();
}

echo json_encode($results, JSON_PRETTY_PRINT);
