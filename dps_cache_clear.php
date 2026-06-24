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
