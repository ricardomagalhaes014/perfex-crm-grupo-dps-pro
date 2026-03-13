<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="newAppointmentModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo _l('Nova Marcação'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <?php echo form_open(admin_url('appointly/appointments/create'), ['id' => 'appointment_form']); ?>

        <!-- 🔒 Campo obrigatório client_id (inserido via JS no momento correto) -->
        <input type="hidden" name="client_id" id="client_id_hidden" value="">
        <input type="hidden" name="rel_type" value="external">

        <div class="form-group">
          <label for="subject" class="control-label">* <?php echo _l('Subject'); ?></label>
          <input type="text" name="subject" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="description">* <?php echo _l('Description'); ?></label>
          <textarea name="description" class="form-control" rows="4" required></textarea>
        </div>

        <div class="form-group">
          <label>* <?php echo _l('Relacionamento'); ?></label>
          <select name="search_type" class="form-control" id="search_type_select">
            <option value="contacto"><?php echo _l('Contacto'); ?></option>
            <option value="empresa"><?php echo _l('Empresa'); ?></option>
            <option value="nif"><?php echo _l('NIF'); ?></option>
            <option value="company"><?php echo _l('Company'); ?></option>
          </select>
        </div>

        <div class="form-group">
          <label for="search_input">* <?php echo _l('Pesquisar'); ?></label>
          <input type="text" id="search_input" class="form-control" placeholder="Digite para pesquisar..." autocomplete="off">
          <div id="search_results" class="dropdown-menu" style="display: none; width: 100%; max-height: 200px; overflow-y: auto;"></div>
        </div>

        <input type="hidden" name="contact_id" id="selected_contact_id">

        <div class="form-group">
          <label for="date">* <?php echo _l('Date / Time'); ?></label>
          <input type="text" name="date" class="form-control datetimepicker" required>
        </div>

        <div class="form-group">
          <label for="location"><?php echo _l('Appointment Location'); ?></label>
          <input type="text" name="location" class="form-control">
        </div>

        <div class="form-group">
          <label>* <?php echo _l('Attendees'); ?></label>
          <select name="attendees[]" class="form-control selectpicker" multiple data-live-search="true">
            <?php foreach ($staff_members as $member): ?>
              <option value="<?php echo $member['staffid']; ?>"><?php echo get_staff_full_name($member['staffid']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group form-check">
          <input type="checkbox" class="form-check-input" name="sms_notification" id="sms_notification">
          <label class="form-check-label" for="sms_notification"><?php echo _l('SMS Notification'); ?></label>
        </div>

        <div class="form-group form-check">
          <input type="checkbox" class="form-check-input" name="email_notification" id="email_notification">
          <label class="form-check-label" for="email_notification"><?php echo _l('Email Notification'); ?></label>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary"><?php echo _l('Save'); ?></button>
        </div>

        <?php echo form_close(); ?>
      </div>
    </div>
  </div>
</div>

<script>
  // ✅ Só injeta o client_id quando o modal for mostrado
  $('#newAppointmentModal').on('shown.bs.modal', function () {
    const clientId = new URLSearchParams(window.location.search).get('client_id');
    if (clientId) {
      $('#client_id_hidden').val(clientId);
    } else {
      console.warn('⚠️ client_id não encontrado na URL.');
    }
  });

  // 🔍 Pesquisa de clientes/leads
  $('#search_input').on('input', function () {
    const type = $('#search_type_select').val();
    const query = $(this).val();

    if (query.length < 3) return;

    $.post(admin_url + 'appointly/appointments/search_clients', { q: query, type: type }, function (res) {
      const results = $('#search_results').empty().show();
      if (res.results && res.results.length > 0) {
        res.results.forEach(item => {
          results.append(`<a href="#" class="dropdown-item" data-id="${item.id}">${item.text}</a>`);
        });
      } else {
        results.append(`<span class="dropdown-item disabled">Nenhum resultado</span>`);
      }
    }, 'json');
  });

  // ✅ Seleciona resultado da pesquisa
  $('#search_results').on('click', '.dropdown-item:not(.disabled)', function (e) {
    e.preventDefault();
    const name = $(this).text();
    const id = $(this).data('id');
    $('#search_input').val(name);
    $('#selected_contact_id').val(id);
    $('#search_results').hide();
  });
</script>