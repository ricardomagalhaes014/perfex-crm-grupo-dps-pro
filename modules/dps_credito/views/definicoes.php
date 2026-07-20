<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Definições — DPS Crédito</h4>
                        <hr>

                        <?php echo form_open(admin_url('dps_credito/definicoes')); ?>

                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="bloqueio_ativo" id="bloqueio_ativo"
                                    <?php echo $bloqueio_ativo ? 'checked' : ''; ?>>
                                <label for="bloqueio_ativo">
                                    <strong>Exigir o questionário antes de fechar uma lead</strong>
                                </label>
                            </div>
                            <small class="text-muted">
                                Se desligar, o questionário continua disponível mas deixa de ser obrigatório.
                                Útil para um período de habituação da equipa.
                            </small>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label class="control-label">Estados que contam como "fechar a lead"</label>
                            <select name="estados_fecho[]" class="form-control selectpicker" multiple data-live-search="true"
                                    data-actions-box="true">
                                <?php foreach ($estados_lead as $estado) { ?>
                                    <option value="<?php echo $estado['id']; ?>"
                                        <?php echo in_array((int) $estado['id'], $estados_fecho, true) ? 'selected' : ''; ?>>
                                        <?php echo html_escape($estado['name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <small class="text-muted">
                                Só ao mover uma lead para um destes estados é que o questionário é exigido.
                                Assim o comercial não é interrompido a cada gravação — apenas quando a lead
                                sai efectivamente do funil.
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Fontes de lead a que o questionário se aplica</label>
                            <select name="fontes[]" class="form-control selectpicker" multiple data-live-search="true"
                                    data-actions-box="true">
                                <?php foreach ($fontes as $fonte) { ?>
                                    <option value="<?php echo $fonte['id']; ?>"
                                        <?php echo in_array((int) $fonte['id'], $fontes_aplicaveis, true) ? 'selected' : ''; ?>>
                                        <?php echo html_escape($fonte['name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <small class="text-muted">
                                O questionário de crédito só é exigido a leads destas fontes — tipicamente
                                as de imobiliário em Portugal. Leads de outros países não são afectadas.
                                Se não escolher nenhuma, aplicam-se as fontes com «Portugal» no nome.
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Quem é notificado de novos processos de crédito</label>
                            <select name="notificar_staff[]" class="form-control selectpicker" multiple data-live-search="true">
                                <?php foreach ($staff as $s) { ?>
                                    <option value="<?php echo $s['staffid']; ?>"
                                        <?php echo in_array((int) $s['staffid'], $notificar_staff, true) ? 'selected' : ''; ?>>
                                        <?php echo html_escape($s['nome']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <small class="text-muted">
                                Se não escolher ninguém, são notificados todos os administradores.
                            </small>
                        </div>

                        <hr>
                        <div class="text-right">
                            <a href="<?php echo admin_url('dps_credito'); ?>" class="btn btn-default">Voltar</a>
                            <button type="submit" class="btn btn-info">Guardar definições</button>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
