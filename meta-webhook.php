<?php
/**
 * Meta Lead Ads → Perfex CRM Webhook
 * 
 * Integração directa sem Make/Zapier.
 * Recebe eventos de novas leads da Meta e cria-as no Perfex CRM.
 * 
 * URL pública: https://crm.grupo-dps.com/meta-webhook.php
 * 
 * Configuração no Meta:
 *   Callback URL: https://crm.grupo-dps.com/meta-webhook.php
 *   Verify Token: dps-meta-webhook-2026
 *   Subscriptions: leadgen
 */

// ─── CONFIGURAÇÃO ────────────────────────────────────────────────────────────

// Token de verificação (definido na Meta ao registar o webhook)
define('VERIFY_TOKEN', 'dps-meta-webhook-2026');

// App Secret da Meta (para validar assinatura dos payloads)
// Preencher após criar a Meta App em developers.facebook.com
define('META_APP_SECRET', 'eca5f81d64c53aed0ff527bd1b5682d3');

// Token de acesso da página (Page Access Token) para chamar a Graph API
// Obter em Meta for Developers > Tools > Graph API Explorer
define('META_PAGE_ACCESS_TOKEN', 'EAAMrr8bw7ZCUBRGSCm6jzJkrL4ISvFCPZB1QpkXaqOWZBTU61ndKoE7LHWYmVAflAWZBzBaRrz5H9suiEEv8TcVpet0KXGXiOQKhuGhyQ3K0ViZBFii4ijlO8rlglsWgnDZA03SZBoO5YLxRZABbcA97yZACfEuOvMyTkapJZB3XSnaQtUXkLsmZCER17EY42R6JeIqweipx3EKkd12tCloscGdMjSx');

// URL base do Perfex CRM
define('PERFEX_URL', 'https://crm.grupo-dps.com');

// Token de autenticação da API do Perfex
define('PERFEX_API_TOKEN', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoiUmljYXJkbyBNYWdhbGhhZXMiLCJuYW1lIjoiV0VCSE9PSyBNRVRBIiwiQVBJX1RJTUUiOjE3NzYwMDIxODR9.g9wEzwauRwyVBh8W7OPPi5ls5Y84DXDcPkxcNEWkfOw');

// Mapeamento: form_id → configuração do comercial
// Adicionar mais entradas para outros comerciais/formulários
define('FORM_CONFIG', [
    // Ricardo Magalhães - Formulário Qualificado Portugal
    '1862490407784664' => [
        'assigned'   => 1,                                  // staff_id do Ricardo no Perfex
        'email'      => 'ricardomagalhaes@grupo-dps.com',
        'name'       => 'Ricardo Magalhães',
        'status'     => 4,                                  // estado "Novos"
        'source'     => 2,                                  // fonte "Facebook/Meta"
    ],
    // Adicionar outros formulários aqui:
    // 'FORM_ID_CLAUDIO' => [
    //     'assigned' => 2,
    //     'email'    => 'claudio@grupo-dps.com',
    //     'name'     => 'Cláudio Carvalho',
    //     'status'   => 4,
    //     'source'   => 2,
    // ],
]);

// Ficheiro de log (relativo ao directório do CRM)
define('LOG_FILE', __DIR__ . '/application/logs/meta_webhook.log');

// ─── FUNÇÕES AUXILIARES ───────────────────────────────────────────────────────

function log_msg($msg) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents(LOG_FILE, $line, FILE_APPEND);
}

function http_post($url, $data, $headers = []) {
    $post_body = http_build_query($data);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    // Garantir Content-Type correcto para a API do Perfex
    $default_headers = ['Content-Type: application/x-www-form-urlencoded'];
    $all_headers = array_merge($default_headers, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $all_headers);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err       = curl_error($ch);
    curl_close($ch);
    return ['body' => $response, 'code' => $http_code, 'error' => $err];
}

function http_get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err       = curl_error($ch);
    curl_close($ch);
    return ['body' => $response, 'code' => $http_code, 'error' => $err];
}

/**
 * Obtém os dados completos de uma lead via Meta Graph API
 * Tenta múltiplos endpoints para contornar limitações de permissões
 */
function get_lead_data($leadgen_id) {
    $token = META_PAGE_ACCESS_TOKEN;
    if (empty($token)) {
        log_msg("ERRO: META_PAGE_ACCESS_TOKEN não configurado");
        return null;
    }

    // Endpoint 1: campo field_data directo (requer leads_retrieval)
    $url = "https://graph.facebook.com/v19.0/{$leadgen_id}?fields=field_data,form_id,ad_id,ad_name,created_time&access_token={$token}";
    $res = http_get($url);
    if (!$res['error'] && $res['code'] === 200) {
        $data = json_decode($res['body'], true);
        if (!empty($data) && !isset($data['error'])) {
            log_msg("Lead obtida via endpoint principal: {$leadgen_id}");
            return $data;
        }
    }
    log_msg("Endpoint principal falhou para {$leadgen_id}: HTTP {$res['code']} - {$res['body']}");

    // Endpoint 2: acesso via página (requer pages_manage_ads)
    $page_id = '294945163693663';
    $url2 = "https://graph.facebook.com/v19.0/{$page_id}/leadgen_forms/{$leadgen_id}?access_token={$token}";
    $res2 = http_get($url2);
    if (!$res2['error'] && $res2['code'] === 200) {
        $data2 = json_decode($res2['body'], true);
        if (!empty($data2) && !isset($data2['error'])) {
            log_msg("Lead obtida via endpoint página: {$leadgen_id}");
            return $data2;
        }
    }
    log_msg("Endpoint página falhou para {$leadgen_id}: HTTP {$res2['code']} - {$res2['body']}");

    return null;
}

/**
 * Cria uma lead no Perfex CRM via API
 */
function create_perfex_lead($fields) {
    $url = PERFEX_URL . '/api/leads';
    $res = http_post($url, $fields, [
        'authtoken: ' . PERFEX_API_TOKEN,
    ]);
    if ($res['error'] || $res['code'] < 200 || $res['code'] >= 300) {
        log_msg("ERRO ao criar lead no Perfex: HTTP {$res['code']} - {$res['error']} - {$res['body']}");
        return false;
    }
    $decoded = json_decode($res['body'], true);
    log_msg("Lead criada no Perfex: HTTP {$res['code']} - " . json_encode($decoded));
    return $decoded;
}

/**
 * Envia email de notificação ao comercial
 */
function send_notification_email($to_email, $to_name, $lead_data) {
    $name      = $lead_data['name']        ?? 'N/D';
    $email     = $lead_data['email']       ?? 'N/D';
    $phone     = $lead_data['phonenumber'] ?? 'N/D';
    $interesse = $lead_data['Interesse']   ?? 'N/D';
    $wa_link   = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $phone);

    $subject = 'NOVA LEAD';
    $body    = "Nova lead na área! 🚀\r\n\r\n"
             . "Este cliente escolheu falar <strong>consigo</strong> — seja rápido, ligue agora e transforme este interesse em negócio.\r\n\r\n"
             . "Você nasceu para fechar esta! 💪\r\n\r\n"
             . "Nome: {$name}\r\n"
             . "Email: {$email}\r\n"
             . "Telefone: {$phone}\r\n"
             . "Interesse: {$interesse}\r\n"
             . "Data/hora: " . date('Y-m-d H:i:s') . "\r\n\r\n"
             . $wa_link . "\r\n\r\n"
             . "Grupo DPS Imobiliário";

    $headers = "From: noreply@grupo-dps.com\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n";

    $result = @mail($to_email, $subject, nl2br($body), $headers);
    log_msg("Email de notificação para {$to_email}: " . ($result ? 'OK' : 'FALHOU'));
    return $result;
}

// ─── PROCESSAMENTO DO PEDIDO ──────────────────────────────────────────────────

// Verificação do webhook (GET) — Meta valida o endpoint antes de activar
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode']          ?? '';
    $token     = $_GET['hub_verify_token']  ?? '';
    $challenge = $_GET['hub_challenge']     ?? '';

    if ($mode === 'subscribe' && $token === VERIFY_TOKEN) {
        log_msg("Webhook verificado com sucesso pela Meta");
        http_response_code(200);
        echo $challenge;
    } else {
        log_msg("Verificação do webhook falhou: mode={$mode}, token={$token}");
        http_response_code(403);
        echo 'Forbidden';
    }
    exit;
}

// Recepção de eventos (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_body = file_get_contents('php://input');

    // Validar assinatura da Meta (se APP_SECRET estiver configurado)
    if (!empty(META_APP_SECRET)) {
        $sig_header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
        $expected   = 'sha256=' . hash_hmac('sha256', $raw_body, META_APP_SECRET);
        if (!hash_equals($expected, $sig_header)) {
            log_msg("Assinatura inválida: {$sig_header}");
            http_response_code(403);
            echo 'Invalid signature';
            exit;
        }
    }

    $payload = json_decode($raw_body, true);
    if (empty($payload)) {
        log_msg("Payload inválido: {$raw_body}");
        http_response_code(400);
        echo 'Bad Request';
        exit;
    }

    log_msg("Payload recebido: " . json_encode($payload));

    // Responder imediatamente à Meta (obrigatório em <5s)
    http_response_code(200);
    echo 'OK';

    // Processar os eventos
    if (($payload['object'] ?? '') === 'page') {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'leadgen') continue;

                $leadgen_id = $change['value']['leadgen_id'] ?? null;
                $form_id    = (string)($change['value']['form_id'] ?? '');
                $page_id    = (string)($change['value']['page_id'] ?? '');

                log_msg("Nova lead: leadgen_id={$leadgen_id}, form_id={$form_id}, page_id={$page_id}");

                if (empty($leadgen_id)) {
                    log_msg("ERRO: leadgen_id em falta no payload");
                    continue;
                }

                // Identificar configuração pelo form_id
                $form_configs = FORM_CONFIG;
                $config = $form_configs[$form_id] ?? null;

                if (empty($config)) {
                    log_msg("AVISO: form_id {$form_id} não configurado. A usar configuração padrão (Ricardo).");
                    // Fallback: usar o primeiro formulário configurado
                    $config = reset($form_configs);
                }

                // Obter dados completos da lead via Graph API
                $lead_raw = get_lead_data($leadgen_id);

                if (empty($lead_raw)) {
                    log_msg("ERRO: não foi possível obter dados da lead {$leadgen_id}");
                    continue;
                }

                // Extrair campos do formulário
                $field_map = [];
                foreach ($lead_raw['field_data'] ?? [] as $field) {
                    $key   = strtolower(trim($field['name'] ?? ''));
                    $value = implode(', ', (array)($field['values'] ?? []));
                    $field_map[$key] = $value;
                }

                log_msg("Campos da lead: " . json_encode($field_map));

                // Mapear campos Meta → Perfex
                $name      = $field_map['full name']   ?? $field_map['first_name'] ?? $field_map['nome'] ?? '';
                $email     = $field_map['e-mail']      ?? $field_map['email']      ?? '';
                $phone     = $field_map['phone']       ?? $field_map['phone_number'] ?? $field_map['telefone'] ?? '';
                $interesse = '';
                // Campo de interesse (nome pode variar)
                foreach ($field_map as $k => $v) {
                    if (strpos($k, 'interesse') !== false || strpos($k, 'investimento') !== false) {
                        $interesse = $v;
                        break;
                    }
                }

                // Limpar número de telefone (remover +, espaços, traços)
                $phone_clean = preg_replace('/[^0-9]/', '', $phone);

                // Criar lead no Perfex
                $perfex_fields = [
                    'name'        => $name,
                    'email'       => $email,
                    'phonenumber' => $phone_clean ?: $phone,
                    'source'      => $config['source'],
                    'status'      => $config['status'],
                    'assigned'    => $config['assigned'],
                    'Interesse'   => $interesse,
                ];

                $result = create_perfex_lead($perfex_fields);

                if ($result) {
                    log_msg("Lead criada com sucesso para form_id={$form_id}, assigned={$config['assigned']}");

                    // Enviar email de notificação
                    send_notification_email(
                        $config['email'],
                        $config['name'],
                        array_merge($perfex_fields, ['Interesse' => $interesse])
                    );
                } else {
                    log_msg("ERRO: falha ao criar lead no Perfex para leadgen_id={$leadgen_id}");
                }
            }
        }
    }

    exit;
}

// Método não suportado
http_response_code(405);
echo 'Method Not Allowed';
