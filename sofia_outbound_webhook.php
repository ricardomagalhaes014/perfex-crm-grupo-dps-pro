<?php
/**
 * DPS - Sofia Outbound Webhook
 * Recebe o evento de fim de chamada do ElevenLabs e grava a transcrição como nota no CRM
 * 
 * Endpoint: POST /sofia_outbound_webhook.php
 * 
 * Payload do ElevenLabs (post_call_webhook):
 * {
 *   "type": "post_call",
 *   "event_timestamp": 1234567890,
 *   "data": {
 *     "conversation_id": "...",
 *     "agent_id": "...",
 *     "call": {
 *       "call_duration_secs": 120,
 *       "to_number": "+351912345678",
 *       "from_number": "+19452929712",
 *       "status": "done"
 *     },
 *     "transcript": [
 *       {"role": "agent", "message": "Olá, sou a Sofia..."},
 *       {"role": "user", "message": "Olá..."}
 *     ],
 *     "analysis": {
 *       "call_successful": true,
 *       "data_collection_results": {...}
 *     }
 *   }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ElevenLabs-Signature');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log file
$log_file = __DIR__ . '/sofia_outbound_log.txt';

function log_msg($msg) {
    global $log_file;
    $ts = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$ts] $msg\n", FILE_APPEND);
}

// Ler o payload
$raw_body = file_get_contents('php://input');
$payload = json_decode($raw_body, true);

log_msg("Webhook recebido: " . substr($raw_body, 0, 500));

if (!$payload) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload inválido']);
    exit;
}

// Verificar se é um evento de pós-chamada
$event_type = $payload['type'] ?? '';
if ($event_type !== 'post_call') {
    log_msg("Evento ignorado (tipo: $event_type)");
    echo json_encode(['success' => true, 'message' => 'Evento ignorado']);
    exit;
}

$data = $payload['data'] ?? [];
$call = $data['call'] ?? [];
$transcript_array = $data['transcript'] ?? [];
$analysis = $data['analysis'] ?? [];
$conversation_id = $data['conversation_id'] ?? '';
$agent_id = $data['agent_id'] ?? '';
$to_number = $call['to_number'] ?? '';
$duration = $call['call_duration_secs'] ?? 0;
$status = $call['status'] ?? 'unknown';

// Determinar o nome do agente
$agent_name = 'Sofia';
if (strpos($agent_id, 'agent_4301kv1pv8g8e259bbdyfk7mrefb') !== false) {
    $agent_name = 'Sofia - Raízes';
} elseif (strpos($agent_id, 'agent_9901kv1pvewveh9s9ebs1rys274k') !== false) {
    $agent_name = 'Sofia - Belo Horizonte';
}

// Formatar a transcrição
$transcript_text = '';
foreach ($transcript_array as $line) {
    $role = $line['role'] ?? 'unknown';
    $message = $line['message'] ?? '';
    $role_label = ($role === 'agent') ? '🤖 Sofia' : '👤 Cliente';
    $transcript_text .= "$role_label: $message\n";
}

// Formatar a nota completa
$call_successful = $analysis['call_successful'] ?? false;
$success_label = $call_successful ? '✅ Chamada bem-sucedida' : '❌ Chamada sem sucesso';

$note = "📞 [Sofia Outbound] Chamada automática concluída\n";
$note .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$note .= "Agente: $agent_name\n";
$note .= "Número contactado: $to_number\n";
$note .= "Duração: {$duration}s\n";
$note .= "Estado: $status\n";
$note .= "Resultado: $success_label\n";
$note .= "ID Conversa: $conversation_id\n";
$note .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$note .= "TRANSCRIÇÃO DA CONVERSA:\n\n";
$note .= $transcript_text ?: "(Sem transcrição disponível)";

// Conectar à base de dados do CRM
define('BASEPATH', realpath(__DIR__) . '/');
define('APPPATH', realpath(__DIR__ . '/application') . '/');

$db_config_file = __DIR__ . '/application/config/database.php';
if (!file_exists($db_config_file)) {
    // Tentar app-config.php
    $db_config_file = __DIR__ . '/application/config/app-config.php';
}

if (!file_exists($db_config_file)) {
    log_msg("ERRO: Ficheiro de configuração da BD não encontrado");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuração da BD não encontrada']);
    exit;
}

// Ler credenciais da BD
$db_content = file_get_contents($db_config_file);
$hostname = '';
$username = '';
$password = '';
$database = '';
$db_prefix = 'tbl';

if (preg_match("/define\('APP_DB_HOSTNAME',\s*'([^']+)'\)/", $db_content, $m)) {
    $hostname = $m[1];
    preg_match("/define\('APP_DB_USERNAME',\s*'([^']+)'\)/", $db_content, $m2);
    $username = $m2[1] ?? '';
    preg_match("/define\('APP_DB_PASSWORD',\s*'([^']+)'\)/", $db_content, $m3);
    $password = $m3[1] ?? '';
    preg_match("/define\('APP_DB_NAME',\s*'([^']+)'\)/", $db_content, $m4);
    $database = $m4[1] ?? '';
} elseif (preg_match("/'hostname'\s*=>\s*'([^']+)'/", $db_content, $m)) {
    $hostname = $m[1];
    preg_match("/'username'\s*=>\s*'([^']+)'/", $db_content, $m2);
    $username = $m2[1] ?? '';
    preg_match("/'password'\s*=>\s*'([^']+)'/", $db_content, $m3);
    $password = $m3[1] ?? '';
    preg_match("/'database'\s*=>\s*'([^']+)'/", $db_content, $m4);
    $database = $m4[1] ?? '';
}

$conn = new mysqli($hostname, $username, $password, $database);
if ($conn->connect_error) {
    log_msg("ERRO BD: " . $conn->connect_error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão à BD']);
    exit;
}
$conn->set_charset('utf8mb4');

// Procurar a lead pelo número de telefone
$phone_clean = preg_replace('/[^0-9+]/', '', $to_number);
$phone_digits = preg_replace('/[^0-9]/', '', $to_number);
$phone_last9 = substr($phone_digits, -9);

$sql = "SELECT id, name, email FROM {$db_prefix}leads WHERE 
        REPLACE(REPLACE(REPLACE(phonenumber, ' ', ''), '-', ''), '(', '') LIKE ? OR
        REPLACE(REPLACE(REPLACE(phonenumber, ' ', ''), '-', ''), '(', '') LIKE ?
        LIMIT 1";
$pattern1 = "%$phone_clean%";
$pattern2 = "%$phone_last9%";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $pattern1, $pattern2);
$stmt->execute();
$result = $stmt->get_result();
$lead = $result->fetch_assoc();
$stmt->close();

if (!$lead) {
    log_msg("Lead não encontrada para o número $to_number");
    // Ainda assim, gravar o log
    $conn->close();
    echo json_encode(['success' => false, 'message' => "Lead não encontrada para $to_number"]);
    exit;
}

$lead_id = $lead['id'];
log_msg("Lead encontrada: ID=$lead_id, Nome={$lead['name']}, Email={$lead['email']}");

// Adicionar a nota à lead
$now = date('Y-m-d H:i:s');
$addedfrom = 1; // Staff ID 1 (admin)
$stmt2 = $conn->prepare("INSERT INTO {$db_prefix}notes (rel_id, rel_type, description, addedfrom, dateadded, date_contacted) VALUES (?, 'lead', ?, ?, ?, ?)");
$stmt2->bind_param('isiss', $lead_id, $note, $addedfrom, $now, $now);
$success_insert = $stmt2->execute();
$note_id = $conn->insert_id;
$stmt2->close();

if ($success_insert) {
    log_msg("Nota adicionada com sucesso à lead $lead_id (nota ID: $note_id)");
    
    // Registar actividade
    $activity = "📞 Sofia Outbound: Chamada automática concluída ($success_label)";
    $stmt3 = $conn->prepare("INSERT INTO {$db_prefix}leads_activity_log (leadid, description, additional_data, staffid, full_name, date) VALUES (?, ?, '', ?, 'Sofia Outbound', ?)");
    $stmt3->bind_param('isis', $lead_id, $activity, $addedfrom, $now);
    $stmt3->execute();
    $stmt3->close();
    
    $conn->close();
    echo json_encode([
        'success' => true,
        'message' => 'Nota adicionada com sucesso',
        'lead_id' => $lead_id,
        'lead_name' => $lead['name'],
        'note_id' => $note_id
    ]);
} else {
    log_msg("ERRO ao adicionar nota: " . $conn->error);
    $conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar nota: ' . $conn->error]);
}
