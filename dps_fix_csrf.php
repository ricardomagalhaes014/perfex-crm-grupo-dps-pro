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
header('Content-Type: application/json');
echo json_encode(['status'=>'csrf_fix_applied','files'=>$results,'next'=>'Agora use dps_cache_clear.php?deploy_sofia para futuros updates','time'=>date('Y-m-d H:i:s')], JSON_PRETTY_PRINT);
