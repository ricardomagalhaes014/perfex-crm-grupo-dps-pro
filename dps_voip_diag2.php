<?php
header('Content-Type: text/plain');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ler error_log
$paths = [
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log',
    '/tmp/php_errors.log',
    ini_get('error_log'),
];
foreach ($paths as $p) {
    if ($p && file_exists($p) && is_readable($p) && filesize($p) > 0) {
        echo "=== LOG: $p (" . filesize($p) . " bytes) ===\n";
        echo substr(file_get_contents($p), -3000);
        echo "\n\n";
    }
}

// Verificar módulo
$mod = __DIR__ . '/modules/dps_voip';
echo "Module dir: " . (is_dir($mod) ? 'YES' : 'NO') . "\n";
foreach (['dps_voip.php', 'controllers/Dps_voip.php', 'models/Dps_voip_model.php', 'views/dps_voip/index.php'] as $f) {
    $fp = $mod . '/' . $f;
    echo basename($f) . ": " . (file_exists($fp) ? filesize($fp) . " bytes" : "NOT FOUND") . "\n";
}

// Verificar se o ficheiro principal tem erros
echo "\n=== SYNTAX CHECK ===\n";
foreach (['controllers/Dps_voip.php', 'models/Dps_voip_model.php', 'dps_voip.php'] as $f) {
    $fp = $mod . '/' . $f;
    if (file_exists($fp)) {
        $out = shell_exec('php -l ' . escapeshellarg($fp) . ' 2>&1');
        echo basename($f) . ": " . trim($out) . "\n";
    }
}

// Tentar fazer include do controller para ver o erro
echo "\n=== INCLUDE TEST ===\n";
define('BASEPATH', __DIR__ . '/');
try {
    ob_start();
    include_once($mod . '/controllers/Dps_voip.php');
    ob_end_clean();
    echo "Controller: OK\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "Controller ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
