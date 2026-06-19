<?php
/**
 * Script de diagnóstico do módulo dps_sofia_calls
 * Aceder via: https://crm.grupo-dps.com/dps_sofia_debug.php
 * APAGAR após diagnóstico!
 */

$api_key = 'e632bad54e6bf1bfb697cf7d095a6d0aa514fc4c03a77e1180b4ccd544d50348';

header('Content-Type: application/json; charset=utf-8');
$out = [];

// ---- 1. Verificar chamadas em estado "calling" na BD ----
$dsn = null;
$db  = null;

// Tentar ler config do Perfex
$config_file = __DIR__ . '/application/config/app.php';
if (file_exists($config_file)) {
    $content = file_get_contents($config_file);
    preg_match("/\\\$db\['default'\]\['hostname'\]\s*=\s*'([^']+)'/", $content, $m_host);
    preg_match("/\\\$db\['default'\]\['username'\]\s*=\s*'([^']+)'/", $content, $m_user);
    preg_match("/\\\$db\['default'\]\['password'\]\s*=\s*'([^']+)'/", $content, $m_pass);
    preg_match("/\\\$db\['default'\]\['database'\]\s*=\s*'([^']+)'/", $content, $m_db);
    
    if ($m_host && $m_user && $m_pass && $m_db) {
        try {
            $db = new PDO(
                "mysql:host={$m_host[1]};dbname={$m_db[1]};charset=utf8mb4",
                $m_user[1],
                $m_pass[1]
            );
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $out['db_connect'] = 'OK';
        } catch (Exception $e) {
            $out['db_connect'] = 'ERRO: ' . $e->getMessage();
        }
    } else {
        $out['db_connect'] = 'ERRO: Não consegui ler credenciais do app.php';
    }
} else {
    $out['db_connect'] = 'ERRO: app.php não encontrado em ' . $config_file;
}

if ($db) {
    // Chamadas em "calling"
    $stmt = $db->query("SELECT id, campaign_id, lead_name, phone_number, elevenlabs_call_id, started_at, status,
        TIMESTAMPDIFF(SECOND, started_at, NOW()) as elapsed_secs
        FROM tbldps_sofia_call_logs WHERE status='calling' ORDER BY id ASC LIMIT 20");
    $calling = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out['calling_count'] = count($calling);
    $out['calling_calls'] = $calling;

    // Campanhas ativas
    $stmt2 = $db->query("SELECT id, name, status, calls_made, calls_answered, calls_failed FROM tbldps_sofia_campaigns WHERE status='active'");
    $out['active_campaigns'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Verificar se o hook está registado
    $stmt3 = $db->query("SELECT * FROM tblhooks WHERE hook_name='perfex_cron' LIMIT 5");
    $out['cron_hooks'] = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    // Última execução do cron (via tblscheduled_emails ou similar)
    try {
        $stmt4 = $db->query("SELECT * FROM tblcron_log ORDER BY id DESC LIMIT 5");
        $out['cron_log'] = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $out['cron_log'] = 'Tabela não existe: ' . $e->getMessage();
    }
}

// ---- 2. Testar chamada à ElevenLabs para uma das conversas presas ----
if (!empty($calling)) {
    $test_conv = $calling[0]['elevenlabs_call_id'];
    if ($test_conv) {
        $ch = curl_init("https://api.elevenlabs.io/v1/convai/conversations/{$test_conv}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["xi-api-key: {$api_key}"],
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        
        $out['elevenlabs_test'] = [
            'conv_id'  => $test_conv,
            'response' => $resp ? json_decode($resp, true) : null,
            'curl_error' => $err ?: null,
        ];
    }
}

// ---- 3. Testar se o cron do Perfex chama o hook ----
// Simular manualmente o process_pending_calls
if ($db && isset($_GET['run_process'])) {
    $out['manual_run'] = 'A executar process_pending_calls...';
    
    // Buscar chamadas em calling com mais de 4 min
    $stmt = $db->query("SELECT * FROM tbldps_sofia_call_logs 
        WHERE status='calling' AND TIMESTAMPDIFF(SECOND, started_at, NOW()) > 240
        ORDER BY id ASC LIMIT 20");
    $timed_out_calls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out['timed_out_calls'] = count($timed_out_calls);
    
    foreach ($timed_out_calls as $log) {
        // Consultar ElevenLabs
        $conv_status = null;
        $duration = 0;
        
        if ($log['elevenlabs_call_id']) {
            $ch = curl_init("https://api.elevenlabs.io/v1/convai/conversations/{$log['elevenlabs_call_id']}");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ["xi-api-key: {$api_key}"],
                CURLOPT_TIMEOUT => 10,
            ]);
            $resp = curl_exec($ch);
            curl_close($ch);
            $conv = $resp ? json_decode($resp, true) : null;
            $conv_status = $conv ? ($conv['status'] ?? null) : null;
            $duration = $conv ? ($conv['metadata']['call_duration_secs'] ?? 0) : 0;
        }
        
        $final_status = 'no_answer';
        if ($conv_status === 'failed') $final_status = 'failed';
        elseif (!empty($conv['analysis']['call_successful']) && $conv['analysis']['call_successful'] === 'success') $final_status = 'answered';
        elseif ($duration > 15 && $conv_status === 'done') $final_status = 'answered';
        
        // Actualizar
        $stmt_upd = $db->prepare("UPDATE tbldps_sofia_call_logs SET status=?, ended_at=NOW() WHERE id=?");
        $stmt_upd->execute([$final_status, $log['id']]);
        
        // Actualizar contadores campanha
        if ($final_status === 'answered') {
            $db->exec("UPDATE tbldps_sofia_campaigns SET calls_answered=calls_answered+1 WHERE id={$log['campaign_id']}");
        } else {
            $db->exec("UPDATE tbldps_sofia_campaigns SET calls_failed=calls_failed+1 WHERE id={$log['campaign_id']}");
        }
        
        $out['processed'][] = [
            'log_id'      => $log['id'],
            'lead'        => $log['lead_name'],
            'conv_id'     => $log['elevenlabs_call_id'],
            'conv_status' => $conv_status,
            'duration'    => $duration,
            'final'       => $final_status,
        ];
    }
    
    // Agora disparar próximas chamadas para campanhas ativas sem chamadas em curso
    $stmt_active = $db->query("SELECT * FROM tbldps_sofia_campaigns WHERE status='active'");
    $active = $stmt_active->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($active as $campaign) {
        $stmt_in_prog = $db->prepare("SELECT COUNT(*) FROM tbldps_sofia_call_logs WHERE campaign_id=? AND status='calling'");
        $stmt_in_prog->execute([$campaign['id']]);
        $in_progress = $stmt_in_prog->fetchColumn();
        
        if ($in_progress == 0) {
            // Buscar próximo pendente
            $stmt_next = $db->prepare("SELECT * FROM tbldps_sofia_call_logs WHERE campaign_id=? AND status='pending' ORDER BY id ASC LIMIT 1");
            $stmt_next->execute([$campaign['id']]);
            $next = $stmt_next->fetch(PDO::FETCH_ASSOC);
            
            if ($next) {
                // Disparar chamada
                $phone_number_id = 'phnum_6701kvea8mbhe4vbdz75jf1wd1y7';
                $payload = json_encode([
                    'agent_id'              => $campaign['agent_id'] ?? 'agent_9901kv1pvewveh9s9ebs1rys274k',
                    'agent_phone_number_id' => $phone_number_id,
                    'to_number'             => $next['phone_number'],
                    'conversation_initiation_client_data' => [
                        'dynamic_variables' => [
                            'lead_name'  => $next['lead_name'] ?? '',
                            'focus_text' => $campaign['focus_text'] ?? '',
                        ],
                    ],
                ]);
                
                $ch = curl_init('https://api.elevenlabs.io/v1/convai/twilio/outbound-call');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => [
                        "xi-api-key: {$api_key}",
                        'Content-Type: application/json',
                    ],
                    CURLOPT_TIMEOUT => 15,
                ]);
                $resp = curl_exec($ch);
                $err  = curl_error($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $result = $resp ? json_decode($resp, true) : null;
                $call_id = $result['conversation_id'] ?? ($result['call_id'] ?? null);
                
                $out['next_call'][] = [
                    'campaign'    => $campaign['name'],
                    'lead'        => $next['lead_name'],
                    'phone'       => $next['phone_number'],
                    'http_code'   => $http_code,
                    'curl_error'  => $err ?: null,
                    'response'    => $result,
                    'call_id'     => $call_id,
                ];
                
                if ($call_id) {
                    $stmt_upd = $db->prepare("UPDATE tbldps_sofia_call_logs SET status='calling', elevenlabs_call_id=?, started_at=NOW() WHERE id=?");
                    $stmt_upd->execute([$call_id, $next['id']]);
                    $db->exec("UPDATE tbldps_sofia_campaigns SET calls_made=calls_made+1 WHERE id={$campaign['id']}");
                } else {
                    $stmt_upd = $db->prepare("UPDATE tbldps_sofia_call_logs SET status='failed', ended_at=NOW() WHERE id=?");
                    $stmt_upd->execute([$next['id']]);
                    $db->exec("UPDATE tbldps_sofia_campaigns SET calls_failed=calls_failed+1 WHERE id={$campaign['id']}");
                }
            } else {
                $db->exec("UPDATE tbldps_sofia_campaigns SET status='completed' WHERE id={$campaign['id']}");
                $out['completed_campaigns'][] = $campaign['name'];
            }
        }
    }
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
