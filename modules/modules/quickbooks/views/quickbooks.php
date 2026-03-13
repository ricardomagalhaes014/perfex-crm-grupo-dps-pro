<?php defined('BASEPATH') or exit('No direct script access allowed');?>
<?php init_head();?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
			            <h3 class="text-success no-margin"><img height="50" src="<?php echo module_dir_url('quickbooks', 'assets/imgs/qb.png'); ?>"> <?php echo _l('quickbooks'); ?></h3>
			        </div>
			    </div>
				<div class="panel_s">
					<div class="panel-body">
			            <ul class="nav nav-tabs">
		                  <li class="active"><a data-toggle="tab" href="#chat_of_acc"><?php echo _l('qb_chart_of_acc'); ?></a></li>
		                  <li><a data-toggle="tab" href="#qb_ac_synchs"><i class="fa fa-refresh"></i> Synchronizations</a></li>
		                </ul>
		                <div class="tab-content">
		                	<div id="chat_of_acc" class="tab-pane fade in active">
		                		<div class="clearfix"></div>
					              <?php
echo '<table id="qb_ledgertable" class="table table-striped dataTable no-footer dtr-inline">';
echo '<thead>';
echo '<th>' . _l('account_code') . '</th>';
echo '<th>' . _l('account_name') . '</th>';
echo '<th>' . _l('account_type') . '</th>';
$currency = !empty($accounts) ? $accounts->currency : '';
echo '<th align="right">' . _l('o/p_balance') . ' (' . $currency . ')' . '</th>';
echo '<th align="right">' . _l('c/l_balance') . ' (' . $currency . ')' . '</th>';
echo '</thead>';
echo "<tbody>";
$chart = !empty($accounts) ? $accounts->chart : array();
echo print_account_chart($chart);
echo "</tbody>";
echo '</table>';
?>
		                	</div>
		                	<div id="qb_ac_synchs" class="tab-pane fade">
		                	<?php if (get_option('qb_access_token') && time() < get_option('qb_expiration_date')) {?>
		                		<div class="alert alert-info">
		                			<?=_l('qb_sync_helper');?>
		                		</div>
							    <div class="row">
							        <div class="col-md-4 qb_sub">
							            <div class="panel_s">
							                <div class="panel-heading"><?php echo _l('qb_chart_of_acc'); ?><strong class="pull-right" data-toggle="tooltip" data-title="<?php echo _l('qb_download_helper_2'); ?>"><i class="text-warning fa-2x fa fa-download" ></i></strong></div>
							                <div class="panel-body">
							                    <button class="btn btn-success" id="synch_chart_of_accounts" data-qb_synch_area="true"><i class="fa fa-refresh"></i> Synchronize</button>
							                </div>
							            </div>
							        </div>
							        <div class="col-md-4 qb_sub">
							            <div class="panel_s">
							                <div class="panel-heading"><?php echo _l('qb_customers'); ?><strong class="pull-right" data-toggle="tooltip" data-title="<?php echo _l('qb_upload_helper_2'); ?>"><i class="text-success fa-2x fa fa-upload"></i></strong></div>
							                <div class="panel-body">
							                    <button class="btn btn-success" id="synch_clients" data-qb_synch_area="true"><i class="fa fa-refresh"></i> Synchronize</button>
							                </div>
							            </div>
							        </div>
							        <?php if (get_option('qb_sales_tax_model') == 'non_us_locales') {?>
							        <div class="col-md-4 qb_sub">
							            <div class="panel_s">
							                <div class="panel-heading"><?php echo _l('qb_tax_n_agencies'); ?><strong class="pull-right" data-toggle="tooltip" data-title="<?php echo _l('qb_download_helper_2'); ?>"><i class="text-warning fa-2x fa fa-download" ></i></strong><strong class="pull-right mright5" data-toggle="tooltip" data-title="<?php echo _l('qb_upload_helper_2'); ?>"><i class="text-success fa-2x fa fa-upload"></i></strong></div>
							                <div class="panel-body">
							                    <button class="btn btn-success" id="synch_taxes" data-qb_synch_area="true"><i class="fa fa-refresh"></i> Synchronize</button>
							                </div>
							            </div>
							        </div>
							    <?php }?>
							        <div class="col-md-4 qb_sub">
							            <div class="panel_s">
							                <div class="panel-heading"><?php echo _l('qb_items'); ?><strong class="pull-right" data-toggle="tooltip" data-title="<?php echo _l('qb_upload_helper_2'); ?>"><i class="text-success fa-2x fa fa-upload"></i></strong></div>
							                <div class="panel-body">
							                    <button class="btn btn-success" id="synch_items" data-qb_synch_area="true"><i class="fa fa-refresh"></i> Synchronize</button>
							                </div>
							            </div>
							        </div>
							        <div class="col-md-4 qb_sub">
							            <div class="panel_s">
							                <div class="panel-heading"><?php echo _l('qb_invoices'); ?><strong class="pull-right" data-toggle="tooltip" data-title="<?php echo _l('qb_upload_helper_2'); ?>"><i class="text-success fa-2x fa fa-upload"></i></strong></div>
							                <div class="panel-body">
							                    <button class="btn btn-success" id="synch_invoices" data-qb_synch_area="true"><i class="fa fa-refresh"></i> Synchronize</button>
							                </div>
							            </div>
							        </div>
							        <div class="col-md-4 qb_sub">
							            <div class="panel_s">
							                <div class="panel-heading"><?php echo _l('qb_payments'); ?><strong class="pull-right" data-toggle="tooltip" data-title="<?php echo _l('qb_upload_helper_2'); ?>"><i class="text-success fa-2x fa fa-upload"></i></strong></div>
							                <div class="panel-body">
							                    <button class="btn btn-success" id="synch_payments" data-qb_synch_area="true"><i class="fa fa-refresh"></i> Synchronize</button>
							                </div>
							            </div>
							        </div>

							        <div class="col-md-4 qb_sub">
							            <div class="panel_s">
							                <div class="panel-heading"><?php echo _l('qb_expenses'); ?><strong class="pull-right" data-toggle="tooltip" data-title="<?php echo _l('qb_upload_helper_2'); ?>"><i class="text-success fa-2x fa fa-upload"></i></strong></div>
							                <div class="panel-body">
							                    <button class="btn btn-success" id="synch_expenses" data-qb_synch_area="true"><i class="fa fa-refresh"></i> Synchronize</button>
							                </div>
							            </div>
							        </div>
							    </div>
							<?php }?>
							</div>
		                </div>
			        </div>
			    </div>
			</div>
		</div>
	</div>
</div>
<?php init_tail();?>
</body>
</html>