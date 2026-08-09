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

/**
 * Segredo do webhook, para validar a assinatura da ElevenLabs.
 *
 * Fica FORA do docroot de propósito: o deploy por FTP sincroniza o docroot e já
 * apagou a pasta dps_secure/ mais do que uma vez, deixando endpoints a
 * responder 503 sem ninguém perceber porquê.
 */
function sofia_segredo_webhook() {
    $ambiente = getenv('SOFIA_WEBHOOK_SECRET');
    if (is_string($ambiente) && trim($ambiente) !== '') {
        return trim($ambiente);
    }

    foreach ([
        '/home/u172337921/.sofia_webhook_secret',
        __DIR__ . '/dps_secure/sofia_webhook_secret',
    ] as $ficheiro) {
        if (is_readable($ficheiro)) {
            $valor = trim((string) file_get_contents($ficheiro));
            if ($valor !== '') {
                return $valor;
            }
        }
    }

    return '';
}

/**
 * A ElevenLabs assina o corpo com HMAC-SHA256 e envia
 * `ElevenLabs-Signature: t=<timestamp>,v0=<hash>`, onde o hash cobre
 * "<timestamp>.<corpo bruto>".
 */
function sofia_assinatura_valida($raw_body, $segredo) {
    $cabecalho = $_SERVER['HTTP_ELEVENLABS_SIGNATURE'] ?? ($_SERVER['HTTP_X_ELEVENLABS_SIGNATURE'] ?? '');

    if ($cabecalho === '') {
        return false;
    }

    $timestamp = '';
    $recebido  = '';
    foreach (explode(',', $cabecalho) as $parte) {
        $parte = trim($parte);
        if (strpos($parte, 't=') === 0) {
            $timestamp = substr($parte, 2);
        } elseif (strpos($parte, 'v0=') === 0) {
            $recebido = substr($parte, 3);
        }
    }

    if ($timestamp === '' || $recebido === '') {
        return false;
    }

    // Janela de 30 minutos: sem isto, quem apanhasse um pedido antigo podia
    // reenviá-lo indefinidamente e continuar a passar na validação.
    if (abs(time() - (int) $timestamp) > 1800) {
        log_msg('Assinatura fora da janela de tempo (t=' . $timestamp . ')');

        return false;
    }

    $esperado = hash_hmac('sha256', $timestamp . '.' . $raw_body, $segredo);

    return hash_equals($esperado, $recebido);
}

/**
 * Cria a tarefa de seguimento da chamada, atribuída ao comercial dono da lead.
 *
 * Só nasce tarefa quando houve conversa a sério (há transcrição). Uma chamada
 * que caiu no voicemail não é trabalho para ninguém, e criar tarefa para todas
 * enchia a lista de coisas para fechar sem ler — que é a forma mais rápida de a
 * equipa deixar de olhar para a lista.
 *
 * As colunas da tabela de tarefas são lidas em tempo real e só se escreve nas
 * que existirem: este script corre fora do CodeIgniter, sem os modelos do
 * Perfex, e uma coluna a mais ou a menos entre versões daria erro de SQL em
 * produção. Qualquer falha aqui é registada e engolida — a nota, que é o que já
 * funcionava, nunca pode deixar de ser gravada por causa disto.
 */
function sofia_criar_tarefa_seguimento($conn, $db_prefix, $lead, $transcript_text, $success_label, $conversation_id, $now) {
    if (trim($transcript_text) === '') {
        log_msg('Sem transcrição: não foi criada tarefa.');

        return null;
    }

    $colunas_existentes = [];
    if ($res = $conn->query("SHOW COLUMNS FROM {$db_prefix}tasks")) {
        while ($linha = $res->fetch_assoc()) {
            $colunas_existentes[$linha['Field']] = true;
        }
        $res->free();
    }

    if (empty($colunas_existentes)) {
        log_msg('ERRO: não consegui ler as colunas de ' . $db_prefix . 'tasks; tarefa não criada.');

        return null;
    }

    $responsavel = (int) ($lead['assigned'] ?? 0);
    if ($responsavel <= 0) {
        $responsavel = 1; // sem comercial atribuído, fica para o administrador
    }

    $descricao = "Chamada automática da Sofia concluída ($success_label).\n\n"
               . "Ouvir/ler a transcrição na ficha da lead e dar seguimento.\n\n"
               . "ID da conversa: $conversation_id\n\n"
               . "--- Transcrição ---\n" . $transcript_text;

    $valores = [
        'name'                  => 'Seguimento da chamada da Sofia — ' . $lead['name'],
        'description'           => $descricao,
        'priority'              => 2,
        'dateadded'             => $now,
        'startdate'             => date('Y-m-d'),
        'duedate'               => date('Y-m-d', strtotime('+1 day')),
        'status'                => 1,
        'addedfrom'             => $responsavel,
        'rel_id'                => (int) $lead['id'],
        'rel_type'              => 'lead',
        'is_added_from_contact' => 0,
        'is_public'             => 1,
        'billable'              => 0,
        'recurring'             => 0,
    ];

    $campos = [];
    $dados  = [];
    foreach ($valores as $coluna => $valor) {
        if (!isset($colunas_existentes[$coluna])) {
            continue;
        }
        $campos[] = "`$coluna`";
        $dados[]  = is_int($valor) ? (string) $valor : "'" . $conn->real_escape_string((string) $valor) . "'";
    }

    if (empty($campos)) {
        return null;
    }

    $sql = "INSERT INTO {$db_prefix}tasks (" . implode(', ', $campos) . ') VALUES (' . implode(', ', $dados) . ')';

    if (!$conn->query($sql)) {
        log_msg('ERRO ao criar tarefa: ' . $conn->error);

        return null;
    }

    $task_id = $conn->insert_id;

    // A atribuição vive numa tabela à parte; sem ela a tarefa existe mas não
    // aparece na lista de ninguém.
    $stmt = $conn->prepare("INSERT INTO {$db_prefix}task_assigned (staffid, taskid, assigned_from) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param('iii', $responsavel, $task_id, $responsavel);
        if (!$stmt->execute()) {
            log_msg('AVISO: tarefa ' . $task_id . ' criada mas não atribuída: ' . $stmt->error);
        }
        $stmt->close();
    }

    log_msg("Tarefa $task_id criada para a lead {$lead['id']}, atribuída ao staff $responsavel.");

    return $task_id;
}

// Ler o payload
$raw_body = file_get_contents('php://input');
$payload = json_decode($raw_body, true);

log_msg("Webhook recebido: " . substr($raw_body, 0, 500));

/*
 * Validação da assinatura.
 *
 * Enquanto não houver segredo configurado, o pedido passa — mas com aviso no
 * log. É deliberado: recusar tudo por omissão deixaria este endpoint em baixo
 * no momento exacto em que se está a tentar pôr as chamadas a entrar, e o
 * primeiro sintoma seria "continua a não funcionar" sem pista nenhuma. Assim
 * que o ficheiro do segredo existir, a validação passa a ser obrigatória.
 *
 * ATENÇÃO: sem segredo, qualquer pessoa que descubra este URL consegue inserir
 * notas e tarefas em qualquer lead. Configurar isto não é opcional a prazo.
 */
$segredo = sofia_segredo_webhook();
if ($segredo === '') {
    log_msg('AVISO: sem segredo configurado — assinatura NAO verificada. Ver sofia_segredo_webhook().');
} elseif (!sofia_assinatura_valida($raw_body, $segredo)) {
    log_msg('REJEITADO: assinatura invalida.');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Assinatura inválida']);
    exit;
}

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

$sql = "SELECT id, name, email, assigned FROM {$db_prefix}leads WHERE
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

    $task_id = sofia_criar_tarefa_seguimento($conn, $db_prefix, $lead, $transcript_text, $success_label, $conversation_id, $now);

    $conn->close();
    echo json_encode([
        'success' => true,
        'message' => 'Nota adicionada com sucesso',
        'lead_id' => $lead_id,
        'lead_name' => $lead['name'],
        'note_id' => $note_id,
        'task_id' => $task_id
    ]);
} else {
    log_msg("ERRO ao adicionar nota: " . $conn->error);
    $conn->close();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao adicionar nota: ' . $conn->error]);
}
