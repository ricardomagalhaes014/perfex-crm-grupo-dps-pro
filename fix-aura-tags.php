<?php
/**
 * Fix AURA Tags - Corrige leads que entraram com tag MV mas são do formulário AURA
 * Uso: https://crm.grupo-dps.com/fix-aura-tags.php?token=dps2026deploy
 * Uso (dry-run): https://crm.grupo-dps.com/fix-aura-tags.php?token=dps2026deploy&dry=1
 */

define('BASEPATH', __DIR__ . '/');

$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$dry_run = isset($_GET['dry']) && $_GET['dry'] == '1';

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

// Encontrar leads com tag MV que têm "AURA" na descrição
// (foram criadas pelo cenário AURA mas usaram o endpoint MV por engano)
$stmt = $pdo->prepare("
    SELECT l.id, l.name, l.email, l.description, l.dateadded
    FROM tblleads l
    INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = ?
    WHERE l.description LIKE '%AURA%'
    OR l.description LIKE '%3920049508291548%'
    ORDER BY l.dateadded DESC
");
$stmt->execute([$mv_tag_id]);
$leads_to_fix = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Também procurar leads com tag MV que vieram do cenário AURA (pelo form_id na descrição)
// O form_id do AURA é 3920049508291548
$stmt2 = $pdo->prepare("
    SELECT l.id, l.name, l.email, l.description, l.dateadded
    FROM tblleads l
    INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = ?
    WHERE l.description LIKE '%AURA RICARDO MAGALHAES%'
    ORDER BY l.dateadded DESC
");
$stmt2->execute([$mv_tag_id]);
$leads_to_fix2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Combinar e remover duplicados
$all_leads = [];
foreach (array_merge($leads_to_fix, $leads_to_fix2) as $lead) {
    $all_leads[$lead['id']] = $lead;
}

$fixed = [];
$errors = [];

foreach ($all_leads as $lead) {
    if ($dry_run) {
        $fixed[] = [
            'id' => $lead['id'],
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

        // Adicionar tag AURA (se não existir já)
        $stmt = $pdo->prepare("INSERT IGNORE INTO tbltaggables (tag_id, rel_id, rel_type, tag_order) VALUES (?, ?, 'lead', 0)");
        $stmt->execute([$aura_tag_id, $lead['id']]);

        // Actualizar descrição para reflectir AURA
        $new_desc = str_replace('Formulário MV', 'Formulário AURA', $lead['description']);
        $stmt = $pdo->prepare("UPDATE tblleads SET description = ? WHERE id = ?");
        $stmt->execute([$new_desc, $lead['id']]);

        $fixed[] = [
            'id' => $lead['id'],
            'name' => $lead['name'],
            'email' => $lead['email'],
            'dateadded' => $lead['dateadded'],
            'action' => 'fixed'
        ];
    } catch (PDOException $e) {
        $errors[] = ['id' => $lead['id'], 'error' => $e->getMessage()];
    }
}

echo json_encode([
    'dry_run' => $dry_run,
    'mv_tag_id' => $mv_tag_id,
    'aura_tag_id' => $aura_tag_id,
    'total_found' => count($all_leads),
    'fixed' => $fixed,
    'errors' => $errors
], JSON_PRETTY_PRINT);
