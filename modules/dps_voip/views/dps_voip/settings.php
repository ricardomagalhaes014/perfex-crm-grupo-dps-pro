<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-heading">
            <h4 class="panel-title">
              <i class="fa fa-cog"></i> VoIP Central — Configurações Twilio
            </h4>
          </div>
          <div class="panel-body">
            <?= form_open(admin_url('dps_voip/settings')) ?>

            <div class="form-group">
              <label>Account SID <span class="text-danger">*</span></label>
              <input type="text" name="account_sid" class="form-control"
                value="<?= htmlspecialchars($account_sid) ?>" placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
            </div>

            <div class="form-group">
              <label>Auth Token <span class="text-danger">*</span></label>
              <input type="text" name="auth_token" class="form-control"
                value="<?= htmlspecialchars($auth_token) ?>" placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
            </div>

            <div class="form-group">
              <label>TwiML App SID <span class="text-danger">*</span></label>
              <input type="text" name="app_sid" class="form-control"
                value="<?= htmlspecialchars($app_sid) ?>" placeholder="APxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
              <small class="text-muted">
                Crie uma TwiML App em
                <a href="https://console.twilio.com/us1/develop/voice/manage/twiml-apps" target="_blank">
                  Twilio Console → Voice → TwiML Apps
                </a>
                com o Voice Request URL: <code><?= site_url('admin/dps_voip/twiml_outbound') ?></code>
              </small>
            </div>

            <div class="form-group">
              <label>Timeout de chamada (segundos)</label>
              <input type="number" name="timeout" class="form-control" value="<?= (int)$timeout ?>" min="10" max="120" style="width:100px;">
            </div>

            <div class="form-group">
              <label>
                <input type="checkbox" name="record_calls" value="1" <?= $record == '1' ? 'checked' : '' ?>>
                Gravar chamadas automaticamente
              </label>
            </div>

            <hr>
            <h5>Webhooks para configurar no Twilio</h5>
            <div class="table-responsive">
              <table class="table table-bordered">
                <tr>
                  <th>Chamadas de Entrada (Voice URL)</th>
                  <td><code><?= site_url('admin/dps_voip/twiml_inbound') ?></code></td>
                </tr>
                <tr>
                  <th>TwiML App Voice URL (Saída)</th>
                  <td><code><?= site_url('admin/dps_voip/twiml_outbound') ?></code></td>
                </tr>
                <tr>
                  <th>Status Callback</th>
                  <td><code><?= site_url('admin/dps_voip/call_status') ?></code></td>
                </tr>
              </table>
            </div>

            <div class="tw-flex tw-gap-2">
              <button type="submit" class="btn btn-success">
                <i class="fa fa-save"></i> Guardar Configurações
              </button>
              <a href="<?= admin_url('dps_voip') ?>" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> Voltar
              </a>
            </div>

            <?= form_close() ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
