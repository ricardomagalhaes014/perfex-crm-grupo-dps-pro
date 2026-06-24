<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular o ambiente do Perfex CRM
define('BASEPATH', '');
define('APPPATH', __DIR__ . '/application/');

// Tentar incluir o ficheiro principal do módulo
$module_path = __DIR__ . '/modules/dps_voip/';
$files = [
    'dps_voip.php',
    'controllers/Dps_voip.php',
    'models/Dps_voip_model.php',
    'views/dps_voip/index.php',
];

$results = [];
foreach ($files as $f) {
    $full = $module_path . $f;
    if (file_exists($full)) {
        $results[$f] = filesize($full) . ' bytes';
        // Verificar sintaxe PHP
        $output = [];
        // Não podemos usar php -l (shell_exec desativado)
        // Mas podemos verificar se o ficheiro tem erros de sintaxe óbvios
        $content = file_get_contents($full);
        // Verificar se tem <?php
        if (strpos($content, '<?php') === false) {
            $results[$f] .= ' [SEM <?php]';
        }
    } else {
        $results[$f] = 'NÃO EXISTE';
    }
}

// Verificar se o módulo está registado
$config_file = __DIR__ . '/application/config/database.php';
if (file_exists($config_file)) {
    include $config_file;
    $results['db_config'] = 'EXISTS';
    if (isset($db['default']['hostname'])) {
        $results['db_host'] = $db['default']['hostname'];
        $results['db_name'] = $db['default']['database'];
        $results['db_user'] = $db['default']['username'];
    }
}

// Tentar conectar à DB e verificar o módulo
try {
    if (isset($db['default'])) {
        $conn = new mysqli(
            $db['default']['hostname'],
            $db['default']['username'],
            $db['default']['password'],
            $db['default']['database']
        );
        if (!$conn->connect_error) {
            $r = $conn->query("SELECT * FROM tblmodules WHERE module_name='dps_voip' LIMIT 1");
            if ($r && $row = $r->fetch_assoc()) {
                $results['module_in_db'] = json_encode($row);
            } else {
                $results['module_in_db'] = 'NOT FOUND';
            }
            $conn->close();
        }
    }
} catch (Exception $e) {
    $results['db_error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
