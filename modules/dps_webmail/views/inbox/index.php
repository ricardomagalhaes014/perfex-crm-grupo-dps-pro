<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
<div class="content">
<div class="row">
  <div class="col-md-12">

    <!-- Layout de duas colunas: sidebar + mensagens -->
    <div class="row">

      <!-- SIDEBAR: Pastas -->
      <div class="col-md-2 col-sm-3">
        <div class="panel_s">
          <div class="panel-body" style="padding:10px 0;">

            <!-- Botão Escrever -->
            <div style="padding:0 12px 12px;">
              <a href="<?php echo admin_url('dps_webmail/compose'); ?>" class="btn btn-primary btn-block">
                <i class="fa fa-pencil"></i> Escrever
              </a>
            </div>

            <!-- Lista de pastas -->
            <ul class="list-unstyled" style="margin:0;">
              <?php
              $folder_map = [
                'INBOX'   => ['label' => 'Caixa de Entrada', 'icon' => 'fa-inbox',       'color' => ''],
                'Sent'    => ['label' => 'Enviados',          'icon' => 'fa-paper-plane', 'color' => ''],
                'Drafts'  => ['label' => 'Rascunhos',         'icon' => 'fa-pencil',      'color' => ''],
                'Trash'   => ['label' => 'Lixo',              'icon' => 'fa-trash',       'color' => 'text-danger'],
                'Archive' => ['label' => 'Arquivo',           'icon' => 'fa-archive',     'color' => ''],
                'Spam'    => ['label' => 'Spam',              'icon' => 'fa-ban',         'color' => 'text-warning'],
              ];
              foreach ($folder_map as $fkey => $finfo):
                $active = ($folder === $fkey) ? 'background:#f5f5f5; font-weight:bold;' : '';
              ?>
              <li style="<?php echo $active; ?>">
                <a href="<?php echo admin_url('dps_webmail/index/' . $fkey); ?>"
                   style="display:block; padding:8px 16px; color:inherit; text-decoration:none;">
                  <i class="fa <?php echo $finfo['icon']; ?> <?php echo $finfo['color']; ?>" style="width:18px;"></i>
                  <?php echo $finfo['label']; ?>
                  <?php if ($fkey === 'INBOX' && !empty($unread_inbox) && $unread_inbox > 0): ?>
                    <span class="badge" style="background:#e74c3c; color:#fff; float:right;"><?php echo $unread_inbox; ?></span>
                  <?php endif; ?>
                </a>
              </li>
              <?php endforeach; ?>
            </ul>

            <hr style="margin:8px 0;">

            <!-- Configurações -->
            <ul class="list-unstyled" style="margin:0;">
              <li>
                <a href="<?php echo admin_url('dps_webmail/settings'); ?>"
                   style="display:block; padding:8px 16px; color:#888; text-decoration:none;">
                  <i class="fa fa-cog" style="width:18px;"></i> Configurações
                </a>
              </li>
            </ul>

            <!-- Info da conta -->
            <div style="padding:10px 12px; border-top:1px solid #eee; margin-top:8px;">
              <small class="text-muted" style="word-break:break-all;">
                <i class="fa fa-user-circle"></i>
                <?php echo htmlspecialchars($config['email']); ?>
              </small>
            </div>

          </div>
        </div>
      </div>

      <!-- ÁREA PRINCIPAL: Lista de mensagens -->
      <div class="col-md-10 col-sm-9">
        <div class="panel_s">
          <div class="panel-body" style="padding:0;">

            <!-- Cabeçalho da pasta -->
            <div style="padding:14px 20px; border-bottom:1px solid #eee; display:flex; align-items:center; justify-content:space-between;">
              <div>
                <h4 style="margin:0; font-weight:600;">
                  <i class="fa <?php echo $folder_map[$folder]['icon'] ?? 'fa-folder'; ?>"></i>
                  <?php echo $folder_map[$folder]['label'] ?? htmlspecialchars($folder); ?>
                  <?php if ($total > 0): ?>
                    <small class="text-muted" style="font-weight:normal; font-size:13px;">
                      (<?php echo $total; ?> mensagens)
                    </small>
                  <?php endif; ?>
                </h4>
              </div>
              <div>
                <button class="btn btn-default btn-xs" onclick="location.reload();" title="Actualizar">
                  <i class="fa fa-refresh"></i> Actualizar
                </button>
              </div>
            </div>

            <?php if (empty($error) && !empty($messages)): ?>
            <!--
              Barra das acções em lote. Só aparece com alguma coisa escolhida:
              uma barra de botões sempre visível e quase sempre inútil só rouba
              espaço à lista.
            -->
            <div id="wm-barra" style="display:none; padding:10px 16px; border-bottom:1px solid #eee; background:#f7fbff; align-items:center; gap:8px; flex-wrap:wrap;">
              <strong id="wm-conta" style="margin-right:4px;"></strong>
              <button class="btn btn-default btn-xs wm-lote" data-accao="mark_read">
                <i class="fa fa-envelope-open-o"></i> Marcar como lido
              </button>
              <button class="btn btn-default btn-xs wm-lote" data-accao="mark_unread">
                <i class="fa fa-envelope"></i> Marcar como não lido
              </button>
              <?php if ($folder === 'INBOX'): ?>
              <button class="btn btn-default btn-xs wm-lote" data-accao="archive">
                <i class="fa fa-archive"></i> Arquivar
              </button>
              <?php endif; ?>
              <?php if ($folder !== 'Trash'): ?>
              <button class="btn btn-danger btn-xs wm-lote" data-accao="delete">
                <i class="fa fa-trash"></i> Apagar
              </button>
              <?php else: ?>
              <button class="btn btn-danger btn-xs wm-lote" data-accao="delete_permanent">
                <i class="fa fa-times"></i> Apagar definitivamente
              </button>
              <?php endif; ?>
              <div style="flex:1;"></div>
              <a href="#" id="wm-limpar" style="font-size:12px; color:#888;">Limpar seleção</a>
            </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin:15px;">
              <i class="fa fa-exclamation-triangle"></i>
              Erro ao ligar ao servidor de email: <?php echo htmlspecialchars($error); ?>
              <br><small>Verifique as <a href="<?php echo admin_url('dps_webmail/settings'); ?>">configurações da conta</a>.</small>
            </div>
            <?php elseif (empty($messages)): ?>
            <div class="text-center" style="padding:60px 20px;">
              <i class="fa fa-inbox" style="font-size:48px; color:#ddd;"></i>
              <p class="text-muted" style="margin-top:15px; font-size:15px;">Sem mensagens nesta pasta</p>
            </div>
            <?php else: ?>

            <!-- Tabela de mensagens -->
            <table class="table table-hover" style="margin:0;" id="webmail-list">
              <thead>
                <tr>
                  <th style="width:34px; padding:8px 4px 8px 16px;">
                    <input type="checkbox" id="wm-todos" title="Escolher todas as mensagens desta página">
                  </th>
                  <th colspan="6" style="padding:8px; font-weight:normal; font-size:12px; color:#999;">
                    Escolher para marcar como lido, arquivar ou apagar em conjunto
                  </th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($messages as $msg): ?>
                <?php $unread_style = $msg['seen'] ? '' : 'background:#f0f7ff; font-weight:bold;'; ?>
                <tr style="cursor:pointer; <?php echo $unread_style; ?>" data-uid="<?php echo (int) $msg['uid']; ?>"
                    onclick="window.location='<?php echo admin_url('dps_webmail/view_message/' . urlencode($folder) . '/' . $msg['uid']); ?>'">
                  <td style="width:34px; padding:10px 4px 10px 16px;" onclick="event.stopPropagation();">
                    <input type="checkbox" class="wm-caixa" value="<?php echo (int) $msg['uid']; ?>">
                  </td>
                  <td style="width:20px; padding:10px 8px 10px 4px;">
                    <?php if (!$msg['seen']): ?>
                      <span style="display:inline-block; width:8px; height:8px; background:#3498db; border-radius:50%;"></span>
                    <?php endif; ?>
                  </td>
                  <td style="width:200px; padding:10px 8px;">
                    <span style="<?php echo $msg['seen'] ? '' : 'font-weight:600;'; ?>">
                      <?php echo htmlspecialchars($msg['from_name'] ?: $msg['from_email']); ?>
                    </span>
                  </td>
                  <td style="padding:10px 8px;">
                    <span style="<?php echo $msg['seen'] ? '' : 'font-weight:600;'; ?>">
                      <?php echo htmlspecialchars($msg['subject']); ?>
                    </span>
                  </td>
                  <td style="width:30px; padding:10px 8px; text-align:center;">
                    <?php if ($msg['has_attach']): ?>
                      <i class="fa fa-paperclip text-muted"></i>
                    <?php endif; ?>
                  </td>
                  <td style="width:130px; padding:10px 16px 10px 8px; text-align:right; color:#888; font-size:12px; white-space:nowrap;">
                    <?php
                    $ts = $msg['date_ts'];
                    $today = strtotime('today');
                    if ($ts >= $today) {
                        echo date('H:i', $ts);
                    } elseif ($ts >= strtotime('yesterday')) {
                        echo 'Ontem ' . date('H:i', $ts);
                    } else {
                        echo date('d/m/Y', $ts);
                    }
                    ?>
                  </td>
                  <td style="width:70px; padding:10px 16px 10px 4px;" onclick="event.stopPropagation();">
                    <div class="btn-group btn-group-xs">
                      <?php if ($folder !== 'Trash'): ?>
                      <button class="btn btn-default btn-xs webmail-action" title="Mover para Lixo"
                              data-action="delete" data-uid="<?php echo $msg['uid']; ?>" data-folder="<?php echo htmlspecialchars($folder); ?>">
                        <i class="fa fa-trash"></i>
                      </button>
                      <?php endif; ?>
                      <?php if ($folder === 'INBOX'): ?>
                      <button class="btn btn-default btn-xs webmail-action" title="Arquivar"
                              data-action="archive" data-uid="<?php echo $msg['uid']; ?>" data-folder="<?php echo htmlspecialchars($folder); ?>">
                        <i class="fa fa-archive"></i>
                      </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>

            <!-- Paginação -->
            <?php if ($total > $per_page): ?>
            <div style="padding:12px 20px; border-top:1px solid #eee; text-align:right;">
              <?php
              $total_pages = ceil($total / $per_page);
              for ($p = 1; $p <= $total_pages; $p++):
                $active_pg = ($p == $page) ? 'btn-primary' : 'btn-default';
              ?>
              <a href="<?php echo admin_url('dps_webmail/index/' . urlencode($folder) . '?page=' . $p); ?>"
                 class="btn btn-xs <?php echo $active_pg; ?>"><?php echo $p; ?></a>
              <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>

          </div>
        </div>
      </div>

    </div><!-- /.row -->
  </div>
</div>
</div>
</div>
<?php init_tail(); ?>
<script>
$(document).ready(function() {
    // Acções rápidas (apagar, arquivar) via AJAX
    $(document).on('click', '.webmail-action', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var btn    = $(this);
        var action = btn.data('action');
        var uid    = btn.data('uid');
        var folder = btn.data('folder');
        var row    = btn.closest('tr');

        var labels = {
            'delete':  'Mover para o Lixo?',
            'archive': 'Arquivar esta mensagem?'
        };
        if (!confirm(labels[action] || 'Confirmar?')) return;

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

        $.post('<?php echo admin_url('dps_webmail/action'); ?>', {
            action: action,
            uid:    uid,
            folder: folder
        }, function(resp) {
            if (resp.success) {
                row.fadeOut(300, function() { $(this).remove(); });
            } else {
                btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                alert('Erro ao executar a acção.');
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false);
            alert('Erro de comunicação com o servidor.');
        });
    });

    /* ---------------------------------------------------------------
     * SELEÇÃO E ACÇÕES EM LOTE
     * ------------------------------------------------------------ */

    function wmEscolhidos() {
        return $('.wm-caixa:checked').map(function () { return this.value; }).get();
    }

    function wmActualizarBarra() {
        var n = wmEscolhidos().length;
        $('#wm-barra').css('display', n > 0 ? 'flex' : 'none');
        $('#wm-conta').text(n + (n === 1 ? ' mensagem escolhida' : ' mensagens escolhidas'));

        // O "escolher todos" fica a meio quando só algumas estão marcadas —
        // senão diz "todas" com três de vinte escolhidas.
        var total = $('.wm-caixa').length;
        $('#wm-todos').prop('checked', n > 0 && n === total)
                      .prop('indeterminate', n > 0 && n < total);
    }

    $(document).on('change', '.wm-caixa', wmActualizarBarra);

    $('#wm-todos').on('change', function () {
        $('.wm-caixa').prop('checked', $(this).is(':checked'));
        wmActualizarBarra();
    });

    $('#wm-limpar').on('click', function (e) {
        e.preventDefault();
        $('.wm-caixa, #wm-todos').prop('checked', false);
        wmActualizarBarra();
    });

    // Clicar na caixa não deve abrir a mensagem (a linha inteira é um link).
    $(document).on('click', '.wm-caixa', function (e) { e.stopPropagation(); });

    $('.wm-lote').on('click', function () {
        var accao = $(this).data('accao');
        var uids  = wmEscolhidos();
        if (!uids.length) { return; }

        var perguntas = {
            'delete':           'Mover ' + uids.length + ' mensagem(ns) para o Lixo?',
            'delete_permanent': 'Apagar DEFINITIVAMENTE ' + uids.length + ' mensagem(ns)? Não há como recuperar.',
            'archive':          'Arquivar ' + uids.length + ' mensagem(ns)?'
        };

        // Marcar como lido não pergunta: desfaz-se com um clique no botão ao lado.
        if (perguntas[accao] && !confirm(perguntas[accao])) { return; }

        var botoes = $('.wm-lote').prop('disabled', true);
        $('#wm-conta').text('A tratar ' + uids.length + '…');

        $.post('<?php echo admin_url('dps_webmail/bulk'); ?>', {
            action: accao,
            folder: '<?php echo htmlspecialchars($folder, ENT_QUOTES); ?>',
            uids:   uids
        }, function (r) {
            botoes.prop('disabled', false);

            if (!r || !r.success) {
                alert((r && r.message) ? r.message : 'Não foi possível executar a acção.');
                wmActualizarBarra();
                return;
            }

            if (accao === 'mark_read' || accao === 'mark_unread') {
                /*
                 * Marcar não tira a mensagem da pasta — recarrega-se para o
                 * estilo de "não lida" e o contador do lado ficarem certos.
                 */
                location.reload();
                return;
            }

            // Apagar e arquivar tiram-nas da pasta: saem da lista à vista.
            uids.forEach(function (uid) {
                $('tr[data-uid="' + uid + '"]').fadeOut(250, function () { $(this).remove(); });
            });

            setTimeout(function () {
                $('.wm-caixa, #wm-todos').prop('checked', false);
                wmActualizarBarra();
                if (!$('.wm-caixa').length) { location.reload(); }
            }, 300);
        }, 'json').fail(function () {
            botoes.prop('disabled', false);
            wmActualizarBarra();
            alert('Erro de comunicação com o servidor.');
        });
    });

    wmActualizarBarra();
});
</script>
