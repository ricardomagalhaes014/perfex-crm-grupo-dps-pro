<!-- box loading -->
<div id="box-loading">
	<img src="<?php echo site_url('modules/realestate/assets/images/loading.gif'); ?>" alt="">
</div>

<div class="modal fade" id="add_group_retire_manage" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<?php echo form_open($site_url . ('add_group_form_manage'),  array('id'=>'add_group_form_manage')); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title add"><?php echo _l('real_add_group'); ?></h4>
				<h4 class="modal-title edit"><?php echo _l('real_edit_group'); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" name="id" value="">
					<div class="col-md-12">
						<div class="form-group">
							<?php echo render_input('name','real_group_name'); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="button" class="btn btn-primary add_group"><?php echo _l('submit'); ?></button>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="add_school_retire_manage" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<?php echo form_open($site_url . ('add_school_form_manage'),  array('id'=>'add_school_form_manage')); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title add"><?php echo _l('real_add_school'); ?></h4>
				<h4 class="modal-title edit"><?php echo _l('real_edit_school'); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" name="id" value="">
					<div class="col-md-12">
						<div class="form-group">
							<?php echo render_input('name','rel_school_name'); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="button" class="btn btn-primary add_school"><?php echo _l('submit'); ?></button>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="add_landmark_retire_manage" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<?php echo form_open($site_url . ('add_landmark_form_manage'),  array('id'=>'add_landmark_form_manage')); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title add"><?php echo _l('real_add_landmark'); ?></h4>
				<h4 class="modal-title edit"><?php echo _l('real_edit_landmark'); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" name="id" value="">
					<div class="col-md-12">
						<div class="form-group">
							<?php echo render_input('name','rel_landmark_name'); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="button" class="btn btn-primary add_landmark"><?php echo _l('submit'); ?></button>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade" id="add_hopspital_retire_manage" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<?php echo form_open($site_url . ('add_hopspital_form_manage'),  array('id'=>'add_hopspital_form_manage')); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
				<h4 class="modal-title add"><?php echo _l('real_add_hopspital'); ?></h4>
				<h4 class="modal-title edit"><?php echo _l('real_edit_hopspital'); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" name="id" value="">
					<div class="col-md-12">
						<div class="form-group">
							<?php echo render_input('name','rel_hopspital_name'); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
				<button type="button" class="btn btn-primary add_hopspital"><?php echo _l('submit'); ?></button>
			</div>
			<?php echo form_close(); ?>
		</div>
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
