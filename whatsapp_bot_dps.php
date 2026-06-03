<?php
/**
 * DPS Imobiliário — Chatbot WhatsApp
 * Webhook para Evolution API (resposta automática)
 *
 * Evolution API: https://evolution-api-production-22e5.up.railway.app
 * Instância:     staff-1
 * API Key:       dps-evolution-2026
 */

define('EVO_URL',      'https://evolution-api-production-22e5.up.railway.app');
define('EVO_KEY',      'dps-evolution-2026');
define('EVO_INSTANCE', 'staff-1');
define('STATE_DIR',    __DIR__ . '/bot_states/');
define('BOT_LOG',      __DIR__ . '/bot_log.txt');

// ── Inicialização ─────────────────────────────────────────────────────────────
if (!is_dir(STATE_DIR)) mkdir(STATE_DIR, 0755, true);

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

bot_log("IN: " . substr($raw, 0, 500));

// Só processar evento de mensagem recebida
if (($data['event'] ?? '') !== 'messages.upsert') {
    http_response_code(200); echo 'ok'; exit;
}

$msg_data = $data['data'] ?? [];

// Ignorar mensagens enviadas pelo próprio bot
if (!empty($msg_data['key']['fromMe'])) {
    http_response_code(200); echo 'ok'; exit;
}

// Ignorar grupos
$jid = $msg_data['key']['remoteJid'] ?? '';
if (strpos($jid, '@g.us') !== false) {
    http_response_code(200); echo 'ok'; exit;
}

// Extrair número limpo (para estado e CRM)
$phone = preg_replace('/[^0-9]/', '', explode('@', $jid)[0]); // número limpo — para estado e CRM

// Resolver JID para envio:
// Se for @lid (formato novo), converter para @s.whatsapp.net via API
// Se for @s.whatsapp.net, usar directamente
if (strpos($jid, '@lid') !== false && !empty($phone)) {
    $resolve_url = EVO_URL . '/chat/whatsappNumbers/' . EVO_INSTANCE;
    $resolve_ch  = curl_init($resolve_url);
    curl_setopt($resolve_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($resolve_ch, CURLOPT_POST, true);
    curl_setopt($resolve_ch, CURLOPT_POSTFIELDS, json_encode(['numbers' => [$phone]]));
    curl_setopt($resolve_ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'apikey: ' . EVO_KEY]);
    curl_setopt($resolve_ch, CURLOPT_TIMEOUT, 10);
    $resolve_resp = curl_exec($resolve_ch);
    curl_close($resolve_ch);
    $resolve_data = json_decode($resolve_resp, true);
    if (!empty($resolve_data[0]['jid'])) {
        $phone_jid = $resolve_data[0]['jid']; // @s.whatsapp.net resolvido
        bot_log("RESOLVED @lid -> " . $phone_jid);
    } else {
        $phone_jid = $phone . '@s.whatsapp.net'; // fallback
        bot_log("FALLBACK JID: " . $phone_jid);
    }
} else {
    $phone_jid = $jid; // já é @s.whatsapp.net ou outro formato válido
}

$name  = $msg_data['pushName'] ?? 'Cliente';
$text  = '';

if (!empty($msg_data['message']['conversation'])) {
    $text = trim($msg_data['message']['conversation']);
} elseif (!empty($msg_data['message']['extendedTextMessage']['text'])) {
    $text = trim($msg_data['message']['extendedTextMessage']['text']);
} elseif (!empty($msg_data['message']['buttonsResponseMessage']['selectedButtonId'])) {
    $text = trim($msg_data['message']['buttonsResponseMessage']['selectedButtonId']);
} elseif (!empty($msg_data['message']['listResponseMessage']['singleSelectReply']['selectedRowId'])) {
    $text = trim($msg_data['message']['listResponseMessage']['singleSelectReply']['selectedRowId']);
}

if (empty($phone) || $text === '') {
    http_response_code(200); echo 'ok'; exit;
}

bot_log("FROM: $phone | JID: $phone_jid | NAME: $name | TEXT: $text");

// Registar mensagem recebida no CRM
registar_mensagem_crm($phone, $text, 'received');

processar($phone_jid, $phone, $name, $text);

http_response_code(200); echo 'ok'; exit;

// ═════════════════════════════════════════════════════════════════════════════
// LÓGICA DO CHATBOT
// ═════════════════════════════════════════════════════════════════════════════

function processar($jid, $phone, $name, $text)
{
    $input = mb_strtolower(trim($text));

    // Palavras que reiniciam o menu
    $reset = ['menu','inicio','início','olá','ola','oi','bom dia','boa tarde','boa noite','hello','hi','0','voltar'];
    if (in_array($input, $reset)) {
        set_state($phone, 'main');
        menu_principal($jid, $phone, $name);
        return;
    }

    $state = get_state($phone);
    bot_log("STATE: $state");

    switch ($state) {
        case 'main':       handle_main($jid, $phone, $name, $input);       break;
        case 'portugal':   handle_portugal($jid, $phone, $name, $input);   break;
        case 'porto':      handle_porto($jid, $phone, $name, $input);      break;
        case 'laketowers': handle_laketowers($jid, $phone, $name, $input); break;
        case 'setubal':    handle_setubal($jid, $phone, $name, $input);    break;
        case 'brasil':     handle_brasil($jid, $phone, $name, $input);     break;
        case 'itapema':    handle_itapema($jid, $phone, $name, $input);    break;
        case 'cars':       handle_cars($jid, $phone, $name, $input);       break;
        case 'cars_info':  handle_cars_info($jid, $phone, $name, $input);  break;
        case 'credito':    handle_credito($jid, $phone, $name, $input);    break;

        // Todos os estados de horário
        case 'raizes_h':
        case 'lt_inv_h':
        case 'lt_hab_h':
        case 'set_inv_h':
        case 'set_hab_h':
        case 'prontos_h':
        case 'mediacao_h':
        case 'itapema_h':
        case 'portobelo_h':
        case 'dubai_h':
        case 'cred_hab_h':
        case 'cred_transf_h':
        case 'cred_cons_h':
        case 'cars_info_h':
        case 'cars_vender_h':
        case 'contacto_h':
            handle_horario($jid, $phone, $name, $input, $state);
            break;

        default:
            set_state($phone, 'main');
            menu_principal($jid, $phone, $name);
    }
}

// ── Menu Principal ────────────────────────────────────────────────────────────
function menu_principal($jid, $phone, $name)
{
    $msg = "Olá {$name}! 👋 Bem-vindo à *DPS Imobiliário*.\n\n"
         . "Como posso ajudar? Escolha uma opção:\n\n"
         . "1️⃣  Portugal\n"
         . "2️⃣  Brasil\n"
         . "3️⃣  Dubai\n"
         . "4️⃣  DPS Crédito\n"
         . "5️⃣  DPS Cars\n"
         . "6️⃣  Pedir Contacto\n\n"
         . "_Responda com o número da opção._";
    set_state($phone, 'main');
    send($jid, $phone, $msg);
}

function handle_main($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'portugal':
            set_state($phone, 'portugal');
            send($jid, $phone, "🇵🇹 *Portugal*\n\nEscolha a zona:\n\n1️⃣  Porto\n2️⃣  Setúbal\n3️⃣  Prontos para Habitar\n4️⃣  Pedir Mediação / Parceria\n\n_Responda com o número._");
            break;
        case '2': case 'brasil':
            set_state($phone, 'brasil');
            send($jid, $phone, "🇧🇷 *Brasil*\n\nEscolha a zona:\n\n1️⃣  Itapema\n2️⃣  Porto Belo\n\n_Responda com o número._");
            break;
        case '3': case 'dubai':
            set_state($phone, 'dubai_h');
            send($jid, $phone, "🇦🇪 *Dubai*\n\n👉 https://dpsdubai.grupo-dps.com\n\nConheça os nossos projectos em Dubai. Qual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        case '4': case 'credito': case 'crédito':
            set_state($phone, 'credito');
            send($jid, $phone, "🏦 *DPS Crédito*\n\nComo podemos ajudar?\n\n1️⃣  Crédito Habitação\n2️⃣  Transferência de Crédito Habitação\n3️⃣  Crédito Consumo\n\n_Responda com o número._");
            break;
        case '5': case 'cars':
            set_state($phone, 'cars');
            send($jid, $phone, "🚗 *DPS Cars*\n\n👉 https://dpscars.grupo-dps.com\n\nComo podemos ajudar?\n\n1️⃣  Informação sobre uma viatura\n2️⃣  Pretendo vender a minha viatura\n\n_Responda com o número._");
            break;
        case '6': case 'contacto':
            set_state($phone, 'contacto_h');
            send($jid, $phone, "📞 *Pedir Contacto*\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        default:
            menu_principal($jid, $phone, $name);
    }
}

// ── Portugal ──────────────────────────────────────────────────────────────────
function handle_portugal($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'porto':
            set_state($phone, 'porto');
            send($jid, $phone, "🏙️ *Porto*\n\nEscolha o empreendimento:\n\n1️⃣  Lake Towers\n2️⃣  Raízes\n\n_Responda com o número._");
            break;
        case '2': case 'setubal': case 'setúbal':
            set_state($phone, 'setubal');
            send($jid, $phone, "🌊 *Setúbal — Belo Horizonte Residences*\n\n👉 https://dpsimobiliario.pt/belohorizonte\n\nQual o seu objectivo?\n\n1️⃣  Investimento\n2️⃣  Habitação\n\n_Responda com o número._");
            break;
        case '3': case 'prontos':
            set_state($phone, 'prontos_h');
            send($jid, $phone, "🏠 *Prontos para Habitar*\n\n👉 https://dpsimobiliario.pt/imoveis/\n\nVeja todos os imóveis disponíveis. Qual o melhor período para ser contactado?\n\n1️⃣  Manhã\n2️⃣  Tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        case '4': case 'mediacao': case 'mediação': case 'parceria':
            set_state($phone, 'mediacao_h');
            send($jid, $phone, "🤝 *Mediação / Parceria*\n\nObrigado pelo interesse em trabalhar connosco!\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        default:
            send($jid, $phone, "Por favor escolha uma opção válida:\n\n1️⃣  Porto\n2️⃣  Setúbal\n3️⃣  Prontos para Habitar\n4️⃣  Pedir Mediação / Parceria");
    }
}

// ── Porto ─────────────────────────────────────────────────────────────────────
function handle_porto($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'laketowers': case 'lake towers':
            set_state($phone, 'laketowers');
            send($jid, $phone, "🏢 *Lake Towers — Porto*\n\n👉 https://laketowers.grupo-dps.com\n\nQual o seu objectivo?\n\n1️⃣  Investimento\n2️⃣  Habitação\n\n_Responda com o número._");
            break;
        case '2': case 'raizes': case 'raízes':
            set_state($phone, 'raizes_h');
            send($jid, $phone,
                "🌿 *Raízes | 5 minutos do Dragão*\n\n"
              . "👉 https://dpsimobiliario.pt/raizes\n"
              . "📍 https://maps.app.goo.gl/nK3ZeKLbaLQGQrDk7\n\n"
              . "Condomínio fechado com piscina, ginásio, jardins e estacionamento privado, a poucos minutos do Metro e do Porto.\n\n"
              . "💰 *Preços de lançamento:*\n"
              . "• T0 desde 170.000€\n"
              . "• T1 desde 200.000€\n"
              . "• T2 desde 280.000€\n\n"
              . "📋 *Condições de pagamento:*\n"
              . "• 15% no CPCV\n"
              . "• 10% no início de 2027\n"
              . "• Restante na escritura\n\n"
              . "⏳ Conclusão prevista: final de 2028\n\n"
              . "Qual o melhor período para ser contactado?\n\n"
              . "1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n"
              . "_Responda com o número._"
            );
            break;
        default:
            send($jid, $phone, "Por favor escolha:\n\n1️⃣  Lake Towers\n2️⃣  Raízes");
    }
}

// ── Lake Towers ───────────────────────────────────────────────────────────────
function handle_laketowers($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'investimento':
            set_state($phone, 'lt_inv_h');
            send($jid, $phone, "💼 *Lake Towers — Investimento*\n\n👉 https://laketowers.grupo-dps.com\n\nExcelente escolha! O Lake Towers oferece um retorno de investimento muito atractivo no Porto.\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        case '2': case 'habitação': case 'habitacao':
            set_state($phone, 'lt_hab_h');
            send($jid, $phone, "🏠 *Lake Towers — Habitação*\n\n👉 https://laketowers.grupo-dps.com\n👉 https://dpsimobiliario.pt/imoveis\n\nQuanto pretende investir na sua habitação? Indique um valor aproximado ou escolha o período:\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número ou indique o seu orçamento._");
            break;
        default:
            send($jid, $phone, "Por favor escolha:\n\n1️⃣  Investimento\n2️⃣  Habitação\n\n👉 https://laketowers.grupo-dps.com");
    }
}

// ── Setúbal ───────────────────────────────────────────────────────────────────
function handle_setubal($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'investimento':
            set_state($phone, 'set_inv_h');
            send($jid, $phone, "💼 *Belo Horizonte — Investimento*\n\n👉 https://dpsimobiliario.pt/belohorizonte\n\nSetúbal tem um forte potencial de valorização com vista rio e proximidade a Tróia.\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        case '2': case 'habitação': case 'habitacao':
            set_state($phone, 'set_hab_h');
            send($jid, $phone, "🏠 *Belo Horizonte — Habitação*\n\n👉 https://dpsimobiliario.pt/belohorizonte\n👉 https://dpsimobiliario.pt/imoveis\n\nQuanto pretende investir na sua habitação? Indique um valor aproximado ou escolha o período:\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número ou indique o seu orçamento._");
            break;
        default:
            send($jid, $phone, "Por favor escolha:\n\n1️⃣  Investimento\n2️⃣  Habitação\n\n👉 https://dpsimobiliario.pt/belohorizonte");
    }
}

// ── Brasil ────────────────────────────────────────────────────────────────────
function handle_brasil($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'itapema':
            set_state($phone, 'itapema');
            send($jid, $phone, "🌴 *Itapema — Brasil*\n\n👉 https://brasil.grupo-dps.com\n\nQual o seu objectivo?\n\n1️⃣  Investimento\n2️⃣  Habitação\n\n_Responda com o número._");
            break;
        case '2': case 'porto belo': case 'portobelo':
            set_state($phone, 'portobelo_h');
            send($jid, $phone, "⛵ *Porto Belo — Sky Marine*\n\n👉 https://skymarine.grupo-dps.com\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        default:
            send($jid, $phone, "Por favor escolha:\n\n1️⃣  Itapema\n2️⃣  Porto Belo");
    }
}

function handle_itapema($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'investimento':
            set_state($phone, 'itapema_h');
            send($jid, $phone, "💼 *Itapema — Investimento*\n\n👉 https://brasil.grupo-dps.com\n\nItapema é um dos mercados com maior crescimento no Brasil.\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        case '2': case 'habitação': case 'habitacao':
            set_state($phone, 'itapema_h');
            send($jid, $phone, "🏠 *Itapema — Habitação*\n\n👉 https://brasil.grupo-dps.com\n\nViva no paraíso de Itapema com toda a qualidade de vida que merece.\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        default:
            send($jid, $phone, "Por favor escolha:\n\n1️⃣  Investimento\n2️⃣  Habitação\n\n👉 https://brasil.grupo-dps.com");
    }
}

// ── Crédito ───────────────────────────────────────────────────────────────────
function handle_credito($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'habitação': case 'habitacao':
            set_state($phone, 'cred_hab_h');
            send($jid, $phone, "🏦 *Crédito Habitação*\n\nA nossa equipa vai encontrar a melhor solução para si.\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        case '2': case 'transferência': case 'transferencia':
            set_state($phone, 'cred_transf_h');
            send($jid, $phone, "🔄 *Transferência de Crédito Habitação*\n\nPoupe na sua prestação mensal! A nossa equipa analisa a melhor proposta para si.\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        case '3': case 'consumo':
            set_state($phone, 'cred_cons_h');
            send($jid, $phone, "💳 *Crédito Consumo*\n\nTemos as melhores condições de crédito pessoal e consumo.\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        default:
            send($jid, $phone, "Por favor escolha:\n\n1️⃣  Crédito Habitação\n2️⃣  Transferência de Crédito Habitação\n3️⃣  Crédito Consumo");
    }
}

// ── Cars ──────────────────────────────────────────────────────────────────────
function handle_cars($jid, $phone, $name, $input)
{
    switch ($input) {
        case '1': case 'informação': case 'informacao': case 'viatura':
            set_state($phone, 'cars_info');
            send($jid, $phone, "🚗 *DPS Cars — Informação sobre Viatura*\n\n👉 https://dpscars.grupo-dps.com\n\nSobre qual viatura pretende informação?\nIndique o modelo ou referência.\n\n_(Ou escolha directamente o período para ser contactado)_\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato");
            break;
        case '2': case 'vender': case 'venda':
            set_state($phone, 'cars_vender_h');
            send($jid, $phone, "🔑 *DPS Cars — Vender Viatura*\n\n👉 https://dpscars.grupo-dps.com\n\nQual o melhor período para ser contactado para avaliação da sua viatura?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
            break;
        default:
            send($jid, $phone, "Por favor escolha:\n\n1️⃣  Informação sobre uma viatura\n2️⃣  Pretendo vender a minha viatura\n\n👉 https://dpscars.grupo-dps.com");
    }
}

function handle_cars_info($jid, $phone, $name, $input)
{
    if (in_array($input, ['1','2','3','manhã','manha','tarde','imediato'])) {
        handle_horario($jid, $phone, $name, $input, 'cars_info_h');
    } else {
        set_state($phone, 'cars_info_h');
        send($jid, $phone, "✅ Recebido! Vou verificar a disponibilidade da viatura.\n\n👉 https://dpscars.grupo-dps.com\n\nQual o melhor período para ser contactado?\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato\n\n_Responda com o número._");
    }
}

// ── Handler genérico de horário ───────────────────────────────────────────────
function handle_horario($jid, $phone, $name, $input, $state)
{
    $labels = [
        'raizes_h'     => 'Raízes – Porto',
        'lt_inv_h'     => 'Lake Towers – Investimento',
        'lt_hab_h'     => 'Lake Towers – Habitação',
        'set_inv_h'    => 'Belo Horizonte – Investimento',
        'set_hab_h'    => 'Belo Horizonte – Habitação',
        'prontos_h'    => 'Prontos para Habitar',
        'mediacao_h'   => 'Mediação / Parceria',
        'itapema_h'    => 'Itapema – Brasil',
        'portobelo_h'  => 'Porto Belo – Sky Marine',
        'dubai_h'      => 'Dubai',
        'cred_hab_h'   => 'Crédito Habitação',
        'cred_transf_h'=> 'Transferência de Crédito',
        'cred_cons_h'  => 'Crédito Consumo',
        'cars_info_h'  => 'DPS Cars – Informação',
        'cars_vender_h'=> 'DPS Cars – Venda',
        'contacto_h'   => 'Contacto Geral',
    ];
    $label = $labels[$state] ?? 'DPS';

    switch ($input) {
        case '1': case 'manhã': case 'manha':
            confirmar($jid, $phone, $name, $label, 'período da manhã'); break;
        case '2': case 'tarde':
            confirmar($jid, $phone, $name, $label, 'período da tarde'); break;
        case '3': case 'imediato':
            confirmar($jid, $phone, $name, $label, 'imediato'); break;
        default:
            send($jid, $phone, "Por favor escolha o melhor período:\n\n1️⃣  Período da manhã\n2️⃣  Período da tarde\n3️⃣  Imediato");
    }
}

function confirmar($jid, $phone, $name, $label, $horario)
{
    if ($horario === 'imediato') {
        $msg = "✅ *Perfeito, {$name}!*\n\nA nossa equipa vai entrar em contacto *imediatamente*.\n\nObrigado pelo seu interesse — *{$label}*! 🏠\n\n_Escreva *menu* para voltar ao início._";
    } else {
        $msg = "✅ *Perfeito, {$name}!*\n\nA nossa equipa vai entrar em contacto no *{$horario}*.\n\nObrigado pelo seu interesse — *{$label}*! 🏠\n\n_Escreva *menu* para voltar ao início._";
    }
    set_state($phone, 'fim');
    send($jid, $phone, $msg);
}

// ═════════════════════════════════════════════════════════════════════════════
// FUNÇÕES AUXILIARES
// ═════════════════════════════════════════════════════════════════════════════

function send($jid, $phone, $message)
{
    // Registar mensagem enviada pelo bot no CRM
    registar_mensagem_crm($phone, $message, 'sent');

    $url     = EVO_URL . '/message/sendText/' . EVO_INSTANCE;
    $payload = json_encode([
        'number'      => $jid,  // Usar o JID original (@lid ou @s.whatsapp.net)
        'textMessage' => ['text' => $message]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'apikey: ' . EVO_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    bot_log("SEND $jid | HTTP $code | " . substr($resp, 0, 150));
    return $code;
}

function get_state($phone)
{
    $f = STATE_DIR . md5($phone) . '.json';
    if (!file_exists($f)) return 'main';
    $d = json_decode(file_get_contents($f), true);
    // Expirar após 30 minutos de inactividade
    if (!empty($d['ts']) && (time() - $d['ts']) > 1800) {
        unlink($f);
        return 'main';
    }
    return $d['state'] ?? 'main';
}

function set_state($phone, $state)
{
    if (!is_dir(STATE_DIR)) mkdir(STATE_DIR, 0755, true);
    file_put_contents(STATE_DIR . md5($phone) . '.json', json_encode(['state' => $state, 'ts' => time()]));
}

function bot_log($msg)
{
    file_put_contents(BOT_LOG, date('Y-m-d H:i:s') . ' | ' . $msg . "\n", FILE_APPEND | LOCK_EX);
}

function registar_mensagem_crm($phone, $message, $direction = 'received')
{
    $crm_url = 'https://crm.grupo-dps.com/whatsapp-log-message.php';

    $payload = json_encode([
        'phone'     => $phone,
        'message'   => $message,
        'direction' => $direction,
        'secret'    => 'dps-webhook-secret-2026'
    ]);

    $ch = curl_init($crm_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_exec($ch);
    curl_close($ch);
}
