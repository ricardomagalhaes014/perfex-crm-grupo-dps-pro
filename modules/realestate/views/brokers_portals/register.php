<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-4 col-md-offset-4 text-center mbot15">
	<h1 class="text-uppercase register-heading"><?php echo _l('clients_register_heading'); ?></h1>
</div>
<div class="col-md-8 col-md-offset-2">
	<?php echo form_open('realestate/authentication_broker/register', ['id'=>'register-form']); ?>
	<div class="panel_s">
		<div class="panel-body">
			<div class="row">
				<div class="col-md-12">
					<h4 class="bold register-contact-info-heading"><?php echo _l('broker_register_contact_info'); ?></h4>
					<div class="form-group register-name-group">
						<label class="control-label" for="name"><span class="text-danger">*</span><?php echo _l('real_broker_name'); ?></label>
						<input type="text" class="form-control" name="name" id="name" value="<?php echo set_value('name'); ?>">
						<?php echo form_error('name'); ?>
					</div>
					<h4 class="bold register-contact-info-heading"><?php echo _l('broker_register_primary_contact_info'); ?></h4>
					<div class="form-group register-firstname-group">
						<label class="control-label" for="firstname"><span class="text-danger">*</span><?php echo _l('broker_firstname'); ?></label>
						<input type="text" class="form-control" name="firstname" id="firstname" value="<?php echo set_value('firstname'); ?>">
						<?php echo form_error('firstname'); ?>
					</div>

					<div class="form-group register-lastname-group">
						<label class="control-label" for="lastname"><span class="text-danger">*</span><?php echo _l('broker_lastname'); ?></label>
						<input type="text" class="form-control" name="lastname" id="lastname" value="<?php echo set_value('lastname'); ?>">
						<?php echo form_error('lastname'); ?>
					</div>
					
					<div class="form-group register-email-group">
						<label class="control-label" for="email"><span class="text-danger">*</span><?php echo _l('broker_email'); ?></label>
						<input type="email" class="form-control" name="email" id="email" value="<?php echo set_value('email'); ?>">
						<?php echo form_error('email'); ?>
					</div>
					
					<div class="form-group register-contact-phone-group">
						<label class="control-label" for="phonenumber"><?php echo _l('broker_phone'); ?></label>
						<input type="text" class="form-control" name="phonenumber" id="phonenumber" value="<?php echo set_value('contact_phonenumber'); ?>">
					</div>
					
					<div class="form-group register-password-group">
						<label class="control-label" for="password"><span class="text-danger">*</span><?php echo _l('broker_register_password'); ?></label>
						<input type="password" class="form-control" name="password" id="password">
						<?php echo form_error('password'); ?>
					</div>
					<div class="form-group register-password-repeat-group">
						<label class="control-label" for="passwordr"><span class="text-danger">*</span><?php echo _l('broker_register_password_repeat'); ?></label>
						<input type="password" class="form-control" name="passwordr" id="passwordr">
						<?php echo form_error('passwordr'); ?>
					</div>

				</div>

			</div>

			<div class="row">
				<div class="col-md-12 text-center">
					<div class="form-group">
						<button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-info"><?php echo _l('clients_register_string'); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php echo form_close(); ?>
</div>
