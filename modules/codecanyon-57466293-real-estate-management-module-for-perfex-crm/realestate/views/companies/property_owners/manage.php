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
		<div class="row">
			<div class="col-md-12">
				<div class="panel_s"> 
					<div class="panel-body">

						<div class="row">
							<div class="col-md-12">

								<div class="row">
									<div class="col-md-9">
										<h4 class="h4-color no-margin"><i class="fa-solid fa-hand-holding-hand" aria-hidden="true"></i> <?php echo _l('real_property_owners'); ?></h4>
									</div>
									<?php if(has_permission('real_property_owner', '', 'create') || is_broker_logged_in()){ ?>
										<div class="col-md-3">
											<div class="_buttons">
												<a href="<?php echo html_entity_decode($site_url).('add_edit_owner'); ?>" class="btn btn-primary mright5 test pull-right display-block">
													<?php echo _l('real_add_new'); ?>

												</a>
											</div>
										</div>
										<br>
									<?php } ?>
								</div>

								<hr class="hr-panel-heading" />
								<?php render_datatable(array(
									_l('id'),
									_l('real_photo'),
									_l('real_owner_code'),
									_l('real_owner_name'),
									_l('real_email'),
									_l('real_phonenumber'),
									_l('real_created_by'),
									_l('real_active'),
									_l('real_created_date'),
								),'owner_table',

								array('customizable-table'),
								array(
									'id'=>'table-owner_table',
									'data-last-order-identifier'=>'owner_table',
									'data-default-order'=>get_table_last_order('owner_table'),
								)); ?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="search_modal_wrapper"></div>

<?php 
if(is_broker_logged_in()){
	broker_init_tail();
}else{
	init_tail();
}
?>

<?php 
require('modules/realestate/assets/js/companies/property_owners/manage_js.php');
?>
</body>
</html>
