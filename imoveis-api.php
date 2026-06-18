<?php
/**
 * API Pública de Imóveis - DPS Imobiliário
 * Endpoint: https://crm.grupo-dps.com/imoveis-api.php
 * 
 * Parâmetros GET:
 *   ?action=imoveis          - Lista imóveis aprovados
 *   ?action=agentes          - Lista agentes activos
 *   ?action=imovel&id=X      - Detalhe de um imóvel
 *   ?action=agente&slug=X    - Detalhe de um agente e os seus imóveis
 *   ?action=equipa           - Lista toda a equipa (para página /equipa)
 *   ?action=cache_clear      - Limpar cache (uso interno)
 */
// CORS - permitir acesso do site dpsimobiliario.pt
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ─── CACHE ───────────────────────────────────────────────────────────────────
define('CACHE_DIR', __DIR__ . '/imoveis_cache/');
define('CACHE_TTL', 300); // 5 minutos

function cache_get($key) {
    $file = CACHE_DIR . md5($key) . '.json';
    if (!file_exists($file)) return null;
    if ((time() - filemtime($file)) > CACHE_TTL) {
        @unlink($file);
        return null;
    }
    $data = @file_get_contents($file);
    return $data ? json_decode($data, true) : null;
}

function cache_set($key, $data) {
    if (!is_dir(CACHE_DIR)) {
        @mkdir(CACHE_DIR, 0755, true);
    }
    $file = CACHE_DIR . md5($key) . '.json';
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

function cache_clear() {
    if (!is_dir(CACHE_DIR)) return;
    foreach (glob(CACHE_DIR . '*.json') as $f) {
        @unlink($f);
    }
}
// ─────────────────────────────────────────────────────────────────────────────

// Configuração da BD
define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');
define('TBL_PREFIX', 'tbl');
// URL base do CRM para imagens
define('CRM_URL', 'https://crm.grupo-dps.com');

// Corrigir URLs de imagens que possam ter o caminho duplicado na BD
function fix_image_url($url) {
    if (empty($url)) return null;
    $dup = 'modules/dps_imoveis/uploads/fotos/modules/dps_imoveis/uploads/fotos/';
    $fix = 'modules/dps_imoveis/uploads/fotos/';
    if (strpos($url, $dup) !== false) {
        $url = str_replace($dup, $fix, $url);
    }
    if (strpos($url, 'http') === 0) return $url;
    return CRM_URL . '/' . ltrim($url, '/');
}

function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro de ligação à base de dados']);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function get_foto_url($staffid, $landing_foto, $profile_image) {
    $base = CRM_URL . '/';
    if (!empty($landing_foto)) {
        return $base . 'uploads/staff_landing_photos/' . $staffid . '/' . $landing_foto;
    }
    if (!empty($profile_image)) {
        return $base . 'uploads/staff_profile_images/' . $staffid . '/small_' . $profile_image;
    }
    return null;
}

function make_slug($firstname, $lastname) {
    $name = trim($firstname) . '-' . trim($lastname);
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9]+/', '-', $name);
    $name = trim(preg_replace('/-+/', '-', $name), '-');
    return $name;
}

$TIPO_MAP = [
    'Apartment'  => 'Apartamento',
    'House'      => 'Moradia',
    'Commercial' => 'Loja',
    'Land'       => 'Terreno',
];

function get_imoveis($conn, $filters = []) {
    global $TIPO_MAP;

    // Chave de cache baseada nos filtros
    $cache_key = 'imoveis_' . json_encode($filters);
    $cached = cache_get($cache_key);
    if ($cached !== null) return $cached;

    $where = ["i.published_website = 1", "i.status = 'aprovado'"];
    
    if (!empty($filters['tipo'])) {
        $tipo_raw = $filters['tipo'];
        $tipo = isset($TIPO_MAP[$tipo_raw]) ? $TIPO_MAP[$tipo_raw] : $tipo_raw;
        $tipo = $conn->real_escape_string($tipo);
        $where[] = "i.tipo = '$tipo'";
    }
    if (!empty($filters['distrito'])) {
        $distrito = $conn->real_escape_string($filters['distrito']);
        $where[] = "i.distrito = '$distrito'";
    }
    if (!empty($filters['tipologia'])) {
        $tipologia = $conn->real_escape_string($filters['tipologia']);
        $where[] = "i.tipologia = '$tipologia'";
    }
    if (!empty($filters['agente_slug'])) {
        $slug = $conn->real_escape_string($filters['agente_slug']);
        $where[] = "LOWER(REPLACE(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-'), 'ã', 'a')) = '$slug'";
    }
    // Filtro por ID específico (optimização para action=imovel)
    if (!empty($filters['id'])) {
        $id = (int)$filters['id'];
        $where[] = "i.id = $id";
    }
    
    $where_sql = implode(' AND ', $where);
    
    $sql = "SELECT
        i.id,
        i.titulo,
        i.tipo AS tipo_imovel,
        i.tipologia,
        i.distrito,
        i.cidade,
        i.morada,
        i.preco,
        i.area_total,
        i.nr_quartos,
        i.nr_suites,
        i.nr_salas,
        i.nr_casas_banho,
        i.garagem,
        i.lugar_garagem,
        i.ano_construcao,
        i.equipamento,
        i.texto_livre,
        i.texto_livre AS descricao,
        i.foto_principal,
        i.fotos,
        i.date_approval AS data_publicacao,
        CONCAT(s.firstname, ' ', s.lastname) AS agente_nome,
        s.staffid AS agente_id,
        s.phonenumber AS agente_telefone,
        s.email AS agente_email,
        s.profile_image AS agente_foto,
        s.landing_foto AS agente_landing_foto,
        LOWER(REPLACE(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-'), 'ã', 'a')) AS agente_slug
    FROM " . TBL_PREFIX . "dps_imoveis i
    LEFT JOIN " . TBL_PREFIX . "staff s ON s.staffid = i.agente_id
    WHERE $where_sql
    ORDER BY i.date_approval DESC";
    
    $result = $conn->query($sql);
    if (!$result) return [];
    
    $imoveis = [];
    
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['foto_principal'])) {
            $row['foto_principal'] = fix_image_url($row['foto_principal']);
        } else {
            $row['foto_principal'] = null;
        }
        if (!empty($row['fotos'])) {
            $fotos_arr = json_decode($row['fotos'], true) ?: [];
            $fotos_fixed = array_map(function($f) { return fix_image_url($f); }, $fotos_arr);
            $row['fotos'] = $fotos_fixed;
            $row['fotos_urls'] = $fotos_fixed;
        } else {
            $row['fotos'] = [];
            $row['fotos_urls'] = [];
        }
        $row['agente_foto_url'] = get_foto_url(
            $row['agente_id'],
            $row['agente_landing_foto'] ?? null,
            $row['agente_foto']
        );
        $row['preco_formatado'] = number_format((float)$row['preco'], 0, ',', '.') . ' €';
        unset($row['agente_foto'], $row['agente_landing_foto']);
        $imoveis[] = $row;
    }

    // Guardar em cache
    cache_set($cache_key, $imoveis);

    return $imoveis;
}

function get_agentes($conn) {
    $cache_key = 'agentes';
    $cached = cache_get($cache_key);
    if ($cached !== null) return $cached;

    $sql = "SELECT DISTINCT
        s.staffid AS id,
        s.firstname AS nome,
        s.lastname AS apelido,
        s.email,
        s.phonenumber AS telefone,
        s.profile_image AS foto,
        s.landing_foto AS landing_foto,
        s.landing_slug,
        COUNT(i.id) AS total_imoveis
    FROM " . TBL_PREFIX . "staff s
    INNER JOIN " . TBL_PREFIX . "dps_imoveis i ON (i.agente_id = s.staffid AND i.published_website = 1 AND i.status = 'aprovado')
    WHERE s.active = 1
    GROUP BY s.staffid
    ORDER BY s.firstname ASC";
    $result = $conn->query($sql);
    if (!$result) return [];
    $agentes = [];
    while ($row = $result->fetch_assoc()) {
        $row['foto_url'] = get_foto_url($row['id'], $row['landing_foto'], $row['foto']);
        $row['slug'] = make_slug($row['nome'], $row['apelido']);
        unset($row['foto'], $row['landing_foto']);
        $agentes[] = $row;
    }

    cache_set($cache_key, $agentes);
    return $agentes;
}

function get_agente_by_slug($conn, $slug) {
    $cache_key = 'agente_' . $slug;
    $cached = cache_get($cache_key);
    if ($cached !== null) return $cached;

    $sql = "SELECT
        s.staffid AS id,
        s.firstname AS nome,
        s.lastname AS apelido,
        s.email,
        s.phonenumber AS telefone,
        s.profile_image AS foto,
        s.landing_foto,
        s.landing_slug,
        s.landing_whatsapp AS whatsapp
    FROM " . TBL_PREFIX . "staff s
    WHERE s.active = 1";
    $result = $conn->query($sql);
    if (!$result) return null;
    $agente = null;
    while ($row = $result->fetch_assoc()) {
        $landing_slug = !empty($row['landing_slug']) ? $row['landing_slug'] : null;
        $generated_slug = make_slug($row['nome'], $row['apelido']);
        if ($landing_slug === $slug || $generated_slug === $slug) {
            $agente = $row;
            break;
        }
    }
    if (!$agente) return null;
    
    $agente['foto_url'] = get_foto_url($agente['id'], $agente['landing_foto'], $agente['foto']);
    $agente['slug'] = $slug;
    unset($agente['foto'], $agente['landing_foto']);

    cache_set($cache_key, $agente);
    return $agente;
}

function get_equipa($conn) {
    $cache_key = 'equipa';
    $cached = cache_get($cache_key);
    if ($cached !== null) return $cached;

    $sql = "SELECT
        s.staffid AS id,
        s.firstname AS nome,
        s.lastname AS apelido,
        s.email,
        s.phonenumber AS telefone,
        s.profile_image AS foto,
        s.landing_foto,
        s.landing_slug,
        s.landing_whatsapp AS whatsapp
    FROM " . TBL_PREFIX . "staff s
    WHERE s.active = 1
    ORDER BY s.staffid ASC";
    
    $result = $conn->query($sql);
    if (!$result) {
        return ['lideranca' => [], 'mercados' => []];
    }
    
    $staff_map = [];
    while ($row = $result->fetch_assoc()) {
        $foto_url = get_foto_url($row['id'], $row['landing_foto'], $row['foto']);
        $row['foto_url'] = $foto_url;
        $row['has_foto'] = !empty($foto_url);
        $row['slug'] = !empty($row['landing_slug']) ? $row['landing_slug'] : make_slug($row['nome'], $row['apelido']);
        unset($row['foto'], $row['landing_foto'], $row['landing_slug']);
        $staff_map[(int)$row['id']] = $row;
    }
    
    $teams_sql = "SELECT t.id, t.name, t.area,
        m.staff_id, m.role
    FROM " . TBL_PREFIX . "dps_teams t
    LEFT JOIN " . TBL_PREFIX . "dps_team_members m ON m.team_id = t.id
    WHERE t.area = 'imo'
    ORDER BY t.id ASC, m.role ASC";
    
    $teams_result = $conn->query($teams_sql);
    $teams = [];
    if ($teams_result) {
        while ($tr = $teams_result->fetch_assoc()) {
            $tid = (int)$tr['id'];
            if (!isset($teams[$tid])) {
                $teams[$tid] = [
                    'name'     => $tr['name'],
                    'area'     => $tr['area'],
                    'managers' => [],
                    'members'  => [],
                ];
            }
            if (!empty($tr['staff_id'])) {
                $sid = (int)$tr['staff_id'];
                if ($tr['role'] === 'manager') {
                    $teams[$tid]['managers'][] = $sid;
                } else {
                    $teams[$tid]['members'][] = $sid;
                }
            }
        }
    }
    
    $ceo_id    = 1;
    $gestor_id = 46;
    $lideranca = [];
    foreach ([$ceo_id => ['role' => 'ceo', 'label' => 'CEO'],
              $gestor_id => ['role' => 'gestor', 'label' => 'Gestor de Equipa']] as $sid => $info) {
        if (isset($staff_map[$sid]) && $staff_map[$sid]['has_foto']) {
            $m = $staff_map[$sid];
            $m['role']       = $info['role'];
            $m['role_label'] = $info['label'];
            $lideranca[] = $m;
        }
    }
    
    $market_map = [
        'IMO BRASIL & BSX' => ['flag' => '🇧🇷', 'label' => 'Brasil'],
        'IMO BRASIL'       => ['flag' => '🇧🇷', 'label' => 'Brasil'],
        'IMO PORTUGAL'     => ['flag' => '🇵🇹', 'label' => 'Portugal'],
        'IMO DUBAI'        => ['flag' => '🇦🇪', 'label' => 'Dubai'],
    ];
    
    $order_map = ['IMO PORTUGAL' => 1, 'IMO BRASIL & BSX' => 2, 'IMO BRASIL' => 2, 'IMO DUBAI' => 3];
    $teams_arr = array_values($teams);
    usort($teams_arr, function($a, $b) use ($order_map) {
        $oa = $order_map[strtoupper(trim($a['name']))] ?? 99;
        $ob = $order_map[strtoupper(trim($b['name']))] ?? 99;
        return $oa - $ob;
    });
    
    $mercados = [];
    foreach ($teams_arr as $team) {
        $name_upper  = strtoupper(trim($team['name']));
        $market_info = $market_map[$name_upper] ?? ['flag' => '🌍', 'label' => $team['name']];
        $all_ids     = array_unique(array_merge($team['managers'], $team['members']));
        $membros     = [];
        foreach ($all_ids as $sid) {
            if ($sid === $ceo_id || $sid === $gestor_id) continue;
            if (isset($staff_map[$sid]) && $staff_map[$sid]['has_foto']) {
                $m = $staff_map[$sid];
                $m['role_label'] = in_array($sid, $team['managers']) ? 'Coordenador' : 'Consultor';
                $membros[] = $m;
            }
        }
        if (empty($membros)) continue;
        $mercados[] = [
            'name'    => $team['name'],
            'flag'    => $market_info['flag'],
            'label'   => $market_info['label'],
            'membros' => $membros,
        ];
    }
    
    $result_data = [
        'lideranca' => $lideranca,
        'mercados'  => $mercados,
    ];

    cache_set($cache_key, $result_data);
    return $result_data;
}

// Router principal
$action = isset($_GET['action']) ? $_GET['action'] : 'imoveis';
$conn = null;

switch ($action) {
    case 'imoveis':
        $filters = [
            'tipo'      => $_GET['tipo'] ?? '',
            'distrito'  => $_GET['distrito'] ?? '',
            'tipologia' => $_GET['tipologia'] ?? '',
        ];
        $conn = db_connect();
        $data = get_imoveis($conn, $filters);
        echo json_encode(['success' => true, 'data' => $data, 'total' => count($data)]);
        break;
        
    case 'imovel':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            break;
        }
        // Optimizado: filtrar directamente por ID em vez de carregar todos
        $conn = db_connect();
        $imoveis = get_imoveis($conn, ['id' => $id]);
        if (!empty($imoveis)) {
            echo json_encode(['success' => true, 'data' => $imoveis[0]]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Imóvel não encontrado']);
        }
        break;
        
    case 'agentes':
        $conn = db_connect();
        $data = get_agentes($conn);
        echo json_encode(['success' => true, 'data' => $data]);
        break;
        
    case 'agente':
        $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
        if (!$slug) {
            echo json_encode(['success' => false, 'error' => 'Slug inválido']);
            break;
        }
        $conn = db_connect();
        $agente = get_agente_by_slug($conn, $slug);
        if (!$agente) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Agente não encontrado']);
            break;
        }
        $imoveis = get_imoveis($conn, ['agente_slug' => $slug]);
        echo json_encode(['success' => true, 'data' => ['agente' => $agente, 'imoveis' => $imoveis]]);
        break;
    
    case 'equipa':
        $conn = db_connect();
        $data = get_equipa($conn);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'cache_clear':
        cache_clear();
        echo json_encode(['success' => true, 'message' => 'Cache limpa']);
        break;

    case 'sofia_patch':
        $results = [];
        // Parar campanhas ativas
        $conn->query("UPDATE " . TBL_PREFIX . "dps_sofia_campaigns SET status='stopped' WHERE status='active'");
        $results['campaigns_stopped'] = $conn->affected_rows;
        // Corrigir a view do módulo Sofia
        $view_path = __DIR__ . '/modules/dps_sofia_calls/views/sofia_calls/index.php';
        $view_content = file_get_contents($view_path);
        $results['view_exists'] = file_exists($view_path);
        $results['view_has_ajax'] = strpos($view_content, 'X-Requested-With') !== false ? 'SIM' : 'NAO';
        $results['view_has_agents'] = strpos($view_content, '$agents') !== false ? 'SIM (ERRO!)' : 'NAO (OK)';
        $results['view_size'] = strlen($view_content);
        // Verificar controller
        $ctrl_path = __DIR__ . '/modules/dps_sofia_calls/controllers/Dps_sofia_calls.php';
        $ctrl_content = file_get_contents($ctrl_path);
        $results['ctrl_has_create_campaign'] = strpos($ctrl_content, 'create_campaign') !== false ? 'SIM' : 'NAO';
        $results['ctrl_has_staff_filter'] = strpos($ctrl_content, 'staff_id') !== false ? 'SIM' : 'NAO';
        echo json_encode(['success' => true, 'data' => $results]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acção inválida']);
}

if ($conn) $conn->close();
