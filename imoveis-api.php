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

function get_imoveis($conn, $filters = []) {
    $where = ["i.can_be_property_listing = 1", "i.published_website = 1"];
    
    // Filtros opcionais
    if (!empty($filters['tipo'])) {
        $tipo = $conn->real_escape_string($filters['tipo']);
        $where[] = "i.property_style = '$tipo'";
    }
    if (!empty($filters['distrito'])) {
        $distrito = $conn->real_escape_string($filters['distrito']);
        $where[] = "i.distrito = '$distrito'";
    }
    if (!empty($filters['tipologia'])) {
        $tipologia = $conn->real_escape_string($filters['tipologia']);
        $where[] = "i.tipologia = '$tipologia'";
    }
    if (!empty($filters['preco_min'])) {
        $preco_min = (float)$filters['preco_min'];
        $where[] = "i.rate >= $preco_min";
    }
    if (!empty($filters['preco_max'])) {
        $preco_max = (float)$filters['preco_max'];
        $where[] = "i.rate <= $preco_max";
    }
    if (!empty($filters['agente_slug'])) {
        $slug = $conn->real_escape_string($filters['agente_slug']);
        $where[] = "LOWER(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-')) = '$slug'";
    }
    
    $where_sql = implode(' AND ', $where);
    
    $sql = "SELECT 
        i.id,
        i.description AS titulo,
        i.short_description AS resumo,
        i.long_description AS descricao,
        i.rate AS preco,
        i.property_style AS tipo_imovel,
        i.listing_type AS tipo_anuncio,
        i.city AS cidade,
        i.state AS estado,
        i.distrito,
        i.tipologia,
        i.beds AS nr_quartos,
        i.area_quartos,
        i.nr_suites,
        i.area_suites,
        i.nr_salas,
        i.area_salas,
        i.full_baths AS nr_casas_banho,
        i.area_casas_banho,
        i.area_cozinha,
        i.equipamento,
        i.sqFt_total AS area_total,
        i.garage AS garagem,
        i.lugar_garagem,
        i.ano_construcao,
        i.long_description AS texto_livre,
        i.status,
        i.date_entered AS data_criacao,
        i.date_approval AS data_aprovacao,
        s.staffid AS agente_id,
        s.firstname AS agente_nome,
        s.lastname AS agente_apelido,
        s.email AS agente_email,
        s.phonenumber AS agente_telefone,
        s.profile_image AS agente_foto,
        LOWER(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-')) AS agente_slug
    FROM " . TBL_PREFIX . "items i
    LEFT JOIN " . TBL_PREFIX . "staff s ON (i.related_type = 'staff' AND i.related_id = s.staffid)
    WHERE $where_sql
    ORDER BY i.date_approval DESC";
    
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }
    
    $imoveis = [];
    while ($row = $result->fetch_assoc()) {
        // Buscar fotos do imóvel
        $id = (int)$row['id'];
        $fotos = get_imovel_fotos($conn, $id);
        $row['fotos'] = $fotos;
        $row['foto_principal'] = !empty($fotos) ? $fotos[0] : null;
        
        // Formatar dados do agente
        if ($row['agente_foto']) {
            $row['agente_foto_url'] = CRM_URL . '/uploads/staff_profile_images/' . $row['agente_foto'];
        } else {
            $row['agente_foto_url'] = null;
        }
        
        // Formatar preço
        $row['preco_formatado'] = number_format((float)$row['preco'], 0, ',', '.') . ' €';
        
        $imoveis[] = $row;
    }
    
    return $imoveis;
}

function get_imovel_fotos($conn, $imovel_id) {
    $sql = "SELECT site_url, file_name, is_main_image 
            FROM " . TBL_PREFIX . "real_property_assets 
            WHERE item_id = $imovel_id 
            ORDER BY is_main_image DESC, id ASC 
            LIMIT 20";
    
    $result = $conn->query($sql);
    if (!$result) {
        // Tentar tabela alternativa
        $sql2 = "SELECT site_url FROM " . TBL_PREFIX . "itemsattachments 
                 WHERE itemid = $imovel_id LIMIT 20";
        $result = $conn->query($sql2);
        if (!$result) return [];
    }
    
    $fotos = [];
    while ($row = $result->fetch_assoc()) {
        $url = !empty($row['site_url']) ? $row['site_url'] : null;
        if ($url) {
            $fotos[] = $url;
        }
    }
    return $fotos;
}

function get_agentes($conn) {
    $sql = "SELECT DISTINCT
        s.staffid AS id,
        s.firstname AS nome,
        s.lastname AS apelido,
        s.email,
        s.phonenumber AS telefone,
        s.profile_image AS foto,
        LOWER(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-')) AS slug,
        COUNT(i.id) AS total_imoveis
    FROM " . TBL_PREFIX . "staff s
    INNER JOIN " . TBL_PREFIX . "items i ON (i.related_type = 'staff' AND i.related_id = s.staffid AND i.can_be_property_listing = 1 AND i.published_website = 1)
    WHERE s.active = 1
    GROUP BY s.staffid
    ORDER BY s.firstname ASC";
    
    $result = $conn->query($sql);
    if (!$result) return [];
    
    $agentes = [];
    while ($row = $result->fetch_assoc()) {
        if ($row['foto']) {
            $row['foto_url'] = CRM_URL . '/uploads/staff_profile_images/' . $row['foto'];
        } else {
            $row['foto_url'] = null;
        }
        $agentes[] = $row;
    }
    return $agentes;
}

function get_agente_by_slug($conn, $slug) {
    $slug_escaped = $conn->real_escape_string($slug);
    $sql = "SELECT 
        s.staffid AS id,
        s.firstname AS nome,
        s.lastname AS apelido,
        s.email,
        s.phonenumber AS telefone,
        s.profile_image AS foto
    FROM " . TBL_PREFIX . "staff s
    WHERE LOWER(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-')) = '$slug_escaped'
    AND s.active = 1
    LIMIT 1";
    
    $result = $conn->query($sql);
    if (!$result || $result->num_rows === 0) return null;
    
    $agente = $result->fetch_assoc();
    if ($agente['foto']) {
        $agente['foto_url'] = CRM_URL . '/uploads/staff_profile_images/' . $agente['foto'];
    } else {
        $agente['foto_url'] = null;
    }
    return $agente;
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
            'preco_min' => $_GET['preco_min'] ?? '',
            'preco_max' => $_GET['preco_max'] ?? '',
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
        $slug = isset($_GET['slug']) ? $_GET['slug'] : '';
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
        // Buscar imóveis do agente
        $imoveis = get_imoveis($conn, ['agente_slug' => $slug]);
        echo json_encode(['success' => true, 'data' => ['agente' => $agente, 'imoveis' => $imoveis]]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acção inválida']);
}

$conn->close();
