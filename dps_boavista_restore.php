<?php
header('Content-Type: application/json');
$raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/boavistatowers/master';
$base = '/home/u172337921/domains/dpsimobiliario.pt/public_html';
$files = ['boavistatowers/index.html', 'boavistatowers/logo.png', 'boavistatowers/sofia_boavista.png'];
$results = [];
foreach ($files as $rel) {
    $url = $raw . '/' . basename($rel);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>60,CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_USERAGENT=>'DPS-Deploy/1.0']);
    $fc = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($fc && $http === 200 && strlen($fc) > 100) {
        $dest = $base . '/' . $rel; $dir = dirname($dest);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $ok = file_put_contents($dest, $fc);
        $results[$rel] = $ok !== false ? 'OK (' . strlen($fc) . ' bytes)' : 'ERRO escrita';
    } else { $results[$rel] = 'ERRO GitHub HTTP ' . $http . ' size=' . strlen($fc ?? ''); }
}
if (function_exists('opcache_reset')) opcache_reset();
echo json_encode(['ok'=>true,'results'=>$results,'ts'=>date('Y-m-d H:i:s'),'base'=>$base], JSON_PRETTY_PRINT);
