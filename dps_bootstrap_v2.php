<?php
/**
 * DPS Bootstrap v2 - Atualiza dps_cache_clear.php do GitHub
 */
header('Content-Type: application/json');
$raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main/dps_cache_clear.php';
$ch = curl_init($raw);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$content = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($content && $http === 200 && strlen($content) > 5000) {
    $dest = __DIR__ . '/dps_cache_clear.php';
    $ok = file_put_contents($dest, $content);
    if (function_exists('opcache_invalidate')) opcache_invalidate($dest, true);
    if (function_exists('opcache_reset')) opcache_reset();
    echo json_encode([
        'status' => $ok !== false ? 'OK' : 'ERRO escrita',
        'bytes' => strlen($content),
        'http' => $http
    ]);
} else {
    echo json_encode(['status' => 'ERRO', 'http' => $http, 'bytes' => strlen($content ?? '')]);
}
