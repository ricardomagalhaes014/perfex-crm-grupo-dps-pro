<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Widget DPS — a equipa toda, por comercial. SÓ ADMINISTRADORES.
 *
 * O widget "Os meus números" responde a "como vou eu". Este responde a "como
 * vai a equipa", que é outra pergunta e pede outro corte: cada comercial numa
 * barra, repartida pelos estados.
 *
 * Quatro quadros, um por matéria: leads, propostas, vendas do mês e tarefas.
 * São contas diferentes com nomes parecidos — juntá-las numa só barra dava um
 * número que não queria dizer nada.
 *
 * Uma consulta por quadro, agrupada na base de dados. Trazer as linhas todas
 * para PHP e contar aqui seria ler dezenas de milhares de registos para
 * mostrar quatro gráficos — e este CRM já foi abaixo uma vez por excesso de
 * base de dados.
 */

$CI = &get_instance();

if (!is_admin()) {
    return;
}

$p = db_prefix();

/* ---------------------------------------------------------------- LEADS */
$eq_leads = [];   // comercial => [estado => n]
$eq_leads_estados = [];
$eq_leads_cores   = [];

foreach ($CI->db->query(
    "SELECT CONCAT(s.firstname,' ',s.lastname) AS com, ls.name AS estado, ls.color,
            ls.statusorder, COUNT(*) AS n
     FROM {$p}leads l
     JOIN {$p}staff s ON s.staffid = l.assigned
     LEFT JOIN {$p}leads_status ls ON ls.id = l.status
     WHERE l.assigned > 0
     GROUP BY s.staffid, ls.id
     ORDER BY ls.statusorder"
)->result_array() as $r) {
    $com = trim((string) $r['com']) ?: 'Sem comercial';
    $est = (string) ($r['estado'] ?: 'Sem estado');

    $eq_leads[$com][$est] = (int) $r['n'];
    $eq_leads_estados[$est] = true;
    $eq_leads_cores[$est]   = $r['color'] ?: '#8a8f94';
}

/* ------------------------------------------------------------ PROPOSTAS */
$eq_prop = [];

foreach ($CI->db->query(
    "SELECT CONCAT(s.firstname,' ',s.lastname) AS com,
            COALESCE(NULLIF(pr.outcome, ''), 'pendente') AS resultado, COUNT(*) AS n
     FROM {$p}dps_propostas pr
     LEFT JOIN {$p}staff s ON s.staffid = pr.staff_id
     WHERE pr.tipo = 'proposta'
     GROUP BY pr.staff_id, COALESCE(NULLIF(pr.outcome, ''), 'pendente')"
)->result_array() as $r) {
    $com = trim((string) $r['com']) ?: 'Sem comercial';
    $eq_prop[$com][$r['resultado']] = (int) $r['n'];
}

/* -------------------------------------------------------------- TAREFAS */
$eq_tar = [];
$eq_tar_estados = [];
$eq_tar_cores   = [];

$CI->load->model('tasks_model');
$nomes_tarefa = [];
foreach ($CI->tasks_model->get_statuses() as $e) {
    $nomes_tarefa[(int) $e['id']] = $e['name'];
    $eq_tar_estados[$e['name']]   = true;
    $eq_tar_cores[$e['name']]     = $e['color'];
}

foreach ($CI->db->query(
    "SELECT CONCAT(s.firstname,' ',s.lastname) AS com, t.status, COUNT(DISTINCT t.id) AS n
     FROM {$p}tasks t
     JOIN {$p}task_assigned ta ON ta.taskid = t.id
     JOIN {$p}staff s ON s.staffid = ta.staffid
     GROUP BY ta.staffid, t.status"
)->result_array() as $r) {
    $com = trim((string) $r['com']) ?: 'Sem comercial';
    $est = $nomes_tarefa[(int) $r['status']] ?? ('Estado ' . $r['status']);
    $eq_tar[$com][$est] = (int) $r['n'];
}

/* --------------------------------------------------------- VENDAS DO MÊS */
$eq_vend = [];
$eq_vend_estados = [];

$nomes_venda = [
    'pedido' => 'Pedido', 'reservado' => 'Reservado', 'submetido' => 'Submetido',
    'vendido' => 'CPCV', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado',
    'pendente' => 'Pendente',
];
$cores_venda = [
    'Pedido' => '#9aa3ab', 'Reservado' => '#e08a1e', 'Submetido' => '#0f8b8d',
    'CPCV' => '#1d6fb8', 'Concluído' => '#2f9e44', 'Cancelado' => '#c0392b',
    'Pendente' => '#8a8f94',
];

if ($CI->db->table_exists($p . 'simulador_vendas')) {
    /*
     * Só o mês corrente, e pela data da VENDA — não pela de criação do
     * registo. Uma venda lançada hoje mas fechada no mês passado pertence ao
     * mês passado; contá-la aqui inflacionava o mês em curso.
     */
    foreach ($CI->db->query(
        "SELECT CONCAT(s.firstname,' ',s.lastname) AS com,
                COALESCE(NULLIF(v.estado,''),'pendente') AS estado,
                COUNT(*) AS n, SUM(v.valor) AS valor
         FROM {$p}simulador_vendas v
         LEFT JOIN {$p}staff s ON s.staffid = v.staff_id
         WHERE v.data_venda >= ? AND v.data_venda < ?
         GROUP BY v.staff_id, estado",
        [date('Y-m-01'), date('Y-m-01', strtotime('+1 month'))]
    )->result_array() as $r) {
        $com = trim((string) $r['com']) ?: 'Sem comercial';
        $est = $nomes_venda[$r['estado']] ?? ucfirst((string) $r['estado']);

        $eq_vend[$com][$est] = (int) $r['n'];
        $eq_vend_estados[$est] = true;
    }
}

/**
 * Ordena os comerciais por total, do maior para o menor, e devolve o que o
 * Chart.js precisa. Quem tem mais fica em cima — é o que se procura primeiro.
 */
$eq_series = function (array $dados, array $estados) {
    uasort($dados, function ($a, $b) { return array_sum($b) <=> array_sum($a); });

    $labels = array_keys($dados);
    $series = [];

    foreach (array_keys($estados) as $est) {
        $linha = [];
        foreach ($labels as $com) {
            $linha[] = (int) ($dados[$com][$est] ?? 0);
        }
        $series[$est] = $linha;
    }

    return ['labels' => $labels, 'series' => $series];
};

$g_leads = $eq_series($eq_leads, $eq_leads_estados);
$g_tar   = $eq_series($eq_tar, $eq_tar_estados);
$g_prop  = $eq_series($eq_prop, ['aceite' => true, 'recusado' => true, 'pendente' => true]);
$g_vend  = $eq_series($eq_vend, $eq_vend_estados);

$eq_dados = [
    'leads'   => ['g' => $g_leads, 'cores' => $eq_leads_cores],
    'tarefas' => ['g' => $g_tar,   'cores' => $eq_tar_cores],
    'propostas' => ['g' => $g_prop, 'cores' => [
        'aceite' => '#2f9e44', 'recusado' => '#c0392b', 'pendente' => '#9aa3ab',
    ]],
    'vendas' => ['g' => $g_vend, 'cores' => $cores_venda],
];
?>
<div class="widget" id="widget-<?php echo create_widget_id(); ?>" data-name="DPS — A equipa">
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body padding-10">
                    <div class="widget-dragger"></div>
                    <p class="tw-font-semibold tw-mb-0 tw-p-1.5 tw-text-neutral-700">
                        <i class="fa fa-users tw-mr-1"></i> A equipa — por comercial
                    </p>
                    <hr class="-tw-mx-3 tw-mt-3 tw-mb-4">

                    <p class="text-muted bold mbot5">Leads por estado</p>
                    <div style="height:<?php echo max(240, 40 + count($g_leads['labels']) * 34); ?>px">
                        <canvas id="dps_eq_leads"></canvas>
                    </div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">Propostas por resultado</p>
                    <div style="height:<?php echo max(200, 40 + count($g_prop['labels']) * 34); ?>px">
                        <canvas id="dps_eq_propostas"></canvas>
                    </div>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">
                        Vendas do mês <small class="text-muted">— <?php
                            // strftime está obsoleto no PHP 8.1 e desaparece na 9.
                            $meses_pt = [1=>'Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                                         'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                            echo $meses_pt[(int) date('n')] . ' de ' . date('Y');
                        ?></small>
                    </p>
                    <?php if (empty($g_vend['labels'])) { ?>
                        <p class="text-muted">Ainda não há vendas com data neste mês.</p>
                    <?php } else { ?>
                        <div style="height:<?php echo max(200, 40 + count($g_vend['labels']) * 34); ?>px">
                            <canvas id="dps_eq_vendas"></canvas>
                        </div>
                    <?php } ?>
                    <hr class="tw-my-4">

                    <p class="text-muted bold mbot5">Tarefas por estado</p>
                    <div style="height:<?php echo max(240, 40 + count($g_tar['labels']) * 34); ?>px">
                        <canvas id="dps_eq_tarefas"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php /* O <script> fica DENTRO da div raiz: o renderizador de widgets só
   aproveita o primeiro elemento HTML do ficheiro e descarta o resto. */ ?>
<script>
(function () {
    var D = <?php echo json_encode($eq_dados, JSON_UNESCAPED_UNICODE); ?>;

    function esperar(cb) {
        if (typeof Chart !== 'undefined') { return cb(); }
        setTimeout(function () { esperar(cb); }, 200);
    }

    function desenhar(id, bloco) {
        var el = document.getElementById(id);
        if (!el || !bloco.g.labels.length) { return; }

        var datasets = Object.keys(bloco.g.series).map(function (estado) {
            return {
                label: estado,
                data: bloco.g.series[estado],
                backgroundColor: bloco.cores[estado] || '#8a8f94'
            };
        });

        new Chart(el, {
            type: 'horizontalBar',
            data: { labels: bloco.g.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom', labels: { boxWidth: 12, fontSize: 11 } },
                // Empilhado: a barra inteira é o total do comercial e cada
                // fatia um estado. Lado a lado, com dez estados, não se lia
                // nada.
                scales: {
                    xAxes: [{ stacked: true, ticks: { beginAtZero: true,
                        callback: function (v) { return Math.floor(v) === v ? v : undefined; } } }],
                    yAxes: [{ stacked: true, gridLines: { display: false } }]
                }
            }
        });
    }

    esperar(function () {
        desenhar('dps_eq_leads', D.leads);
        desenhar('dps_eq_propostas', D.propostas);
        desenhar('dps_eq_vendas', D.vendas);
        desenhar('dps_eq_tarefas', D.tarefas);
    });
})();
</script>
</div>
