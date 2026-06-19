<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config_file = __DIR__ . '/application/config/database.php';
if (!file_exists($config_file)) {
    echo json_encode(['error' => 'Config BD nao encontrada']);
    exit;
}

$db = [];
include $config_file;
$cfg = $db['default'];

$conn = new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);
if ($conn->connect_error) {
    echo json_encode(['error' => 'Erro BD: ' . $conn->connect_error]);
    exit;
}

$r = $conn->query("SHOW TABLES LIKE '%dps_sofia_campaigns'");
$prefix = 'tbl';
if ($r && $r->num_rows > 0) {
    $row = $r->fetch_row();
    $prefix = str_replace('dps_sofia_campaigns', '', $row[0]);
}

$out = ['prefix' => $prefix, 'time' => date('Y-m-d H:i:s')];

// Verificar hook
$module_file = __DIR__ . '/modules/dps_sofia_calls/dps_sofia_calls.php';
if (file_exists($module_file)) {
    $content = file_get_contents($module_file);
    preg_match("/add_action\('(\w+)',\s*'dps_sofia_calls_process_cron'\)/", $content, $m);
    $out['hook'] = isset($m[1]) ? $m[1] : 'NOT FOUND';
    $out['hook_ok'] = ($out['hook'] === 'after_cron_run') ? 'YES' : 'NO';
}

// Chamadas em calling
$sql = "SELECT id, campaign_id, lead_name, phone_number, started_at, TIMESTAMPDIFF(SECOND, started_at, NOW()) as secs FROM `{$prefix}dps_sofia_call_logs` WHERE status='calling' ORDER BY started_at ASC";
$r2 = $conn->query($sql);
$calling = [];
while ($row = $r2->fetch_assoc()) $calling[] = $row;
$out['calling_count'] = count($calling);
$out['calling'] = $calling;

// fix_hook: corrigir o hook
if (isset($_GET['fix_hook']) && file_exists($module_file)) {
    $c = file_get_contents($module_file);
    $c2 = str_replace("add_action('perfex_cron'", "add_action('after_cron_run'", $c);
    if ($c2 !== $c) {
        file_put_contents($module_file, $c2);
        if (function_exists('opcache_invalidate')) opcache_invalidate($module_file, true);
        $out['fix_hook'] = 'FIXED';
    } else {
        $out['fix_hook'] = 'NO CHANGE';
    }
    preg_match("/add_action\('(\w+)',\s*'dps_sofia_calls_process_cron'\)/", file_get_contents($module_file), $m3);
    $out['hook_now'] = isset($m3[1]) ? $m3[1] : 'NOT FOUND';
}

// fix: fechar chamadas presas
if (isset($_GET['fix'])) {
    $fixed = 0;
    foreach ($calling as $call) {
        $conn->query("UPDATE `{$prefix}dps_sofia_call_logs` SET status='no_answer', ended_at=NOW() WHERE id=" . (int)$call['id']);
        $conn->query("UPDATE `{$prefix}dps_sofia_campaigns` SET calls_failed=calls_failed+1 WHERE id=" . (int)$call['campaign_id']);
        $fixed++;
    }
    $out['fixed'] = $fixed;
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($out, JSON_PRETTY_PRINT);
