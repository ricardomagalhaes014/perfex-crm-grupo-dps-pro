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
			<?php if($group == 'add_edit_owner'){ ?>
				<div class="btn-bottom-toolbar btn-toolbar-container-out text-right">
					<a href="<?php echo html_entity_decode($site_url). ('property_owners'); ?>"class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>
					<button type="submit" class="btn btn-primary only-save sub-company-form-submiter">
						<?php echo _l( 'submit'); ?>
					</button>
				</div>
			<?php } ?>
			<div class="col-md-12">
				<div class="panel_s">
					<div class="panel-body">
						<?php if(isset($owner)){ ?>
							<?php echo form_hidden('owner_id', $owner->id); ?>
							<div class="clearfix"></div>
						<?php } ?>
						<div>
							<div class="tab-content">
								<?php $this->load->view((isset($tabs) ? $tabs['view'] : 'companies/property_owners/groups/add_edit_owner')); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php if($group == 'add_edit_owner'){ ?>
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
require 'modules/realestate/assets/js/companies/property_owners/add_edit_owner_js.php';
?>

</body>
</html>
