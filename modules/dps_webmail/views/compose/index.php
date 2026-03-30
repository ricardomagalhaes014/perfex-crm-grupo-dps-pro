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
              <a href="<?php echo admin_url('dps_webmail/compose'); ?>" class="btn btn-primary btn-block" style="background:#1a73e8;">
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
              <li>
                <a href="<?php echo admin_url('dps_webmail/settings'); ?>"
                   style="display:block; padding:8px 16px; color:#888; text-decoration:none;">
                  <i class="fa fa-cog" style="width:18px;"></i> Configurações
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- ÁREA PRINCIPAL: Compor -->
      <div class="col-md-10 col-sm-9">
        <div class="panel_s">
          <div class="panel-body">

            <h4 style="margin-bottom:20px; font-weight:600;">
              <i class="fa fa-<?php echo $forward ? 'share' : ($reply_to ? 'reply' : 'pencil'); ?>"></i>
              <?php echo $forward ? 'Reencaminhar Email' : ($reply_to ? 'Responder' : 'Novo Email'); ?>
            </h4>

            <?php
            $form_action = admin_url('dps_webmail/compose');
            if ($reply_to) {
                $form_action = admin_url('dps_webmail/reply/' . urlencode($folder ?? 'INBOX') . '/' . $reply_to['uid']);
            } elseif ($forward) {
                $form_action = admin_url('dps_webmail/forward/' . urlencode($folder ?? 'INBOX') . '/' . ($reply_to['uid'] ?? 0));
            }
            ?>
            <?php echo form_open_multipart($form_action, ['id' => 'compose-form']); ?>

            <!-- De (apenas informativo) -->
            <div class="form-group">
              <label class="control-label" style="width:60px; display:inline-block; color:#888;">De:</label>
              <span class="text-muted" style="font-size:13px;">
                <i class="fa fa-user-circle"></i>
                <?php echo htmlspecialchars($config['email']); ?>
              </span>
            </div>

            <!-- Para -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon" style="width:60px; text-align:left; color:#555;">Para:</span>
                <input type="text" name="to" class="form-control" required
                  placeholder="destinatario@exemplo.com"
                  value="<?php echo htmlspecialchars($prefill['to']); ?>">
              </div>
            </div>

            <!-- CC (colapsável) -->
            <div id="cc-row" class="form-group" style="display:none;">
              <div class="input-group">
                <span class="input-group-addon" style="width:60px; text-align:left; color:#555;">CC:</span>
                <input type="text" name="cc" class="form-control" placeholder="cc@exemplo.com">
              </div>
            </div>

            <!-- BCC (colapsável) -->
            <div id="bcc-row" class="form-group" style="display:none;">
              <div class="input-group">
                <span class="input-group-addon" style="width:60px; text-align:left; color:#555;">BCC:</span>
                <input type="text" name="bcc" class="form-control" placeholder="bcc@exemplo.com">
              </div>
            </div>

            <!-- Botões CC/BCC -->
            <div style="margin-bottom:10px; font-size:12px;">
              <a href="#" onclick="toggleField('cc-row'); return false;" class="text-muted" style="margin-right:10px;">+ CC</a>
              <a href="#" onclick="toggleField('bcc-row'); return false;" class="text-muted">+ BCC</a>
            </div>

            <!-- Assunto -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon" style="width:60px; text-align:left; color:#555;">Assunto:</span>
                <input type="text" name="subject" class="form-control" required
                  placeholder="Assunto do email"
                  value="<?php echo htmlspecialchars($prefill['subject']); ?>">
              </div>
            </div>

            <!-- Corpo da mensagem -->
            <div class="form-group">
              <textarea name="body" id="compose-body" class="form-control"
                rows="14" style="resize:vertical; font-family:inherit;"><?php echo $prefill['body']; ?></textarea>
            </div>

            <!-- Anexos -->
            <div class="form-group">
              <label class="control-label" style="font-size:13px; color:#888;">
                <i class="fa fa-paperclip"></i> Anexos
              </label>
              <input type="file" name="attachments[]" multiple class="form-control" style="font-size:13px;">
              <small class="text-muted">Pode seleccionar múltiplos ficheiros.</small>
            </div>

            <!-- Botões de acção -->
            <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
              <button type="submit" class="btn btn-primary" id="btn-send">
                <i class="fa fa-paper-plane"></i> Enviar
              </button>
              <a href="<?php echo admin_url('dps_webmail'); ?>" class="btn btn-default">
                Cancelar
              </a>
            </div>

            <?php echo form_close(); ?>

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
function toggleField(id) {
    var el = document.getElementById(id);
    if (el.style.display === 'none') {
        el.style.display = '';
        el.querySelector('input').focus();
    } else {
        el.style.display = 'none';
        el.querySelector('input').value = '';
    }
}

$(document).ready(function() {
    // Mostrar CC/BCC se já têm valor
    <?php if (!empty($prefill['cc'])): ?>
    document.getElementById('cc-row').style.display = '';
    <?php endif; ?>

    // Envio com indicador de loading
    $('#compose-form').on('submit', function() {
        var btn = $('#btn-send');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> A enviar...');
    });

    // Auto-resize do textarea
    var textarea = document.getElementById('compose-body');
    if (textarea) {
        textarea.style.minHeight = '300px';
    }
});
</script>
