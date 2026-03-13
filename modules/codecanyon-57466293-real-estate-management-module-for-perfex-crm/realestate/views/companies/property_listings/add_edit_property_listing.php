<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
if(is_broker_logged_in()){
	broker_init_head();
}else{
	init_head();
}
?>

<div id="wrapper">
	<div class="content">
		<div class="row">

			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						
						<div>
							<div class="tab-content">

								<div class="row">
									<div class="col-md-12">
										<h4 class=""><?php echo html_entity_decode($title); ?></h4>
									</div>
								</div>
								<div class="row">
									<?php
									$property_id = isset($property_listing) ? $property_listing->id : '';
									?>
									<?php echo form_open_multipart(html_entity_decode($site_url) . ('add_edit_property_listing/'.$property_id), array('id' => 'add_update_property_listing1', 'autocomplete'=>'off')); ?>
									<?php echo form_close(); ?>

									<?php echo form_open_multipart(html_entity_decode($site_url) . ('add_edit_property_listing/'.$property_id), array('id' => 'add_update_property_listing2', 'autocomplete'=>'off')); ?>
									<?php echo form_close(); ?>

									<?php echo form_open_multipart(html_entity_decode($site_url) . ('add_edit_property_listing/'.$property_id), array('id' => 'add_update_property_listing3', 'autocomplete'=>'off')); ?>
									<?php echo form_close(); ?>


									<?php echo form_open_multipart(html_entity_decode($site_url) . ('add_edit_property_listing/'.$property_id), array('id' => 'add_update_property_listing', 'autocomplete'=>'off')); ?>

									
									<?php  $this->load->view('companies/property_listings/utilities/add_edit_property_template', ['property_id' => $property_id]); ?>
									<div class="btn-bottom-toolbar btn-toolbar-container-out text-right">
										<a href="<?php echo html_entity_decode($site_url) . ('properties'); ?>"class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>
										<button type="submit" class="btn btn-primary">
											<?php echo _l( 'submit'); ?>
										</button>
									</div>
									<?php echo form_close(); ?>

								</div>


							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="btn-bottom-pusher"></div>
	</div>
</div>

<div id="select_on_map" class="p10">
	<div class="h100">
		<div class="modal-content h100">
			<div class="modal-header">
				<button type="button" class="btn btn-primary mtop4 accept_handle pull-right"><?php echo _l('real_accept'); ?></button>
				<button class="btn btn-default pull-right mtop4 mright5 close_handle"><?php echo _l('close'); ?></button>
				<h4><?php echo _l('real_please_select_location'); ?></h4>
			</div>
			<div class="map-content">
				<div class="hide map-input-search">				
				</div>		
				<div id="map_area" class="h100"></div>
				<input type="hidden" name="lat">
				<input type="hidden" name="lng">
			</div>
		</div>
	</div>
</div>
<?php  $this->load->view('companies/property_listings/utilities/amenities_template'); ?>

<?php if(isset($property_listing)){ ?>
	<?php  $this->load->view('companies/property_listings/request_brokerages/request_broker_modal'); ?>
<?php } ?>
<div id="search_modal_wrapper"></div>

<?php 
if(is_broker_logged_in()){
	broker_init_tail();
}else{
	init_tail();
}
?>

<?php 
$viewuri = $_SERVER['REQUEST_URI'];
?>

<?php 
	require 'modules/realestate/assets/js/companies/property_listings/add_edit_property_listing_js.php';
	require('modules/realestate/assets/js/companies/property_listings/preview_property_file_js.php');

if(isset($property_listing)){
	require 'modules/realestate/assets/js/companies/property_listings/request_brokerages/manage_js.php';
}
?>

</body>
</html>
