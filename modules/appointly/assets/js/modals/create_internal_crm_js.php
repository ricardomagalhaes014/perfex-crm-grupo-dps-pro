<script>
    $(function () {
        init_selectpicker();
        initAppointmentScheduledDatesStaff();
        init_editor('textarea[name="notes"]', {
            menubar: false,
        });

        $('.modal').on('hidden.bs.modal', function () {
            $('.xdsoft_datetimepicker').remove();
            $(this).removeData();
        });

        // Validação do formulário
        appValidateForm($("#appointment-internal-crm-form"), {
            subject: "required",
            description: "required",
            date: "required",
            rel_type: "required",
            'attendees[]': {
                required: true,
                minlength: 1
            }
        }, apply_appointments_form_data, {
            'attendees[]': "Por favor, selecione pelo menos 1 membro da equipa."
        });

        // Função para processar e submeter o formulário
        function apply_appointments_form_data(form) {
            // Previne duplo envio
            $('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-refresh fa-spin fa-fw"></i>');
            $('#appointment-internal-crm-form .modal-body').addClass('filterBlur');
            $('.modal-title').html("<?= _l('appointment_please_wait'); ?>");

            const data = $(form).serialize();
            const url = form.action;

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function (response) {
                    console.log("Resposta do servidor:", response);

                    if (response.result) {
                        alert_float('success', "<?= _l('appointment_created'); ?>");
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        alert_float('danger', response.error || 'Erro ao criar o agendamento.');
                        $('button[type="submit"]').prop('disabled', false).html('<?= _l('submit'); ?>');
                        $('#appointment-internal-crm-form .modal-body').removeClass('filterBlur');
                        $('.modal-title').html("<?= _l('new_appointment'); ?>");
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Erro na submissão:', error);
                    alert_float('danger', 'Erro ao comunicar com o servidor.');
                    $('button[type="submit"]').prop('disabled', false).html('<?= _l('submit'); ?>');
                    $('#appointment-internal-crm-form .modal-body').removeClass('filterBlur');
                    $('.modal-title').html("<?= _l('new_appointment'); ?>");
                }
            });

            return false;
        }
    });
</script>