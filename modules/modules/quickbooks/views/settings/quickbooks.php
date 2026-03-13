<?php defined('BASEPATH') or exit('No direct script access allowed');?>

<?php
$CI = &get_instance();
$qb_income_accounts_arr = array();
$qb_asset_accounts_arr = array();
$qb_payment_accounts_arr = array();

//Income
$qb_income_accounts = $CI->quickbooks_model->get_qb_accounts("*", "Revenue");
if (!empty($qb_income_accounts)) {
	$qb_income_accounts = json_decode(json_encode($qb_income_accounts), true);
	foreach ($qb_income_accounts['chart']['Revenue'] as $key => $income_vals) {
		foreach ($income_vals as $key => $value) {
			$qb_income_accounts_arr[$value['Id']] = $value['Name'];
		}
	}
}
//asset
$qb_asset_accounts = $CI->quickbooks_model->get_qb_accounts("*", "Asset", '*');
if (!empty($qb_asset_accounts)) {
	$qb_asset_accounts = json_decode(json_encode($qb_asset_accounts), true);
	foreach ($qb_asset_accounts['chart']['Asset'] as $key => $value) {
		$qb_asset_accounts_arr[$value['Id']] = $value['Name'];
	}
}
//payment
$qb_payment_accounts = $CI->quickbooks_model->get_qb_accounts("*", "Asset");

if (!empty($qb_payment_accounts)) {
	$qb_payment_accounts = json_decode(json_encode($qb_payment_accounts), true);
	foreach ($qb_payment_accounts['chart']['Asset'] as $key => $payment_values) {
		foreach ($payment_values as $key => $value) {
			$qb_payment_accounts_arr[$value['Id']] = $value['Name'];
		}
	}
}
?>

<?php if ((bool) get_option('qb_active')) {?>

    <label class="control-label"><?php echo _l('quickbooks'); ?> Connect</label>
    <?php if (get_option('qb_access_token') && time() < get_option('qb_expiration_date')): ?>
    <div class="row">
        <div class="col-md-10">
            <div class="alert alert-success"><i class="fa fa-check-square"></i> <?php echo _l('qb_connection_success'); ?></div>
        </div>
        <div class="col-md-2">
            <button class="btn btn-default" id="qb_disconnect_btn"><img height="30" src="<?php echo module_dir_url('quickbooks', 'assets/imgs/disconnect.png'); ?>"></button>
        </div>
        <div class="col-md-12 hide"><div class="alert alert-warning"><?php echo _l('qb_connection_notice'); ?></div></div>
    </div>
    <div class="row mtop10 hide"><div class="col-md-12" id="process"><div class="progress progress-bar-mini"> <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div></div></div></div>

    <?php else: ?>
        <div>
            <ipp:connectToIntuit></ipp:connectToIntuit>
        </div>
    <?php endif;?>
<?php }?>

<ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active">
        <a href="#general" aria-controls="general" role="tab" data-toggle="tab"><?php echo _l('qb_general'); ?></a>
    </li>
    <li role="presentation">
        <a href="#config" aria-controls="config" role="tab" data-toggle="tab"><?php echo _l('qb_config'); ?></a>
    </li>
    <li role="presentation">
        <a href="#reset" aria-controls="reset" role="tab" data-toggle="tab"><?php echo _l('qb_reset_sync'); ?></a>
    </li>
</ul>

<div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="general">
        <h4 class="bold">
            <?php echo _l('qb_general'); ?>
        </h4>

        <?php echo render_yes_no_option('qb_active', 'qb_active'); ?>
        <hr />
        <h4>Production Mode</h4>
        <?php echo render_input('settings[qb_client_id]', 'qb_client_id', get_option('qb_client_id')); ?>
        <?php echo render_input('settings[qb_client_secret]', 'qb_client_secret', get_option('qb_client_secret')); ?>
        <hr>
        <h4>Development Mode</h4>
        <?php echo render_input('settings[qb_client_id_demo]', 'qb_client_id_demo', get_option('qb_client_id_demo')); ?>
        <?php echo render_input('settings[qb_client_secret_demo]', 'qb_client_secret_demo', get_option('qb_client_secret_demo')); ?>
        <hr />
        <?php echo render_yes_no_option('qb_test_mode_enabled', 'qb_test_mode_enabled'); ?>
    </div>
    <div role="tabpanel" class="tab-pane" id="config">
        <?php if (get_option('cron_has_run_from_cli') != '1') {?>
            <div class="alert alert-danger">
                <?php echo _l('qb_cron_alert'); ?>
            </div>
        <?php }?>
        <div class="row mtop15">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sales_tax_model"><?php echo _l('qb_sales_tax_model'); ?></label><br />
                    <select name="settings[qb_sales_tax_model]" class="selectpicker" data-width="100%">
                        <option value="us_locales" <?php if (get_option('qb_sales_tax_model') == 'us_locales') {echo 'selected';}?>>US Locales</option>
                        <option value="non_us_locales" <?php if (get_option('qb_sales_tax_model') == 'non_us_locales') {echo 'selected';}?>>Non-US Locales</option>
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('qb_manual_cron_sync_enabled_help'); ?>"></i>
                <?php echo render_yes_no_option('qb_manual_cron_sync_enabled', 'qb_manual_cron_sync_enabled'); ?>
            </div>
        </div>
        <?php if (get_option('qb_manual_cron_sync_enabled') != '1') {?>
            <div class="row" id="auto_synch_area">
                <div class="col-md-12">
                    <hr>
                </div>
                <div class="col-md-6">
                    <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('qb_hour_of_day_perform_auto_operations_help'); ?>"></i>
                    <?php echo render_input('settings[qb_auto_operations_hour]', 'hour_of_day_perform_auto_operations', get_option('qb_auto_operations_hour'), 'number', array('data-toggle' => 'tooltip', 'data-title' => _l('hour_of_day_perform_auto_operations_format'), 'max' => 23)); ?>
                </div>

                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <?php echo render_yes_no_option('qb_auto_cron_sync_clients_enabled', 'qb_auto_cron_sync_clients_enabled'); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_yes_no_option('qb_auto_cron_sync_taxes_enabled', 'qb_auto_cron_sync_taxes_enabled'); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_yes_no_option('qb_auto_cron_sync_items_enabled', 'qb_auto_cron_sync_items_enabled'); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_yes_no_option('qb_auto_cron_sync_invoices_enabled', 'qb_auto_cron_sync_invoices_enabled'); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_yes_no_option('qb_auto_cron_sync_payments_enabled', 'qb_auto_cron_sync_payments_enabled'); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_yes_no_option('qb_auto_cron_sync_expenses_enabled', 'qb_auto_cron_sync_expenses_enabled'); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php }?>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('qb_default_income_acc_help'); ?>"></i>
                <div class="form-group inc_acc">
                    <label class="control-label" for="parent_id"><?php echo _l('qb_default_income_acc'); ?></label>
                    <?php echo form_dropdown('settings[qb_default_income_account]', $qb_income_accounts_arr, get_option('qb_default_income_account'), array('id' => 'settings[qb_default_income_account]', 'class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-width' => '100%')); ?>
                </div>
            </div>
            <div class="col-md-6 hide">
                <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('qb_default_asset_acc_help'); ?>"></i>
                <div class="form-group inc_acc">
                    <label class="control-label" for="parent_id"><?php echo _l('qb_default_asset_acc'); ?></label>
                    <?php echo form_dropdown('settings[qb_default_asset_account]', $qb_asset_accounts_arr, get_option('qb_default_asset_account'), array('id' => 'settings[qb_default_asset_account]', 'class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-width' => '100%')); ?>
                </div>
            </div>
            <div class="col-md-6">
                <i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('qb_default_payment_acc_help'); ?>"></i>
                <div class="form-group inc_acc">
                    <label class="control-label" for="parent_id"><?php echo _l('qb_default_payment_acc'); ?></label>
                    <?php echo form_dropdown('settings[qb_default_payment_account]', $qb_payment_accounts_arr, get_option('qb_default_payment_account'), array('id' => 'settings[qb_default_payment_account]', 'class' => 'form-control selectpicker', 'data-live-search' => 'true', 'data-width' => '100%')); ?>
                </div>
            </div>
            <div class="col-md-12">
                <div class="alert alert-info"><i class="fa fa-warning"></i> Synchronize Chart of Accounts first to be able to edit this fields, then save the configurations.</div>
            </div>
        </div>
    </div>
    <div role="tabpanel" class="tab-pane" id="reset">
        <div class="alert alert-info"><?php echo _l('qb_reset_sync_help'); ?></div>
        <div class="row">
            <?php if (get_option('qb_access_token') && time() < get_option('qb_expiration_date')): ?>
            <div class="col-md-12 text-center">
                <button type="button" class="btn btn-warning" id="qb_reset_all_sync"><?php echo _l('qb_reset_sync'); ?></button>
            </div>
            <div id="qb_reset_progress_area" class="mtop10 col-md-12 hide"><div class="progress progress-bar-mini"> <div class="progress-bar progress-bar-success progress-bar-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div></div></div>
            <?php else: ?>

              <div class="alert alert-warning text-center"><?php echo _l('requires_connection'); ?></div>

             <?php endif;?>
        </div>
    </div>
</div>