	<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

	<div class="row">
		<div class="col-md-9">
			<h4 class="h4-color no-margin"><?php echo _l('real_plans'); ?></h4>
		</div>
		<?php if(has_permission('real_permission', '', 'create')){ ?>
			<div class="col-md-3">
				<div class="_buttons">
					<a href="#" onclick="new_plan(); return false;" class="btn btn-primary pull-right display-block">
						<?php echo _l('real_new_plan'); ?>
					</a>
				</div>
			</div>
		<?php } ?>
	</div>
	<br>

	<?php 
	render_datatable(
		array(
			_l('id'),
			_l('name'),
			_l('real_monthly_listing'),
			_l('real_rate'),
			_l('real_description'),
			_l('real_payment_type'),
			_l('real_created_by'),
			_l('date_created'),
			_l('options'),
		),'company_plan_table'
	);
	?>

	<div class="modal fade" id="plan_filter" tabindex="-1" role="dialog">
		<div class="modal-dialog">
			<?php echo form_open_multipart(admin_url('realestate/plan'), array('id'=>'add_edit_plan')); ?>

			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title">
						<span class="edit-title"><?php echo _l('real_edit_plan'); ?></span>
						<span class="add-title"><?php echo _l('real_add_plan'); ?></span>
					</h4>
				</div>

				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div id="plan_filter_additional"></div>

							<?php echo render_input('name', 'real_plan_name'); ?>
							<?php echo render_input('monthly_listing_number', 'real_monthly_listings', '', 'number'); ?>
							<?php echo render_input('rate', 'real_plan_rate', '', 'number', ['min' => 0]); ?>
							<?php echo render_textarea('description', 'real_plan_description', '', ['maxlength' => 100]); ?>

							<p class="bold"><?php echo _l('real_long_description'); ?></p>
								<?php $contents = ''; if(isset($project)){$contents = $project->description;} ?>
								<?php echo render_textarea('long_description','',$contents,array(),array(),'','tinymce'); ?>

							<div class="form-group hide">
							<label for="type"><?php echo _l('real_read_only'); ?></label>

								<div class="radio radio-primary radio-inline" >
									<input type="radio" id="y_opt_1_" name="read_only" value="1" >
									<label for="y_opt_1_"><?php echo _l('real_yes'); ?></label>
								</div>

								<div class="radio radio-primary radio-inline" >
									<input type="radio" id="y_opt_2_" name="read_only" value="0" checked>
									<label for="y_opt_2_"><?php echo _l('real_no'); ?></label>
								</div>
							</div>

							<div class="form-group hide">
								<input type="text" id="payment_type" name="payment_type" value="one_time_payment" >
							</div>

							<?php echo render_select('role_id', $roles, ['roleid', 'name'], 'real_plan_detail'); ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
					<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
				</div>
			</div><!-- /.modal-content -->
			<?php echo form_close(); ?>
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->


	<input type="hidden" name="company_plan" value="1">   
	<input type="hidden" name="freelance_plan" value="1">   
	</body>
	</html>
