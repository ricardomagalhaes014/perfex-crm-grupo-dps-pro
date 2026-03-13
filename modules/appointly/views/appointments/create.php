<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <h4 class="tw-font-semibold"><?= _l('Nova Marcação'); ?></h4>
        <hr />

        <?php echo form_open(admin_url('appointly/appointments/create'), ['id' => 'appointment-form']); ?>

        <!-- (NOVO) Tipo de relação - mantemos o hidden existente e sincronizamos via JS -->
        <div class="form-group">
          <label for="rel_type_select"><?= _l('Tipo de relação'); ?></label>
          <select class="form-control" id="rel_type_select">
            <option value="external" selected><?= _l('Externo (cliente)'); ?></option>
            <option value="internal"><?= _l('Interno'); ?></option>
            <option value="lead"><?= _l('Lead'); ?></option>
          </select>
          <small class="text-muted">Este seletor atualiza o campo oculto <code>rel_type</code> já existente.</small>
        </div>

        <div class="form-group">
          <label for="subject"><?= _l('appointment_subject'); ?></label>
          <input type="text" class="form-control" name="subject" required>
        </div>

        <div class="form-group">
          <label for="date"><?= _l('appointment_meeting_date'); ?></label>
          <input type="date" class="form-control" name="date" required>
        </div>

        <div class="form-group">
          <label for="start_hour"><?= _l('appointment_meeting_start_hour'); ?></label>
          <input type="time" class="form-control" name="start_hour" required>
        </div>

        <div class="form-group">
          <label for="description"><?= _l('appointment_description'); ?></label>
          <textarea class="form-control" name="description"></textarea>
        </div>

        <!-- (NOVO) Participantes - o JS da página pode validar attendees[] -->
        <?php if (isset($staff_members) && !empty($staff_members)) : ?>
          <div class="form-group">
            <label><?= _l('Participantes'); ?></label>
            <select class="form-control selectpicker" name="attendees[]" multiple data-actions-box="true" data-live-search="true">
              <?php foreach ($staff_members as $m): ?>
                <?php
                  $id  = isset($m['staffid']) ? $m['staffid'] : (isset($m->staffid) ? $m->staffid : '');
                  $nm1 = isset($m['firstname']) ? $m['firstname'] : (isset($m->firstname) ? $m->firstname : '');
                  $nm2 = isset($m['lastname'])  ? $m['lastname']  : (isset($m->lastname)  ? $m->lastname  : '');
                  $nmf = isset($m['fullname'])  ? $m['fullname']  : trim($nm1 . ' ' . $nm2);
                ?>
                <option value="<?= html_escape($id); ?>"><?= html_escape($nmf ?: 'Sem nome'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <!-- Campos obrigatórios invisíveis -->
        <input type="hidden" name="client_id" value="<?= html_escape($client_id ?? ''); ?>">
        <input type="hidden" name="contact_id" value="<?= html_escape($client_id ?? ''); ?>">
        <input type="hidden" name="rel_type" id="rel_type_hidden" value="external">

        <button type="submit" class="btn btn-primary"><?= _l('submit'); ?></button>

        <?php echo form_close(); ?>

        <!-- (NOVO) Área de debug opcional -->
        <pre id="create-debug" class="mt-3" style="display:none; max-height:220px; overflow:auto; background:#f8f9fa; padding:10px; border:1px solid #eee;"></pre>

      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>

<script>
  (function () {
    // Sincroniza o seletor visível com o hidden existente (sem apagar nada do original)
    var $relSelect = $('#rel_type_select');
    var $relHidden = $('#rel_type_hidden'); // input[name="rel_type"] original
    $relSelect.on('change', function () {
      $relHidden.val(this.value);
    });

    // Garante que client_id do URL preenche o hidden, se existir
    try {
      var params = new URLSearchParams(window.location.search);
      var clientFromUrl = params.get('client_id');
      if (clientFromUrl) {
        var $hidClient = $('input[name="client_id"]');
        if ($hidClient.length === 0) {
          $('<input>', { type: 'hidden', name: 'client_id', value: clientFromUrl }).appendTo('#appointment-form');
        } else {
          $hidClient.val(clientFromUrl);
        }
        // Se contact_id não foi definido, por enquanto mantemos como estava (sem remover nada)
        var $hidContact = $('input[name="contact_id"]');
        if ($hidContact.length && !$hidContact.val()) {
          $hidContact.val(clientFromUrl);
        }
        console.log('client_id preenchido via URL:', clientFromUrl);
      }
    } catch (e) {
      console.warn('Não foi possível ler client_id da URL:', e);
    }

    // Submissão AJAX (mantendo o comportamento original e enriquecendo os erros)
    $('#appointment-form').on('submit', function(e) {
      e.preventDefault();
      var $form = $(this);
      var $btn  = $form.find('button[type="submit"]');
      var $dbg  = $('#create-debug');

      $btn.prop('disabled', true).data('old', $btn.html()).html('<i class="fa fa-refresh fa-spin fa-fw"></i>');

      $.post($form.attr('action'), $form.serialize())
        .done(function(resp) {
          var res = resp;
          if (typeof resp === 'string') {
            try { res = JSON.parse(resp); } catch (e) {
              // Mostra retorno bruto para debug
              $dbg.text(String(resp)).show();
              alert('Erro inesperado ao processar resposta do servidor (JSON inválido).');
              console.error('Resposta bruta:', resp);
              return;
            }
          }

          if (res && res.result) {
            alert('Marcação criada com sucesso!');
            window.location.href = '<?= admin_url('appointly/appointments'); ?>';
          } else {
            var msg = (res && (res.error || res.message)) || 'Erro ao criar a marcação. Verifique os dados.';
            alert(msg);
            console.error('Resposta do servidor:', res || resp);
            try { $dbg.text(JSON.stringify(res || resp, null, 2)).show(); } catch(_) {}
          }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
          var body = jqXHR && jqXHR.responseText ? jqXHR.responseText : '';
          alert('Erro de comunicação: ' + textStatus + ' - ' + errorThrown);
          console.error('HTTP ' + jqXHR.status, body || '(sem corpo)');
          $('#create-debug').text('HTTP ' + jqXHR.status + '\n' + (body || '(sem corpo)')).show();
        })
        .always(function () {
          $btn.prop('disabled', false).html($btn.data('old') || '<?= _l('submit'); ?>');
        });
    });
  })();
</script>

</body>
</html>