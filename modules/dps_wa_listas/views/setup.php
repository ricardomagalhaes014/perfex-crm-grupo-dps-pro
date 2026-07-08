<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><i class="fa fa-whatsapp text-success"></i> WhatsApp Listas — sincronização de estados</h4>
                        <p class="text-muted">Quando o estado de uma lead muda, a etiqueta correspondente é aplicada/movida na conversa do cliente no WhatsApp do comercial atribuído. Requer <b>WhatsApp Business</b> com as etiquetas criadas com o <b>mesmo nome do estado</b>.</p>

                        <p>
                            Estado do módulo:
                            <?php if ($enabled) { ?><span class="label label-success">Ativo</span><?php } else { ?><span class="label label-default">Desativado</span><?php } ?>
                            &nbsp; Evolution API: <code><?= e($evo_url ?: '(não configurada)'); ?></code>
                        </p>

                        <div class="row">
                            <div class="col-md-3"><div class="tw-text-center" style="text-align:center;padding:10px;border:1px solid #eee;border-radius:8px;"><div style="font-size:22px;font-weight:600;"><?= (int) $stats['pending']; ?></div><div class="text-muted">na fila</div></div></div>
                            <div class="col-md-3"><div style="text-align:center;padding:10px;border:1px solid #eee;border-radius:8px;"><div style="font-size:22px;font-weight:600;color:#2f9e44;"><?= (int) $stats['done']; ?></div><div class="text-muted">aplicadas</div></div></div>
                            <div class="col-md-3"><div style="text-align:center;padding:10px;border:1px solid #eee;border-radius:8px;"><div style="font-size:22px;font-weight:600;color:#b58105;"><?= (int) $stats['skipped']; ?></div><div class="text-muted">ignoradas</div></div></div>
                            <div class="col-md-3"><div style="text-align:center;padding:10px;border:1px solid #eee;border-radius:8px;"><div style="font-size:22px;font-weight:600;color:#c0392b;"><?= (int) $stats['failed']; ?></div><div class="text-muted">falhadas</div></div></div>
                        </div>

                        <form method="post" style="margin-top:12px;">
                            <button type="submit" name="process_now" value="1" class="btn btn-info btn-sm"><i class="fa fa-refresh"></i> Processar fila agora</button>
                        </form>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Etiquetas a criar no WhatsApp Business</h4>
                        <p class="text-muted">Cria estas etiquetas (nomes exatos) na app WhatsApp Business de cada comercial. A verde já existem na conta; a cinzento faltam.</p>
                        <?php if (empty($instances)) { ?>
                        <p class="text-muted">Nenhum comercial com WhatsApp ligado.</p>
                        <?php } ?>
                        <?php foreach ($instances as $inst) { ?>
                        <div style="margin-bottom:14px;">
                            <b><?= e($inst['name']); ?></b> <span class="text-muted">(<?= e($inst['instance']); ?>)</span>
                            <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px;">
                                <?php foreach ($statuses as $st) {
                                    $has = in_array(mb_strtolower(trim($st)), $inst['labels'], true);
                                ?>
                                <span class="label <?= $has ? 'label-success' : 'label-default'; ?>" style="font-size:12px;<?= $has ? '' : 'opacity:.7;'; ?>"><?= $has ? '✓ ' : ''; ?><?= e($st); ?></span>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Últimas sincronizações</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>#</th><th>Lead</th><th>Comercial</th><th>Estado</th><th>Resultado</th><th>Detalhe</th><th>Quando</th></tr></thead>
                                <tbody>
                                    <?php if (empty($recent)) { ?><tr><td colspan="7" class="text-muted text-center">Ainda sem registos.</td></tr><?php } ?>
                                    <?php foreach ($recent as $q) {
                                        $badge = ['pending' => 'default', 'done' => 'success', 'skipped' => 'warning', 'failed' => 'danger'][$q->status] ?? 'default';
                                    ?>
                                    <tr>
                                        <td><?= (int) $q->id; ?></td>
                                        <td><a href="<?= admin_url('leads/index/' . (int) $q->lead_id); ?>">#<?= (int) $q->lead_id; ?></a></td>
                                        <td><?= $q->staff_id ? e(get_staff_full_name($q->staff_id)) : '—'; ?></td>
                                        <td><?= e(dps_wa_listas_status_name($q->new_status_id) ?: '—'); ?></td>
                                        <td><span class="label label-<?= $badge; ?>"><?= e($q->status); ?></span></td>
                                        <td class="text-muted" style="font-size:12px;"><?= e($q->last_error ?: ''); ?></td>
                                        <td class="text-muted" style="font-size:12px;"><?= e($q->processed_at ?: $q->created_at); ?></td>
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
