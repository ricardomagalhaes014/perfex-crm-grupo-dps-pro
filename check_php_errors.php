<?php
// Verificar os logs de erro PHP recentes
$log_dir = __DIR__ . '/application/logs/';
$logs = glob($log_dir . 'log-2026-03-31.php');
if ($logs) {
    $content = file_get_contents($logs[0]);
    // Mostrar as últimas 50 linhas
    $lines = explode("\n", $content);
    $last = array_slice($lines, -50);
    echo implode("\n", $last);
} else {
    echo "Sem logs de hoje";
    // Verificar logs recentes
    $all_logs = glob($log_dir . 'log-*.php');
    if ($all_logs) {
        rsort($all_logs);
        echo "\nLogs disponíveis: " . implode(', ', array_slice($all_logs, 0, 3));
    }
}
