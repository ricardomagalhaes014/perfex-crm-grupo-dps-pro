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
define('CRM_URL', 'https://crm.grupo-dps.com/admin');

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
    $where = ["i.published_website = 1", "i.status = 'aprovado'"];
    
    // Filtros opcionais
    if (!empty($filters['tipo'])) {
        $tipo = $conn->real_escape_string($filters['tipo']);
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
        $where[] = "LOWER(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-')) = '$slug'";
    }
    
    $where_sql = implode(' AND ', $where);
    
    $sql = "SELECT
        i.id,
        i.titulo,
        i.tipo,
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
        s.phonenumber AS agente_telefone,
        s.email AS agente_email,
        s.profile_image AS agente_foto,
        LOWER(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-')) AS agente_slug
    FROM " . TBL_PREFIX . "dps_imoveis i
    LEFT JOIN " . TBL_PREFIX . "staff s ON s.staffid = i.agente_id
    WHERE $where_sql
    ORDER BY i.date_approval DESC";
    
    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }
    
    $imoveis = [];
    $base_url = CRM_URL . '/';
    while ($row = $result->fetch_assoc()) {
        // Foto principal
        if (!empty($row['foto_principal'])) {
            $row['foto_principal_url'] = $base_url . $row['foto_principal'];
        } else {
            $row['foto_principal_url'] = null;
        }

        // Galeria
        $fotos_arr = [];
        if (!empty($row['fotos'])) {
            $decoded = json_decode($row['fotos'], true);
            if (is_array($decoded)) {
                $fotos_arr = array_map(function($f) use ($base_url) { return $base_url . $f; }, $decoded);
            }
        }
        $row['fotos_urls'] = $fotos_arr;
        unset($row['fotos']);

        // Foto do agente
        if (!empty($row['agente_foto'])) {
            $row['agente_foto_url'] = $base_url . 'uploads/staff_profile_images/' . $row['agente_foto'];
        } else {
            $row['agente_foto_url'] = null;
        }
        unset($row['agente_foto']);

        // Preço formatado
        $row['preco_formatado'] = $row['preco'] ? number_format((float)$row['preco'], 0, ',', '.') . ' €' : 'Preço sob consulta';

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
        LOWER(REPLACE(CONCAT(s.firstname, '-', s.lastname), ' ', '-')) AS slug,
        COUNT(i.id) AS total_imoveis
    FROM " . TBL_PREFIX . "staff s
    INNER JOIN " . TBL_PREFIX . "dps_imoveis i ON (i.agente_id = s.staffid AND i.published_website = 1 AND i.status = 'aprovado')
    WHERE s.active = 1
    GROUP BY s.staffid
    ORDER BY s.firstname ASC";

    $result = $conn->query($sql);
    if (!$result) return [];

    $agentes = [];
    $base_url = CRM_URL . '/';
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $row['foto_url'] = $base_url . 'uploads/staff_profile_images/' . $row['foto'];
        } else {
            $row['foto_url'] = null;
        }
        unset($row['foto']);
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
    $base_url = CRM_URL . '/';
    if (!empty($agente['foto'])) {
        $agente['foto_url'] = $base_url . 'uploads/staff_profile_images/' . $agente['foto'];
    } else {
        $agente['foto_url'] = null;
    }
    unset($agente['foto']);
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
