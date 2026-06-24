<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$results = [];

// Verificar ficheiros
$base = __DIR__;
$files = [
    'modules/dps_voip/dps_voip.php',
    'modules/dps_voip/controllers/Dps_voip.php',
    'modules/dps_voip/models/Dps_voip_model.php',
    'modules/dps_voip/views/dps_voip/index.php',
    'modules/dps_voip/views/dps_voip/settings.php',
    'modules/dps_voip/config/csrf_exclude_uris.php',
];

foreach ($files as $f) {
    $full = $base . '/' . $f;
    if (file_exists($full)) {
        $content = file_get_contents($full);
        $results[$f] = filesize($full) . ' bytes';
        // Verificar se tem erros de sintaxe óbvios
        // Verificar balanceamento de chaves
        $open = substr_count($content, '{');
        $close = substr_count($content, '}');
        if ($open !== $close) {
            $results[$f] .= " [CHAVES: {$open} open, {$close} close]";
        }
    } else {
        $results[$f] = 'NÃO EXISTE';
    }
}

// Verificar o controller em detalhe
$ctrl = $base . '/modules/dps_voip/controllers/Dps_voip.php';
if (file_exists($ctrl)) {
    $content = file_get_contents($ctrl);
    // Verificar se extends AdminController
    if (strpos($content, 'extends AdminController') !== false) {
        $results['ctrl_extends'] = 'AdminController OK';
    } else {
        $results['ctrl_extends'] = 'NÃO extends AdminController';
    }
    // Verificar se tem function index
    if (strpos($content, 'function index') !== false) {
        $results['ctrl_index'] = 'function index OK';
    }
    // Verificar se usa $this->load->model
    if (strpos($content, 'load->model') !== false) {
        $results['ctrl_model_load'] = 'load->model OK';
    }
    // Verificar se usa get_instance
    if (strpos($content, 'get_instance') !== false) {
        $results['ctrl_get_instance'] = 'get_instance FOUND (pode causar erro)';
    }
}

// Verificar o model
$model = $base . '/modules/dps_voip/models/Dps_voip_model.php';
if (file_exists($model)) {
    $content = file_get_contents($model);
    if (strpos($content, 'extends App_Model') !== false) {
        $results['model_extends'] = 'App_Model OK';
    } else {
        $results['model_extends'] = 'NÃO extends App_Model';
    }
}

// Verificar o dps_voip.php (ficheiro principal do módulo)
$main = $base . '/modules/dps_voip/dps_voip.php';
if (file_exists($main)) {
    $content = file_get_contents($main);
    // Verificar se tem register_module
    if (strpos($content, 'register_module') !== false) {
        $results['main_register'] = 'register_module OK';
    }
    // Verificar se tem hooks
    if (strpos($content, 'hooks') !== false) {
        $results['main_hooks'] = 'hooks OK';
    }
    // Mostrar as primeiras 500 chars
    $results['main_preview'] = substr($content, 0, 200);
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
