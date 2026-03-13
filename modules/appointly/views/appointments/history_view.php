<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <h3 class="tw-text-xl tw-font-semibold">
          <?= _l('appointment_history_label') ?: 'Histórico de Marcações'; ?>
        </h3>
        <hr />

        <div class="panel_s">
          <div class="panel-body">

            <?php if (isset($appointments) && is_array($appointments) && count($appointments) > 0) { ?>
              <div class="table-responsive">
                <table class="table table-hover table-bordered">
                  <thead>
                    <tr>
                      <th><?= _l('id'); ?></th>
                      <th><?= _l('appointment_subject'); ?></th>
                      <th><?= _l('appointment_meeting_date'); ?></th>
                      <th><?= _l('appointment_description'); ?></th>
                      <th><?= _l('appointment_source'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($appointments as $a) { ?>
                      <tr>
                        <td><a href="/admin/appointly/appointments/view?appointment_id=<?= htmlspecialchars($a['id']); ?>"><?= htmlspecialchars($a['id']); ?></a></td>
                        <td><?= htmlspecialchars($a['subject']); ?></td>
                        <td><?= _dt($a['date'] . ' ' . $a['start_hour']); ?></td>
                        <td><?= htmlspecialchars($a['description']); ?></td>
                        <td><?= htmlspecialchars($a['source']); ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } else { ?>
              <div class="alert alert-info">
                <?= _l('appointment_no_results') ?: 'Nenhuma marcação encontrada para este cliente.'; ?>
              </div>
            <?php } ?>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
</body>
</html>