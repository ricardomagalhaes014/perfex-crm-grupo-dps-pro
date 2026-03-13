<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('app_customers_portal_head'); ?>

<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
	<?php echo _l('real_properties'); ?>
</h4>

<div class="panel_s mbot5">
	<div class="panel-body">
		<div class="row">
			<div class="col-md-12" id="property_search">
				<div class="row">
					<div class="col-md-12">
						<?php $this->load->view('companies/property_listings/utilities/search_box', ['is_client' => true]); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="panel_s">
	<div class="panel-body">
		<?php if (isset($isMap) && $isMap) { ?>
			<div class="col-md-12">
				<div class="map-content">
					<div class="hide map-input-search">             
					</div>      
					<div id="map_area" class="listing_map"></div>
				</div>
			</div>
		<?php }else{ ?>
			<div class="col-md-12 no-p-left no-p-right">                 
				<div class="row">
					<div class="col-md-12 no-p-left no-p-right">
						<div id="grid_dt_view" >
						</div>
					</div>
					<div class="col-md-12">
						<div class="text-right">
							<ul id="pagination-demo" class="pagination-lg"></ul>
						</div>
						<input type="hidden" name="page_number" value=""> 
					</div>
				</div>
			</div>
		<?php } ?>

	</div>
</div>
<?php $this->load->view('companies/property_listings/utilities/filter_form'); ?>

<?php real_client_init_tail(); ?>
<?php 
require('modules/realestate/assets/js/clients/property_listings/property_manage_js.php');
require('modules/realestate/assets/js/companies/property_listings/utilities/filter_form_js.php');

?>