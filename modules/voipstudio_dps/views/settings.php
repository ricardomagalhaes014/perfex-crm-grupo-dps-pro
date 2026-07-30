<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-5">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin"><i class="fa fa-phone"></i> VoIPstudio — Definições</h4>
            <hr/>
            <?php echo form_open(admin_url('voipstudio_dps')); ?>
              <div class="form-group">
                <label>Email da conta VoIPstudio</label>
                <input type="email" name="vs_email" class="form-control"
                       value="<?php echo html_escape(get_option('voipstudio_dps_email')); ?>" />
              </div>
              <div class="form-group">
                <label>Password</label>
                <input type="password" name="vs_password" class="form-control" autocomplete="new-password"
                       placeholder="<?php echo get_option('voipstudio_dps_password') ? '••••••••  (guardada — deixar vazio para manter)' : 'Password VoIPstudio'; ?>" />
              </div>
              <div class="form-group">
                <label>Caller ID (opcional, formato E.164 — ex. 351220000000)</label>
                <input type="text" name="vs_caller_id" class="form-control"
                       value="<?php echo html_escape(get_option('voipstudio_dps_caller_id')); ?>" />
              </div>
              <button type="submit" class="btn btn-primary">Guardar</button>
              <a href="<?php echo admin_url('voipstudio_dps/test'); ?>" class="btn btn-default">Testar ligação</a>
              <a href="<?php echo admin_url('voipstudio_dps/sync'); ?>" class="btn btn-default">Sincronizar chamadas</a>
            <?php echo form_close(); ?>

            <?php if (get_option('voipstudio_dps_2fa_pendente') === '1' || !get_option('voipstudio_dps_token')) { ?>
              <hr/>
              <div class="alert alert-warning" style="margin-bottom:10px;">
                <strong>Autenticação em dois passos (2FA)</strong><br>
                O VoIPstudio enviou um código para o email da conta. Introduza aqui o código
                do email <u>mais recente</u> — depois disso o CRM guarda a sessão e
                <strong>não voltam a chover emails de código</strong>.
              </div>
              <?php echo form_open(admin_url('voipstudio_dps/confirmar_2fa'), ['style' => 'display:flex;gap:8px;']); ?>
                <input type="text" name="vs_2fa_code" class="form-control" style="max-width:180px;"
                       placeholder="Código 2FA" autocomplete="one-time-code" />
                <button type="submit" class="btn btn-success">Confirmar código</button>
              <?php echo form_close(); ?>
            <?php } ?>
            <hr/>
            <p class="text-muted">
              Como funciona: junto a cada número de telefone no CRM aparece um botão
              <i class="fa fa-phone" style="color:#28a745"></i>. Ao clicar, o telefone/softphone
              VoIPstudio desta conta toca primeiro e depois liga ao contacto.
              As chamadas são registadas abaixo e associadas às leads/clientes pelo número.
            </p>
          </div>
        </div>
      </div>
      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Registo de chamadas</h4>
            <hr/>
            <table class="table table-striped">
              <thead>
                <tr><th>Data</th><th>Direção</th><th>De</th><th>Para</th><th>Duração</th><th>Estado</th><th>Associada a</th></tr>
              </thead>
              <tbody>
              <?php if (empty($calls)) { ?>
                <tr><td colspan="7" class="text-muted">Sem chamadas registadas ainda.</td></tr>
              <?php } else { foreach ($calls as $c) { ?>
                <tr>
                  <td><?php echo html_escape($c->calldate); ?></td>
                  <td><?php echo $c->direction === 'inbound' ? '<span class="label label-info">Recebida</span>' : '<span class="label label-success">Efetuada</span>'; ?></td>
                  <td><?php echo html_escape($c->src); ?></td>
                  <td><?php echo html_escape($c->dst); ?></td>
                  <td><?php echo (int) $c->duration; ?>s</td>
                  <td><?php echo html_escape((string) $c->disposition); ?></td>
                  <td>
                    <?php if ($c->rel_type === 'lead' && $c->rel_id) { ?>
                      <a href="<?php echo admin_url('leads/index/' . $c->rel_id); ?>">Lead #<?php echo (int) $c->rel_id; ?></a>
                    <?php } elseif ($c->rel_type === 'customer' && $c->rel_id) { ?>
                      <a href="<?php echo admin_url('clients/client/' . $c->rel_id); ?>">Cliente #<?php echo (int) $c->rel_id; ?></a>
                    <?php } else { echo '—'; } ?>
                  </td>
                </tr>
              <?php } } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
