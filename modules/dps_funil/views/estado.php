<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center" style="display:flex;align-items:center;gap:12px;margin-bottom:15px;">
                            <a href="<?= admin_url('dps_funil') . ($comercial > 0 ? '?comercial=' . (int) $comercial : ''); ?>" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Funil</a>
                            <h4 class="no-margin" style="margin:0;">
                                <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:<?= e($status->color ?: '#ccc'); ?>;margin-right:6px;"></span>
                                <?= e($status->name); ?>
                                <?php if (! empty($comercial_nome)) { ?><small class="text-muted">· <?= e($comercial_nome); ?></small><?php } ?>
                            </h4>
                            <span class="text-muted" style="margin-left:auto;">
                                A mostrar <?= (int) $showing; ?> de <?= number_format((int) $total, 0, ',', '.'); ?> leads
                            </span>
                        </div>

                        <?php if ($total > $showing) { ?>
                        <p class="text-muted" style="font-size:12px;">Limitado às <?= (int) $showing; ?> mais recentes. Usa o <a href="<?= admin_url('leads'); ?>">Kanban / lista completa</a> para ver todas.</p>
                        <?php } ?>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Telefone</th>
                                        <th>Email</th>
                                        <th>Comercial</th>
                                        <th>Último contacto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($leads)) { ?>
                                    <tr><td colspan="6" class="text-muted text-center">Sem leads neste estado.</td></tr>
                                    <?php } ?>
                                    <?php foreach ($leads as $lead) {
                                        $phone = trim((string) $lead['phonenumber']);
                                        $waPhone = preg_replace('/[^0-9]/', '', $phone);
                                    ?>
                                    <tr>
                                        <td><a href="<?= admin_url('leads/index/' . (int) $lead['id']); ?>"><?= e($lead['name']); ?></a></td>
                                        <td>
                                            <?php if ($phone !== '') { ?>
                                            <?= e($phone); ?>
                                            <?php if ($waPhone !== '') { ?>
                                            <a href="https://wa.me/<?= e($waPhone); ?>" target="_blank" rel="noopener" title="WhatsApp"><i class="fa fa-whatsapp text-success"></i></a>
                                            <?php } ?>
                                            <?php } ?>
                                        </td>
                                        <td><?= e($lead['email']); ?></td>
                                        <td><?= $lead['assigned'] ? e(get_staff_full_name($lead['assigned'])) : '<span class="text-muted">—</span>'; ?></td>
                                        <td><?= $lead['lastcontact'] && $lead['lastcontact'] != '0000-00-00 00:00:00' ? _dt($lead['lastcontact']) : '<span class="text-muted">—</span>'; ?></td>
                                        <td class="text-right"><a href="<?= admin_url('leads/index/' . (int) $lead['id']); ?>" class="btn btn-default btn-xs">Abrir</a></td>
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
