<?php
/**
 * DPS Sofia Fix - Script de diagnóstico e correção direta
 * Instalar em: /home/u172337921/domains/crm.grupo-dps.com/public_html/dps_sofia_fix.php
 * Usar: https://crm.grupo-dps.com/dps_sofia_fix.php
 * APAGAR após uso!
 */

// Ler config da BD do Perfex
$config_file = __DIR__ . '/application/config/database.php';
if (!file_exists($config_file)) {
    die(json_encode(['error' => 'Config BD não encontrada']));
}

$db = [];
include $config_file;
$cfg = $db['default'];

$conn = new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Erro BD: ' . $conn->connect_error]));
}

// Detectar prefixo
$r = $conn->query("SHOW TABLES LIKE '%dps_sofia_campaigns'");
$prefix = 'tbl';
if ($r && $r->num_rows > 0) {
    $row = $r->fetch_row();
    $prefix = str_replace('dps_sofia_campaigns', '', $row[0]);
}

$out = ['prefix' => $prefix, 'time' => date('Y-m-d H:i:s')];

// 1. Verificar o hook no dps_sofia_calls.php
$module_file = __DIR__ . '/modules/dps_sofia_calls/dps_sofia_calls.php';
if (file_exists($module_file)) {
    $content = file_get_contents($module_file);
    preg_match('/add_action\([\'"](\w+)[\'"],\s*[\'"]dps_sofia_calls_process_cron/', $content, $m);
    $out['hook_registered'] = $m[1] ?? 'NOT FOUND';
    $out['hook_correct'] = ($out['hook_registered'] === 'after_cron_run') ? 'SIM ✓' : 'NÃO ✗ (tem: ' . $out['hook_registered'] . ')';
}

// 2. Estado das chamadas em 'calling'
$r = $conn->query("SELECT id, campaign_id, lead_name, phone_number, elevenlabs_call_id, started_at,
    TIMESTAMPDIFF(SECOND, started_at, NOW()) as elapsed_secs
    FROM `{$prefix}dps_sofia_call_logs` WHERE status='calling' ORDER BY started_at ASC");
$calling = [];
while ($row = $r->fetch_assoc()) $calling[] = $row;
$out['calling_count'] = count($calling);
$out['calling_calls'] = $calling;

// 3. Se ?fix=1, fechar todas as chamadas presas e disparar próxima
if (isset($_GET['fix'])) {
    $fixed = 0;
    foreach ($calling as $call) {
        // Fechar como no_answer
        $conn->query("UPDATE `{$prefix}dps_sofia_call_logs` SET status='no_answer', ended_at=NOW() WHERE id={$call['id']}");
        // Incrementar calls_failed na campanha
        $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET calls_failed=calls_failed+1 WHERE id={$call['campaign_id']}");
        $fixed++;
    }
    $out['fixed'] = $fixed . ' chamadas fechadas como no_answer';
    
    // Disparar próxima chamada para cada campanha ativa
    $r2 = $conn->query("SELECT * FROM `{$prefix}dps_sofia_campaigns` WHERE status='active'");
    $campaigns_processed = [];
    while ($camp = $r2->fetch_assoc()) {
        // Verificar se há chamadas em calling
        $r3 = $conn->query("SELECT COUNT(*) as c FROM `{$prefix}dps_sofia_call_logs` WHERE campaign_id={$camp['id']} AND status='calling'");
        $row3 = $r3->fetch_assoc();
        if ($row3['c'] == 0) {
            // Buscar próximo pendente
            $r4 = $conn->query("SELECT * FROM `{$prefix}dps_sofia_call_logs` WHERE campaign_id={$camp['id']} AND status='pending' ORDER BY id ASC LIMIT 1");
            $next = $r4->fetch_assoc();
            if ($next) {
                $campaigns_processed[] = "Campanha {$camp['id']}: próximo lead = {$next['lead_name']} ({$next['phone_number']})";
                // Nota: não dispara a chamada ElevenLabs aqui (precisaria da API key e do agente)
                // Apenas marca como 'calling' para o cron pegar
                // Na verdade, vamos deixar o cron fazer isso
            } else {
                $campaigns_processed[] = "Campanha {$camp['id']}: sem leads pendentes";
            }
        }
    }
    $out['campaigns_after_fix'] = $campaigns_processed;
}

// 4. Se ?fix_hook=1, corrigir o hook no ficheiro
if (isset($_GET['fix_hook'])) {
    if (file_exists($module_file)) {
        $content = file_get_contents($module_file);
        $new_content = str_replace(
            "hooks()->add_action('perfex_cron', 'dps_sofia_calls_process_cron');",
            "hooks()->add_action('after_cron_run', 'dps_sofia_calls_process_cron');",
            $content
        );
        if ($new_content !== $content) {
            file_put_contents($module_file, $new_content);
            if (function_exists('opcache_invalidate')) opcache_invalidate($module_file, true);
            $out['fix_hook_result'] = 'Hook corrigido com sucesso!';
        } else {
            $out['fix_hook_result'] = 'Nenhuma alteração necessária (hook já correto ou não encontrado)';
        }
        // Reler para confirmar
        $content2 = file_get_contents($module_file);
        preg_match('/add_action\([\'"](\w+)[\'"],\s*[\'"]dps_sofia_calls_process_cron/', $content2, $m2);
        $out['hook_after_fix'] = $m2[1] ?? 'NOT FOUND';
    }
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
