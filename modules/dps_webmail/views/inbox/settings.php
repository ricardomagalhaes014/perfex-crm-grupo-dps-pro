<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">
<div class="row">
  <div class="col-md-12">

    <div class="row">

      <!-- SIDEBAR -->
      <div class="col-md-2 col-sm-3">
        <div class="panel_s">
          <div class="panel-body" style="padding:10px 0;">
            <div style="padding:0 12px 12px;">
              <a href="<?php echo admin_url('dps_webmail/compose'); ?>" class="btn btn-primary btn-block">
                <i class="fa fa-pencil"></i> Escrever
              </a>
            </div>
            <?php
            $folder_map = [
              'INBOX'   => ['label' => 'Caixa de Entrada', 'icon' => 'fa-inbox'],
              'Sent'    => ['label' => 'Enviados',          'icon' => 'fa-paper-plane'],
              'Drafts'  => ['label' => 'Rascunhos',         'icon' => 'fa-pencil'],
              'Trash'   => ['label' => 'Lixo',              'icon' => 'fa-trash'],
              'Archive' => ['label' => 'Arquivo',           'icon' => 'fa-archive'],
              'Spam'    => ['label' => 'Spam',              'icon' => 'fa-ban'],
            ];
            ?>
            <ul class="list-unstyled" style="margin:0;">
              <?php foreach ($folder_map as $fkey => $finfo): ?>
              <li>
                <a href="<?php echo admin_url('dps_webmail/index/' . $fkey); ?>"
                   style="display:block; padding:8px 16px; color:inherit; text-decoration:none;">
                  <i class="fa <?php echo $finfo['icon']; ?>" style="width:18px;"></i>
                  <?php echo $finfo['label']; ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>
            <hr style="margin:8px 0;">
            <ul class="list-unstyled" style="margin:0;">
              <li style="background:#f5f5f5;">
                <a href="<?php echo admin_url('dps_webmail/settings'); ?>"
                   style="display:block; padding:8px 16px; color:inherit; text-decoration:none; font-weight:bold;">
                  <i class="fa fa-cog" style="width:18px;"></i> Configurações
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- ÁREA PRINCIPAL: Configurações -->
      <div class="col-md-10 col-sm-9">
        <div class="panel_s">
          <div class="panel-body">

            <h4 class="tw-font-semibold tw-mb-4">
              <i class="fa fa-cog"></i> Configurar Conta de Email
            </h4>

            <?php if (!$config): ?>
            <div class="alert alert-info">
              <i class="fa fa-info-circle"></i>
              <strong>Primeira configuração</strong> — Introduza o seu email @grupo-dps.com e a palavra-passe
              para aceder à caixa de correio directamente no CRM.
            </div>
            <?php endif; ?>

            <?php echo form_open(admin_url('dps_webmail/settings'), ['id' => 'form-webmail-settings']); ?>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">
                    <i class="fa fa-envelope"></i> Endereço de Email <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <input type="email" name="email" class="form-control" required
                      placeholder="nome.apelido@grupo-dps.com"
                      value="<?php echo $config ? htmlspecialchars($config['email']) : htmlspecialchars($suggested_email); ?>">
                    <span class="input-group-addon">@grupo-dps.com</span>
                  </div>
                  <small class="text-muted">Use o seu email @grupo-dps.com criado no Hostinger.</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="control-label">
                    <i class="fa fa-lock"></i> Palavra-passe <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <input type="password" name="password" id="webmail-pass" class="form-control" required
                      placeholder="<?php echo $config ? '••••••••••••' : 'Palavra-passe do email'; ?>">
                    <span class="input-group-addon" style="cursor:pointer;" onclick="togglePass()">
                      <i class="fa fa-eye" id="pass-eye"></i>
                    </span>
                  </div>
                  <?php if ($config): ?>
                  <small class="text-muted">Deixe em branco para manter a palavra-passe actual.</small>
                  <?php else: ?>
                  <small class="text-muted">A mesma palavra-passe que criou no Hostinger para este email.</small>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Configurações avançadas (colapsável) -->
            <div class="tw-mt-2">
              <a href="#advanced-settings" data-toggle="collapse" class="text-muted" style="font-size:13px;">
                <i class="fa fa-caret-right"></i> Configurações avançadas (IMAP/SMTP)
              </a>
              <div id="advanced-settings" class="collapse">
                <div class="row tw-mt-3">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label class="control-label">Servidor IMAP</label>
                      <input type="text" name="imap_host" class="form-control"
                        value="<?php echo $config ? htmlspecialchars($config['imap_host']) : 'imap.hostinger.com'; ?>">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label class="control-label">Porta IMAP</label>
                      <input type="number" name="imap_port" class="form-control"
                        value="<?php echo $config ? (int)$config['imap_port'] : 993; ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label class="control-label">Servidor SMTP</label>
                      <input type="text" name="smtp_host" class="form-control"
                        value="<?php echo $config ? htmlspecialchars($config['smtp_host']) : 'smtp.hostinger.com'; ?>">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label class="control-label">Porta SMTP</label>
                      <input type="number" name="smtp_port" class="form-control"
                        value="<?php echo $config ? (int)$config['smtp_port'] : 587; ?>">
                    </div>
                  </div>
                </div>
                <div class="alert alert-info" style="font-size:12px;">
                  <strong>Configurações padrão Hostinger:</strong><br>
                  IMAP: imap.hostinger.com:993 (SSL)<br>
                  SMTP: smtp.hostinger.com:587 (STARTTLS)
                </div>
              </div>
            </div>

            <div class="tw-mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-save"></i> Guardar Configurações
              </button>
              <?php if ($config): ?>
              <a href="<?php echo admin_url('dps_webmail'); ?>" class="btn btn-default tw-ml-2">Cancelar</a>
              <?php endif; ?>
            </div>

            <?php echo form_close(); ?>

          </div>
        </div>

        <!-- Informação de ajuda -->
        <div class="panel_s">
          <div class="panel-body">
            <h5><i class="fa fa-question-circle text-info"></i> Como configurar</h5>
            <ol style="padding-left:20px; line-height:1.8;">
              <li>Introduza o seu email <strong>@grupo-dps.com</strong> (ex: <code>joao.silva@grupo-dps.com</code>)</li>
              <li>Introduza a palavra-passe que foi criada no Hostinger para esse email</li>
              <li>Clique em <strong>Guardar Configurações</strong></li>
              <li>Será redirecionado para a sua caixa de entrada</li>
            </ol>
            <p class="text-muted" style="font-size:12px;">
              <i class="fa fa-shield"></i> A sua palavra-passe é guardada de forma encriptada e nunca é partilhada.
            </p>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>
</div>
</div>
<?php init_tail(); ?>
<script>
function togglePass() {
    var input = document.getElementById('webmail-pass');
    var eye   = document.getElementById('pass-eye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'fa fa-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'fa fa-eye';
    }
}

// Se já existe config e o campo password está vazio, não é obrigatório
<?php if ($config): ?>
document.getElementById('webmail-pass').removeAttribute('required');
<?php endif; ?>
</script>
