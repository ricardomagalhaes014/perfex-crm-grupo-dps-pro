<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
if(is_broker_logged_in()){
	broker_init_head();
}else{
	init_head();
}
?>

<div id="wrapper">
	<div class="content">

		<div class="row row tw-mt-2 sm:tw-mt-4">
			<div class="col-md-12" id="small-table">
				<div class="panel_s">
					<div class="panel-body">

						<div class="row">
							<div class="col-md-6">
								<h4 class="h4-color no-margin"><i class="fa fa-house-circle-exclamation" aria-hidden="true"></i> <?php echo _l('real_buy_requests'); ?></h4>
							</div>
							<div class="col-md-6">

								<div class="display-block text-right">
									<a href="#" class="btn btn-default btn-with-tooltip toggle-small-view hidden-xs pull-right"
									onclick="toggle_small_view('.table-property_request_table','#property_request'); return false;" data-toggle="tooltip"
									title="<?php echo _l('estimates_toggle_table_tooltip'); ?>"><i class="fa fa-angle-double-left"></i></a>

									<?php if (has_permission('real_buy_request', '', 'create') || is_broker_logged_in() ) { ?>
										<a href="<?php echo html_entity_decode($site_url) . ('add_edit_property_request'); ?>" class="btn btn-info pull-right display-block mright5"><?php echo _l('real_new_property_request'); ?></a>
									<?php } ?>
								</div>
							</div>
						</div>
						<hr class="hr-panel-heading">
						<div class="row">
							<div class=" col-md-4 ">
								<div class="form-group  no-mbot">
									<select name="client_filter[]" id="client_filter" class="selectpicker" multiple="true" data-actions-box="true"  data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('clients'); ?>">

										<?php foreach($clients as $client) { ?>
											<option value="<?php echo html_entity_decode($client['userid']); ?>"><?php echo html_entity_decode($client['company']); ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
						</div>
						<br>
						<div class="row">
							<div class="col-md-12">
								<?php echo form_hidden('propertyrequestid', $propertyrequestid); ?>

								<?php 
								$table_data = array(
									_l('real_request_number'),
									_l('real_property_name'),
									_l('client'),
									_l('real_property_price'),
									_l('real_contract_amount'),
									_l('real_term'),
									_l('real_created_date'),
									_l('real_expected_buy_date'),
									_l('real_end_date'),
									_l('real_status'),
									_l('contract'),
									_l('invoice'),
								);

								render_datatable($table_data,'property_request_table',
									array('customizable-table'),
									array(
										'proposal_sm' => 'proposal_sm',
										'id'=>'table-property_request_table',
										'data-last-order-identifier'=>'property_request_table',
										'data-default-order'=>get_table_last_order('property_request_table'),
									)); ?>

								</div>
							</div>

						</div>
					</div>
				</div>
				<div class="col-md-7 small-table-right-col">
					<div id="property_request" class="hide">

					</div>
				</div>
				
			</div>
		</div>
	</div>

	<?php 
	if(is_broker_logged_in()){
		broker_init_tail();
	}else{
		init_tail();
	}
	?>
	<div id="renew_contract">
		
	</div>

	<?php require 'modules/realestate/assets/js/companies/property_requests/property_request_js.php';?>
</body>
</html>
