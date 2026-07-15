<?php
/**
 * dps_task.php — Cria tarefa(s) no Perfex CRM a partir de um número de telefone.
 *
 * Porquê: substitui a chamada a dps_site_deploy.php?a=exec_sql, que envia SQL cru no
 * corpo do pedido e está a ser bloqueada com 403 (WAF/ModSecurity). Aqui só viaja o
 * número de telefone — nada de SQL — por isso passa.
 *
 * O que faz: procura TODAS as leads com aquele número e cria uma tarefa por cada
 * comercial dono (tblleads.assigned). Se o número pertencer a dois comerciais, cria
 * nos dois. Atribui a tarefa ao dono em tbltask_assigned.
 *
 * Credenciais: lidas do config do Perfex (application/config/database.php).
 * NÃO há passwords neste ficheiro — mesmo padrão do mv-lead.php.
 *
 * Uso (POST x-www-form-urlencoded):
 *   URL:  https://crm.grupo-dps.com/dps_task.php?t=SEU_TOKEN
 *   body: phone=+3519XXXXXXXX
 *         (opcional) title=... | description=...
 *
 * Deploy: raiz do CRM (ao lado do mv-lead.php).
 */

header('Content-Type: application/json; charset=utf-8');

// ─── 1. Token ─────────────────────────────────────────────────────────────────
define('DPS_TASK_TOKEN', 'TROCAR_ESTE_TOKEN');   // <── define aqui o teu segredo
if (!hash_equals(DPS_TASK_TOKEN, (string)($_GET['t'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

// ─── 2. Ligação à BD (credenciais do config do Perfex) ───────────────────────
if (!defined('BASEPATH')) define('BASEPATH', true);
$configFile = __DIR__ . '/application/config/database.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'config nao encontrado']);
    exit;
}
$db = [];
require $configFile;                       // define $db['default']
$cfg = $db['default'] ?? null;
if (!$cfg) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'config invalido']);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . $cfg['hostname'] . ';dbname=' . $cfg['database'] . ';charset=utf8mb4',
        $cfg['username'],
        $cfg['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connect failed']);
    exit;
}

// ─── 3. Input ─────────────────────────────────────────────────────────────────
$last9 = substr(preg_replace('/\D/', '', (string)($_POST['phone'] ?? '')), -9);
if (strlen($last9) < 9) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'phone invalido']);
    exit;
}
$titlePrefix = trim((string)($_POST['title'] ?? 'Contacto Agente IA'));
$descText    = trim((string)($_POST['description'] ?? 'Lead respondeu SIM na chamada da Sofia. Contactar para dar seguimento.'));

// ─── 4. Donos da lead (um por comercial, a lead mais recente) ────────────────
$sql = "SELECT id, name, phonenumber, assigned
          FROM tblleads
         WHERE assigned IS NOT NULL AND assigned <> 0
           AND RIGHT(REPLACE(REPLACE(REPLACE(phonenumber,' ',''),'+351',''),'+',''), 9) = :p
         ORDER BY id ASC";
$st = $pdo->prepare($sql);
$st->execute([':p' => $last9]);

$owners = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $owners[(int)$row['assigned']] = [       // fica a mais recente por dono
        'lead_id' => (int)$row['id'],
        'name'    => $row['name'],
        'phone'   => $row['phonenumber'],
    ];
}

if (!$owners) {
    echo json_encode(['success' => true, 'created' => 0, 'note' => 'nenhuma lead com esse numero']);
    exit;
}

// ─── 5. Criar tarefa + atribuir, por cada dono ───────────────────────────────
$insTask = $pdo->prepare(
    "INSERT INTO tbltasks
        (name, description, priority, dateadded, startdate, duedate, addedfrom,
         is_added_from_contact, status, rel_id, rel_type)
     VALUES (:name, :descr, 3, NOW(), CURDATE(), CURDATE(), :addedfrom, 0, 1, :relid, 'lead')"
);
$insAssign = $pdo->prepare(
    "INSERT INTO tbltask_assigned (staffid, taskid, assigned_from, is_assigned_from_contact)
     VALUES (:staff, :task, :staff2, 0)"
);

$created = [];
$pdo->beginTransaction();
try {
    foreach ($owners as $staffid => $info) {
        $insTask->execute([
            ':name'      => $titlePrefix . ' - ' . $info['name'],
            ':descr'     => $descText . ' Numero: ' . $info['phone'],
            ':addedfrom' => $staffid,
            ':relid'     => $info['lead_id'],
        ]);
        $taskId = (int)$pdo->lastInsertId();
        $insAssign->execute([':staff' => $staffid, ':task' => $taskId, ':staff2' => $staffid]);
        $created[] = ['task_id' => $taskId, 'staffid' => (int)$staffid, 'lead_id' => $info['lead_id']];
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'insert failed']);
    exit;
}

echo json_encode(['success' => true, 'created' => count($created), 'tasks' => $created]);
