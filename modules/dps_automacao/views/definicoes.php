<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Definições — Automação</h4>
                        <hr>

                        <?php echo form_open(admin_url('dps_automacao/definicoes_guardar')); ?>

                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="dps_automacao_ativo" id="dps_automacao_ativo" value="1"
                                    <?php echo $automacao_ativa ? 'checked' : ''; ?>>
                                <label for="dps_automacao_ativo">
                                    <strong>Interruptor geral da automação</strong>
                                </label>
                            </div>
                            <small class="text-muted">
                                Com este interruptor desligado <strong>NADA é enviado</strong> —
                                nem envios em massa, nem testes, nem follow-ups automáticos.
                                A verificação é feita no servidor em todos os envios.
                            </small>
                        </div>

                        <hr>

                        <div class="form-group">
                            <div class="checkbox checkbox-primary">
                                <input type="checkbox" name="dps_automacao_followups_ativo" id="dps_automacao_followups_ativo" value="1"
                                    <?php echo $followups_ativos ? 'checked' : ''; ?>>
                                <label for="dps_automacao_followups_ativo">
                                    <strong>Follow-ups automáticos por email (7 / 15 / 30 dias sem interação)</strong>
                                </label>
                            </div>
                            <small class="text-muted">
                                Exige o interruptor geral ligado E o cron do Perfex a correr.
                                Cada lead recebe cada marco no máximo uma vez; leads perdidas,
                                lixo, convertidas ou sem email ficam de fora.
                            </small>
                        </div>

                        <hr>

                        <p class="text-muted">
                            Mensagens dos follow-ups. Variáveis disponíveis:
                            <code>{nome}</code> (nome da lead) e <code>{comercial}</code>
                            (nome do comercial atribuído).
                        </p>

                        <div class="form-group">
                            <label for="msg_followup_7">Mensagem aos 7 dias sem interação</label>
                            <textarea name="msg_followup_7" id="msg_followup_7" rows="5" class="form-control"><?php echo html_escape((string) $msg_7); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="msg_followup_15">Mensagem aos 15 dias sem interação</label>
                            <textarea name="msg_followup_15" id="msg_followup_15" rows="5" class="form-control"><?php echo html_escape((string) $msg_15); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="msg_followup_30">Mensagem aos 30 dias sem interação</label>
                            <textarea name="msg_followup_30" id="msg_followup_30" rows="5" class="form-control"><?php echo html_escape((string) $msg_30); ?></textarea>
                        </div>

                        <small class="text-muted">
                            Nota: um marco com a mensagem vazia não é enviado.
                        </small>

                        <hr>

                        <button type="submit" class="btn btn-info">Guardar definições</button>

                        <?php echo form_close(); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
