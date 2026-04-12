<?php
// Script de diagnóstico - verificar conteúdo do meta-webhook.php
if (($_GET['key'] ?? '') !== 'dps-check-2026') { http_response_code(403); exit; }
header('Content-Type: text/plain');

$file = __DIR__ . '/meta-webhook.php';
if (!file_exists($file)) { echo "FICHEIRO NÃO EXISTE"; exit; }

$content = file_get_contents($file);
// Mostrar linhas relevantes
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    $n = $i + 1;
    if (strpos($line, 'PERFEX_API_TOKEN') !== false ||
        strpos($line, 'default_headers') !== false ||
        strpos($line, 'Content-Type') !== false ||
        strpos($line, 'http_post') !== false) {
        echo "L{$n}: {$line}\n";
    }
}
echo "\n--- HASH MD5 DO FICHEIRO: " . md5($content) . " ---\n";
echo "--- TAMANHO: " . strlen($content) . " bytes ---\n";
echo "--- MODIFICADO: " . date('Y-m-d H:i:s', filemtime($file)) . " ---\n";
