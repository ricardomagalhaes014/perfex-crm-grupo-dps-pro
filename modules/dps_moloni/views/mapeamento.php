<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop"><i class="fa fa-random"></i> <?php echo _l('dps_moloni_mapeamento'); ?></h4>
            <p class="text-muted"><?php echo _l('dps_moloni_mapping_intro'); ?></p>

            <?php echo form_open(admin_url('dps_moloni/mapeamento'), ['id' => 'dps-mapping-form']); ?>

              <div class="form-group">
                <label for="src_table"><?php echo _l('dps_moloni_src_table'); ?></label>
                <select name="src_table" id="src_table" class="form-control selectpicker" data-live-search="true">
                  <option value=""><?php echo _l('dps_moloni_choose'); ?></option>
                  <?php if (!empty($candidates)) { ?>
                    <optgroup label="<?php echo _l('dps_moloni_suggested_tables'); ?>">
                      <?php foreach ($candidates as $table) { ?>
                        <option value="<?php echo html_escape($table); ?>"
                          <?php echo $settings['src_table'] === $table ? 'selected' : ''; ?>>
                          <?php echo html_escape($table); ?>
                        </option>
                      <?php } ?>
                    </optgroup>
                  <?php } ?>
                  <optgroup label="<?php echo _l('dps_moloni_all_tables'); ?>">
                    <?php foreach ($tables as $table) { ?>
                      <option value="<?php echo html_escape($table); ?>"
                        <?php echo $settings['src_table'] === $table ? 'selected' : ''; ?>>
                        <?php echo html_escape($table); ?>
                      </option>
                    <?php } ?>
                  </optgroup>
                </select>
              </div>

              <button type="button" class="btn btn-default btn-sm mbot15" id="dps-suggest">
                <i class="fa fa-magic"></i> <?php echo _l('dps_moloni_autodetect'); ?>
              </button>

              <div class="row">
                <?php
                $columns_map = [
                    'src_col_id'             => 'dps_moloni_col_id',
                    'src_col_project'        => 'dps_moloni_col_project',
                    'src_col_unit'           => 'dps_moloni_col_unit',
                    'src_col_client'         => 'dps_moloni_col_client',
                    'src_col_commercial'     => 'dps_moloni_col_commercial',
                    'src_col_sale_value'     => 'dps_moloni_col_sale_value',
                    'src_col_commission'     => 'dps_moloni_col_commission',
                    'src_col_received'       => 'dps_moloni_col_received',
                    'src_col_receipt_flag'   => 'dps_moloni_col_receipt_flag',
                    'src_col_receipt_number' => 'dps_moloni_col_receipt_number',
                    'src_col_date'           => 'dps_moloni_col_date',
                ];

                foreach ($columns_map as $field => $label) { ?>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="<?php echo $field; ?>"><?php echo _l($label); ?></label>
                      <select name="<?php echo $field; ?>" id="<?php echo $field; ?>"
                              class="form-control dps-col-select" data-selected="<?php echo html_escape($settings[$field]); ?>">
                        <option value="">—</option>
                        <?php foreach ($columns as $column) { ?>
                          <option value="<?php echo html_escape($column); ?>"
                            <?php echo $settings[$field] === $column ? 'selected' : ''; ?>>
                            <?php echo html_escape($column); ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                <?php } ?>
              </div>

              <div class="checkbox checkbox-primary mbot15">
                <input type="checkbox" name="src_commercial_is_staff" id="src_commercial_is_staff" value="1"
                  <?php echo $settings['src_commercial_is_staff'] === '1' ? 'checked' : ''; ?>>
                <label for="src_commercial_is_staff"><?php echo _l('dps_moloni_commercial_is_staff'); ?></label>
              </div>

              <hr>
              <h5 class="bold"><?php echo _l('dps_moloni_overlay'); ?></h5>
              <p class="text-muted"><?php echo _l('dps_moloni_overlay_intro'); ?></p>

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="src_overlay_table"><?php echo _l('dps_moloni_overlay_table'); ?></label>
                    <select name="src_overlay_table" id="src_overlay_table" class="form-control selectpicker" data-live-search="true">
                      <option value="">— <?php echo _l('dps_moloni_overlay_none'); ?> —</option>
                      <?php foreach ($tables as $table) { ?>
                        <option value="<?php echo html_escape($table); ?>"
                          <?php echo $settings['src_overlay_table'] === $table ? 'selected' : ''; ?>>
                          <?php echo html_escape($table); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <?php
                $overlay_map = [
                    'src_overlay_fk'        => 'dps_moloni_overlay_fk',
                    'ov_col_received'       => 'dps_moloni_col_received',
                    'ov_col_receipt_flag'   => 'dps_moloni_col_receipt_flag',
                    'ov_col_receipt_number' => 'dps_moloni_col_receipt_number',
                    'ov_col_moloni_doc'     => 'dps_moloni_overlay_doc',
                ];

                foreach ($overlay_map as $field => $label) { ?>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="<?php echo $field; ?>"><?php echo _l($label); ?></label>
                      <select name="<?php echo $field; ?>" id="<?php echo $field; ?>"
                              class="form-control dps-ov-select" data-selected="<?php echo html_escape($settings[$field]); ?>">
                        <option value="">—</option>
                        <?php foreach ($overlay_columns as $column) { ?>
                          <option value="<?php echo html_escape($column); ?>"
                            <?php echo $settings[$field] === $column ? 'selected' : ''; ?>>
                            <?php echo html_escape($column); ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                <?php } ?>
              </div>

              <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
              <a href="<?php echo admin_url('dps_moloni/definicoes'); ?>" class="btn btn-default"><?php echo _l('dps_moloni_definicoes'); ?></a>

            <?php echo form_close(); ?>
          </div>
        </div>

        <?php if (!empty($preview)) { ?>
          <div class="panel_s">
            <div class="panel-body">
              <h5 class="no-mtop bold"><?php echo _l('dps_moloni_preview'); ?></h5>
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th><?php echo _l('dps_moloni_col_project'); ?></th>
                      <th><?php echo _l('dps_moloni_col_unit'); ?></th>
                      <th><?php echo _l('dps_moloni_col_client'); ?></th>
                      <th><?php echo _l('dps_moloni_col_commercial'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_col_sale_value'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_col_commission'); ?></th>
                      <th class="text-right"><?php echo _l('dps_moloni_col_received'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($preview as $row) { ?>
                      <tr>
                        <td><?php echo html_escape($row['id']); ?></td>
                        <td><?php echo html_escape($row['project']); ?></td>
                        <td><?php echo html_escape($row['unit']); ?></td>
                        <td><?php echo html_escape($row['client']); ?></td>
                        <td><?php echo html_escape($row['commercial']); ?></td>
                        <td class="text-right"><?php echo dps_moloni_money($row['sale_value']); ?></td>
                        <td class="text-right"><?php echo dps_moloni_money($row['commission']); ?></td>
                        <td class="text-right"><?php echo dps_moloni_money($row['received']); ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
              <p class="text-muted small"><?php echo _l('dps_moloni_preview_hint'); ?></p>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
<script>
  var DPS_MOLONI_COLUMNS_URL = '<?php echo admin_url('dps_moloni/colunas'); ?>';
</script>
<?php init_tail(); ?>
