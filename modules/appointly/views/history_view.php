<?php defined('BASEPATH') or exit('No direct script access allowed');
init_head();

$client_id = $client_id ?? 'N/D';
?>

<div id="wrapper">
    <div class="content">

        <?php if (get_option('appointly_responsible_person') == '') { ?>
            <div class="alert alert-warning alert-dismissible" role="alert">
                <?= _l('appointments_resp_person_not_set'); ?>
                <a href="<?= admin_url('settings?group=appointly-settings'); ?>">
                    <?= _l('appointly_settings_label_pointer'); ?>
                </a>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">×</span>
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
                    <span aria-hidden="true">×</span>
                </button>
            </div>
        <?php } ?>

        <div class="row">
            <div class="col-md-12">
                <h3>Histórico de Marcações do Cliente ID: <?= $client_id; ?></h3>
                <hr>
            </div>
        </div>

        <div class="row main_row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <a href="<?= admin_url('appointly/appointments'); ?>" class="btn btn-primary pull-left display-block mleft10">
                                <?= ucfirst(_l('appointments')); ?>
                            </a>
                            <a href="<?= admin_url('appointly/callbacks'); ?>" id="backToAppointments" class="btn btn-primary pull-left display-block mleft10">
                                <?= _l('appointly_callbacks'); ?>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead>
                                    <tr>
                                        <th width="10px"><?= _l('id'); ?></th>
                                        <th width="350px"><?= _l('appointment_subject'); ?></th>
                                        <th><?= _l('appointment_meeting_date'); ?></th>
                                        <th><?= _l('appointment_initiated_by'); ?></th>
                                        <th><?= _l('appointment_description'); ?></th>
                                        <th><?= _l('appointment_source'); ?></th>
                                        <th width="120px"><?= _l('appointments_table_calendar'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($appointments)) {
                                        foreach ($appointments as $appointment) { ?>
                                            <tr>
                                                <td><?= $appointment['id']; ?></td>
                                                <td><?= e($appointment['subject']); ?></td>
                                                <td><?= _dt($appointment['date'] . ' ' . $appointment['start_hour']); ?></td>
                                                <td><?= get_staff_full_name($appointment['created_by']); ?></td>
                                                <td><?= e($appointment['description']); ?></td>
                                                <td><?= e($appointment['source']); ?></td>
                                                <td>
                                                    <a href="<?= admin_url('appointly/appointments/view?appointment_id=' . $appointment['id']); ?>" class="btn btn-default btn-sm">
                                                        <?= _l('view'); ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php }
                                    } else { ?>
                                        <tr>
                                            <td colspan="7" class="text-center"><?= _l('no_appointments_found'); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div id="modal_wrapper"></div>

<?php init_tail(); ?>
</body>
</html>