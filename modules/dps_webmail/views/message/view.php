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
              <li style="<?php echo ($folder === $fkey) ? 'background:#f5f5f5;' : ''; ?>">
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

      <!-- ÁREA PRINCIPAL: Mensagem -->
      <div class="col-md-10 col-sm-9">
        <div class="panel_s">
          <div class="panel-body">

            <!-- Botões de acção -->
            <div style="margin-bottom:16px; display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
              <a href="<?php echo admin_url('dps_webmail/index/' . urlencode($folder)); ?>"
                 class="btn btn-default btn-sm">
                <i class="fa fa-arrow-left"></i> Voltar
              </a>
              <a href="<?php echo admin_url('dps_webmail/reply/' . urlencode($folder) . '/' . $message['uid']); ?>"
                 class="btn btn-primary btn-sm">
                <i class="fa fa-reply"></i> Responder
              </a>
              <a href="<?php echo admin_url('dps_webmail/reply/' . urlencode($folder) . '/' . $message['uid'] . '?all=1'); ?>"
                 class="btn btn-default btn-sm">
                <i class="fa fa-reply-all"></i> Responder a Todos
              </a>
              <a href="<?php echo admin_url('dps_webmail/forward/' . urlencode($folder) . '/' . $message['uid']); ?>"
                 class="btn btn-default btn-sm">
                <i class="fa fa-share"></i> Reencaminhar
              </a>
              <div style="flex:1;"></div>
              <?php if ($folder !== 'Trash'): ?>
              <button class="btn btn-default btn-sm webmail-msg-action" title="Mover para Lixo"
                      data-action="delete" data-uid="<?php echo $message['uid']; ?>" data-folder="<?php echo htmlspecialchars($folder); ?>"
                      data-redirect="<?php echo admin_url('dps_webmail/index/' . urlencode($folder)); ?>">
                <i class="fa fa-trash"></i> Apagar
              </button>
              <?php else: ?>
              <button class="btn btn-danger btn-sm webmail-msg-action" title="Apagar permanentemente"
                      data-action="delete_permanent" data-uid="<?php echo $message['uid']; ?>" data-folder="<?php echo htmlspecialchars($folder); ?>"
                      data-redirect="<?php echo admin_url('dps_webmail/index/Trash'); ?>">
                <i class="fa fa-times"></i> Apagar Permanentemente
              </button>
              <?php endif; ?>
              <?php if ($folder === 'INBOX'): ?>
              <button class="btn btn-default btn-sm webmail-msg-action" title="Arquivar"
                      data-action="archive" data-uid="<?php echo $message['uid']; ?>" data-folder="<?php echo htmlspecialchars($folder); ?>"
                      data-redirect="<?php echo admin_url('dps_webmail/index/INBOX'); ?>">
                <i class="fa fa-archive"></i> Arquivar
              </button>
              <?php endif; ?>
            </div>

            <!-- Cabeçalho da mensagem -->
            <div style="background:#f9f9f9; border:1px solid #eee; border-radius:4px; padding:16px; margin-bottom:16px;">
              <h4 style="margin:0 0 12px; font-weight:600;">
                <?php echo htmlspecialchars($message['subject']); ?>
              </h4>
              <table style="font-size:13px; line-height:1.8; width:100%;">
                <tr>
                  <td style="color:#888; width:60px; vertical-align:top;"><strong>De:</strong></td>
                  <td>
                    <?php if ($message['from_name'] && $message['from_name'] !== $message['from_email']): ?>
                      <strong><?php echo htmlspecialchars($message['from_name']); ?></strong>
                      &lt;<?php echo htmlspecialchars($message['from_email']); ?>&gt;
                    <?php else: ?>
                      <?php echo htmlspecialchars($message['from_email']); ?>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php if ($message['to']): ?>
                <tr>
                  <td style="color:#888; vertical-align:top;"><strong>Para:</strong></td>
                  <td><?php echo htmlspecialchars($message['to']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($message['cc']): ?>
                <tr>
                  <td style="color:#888; vertical-align:top;"><strong>CC:</strong></td>
                  <td><?php echo htmlspecialchars($message['cc']); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                  <td style="color:#888;"><strong>Data:</strong></td>
                  <td><?php echo htmlspecialchars($message['date']); ?></td>
                </tr>
              </table>
            </div>

            <!-- Corpo da mensagem -->
            <div id="message-body" style="padding:8px 0; line-height:1.6; overflow-x:auto;">
              <?php echo $message['body']; ?>
            </div>

            <!-- Anexos -->
            <?php if (!empty($message['attachments'])): ?>
            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #eee;">
              <h6 style="color:#888; margin-bottom:10px;">
                <i class="fa fa-paperclip"></i> Anexos (<?php echo count($message['attachments']); ?>)
              </h6>
              <div style="display:flex; flex-wrap:wrap; gap:8px;">
                <?php foreach ($message['attachments'] as $att): ?>
                <div style="background:#f5f5f5; border:1px solid #ddd; border-radius:4px; padding:8px 12px; font-size:13px;">
                  <i class="fa fa-file-o"></i>
                  <?php echo htmlspecialchars($att['name']); ?>
                  <small class="text-muted">(<?php echo round($att['size'] / 1024, 1); ?> KB)</small>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>

          </div>
        </div>

        <!-- Resposta rápida -->
        <div class="panel_s">
          <div class="panel-body">
            <h6 style="margin-bottom:12px;"><i class="fa fa-reply"></i> Resposta Rápida</h6>
            <?php echo form_open(admin_url('dps_webmail/reply/' . urlencode($folder) . '/' . $message['uid']), ['id' => 'quick-reply-form']); ?>
            <input type="hidden" name="to" value="<?php echo htmlspecialchars($message['from_email']); ?>">
            <input type="hidden" name="cc" value="">
            <input type="hidden" name="bcc" value="">
            <input type="hidden" name="subject" value="Re: <?php echo htmlspecialchars($message['subject']); ?>">
            <div class="form-group">
              <textarea name="body" class="form-control" rows="4"
                placeholder="Escreva a sua resposta aqui..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">
              <i class="fa fa-paper-plane"></i> Enviar Resposta
            </button>
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
$(document).ready(function() {
    // Acções sobre a mensagem (apagar, arquivar)
    $(document).on('click', '.webmail-msg-action', function(e) {
        e.preventDefault();
        var btn      = $(this);
        var action   = btn.data('action');
        var uid      = btn.data('uid');
        var folder   = btn.data('folder');
        var redirect = btn.data('redirect');

        var labels = {
            'delete':           'Mover esta mensagem para o Lixo?',
            'delete_permanent': 'Apagar permanentemente? Esta acção não pode ser desfeita.',
            'archive':          'Arquivar esta mensagem?'
        };
        if (!confirm(labels[action] || 'Confirmar?')) return;

        btn.prop('disabled', true).prepend('<i class="fa fa-spinner fa-spin"></i> ');

        $.post('<?php echo admin_url('dps_webmail/action'); ?>', {
            action: action,
            uid:    uid,
            folder: folder
        }, function(resp) {
            if (resp.success) {
                window.location.href = redirect;
            } else {
                btn.prop('disabled', false);
                alert('Erro ao executar a acção.');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false);
            alert('Erro de comunicação com o servidor.');
        });
    });

    // Isolar estilos do corpo do email para não afectar o CRM
    var body = document.getElementById('message-body');
    if (body) {
        // Garantir que links abrem em nova janela
        var links = body.querySelectorAll('a');
        links.forEach(function(link) {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        });
    }
});
</script>
