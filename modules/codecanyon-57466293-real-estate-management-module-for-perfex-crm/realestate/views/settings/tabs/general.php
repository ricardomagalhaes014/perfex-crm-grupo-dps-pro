<div class="row">
	<div class="col-md-12">
		<h5 class="no-margin font-bold tw-font-semibold"><?php echo _l('real_Google_Map_API_Code') ?></h5>

		<hr class="hr-color">
	</div>
</div>
<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank"><?php echo _l('real_get_google_map_api_key_tutorial'); ?></a>
<div class="form-group">
	<div onchange="setting_Google_Map_API_Code(this); return false" class="form-group" app-field-wrapper="real_Gogle_Map_API_Code">
		<input type="text" id="real_Gogle_Map_API_Code" name="real_Gogle_Map_API_Code" class="form-control" value="<?php echo get_option('real_Gogle_Map_API_Code'); ?>">
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<h5 class="no-margin font-bold tw-font-semibold"><?php echo _l('real_show_broker_portal') ?></h5>

		<hr class="hr-color">
	</div>
</div>
<div class="row">
	<div class="col-md-12">
		<div class="form-group">
			<div class="checkbox checkbox-primary">
				<input onchange="auto_create_change_setting(this); return false" type="checkbox" id="real_show_broker_portal" name="real_show_broker_portal" <?php if(get_option('real_show_broker_portal') == 1 ){ echo 'checked';} ?> value="real_show_broker_portal">
				<label for="real_show_broker_portal"><?php echo _l('real_show_broker_portal'); ?>
				<a href="#" class="pull-right display-block input_method"></a>
			</label>
		</div>
	</div>
</div>
</div>