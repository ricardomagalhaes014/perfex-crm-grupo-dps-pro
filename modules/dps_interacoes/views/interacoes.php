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
.ic-table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); }
.ic-table th { background:#f5f5f5; padding:12px 16px; text-align:left; font-size:13px; font-weight:700; color:#333; border-bottom:2px solid #e8e8e8; }
.ic-table td { padding:12px 16px; font-size:13px; color:#444; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.ic-table tr:last-child td { border-bottom:none; }
.ic-table tr:hover td { background:#fafafa; }
.ic-badge { display:inline-block; background:#f97316; color:#fff; border-radius:20px; padding:3px 12px; font-size:13px; font-weight:700; min-width:36px; text-align:center; }
.ic-badge-zero { background:#e0e0e0; color:#999; }
.ic-btn-detail { background:#1976d2; color:#fff; border:none; border-radius:4px; padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer; }
.ic-btn-detail:hover { background:#1565c0; }
.ic-btn-close { background:#757575; color:#fff; border:none; border-radius:4px; padding:5px 14px; font-size:12px; font-weight:600; cursor:pointer; }
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
                        </div>
                    </form>
                </div>

                <!-- Período activo -->
                <p class="ic-period-label">
                    <strong><?php echo htmlspecialchars($label); ?></strong>
                    &nbsp;|&nbsp; <?php echo date('d/m/Y', strtotime($date_from)); ?> a <?php echo date('d/m/Y', strtotime($date_to)); ?>
                </p>

                <!-- Tabela -->
                <table class="ic-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Comercial</th>
                            <th>Total Interacções</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comerciais)): ?>
                        <tr><td colspan="4" style="text-align:center;color:#aaa;padding:30px;">Sem dados para o período seleccionado.</td></tr>
                        <?php else: ?>
                        <?php $i = 1; foreach ($comerciais as $c): ?>
                        <tr id="row-<?php echo $c['staff_id']; ?>">
                            <td><?php echo $i++; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                            <td>
                                <span class="ic-badge <?php echo $c['total_interacoes']==0?'ic-badge-zero':''; ?>">
                                    <?php echo $c['total_interacoes']; ?>
                                </span>
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
                                            btn.textContent='✕ Fechar';
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
                            <td colspan="4">
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
                                                <td><?php echo htmlspecialchars($lead['status'] ?: '—'); ?></td>
                                                <td><strong><?php echo $lead['interacoes']; ?></strong></td>
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
<?php init_tail(); ?>
