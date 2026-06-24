<?php
header('Content-Type: text/plain');
$paths = [
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log',
    __DIR__ . '/../../error_log',
    '/tmp/php_errors.log',
    ini_get('error_log'),
];
foreach ($paths as $p) {
    if ($p && file_exists($p) && is_readable($p) && filesize($p) > 0) {
        echo "=== $p (" . filesize($p) . " bytes) ===\n";
        echo substr(file_get_contents($p), -3000);
        echo "\n\n";
    }
}
// Também tentar activar display_errors e carregar o módulo
ini_set('display_errors', 1);
error_reporting(E_ALL);
// Tentar incluir o bootstrap do CI para ver o erro
echo "PHP version: " . PHP_VERSION . "\n";
echo "CWD: " . __DIR__ . "\n";
// Verificar se o módulo existe
$mod = __DIR__ . '/modules/dps_voip';
echo "Module dir exists: " . (is_dir($mod) ? 'YES' : 'NO') . "\n";
echo "Controller exists: " . (file_exists($mod . '/controllers/Dps_voip.php') ? 'YES' : 'NO') . "\n";
echo "Model exists: " . (file_exists($mod . '/models/Dps_voip_model.php') ? 'YES' : 'NO') . "\n";
echo "View exists: " . (file_exists($mod . '/views/dps_voip/index.php') ? 'YES' : 'NO') . "\n";
echo "Main file exists: " . (file_exists($mod . '/dps_voip.php') ? 'YES' : 'NO') . "\n";
// Verificar tamanhos
foreach (['dps_voip.php', 'controllers/Dps_voip.php', 'models/Dps_voip_model.php', 'views/dps_voip/index.php'] as $f) {
    $fp = $mod . '/' . $f;
    if (file_exists($fp)) echo basename($f) . ": " . filesize($fp) . " bytes\n";
}
