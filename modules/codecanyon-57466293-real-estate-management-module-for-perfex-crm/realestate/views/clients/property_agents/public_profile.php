<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('app_customers_portal_head'); ?>

<div class="panel_s">
	<div class="panel-body">
		<?php echo form_hidden('company_id', $construction_company->id); ?>
		<?php $this->load->view('companies/companies/groups/review_template'); ?>
	</div>
</div>

<?php real_client_init_tail(); ?>
<?php 
require 'modules/realestate/assets/js/companies/companies/company_review_js.php';
require('modules/realestate/assets/js/map_js.php');

?>