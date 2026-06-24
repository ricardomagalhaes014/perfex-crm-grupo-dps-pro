<?php
header('Content-Type: application/json');
$results = [];
// Tentar ler o error log
$log_paths = [
    __DIR__ . '/error_log',
    __DIR__ . '/../error_log',
    '/var/log/php_errors.log',
    ini_get('error_log'),
];
foreach ($log_paths as $p) {
    if ($p && file_exists($p)) {
        $content = file_get_contents($p);
        // Pegar as últimas 3000 chars
        $results['log_' . basename($p)] = substr($content, -3000);
        break;
    }
}
// Tentar carregar o módulo manualmente para ver o erro
error_reporting(E_ALL);
ini_set('display_errors', 1);
$results['basepath'] = __DIR__;
// Verificar se os ficheiros do módulo existem
$mod = __DIR__ . '/modules/dps_voip/';
$results['mod_exists'] = is_dir($mod) ? 'YES' : 'NO';
$results['controller'] = file_exists($mod . 'controllers/Dps_voip.php') ? 'YES' : 'NO';
$results['model'] = file_exists($mod . 'models/Dps_voip_model.php') ? 'YES' : 'NO';
$results['view'] = file_exists($mod . 'views/dps_voip/index.php') ? 'YES' : 'NO';
$results['main'] = file_exists($mod . 'dps_voip.php') ? 'YES' : 'NO';
// Verificar sintaxe dos ficheiros PHP
foreach (['controllers/Dps_voip.php', 'models/Dps_voip_model.php', 'dps_voip.php'] as $f) {
    $fp = $mod . $f;
    if (file_exists($fp)) {
        $output = shell_exec("php -l " . escapeshellarg($fp) . " 2>&1");
        $results['syntax_' . basename($f)] = trim($output);
    }
}
echo json_encode($results, JSON_PRETTY_PRINT);
