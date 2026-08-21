<?php
/**
 * ElevenLabs → CRM: recebe o webhook de FIM DE CHAMADA da Sofia.
 *
 * PORQUE ESTE CAMINHO E NÃO UMA "tool"
 * ------------------------------------
 * A abordagem anterior era dar à Sofia uma ferramenta e esperar que ela a
 * chamasse quando detectasse interesse. Em 31/07/2026 verificou-se que nunca
 * chamava: o modelo decide, e quando não decide não há registo nenhum e
 * ninguém percebe porquê.
 *
 * O webhook de fim de chamada não depende de decisão nenhuma do modelo: o
 * ElevenLabs envia SEMPRE, no fim de cada conversa, a transcrição e os campos
 * que o agente extraiu. Somos NÓS que decidimos se aquilo é uma oportunidade.
 * A regra passa a estar aqui, onde se pode ler, testar e corrigir.
 *
 * Configurar em: ElevenLabs → Agents Platform → Settings → Security →
 * Post-Call Webhook →  https://crm.grupo-dps.com/sofia-webhook.php
 */

header('Content-Type: application/json; charset=utf-8');

const SEGREDO_FICHEIRO = __DIR__ . '/../sofia-webhook-segredo.txt';
const REGISTO          = __DIR__ . '/../sofia-webhook.log';

function sw_log($etapa, $extra = '')
{
    if (@filesize(REGISTO) > 1048576) {          // 1 MB: fica a segunda metade
        $t = @file_get_contents(REGISTO);
        @file_put_contents(REGISTO, substr((string) $t, -524288));
    }

    @file_put_contents(REGISTO, sprintf(
        "[%s] %s | %s\n",
        date('Y-m-d H:i:s'),
        $etapa,
        substr(str_replace(["\n", "\r"], ' ', $extra), 0, 2000)
    ), FILE_APPEND | LOCK_EX);
}

/* ======================================================================
 * AVISAR — no CRM e no WhatsApp
 *
 * Até aqui uma conversa da Sofia criava a tarefa e mais nada: quem não
 * abrisse a lista de tarefas não sabia que ela existia. Pedido do dono
 * (07/08/2026): aviso visível no CRM e WhatsApp para si, sempre.
 * =================================================================== */

/** Lê uma opção do CRM (a mesma tabela que o Perfex usa). */
function sw_opcao($bd, $p, $nome)
{
    $st = $bd->prepare("SELECT value FROM {$p}options WHERE name = ? LIMIT 1");
    if (!$st) {
        return '';
    }
    $st->bind_param('s', $nome);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();

    return (string) ($r['value'] ?? '');
}

/** O sino do CRM. Escreve-se directamente porque isto não é CodeIgniter. */
function sw_avisar_crm($bd, $p, $staff, $texto, $link = '')
{
    $staff = (int) $staff;
    if ($staff <= 0) {
        return;
    }
    $st = $bd->prepare("INSERT INTO {$p}notifications
        (isread, isread_inline, date, description, fromuserid, fromclientid, from_fullname, touserid, fromcompany, link)
        VALUES (0, 0, ?, ?, 0, 0, 'Sofia', ?, 1, ?)");
    if (!$st) {
        return;
    }
    $agora = date('Y-m-d H:i:s');
    $st->bind_param('ssis', $agora, $texto, $staff, $link);
    $st->execute();
    $st->close();
}

/**
 * WhatsApp para a direção.
 *
 * Vai pela instância do próprio Ricardo (staff-1) para o número dele: é uma
 * mensagem para si mesmo, que é como o WhatsApp guarda recados.
 *
 * Curto de propósito. O ElevenLabs repete o envio quando não recebe 200
 * depressa, e uma chamada lenta a um serviço externo faria chegar a mesma
 * conversa duas vezes — daí 6 segundos e nunca falhar o pedido por causa
 * disto.
 */
function sw_whatsapp_direcao($bd, $p, $texto)
{
    $url = rtrim(sw_opcao($bd, $p, 'dps_whatsapp_evolution_url'), '/');
    $key = sw_opcao($bd, $p, 'dps_whatsapp_evolution_api_key');

    if ($url === '' || $key === '') {
        sw_log('WHATSAPP nao configurado', 'Evolution sem url/chave');
        return;
    }

    $st = $bd->prepare("SELECT phonenumber FROM {$p}staff WHERE staffid = 1 LIMIT 1");
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();

    $num = preg_replace('/[^0-9]/', '', (string) ($r['phonenumber'] ?? ''));
    if (strlen($num) === 9) {
        $num = '351' . $num;               // número nacional sem indicativo
    }
    if (strlen($num) < 11) {
        sw_log('WHATSAPP sem numero', 'staff 1 sem telefone utilizável');
        return;
    }

    $ch = curl_init($url . '/message/sendText/staff-1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 6,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $key],
        CURLOPT_POSTFIELDS     => json_encode(['number' => $num, 'text' => $texto]),
    ]);
    curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erro = curl_error($ch);
    curl_close($ch);

    sw_log($http >= 200 && $http < 300 ? 'WHATSAPP enviado' : 'WHATSAPP falhou',
        'http=' . $http . ($erro ? ' erro=' . $erro : ''));
}

/**
 * Guarda o resultado da chamada no registo da campanha.
 *
 * A tabela das chamadas sabia se a chamada foi atendida, mas não sabia o que a
 * pessoa RESPONDEU. Sem isso não havia relatório possível de sim / não / não
 * atendida — que é o que a direção precisa de ver ao fim do dia.
 *
 * Liga-se pelo conversation_id, que é o mesmo dos dois lados.
 */
function sw_marcar_resultado($bd, $p, $conversa, $resultado, $resumo = '')
{
    if ($conversa === '') {
        return;
    }
    $st = $bd->prepare("UPDATE {$p}dps_sofia_call_logs
                           SET resultado = ?, resumo = ?
                         WHERE elevenlabs_call_id = ?");
    if (!$st) {
        return;
    }
    $r = mb_substr((string) $resumo, 0, 2000);
    $st->bind_param('sss', $resultado, $r, $conversa);
    $st->execute();
    $st->close();
}

$corpo = (string) file_get_contents('php://input');

if ($corpo === '') {
    sw_log('RECUSADO corpo vazio');
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Corpo vazio']);
    exit;
}

/*
 * Assinatura HMAC. O ElevenLabs assina com o segredo que dá ao criar o
 * webhook. Enquanto esse segredo não estiver gravado no servidor aceita-se na
 * mesma, mas fica registado: é melhor receber chamadas e saber que a porta
 * está aberta do que rejeitar tudo em silêncio e a equipa não perceber porquê.
 */
$segredo = is_file(SEGREDO_FICHEIRO) ? trim((string) file_get_contents(SEGREDO_FICHEIRO)) : '';

if ($segredo !== '') {
    $cab = $_SERVER['HTTP_ELEVENLABS_SIGNATURE'] ?? ($_SERVER['HTTP_X_ELEVENLABS_SIGNATURE'] ?? '');
    $ok  = false;

    // Formato "t=<timestamp>,v0=<hmac>"
    if (preg_match('/t=(\d+)/', $cab, $mt) && preg_match('/v0=([a-f0-9]+)/i', $cab, $mv)) {
        $esperado = hash_hmac('sha256', $mt[1] . '.' . $corpo, $segredo);
        $ok = hash_equals($esperado, strtolower($mv[1]));
    }

    if (!$ok) {
        sw_log('RECUSADO assinatura invalida', 'cabecalho=' . $cab);
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Assinatura inválida']);
        exit;
    }
} else {
    sw_log('AVISO sem segredo configurado', 'a aceitar sem verificar assinatura');
}

$in = json_decode($corpo, true);
if (!is_array($in)) {
    sw_log('RECUSADO json invalido', substr($corpo, 0, 500));
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

$tipo = (string) ($in['type'] ?? '');
$d    = $in['data'] ?? [];

// Só interessa a transcrição; o webhook de áudio não traz nada de útil aqui.
if ($tipo !== '' && $tipo !== 'post_call_transcription') {
    sw_log('IGNORADO tipo ' . $tipo);
    echo json_encode(['ok' => true, 'ignored' => $tipo]);
    exit;
}

$conversa = (string) ($d['conversation_id'] ?? '');
$analise  = $d['analysis'] ?? [];
$campos   = $analise['data_collection_results'] ?? [];

/**
 * Lê um campo extraído pelo agente. Aceita vários nomes porque quem os cria na
 * consola do ElevenLabs escreve como quer — "nome", "name", "nome_cliente".
 */
function sw_campo(array $campos, array $nomes)
{
    foreach ($nomes as $n) {
        foreach ($campos as $k => $v) {
            if (strcasecmp((string) $k, $n) !== 0) {
                continue;
            }
            $valor = is_array($v) ? ($v['value'] ?? '') : $v;
            $valor = trim((string) $valor);
            if ($valor !== '' && strcasecmp($valor, 'null') !== 0) {
                return $valor;
            }
        }
    }

    return '';
}

/*
 * Leitura do consentimento — em BRUTO, sem passar por string.
 *
 * O ElevenLabs devolve o campo quer_contacto como BOOLEANO. Em PHP,
 * (string) false é "" — exactamente igual a "campo não configurado". O
 * sw_campo() faz essa conversão, por isso um "não" chegava aqui
 * indistinguível de um campo em falta, e a regra antiga deixava passar os
 * dois. Foi assim que a campanha de 31/07/2026 criou 620 tarefas em poucos
 * minutos sem uma única recusa registada: o sim e o não davam tarefa.
 *
 * Na dúvida não se cria tarefa. Só o sim explícito conta.
 */
function sw_consentiu(array $campos)
{
    $nomes = ['quer_contacto', 'aceita_contacto', 'contactar', 'quer_gestor', 'wants_contact'];

    foreach ($campos as $k => $v) {
        if (!in_array(mb_strtolower((string) $k, 'UTF-8'), $nomes, true)) {
            continue;
        }

        $valor = is_array($v) ? ($v['value'] ?? null) : $v;

        if (is_bool($valor))    { return $valor; }
        if (is_numeric($valor)) { return (float) $valor !== 0.0; }
        if (is_string($valor))  {
            return in_array(mb_strtolower(trim($valor), 'UTF-8'), [
                'sim', 's', 'yes', 'y', 'true', 'ok', 'okay', 'claro', 'quero',
                'aceito', 'pode', 'podem', 'positivo', 'afirmativo', 'com certeza',
            ], true);
        }

        return false;   // null / tipo inesperado: não é um sim
    }

    return false;       // o campo nem veio: não é um sim
}

/**
 * O consentimento em TRÊS estados, não dois.
 *
 * sw_consentiu() devolve false tanto para "o cliente disse não" como para "o
 * agente não tem o campo configurado". Confundir os dois foi o que estragou a
 * primeira tentativa desta regra, a 31/07/2026: agentes sem Data Collection
 * davam sempre false e 473 chamadas ficaram sem tarefa nenhuma.
 *
 * @return string 'sim' | 'nao' | 'sem_campo'
 */
function sw_consentimento(array $campos)
{
    $nomes = ['quer_contacto', 'aceita_contacto', 'contactar', 'quer_gestor', 'wants_contact'];

    foreach ($campos as $k => $v) {
        if (in_array(mb_strtolower((string) $k, 'UTF-8'), $nomes, true)) {
            return sw_consentiu($campos) ? 'sim' : 'nao';
        }
    }

    return 'sem_campo';
}

/**
 * O cliente disse que sim, algures na conversa?
 *
 * Só serve de recurso, quando o agente não tem o campo de consentimento. Lê-se
 * o que o CLIENTE disse (nunca o que o agente disse, senão a própria pergunta
 * da Marta contava como resposta) e o resumo que o ElevenLabs escreve.
 */
function sw_sim_na_conversa(array $d, array $analise)
{
    $ditos = [];
    foreach (($d['transcript'] ?? []) as $linha) {
        if (!is_array($linha)) {
            continue;
        }
        $papel = mb_strtolower((string) ($linha['role'] ?? ''), 'UTF-8');
        if ($papel !== 'user') {
            continue;
        }
        $ditos[] = mb_strtolower((string) ($linha['message'] ?? ''), 'UTF-8');
    }

    $texto = ' ' . implode(' | ', $ditos) . ' ';

    // Um "não" explícito manda mais do que qualquer sim que venha antes.
    if (preg_match('/\b(n[ãa]o tenho interesse|n[ãa]o obrigad|n[ãa]o quero|n[ãa]o me interessa|n[ãa]o volt)/u', $texto)) {
        return false;
    }

    if (preg_match('/\b(sim|claro|quero|pode(m)? ligar|com certeza|est[áa] bem|aceito|por favor)\b/u', $texto)) {
        return true;
    }

    // O resumo do ElevenLabs diz muitas vezes em claro que a pessoa aceitou.
    $resumo = mb_strtolower((string) ($analise['transcript_summary'] ?? ''), 'UTF-8');
    if (preg_match('/(agreed to be contacted|accepted|aceitou ser contactad|concordou em ser contactad|expressed interest)/u', $resumo)) {
        return true;
    }

    return false;
}

/*
 * Atendedor de chamadas — a chamada foi atendida por uma maquina.
 *
 * 28% das chamadas de 31/07/2026 (176 em 628) cairam em atendedor e criaram
 * tarefa na mesma: o comercial abria uma tarefa para descobrir que ninguem
 * tinha falado com ninguem. Nao ha campo proprio no payload, mas o resumo do
 * ElevenLabs di-lo sempre em claro ("voicemail system", "answering machine"),
 * e a transcricao traz a ferramenta de deteccao. Basta uma das duas.
 *
 * Nao se confunde com "nao atendeu": aqui houve atendimento, por uma maquina.
 * Estas ficam registadas para se voltar a ligar mais tarde.
 */
function sw_atendedor(array $d)
{
    $marcas = [
        'voicemail', 'answering machine', 'answering service', 'voice mail',
        'automated message', 'recording system', 'record a message',
        'record your message', 'atendedor', 'caixa postal', 'gravar mensagem',
    ];

    $texto = mb_strtolower((string) ($d['analysis']['transcript_summary'] ?? ''), 'UTF-8');

    foreach ($marcas as $m) {
        if ($texto !== '' && strpos($texto, $m) !== false) {
            return true;
        }
    }

    if (!empty($d['transcript']) && is_array($d['transcript'])) {
        foreach ($d['transcript'] as $linha) {
            $bruto = mb_strtolower((string) json_encode($linha, JSON_UNESCAPED_UNICODE), 'UTF-8');
            if (strpos($bruto, 'voicemail') !== false) {
                return true;
            }
        }
    }

    return false;
}


$nome  = sw_campo($campos, ['nome', 'name', 'nome_cliente', 'client_name', 'customer_name']);
$tel   = sw_campo($campos, ['telefone', 'phone', 'phone_number', 'telemovel', 'contacto']);
$email = sw_campo($campos, ['email', 'e_mail', 'email_cliente']);
$emp   = sw_campo($campos, ['empreendimento', 'projeto', 'projecto', 'development', 'interesse_empreendimento']);
$notas = sw_campo($campos, ['interesse', 'notas', 'resumo', 'summary', 'observacoes']);
$quer  = sw_campo($campos, ['quer_contacto', 'aceita_contacto', 'contactar', 'quer_gestor', 'wants_contact']);

// Resumo da conversa como recurso, quando não há campo de notas.
if ($notas === '') {
    $notas = trim((string) ($analise['transcript_summary'] ?? ''));
}

/*
 * TELEFONE — o dado mais fiável não é o que o modelo percebeu, é o que a
 * operadora sabe.
 *
 * Nas campanhas de SAÍDA o número é o DESTINO: já se sabe para quem se está a
 * ligar, e a Sofia não tem de o perguntar. Vem em dois sítios, conforme a
 * chamada e a versão:
 *
 *   - metadata: 'to_number'/'called_number' nas de saída,
 *               'caller_id'/'from_number' nas de entrada;
 *   - dynamic_variables: a coluna phone_number do CSV da campanha, mais o
 *     system__called_number que o ElevenLabs injeta.
 *
 * Procura-se em ambos, e nos dois sentidos, para o mesmo código servir chamadas
 * de entrada e de saída sem depender de configuração.
 */
if ($tel === '') {
    $meta = is_array($d['metadata'] ?? null) ? $d['metadata'] : [];
    $dyn  = $d['conversation_initiation_client_data']['dynamic_variables'] ?? [];
    $dyn  = is_array($dyn) ? $dyn : [];

    $fontes = array_merge($dyn, $meta);   // metadata ganha: vem da operadora

    foreach ([
        'to_number', 'called_number', 'destination_number', 'recipient_phone_number',
        'system__called_number', 'phone_number', 'telefone',
        'caller_id', 'from_number', 'external_number', 'system__caller_id',
    ] as $k) {
        foreach ($fontes as $chave => $valor) {
            if (strcasecmp((string) $chave, $k) !== 0) {
                continue;
            }
            $valor = trim((string) $valor);
            if ($valor !== '' && preg_match('/\d{6,}/', $valor)) {
                $tel = $valor;
                sw_log('TELEFONE DA CHAMADA', $k . '=' . $valor);
                break 2;
            }
        }
    }
}

// O nome também pode vir da campanha (coluna 'name' do CSV).
if ($nome === '') {
    $dyn = $d['conversation_initiation_client_data']['dynamic_variables'] ?? [];
    foreach (['name', 'nome', 'user_name', 'lead_name'] as $k) {
        if (is_array($dyn) && !empty($dyn[$k])) {
            $nome = trim((string) $dyn[$k]);
            break;
        }
    }
}

/*
 * Último recurso: varrer a transcrição à procura de um número português.
 *
 * Aconteceu a 30/07/2026 numa chamada real — o agente ainda não tinha os
 * campos de Data Collection criados e o pedido chegou vazio. O cliente tinha
 * ditado o número em voz alta ("925708456"), e estava ali na transcrição.
 * Perder uma oportunidade por causa de um campo por configurar é caro demais.
 */
if ($tel === '' && !empty($d['transcript']) && is_array($d['transcript'])) {
    $falado = '';
    foreach ($d['transcript'] as $linha) {
        $falado .= ' ' . (is_array($linha) ? (string) ($linha['message'] ?? '') : (string) $linha);
    }
    // 9 dígitos começados por 9 (telemóvel) ou 2 (fixo), com espaços à mistura
    if (preg_match('/\b([92]\d(?:[\s.-]?\d){7})\b/', $falado, $mm)) {
        $tel = preg_replace('/[^0-9]/', '', $mm[1]);
        sw_log('TELEFONE DA TRANSCRICAO', $tel);
    }
}

/*
 * REGRA DA OPORTUNIDADE — está aqui de propósito, para se poder ler e mudar.
 *
 * Duas condições, ambas necessárias:
 *
 *  1. A PESSOA ACEITOU ser contactada por um gestor. A Sofia pergunta; só o
 *     "sim" gera tarefa. Sem isto, cada chamada atendida criava trabalho para
 *     a equipa, incluindo enganos e curiosos — e uma lista cheia de tarefas
 *     que ninguém quer fechar deixa de ser lida.
 *
 *  2. Há como lhe chegar: telefone ou email.
 *
 * O campo do agente pode vir "true", "sim", "yes"... aceita-se qualquer forma
 * afirmativa. Quando o campo não existe de todo (agente ainda sem o Data
 * Collection configurado), não se trava: vale a condição do contacto, para não
 * perder oportunidades enquanto a configuração não estiver feita.
 */
/*
 * SÓ HÁ TAREFA QUANDO O CLIENTE DIZ QUE SIM. Regra do dono (11/08/2026).
 *
 * Esta regra já esteve cá a 31/07 e foi retirada no mesmo dia: travou 473
 * chamadas. A causa não era a regra, era como se lia o "sim" — agentes sem o
 * campo de Data Collection davam sempre não, e ninguém percebeu porquê.
 *
 * Agora o consentimento tem três estados. Com o campo configurado, manda o
 * campo. Sem campo, lê-se o que o CLIENTE disse na conversa, que é melhor do
 * que assumir. O motivo de não haver tarefa fica sempre escrito no registo, e
 * quando o campo falta ao agente isso é dito em claro — para se corrigir a
 * configuração em vez de se voltar a culpar a regra.
 */
$consent = sw_consentimento($campos);

if ($consent === 'sem_campo') {
    $aceitou = sw_sim_na_conversa($d, $analise);
    sw_log('AGENTE SEM CAMPO DE CONSENTIMENTO',
        'conversa=' . $conversa . ' — decidido pela transcrição: ' . ($aceitou ? 'SIM' : 'nao')
        . ' | configure "quer_contacto" no Data Collection deste agente');
} else {
    $aceitou = ($consent === 'sim');
}

// --- base de dados ---
$cfg = @file_get_contents(__DIR__ . '/application/config/app-config.php');
function sw_cfg($c, $k) { return preg_match('/' . $k . "['\"]\s*,\s*['\"](.*?)['\"]/", $c, $m) ? $m[1] : null; }

$bd = @new mysqli(sw_cfg($cfg, 'APP_DB_HOSTNAME'), sw_cfg($cfg, 'APP_DB_USERNAME'), sw_cfg($cfg, 'APP_DB_PASSWORD'), sw_cfg($cfg, 'APP_DB_NAME'));
if ($bd->connect_errno) {
    sw_log('ERRO base de dados', $bd->connect_error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB']);
    exit;
}
$bd->set_charset('utf8mb4');
$p = sw_cfg($cfg, 'APP_DB_PREFIX') ?: 'tbl';

/*
 * Atendedor: nao ha pessoa do outro lado, logo nao ha nada para o comercial
 * fazer com esta chamada. Fica no registo para se poder voltar a ligar.
 */
if (sw_atendedor($d)) {
    sw_marcar_resultado($bd, $p, $conversa, 'nao_atendida', (string) ($analise['transcript_summary'] ?? ''));
    sw_log('ATENDEDOR DE CHAMADAS — sem tarefa',
        'conversa=' . $conversa . ' tel=' . ($tel !== '' ? $tel : '(sem numero)'));
    echo json_encode(['ok' => true, 'task_created' => false, 'reason' => 'atendedor de chamadas']);
    exit;
}


if (! $aceitou) {
    sw_marcar_resultado($bd, $p, $conversa, 'nao', (string) ($analise['transcript_summary'] ?? ''));
    sw_log('SEM SIM — sem tarefa',
        'conversa=' . $conversa . ' | consentimento=' . $consent
        . ' | tel=' . ($tel !== '' ? $tel : '(sem numero)')
        . ' | resumo=' . mb_substr((string) ($analise['transcript_summary'] ?? ''), 0, 160));
    echo json_encode(['ok' => true, 'task_created' => false, 'reason' => 'o cliente nao aceitou ser contactado']);
    exit;
}

$tem_contacto = ($tel !== '' || ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)));



if (!$tem_contacto) {
    /*
     * Registo enxuto e útil.
     *
     * A primeira versão despejava o metadata inteiro — e o do ElevenLabs traz
     * facturação, contagem de tokens e uso de TTS, dezenas de linhas que
     * esgotavam o limite e escondiam justamente os campos que se queria ver.
     * Regista-se agora só o que serve para perceber porque não houve tarefa.
     */
    $meta_log = [];
    foreach ((array) ($d['metadata'] ?? []) as $k => $vv) {
        if (!is_array($vv) && preg_match('/number|phone|caller|sip|from|to$/i', (string) $k)) {
            $meta_log[$k] = $vv;
        }
    }
    $dyn_log = array_keys((array) ($d['conversation_initiation_client_data']['dynamic_variables'] ?? []));

    /*
     * Conversa de texto (Preview do ElevenLabs) não tem telefone nenhum — não
     * há chamada, logo não há número de origem nem de destino. Dizê-lo em
     * claro poupa o tempo de procurar avaria onde não há: aconteceu duas vezes
     * a 31/07/2026, com dois testes seguidos pelo Preview.
     */
    $so_texto = !empty($d['conversation_initiation_client_data']['dynamic_variables']['system__is_text_only'])
        || ($d['metadata']['phone_call'] ?? 'x') === null;

    sw_log($so_texto
            ? 'SEM CONTACTO — conversa de TEXTO (Preview), nao ha telefone: normal'
            : 'SEM CONTACTO — sem tarefa',
        'conversa=' . $conversa
        . ' | campos=' . (empty($campos) ? '(nenhum configurado)' : json_encode($campos, JSON_UNESCAPED_UNICODE))
        . ' | metadata_tel=' . (empty($meta_log) ? '(nenhum campo de telefone)' : json_encode($meta_log, JSON_UNESCAPED_UNICODE))
        . ' | variaveis=' . (empty($dyn_log) ? '(nenhuma)' : implode(',', $dyn_log))
        . ' | linhas_transcricao=' . (is_array($d['transcript'] ?? null) ? count($d['transcript']) : 0)
        . ' | resumo=' . substr((string) ($analise['transcript_summary'] ?? ''), 0, 200));
    /*
     * Sem contacto não há tarefa — não há a quem ligar. Mas há conversa, e é
     * exactamente a conversa do site que antes não deixava rasto nenhum: a
     * pessoa perguntava preços à Sofia, ia-se embora, e ninguém no CRM ficava
     * a saber que tinha existido. Avisa-se a direção, com o resumo.
     */
    $resumo = trim((string) ($analise['transcript_summary'] ?? ''));
    $onde   = $so_texto ? 'no site' : 'numa chamada';

    sw_avisar_crm($bd, $p,
        1,
        '💬 Conversa com a Sofia ' . $onde . ', sem contacto deixado'
            . ($resumo !== '' ? ' — ' . mb_substr($resumo, 0, 160) : ''),
        'tasks'
    );

    sw_whatsapp_direcao($bd, $p,
        "💬 *Conversa com a Sofia* (" . $onde . ")\n\n"
        . "A pessoa não deixou contacto, por isso não há tarefa.\n\n"
        . ($resumo !== '' ? "Resumo:\n" . mb_substr($resumo, 0, 700) . "\n\n" : '')
        . 'Conversa: ' . $conversa
    );

    echo json_encode(['ok' => true, 'task_created' => false, 'reason' => 'sem contacto']);
    exit;
}


/*
 * Não duplicar: o ElevenLabs repete o envio quando não recebe 200 depressa.
 * O conversation_id vai na descrição e serve de marca.
 */
if ($conversa !== '') {
    $st = $bd->prepare("SELECT id FROM {$p}tasks WHERE description LIKE ? LIMIT 1");
    $marca = '%[sofia:' . $conversa . ']%';
    $st->bind_param('s', $marca);
    $st->execute();
    if ($st->get_result()->fetch_assoc()) {
        sw_log('REPETIDO — ja existia', 'conversa=' . $conversa);
        echo json_encode(['ok' => true, 'task_created' => false, 'reason' => 'já registado']);
        exit;
    }
    $st->close();
}

/*
 * A QUEM FICA A TAREFA — pelo número de telefone.
 *
 * Procura-se uma lead com aquele telefone e a tarefa vai para o comercial que
 * já a acompanha. É a diferença entre "alguém que ligou" e "o cliente do
 * Sérgio ligou": quem já falou com a pessoa retoma a conversa em vez de a
 * obrigar a explicar tudo de novo.
 *
 * Compara-se pelos ÚLTIMOS 9 DÍGITOS: o número chega da operadora com
 * indicativo (351...) e na lead está muitas vezes sem ele. Comparar as
 * cadeias inteiras nunca daria coincidência.
 */
/**
 * Cria a lead da chamada e etiqueta-a com SITE + o site de onde veio.
 *
 * O estado é "Novos" (4) e a fonte a mesma das campanhas — a lead entra no
 * funil como qualquer outra, para não ficar num limbo só dela.
 *
 * As etiquetas são criadas se não existirem. INSERT IGNORE na ligação: a
 * mesma etiqueta duas vezes na mesma lead não é erro, é ruído.
 *
 * @return int id da lead, ou 0 se não foi possível criar
 */
/**
 * O número em forma de se poder ligar.
 *
 * A Sofia devolve o que a operadora lhe dá, e isso vem tantas vezes sem
 * indicativo — "966604036". Gravado assim, o botão de ligar do CRM marca nove
 * dígitos e a chamada dá "número indisponível"; o link do WhatsApp também não
 * abre conversa nenhuma. Põe-se o +351 nos números portugueses de nove
 * dígitos e normaliza-se o que já vem com o indicativo colado.
 *
 * Números estrangeiros ficam como estão: pôr-lhes +351 seria pior do que
 * deixá-los sem nada.
 */
function sw_telefone_pt($tel)
{
    $tel = trim((string) $tel);

    if ($tel === '') {
        return '';
    }

    $d = preg_replace('/\D+/', '', $tel);

    // 9 dígitos: telemóvel (9) ou fixo (2) português.
    if (strlen($d) === 9 && ($d[0] === '9' || $d[0] === '2')) {
        return '+351' . $d;
    }

    // Já traz o indicativo, com ou sem o sinal.
    if (strlen($d) === 12 && strpos($d, '351') === 0) {
        return '+' . $d;
    }

    // Outra coisa qualquer — devolve-se como veio.
    return $tel;
}

function sw_criar_lead($bd, $p, $nome, $tel, $email, $emp, $notas, $conversa)
{
    $nome = trim((string) $nome) !== '' ? trim((string) $nome) : 'Contacto Sofia';
    $tel  = sw_telefone_pt($tel);

    $descricao = "Lead criada a partir de uma chamada atendida pela Sofia.\n"
        . 'Empreendimento: ' . ($emp !== '' ? $emp : '—') . "\n"
        . ($notas !== '' ? "\nResumo da conversa:\n" . $notas . "\n" : '')
        . "\n[sofia:" . $conversa . ']';

    $agora = date('Y-m-d H:i:s');

    /*
     * FONTE: IMO PORTUGAL. Regra do dono (21/08/2026).
     *
     * Gravei source=2 à conta de ser o que o mv-lead.php usa, e nesta base o
     * id 2 não corresponde a fonte nenhuma — uma lead com fonte órfã não
     * aparece na listagem, e as sete primeiras ficaram invisíveis com tudo o
     * resto certo. Cheguei a criar uma fonte "Sofia"; o dono quer estas leads
     * na mesma fonte das outras, e tem razão: o que as distingue é a etiqueta
     * SITE, não uma origem à parte que fragmenta os relatórios.
     *
     * Procura-se pelo nome, com recurso ao primeiro id que exista — nunca um
     * número escrito à mão, que foi o que causou isto.
     */
    $fonte = 0;
    $st = $bd->prepare("SELECT id FROM {$p}leads_sources WHERE name = 'IMO Portugal' LIMIT 1");
    $st->execute();
    if ($linha = $st->get_result()->fetch_assoc()) {
        $fonte = (int) $linha['id'];
    }
    $st->close();

    if (!$fonte) {
        $q = $bd->query("SELECT id FROM {$p}leads_sources ORDER BY id LIMIT 1");
        $fonte = $q && ($x = $q->fetch_assoc()) ? (int) $x['id'] : 0;
    }

    $st = $bd->prepare(
        "INSERT INTO {$p}leads (status, source, assigned, name, email, phonenumber, description,
                                dateadded, addedfrom, is_public, lastcontact)
         VALUES (4, ?, 1, ?, ?, ?, ?, ?, 1, 0, NULL)"
    );

    if (!$st) {
        sw_log('ERRO ao preparar a criação da lead', $bd->error);

        return 0;
    }

    $st->bind_param('isssss', $fonte, $nome, $email, $tel, $descricao, $agora);
    $st->execute();
    $lead = (int) $st->insert_id;
    $st->close();

    if (!$lead) {
        sw_log('ERRO ao criar a lead', $bd->error);

        return 0;
    }

    /*
     * SITE, e o nome do site. O empreendimento que a Sofia apurou é o que
     * identifica de onde veio a chamada — cada site tem o seu agente.
     */
    $etiquetas = ['SITE'];
    if (trim((string) $emp) !== '') {
        $etiquetas[] = trim((string) $emp);
    }

    foreach ($etiquetas as $et) {
        $et = mb_substr(trim(preg_replace('/\s+/', ' ', $et)), 0, 50);
        if ($et === '') { continue; }

        $st = $bd->prepare("SELECT id FROM {$p}tags WHERE name = ? LIMIT 1");
        $st->bind_param('s', $et);
        $st->execute();
        $linha = $st->get_result()->fetch_assoc();
        $st->close();

        if ($linha) {
            $tag_id = (int) $linha['id'];
        } else {
            $st = $bd->prepare("INSERT INTO {$p}tags (name) VALUES (?)");
            $st->bind_param('s', $et);
            $st->execute();
            $tag_id = (int) $st->insert_id;
            $st->close();
        }

        if ($tag_id) {
            $st = $bd->prepare("INSERT IGNORE INTO {$p}taggables (tag_id, rel_id, rel_type, tag_order)
                                VALUES (?, ?, 'lead', 0)");
            $st->bind_param('ii', $tag_id, $lead);
            $st->execute();
            $st->close();
        }
    }

    sw_log('LEAD CRIADA', 'id=' . $lead . ' nome=' . $nome . ' tel=' . $tel
        . ' etiquetas=' . implode('+', $etiquetas));

    return $lead;
}

$staff_destino = 1;                 // por omissão, o Ricardo
$lead_id       = 0;
$como          = 'sem correspondência — fica com a direção';

$digitos = preg_replace('/[^0-9]/', '', $tel);
$fim9    = strlen($digitos) >= 9 ? substr($digitos, -9) : '';

if ($fim9 !== '') {
    $st = $bd->prepare(
        "SELECT id, name, assigned FROM {$p}leads
         WHERE RIGHT(REPLACE(REPLACE(REPLACE(phonenumber,' ',''),'-',''),'+',''), 9) = ?
           AND assigned > 0
         ORDER BY id DESC LIMIT 1"
    );
    if ($st) {
        $st->bind_param('s', $fim9);
        $st->execute();
        if ($lead = $st->get_result()->fetch_assoc()) {
            $staff_destino = (int) $lead['assigned'];
            $lead_id       = (int) $lead['id'];
            $como          = 'lead #' . $lead_id . ' (' . $lead['name'] . ')';
        }
        $st->close();
    }
}

/*
 * SEM LEAD, CRIA-SE A LEAD. Regra do dono (21/08/2026).
 *
 * Até aqui, quem ligasse pela Sofia sem já existir no CRM só gerava uma
 * tarefa: o contacto ficava escrito na descrição dela e mais nada. Não entrava
 * no funil, não contava para nada, e ninguém lhe podia mudar o estado — quando
 * é precisamente uma pessoa que ligou por vontade própria a partir de um site.
 *
 * Agora nasce lead, com o que a Sofia recolheu, e a tarefa fica ligada a ela.
 * Vai com duas etiquetas: SITE, para se saber por onde entrou, e o nome do
 * site que gerou a chamada.
 */
if ($lead_id === 0 && ($tel !== '' || $email !== '')) {
    $lead_id = sw_criar_lead($bd, $p, $nome, $tel, $email, $emp, $notas, $conversa);

    if ($lead_id > 0) {
        $como = 'lead #' . $lead_id . ' criada agora pela Sofia';
    }
}

$titulo = '☎️ Sofia: contactar' . ($emp !== '' ? ' — ' . $emp : '') . ($nome !== '' ? ': ' . $nome : '');

$desc  = "Oportunidade detetada pela assistente virtual Sofia numa chamada.\n\n";
$desc .= 'Nome: ' . ($nome ?: '—') . "\n";
$desc .= 'Telefone: ' . ($tel ?: '—') . "\n";
$desc .= 'Email: ' . ($email ?: '—') . "\n";
$desc .= 'Empreendimento: ' . ($emp ?: '—') . "\n";
if ($notas !== '') {
    $desc .= "\nResumo da conversa:\n" . $notas . "\n";
}
$desc .= "\nAtribuída a: staff " . $staff_destino . ' — ' . $como . "\n";
$desc .= "\n[sofia:" . $conversa . ']';

$agora = date('Y-m-d H:i:s');
$hoje  = date('Y-m-d');
$task  = 0;

// Quando há lead, a tarefa fica ligada a ela e aparece na ficha do cliente.
$rel_tipo = $lead_id ? 'lead' : '';

$st = $bd->prepare("INSERT INTO {$p}tasks
    (name, description, priority, dateadded, startdate, duedate, status, addedfrom,
     is_added_from_contact, rel_id, rel_type, is_public, billable, visible_to_client, kanban_order)
    VALUES (?, ?, 3, ?, ?, ?, 1, 1, 0, ?, ?, 0, 0, 0, 0)");
if ($st) {
    $st->bind_param('sssssis', $titulo, $desc, $agora, $hoje, $hoje, $lead_id, $rel_tipo);
    $st->execute();
    $task = (int) $st->insert_id;
    $st->close();
}

if (!$task) {
    sw_log('ERRO ao criar tarefa', $bd->error);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Falha ao criar a tarefa']);
    exit;
}

$st = $bd->prepare("INSERT INTO {$p}task_assigned (staffid, taskid, assigned_from, is_assigned_from_contact) VALUES (?, ?, 1, 0)");
$st->bind_param('ii', $staff_destino, $task);
$st->execute();
$st->close();

/*
 * Avisar. A tarefa por si só não avisa ninguém: fica na lista à espera de que
 * alguém a abra. O comercial recebe no sino do CRM; a direção recebe sempre,
 * no sino e no WhatsApp, mesmo quando a tarefa é de outra pessoa.
 */
$aviso = '☎️ Sofia: ' . ($nome !== '' ? $nome : 'contacto novo')
    . ($emp !== '' ? ' — ' . $emp : '') . ($tel !== '' ? ' (' . $tel . ')' : '');

sw_avisar_crm($bd, $p, $staff_destino, $aviso, 'tasks/view/' . $task);

if ((int) $staff_destino !== 1) {
    sw_avisar_crm($bd, $p, 1, $aviso . ' — tarefa de staff ' . $staff_destino, 'tasks/view/' . $task);
}

sw_whatsapp_direcao($bd, $p,
    "☎️ *Nova oportunidade da Sofia*\n\n"
    . 'Nome: ' . ($nome ?: '—') . "\n"
    . 'Telefone: ' . ($tel ?: '—') . "\n"
    . 'Email: ' . ($email ?: '—') . "\n"
    . 'Empreendimento: ' . ($emp ?: '—') . "\n"
    . 'Fica com: ' . $como . "\n"
    . ($notas !== '' ? "\nResumo:\n" . mb_substr($notas, 0, 700) . "\n" : '')
    . "\nTarefa: https://crm.grupo-dps.com/admin/tasks/view/" . $task
);

sw_marcar_resultado($bd, $p, $conversa, 'sim', $notas !== '' ? $notas : (string) ($analise['transcript_summary'] ?? ''));

sw_log('TAREFA CRIADA', 'id=' . $task . ' conversa=' . $conversa . ' nome=' . $nome
    . ' tel=' . $tel . ' -> staff ' . $staff_destino . ' [' . $como . ']');

echo json_encode(['ok' => true, 'task_created' => true, 'task_id' => $task]);
