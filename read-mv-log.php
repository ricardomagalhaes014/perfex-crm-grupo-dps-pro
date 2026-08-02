<?php
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$log_file = __DIR__ . '/mv-lead-debug.log';
$aura_log = __DIR__ . '/aura-lead-debug.log';

echo "=== MV LOG ===\n";
if (file_exists($log_file)) {
    $content = file_get_contents($log_file);
    $lines = explode("\n", $content);
    $last_lines = array_slice($lines, -50);
    echo implode("\n", $last_lines);
} else {
    echo "Log MV não existe\n";
}

echo "\n\n=== AURA LOG ===\n";
if (file_exists($aura_log)) {
    $content = file_get_contents($aura_log);
    $lines = explode("\n", $content);
    $last_lines = array_slice($lines, -50);
    echo implode("\n", $last_lines);
} else {
    echo "Log AURA não existe\n";
}
