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
										<h4 class="h4-color no-margin"><i class="fa-solid fa-building-user" aria-hidden="true"></i> <?php echo _l('real_company_staffs'); ?></h4>
									</div>
									<?php if(has_permission('staff', '', 'create')){ ?>
										<div class="col-md-3">
											<div class="_buttons">
												<a href="<?php echo admin_url('staff/member') ?>" class="btn btn-primary new-contact mbot25  pull-right"><?php echo _l('real_add_new_staff'); ?></a>
											</div>
											<br>
										<?php } ?>
									</div>

									<hr class="hr-panel-heading" />
									<?php echo form_hidden('company_id', 0); ?>
									<div class="col-md-12">

										<?php
										$table_data = array(_l('id'),_l('real_staff_code'),_l('clients_list_full_name'));
										$table_data = array_merge($table_data, array(
											_l('client_email'),
											_l('client_phonenumber'),
											_l('real_approval_manager'),
											_l('real_require_approval'),
											_l('contact_active'),
											_l('real_public_staff'),
											_l('clients_list_last_login'),
											_l('role'),
										));
										echo render_datatable($table_data,'company_staff_table'); ?>
									</div>
								</div>
							</div>

						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="search_modal_wrapper"></div>
<div class="modal fade" id="delete_staff" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<?php echo form_open(admin_url('realestate/delete_staff', ['delete_staff_form'])); ?>
		<?php 
		echo form_hidden('company_id', 0); 
		?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
					aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?php echo _l('delete_staff'); ?></h4>
				</div>
				<div class="modal-body">
					<div class="delete_id">
						<?php echo form_hidden('id'); ?>
					</div>
					<p><?php echo _l('delete_staff_info'); ?></p>
					<?php
					echo render_select('transfer_data_to', $staff_members, ['staffid', ['firstname', 'lastname']], 'staff_member', get_staff_user_id(), [], [], '', '', false);
					?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
					<button type="submit" class="btn btn-danger _delete"><?php echo _l('confirm'); ?></button>
				</div>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div>
	<div id="modal_wrapper"></div>


	<?php init_tail(); ?>
	<?php 
	require 'modules/realestate/assets/js/companies/companies/company_staff_manage_js.php';
	?>
</body>
</html>
