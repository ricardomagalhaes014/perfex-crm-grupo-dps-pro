<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper" class="customer_profile">
  <div class="content">
    <div class="md:tw-w-[calc(100%-theme(width.64)+theme(spacing.16))] [&_div:last-child]:tw-mb-6">
      <?php if (isset($client) && $client->registration_confirmed == 0 && is_admin()) { ?>
        <div class="alert alert-warning">
          <h4><?= _l('customer_requires_registration_confirmation'); ?></h4>
          <a href="<?= admin_url('clients/confirm_registration/' . $client->userid); ?>" class="alert-link">
            <?= _l('confirm_registration'); ?>
          </a>
        </div>
      <?php } elseif (isset($client) && $client->active == 0 && $client->registration_confirmed == 1) { ?>
        <div class="alert alert-warning">
          <?= _l('customer_inactive_message'); ?>
          <br />
          <a href="<?= admin_url('clients/mark_as_active/' . $client->userid); ?>" class="alert-link">
            <?= _l('mark_as_active'); ?>
          </a>
        </div>
      <?php } ?>
      <?php if (isset($client) && (staff_cant('view', 'customers') && is_customer_admin($client->userid))) { ?>
        <div class="alert alert-info">
          <?= e(_l('customer_admin_login_as_client_message', get_staff_full_name(get_staff_user_id()))); ?>
        </div>
      <?php } ?>
    </div>

    <?php if (isset($client) && $client->leadid != null) { ?>
      <small class="tw-block">
        <b><?= e(_l('customer_from_lead', _l('lead'))); ?></b>
        <a href="<?= admin_url('leads/index/' . $client->leadid); ?>" onclick="init_lead(<?= e($client->leadid); ?>); return false;">- <?= _l('view'); ?></a>
      </small>
    <?php } ?>

    <div class="md:tw-max-w-64 tw-w-full">
      <?php if (isset($client)) { ?>
        <h4 class="tw-text-lg tw-font-bold tw-text-neutral-800 tw-mt-0">
          <div class="tw-space-x-3 tw-flex tw-items-center tw-flex-wrap">
            <span class="tw-truncate">#<?= e($client->userid . ' ' . $title); ?></span>

            <?php
              // Opções de Status
              $status_options = [
                'Novo',
                'Contactado',
                'Interessado',
                'Sem resposta',
                'Follow-up',
                'Reunião marcada',
                'Proposta enviada',
                'Negociação',
                'Fechado (ganho)',
                'Fechado (perdido)',
              ];
              if (function_exists('hooks')) {
                $status_options = hooks()->apply_filters('customer_status_options', $status_options);
              }
              $current_status = isset($client->status) && $client->status !== '' ? $client->status : 'Novo';

              // Staff atribuído
              $current_assigned = isset($client->assigned) ? $client->assigned : null;
              $staff_list = $this->staff_model->get(); // todos staff
              $current_user = get_staff_user_id();
            ?>

            <!-- Dropdown de Status -->
            <div class="tw-flex tw-items-center tw-gap-2">
              <label class="tw-text-xs tw-text-neutral-500 tw-mr-1">
                <?= _l('status'); ?>:
              </label>
              <select id="client-status"
                      class="selectpicker"
                      data-width="fit"
                      data-live-search="false">
                <?php foreach ($status_options as $opt): ?>
                  <option value="<?= e($opt); ?>" <?= ($current_status === $opt ? 'selected' : ''); ?>>
                    <?= e($opt); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Dropdown de Atribuído -->
            <div class="tw-flex tw-items-center tw-gap-2">
              <label class="tw-text-xs tw-text-neutral-500 tw-mr-1">
                <?= _l('assigned'); ?>:
              </label>
              <select id="client-assigned"
                      class="selectpicker"
                      data-width="fit"
                      data-live-search="true">
                <?php if (is_admin()): ?>
                  <?php foreach ($staff_list as $s): ?>
                    <option value="<?= $s['staffid']; ?>" <?= ($current_assigned == $s['staffid'] ? 'selected' : ''); ?>>
                      <?= e($s['firstname'] . ' ' . $s['lastname']); ?>
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="<?= $current_user; ?>" <?= ($current_assigned == $current_user ? 'selected' : ''); ?>>
                    <?= e(get_staff_full_name($current_user)); ?>
                  </option>
                <?php endif; ?>
              </select>
            </div>

            <!-- Botão Marcar Visita -->
            <a href="<?= admin_url('appointly/appointments?new=1&rel_type=customer&rel_id=' . $client->userid); ?>"
               class="btn btn-success btn-sm">
              <i class="fa fa-calendar"></i> <?= _l('book_appointment'); ?>
            </a>

            <!-- Botão Ver Visitas -->
            <a href="<?= admin_url('appointly/appointments?rel_type=customer&rel_id=' . $client->userid); ?>"
               class="btn btn-info btn-sm">
              <i class="fa fa-list"></i> <?= _l('view_appointments'); ?>
            </a>

            <?php if (staff_can('delete', 'customers') || is_admin()) { ?>
              <div class="btn-group">
                <a href="#" class="dropdown-toggle btn-link" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <span class="caret"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
                  <?php if (is_admin()) { ?>
                    <li>
                      <a href="<?= admin_url('clients/login_as_client/' . $client->userid); ?>" target="_blank">
                        <i class="fa-regular fa-share-from-square"></i> <?= _l('login_as_client'); ?>
                      </a>
                    </li>
                  <?php } ?>
                  <?php if (staff_can('delete', 'customers')) { ?>
                    <li>
                      <a href="<?= admin_url('clients/delete/' . $client->userid); ?>" class="text-danger delete-text _delete">
                        <i class="fa fa-remove"></i> <?= _l('delete'); ?>
                      </a>
                    </li>
                  <?php } ?>
                </ul>
              </div>
            <?php } ?>
          </div>
        </h4>
      <?php } ?>
    </div>

    <div class="md:tw-flex md:tw-gap-6">
      <?php if (isset($client)) { ?>
        <div class="md:tw-max-w-64 tw-w-full">
          <?php $this->load->view('admin/clients/tabs'); ?>
        </div>
      <?php } ?>
      <div class="tw-mt-12 md:tw-mt-0 tw-w-full <?= isset($client) ? 'tw-max-w-6xl' : 'tw-mx-auto tw-max-w-4xl'; ?>">
        <?php if (!isset($client)) { ?>
          <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700"><?= $title ?></h4>
        <?php } ?>

        <div class="panel_s">
          <div class="panel-body">
            <?php if (isset($client)) { ?>
              <?= form_hidden('isedit'); ?>
              <?= form_hidden('userid', $client->userid); ?>
              <div class="clearfix"></div>
            <?php } ?>
            <div>
              <div class="tab-content">
                <?php $this->load->view((isset($tab) ? $tab['view'] : 'admin/clients/groups/profile')); ?>
              </div>
            </div>
          </div>
          <?php if ($group == 'profile') { ?>
            <div class="panel-footer text-right tw-space-x-1" id="profile-save-section">
              <?php if (!isset($client)) { ?>
                <button class="btn btn-default save-and-add-contact customer-form-submiter">
                  <?= _l('save_customer_and_add_contact'); ?>
                </button>
              <?php } ?>
              <button class="btn btn-primary only-save customer-form-submiter">
                <?= _l('submit'); ?>
              </button>
            </div>
          <?php } ?>
        </div>
      </div>
    </div>

  </div>
</div>
<?php init_tail(); ?>
<?php if (isset($client)) { ?>
<script>
  $(function () {
    init_rel_tasks_table(<?= e($client->userid); ?>, 'customer');

    // Atualiza STATUS via AJAX
    $('#client-status').on('changed.bs.select', function () {
      var newStatus = $(this).val();
      $.post(admin_url + 'clients/update_status', {
        userid: <?= json_encode($client->userid); ?>,
        status: newStatus
      }).done(function () {
        alert_float('success', 'Status atualizado');
      }).fail(function () {
        alert_float('danger', 'Falha ao atualizar o status');
      });
    });

    // Atualiza ATRIBUÍDO via AJAX
    $('#client-assigned').on('changed.bs.select', function () {
      var newAssigned = $(this).val();
      $.post(admin_url + 'clients/update_assigned', {
        userid: <?= json_encode($client->userid); ?>,
        assigned: newAssigned
      }).done(function () {
        alert_float('success', 'Atribuído atualizado');
      }).fail(function () {
        alert_float('danger', 'Falha ao atualizar atribuído');
      });
    });
  });
</script>
<?php } ?>
<?php $this->load->view('admin/clients/client_js'); ?>
</body>
</html>