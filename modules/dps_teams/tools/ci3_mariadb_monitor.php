<?php
/**
 * ============================================================================
 * CI3 × MariaDB 11.8 — Monitor de Incompatibilidades SQL
 * ============================================================================
 *
 * Detecta padrões de código PHP/SQL que causam erros silenciosos ou excepções
 * quando o CodeIgniter 3 é executado sobre MariaDB 10.6+ / 11.x.
 *
 * Categorias verificadas:
 *   A) Escape de identificadores — protect_identifiers() com dbprefix
 *   B) Collation — mistura de utf8 / utf8mb4 em JOINs
 *   C) SQL Mode — ONLY_FULL_GROUP_BY, STRICT_TRANS_TABLES
 *   D) Query Builder — padrões que geram SQL inválido
 *   E) Funções depreciadas — DATE() em WHERE, GROUP_CONCAT sem ORDER
 *   F) Índices e performance — colunas sem índice usadas em filtros
 *   G) Configuração do driver mysqli do CI3
 *
 * Uso (linha de comando):
 *   php ci3_mariadb_monitor.php [--dir=/caminho/para/modules] [--html] [--quiet]
 *
 * Uso (web — protegido por token):
 *   https://crm.grupo-dps.com/dps_monitor.php?token=SEU_TOKEN
 *
 * Saída:
 *   - Texto simples (CLI) ou HTML (--html / web)
 *   - Código de saída 0 = sem problemas críticos, 1 = problemas encontrados
 *
 * ============================================================================
 */

declare(strict_types=1);

// ── Configuração ─────────────────────────────────────────────────────────────

define('MONITOR_VERSION', '1.1.0');
define('MONITOR_TOKEN',   'dps_monitor_2026');   // Alterar em produção

// Directório raiz do Perfex — definido pelo wrapper web (dps_monitor.php)
// ou calculado automaticamente quando executado directamente
if (!defined('PERFEX_ROOT')) {
    define('PERFEX_ROOT', dirname(__DIR__, 3));
}

// Ler credenciais do app-config.php do Perfex (evita duplicar segredos)
$_app_config = PERFEX_ROOT . '/application/config/app-config.php';
if (file_exists($_app_config) && !defined('APP_DB_HOSTNAME')) {
    // Definir BASEPATH para evitar o exit() no topo do app-config.php
    if (!defined('BASEPATH')) define('BASEPATH', true);
    include $_app_config;
}

// Fallback se o app-config não existir
if (!defined('APP_DB_HOSTNAME')) define('APP_DB_HOSTNAME', 'localhost');
if (!defined('APP_DB_USERNAME')) define('APP_DB_USERNAME', '');
if (!defined('APP_DB_PASSWORD')) define('APP_DB_PASSWORD', '');
if (!defined('APP_DB_NAME'))     define('APP_DB_NAME',     '');

define('DB_HOST',   APP_DB_HOSTNAME);
define('DB_USER',   APP_DB_USERNAME);
define('DB_PASS',   APP_DB_PASSWORD);
define('DB_NAME',   APP_DB_NAME);
define('DB_PREFIX', 'tbl');

// ── Detecção de contexto (CLI vs Web) ────────────────────────────────────────

$is_cli  = (PHP_SAPI === 'cli');
$is_html = $is_cli ? in_array('--html', $argv ?? [], true) : true;
$quiet   = $is_cli && in_array('--quiet', $argv ?? [], true);

// Protecção web por token
if (!$is_cli) {
    $token = $_GET['token'] ?? '';
    if ($token !== MONITOR_TOKEN) {
        http_response_code(403);
        exit('Acesso negado. Forneça o token correcto: ?token=SEU_TOKEN');
    }
}

// Directório de módulos a analisar
// Por defeito: apenas os módulos DPS (mais rápido); usar --dir=modules para tudo
$scan_dir = PERFEX_ROOT . '/modules/dps_teams';
if ($is_cli) {
    foreach ($argv ?? [] as $arg) {
        if (str_starts_with($arg, '--dir=')) {
            $scan_dir = substr($arg, 6);
        }
    }
} elseif (isset($_GET['dir'])) {
    // Web: permitir escolher o directório via parâmetro (apenas subdirectórios de modules/)
    $requested = basename($_GET['dir']);
    $candidate = PERFEX_ROOT . '/modules/' . $requested;
    if (is_dir($candidate)) {
        $scan_dir = $candidate;
    } elseif ($_GET['dir'] === 'all') {
        $scan_dir = PERFEX_ROOT . '/modules';
    }
}

// ── Classes de resultado ─────────────────────────────────────────────────────

class MonitorResult
{
    public string $category;
    public string $severity;   // CRITICAL | WARNING | INFO
    public string $title;
    public string $description;
    public string $file;
    public int    $line;
    public string $snippet;
    public string $fix;

    public function __construct(
        string $category,
        string $severity,
        string $title,
        string $description,
        string $file = '',
        int    $line = 0,
        string $snippet = '',
        string $fix = ''
    ) {
        $this->category    = $category;
        $this->severity    = $severity;
        $this->title       = $title;
        $this->description = $description;
        $this->file        = $file;
        $this->line        = $line;
        $this->snippet     = $snippet;
        $this->fix         = $fix;
    }
}

// ── Monitor principal ────────────────────────────────────────────────────────

class CI3MariaDBMonitor
{
    /** @var MonitorResult[] */
    private array $results = [];
    private ?mysqli $db    = null;
    private string  $scan_dir;

    // Padrões de código que causam problemas no MariaDB 11.x com CI3
    private const CODE_PATTERNS = [

        // ── Categoria A: Escape de identificadores ────────────────────────
        'A1' => [
            'severity'    => 'CRITICAL',
            'category'    => 'Escape de Identificadores',
            'title'       => 'db->select() com db_prefix() e wildcard (*)',
            'regex'       => '/\$this->db->select\s*\(\s*db_prefix\s*\(\s*\)\s*\.\s*[\'"][^\'"\)]+\.\*/',
            'description' => 'O CI3 interpreta "tblleads.*" como "base_de_dados.tabela" quando o dbprefix contém o nome da tabela, gerando "Unknown table" no MariaDB 11.x.',
            'fix'         => 'Listar explicitamente as colunas ou usar SQL raw: $this->db->query("SELECT l.col1, l.col2 FROM tblleads l ...")',
        ],
        'A2' => [
            'severity'    => 'CRITICAL',
            'category'    => 'Escape de Identificadores',
            'title'       => 'db->where() com prefixo de tabela completo',
            'regex'       => '/\$this->db->where\s*\(\s*db_prefix\s*\(\s*\)\s*\.\s*[\'"][a-z_]+\.[a-z_]+[\'"]/',
            'description' => 'Usar db_prefix()."tabela.coluna" no where() faz o CI3 adicionar o prefixo novamente, resultando em "tbl.tbltabela.coluna" inválido.',
            'fix'         => 'Usar apenas o nome da coluna sem prefixo de tabela: $this->db->where("coluna", $valor) ou SQL raw.',
        ],
        'A3' => [
            'severity'    => 'WARNING',
            'category'    => 'Escape de Identificadores',
            'title'       => 'db->order_by() com nome de tabela prefixado',
            'regex'       => '/\$this->db->order_by\s*\(\s*db_prefix\s*\(\s*\)\s*\.\s*[\'"][a-z_]+\.[a-z_]+[\'"]/',
            'description' => 'O mesmo problema de escape aplica-se ao order_by() com prefixo de tabela.',
            'fix'         => 'Usar apenas o nome da coluna: $this->db->order_by("coluna", "DESC")',
        ],
        'A4' => [
            'severity'    => 'WARNING',
            'category'    => 'Escape de Identificadores',
            'title'       => 'db->group_by() com prefixo de tabela',
            'regex'       => '/\$this->db->group_by\s*\(\s*db_prefix\s*\(\s*\)\s*\.\s*[\'"][a-z_]+\.[a-z_]+[\'"]/',
            'description' => 'group_by() com db_prefix()."tabela.coluna" pode gerar SQL ambíguo.',
            'fix'         => 'Usar SQL raw ou apenas o nome da coluna sem prefixo.',
        ],

        // ── Categoria B: Collation ────────────────────────────────────────
        'B1' => [
            'severity'    => 'CRITICAL',
            'category'    => 'Collation',
            'title'       => 'JOIN entre tabelas com collation diferente',
            'regex'       => '/\$this->db->join\s*\(.*utf8[^4]/',
            'description' => 'JOIN entre colunas com collation utf8_general_ci e utf8mb4_unicode_ci causa "Illegal mix of collations" no MariaDB 11.x.',
            'fix'         => 'Definir APP_DB_CHARSET=utf8mb4 e APP_DB_COLLATION=utf8mb4_unicode_ci no app-config.php e garantir que todas as tabelas usam a mesma collation.',
        ],

        // ── Categoria D: Query Builder — padrões problemáticos ────────────
        'D1' => [
            'severity'    => 'CRITICAL',
            'category'    => 'Query Builder',
            'title'       => 'db->select() com FALSE e variável (possível tblX.*)',
            'regex'       => '/\$this->db->select\s*\(\s*\$[a-zA-Z_]+\s*,\s*FALSE\s*\)/',
            'description' => 'Usar select($var, FALSE) desactiva o escape mas não resolve o problema de prefixo de tabela no MariaDB 11.x quando há JOINs e a variável contém "tblX.*".',
            'fix'         => 'Migrar para SQL raw com $this->db->query() e aliases de tabela (ex: "l" para leads).',
        ],
        'D2' => [
            'severity'    => 'WARNING',
            'category'    => 'Query Builder',
            'title'       => 'DISTINCT no select() sem SQL raw',
            'regex'       => '/\$this->db->select\s*\(\s*[\'"]DISTINCT\s/',
            'description' => 'O CI3 pode gerar "SELECT DISTINCT DISTINCT coluna" em certas versões do MariaDB.',
            'fix'         => 'Usar $this->db->distinct() separadamente ou SQL raw.',
        ],
        'D3' => [
            'severity'    => 'WARNING',
            'category'    => 'Query Builder',
            'title'       => 'Subquery no where() sem null e FALSE',
            'regex'       => '/\$this->db->where\s*\([^,]+IN\s*\(SELECT/',
            'description' => 'Subqueries no where() do CI3 podem ter o escape aplicado incorrectamente.',
            'fix'         => 'Usar: $this->db->where("col IN (SELECT ...)", null, false)',
        ],

        // ── Categoria E: Funções depreciadas / problemáticas ──────────────
        'E1' => [
            'severity'    => 'WARNING',
            'category'    => 'Performance / SQL Mode',
            'title'       => 'DATE() em cláusula WHERE (SQL raw)',
            'regex'       => '/WHERE.*DATE\s*\([a-z_]+\)\s*[=<>]/',
            'description' => 'Usar DATE(coluna) = "valor" impede o uso de índices e degrada performance em tabelas grandes. No MariaDB 11.x com STRICT mode pode também causar erros de tipo.',
            'fix'         => 'Usar intervalos: coluna >= "2026-01-01 00:00:00" AND coluna < "2026-02-01 00:00:00"',
        ],
        'E2' => [
            'severity'    => 'WARNING',
            'category'    => 'Performance / SQL Mode',
            'title'       => 'DATE() em where() do Query Builder',
            'regex'       => '/\$this->db->where\s*\(\s*[\'"]DATE\s*\(/',
            'description' => 'Mesmo problema que E1 mas via Query Builder.',
            'fix'         => 'Usar $this->db->where("coluna >=", $inicio) e $this->db->where("coluna <", $fim)',
        ],
        'E3' => [
            'severity'    => 'INFO',
            'category'    => 'SQL Mode',
            'title'       => 'GROUP_CONCAT sem ORDER BY',
            'regex'       => '/GROUP_CONCAT\s*\([^)]+\)(?!\s*ORDER)/',
            'description' => 'No MariaDB 11.x com ONLY_FULL_GROUP_BY activo, GROUP_CONCAT sem ORDER BY pode retornar resultados não determinísticos.',
            'fix'         => 'Adicionar ORDER BY dentro do GROUP_CONCAT: GROUP_CONCAT(col ORDER BY col ASC)',
        ],

        // ── Categoria F: Padrões de N+1 queries ──────────────────────────
        'F1' => [
            'severity'    => 'WARNING',
            'category'    => 'Performance',
            'title'       => 'Query dentro de foreach (N+1)',
            'regex'       => '/foreach\s*\([^)]+\)\s*\{[^}]*\$this->db->(query|get|select)/s',
            'description' => 'Executar queries dentro de loops foreach causa N+1 queries, degradando severamente a performance com muitos registos.',
            'fix'         => 'Usar batch loading: uma query com IN(...) fora do loop e indexar os resultados por ID.',
        ],
    ];

    public function __construct(string $scan_dir)
    {
        $this->scan_dir = $scan_dir;
    }

    // ── Conexão à BD ──────────────────────────────────────────────────────────

    private function connect(): bool
    {
        $this->db = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->db->connect_error) {
            $this->results[] = new MonitorResult(
                'Configuração',
                'CRITICAL',
                'Falha na conexão à base de dados',
                'Não foi possível conectar: ' . $this->db->connect_error,
                '',
                0,
                '',
                'Verificar credenciais em application/config/app-config.php'
            );
            return false;
        }
        $this->db->set_charset('utf8mb4');
        return true;
    }

    // ── Verificações da base de dados ─────────────────────────────────────────

    private function checkDatabaseConfig(): void
    {
        if (!$this->connect()) return;

        // Versão do MariaDB
        $ver = $this->db->query("SELECT VERSION() AS v")->fetch_assoc()['v'] ?? 'desconhecida';
        $is_mariadb = str_contains(strtolower($ver), 'mariadb');
        $this->results[] = new MonitorResult(
            'Configuração',
            'INFO',
            'Versão do servidor de base de dados',
            ($is_mariadb ? 'MariaDB' : 'MySQL') . " detectado: {$ver}",
            '',
            0,
            "SELECT VERSION() → {$ver}",
            ''
        );

        // SQL Mode
        $sql_mode = $this->db->query("SELECT @@sql_mode AS m")->fetch_assoc()['m'] ?? '';
        $dangerous_modes = ['ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES', 'NO_ZERO_DATE', 'ERROR_FOR_DIVISION_BY_ZERO'];
        $active_dangerous = array_filter($dangerous_modes, fn($m) => str_contains($sql_mode, $m));
        if (!empty($active_dangerous)) {
            $this->results[] = new MonitorResult(
                'SQL Mode',
                'WARNING',
                'SQL modes restritivos activos: ' . implode(', ', $active_dangerous),
                'Estes SQL modes podem causar erros com queries antigas do CI3 que não usam GROUP BY completo ou inserem zeros em datas.',
                'application/config/database.php',
                0,
                "@@sql_mode = {$sql_mode}",
                "Adicionar ao database.php: 'stricton' => FALSE,  ou definir sql_mode='' no my.cnf"
            );
        } else {
            $this->results[] = new MonitorResult(
                'SQL Mode',
                'INFO',
                'SQL modes sem restrições problemáticas',
                "sql_mode activo: " . ($sql_mode ?: '(vazio — sem restrições)'),
                '',
                0,
                "@@sql_mode = {$sql_mode}",
                ''
            );
        }

        // Collation das tabelas principais
        $tables_to_check = ['leads', 'staff', 'clients', 'dps_teams', 'dps_team_members'];
        $collations_found = [];
        foreach ($tables_to_check as $t) {
            $tbl = DB_PREFIX . $t;
            $r = $this->db->query(
                "SELECT TABLE_COLLATION FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '{$tbl}'"
            );
            if ($r && $row = $r->fetch_assoc()) {
                $collations_found[$tbl] = $row['TABLE_COLLATION'];
            }
        }

        if (!empty($collations_found)) {
            $unique_collations = array_unique(array_values($collations_found));
            if (count($unique_collations) > 1) {
                $detail = implode(', ', array_map(
                    fn($t, $c) => "{$t}: {$c}",
                    array_keys($collations_found),
                    $collations_found
                ));
                $this->results[] = new MonitorResult(
                    'Collation',
                    'CRITICAL',
                    'Mistura de collations entre tabelas — causa "Illegal mix of collations"',
                    "Tabelas com collations diferentes causam erros em JOINs. Encontrado:\n{$detail}",
                    'application/config/app-config.php',
                    0,
                    $detail,
                    "Executar para cada tabela afectada:\n" .
                    "ALTER TABLE tblleads CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n" .
                    "ALTER TABLE tblstaff CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n" .
                    "(repetir para todas as tabelas listadas acima)"
                );
            } else {
                $col = reset($unique_collations) ?: 'N/A';
                $this->results[] = new MonitorResult(
                    'Collation',
                    'INFO',
                    'Collation uniforme nas tabelas principais: ' . $col,
                    'Todas as tabelas verificadas usam a mesma collation — sem risco de "Illegal mix".',
                    '',
                    0,
                    implode(', ', array_map(fn($t, $c) => "{$t}={$c}", array_keys($collations_found), $collations_found)),
                    ''
                );
            }
        }

        // Verificar índices nas colunas mais usadas em filtros
        $index_checks = [
            ['table' => DB_PREFIX . 'leads', 'column' => 'assigned',  'reason' => 'Filtro por comercial/equipa'],
            ['table' => DB_PREFIX . 'leads', 'column' => 'dateadded', 'reason' => 'Filtro por data'],
            ['table' => DB_PREFIX . 'leads', 'column' => 'status',    'reason' => 'Filtro por status'],
            ['table' => DB_PREFIX . 'leads', 'column' => 'source',    'reason' => 'Filtro por fonte'],
            ['table' => DB_PREFIX . 'leads', 'column' => 'country',   'reason' => 'Filtro por país'],
        ];
        foreach ($index_checks as $chk) {
            $r = $this->db->query(
                "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = '" . DB_NAME . "'
                   AND TABLE_NAME = '{$chk['table']}'
                   AND COLUMN_NAME = '{$chk['column']}'"
            );
            $cnt = (int)($r->fetch_assoc()['cnt'] ?? 0);
            if ($cnt === 0) {
                $this->results[] = new MonitorResult(
                    'Performance',
                    'WARNING',
                    "Índice em falta: {$chk['table']}.{$chk['column']}",
                    "A coluna '{$chk['column']}' é usada em filtros ({$chk['reason']}) mas não tem índice. Pode causar full table scans.",
                    '',
                    0,
                    "SHOW INDEX FROM {$chk['table']} WHERE Column_name = '{$chk['column']}'",
                    "ALTER TABLE {$chk['table']} ADD INDEX idx_{$chk['column']} ({$chk['column']});"
                );
            } else {
                $this->results[] = new MonitorResult(
                    'Performance',
                    'INFO',
                    "Índice presente: {$chk['table']}.{$chk['column']}",
                    "A coluna '{$chk['column']}' ({$chk['reason']}) tem índice — queries de filtro são eficientes.",
                    '',
                    0,
                    '',
                    ''
                );
            }
        }

        // Teste de compatibilidade de queries
        $this->testQueryCompatibility();
    }

    // ── Teste de compatibilidade de queries ───────────────────────────────────

    private function testQueryCompatibility(): void
    {
        $p = DB_PREFIX;

        // Teste 1: JOIN com alias (deve funcionar)
        $r = @$this->db->query(
            "SELECT l.id FROM {$p}leads l LEFT JOIN {$p}staff s ON s.staffid = l.assigned LIMIT 1"
        );
        if ($r !== false) {
            $this->results[] = new MonitorResult(
                'Compatibilidade',
                'INFO',
                'JOIN com alias de tabela: OK',
                'Queries com aliases (l, s) funcionam correctamente neste servidor. Este é o padrão recomendado.',
                '',
                0,
                "SELECT l.id FROM {$p}leads l LEFT JOIN {$p}staff s ON s.staffid = l.assigned LIMIT 1",
                ''
            );
        } else {
            $this->results[] = new MonitorResult(
                'Compatibilidade',
                'CRITICAL',
                'JOIN com alias de tabela: FALHOU',
                'Erro: ' . $this->db->error . '. Mesmo queries com SQL raw falham — verificar permissões.',
                '',
                0,
                "SELECT l.id FROM {$p}leads l LEFT JOIN {$p}staff s ON s.staffid = l.assigned LIMIT 1",
                'Verificar permissões do utilizador da BD e estrutura das tabelas.'
            );
        }

        // Teste 2: Simular o que o CI3 gera com db_prefix().'leads.*'
        $bad_sql = "SELECT {$p}leads.*, CONCAT({$p}staff.firstname, ' ', {$p}staff.lastname) as staff_name "
                 . "FROM {$p}leads LEFT JOIN {$p}staff ON {$p}staff.staffid = {$p}leads.assigned LIMIT 1";
        $r2 = @$this->db->query($bad_sql);
        if ($r2 === false) {
            $this->results[] = new MonitorResult(
                'Compatibilidade',
                'CRITICAL',
                'Query com tblX.* em SELECT com JOIN: FALHOU (comportamento esperado neste servidor)',
                'Este servidor rejeita "' . DB_PREFIX . 'leads.*" em SELECT quando há JOINs. '
                . 'Confirma que todo o código que usa este padrão via Query Builder irá falhar.',
                '',
                0,
                $bad_sql,
                'Usar SQL raw com aliases: SELECT l.id, l.name, ... FROM tblleads l LEFT JOIN tblstaff s ON ...'
            );
        } else {
            $this->results[] = new MonitorResult(
                'Compatibilidade',
                'INFO',
                'Query com tblX.* em SELECT com JOIN: OK neste servidor',
                'Este servidor aceita "' . DB_PREFIX . 'leads.*" em SELECT com JOINs. '
                . 'Atenção: pode falhar após actualização do MariaDB.',
                '',
                0,
                $bad_sql,
                'Migrar preventivamente para SQL raw com aliases para garantir compatibilidade futura.'
            );
        }

        // Teste 3: ONLY_FULL_GROUP_BY
        $group_sql = "SELECT assigned, name FROM {$p}leads GROUP BY assigned LIMIT 1";
        $r3 = @$this->db->query($group_sql);
        if ($r3 === false && str_contains($this->db->error ?? '', 'GROUP BY')) {
            $this->results[] = new MonitorResult(
                'SQL Mode',
                'CRITICAL',
                'ONLY_FULL_GROUP_BY activo — queries antigas do CI3 falharão',
                'O servidor rejeita SELECT com colunas não agregadas fora do GROUP BY. '
                . 'Muitas queries geradas pelo CI3 são incompatíveis com este modo.',
                '',
                0,
                $group_sql,
                "Desactivar ONLY_FULL_GROUP_BY:\n"
                . "SET GLOBAL sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));"
            );
        }
    }

    // ── Análise estática de ficheiros PHP ─────────────────────────────────────

    private function scanPhpFiles(): void
    {
        if (!is_dir($this->scan_dir)) {
            $this->results[] = new MonitorResult(
                'Configuração',
                'WARNING',
                'Directório a analisar não encontrado',
                "O directório '{$this->scan_dir}' não existe ou não é acessível.",
                '',
                0,
                '',
                'Verificar o caminho em PERFEX_ROOT ou usar --dir=/caminho/correcto (CLI) ou ?dir=nome_modulo (web)'
            );
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->scan_dir, FilesystemIterator::SKIP_DOTS)
        );

        $php_files = [];
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $php_files[] = $file->getPathname();
            }
        }

        $total = count($php_files);
        $rel_dir = str_replace(PERFEX_ROOT . '/', '', $this->scan_dir);
        $this->results[] = new MonitorResult(
            'Análise Estática',
            'INFO',
            "Ficheiros PHP analisados: {$total}",
            "Directório analisado: {$rel_dir}  |  Para analisar todos os módulos: ?dir=all (web) ou --dir=modules (CLI)",
            '',
            0,
            '',
            ''
        );

        foreach ($php_files as $filepath) {
            $this->analyzeFile($filepath);
        }
    }

    private function analyzeFile(string $filepath): void
    {
        $content = file_get_contents($filepath);
        if ($content === false) return;

        $lines   = explode("\n", $content);
        $rel_path = str_replace(PERFEX_ROOT . '/', '', $filepath);

        foreach (self::CODE_PATTERNS as $code => $pattern) {
            foreach ($lines as $line_num => $line) {
                if (preg_match($pattern['regex'], $line)) {
                    $this->results[] = new MonitorResult(
                        $pattern['category'],
                        $pattern['severity'],
                        "[{$code}] " . $pattern['title'],
                        $pattern['description'],
                        $rel_path,
                        $line_num + 1,
                        trim($line),
                        $pattern['fix']
                    );
                }
            }
        }
    }

    // ── Verificação da configuração do CI3 ────────────────────────────────────

    private function checkCI3Config(): void
    {
        $db_config = PERFEX_ROOT . '/application/config/database.php';
        if (!file_exists($db_config)) {
            $this->results[] = new MonitorResult(
                'Configuração',
                'WARNING',
                'Ficheiro database.php não encontrado',
                "Esperado em: {$db_config}",
                '',
                0,
                '',
                'Verificar se PERFEX_ROOT está correcto.'
            );
            return;
        }

        $cfg = file_get_contents($db_config);

        // Verificar db_debug em produção
        if (str_contains($cfg, "'db_debug' => TRUE") || str_contains($cfg, '"db_debug" => TRUE')) {
            $this->results[] = new MonitorResult(
                'Configuração',
                'WARNING',
                'db_debug activo — expõe erros SQL em produção',
                'Com db_debug=TRUE, todos os erros SQL são exibidos ao utilizador, expondo a estrutura da base de dados.',
                'application/config/database.php',
                0,
                "'db_debug' => TRUE",
                "Usar: 'db_debug' => (ENVIRONMENT !== 'production')"
            );
        } else {
            $this->results[] = new MonitorResult(
                'Configuração',
                'INFO',
                'db_debug desactivado em produção',
                'Os erros SQL não são expostos ao utilizador em ambiente de produção.',
                'application/config/database.php',
                0,
                '',
                ''
            );
        }

        // Verificar stricton
        if (!str_contains($cfg, 'stricton')) {
            $this->results[] = new MonitorResult(
                'Configuração',
                'INFO',
                'Opção stricton não definida no database.php',
                'A opção stricton controla o SQL strict mode via CI3. Sem ela, o MariaDB usa o seu próprio sql_mode.',
                'application/config/database.php',
                0,
                '// stricton não encontrado',
                "Adicionar: 'stricton' => FALSE, para desactivar STRICT_TRANS_TABLES via CI3."
            );
        }

        // Verificar charset
        if (str_contains($cfg, "APP_DB_CHARSET") || str_contains($cfg, "'utf8mb4'")) {
            $this->results[] = new MonitorResult(
                'Collation',
                'INFO',
                'Charset configurado como utf8mb4',
                'O driver CI3 está configurado para usar utf8mb4, compatível com MariaDB 11.x.',
                'application/config/database.php',
                0,
                '',
                ''
            );
        } elseif (str_contains($cfg, "'utf8'")) {
            $this->results[] = new MonitorResult(
                'Collation',
                'WARNING',
                'CI3 configurado com charset utf8 em vez de utf8mb4',
                'O ficheiro database.php usa utf8 como charset padrão. Isto causa mistura de collations em JOINs com tabelas utf8mb4.',
                'application/config/database.php',
                0,
                "'char_set' => 'utf8'",
                "Definir APP_DB_CHARSET=utf8mb4 e APP_DB_COLLATION=utf8mb4_unicode_ci no app-config.php."
            );
        }
    }

    // ── Execução principal ────────────────────────────────────────────────────

    public function run(): array
    {
        $this->checkCI3Config();
        $this->checkDatabaseConfig();
        $this->scanPhpFiles();

        // Ordenar por severidade
        usort($this->results, function (MonitorResult $a, MonitorResult $b) {
            $order = ['CRITICAL' => 0, 'WARNING' => 1, 'INFO' => 2];
            $cmp = ($order[$a->severity] ?? 3) <=> ($order[$b->severity] ?? 3);
            if ($cmp !== 0) return $cmp;
            return strcmp($a->category, $b->category);
        });

        return $this->results;
    }

    public function getSummary(): array
    {
        $summary = ['CRITICAL' => 0, 'WARNING' => 0, 'INFO' => 0];
        foreach ($this->results as $r) {
            $summary[$r->severity] = ($summary[$r->severity] ?? 0) + 1;
        }
        return $summary;
    }
}

// ── Renderização HTML ─────────────────────────────────────────────────────────

function render_html(array $results, array $summary, float $elapsed, string $scan_dir): void
{
    $ts      = date('Y-m-d H:i:s');
    $version = MONITOR_VERSION;
    $crit    = $summary['CRITICAL'];
    $warn    = $summary['WARNING'];
    $info    = $summary['INFO'];
    $rel_dir = str_replace(PERFEX_ROOT . '/', '', $scan_dir);

    echo '<!DOCTYPE html>' . "\n";
    echo '<html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>CI3 &times; MariaDB Monitor &mdash; Relat&oacute;rio</title>';
    echo '<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: "Segoe UI", Arial, sans-serif; background: #f0f2f5; color: #222; }
.header { background: #1a2340; color: #fff; padding: 24px 32px; }
.header h1 { font-size: 1.5rem; font-weight: 700; }
.header p  { font-size: 0.85rem; opacity: 0.7; margin-top: 4px; }
.summary { display: flex; gap: 16px; padding: 20px 32px; flex-wrap: wrap; }
.badge { padding: 12px 24px; border-radius: 8px; font-weight: 700; font-size: 1.1rem; min-width: 120px; text-align: center; }
.badge.CRITICAL { background: #fee2e2; color: #991b1b; border: 2px solid #fca5a5; }
.badge.WARNING  { background: #fef3c7; color: #92400e; border: 2px solid #fcd34d; }
.badge.INFO     { background: #dbeafe; color: #1e40af; border: 2px solid #93c5fd; }
.badge .num { font-size: 2rem; display: block; }
.container { padding: 0 32px 32px; }
.section-title { font-size: 1rem; font-weight: 700; color: #555; text-transform: uppercase;
                 letter-spacing: 1px; margin: 24px 0 12px; border-bottom: 2px solid #ddd; padding-bottom: 6px; }
.card { background: #fff; border-radius: 8px; margin-bottom: 8px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
.card-header { display: flex; align-items: center; gap: 12px; padding: 12px 18px; cursor: pointer; }
.sev { font-size: 0.7rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; white-space: nowrap; }
.sev.CRITICAL { background: #fee2e2; color: #991b1b; }
.sev.WARNING  { background: #fef3c7; color: #92400e; }
.sev.INFO     { background: #dbeafe; color: #1e40af; }
.card-title { font-weight: 600; font-size: 0.9rem; flex: 1; }
.cat-tag { font-size: 0.72rem; color: #888; background: #f3f4f6; padding: 2px 8px; border-radius: 4px; white-space: nowrap; }
.card-body { padding: 12px 18px; border-top: 1px solid #f0f0f0; font-size: 0.86rem; line-height: 1.6; }
.card-body .desc { color: #444; margin-bottom: 8px; white-space: pre-wrap; }
.card-body .loc  { font-size: 0.78rem; color: #888; margin-bottom: 6px; }
.card-body .loc span { font-family: monospace; background: #f3f4f6; padding: 1px 5px; border-radius: 3px; }
.card-body pre { background: #1e293b; color: #e2e8f0; padding: 10px 14px; border-radius: 6px;
                 font-size: 0.8rem; overflow-x: auto; margin: 6px 0; white-space: pre-wrap; word-break: break-all; }
.card-body .fix-label { font-weight: 600; color: #059669; margin-top: 8px; margin-bottom: 2px; }
.footer { text-align: center; padding: 20px; font-size: 0.8rem; color: #aaa; }
.dir-info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 16px;
            font-size: 0.82rem; color: #555; margin: 0 32px 8px; }
details > summary { list-style: none; }
details > summary::-webkit-details-marker { display: none; }
</style></head><body>';

    echo '<div class="header">';
    echo '<h1>CI3 &times; MariaDB 11.8 &mdash; Monitor de Incompatibilidades SQL</h1>';
    echo '<p>Gerado em ' . htmlspecialchars($ts) . ' &nbsp;|&nbsp; Tempo: ' . $elapsed . 's &nbsp;|&nbsp; Vers&atilde;o: ' . htmlspecialchars($version) . '</p>';
    echo '</div>';

    echo '<div class="summary">';
    echo '<div class="badge CRITICAL"><span class="num">' . $crit . '</span>CR&Iacute;TICOS</div>';
    echo '<div class="badge WARNING"><span class="num">' . $warn . '</span>AVISOS</div>';
    echo '<div class="badge INFO"><span class="num">' . $info . '</span>INFO</div>';
    echo '</div>';

    echo '<div class="dir-info">&#128269; Directório analisado: <strong>' . htmlspecialchars($rel_dir) . '</strong>';
    echo ' &nbsp;&mdash;&nbsp; Para analisar todos os módulos: <code>?token=' . MONITOR_TOKEN . '&amp;dir=all</code></div>';

    echo '<div class="container">';

    $current_cat = null;
    foreach ($results as $r) {
        if ($r->category !== $current_cat) {
            $current_cat = $r->category;
            echo '<div class="section-title">' . htmlspecialchars($current_cat) . "</div>\n";
        }
        $file_loc = $r->file
            ? "<div class='loc'>Ficheiro: <span>" . htmlspecialchars($r->file) . "</span>"
              . ($r->line > 0 ? " linha <span>{$r->line}</span>" : '') . "</div>"
            : '';
        $snippet = $r->snippet ? "<pre>" . htmlspecialchars($r->snippet) . "</pre>" : '';
        $fix     = $r->fix
            ? "<div class='fix-label'>&#10004; Correc&ccedil;&atilde;o sugerida:</div><pre>" . htmlspecialchars($r->fix) . "</pre>"
            : '';

        echo '<details class="card">';
        echo '<summary class="card-header">';
        echo '<span class="sev ' . $r->severity . '">' . htmlspecialchars($r->severity) . '</span>';
        echo '<span class="card-title">' . htmlspecialchars($r->title) . '</span>';
        echo '<span class="cat-tag">' . htmlspecialchars($r->category) . '</span>';
        echo '</summary>';
        echo '<div class="card-body">';
        echo '<div class="desc">' . htmlspecialchars($r->description) . '</div>';
        echo $file_loc . $snippet . $fix;
        echo '</div></details>' . "\n";
    }

    echo '</div>';
    echo '<div class="footer">CI3 &times; MariaDB Monitor v' . htmlspecialchars($version) . ' &mdash; Perfex CRM Grupo DPS</div>';
    echo '</body></html>' . "\n";
}

// ── Renderização CLI ──────────────────────────────────────────────────────────

function render_cli(array $results, array $summary, float $elapsed, bool $quiet, string $scan_dir): void
{
    $ts = date('Y-m-d H:i:s');
    $rel_dir = str_replace(PERFEX_ROOT . '/', '', $scan_dir);
    echo "\n=== CI3 × MariaDB Monitor v" . MONITOR_VERSION . " — {$ts} ===\n";
    echo "Directório: {$rel_dir}\n";
    echo "Tempo: {$elapsed}s | CRÍTICOS: {$summary['CRITICAL']} | AVISOS: {$summary['WARNING']} | INFO: {$summary['INFO']}\n";
    echo str_repeat('─', 80) . "\n";

    $icons = ['CRITICAL' => '[CRÍTICO]', 'WARNING' => '[AVISO]  ', 'INFO' => '[INFO]   '];

    foreach ($results as $r) {
        if ($quiet && $r->severity === 'INFO') continue;
        $icon = $icons[$r->severity] ?? '[?]';
        echo "\n{$icon} {$r->title}\n";
        echo "         Categoria: {$r->category}\n";
        echo "         " . wordwrap($r->description, 70, "\n         ") . "\n";
        if ($r->file) {
            echo "         Ficheiro: {$r->file}" . ($r->line > 0 ? " (linha {$r->line})" : '') . "\n";
        }
        if ($r->snippet) {
            echo "         Código:   " . substr(trim($r->snippet), 0, 100) . "\n";
        }
        if ($r->fix) {
            echo "         Correcção: " . wordwrap($r->fix, 70, "\n                   ") . "\n";
        }
    }

    echo "\n" . str_repeat('─', 80) . "\n";
    echo "Resumo: {$summary['CRITICAL']} crítico(s), {$summary['WARNING']} aviso(s), {$summary['INFO']} informativo(s)\n\n";
}

// ── Ponto de entrada ──────────────────────────────────────────────────────────

$start   = microtime(true);
$monitor = new CI3MariaDBMonitor($scan_dir);
$results = $monitor->run();
$summary = $monitor->getSummary();
$elapsed = round(microtime(true) - $start, 3);

if ($is_html) {
    header('Content-Type: text/html; charset=utf-8');
    render_html($results, $summary, $elapsed, $scan_dir);
} else {
    render_cli($results, $summary, $elapsed, $quiet, $scan_dir);
}

// Código de saída para uso em CI/CD
exit($summary['CRITICAL'] > 0 ? 1 : 0);
