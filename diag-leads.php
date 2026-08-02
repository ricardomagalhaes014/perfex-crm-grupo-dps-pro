<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }

require_once __DIR__ . '/application/config/app-config.php';
$pdo = new PDO(
    "mysql:host=" . APP_DB_HOSTNAME . ";dbname=" . APP_DB_NAME . ";charset=utf8mb4",
    APP_DB_USERNAME, APP_DB_PASSWORD
);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$action = isset($_GET['action']) ? $_GET['action'] : 'recent';

if ($action === 'recent') {
    // Ver todas as leads recentes com as suas tags
    $stmt = $pdo->query("
        SELECT l.id, l.name, l.email, l.phonenumber, l.dateadded,
               l.description,
               GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') as tags
        FROM tblleads l
        LEFT JOIN tbltaggables tg ON tg.rel_id = l.id AND tg.rel_type = 'lead'
        LEFT JOIN tbltags t ON t.id = tg.tag_id
        WHERE l.dateadded >= '2026-08-01 00:00:00'
        GROUP BY l.id
        ORDER BY l.dateadded DESC
        LIMIT 30
    ");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['total' => count($leads), 'leads' => $leads], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'mv_only') {
    // Ver leads com APENAS tag MV (sem AURA) desde uma data
    $since = isset($_GET['since']) ? $_GET['since'] : '2026-08-01';
    $stmt = $pdo->prepare("
        SELECT l.id, l.name, l.email, l.phonenumber, l.dateadded, l.description
        FROM tblleads l
        INNER JOIN tbltaggables tg ON tg.rel_id = l.id AND tg.rel_type = 'lead'
        INNER JOIN tbltags t ON t.id = tg.tag_id AND t.name = 'MV'
        WHERE l.dateadded >= ?
        AND l.id NOT IN (
            SELECT tg2.rel_id FROM tbltaggables tg2
            INNER JOIN tbltags t2 ON t2.id = tg2.tag_id AND t2.name = 'AURA'
            WHERE tg2.rel_type = 'lead'
        )
        ORDER BY l.dateadded DESC
    ");
    $stmt->execute([$since]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['total' => count($leads), 'leads' => $leads], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'scenarios') {
    // Ver quantas leads entraram por cada endpoint (pela descrição)
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN description LIKE '%Formulário AURA%' THEN 'AURA endpoint'
                WHEN description LIKE '%Facebook Lead Ads - MV%' THEN 'MV endpoint'
                WHEN description LIKE '%Facebook Lead Ads - Formulário MV%' THEN 'MV endpoint'
                ELSE 'Outro'
            END as endpoint,
            COUNT(*) as total,
            MIN(dateadded) as primeiro,
            MAX(dateadded) as ultimo
        FROM tblleads
        WHERE dateadded >= '2026-07-01'
        GROUP BY endpoint
        ORDER BY total DESC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_PRETTY_PRINT);
    exit;
}
