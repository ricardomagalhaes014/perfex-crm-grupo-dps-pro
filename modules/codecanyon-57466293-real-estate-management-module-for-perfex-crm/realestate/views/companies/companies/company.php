<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
if(is_broker_logged_in()){
	broker_init_head();
}else{
	init_head();
}

?>
<div id="wrapper" class="customer_profile">
	<div class="content">
		<div class="row">

			<?php if($group == 'add_edit_company'){ ?>
				<div class="btn-bottom-toolbar btn-toolbar-container-out text-right">
					<?php if(isset($related_type) && $related_type == 'business_broker'){ ?>
						
					<?php }else{ ?>
						<a href="<?php echo html_entity_decode($site_url).('companies'); ?>"class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>
					<?php } ?>
					<?php if(has_permission('real_estate_agent', '', 'create') || has_permission('real_estate_agent', '', 'edit') || is_broker_logged_in()){ ?>
						<button type="submit" class="btn btn-primary only-save sub-company-form-submiter">
							<?php echo _l( 'submit'); ?>
						</button>
					<?php } ?>

				</div>
			<?php } ?>
			<?php if(isset($construction_company) && isset($is_staff)){ ?>
				<div class="col-md-3">
					<div class="panel_s ">
						<div class="panel-body padding-10">
							<h4 class="bold">
								#<?php echo html_entity_decode($construction_company->id . ' ' . $title); ?>
							</h4>
						</div>
					</div>
					<?php $this->load->view('companies/companies/tabs'); ?>
				</div>
			<?php } ?>
			<div class="col-md-<?php if(isset($construction_company) && isset($is_staff)){echo 9;} else {echo 12;} ?>">
				<div class="panel_s">
					<div class="panel-body">
						<?php if(isset($construction_company)){ ?>
							<?php echo form_hidden('isedit'); ?>
							<?php echo form_hidden('company_id', $construction_company->id); ?>
							<div class="clearfix"></div>
						<?php } ?>
						<div>
							<div class="tab-content">
								<?php $this->load->view((isset($tabs) ? $tabs['view'] : 'companies/companies/groups/add_edit_company')); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if($group == 'add_edit_company'){ ?>
			<div class="btn-bottom-pusher"></div>
		<?php } ?>
	</div>
</div>
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

<?php if(!(strpos($viewuri,'admin/realestate/add_edit_company') === false)){ 
	require 'modules/realestate/assets/js/companies/companies/add_edit_company_js.php';
	require 'modules/realestate/assets/js/companies/companies/staff_manage_js.php';

	if(!(strpos($viewuri,'?group=broker_staffs') === false)){ 
		require('modules/realestate/assets/js/companies/business_brokers/staff_manage_js.php');
	}

	if(!(strpos($viewuri,'?group=company_listings') === false)){ 
		require 'modules/realestate/assets/js/companies/companies/company_listing_manage_js.php';
		require('modules/realestate/assets/js/map_js.php');
	}
	
	if(!(strpos($viewuri,'?group=review') === false)){ 
		require 'modules/realestate/assets/js/companies/companies/company_review_js.php';
		require('modules/realestate/assets/js/map_js.php');
	}
}

if(!(strpos($viewuri,'realestate/broker/add_edit_company') === false)){ 
	require 'modules/realestate/assets/js/brokers/companies/add_edit_company_js.php';
}

?>


</body>
</html>
