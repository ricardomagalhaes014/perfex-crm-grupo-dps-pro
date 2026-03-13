'use strict';

/**
 * Staff
 */
function appointmentGlobalStaffEditModal(el, staff = null) {
    createModalWrapper();
    let userId = $(el).attr('id');

    $("#modal_wrapper").load(`${admin_url}appointly/appointments/modal_internal_crm`, {
        slug: 'create'
    }, function () {
        preventDoubleClicksModal();
        if ($('#newAppointmentModal').is(':hidden')) {
            $('#newAppointmentModal').modal({ show: true });
        }
        if (userId) {
            $('#newAppointmentModal').find('select option[value=' + userId + ']').attr('selected', true);
        }
        initAppointmentScheduledDates(); // ✅ Inicializa datas
    });
}

/**
 * Open staff edit modal for new appointment with staff
 */
function appointmentGlobalStaffModal(el) {
    var id = $(el).data('id');
    $("#modal_wrapper").load(`${admin_url}appointly/appointments/modal_internal_crm`, {
        slug: 'edit',
        appointment_id: id
    }, function () {
        preventDoubleClicksModal();
        if ($('#appointmentModal').is(':hidden')) {
            $('#appointmentModal').modal({ show: true });
        }
        initAppointmentScheduledDates(); // ✅ Inicializa datas
    });
}

/**
 * Internal Staff Appointment
 */
$('#createInternal').click(function () {
    $("#modal_wrapper").load(`${admin_url}appointly/appointments/modal_internal_crm`, {
        slug: 'create'
    }, function () {
        preventDoubleClicksModal();
        if ($('#newAppointmentModal').is(':hidden')) {
            $('#newAppointmentModal').modal({ show: true });
        }
        initAppointmentScheduledDates(); // ✅ Inicializa datas
    });
});

/**
 * Init dates for internal appointments (padrão global)
 */
function initAppointmentScheduledDates() {
    const $dateField = $('input[name="date"]');

    if (!$dateField.length) return;

    if (!$dateField.hasClass('datetimepicker')) {
        $dateField.addClass('datetimepicker');
    }

    if (typeof $.fn.datetimepicker !== 'undefined') {
        $dateField.datetimepicker({
            format: 'Y-m-d H:i',       // ✅ Corrigido para MySQL
            step: 15,
            lang: app.locale || 'pt',
            dayOfWeekStart: 1,
            scrollInput: false,
            timepicker: true,
            lazyInit: true,
            closeOnDateSelect: false,
            minDate: 0,
            validateOnBlur: false
        });

        $dateField.on('focus click', function () {
            $(this).datetimepicker('show');
        });
    } else {
        console.error('❌ datetimepicker não está carregado.');
    }
}

/** 
 * Leads
 */
function appointmentGlobalLeadsContactsModalNew(el) {
    let type = $(el).attr('data-type');

    createModalWrapper();
    $("#modal_wrapper").load(`${admin_url}appointly/appointments/modal_leads_contacts_crm`, {
        user_id: $(el).attr('id'),
        type: type
    }, function () {
        preventDoubleClicksModal();
        if ($('#leadAppointmentModal').is(':hidden')) {
            $('#leadAppointmentModal').modal({ show: true });
        }
        initAppointmentScheduledDates(); // ✅ Inicializa datas
    });
}

/**
 * Mutual functions
 */
$('body').on('submit', '#appointment-internal-crm-form, #appointment-leads-contacts-crm-form, #appointment-contacts-crm-form', function (e) {
    e.preventDefault();
});

/** 
 * Appointment type checkboxes
 */
$('body').on('change', '#appointment_select_type', function () {
    let selectedColorType = $(this).children("option:selected").data('color');
    $('#appointment_color_type').attr('style', 'background-color:' + selectedColorType);
});

/** 
 * Clear data after modals are closed
 */
$('.modal').on('hidden.bs.modal', function () {
    $('.xdsoft_datetimepicker').remove();
    $(this).removeData();
});

/** 
 * Email and SMS Reminders checkboxes
 */
$('body').on('change', '#by_sms, #by_email', function () {
    var anyChecked = $('#by_sms').prop('checked') || $('#by_email').prop('checked');
    if (anyChecked) {
        $('.appointment-reminder').removeClass('hide');
    } else {
        $('.appointment-reminder').addClass('hide');
    }
});

/**
 * Create modal wrapper for appointments
 */
function createModalWrapper() {
    let modalWrapper = document.createElement('div');
    modalWrapper.setAttribute("id", "modal_wrapper");
    document.getElementsByTagName('body')[0].appendChild(modalWrapper);
}

/**
 * Prevent doubleclicking on modal to open multiple backdrop backgrounds
 */
function preventDoubleClicksModal() {
    if ($('.modal-backdrop.fade').hasClass('in')) {
        $('.modal-backdrop.fade').remove();
    }
}

/**
 * Outlook integration safe check
 */
$(function () {
    if (typeof isOutlookLoggedIn === 'function') {
        if (!isOutlookLoggedIn()) {
            $('#showOutlookCheckbox').remove();
        }
    } else {
        console.warn("⚠️ Função isOutlookLoggedIn não existe — ignorando integração Outlook");
    }

    if (typeof acquireTokenPopupAndCallMSGraph === 'function') {
        acquireTokenPopupAndCallMSGraph();
    }
});