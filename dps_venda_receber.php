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

/* ---------------------------------------------------------------------
 * Acções
 * ------------------------------------------------------------------ */

$accao = (string) ($_GET['a'] ?? $_POST['a'] ?? '');

if ($accao === 'comerciais') {
    $bd = ligar_bd();

    $res = $bd->query(
        "SELECT staffid, CONCAT(firstname, ' ', lastname) AS nome
         FROM tblstaff
         WHERE active = 1
         ORDER BY firstname ASC"
    );

    $comerciais = [];
    while ($linha = $res->fetch_assoc()) {
        $comerciais[] = [
            'id'   => (int) $linha['staffid'],
            'nome' => $linha['nome'],
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

    // No pedido feito pelo cliente o Cartão de Cidadão é obrigatório. Validar
    // ANTES de criar seja o que for, para não ficar um pedido órfão sem documento.
    if ($e_pedido) {
        if (empty($_FILES['cc']['name']) || ($_FILES['cc']['error'] ?? 1) !== UPLOAD_ERR_OK) {
            responder(['success' => false, 'error' => 'É obrigatório anexar o Cartão de Cidadão.'], 400);
        }

        $ext_cc = strtolower(pathinfo($_FILES['cc']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext_cc, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            responder(['success' => false, 'error' => 'O documento tem de ser PDF, JPG ou PNG.'], 400);
        }
        if (($_FILES['cc']['size'] ?? 0) > 10485760) {
            responder(['success' => false, 'error' => 'O documento excede 10 MB.'], 400);
        }

        foreach (['cliente' => 'nome', 'morada' => 'morada', 'telefone' => 'telefone', 'email' => 'email', 'estado_civil' => 'estado civil'] as $campo => $etiqueta) {
            if (trim((string) ($_POST[$campo] ?? '')) === '') {
                responder(['success' => false, 'error' => 'Falta preencher: ' . $etiqueta . '.'], 400);
            }
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

    $agora = date('Y-m-d H:i:s');
    $hoje  = date('Y-m-d');

    $stmt = $bd->prepare(
        "INSERT INTO tblsimulador_vendas
            (empreendimento, unidade, cliente, cliente_morada, cliente_telefone, cliente_email,
             regime_civil, cliente_tipo, cliente_crc, valor, taxa, cpcv_taxa, escritura_taxa,
             data_venda, estado, origem, staff_id, comissao_estado, date_created, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'na', ?, ?)"
    );

    // 9 strings, 4 decimais, data(s), estado(s), origem(s), comercial(i), data/hora(s), comercial(i)
    $stmt->bind_param(
        'sssssssssddddsssisi',
        $empreendimento, $unidade, $cli_nome, $cli_morada, $cli_tel, $cli_email,
        $cli_civil, $cli_tipo, $cli_crc, $valor, $taxa, $cpcv_taxa, $escritura_taxa,
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

    // Documento: Cartão de Cidadão anexado na reserva
    $doc_msg = '';
    if (!empty($_FILES['cc']['name']) && ($_FILES['cc']['error'] ?? 1) === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cc']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true) && $_FILES['cc']['size'] <= 10485760) {
            $dir = __DIR__ . '/uploads/dps_vendas/' . $venda_id . '/';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
                @file_put_contents($dir . '.htaccess', "Deny from all\n");
            }
            $fn = 'cc_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (@move_uploaded_file($_FILES['cc']['tmp_name'], $dir . $fn)) {
                $orig  = $bd->real_escape_string($_FILES['cc']['name']);
                $fnesc = $bd->real_escape_string($fn);
                $bd->query(
                    "INSERT INTO tblvendas_docs (venda_id, tipo, filename, original_name, uploaded_by, dateadded)
                     VALUES ({$venda_id}, 'cc_frente', '{$fnesc}', '{$orig}', {$comercial_id}, '{$agora}')"
                );
                $doc_msg = ' Cartão de Cidadão anexado.';
            }
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
