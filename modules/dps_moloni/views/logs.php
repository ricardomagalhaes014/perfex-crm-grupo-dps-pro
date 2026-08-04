<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-list"></i> <?php echo _l('dps_moloni_logs'); ?>
              <a href="<?php echo admin_url('dps_moloni/limpar_logs'); ?>"
                 class="btn btn-default btn-sm pull-right"
                 onclick="return confirm('<?php echo _l('dps_moloni_clear_logs_confirm'); ?>');">
                <?php echo _l('dps_moloni_clear_logs'); ?>
              </a>
            </h4>
            <p class="text-muted"><?php echo _l('dps_moloni_logs_intro'); ?></p>

            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th><?php echo _l('dps_moloni_date'); ?></th>
                    <th><?php echo _l('dps_moloni_endpoint'); ?></th>
                    <th><?php echo _l('dps_moloni_status'); ?></th>
                    <th><?php echo _l('dps_moloni_message'); ?></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($logs as $log) { ?>
                    <tr>
                      <td class="text-muted small"><?php echo html_escape($log['date_create']); ?></td>
                      <td><code><?php echo html_escape($log['endpoint']); ?></code></td>
                      <td>
                        <?php if ($log['status'] === 'ok') { ?>
                          <span class="label label-success">ok</span>
                        <?php } else { ?>
                          <span class="label label-danger"><?php echo html_escape($log['status']); ?></span>
                        <?php } ?>
                      </td>
                      <td><small><?php echo html_escape($log['message']); ?></small></td>
                      <td>
                        <a href="#" class="dps-toggle-detail" data-target="#log-<?php echo (int) $log['id']; ?>">
                          <?php echo _l('dps_moloni_detail'); ?>
                        </a>
                      </td>
                    </tr>
                    <tr id="log-<?php echo (int) $log['id']; ?>" class="hide">
                      <td colspan="5">
                        <strong><?php echo _l('dps_moloni_request'); ?></strong>
                        <pre class="dps-log-pre"><?php echo html_escape($log['request']); ?></pre>
                        <strong><?php echo _l('dps_moloni_response'); ?></strong>
                        <pre class="dps-log-pre"><?php echo html_escape($log['response']); ?></pre>
                      </td>
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
<?php init_tail(); ?>
