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
    // Remover duplicação do caminho se existir
    $dup = 'modules/dps_imoveis/uploads/fotos/modules/dps_imoveis/uploads/fotos/';
    $fix = 'modules/dps_imoveis/uploads/fotos/';
    if (strpos($url, $dup) !== false) {
        $url = str_replace($dup, $fix, $url);
    }
    // Se o URL já começa com http, retornar directamente
    if (strpos($url, 'http') === 0) return $url;
    // Caso contrário, construir URL completo
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

/**
 * Determina a melhor URL de foto para um agente/staff.
 * Constrói URLs directamente sem file_exists() (que falha com SERVER_BASE errado).
 * Prioridade: landing_foto > small_profile > flat_profile > thumb_profile
 */
function get_foto_url($staffid, $landing_foto, $profile_image) {
    $base = CRM_URL . '/';

    // 1. Prioridade: landing_foto (campo guardado no CRM na secção Landing Page)
    if (!empty($landing_foto)) {
        return $base . 'uploads/staff_landing_photos/' . $staffid . '/' . $landing_foto;
    }

    // 2. Fallback: foto de perfil — construir URL directamente sem verificar existência
    // (evitar curl HEAD requests que causam timeout em loop)
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

// Mapeamento de tipos EN->PT (para compatibilidade com o React)
$TIPO_MAP = [
    'Apartment'  => 'Apartamento',
    'House'      => 'Moradia',
    'Commercial' => 'Loja',
    'Land'       => 'Terreno',
];

function get_imoveis($conn, $filters = []) {
    global $TIPO_MAP;
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
        // Foto principal - corrigir URL duplicado se existir
        if (!empty($row['foto_principal'])) {
            $row['foto_principal'] = fix_image_url($row['foto_principal']);
        } else {
            $row['foto_principal'] = null;
        }

        // Galeria - corrigir URLs duplicados se existirem
        if (!empty($row['fotos'])) {
            $fotos_arr = json_decode($row['fotos'], true) ?: [];
            $fotos_fixed = array_map(function($f) { return fix_image_url($f); }, $fotos_arr);
            $row['fotos'] = $fotos_fixed;      // campo 'fotos' usado pelo frontend React
            $row['fotos_urls'] = $fotos_fixed; // alias para compatibilidade
        } else {
            $row['fotos'] = [];
            $row['fotos_urls'] = [];
        }
        // Foto do agente com fallback
        $row['agente_foto_url'] = get_foto_url(
            $row['agente_id'],
            $row['agente_landing_foto'] ?? null,
            $row['agente_foto']
        );
        // Formatar preço
        $row['preco_formatado'] = number_format((float)$row['preco'], 0, ',', '.') . ' €';
        unset($row['agente_foto'], $row['agente_landing_foto']); // 'fotos' mantido para o frontend React
        $imoveis[] = $row;
    }
    return $imoveis;
}

function get_agentes($conn) {
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
    return $agentes;
}

function get_agente_by_slug($conn, $slug) {
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
    return $agente;
}

/**
 * Endpoint para a página /equipa - retorna equipa por sector de mercado
 * Lê da tabela tbldps_teams (sem coluna parent_id)
 */
function get_equipa($conn) {
    // Buscar todos os staff activos (sem JOIN para evitar erros com tabelas opcionais)
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
    
    // Buscar equipas imobiliárias (sem coluna parent_id)
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
                    'id'       => $tid,
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
    
    // Liderança fixa: CEO (id=1) e Gestor de Equipa (id=46)
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
    
    // Mapear nomes de equipas para mercados
    $market_map = [
        'IMO BRASIL & BSX' => ['flag' => '🇧🇷', 'label' => 'Brasil'],
        'IMO BRASIL'       => ['flag' => '🇧🇷', 'label' => 'Brasil'],
        'IMO PORTUGAL'     => ['flag' => '🇵🇹', 'label' => 'Portugal'],
        'IMO DUBAI'        => ['flag' => '🇦🇪', 'label' => 'Dubai'],
    ];
    
    // Ordenar mercados: Portugal, Brasil, Dubai
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
    
    return [
        'lideranca' => $lideranca,
        'mercados'  => $mercados,
    ];
}

// Router principal
$action = isset($_GET['action']) ? $_GET['action'] : 'imoveis';
$conn = db_connect();
switch ($action) {
    case 'imoveis':
        $filters = [
            'tipo'      => $_GET['tipo'] ?? '',
            'distrito'  => $_GET['distrito'] ?? '',
            'tipologia' => $_GET['tipologia'] ?? '',
        ];
        $data = get_imoveis($conn, $filters);
        echo json_encode(['success' => true, 'data' => $data, 'total' => count($data)]);
        break;
        
    case 'imovel':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID inválido']);
            break;
        }
        $imoveis = get_imoveis($conn, []);
        $imovel = null;
        foreach ($imoveis as $i) {
            if ((int)$i['id'] === $id) {
                $imovel = $i;
                break;
            }
        }
        if ($imovel) {
            echo json_encode(['success' => true, 'data' => $imovel]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Imóvel não encontrado']);
        }
        break;
        
    case 'agentes':
        $data = get_agentes($conn);
        echo json_encode(['success' => true, 'data' => $data]);
        break;
        
    case 'agente':
        $slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
        if (!$slug) {
            echo json_encode(['success' => false, 'error' => 'Slug inválido']);
            break;
        }
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
        $data = get_equipa($conn);
        echo json_encode(['success' => true, 'data' => $data]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acção inválida']);
}
$conn->close();
