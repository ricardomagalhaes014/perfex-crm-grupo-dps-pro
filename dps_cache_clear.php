<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
$results = [];
$file = __DIR__ . '/imoveis-api.php';
if (function_exists('opcache_invalidate')) $results['opcache_invalidate_imoveis'] = opcache_invalidate($file, true) ? 'OK' : 'FAILED';
if (function_exists('opcache_reset')) $results['opcache_reset'] = opcache_reset() ? 'OK' : 'FAILED';
$results['imoveis_api_preview'] = substr(file_get_contents($file), 0, 500);
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
            $conn->query("UPDATE tblDps_sofia_campaigns SET status='stopped' WHERE status='active'");
            $results['sofia_stop'] = 'Rows: ' . $conn->affected_rows;
            $conn->close();
        } else { $results['sofia_stop_error'] = $conn->connect_error; }
    }
}

// ===== DEPLOY FROM GITHUB =====
if (isset($_GET['fix_sofia']) || isset($_GET['deploy_sofia'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
    $files = [
        'modules/dps_sofia_calls/controllers/Dps_sofia_calls.php',
        'modules/dps_sofia_calls/models/Dps_sofia_calls_model.php',
        'modules/dps_sofia_calls/views/sofia_calls/index.php',
        'modules/dps_sofia_calls/config/csrf_exclude_uris.php',
        'dps_diag.php',
        'dps_fix_csrf.php',
        'dps_sofia_check.php',
        'dps_cache_clear.php',
    ];
    foreach ($files as $rel) {
        $ch = curl_init($raw . '/' . $rel);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
        $fc = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($fc && $http === 200) {
            $dest = __DIR__ . '/' . $rel; $dir = dirname($dest);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ok = file_put_contents($dest, $fc);
            $results['deploy_' . basename($rel)] = $ok !== false ? 'OK (' . strlen($fc) . ' bytes)' : 'ERRO escrita';
        } else { $results['deploy_' . basename($rel)] = 'ERRO GitHub HTTP ' . $http; }
    }
    if (function_exists('opcache_reset')) opcache_reset();
    $results['deploy_status'] = 'Deploy concluido do GitHub';
}

// ===== CHECK SOFIA =====
if (isset($_GET['check_sofia'])) {
    $ci_config = __DIR__ . '/application/config/database.php';
    if (file_exists($ci_config)) {
        include_once($ci_config);
        $conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
        if (!$conn->connect_error) {
            // Descobrir prefixo
            $r = $conn->query("SHOW TABLES LIKE '%modules'");
            $prefix = 'tbl';
            if ($r && $r->num_rows > 0) { $row = $r->fetch_row(); $prefix = str_replace('modules', '', $row[0]); }
            $results['prefix'] = $prefix;
            // Verificar tabelas
            foreach (['dps_sofia_campaigns', 'dps_sofia_call_logs'] as $t) {
                $r = $conn->query("SHOW TABLES LIKE '{$prefix}{$t}'");
                $results["table_{$t}"] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'NOT FOUND';
            }
            // Verificar módulo
            $r = $conn->query("SELECT module_name, active FROM `{$prefix}modules` WHERE module_name='dps_sofia_calls' LIMIT 1");
            $results['module'] = ($r && $r->num_rows > 0) ? $r->fetch_assoc() : 'NOT INSTALLED';
            // Contar campanhas
            if ($results['table_dps_sofia_campaigns'] === 'EXISTS') {
                $r = $conn->query("SELECT COUNT(*) as c FROM `{$prefix}dps_sofia_campaigns`");
                $results['campaigns_count'] = $r ? $r->fetch_assoc()['c'] : 0;
                $r = $conn->query("SELECT id, name, status, created_at FROM `{$prefix}dps_sofia_campaigns` ORDER BY id DESC LIMIT 5");
                $results['campaigns'] = [];
                if ($r) while ($row = $r->fetch_assoc()) $results['campaigns'][] = $row;
            }
            $conn->close();
        } else { $results['db_error'] = $conn->connect_error; }
    }
    // Verificar ficheiros
    $mod = __DIR__ . '/modules/dps_sofia_calls';
    $results['view_size'] = file_exists($mod.'/views/sofia_calls/index.php') ? filesize($mod.'/views/sofia_calls/index.php') : 0;
    $results['view_has_csrf'] = file_exists($mod.'/views/sofia_calls/index.php') ? (strpos(file_get_contents($mod.'/views/sofia_calls/index.php'), 'get_csrf_for_ajax') !== false) : false;
    $results['csrf_exclude_exists'] = file_exists($mod.'/config/csrf_exclude_uris.php');
}

// Fix view only - instala apenas a view corrigida
if (isset($_GET['fix_view'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main/modules/dps_sofia_calls/views/sofia_calls/index.php';
    $ch = curl_init($raw);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>false]);
    $c = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $dest = __DIR__ . '/modules/dps_sofia_calls/views/sofia_calls/index.php';
    if ($c && $http === 200 && strlen($c) > 1000) {
        file_put_contents($dest, $c);
        if (function_exists('opcache_invalidate')) opcache_invalidate($dest, true);
        if (function_exists('opcache_reset')) opcache_reset();
        echo json_encode(['status'=>'OK','bytes'=>strlen($c),'init_tail_line'=>substr_count(substr($c,0,strpos($c,'init_tail')),"\n")+1]);
    } else {
        echo json_encode(['status'=>'ERRO','http'=>$http,'bytes'=>strlen($c)]);
    }
    exit;
}

echo json_encode($results, JSON_PRETTY_PRINT);
