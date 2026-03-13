<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if(isset($construction_company)){ ?>

	<div class="row">
		<div class="col-md-6">
			<h4 class=""><?php echo _l('real_staffs'); ?></h4>
		</div>
		<?php if(has_permission('real_estate_agent_staff', '', 'create') && $construction_company->related_type == 'company'){ ?>
			<div class="col-md-6 inline-block new-contact-wrapper">
				<a href="#" onclick="add_staff(<?php echo html_entity_decode($construction_company->id); ?>, '', 'add'); return false;" class="btn btn-primary new-contact mbot25  pull-right"><?php echo _l('real_add_new_staff'); ?></a>
			</div>
		<?php } ?>
	</div>
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
<?php } ?>
<div id="modal_wrapper"></div>
<div class="modal fade" id="delete_staff" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<?php echo form_open(admin_url('realestate/delete_staff', ['delete_staff_form'])); ?>
		<?php 
		echo form_hidden('company_id', $construction_company->id); 
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
			</div><!-- /.modal-content -->
			<?php echo form_close(); ?>
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
