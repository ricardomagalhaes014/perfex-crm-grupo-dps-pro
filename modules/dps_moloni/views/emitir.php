<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-file-text-o"></i>
              <?php echo $kind === 'invoice'
                  ? _l('dps_moloni_issue_invoice')
                  : _l('dps_moloni_issue_receipt'); ?>
              — <?php echo _l('dps_moloni_sale'); ?> #<?php echo html_escape($sale['id']); ?>
            </h4>

            <?php if ($settings['always_draft'] === '1') { ?>
              <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <?php echo _l('dps_moloni_draft_notice'); ?>
              </div>
            <?php } ?>

            <?php if ($api_error) { ?>
              <div class="alert alert-danger"><?php echo html_escape($api_error); ?></div>
            <?php } ?>

            <table class="table table-condensed mbot20">
              <tr>
                <td class="bold" width="35%"><?php echo _l('dps_moloni_col_project'); ?></td>
                <td><?php echo html_escape($sale['project']); ?> <?php echo html_escape($sale['unit']); ?></td>
              </tr>
              <tr>
                <td class="bold"><?php echo _l('dps_moloni_col_client'); ?></td>
                <td><?php echo html_escape($sale['client']); ?></td>
              </tr>
              <tr>
                <td class="bold"><?php echo _l('dps_moloni_col_commercial'); ?></td>
                <td><?php echo html_escape($sale['commercial']); ?></td>
              </tr>
              <tr>
                <td class="bold"><?php echo _l('dps_moloni_col_sale_value'); ?></td>
                <td><?php echo dps_moloni_money($sale['sale_value']); ?></td>
              </tr>
              <tr>
                <td class="bold"><?php echo _l('dps_moloni_col_commission'); ?></td>
                <td><?php echo dps_moloni_money($sale['commission']); ?></td>
              </tr>
              <tr>
                <td class="bold"><?php echo _l('dps_moloni_col_received'); ?></td>
                <td><?php echo dps_moloni_money($sale['received']); ?></td>
              </tr>
            </table>

            <?php echo form_open(admin_url('dps_moloni/emitir_confirmar')); ?>
              <input type="hidden" name="sale_id" value="<?php echo (int) $sale['id']; ?>">
              <input type="hidden" name="kind" value="<?php echo html_escape($kind); ?>">

              <?php echo render_input('entity_name', 'dps_moloni_entity', $entity_name); ?>
              <?php echo render_input('vat', 'dps_moloni_vat', ''); ?>
              <p class="text-muted small"><?php echo _l('dps_moloni_vat_hint'); ?></p>

              <?php echo render_input('description', 'dps_moloni_description', _l('dps_moloni_default_line')); ?>

              <div class="row">
                <div class="col-md-4">
                  <?php echo render_input('amount', 'dps_moloni_amount', number_format($amount, 2, '.', '')); ?>
                </div>
                <div class="col-md-4">
                  <?php echo render_input('date', 'dps_moloni_date', date('Y-m-d'), 'date'); ?>
                </div>
                <div class="col-md-4">
                  <?php echo render_input('expiration_date', 'dps_moloni_expiration', date('Y-m-d'), 'date'); ?>
                </div>
              </div>

              <div class="form-group">
                <label for="document_set_id"><?php echo _l('dps_moloni_set'); ?></label>
                <select name="document_set_id" id="document_set_id" class="form-control selectpicker" data-live-search="true">
                  <option value=""><?php echo _l('dps_moloni_choose'); ?></option>
                  <?php
                  $preferred = $kind === 'invoice' ? $settings['set_invoice'] : $settings['set_receipt'];
                  foreach ($sets as $set) { ?>
                    <option value="<?php echo (int) $set['document_set_id']; ?>"
                      <?php echo (string) $preferred === (string) $set['document_set_id'] ? 'selected' : ''; ?>>
                      <?php echo html_escape($set['name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="form-group">
                <label for="tax_id"><?php echo _l('dps_moloni_tax'); ?></label>
                <select name="tax_id" id="tax_id" class="form-control selectpicker" data-live-search="true">
                  <option value="0"><?php echo _l('dps_moloni_no_tax'); ?></option>
                  <?php
                  $preferred_tax = $kind === 'invoice' ? $settings['tax_invoice'] : $settings['tax_receipt'];
                  foreach ($taxes as $tax) { ?>
                    <option value="<?php echo (int) $tax['tax_id']; ?>"
                      <?php echo (string) $preferred_tax === (string) $tax['tax_id'] ? 'selected' : ''; ?>>
                      <?php echo html_escape($tax['name']); ?> — <?php echo html_escape($tax['value']); ?>%
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="form-group">
                <label for="notes"><?php echo _l('dps_moloni_notes'); ?></label>
                <textarea name="notes" id="notes" rows="2" class="form-control"></textarea>
              </div>

              <?php if ($settings['always_draft'] !== '1') { ?>
                <div class="checkbox checkbox-warning">
                  <input type="checkbox" name="close_document" id="close_document" value="1">
                  <label for="close_document"><?php echo _l('dps_moloni_close_document'); ?></label>
                </div>
                <p class="text-muted small"><?php echo _l('dps_moloni_close_hint'); ?></p>
              <?php } ?>

              <hr>
              <button type="submit" class="btn btn-primary">
                <i class="fa fa-paper-plane"></i> <?php echo _l('dps_moloni_create_in_moloni'); ?>
              </button>
              <a href="<?php echo admin_url('dps_moloni'); ?>" class="btn btn-default"><?php echo _l('cancel'); ?></a>
            <?php echo form_close(); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
