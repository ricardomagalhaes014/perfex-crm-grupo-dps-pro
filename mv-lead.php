<?php
/**
 * MV Lead Endpoint
 * Recebe leads do Make (Facebook Lead Ads - Formulário MV)
 * Cria lead no Perfex CRM com tag MV, atribuída ao Ricardo (staff_id=1)
 * Envia mensagem de WhatsApp de boas-vindas
 */

// Definir BASEPATH para contornar a verificação de segurança do CodeIgniter nos ficheiros de config
define('BASEPATH', __DIR__ . '/');

// Token de segurança
define('MV_TOKEN', 'dps-mv-2026');

// Configurar logging
$log_file = __DIR__ . '/mv-lead-debug.log';
function log_debug($message, $data = null) {
    global $log_file;
    $date = date('Y-m-d H:i:s');
    $log_msg = "[$date] $message";
    if ($data !== null) {
        $log_msg .= " | Data: " . (is_string($data) ? $data : json_encode($data));
    }
    file_put_contents($log_file, $log_msg . "\n", FILE_APPEND);
}

log_debug("--- NOVO PEDIDO RECEBIDO ---", $_POST);

// Verificar token
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== MV_TOKEN) {
    log_debug("Erro: Token inválido", $token);
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
    log_debug("Ligação à BD com sucesso");
} catch (PDOException $e) {
    log_debug("Erro de ligação à BD", $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

if (isset($_GET['fixsrc'])) {
    $src_stmt = $pdo->prepare("SELECT id FROM tblleads_sources WHERE name LIKE ?");
    $src_stmt->execute(['%Imo Portugal%']);
    $src_row = $src_stmt->fetch(PDO::FETCH_ASSOC);
    if ($src_row) { $sid = (int) $src_row['id']; }
    else { $pdo->prepare("INSERT INTO tblleads_sources (name) VALUES (?)")->execute(['Imo Portugal']); $sid = (int) $pdo->lastInsertId(); }
    $n = $pdo->exec("UPDATE tblleads SET source = $sid WHERE source NOT IN (SELECT id FROM (SELECT id FROM tblleads_sources) x)");
    echo json_encode(['source_id' => $sid, 'updated' => $n]);
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
    log_debug("Erro: Nome obrigatório em falta");
    http_response_code(400);
    echo json_encode(['error' => 'Name is required']);
    exit;
}

// Limpar número de telefone
$phone = preg_replace('/[^0-9+]/', '', $phone);

// Resolver fonte "Imo Portugal" pelo nome (id estatico pode nao existir)
try {
    $src_stmt = $pdo->prepare("SELECT id FROM tblleads_sources WHERE name LIKE ?");
    $src_stmt->execute(['%Imo Portugal%']);
    $src_row = $src_stmt->fetch(PDO::FETCH_ASSOC);
    if ($src_row) { $source_id = (int) $src_row['id']; }
    else { $pdo->prepare("INSERT INTO tblleads_sources (name) VALUES (?)")->execute(['Imo Portugal']); $source_id = (int) $pdo->lastInsertId(); }
    log_debug('Fonte resolvida: Imo Portugal id=' . $source_id);
} catch (Exception $e) { log_debug('Aviso ao resolver fonte: ' . $e->getMessage()); }

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
        log_debug("Lead duplicada encontrada", $existing['id']);
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
    log_debug("Lead inserida com sucesso", $lead_id);
    
    // Activar WhatsApp por defeito (campo customizado fieldid=10)
    try {
        $stmt_cf = $pdo->prepare("INSERT INTO tblcustomfieldsvalues (relid, fieldid, fieldto, value) VALUES (?, 10, 'leads', 'Enable')");
        $stmt_cf->execute([$lead_id]);
        log_debug("WhatsApp activado para a lead");
    } catch (PDOException $e) {
        log_debug("Erro ao activar WhatsApp", $e->getMessage());
    }
} catch (PDOException $e) {
    log_debug("Erro ao inserir lead", $e->getMessage());
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

    // Associar tag à lead - CORRIGIDO NOME DA TABELA (tbltaggables em vez de tbltagstaggables)
    $stmt = $pdo->prepare("INSERT IGNORE INTO tbltaggables (tag_id, rel_id, rel_type, tag_order) VALUES (?, ?, 'lead', 0)");
    $stmt->execute([$tag_id, $lead_id]);
    log_debug("Tag MV associada com sucesso");
} catch (PDOException $e) {
    // Não falhar por causa da tag
    log_debug("Erro ao associar tag MV", $e->getMessage());
}

// -------------------------------------------------------------------------
// ENVIAR MENSAGEM WHATSAPP DE BOAS VINDAS
// -------------------------------------------------------------------------
$wa_sent = false;
$wa_error = '';

// DESATIVADO (2026-08-02): envio automatico de WhatsApp de boas-vindas desligado.
// Motivo: a Evolution API (nao-oficial) faz banir os numeros. Migracao p/ Cloud API oficial.
// NAO REMOVER este bloqueio sem decisao explicita do Ricardo.
// Para reativar SO com a API oficial, repor a condicao original: if (!empty($phone))
if (false) {
    try {
        // Obter configurações da Evolution API
        $stmt = $pdo->prepare("SELECT name, value FROM tbloptions WHERE name IN ('dps_whatsapp_evolution_url', 'dps_whatsapp_evolution_api_key')");
        $stmt->execute();
        $options = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $evo_url = rtrim($options['dps_whatsapp_evolution_url'] ?? '', '/');
        $evo_key = $options['dps_whatsapp_evolution_api_key'] ?? '';
        
        if (!empty($evo_url) && !empty($evo_key)) {
            $instance_name = 'staff-' . $assigned;
            
            // Verificar estado da instância
            $ch = curl_init($evo_url . '/instance/connectionState/' . $instance_name);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $evo_key]);
            $state_json = curl_exec($ch);
            curl_close($ch);
            
            $state_data = json_decode($state_json, true);
            $is_connected = isset($state_data['instance']['state']) && $state_data['instance']['state'] === 'open';
            
            if ($is_connected) {
                // Preparar mensagem de boas-vindas para MV
                $message  = "Olá, tudo bem?\n\n";
                $message .= "Obrigado pelo seu interesse nas oportunidades da DPS Imobiliário.\n\n";
                $message .= "Pode visitar já o nosso site em www.dpsimobiliario.pt e falar com a Sofia, a nossa assistente virtual, que lhe pode apresentar os empreendimentos disponíveis e esclarecer as primeiras dúvidas.\n\n";
                $message .= "De qualquer forma, será brevemente contactado por um dos nossos gestores.\n\n";
                $message .= "Para o conseguirmos orientar melhor, diga-nos por favor:\n\n";
                $message .= "Qual a tipologia que procura?\n";
                $message .= "T1, T2, T3 ou outra?\n\n";
                $message .= "E o objetivo do investimento?\n";
                $message .= "Revenda, arrendamento ou habitação própria?";
                
                // Normalizar número para a Evolution API (apenas dígitos)
                $wa_number = preg_replace('/[^0-9]/', '', $phone);
                
                // Enviar mensagem
                $ch = curl_init($evo_url . '/message/sendText/' . $instance_name);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    // Evolution v2: 'text' no primeiro nível (a v1 aninhava em textMessage).
                    'number' => $wa_number,
                    'text'   => $message,
                ]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'apikey: ' . $evo_key
                ]);
                
                $send_json = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                log_debug("Resposta Evolution API", ['code' => $http_code, 'body' => $send_json]);
                
                if ($http_code >= 200 && $http_code < 300) {
                    $wa_sent = true;
                    // Registar nota na lead
                    $stmt_note = $pdo->prepare("INSERT INTO tblleads_notes (leadid, staffid, dateadded, description) VALUES (?, ?, NOW(), ?)");
                    $stmt_note->execute([
                        $lead_id,
                        $assigned,
                        '[WhatsApp Automático] Mensagem de boas-vindas enviada para ' . $wa_number
                    ]);
                    log_debug("Nota de WhatsApp registada na lead");
                } else {
                    $wa_error = "Erro HTTP $http_code: $send_json";
                    log_debug("Erro ao enviar WhatsApp", $wa_error);
                }
            } else {
                $wa_error = "Instância WhatsApp $instance_name não está ligada";
                log_debug("Erro WhatsApp", $wa_error);
            }
        } else {
            $wa_error = "Configurações Evolution API não encontradas";
            log_debug("Erro WhatsApp", $wa_error);
        }
    } catch (Exception $e) {
        $wa_error = $e->getMessage();
        log_debug("Excepção ao enviar WhatsApp", $wa_error);
    }
}

// Resposta de sucesso
$response = [
    'status'  => 'success',
    'lead_id' => $lead_id,
    'name'    => $name,
    'email'   => $email,
    'phone'   => $phone,
    'tag'     => 'MV',
    'whatsapp_sent' => $wa_sent
];

if (!$wa_sent && $wa_error) {
    $response['whatsapp_error'] = $wa_error;
}

log_debug("Processo concluído com sucesso", $response);
echo json_encode($response);
