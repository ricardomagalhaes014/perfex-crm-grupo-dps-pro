<?php
/**
 * Recebe o "Enviar Proposta ao Cliente" vindo do simulador e entrega o PDF
 * ao lead por WhatsApp, pela instância do próprio comercial (staff-<id>).
 *
 * Contrato (POST JSON, definido pelo bloco DPS_PROPOSTA_INJECT do simulador):
 *   { lead_id, staff_id, token, empreendimento, unidade, file_name, pdf_base64 }
 * Resposta esperada pelo simulador:
 *   { "success": true|false, "message": "..." }
 *
 * Autenticação: HMAC-SHA256 de "<lead_id>|<staff_id>|<YYYY-MM-DD>" com o
 * segredo em /home/u172337921/.dps_proposta_secret (fora do docroot, para o
 * deploy não lhe tocar). Aceita hoje e ontem, para não falhar à meia-noite
 * nem com desvio de fuso.
 */

declare(strict_types=1);

const CAMINHO_SEGREDO = '/home/u172337921/.dps_proposta_secret';
const CONFIG_APP      = __DIR__ . '/application/config/app-config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://dpsimobiliario.pt');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function responder(bool $ok, string $msg, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode(['success' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

// Nunca devolver um erro cru de PHP: o simulador só sabe ler JSON.
set_exception_handler(static function (Throwable $e): void {
    error_log('dps_proposta_receber: ' . $e->getMessage());
    responder(false, 'Erro interno ao processar a proposta.', 500);
});

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder(false, 'Método inválido.', 405);
}

$dados = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($dados)) {
    responder(false, 'Pedido inválido.', 400);
}

$lead_id        = (int) ($dados['lead_id'] ?? 0);
$staff_id       = (int) ($dados['staff_id'] ?? 0);
$token          = trim((string) ($dados['token'] ?? ''));
$empreendimento = trim((string) ($dados['empreendimento'] ?? ''));
$unidade        = trim((string) ($dados['unidade'] ?? ''));
$file_name      = trim((string) ($dados['file_name'] ?? 'Proposta.pdf'));
$pdf_base64     = (string) ($dados['pdf_base64'] ?? '');

if ($lead_id <= 0 || $staff_id <= 0 || $token === '' || $pdf_base64 === '') {
    responder(false, 'Faltam dados obrigatórios.', 400);
}

/* ---------------------------------------------------------------------
 * Autenticação
 * ------------------------------------------------------------------ */

$segredo = @file_get_contents(CAMINHO_SEGREDO);
$segredo = $segredo !== false ? trim($segredo) : '';

if ($segredo === '') {
    error_log('dps_proposta_receber: segredo em falta (' . CAMINHO_SEGREDO . ')');
    responder(false, 'Envio indisponível: configuração do servidor em falta. Avise o administrador.', 503);
}

$assinatura_valida = false;
foreach ([date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))] as $dia) {
    $esperado = hash_hmac('sha256', $lead_id . '|' . $staff_id . '|' . $dia, $segredo);
    if (hash_equals($esperado, $token)) {
        $assinatura_valida = true;
        break;
    }
}

if (!$assinatura_valida) {
    responder(false, 'Sessão expirada. Volte ao CRM e abra a proposta de novo.', 403);
}

/* ---------------------------------------------------------------------
 * Base de dados (credenciais do app-config.php do Perfex)
 * ------------------------------------------------------------------ */

function ligar_bd(): mysqli
{
    $conteudo = @file_get_contents(CONFIG_APP);
    if ($conteudo === false) {
        throw new RuntimeException('app-config.php ilegível');
    }

    $ler = static function (string $constante) use ($conteudo): string {
        if (preg_match("/define\(\s*'" . $constante . "'\s*,\s*'([^']*)'\s*\)/", $conteudo, $m)) {
            return $m[1];
        }
        return '';
    };

    $host = $ler('APP_DB_HOSTNAME') ?: 'localhost';
    $user = $ler('APP_DB_USERNAME');
    $pass = $ler('APP_DB_PASSWORD');
    $nome = $ler('APP_DB_NAME');

    if ($user === '' || $nome === '') {
        throw new RuntimeException('credenciais de BD incompletas');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $bd = new mysqli($host, $user, $pass, $nome);
    $bd->set_charset('utf8mb4');

    return $bd;
}

$bd = ligar_bd();

// Prefixo das tabelas (o Perfex usa 'tbl' mas lemos do config para não fixar)
$conf_prefixo = @file_get_contents(CONFIG_APP);
$prefixo = 'tbl';
if ($conf_prefixo !== false && preg_match("/define\(\s*'APP_DB_PREFIX'\s*,\s*'([^']*)'\s*\)/", $conf_prefixo, $m)) {
    $prefixo = $m[1];
}

/* ---------------------------------------------------------------------
 * Lead: número de telefone de destino
 * ------------------------------------------------------------------ */

$stmt = $bd->prepare('SELECT name, phonenumber, status FROM `' . $prefixo . 'leads` WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $lead_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();

if (!$lead) {
    responder(false, 'Lead não encontrada.', 404);
}

$numero = preg_replace('/\D/', '', (string) $lead['phonenumber']);
if ($numero === '') {
    responder(false, 'Esta lead não tem número de telefone — não é possível enviar por WhatsApp.');
}
// Sem indicativo assume-se Portugal (a Evolution exige o número internacional)
if (strlen($numero) === 9 && $numero[0] === '9') {
    $numero = '351' . $numero;
}

/* ---------------------------------------------------------------------
 * Evolution API (mesmas opções que o módulo dps_whatsapp usa)
 * ------------------------------------------------------------------ */

function opcao(mysqli $bd, string $prefixo, string $nome): string
{
    $stmt = $bd->prepare('SELECT value FROM `' . $prefixo . 'options` WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $nome);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();

    return $r ? (string) $r['value'] : '';
}

$evo_url = rtrim(opcao($bd, $prefixo, 'dps_whatsapp_evolution_url'), '/');
$evo_key = opcao($bd, $prefixo, 'dps_whatsapp_evolution_api_key');

if ($evo_url === '' || $evo_key === '') {
    responder(false, 'WhatsApp não está configurado no CRM. Avise o administrador.', 503);
}

/*
 * O jsPDF devolve "data:application/pdf;filename=generated.pdf;base64,XXXX".
 * A Evolution só aceita o base64 puro — e o strip ingénuo por
 * `^data:[^;]+;base64,` falha por causa do `;filename=`. Cortar até "base64,".
 */
$pos = strpos($pdf_base64, 'base64,');
if ($pos !== false) {
    $pdf_base64 = substr($pdf_base64, $pos + 7);
}
$pdf_base64 = trim($pdf_base64);

if ($pdf_base64 === '' || base64_decode($pdf_base64, true) === false) {
    responder(false, 'O PDF da proposta chegou inválido. Gere a proposta de novo.', 400);
}

if (!preg_match('/\.pdf$/i', $file_name)) {
    $file_name .= '.pdf';
}

$legenda = 'Proposta' . ($empreendimento !== '' ? ' — ' . $empreendimento : '')
    . ($unidade !== '' ? ' — Fracção ' . $unidade : '');

$corpo = json_encode([
    'number'       => $numero,
    'mediaMessage' => [
        'mediatype' => 'document',
        'fileName'  => $file_name,
        'media'     => $pdf_base64,
        'caption'   => $legenda,
    ],
]);

// UMA só tentativa: um 500 transitório pode já ter entregue a mensagem, e
// repetir criaria duplicados no WhatsApp do cliente.
$ch = curl_init($evo_url . '/message/sendMedia/staff-' . $staff_id);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $evo_key],
    CURLOPT_POSTFIELDS     => $corpo,
]);
$raw  = (string) curl_exec($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$wa_ok = ($http >= 200 && $http < 300);

/* ---------------------------------------------------------------------
 * Registo em "Propostas Enviadas" (mesmo com falha, para haver rasto)
 * ------------------------------------------------------------------ */

$estado_nome = '';
if (!empty($lead['status'])) {
    $stmt = $bd->prepare('SELECT name FROM `' . $prefixo . 'leads_status` WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $lead['status']);
    $stmt->execute();
    $st = $stmt->get_result()->fetch_assoc();
    $estado_nome = $st ? (string) $st['name'] : '';
}

$agora   = date('Y-m-d H:i:s');
$detalhe = 'HTTP ' . $http . ' ' . substr($raw, 0, 400);
$wa_int  = $wa_ok ? 1 : 0;
$est_id  = (int) ($lead['status'] ?? 0);

$stmt = $bd->prepare(
    'INSERT INTO `' . $prefixo . "dps_propostas`
        (lead_id, staff_id, tipo, empreendimento, unidade, lead_status_id, lead_status_nome,
         ficheiro, detalhe, wa_ok, outcome, created_at)
     VALUES (?, ?, 'proposta', ?, ?, ?, ?, ?, ?, ?, 'pendente', ?)"
);
$stmt->bind_param(
    'iississsis',
    $lead_id, $staff_id, $empreendimento, $unidade, $est_id, $estado_nome,
    $file_name, $detalhe, $wa_int, $agora
);
@$stmt->execute();

/* ---------------------------------------------------------------------
 * Efeitos na lead (só quando o envio correu bem)
 *   1. Nota + registo de atividade "Proposta enviada"
 *   2. Estado passa a VIP 1
 * Replica o que o módulo dps_propostas faz em enviar_info(), para o
 * comportamento ser o mesmo que era antes.
 * ------------------------------------------------------------------ */

if ($wa_ok) {
    $desc = '📄 Proposta enviada ao cliente'
        . ($empreendimento !== '' ? ' — ' . $empreendimento : '')
        . ($unidade !== '' ? ' — Fracção ' . $unidade : '');

    // Nome do comercial, para a nota/atividade ficarem atribuídas
    $nome_staff = '';
    $stmt = $bd->prepare('SELECT CONCAT(firstname, " ", lastname) AS n FROM `' . $prefixo . 'staff` WHERE staffid = ? LIMIT 1');
    $stmt->bind_param('i', $staff_id);
    $stmt->execute();
    $s = $stmt->get_result()->fetch_assoc();
    $nome_staff = $s ? trim((string) $s['n']) : '';

    // 1a. Nota na lead (é o que aparece na coluna "Notes" da tabela de leads)
    $stmt = $bd->prepare(
        'INSERT INTO `' . $prefixo . "notes` (rel_id, rel_type, description, addedfrom, dateadded)
         VALUES (?, 'lead', ?, ?, ?)"
    );
    $stmt->bind_param('isis', $lead_id, $desc, $staff_id, $agora);
    @$stmt->execute();

    // 1b. Registo de atividade + último contacto
    $stmt = $bd->prepare('UPDATE `' . $prefixo . 'leads` SET lastcontact = ? WHERE id = ?');
    $stmt->bind_param('si', $agora, $lead_id);
    @$stmt->execute();

    $stmt = $bd->prepare(
        'INSERT INTO `' . $prefixo . "lead_activity_log` (leadid, description, date, staffid, full_name, additional_data)
         VALUES (?, ?, ?, ?, ?, '')"
    );
    $stmt->bind_param('issis', $lead_id, $desc, $agora, $staff_id, $nome_staff);
    @$stmt->execute();

    /*
     * 2. Estado → VIP 1.
     * Resolvido por NOME e não por id fixo: os estados já foram renomeados
     * uma vez (VIP PORTO → VIP 1) e um id à mão partiria em silêncio.
     * Preferimos o que tem "1"/"UM"/"PORTO"; se não houver, o primeiro VIP
     * pela ordem definida no CRM.
     */
    $vip_row = null;
    $res_vip = $bd->query(
        "SELECT id, name FROM `" . $prefixo . "leads_status`
         WHERE UPPER(name) LIKE '%VIP%' ORDER BY statusorder, id"
    );
    if ($res_vip) {
        $candidatos = [];
        while ($linha = $res_vip->fetch_assoc()) {
            $candidatos[] = $linha;
        }
        foreach ($candidatos as $linha) {
            $n = strtoupper((string) $linha['name']);
            if (strpos($n, '1') !== false || strpos($n, 'UM') !== false || strpos($n, 'PORTO') !== false) {
                $vip_row = $linha;
                break;
            }
        }
        if (!$vip_row && !empty($candidatos)) {
            $vip_row = $candidatos[0];
        }
    }

    if ($vip_row && (int) $lead['status'] !== (int) $vip_row['id']) {
        $novo_id = (int) $vip_row['id'];
        $stmt = $bd->prepare('UPDATE `' . $prefixo . 'leads` SET status = ?, last_status_change = ? WHERE id = ?');
        $stmt->bind_param('isi', $novo_id, $agora, $lead_id);
        @$stmt->execute();

        $desc_estado = 'Estado alterado para ' . $vip_row['name'] . ' (proposta enviada)';
        $stmt = $bd->prepare(
            'INSERT INTO `' . $prefixo . "lead_activity_log` (leadid, description, date, staffid, full_name, additional_data)
             VALUES (?, ?, ?, ?, ?, '')"
        );
        $stmt->bind_param('issis', $lead_id, $desc_estado, $agora, $staff_id, $nome_staff);
        @$stmt->execute();
    }
}

if (!$wa_ok) {
    // Traduzir os erros mais comuns da Evolution para linguagem do comercial.
    if (strpos($raw, '"exists":false') !== false) {
        responder(false, 'O número ' . $numero . ' não tem WhatsApp — não é possível enviar por aqui.');
    }
    if (strpos($raw, 'No sessions') !== false || strpos($raw, 'does not exist') !== false) {
        responder(false, 'O teu WhatsApp precisa de reconectar (lê o QR no módulo de WhatsApp).');
    }
    if ($http === 0 || $http >= 500) {
        responder(false, 'A Evolution não respondeu neste momento — tenta de novo daqui a instantes.');
    }
    responder(false, 'Falha no envio pelo WhatsApp (HTTP ' . $http . ').');
}

responder(true, 'Proposta enviada ao cliente por WhatsApp.');
