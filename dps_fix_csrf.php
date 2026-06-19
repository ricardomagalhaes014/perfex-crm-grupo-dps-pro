<?php
// DPS Fix CSRF + Auto-Deploy - busca ficheiros do GitHub e aplica
// Uso: https://crm.grupo-dps.com/dps_fix_csrf.php
$raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
$files = [
    'modules/dps_sofia_calls/controllers/Dps_sofia_calls.php',
    'modules/dps_sofia_calls/models/Dps_sofia_calls_model.php',
    'modules/dps_sofia_calls/views/sofia_calls/index.php',
    'modules/dps_sofia_calls/config/csrf_exclude_uris.php',
    'dps_cache_clear.php',
    'dps_diag.php',
];
$results = [];
foreach ($files as $rel) {
    $ch = curl_init($raw . '/' . $rel);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $c = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($c && $http === 200) {
        $dest = __DIR__ . '/' . $rel; $dir = dirname($dest);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ok = file_put_contents($dest, $c);
        $results[$rel] = $ok !== false ? 'OK (' . strlen($c) . ' bytes)' : 'ERRO escrita';
    } else { $results[$rel] = 'ERRO GitHub HTTP ' . $http; }
}
if (function_exists('opcache_reset')) opcache_reset();

// ===== DIAGNÓSTICO BD SOFIA =====
$ci_config = __DIR__ . '/application/config/database.php';
if (file_exists($ci_config)) {
    include_once($ci_config);
    $conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
    if (!$conn->connect_error) {
        $r = $conn->query("SHOW TABLES LIKE '%modules'");
        $prefix = 'tbl';
        if ($r && $r->num_rows > 0) { $row = $r->fetch_row(); $prefix = str_replace('modules', '', $row[0]); }
        $results['db_prefix'] = $prefix;
        foreach (['dps_sofia_campaigns', 'dps_sofia_call_logs'] as $t) {
            $r = $conn->query("SHOW TABLES LIKE '{$prefix}{$t}'");
            $results["table_{$t}"] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'NOT FOUND';
        }
        $r = $conn->query("SELECT module_name, active FROM `{$prefix}modules` WHERE module_name='dps_sofia_calls' LIMIT 1");
        $results['module_sofia'] = ($r && $r->num_rows > 0) ? $r->fetch_assoc() : 'NOT INSTALLED';
        if (isset($results['table_dps_sofia_campaigns']) && $results['table_dps_sofia_campaigns'] === 'EXISTS') {
            $r = $conn->query("SELECT COUNT(*) as c FROM `{$prefix}dps_sofia_campaigns`");
            $results['campaigns_count'] = $r ? $r->fetch_assoc()['c'] : 0;
        }
        $conn->close();
    } else { $results['db_error'] = $conn->connect_error; }
}
$mod = __DIR__ . '/modules/dps_sofia_calls';
$results['view_has_csrf'] = file_exists($mod.'/views/sofia_calls/index.php') ? 
    (strpos(file_get_contents($mod.'/views/sofia_calls/index.php'), 'get_csrf_for_ajax') !== false) : false;
$results['csrf_exclude_exists'] = file_exists($mod.'/config/csrf_exclude_uris.php');

header('Content-Type: application/json');
echo json_encode(['status'=>'csrf_fix_applied','files'=>$results,'next'=>'Agora use dps_cache_clear.php?deploy_sofia para futuros updates','time'=>date('Y-m-d H:i:s')], JSON_PRETTY_PRINT);
