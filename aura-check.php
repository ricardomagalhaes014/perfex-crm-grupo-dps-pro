<?php
/**
 * AURA Check & Fix - Diagnóstico e correcção de tags
 * Uso: ?token=dps2026deploy&action=check|fix_dry|fix_run
 */
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }

// Carregar credenciais da BD
require_once __DIR__ . '/application/config/app-config.php';
try {
    $pdo = new PDO(
        "mysql:host=" . APP_DB_HOSTNAME . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
        APP_DB_USERNAME, APP_DB_PASSWORD
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB: ' . $e->getMessage()]); exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'check';

if ($action === 'check') {
    // Mostrar leads MV desde data especificada (default: últimas 10)
    $since = isset($_GET['since']) ? $_GET['since'] : null;
    if ($since) {
        $stmt_mv = $pdo->prepare("
            SELECT l.id, l.name, l.email, l.description, l.dateadded
            FROM tblleads l
            INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead'
            INNER JOIN tbltags tg ON tg.id = t.tag_id AND tg.name = 'MV'
            WHERE l.dateadded >= ?
            ORDER BY l.dateadded DESC
        ");
        $stmt_mv->execute([$since]);
    } else {
        $stmt_mv = $pdo->query("
            SELECT l.id, l.name, l.email, l.description, l.dateadded
            FROM tblleads l
            INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead'
            INNER JOIN tbltags tg ON tg.id = t.tag_id AND tg.name = 'MV'
            ORDER BY l.dateadded DESC LIMIT 10
        ");
    }
    $stmt_aura = $pdo->query("
        SELECT l.id, l.name, l.email, l.description, l.dateadded
        FROM tblleads l
        INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead'
        INNER JOIN tbltags tg ON tg.id = t.tag_id AND tg.name = 'AURA'
        ORDER BY l.dateadded DESC LIMIT 10
    ");
    echo json_encode([
        'action' => 'check',
        'since' => $since,
        'last_mv_leads' => $stmt_mv->fetchAll(PDO::FETCH_ASSOC),
        'last_aura_leads' => $stmt_aura->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'fix_by_date') {
    // Corrigir leads MV -> AURA desde uma data especificada
    $since = isset($_GET['since']) ? $_GET['since'] : null;
    $dry = isset($_GET['dry']) ? ($_GET['dry'] == '1') : true;
    if (!$since) { echo json_encode(['error' => 'Parametro since obrigatorio (ex: 2026-07-29']); exit; }

    // Obter tag IDs
    $mv_row = $pdo->query("SELECT id FROM tbltags WHERE name = 'MV' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $aura_row = $pdo->query("SELECT id FROM tbltags WHERE name = 'AURA' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$mv_row) { echo json_encode(['error' => 'Tag MV nao encontrada']); exit; }
    $mv_tag_id = $mv_row['id'];
    $aura_tag_id = $aura_row ? $aura_row['id'] : null;
    if (!$aura_tag_id) {
        $pdo->prepare("INSERT INTO tbltags (name) VALUES ('AURA')")->execute();
        $aura_tag_id = $pdo->lastInsertId();
    }

    // Obter leads com tag MV desde a data
    $stmt = $pdo->prepare("
        SELECT l.id, l.name, l.email, l.description, l.dateadded
        FROM tblleads l
        INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = ?
        WHERE l.dateadded >= ?
        ORDER BY l.dateadded ASC
    ");
    $stmt->execute([$mv_tag_id, $since]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fixed = 0;
    if (!$dry) {
        foreach ($leads as $lead) {
            // Remover tag MV
            $pdo->prepare("DELETE FROM tbltaggables WHERE rel_id=? AND rel_type='lead' AND tag_id=?")->execute([$lead['id'], $mv_tag_id]);
            // Adicionar tag AURA
            $pdo->prepare("INSERT IGNORE INTO tbltaggables (rel_id, rel_type, tag_id) VALUES (?,?,?)")->execute([$lead['id'], 'lead', $aura_tag_id]);
            // Actualizar descrição
            $new_desc = str_replace('Lead Facebook Lead Ads - MV', 'Lead Facebook Lead Ads - AURA', $lead['description']);
            $pdo->prepare("UPDATE tblleads SET description=? WHERE id=?")->execute([$new_desc, $lead['id']]);
            $fixed++;
        }
    }

    echo json_encode([
        'action' => $action,
        'since' => $since,
        'dry_run' => $dry,
        'total_found' => count($leads),
        'fixed' => $fixed,
        'leads' => $leads
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($action === 'fix_dry' || $action === 'fix_run') {
    $dry = ($action === 'fix_dry');
    $AURA_FORM_ID = '3920049508291548';
    $fb_token_param = isset($_GET['fb_token']) ? trim($_GET['fb_token']) : '';

    // Obter tag IDs
    $mv_row = $pdo->query("SELECT id FROM tbltags WHERE name = 'MV' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $aura_row = $pdo->query("SELECT id FROM tbltags WHERE name = 'AURA' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$mv_row) { echo json_encode(['error' => 'Tag MV nao encontrada']); exit; }
    $mv_tag_id = $mv_row['id'];
    $aura_tag_id = $aura_row ? $aura_row['id'] : null;
    if (!$aura_tag_id) {
        $pdo->prepare("INSERT INTO tbltags (name) VALUES ('AURA')")->execute();
        $aura_tag_id = $pdo->lastInsertId();
    }

    // Obter leads com tag MV que têm Facebook Lead ID na descrição
    $stmt = $pdo->prepare("
        SELECT l.id, l.name, l.email, l.description, l.dateadded
        FROM tblleads l
        INNER JOIN tbltaggables t ON t.rel_id = l.id AND t.rel_type = 'lead' AND t.tag_id = ?
        WHERE l.description LIKE '%Facebook Lead ID:%'
        ORDER BY l.dateadded DESC
    ");
    $stmt->execute([$mv_tag_id]);
    $mv_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $to_fix = [];
    $checked = 0;
    $fixed = 0;
    $errors = [];

    foreach ($mv_leads as $lead) {
        $checked++;
        // Extrair o Facebook Lead ID da descrição
        if (preg_match('/Facebook Lead ID:\s*(\d+)/i', $lead['description'], $m)) {
            $fb_lead_id = $m[1];
            // Verificar o form_id via Facebook Graph API
            if (!empty($fb_token_param)) {
                $url = "https://graph.facebook.com/v19.0/{$fb_lead_id}?fields=form_id&access_token={$fb_token_param}";
                $resp = @file_get_contents($url);
                if ($resp) {
                    $data = json_decode($resp, true);
                    if (isset($data['form_id']) && $data['form_id'] == $AURA_FORM_ID) {
                        $to_fix[] = $lead;
                        if (!$dry) {
                            // Remover tag MV
                            $pdo->prepare("DELETE FROM tbltaggables WHERE rel_id=? AND rel_type='lead' AND tag_id=?")->execute([$lead['id'], $mv_tag_id]);
                            // Adicionar tag AURA
                            $pdo->prepare("INSERT IGNORE INTO tbltaggables (rel_id, rel_type, tag_id) VALUES (?,?,?)")->execute([$lead['id'], 'lead', $aura_tag_id]);
                            $fixed++;
                        }
                    }
                } else {
                    $errors[] = "Lead {$lead['id']}: erro ao consultar Facebook API";
                }
            } else {
                // Sem token FB: listar todas as leads MV com FB Lead ID para revisão manual
                $to_fix[] = array_merge($lead, ['fb_lead_id' => $fb_lead_id]);
            }
        }
    }

    echo json_encode([
        'action' => $action,
        'dry_run' => $dry,
        'total_mv_with_fb_id' => count($mv_leads),
        'checked' => $checked,
        'to_fix' => $to_fix,
        'fixed' => $fixed,
        'errors' => $errors,
        'note' => empty($fb_token_param) ? 'Sem fb_token: listando todas as leads MV com FB Lead ID para revisao manual' : ''
    ], JSON_PRETTY_PRINT);
    exit;
}

echo json_encode(['error' => 'Acao invalida. Use: check, fix_dry, fix_run']);
