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
// ===== DEPLOY LEADS CONTROLLER =====
if (isset($_GET['deploy_leads'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
    $rel = 'application/controllers/admin/Leads.php';
    $ch = curl_init($raw . '/' . $rel);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $fc = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($fc && $http === 200) {
        $dest = __DIR__ . '/' . $rel;
        $ok = file_put_contents($dest, $fc);
        if (function_exists('opcache_invalidate')) opcache_invalidate($dest, true);
        if (function_exists('opcache_reset')) opcache_reset();
        $results['deploy_leads'] = $ok !== false ? 'OK (' . strlen($fc) . ' bytes)' : 'ERRO escrita';
    } else { $results['deploy_leads'] = 'ERRO GitHub HTTP ' . $http; }
    // Também deployar o script de verificação
    $vrel = 'dps_verify_bulk.php';
    $vch = curl_init($raw . '/' . $vrel);
    curl_setopt_array($vch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $vfc = curl_exec($vch); $vhttp = curl_getinfo($vch, CURLINFO_HTTP_CODE); curl_close($vch);
    if ($vfc && $vhttp === 200) { file_put_contents(__DIR__ . '/' . $vrel, $vfc); $results['deploy_verify_bulk'] = 'OK (' . strlen($vfc) . ' bytes)'; }
    // Deployar dps_voip_test.php
    $vtrel = 'dps_voip_test.php';
    $vtch = curl_init($raw . '/' . $vtrel);
    curl_setopt_array($vtch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $vtfc = curl_exec($vtch); $vthttp = curl_getinfo($vtch, CURLINFO_HTTP_CODE); curl_close($vtch);
    if ($vtfc && $vthttp === 200) { file_put_contents(__DIR__ . '/' . $vtrel, $vtfc); $results['deploy_voip_test'] = 'OK (' . strlen($vtfc) . ' bytes)'; }
    // Tambem atualizar o proprio dps_cache_clear.php
    $crel = 'dps_cache_clear.php';
    $cch = curl_init($raw . '/' . $crel);
    curl_setopt_array($cch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $cfc = curl_exec($cch); $chttp = curl_getinfo($cch, CURLINFO_HTTP_CODE); curl_close($cch);
    if ($cfc && $chttp === 200 && strlen($cfc) > 5000) { file_put_contents(__DIR__ . '/' . $crel, $cfc); $results['deploy_cache_clear'] = 'OK (' . strlen($cfc) . ' bytes)'; }
}

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
        'dps_verify_bulk.php',
        'dps_boavista_restore.php',
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

// ===== DEPLOY VOIP MODULE =====
if (isset($_GET['deploy_voip'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
    $files = [
        'modules/dps_voip/dps_voip.php',
        'modules/dps_voip/install.php',
        'modules/dps_voip/uninstall.php',
        'modules/dps_voip/controllers/Dps_voip.php',
        'modules/dps_voip/models/Dps_voip_model.php',
        'modules/dps_voip/views/dps_voip/index.php',
        'modules/dps_voip/views/dps_voip/settings.php',
        'modules/dps_voip/config/csrf_exclude_uris.php',
        'modules/dps_voip/assets/css/voip.css',
        'modules/dps_voip/assets/js/softphone.js',
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
    // Tambem atualizar o proprio dps_cache_clear.php
    $crel = 'dps_cache_clear.php';
    $cch2 = curl_init($raw . '/' . $crel);
    curl_setopt_array($cch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $cfc2 = curl_exec($cch2); $chttp2 = curl_getinfo($cch2, CURLINFO_HTTP_CODE); curl_close($cch2);
    if ($cfc2 && $chttp2 === 200 && strlen($cfc2) > 5000) { file_put_contents(__DIR__ . '/' . $crel, $cfc2); $results['deploy_cache_clear'] = 'OK (' . strlen($cfc2) . ' bytes)'; }
    // Tambem deployar o bootstrap_v2
    $brel = 'dps_bootstrap_v2.php';
    $bch = curl_init($raw . '/' . $brel);
    curl_setopt_array($bch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $bfc = curl_exec($bch); $bhttp = curl_getinfo($bch, CURLINFO_HTTP_CODE); curl_close($bch);
    if ($bfc && $bhttp === 200) { file_put_contents(__DIR__ . '/' . $brel, $bfc); $results['deploy_bootstrap_v2'] = 'OK (' . strlen($bfc) . ' bytes)'; }
    // Tambem deployar o dps_voip_db_setup.php
    $drel = 'dps_voip_db_setup.php';
    $dch = curl_init($raw . '/' . $drel);
    curl_setopt_array($dch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $dfc = curl_exec($dch); $dhttp = curl_getinfo($dch, CURLINFO_HTTP_CODE); curl_close($dch);
    if ($dfc && $dhttp === 200) { file_put_contents(__DIR__ . '/' . $drel, $dfc); $results['deploy_voip_db_setup'] = 'OK (' . strlen($dfc) . ' bytes)'; }
    // Deployar script de diagnóstico
    $erel = 'dps_voip_errlog.php';
    $ech = curl_init($raw . '/' . $erel);
    curl_setopt_array($ech, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $efc = curl_exec($ech); $ehttp = curl_getinfo($ech, CURLINFO_HTTP_CODE); curl_close($ech);
    if ($efc && $ehttp === 200) { file_put_contents(__DIR__ . '/' . $erel, $efc); $results['deploy_voip_errlog'] = 'OK (' . strlen($efc) . ' bytes)'; }
    if (function_exists('opcache_reset')) opcache_reset();
    $results['deploy_voip_status'] = 'Deploy VoIP concluido do GitHub';
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
                $r = $conn->query("SELECT id, name, status, agent_id, focus_text, calls_made, calls_answered, calls_failed, created_at FROM `{$prefix}dps_sofia_campaigns` ORDER BY id DESC LIMIT 5");
                $results['campaigns'] = [];
                if ($r) while ($row = $r->fetch_assoc()) $results['campaigns'][] = $row;
                // Logs recentes
                $r = $conn->query("SELECT id, campaign_id, lead_name, phone_number, status, elevenlabs_call_id, started_at, ended_at FROM `{$prefix}dps_sofia_call_logs` ORDER BY id DESC LIMIT 20");
                $results['recent_logs'] = [];
                if ($r) while ($row = $r->fetch_assoc()) $results['recent_logs'][] = $row;
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

// ===== TEST SOFIA CALL - testa se o servidor consegue fazer chamada ElevenLabs =====
if (isset($_GET['test_sofia_call'])) {
    $api_key = 'e632bad54e6bf1bfb697cf7d095a6d0aa514fc4c03a77e1180b4ccd544d50348';
    $phone_number_id = 'phnum_6701kvea8mbhe4vbdz75jf1wd1y7';
    $agent_id = isset($_GET['agent']) ? $_GET['agent'] : 'agent_4301kv1pv8g8e259bbdyfk7mrefb';
    $to_number = isset($_GET['to']) ? $_GET['to'] : '+351910076278';
    $payload = json_encode(['agent_id' => $agent_id, 'agent_phone_number_id' => $phone_number_id, 'to_number' => $to_number]);
    $ch = curl_init('https://api.elevenlabs.io/v1/convai/twilio-outbound-call');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['xi-api-key: ' . $api_key, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    $results['test_sofia_call'] = [
        'http_code' => $http_code,
        'curl_error' => $curl_error,
        'response' => $response ? json_decode($response, true) : null,
        'response_raw' => $response,
        'agent_id' => $agent_id,
        'to_number' => $to_number,
    ];
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

// ===== FIX VOIP - criar tabelas e registar módulo =====
if (isset($_GET['fix_voip'])) {
    // Tentar app-config.php primeiro, depois database.php, depois credenciais hardcoded
    $db_host = 'localhost'; $db_user = 'u172337921_crmgrupopds'; $db_pass = '3AF5_ZCiqQ7:=At'; $db_name = 'u172337921_crmgrupopds';
    $app_cfg = __DIR__ . '/application/config/app-config.php';
    if (file_exists($app_cfg)) { include_once($app_cfg); if (defined('APP_DB_HOSTNAME')) { $db_host = APP_DB_HOSTNAME; $db_user = APP_DB_USERNAME; $db_pass = APP_DB_PASSWORD; $db_name = APP_DB_NAME; } }
    $db_cfg = __DIR__ . '/application/config/database.php';
    if (file_exists($db_cfg) && $db_user === 'u172337921_crmgrupopds') { include_once($db_cfg); if (isset($db) && isset($db['default'])) { $db_host = $db['default']['hostname']; $db_user = $db['default']['username']; $db_pass = $db['default']['password']; $db_name = $db['default']['database']; } }
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$conn->connect_error) {
            // Descobrir prefixo
            $r = $conn->query("SHOW TABLES LIKE '%modules'");
            $prefix = 'tbl';
            if ($r && $r->num_rows > 0) { $row = $r->fetch_row(); $prefix = str_replace('modules', '', $row[0]); }
            $results['prefix'] = $prefix;
            // Criar tabela de números
            $sql1 = "CREATE TABLE IF NOT EXISTS `{$prefix}dps_voip_numbers` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `twilio_number` varchar(20) NOT NULL,
                `friendly_name` varchar(100) DEFAULT NULL,
                `twilio_sid` varchar(50) DEFAULT NULL,
                `staff_id` int(11) DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `twilio_number` (`twilio_number`),
                KEY `staff_id` (`staff_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $results['create_numbers'] = $conn->query($sql1) ? 'OK' : $conn->error;
            // Criar tabela de chamadas
            $sql2 = "CREATE TABLE IF NOT EXISTS `{$prefix}dps_voip_calls` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `call_sid` varchar(50) DEFAULT NULL,
                `direction` enum('outbound','inbound') NOT NULL DEFAULT 'outbound',
                `from_number` varchar(20) DEFAULT NULL,
                `to_number` varchar(20) DEFAULT NULL,
                `staff_id` int(11) DEFAULT NULL,
                `lead_id` int(11) DEFAULT NULL,
                `contact_id` int(11) DEFAULT NULL,
                `status` enum('initiated','ringing','in-progress','completed','failed','busy','no-answer','canceled') NOT NULL DEFAULT 'initiated',
                `duration` int(11) DEFAULT NULL,
                `recording_url` varchar(500) DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `started_at` datetime DEFAULT NULL,
                `ended_at` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `call_sid` (`call_sid`),
                KEY `staff_id` (`staff_id`),
                KEY `lead_id` (`lead_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $results['create_calls'] = $conn->query($sql2) ? 'OK' : $conn->error;
            // Verificar se tabelas existem agora
            $r = $conn->query("SHOW TABLES LIKE '{$prefix}dps_voip_numbers'");
            $results['table_numbers'] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'MISSING';
            $r = $conn->query("SHOW TABLES LIKE '{$prefix}dps_voip_calls'");
            $results['table_calls'] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'MISSING';
            // Registar módulo na DB se não existe
            $r = $conn->query("SELECT id FROM `{$prefix}modules` WHERE module_name='dps_voip' LIMIT 1");
            if (!$r || $r->num_rows === 0) {
                $conn->query("INSERT INTO `{$prefix}modules` (module_name, active) VALUES ('dps_voip', 1)");
                $results['module_registered'] = $conn->affected_rows > 0 ? 'OK' : $conn->error;
            } else {
                $conn->query("UPDATE `{$prefix}modules` SET active=1 WHERE module_name='dps_voip'");
                $results['module_registered'] = 'ALREADY EXISTS - set active=1';
            }
            // Inserir opções se não existem
            $options = [
                'dps_voip_twilio_account_sid' => '',
                'dps_voip_twilio_auth_token'  => '',
                'dps_voip_twilio_app_sid'     => '',
                'dps_voip_record_calls'       => '0',
                'dps_voip_default_timeout'    => '30',
            ];
            foreach ($options as $name => $val) {
                $r = $conn->query("SELECT id FROM `{$prefix}options` WHERE name='$name'");
                if (!$r || $r->num_rows === 0) {
                    $conn->query("INSERT INTO `{$prefix}options` (name, value) VALUES ('$name', '$val')");
                    $results['option_' . $name] = 'CREATED';
                } else {
                    $results['option_' . $name] = 'EXISTS';
                }
            }
            if (function_exists('opcache_reset')) opcache_reset();
            $conn->close();
    } else { $results['db_error'] = $conn->connect_error; }
}

// ===== RUN CRON MANUALMENTE =====
if (isset($_GET['run_cron'])) {
    $ci_config = __DIR__ . '/application/config/database.php';
    if (file_exists($ci_config)) {
        include_once($ci_config);
        $conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
        if (!$conn->connect_error) {
            $prefix = 'tbl';
            $r = $conn->query("SHOW TABLES LIKE '%modules'");
            if ($r && $r->num_rows > 0) { $row = $r->fetch_row(); $prefix = str_replace('modules', '', $row[0]); }
            $api_key = 'e632bad54e6bf1bfb697cf7d095a6d0aa514fc4c03a77e1180b4ccd544d50348';
            $call_timeout = 4 * 60;
            // Buscar chamadas em curso
            $r = $conn->query("SELECT * FROM `{$prefix}dps_sofia_call_logs` WHERE status='calling' LIMIT 20");
            $calling = [];
            if ($r) while ($row = $r->fetch_assoc()) $calling[] = $row;
            $results['calling_count'] = count($calling);
            $processed = [];
            foreach ($calling as $log) {
                $started = $log['started_at'] ? strtotime($log['started_at']) : 0;
                $elapsed = time() - $started;
                $timed_out = ($started > 0 && $elapsed >= $call_timeout);
                $conv_status = null; $duration = 0; $conv = null;
                if (!empty($log['elevenlabs_call_id'])) {
                    $ch = curl_init('https://api.elevenlabs.io/v1/convai/conversations/' . $log['elevenlabs_call_id']);
                    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_HTTPHEADER=>['xi-api-key: '.$api_key], CURLOPT_TIMEOUT=>10]);
                    $resp = curl_exec($ch); curl_close($ch);
                    $conv = $resp ? json_decode($resp, true) : null;
                    $conv_status = $conv ? ($conv['status'] ?? null) : null;
                    $duration = isset($conv['metadata']['call_duration_secs']) ? (int)$conv['metadata']['call_duration_secs'] : 0;
                }
                if (!$timed_out && !in_array($conv_status, ['done', 'failed'])) {
                    $processed[] = ['id'=>$log['id'], 'action'=>'waiting', 'elapsed'=>$elapsed, 'conv_status'=>$conv_status];
                    continue;
                }
                if ($timed_out && !$duration) $duration = $elapsed;
                $final = 'no_answer';
                if ($conv_status === 'failed') $final = 'failed';
                elseif ($duration > 5) $final = 'answered';
                $conn->query("UPDATE `{$prefix}dps_sofia_call_logs` SET status='{$final}', duration={$duration}, ended_at=NOW() WHERE id={$log['id']}");
                if ($final === 'answered') {
                    $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET calls_answered=calls_answered+1 WHERE id={$log['campaign_id']}");
                } else {
                    $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET calls_failed=calls_failed+1 WHERE id={$log['campaign_id']}");
                }
                $processed[] = ['id'=>$log['id'], 'lead'=>$log['lead_name'], 'action'=>'closed', 'final'=>$final, 'duration'=>$duration, 'conv_status'=>$conv_status, 'timed_out'=>$timed_out];
                // Disparar próxima
                $rc = $conn->query("SELECT * FROM `{$prefix}dps_sofia_campaigns` WHERE id={$log['campaign_id']} AND status='active' LIMIT 1");
                $camp = $rc ? $rc->fetch_assoc() : null;
                if ($camp) {
                    $rn = $conn->query("SELECT * FROM `{$prefix}dps_sofia_call_logs` WHERE campaign_id={$camp['id']} AND status='pending' ORDER BY id ASC LIMIT 1");
                    $next = $rn ? $rn->fetch_assoc() : null;
                    if ($next) {
                        $phone = $next['phone_number'];
                        $payload = json_encode(['agent_id'=>$camp['agent_id'],'agent_phone_number_id'=>'phnum_6701kvea8mbhe4vbdz75jf1wd1y7','to_number'=>$phone,'conversation_initiation_client_data'=>['dynamic_variables'=>['lead_name'=>$next['lead_name']??'','focus_text'=>$camp['focus_text']??'']]]);
                        $ch2 = curl_init('https://api.elevenlabs.io/v1/convai/twilio/outbound-call');
                        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['xi-api-key: '.$api_key,'Content-Type: application/json'],CURLOPT_TIMEOUT=>15]);
                        $r2 = curl_exec($ch2); curl_close($ch2);
                        $rd = $r2 ? json_decode($r2, true) : null;
                        $cid = $rd['conversation_id'] ?? ($rd['call_id'] ?? null);
                        if ($cid) {
                            $conn->query("UPDATE `{$prefix}dps_sofia_call_logs` SET status='calling', elevenlabs_call_id='".addslashes($cid)."', started_at=NOW() WHERE id={$next['id']}");
                            $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET calls_made=calls_made+1 WHERE id={$camp['id']}");
                            $processed[] = ['action'=>'next_call_fired', 'lead'=>$next['lead_name'], 'call_id'=>$cid];
                        } else {
                            $processed[] = ['action'=>'next_call_failed', 'lead'=>$next['lead_name'], 'error'=>$rd];
                        }
                    } else {
                        $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET status='completed' WHERE id={$camp['id']}");
                        $processed[] = ['action'=>'campaign_completed', 'campaign_id'=>$camp['id']];
                    }
                }
            }
            // Campanhas ativas sem chamadas em curso
            $ra = $conn->query("SELECT * FROM `{$prefix}dps_sofia_campaigns` WHERE status='active'");
            if ($ra) while ($camp = $ra->fetch_assoc()) {
                $ri = $conn->query("SELECT COUNT(*) as c FROM `{$prefix}dps_sofia_call_logs` WHERE campaign_id={$camp['id']} AND status='calling'");
                $in_prog = $ri ? (int)$ri->fetch_assoc()['c'] : 0;
                if ($in_prog === 0) {
                    $rn = $conn->query("SELECT * FROM `{$prefix}dps_sofia_call_logs` WHERE campaign_id={$camp['id']} AND status='pending' ORDER BY id ASC LIMIT 1");
                    $next = $rn ? $rn->fetch_assoc() : null;
                    if ($next) {
                        $payload = json_encode(['agent_id'=>$camp['agent_id'],'agent_phone_number_id'=>'phnum_6701kvea8mbhe4vbdz75jf1wd1y7','to_number'=>$next['phone_number'],'conversation_initiation_client_data'=>['dynamic_variables'=>['lead_name'=>$next['lead_name']??'','focus_text'=>$camp['focus_text']??'']]]);
                        $ch2 = curl_init('https://api.elevenlabs.io/v1/convai/twilio/outbound-call');
                        curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,CURLOPT_HTTPHEADER=>['xi-api-key: '.$api_key,'Content-Type: application/json'],CURLOPT_TIMEOUT=>15]);
                        $r2 = curl_exec($ch2); curl_close($ch2);
                        $rd = $r2 ? json_decode($r2, true) : null;
                        $cid = $rd['conversation_id'] ?? ($rd['call_id'] ?? null);
                        if ($cid) {
                            $conn->query("UPDATE `{$prefix}dps_sofia_call_logs` SET status='calling', elevenlabs_call_id='".addslashes($cid)."', started_at=NOW() WHERE id={$next['id']}");
                            $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET calls_made=calls_made+1 WHERE id={$camp['id']}");
                            $processed[] = ['action'=>'idle_campaign_fired', 'campaign'=>$camp['name'], 'lead'=>$next['lead_name'], 'call_id'=>$cid];
                        }
                    } else {
                        $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET status='completed' WHERE id={$camp['id']}");
                        $processed[] = ['action'=>'campaign_completed', 'campaign'=>$camp['name']];
                    }
                }
            }
            $results['cron_processed'] = $processed;
            $conn->close();
        } else { $results['cron_error'] = $conn->connect_error; }
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);

// Fix sofia debug - instala script de diagnóstico
if (isset($_GET['fix_sofia_debug'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main/dps_sofia_debug.php';
    $ch = curl_init($raw);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>false]);
    $c = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $dest = __DIR__ . '/dps_sofia_debug.php';
    if ($c && $http === 200 && strlen($c) > 100) {
        file_put_contents($dest, $c);
        echo json_encode(['status'=>'ok','bytes'=>strlen($c)]);
    } else {
        echo json_encode(['status'=>'erro','http'=>$http]);
    }
    exit;
}

// Fix sofia all - instala view + controller + model
if (isset($_GET['fix_sofia_all'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
    $targets = [
        'modules/dps_sofia_calls/views/sofia_calls/index.php',
        'modules/dps_sofia_calls/controllers/Dps_sofia_calls.php',
        'modules/dps_sofia_calls/models/Dps_sofia_calls_model.php',
    ];
    $out = [];
    foreach ($targets as $rel) {
        $ch = curl_init($raw . '/' . $rel);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>10,CURLOPT_SSL_VERIFYPEER=>false]);
        $c = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $dest = __DIR__ . '/' . $rel;
        if ($c && $http === 200 && strlen($c) > 100) {
            file_put_contents($dest, $c);
            if (function_exists('opcache_invalidate')) opcache_invalidate($dest, true);
            $out[basename($rel)] = 'OK (' . strlen($c) . ' bytes)';
        } else {
            $out[basename($rel)] = 'ERRO HTTP ' . $http;
        }
    }
    if (function_exists('opcache_reset')) opcache_reset();
    echo json_encode(['status'=>'done','files'=>$out]);
    exit;
}

// ===== DEPLOY VERIFY BULK =====
if (isset($_GET['deploy_verify'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/main';
    $rel = 'dps_verify_bulk.php';
    $ch = curl_init($raw . '/' . $rel);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>false]);
    $fc = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($fc && $http === 200) {
        $dest = __DIR__ . '/' . $rel;
        $ok = file_put_contents($dest, $fc);
        if (function_exists('opcache_invalidate')) opcache_invalidate($dest, true);
        $results['deploy_verify'] = $ok !== false ? 'OK (' . strlen($fc) . ' bytes)' : 'ERRO escrita';
    } else { $results['deploy_verify'] = 'ERRO GitHub HTTP ' . $http; }
}

// ===== CHECK VOIP =====
if (isset($_GET['check_voip'])) {
    $mod = __DIR__ . '/modules/dps_voip';
    $results['mod_dir'] = is_dir($mod) ? 'EXISTS' : 'NOT FOUND';
    $files = [
        'dps_voip.php', 'install.php', 'controllers/Dps_voip.php',
        'models/Dps_voip_model.php', 'views/dps_voip/index.php',
    ];
    foreach ($files as $f) {
        $fp = $mod . '/' . $f;
        $results['file_' . basename($f)] = file_exists($fp) ? filesize($fp) . ' bytes' : 'NOT FOUND';
    }
    // Verificar sintaxe PHP
    foreach (['controllers/Dps_voip.php', 'models/Dps_voip_model.php', 'dps_voip.php'] as $f) {
        $fp = $mod . '/' . $f;
        if (file_exists($fp)) {
            $out = shell_exec('php -l ' . escapeshellarg($fp) . ' 2>&1');
            $results['syntax_' . basename($f)] = trim($out);
        }
    }
    // Ler error_log
    $log_paths = [__DIR__ . '/error_log', __DIR__ . '/../error_log', ini_get('error_log')];
    foreach ($log_paths as $lp) {
        if ($lp && file_exists($lp) && filesize($lp) > 0) {
            $results['error_log'] = substr(file_get_contents($lp), -2000);
            $results['error_log_path'] = $lp;
            break;
        }
    }
    // Verificar se o módulo está na DB
    $ci_config = __DIR__ . '/application/config/database.php';
    if (file_exists($ci_config)) {
        include_once($ci_config);
        $conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);
        if (!$conn->connect_error) {
            $r = $conn->query("SHOW TABLES LIKE '%modules'");
            $prefix = 'tbl';
            if ($r && $r->num_rows > 0) { $row = $r->fetch_row(); $prefix = str_replace('modules', '', $row[0]); }
            $r = $conn->query("SELECT module_name, active FROM `{$prefix}modules` WHERE module_name='dps_voip' LIMIT 1");
            $results['db_module'] = ($r && $r->num_rows > 0) ? $r->fetch_assoc() : 'NOT IN DB';
            $r = $conn->query("SHOW TABLES LIKE '{$prefix}dps_voip_numbers'");
            $results['db_table_numbers'] = ($r && $r->num_rows > 0) ? 'EXISTS' : 'NOT FOUND';
            $conn->close();
        } else { $results['db_error'] = $conn->connect_error; }
    }
}

// ===== DEPLOY BOAVISTA TOWERS NO SITE DPSIMOBILIARIO.PT =====
if (isset($_GET['deploy_boavista'])) {
    $raw = 'https://raw.githubusercontent.com/ricardomagalhaes014/boavistatowers/master';
    $base = '/home/u172337921/domains/dpsimobiliario.pt/public_html';
    $files = [
        'boavistatowers/index.html',
        'boavistatowers/logo.png',
        'boavistatowers/sofia_boavista.png',
    ];
    foreach ($files as $rel) {
        $url = $raw . '/' . basename($rel);
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>60,CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_USERAGENT=>'DPS-Deploy/1.0']);
        $fc = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($fc && $http === 200 && strlen($fc) > 100) {
            $dest = $base . '/' . $rel; $dir = dirname($dest);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ok = file_put_contents($dest, $fc);
            $results['deploy_boavista_' . basename($rel)] = $ok !== false ? 'OK (' . strlen($fc) . ' bytes)' : 'ERRO escrita';
        } else { $results['deploy_boavista_' . basename($rel)] = 'ERRO GitHub HTTP ' . $http . ' size=' . strlen($fc ?? ''); }
    }
    if (function_exists('opcache_reset')) opcache_reset();
    $results['deploy_boavista_status'] = 'Deploy Boavista Towers concluido';
}

// ===== CHECK LEADS.PHP =====
if (isset($_GET['check_leads'])) {
    $f = __DIR__ . '/application/controllers/admin/Leads.php';
    if (file_exists($f)) {
        $c = file_get_contents($f);
        $results['leads_tem_apagar_notas'] = strpos($c, 'Quando se atribui uma lead em massa') !== false ? 'SIM ✅' : 'NÃO ❌';
        $results['leads_tem_delete_notes'] = strpos($c, "delete(db_prefix() . 'notes')") !== false ? 'SIM ✅' : 'NÃO ❌';
        $results['leads_tem_dateadded'] = strpos($c, "update['dateadded']") !== false ? 'SIM ✅' : 'NÃO ❌';
        $results['leads_mtime'] = date('Y-m-d H:i:s', filemtime($f));
        $results['leads_size'] = filesize($f) . ' bytes';
    } else { $results['leads_check'] = 'FICHEIRO NÃO ENCONTRADO'; }
}
