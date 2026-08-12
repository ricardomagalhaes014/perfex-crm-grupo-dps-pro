<?php
/**
 * Recibos de entrega do WhatsApp.
 *
 * PORQUÊ ISTO EXISTE
 * ------------------
 * A Evolution responde ao envio com "status":"PENDING" — que é apenas o
 * instante em que a mensagem nasce, antes de o WhatsApp confirmar seja o que
 * for. O CRM guardava esse instante e nunca mais perguntava nada: em sete dias
 * havia 101 propostas enviadas, todas em PENDING e zero confirmações. Uma
 * mensagem que ficava pelo caminho era indistinguível de uma entregue, e o
 * comercial dava a proposta por entregue sem ela ter saído.
 *
 * Este ficheiro é o outro lado da conversa: a Evolution passa a avisar sempre
 * que o estado de uma mensagem muda, e cada proposta fica a saber se foi
 * mesmo enviada, entregue e lida.
 *
 * Também escuta as mudanças de ligação das instâncias. Foi por aí que se viu
 * o problema: uma sessão pode cair (razão 401, o telemóvel desassociou o
 * dispositivo) e a Evolution continuar a dizer "open" e a aceitar mensagens.
 * Ficam registadas, com hora, para se poder cruzar com os envios falhados.
 *
 * SEGURANÇA
 * ---------
 * O segredo vive em tbloptions (`dps_wa_status_token`), não num ficheiro: o
 * deploy sincroniza o docroot e apagaria um ficheiro que não esteja no
 * repositório — foi o que já aconteceu ao segredo das vendas.
 */

// -------- entrada --------------------------------------------------------

$token_recebido = isset($_GET['t']) ? (string) $_GET['t'] : '';
$corpo_bruto    = file_get_contents('php://input');

// Os ficheiros de configuração do Perfex recusam-se a carregar sem isto — é
// a guarda contra acesso directo, e aqui o acesso directo é o que queremos.
defined('BASEPATH') or define('BASEPATH', true);
defined('ENVIRONMENT') or define('ENVIRONMENT', 'production');

require_once __DIR__ . '/application/config/app-config.php';
require_once __DIR__ . '/application/config/database.php';

$cfg = $db['default'];
$bd  = @new mysqli($cfg['hostname'], $cfg['username'], $cfg['password'], $cfg['database']);

if ($bd->connect_errno) {
    http_response_code(503);
    exit('bd indisponivel');
}
$bd->set_charset('utf8mb4');

$linha = $bd->query("SELECT value FROM tbloptions WHERE name='dps_wa_status_token'");
$token = ($linha && $linha->num_rows) ? (string) $linha->fetch_row()[0] : '';

// hash_equals: comparar segredos com === deixa medir o tempo e adivinhar
// carácter a carácter.
if ($token === '' || ! hash_equals($token, $token_recebido)) {
    http_response_code(403);
    exit('nao autorizado');
}

$ev = json_decode($corpo_bruto, true);
if (! is_array($ev)) {
    http_response_code(400);
    exit('corpo invalido');
}

// A Evolution responde-nos já: o resto faz-se com a ligação a fechar, para
// nunca segurar a fila de eventos dela por causa do nosso processamento.
http_response_code(200);
header('Content-Type: application/json');
echo '{"ok":true}';

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

// -------- tabela do diário -----------------------------------------------

$bd->query(
    "CREATE TABLE IF NOT EXISTS tbldps_wa_eventos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        instancia VARCHAR(40) NULL,
        evento VARCHAR(40) NULL,
        msg_id VARCHAR(80) NULL,
        estado VARCHAR(32) NULL,
        destino VARCHAR(40) NULL,
        detalhe TEXT NULL,
        criado_em DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY dps_wa_ev_msg (msg_id),
        KEY dps_wa_ev_quando (criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

// -------- leitura do evento ----------------------------------------------

/** A Evolution muda os nomes dos campos entre versões — procura-se em todos. */
function wa_campo(array $d, array $nomes, $omissao = '')
{
    foreach ($nomes as $n) {
        if (isset($d[$n]) && $d[$n] !== '' && ! is_array($d[$n])) {
            return (string) $d[$n];
        }
    }

    return $omissao;
}

$evento    = strtolower(wa_campo($ev, ['event'], ''));
$instancia = wa_campo($ev, ['instance', 'instanceName'], '');
$dados     = isset($ev['data']) && is_array($ev['data']) ? $ev['data'] : [];
$chave     = isset($dados['key']) && is_array($dados['key']) ? $dados['key'] : [];

$msg_id = wa_campo($dados, ['keyId', 'messageId', 'id'], '');
if ($msg_id === '') {
    $msg_id = wa_campo($chave, ['id'], '');
}

$estado  = strtoupper(wa_campo($dados, ['status'], ''));
$destino = wa_campo($dados, ['remoteJid'], '');
if ($destino === '') {
    $destino = wa_campo($chave, ['remoteJid'], '');
}
$destino = explode('@', $destino)[0];

$agora = date('Y-m-d H:i:s');

function wa_esc($bd, $v)
{
    return "'" . $bd->real_escape_string((string) $v) . "'";
}

// -------- ligação da instância: é aqui que se apanham as sessões mortas ----

if ($evento === 'connection.update') {
    $novo   = strtolower(wa_campo($dados, ['state', 'connection'], ''));
    $razao  = wa_campo($dados, ['statusReason', 'reason', 'lastDisconnect'], '');
    $bd->query('INSERT INTO tbldps_wa_eventos (instancia,evento,msg_id,estado,destino,detalhe,criado_em) VALUES ('
        . wa_esc($bd, $instancia) . ",'connection.update',NULL,"
        . wa_esc($bd, $novo) . ',NULL,'
        . wa_esc($bd, 'razao=' . $razao) . ',' . wa_esc($bd, $agora) . ')');
    exit;
}

// -------- recibos das mensagens ------------------------------------------

if ($msg_id === '') {
    exit;
}

$bd->query('INSERT INTO tbldps_wa_eventos (instancia,evento,msg_id,estado,destino,detalhe,criado_em) VALUES ('
    . wa_esc($bd, $instancia) . ',' . wa_esc($bd, $evento) . ',' . wa_esc($bd, $msg_id) . ','
    . wa_esc($bd, $estado) . ',' . wa_esc($bd, $destino) . ','
    . wa_esc($bd, mb_substr($corpo_bruto, 0, 1500)) . ',' . wa_esc($bd, $agora) . ')');

if ($estado === '') {
    exit;
}

/*
 * Só se avança no estado, nunca se recua: os eventos chegam fora de ordem com
 * frequência, e um SERVER_ACK atrasado não pode apagar um READ que já entrou.
 */
$ordem = ['ERROR' => 0, 'PENDING' => 1, 'SERVER_ACK' => 2, 'DELIVERY_ACK' => 3, 'READ' => 4, 'PLAYED' => 5];
$peso  = isset($ordem[$estado]) ? $ordem[$estado] : 1;

$linha = $bd->query('SELECT id, wa_status FROM tbldps_propostas WHERE wa_msg_id = ' . wa_esc($bd, $msg_id) . ' LIMIT 1');

if (! $linha || ! $linha->num_rows) {
    exit;   // recibo de uma mensagem que não é nossa (conversa normal do comercial)
}

$p          = $linha->fetch_assoc();
$peso_atual = isset($ordem[strtoupper((string) $p['wa_status'])]) ? $ordem[strtoupper((string) $p['wa_status'])] : 1;

if ($peso < $peso_atual) {
    exit;
}

/*
 * wa_ok passa a significar "o WhatsApp confirmou", não "a API aceitou". É o
 * SERVER_ACK que diz que a mensagem saiu mesmo; ERROR marca-a como falhada,
 * mesmo que na altura tenha sido dada como enviada.
 */
$ok = ($peso >= 2) ? 1 : (($estado === 'ERROR') ? 0 : null);

$sql = 'UPDATE tbldps_propostas SET wa_status = ' . wa_esc($bd, $estado)
     . ', wa_status_at = ' . wa_esc($bd, $agora)
     . ($ok === null ? '' : ', wa_ok = ' . (int) $ok)
     . ' WHERE id = ' . (int) $p['id'];

$bd->query($sql);
