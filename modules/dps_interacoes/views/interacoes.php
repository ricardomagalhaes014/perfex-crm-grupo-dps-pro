<?php init_head(); ?>
<style>
.ic-filters { background:#fff; border-radius:8px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
.ic-filters form { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; }
.ic-filters .form-group { margin:0; }
.ic-filters label { font-size:12px; font-weight:600; color:#555; display:block; margin-bottom:4px; }
.ic-filters select { height:36px; border-radius:4px; border:1px solid #ddd; padding:0 10px; font-size:13px; }
.ic-filters .btn-filter { height:36px; padding:0 18px; background:#f97316; color:#fff; border:none; border-radius:4px; font-size:13px; font-weight:600; cursor:pointer; }
.ic-filters .btn-filter:hover { background:#ea6c0a; }
.ic-period-label { font-size:13px; color:#888; margin-bottom:12px; }
.ic-obj-info { font-size:12px; color:#666; margin-bottom:16px; background:#fff8f0; border-left:3px solid #f97316; padding:8px 14px; border-radius:0 6px 6px 0; display:inline-block; }
.ic-table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); }
.ic-table th { background:#f5f5f5; padding:12px 16px; text-align:left; font-size:13px; font-weight:700; color:#333; border-bottom:2px solid #e8e8e8; }
.ic-table td { padding:12px 16px; font-size:13px; color:#444; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.ic-table tr:last-child td { border-bottom:none; }
.ic-table tr:hover td { background:#fafafa; }
/* Badge de interacções */
.ic-badge { display:inline-block; background:#f97316; color:#fff; border-radius:20px; padding:3px 12px; font-size:13px; font-weight:700; min-width:36px; text-align:center; }
.ic-badge-zero { background:#e0e0e0; color:#999; }
/* Célula de progresso */
.ic-progress-cell { display:flex; align-items:center; gap:10px; min-width:220px; }
.ic-progress-wrap { flex:1; background:#f0f0f0; border-radius:20px; height:10px; overflow:hidden; min-width:80px; }
.ic-progress-bar { height:10px; border-radius:20px; transition:width .3s; }
.ic-progress-bar.green  { background:#22c55e; }
.ic-progress-bar.orange { background:#f97316; }
.ic-progress-bar.red    { background:#ef4444; }
.ic-pct-label { font-size:12px; font-weight:700; white-space:nowrap; min-width:48px; }
.ic-pct-label.green  { color:#16a34a; }
.ic-pct-label.orange { color:#ea6c0a; }
.ic-pct-label.red    { color:#dc2626; }
.ic-obj-label { font-size:11px; color:#aaa; white-space:nowrap; }
/* Botões */
.ic-btn-detail { background:#1976d2; color:#fff; border:none; border-radius:4px; padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer; }
.ic-btn-detail:hover { background:#1565c0; }
.ic-btn-close { background:#757575; color:#fff; border:none; border-radius:4px; padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer; }
/* Detalhe */
.ic-detail-row { display:none; }
.ic-detail-row td { padding:0 !important; }
.ic-detail-inner { padding:12px 24px 16px 24px; background:#f9f9f9; border-top:1px solid #eee; }
.ic-detail-title { font-size:12px; font-weight:700; color:#666; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px; }
.ic-detail-table { width:100%; border-collapse:collapse; font-size:12px; }
.ic-detail-table th { background:#ececec; padding:6px 10px; text-align:left; font-weight:700; color:#555; }
.ic-detail-table td { padding:6px 10px; border-bottom:1px solid #eee; color:#444; }
.ic-detail-table tr:last-child td { border-bottom:none; }
.ic-empty { color:#aaa; font-style:italic; font-size:12px; padding:8px 0; }
</style>

<div id="wrapper">
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-md-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Interacções por Comercial</h4>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="ic-filters">
                    <form method="GET" action="<?php echo admin_url('dps_interacoes'); ?>">
                        <div class="form-group">
                            <label>Comercial</label>
                            <select name="comercial">
                                <option value="0">Todos os comerciais</option>
                                <?php foreach (($equipa ?? []) as $m): ?>
                                <option value="<?php echo (int) $m['staffid']; ?>"
                                    <?php echo (int) ($comercial_id ?? 0) === (int) $m['staffid'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Período</label>
                            <select name="periodo">
                                <option value="today" <?php echo $periodo=='today'?'selected':''; ?>>Hoje</option>
                                <option value="today_yesterday" <?php echo $periodo=='today_yesterday'?'selected':''; ?>>Hoje e Ontem</option>
                                <option value="last_7" <?php echo $periodo=='last_7'?'selected':''; ?>>Últimos 7 dias</option>
                                <option value="last_15" <?php echo $periodo=='last_15'?'selected':''; ?>>Últimos 15 dias</option>
                                <option value="last_30" <?php echo $periodo=='last_30'?'selected':''; ?>>Últimos 30 dias</option>
                                <option value="last_3m" <?php echo $periodo=='last_3m'?'selected':''; ?>>Últimos 3 meses</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status do Lead</label>
                            <select name="status_id">
                                <option value="0">Todos os Status</option>
                                <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo $s['id']; ?>" <?php echo $status_id==$s['id']?'selected':''; ?>>
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="ic-btn-filter btn-filter">Filtrar</button>
                            <?php if ((int) ($comercial_id ?? 0) > 0 || $periodo !== ($periodo_omissao ?? 'today_yesterday') || $status_id > 0): ?>
                                <a href="<?php echo admin_url('dps_interacoes'); ?>"
                                   style="margin-left:8px;font-size:13px;color:#888;">Limpar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Gráfico -->
                <?php if (!empty($g_etiquetas)): ?>
                <div style="background:#fff;border-radius:8px;padding:18px 20px 8px;margin-bottom:20px;
                            box-shadow:0 1px 4px rgba(0,0,0,.08);">
                    <div style="font-size:13px;font-weight:700;color:#333;margin-bottom:4px;">
                        <?php echo (int) ($comercial_id ?? 0) > 0
                            ? 'Interacções dia a dia'
                            : 'Interacções por comercial'; ?>
                    </div>
                    <div style="font-size:12px;color:#888;margin-bottom:12px;">
                        <?php echo (int) ($comercial_id ?? 0) > 0
                            ? 'Os dias sem nenhuma interacção aparecem a zero — são precisamente os que interessa ver.'
                            : 'Cada mensagem diferente conta uma vez por dia — a mesma frase colada em vinte leads conta uma.'; ?>
                    </div>
                    <div style="height:<?php echo (int) ($comercial_id ?? 0) > 0 ? 220 : max(180, count($g_etiquetas) * 26); ?>px;">
                        <canvas id="ic-grafico"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Período activo + objectivo -->
                <p class="ic-period-label">
                    <strong><?php echo htmlspecialchars($label); ?></strong>
                    &nbsp;|&nbsp; <?php echo date('d/m/Y', strtotime($date_from)); ?> a <?php echo date('d/m/Y', strtotime($date_to)); ?>
                </p>
                <div class="ic-obj-info">
                    🎯 Objectivo para este período: <strong><?php echo number_format($objectivo, 1, ',', '.'); ?> interacções</strong>
                    &nbsp;·&nbsp; Base: 200/semana · 800/mês
                </div>

                <!-- Tabela -->
                <table class="ic-table" style="margin-top:16px;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Comercial</th>
                            <th>Interacções</th>
                            <th>Objectivo / Concretizado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comerciais)): ?>
                        <tr><td colspan="5" style="text-align:center;color:#aaa;padding:30px;">Sem dados para o período seleccionado.</td></tr>
                        <?php else: ?>
                        <?php $i = 1; foreach ($comerciais as $c):
                            $pct      = $c['pct'];
                            $pct_disp = min($pct, 100); // barra máx 100%
                            if ($pct >= 100)      { $color = 'green'; }
                            elseif ($pct >= 50)   { $color = 'orange'; }
                            else                  { $color = 'red'; }
                        ?>
                        <tr id="row-<?php echo $c['staff_id']; ?>">
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                            <td>
                                <span class="ic-badge <?php echo $c['total_interacoes']==0?'ic-badge-zero':''; ?>">
                                    <?php echo $c['total_interacoes']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="ic-progress-cell">
                                    <div class="ic-progress-wrap">
                                        <div class="ic-progress-bar <?php echo $color; ?>"
                                             style="width:<?php echo $pct_disp; ?>%"></div>
                                    </div>
                                    <span class="ic-pct-label <?php echo $color; ?>">
                                        <?php echo number_format($pct, 1, ',', '.'); ?>%
                                    </span>
                                    <span class="ic-obj-label">/ <?php echo number_format($c['objectivo'], 1, ',', '.'); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($c['leads'])): ?>
                                <button class="ic-btn-detail"
                                    onclick="(function(btn,sid){
                                        var row = document.getElementById('detail-'+sid);
                                        if(row.style.display==='table-row'){
                                            row.style.display='none';
                                            btn.textContent='Detalhe';
                                            btn.className='ic-btn-detail';
                                        } else {
                                            row.style.display='table-row';
                                            btn.textContent='\u2715 Fechar';
                                            btn.className='ic-btn-close';
                                        }
                                    })(this, <?php echo $c['staff_id']; ?>)">Detalhe</button>
                                <?php else: ?>
                                <span style="color:#ccc;font-size:12px;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (!empty($c['leads'])): ?>
                        <tr id="detail-<?php echo $c['staff_id']; ?>" class="ic-detail-row">
                            <td colspan="5">
                                <div class="ic-detail-inner">
                                    <div class="ic-detail-title">Leads com interacção — <?php echo htmlspecialchars($c['nome']); ?></div>
                                    <table class="ic-detail-table">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>Telefone</th>
                                                <th>Status</th>
                                                <th>Interacções</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($c['leads'] as $lead): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo admin_url('leads/index/'.$lead['id']); ?>" target="_blank">
                                                        <?php echo htmlspecialchars($lead['name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($lead['email'] ?: '—'); ?></td>
                                                <td><?php echo htmlspecialchars($lead['phonenumber'] ?: '—'); ?></td>
                                                <td><?php echo htmlspecialchars($lead['status_name'] ?: '—'); ?></td>
                                                <td><strong><?php echo $lead['num_interacoes']; ?></strong></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>
<script src="<?php echo base_url('assets/plugins/Chart.js/Chart.min.js'); ?>"></script>
<script>
(function () {
    var el = document.getElementById('ic-grafico');
    if (!el || typeof Chart === 'undefined') { return; }

    var etiquetas = <?php echo json_encode($g_etiquetas ?? [], JSON_UNESCAPED_UNICODE); ?>;
    var valores   = <?php echo json_encode($g_valores ?? []); ?>;
    var porDia    = <?php echo (int) ($comercial_id ?? 0) > 0 ? 'true' : 'false'; ?>;
    var objectivo = <?php echo (float) ($objectivo ?? 0); ?>;

    /*
     * Um comercial -> linha, para se ver a evolução ao longo dos dias.
     * Todos -> barras deitadas, que é como se compara gente: os nomes ficam
     * legíveis e a lista cresce para baixo sem espremer o texto.
     */
    new Chart(el.getContext('2d'), {
        type: porDia ? 'line' : 'horizontalBar',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Interacções',
                data: valores,
                backgroundColor: porDia ? 'rgba(249,115,22,.15)' : '#f97316',
                borderColor: '#f97316',
                borderWidth: porDia ? 2 : 0,
                pointBackgroundColor: '#f97316',
                fill: porDia
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            tooltips: { callbacks: {
                label: function (t) { return t[porDia ? 'yLabel' : 'xLabel'] + ' interacções'; }
            } },
            scales: {
                xAxes: [{ ticks: { beginAtZero: true, precision: 0 } }],
                yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
            }
        }
    });
})();
</script>
<?php init_tail(); ?>
