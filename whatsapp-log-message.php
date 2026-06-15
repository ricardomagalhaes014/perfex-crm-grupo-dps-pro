<?php
/**
 * Script para receber logs de mensagens do WhatsApp e gravar nas leads do Perfex CRM
 */

// Ler dados
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (empty($input) || !isset($input['secret']) || $input['secret'] !== 'dps-webhook-secret-2026') {
    http_response_code(403);
    die('Forbidden');
}

$phone = $input['phone'] ?? '';
$message = $input['message'] ?? '';
$direction = $input['direction'] ?? 'received';

if (empty($phone) || empty($message)) {
    http_response_code(400);
    die('Missing data');
}

// Limpar número
$phone_clean = preg_replace('/[^0-9]/', '', $phone);

// Configurações da Base de Dados (extraídas do Perfex)
// Como não temos acesso ao application/config/database.php directamente, 
// vamos ler do ficheiro config se possível, ou usar as credenciais padrão da Hostinger
$db_user = 'u172337921_crmgrupopds';
$db_pass = '3AF5_ZCiqQ7:=At';
$db_name = 'u172337921_crmgrupopds';
$db_host = 'localhost';

// Tentar ler a password do ficheiro de configuração se existir
$config_path = __DIR__ . '/application/config/database.php';
if (file_exists($config_path)) {
    $config_content = file_get_contents($config_path);
    if (preg_match('/\'username\'\s*=>\s*\'([^\']+)\'/', $config_content, $matches)) {
        $db_user = $matches[1];
    }
    if (preg_match('/\'password\'\s*=>\s*\'([^\']+)\'/', $config_content, $matches)) {
        $db_pass = $matches[1];
    }
    if (preg_match('/\'database\'\s*=>\s*\'([^\']+)\'/', $config_content, $matches)) {
        $db_name = $matches[1];
    }
}

// Ligar à BD
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    die("Connection failed: " . $conn->connect_error);
}

// Procurar lead — gerar variantes do número para cobrir todos os formatos na BD
$phone_variants = [
    $phone_clean,           // 351912962578
    '+' . $phone_clean,     // +351912962578
];
if (strpos($phone_clean, '351') === 0) {
    $short = substr($phone_clean, 3);
    $phone_variants[] = $short;       // 912962578
    $phone_variants[] = '+' . $short; // +912962578
} elseif (strpos($phone_clean, '55') === 0) {
    $short = substr($phone_clean, 2);
    $phone_variants[] = $short;
    $phone_variants[] = '+' . $short;
} else {
    $phone_variants[] = '351' . $phone_clean;
    $phone_variants[] = '+351' . $phone_clean;
}

$in_clause = implode("','", array_map([$conn, 'real_escape_string'], $phone_variants));
$sql = "SELECT id FROM tblleads WHERE phonenumber IN ('$in_clause') ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lead_id = $row['id'];
    
    // Adicionar nota
    $prefix = ($direction == 'received') ? '[WhatsApp Recebido]: ' : '[WhatsApp Enviado Bot]: ';
    $full_message = $conn->real_escape_string($prefix . $message);
    $date = date('Y-m-d H:i:s');
    
    $insert_sql = "INSERT INTO tblnotes (rel_id, rel_type, description, date_contacted, addedfrom, dateadded) 
                   VALUES ($lead_id, 'lead', '$full_message', '$date', 1, '$date')";
    $conn->query($insert_sql);

    // Registar como interacção em tbllead_activity_log
    $activity_desc = ($direction == 'received')
        ? $conn->real_escape_string('💬 WhatsApp recebido: ' . $message)
        : $conn->real_escape_string('💬 WhatsApp enviado: ' . $message);
    $activity_sql = "INSERT INTO tbllead_activity_log (leadid, staffid, description, date, full_name, additional_data)
                     VALUES ($lead_id, 1, '$activity_desc', '$date', 'WhatsApp Bot', '')";
    $conn->query($activity_sql);

    // Actualizar lastcontact da lead
    $conn->query("UPDATE tblleads SET lastcontact='$date' WHERE id=$lead_id");

    echo "Success: Note and activity added to lead $lead_id";
} else {
    echo "Success: No matching lead found";
}

$conn->close();
?>
