<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widget DPS — gráficos pessoais no dashboard.
 * Cada utilizador vê apenas os SEUS números:
 *   1. Propostas enviadas (últimos 30 dias)
 *   2. Leads por estado
 *   2b. Tarefas por estado
 *   3. Interações da última semana (notas em leads)
 *   4. Vendas no mês (por estado)
 *   5. Propostas DPS Crédito no mês (por estado)
 *   6. Crédito ganho no mês (nº + valor)
 */

$CI       = &get_instance();
$p        = db_prefix();
$staff_id = (int) get_staff_user_id();
$mes_ini  = date('Y-m-01 00:00:00');

/* 1. Propostas enviadas — últimos 30 dias, por dia */
$prop_labels = $prop_data = [];
if ($CI->db->table_exists($p . 'dps_propostas')) {
    $rows = $CI->db->query(
        "SELECT DATE(created_at) d, COUNT(*) c FROM {$p}dps_propostas
         WHERE staff_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY DATE(created_at)",
        [$staff_id]
    )->result_array();
    $por_dia = array_column($rows, 'c', 'd');
    for ($i = 29; $i >= 0; $i--) {
        $d             = date('Y-m-d', strtotime("-$i days"));
        $prop_labels[] = date('d/m', strtotime($d));
        $prop_data[]   = (int) ($por_dia[$d] ?? 0);
    }
}

/* 2. Leads por estado */
$leads_labels = $leads_data = $leads_cores = [];
$rows = $CI->db->query(
    "SELECT ls.name, ls.color, COUNT(l.id) c
     FROM {$p}leads l JOIN {$p}leads_status ls ON ls.id = l.status
     WHERE l.assigned = ? GROUP BY ls.id ORDER BY ls.statusorder",
    [$staff_id]
)->result_array();
foreach ($rows as $r) {
    $leads_labels[] = $r['name'];
    $leads_data[]   = (int) $r['c'];
    $leads_cores[]  = $r['color'] ?: '#84c529';
}

/*
 * 2b. Tarefas por estado — só as minhas.
 *
 * Conta pelo task_assigned e não pelo addedfrom: o que interessa a quem olha
 * para o dashboard é o que TEM PARA FAZER, não o que criou. Assim também
 * respeita sozinho a regra de visibilidade — cada um só soma as suas.
 *
 * Os estados e as cores vêm do Tasks_model para o gráfico dizer o mesmo que a
 * lista de tarefas. Escritos à mão, ficariam a divergir na primeira vez que o
 * Perfex mudasse um nome.
 */
$tar_labels = $tar_data = $tar_cores = [];
$CI->load->model('tasks_model');

$rows = $CI->db->query(
    "SELECT t.status, COUNT(*) c
     FROM {$p}tasks t JOIN {$p}task_assigned a ON a.taskid = t.id
     WHERE a.staffid = ? GROUP BY t.status",
    [$staff_id]
)->result_array();
$tar_por_estado = array_column($rows, 'c', 'status');

foreach ($CI->tasks_model->get_statuses() as $e) {
    $tar_labels[] = $e['name'];
    $tar_data[]   = (int) ($tar_por_estado[$e['id']] ?? 0);
    $tar_cores[]  = $e['color'] ?: '#64748b';
}

/* 3. Interações (notas em leads) — últimos 7 dias, por dia */
$int_labels = $int_data = [];
$rows = $CI->db->query(
    "SELECT DATE(dateadded) d, COUNT(*) c FROM {$p}notes
     WHERE rel_type = 'lead' AND addedfrom = ?
       AND dateadded >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(dateadded)",
    [$staff_id]
)->result_array();
$por_dia = array_column($rows, 'c', 'd');
for ($i = 6; $i >= 0; $i--) {
    $d            = date('Y-m-d', strtotime("-$i days"));
    $int_labels[] = date('D d/m', strtotime($d));
    $int_data[]   = (int) ($por_dia[$d] ?? 0);
}

/* 4. Vendas no mês — por estado */
$nomes_estado_venda = [
    'pedido' => 'Pedido', 'reservado' => 'Reservado', 'submetido' => 'Submetido',
    'vendido' => 'CPCV', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado', 'pendente' => 'Pendente',
];
$vendas_labels = $vendas_data = [];
if ($CI->db->table_exists($p . 'simulador_vendas')) {
    $rows = $CI->db->query(
        "SELECT COALESCE(estado,'pendente') e, COUNT(*) c FROM {$p}simulador_vendas
         WHERE staff_id = ? AND date_created >= ? GROUP BY estado",
        [$staff_id, $mes_ini]
    )->result_array();
    foreach ($rows as $r) {
        $vendas_labels[] = $nomes_estado_venda[$r['e']] ?? ucfirst($r['e']);
        $vendas_data[]   = (int) $r['c'];
    }
}

/* 5 + 6. DPS Crédito no mês — por estado + ganhas/valor */
$nomes_estado_credito = [
    'submetido' => 'Submetido', 'documentos_em_falta' => 'Docs em falta',
    'em_analise' => 'Em análise', 'sucesso' => 'Sucesso', 'recusado' => 'Recusado',
];
$cred_labels = $cred_data = [];
$cred_ganhas = 0;
$cred_valor  = 0.0;
if ($CI->db->table_exists($p . 'simulador_credito')) {
    $rows = $CI->db->query(
        "SELECT estado e, COUNT(*) c FROM {$p}simulador_credito
         WHERE staff_id = ? AND date_created >= ? GROUP BY estado",
        [$staff_id, $mes_ini]
    )->result_array();
    foreach ($rows as $r) {
        $cred_labels[] = $nomes_estado_credito[$r['e']] ?? ucfirst(str_replace('_', ' ', (string) $r['e']));
        $cred_data[]   = (int) $r['c'];
    }

    $g = $CI->db->query(
        "SELECT COUNT(*) c, COALESCE(SUM(COALESCE(valor_credito, montante, valor, 0)),0) v
         FROM {$p}simulador_credito
         WHERE staff_id = ? AND estado = 'sucesso'
           AND COALESCE(dateupdated, date_created) >= ?",
        [$staff_id, $mes_ini]
    )->row_array();
    $cred_ganhas = (int) ($g['c'] ?? 0);
    $cred_valor  = (float) ($g['v'] ?? 0);
}

$dps_charts = [
    'propostas'  => ['labels' => $prop_labels,   'data' => $prop_data],
    'leads'      => ['labels' => $leads_labels,  'data' => $leads_data, 'cores' => $leads_cores],
    'tarefas'    => ['labels' => $tar_labels,    'data' => $tar_data,   'cores' => $tar_cores],
    'interacoes' => ['labels' => $int_labels,    'data' => $int_data],
    'vendas'     => ['labels' => $vendas_labels, 'data' => $vendas_data],
    'credito'    => ['labels' => $cred_labels,   'data' => $cred_data],
];
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="DPS — Os meus gráficos">
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body padding-10">
                    <div class="widget-dragger"></div>
                    <p class="tw-font-semibold tw-mb-0 tw-p-1.5 tw-text-neutral-700">
                        <i class="fa fa-bar-chart tw-mr-1"></i> Os meus números
                    </p>
                    <hr class="-tw-mx-3 tw-mt-3 tw-mb-4">
                    <p class="text-muted bold mbot5">Propostas enviadas — 30 dias</p>
                    <div style="height:300px"><canvas id="dps_g_propostas"></canvas></div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">Leads por estado</p>
                    <div style="height:<?php echo max(300, 40 + count($leads_labels) * 32); ?>px"><canvas id="dps_g_leads"></canvas></div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">Tarefas por estado</p>
                    <div style="height:<?php echo max(240, 40 + count($tar_labels) * 32); ?>px"><canvas id="dps_g_tarefas"></canvas></div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">Interações — última semana</p>
                    <div style="height:300px"><canvas id="dps_g_interacoes"></canvas></div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">Vendas no mês</p>
                    <div style="height:300px"><canvas id="dps_g_vendas"></canvas></div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">DPS Crédito no mês</p>
                    <div style="height:300px"><canvas id="dps_g_credito"></canvas></div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">Crédito ganho no mês</p>
                    <div class="text-center" style="padding:20px 0;">
                        <h1 class="no-margin text-success bold"><?php echo $cred_ganhas; ?></h1>
                        <p class="text-muted">proposta<?php echo $cred_ganhas === 1 ? '' : 's'; ?> com sucesso</p>
                        <h3 class="text-success bold">
                            <?php echo app_format_money($cred_valor, get_base_currency()); ?>
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php /* O <script> TEM de ficar dentro da div raiz: o renderizador de
   widgets (render_dashboard_widgets) só aproveita o primeiro elemento
   HTML do ficheiro e descarta o resto. */ ?>
<script>
(function () {
    var D = <?php echo json_encode($dps_charts); ?>;

    // O Chart.js não vem nos scripts do admin (só no portal de clientes) —
    // carregamo-lo aqui uma única vez.
    function ensureChart(cb) {
        if (typeof Chart !== 'undefined') { return cb(); }
        if (window._dpsChartLoading) {
            return setTimeout(function () { ensureChart(cb); }, 200);
        }
        window._dpsChartLoading = true;
        var s = document.createElement('script');
        s.src = '<?php echo base_url('assets/plugins/Chart.js/Chart.min.js'); ?>';
        s.onload = cb;
        document.head.appendChild(s);
    }

    function init() {
        if (typeof Chart === 'undefined') { return ensureChart(init); }

        // Eixo numérico só com inteiros (0.1 vendas não existe)
        var soInteiros = {
            beginAtZero: true,
            callback: function (v) { return Math.floor(v) === v ? v : undefined; }
        };

        function mk(id, tipo, labels, data, cores) {
            var el = document.getElementById(id);
            if (!el) { return; }

            var scales;
            if (tipo === 'horizontalBar') {
                scales = { xAxes: [{ ticks: soInteiros }], yAxes: [{ gridLines: { display: false } }] };
            } else {
                scales = { yAxes: [{ ticks: soInteiros }] };
            }

            new Chart(el, {
                type: tipo,
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: cores || 'rgba(50,118,177,0.65)',
                        borderColor: tipo === 'line' ? 'rgba(50,118,177,1)' : (cores || 'rgba(50,118,177,1)'),
                        borderWidth: 1,
                        fill: tipo === 'line'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    scales: scales
                }
            });
        }
        mk('dps_g_propostas', 'line', D.propostas.labels, D.propostas.data);
        mk('dps_g_leads', 'horizontalBar', D.leads.labels, D.leads.data, D.leads.cores);
        mk('dps_g_tarefas', 'horizontalBar', D.tarefas.labels, D.tarefas.data, D.tarefas.cores);
        mk('dps_g_interacoes', 'bar', D.interacoes.labels, D.interacoes.data);
        mk('dps_g_vendas', 'bar', D.vendas.labels, D.vendas.data, 'rgba(132,197,41,0.7)');
        mk('dps_g_credito', 'bar', D.credito.labels, D.credito.data, 'rgba(197,165,90,0.8)');
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
</div>
