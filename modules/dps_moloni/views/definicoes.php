<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-mtop">
              <i class="fa fa-plug"></i> <?php echo _l('dps_moloni_definicoes'); ?>
              <?php if ($connected) { ?>
                <span class="label label-success pull-right"><?php echo _l('dps_moloni_connected'); ?></span>
              <?php } else { ?>
                <span class="label label-default pull-right"><?php echo _l('dps_moloni_disconnected'); ?></span>
              <?php } ?>
            </h4>
            <p class="text-muted">
              <?php echo _l('dps_moloni_settings_intro'); ?>
            </p>

            <?php if ($api_error) { ?>
              <div class="alert alert-danger"><?php echo html_escape($api_error); ?></div>
            <?php } ?>

            <hr>

            <?php echo form_open(admin_url('dps_moloni/definicoes'), ['autocomplete' => 'off']); ?>

              <h5 class="bold"><?php echo _l('dps_moloni_credentials'); ?></h5>

              <?php if ($settings['client_secret'] === '' || $settings['password'] === '') { ?>
                <div class="alert alert-info">
                  <?php echo _l('dps_moloni_import_hint'); ?>
                  <a href="<?php echo admin_url('dps_moloni/importar_credenciais'); ?>" class="bold">
                    <?php echo _l('dps_moloni_import'); ?>
                  </a>
                </div>
              <?php } ?>

              <?php echo render_input('dev_id', 'dps_moloni_dev_id', $settings['dev_id']); ?>

              <div class="form-group">
                <label for="client_secret"><?php echo _l('dps_moloni_client_secret'); ?></label>
                <input type="password" name="client_secret" id="client_secret" class="form-control"
                       autocomplete="new-password"
                       placeholder="<?php echo $settings['client_secret'] !== '' ? _l('dps_moloni_stored_leave_blank') : ''; ?>">
              </div>

              <?php echo render_input('username', 'dps_moloni_username', $settings['username'], 'email'); ?>

              <div class="form-group">
                <label for="password"><?php echo _l('dps_moloni_password'); ?></label>
                <input type="password" name="password" id="password" class="form-control"
                       autocomplete="new-password"
                       placeholder="<?php echo $settings['password'] !== '' ? _l('dps_moloni_stored_leave_blank') : ''; ?>">
              </div>

              <hr>
              <h5 class="bold"><?php echo _l('dps_moloni_company'); ?></h5>

              <?php if (!empty($companies)) { ?>
                <div class="form-group">
                  <label for="company_id"><?php echo _l('dps_moloni_company'); ?></label>
                  <select name="company_id" id="company_id" class="form-control selectpicker" data-live-search="true">
                    <option value=""><?php echo _l('dps_moloni_choose'); ?></option>
                    <?php foreach ($companies as $company) { ?>
                      <option value="<?php echo (int) $company['company_id']; ?>"
                        <?php echo (string) $settings['company_id'] === (string) $company['company_id'] ? 'selected' : ''; ?>>
                        <?php echo html_escape($company['name'] ?? ('#' . $company['company_id'])); ?>
                        (#<?php echo (int) $company['company_id']; ?>)
                      </option>
                    <?php } ?>
                  </select>
                </div>
              <?php } else { ?>
                <?php echo render_input('company_id', 'dps_moloni_company_id', $settings['company_id']); ?>
                <p class="text-muted small"><?php echo _l('dps_moloni_company_hint'); ?></p>
              <?php } ?>

              <?php if (!empty($sets)) { ?>
                <hr>
                <h5 class="bold"><?php echo _l('dps_moloni_series'); ?></h5>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="set_invoice"><?php echo _l('dps_moloni_set_invoice'); ?></label>
                      <select name="set_invoice" id="set_invoice" class="form-control selectpicker" data-live-search="true">
                        <option value=""><?php echo _l('dps_moloni_choose'); ?></option>
                        <?php foreach ($sets as $set) { ?>
                          <option value="<?php echo (int) $set['document_set_id']; ?>"
                            <?php echo (string) $settings['set_invoice'] === (string) $set['document_set_id'] ? 'selected' : ''; ?>>
                            <?php echo html_escape($set['name']); ?>
                          </option>
                        <?php } ?>
                      </select>
                      <span class="help-block small"><?php echo _l('dps_moloni_set_invoice_hint'); ?></span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="set_receipt"><?php echo _l('dps_moloni_set_receipt'); ?></label>
                      <select name="set_receipt" id="set_receipt" class="form-control selectpicker" data-live-search="true">
                        <option value=""><?php echo _l('dps_moloni_choose'); ?></option>
                        <?php foreach ($sets as $set) { ?>
                          <option value="<?php echo (int) $set['document_set_id']; ?>"
                            <?php echo (string) $settings['set_receipt'] === (string) $set['document_set_id'] ? 'selected' : ''; ?>>
                            <?php echo html_escape($set['name']); ?>
                          </option>
                        <?php } ?>
                      </select>
                      <span class="help-block small"><?php echo _l('dps_moloni_set_receipt_hint'); ?></span>
                    </div>
                  </div>
                </div>
              <?php } ?>

              <?php if (!empty($taxes)) { ?>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="tax_invoice"><?php echo _l('dps_moloni_tax_invoice'); ?></label>
                      <select name="tax_invoice" id="tax_invoice" class="form-control selectpicker" data-live-search="true">
                        <option value=""><?php echo _l('dps_moloni_no_tax'); ?></option>
                        <?php foreach ($taxes as $tax) { ?>
                          <option value="<?php echo (int) $tax['tax_id']; ?>"
                            <?php echo (string) $settings['tax_invoice'] === (string) $tax['tax_id'] ? 'selected' : ''; ?>>
                            <?php echo html_escape($tax['name']); ?> — <?php echo html_escape($tax['value']); ?>%
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="tax_receipt"><?php echo _l('dps_moloni_tax_receipt'); ?></label>
                      <select name="tax_receipt" id="tax_receipt" class="form-control selectpicker" data-live-search="true">
                        <option value=""><?php echo _l('dps_moloni_no_tax'); ?></option>
                        <?php foreach ($taxes as $tax) { ?>
                          <option value="<?php echo (int) $tax['tax_id']; ?>"
                            <?php echo (string) $settings['tax_receipt'] === (string) $tax['tax_id'] ? 'selected' : ''; ?>>
                            <?php echo html_escape($tax['name']); ?> — <?php echo html_escape($tax['value']); ?>%
                          </option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                </div>

                <?php echo render_input('exemption_reason', 'dps_moloni_exemption_reason', $settings['exemption_reason']); ?>
                <p class="text-muted small"><?php echo _l('dps_moloni_exemption_hint'); ?></p>
              <?php } ?>

              <?php if (!empty($products)) { ?>
                <div class="form-group">
                  <label for="product_commission"><?php echo _l('dps_moloni_product'); ?></label>
                  <select name="product_commission" id="product_commission" class="form-control selectpicker" data-live-search="true">
                    <option value=""><?php echo _l('dps_moloni_free_line'); ?></option>
                    <?php foreach ($products as $product) { ?>
                      <option value="<?php echo (int) $product['product_id']; ?>"
                        <?php echo (string) $settings['product_commission'] === (string) $product['product_id'] ? 'selected' : ''; ?>>
                        <?php echo html_escape($product['reference'] ?? ''); ?>
                        <?php echo html_escape($product['name']); ?>
                      </option>
                    <?php } ?>
                  </select>
                  <span class="help-block small"><?php echo _l('dps_moloni_product_hint'); ?></span>
                </div>
              <?php } ?>

              <hr>
              <h5 class="bold"><?php echo _l('dps_moloni_document_classes'); ?></h5>
              <div class="row">
                <div class="col-md-6">
                  <?php echo render_input('document_class_invoice', 'dps_moloni_class_invoice', $settings['document_class_invoice']); ?>
                </div>
                <div class="col-md-6">
                  <?php echo render_input('document_class_receipt', 'dps_moloni_class_receipt', $settings['document_class_receipt']); ?>
                </div>
              </div>
              <p class="text-muted small"><?php echo _l('dps_moloni_class_hint'); ?></p>

              <hr>
              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="always_draft" id="always_draft" value="1"
                  <?php echo $settings['always_draft'] === '1' ? 'checked' : ''; ?>>
                <label for="always_draft"><?php echo _l('dps_moloni_always_draft'); ?></label>
              </div>
              <p class="text-muted small"><?php echo _l('dps_moloni_always_draft_hint'); ?></p>

              <div class="checkbox checkbox-primary">
                <input type="checkbox" name="auto_create_customers" id="auto_create_customers" value="1"
                  <?php echo $settings['auto_create_customers'] === '1' ? 'checked' : ''; ?>>
                <label for="auto_create_customers"><?php echo _l('dps_moloni_auto_create_customers'); ?></label>
              </div>

              <hr>
              <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
              <a href="<?php echo admin_url('dps_moloni/testar'); ?>" class="btn btn-success">
                <i class="fa fa-bolt"></i> <?php echo _l('dps_moloni_test'); ?>
              </a>
              <a href="<?php echo admin_url('dps_moloni/mapeamento'); ?>" class="btn btn-default">
                <?php echo _l('dps_moloni_mapeamento'); ?>
              </a>

            <?php echo form_close(); ?>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop bold"><?php echo _l('dps_moloni_howto'); ?></h5>
            <p class="text-muted">
              <?php echo _l('dps_moloni_howto_1'); ?>
            </p>
            <p class="text-muted">
              <?php echo _l('dps_moloni_howto_2'); ?><br>
              <code><?php echo html_escape($callback_url); ?></code>
            </p>
            <p class="text-muted">
              <?php echo _l('dps_moloni_howto_3'); ?>
            </p>
          </div>
        </div>

        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-mtop bold"><?php echo _l('dps_moloni_security'); ?></h5>
            <p class="text-muted small"><?php echo _l('dps_moloni_security_note'); ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
