<?php
/**
 * MV Lead Endpoint
 * Recebe leads do Make (Facebook Lead Ads - Formulário MV)
 * Cria lead no Perfex CRM com tag MV, atribuída ao Ricardo (staff_id=1)
 */

// Definir BASEPATH para contornar a verificação de segurança do CodeIgniter nos ficheiros de config
define('BASEPATH', __DIR__ . '/');

// Token de segurança
define('MV_TOKEN', 'dps-mv-2026');

// Verificar token
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== MV_TOKEN) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Incluir configuração da BD
require_once __DIR__ . '/application/config/app-config.php';

// Ligação à base de dados
try {
    $pdo = new PDO(
        "mysql:host=" . APP_DB_HOSTNAME . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
        APP_DB_USERNAME,
        APP_DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

// Receber dados do Make
$name       = isset($_POST['name'])        ? trim($_POST['name'])        : '';
$email      = isset($_POST['email'])       ? trim($_POST['email'])       : '';
$phone      = isset($_POST['phonenumber']) ? trim($_POST['phonenumber']) : '';
$source_id  = isset($_POST['source'])      ? intval($_POST['source'])    : 2;
$status_id  = isset($_POST['status'])      ? intval($_POST['status'])    : 4;
$assigned   = isset($_POST['assigned'])    ? intval($_POST['assigned'])  : 1;
$fb_lead_id = isset($_POST['lead_id'])     ? trim($_POST['lead_id'])     : '';

// Validar campos obrigatórios
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Name is required']);
    exit;
}

// Limpar número de telefone
$phone = preg_replace('/[^0-9+]/', '', $phone);

// Verificar duplicado (por email ou telefone)
if (!empty($email) || !empty($phone)) {
    $check_sql = "SELECT id FROM tblleads WHERE 1=0";
    $check_params = [];
    if (!empty($email)) {
        $check_sql .= " OR email = ?";
        $check_params[] = $email;
    }
    if (!empty($phone)) {
        $check_sql .= " OR phonenumber = ?";
        $check_params[] = $phone;
    }
    $stmt = $pdo->prepare($check_sql);
    $stmt->execute($check_params);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        echo json_encode(['status' => 'duplicate', 'lead_id' => $existing['id'], 'message' => 'Lead already exists']);
        exit;
    }
}

// Inserir lead
try {
    $stmt = $pdo->prepare("
        INSERT INTO tblleads
            (status, source, assigned, name, email, phonenumber, description, dateadded, lastcontact, addedfrom, is_public, date_converted)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, NOW(), NULL, 1, 0, NULL)
    ");
    $description = "Lead Facebook Lead Ads - Formulário MV\nFacebook Lead ID: " . $fb_lead_id;
    $stmt->execute([
        $status_id,
        $source_id,
        $assigned,
        $name,
        $email,
        $phone,
        $description
    ]);
    $lead_id = $pdo->lastInsertId();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to insert lead: ' . $e->getMessage()]);
    exit;
}

// Adicionar tag MV
try {
    // Verificar se a tag MV existe
    $stmt = $pdo->prepare("SELECT id FROM tbltags WHERE name = 'MV' LIMIT 1");
    $stmt->execute();
    $tag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tag) {
        // Criar a tag MV se não existir
        $stmt = $pdo->prepare("INSERT INTO tbltags (name) VALUES ('MV')");
        $stmt->execute();
        $tag_id = $pdo->lastInsertId();
    } else {
        $tag_id = $tag['id'];
    }

    // Associar tag à lead
    $stmt = $pdo->prepare("INSERT IGNORE INTO tbltagstaggables (tag_id, rel_id, rel_type) VALUES (?, ?, 'lead')");
    $stmt->execute([$tag_id, $lead_id]);
} catch (PDOException $e) {
    // Não falhar por causa da tag
    error_log('MV tag error: ' . $e->getMessage());
}

// Resposta de sucesso
echo json_encode([
    'status'  => 'success',
    'lead_id' => $lead_id,
    'name'    => $name,
    'email'   => $email,
    'phone'   => $phone,
    'tag'     => 'MV'
]);
