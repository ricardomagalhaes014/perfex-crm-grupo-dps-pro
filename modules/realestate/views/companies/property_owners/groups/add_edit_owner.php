<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$owner_id = isset($owner) ? $owner->id : '';
$company_id = isset($owner) ? $owner->company_id : '';
echo form_hidden('owner_id', $owner_id); 
?>

<div class="row">
	<div class="col-md-12">
		<?php if(isset($owner)){ 
			$code = isset($owner) ? $owner->code.' '.$owner->name : '';
			?>
			<div class="row">
				<div class="col-md-9">
					<h4 class=""><?php echo new_html_entity_decode($code); ?></h4>
				</div>
				<div class="col-md-3 hide">
					<div class="_buttons">
						<a href="<?php echo html_entity_decode($site_url).('owner_public_profile/'.$owner->hash); ?>" class="btn btn-primary mright5 test pull-right display-block mbot10"><?php echo _l('real_view_property_owner_public_profile'); ?></a>
					</div>
				</div>
			</div>
		<?php }else{ ?>
			<h4 class=""><?php echo _l('real_property_owner'); ?></h4>
		<?php } ?>

	</div>
</div>
<div class="row">
	<?php echo form_open_multipart(html_entity_decode($site_url).('add_edit_owner/'.$owner_id), array('id' => 'add_edit_owner', 'autocomplete'=>'off')); ?>
	<?php 
	echo form_hidden('company_id', $company_id); 
	?>
	<div class="additional">
	</div>
	
	<div class="col-md-12">
		<div class="horizontal-scrollable-tabs">
			<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
			<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
			<div class="horizontal-tabs">
				<ul class="nav nav-tabs profile-tabs row customer-profile-tabs nav-tabs-horizontal" role="tablist">
					<li role="presentation" class="<?php if(!$this->input->get('tab')){echo 'active';}; ?>">
						<a href="#contact_info" aria-controls="contact_info" role="tab" data-toggle="tab">
							<span class="text-danger">(*)</span><?php echo _l( 'real_property_owner_detail'); ?>
						</a>
					</li>
					<li role="presentation">
						<a href="#billing_and_shipping" aria-controls="billing_and_shipping" role="tab" data-toggle="tab">
							<?php echo _l( 'billing_shipping'); ?>
						</a>
					</li>
				</ul>
			</div>
		</div>
		<div class="tab-content mtop15">

			<div role="tabpanel" class="tab-pane<?php if(!$this->input->get('tab')){echo ' active';}; ?>" id="contact_info">
				<?php if(!isset($owner)){ ?>
					<h4 class="bold register-company-info-heading"><?php echo _l('real_property_owner_info'); ?></h4>
				<?php } ?>
				<div class="row">
					<div class="col-md-6">

						<?php 
						$company_name=( isset($owner) ? $owner->name : '');
						$company_code=( isset($owner) ? $owner->code : $owner_code);
						$vat= ( isset($owner) ? $owner->vat : '');
						$phonenumber=( isset($owner) ? $owner->phonenumber : '');
						$website=( isset($owner) ? $owner->website : '');
						$email=( isset($owner) ? $owner->email : '');
						?>

						<?php $attrs = (isset($owner) ? array() : array('autofocus'=>true)); ?>

						<?php if ((isset($owner) && $owner->profile_image == null) || !isset($owner)) { ?>
							<div class="form-group">
								<label for="profile_image"
								class="profile-image"><?php echo _l('staff_edit_profile_image'); ?></label>
								<input type="file" name="profile_image" class="form-control" id="profile_image">
							</div>
						<?php } ?>
						<?php if (isset($owner) && $owner->profile_image != null) { ?>
							<div class="form-group">
								<div class="row">
									<div class="col-md-9">
										<?php echo owner_profile_image($owner->id, ['img', 'img-responsive']); ?>
									</div>
									<div class="col-md-3 text-right">
										<a
										href="<?php echo html_entity_decode($site_url).('remove_owner_profile_image/' . $owner->id); ?>"><i
										class="fa fa-remove"></i></a>
									</div>
								</div>
							</div>
						<?php } ?>

						<div class="row">
							<div class="col-md-6">
								<?php echo render_input( 'code', 'real_code_label',$company_code,'text', ['readonly' => true]); ?>
							</div>
							<div class="col-md-6">
								<?php echo render_input( 'name', 'real_owner_name',$company_name,'text',$attrs); ?>
								
							</div>
						</div>

						<?php echo render_input( 'vat', 'client_vat_number',$vat); ?>
						<?php echo render_input( 'company_email', 'real_email',$email, 'email'); ?>
						<div class="row">
							<div class="col-md-12">
								<?php echo render_input( 'company_phonenumber', 'real_phonenumber',$phonenumber, 'text'); ?>
							</div>
						</div>
						<?php echo render_input( 'website', 'client_website',$website); ?>
					</div>
					<div class="col-md-6">
						<?php $value=( isset($owner) ? $owner->address : '');
						$rows['rows']=1;
						?>
						<?php echo render_textarea( 'address', 'client_address',$value, $rows); ?>
						<?php $value=( isset($owner) ? $owner->city : ''); ?>
						<?php echo render_input( 'city', 'client_city',$value); ?>
						<?php $value=( isset($owner) ? $owner->state : ''); ?>
						<?php echo render_input( 'state', 'client_state',$value); ?>
						<?php $value=( isset($owner) ? $owner->zip : ''); ?>
						<?php echo render_input( 'zip', 'client_postal_code',$value); ?>
						<?php $countries= get_all_countries();
						$customer_default_country = get_option('customer_default_country');
						$selected =( isset($owner) ? $owner->country : $customer_default_country);
						echo render_select( 'country',$countries,array( 'country_id',array( 'short_name')), 'clients_country',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex')));
						?>

					</div>
					
				</div>


				<div class="row">
					<div class="col-md-12">
						<h4 class="bold register-company-info-heading"><?php echo  _l('real_property_owner_social_medial_accounts'); ?></h4>
						<hr>
					</div>
					<div class="col-md-6">
						<?php $value=( isset($owner) ? $owner->facebook_url : ''); ?>
						<?php echo render_input( 'facebook_url', 'real_facebook_url',$value, '', ['placeholder' => _l('real_facebook_url')]); ?>
						<?php $value=( isset($owner) ? $owner->instagram_url : ''); ?>
						<?php echo render_input( 'instagram_url', 'real_instagram_url',$value, '', ['placeholder' => _l('real_instagram_url')]); ?>
					</div>
					<div class="col-md-6">
						<?php $value=( isset($owner) ? $owner->whatsapp_url : ''); ?>
						<?php echo render_input( 'whatsapp_url', 'real_whatsapp_url',$value, '', ['placeholder' => _l('real_whatsapp_url')]); ?>
						
					</div>
				</div>

			</div>

			<div role="tabpanel" class="tab-pane" id="billing_and_shipping">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-6">
								<h4 class="no-mtop"><?php echo _l('billing_address'); ?> <a href="#" class="pull-right billing-same-as-customer"><small class="font-medium-xs"><?php echo _l('real_property_owner_billing_same_as_profile'); ?></small></a></h4>
								<hr />
								<?php $value=( isset($owner) ? $owner->billing_street : ''); ?>
								<?php echo render_textarea( 'billing_street', 'billing_street',$value); ?>
								<?php $value=( isset($owner) ? $owner->billing_city : ''); ?>
								<?php echo render_input( 'billing_city', 'billing_city',$value); ?>
								<?php $value=( isset($owner) ? $owner->billing_state : ''); ?>
								<?php echo render_input( 'billing_state', 'billing_state',$value); ?>
								<?php $value=( isset($owner) ? $owner->billing_zip : ''); ?>
								<?php echo render_input( 'billing_zip', 'billing_zip',$value); ?>
								<?php $selected=( isset($owner) ? $owner->billing_country : '' ); ?>
								<?php echo render_select( 'billing_country',$countries,array( 'country_id',array( 'short_name')), 'billing_country',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex'))); ?>
							</div>
							<div class="col-md-6">
								<h4 class="no-mtop">
									<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('customer_shipping_address_notice'); ?>"></i>
									<?php echo _l('shipping_address'); ?> <a href="#" class="pull-right customer-copy-billing-address"><small class="font-medium-xs"><?php echo _l('customer_billing_copy'); ?></small></a>
								</h4>
								<hr />
								<?php $value=( isset($owner) ? $owner->shipping_street : ''); ?>
								<?php echo render_textarea( 'shipping_street', 'shipping_street',$value); ?>
								<?php $value=( isset($owner) ? $owner->shipping_city : ''); ?>
								<?php echo render_input( 'shipping_city', 'shipping_city',$value); ?>
								<?php $value=( isset($owner) ? $owner->shipping_state : ''); ?>
								<?php echo render_input( 'shipping_state', 'shipping_state',$value); ?>
								<?php $value=( isset($owner) ? $owner->shipping_zip : ''); ?>
								<?php echo render_input( 'shipping_zip', 'shipping_zip',$value); ?>
								<?php $selected=( isset($owner) ? $owner->shipping_country : '' ); ?>
								<?php echo render_select( 'shipping_country',$countries,array( 'country_id',array( 'short_name')), 'shipping_country',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex'))); ?>
							</div>

						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	<?php echo form_close(); ?>
</div>

