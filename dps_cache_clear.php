<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$results = [];

// Invalidar o imoveis-api.php especificamente
$file = __DIR__ . '/imoveis-api.php';
if (function_exists('opcache_invalidate')) {
    $results['opcache_invalidate_imoveis'] = opcache_invalidate($file, true) ? 'OK' : 'FAILED';
}
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset() ? 'OK' : 'FAILED';
}

// Verificar o conteúdo actual do imoveis-api.php (primeiras 500 chars)
$results['imoveis_api_preview'] = substr(file_get_contents($file), 0, 500);

// Verificar se a linha do LEFT JOIN ainda existe
$content = file_get_contents($file);
$results['has_left_join_customfields'] = strpos($content, 'customfieldsvalues') !== false ? 'SIM (versão antiga!)' : 'NÃO (versão nova OK)';
$results['has_landing_whatsapp'] = strpos($content, 'landing_whatsapp') !== false ? 'SIM (versão nova OK)' : 'NÃO';

$results['php_version'] = PHP_VERSION;
$results['file_mtime'] = date('Y-m-d H:i:s', filemtime($file));

// ===== STOP SOFIA CAMPAIGNS =====
if (isset($_GET['stop_sofia'])) {
    $ci_config = __DIR__ . '/application/config/database.php';
    if (file_exists($ci_config)) {
        include_once($ci_config);
        $conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
        if (!$conn->connect_error) {
            $conn->query("UPDATE dps_sofia_campaigns SET status='stopped' WHERE status='active'");
            $results['sofia_stop'] = 'Todas as campanhas ativas paradas. Rows: ' . $conn->affected_rows;
            $conn->close();
        } else {
            $results['sofia_stop_error'] = $conn->connect_error;
        }
    }
}

// ===== DEPLOY SOFIA FROM GITHUB (busca ficheiros do GitHub e aplica) =====
if (isset($_GET['fix_sofia']) || isset($_GET['deploy_sofia'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
    $files = [
        'modules/dps_sofia_calls/controllers/Dps_sofia_calls.php',
        'modules/dps_sofia_calls/models/Dps_sofia_calls_model.php',
        'modules/dps_sofia_calls/views/sofia_calls/index.php',
        'modules/dps_sofia_calls/config/csrf_exclude_uris.php',
    ];
    foreach ($files as $rel) {
        $url = $raw . '/' . $rel;
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
        $fc = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($fc && $http === 200) {
            $dest = __DIR__ . '/' . $rel;
            $dir = dirname($dest);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ok = file_put_contents($dest, $fc);
            $results['deploy_' . basename($rel)] = $ok !== false ? 'OK (' . strlen($fc) . ' bytes)' : 'ERRO escrita';
        } else {
            $results['deploy_' . basename($rel)] = 'ERRO GitHub HTTP ' . $http;
        }
    }
    if (function_exists('opcache_reset')) opcache_reset();
    $results['deploy_status'] = 'Sofia Calls atualizado do GitHub';
}

echo json_encode($results, JSON_PRETTY_PRINT);

// ===== DIAGNÓSTICO SOFIA =====
// Não incluído por defeito - adicionar ?diag_sofia para ativar
