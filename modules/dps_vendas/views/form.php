<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <h4 class="no-margin"><?php echo $venda ? 'Editar Venda #' . $venda['id'] : 'Nova Venda'; ?></h4>
                        <hr>

                        <?php echo form_open_multipart(admin_url('dps_vendas/form' . ($venda ? '/' . $venda['id'] : '')), ['id' => 'dps-venda-form']); ?>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="control-label">Empreendimento <span class="text-danger">*</span></label>
                                <input type="text" name="empreendimento" class="form-control" list="lista-empreendimentos"
                                       value="<?php echo html_escape($venda['empreendimento'] ?? ''); ?>" required>
                                <datalist id="lista-empreendimentos">
                                    <?php foreach ($empreendimentos as $emp) { ?>
                                        <option value="<?php echo html_escape($emp); ?>"></option>
                                    <?php } ?>
                                </datalist>
                                <small class="text-muted">Escolha da lista ou escreva um novo.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Unidade / Fracção <span class="text-danger">*</span></label>
                                <input type="text" name="unidade" class="form-control"
                                       value="<?php echo html_escape($venda['unidade'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Valor da venda (€) <span class="text-danger">*</span></label>
                                <input type="text" name="valor" class="form-control"
                                       value="<?php echo $venda['valor'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <div class="row mtop15">
                            <div class="col-md-4">
                                <label class="control-label">Taxa de comissão (%)</label>
                                <input type="text" name="taxa" class="form-control"
                                       value="<?php echo $venda['taxa'] ?? ''; ?>"
                                       placeholder="Deixe vazio para usar a regra">
                                <small class="text-muted">
                                    Se deixar vazio, aplica-se a regra do empreendimento.
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Data da venda</label>
                                <div class="input-group date">
                                    <input type="text" name="data_venda" class="form-control datepicker"
                                           value="<?php echo !empty($venda['data_venda']) ? _d($venda['data_venda']) : ''; ?>">
                                    <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Lead associada (opcional)</label>
                                <input type="number" name="lead_id" class="form-control"
                                       value="<?php echo $venda['lead_id'] ?? ''; ?>"
                                       placeholder="ID da lead">
                            </div>
                        </div>

                        <hr>
                        <h5>Dados do cliente</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="control-label">Nome <span class="text-danger">*</span></label>
                                <input type="text" name="cliente" class="form-control"
                                       value="<?php echo html_escape($venda['cliente'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="control-label">Morada</label>
                                <input type="text" name="cliente_morada" class="form-control"
                                       value="<?php echo html_escape($venda['cliente_morada'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="row mtop15">
                            <div class="col-md-4">
                                <label class="control-label">Telefone</label>
                                <input type="text" name="cliente_telefone" class="form-control"
                                       value="<?php echo html_escape($venda['cliente_telefone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Email</label>
                                <input type="email" name="cliente_email" class="form-control"
                                       value="<?php echo html_escape($venda['cliente_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Regime civil</label>
                                <select name="regime_civil" class="form-control selectpicker">
                                    <option value="">—</option>
                                    <?php foreach (dps_vendas_regimes_civis() as $regime) { ?>
                                        <option value="<?php echo html_escape($regime); ?>"
                                            <?php echo ($venda['regime_civil'] ?? '') === $regime ? 'selected' : ''; ?>>
                                            <?php echo html_escape($regime); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <hr>
                        <h5>Documentos</h5>

                        <?php if (!$venda) { ?>
                            <p class="text-muted">
                                O Cartão de Cidadão (frente e verso) é obrigatório para registar a venda.
                                Aceita-se PDF, JPG ou PNG até 8 MB por ficheiro.
                            </p>
                        <?php } else { ?>
                            <p class="text-muted">
                                Os documentos já enviados aparecem no detalhe da venda.
                                Aqui só precisa de anexar se quiser substituir ou acrescentar.
                            </p>
                        <?php } ?>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="control-label">
                                    CC frente <?php echo !$venda ? '<span class="text-danger">*</span>' : ''; ?>
                                </label>
                                <input type="file" name="cc_frente" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">
                                    CC verso <?php echo !$venda ? '<span class="text-danger">*</span>' : ''; ?>
                                </label>
                                <input type="file" name="cc_verso" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Outros documentos</label>
                                <input type="file" name="outros[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <hr>

                        <div class="text-right">
                            <a href="<?php echo admin_url('dps_vendas'); ?>" class="btn btn-default">Cancelar</a>
                            <button type="submit" class="btn btn-info">
                                <?php echo $venda ? 'Guardar alterações' : 'Criar venda'; ?>
                            </button>
                        </div>

                        <?php echo form_close(); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
