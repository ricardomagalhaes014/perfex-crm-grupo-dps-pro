<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="address_history_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<?php echo form_open_multipart(site_url('realestate/client/address_history'), array('id'=>'add_edit_address_history')); ?>

		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">
					<span class="add-title"><?php echo _l('real_add_address_history'); ?></span>
					<span class="edit-title"><?php echo _l('real_edit_address_history'); ?></span>
				</h4>
			</div>

			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div id="address_history_additional"></div>
						<?php echo render_input('address', 'real_address'); ?>
						<?php echo render_date_input('move_in', 'real_you_move_in'); ?>
						<?php echo render_date_input('move_out', 'real_you_move_out'); ?>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
			</div>
		</div>
		<?php echo form_close(); ?>
	</div>
</div>
