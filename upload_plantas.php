<?php
// Script temporário de upload de plantas
$secret = 'dps2026upload';
$received_secret = $_POST['secret'] ?? '';

if ($received_secret !== $secret) {
    http_response_code(403);
    die('Forbidden');
}

$filename = $_POST['filename'] ?? '';
$content_b64 = $_POST['content'] ?? '';

if (empty($filename) || empty($content_b64)) {
    die('Missing params');
}

// Sanitizar nome do ficheiro
$filename = basename($filename);
if (!preg_match('/^[A-Z_0-9neg]+\.pdf$/i', $filename)) {
    die('Invalid filename: ' . $filename);
}

$content = base64_decode($content_b64);
$target_dir = '/var/www/html/simuladorportugal/raizes/plantas/';
$target_path = $target_dir . $filename;

if (file_put_contents($target_path, $content) !== false) {
    echo "OK: $filename";
} else {
    echo "ERROR: Could not write $filename";
}
