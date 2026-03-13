<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
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
										<h4 class="h4-color no-margin"><i class="fa-solid fa-building-user" aria-hidden="true"></i> <?php echo _l('real_real_estate_agents'); ?></h4>
									</div>
									<?php if(has_permission('real_estate_agent', '', 'create')){ ?>
										<div class="col-md-3">
											<div class="_buttons">
												<a href="<?php echo admin_url('realestate/add_edit_company'); ?>" class="btn btn-primary mright5 test pull-right display-block mbot10">
													<?php echo _l('real_new_real_estate_agent'); ?></a>
												</div>
											</div>
											<br>
										<?php } ?>
									</div>

									<hr class="hr-panel-heading" />
									<?php render_datatable(array(
										_l('id'),
										_l('real_photo'),
										_l('real_real_estate_agent_code'),
										_l('real_real_estate_agent_name'),
										_l('real_email'),
										_l('real_phonenumber'),
										_l('real_created_by'),
										_l('real_verification_status'),
										_l('real_active'),
										_l('real_created_date'),
									),'company_table',

									array('customizable-table'),
									array(
										'id'=>'table-company_table',
										'data-last-order-identifier'=>'company_table',
										'data-default-order'=>get_table_last_order('company_table'),
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

	<?php init_tail(); ?>
	<?php 
	require('modules/realestate/assets/js/companies/companies/manage_js.php');
	?>
</body>
</html>
