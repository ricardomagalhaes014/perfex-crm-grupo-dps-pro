<?php
/**
 * Verifica se a lógica de bulk assign apaga notas corretamente
 * Aceder via: https://crm.grupo-dps.com/dps_verify_bulk.php
 */
header('Content-Type: application/json');

// Carregar config da BD
$ci_config = __DIR__ . '/application/config/database.php';
if (!file_exists($ci_config)) {
    die(json_encode(['erro' => 'database.php não encontrado']));
}
include_once($ci_config);
$conn = new mysqli(
    $db['default']['hostname'],
    $db['default']['username'],
    $db['default']['password'],
    $db['default']['database']
);
if ($conn->connect_error) {
    die(json_encode(['erro' => $conn->connect_error]));
}

$prefix = isset($db['default']['dbprefix']) ? $db['default']['dbprefix'] : 'tbl';
$results = [];

// 1. Verificar o código atual do Leads.php no servidor
$leads_file = __DIR__ . '/application/controllers/admin/Leads.php';
if (file_exists($leads_file)) {
    $content = file_get_contents($leads_file);
    $results['leads_php_tem_apagar_notas'] = strpos($content, 'Quando se atribui uma lead em massa') !== false ? 'SIM ✅' : 'NÃO ❌';
    $results['leads_php_tem_delete_notes'] = strpos($content, "delete(db_prefix() . 'notes')") !== false ? 'SIM ✅' : 'NÃO ❌';
    $results['leads_php_tem_dateadded'] = strpos($content, "update['dateadded']") !== false ? 'SIM ✅' : 'NÃO ❌';
    $results['leads_php_mtime'] = date('Y-m-d H:i:s', filemtime($leads_file));
    $results['leads_php_tamanho'] = filesize($leads_file) . ' bytes';
} else {
    $results['leads_php'] = 'FICHEIRO NÃO ENCONTRADO ❌';
}

// 2. Contar notas existentes por lead (para referência)
$r = $conn->query("SELECT COUNT(*) as total FROM {$prefix}notes WHERE rel_type='lead'");
$row = $r->fetch_assoc();
$results['total_notas_lead_na_bd'] = (int)$row['total'];

// 3. Verificar leads com notas
$r = $conn->query("
    SELECT l.id, l.firstname, l.lastname, l.assigned, COUNT(n.id) as num_notas
    FROM {$prefix}leads l
    JOIN {$prefix}notes n ON n.rel_id = l.id AND n.rel_type = 'lead'
    GROUP BY l.id
    ORDER BY num_notas DESC
    LIMIT 5
");
$leads_com_notas = [];
while ($row = $r->fetch_assoc()) {
    $leads_com_notas[] = [
        'id' => $row['id'],
        'nome' => $row['firstname'] . ' ' . $row['lastname'],
        'assigned' => $row['assigned'],
        'num_notas' => (int)$row['num_notas'],
    ];
}
$results['leads_com_mais_notas'] = $leads_com_notas;

$conn->close();
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
