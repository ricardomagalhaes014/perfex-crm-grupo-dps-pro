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
			<div class="col-md-12" id="small-table">
				<div class="panel_s mbot5">
					<div class="panel-body">
						<div class="row">
							<div class="col-md-10" id="property_search">
								<div class="row">
									<div class="col-md-12">
										<?php $this->load->view('companies/property_listings/utilities/search_box'); ?>
									</div>
								</div>
							</div>

							<div class="col-md-2">
								<?php if(is_broker_logged_in() || real_has_monthly_property() == 'true'){ ?>
								<div class="_buttons">
									<a href="<?php echo html_entity_decode($site_url) . ('add_edit_property_listing'); ?>" class="btn btn-info pull-right"><?php echo _l('real_add_new'); ?></a>
								</div>
							<?php } ?>
							</div>
						</div>
					</div>
				</div>

				<div class="panel_s">
					<div class="panel-body">
						<?php if ($isMap) { ?>
							<div class="col-md-12">
								<div class="map-content">
									<div class="hide map-input-search">				
									</div>		
									<div id="map_area" class="listing_map"></div>
								</div>
							</div>
						<?php }else{ ?>
							<div class="row">
								<div class="col-md-12">
									<?php $this->load->view('companies/property_listings/utilities/table_html'); ?>
								</div>
							</div>

							<div class="col-md-3 hide"  id="listings_col">
							</div>
						<?php } ?>

					</div>
				</div>


			</div>
		</div>
	</div>
	<?php $this->load->view('companies/property_listings/utilities/filter_form'); ?>

	<?php 
	if(is_broker_logged_in()){
		broker_init_tail();
	}else{
		init_tail();
	}
	?>

</body>
<?php 
require('modules/realestate/assets/js/companies/property_listings/manage_js.php');
require('modules/realestate/assets/js/companies/property_listings/utilities/filter_form_js.php');
require('modules/realestate/assets/js/map_js.php');


?>

</html>
