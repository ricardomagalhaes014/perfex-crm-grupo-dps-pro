<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
init_head();
?>

<div id="wrapper">
	<div class="content">

		<div class="row row tw-mt-2 sm:tw-mt-4">
			<div class="col-md-12" id="small-table">
				<div class="panel_s">
					<div class="panel-body">

						<div class="row">
							<div class="col-md-12">
								<h4 class="h4-color no-margin"><i class="fa fa-house-circle-exclamation" aria-hidden="true"></i> <?php echo _l('real_tenants'); ?></h4>
							</div>
							
						</div>
						<hr class="hr-panel-heading">
						<div class="row">
							<div class="col-md-3">
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
									array(
										'name'=>_l('real_tenant'),
										'th_attrs' => [
											'style' => 'min-width:150px',
										],
									),
									array(
										'name'=>_l('real_property_code'),
										'th_attrs' => [
											'style' => 'min-width:60px',
										],
									),
									array(
										'name'=>_l('real_property_name'),
										'th_attrs' => [
											'style' => 'min-width:150px',
										],
									),
									_l('real_property_price'),
									_l('real_reference_number'),
									array(
										'name'=>_l('contact_primary'),
										'th_attrs' => [
											'style' => 'min-width:100px',
										],
									),
									array(
										'name'=>_l('invoice').' ('._l('invoice_status_unpaid').')',
										'th_attrs' => [
											'style' => 'min-width:200px',
										],
									),
									array(
										'name'=>_l('contract'),
										'th_attrs' => [
											'style' => 'min-width:100px',
										],
									),
									_l('real_contract_amount'),
									_l('real_term'),
									_l('real_lease_term'),
									_l('real_status'),
									_l('real_created_date'),

								);

								render_datatable($table_data,'tenant_table',
									array('customizable-table'),
									array(
										'proposal_sm' => 'proposal_sm',
										'id'=>'table-tenant_table',
										'data-last-order-identifier'=>'tenant_table',
										'data-default-order'=>get_table_last_order('tenant_table'),
									)); ?>

								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php 
	init_tail();
	?>
	<?php require 'modules/realestate/assets/js/companies/tenants/manage_js.php';?>
</body>
</html>
