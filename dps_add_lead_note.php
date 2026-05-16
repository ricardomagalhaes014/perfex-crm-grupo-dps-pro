<?php
/**
 * DPS - Adicionar Nota a Lead via Email
 * Endpoint: POST /dps_add_lead_note.php
 * 
 * Parâmetros POST (JSON ou form-data):
 *   - email: email da lead para pesquisar
 *   - note: texto da nota a adicionar
 *   - token: token de autenticação (JWT do CRM)
 * 
 * Resposta JSON:
 *   - success: true/false
 *   - message: mensagem de sucesso ou erro
 *   - lead_id: ID da lead encontrada
 *   - note_id: ID da nota criada
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, authtoken');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ler o input (JSON ou POST)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$email = isset($input['email']) ? trim($input['email']) : '';
$note  = isset($input['note'])  ? trim($input['note'])  : '';
$token = isset($input['token']) ? trim($input['token']) : '';

// Também aceitar o token via header
if (empty($token)) {
    $token = isset($_SERVER['HTTP_AUTHTOKEN']) ? $_SERVER['HTTP_AUTHTOKEN'] : '';
}

// Validação básica
if (empty($email) || empty($note)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email e nota são obrigatórios']);
    exit;
}

// Validar o token JWT
$jwt_key = 'eyJ0eXAiOiJKV1QiLCJhbGciTWeLUzI1NiJ9IiRkYXRhIz';
$token_expire_time = 315569260; // ~10 anos

function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function validate_jwt($token, $key, $expire_time) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    
    list($header_b64, $payload_b64, $sig_b64) = $parts;
    
    // Verificar assinatura
    $signing_input = "$header_b64.$payload_b64";
    $expected_sig = hash_hmac('sha256', $signing_input, $key, true);
    $actual_sig = base64url_decode($sig_b64);
    
    if (!hash_equals($expected_sig, $actual_sig)) return false;
    
    // Verificar payload
    $payload = json_decode(base64url_decode($payload_b64), true);
    if (!$payload || !isset($payload['API_TIME'])) return false;
    
    $time_diff = time() - $payload['API_TIME'];
    if ($time_diff >= $expire_time) return false;
    
    return $payload;
}

if (!empty($token)) {
    $payload = validate_jwt($token, $jwt_key, $token_expire_time);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado']);
        exit;
    }
}

// Carregar o Perfex CRM
define('BASEPATH', realpath(__DIR__) . '/');
define('APPPATH', realpath(__DIR__ . '/application') . '/');

// Conectar à base de dados diretamente
$db_config_file = __DIR__ . '/application/config/database.php';
if (!file_exists($db_config_file)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuração da base de dados não encontrada']);
    exit;
}

// Ler a configuração da base de dados
$db = array();
include $db_config_file;

$db_host     = $db['default']['hostname'];
$db_user     = $db['default']['username'];
$db_pass     = $db['default']['password'];
$db_name     = $db['default']['database'];
$db_prefix   = isset($db['default']['dbprefix']) ? $db['default']['dbprefix'] : 'tbl';

// Conectar ao MySQL
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão à base de dados: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

// Pesquisar a lead pelo email
$stmt = $conn->prepare("SELECT id, name, email, phonenumber FROM {$db_prefix}leads WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$lead = $result->fetch_assoc();
$stmt->close();

if (!$lead) {
    // Tentar pesquisa por nome (extraído do email)
    $name_part = explode('@', $email)[0];
    $name_part = str_replace(['.', '_', '-'], ' ', $name_part);
    $name_search = "%{$name_part}%";
    
    $stmt2 = $conn->prepare("SELECT id, name, email, phonenumber FROM {$db_prefix}leads WHERE name LIKE ? LIMIT 1");
    $stmt2->bind_param('s', $name_search);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $lead = $result2->fetch_assoc();
    $stmt2->close();
}

if (!$lead) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => "Lead não encontrada com email: $email"]);
    $conn->close();
    exit;
}

$lead_id = $lead['id'];

// Adicionar a nota à lead
$now = date('Y-m-d H:i:s');
$addedfrom = 1; // Staff ID 1 (admin)

$stmt3 = $conn->prepare("INSERT INTO {$db_prefix}notes (rel_id, rel_type, description, addedfrom, dateadded, date_contacted) VALUES (?, 'lead', ?, ?, ?, ?)");
$stmt3->bind_param('isiss', $lead_id, $note, $addedfrom, $now, $now);
$success = $stmt3->execute();
$note_id = $conn->insert_id;
$stmt3->close();

if (!$success) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar nota: ' . $conn->error]);
    $conn->close();
    exit;
}

// Registar actividade na lead
$activity = "📧 Resposta por email registada: " . mb_substr(strip_tags($note), 0, 200);
$stmt4 = $conn->prepare("INSERT INTO {$db_prefix}leads_activity_log (leadid, description, additional_data, staffid, full_name, date) VALUES (?, ?, '', ?, 'Make Integration', ?)");
$stmt4->bind_param('isis', $lead_id, $activity, $addedfrom, $now);
$stmt4->execute();
$stmt4->close();

$conn->close();

echo json_encode([
    'success' => true,
    'message' => 'Nota adicionada com sucesso',
    'lead_id' => $lead_id,
    'lead_name' => $lead['name'],
    'lead_email' => $lead['email'],
    'note_id' => $note_id
]);
