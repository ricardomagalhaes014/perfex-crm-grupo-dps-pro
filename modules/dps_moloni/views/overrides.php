<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-5">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-percent"></i> <?php echo _l('dps_moloni_overrides'); ?></h4>
            <p class="text-muted"><?php echo _l('dps_moloni_overrides_intro'); ?></p>

            <?php echo form_open(admin_url('dps_moloni/overrides')); ?>
              <div class="form-group">
                <label for="beneficiary"><?php echo _l('dps_moloni_override_who'); ?></label>
                <select name="beneficiary" id="beneficiary" class="form-control selectpicker" data-live-search="true">
                  <?php foreach ($commercials as $name) { ?>
                    <option value="<?php echo html_escape($name); ?>"><?php echo html_escape($name); ?></option>
                  <?php } ?>
                </select>
              </div>

              <?php echo render_input('rate', 'dps_moloni_override_rate', ''); ?>

              <div class="form-group">
                <label for="excluded"><?php echo _l('dps_moloni_override_excluded'); ?></label>
                <select name="excluded[]" id="excluded" class="form-control selectpicker"
                        multiple data-live-search="true" data-actions-box="true">
                  <?php foreach ($commercials as $name) { ?>
                    <option value="<?php echo html_escape($name); ?>"><?php echo html_escape($name); ?></option>
                  <?php } ?>
                </select>
                <span class="help-block small"><?php echo _l('dps_moloni_override_excluded_hint'); ?></span>
              </div>

              <?php echo render_input('note', 'dps_moloni_override_note', ''); ?>

              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="active" id="active" value="1" checked>
                <label for="active"><?php echo _l('dps_moloni_override_active'); ?></label>
              </div>

              <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>

      <div class="col-md-7">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop bold"><?php echo _l('dps_moloni_overrides_current'); ?></h5>

            <?php if (empty($calculated)) { ?>
              <p class="text-muted"><?php echo _l('dps_moloni_overrides_none'); ?></p>
            <?php } else { ?>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th><?php echo _l('dps_moloni_override_who'); ?></th>
                      <th class="text-center"><?php echo _l('dps_moloni_override_rate'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_override_base'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_override_amount'); ?></th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($calculated as $row) { ?>
                      <tr>
                        <td>
                          <strong><?php echo html_escape($row['beneficiary']); ?></strong>
                          <?php if ($row['excluded'] !== '') { ?>
                            <br><small class="text-muted">
                              <?php echo _l('dps_moloni_override_excluding'); ?>
                              <?php echo html_escape($row['excluded']); ?>
                            </small>
                          <?php } ?>
                          <?php if ($row['note'] !== '') { ?>
                            <br><small class="text-muted"><?php echo html_escape($row['note']); ?></small>
                          <?php } ?>
                        </td>
                        <td class="text-center"><?php echo rtrim(rtrim(number_format($row['rate'], 4, ',', ''), '0'), ','); ?>%</td>
                        <td class="text-right">
                          <?php echo dps_moloni_money($row['base']); ?><br>
                          <small class="text-muted">
                            <?php echo (int) $row['counted']; ?> <?php echo _l('dps_moloni_kpi_lines'); ?>
                            <?php if ($row['skipped'] > 0) { ?>
                              · <?php echo (int) $row['skipped']; ?> <?php echo _l('dps_moloni_override_skipped'); ?>
                            <?php } ?>
                          </small>
                        </td>
                        <td class="text-right bold"><?php echo dps_moloni_money($row['amount']); ?></td>
                        <td class="text-right">
                          <?php if ($row['id']) { ?>
                            <a href="<?php echo admin_url('dps_moloni/remover_override/' . $row['id']); ?>"
                               class="text-danger"
                               onclick="return confirm('<?php echo _l('dps_moloni_unlink_confirm'); ?>');">
                              <i class="fa fa-times"></i>
                            </a>
                          <?php } ?>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th colspan="3" class="text-right"><?php echo _l('dps_moloni_override_total'); ?></th>
                      <th class="text-right"><?php echo dps_moloni_money(dps_moloni_overrides_total($calculated)); ?></th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            <?php } ?>

            <?php
            $inactive = array_filter($overrides, function ($o) { return (int) $o['active'] !== 1; });
            if (!empty($inactive)) { ?>
              <hr>
              <p class="text-muted small"><?php echo _l('dps_moloni_overrides_inactive'); ?></p>
              <ul class="text-muted small">
                <?php foreach ($inactive as $o) { ?>
                  <li>
                    <?php echo html_escape($o['beneficiary']); ?> — <?php echo html_escape($o['rate']); ?>%
                    <a href="<?php echo admin_url('dps_moloni/remover_override/' . (int) $o['id']); ?>"
                       class="text-danger mleft5"
                       onclick="return confirm('<?php echo _l('dps_moloni_unlink_confirm'); ?>');">
                      <i class="fa fa-times"></i>
                    </a>
                  </li>
                <?php } ?>
              </ul>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
