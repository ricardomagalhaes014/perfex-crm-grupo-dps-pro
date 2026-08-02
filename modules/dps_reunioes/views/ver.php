<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row">
    <div class="col-md-7"><div class="panel_s"><div class="panel-body">
      <h4 class="no-margin">Reunião com <?php echo html_escape((string) $r['cliente_nome']); ?></h4>
      <hr>
      <table class="table">
        <tr><td width="180" class="text-muted">Data e hora</td><td><strong><?php echo dps_reunioes_quando($r['data_hora']); ?></strong></td></tr>
        <tr><td class="text-muted">Duração prevista</td><td><?php echo (int) $r['duracao_min']; ?> minutos</td></tr>
        <tr><td class="text-muted">Comercial</td><td><?php echo html_escape((string) $r['comercial']); ?></td></tr>
        <?php if (!empty($r['convidado'])) { ?>
        <tr><td class="text-muted">Convidado</td><td>
          <?php echo html_escape($r['convidado']); ?>
          <span class="label <?php echo $r['convite_estado']==='aceite'?'label-success':($r['convite_estado']==='recusado'?'label-danger':'label-warning'); ?>">
            <?php echo html_escape($r['convite_estado'] ?: 'pendente'); ?>
          </span>
        </td></tr>
        <?php } ?>
        <tr><td class="text-muted">Contactos</td><td>
          <?php echo html_escape((string) $r['cliente_email']); ?><br>
          <?php echo html_escape((string) $r['cliente_telefone']); ?>
        </td></tr>
        <tr><td class="text-muted">Link</td><td>
          <a href="<?php echo html_escape($r['link']); ?>" target="_blank" class="btn btn-success btn-sm">
            <i class="fa fa-video-camera"></i> Entrar na reunião
          </a>
          <div style="font-size:12px;color:#888;margin-top:6px;"><?php echo html_escape($r['link']); ?></div>
        </td></tr>
      </table>

      <?php if ((int) $r['convidado_id'] === (int) get_staff_user_id() && $r['convite_estado'] === 'pendente') { ?>
        <hr>
        <p><strong>Foi convidado para esta reunião.</strong></p>
        <a href="<?php echo admin_url('dps_reunioes/responder/' . (int) $r['id'] . '/aceite'); ?>" class="btn btn-success">Aceitar</a>
        <a href="<?php echo admin_url('dps_reunioes/responder/' . (int) $r['id'] . '/recusado'); ?>" class="btn btn-danger">Recusar</a>
      <?php } ?>
    </div></div></div>

    <div class="col-md-5"><div class="panel_s"><div class="panel-body">
      <h4 class="no-margin">Depois da reunião</h4>
      <hr>
      <p class="text-muted" style="font-size:13px;">
        Nem o Jitsi nem o Meet dizem ao CRM quem entrou e quanto tempo ficou.
        Registe aqui — é o que alimenta os números do acompanhamento.
      </p>
      <?php echo form_open(admin_url('dps_reunioes/fechar/' . (int) $r['id'])); ?>
        <label class="control-label">O que aconteceu</label>
        <select name="estado" class="form-control">
          <option value="realizada" <?php echo $r['estado']==='realizada'?'selected':''; ?>>Realizada</option>
          <option value="nao_compareceu" <?php echo $r['estado']==='nao_compareceu'?'selected':''; ?>>Não compareceu</option>
          <option value="cancelada" <?php echo $r['estado']==='cancelada'?'selected':''; ?>>Cancelada</option>
        </select>
        <label class="control-label mtop15">Duração real (minutos)</label>
        <input type="number" name="duracao_real_min" class="form-control" min="0"
               value="<?php echo $r['duracao_real_min'] ?? ''; ?>">
        <label class="control-label mtop15">Notas</label>
        <textarea name="notas" class="form-control" rows="4"><?php echo html_escape((string) $r['notas']); ?></textarea>
        <button type="submit" class="btn btn-info mtop15">Guardar</button>
      <?php echo form_close(); ?>

      <?php if (!empty($r['followup_task_id'])) { ?>
        <hr>
        <a href="<?php echo admin_url('tasks/view/' . (int) $r['followup_task_id']); ?>">
          <i class="fa fa-check-square-o"></i> Tarefa de follow-up #<?php echo (int) $r['followup_task_id']; ?>
        </a>
      <?php } ?>
    </div></div></div>
  </div>
</div></div>
<?php init_tail(); ?>
</body></html>
