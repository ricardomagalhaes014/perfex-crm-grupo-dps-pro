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

            <div class="row">
                <div class="col-md-7"><div class="dps-card"><h4>Novas leads por dia (30 dias)</h4><canvas id="ch_dia" height="120"></canvas></div></div>
                <div class="col-md-5"><div class="dps-card"><h4>Leads por estado</h4><canvas id="ch_estado" height="180"></canvas></div></div>
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
        options: { legend: { display: false }, scales: { yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }, maintainAspectRatio: true }
    });

    var est = <?= json_encode($por_estado); ?>;
    new Chart(document.getElementById('ch_estado').getContext('2d'), {
        type: 'horizontalBar',
        data: {
            labels: est.map(function (e) { return e.name; }),
            datasets: [{ data: est.map(function (e) { return parseInt(e.n, 10); }), backgroundColor: est.map(function (e) { return e.color || '#7a8798'; }) }]
        },
        options: { legend: { display: false }, scales: { xAxes: [{ ticks: { beginAtZero: true, precision: 0 } }] }, maintainAspectRatio: true }
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
<?php init_tail(); ?>
