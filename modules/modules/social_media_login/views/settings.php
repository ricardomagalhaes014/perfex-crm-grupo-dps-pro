<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4><?php echo _l("configuration_options");?></h4>
<hr>
<div class="form-group">
	<label class="control-label clearfix"><?php echo _l("module_status");?></label>
	<label class="radio-inline">
	  <input type="radio" value="Active" class="ignore" name="settings[social_media_login_module_status]" <?php echo (get_option('social_media_login_module_status') == "Active") ? "checked" : 'checked' ;?>>
	  <?php echo _l("enable_module");?>
	</label>
	<label class="radio-inline">
	  <input type="radio" value="Inactive" class="ignore" name="settings[social_media_login_module_status]" <?php echo (get_option('social_media_login_module_status') == "Inactive") ? "checked" : '' ;?>>
	  <?php echo _l("disable_module");?>
	</label>
</div>
<hr class="">
<h4><?php echo _l("google_login_details");?></h4>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("client_secret");?></label>
	<input type="text" class="form-control ignore" name="settings[google_key]" value="<?php echo set_value('google_key', get_option('google_key')); ?>">
</div>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("client_id");?></label>
	<input type="text" class="form-control ignore" name="settings[google_id]" value="<?php echo set_value('google_id', get_option('google_id')); ?>">
</div>
<div class="form-group">
	<label for="use_recaptcha_customers_area" class="control-label clearfix"><?php echo _l("status");?></label>
	<label class="radio-inline">
	  <input type="radio" value="Active" class="ignore" name="settings[google_btn_status]" <?php echo (get_option('google_btn_status') == "Active") ? "checked" : 'Active' ;?>>
	  <?php echo _l("active");?>
	</label>
	<label class="radio-inline">
	  <input type="radio" value="Inactive" class="ignore" name="settings[google_btn_status]" <?php echo (get_option('google_btn_status') == "Inactive") ? "checked" : '' ;?>>
	  <?php echo _l("inactive");?>
	</label>
</div>
<hr>
<h4><?php echo _l("linkedin_login_details");?></h4>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("client_secret");?></label>
	<input type="text" class="form-control ignore" name="settings[linkedin_key]" value="<?php echo set_value('linkedin_key', get_option('linkedin_key')); ?>">
</div>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("client_id");?></label>
	<input type="text" class="form-control ignore" name="settings[linkedin_id]" value="<?php echo set_value('linkedin_id', get_option('linkedin_id')); ?>">
</div>
<div class="form-group">
	<label for="use_recaptcha_customers_area" class="control-label clearfix"><?php echo _l("status");?></label>
	<label class="radio-inline">
	  <input type="radio" value="Active" class="ignore" name="settings[linkedin_btn_status]" <?php echo (get_option('linkedin_btn_status') == "Active") ? "checked" : 'Active' ;?> ><?php echo _l("active");?>
	</label>
	<label class="radio-inline">
	  <input type="radio" value="Inactive" class="ignore" name="settings[linkedin_btn_status]" <?php echo (get_option('linkedin_btn_status') == "Inactive") ? "checked" : '' ;?> ><?php echo _l("inactive");?>
	</label>
</div>
<hr>
<h4><?php echo _l("twitter_login_details");?></h4>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("api_secret_key");?></label>
	<input type="text" class="form-control ignore" name="settings[twitter_key]" value="<?php echo set_value('twitter_key', get_option('twitter_key')); ?>">
</div>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("api_key");?></label>
	<input type="text" class="form-control ignore" name="settings[twitter_id]" value="<?php echo set_value('twitter_id', get_option('twitter_id')); ?>">
</div>
<div class="form-group">
	<label for="use_recaptcha_customers_area" class="control-label clearfix"><?php echo _l("status");?></label>
	<label class="radio-inline">
	  <input type="radio" value="Active" class="ignore" name="settings[twitter_btn_status]" <?php echo (get_option('twitter_btn_status') == "Active") ? "checked" : 'Active' ;?> ><?php echo _l("active");?>
	</label>
	<label class="radio-inline">
	  <input type="radio" value="Inactive" class="ignore" name="settings[twitter_btn_status]" <?php echo (get_option('twitter_btn_status') == "Inactive") ? "checked" : '' ;?> ><?php echo _l("inactive");?>
	</label>
</div>
<hr>
<h4><?php echo _l("app_secret");?></h4>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("app_secret");?></label>
	<input type="text" class="form-control ignore" name="settings[facebook_key]" value="<?php echo set_value('facebook_key', get_option('facebook_key')); ?>">
</div>
<div class="form-group">
	<label class="form-control-label"><?php echo _l("app_id");?></label>
	<input type="text" class="form-control ignore" name="settings[facebook_id]" value="<?php echo set_value('facebook_id', get_option('facebook_id')); ?>">
</div>
<div class="form-group">
	<label for="use_recaptcha_customers_area" class="control-label clearfix"><?php echo _l("status");?></label>
	<label class="radio-inline">
	  <input type="radio" value="Active" class="ignore" name="settings[facebook_btn_status]" <?php echo (get_option('facebook_btn_status') == "Active") ? "checked" : 'Active' ;?> ><?php echo _l("active");?>
	</label>
	<label class="radio-inline">
	  <input type="radio" value="Inactive" class="ignore" name="settings[facebook_btn_status]" <?php echo (get_option('facebook_btn_status') == "Inactive") ? "checked" : '' ;?> ><?php echo _l("inactive");?>
	</label>
</div>