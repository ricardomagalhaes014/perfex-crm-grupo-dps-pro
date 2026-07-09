<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:15px;">
                            <h4 class="no-margin"><i class="fa fa-file-pdf-o text-danger"></i> Propostas Enviadas</h4>
                            <span class="text-muted"><?= count($propostas); ?> proposta<?= count($propostas) === 1 ? '' : 's'; ?><?= $comercial > 0 ? ' · ' . e(get_staff_full_name($comercial)) : ''; ?></span>

                            <?php if ($can_view_all) { ?>
                            <form method="get" action="<?= admin_url('dps_propostas/todas'); ?>" style="margin-left:auto;display:flex;align-items:center;gap:8px;">
                                <label style="margin:0;font-size:13px;color:#5a6673;"><i class="fa fa-user-o"></i> Comercial:</label>
                                <select name="comercial" class="selectpicker" data-width="260px" data-live-search="true" onchange="this.form.submit()">
                                    <option value="0"<?= $comercial == 0 ? ' selected' : ''; ?>>Todos os comerciais</option>
                                    <?php foreach ($comerciais as $c) {
                                        $cid = (int) $c['staffid'];
                                    ?>
                                    <option value="<?= $cid; ?>"<?= $comercial == $cid ? ' selected' : ''; ?>><?= e(trim($c['firstname'] . ' ' . $c['lastname'])); ?> (<?= (int) $c['c']; ?>)</option>
                                    <?php } ?>
                                </select>
                                <?php if ($comercial > 0) { ?>
                                <a href="<?= admin_url('dps_propostas/todas'); ?>" class="btn btn-default btn-sm">Limpar</a>
                                <?php } ?>
                            </form>
                            <?php } ?>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Lead</th>
                                        <th>Comercial</th>
                                        <th>Empreendimento</th>
                                        <th>Unidade</th>
                                        <th>Estado da lead</th>
                                        <th>Resultado</th>
                                        <th>Enviada em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($propostas)) { ?>
                                    <tr><td colspan="8" class="text-muted text-center">Sem propostas enviadas.</td></tr>
                                    <?php } ?>
                                    <?php foreach ($propostas as $p) { ?>
                                    <tr>
                                        <td><a href="<?= admin_url('leads/index/' . (int) $p->lead_id); ?>"><?= e($p->lead_nome ?: ('#' . (int) $p->lead_id)); ?></a></td>
                                        <td><?= $p->staff_id ? e(get_staff_full_name($p->staff_id)) : '—'; ?></td>
                                        <td><?= e($p->empreendimento ?: '—'); ?></td>
                                        <td><strong><?= e($p->unidade ?: '—'); ?></strong></td>
                                        <td><?= e($p->estado_atual ?: ($p->lead_status_nome ?: '—')); ?></td>
                                        <td>
                                            <?php if ($p->outcome === 'aceite') { ?>
                                            <span class="label label-success">Aceite</span> <strong><?= number_format((float) $p->valor, 0, ',', '.'); ?> €</strong>
                                            <?php } elseif ($p->outcome === 'recusado') { ?>
                                            <span class="label label-danger">Recusada</span>
                                            <?php } else { ?>
                                            <span class="label label-default">Pendente</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-muted" style="font-size:12px;"><?= e($p->created_at); ?></td>
                                        <td>
                                            <?php if ($p->outcome === 'pendente') { ?>
                                            <button class="btn btn-success btn-xs" onclick="dpsResultado(<?= (int) $p->id; ?>,'aceite')"><i class="fa fa-check"></i> Aceite</button>
                                            <button class="btn btn-danger btn-xs" onclick="dpsResultado(<?= (int) $p->id; ?>,'recusado')"><i class="fa fa-times"></i> Recusada</button>
                                            <?php } else { ?>
                                            <span class="text-muted" style="font-size:11px;"><?= e($p->outcome_at); ?></span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
var DPS_CSRF = { name: '<?= $this->security->get_csrf_token_name(); ?>', hash: '<?= $this->security->get_csrf_hash(); ?>' };
function dpsResultado(id, outcome) {
    var valor = '';
    if (outcome === 'aceite') {
        valor = prompt('Valor da proposta aceite (€):');
        if (valor === null || valor === '') { return; }
    } else {
        if (!confirm('Marcar como RECUSADA? A lead passa para "Para outras oportunidades".')) { return; }
    }
    var data = { proposta_id: id, outcome: outcome, valor: valor };
    data[DPS_CSRF.name] = DPS_CSRF.hash;
    $.post(admin_url + 'dps_propostas/resultado_proposta', data, function (r) {
        try { r = (typeof r === 'string') ? JSON.parse(r) : r; } catch (e) {}
        alert_float(r && r.success ? 'success' : 'danger', (r && r.message) || 'Erro.');
        if (r && r.success) { setTimeout(function () { location.reload(); }, 1000); }
    }).fail(function () { alert_float('danger', 'Erro de comunicação.'); });
}
</script>
<?php init_tail(); ?>
