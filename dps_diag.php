<?php
// DPS Diagnóstico - verifica estado do módulo Sofia Calls
header('Content-Type: application/json');
$results = [];

// Conectar à BD
$ci_config = __DIR__ . '/application/config/database.php';
if (!file_exists($ci_config)) { die(json_encode(['error' => 'database.php não encontrado'])); }
include_once($ci_config);
$conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
if ($conn->connect_error) { die(json_encode(['error' => 'BD: ' . $conn->connect_error])); }

$prefix = 'tbl';
// Tentar descobrir o prefixo
$r = $conn->query("SHOW TABLES LIKE '%modules'");
if ($r && $r->num_rows > 0) {
    $row = $r->fetch_row();
    $prefix = str_replace('modules', '', $row[0]);
}
$results['db_prefix'] = $prefix;

// Verificar se o módulo está instalado
$r = $conn->query("SELECT * FROM `{$prefix}modules` WHERE `module_name` = 'dps_sofia_calls' LIMIT 1");
if ($r && $r->num_rows > 0) {
    $mod = $r->fetch_assoc();
    $results['module_installed'] = true;
    $results['module_active'] = $mod['active'] ?? 'N/A';
} else {
    $results['module_installed'] = false;
}

// Verificar se a tabela dps_sofia_campaigns existe
$r = $conn->query("SHOW TABLES LIKE '{$prefix}dps_sofia_campaigns'");
$results['table_campaigns_exists'] = ($r && $r->num_rows > 0);

// Verificar se a tabela dps_sofia_call_logs existe
$r = $conn->query("SHOW TABLES LIKE '{$prefix}dps_sofia_call_logs'");
$results['table_call_logs_exists'] = ($r && $r->num_rows > 0);

// Contar campanhas
if ($results['table_campaigns_exists']) {
    $r = $conn->query("SELECT COUNT(*) as c FROM `{$prefix}dps_sofia_campaigns`");
    $results['campaigns_count'] = $r ? $r->fetch_assoc()['c'] : 'erro';
    // Últimas campanhas
    $r = $conn->query("SELECT id, name, status, created_at FROM `{$prefix}dps_sofia_campaigns` ORDER BY id DESC LIMIT 3");
    $results['last_campaigns'] = [];
    if ($r) while ($row = $r->fetch_assoc()) $results['last_campaigns'][] = $row;
}

// Verificar ficheiros do módulo
$module_dir = __DIR__ . '/modules/dps_sofia_calls';
$results['controller_exists'] = file_exists($module_dir . '/controllers/Dps_sofia_calls.php');
$results['model_exists'] = file_exists($module_dir . '/models/Dps_sofia_calls_model.php');
$results['view_exists'] = file_exists($module_dir . '/views/sofia_calls/index.php');
$results['csrf_exclude_exists'] = file_exists($module_dir . '/config/csrf_exclude_uris.php');

// Verificar tamanho da view (17334 = versão com CSRF)
if ($results['view_exists']) {
    $results['view_size'] = filesize($module_dir . '/views/sofia_calls/index.php');
    $results['view_has_csrf'] = strpos(file_get_contents($module_dir . '/views/sofia_calls/index.php'), 'get_csrf_for_ajax') !== false;
}

$conn->close();
echo json_encode($results, JSON_PRETTY_PRINT);
