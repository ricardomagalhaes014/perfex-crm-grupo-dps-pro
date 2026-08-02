<?php
/**
 * Fix AURA Tags - Corrige leads que entraram com tag MV mas são do formulário AURA
 * O form_id do AURA é 3920049508291548
 * 
 * Uso (dry-run): https://crm.grupo-dps.com/fix-aura-tags.php?token=dps2026deploy&dry=1
 * Uso (executar): https://crm.grupo-dps.com/fix-aura-tags.php?token=dps2026deploy
 * Uso (debug - ver leads MV com FB lead IDs): https://crm.grupo-dps.com/fix-aura-tags.php?token=dps2026deploy&debug=1
 */

define('BASEPATH', __DIR__ . '/');

$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$dry_run = isset($_GET['dry']) && $_GET['dry'] == '1';
$debug   = isset($_GET['debug']) && $_GET['debug'] == '1';

// Form ID do formulário AURA no Facebook
$AURA_FORM_ID = '3920049508291548';

require_once __DIR__ . '/application/config/app-config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . APP_DB_HOSTNAME . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
        APP_DB_USERNAME,
        APP_DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'DB: ' . $e->getMessage()]));
}

// Encontrar o ID da tag MV
$stmt = $pdo->prepare("SELECT id FROM tbltags WHERE name = 'MV' LIMIT 1");
$stmt->execute();
$mv_tag = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mv_tag) {
    die(json_encode(['error' => 'Tag MV não encontrada']));
}
$mv_tag_id = $mv_tag['id'];

// Encontrar ou criar a tag AURA
$stmt = $pdo->prepare("SELECT id FROM tbltags WHERE name = 'AURA' LIMIT 1");
$stmt->execute();
$aura_tag = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aura_tag) {
    $stmt = $pdo->prepare("INSERT INTO tbltags (name) VALUES ('AURA')");
    $stmt->execute();
    $aura_tag_id = $pdo->lastInsertId();
} else {
    $aura_tag_id = $aura_tag['id'];
}

// Obter TODAS as leads com tag MV que têm um Facebook Lead ID na descrição
$stmt = $pdo->prepare("
    SELECT l.id, l.name, l.email, l.phonenumber, l.description, l.dateadded
    FROM tblleads l
    INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = ?
    WHERE l.description LIKE '%Facebook Lead ID:%'
    ORDER BY l.dateadded DESC
");
$stmt->execute([$mv_tag_id]);
$mv_leads_with_fb_id = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Extrair os Facebook Lead IDs das descrições
$fb_lead_ids = [];
$lead_map = []; // fb_lead_id => lead_data

foreach ($mv_leads_with_fb_id as $lead) {
    if (preg_match('/Facebook Lead ID:\s*(\d+)/i', $lead['description'], $m)) {
        $fb_lead_id = $m[1];
        $fb_lead_ids[] = $fb_lead_id;
        $lead_map[$fb_lead_id] = $lead;
    }
}

if ($debug) {
    echo json_encode([
        'mv_tag_id' => $mv_tag_id,
        'aura_tag_id' => $aura_tag_id,
        'total_mv_leads_with_fb_id' => count($mv_leads_with_fb_id),
        'fb_lead_ids_found' => $fb_lead_ids,
        'leads_sample' => array_slice($mv_leads_with_fb_id, 0, 5)
    ], JSON_PRETTY_PRINT);
    exit;
}

// Obter o Facebook Access Token das configurações do CRM
$stmt = $pdo->prepare("SELECT value FROM tbloptions WHERE name = 'facebook_access_token' LIMIT 1");
$stmt->execute();
$fb_token_row = $stmt->fetch(PDO::FETCH_ASSOC);
$fb_access_token = $fb_token_row ? $fb_token_row['value'] : '';

// Se não houver token do Facebook, usar o form_id guardado nas leads
// O Make guarda o form_id na tag do cenário - vamos verificar via API do Facebook
// se cada lead_id pertence ao form_id do AURA

$leads_to_fix = [];
$fb_check_errors = [];

if (empty($fb_access_token)) {
    // Sem token do Facebook, não conseguimos verificar via API
    // Mas podemos verificar se o Make guardou o form_id na nota ou noutro campo
    // Por agora, reportar o que encontrámos
    echo json_encode([
        'error' => 'Facebook Access Token não encontrado nas configurações do CRM',
        'suggestion' => 'Forneça o token via parâmetro: &fb_token=SEU_TOKEN',
        'mv_leads_with_fb_id' => count($fb_lead_ids),
        'fb_lead_ids' => $fb_lead_ids
    ], JSON_PRETTY_PRINT);
    exit;
}

// Verificar cada lead_id via Facebook Graph API para obter o form_id
foreach ($fb_lead_ids as $fb_lead_id) {
    $url = "https://graph.facebook.com/v18.0/{$fb_lead_id}?fields=form_id&access_token={$fb_access_token}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['form_id']) && $data['form_id'] === $AURA_FORM_ID) {
        $leads_to_fix[$fb_lead_id] = $lead_map[$fb_lead_id];
    } elseif (isset($data['error'])) {
        $fb_check_errors[$fb_lead_id] = $data['error']['message'];
    }
}

$fixed = [];
$errors = [];

foreach ($leads_to_fix as $fb_lead_id => $lead) {
    if ($dry_run) {
        $fixed[] = [
            'crm_id' => $lead['id'],
            'fb_lead_id' => $fb_lead_id,
            'name' => $lead['name'],
            'email' => $lead['email'],
            'dateadded' => $lead['dateadded'],
            'action' => 'would_fix'
        ];
        continue;
    }

    try {
        // Remover tag MV
        $stmt = $pdo->prepare("DELETE FROM tbltaggables WHERE tag_id = ? AND rel_id = ? AND rel_type = 'lead'");
        $stmt->execute([$mv_tag_id, $lead['id']]);

        // Adicionar tag AURA
        $stmt = $pdo->prepare("INSERT IGNORE INTO tbltaggables (tag_id, rel_id, rel_type, tag_order) VALUES (?, ?, 'lead', 0)");
        $stmt->execute([$aura_tag_id, $lead['id']]);

        // Actualizar descrição
        $new_desc = str_replace('Formulário MV', 'Formulário AURA', $lead['description']);
        $stmt = $pdo->prepare("UPDATE tblleads SET description = ? WHERE id = ?");
        $stmt->execute([$new_desc, $lead['id']]);

        $fixed[] = [
            'crm_id' => $lead['id'],
            'fb_lead_id' => $fb_lead_id,
            'name' => $lead['name'],
            'email' => $lead['email'],
            'dateadded' => $lead['dateadded'],
            'action' => 'fixed'
        ];
    } catch (PDOException $e) {
        $errors[] = ['crm_id' => $lead['id'], 'error' => $e->getMessage()];
    }
}

echo json_encode([
    'dry_run' => $dry_run,
    'aura_form_id' => $AURA_FORM_ID,
    'mv_tag_id' => $mv_tag_id,
    'aura_tag_id' => $aura_tag_id,
    'total_mv_leads_checked' => count($fb_lead_ids),
    'total_aura_leads_found' => count($leads_to_fix),
    'fixed' => $fixed,
    'errors' => $errors,
    'fb_check_errors' => $fb_check_errors
], JSON_PRETTY_PRINT);
