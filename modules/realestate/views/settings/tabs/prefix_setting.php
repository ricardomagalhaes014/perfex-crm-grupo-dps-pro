<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php echo form_open_multipart(admin_url('realestate/prefix_setting'),array('class'=>'inventory_setting','autocomplete'=>'off')); ?>
<div class="row">
	<div class="col-md-12">
		<h5 class="no-margin font-bold tw-font-semibold text-danger"><?php echo _l('real_properties') ?></h5>
		<hr class="hr-color">
	</div>
</div>

<div class="form-group">
	<label><?php echo _l('real_property_prefix'); ?></label>
	<div  class="form-group" app-field-wrapper="real_property_prefix">
		<input type="text" id="real_property_prefix" name="real_property_prefix" class="form-control" value="<?php echo get_option('real_property_prefix'); ?>"></div>
	</div>

	<div class="form-group">
		<label><?php echo _l('real_property_number'); ?></label>
		<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('real_property_number_tooltip'); ?>"></i>
		<div  class="form-group" app-field-wrapper="real_property_number">
			<input type="number" min="0" id="real_property_number" name="real_property_number" class="form-control" value="<?php echo get_option('real_property_number'); ?>">
		</div>
	</div>

<div class="row">
	<div class="col-md-12">
		<h5 class="no-margin font-bold tw-font-semibold text-danger"><?php echo _l('real_real_estate_agents') ?></h5>
		<hr class="hr-color">
	</div>
</div>

<div class="form-group">
	<label><?php echo _l('real_real_estate_agent_prefix'); ?></label>
	<div  class="form-group" app-field-wrapper="real_company_prefix">
		<input type="text" id="real_company_prefix" name="real_company_prefix" class="form-control" value="<?php echo get_option('real_company_prefix'); ?>"></div>
	</div>

	<div class="form-group">
		<label><?php echo _l('real_real_estate_agent_number'); ?></label>
		<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('real_property_number_tooltip'); ?>"></i>
		<div  class="form-group" app-field-wrapper="real_company_number">
			<input type="number" min="0" id="real_company_number" name="real_company_number" class="form-control" value="<?php echo get_option('real_company_number'); ?>">
		</div>
	</div>

	<div class="form-group">
		<label><?php echo _l('real_real_estate_agent_staff_prefix'); ?></label>
		<div  class="form-group" app-field-wrapper="staff_code_prefix">
			<input type="text" id="staff_code_prefix" name="staff_code_prefix" class="form-control" value="<?php echo get_option('staff_code_prefix'); ?>"></div>
		</div>

		<div class="form-group">
			<label><?php echo _l('real_real_estate_agent_staff_number'); ?></label>
			<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('real_property_number_tooltip'); ?>"></i>
			<div  class="form-group" app-field-wrapper="staff_code_number">
				<input type="number" min="0" id="staff_code_number" name="staff_code_number" class="form-control" value="<?php echo get_option('staff_code_number'); ?>">
			</div>
		</div>

	<div class="row">
		<div class="col-md-12">
			<h5 class="no-margin font-bold tw-font-semibold text-danger"><?php echo _l('real_property_owners') ?></h5>
			<hr class="hr-color">
		</div>
	</div>

	<div class="form-group">
		<label><?php echo _l('real_property_owner_prefix'); ?></label>
		<div class="form-group" app-field-wrapper="real_property_owner_prefix">
			<input type="text" id="real_property_owner_prefix" name="real_property_owner_prefix" class="form-control" value="<?php echo get_option('real_property_owner_prefix'); ?>"></div>
		</div>

		<div class="form-group">
			<label> <?php echo _l('real_property_owner_number'); ?></label>
			<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('real_property_number_tooltip'); ?>"></i>
			<div  class="form-group" app-field-wrapper="real_property_owner_number">
				<input type="number" min="0" id="real_property_owner_number" name="real_property_owner_number" class="form-control" value="<?php echo get_option('real_property_owner_number'); ?>"></div>
			</div>


			<div class="row">
				<div class="col-md-12">
					<h5 class="no-margin font-bold tw-font-semibold text-danger"><?php echo _l('real_business_broker') ?></h5>
					<hr class="hr-color">
				</div>
			</div>

			<div class="form-group">
				<label><?php echo _l('real_business_broker_prefix'); ?></label>
				<div class="form-group" app-field-wrapper="real_business_broker_prefix">
					<input type="text" id="real_business_broker_prefix" name="real_business_broker_prefix" class="form-control" value="<?php echo get_option('real_business_broker_prefix'); ?>"></div>
				</div>

				<div class="form-group">
					<label> <?php echo _l('real_business_broker_number'); ?></label>
					<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('real_property_number_tooltip'); ?>"></i>

					<div  class="form-group" app-field-wrapper="real_business_broker_number">
						<input type="number" min="0" id="real_business_broker_number" name="real_business_broker_number" class="form-control" value="<?php echo get_option('real_business_broker_number'); ?>"></div>
					</div>

					<div class="form-group">
						<label><?php echo _l('real_business_broker_staff_prefix'); ?></label>
						<div  class="form-group" app-field-wrapper="real_broker_staff_prefix">
							<input type="text" id="real_broker_staff_prefix" name="real_broker_staff_prefix" class="form-control" value="<?php echo get_option('real_broker_staff_prefix'); ?>"></div>
						</div>

						<div class="form-group">
							<label><?php echo _l('real_business_broker_staff_number'); ?></label>
							<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('real_property_number_tooltip'); ?>"></i>
							<div  class="form-group" app-field-wrapper="real_broker_staff_number">
								<input type="number" min="0" id="real_broker_staff_number" name="real_broker_staff_number" class="form-control" value="<?php echo get_option('real_broker_staff_number'); ?>">
							</div>
						</div>


					<div class="clearfix"></div>
					<?php if(has_permission('real_permission', '', 'edit') || has_permission('real_permission', '', 'create')){ ?>
						<div class="modal-footer">
							<button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
						</div>
					<?php } ?>
					<?php echo form_close(); ?>


				</body>
				</html>


