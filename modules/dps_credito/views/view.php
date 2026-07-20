<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <?php echo form_open_multipart(admin_url('dps_credito/update/' . $processo['id'])); ?>
        <div class="row">

            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            Processo #<?php echo $processo['id']; ?>
                            <span class="label <?php echo dps_credito_cor_estado($processo['estado']); ?>">
                                <?php echo dps_credito_nome_estado($processo['estado']); ?>
                            </span>
                        </h4>
                        <hr>

                        <table class="table table-borderless">
                            <tr>
                                <td width="35%"><strong>Cliente</strong></td>
                                <td><?php echo html_escape($processo['cliente']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Lead de origem</strong></td>
                                <td>
                                    <?php if (!empty($processo['lead_id'])) { ?>
                                        <a href="<?php echo admin_url('leads/index/' . $processo['lead_id']); ?>" target="_blank">
                                            #<?php echo $processo['lead_id']; ?>
                                            <?php echo html_escape($processo['lead_nome']); ?>
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted">Criado manualmente</span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Contacto</strong></td>
                                <td>
                                    <?php echo html_escape($processo['lead_telefone'] ?: '—'); ?>
                                    <?php if (!empty($processo['lead_email'])) { ?>
                                        · <?php echo html_escape($processo['lead_email']); ?>
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Comercial</strong></td>
                                <td><?php echo html_escape($processo['staff_nome']); ?></td>
                            </tr>
                        </table>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="control-label">Situação</label>
                                <select name="situacao" class="form-control">
                                    <option value="novo_pedido" <?php echo $processo['situacao'] === 'novo_pedido' ? 'selected' : ''; ?>>
                                        Novo pedido de crédito
                                    </option>
                                    <option value="financiamento_existente" <?php echo $processo['situacao'] === 'financiamento_existente' ? 'selected' : ''; ?>>
                                        Já tem financiamento
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="control-label">Banco</label>
                                <input type="text" name="banco" class="form-control" list="bancos"
                                       value="<?php echo html_escape($processo['banco'] ?? ''); ?>">
                                <datalist id="bancos">
                                    <?php foreach (dps_credito_bancos() as $b) { ?>
                                        <option value="<?php echo html_escape($b); ?>"></option>
                                    <?php } ?>
                                </datalist>
                            </div>
                        </div>

                        <div class="row mtop15">
                            <div class="col-md-4">
                                <label class="control-label">Montante pedido (€)</label>
                                <input type="text" name="montante" class="form-control"
                                       value="<?php echo $processo['montante'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Valor da operação (€)</label>
                                <input type="text" name="valor" class="form-control"
                                       value="<?php echo $processo['valor'] ?? ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Taxa de comissão (%)</label>
                                <input type="text" name="taxa" class="form-control"
                                       value="<?php echo $processo['taxa'] ?? ''; ?>">
                            </div>
                        </div>

                        <div class="row mtop15">
                            <div class="col-md-6">
                                <label class="control-label">Estado</label>
                                <select name="estado" class="form-control">
                                    <?php foreach (['novo', 'em_analise', 'enviado_banco', 'aprovado', 'recusado', 'concluido'] as $e) { ?>
                                        <option value="<?php echo $e; ?>" <?php echo $processo['estado'] === $e ? 'selected' : ''; ?>>
                                            <?php echo dps_credito_nome_estado($e); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="control-label">Comissão calculada</label>
                                <p class="form-control-static">
                                    <strong><?php echo app_format_money($processo['comissao_total'] ?? 0, get_base_currency()); ?></strong>
                                    <small class="text-muted">valor × taxa, actualizado ao guardar</small>
                                </p>
                            </div>
                        </div>

                        <div class="form-group mtop15">
                            <label class="control-label">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="3"><?php echo html_escape($processo['observacoes'] ?? ''); ?></textarea>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Documentos</h5>
                        <p class="text-muted">
                            Documentos do cliente para encaminhar ao banco.
                            PDF, imagem, Word ou Excel, até 10 MB cada.
                        </p>
                        <hr>

                        <?php if (empty($docs)) { ?>
                            <p class="text-muted">Ainda sem documentos.</p>
                        <?php } else { ?>
                            <table class="table">
                                <tbody>
                                    <?php foreach ($docs as $doc) { ?>
                                        <tr>
                                            <td>
                                                <?php echo html_escape($doc['original_name']); ?><br>
                                                <small class="text-muted"><?php echo _dt($doc['dateadded']); ?></small>
                                            </td>
                                            <td class="text-right">
                                                <?php if ($pode_download || (int) $processo['staff_id'] === (int) get_staff_user_id()) { ?>
                                                    <a href="<?php echo admin_url('dps_credito/download_doc/' . $doc['id']); ?>"
                                                       class="btn btn-default btn-xs">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                <?php } ?>
                                                <a href="<?php echo admin_url('dps_credito/delete_doc/' . $doc['id']); ?>"
                                                   class="btn btn-danger btn-xs _delete">
                                                    <i class="fa fa-remove"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                        <div class="form-group">
                            <label class="control-label">Anexar documentos</label>
                            <input type="file" name="documentos[]" class="form-control" multiple
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body text-right">
                        <a href="<?php echo admin_url('dps_credito'); ?>" class="btn btn-default">Voltar</a>
                        <button type="submit" class="btn btn-info">Guardar</button>
                    </div>
                </div>
            </div>

        </div>
        <?php echo form_close(); ?>
    </div>
</div>
<?php init_tail(); ?>
