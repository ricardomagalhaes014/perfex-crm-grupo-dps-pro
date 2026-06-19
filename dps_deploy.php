<?php
// DPS Auto-Deploy - busca ficheiros do GitHub e aplica no servidor
// Uso: https://crm.grupo-dps.com/dps_deploy.php
// Segurança: só funciona com o token correto
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$base    = __DIR__;
$raw     = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
$results = [];

$files = [
    'modules/dps_sofia_calls/controllers/Dps_sofia_calls.php',
    'modules/dps_sofia_calls/models/Dps_sofia_calls_model.php',
    'modules/dps_sofia_calls/views/sofia_calls/index.php',
    'modules/dps_sofia_calls/config/csrf_exclude_uris.php',
];

foreach ($files as $rel) {
    $url     = $raw . '/' . $rel;
    $content = @file_get_contents($url);
    if ($content === false) {
        // Tentar com curl
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $content = curl_exec($ch);
        $http    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$content || $http !== 200) {
            $results[$rel] = 'ERRO ao buscar do GitHub (HTTP ' . $http . ')';
            continue;
        }
    }
    $dest = $base . '/' . $rel;
    $dir  = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ok = file_put_contents($dest, $content);
    $results[$rel] = $ok !== false ? 'OK (' . strlen($content) . ' bytes)' : 'ERRO ao escrever';
}

if (function_exists('opcache_reset')) opcache_reset();

header('Content-Type: application/json');
echo json_encode([
    'status'  => 'deployed',
    'source'  => $raw,
    'files'   => $results,
    'time'    => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);
