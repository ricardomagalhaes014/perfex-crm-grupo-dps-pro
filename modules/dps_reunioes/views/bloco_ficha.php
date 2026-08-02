<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/**
 * Bloco "Reuniões online" para a ficha de uma lead ou de um cliente.
 *
 * Espera $rel_type ('lead'|'customer'), $rel_id, e os contactos que já se
 * conhecem ($pre_nome, $pre_email, $pre_tel) para não obrigar o comercial a
 * reescrever o que o CRM já sabe.
 */
$CI = &get_instance();
$CI->load->model('dps_reunioes/dps_reunioes_model');

$reunioes = $CI->dps_reunioes_model->da_ficha($rel_type, $rel_id);

$CI->load->model('staff_model');
$equipa = $CI->staff_model->get('', ['active' => 1]);

$cores = [
    'agendada'       => 'label-info',
    'realizada'      => 'label-success',
    'nao_compareceu' => 'label-danger',
    'cancelada'      => 'label-default',
];
$nomes = [
    'agendada'       => 'Agendada',
    'realizada'      => 'Realizada',
    'nao_compareceu' => 'Não compareceu',
    'cancelada'      => 'Cancelada',
];
?>
<div class="dps-reunioes-bloco">
    <div class="clearfix mbot15">
        <h4 class="no-margin pull-left">Reuniões online</h4>
        <button type="button" class="btn btn-info btn-sm pull-right" data-toggle="modal"
                data-target="#dps-marcar-reuniao">
            <i class="fa fa-video-camera"></i> Marcar reunião
        </button>
    </div>

    <?php if (empty($reunioes)) { ?>
        <p class="text-muted">Ainda não há reuniões marcadas.</p>
    <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Data</th><th>Hora</th><th>Comercial</th>
                        <th>Link</th><th>Estado</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reunioes as $r) { ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($r['data_hora'])); ?></td>
                        <td><?php echo date('H:i', strtotime($r['data_hora'])); ?></td>
                        <td>
                            <?php echo html_escape((string) $r['comercial']); ?>
                            <?php if (!empty($r['convidado'])) { ?>
                                <br><small class="text-muted">
                                    + <?php echo html_escape($r['convidado']); ?>
                                    (<?php echo html_escape($r['convite_estado'] ?: 'pendente'); ?>)
                                </small>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="<?php echo html_escape($r['link']); ?>" target="_blank"
                               class="btn btn-success btn-xs">
                                <i class="fa fa-video-camera"></i> Entrar
                            </a>
                        </td>
                        <td>
                            <span class="label <?php echo $cores[$r['estado']] ?? 'label-default'; ?>">
                                <?php echo $nomes[$r['estado']] ?? $r['estado']; ?>
                            </span>
                            <?php if (!empty($r['duracao_real_min'])) { ?>
                                <br><small class="text-muted"><?php echo (int) $r['duracao_real_min']; ?> min</small>
                            <?php } ?>
                        </td>
                        <td class="text-right">
                            <a href="<?php echo admin_url('dps_reunioes/ver/' . (int) $r['id']); ?>"
                               class="btn btn-default btn-xs">Abrir</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } ?>
</div>

<div class="modal fade" id="dps-marcar-reuniao" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <?php echo form_open(admin_url('dps_reunioes/marcar')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Marcar reunião online</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="rel_type" value="<?php echo html_escape($rel_type); ?>">
                <input type="hidden" name="rel_id" value="<?php echo (int) $rel_id; ?>">

                <div class="row">
                    <div class="col-md-6">
                        <label class="control-label">Data <span class="text-danger">*</span></label>
                        <input type="date" name="data" class="form-control" required
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="control-label">Hora <span class="text-danger">*</span></label>
                        <input type="time" name="hora" class="form-control" required value="15:00">
                    </div>
                    <div class="col-md-3">
                        <label class="control-label">Duração</label>
                        <select name="duracao_min" class="form-control">
                            <option value="30" selected>30 min</option>
                            <option value="45">45 min</option>
                            <option value="60">1 hora</option>
                        </select>
                    </div>
                </div>

                <div class="row mtop15">
                    <div class="col-md-6">
                        <label class="control-label">Comercial <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-control" required>
                            <?php foreach ($equipa as $s) { ?>
                                <option value="<?php echo (int) $s['staffid']; ?>"
                                    <?php echo (int) $s['staffid'] === (int) get_staff_user_id() ? 'selected' : ''; ?>>
                                    <?php echo html_escape(trim($s['firstname'] . ' ' . $s['lastname'])); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="control-label">Convidar (opcional)</label>
                        <select name="convidado_id" class="form-control">
                            <option value="">— ninguém —</option>
                            <?php
                            /*
                             * Lista fechada, não "todos os administradores": convidar
                             * é interromper alguém, e só faz sentido para quem
                             * acompanha reuniões comerciais.
                             */
                            $convidaveis = defined('DPS_REUNIOES_CONVIDAVEL') ? DPS_REUNIOES_CONVIDAVEL : [];
                            foreach ($equipa as $s) {
                                if (!in_array((int) $s['staffid'], $convidaveis, true)) { continue; } ?>
                                <option value="<?php echo (int) $s['staffid']; ?>">
                                    <?php echo html_escape(trim($s['firstname'] . ' ' . $s['lastname'])); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <span class="help-block" style="margin-bottom:0;">
                            Recebe notificação no CRM para aceitar ou recusar.
                        </span>
                    </div>
                </div>

                <hr>
                <p class="text-muted" style="font-size:13px;">
                    Estes dados vêm da ficha. Corrija se algum estiver errado — é para aqui
                    que vão o email e o WhatsApp.
                </p>
                <div class="row">
                    <div class="col-md-4">
                        <label class="control-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" name="cliente_nome" class="form-control" required
                               value="<?php echo html_escape($pre_nome ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">Email</label>
                        <input type="email" name="cliente_email" class="form-control"
                               value="<?php echo html_escape($pre_email ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="control-label">Telefone</label>
                        <input type="text" name="cliente_telefone" class="form-control"
                               value="<?php echo html_escape($pre_tel ?? ''); ?>">
                    </div>
                </div>

                <div class="row mtop15">
                    <div class="col-md-12">
                        <label class="control-label">Assunto</label>
                        <input type="text" name="assunto" class="form-control"
                               placeholder="Reunião online" value="Reunião online">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-info">Marcar e avisar o cliente</button>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>
