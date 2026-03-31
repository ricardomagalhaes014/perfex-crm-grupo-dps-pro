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
// Configuração da BD (lida do app-config.php do Perfex)
define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');
define('TBL_PREFIX', 'tbl');
// URL base do CRM para imagens
define('CRM_URL', 'https://crm.grupo-dps.com');
// Caminho físico base no servidor
define('SERVER_BASE', '/home/u172337921/domains/grupo-dps.com/public_html/');

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
 * Determina a melhor URL de foto para um agente/staff
 * Prioridade: landing_foto (se existir fisicamente) > profile_image > null
 */
function get_foto_url($staffid, $landing_foto, $profile_image) {
    $base_url = CRM_URL . '/';
    $base_path = SERVER_BASE;
    
    // 1. Tentar landing_foto
    if (!empty($landing_foto)) {
        $landing_path = $base_path . 'uploads/landing_fotos/' . $staffid . '/' . $landing_foto;
        if (file_exists($landing_path)) {
            return $base_url . 'uploads/landing_fotos/' . $staffid . '/' . $landing_foto;
        }
    }
    
    // 2. Fallback para foto de perfil do staff
    if (!empty($profile_image)) {
        // Tentar subpasta por staffid (formato novo)
        $profile_path_sub = $base_path . 'uploads/staff_profile_images/' . $staffid . '/small_' . $profile_image;
        if (file_exists($profile_path_sub)) {
            return $base_url . 'uploads/staff_profile_images/' . $staffid . '/small_' . $profile_image;
        }
        // Tentar sem subpasta (formato antigo)
        $profile_path_flat = $base_path . 'uploads/staff_profile_images/' . $profile_image;
        if (file_exists($profile_path_flat)) {
            return $base_url . 'uploads/staff_profile_images/' . $profile_image;
        }
        // Tentar thumb
        $profile_path_thumb = $base_path . 'uploads/staff_profile_images/' . $staffid . '/thumb_' . $profile_image;
        if (file_exists($profile_path_thumb)) {
            return $base_url . 'uploads/staff_profile_images/' . $staffid . '/thumb_' . $profile_image;
        }
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
        // Converter de EN para PT se necessário
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
    $base_url = CRM_URL . '/';
    $module_url = CRM_URL . '/modules/dps_imoveis/uploads/fotos/';
    
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['foto_principal'])) {
            if (strpos($row['foto_principal'], 'http') === 0) {
                $row['foto_principal_url'] = $row['foto_principal'];
            } else {
                $row['foto_principal_url'] = $module_url . $row['foto_principal'];
            }
        } else {
            $row['foto_principal_url'] = null;
        }
        if (!empty($row['fotos'])) {
            $fotos_arr = json_decode($row['fotos'], true) ?: [];
            $row['fotos_urls'] = array_map(function($f) use ($module_url) {
                return (strpos($f, 'http') === 0) ? $f : $module_url . $f;
            }, $fotos_arr);
        } else {
            $row['fotos_urls'] = [];
        }
        // Foto do agente com fallback
        $row['agente_foto_url'] = get_foto_url(
            $row['agente_id'],
            $row['agente_landing_foto'] ?? null,
            $row['agente_foto']
        );
        // Foto principal: usar URL directamente (campo foto_principal já é URL)
        // O React espera 'foto_principal' como URL
        $row['foto_principal'] = $row['foto_principal_url'];
        unset($row['foto_principal_url']);
        // Formatar preço
        $row['preco_formatado'] = number_format((float)$row['preco'], 0, ',', '.') . ' €';
        unset($row['fotos'], $row['agente_foto'], $row['agente_landing_foto']);
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
        s.landing_whatsapp,
        cfv_wa.value AS whatsapp
    FROM " . TBL_PREFIX . "staff s
    LEFT JOIN " . TBL_PREFIX . "customfieldsvalues cfv_wa ON (cfv_wa.relid = s.staffid AND cfv_wa.fieldid = 19)
    WHERE s.active = 1";
    $result = $conn->query($sql);
    if (!$result) return null;
    $agente = null;
    while ($row = $result->fetch_assoc()) {
        // Verificar slug por landing_slug ou slug gerado
        $landing_slug = !empty($row['landing_slug']) ? $row['landing_slug'] : null;
        $generated_slug = make_slug($row['nome'], $row['apelido']);
        if ($landing_slug === $slug || $generated_slug === $slug) {
            $agente = $row;
            break;
        }
    }
    if (!$agente) return null;
    
    // Determinar a melhor foto
    $agente['foto_url'] = get_foto_url($agente['id'], $agente['landing_foto'], $agente['foto']);
    $agente['slug'] = $slug;
    unset($agente['foto'], $agente['landing_foto']);
    return $agente;
}

/**
 * Endpoint para a página /equipa - retorna toda a equipa com hierarquia
 */
function get_equipa($conn) {
    // Buscar todos os staff activos com foto
    $sql = "SELECT
        s.staffid AS id,
        s.firstname AS nome,
        s.lastname AS apelido,
        s.email,
        s.phonenumber AS telefone,
        s.profile_image AS foto,
        s.landing_foto,
        s.landing_slug,
        s.is_admin,
        cfv_wa.value AS whatsapp,
        cfv_cargo.value AS cargo,
        cfv_mercado.value AS mercado
    FROM " . TBL_PREFIX . "staff s
    LEFT JOIN " . TBL_PREFIX . "customfieldsvalues cfv_wa ON (cfv_wa.relid = s.staffid AND cfv_wa.fieldid = 19)
    LEFT JOIN " . TBL_PREFIX . "customfieldsvalues cfv_cargo ON (cfv_cargo.relid = s.staffid AND cfv_cargo.fieldid = 20)
    LEFT JOIN " . TBL_PREFIX . "customfieldsvalues cfv_mercado ON (cfv_mercado.relid = s.staffid AND cfv_mercado.fieldid = 21)
    WHERE s.active = 1
    ORDER BY s.is_admin DESC, s.staffid ASC";
    
    $result = $conn->query($sql);
    if (!$result) return [];
    
    $staff = [];
    while ($row = $result->fetch_assoc()) {
        $foto_url = get_foto_url($row['id'], $row['landing_foto'], $row['foto']);
        // Só incluir se tiver foto
        if (empty($foto_url)) continue;
        
        $row['foto_url'] = $foto_url;
        $row['slug'] = !empty($row['landing_slug']) ? $row['landing_slug'] : make_slug($row['nome'], $row['apelido']);
        unset($row['foto'], $row['landing_foto'], $row['landing_slug']);
        $staff[] = $row;
    }
    
    // Buscar equipas do módulo dps_teams
    $teams_sql = "SELECT t.id, t.name, t.area, t.parent_id,
        m.staff_id, m.role
    FROM " . TBL_PREFIX . "dps_teams t
    LEFT JOIN " . TBL_PREFIX . "dps_team_members m ON m.team_id = t.id
    ORDER BY t.area, t.id, m.role";
    
    $teams_result = $conn->query($teams_sql);
    $teams = [];
    if ($teams_result) {
        while ($tr = $teams_result->fetch_assoc()) {
            $tid = $tr['id'];
            if (!isset($teams[$tid])) {
                $teams[$tid] = [
                    'id' => $tid,
                    'name' => $tr['name'],
                    'area' => $tr['area'],
                    'parent_id' => $tr['parent_id'],
                    'members' => []
                ];
            }
            if (!empty($tr['staff_id'])) {
                $teams[$tid]['members'][] = [
                    'staff_id' => $tr['staff_id'],
                    'role' => $tr['role']
                ];
            }
        }
    }
    
    return [
        'staff' => $staff,
        'teams' => array_values($teams)
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
