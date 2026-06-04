<?php
// Script de deploy de plantas - executa no servidor CRM
// Vai buscar as plantas de URLs públicos e guarda-as na pasta correcta

$secret = $_GET['secret'] ?? $_POST['secret'] ?? '';
if ($secret !== 'dps2026plantas') {
    http_response_code(403);
    die('Forbidden');
}

$target_dir = '/var/www/html/simuladorportugal/raizes/plantas/';

// Lista de plantas e URLs (será preenchida com URLs S3)
$plantas = json_decode(file_get_contents(__DIR__ . '/plantas_urls.json'), true);

if (!$plantas) {
    die('ERROR: plantas_urls.json not found');
}

$ok = 0;
$errors = [];

foreach ($plantas as $filename => $url) {
    $content = @file_get_contents($url);
    if ($content === false) {
        $errors[] = "FAIL: $filename";
        continue;
    }
    $target = $target_dir . $filename;
    if (file_put_contents($target, $content) !== false) {
        $ok++;
        echo "OK: $filename\n";
        flush();
    } else {
        $errors[] = "WRITE_ERROR: $filename";
    }
}

echo "\n=== RESULTADO ===\n";
echo "OK: $ok\n";
echo "Erros: " . count($errors) . "\n";
foreach ($errors as $e) echo "$e\n";
