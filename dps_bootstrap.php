<?php
/**
 * Bootstrap script - instala o dps_cache_clear.php atualizado e o dps_sofia_debug.php
 * Aceder via: https://crm.grupo-dps.com/dps_bootstrap.php
 * APAGAR após uso!
 */

$raw_base = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
$results  = [];

$files = [
    'dps_cache_clear.php',
    'dps_sofia_debug.php',
];

foreach ($files as $f) {
    $ch = curl_init($raw_base . '/' . $f);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $content = curl_exec($ch);
    $http    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $dest = __DIR__ . '/' . $f;
    if ($content && $http === 200 && strlen($content) > 100) {
        file_put_contents($dest, $content);
        if (function_exists('opcache_invalidate')) opcache_invalidate($dest, true);
        $results[$f] = 'OK (' . strlen($content) . ' bytes)';
    } else {
        $results[$f] = 'ERRO HTTP ' . $http;
    }
}

if (function_exists('opcache_reset')) opcache_reset();

header('Content-Type: application/json');
echo json_encode(['status' => 'done', 'files' => $results], JSON_PRETTY_PRINT);
