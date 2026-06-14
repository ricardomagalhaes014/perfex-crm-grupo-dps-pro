<?php
/**
 * DPS - Obter leads recentes para a Sofia Outbound
 * Endpoint: GET /sofia_get_leads.php
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

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

$db = array();
include $db_config_file;
$db_host     = $db['default']['hostname'];
$db_user     = $db['default']['username'];
$db_pass     = $db['default']['password'];
$db_name     = $db['default']['database'];
$db_prefix   = isset($db['default']['dbprefix']) ? $db['default']['dbprefix'] : 'tbl';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

// Receber parâmetros
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : 60; // leads dos últimos 60 minutos
$user_id = 1; // ricardomagalhaes

// Calcular a data limite
$time_limit = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

// Obter leads
// assigned = 1 (ricardomagalhaes)
$sql = "SELECT id, name, email, phonenumber, dateadded, status, description, 
        (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM {$db_prefix}tags 
         JOIN {$db_prefix}taggables ON {$db_prefix}taggables.tag_id = {$db_prefix}tags.id 
         WHERE {$db_prefix}taggables.rel_id = {$db_prefix}leads.id AND {$db_prefix}taggables.rel_type = 'lead') as tags
        FROM {$db_prefix}leads 
        WHERE assigned = ? AND dateadded >= ?
        ORDER BY dateadded DESC LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('isi', $user_id, $time_limit, $limit);
$stmt->execute();
$result = $stmt->get_result();

$leads = [];
while ($row = $result->fetch_assoc()) {
    // Verificar se a lead já foi contactada pela Sofia
    // Verificamos nas notas se já existe alguma com "Sofia Outbound"
    $lead_id = $row['id'];
    $sql_notes = "SELECT id FROM {$db_prefix}notes WHERE rel_id = ? AND rel_type = 'lead' AND description LIKE '%Sofia Outbound%' LIMIT 1";
    $stmt_notes = $conn->prepare($sql_notes);
    $stmt_notes->bind_param('i', $lead_id);
    $stmt_notes->execute();
    $result_notes = $stmt_notes->get_result();
    
    if ($result_notes->num_rows == 0) {
        // Se não tiver nota da Sofia, adicionamos à lista
        $leads[] = $row;
    }
    $stmt_notes->close();
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'count' => count($leads),
    'leads' => $leads
]);
