<?php
/**
 * DPS Force Update - Força atualização dos ficheiros HTML do GitHub
 * Executar uma vez e depois apagar
 */
if (!isset($_GET['key']) || $_GET['key'] !== 'dps2026force') {
    die(json_encode(['error' => 'Acesso negado']));
}

$base = '/home/u172337921/domains/dpsimobiliario.pt/public_html';
$github_raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/dpsimobiliario.pt/main';

$files = [
    'belohorizonte/index.html',
    'raizes/index.html',
    'index.html',
];

$results = [];
foreach ($files as $file) {
    $url = $github_raw . '/' . $file;
    $content = @file_get_contents($url);
    if ($content === false) {
        $results[$file] = 'ERROR_FETCH';
        continue;
    }
    $dest = $base . '/' . $file;
    if (@file_put_contents($dest, $content) !== false) {
        $results[$file] = 'UPDATED';
    } else {
        $results[$file] = 'ERROR_WRITE';
    }
}

echo json_encode(['ok' => true, 'results' => $results, 'ts' => date('Y-m-d H:i:s')]);
