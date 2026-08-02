<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper"><div class="content">
  <div class="row">

    <div class="col-md-7">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">A minha conta Google</h4>
        <hr>

        <?php if (!$configurado) { ?>
          <div class="alert alert-warning">
            A ligação ao Google ainda não está configurada.
            <?php echo is_admin()
                ? 'Preencha as credenciais no quadro ao lado.'
                : 'Fale com a direção — falta um passo que só ela pode dar.'; ?>
          </div>
        <?php } elseif (empty($conta)) { ?>
          <p>
            Ligue a sua conta Google e as reuniões que marcar passam a aparecer
            no seu Google Calendar — criadas, actualizadas e apagadas pelo CRM.
            O cliente entra como convidado e recebe o convite dele.
          </p>
          <a href="<?php echo admin_url('dps_google/ligar'); ?>" class="btn btn-info">
            <i class="fa fa-google"></i> Ligar a minha conta Google
          </a>
          <p class="text-muted mtop15" style="font-size:13px;">
            O Google vai mostrar um ecrã a dizer que <strong>não verificou esta aplicação</strong>.
            É normal e é o nosso CRM: carregue em <em>Avançadas</em> e depois em
            <em>Aceder a crm.grupo-dps.com</em>.
          </p>
        <?php } else { ?>
          <p>
            Ligada a <strong><?php echo html_escape((string) $conta['email']); ?></strong>
            desde <?php echo _dt($conta['date_created']); ?>.
          </p>

          <?php if (!empty($conta['ultimo_erro'])) { ?>
            <div class="alert alert-danger">
              <strong>A ligação deixou de funcionar.</strong>
              Volte a ligar a conta.
              <br><small style="opacity:.75;"><?php echo html_escape(substr($conta['ultimo_erro'], 0, 200)); ?></small>
            </div>
            <a href="<?php echo admin_url('dps_google/ligar'); ?>" class="btn btn-info">
              <i class="fa fa-refresh"></i> Voltar a ligar
            </a>
          <?php } else { ?>
            <span class="label label-success">Activa</span>
          <?php } ?>

          <hr>
          <a href="<?php echo admin_url('dps_google/desligar'); ?>" class="btn btn-default btn-sm"
             onclick="return confirm('Desligar? As reuniões deixam de ir para o seu Google Calendar.');">
            Desligar
          </a>
        <?php } ?>
      </div></div>

      <?php if (is_admin() && !empty($todas)) { ?>
        <div class="panel_s"><div class="panel-body">
          <h4 class="no-margin">Quem já ligou</h4>
          <hr>
          <table class="table table-striped">
            <thead><tr><th>Comercial</th><th>Conta Google</th><th>Desde</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($todas as $t) { ?>
              <tr>
                <td><?php echo html_escape((string) $t['nome']); ?></td>
                <td><?php echo html_escape((string) $t['email']); ?></td>
                <td><?php echo _dt($t['date_created']); ?></td>
                <td>
                  <?php if (!empty($t['ultimo_erro'])) { ?>
                    <span class="label label-danger">Precisa de religar</span>
                  <?php } else { ?>
                    <span class="label label-success">Activa</span>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div></div>
      <?php } ?>
    </div>

    <?php if (is_admin()) { ?>
    <div class="col-md-5">
      <div class="panel_s"><div class="panel-body">
        <h4 class="no-margin">Credenciais da aplicação</h4>
        <hr>
        <p class="text-muted" style="font-size:13px;">
          Criadas uma vez em <code>console.cloud.google.com</code>, com a
          <strong>Google Calendar API</strong> activada. Servem toda a equipa —
          cada pessoa liga depois a conta dela.
        </p>

        <label class="control-label">Endereço de retorno</label>
        <p class="text-muted" style="font-size:13px;margin-bottom:4px;">
          Cole isto <strong>tal e qual</strong> nos "URIs de redireccionamento autorizados".
          Um caracter diferente e o Google recusa com <em>redirect_uri_mismatch</em>.
        </p>
        <input type="text" class="form-control" readonly
               onclick="this.select();"
               value="<?php echo html_escape($redirect_uri); ?>">

        <hr>
        <?php echo form_open(admin_url('dps_google/guardar_credenciais')); ?>
          <label class="control-label">Client ID</label>
          <input type="text" name="client_id" class="form-control"
                 value="<?php echo html_escape((string) get_option('dps_google_client_id')); ?>"
                 placeholder="000000000000-xxxxxxxx.apps.googleusercontent.com">

          <label class="control-label mtop15">Client Secret</label>
          <input type="text" name="client_secret" class="form-control"
                 value="<?php echo html_escape((string) get_option('dps_google_client_secret')); ?>"
                 placeholder="GOCSPX-...">

          <button type="submit" class="btn btn-info mtop15">Guardar</button>
        <?php echo form_close(); ?>

        <hr>
        <p class="text-muted" style="font-size:13px;">
          <strong>Publique a aplicação</strong> no ecrã de consentimento
          (<em>Publicar aplicação</em>). Enquanto estiver em <em>Testing</em>, o
          Google corta as autorizações ao fim de <strong>7 dias</strong> e a
          sincronização pára sozinha, a toda a gente, sem aviso.
        </p>
      </div></div>
    </div>
    <?php } ?>

  </div>
</div></div>
<?php init_tail(); ?>
</body></html>
