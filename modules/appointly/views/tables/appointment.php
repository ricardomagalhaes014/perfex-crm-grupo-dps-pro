<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2 appointmentWrapper" style="float:none;">
        <div class="panel_s">
          <div class="panel-body">
            <div class="col-md-12">
              <div class="appointment_recurring_back">
                <div style="display:flex;">
                  <?php if (staff_can('view', 'appointments') || staff_can('view_own', 'appointments') || staff_appointments_responsible()) : ?>
                    <a href="<?= admin_url('appointly/appointments'); ?>" class="btn btn-default mright15 appointment_go_back">
                      <?= _l('go_back'); ?>
                    </a>
                  <?php endif; ?>

                  <?php if (staff_can('edit', 'appointments') || staff_appointments_responsible()) : ?>
                    <button type="button" class="btn btn-primary pull-left" onclick="editAppointmentFromView()">
                      <?= _l('edit'); ?>
                    </button>
                  <?php endif; ?>
                </div>

                <?php if (isset($appointment['recurring']) && $appointment['recurring'] == 1): ?>
                  <span><strong><?= _l('appointment_recurring'); ?></strong></span>
                <?php endif; ?>
              </div>
            </div>
                        <div class="col-md-12 mtop15 no-padding">
              <div class="panel-heading info-header no-padding">
                <h3 class="pull-left"><?= _l('appointment_overview'); ?></h3>

                <?php if (!empty($appointment['public_url'])): ?>
                  <a data-toggle="tooltip" title="<?= _l('appointment_public_url'); ?>"
                     class="appointment_public_url pull-right"
                     href="<?= $appointment['public_url']; ?>" target="_blank">
                    <i class="fa fa-external-link-square appointment_public_link" aria-hidden="true"></i>
                  </a>
                <?php endif; ?>

                <?php if (!empty($appointment['google_meet_link'])): ?>
                  <div class="google_meet_main">
                    <a href="<?= $appointment['google_meet_link']; ?>" target="_blank"
                       data-toggle="tooltip" title="<?= _l('appointment_google_meet_info'); ?>">
                      <img width="30"
                           src="<?= base_url('/modules/appointly/assets/images/google_meet.png') ?>"
                           alt="Google Meet">
                    </a>
                  </div>
                <?php endif; ?>
              </div>

              <span class="spmodified_fit_center">
                <boldit><?= _l('appointment_status_text'); ?></boldit>
                <h3 class="appointment_status_info">
                  <?php
                  if ($appointment['cancelled']) {
                      echo '<span class="label label-danger">' . strtoupper(_l('appointment_cancelled')) . '</span>';
                  } elseif (
                      !$appointment['finished'] &&
                      !$appointment['cancelled'] &&
                      !$appointment['approved'] &&
                      strtotime($appointment['date'] . ' ' . $appointment['start_hour']) < time()
                  ) {
                      echo '<span class="label label-danger">' . strtoupper(_l('appointment_missed_label')) . '</span>';
                  } elseif (
                      !$appointment['finished'] &&
                      !$appointment['cancelled'] &&
                      $appointment['approved']
                  ) {
                      echo '<span class="label label-info">' . strtoupper(_l('appointment_upcoming')) . '</span>';
                  } elseif (
                      !$appointment['finished'] &&
                      !$appointment['cancelled'] &&
                      !$appointment['approved']
                  ) {
                      echo '<span class="label label-warning">' . strtoupper(_l('appointment_pending_approval')) . '</span>';

                      if (
                          (is_admin() || staff_can('view', 'appointments')) &&
                          $appointment['cancelled'] == 0 &&
                          $appointment['approved'] == 0
                      ) {
                          echo '<a class="label label-info mleft5 approve_appointment_single" 
                                    onClick="disableButtonsAfterDelete()" 
                                    href="' . admin_url('appointly/appointments/approve?appointment_id=' . $appointment['id']) . '">' .
                                    _l('appointment_approve') .
                               '</a>';
                      }
                  } else {
                      echo '<span class="label label-success">' . strtoupper(_l('appointment_finished')) . '</span>';
                  }
                  ?>
                </h3>
              </span>
            </div>
                        <div class="row text-center">
              <div class="col-lg-6 col-xs-12">
                <h4 class="appointly-default reorder-content"><?= _l('appointment_general_info'); ?></h4>

                <span class="spmodified">
                  <boldit><?= _l('appointment_initiated_by'); ?></boldit>
                  <?= isset($appointment['created_by']) ? get_staff_full_name($appointment['created_by']) : $appointment['name']; ?>
                </span><br>

                <span class="spmodified">
                  <boldit><?= _l('appointment_subject'); ?></boldit>
                  <?= e($appointment['subject']); ?>
                </span><br>

                <span class="spmodified">
                  <boldit><?= _l('appointment_description'); ?></boldit>
                  <?= e($appointment['description']); ?>
                </span><br>

                <span class="spmodified">
                  <boldit><?= _l('appointment_meeting_time'); ?></boldit>
                  <?= _d($appointment['date']); ?>
                </span><br>

                <span class="spmodified">
                  <boldit><?= _l('appointment_squeduled_at_text'); ?></boldit>
                  <?= date("H:i", strtotime($appointment['start_hour'])); ?>
                </span><br>

                <div class="spmodified attendees">
                  <boldit><?= _l('appointment_staff_attendees'); ?></boldit>
                  <?php if (!empty($appointment['attendees']) && is_array($appointment['attendees'])): ?>
                    <?php foreach ($appointment['attendees'] as $staff): ?>
                      <a target="_blank" href="<?= admin_url('profile/' . $staff); ?>">
                        <img src="<?= staff_profile_image_url($staff, 'small'); ?>"
                             data-toggle="tooltip"
                             data-title="<?= get_staff_full_name($staff); ?>"
                             class="staff-profile-image-small mright5" />
                      </a>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <strong>- &nbsp; <?= _l('appointment_no_assigned_staff_found'); ?></strong>
                  <?php endif; ?>
                </div>
              </div>
                            <div class="col-lg-6 col-xs-12">
                <h4 class="appointly-default reorder-content"><?= _l('appointment_additional_info'); ?></h4>
                <div class="appointly_single_container">

                  <span class="spmodified">
                    <boldit><?= _l('appointment_source'); ?></boldit>
                    <?php
                      switch ($appointment['source']) {
                        case 'lead_related':
                          echo _l('appointment_source_leads_label');
                          break;
                        case 'internal':
                          echo _l('appointment_source_internal');
                          break;
                        case 'external':
                          echo _l('appointment_source_external_text');
                          break;
                        case 'internal_staff_crm':
                          echo _l("appointment_ism_label");
                          break;
                      }
                    ?>
                  </span><br>

                  <?php $internalStaff = $appointment['source'] == 'internal_staff_crm'; ?>

                  <span class="spmodified <?= ($internalStaff ? 'line-trough-grayish' : '') ?>">
                    <boldit><?= _l('appointment_name'); ?></boldit>
                    <?php
                      $name = isset($appointment['name']) ? $appointment['name'] : $appointment['details']['full_name'];
                      if ($appointment['source'] == 'internal') {
                        echo '<a target="_blank" href="' . admin_url('clients/client/' . $appointment['details']['userid'] . '?group=contacts&contactid=' . $appointment['contact_id']) . '">' . $name . '</a>';
                      } elseif ($appointment['source'] == 'lead_related') {
                        echo '<a target="_blank" href="' . admin_url('leads/index/' . $appointment['contact_id']) . '">' . $name . '</a>';
                      } else {
                        echo $name;
                      }
                    ?>
                  </span><br>

                  <?php if (isset($appointment['details']['company_name'])): ?>
                    <span class="spmodified">
                      <boldit><?= _l('Company'); ?></boldit>
                      <a target="_blank" href="<?= admin_url('clients/client/' . $appointment['details']['userid']); ?>">
                        <?= e($appointment['details']['company_name']); ?>
                      </a>
                    </span><br>
                  <?php endif; ?>

                  <span class="spmodified">
                    <boldit><?= _l('appointment_email'); ?></boldit>
                    <?php
                      $email = $appointment['email'] ?? ($appointment['details']['email'] ?? '');
                    ?>
                    <a href="mailto:<?= $email; ?>"><?= $email; ?></a>
                  </span><br>

                  <span class="spmodified client_phone <?= ($internalStaff ? 'line-trough-grayish' : '') ?>">
                    <boldit><?= _l('appointment_phone'); ?></boldit>
                    <?php
                      $phone = $appointment['phone'] ?? ($appointment['details']['phone'] ?? '');
                      if ($phone != ''): ?>
                        <div class="client_numbers">
                          <a data-toggle="tooltip" class="label label-success"
                             title="<?= _l('appointment_send_an_sms'); ?>" href="sms:<?= $phone; ?>">SMS: <?= $phone; ?></a>
                          <a data-toggle="tooltip" class="label label-success mleft5"
                             title="<?= _l('appointment_call_number'); ?>" href="tel:<?= $phone; ?>">Call: <?= $phone; ?></a>
                        </div>
                    <?php endif; ?>
                  </span><br>

                  <?php if (!empty($appointment['address'])): ?>
                    <span class="spmodified">
                      <boldit><?= _l('appointment_location_address'); ?></boldit>
                      <a target="_blank"
                         data-toggle="tooltip"
                         title="<?= _l('appointment_google_maps'); ?>"
                         href="https://maps.google.com/?q=<?= urlencode($appointment['address']); ?>">
                        <?= $appointment['address']; ?>
                      </a>
                    </span><br>
                  <?php endif; ?>

                  <?php if (!empty($appointment['type_id'])): ?>
                    <span class="spmodified">
                      <boldit><?= _l('appointments_type_heading'); ?></boldit>
                      <?= get_appointment_type($appointment['type_id']); ?>
                    </span><br>
                  <?php endif; ?>
                                    <span class="spmodified">
                    <boldit><?= _l('appointment_email_tracking'); ?></boldit>
                    <?php
                      $isEmailRead = get_tracked_emails($appointment['id'], 'appointment');
                      if (!empty($isEmailRead) && $isEmailRead[0]['opened']) {
                        echo _l('appointment_email_read_at') . ' <strong>' . $isEmailRead[0]['date'] . '</strong>';
                      } else {
                        echo _l('appointment_email_not_read');
                      }
                    ?>
                  </span><br>
                </div> <!-- .appointly_single_container -->
              </div> <!-- .col-lg-6 -->

              <?php
              // Campos personalizados
              $custom_fields = get_custom_fields('appointly');
              $campos_preenchidos = [];

              foreach ($custom_fields as $cf) {
                  $valor = get_custom_field_value($appointment['id'], $cf['id'], 'appointly');
                  if (!empty($valor)) {
                      $campos_preenchidos[] = ['nome' => $cf['name'], 'valor' => $valor];
                  }
              }

              if (!empty($campos_preenchidos)) {
                  echo '<div class="col-md-12 mtop20">';
                  echo '<h4 class="appointly-default reorder-content">' . _l('custom_fields') . '</h4>';
                  echo '<div class="appointly_single_container">';
                  foreach ($campos_preenchidos as $campo) {
                      echo '<span class="spmodified col-md-12 mtop15">';
                      echo '<boldit>' . e($campo['nome']) . '</boldit>';
                      echo '<strong>' . e($campo['valor']) . '</strong>';
                      echo '</span><br>';
                  }
                  echo '</div></div>';
              }
              ?>
              
              <?php if (isset($appointment['notes']) && trim($appointment['notes']) !== ''): ?>
              <div class="col-md-12 mtop20">
                <h4 class="appointly-default reorder-content"><?= _l('appointment_client_notes'); ?></h4>
                <div class="appointly_single_container">
                  <div class="spmodified appointment_notes">
                    <?= e($appointment['notes']); ?>
                  </div>
                </div>
              </div>
              <?php endif; ?>
                            <?php if (
                $appointment['reminder_before_type'] !== null &&
                $appointment['reminder_before'] !== null &&
                $appointment['approved'] == 1
              ): ?>
              <div class="col-lg-12 col-xs-12">
                <h4 class="appointly-default reorder-content"><?= _l('appointment_notified'); ?></h4>
                <div class="appointly_single_container">
                  <?php if (!$internalStaff): ?>
                  <span class="spmodified">
                    <boldit><?= _l('appointment_notified_by_sms'); ?></boldit>
                    <?= ($appointment['notification_date'] !== null && $appointment['by_sms']) ? _l('appointment_yes') : _l('appointment_no'); ?>
                  </span><br>
                  <?php endif; ?>

                  <span class="spmodified">
                    <boldit><?= _l('appointment_notified_by_email'); ?></boldit>
                    <?= ($appointment['notification_date'] !== null && $appointment['by_email']) ? _l('appointment_yes') : _l('appointment_no'); ?>
                  </span><br>

                  <?php if ($appointment['notification_date'] !== null): ?>
                  <span class="spmodified mbot25">
                    <boldit class="mtop10"><?= _l('appointment_notified_at'); ?></boldit>
                    <h4 class="label label-info label-big font-medium"><?= _dt($appointment['notification_date']); ?></h4>
                  </span><br>
                  <?php endif; ?>
                </div>
              </div>
              <?php endif; ?>

              <?php if (get_option('google_api_key') && $appointment['address'] !== ''): ?>
              <div class="col-lg-12 col-xs-12 mtop20">
                <h4 class="appointly-default reorder-content"><?= _l('location'); ?> <?= _l('map'); ?></h4>
                <iframe
                  style="width:100%;height:400px;border:1px solid #cfcaca;border-radius: 4px;"
                  src="https://www.google.com/maps/embed/v1/place?key=<?= get_option('google_api_key'); ?>&q=<?= urlencode($appointment['address']); ?>"
                  allowfullscreen>
                </iframe>
              </div>
              <?php elseif (!get_option('google_api_key')): ?>
              <div id="google_not_set">
                <strong class="text-danger"><?= _l("appointly_google_maps_not_shown"); ?></strong><br>
                <?= _l("appointly_google_api_key_notset"); ?>
                <a href="<?= admin_url('settings?group=google'); ?>"><?= _l("settings_google_api"); ?></a><br>
                <span><?= _l("appointly_message_will_hide"); ?></span>
              </div>
              <?php endif; ?>

              <div class="col-md-12 mtop25">
                <div class="pull-right">
                  <?php if (staff_can('edit', 'appointments')): ?>
                    <?php if (!$appointment['cancelled'] && !$appointment['finished'] && ($appointment['created_by'] == get_staff_user_id() || staff_appointments_responsible())): ?>
                      <button class="btn btn-danger" onclick="cancelAppointment()" id="cancelAppointment"><?= _l('appointment_cancel'); ?></button>
                      <button class="btn btn-primary" onclick="markAppointmentAsFinished()" id="markAsFinished"><?= _l('appointment_mark_as_finished'); ?></button>
                    <?php endif; ?>

                    <?php if ($appointment['cancelled'] == 1 && !$appointment['finished'] && ($appointment['created_by'] == get_staff_user_id() || staff_appointments_responsible())): ?>
                      <button class="btn btn-primary" onclick="markAppointmentAsOngoing()" id="markAppointmentAsOngoing"><?= _l('appointment_mark_as_ongoing'); ?></button>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
                            <?php
              if ($appointment['finished'] == 1 && $appointment['feedback'] !== null) {
                  echo renderAppointmentFeedbacks($appointment);
              }
              ?>
            </div> <!-- end row -->
          </div> <!-- end panel-body -->
        </div> <!-- end panel_s -->
      </div> <!-- end col-md-8 -->
    </div> <!-- end row -->
  </div> <!-- end content -->
</div> <!-- end wrapper -->

<?php if (isset($appointment['google_meet_link'])): ?>
<div class="modal fade" id="customEmailModal" tabindex="-1" role="dialog" aria-labelledby="customEmailLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title" id="customEmailLabel"><?= _l('appointment_google_meet_modal_custom_label'); ?></h4>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <?php $message = '<p>' . _l('appointment_meet_message') . ' ' . $appointment['google_meet_link'] . '</p>'; ?>
          <textarea class="form-control" name="google_meet_notify_message" rows="10"><?= $message; ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= _l('close'); ?></button>
        <button type="button" id="submit_google_meet_email_btn" onclick="sendAppointmentRemindersEmail()" class="btn btn-primary"><?= _l('send'); ?></button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div id="modal_wrapper"></div>
</body>

<?php
$google_meet_attendees = [];
$google_meet_attendees[] = array_map(function ($attendee) {
    return $attendee['email'] ?? '';
}, $appointment['attendees']);
?>

<?php init_tail(); ?>
<?php require 'modules/appointly/assets/js/tables_appointment_js.php'; ?>

<script>
  $(function () {
    let el = $('body').find('#google_not_set');
    setTimeout(() => {
      if ($(el).is(":visible")) $(el).fadeOut();
    }, 5000)
  })
</script>