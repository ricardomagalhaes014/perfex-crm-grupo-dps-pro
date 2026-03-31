<?php
// Limpar cache do Perfex CRM
$cache_dir = __DIR__ . '/application/cache/';
$count = 0;
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '*');
    foreach ($files as $f) {
        if (is_file($f) && basename($f) !== '.htaccess' && basename($f) !== 'index.html') {
            unlink($f);
            $count++;
        }
    }
}

// Verificar também cache de views
$view_cache = __DIR__ . '/application/cache/views/';
if (is_dir($view_cache)) {
    $files = glob($view_cache . '*');
    foreach ($files as $f) {
        if (is_file($f)) { unlink($f); $count++; }
    }
}

echo json_encode(['cleared' => $count, 'cache_dir' => $cache_dir]);
