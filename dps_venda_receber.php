<?php
/**
 * Recebe vendas vindas do Modo Edição do simulador (dpsimobiliario.pt).
 *
 * Ao marcar uma unidade como VENDIDO, o simulador pergunta para que comercial
 * é a venda e chama este endpoint, que cria a venda em PENDENTE no CRM.
 *
 * Acções:
 *   GET  ?a=comerciais&t=TOKEN   -> lista de comerciais activos
 *   POST  a=importar             -> cria a venda
 *
 * Segurança — ler antes de mexer:
 *   O token vive em /home/u172337921/.dps_venda_secret (fora da raiz web).
 *   Se o ficheiro não existir, este endpoint recusa tudo. NÃO acrescentar um
 *   token por omissão no código: foi esse o erro do dps_site_deploy.php.
 *
 *   O token acaba por ir no JavaScript do simulador, que é público. Isso é
 *   inevitável num site estático e significa que alguém determinado consegue
 *   criar vendas falsas. O estrago está contido de propósito: este ficheiro
 *   só faz INSERT numa tabela, com campos fixos, sempre em estado "pendente".
 *   Não lê dados, não apaga, não executa SQL arbitrário.
 */

declare(strict_types=1);

// Fora do CodeIgniter não se herda o fuso do CRM: sem isto as reservas
// ficavam gravadas com menos uma hora (o servidor corre em UTC).
date_default_timezone_set('Europe/Lisbon');

const ORIGEM_PERMITIDA = 'https://dpsimobiliario.pt';
const NOME_SEGREDO = '.dps_venda_secret';

/**
 * O gestor de ficheiros da Hostinger mostra uma raiz que nem sempre coincide
 * com o caminho real no disco. Em vez de fixar um caminho e falhar em silêncio,
 * procuramos o segredo nos sítios plausíveis — acima da raiz web, na home da
 * conta, ou onde o PHP disser que é a home.
 */
function localizar_segredo(): ?string
{
    $candidatos = [
        // Preferido: dentro de uploads/, que o deploy por FTP EXCLUI do sync
        // (`**/uploads/**`). Ficheiros fora do repo em qualquer outro sítio do
        // docroot são apagados a cada push — foi o que matou o dps_secure/.
        // Protegido do browser pelo .htaccess "Deny from all" na mesma pasta.
        __DIR__ . '/uploads/dps_secure/venda_secret',
        // Alternativa histórica (é apagada pelos deploys — manter só por compat.)
        __DIR__ . '/dps_secure/venda_secret',
        '/home/u172337921/' . NOME_SEGREDO,
        dirname(__DIR__) . '/' . NOME_SEGREDO,
        dirname(__DIR__, 2) . '/' . NOME_SEGREDO,
        dirname(__DIR__, 3) . '/' . NOME_SEGREDO,
        dirname(__DIR__, 4) . '/' . NOME_SEGREDO,
    ];

    $home = getenv('HOME');
    if (is_string($home) && $home !== '') {
        $candidatos[] = rtrim($home, '/') . '/' . NOME_SEGREDO;
    }

    foreach (array_unique($candidatos) as $caminho) {
        if (is_readable($caminho) && trim((string) file_get_contents($caminho)) !== '') {
            return $caminho;
        }
    }

    return null;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ORIGEM_PERMITIDA);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function responder(array $dados, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

/*
 * Desde o PHP 8.1 o mysqli lança excepções por omissão. Sem isto apanhado, um
 * erro de SQL sai como 500 de corpo vazio e ficamos às escuras. Como o
 * endpoint só responde a quem tem o token, devolvemos a mensagem real — vale
 * mais um diagnóstico claro do que adivinhar.
 */
set_exception_handler(static function (Throwable $e): void {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro interno.',
        'detalhe' => $e->getMessage(),
        'onde'    => basename($e->getFile()) . ':' . $e->getLine(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

/* ---------------------------------------------------------------------
 * Autenticação
 * ------------------------------------------------------------------ */

$ficheiro_segredo = localizar_segredo();

if ($ficheiro_segredo === null) {
    // Sem segredo não se abre a porta, nem com um valor de reserva.
    // Devolvemos os caminhos tentados para se perceber onde falta pôr o
    // ficheiro — são caminhos, não revelam nada de sensível.
    responder([
        'success'    => false,
        'error'      => 'Endpoint não configurado: não encontrei o ' . NOME_SEGREDO . '.',
        'procurei_em' => [
            '/home/u172337921/',
            dirname(__DIR__) . '/',
            dirname(__DIR__, 2) . '/',
            dirname(__DIR__, 3) . '/',
            getenv('HOME') ?: '(HOME indefinido)',
        ],
        'raiz_web'   => __DIR__,
    ], 503);
}

$segredo = trim((string) file_get_contents($ficheiro_segredo));

$token = (string) ($_GET['t'] ?? $_POST['token'] ?? '');

if (!hash_equals($segredo, $token)) {
    responder(['success' => false, 'error' => 'Não autorizado.'], 403);
}

/* ---------------------------------------------------------------------
 * Ligação à base de dados (credenciais do próprio Perfex)
 * ------------------------------------------------------------------ */

function ligar_bd(): mysqli
{
    $config = __DIR__ . '/application/config/app-config.php';

    if (!is_readable($config)) {
        responder(['success' => false, 'error' => 'Configuração indisponível.'], 500);
    }

    $conteudo = (string) file_get_contents($config);

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
        responder(['success' => false, 'error' => 'Configuração incompleta.'], 500);
    }

    $ligacao = @new mysqli($host, $user, $pass, $nome);

    if ($ligacao->connect_error) {
        responder(['success' => false, 'error' => 'Base de dados indisponível.'], 500);
    }

    $ligacao->set_charset('utf8mb4');

    return $ligacao;
}

/**
 * Nome livre do empreendimento ("Boavista Towers") -> chave do simulador.
 * A ordem importa: "Gaia Premium" tem de bater antes de "Gaia" (Douro).
 */
function chave_simulador(string $empreendimento): ?string
{
    $nome = mb_strtolower($empreendimento);
    $nome = strtr($nome, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e',
                          'í'=>'i','ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ç'=>'c']);

    foreach ([
        'boavista'  => 'boavista',
        'premium'   => 'gp',
        'douro'     => 'gaiadouro',
        'gaia'      => 'gaiadouro',
        'horizonte' => 'bh',
        'belo'      => 'bh',
        'raiz'      => 'raizes',
        'fanzeres'  => 'raizes',
        'aura'      => 'aura',
        'lake'      => 'lake',
    ] as $pedaco => $chave) {
        if (strpos($nome, $pedaco) !== false) {
            return $chave;
        }
    }

    return null;
}

/* ---------------------------------------------------------------------
 * Acções
 * ------------------------------------------------------------------ */

$accao = (string) ($_GET['a'] ?? $_POST['a'] ?? '');

if ($accao === 'comerciais') {
    $bd = ligar_bd();

    // O flag `admin` serve ao simulador: um comercial vê a reserva já
    // atribuída a si e não a pode desviar para outro; o admin continua a
    // poder escolher (é ele que regista reservas por terceiros).
    $res = $bd->query(
        "SELECT staffid, CONCAT(firstname, ' ', lastname) AS nome, admin
         FROM tblstaff
         WHERE active = 1
         ORDER BY firstname ASC"
    );

    $comerciais = [];
    while ($linha = $res->fetch_assoc()) {
        $comerciais[] = [
            'id'    => (int) $linha['staffid'],
            'nome'  => $linha['nome'],
            'admin' => ((int) $linha['admin'] === 1),
        ];
    }

    responder(['success' => true, 'comerciais' => $comerciais]);
}

if ($accao === 'importar') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        responder(['success' => false, 'error' => 'Método inválido.'], 405);
    }

    $empreendimento = trim((string) ($_POST['empreendimento'] ?? ''));
    $unidade        = trim((string) ($_POST['unidade'] ?? ''));
    $valor          = (float) ($_POST['valor'] ?? 0);
    $comercial_id   = (int) ($_POST['comercial_id'] ?? 0);
    $tipologia      = trim((string) ($_POST['tipologia'] ?? ''));

    /*
     * Dois caminhos, o mesmo destino:
     *  - 'reserva' (omissão): é o COMERCIAL a registar no simulador → entra
     *    já como "reservado".
     *  - 'pedido': é o CLIENTE a submeter pela proposta que recebeu → entra
     *    como "pedido", à espera de confirmação, e NÃO bloqueia a unidade.
     */
    $e_pedido = (($_POST['tipo_registo'] ?? '') === 'pedido');
    $estado   = $e_pedido ? 'pedido' : 'reservado';
    $origem   = $e_pedido ? 'formulario' : 'simulador';

    if ($empreendimento === '' || $unidade === '' || $comercial_id <= 0) {
        responder(['success' => false, 'error' => 'Faltam dados obrigatórios.'], 400);
    }

    if ($valor <= 0) {
        responder(['success' => false, 'error' => 'Valor inválido.'], 400);
    }

    /*
     * Dados completos + Cartão de Cidadão são obrigatórios nos DOIS caminhos:
     * na reserva feita pelo comercial e no pedido feito pelo cliente. A
     * validação do browser é conveniência; esta é a que conta — sem ela era
     * possível gravar uma reserva incompleta contornando o formulário.
     * Validar ANTES de criar o que quer que seja, para não ficar um registo
     * órfão sem documento.
     */
    /*
     * A unidade ainda está disponível? Esta é a validação que conta — sem ela
     * dois comerciais a trabalhar ao mesmo tempo reservavam a mesma fração e
     * só se descobria com os dois clientes já a contar com ela.
     * Só bloqueia com certeza: se o simulador não responder, deixa passar.
     */
    $chave_emp = chave_simulador($empreendimento);

    if ($chave_emp !== null) {
        $ch = curl_init('https://dpsimobiliario.pt/simuladorportugal/save_states.php');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $estados_sim = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        if (is_array($estados_sim) && isset($estados_sim[$chave_emp . '_states'][$unidade])) {
            $estado_unidade = (string) $estados_sim[$chave_emp . '_states'][$unidade];

            if ($estado_unidade !== '' && $estado_unidade !== 'Disponível') {
                responder([
                    'success' => false,
                    'error'   => 'Esta unidade já não está disponível (' . $estado_unidade
                        . '). Escolha outra — actualize o simulador para ver as livres.',
                ], 409);
            }
        }
    }

    /*
     * Campos de texto primeiro: é mais barato do que validar ficheiros e a
     * mensagem que o utilizador recebe é a que interessa.
     */
    foreach ([
        'cliente'       => 'nome',
        'morada'        => 'morada',
        'codigo_postal' => 'código postal',
        'telefone'      => 'telefone',
        'email'         => 'email',
        'estado_civil'  => 'estado civil',
    ] as $campo => $etiqueta) {
        if (trim((string) ($_POST[$campo] ?? '')) === '') {
            responder(['success' => false, 'error' => 'Falta preencher: ' . $etiqueta . '.'], 400);
        }
    }

    /*
     * CAMPOS EXTRA DO CPCV — SÓ NO AURA, E NUNCA OBRIGATÓRIOS NA RESERVA.
     *
     * O contrato-promessa do Meixomil identifica o comprador com estes dados
     * (NIF, Cartão de Cidadão e naturalidade/nacionalidade/freguesia/concelho).
     *
     * Já foram exigidos aqui e travavam a reserva: o comercial tinha o negócio
     * fechado e a unidade por reservar porque lhe faltava a freguesia do
     * comprador. Regra do dono (31/07/2026): "nesta fase funciona como os
     * outros; depois é que no mapa de vendas põe os restantes dados".
     *
     * Guardam-se à mesma quando vierem preenchidos — poupa trabalho a quem
     * depois completa a ficha —, mas a falta deles nunca impede a reserva. Os
     * dados em falta pedem-se no mapa de vendas, antes de gerar o CPCV.
     */
    $e_aura = stripos($empreendimento, 'aura') !== false;

    $limpo = function ($campo) {
        $v = trim((string) ($_POST[$campo] ?? ''));

        return $v === '' ? null : $v;
    };

    $cli_nif      = $e_aura ? $limpo('nif')           : null;
    $cli_cc       = $e_aura ? $limpo('cc_numero')     : null;
    $cli_cc_val   = $e_aura ? $limpo('cc_validade')   : null;
    $cli_natural  = $e_aura ? $limpo('naturalidade')  : null;
    $cli_nacional = $e_aura ? $limpo('nacionalidade') : null;
    $cli_freg     = $e_aura ? $limpo('freguesia')     : null;
    $cli_conc     = $e_aura ? $limpo('concelho')      : null;

    /*
     * Só se valida o que veio. Um formato errado guardado é pior do que um
     * campo vazio: o vazio vê-se no mapa de vendas e pede-se; o errado passa
     * despercebido e sai impresso no contrato.
     */
    if ($cli_cc_val !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $cli_cc_val)) {
        $cli_cc_val = null;
    }

    if (empty($_FILES['cc']['name']) || ($_FILES['cc']['error'] ?? 1) !== UPLOAD_ERR_OK) {
        responder(['success' => false, 'error' => 'É obrigatório anexar a FRENTE do Cartão de Cidadão.'], 400);
    }

    $ext_cc = strtolower(pathinfo($_FILES['cc']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext_cc, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
        responder(['success' => false, 'error' => 'O documento tem de ser PDF, JPG ou PNG.'], 400);
    }
    if (($_FILES['cc']['size'] ?? 0) > 10485760) {
        responder(['success' => false, 'error' => 'O documento excede 10 MB.'], 400);
    }

    /*
     * Verso do CC: obrigatório quando a frente é fotografia. Dispensa-se com
     * um PDF, porque o scan traz normalmente os dois lados no mesmo ficheiro.
     */
    $tem_verso = !empty($_FILES['cc_verso']['name']) && ($_FILES['cc_verso']['error'] ?? 1) === UPLOAD_ERR_OK;

    if (!$tem_verso && $ext_cc !== 'pdf') {
        responder(['success' => false, 'error' => 'É obrigatório anexar também o VERSO do Cartão de Cidadão.'], 400);
    }

    if ($tem_verso) {
        $ext_v = strtolower(pathinfo($_FILES['cc_verso']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_v, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            responder(['success' => false, 'error' => 'O verso tem de ser PDF, JPG ou PNG.'], 400);
        }
        if (($_FILES['cc_verso']['size'] ?? 0) > 10485760) {
            responder(['success' => false, 'error' => 'O verso excede 10 MB.'], 400);
        }
    }

    $bd = ligar_bd();

    // O comercial tem de existir e estar activo
    $stmt = $bd->prepare('SELECT staffid FROM tblstaff WHERE staffid = ? AND active = 1');
    $stmt->bind_param('i', $comercial_id);
    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        responder(['success' => false, 'error' => 'Comercial inválido.'], 400);
    }

    // Idempotência: marcar duas vezes a mesma unidade não deve criar duas vendas
    $stmt = $bd->prepare(
        'SELECT id FROM tblsimulador_vendas
         WHERE empreendimento = ? AND unidade = ?
         LIMIT 1'
    );
    $stmt->bind_param('ss', $empreendimento, $unidade);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();

    if ($existente) {
        responder([
            'success'   => true,
            'duplicado' => true,
            'venda_id'  => (int) $existente['id'],
            'message'   => 'Já existia uma venda para esta unidade. Não foi criada outra.',
        ]);
    }

    // A taxa vem da regra do empreendimento, se houver
    $taxa = null;
    $stmt = $bd->prepare(
        'SELECT taxa, cpcv_taxa, escritura_taxa FROM tblcomissao_regras
         WHERE LOWER(TRIM(empreendimento)) = LOWER(TRIM(?)) AND ativo = 1
         LIMIT 1'
    );
    $stmt->bind_param('s', $empreendimento);
    $stmt->execute();
    $regra = $stmt->get_result()->fetch_assoc();

    // As colunas cpcv_taxa/escritura_taxa não aceitam NULL — usar 0 quando a
    // regra não as define.
    $taxa           = $regra ? (float) $regra['taxa'] : 0.0;
    $cpcv_taxa      = $regra && $regra['cpcv_taxa'] !== null ? (float) $regra['cpcv_taxa'] : 0.0;
    $escritura_taxa = $regra && $regra['escritura_taxa'] !== null ? (float) $regra['escritura_taxa'] : 0.0;

    // Empreendimento novo (sem regra) → criar já uma regra "por definir", para
    // aparecer em Vendas > Regras de Comissão e o admin lá pôr a percentagem.
    if (!$regra) {
        $stmt = $bd->prepare(
            "INSERT INTO tblcomissao_regras (empreendimento, taxa, ativo, notas, dateadded)
             VALUES (?, 0, 1, 'TAXA POR DEFINIR (criada automaticamente a partir de uma venda)', NOW())"
        );
        $stmt->bind_param('s', $empreendimento);
        @$stmt->execute();
    }

    // Dados do cliente recolhidos na reserva pelo comercial
    $cli_nome   = trim((string) ($_POST['cliente'] ?? ''));
    if ($cli_nome === '') {
        $cli_nome = $tipologia !== '' ? 'A preencher (' . $tipologia . ')' : 'A preencher';
    }
    $cli_morada = trim((string) ($_POST['morada'] ?? '')) ?: null;
    $cli_tel    = trim((string) ($_POST['telefone'] ?? '')) ?: null;
    $cli_email  = trim((string) ($_POST['email'] ?? '')) ?: null;
    $cli_civil  = trim((string) ($_POST['estado_civil'] ?? '')) ?: null;
    $cli_tipo   = trim((string) ($_POST['tipo'] ?? '')) ?: null;
    $cli_crc    = trim((string) ($_POST['crc'] ?? '')) ?: null;

    /*
     * Quem assina pela empresa. Só o simulador do Aura os envia, e só quando
     * o comprador é uma empresa — nos outros casos chegam vazios e ficam a
     * null, que é o que o contrato do particular espera.
     */
    $cli_repres     = trim((string) ($_POST['representante'] ?? '')) ?: null;
    $cli_repres_nif = trim((string) ($_POST['representante_nif'] ?? '')) ?: null;
    $cli_cp     = trim((string) ($_POST['codigo_postal'] ?? '')) ?: null;

    $agora = date('Y-m-d H:i:s');
    $hoje  = date('Y-m-d');

    $stmt = $bd->prepare(
        "INSERT INTO tblsimulador_vendas
            (empreendimento, unidade, cliente, cliente_morada, cliente_codigo_postal,
             cliente_telefone, cliente_email,
             regime_civil, cliente_tipo, cliente_crc,
             cliente_representante, cliente_representante_nif,
             valor, taxa, cpcv_taxa, escritura_taxa,
             cliente_nif, cliente_cc, cliente_cc_validade, cliente_naturalidade,
             cliente_nacionalidade, cliente_freguesia, cliente_concelho,
             data_venda, estado, origem, staff_id, comissao_estado, date_created, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'na', ?, ?)"
    );

    // 10 strings, 4 decimais, data(s), estado(s), origem(s), comercial(i), data/hora(s), comercial(i)
    // 10 strings + 4 decimais + 7 strings do CPCV + data/estado/origem + ids
    $stmt->bind_param(
        'ssssssssss' . 'ss' . 'dddd' . 'sssssss' . 'sssisi',
        $empreendimento, $unidade, $cli_nome, $cli_morada, $cli_cp, $cli_tel, $cli_email,
        $cli_civil, $cli_tipo, $cli_crc, $cli_repres, $cli_repres_nif,
        $valor, $taxa, $cpcv_taxa, $escritura_taxa,
        $cli_nif, $cli_cc, $cli_cc_val, $cli_natural, $cli_nacional, $cli_freg, $cli_conc,
        $hoje, $estado, $origem, $comercial_id, $agora, $comercial_id
    );

    if (!$stmt->execute()) {
        responder(['success' => false, 'error' => 'Não foi possível criar a reserva.'], 500);
    }

    $venda_id = (int) $bd->insert_id;

    $nota_hist = $e_pedido
        ? 'Pedido de reserva submetido pelo CLIENTE a partir da proposta. Aguarda confirmação.'
        : 'Reserva criada no simulador pelo comercial';

    $stmt = $bd->prepare(
        'INSERT INTO tblvendas_historico (venda_id, estado_de, estado_para, staff_id, nota, dateadded)
         VALUES (?, NULL, ?, ?, ?, ?)'
    );
    $stmt->bind_param('isiss', $venda_id, $estado, $comercial_id, $nota_hist, $agora);
    @$stmt->execute();

    // Documentos: Cartão de Cidadão (frente e verso) anexados na reserva
    $doc_msg   = '';
    $guardados = 0;

    foreach (['cc' => 'cc_frente', 'cc_verso' => 'cc_verso'] as $campo => $tipo_doc) {
        if (empty($_FILES[$campo]['name']) || ($_FILES[$campo]['error'] ?? 1) !== UPLOAD_ERR_OK) {
            continue;
        }

        $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true) || $_FILES[$campo]['size'] > 10485760) {
            continue;
        }

        $dir = __DIR__ . '/uploads/dps_vendas/' . $venda_id . '/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            @file_put_contents($dir . '.htaccess', "Deny from all\n");
        }

        $fn = $tipo_doc . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        if (@move_uploaded_file($_FILES[$campo]['tmp_name'], $dir . $fn)) {
            $orig  = $bd->real_escape_string($_FILES[$campo]['name']);
            $fnesc = $bd->real_escape_string($fn);
            $bd->query(
                "INSERT INTO tblvendas_docs (venda_id, tipo, filename, original_name, uploaded_by, dateadded)
                 VALUES ({$venda_id}, '{$tipo_doc}', '{$fnesc}', '{$orig}', {$comercial_id}, '{$agora}')"
            );
            $guardados++;
        }
    }

    if ($guardados > 0) {
        $doc_msg = $guardados === 2
            ? ' Cartão de Cidadão anexado (frente e verso).'
            : ' Cartão de Cidadão anexado.';
    }

    /*
     * AVISO AOS ADMINISTRADORES — só no Aura.
     *
     * Uma reserva do Aura tem de chegar à direção no momento, não no dia
     * seguinte quando alguém abrir o CRM: as unidades boas saem primeiro e a
     * decisão de segurar ou libertar uma fracção não espera.
     *
     * Envolvido em try/catch e sempre DEPOIS da resposta estar garantida: se
     * a Evolution estiver em baixo, a reserva não pode falhar por causa de um
     * aviso interno. Falha o aviso, não a reserva.
     */
    if (stripos($empreendimento, 'aura') !== false) {
        try {
            dps_avisar_admins_reserva($bd, $venda_id, $empreendimento, $unidade, $comercial_id);
        } catch (\Throwable $e) {
            // Silêncio de propósito: já está registado no log da função.
        }
    }

    responder([
        'success'  => true,
        'venda_id' => $venda_id,
        'pedido'   => $e_pedido,
        'message'  => $e_pedido
            ? 'Pedido de reserva enviado com sucesso.' . $doc_msg . ' O seu consultor entrará em contacto para confirmar.'
            : 'Reserva registada no CRM.' . $doc_msg . ' A equipa vai dar seguimento.',
    ]);
}

responder(['success' => false, 'error' => 'Acção desconhecida.'], 400);

/**
 * Avisa por WhatsApp os administradores de que entrou uma reserva.
 *
 * Diz o essencial e nada mais: empreendimento, unidade e quem reservou.
 *
 * Sai pela instância do primeiro administrador que estiver ligada — é uma
 * mensagem interna, não interessa de quem parte, interessa que chegue. Quem
 * não tiver telefone no perfil não recebe, e isso fica no registo em vez de
 * desaparecer.
 */
function dps_avisar_admins_reserva($bd, $venda_id, $empreendimento, $unidade, $comercial_id)
{
    $reg = function ($m) { @file_put_contents(__DIR__ . '/dps-aviso-reserva.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n", FILE_APPEND); };

    $ler = function ($nome) use ($bd) {
        $r = $bd->query("SELECT value FROM tbloptions WHERE name = '" . $bd->real_escape_string($nome) . "'");

        return ($r && $r->num_rows) ? (string) $r->fetch_assoc()['value'] : '';
    };

    $url = rtrim(trim($ler('dps_whatsapp_evolution_url')), '/');
    $key = trim($ler('dps_whatsapp_evolution_api_key'));

    if ($url === '' || $key === '') {
        $reg('sem configuracao da Evolution — aviso nao enviado');

        return;
    }

    $comercial = 'sem comercial';
    if ((int) $comercial_id > 0) {
        $r = $bd->query('SELECT CONCAT(firstname, " ", lastname) n FROM tblstaff WHERE staffid = ' . (int) $comercial_id);
        if ($r && $r->num_rows) {
            $comercial = trim((string) $r->fetch_assoc()['n']);
        }
    }

    /*
     * Só a unidade e quem reservou. O nome do cliente fica de fora de
     * propósito: um aviso interno não precisa dele, e mandá-lo por WhatsApp
     * espalha dados de clientes por telemóveis pessoais sem necessidade
     * nenhuma. Quem quiser saber mais abre a venda no CRM.
     */
    $texto = "🔔 *RESERVA — " . $empreendimento . "*\n\n"
        . "Unidade: *" . ($unidade !== '' ? $unidade : 'por indicar') . "*\n"
        . "Comercial: " . $comercial . "\n"
        . "Venda #" . (int) $venda_id . " no CRM";

    // A instância que envia: a primeira aberta de entre os administradores.
    $envia = null;
    $admins = [];
    $r = $bd->query('SELECT staffid, phonenumber, CONCAT(firstname, " ", lastname) n
                     FROM tblstaff WHERE admin = 1 AND active = 1');
    while ($r && ($a = $r->fetch_assoc())) {
        $admins[] = $a;

        if ($envia === null) {
            $ch = curl_init($url . '/instance/connectionState/staff-' . (int) $a['staffid']);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => ['apikey: ' . $key]]);
            $j = json_decode((string) curl_exec($ch), true);
            curl_close($ch);

            if (($j['instance']['state'] ?? ($j['state'] ?? '')) === 'open') {
                $envia = 'staff-' . (int) $a['staffid'];
            }
        }
    }

    if ($envia === null) {
        $reg('nenhuma instancia de WhatsApp ligada — aviso nao enviado');

        return;
    }

    foreach ($admins as $a) {
        $numero = preg_replace('/[^0-9]/', '', (string) $a['phonenumber']);

        if ($numero === '') {
            $reg('admin ' . trim($a['n']) . ' sem telefone no perfil — nao avisado');
            continue;
        }
        if (strlen($numero) === 9) {
            $numero = '351' . $numero;      // sem indicativo, assume-se Portugal
        }

        $ch = curl_init($url . '/message/sendText/' . $envia);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 12,
            // Evolution v2: 'text' no primeiro nível.
            CURLOPT_POSTFIELDS     => json_encode(['number' => $numero, 'text' => $texto]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . $key],
        ]);
        $resp = curl_exec($ch);
        $cod  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $reg(($cod >= 200 && $cod < 300 ? 'avisado' : 'FALHOU (' . $cod . ')')
            . ' ' . trim($a['n']) . ' ' . $numero . ' venda #' . (int) $venda_id
            . ($cod >= 300 ? ' :: ' . substr((string) $resp, 0, 160) : ''));
    }
}
