<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    Processo #<?php echo $processo['id']; ?>
                                    <span class="label <?php echo dps_credito_cor_estado($processo['estado']); ?>">
                                        <?php echo dps_credito_nome_estado($processo['estado']); ?>
                                    </span>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('dps_credito'); ?>" class="btn btn-default btn-sm">Voltar</a>
                            </div>
                        </div>
                        <hr>

                        <table class="table table-borderless">
                            <tr><td width="35%"><strong>Cliente</strong></td><td><?php echo html_escape($processo['cliente']); ?></td></tr>
                            <tr>
                                <td><strong>Lead</strong></td>
                                <td>
                                    <?php if (!empty($processo['lead_id'])) { ?>
                                        <a href="<?php echo admin_url('leads/index/' . $processo['lead_id']); ?>" target="_blank">
                                            #<?php echo $processo['lead_id']; ?> <?php echo html_escape($processo['lead_nome']); ?>
                                        </a>
                                    <?php } else { echo '—'; } ?>
                                </td>
                            </tr>
                            <tr><td><strong>Contacto</strong></td><td><?php echo html_escape($processo['lead_telefone'] ?: '—'); ?> <?php echo !empty($processo['lead_email']) ? '· ' . html_escape($processo['lead_email']) : ''; ?></td></tr>
                            <tr><td><strong>Comercial</strong></td><td><?php echo html_escape($processo['staff_nome']); ?></td></tr>
                            <tr><td><strong>Situação</strong></td><td><?php echo dps_credito_nome_situacao($processo['situacao']); ?></td></tr>
                            <tr><td><strong>Banco</strong></td><td><?php echo html_escape($processo['banco'] ?: '—'); ?></td></tr>
                            <tr><td><strong>Montante pedido</strong></td><td><?php echo $processo['montante'] !== null ? app_format_money($processo['montante'], get_base_currency()) : '—'; ?></td></tr>
                        </table>

                        <?php if ($processo['estado'] === 'documentos_em_falta' && !empty($processo['docs_em_falta'])) { ?>
                            <div class="alert alert-warning">
                                <strong>Documentos pedidos ao comercial:</strong><br>
                                <?php echo nl2br(html_escape($processo['docs_em_falta'])); ?>
                            </div>
                        <?php } ?>

                        <h5>Documentos</h5>
                        <?php if (empty($docs)) { ?>
                            <p class="text-muted">Sem documentos.</p>
                        <?php } else { ?>
                            <table class="table">
                                <tbody>
                                    <?php foreach ($docs as $doc) { ?>
                                        <tr>
                                            <td><?php echo html_escape($doc['original_name']); ?><br><small class="text-muted"><?php echo _dt($doc['dateadded']); ?></small></td>
                                            <td class="text-right">
                                                <?php if ($pode_download || (int) $processo['staff_id'] === (int) get_staff_user_id()) { ?>
                                                    <a href="<?php echo admin_url('dps_credito/download_doc/' . $doc['id']); ?>" class="btn btn-default btn-xs"><i class="fa fa-download"></i></a>
                                                <?php } ?>
                                                <a href="<?php echo admin_url('dps_credito/delete_doc/' . $doc['id']); ?>" class="btn btn-danger btn-xs _delete"><i class="fa fa-remove"></i></a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                        <?php echo form_open_multipart(admin_url('dps_credito/update/' . $processo['id'])); ?>
                        <div class="form-group">
                            <label class="control-label">Anexar mais documentos</label>
                            <input type="file" name="documentos[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Observações internas</label>
                            <textarea name="observacoes" class="form-control" rows="2"><?php echo html_escape($processo['observacoes'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-default btn-sm">Guardar documentos / notas</button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <?php if (is_admin() || staff_can('edit', 'dps_credito')) { ?>

                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="no-margin">Seguimento</h5>
                            <hr>

                            <?php if ($processo['estado'] !== 'sucesso') { ?>
                                <!-- Pedir documentos em falta -->
                                <?php echo form_open(admin_url('dps_credito/documentos_em_falta/' . $processo['id'])); ?>
                                <div class="form-group">
                                    <label class="control-label">Faltam documentos? Diga ao comercial o quê:</label>
                                    <textarea name="nota" class="form-control" rows="2" placeholder="Ex.: falta o comprovativo de rendimentos e o IRS."></textarea>
                                </div>
                                <button type="submit" class="btn btn-warning btn-block btn-sm">Pedir documentos ao comercial</button>
                                <?php echo form_close(); ?>

                                <hr>

                                <!-- Mudar estado -->
                                <?php echo form_open(admin_url('dps_credito/estado/' . $processo['id'])); ?>
                                <div class="form-group">
                                    <label class="control-label">Estado</label>
                                    <select name="estado" class="form-control" id="dps-credito-estado-sel">
                                        <?php foreach (dps_credito_estados_processo() as $e) { ?>
                                            <option value="<?php echo $e; ?>" <?php echo $processo['estado'] === $e ? 'selected' : ''; ?>>
                                                <?php echo dps_credito_nome_estado($e); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group" id="dps-credito-valor-grupo" style="display:none;">
                                    <label class="control-label">Valor do crédito recebido (€)</label>
                                    <input type="text" name="valor_credito" class="form-control" value="<?php echo $processo['valor_credito'] ?? ''; ?>">
                                    <small class="text-muted">A comissão do comercial (<?php echo rtrim(rtrim(number_format(dps_credito_taxa_comissao(), 2, ',', ''), '0'), ','); ?>%) é calculada sobre este valor.</small>
                                </div>
                                <button type="submit" class="btn btn-info btn-block btn-sm">Actualizar estado</button>
                                <?php echo form_close(); ?>
                            <?php } else { ?>
                                <div class="alert alert-success no-margin">
                                    <strong>Concluído com sucesso.</strong><br>
                                    Valor do crédito: <?php echo app_format_money($processo['valor_credito'] ?? 0, get_base_currency()); ?><br>
                                    Comissão do comercial: <strong><?php echo app_format_money($processo['comissao_total'] ?? 0, get_base_currency()); ?></strong>
                                    (<?php echo rtrim(rtrim(number_format((float) ($processo['taxa'] ?? dps_credito_taxa_comissao()), 2, ',', ''), '0'), ','); ?>%)
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                <?php } ?>

                <?php if ($processo['estado'] === 'sucesso') { ?>
                    <div class="panel_s">
                        <div class="panel-body">
                            <h5 class="no-margin">Comissão</h5>
                            <hr>
                            <table class="table table-borderless no-margin">
                                <tr><td><strong>Valor do crédito</strong></td><td><?php echo app_format_money($processo['valor_credito'] ?? 0, get_base_currency()); ?></td></tr>
                                <tr><td><strong>Taxa</strong></td><td><?php echo rtrim(rtrim(number_format((float) ($processo['taxa'] ?? 0), 2, ',', ''), '0'), ','); ?>%</td></tr>
                                <tr><td><strong>Comissão</strong></td><td><strong><?php echo app_format_money($processo['comissao_total'] ?? 0, get_base_currency()); ?></strong></td></tr>
                            </table>
                        </div>
                    </div>
                <?php } ?>
            </div>

        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function () {
    function toggleValor() {
        var sel = document.getElementById('dps-credito-estado-sel');
        var grp = document.getElementById('dps-credito-valor-grupo');
        if (sel && grp) { grp.style.display = sel.value === 'sucesso' ? '' : 'none'; }
    }
    var sel = document.getElementById('dps-credito-estado-sel');
    if (sel) { sel.addEventListener('change', toggleValor); toggleValor(); }
})();
</script>
