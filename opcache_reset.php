<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$result = [];
if (function_exists('opcache_reset')) {
    $result['opcache_reset'] = opcache_reset() ? 'OK' : 'FAILED';
} else {
    $result['opcache_reset'] = 'function not available';
}
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    $result['opcache_enabled'] = $status['opcache_enabled'] ?? false;
    $result['cached_scripts'] = $status['opcache_statistics']['num_cached_scripts'] ?? 0;
}
$result['php_version'] = PHP_VERSION;
$result['timestamp'] = date('Y-m-d H:i:s');
echo json_encode($result);
