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

/*
 * Este script corre FORA do CodeIgniter, por isso não herda o fuso horário
 * configurado no CRM e ficava em UTC — as propostas apareciam com menos uma
 * hora do que a realidade. Alinhar com o horário de Portugal continental.
 */
date_default_timezone_set('Europe/Lisbon');

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
/**
 * Texto de apresentação do empreendimento — a MESMA fonte que o módulo usa.
 *
 * Este ficheiro é autónomo (não corre dentro do CodeIgniter), mas o helper do
 * módulo é só um conjunto de funções: define-se a constante que ele exige e
 * inclui-se. Copiar os textos para aqui daria duas versões a divergir no dia
 * em que a direcção mudasse uma linha — foi assim que os nomes dos
 * empreendimentos acabaram escritos de três maneiras.
 *
 * Sem helper acessível devolve-se vazio: sem texto é melhor do que não enviar.
 */
function apresentacao_do_empreendimento(string $empreendimento): string
{
    $n = mb_strtolower(trim($empreendimento));
    $n = strtr($n, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e',
                    'í'=>'i','ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ç'=>'c']);

    $chave = null;
    foreach ([
        'boavista'  => 'boavista',
        'horizonte' => 'belohorizonte', 'belo' => 'belohorizonte', 'bh' => 'belohorizonte',
        'aura'      => 'aura',
        'douro'     => 'gaiadouro', 'gaia' => 'gaiadouro',
        'raiz'      => 'raizes', 'fanzeres' => 'raizes',
        'lake'      => 'lake',
    ] as $pedaco => $k) {
        if (strpos($n, $pedaco) !== false) { $chave = $k; break; }
    }

    if ($chave === null) {
        return '';
    }

    if (!function_exists('dps_propostas_apresentacao')) {
        $helper = __DIR__ . '/modules/dps_propostas/helpers/dps_propostas_helper.php';
        if (!is_file($helper)) {
            return '';
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', true);
        }
        if (!function_exists('html_escape')) {
            function html_escape($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
        }
        require_once $helper;
    }

    return function_exists('dps_propostas_apresentacao')
        ? (string) dps_propostas_apresentacao($chave)
        : '';
}

/**
 * A fracção com o nome que o catálogo usa.
 *
 * O simulador nomeia a fracção do Douro Mar como "T1-W"; o catálogo, a montra
 * e as vendas usam "1_W". Gravada em bruto, a proposta ficava numa língua que
 * mais nada percebia: a 16/08/2026 três propostas aceites criaram vendas cuja
 * fracção NÃO foi marcada no simulador, e a unidade continuou a aparecer
 * disponível a toda a gente depois de vendida.
 *
 * Reaproveita-se o resolvedor do módulo — o mesmo que dá o preço — para não
 * haver duas ideias diferentes de qual é a fracção.
 */
function unidade_do_catalogo(string $empreendimento, string $unidade): string
{
    if ($unidade === '' || $empreendimento === '') {
        return $unidade;
    }

    if (!function_exists('dps_propostas_chave_catalogo')) {
        $helper = __DIR__ . '/modules/dps_propostas/helpers/dps_propostas_helper.php';
        if (!is_file($helper)) {
            return $unidade;
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', true);
        }
        if (!function_exists('html_escape')) {
            function html_escape($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
        }
        require_once $helper;
    }

    if (!function_exists('dps_propostas_slug') || !function_exists('dps_propostas_chave_catalogo')) {
        return $unidade;
    }

    $slug = dps_propostas_slug($empreendimento);

    if ($slug === null) {
        return $unidade;
    }

    $chave = dps_propostas_chave_catalogo($slug, $unidade);

    return ($chave === null || $chave === '') ? $unidade : (string) $chave;
}

/**
 * O nome do empreendimento como deve ficar gravado.
 *
 * O simulador manda ora a chave interna ("gaiadouro", "boavista", "aura"),
 * ora o nome de mostrar. Gravado em bruto, o mesmo empreendimento fica
 * arrumado em dois sítios: as propostas #985 e #986, aceites a 10/08/2026,
 * ficaram em "gaiadouro" e desapareceram dos gráficos e do filtro do
 * "Gaia Douro". O módulo já normalizava; este ficheiro, que corre por fora
 * do CodeIgniter, não — e é por aqui que passam as propostas do simulador.
 *
 * Usa-se a MESMA função do módulo, carregada como a do texto de
 * apresentação: duas cópias da lista de nomes voltariam a divergir.
 */
function nome_canonico_do_empreendimento(string $valor): string
{
    $valor = trim($valor);
    if ($valor === '') {
        return '';
    }

    if (!function_exists('dps_propostas_nome_canonico')) {
        $helper = __DIR__ . '/modules/dps_propostas/helpers/dps_propostas_helper.php';
        if (!is_file($helper)) {
            return $valor;
        }
        if (!defined('BASEPATH')) {
            define('BASEPATH', true);
        }
        if (!function_exists('html_escape')) {
            function html_escape($t) { return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8'); }
        }
        require_once $helper;
    }

    return function_exists('dps_propostas_nome_canonico')
        ? (string) dps_propostas_nome_canonico($valor)
        : $valor;
}

$empreendimento = nome_canonico_do_empreendimento((string) ($dados['empreendimento'] ?? ''));
$unidade        = unidade_do_catalogo($empreendimento, trim((string) ($dados['unidade'] ?? '')));
$file_name      = trim((string) ($dados['file_name'] ?? 'Proposta.pdf'));
$pdf_base64     = (string) ($dados['pdf_base64'] ?? '');

if ($lead_id <= 0 || $staff_id <= 0 || $token === '' || $pdf_base64 === '') {
    responder(false, 'Faltam dados obrigatórios.', 400);
}

/*
 * Canal de envio: WhatsApp (número do cliente) ou Email (email gravado na
 * lead). Decidido AQUI, antes de qualquer validação de contacto — exigir
 * telefone a quem escolheu email recusava leads que só têm email.
 */
$canal = strtolower(trim((string) ($dados['canal'] ?? 'whatsapp')));
if (!in_array($canal, ['whatsapp', 'email'], true)) {
    $canal = 'whatsapp';
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

$stmt = $bd->prepare('SELECT name, phonenumber, email, status FROM `' . $prefixo . 'leads` WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $lead_id);
$stmt->execute();
$lead = $stmt->get_result()->fetch_assoc();

if (!$lead) {
    responder(false, 'Lead não encontrada.', 404);
}

$numero = preg_replace('/\D/', '', (string) $lead['phonenumber']);

if ($canal === 'whatsapp') {
    if ($numero === '') {
        responder(false, 'Esta lead não tem número de telefone — não é possível enviar por WhatsApp.');
    }
    // Sem indicativo assume-se Portugal (a Evolution exige o número internacional)
    if (strlen($numero) === 9 && $numero[0] === '9') {
        $numero = '351' . $numero;
    }
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

/**
 * Decifra um valor guardado pelo CodeIgniter 3 (é assim que o Perfex guarda a
 * password do SMTP). Este script corre FORA do CI, por isso não há
 * `$this->encryption` — tem de se repetir o formato:
 *
 *   guardado = hmac_sha512_HEX(128 chars) . base64(iv . texto_cifrado)
 *
 * com as chaves derivadas por HKDF-SHA512 a partir da APP_ENC_KEY, e o HMAC
 * calculado sobre a STRING em base64 (não sobre os bytes).
 *
 * Devolve o valor original, ou '' se não for possível decifrar.
 */
function decifrar_ci3(string $guardado): string
{
    $conf = @file_get_contents(CONFIG_APP);
    if ($conf === false || !preg_match("/define\(\s*'APP_ENC_KEY'\s*,\s*'([^']*)'\s*\)/", $conf, $m)) {
        return '';
    }
    $chave = $m[1];

    if ($chave === '' || strlen($guardado) <= 128) {
        return '';
    }

    $hkdf = static function (string $key, ?int $length, string $info): string {
        $ds  = 64;                                  // sha512
        $len = ($length === null || $length <= 0) ? $ds : $length;
        $prk = hash_hmac('sha512', $key, str_repeat("\0", $ds), true);
        $k = ''; $bloco = '';
        for ($i = 1; strlen($k) < $len; $i++) {
            $bloco = hash_hmac('sha512', $bloco . $info . chr($i), $prk, true);
            $k    .= $bloco;
        }
        return substr($k, 0, $len);
    };

    $hmac_recebido = substr($guardado, 0, 128);
    $b64           = substr($guardado, 128);

    if (!hash_equals(hash_hmac('sha512', $b64, $hkdf($chave, null, 'authentication'), false), $hmac_recebido)) {
        return '';
    }

    $bruto = base64_decode($b64, true);
    if ($bruto === false || strlen($bruto) <= 16) {
        return '';
    }

    $claro = openssl_decrypt(
        substr($bruto, 16),
        'aes-128-cbc',
        $hkdf($chave, strlen($chave), 'encryption'),
        OPENSSL_RAW_DATA,
        substr($bruto, 0, 16)
    );

    return $claro === false ? '' : $claro;
}

$evo_url = rtrim(opcao($bd, $prefixo, 'dps_whatsapp_evolution_url'), '/');
$evo_key = opcao($bd, $prefixo, 'dps_whatsapp_evolution_api_key');

if ($canal === 'whatsapp' && ($evo_url === '' || $evo_key === '')) {
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

/*
 * Legenda: o texto do empreendimento, escrito pela direcção, e a seguir a
 * referência à proposta em anexo. Sem texto próprio fica só a referência,
 * como antes. Nunca vai a chave interna ("boavista") para o cliente.
 */
$apresentacao = apresentacao_do_empreendimento($empreendimento);
$legenda = $apresentacao !== ''
    ? $apresentacao . "\n\n📄 Segue em anexo a proposta que preparámos para si."
    : 'Proposta DPS';

/*
 * O PDF é gravado em disco e enviado à Evolution por URL, em vez de ir
 * embutido em base64 no pedido.
 *
 * Porquê: a Evolution devolve HTTP 500 a partir de ~6 MB de base64 —
 * exactamente o tamanho de uma proposta com fotografias. Medido: 2 MB passa,
 * 6 MB e 10 MB falham sempre. Por URL não há esse tecto, o pedido fica
 * pequeno e a Evolution descarrega o ficheiro directamente.
 *
 * O nome é aleatório (não se adivinha) e os ficheiros são limpos ao fim de
 * 7 dias — é o mesmo modelo de qualquer anexo de WhatsApp.
 */
$pdf_bin = base64_decode($pdf_base64, true);
$pdf_url = '';

/*
 * Regra: o cliente recebe o FICHEIRO. Só quando o PDF é grande demais para a
 * Evolution aguentar (ela devolve 500 acima de ~5 MB de base64) é que se
 * recorre ao envio por URL, para pelo menos a proposta chegar.
 *
 * Com as imagens comprimidas no simulador as propostas ficaram em ~1,5 MB,
 * por isso este recurso quase nunca é usado — existe só para não haver
 * envios perdidos se algum empreendimento novo trouxer fotos pesadas.
 */
$precisa_url = strlen($pdf_base64) > 5 * 1024 * 1024;

if ($pdf_bin !== false && $canal === 'whatsapp' && $precisa_url) {
    $dir_pdf = __DIR__ . '/uploads/propostas_wa';

    if (!is_dir($dir_pdf)) {
        @mkdir($dir_pdf, 0755, true);
    }

    // Limpeza dos anteriores (7 dias), para a pasta não crescer sem fim.
    foreach ((array) @glob($dir_pdf . '/*.pdf') as $antigo) {
        if (@filemtime($antigo) < time() - 604800) {
            @unlink($antigo);
        }
    }

    $nome_disco = bin2hex(random_bytes(16)) . '.pdf';

    if (@file_put_contents($dir_pdf . '/' . $nome_disco, $pdf_bin) !== false) {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'crm.grupo-dps.com');
        $pdf_url  = $base_url . '/uploads/propostas_wa/' . $nome_disco;
    }
}

$corpo = json_encode([
    // Evolution v2: os campos do media vão no primeiro nível. A v1 aninhava
    // em mediaMessage e a v2 rejeita com HTTP 400.
    'number'    => $numero,
    'mediatype' => 'document',
    'mimetype'  => 'application/pdf',
    'fileName'  => $file_name,
    // URL quando conseguimos gravar; base64 como recurso de reserva.
    'media'     => $pdf_url !== '' ? $pdf_url : $pdf_base64,
    'caption'   => $legenda,
]);

$raw  = '';
$http = 0;

if ($canal === 'whatsapp') {
    /*
     * ANTES de enviar, confirmar que o WhatsApp deste comercial está mesmo
     * ligado. A Evolution aceita o pedido (2xx) mesmo com a sessão morta e a
     * mensagem fica pendurada para sempre — foi assim que apareceram
     * propostas "enviadas" que nunca chegaram ao cliente. Com esta
     * verificação, o comercial recebe logo o aviso para reconectar.
     */
    $ch = curl_init($evo_url . '/instance/connectionState/staff-' . $staff_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['apikey: ' . $evo_key],
    ]);
    $estado_raw  = (string) curl_exec($ch);
    $estado_http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Só bloqueia com resposta clara de sessão fechada; se o próprio check
    // falhar (timeout, 500), segue para o envio — não vale a pena travar
    // tudo por causa de um check instável.
    if ($estado_http === 200 && strpos($estado_raw, '"state"') !== false
        && strpos($estado_raw, '"open"') === false) {
        responder(false, 'O teu WhatsApp está desligado — lê o QR no módulo de WhatsApp e volta a enviar. (A proposta NÃO foi enviada.)');
    }
    if ($estado_http === 404) {
        responder(false, 'O teu WhatsApp ainda não foi ligado ao CRM — pede ao administrador para criar a tua ligação. (A proposta NÃO foi enviada.)');
    }

    // UMA só tentativa: um 500 transitório pode já ter entregue a mensagem, e
    // repetir criaria duplicados no WhatsApp do cliente.
    $ch = curl_init($evo_url . '/message/sendMedia/staff-' . $staff_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_CONNECTTIMEOUT => 8,
        // 180s: as propostas com galeria de fotos passam dos 10 MB e, em
        // base64, o corpo do pedido chega perto dos 14 MB. Com o limite
        // antigo de 60s o envio estourava a meio e o comercial via
        // "A Evolution não respondeu" — quando na verdade era o upload a
        // não caber no tempo.
        CURLOPT_TIMEOUT        => 180,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $evo_key],
        CURLOPT_POSTFIELDS     => $corpo,
    ]);
    $raw     = (string) curl_exec($ch);
    $http    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro_curl = curl_error($ch);
    curl_close($ch);

    if ($http === 0 && $erro_curl !== '') {
        $raw .= ' [curl: ' . $erro_curl . ']';
    }
} else {
    /*
     * Envio por EMAIL, para o email gravado na lead, com o PDF anexado.
     * Usa o PHPMailer do próprio Perfex e a configuração SMTP das Definições
     * (a mesma do "Send test email"), por isso se o teste funciona, isto
     * funciona.
     */
    $dest_email = trim((string) ($lead['email'] ?? ''));

    if ($dest_email === '' || !filter_var($dest_email, FILTER_VALIDATE_EMAIL)) {
        responder(false, 'Esta lead não tem email válido gravado — grava o email do cliente na ficha ou envia por WhatsApp.');
    }

    require_once __DIR__ . '/application/vendor/autoload.php';

    /*
     * O cliente deve ver o email do COMERCIAL que lhe envia a proposta. Se
     * ele tiver o Webmail configurado, é a caixa dele que autentica e assina;
     * só na falta disso se usa o SMTP geral do CRM (e aí o "responder a"
     * aponta na mesma para o email dele).
     */
    $smtp_host = ''; $smtp_port = 0; $smtp_user = ''; $smtp_pass = '';
    $smtp_from = ''; $empresa = ''; $reply_to = '';

    /*
     * O número do comercial pode estar em `phonenumber` OU em
     * `landing_whatsapp` (o campo "WhatsApp para landing page", que é o que
     * a maioria tem preenchido). COALESCE em cima do primeiro não-vazio,
     * senão o botão de WhatsApp não aparecia para quase ninguém.
     */
    $stmt = $bd->prepare('SELECT email,
                                 NULLIF(TRIM(COALESCE(phonenumber, "")), "") AS tel1,
                                 NULLIF(TRIM(COALESCE(landing_whatsapp, "")), "") AS tel2,
                                 CONCAT(firstname," ",lastname) AS nome
                          FROM `' . $prefixo . 'staff` WHERE staffid = ? LIMIT 1');
    $stmt->bind_param('i', $staff_id);
    $stmt->execute();
    $com = $stmt->get_result()->fetch_assoc() ?: ['email' => '', 'nome' => '', 'tel1' => null, 'tel2' => null];
    $com['telefone'] = (string) ($com['tel1'] ?? $com['tel2'] ?? '');

    $stmt = $bd->prepare('SELECT email, password, smtp_host, smtp_port FROM `' . $prefixo . 'dps_webmail_config` WHERE staff_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $staff_id);
        @$stmt->execute();
        // get_result() UMA vez: à segunda chamada devolve false e o
        // ->fetch_assoc() rebentava com "erro interno a processar a proposta".
        $res = $stmt->get_result();
        $w   = $res ? $res->fetch_assoc() : null;

        if ($w && !empty($w['email']) && !empty($w['password'])) {
            $conf_ci = @file_get_contents(CONFIG_APP);
            $chave   = (preg_match("/define\(\s*'APP_ENC_KEY'\s*,\s*'([^']*)'\s*\)/", (string) $conf_ci, $mk)) ? $mk[1] : '';
            $k       = md5($chave ?: 'dps_webmail_key');
            $pw      = @openssl_decrypt(base64_decode($w['password']), 'AES-256-CBC', $k, 0, substr($k, 0, 16));

            if ($pw !== false && $pw !== '') {
                $smtp_host = $w['smtp_host'] ?: 'smtp.hostinger.com';
                $smtp_port = (int) ($w['smtp_port'] ?: 587);
                $smtp_user = $w['email'];
                $smtp_pass = $pw;
                $smtp_from = $w['email'];
                $empresa   = trim((string) $com['nome']) ?: $w['email'];
            }
        }
    }

    if ($smtp_host === '') {
        $smtp_host = opcao($bd, $prefixo, 'smtp_host');
        $smtp_port = (int) opcao($bd, $prefixo, 'smtp_port');
        $smtp_user = opcao($bd, $prefixo, 'smtp_username');
        $smtp_pass = decifrar_ci3(opcao($bd, $prefixo, 'smtp_password'));
        $smtp_from = opcao($bd, $prefixo, 'smtp_email');
        $empresa   = trim((string) $com['nome']) ?: (opcao($bd, $prefixo, 'companyname') ?: 'DPS Imobiliário');
        $reply_to  = (string) $com['email'];
    }

    $smtp_sec = $smtp_port === 465 ? 'ssl' : 'tls';

    if ($smtp_host === '' || $smtp_port === 0) {
        responder(false, 'O email do CRM não está configurado (Setup → Definições → Email). Avise o administrador.', 503);
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->CharSet    = 'UTF-8';
        $mail->Host       = $smtp_host;
        $mail->Port       = $smtp_port;
        $mail->SMTPAuth   = ($smtp_user !== '');
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        if ($smtp_sec === 'ssl' || $smtp_sec === 'tls') {
            $mail->SMTPSecure = $smtp_sec;
        }
        $mail->setFrom($smtp_from !== '' ? $smtp_from : $smtp_user, $empresa);
        if ($reply_to !== '' && $reply_to !== $smtp_from) {
            $mail->addReplyTo($reply_to, $empresa);
        }
        $mail->addAddress($dest_email, (string) ($lead['name'] ?? ''));
        $mail->Subject = 'Proposta DPS';

        /*
         * Corpo em HTML com um botão de WhatsApp para o comercial: o cliente
         * abre a conversa com um toque, em vez de ter de procurar o contacto.
         * O número vem do perfil do comercial no CRM.
         */
        $tel_com = preg_replace('/\D/', '', (string) ($com['telefone'] ?? ''));
        if (strlen($tel_com) === 9 && $tel_com[0] === '9') {
            $tel_com = '351' . $tel_com;
        }

        $texto_email = ($apresentacao !== '' ? $apresentacao . "\n\n" : '')
            . "Segue em anexo a proposta que preparámos para si.\n\n"
            . "Qualquer questão, estamos ao dispor.\n\nCom os melhores cumprimentos,\n" . $empresa;

        $corpo_html = '<div style="font-family:-apple-system,Segoe UI,Roboto,sans-serif;'
            . 'font-size:15px;line-height:1.6;color:#1b2432;">'
            . nl2br(htmlspecialchars($texto_email, ENT_QUOTES, 'UTF-8'))
            . '</div>';

        if ($tel_com !== '') {
            $corpo_html .= '<div style="margin-top:26px;">'
                . '<a href="https://wa.me/' . rawurlencode($tel_com) . '" '
                . 'style="display:inline-block;background:#25D366;color:#ffffff;text-decoration:none;'
                . 'font-family:-apple-system,Segoe UI,Roboto,sans-serif;font-size:15px;font-weight:700;'
                . 'padding:13px 26px;border-radius:8px;">Falar com o comercial por WhatsApp</a>'
                . '<div style="margin-top:8px;font-size:13px;color:#5a6675;'
                . 'font-family:-apple-system,Segoe UI,Roboto,sans-serif;">'
                . htmlspecialchars((string) ($com['nome'] ?? ''), ENT_QUOTES, 'UTF-8')
                . ' · ' . htmlspecialchars((string) ($com['telefone'] ?? ''), ENT_QUOTES, 'UTF-8')
                . '</div></div>';
        }

        $mail->isHTML(true);
        $mail->Body    = $corpo_html;
        $mail->AltBody = $texto_email;
        $mail->addStringAttachment(base64_decode($pdf_base64), $file_name, 'base64', 'application/pdf');
        $mail->send();
        $http = 200;
        $raw  = 'email enviado para ' . $dest_email;
    } catch (Throwable $e) {
        $http = 500;
        $raw  = 'email: ' . $e->getMessage();
    }
}

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

/*
 * A coluna 'canal' é acrescentada à medida (instalações antigas não a têm).
 *
 * O try/catch é obrigatório: este ficheiro liga-se com
 * mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT), portanto um
 * ALTER que falhe LANÇA uma excepção — e o '@' não silencia excepções. Com a
 * coluna já criada, o "Duplicate column name" rebentava DEPOIS de a proposta
 * já ter sido enviada: o cliente recebia, o comercial via "erro interno" e
 * nada ficava registado em Propostas Enviadas.
 */
try {
    $bd->query('ALTER TABLE `' . $prefixo . "dps_propostas` ADD COLUMN `canal` VARCHAR(10) NULL DEFAULT NULL");
} catch (Throwable $e) {
    // Já existe — é o caso normal.
}

$stmt = $bd->prepare(
    'INSERT INTO `' . $prefixo . "dps_propostas`
        (lead_id, staff_id, tipo, empreendimento, unidade, lead_status_id, lead_status_nome,
         ficheiro, detalhe, wa_ok, outcome, canal, created_at)
     VALUES (?, ?, 'proposta', ?, ?, ?, ?, ?, ?, ?, 'pendente', ?, ?)"
);
$stmt->bind_param(
    'iississsiss',
    $lead_id, $staff_id, $empreendimento, $unidade, $est_id, $estado_nome,
    $file_name, $detalhe, $wa_int, $canal, $agora
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
    $detalhe = 'Proposta enviada ao cliente por ' . ($canal === 'email' ? 'email' : 'WhatsApp')
        . ($empreendimento !== '' ? ' — ' . $empreendimento : '')
        . ($unidade !== '' ? ' — Fracção ' . $unidade : '');

    // A nota guarda o texto simples...
    $desc = $detalhe;

    /*
     * ...mas o REGISTO DE ATIVIDADE tem de seguir a convenção das notas
     * ('📝 Nota: ...'), senão não conta como interação: o contador do CRM
     * procura descrições que comecem por esse prefixo (dps_interacoes) e é
     * assim que o dps_teams regista as notas. Sem isto, a proposta ficava
     * de fora das interações do comercial.
     */
    $desc_atividade = '📝 Nota: ' . $detalhe;

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
    $stmt->bind_param('issis', $lead_id, $desc_atividade, $agora, $staff_id, $nome_staff);
    @$stmt->execute();

    /*
     * 2. Estado → "PROPOSTAS ENVIADAS".
     * Resolvido por NOME (nunca por id fixo: os estados já foram renomeados
     * uma vez e um id à mão partia em silêncio). Se ainda não existir, é
     * criado — assim não é preciso configurar nada à mão no CRM.
     */
    $vip_row = null;
    $res_st = $bd->query(
        "SELECT id, name FROM `" . $prefixo . "leads_status`
         WHERE UPPER(REPLACE(name,'S','S')) LIKE '%PROPOSTA%' AND UPPER(name) LIKE '%ENVIAD%'
         ORDER BY statusorder, id LIMIT 1"
    );
    if ($res_st) {
        $vip_row = $res_st->fetch_assoc() ?: null;
    }

    if (!$vip_row) {
        // Criar o estado, no fim da ordem existente.
        $ordem = 1;
        $r_ord = $bd->query("SELECT COALESCE(MAX(statusorder),0)+1 AS o FROM `" . $prefixo . "leads_status`");
        if ($r_ord && ($x = $r_ord->fetch_assoc())) { $ordem = (int) $x['o']; }

        $nome_novo = 'PROPOSTAS ENVIADAS';
        $cor_nova  = '#1d6fb8';
        $stmt = $bd->prepare(
            'INSERT INTO `' . $prefixo . 'leads_status` (name, color, statusorder, isdefault) VALUES (?, ?, ?, 0)'
        );
        $stmt->bind_param('ssi', $nome_novo, $cor_nova, $ordem);
        if (@$stmt->execute()) {
            $vip_row = ['id' => $bd->insert_id, 'name' => $nome_novo];
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
    if ($canal === 'email') {
        responder(false, 'Falha no envio por email: ' . substr($raw, 0, 200));
    }

    // Traduzir os erros mais comuns da Evolution para linguagem do comercial.
    if (strpos($raw, '"exists":false') !== false) {
        responder(false, 'O número ' . $numero . ' não tem WhatsApp — não é possível enviar por aqui.');
    }
    if (strpos($raw, 'No sessions') !== false || strpos($raw, 'does not exist') !== false
        || strpos($raw, 'not-acceptable') !== false) {
        /*
         * Sessão morta (a instância diz "ligada" mas não tem sessões). Marcar
         * já na base de dados: assim o aviso vermelho aparece no topo do CRM
         * dele de imediato, em vez de só na verificação da hora seguinte.
         */
        @$bd->query('UPDATE `' . $prefixo . 'dps_whatsapp_config` SET is_connected = 0 WHERE staff_id = ' . (int) $staff_id);

        responder(false, 'O teu WhatsApp perdeu a ligação — lê o QR outra vez no módulo de WhatsApp. (A proposta NÃO foi enviada.)');
    }
    if ($http === 0) {
        $mb = round(strlen($pdf_base64) * 3 / 4 / 1048576, 1);
        responder(false, 'O envio demorou demasiado e foi interrompido (PDF de ~' . $mb . ' MB). '
            . 'Tenta de novo; se voltar a falhar, envia esta proposta por email.');
    }
    if ($http >= 500) {
        responder(false, 'A Evolution devolveu erro interno (HTTP ' . $http . ') — tenta de novo daqui a instantes.');
    }
    responder(false, 'Falha no envio pelo WhatsApp (HTTP ' . $http . ').');
}

responder(true, $canal === 'email'
    ? 'Proposta enviada ao cliente por EMAIL (' . trim((string) ($lead['email'] ?? '')) . ').'
    : 'Proposta enviada ao cliente por WhatsApp.');
