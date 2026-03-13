<script>
$(function () {
  //  Outlook
    if (typeof isOutlookLoggedIn === 'function' && !isOutlookLoggedIn()) {
      $('#showOutlookCheckbox').remove();
    } else if (typeof acquireTokenPopupAndCallMSGraph === 'function') {
      acquireTokenPopupAndCallMSGraph();
    }

  // Editor de notas
  init_editor('textarea[name="notes"]', { menubar: false });
  initAppointmentScheduledDates();

  // Inicializa ao abrir o modal
  /*
  $('#newAppointmentModal').on('shown.bs.modal', function () {
    // 🗓️ Datetimepicker
    if (typeof $.fn.datetimepicker !== 'undefined') {
      $('input[name="date"]').datetimepicker({
        format: 'Y-m-d H:i',
        step: 30,
        lang: 'pt',
        scrollInput: false
      });

      // Ajusta o z-index do calendário
      setTimeout(() => {
        $('.xdsoft_datetimepicker').css('z-index', 9999);
      }, 300);
    }

    // 🔁 Reforça selectpickers e campos personalizados
    setTimeout(() => {
      $('.selectpicker').selectpicker('refresh');
      $('.custom_field select').selectpicker('refresh');
    }, 300);
  });
  */

  init_selectpicker();

  const div_name = $('#div_name');
  const div_email = $('#div_email');
  const div_phone = $('#div_phone');

  // Limpa modal ao fechar
  $('.modal').on('hidden.bs.modal', function () {
    $('.xdsoft_datetimepicker').remove();
    $(this).removeData();
  });

  // Bloqueia envio padrão
  $('#appointment-form').on('submit', function (e) {
    e.preventDefault();
  });

  // Outlook toggle fix
  $('body').on('click', '#outlook-checkbox', function () {
    $(this).prop('checked', $(this).is(':checked'));
  });

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
  }, apply_appointments_form_data, {
    'attendees[]': "Por favor selecione pelo menos 1 membro da equipe"
  });

  // Envio do formulário
  function apply_appointments_form_data(form) {
    $('button[type="submit"], button.close_btn').prop('disabled', true);
    $('button[type="submit"]').html('<i class="fa fa-refresh fa-spin fa-fw"></i>');
    $('#appointment-form .modal-body').addClass('filterBlur');
    $('.modal-title').html("<?= _l('appointment_please_wait'); ?>");

    const data = $(form).serialize();
    const isOutlookChecked = document.getElementById('outlook-checkbox');
    const formSerialized = $(form).serializeArray();

    $.post(form.action, data).done(function (res) {
      let response;
      try {
        response = typeof res === 'object' ? res : JSON.parse(res);
      } catch (err) {
        alert_float('danger', 'Erro ao interpretar resposta do servidor.');
        return;
      }

      if (response.result) {
        if (isOutlookLoggedIn() && isOutlookChecked?.checked) {
          outlookAddOrUpdateEvent(formSerialized);
        }
        alert_float('success', "<?= _l('appointment_created'); ?>");
        setTimeout(() => window.location.reload(), 1000);
      } else {
        alert_float('danger', response.error || response.message || 'Erro ao guardar agendamento.');
      }
    });

    return false;
  }

  // Troca tipo de relacionamento
  $('body').on('change', '#rel_type', function () {
    const selected = $("option:selected", this).attr('id');
    const contact_id = $("#contact_id");
    const select_contacts = $('#select_contacts');
    const div_nep = $('#div_name, #div_email, #div_phone');
    const dninput = $('#div_name input, #div_email input, #div_phone input');
    const rel_id_wrapper = $('#appointment-form #rel_id_wrapper');
    const lead_select = $('#appointment-form #rel_id');

    if (selected === 'external') {
      dninput.val('').prop('disabled', false).prop('required', true);
      $('#div_phone input').prop('required', false);
      div_nep.removeClass('hidden');
      select_contacts.addClass('hidden');
      contact_id.selectpicker("refresh").prop('required', false);
      rel_id_wrapper.addClass('hide');
      lead_select.prop('required', false).val('').selectpicker("refresh");
    } else if (selected === 'internal') {
      contact_id.val('').selectpicker("refresh").prop('required', true);
      div_nep.addClass('hidden');
      select_contacts.removeClass('hidden');
      rel_id_wrapper.addClass('hide');
      lead_select.prop('required', false).val('').selectpicker("refresh");
    } else {
      dninput.prop('required', false);
      contact_id.val('').selectpicker("refresh").prop('required', false);
      div_nep.addClass('hidden');
      select_contacts.addClass('hidden');
      rel_id_wrapper.removeClass('hide');
      lead_select.prop('required', true);
    }
  });

  // Preenche dados do contato
  $('body').on('change', '#contact_id, #rel_id', function () {
    const contact_id = $("option:selected", this).val();

    if (!contact_id && div_name.children('input').is(":visible")) {
      div_name.children('input').val('');
      div_email.children('input').val('');
      div_phone.children('input').val('');
    }

    $.post("<?= admin_url('appointly/appointments/fetch_contact_data'); ?>", {
      contact_id,
      lead: $(this).attr('id') === 'rel_id'
    }).done(function (res) {
      if (res) {
        $('#div_name, #div_email, #div_phone').removeClass('hidden');

        const full_name = res.firstname ? `${res.firstname} ${res.lastname}` : res.name;
        const email = res.email;
        const phone = res.phonenumber;

        div_name.children('input').val(full_name).prop('disabled', true);
        div_email.children('input').val(email).prop('disabled', !!email);
        div_email.children('input[name="email"]').val(email).prop('required', !email);
        div_phone.children('input').val(phone).prop('disabled', true);
      }
    });
  });

  // Busca de leads (search)
  const _rel_id = $('#rel_id');
  const _rel_type = $('#rel_lead_type');
  const serverData = { rel_id: _rel_id.val() };

  init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');
  init_ajax_search(_rel_type.val(), _rel_id, serverData);

  // Captura client_id da URL
  const urlParams = new URLSearchParams(window.location.search);
  const clientId = urlParams.get('client_id');

  if (clientId) {
    let clientInput = document.querySelector('input[name="client_id"]');
    if (!clientInput) {
      clientInput = $('<input>', {
        type: 'hidden',
        name: 'client_id'
      }).appendTo('#appointment-form')[0];
    }

    clientInput.value = clientId;
    console.log('✅ client_id foi adicionado ao formulário:', clientId);
  } else {
    console.warn('⚠️ client_id não encontrado na URL!');
  }

});
</script>