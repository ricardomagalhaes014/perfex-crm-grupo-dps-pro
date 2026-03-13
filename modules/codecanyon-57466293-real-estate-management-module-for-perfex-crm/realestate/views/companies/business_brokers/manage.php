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
										<h4 class="h4-color no-margin"><i class="fa-regular fa-handshake" aria-hidden="true"></i> <?php echo _l('real_business_brokers'); ?></h4>
									</div>
									<?php if(has_permission('real_business_broker', '', 'create')){ ?>
										<div class="col-md-3">
											<div class="_buttons">
												<a href="<?php echo admin_url('realestate/add_edit_company/0/business_broker'); ?>" class="btn btn-primary mright5 test pull-right display-block mbot10">
													<?php echo _l('real_add_new'); ?></a>
												</div>
											</div>
											<br>
										<?php } ?>
									</div>

									<hr class="hr-panel-heading" />
									<?php render_datatable(array(
										_l('id'),
										_l('real_photo'),
										_l('real_code_label'),
										_l('real_name_label'),
										_l('real_email'),
										_l('real_phonenumber'),
										_l('real_created_by'),
										_l('real_active'),
										_l('real_created_date'),
									),'freelance_agent_table',

									array('customizable-table'),
									array(
										'id'=>'table-freelance_agent_table',
										'data-last-order-identifier'=>'freelance_agent_table',
										'data-default-order'=>get_table_last_order('freelance_agent_table'),
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
	require('modules/realestate/assets/js/companies/business_brokers/manage_js.php');
	?>
</body>
</html>
