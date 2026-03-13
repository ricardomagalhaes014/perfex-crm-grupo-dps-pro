<script>
$(function () {
  // Oculta Outlook se não estiver conectado
    if (typeof isOutlookLoggedIn === 'function' && !isOutlookLoggedIn()) {
      $('#showOutlookCheckbox').remove();
    } else if (typeof acquireTokenPopupAndCallMSGraph === 'function') {
      acquireTokenPopupAndCallMSGraph();
    }

  // Inicializa editor de notas
  init_editor('textarea[name="notes"]', { menubar: false });

  // Ao abrir o modal
  $('#newAppointmentModal').on('shown.bs.modal', function () {
    // Inicializa datetimepicker
    if (typeof $.fn.datetimepicker !== 'undefined') {
      $('input[name="date"]').datetimepicker({
        format: 'Y-m-d H:i',
        step: 30,
        lang: 'pt',
        scrollInput: false
      });

      setTimeout(() => {
        $('.xdsoft_datetimepicker').css('z-index', 9999);
      }, 300);
    }

    // Atualiza selects e campos personalizados
    setTimeout(() => {
      $('.selectpicker').selectpicker('refresh');
      $('.custom_field select').selectpicker('refresh');
    }, 300);
  });

  init_selectpicker();
    // Validação do formulário
  appValidateForm($('#appointment-form'), {
    subject: "required",
    description: "required",
    date: "required",
    rel_type: "required",
    'attendees[]': {
      required: true,
      minlength: 1
    }
  }, submitAppointmentForm);

  function submitAppointmentForm(form) {
    const $form = $(form);
    const isOutlookChecked = $('#outlook-checkbox').is(':checked');

    $('button[type="submit"], .close_btn').prop('disabled', true);
    $('button[type="submit"]').html('<i class="fa fa-refresh fa-spin fa-fw"></i>');
    $('.modal-title').html("<?= _l('appointment_please_wait'); ?>");
    $('#appointment-form .modal-body').addClass('filterBlur');

    $.post($form.attr('action'), $form.serialize()).done(function (res) {
      try {
        const response = typeof res === 'object' ? res : JSON.parse(res);

        if (response.result) {
          if (isOutlookChecked && (typeof isOutlookLoggedIn === 'function' && isOutlookLoggedIn())) {
            outlookAddOrUpdateEvent($form.serializeArray());
          }
          alert_float('success', "<?= _l("appointment_created"); ?>");
          setTimeout(() => location.reload(), 1000);
        } else {
          alert_float('danger', response.message || 'Erro ao salvar agendamento.');
        }
      } catch (e) {
        alert_float('danger', 'Erro ao processar resposta.');
      }
    });
    return false;
  }
    // Relacionamento
  $('#rel_type').on('change', function () {
    const selected = $("option:selected", this).attr('id');
    const contact_id = $('#contact_id');
    const divs = $('#div_name, #div_email, #div_phone');
    const inputs = $('#div_name input, #div_email input, #div_phone input');

    if (selected === 'external') {
      inputs.prop('disabled', false).prop('required', true);
      $('#div_phone input').prop('required', false);
      divs.removeClass('hidden');
      $('#select_contacts').addClass('hidden');
      $('#rel_id_wrapper').addClass('hide');
    } else if (selected === 'internal') {
      contact_id.prop('required', true).val('').selectpicker('refresh');
      $('#select_contacts').removeClass('hidden');
      divs.addClass('hidden');
      $('#rel_id_wrapper').addClass('hide');
    } else {
      inputs.prop('required', false);
      $('#select_contacts').addClass('hidden');
      divs.addClass('hidden');
      $('#rel_id_wrapper').removeClass('hide');
    }
  });

  // Preencher dados do contato
  $('#contact_id, #rel_id').on('change', function () {
    const contact_id = $(this).val();
    const is_lead = $(this).attr('id') === 'rel_id';

    $.post("<?= admin_url('appointly/appointments/fetch_contact_data'); ?>", {
      contact_id, lead: is_lead
    }).done(function (res) {
      if (res) {
        $('#div_name input').val(res.firstname + ' ' + res.lastname).prop('disabled', true);
        $('#div_email input').val(res.email).prop('disabled', !!res.email);
        $('#div_phone input').val(res.phonenumber).prop('disabled', true);
        $('#div_name, #div_email, #div_phone').removeClass('hidden');
      }
    });
  });

  // Client ID via URL (fallback)
  const clientId = new URLSearchParams(window.location.search).get('client_id');
  if (clientId) {
    $('input[name="client_id"]').val(clientId);
  }
});
</script>