<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
.dps-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:18px;}
.dps-kpi{background:#fff;border:1px solid #eaecef;border-radius:10px;padding:14px 16px;}
.dps-kpi .v{font-size:26px;font-weight:600;line-height:1.1;color:#2b3440;}
.dps-kpi .l{font-size:12px;color:#7a8798;margin-top:4px;text-transform:uppercase;letter-spacing:.03em;}
.dps-kpi.accent .v{color:#1d6fb8;} .dps-kpi.green .v{color:#2f9e44;} .dps-kpi.red .v{color:#c0392b;} .dps-kpi.amber .v{color:#b58105;}
.dps-card{background:#fff;border:1px solid #eaecef;border-radius:10px;padding:14px 16px;margin-bottom:18px;}
.dps-card h4{margin:0 0 12px;font-weight:600;font-size:15px;}
</style>
<div id="wrapper">
    <div class="content">
        <div class="row"><div class="col-md-12">

            <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px;">
                <h4 class="no-margin"><i class="fa fa-line-chart"></i> Visão Geral<?= $comercial > 0 ? ' · ' . e(get_staff_full_name($comercial)) : ''; ?></h4>
                <?php if ($can_view_all) { ?>
                <form method="get" action="<?= admin_url('dps_propostas/visao'); ?>" style="margin-left:auto;display:flex;align-items:center;gap:8px;">
                    <label style="margin:0;font-size:13px;color:#5a6673;"><i class="fa fa-user-o"></i> Comercial:</label>
                    <select name="comercial" class="selectpicker" data-width="240px" data-live-search="true" onchange="this.form.submit()">
                        <option value="0"<?= $comercial == 0 ? ' selected' : ''; ?>>Todos</option>
                        <?php foreach ($comerciais as $c) { $cid = (int) $c['staffid']; ?>
                        <option value="<?= $cid; ?>"<?= $comercial == $cid ? ' selected' : ''; ?>><?= e(trim($c['firstname'] . ' ' . $c['lastname'])); ?> (<?= (int) $c['c']; ?>)</option>
                        <?php } ?>
                    </select>
                    <?php if ($comercial > 0) { ?><a href="<?= admin_url('dps_propostas/visao'); ?>" class="btn btn-default btn-sm">Limpar</a><?php } ?>
                </form>
                <?php } ?>
                <button type="button" class="btn btn-default btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Imprimir / PDF</button>
            </div>

            <div class="dps-kpis">
                <div class="dps-kpi accent"><div class="v"><?= number_format($kpi['total'], 0, ',', '.'); ?></div><div class="l">Total de leads</div></div>
                <div class="dps-kpi"><div class="v"><?= (int) $kpi['novas_hoje']; ?></div><div class="l">Novas hoje</div></div>
                <div class="dps-kpi"><div class="v"><?= (int) $kpi['novas_7']; ?></div><div class="l">Novas (7 dias)</div></div>
                <div class="dps-kpi"><div class="v"><?= number_format($kpi['interacoes'], 0, ',', '.'); ?></div><div class="l">Interações</div></div>
                <div class="dps-kpi green"><div class="v"><?= number_format($kpi['concret'], 0, ',', '.'); ?></div><div class="l">Concretizados</div></div>
                <div class="dps-kpi amber"><div class="v"><?= $kpi['taxa']; ?>%</div><div class="l">Taxa de conversão</div></div>
                <div class="dps-kpi red"><div class="v"><?= number_format($kpi['perd'], 0, ',', '.'); ?></div><div class="l">Perdidos</div></div>
                <div class="dps-kpi"><div class="v"><?= number_format($kpi['propostas'], 0, ',', '.'); ?></div><div class="l">Propostas enviadas</div></div>
            </div>

            <?php
            $estados_chart = array_values(array_filter($por_estado, function ($e) { return mb_strtolower(trim($e['name'])) !== 'novos'; }));
            usort($estados_chart, function ($a, $b) { return (int) $b['n'] <=> (int) $a['n']; });
            $novos_count = 0;
            foreach ($por_estado as $e) { if (mb_strtolower(trim($e['name'])) === 'novos') { $novos_count = (int) $e['n']; } }
            $est_h = max(240, count($estados_chart) * 30 + 30);
            ?>
            <div class="row">
                <div class="col-md-12"><div class="dps-card"><h4>Novas leads por dia (30 dias)</h4><div style="height:200px;"><canvas id="ch_dia"></canvas></div></div></div>
            </div>
            <div class="row">
                <div class="col-md-12"><div class="dps-card">
                    <h4>Leads por estado <small class="text-muted" style="font-weight:400;">— "Novos" (topo do funil): <?= number_format($novos_count, 0, ',', '.'); ?></small></h4>
                    <div style="height:<?= (int) $est_h; ?>px;"><canvas id="ch_estado"></canvas></div>
                </div></div>
            </div>

            <?php if ($can_view_all && ! empty($por_comercial)) { ?>
            <div class="row">
                <div class="col-md-6"><div class="dps-card"><h4>Leads por comercial</h4><canvas id="ch_com" height="200"></canvas></div></div>
                <div class="col-md-6"><div class="dps-card"><h4>Eficácia por comercial</h4>
                    <div class="table-responsive"><table class="table table-striped" style="font-size:13px;">
                        <thead><tr><th>Comercial</th><th class="text-right">Leads</th><th class="text-right">Concret.</th><th class="text-right">Perdidos</th><th class="text-right">Taxa</th></tr></thead>
                        <tbody>
                        <?php foreach ($por_comercial as $c) {
                            $t = (int) $c['total']; $cc = (int) $c['concret']; $tx = $t > 0 ? round($cc / $t * 100, 1) : 0; ?>
                            <tr>
                                <td><?= e($c['nome']); ?></td>
                                <td class="text-right"><?= number_format($t, 0, ',', '.'); ?></td>
                                <td class="text-right text-success"><?= (int) $cc; ?></td>
                                <td class="text-right text-danger"><?= (int) $c['perd']; ?></td>
                                <td class="text-right"><strong><?= $tx; ?>%</strong></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table></div>
                </div></div>
            </div>
            <?php } ?>

        </div></div>
    </div>
</div>
<script src="<?= base_url('assets/plugins/Chart.js/Chart.min.js'); ?>"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') { return; }
    Chart.defaults.global && (Chart.defaults.global.defaultFontColor = '#5a6673');

    var dia = <?= json_encode($por_dia); ?>;
    new Chart(document.getElementById('ch_dia').getContext('2d'), {
        type: 'line',
        data: {
            labels: dia.map(function (d) { return d.dia.substring(5); }),
            datasets: [{ label: 'Novas leads', data: dia.map(function (d) { return d.n; }), borderColor: '#1d6fb8', backgroundColor: 'rgba(29,111,184,.12)', fill: true, tension: 0.3, pointRadius: 2 }]
        },
        options: { legend: { display: false }, maintainAspectRatio: false, scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] } }
    });

    var est = <?= json_encode($estados_chart); ?>;
    new Chart(document.getElementById('ch_estado').getContext('2d'), {
        type: 'horizontalBar',
        data: {
            labels: est.map(function (e) { return e.name; }),
            datasets: [{ data: est.map(function (e) { return parseInt(e.n, 10); }), backgroundColor: est.map(function (e) { return e.color || '#7a8798'; }) }]
        },
        options: {
            legend: { display: false },
            maintainAspectRatio: false,
            scales: {
                xAxes: [{ ticks: { beginAtZero: true, precision: 0 } }],
                yAxes: [{ ticks: { autoSkip: false, fontSize: 11 } }]
            }
        }
    });

    <?php if ($can_view_all && ! empty($por_comercial)) { ?>
    var com = <?= json_encode($por_comercial); ?>;
    new Chart(document.getElementById('ch_com').getContext('2d'), {
        type: 'bar',
        data: {
            labels: com.map(function (c) { return c.nome; }),
            datasets: [
                { label: 'Leads', data: com.map(function (c) { return parseInt(c.total, 10); }), backgroundColor: '#1d6fb8' },
                { label: 'Concretizados', data: com.map(function (c) { return parseInt(c.concret, 10); }), backgroundColor: '#2f9e44' }
            ]
        },
        options: { scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }, maintainAspectRatio: true }
    });
    <?php } ?>
})();
</script>

<?php
/* ---------------------------------------------------------------------
 * DPS Crédito — resumo por comercial
 * Usa o mesmo helper do ecrã "Análise Comercial" para os números nunca
 * divergirem entre os dois sítios.
 * ------------------------------------------------------------------ */
if (function_exists('dps_credito_analise_dados')) {
    $cr_de  = date('Y-m-01', strtotime('-2 months'));
    $cr_ate = date('Y-m-d');
    $cr     = dps_credito_analise_dados($cr_de, $cr_ate, isset($comercial) ? (int) $comercial : 0);

    $cr_t = ['leads' => 0, 'sim' => 0, 'ind' => 0, 'props' => 0, 'mont' => 0];
    foreach ($cr as $l) {
        $cr_t['leads'] += $l['leads_total'];
        $cr_t['sim']   += $l['sim'];
        $cr_t['ind']   += $l['indefinido'];
        $cr_t['props'] += $l['propostas'];
        $cr_t['mont']  += (float) $l['montante_total'];
    }
    $cr_pct = function ($a, $b) { return $b > 0 ? round($a / $b * 100, 1) : 0; };
?>
<div class="row">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">
                <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
                    <h4 class="no-margin"><i class="fa fa-university"></i> DPS Crédito</h4>
                    <span class="text-muted" style="font-size:.82rem;">
                        desde <?php echo _d($cr_de); ?> · percentagens sobre leads atribuídas
                    </span>
                    <a href="<?php echo admin_url('dps_credito/analise'); ?>" class="btn btn-default btn-sm" style="margin-left:auto;">
                        Análise completa
                    </a>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
                    <?php
                    $cr_kpis = [
                        [number_format($cr_t['leads'], 0, ',', '.'), 'Leads atribuídas', '#2b3440'],
                        [$cr_pct($cr_t['sim'], $cr_t['leads']) . '%', 'Crédito abordado', '#2f7d55'],
                        [number_format($cr_t['sim'], 0, ',', '.'), 'Respostas "Sim"', '#2b3440'],
                        [number_format($cr_t['ind'], 0, ',', '.'), 'Por responder', '#b07d19'],
                        [$cr_pct($cr_t['props'], $cr_t['sim']) . '%', 'Sim → proposta', '#a8873c'],
                        [app_format_money($cr_t['mont'], get_base_currency()), 'Montante crédito', '#a8873c'],
                    ];
                    foreach ($cr_kpis as $k) { ?>
                        <div style="flex:1;min-width:140px;background:#fff;border:1px solid #e6eaef;border-radius:10px;padding:12px 14px;">
                            <div style="font-size:1.6rem;font-weight:700;line-height:1.1;color:<?php echo $k[2]; ?>;font-variant-numeric:tabular-nums;"><?php echo $k[0]; ?></div>
                            <div style="font-size:.7rem;letter-spacing:.06em;text-transform:uppercase;color:#8a97a6;margin-top:3px;"><?php echo $k[1]; ?></div>
                        </div>
                    <?php } ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Comercial</th>
                                <th class="text-center">Leads</th>
                                <th class="text-center">Sim</th>
                                <th class="text-center">Por responder</th>
                                <th>% Abordagem</th>
                                <th class="text-center">Propostas</th>
                                <th>% Sim → proposta</th>
                                <th class="text-right">Montante</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cr)) { ?>
                                <tr><td colspan="8" class="text-center text-muted">Sem dados.</td></tr>
                            <?php } ?>
                            <?php foreach ($cr as $l) { ?>
                                <tr>
                                    <td style="font-weight:600;"><?php echo html_escape($l['comercial']); ?></td>
                                    <td class="text-center"><?php echo (int) $l['leads_total']; ?></td>
                                    <td class="text-center"><strong style="color:#2f7d55;"><?php echo (int) $l['sim']; ?></strong></td>
                                    <td class="text-center">
                                        <?php if ((int) $l['indefinido'] > 0) { ?>
                                            <span class="label label-warning"><?php echo (int) $l['indefinido']; ?></span>
                                        <?php } else { ?><span class="text-muted">0</span><?php } ?>
                                    </td>
                                    <td>
                                        <strong><?php echo $l['pct_abordagem']; ?>%</strong>
                                        <div style="height:7px;border-radius:5px;background:#eef1f5;overflow:hidden;min-width:60px;margin-top:3px;">
                                            <span style="display:block;height:100%;border-radius:5px;background:linear-gradient(90deg,#a8873c,#c5a55a);width:<?php echo min(100, $l['pct_abordagem']); ?>%;"></span>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo (int) $l['propostas']; ?></td>
                                    <td><strong><?php echo $l['pct_proposta']; ?>%</strong></td>
                                    <td class="text-right"><?php echo app_format_money($l['montante_total'], get_base_currency()); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php init_tail(); ?>
