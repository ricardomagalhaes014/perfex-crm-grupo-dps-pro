<?php
// ============================================================
// WEBHOOK ELEVENLABS → EMAIL + CRM PERFEX
// Ficheiro: dpsimobiliario.pt/elevenlabs_webhook.php
// v2.0 — inclui deploy de ficheiros e registo de utilizadores
// ============================================================

define('EMAIL_DESTINO', 'ricardomagalhaes@grupo-dps.com');
define('PERFEX_URL',    'https://crm.grupo-dps.com/admin/api');
define('PERFEX_TOKEN',  'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoiUmljYXJkbyBNYWdhbGhhZXMiLCJuYW1lIjoiV0VCSE9PSyBNRVRBIiwiQVBJX1RJTUUiOjE3NzYwMDIxODR9.g9wEzwauRwyVBh8W7OPPi5ls5Y84DXDcPkxcNEWkfOw');
define('LOG_FILE',      __DIR__ . '/webhook_log.txt');
define('DEPLOY_TOKEN',  'dps_deploy_2026_secure');
define('USERS_FILE',    __DIR__ . '/sofia_users.json');

// ============================================================
// CORS — permitir pedidos do widget
// ============================================================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================
// DEPLOY DE FICHEIROS (acesso seguro)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['deploy'])) {
    $token = $_GET['token'] ?? '';
    if ($token !== DEPLOY_TOKEN) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
    $file = $_GET['file'] ?? '';
    $allowed = ['index.html', 'raizes/index.html', 'belohorizonte/index.html'];
    if (!in_array($file, $allowed)) {
        die(json_encode(['error' => 'File not allowed']));
    }
    $content = file_get_contents('php://input');
    $path = __DIR__ . '/' . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (file_put_contents($path, $content) !== false) {
        echo json_encode(['success' => true, 'file' => $file, 'bytes' => strlen($content)]);
    } else {
        echo json_encode(['error' => 'Write failed', 'path' => $path]);
    }
    exit();
}

// ============================================================
// REGISTO DE UTILIZADOR PRÉ-CHAMADA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'register_user') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?? [];
    
    $entry = [
        'timestamp'  => date('Y-m-d H:i:s'),
        'nome'       => $data['nome'] ?? '',
        'email'      => $data['email'] ?? '',
        'telefone'   => $data['telefone'] ?? '',
        'gestor'     => $data['gestor'] ?? '',
        'agente_id'  => $data['agente_id'] ?? '',
        'pagina'     => $data['pagina'] ?? '',
    ];
    
    // Guardar no ficheiro de utilizadores
    $users = [];
    if (file_exists(USERS_FILE)) {
        $users = json_decode(file_get_contents(USERS_FILE), true) ?? [];
    }
    $users[] = $entry;
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Log
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " | REGISTO: " . json_encode($entry) . "\n\n", FILE_APPEND);
    
    // Tentar criar lead no CRM
    $crm_lead = false;
    if ($entry['nome'] && $entry['email'] && PERFEX_TOKEN !== 'INSERIR_API_TOKEN_AQUI') {
        $pagina = $entry['pagina'];
        $fonte = 'Website DPS';
        if (strpos($pagina, 'raizes') !== false) $fonte = 'Website - Raízes';
        elseif (strpos($pagina, 'belohorizonte') !== false) $fonte = 'Website - Belo Horizonte';
        
        $lead_data = [
            'firstname'  => $entry['nome'],
            'email'      => $entry['email'],
            'phonenumber'=> $entry['telefone'],
            'source'     => $fonte,
            'status'     => 1,
            'assigned'   => 1,
        ];
        $crm_lead = perfex_api_post('/leads', $lead_data);
    }
    
    echo json_encode(['success' => true, 'registered' => true, 'crm_lead' => $crm_lead ? true : false]);
    exit();
}

// ============================================================
// WEBHOOK PRINCIPAL — só aceitar POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Ler o payload do ElevenLabs
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log para debug
file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " | " . $raw . "\n\n", FILE_APPEND);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

// Extrair dados da conversa
$conversation_id = $data['conversation_id'] ?? $data['data']['conversation_id'] ?? 'N/D';
$agent_id        = $data['agent_id'] ?? $data['data']['agent_id'] ?? 'N/D';
$status          = $data['status'] ?? $data['data']['status'] ?? 'N/D';
$duration        = $data['data']['metadata']['call_duration'] ?? 0;
$duration_min    = round($duration / 60, 1);

// Mapa de agentes
$agentes = [
    'agent_0901kv03vzc4eqnvzt5758mms6t8' => 'Sofia — Assistente Geral DPS',
    'agent_7501kv0dj084fmbahfdafsfmgcfv'  => 'Sofia — Especialista Raízes',
    'agent_1901kv0dj4m0fxnr5pxqdhqzjf26'  => 'Sofia — Especialista Belo Horizonte',
];
$agente_nome = $agentes[$agent_id] ?? 'Sofia — DPS Imobiliário';

// Extrair transcrição
$transcript = '';
$messages = $data['data']['transcript'] ?? $data['transcript'] ?? [];
if (is_array($messages)) {
    foreach ($messages as $msg) {
        $role = strtoupper($msg['role'] ?? 'DESCONHECIDO');
        $text = $msg['message'] ?? $msg['content'] ?? '';
        if ($text) {
            $transcript .= "[$role]: $text\n";
        }
    }
}

// Tentar obter dados do utilizador registado (do ficheiro de utilizadores)
$cliente_nome  = '';
$cliente_email = '';
$cliente_tel   = '';
$cliente_gestor = '';

// Procurar utilizador recente (últimos 30 min) com o mesmo agente
if (file_exists(USERS_FILE)) {
    $users = json_decode(file_get_contents(USERS_FILE), true) ?? [];
    $agente_id_clean = $agent_id;
    $now = time();
    foreach (array_reverse($users) as $u) {
        if ($u['agente_id'] === $agente_id_clean) {
            $user_time = strtotime($u['timestamp']);
            if (($now - $user_time) < 1800) { // 30 minutos
                $cliente_nome   = $u['nome'] ?? '';
                $cliente_email  = $u['email'] ?? '';
                $cliente_tel    = $u['telefone'] ?? '';
                $cliente_gestor = $u['gestor'] ?? '';
                break;
            }
        }
    }
}

// Fallback: extrair da transcrição
if (!$cliente_tel) {
    $cliente_tel = extrair_dado_regex($transcript, '/(\+351\s?)?[29]\d{8}/');
}
if (!$cliente_email) {
    $cliente_email = extrair_dado_regex($transcript, '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/');
}

$data_hora = date('d/m/Y H:i');

// ============================================================
// 1. ENVIAR EMAIL
// ============================================================
$assunto = "🏠 Sofia IA — Nova Conversa | $agente_nome | $data_hora";

$gestor_linha = $cliente_gestor ? "<tr><td style='padding:8px;font-weight:bold;'>Gestor Indicador:</td><td style='padding:8px;'>$cliente_gestor</td></tr>" : '';

$corpo_email = "
<html><body style='font-family:Arial,sans-serif;background:#f0f0f0;padding:20px;'>
<div style='max-width:700px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);'>
<div style='background:#1a1a1a;padding:20px;border-bottom:3px solid #C5A55A;'>
  <h2 style='color:#C5A55A;margin:0;'>🏠 Nova Conversa — Sofia IA</h2>
  <p style='color:#999;margin:5px 0 0;'>$agente_nome</p>
</div>
<div style='background:#f9f9f9;padding:20px;border:1px solid #ddd;'>
  <table style='width:100%;border-collapse:collapse;'>
    <tr><td style='padding:8px;font-weight:bold;width:160px;'>Data/Hora:</td><td style='padding:8px;'>$data_hora</td></tr>
    <tr style='background:#fff;'><td style='padding:8px;font-weight:bold;'>ID Conversa:</td><td style='padding:8px;font-family:monospace;font-size:12px;'>$conversation_id</td></tr>
    <tr><td style='padding:8px;font-weight:bold;'>Duração:</td><td style='padding:8px;'>{$duration_min} minutos</td></tr>
    <tr style='background:#fff;'><td style='padding:8px;font-weight:bold;'>Estado:</td><td style='padding:8px;'>$status</td></tr>
    " . ($cliente_nome  ? "<tr><td style='padding:8px;font-weight:bold;'>Cliente:</td><td style='padding:8px;'>$cliente_nome</td></tr>" : "") . "
    " . ($cliente_tel   ? "<tr style='background:#fff;'><td style='padding:8px;font-weight:bold;'>Telefone:</td><td style='padding:8px;'>$cliente_tel</td></tr>" : "") . "
    " . ($cliente_email ? "<tr><td style='padding:8px;font-weight:bold;'>Email:</td><td style='padding:8px;'>$cliente_email</td></tr>" : "") . "
    $gestor_linha
  </table>
</div>
<div style='background:#fff;padding:20px;border:1px solid #ddd;border-top:none;'>
  <h3 style='color:#1a1a1a;border-bottom:2px solid #C5A55A;padding-bottom:8px;'>Transcrição Completa</h3>
  <pre style='background:#f5f5f5;padding:15px;border-radius:4px;font-size:13px;line-height:1.6;white-space:pre-wrap;'>$transcript</pre>
</div>
<div style='background:#1a1a1a;padding:15px;border-radius:0 0 8px 8px;text-align:center;'>
  <a href='https://elevenlabs.io/app/conversational-ai' style='color:#C5A55A;text-decoration:none;font-size:12px;'>Ver no ElevenLabs →</a>
  &nbsp;|&nbsp;
  <a href='https://crm.grupo-dps.com/admin/leads' style='color:#C5A55A;text-decoration:none;font-size:12px;'>Ver CRM →</a>
</div>
</div>
</body></html>
";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: Sofia IA <noreply@dpsimobiliario.pt>\r\n";
$headers .= "Reply-To: " . EMAIL_DESTINO . "\r\n";
$email_enviado = mail(EMAIL_DESTINO, $assunto, $corpo_email, $headers);

// ============================================================
// 2. REGISTAR NOTA NO CRM PERFEX
// ============================================================
$nota_crm = "📞 Conversa Sofia IA — $data_hora\n";
$nota_crm .= "Agente: $agente_nome\n";
$nota_crm .= "Duração: {$duration_min} min | ID: $conversation_id\n\n";
$nota_crm .= "--- TRANSCRIÇÃO ---\n$transcript";

$crm_resultado = false;
if (PERFEX_TOKEN !== 'INSERIR_API_TOKEN_AQUI') {
    // Procurar lead pelo telefone ou email
    $lead_id = null;
    if ($cliente_tel) {
        $resp = perfex_api_get('/leads?search=' . urlencode($cliente_tel));
        $lead_id = $resp[0]['id'] ?? null;
    }
    if (!$lead_id && $cliente_email) {
        $resp = perfex_api_get('/leads?search=' . urlencode($cliente_email));
        $lead_id = $resp[0]['id'] ?? null;
    }
    if ($lead_id) {
        $crm_resultado = perfex_api_post('/leads/notes/' . $lead_id, [
            'description' => $nota_crm,
            'leadid'      => $lead_id,
        ]);
    }
}

// Resposta
http_response_code(200);
echo json_encode([
    'success'       => true,
    'email_enviado' => $email_enviado,
    'crm_nota'      => $crm_resultado ? true : false,
    'conversation'  => $conversation_id,
]);

// ============================================================
// FUNÇÕES AUXILIARES
// ============================================================
function perfex_api_get($endpoint) {
    $ch = curl_init(PERFEX_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['authtoken: ' . PERFEX_TOKEN],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true) ?? [];
}

function perfex_api_post($endpoint, $data) {
    $ch = curl_init(PERFEX_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_HTTPHEADER     => ['authtoken: ' . PERFEX_TOKEN],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($resp, true);
    return ($result['status'] ?? false) === true;
}

function extrair_dado($texto, $keywords) {
    foreach ($keywords as $kw) {
        if (preg_match('/' . $kw . '[^,\n]{1,40}/i', $texto, $m)) {
            return trim($m[0]);
        }
    }
    return '';
}

function extrair_dado_regex($texto, $pattern) {
    if (preg_match($pattern, $texto, $m)) {
        return trim($m[0]);
    }
    return '';
}
