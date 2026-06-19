<?php
header('Content-Type: application/json');
$results = [];
$ci_config = __DIR__ . '/application/config/database.php';
if (!file_exists($ci_config)) { die(json_encode(['error' => 'database.php nao encontrado'])); }
include_once($ci_config);
$conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
if ($conn->connect_error) { die(json_encode(['error' => $conn->connect_error])); }

// Descobrir prefixo
$r = $conn->query("SHOW TABLES LIKE '%modules'");
$prefix = 'tbl';
if ($r && $r->num_rows > 0) { $row = $r->fetch_row(); $prefix = str_replace('modules', '', $row[0]); }
$results['prefix'] = $prefix;

// Verificar tabelas sofia
foreach (['dps_sofia_campaigns', 'dps_sofia_call_logs'] as $t) {
    $r = $conn->query("SHOW TABLES LIKE '{$prefix}{$t}'");
    $results["table_{$t}"] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'NOT FOUND';
}

// Verificar módulo instalado
$r = $conn->query("SELECT module_name, active FROM `{$prefix}modules` WHERE module_name='dps_sofia_calls' LIMIT 1");
if ($r && $r->num_rows > 0) {
    $m = $r->fetch_assoc();
    $results['module'] = $m;
} else {
    $results['module'] = 'NOT INSTALLED';
}

// Se tabela existe, contar campanhas
if ($results['table_dps_sofia_campaigns'] === 'EXISTS') {
    $r = $conn->query("SELECT COUNT(*) as c FROM `{$prefix}dps_sofia_campaigns`");
    $results['campaigns_count'] = $r ? $r->fetch_assoc()['c'] : 'erro';
    $r = $conn->query("SELECT id, name, status, created_at FROM `{$prefix}dps_sofia_campaigns` ORDER BY id DESC LIMIT 5");
    $results['campaigns'] = [];
    if ($r) while ($row = $r->fetch_assoc()) $results['campaigns'][] = $row;
}

// Verificar view CSRF
$view = __DIR__ . '/modules/dps_sofia_calls/views/sofia_calls/index.php';
$results['view_exists'] = file_exists($view);
if (file_exists($view)) {
    $results['view_size'] = filesize($view);
    $results['view_has_csrf'] = strpos(file_get_contents($view), 'get_csrf_for_ajax') !== false;
}

$conn->close();
echo json_encode($results, JSON_PRETTY_PRINT);
