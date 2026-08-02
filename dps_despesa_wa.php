<?php
/**
 * Despesas por WhatsApp — recebe a fotografia e cria a despesa no Painel.
 *
 * O fluxo, do lado de quem usa:
 *   1. Abrir no WhatsApp a conversa consigo próprio ("Mensagem para mim").
 *   2. Enviar a fotografia da factura. Se quiser, com legenda: "45,90 combustível".
 *   3. A despesa nasce em uploads/dps_painel/ e aparece no Painel do Negócio.
 *   4. Se a legenda vier sem valor, responde-se depois só com o número ("45,90")
 *      e ele preenche a última despesa que ficou por valorizar.
 *
 * Segurança — ler antes de mexer:
 *   Três fechaduras, todas necessárias:
 *     a) o token na URL, que só a Evolution conhece (vive fora do repositório,
 *        em uploads/dps_secure/despesa_wa_secret);
 *     b) key.fromMe = true — a mensagem tem de ter sido ESCRITA pelo dono do
 *        telemóvel, não recebida de terceiros;
 *     c) a conversa tem de ser a conversa consigo próprio (remoteJid == dono da
 *        instância). Ninguém no mundo consegue escrever lá dentro.
 *   É por isso que não há lista branca de números: a lista branca é o próprio
 *   telemóvel. Se um dia quiser aceitar fotografias enviadas POR outros, isso
 *   deixa de ser verdade e passa a ser preciso um filtro de remetentes.
 *
 *   O ficheiro só faz INSERT/UPDATE em tbldps_painel_despesas, com colunas
 *   fixas. Não lê clientes, não apaga nada, não corre SQL vindo de fora.
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Lisbon');

/** Instância da Evolution autorizada — o número da empresa. */
const INSTANCIA_PERMITIDA = 'staff-1';

/** A quem fica creditada a despesa no CRM (staffid). */
const AUTOR_DESPESA = 1;

const CAMINHO_SEGREDO = __DIR__ . '/uploads/dps_secure/despesa_wa_secret';
const REGISTO         = __DIR__ . '/application/logs/dps-despesa-wa.log';

const EXT_PERMITIDAS = [
    'image/jpeg'      => 'jpg',
    'image/jpg'       => 'jpg',
    'image/png'       => 'png',
    'application/pdf' => 'pdf',
];

/**
 * A Evolution reenvia o webhook quando não recebe 200. Responder sempre 200
 * (mesmo quando ignoramos a mensagem) evita ficarmos a receber a mesma
 * fotografia de dez em dez segundos para sempre.
 */
function terminar(string $nota): void
{
    anotar($nota);
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'nota' => $nota], JSON_UNESCAPED_UNICODE);
    exit;
}

function anotar(string $linha): void
{
    @file_put_contents(REGISTO, date('Y-m-d H:i:s') . '  ' . $linha . "\n", FILE_APPEND);
}

set_exception_handler(static function (Throwable $e): void {
    anotar('EXCEPÇÃO ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    http_response_code(200);   // ver nota em terminar()
    echo '{"ok":false}';
    exit;
});

/* ------------------------------------------------------------------ *
 * 1. Fechadura do token
 * ------------------------------------------------------------------ */

if (!is_readable(CAMINHO_SEGREDO)) {
    terminar('sem ficheiro de segredo — endpoint desligado');
}
$segredo = trim((string) file_get_contents(CAMINHO_SEGREDO));
$dado    = (string) ($_GET['t'] ?? '');

if ($segredo === '' || !hash_equals($segredo, $dado)) {
    http_response_code(403);
    anotar('token inválido de ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    exit;
}

/* ------------------------------------------------------------------ *
 * 2. Ler o evento
 * ------------------------------------------------------------------ */

$corpo = (string) file_get_contents('php://input');
$ev    = json_decode($corpo, true);

if (!is_array($ev)) {
    terminar('corpo não é JSON');
}

$evento    = strtolower((string) ($ev['event'] ?? ''));
$instancia = (string) ($ev['instance'] ?? '');

if ($evento !== 'messages.upsert') {
    terminar('evento ignorado: ' . $evento);
}
if ($instancia !== INSTANCIA_PERMITIDA) {
    terminar('instância não autorizada: ' . $instancia);
}

$dados = $ev['data'] ?? [];
// A Evolution ora manda um objecto, ora um array com uma mensagem lá dentro.
if (isset($dados[0]) && is_array($dados[0])) {
    $dados = $dados[0];
}

$chave = $dados['key'] ?? [];
if (empty($chave['fromMe'])) {
    terminar('mensagem recebida de terceiros — ignorada');
}

/* ------------------------------------------------------------------ *
 * 3. Fechadura da conversa consigo próprio
 * ------------------------------------------------------------------ */

/** 351925610864:12@s.whatsapp.net -> 351925610864 */
function so_numero(string $jid): string
{
    $jid = explode('@', $jid)[0];
    $jid = explode(':', $jid)[0];

    return preg_replace('/\D+/', '', $jid) ?? '';
}

$dono     = so_numero((string) ($ev['sender'] ?? ''));
$conversa = so_numero((string) ($chave['remoteJid'] ?? ''));

if ($dono === '' || $conversa === '' || $dono !== $conversa) {
    terminar('não é a conversa consigo próprio (' . $conversa . ') — ignorada');
}

/* ------------------------------------------------------------------ *
 * 4. Base de dados
 * ------------------------------------------------------------------ */

/*
 * Lêem-se as constantes do app-config.php por leitura de texto, sem o incluir:
 * o ficheiro tem o guarda "No direct script access" do CodeIgniter e mata
 * qualquer script que não seja o CRM. É o mesmo caminho do dps_venda_receber.
 */
$config = __DIR__ . '/application/config/app-config.php';
if (!is_readable($config)) {
    terminar('app-config.php ilegível');
}
$texto_config = (string) file_get_contents($config);

$ler_config = static function (string $constante) use ($texto_config): string {
    if (preg_match("/define\(\s*'" . $constante . "'\s*,\s*'([^']*)'\s*\)/", $texto_config, $m)) {
        return $m[1];
    }

    return '';
};

$sql = @new mysqli(
    $ler_config('APP_DB_HOSTNAME') ?: 'localhost',
    $ler_config('APP_DB_USERNAME'),
    $ler_config('APP_DB_PASSWORD'),
    $ler_config('APP_DB_NAME')
);
if ($sql->connect_error) {
    terminar('base de dados indisponível');
}
$sql->set_charset('utf8mb4');

$prefixo = $ler_config('APP_DB_PREFIX') ?: 'tbl';
$tabela  = $prefixo . 'dps_painel_despesas';

/* ------------------------------------------------------------------ *
 * 5. Leitura da legenda: valor e categoria
 * ------------------------------------------------------------------ */

/** "45,90 combustível" -> 45.90 ; "" -> null */
function ler_valor(string $texto): ?float
{
    // Aceita 45,90 / 45.90 / 1.234,56 / 45 € / € 45
    if (!preg_match('/(\d{1,3}(?:[.\s]\d{3})*|\d+)(?:[.,](\d{1,2}))?/u', $texto, $m)) {
        return null;
    }
    $inteiro = preg_replace('/\D+/', '', $m[1]);
    if ($inteiro === '') {
        return null;
    }
    $valor = (float) $inteiro + (isset($m[2]) ? (float) str_pad($m[2], 2, '0') / 100 : 0.0);

    return $valor > 0 ? round($valor, 2) : null;
}

/**
 * A lista é fechada no Painel (Representação / Marketing / Outros) — aqui
 * apenas adivinhamos qual das três, por palavras que se escrevem à pressa no
 * telemóvel. O que não se reconhece cai em Outros e corrige-se no CRM.
 */
function ler_categoria(string $texto): string
{
    $t = mb_strtolower($texto, 'UTF-8');
    $t = strtr($t, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c']);

    $mapa = [
        'Representação' => ['representacao', 'almoco', 'jantar', 'refeicao', 'restaurante', 'cafe', 'hotel', 'combustivel', 'gasoleo', 'gasolina', 'portagem', 'estacionamento', 'parque', 'taxi', 'uber', 'viagem'],
        'Marketing'     => ['marketing', 'publicidade', 'anuncio', 'anuncios', 'facebook', 'meta', 'google ads', 'instagram', 'flyer', 'flyers', 'lona', 'outdoor', 'grafica', 'brinde', 'brindes', 'site', 'fotografia', 'video'],
    ];

    foreach ($mapa as $categoria => $palavras) {
        foreach ($palavras as $p) {
            if (str_contains($t, $p)) {
                return $categoria;
            }
        }
    }

    return 'Outros';
}

/* ------------------------------------------------------------------ *
 * 6. Que mensagem é esta?
 * ------------------------------------------------------------------ */

$msg = $dados['message'] ?? [];
// Fotografias com legenda longa vêm embrulhadas.
if (isset($msg['ephemeralMessage']['message'])) {
    $msg = $msg['ephemeralMessage']['message'];
}
if (isset($msg['viewOnceMessageV2']['message'])) {
    $msg = $msg['viewOnceMessageV2']['message'];
}

$imagem    = $msg['imageMessage'] ?? null;
$documento = $msg['documentMessage'] ?? ($msg['documentWithCaptionMessage']['message']['documentMessage'] ?? null);
$media     = $imagem ?: $documento;

$legenda = trim((string) (
    $media['caption']
    ?? $msg['conversation']
    ?? $msg['extendedTextMessage']['text']
    ?? ''
));

/* ------------------------------------------------------------------ *
 * 6a. Mensagem só de texto: valorizar a última despesa pendente
 * ------------------------------------------------------------------ */

if (!$media) {
    $valor = ler_valor($legenda);
    if ($valor === null) {
        terminar('texto sem valor — ignorado');
    }

    $r = $sql->query(
        "SELECT id, descricao FROM `{$tabela}`
          WHERE valor = 0 AND doc IS NOT NULL AND doc <> ''
            AND dateadded >= DATE_SUB(NOW(), INTERVAL 3 DAY)
          ORDER BY id DESC LIMIT 1"
    );
    $pendente = $r ? $r->fetch_assoc() : null;

    if (!$pendente) {
        responder_wa($ev, 'Não encontrei nenhuma despesa à espera de valor. Envie primeiro a fotografia.');
        terminar('valor sem despesa pendente');
    }

    $categoria = ler_categoria($legenda);
    $st = $sql->prepare("UPDATE `{$tabela}` SET valor = ?, categoria = ? WHERE id = ?");
    $st->bind_param('dsi', $valor, $categoria, $pendente['id']);
    $st->execute();

    responder_wa($ev, sprintf(
        "Despesa #%d actualizada: %s €  ·  %s",
        (int) $pendente['id'],
        number_format($valor, 2, ',', ' '),
        $categoria
    ));
    terminar('despesa #' . $pendente['id'] . ' valorizada em ' . $valor);
}

/* ------------------------------------------------------------------ *
 * 7. Trazer o ficheiro
 * ------------------------------------------------------------------ */

/** Configuração da Evolution, tal como o resto do CRM a lê. */
function evolution(mysqli $sql, string $prefixo): array
{
    $cfg = ['url' => '', 'key' => ''];
    $r = $sql->query(
        "SELECT name, value FROM `{$prefixo}options`
          WHERE name IN ('dps_whatsapp_evolution_url','dps_whatsapp_evolution_api_key')"
    );
    while ($r && $o = $r->fetch_assoc()) {
        if ($o['name'] === 'dps_whatsapp_evolution_url') {
            $cfg['url'] = rtrim($o['value'], '/');
        } else {
            $cfg['key'] = $o['value'];
        }
    }

    return $cfg;
}

$evo = evolution($sql, $prefixo);

$mime   = strtolower((string) ($media['mimetype'] ?? ''));
$mime   = trim(explode(';', $mime)[0]);
$base64 = (string) ($dados['message']['base64'] ?? $ev['data']['message']['base64'] ?? '');

if ($base64 === '' && $evo['url'] !== '') {
    // Não veio no webhook: pedimo-lo à Evolution pelo id da mensagem.
    $ch = curl_init($evo['url'] . '/chat/getBase64FromMediaMessage/' . INSTANCIA_PERMITIDA);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $evo['key']],
        CURLOPT_POSTFIELDS     => json_encode([
            'message'      => ['key' => ['id' => (string) ($chave['id'] ?? '')]],
            'convertToMp4' => false,
        ]),
    ]);
    $resposta = json_decode((string) curl_exec($ch), true);
    curl_close($ch);

    $base64 = (string) ($resposta['base64'] ?? '');
    if ($mime === '') {
        $mime = trim(explode(';', strtolower((string) ($resposta['mimetype'] ?? '')))[0]);
    }
}

if ($base64 === '') {
    responder_wa($ev, 'Recebi a mensagem mas não consegui trazer o ficheiro. Tente reenviar.');
    terminar('sem base64 para a mensagem ' . ($chave['id'] ?? '?'));
}

if (!isset(EXT_PERMITIDAS[$mime])) {
    responder_wa($ev, 'Só aceito fotografias (JPG/PNG) ou PDF. Recebi: ' . ($mime ?: 'desconhecido'));
    terminar('mime recusado: ' . $mime);
}

$conteudo = base64_decode($base64, true);
if ($conteudo === false || strlen($conteudo) < 100) {
    terminar('ficheiro vazio ou ilegível');
}
if (strlen($conteudo) > 12 * 1024 * 1024) {
    responder_wa($ev, 'O ficheiro é demasiado grande (máximo 12 MB).');
    terminar('ficheiro grande demais: ' . strlen($conteudo));
}

/* ------------------------------------------------------------------ *
 * 8. Guardar
 * ------------------------------------------------------------------ */

$pasta = __DIR__ . '/uploads/dps_painel/';
if (!is_dir($pasta)) {
    @mkdir($pasta, 0755, true);
}
// A mesma blindagem que o módulo põe: a pasta não se lê pelo browser.
if (!file_exists($pasta . '.htaccess')) {
    @file_put_contents(
        $pasta . '.htaccess',
        "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n"
    );
}

$nome = 'despesa_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . EXT_PERMITIDAS[$mime];
if (file_put_contents($pasta . $nome, $conteudo) === false) {
    responder_wa($ev, 'Não consegui guardar o ficheiro no servidor.');
    terminar('falhou a escrita de ' . $nome);
}

$valor     = ler_valor($legenda) ?? 0.0;
$categoria = ler_categoria($legenda);
$descricao = $legenda !== ''
    ? mb_substr($legenda, 0, 250)
    : 'Por WhatsApp ' . date('d/m/Y H:i');
$data      = date('Y-m-d', (int) ($dados['messageTimestamp'] ?? time()));
$agora     = date('Y-m-d H:i:s');
$autor     = AUTOR_DESPESA;

$st = $sql->prepare(
    "INSERT INTO `{$tabela}` (data, categoria, descricao, valor, doc, dateadded, created_by)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$st->bind_param('sssdssi', $data, $categoria, $descricao, $valor, $nome, $agora, $autor);
$st->execute();
$id = (int) $sql->insert_id;

/* ------------------------------------------------------------------ *
 * 9. Confirmar por WhatsApp
 * ------------------------------------------------------------------ */

function responder_wa(array $ev, string $texto): void
{
    static $enviado = false;
    if ($enviado) {
        return;
    }
    $enviado = true;

    // Marca de ensaio: permite testar o percurso todo (ficheiro, base de dados,
    // categorias) sem mandar mensagens a ninguém. Só chega aqui quem tem o
    // token, e o efeito é apenas calar — nunca fazer mais.
    if (!empty($ev['dps_teste'])) {
        anotar('ENSAIO — resposta suprimida: ' . $texto);

        return;
    }

    $destino = (string) ($ev['sender'] ?? '');
    if ($destino === '') {
        return;
    }

    // A configuração é relida aqui porque esta função também corre em ramos
    // que terminam antes de a ligação à base de dados ser aberta.
    global $sql, $prefixo;
    if (!$sql instanceof mysqli) {
        return;
    }
    $evo = evolution($sql, $prefixo);
    if ($evo['url'] === '') {
        return;
    }

    $ch = curl_init($evo['url'] . '/message/sendText/' . INSTANCIA_PERMITIDA);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $evo['key']],
        CURLOPT_POSTFIELDS     => json_encode(['number' => $destino, 'text' => $texto], JSON_UNESCAPED_UNICODE),
    ]);
    curl_exec($ch);
    curl_close($ch);
}

if ($valor > 0) {
    responder_wa($ev, sprintf(
        "Despesa #%d registada.\n%s €  ·  %s\nJá está no Painel do Negócio.",
        $id,
        number_format($valor, 2, ',', ' '),
        $categoria
    ));
} else {
    responder_wa($ev, sprintf(
        "Despesa #%d registada (%s).\nFalta o valor — responda só com o número, por exemplo: 45,90 combustível",
        $id,
        $categoria
    ));
}

terminar('despesa #' . $id . ' criada · ' . $nome . ' · ' . $valor . ' € · ' . $categoria);
