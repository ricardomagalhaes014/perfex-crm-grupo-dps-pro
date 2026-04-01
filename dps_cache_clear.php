<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$results = [];

// Invalidar o imoveis-api.php especificamente
$file = __DIR__ . '/imoveis-api.php';
if (function_exists('opcache_invalidate')) {
    $results['opcache_invalidate_imoveis'] = opcache_invalidate($file, true) ? 'OK' : 'FAILED';
}
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset() ? 'OK' : 'FAILED';
}

// Verificar o conteúdo actual do imoveis-api.php (primeiras 500 chars)
$results['imoveis_api_preview'] = substr(file_get_contents($file), 0, 500);

// Verificar se a linha do LEFT JOIN ainda existe
$content = file_get_contents($file);
$results['has_left_join_customfields'] = strpos($content, 'customfieldsvalues') !== false ? 'SIM (versão antiga!)' : 'NÃO (versão nova OK)';
$results['has_landing_whatsapp'] = strpos($content, 'landing_whatsapp') !== false ? 'SIM (versão nova OK)' : 'NÃO';

$results['php_version'] = PHP_VERSION;
$results['file_mtime'] = date('Y-m-d H:i:s', filemtime($file));

echo json_encode($results, JSON_PRETTY_PRINT);
