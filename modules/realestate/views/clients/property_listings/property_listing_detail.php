<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('app_customers_portal_head'); ?>

<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
	<?php echo e($title); ?>

</h4>

<div class="panel_s">
	<div class="panel-body">
		
		<div class="col-md-12 no-p-left no-p-right">                 
			<div class="row">
				<div class="col-md-12 no-p-left no-p-right">
					<?php $this->load->view('companies/property_listings/utilities/property_template'); ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php real_client_init_tail(); ?>
<?php 
require('modules/realestate/assets/js/companies/property_listings/property_listing_detail_js.php');
require('modules/realestate/assets/js/companies/property_listings/preview_property_file_js.php');
?>