<script>
"use strict";

const lang = {
    please_wait: "<?= _l('appointment_please_wait'); ?>",
    save: "<?= _l('save'); ?>",
    view_notes: "<?= _l('appointment_viewing_notes'); ?>",
    notes_updated: "<?= _l('appointment_notes_updated'); ?>",
    are_you_sure: "<?= _l('appointment_are_you_sure'); ?>",
    feedback_requested: "<?= _l('appointment_feedback_reuested_alert'); ?>"
};

$(document).ready(function () {
    $('.sub-menu-item-appointly-user-history a').addClass('active');

    initDataTable(
        '.table-appointments-history',
        admin_url + 'appointly/appointments_history/table',
        [5], // não ordenáveis
        [5], // escondidas
        [],
        [1, 'desc']
    );
});

$('.modal').on('hidden.bs.modal', function () {
    $(this).removeData();
});

let app_edit_id = "";

function editAppointmentNotes(el) {
    const appointment_id = $(el).data("id");
    const content_row = $(".content .row.main_row");

    const skeleton_loader = `
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-picture"></div>
                <div class="ph-row">
                    <div class="ph-col-6 big"></div>
                    <div class="ph-col-4 empty big"></div>
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-4"></div>
                    <div class="ph-col-8 empty"></div>
                    <div class="ph-col-6"></div>
                    <div class="ph-col-6 empty"></div>
                    <div class="ph-col-12"></div>
                </div>
            </div>
        </div>`;

    $(".content .col-md-12").removeClass("col-md-12").addClass("col-md-6");
    $("#toggleTableBtn").removeClass("hidden");

    content_row.find(".edit_appointment_history").remove();
    content_row.append(`
        <div class="col-md-6 edit_appointment_history">
            <div class="panel_s">
                <div class="panel-body">${skeleton_loader}</div>
            </div>
        </div>
    `);

    $.ajax({
        url: admin_url + "appointly/appointments_history/get_notes/" + appointment_id,
        beforeSend: function () {
            app_edit_id = appointment_id;
        }
    }).done(function (data) {
        const result = JSON.parse(data);
        const notes = result.notes || "";

        tinymce.remove("textarea[name='notes']");

        setTimeout(() => {
            content_row.find(".edit_appointment_history").html(`
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-heading">
                            <span class="font-medium">${lang.view_notes}: <strong>${result.subject}</strong></span>
                        </div>
                        <textarea name="notes" class="ays-ignore">${notes}</textarea>
                        <div class="form-group">
                            <button class="btn btn-primary mtop10 pull-right" onclick="updateAppointmentFormData()">${lang.save}</button>
                        </div>
                    </div>
                </div>
            `);
            init_editor("textarea[name='notes']");
        }, 1000);
    });
}

function updateAppointmentFormData() {
    const notes = tinyMCE.activeEditor.getContent();
    const $button = $(".edit_appointment_history .form-group button");
    $button.html('<i class="fa fa-refresh fa-spin fa-fw"></i>');

    $.post(admin_url + "appointly/appointments_history/update_note", {
        appointment_id: app_edit_id,
        notes: notes
    }).done(function (res) {
        const response = JSON.parse(res);
        alert_float(response.result ? "success" : "danger", response.result ? lang.notes_updated : "Erro ao guardar notas.");
        $button.html(lang.save);
    });
}

function request_appointment_feedback(appointment_id) {
    $("body").append('<div class="dt-loader"></div>');
    $.post(admin_url + "appointly/appointments/requestAppointmentFeedback/" + appointment_id)
        .done(function (res) {
            if (res.success === true) {
                alert_float("info", lang.feedback_requested);
            }
            $(".dt-loader").remove();
        }).fail(function (err) {
            $(".dt-loader").remove();
            console.error("Erro desconhecido: ", err);
        });
}

function deleteAppointment(id) {
    if (confirm(lang.are_you_sure)) {
        $.post(site_url + "appointly/appointments/delete/" + id)
            .done(function (res) {
                res = JSON.parse(res);
                if (res.success) {
                    alert_float("success", res.message);
                    $(".table-appointments-history").DataTable().ajax.reload();
                }
            });
    }
}

function toggle_appointment_table_view() {
    $("#toggleTableBtn").addClass("hidden");
    $(".content .row.main_row .col-md-6").removeClass("col-md-6").addClass("col-md-12");
    $(".edit_appointment_history").remove();
    $(window).trigger("resize");
}
</script>

<style>
.edit_appointment_history .panel-heading {
    background: #e3e8ee;
}
.mce-tinymce.mce-container.mce-panel {
    margin-top: 10px;
}
.ph-item {
    display: flex;
    flex-wrap: wrap;
    overflow: hidden;
    margin-bottom: 30px;
    background-color: #fff;
    border-radius: 2px;
    position: relative;
}
.ph-item::before {
    content: "";
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 50%;
    z-index: 1;
    width: 500%;
    margin-left: -250%;
    animation: phAnimation 0.8s linear infinite;
    background: linear-gradient(to right, rgba(255,255,255,0) 46%, rgba(255,255,255,0.35) 50%, rgba(255,255,255,0) 54%);
}
.ph-item > * {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    padding: 0 15px;
}
.ph-row {
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 7.5px;
}
.ph-row div {
    height: 10px;
    margin-bottom: 7.5px;
    background-color: #ced4da;
}
.ph-row .big { height: 20px; margin-bottom: 15px; }
.ph-row .empty { background-color: transparent; }
.ph-col-2 { flex: 0 0 16.66667%; }
.ph-col-4 { flex: 0 0 33.33333%; }
.ph-col-6 { flex: 0 0 50%; }
.ph-col-8 { flex: 0 0 66.66667%; }
.ph-col-10 { flex: 0 0 83.33333%; }
.ph-col-12 { flex: 0 0 100%; }
.ph-avatar {
    width: 100%;
    min-width: 60px;
    background-color: #ced4da;
    margin-bottom: 15px;
    border-radius: 50%;
}
.ph-avatar::before {
    content: "";
    display: block;
    padding-top: 100%;
}
.ph-picture {
    width: 100%;
    height: 120px;
    background-color: #ced4da;
    margin-bottom: 15px;
}
@keyframes phAnimation {
    0% { transform: translate3d(-30%, 0, 0); }
    100% { transform: translate3d(30%, 0, 0); }
}
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <?php if (get_option('appointly_responsible_person') == '') { ?>
            <div class="alert alert-warning alert-dismissible" role="alert">
                <?= _l('appointments_resp_person_not_set'); ?>
                <a href="<?= admin_url('settings?group=appointly-settings'); ?>">
                    <?= _l('appointly_settings_label_pointer'); ?>
                </a>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <?php if (get_option('callbacks_responsible_person') == '') { ?>
            <div class="alert alert-warning alert-dismissible" role="alert">
                <?= _l('callbacks_resp_person_not_set'); ?>
                <a href="<?= admin_url('settings?group=appointly-settings'); ?>">
                    <?= _l('appointly_settings_label_pointer'); ?>
                </a>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-md-12">
                <h3><?= _l('appointment_history'); ?> <?= isset($client_id) ? ' - Cliente ID: ' . $client_id : ''; ?></h3>
                <hr>
            </div>
        </div>

        <div class="row main_row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <a href="#" class="btn btn-default btn-with-tooltip toggle-small-view hidden-xs pull-right hidden"
                               id="toggleTableBtn"
                               onclick="toggle_appointment_table_view(); return false;"
                               data-toggle="tooltip"
                               title="<?= _l('toggle_table'); ?>">
                                <i class="fa fa-angle-double-right"></i>
                            </a>
                            <a href="<?= admin_url('appointly/appointments'); ?>" class="btn btn-primary pull-left display-block mleft10">
                                <?= ucfirst(_l('appointments')); ?>
                            </a>
                            <a href="<?= admin_url('appointly/callbacks'); ?>" id="backToAppointments" class="btn btn-primary pull-left display-block mleft10">
                                <?= _l('appointly_callbacks'); ?>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />

                        <?php render_datatable([
                            [
                                'th_attrs' => ['width' => '10px'],
                                'name'     => _l('id')
                            ],
                            [
                                'th_attrs' => ['width' => '350px'],
                                'name'     => _l('appointment_subject')
                            ],
                            _l('appointment_meeting_date'),
                            _l('appointment_initiated_by'),
                            _l('appointment_description'),
                            _l('appointment_source'),
                            [
                                'th_attrs' => ['width' => '120px'],
                                'name'     => _l('appointments_table_calendar')
                            ]
                        ], 'appointments-history'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="modal_wrapper"></div>
<?php init_tail(); ?>
<script src="<?= module_dir_url('appointly', 'assets/js/history_main_js.js'); ?>"></script>
</body>
</html>

</style>