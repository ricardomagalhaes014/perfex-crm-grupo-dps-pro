<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widget DPS — gráficos pessoais no dashboard.
 * Cada utilizador vê apenas os SEUS números:
 *   1. Propostas enviadas (últimos 30 dias)
 *   2. Leads por estado
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
                    <div class="row">
                        <div class="col-md-4">
                            <p class="text-muted bold mbot5">Propostas enviadas — 30 dias</p>
                            <div style="height:200px"><canvas id="dps_g_propostas"></canvas></div>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted bold mbot5">Leads por estado</p>
                            <div style="height:200px"><canvas id="dps_g_leads"></canvas></div>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted bold mbot5">Interações — última semana</p>
                            <div style="height:200px"><canvas id="dps_g_interacoes"></canvas></div>
                        </div>
                    </div>
                    <hr class="tw-my-4">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="text-muted bold mbot5">Vendas no mês</p>
                            <div style="height:200px"><canvas id="dps_g_vendas"></canvas></div>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted bold mbot5">DPS Crédito no mês</p>
                            <div style="height:200px"><canvas id="dps_g_credito"></canvas></div>
                        </div>
                        <div class="col-md-4">
                            <p class="text-muted bold mbot5">Crédito ganho no mês</p>
                            <div class="text-center" style="padding-top:30px;">
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
        function mk(id, tipo, labels, data, cores) {
            var el = document.getElementById(id);
            if (!el) { return; }
            new Chart(el, {
                type: tipo,
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: cores || 'rgba(50,118,177,0.65)',
                        borderColor: cores || 'rgba(50,118,177,1)',
                        borderWidth: 1,
                        fill: tipo === 'line'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: tipo === 'doughnut', position: 'bottom' },
                    scales: tipo === 'doughnut'
                        ? {}
                        : { yAxes: [{ ticks: { beginAtZero: true } }] }
                }
            });
        }
        mk('dps_g_propostas', 'line', D.propostas.labels, D.propostas.data);
        mk('dps_g_leads', 'doughnut', D.leads.labels, D.leads.data, D.leads.cores);
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
