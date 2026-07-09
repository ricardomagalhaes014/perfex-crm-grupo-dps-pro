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
                                        <th>Enviada em</th>
                                        <th>WhatsApp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($propostas)) { ?>
                                    <tr><td colspan="7" class="text-muted text-center">Sem propostas enviadas.</td></tr>
                                    <?php } ?>
                                    <?php foreach ($propostas as $p) { ?>
                                    <tr>
                                        <td><a href="<?= admin_url('leads/index/' . (int) $p->lead_id); ?>"><?= e($p->lead_nome ?: ('#' . (int) $p->lead_id)); ?></a></td>
                                        <td><?= $p->staff_id ? e(get_staff_full_name($p->staff_id)) : '—'; ?></td>
                                        <td><?= e($p->empreendimento ?: '—'); ?></td>
                                        <td><strong><?= e($p->unidade ?: '—'); ?></strong></td>
                                        <td><?= e($p->estado_atual ?: ($p->lead_status_nome ?: '—')); ?></td>
                                        <td class="text-muted" style="font-size:12px;"><?= e($p->created_at); ?></td>
                                        <td><?= $p->wa_ok ? '<i class="fa fa-check text-success"></i> Enviada' : '<i class="fa fa-times text-danger"></i> Falhou'; ?></td>
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
<?php init_tail(); ?>
