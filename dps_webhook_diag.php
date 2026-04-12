<?php
/**
 * Diagnóstico completo do webhook Meta
 * Verifica: token, API, log, configuração
 */

$key = $_GET['key'] ?? '';
if ($key !== 'dps-diag-2026') die('Acesso negado');

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO WEBHOOK META ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Verificar se o ficheiro meta-webhook.php existe
$webhook_file = __DIR__ . '/meta-webhook.php';
echo "1. Ficheiro meta-webhook.php: " . (file_exists($webhook_file) ? "EXISTE" : "NÃO EXISTE") . "\n";

// 2. Ler token actual do webhook
if (file_exists($webhook_file)) {
    $content = file_get_contents($webhook_file);
    if (preg_match("/define\('META_PAGE_ACCESS_TOKEN',\s*'([^']+)'\)/", $content, $m)) {
        $token = $m[1];
        $token_preview = substr($token, 0, 20) . '...' . substr($token, -10);
        echo "2. Token actual: $token_preview\n";
        echo "   Comprimento: " . strlen($token) . " chars\n";
    } else {
        echo "2. Token: NÃO ENCONTRADO no ficheiro\n";
        $token = '';
    }
}

// 3. Testar o token contra a Graph API
echo "\n3. Teste do token Meta:\n";
if (!empty($token)) {
    $url = "https://graph.facebook.com/v19.0/me?access_token=" . urlencode($token);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($resp, true);
    echo "   HTTP: $code\n";
    echo "   Resposta: $resp\n";
    
    if (isset($data['error'])) {
        echo "   ERRO: " . $data['error']['message'] . " (código: " . $data['error']['code'] . ")\n";
        if ($data['error']['code'] == 190) {
            echo "   >> TOKEN EXPIRADO ou INVÁLIDO!\n";
        }
    } else {
        echo "   >> Token válido! ID: " . ($data['id'] ?? 'N/A') . ", Nome: " . ($data['name'] ?? 'N/A') . "\n";
    }
}

// 4. Verificar log do webhook
echo "\n4. Log do webhook:\n";
$log_file = __DIR__ . '/application/logs/meta_webhook.log';
if (file_exists($log_file)) {
    $size = filesize($log_file);
    echo "   Ficheiro: EXISTE ($size bytes)\n";
    // Últimas 30 linhas
    $lines = file($log_file);
    $last_lines = array_slice($lines, -30);
    echo "   Últimas 30 linhas:\n";
    foreach ($last_lines as $line) {
        echo "   " . $line;
    }
} else {
    echo "   Ficheiro de log NÃO EXISTE: $log_file\n";
    // Verificar se o directório existe
    $log_dir = __DIR__ . '/application/logs/';
    echo "   Directório logs: " . (is_dir($log_dir) ? "EXISTE" : "NÃO EXISTE") . "\n";
    if (is_dir($log_dir)) {
        $files = glob($log_dir . 'meta*');
        echo "   Ficheiros meta* no directório: " . count($files) . "\n";
        foreach ($files as $f) echo "   - " . basename($f) . "\n";
    }
}

// 5. Verificar subscrição do webhook na Meta
echo "\n5. Verificar subscrição webhook na Meta:\n";
if (!empty($token)) {
    // Obter page_id
    $url = "https://graph.facebook.com/v19.0/294945163693663/subscribed_apps?access_token=" . urlencode($token);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "   HTTP: $code\n";
    echo "   Resposta: $resp\n";
}

// 6. Verificar API do Perfex
echo "\n6. Teste API Perfex CRM:\n";
$perfex_token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoiTm92byBtYWtlIiwibmFtZSI6ImludGVncmFjYW8iLCJBUElfVElNRSI6MTc2NDQzMzE5NX0.11YaaygGbzoZqdTYSEOG7TEZsi7TheKfHKRq_svJm14';
$url = "https://crm.grupo-dps.com/api/leads?page=1";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["authtoken: $perfex_token"]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP: $code\n";
$data = json_decode($resp, true);
if (is_array($data)) {
    echo "   Leads encontradas: " . count($data) . "\n";
    echo "   >> API Perfex OK\n";
} else {
    echo "   Resposta: " . substr($resp, 0, 200) . "\n";
    echo "   >> PROBLEMA com a API Perfex\n";
}

// 7. Testar criar lead via API Perfex
echo "\n7. Teste criar lead via API Perfex:\n";
$test_lead = [
    'name'        => 'TESTE WEBHOOK ' . date('H:i:s'),
    'email'       => 'teste-webhook-' . time() . '@teste.com',
    'phonenumber' => '999000000',
    'source'      => 2,
    'status'      => 4,
    'assigned'    => 1,
];
$ch = curl_init("https://crm.grupo-dps.com/api/leads");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_lead));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["authtoken: $perfex_token"]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP: $code\n";
echo "   Resposta: $resp\n";
$data = json_decode($resp, true);
if (isset($data['id']) || $code == 200 || $code == 201) {
    echo "   >> Lead de teste criada com sucesso! ID: " . ($data['id'] ?? 'N/A') . "\n";
} else {
    echo "   >> FALHOU a criar lead de teste\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>
