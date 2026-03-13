<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
	<div class="col-md-12">
		<?php if(isset($construction_company)){
			$code = isset($construction_company) ? $construction_company->code.' '.$construction_company->name : '';

			?>
			<div class="col-md-9">
				<h4 class=""><?php echo new_html_entity_decode($code); ?></h4>
			</div>
			<div class="col-md-3 hide">
				<div class="_buttons">
					<a href="<?php echo html_entity_decode($site_url) .('company_public_profile/'.$construction_company->hash); ?>" class="btn btn-primary mright5 test pull-right display-block mbot10" target="_blank"><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_view_company_public_profile') : _l('real_view_freelance_agent_public_profile')); ?></a>
				</div>
			</div>

		<?php }else{ ?>
			<h4 class=""><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_construction_company') : _l('real_business_broker')); ?></h4>
		<?php } ?>
	</div>
</div>
<div class="row">
	<?php
	$company_id = isset($construction_company) ? $construction_company->id : '';
	echo form_hidden('company_id', $company_id); 
	?>

	<?php echo form_open_multipart($site_url.('add_edit_company/'.$company_id), array('id' => 'add_edit_company', 'autocomplete'=>'off')); ?>
	<?php echo form_hidden('related_type', $related_type); ?>
	<div class="additional">
	</div>
	<div class="col-md-12">

	</div>
	<div class="col-md-12">
		<div class="horizontal-scrollable-tabs">
			<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
			<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
			<div class="horizontal-tabs">
				<ul class="nav nav-tabs profile-tabs row customer-profile-tabs nav-tabs-horizontal" role="tablist">
					<li role="presentation" class="<?php if(!$this->input->get('tab')){echo 'active';}; ?>">
						<a href="#contact_info" aria-controls="contact_info" role="tab" data-toggle="tab">
							<span class="text-danger">(*)</span><?php echo new_html_entity_decode($related_type == 'company' ? _l( 'real_real_estate_agent_detail') : _l('real_business_broker_information')); ?>
						</a>
					</li>
					<li role="presentation">
						<a href="#billing_and_shipping" aria-controls="billing_and_shipping" role="tab" data-toggle="tab">
							<?php echo _l( 'billing_shipping'); ?>
						</a>
					</li>

					<?php if(!$company_id){ ?>
						<li role="presentation">
							<a href="#tab_staff_profile" aria-controls="tab_staff_profile" role="tab" data-toggle="tab">
								<span class="text-danger">(*)</span><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_real_estate_agent_user_profile') : _l('real_business_broker_profile')); ?>
							</a>
						</li>
						<?php if($related_type == 'company'){ ?>
							<li role="presentation" class="hide">
								<a href="#staff_permissions" aria-controls="staff_permissions" role="tab" data-toggle="tab">
									<?php echo new_html_entity_decode($related_type == 'company' ?  _l('real_user_permissions') : _l('real_business_broker_permissions')); ?>
								</a>
							</li>
						<?php } ?>
					<?php } ?>

				</ul>
			</div>
		</div>
		<div class="tab-content mtop15">

			<div role="tabpanel" class="tab-pane<?php if(!$this->input->get('tab')){echo ' active';}; ?>" id="contact_info">
				<?php if(!isset($construction_company)){ ?>
					<h4 class="bold register-company-info-heading"><?php echo new_html_entity_decode($related_type == 'company' ? _l('client_register_company_info') : _l('real_business_broker_information')); ?></h4>
				<?php } ?>
				<div class="row">
					<div class="col-md-6">

						<?php 
						$company_code=( isset($construction_company) ? $construction_company->code : $company_code);
						$company_name=( isset($construction_company) ? $construction_company->name : '');
						$vat=( isset($construction_company) ? $construction_company->vat : '');
						$phonenumber=( isset($construction_company) ? $construction_company->phonenumber : '');
						$website=( isset($construction_company) ? $construction_company->website : '');
						$email=( isset($construction_company) ? $construction_company->email : '');
						?>

						<?php $attrs = (isset($construction_company) ? array() : array('autofocus'=>true)); ?>

						<?php if ((isset($construction_company) && $construction_company->profile_image == null) || !isset($construction_company)) { ?>
							<div class="form-group">
								<label for="profile_image"
								class="profile-image"><?php echo _l('staff_edit_profile_image'); ?></label>
								<input type="file" name="agent_profile_image" class="form-control" id="agent_profile_image">
							</div>
						<?php } ?>
						<?php if (isset($construction_company) && $construction_company->profile_image != null) { ?>
							<div class="form-group">
								<div class="row">
									<div class="col-md-9">
										<?php echo company_profile_image($construction_company->id, ['img', 'img-responsive']); ?>
									</div>
									<div class="col-md-3 text-right">
										<a
										href="<?php echo html_entity_decode($site_url) . ('remove_company_profile_image/' . $construction_company->id); ?>"><i
										class="fa fa-remove"></i></a>
									</div>
								</div>
							</div>
						<?php } ?>

						<div class="row">
							<div class="col-md-6">
								<?php echo render_input('code','real_code_label',$company_code,'text',['readonly' =>true]); ?>
							</div>
							<div class="col-md-6">
								<?php echo render_input( 'name', $related_type == 'company' ? 'real_real_estate_agent_name' : 'real_business_broker_name',$company_name,'text',$attrs); ?>
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
						<?php $about_information=( isset($construction_company) ? $construction_company->about_information : ''); ?>
						<?php echo render_textarea( 'about_information', 'real_about_information',$about_information); ?>
						
						<?php $value=( isset($construction_company) ? $construction_company->address : '');
						$rows['rows']=1;
						?>
						<?php echo render_textarea( 'address', 'client_address',$value, $rows); ?>
						<?php $value=( isset($construction_company) ? $construction_company->city : ''); ?>
						<?php echo render_input( 'city', 'client_city',$value); ?>
						<?php $value=( isset($construction_company) ? $construction_company->state : ''); ?>
						<?php echo render_input( 'state', 'client_state',$value); ?>
						<?php $value=( isset($construction_company) ? $construction_company->zip : ''); ?>
						<?php echo render_input( 'zip', 'client_postal_code',$value); ?>
						<?php $countries= get_all_countries();
						$customer_default_country = get_option('customer_default_country');
						$selected =( isset($construction_company) ? $construction_company->country : $customer_default_country);
						echo render_select( 'country',$countries,array( 'country_id',array( 'short_name')), 'clients_country',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex')));
						?>

					</div>
				</div>
				
				<div class="row">
					<div class="col-md-<?php if($related_type == 'company'){ echo "6";}else{ echo "12";} ?>">
						<h4 class="bold register-company-info-heading"><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_real_estate_agent_social_medial_accounts') : _l('real_business_broker_social_medial_accounts')); ?></h4>
						<hr>
						<?php $value=( isset($construction_company) ? $construction_company->facebook_url : ''); ?>
						<?php echo render_input( 'facebook_url', 'real_facebook_url',$value, '', ['placeholder' => _l('real_facebook_url')]); ?>
						<?php $value=( isset($construction_company) ? $construction_company->instagram_url : ''); ?>
						<?php echo render_input( 'instagram_url', 'real_instagram_url',$value, '', ['placeholder' => _l('real_instagram_url')]); ?>
						<?php $value=( isset($construction_company) ? $construction_company->whatsapp_url : ''); ?>
						<?php echo render_input( 'whatsapp_url', 'real_whatsapp_url',$value, '', ['placeholder' => _l('real_whatsapp_url')]); ?>

					</div>
					<?php if($related_type == 'company'){ ?>
						<div class="col-md-6">
							<h4 class="bold register-company-info-heading"><?php echo _l('real_plans'); ?></h4>
							<hr>
							<?php 
							$plan_att =[];
							$check_staff_type = rel_check_staff_type();
							$is_company_admin = $check_staff_type['is_company_admin'];

							if(!is_admin() && ($is_company_admin != 1)){
								$plan_att =['disabled' => true, 'data-none-selected-text'=>_l('dropdown_non_selected_tex')];
							}
							?>

							<?php $plan_id=( isset($construction_company) ? $construction_company->plan_id : '' ); ?>
							<?php echo render_select( 'plan_id', $plans, array( 'id','name'), 'real_select_plan',$plan_id, $plan_att); ?>
						</div>
					<?php } ?>
					
				</div>

				<div class="row">
					<div class="col-md-6">
						<h4 class="bold register-company-info-heading"><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_real_estate_agent_global_privacy') : _l('real_real_estate_agent_business_broker_privacy')); ?></h4>
						<hr>
						<?php 
						$privacy=( isset($construction_company) ? $construction_company->privacy : 'private');
						?>

						<div class=" boxed-check-group boxed-check-outline-warning">
							<div class="col-12 col-sm-3">
								<label class="boxed-check">
									
									<input class="boxed-check-input" type="radio" id="y_opt_1" name="privacy" value="private" <?php if($privacy == 'private'){ echo 'checked';}; ?>>

									<div class="boxed-check-label text-center">
										<div><img src="<?php echo site_url('modules/realestate/assets/images/private.svg') ?>" class="img img-responsive display-inline" width=70></div><div><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_private_company_des') : _l('real_private_business_broker_des')); ?></div></div>

								</label>
							</div>
						</div>
						<div class=" boxed-check-group boxed-check-outline-success">
							<div class="col-12 col-sm-3">
								<label class="boxed-check">

									<input class="boxed-check-input" type="radio" id="y_opt_2" name="privacy" value="public" <?php if($privacy == 'public'){ echo 'checked';}; ?>>

									<div class="boxed-check-label text-center"><div><img src="<?php echo site_url('modules/realestate/assets/images/public.svg') ?>" class="img img-responsive display-inline" width=70></div><div><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_public_company_des') : _l('real_public_business_broker_des')); ?></div></div>
								</label>
							</div>
						</div>


					</div>


					
					<div class="col-md-6">
						<h4 class="bold register-company-info-heading"><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_real_estate_agent_verification_status') : _l('real_business_broker_verification_status')); ?></h4>
						<hr>
						<?php 
						$verification_status=( isset($construction_company) ? $construction_company->verification_status : 'verified');
						?>

						<div class=" boxed-check-group boxed-check-outline-success">
							<div class="col-12 col-sm-3">
								<label class="boxed-check">
									<?php if(!is_admin() && !has_permission('real_business_broker', '', 'create') && !has_permission('real_business_broker', '', 'edit')){ ?>
										<input class="boxed-check-input" type="radio" id="y_opt_3_" name="verification_status" value="verified" disabled <?php if($verification_status == 'verified'){ echo 'checked';}; ?>>
									<?php }else{ ?>
										<input class="boxed-check-input" type="radio" id="y_opt_3_" name="verification_status" value="verified" <?php if($verification_status == 'verified'){ echo 'checked';}; ?>>
									<?php } ?>

									<div class="boxed-check-label text-center"><div><img src="<?php echo site_url('modules/realestate/assets/images/verified.svg') ?>" class="img img-responsive display-inline" width=70></div><div><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_verified_company_des') : _l('real_verified_broker_des')); ?></div></div>

								</label>
							</div>
						</div>
						<div class=" boxed-check-group boxed-check-outline-warning">
							<div class="col-12 col-sm-3">
								<label class="boxed-check">
									<?php if(!is_admin() && !has_permission('real_business_broker', '', 'create') && !has_permission('real_business_broker', '', 'edit')){ ?>
										<input class="boxed-check-input" type="radio" id="y_opt_4_" name="verification_status" value="regular" disabled <?php if($verification_status == 'regular'){ echo 'checked';}; ?>>
									<?php }else{ ?>
										<input class="boxed-check-input" type="radio" id="y_opt_4_" name="verification_status" value="regular" <?php if($verification_status == 'regular'){ echo 'checked';}; ?>>
									<?php } ?>

									<div class="boxed-check-label text-center"><div><img src="<?php echo site_url('modules/realestate/assets/images/unverified.svg') ?>" class="img img-responsive display-inline" width=70></div><div><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_regular_company_des') : _l('real_regular_broker_des')); ?></div></div>
								</label>
							</div>
						</div>


					</div>
				</div>

				<div class="row">
					<div class="col-md-12">

						<h4 class="bold register-company-info-heading"><?php echo new_html_entity_decode($related_type == 'company' ? _l('real_attach_pdf_files_for_company_public_profile') : _l('real_attach_pdf_files_for_business_broker_public_profile')); ?></h4>
						<hr>
					</div>
					<div class="col-md-6">

						<div class="row">
							<div class="col-md-12">
								<div class=" attachments">
									<div class="attachment">
										<div class="form-group">
											<div class="input-group">
												<input type="file" extension="<?php echo new_str_replace('.','',get_option('allowed_files')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="file[0]">
												<span class="input-group-btn">
													<button class="btn btn-success add_more_attachments_file p8" type="button"><i class="fa fa-plus"></i></button>
												</span>
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>

					</div>

				</div>
				<?php if(isset($pdf_attachment) && count($pdf_attachment) > 0){ ?>
					<div class="row">
						<div class="col-md-12">
							<div id="contract_attachments" class="mtop30 ">

								<?php
								$data = '<div class="row" id="attachment_file">';
								foreach($pdf_attachment as $attachment) {
									$data .= '<div class="col-md-6">';
									$href_url = site_url('modules/realestate/uploads/company_pdfs/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
									if(!empty($attachment['external'])){
										$href_url = $attachment['external_link'];
									}
									$data .= '<div class="display-block contract-attachment-wrapper">';
									$data .= '<div class="col-md-10">';
									$data .= '<div class="col-md-1 mright5">';
									$data .= '<a name="preview-btn" onclick="preview_file(this); return false;" rel_id = "'.$attachment['rel_id'].'" id = "'.$attachment['id'].'" href="Javascript:void(0);" class="mbot10 btn btn-success pull-left" data-toggle="tooltip" title data-original-title="'._l("preview_file").'">';
									$data .= '<i class="fa fa-eye"></i>'; 
									$data .= '</a>';
									$data .= '</div>';
									$data .= '<div class=col-md-9>';
									$data .= '<div class="pull-left"><i class="'.get_mime_class($attachment['filetype']).'"></i></div>';
									$data .= '<a href="'.$href_url.'>'.$attachment['file_name'].'</a>';
									$data .= '<p class="text-muted">'.$attachment["filetype"].'</p>';
									$data .= '</div>';
									$data .= '</div>';
									$data .= '<div class="col-md-2 text-right">';
									if(is_admin() || has_permission('real_estate_agent', '', 'delete') || has_permission('rel_agent', '', 'delete') || is_broker_logged_in()){
										$data .= '<a href="#" class="text-danger" onclick="delete_company_attachment_pdf_file(this,'.$attachment['id'].', \'COMPANY_PDF_UPLOAD\'); return false;"><i class="fa fa fa-times"></i></a>';
									}
									$data .= '</div>';
									$data .= '<div class="clearfix"></div><hr>';
									$data .= '</div>';
									$data .= '</div>';
								}
								$data .= '</div>';
								echo new_html_entity_decode($data);
								?>
								<!-- check if edit contract => display attachment file end-->

							</div>
							<div id="contract_file_data"></div>
						</div>
					</div>
				<?php } ?>

			</div>

			<div role="tabpanel" class="tab-pane" id="billing_and_shipping">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="col-md-6">
								<h4 class="no-mtop"><?php echo _l('billing_address'); ?> <a href="#" class="pull-right billing-same-as-customer"><small class="font-medium-xs"><?php echo _l('real_real_estate_agent_billing_same_as_profile'); ?></small></a></h4>
								<hr />
								<?php $value=( isset($construction_company) ? $construction_company->billing_street : ''); ?>
								<?php echo render_textarea( 'billing_street', 'billing_street',$value); ?>
								<?php $value=( isset($construction_company) ? $construction_company->billing_city : ''); ?>
								<?php echo render_input( 'billing_city', 'billing_city',$value); ?>
								<?php $value=( isset($construction_company) ? $construction_company->billing_state : ''); ?>
								<?php echo render_input( 'billing_state', 'billing_state',$value); ?>
								<?php $value=( isset($construction_company) ? $construction_company->billing_zip : ''); ?>
								<?php echo render_input( 'billing_zip', 'billing_zip',$value); ?>
								<?php $selected=( isset($construction_company) ? $construction_company->billing_country : '' ); ?>
								<?php echo render_select( 'billing_country',$countries,array( 'country_id',array( 'short_name')), 'billing_country',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex'))); ?>
							</div>
							<div class="col-md-6">
								<h4 class="no-mtop">
									<i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('customer_shipping_address_notice'); ?>"></i>
									<?php echo _l('shipping_address'); ?> <a href="#" class="pull-right customer-copy-billing-address"><small class="font-medium-xs"><?php echo _l('customer_billing_copy'); ?></small></a>
								</h4>
								<hr />
								<?php $value=( isset($construction_company) ? $construction_company->shipping_street : ''); ?>
								<?php echo render_textarea( 'shipping_street', 'shipping_street',$value); ?>
								<?php $value=( isset($construction_company) ? $construction_company->shipping_city : ''); ?>
								<?php echo render_input( 'shipping_city', 'shipping_city',$value); ?>
								<?php $value=( isset($construction_company) ? $construction_company->shipping_state : ''); ?>
								<?php echo render_input( 'shipping_state', 'shipping_state',$value); ?>
								<?php $value=( isset($construction_company) ? $construction_company->shipping_zip : ''); ?>
								<?php echo render_input( 'shipping_zip', 'shipping_zip',$value); ?>
								<?php $selected=( isset($construction_company) ? $construction_company->shipping_country : '' ); ?>
								<?php echo render_select( 'shipping_country',$countries,array( 'country_id',array( 'short_name')), 'shipping_country',$selected,array('data-none-selected-text'=>_l('dropdown_non_selected_tex'))); ?>
							</div>

						</div>
					</div>
				</div>
			</div>

			<?php if(!$company_id){ ?>

				<div role="tabpanel" class="tab-pane" id="tab_staff_profile">
					<div class="is-not-staff hide">
						<div class="checkbox checkbox-primary">
							<?php
							$checked = '';
							if (isset($member)) {
								if ($member->is_not_staff == 1) {
									$checked = ' checked';
								}
							}
							?>
							<input type="checkbox" value="1" name="is_not_staff" id="is_not_staff"
							<?php echo new_html_entity_decode($checked); ?>>
							<label for="is_not_staff"><?php echo _l('is_not_staff_member'); ?></label>
						</div>
						<hr />
					</div>
					<?php if ((isset($member) && $member->profile_image == null) || !isset($member)) { ?>
						<div class="form-group">
							<label for="profile_image"
							class="profile-image"><?php echo _l('staff_edit_profile_image'); ?></label>
							<input type="file" name="profile_image" class="form-control" id="profile_image">
						</div>
					<?php } ?>
					<?php if (isset($member) && $member->profile_image != null) { ?>
						<div class="form-group">
							<div class="row">
								<div class="col-md-9">
									<?php echo staff_profile_image($member->staffid, ['img', 'img-responsive', 'staff-profile-image-thumb'], 'thumb'); ?>
								</div>
								<div class="col-md-3 text-right">
									<a
									href="<?php echo html_entity_decode($site_url) . ('staff/remove_staff_profile_image/' . $member->staffid); ?>"><i
									class="fa fa-remove"></i></a>
								</div>
							</div>
						</div>
					<?php } ?>
					<?php $value = (isset($member) ? $member->firstname : ''); ?>
					<?php $attrs = (isset($member) ? [] : ['autofocus' => true]); ?>
					<?php echo render_input('firstname', 'staff_add_edit_firstname', $value, 'text', $attrs); ?>
					<?php $value = (isset($member) ? $member->lastname : ''); ?>
					<?php echo render_input('lastname', 'staff_add_edit_lastname', $value); ?>
					<?php $value = (isset($member) ? $member->email : ''); ?>
					<?php echo render_input('email', 'staff_add_edit_email', $value, 'email', ['autocomplete' => 'off']); ?>
					<div class="form-group">
						<label for="hourly_rate"><?php echo _l('staff_hourly_rate'); ?></label>
						<div class="input-group">
							<input type="number" name="hourly_rate" value="<?php if (isset($member)) {
								echo new_html_entity_decode($member->hourly_rate);
							} else {
								echo 0;
							} ?>" id="hourly_rate" class="form-control">
							<span class="input-group-addon">
								<?php echo new_html_entity_decode($base_currency->symbol); ?>
							</span>
						</div>
					</div>
					<?php $value = (isset($member) ? $member->phonenumber : ''); ?>
					<?php echo render_input('phonenumber', 'staff_add_edit_phonenumber', $value); ?>
					<div class="form-group">
						<label for="facebook" class="control-label"><i class="fa-brands fa-facebook-f"></i>
							<?php echo _l('staff_add_edit_facebook'); ?></label>
							<input type="text" class="form-control" name="facebook" value="<?php if (isset($member)) {
								echo new_html_entity_decode($member->facebook);
							} ?>">
						</div>
						<div class="form-group">
							<label for="linkedin" class="control-label"><i class="fa-brands fa-linkedin-in"></i>
								<?php echo _l('staff_add_edit_linkedin'); ?></label>
								<input type="text" class="form-control" name="linkedin" value="<?php if (isset($member)) {
									echo new_html_entity_decode($member->linkedin);
								} ?>">
							</div>
							<div class="form-group">
								<label for="skype" class="control-label"><i class="fa-brands fa-skype"></i>
									<?php echo _l('staff_add_edit_skype'); ?></label>
									<input type="text" class="form-control" name="skype" value="<?php if (isset($member)) {
										echo new_html_entity_decode($member->skype);
									} ?>">
								</div>
								<?php if (!is_language_disabled()) { ?>
									<div class="form-group select-placeholder">
										<label for="default_language"
										class="control-label"><?php echo _l('localization_default_language'); ?></label>
										<select name="default_language" data-live-search="true" id="default_language"
										class="form-control selectpicker"
										data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
										<option value=""><?php echo _l('system_default_string'); ?></option>
										<?php foreach ($this->app->get_available_languages() as $availableLanguage) {
											$selected = '';
											if (isset($member)) {
												if ($member->default_language == $availableLanguage) {
													$selected = 'selected';
												}
											} ?>
											<option value="<?php echo new_html_entity_decode($availableLanguage); ?>" <?php echo new_html_entity_decode($selected); ?>>
												<?php echo ucfirst($availableLanguage); ?></option>
												<?php
											} ?>
										</select>
									</div>
								<?php } ?>
								<i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1"
								data-toggle="tooltip"
								data-title="<?php echo _l('staff_email_signature_help'); ?>"></i>
								<?php $value = (isset($member) ? $member->email_signature : ''); ?>
								<?php echo render_textarea('email_signature', 'settings_email_signature', $value, ['data-entities-encode' => 'true']); ?>
								<div class="form-group select-placeholder">
									<label for="direction"><?php echo _l('document_direction'); ?></label>
									<select class="selectpicker"
									data-none-selected-text="<?php echo _l('system_default_string'); ?>"
									data-width="100%" name="direction" id="direction">
									<option value="" <?php if (isset($member) && empty($member->direction)) {
										echo 'selected';
									} ?>></option>
									<option value="ltr" <?php if (isset($member) && $member->direction == 'ltr') {
										echo 'selected';
									} ?>>LTR</option>
									<option value="rtl" <?php if (isset($member) && $member->direction == 'rtl') {
										echo 'selected';
									} ?>>RTL</option>
								</select>
							</div>
							<div class="form-group">
								<?php if (count($departments) > 0) { ?>
									<label for="departments"><?php echo _l('staff_add_edit_departments'); ?></label>
								<?php } ?>
								<?php foreach ($departments as $department) { ?>
									<div class="checkbox checkbox-primary">
										<?php
										$checked = '';
										if (isset($member)) {
											foreach ($staff_departments as $staff_department) {
												if ($staff_department['departmentid'] == $department['departmentid']) {
													$checked = ' checked';
												}
											}
										}
										?>
										<input type="checkbox" id="dep_<?php echo new_html_entity_decode($department['departmentid']); ?>"
										name="departments[]" value="<?php echo new_html_entity_decode($department['departmentid']); ?>"
										<?php echo new_html_entity_decode($checked); ?>>
										<label
										for="dep_<?php echo new_html_entity_decode($department['departmentid']); ?>"><?php echo new_html_entity_decode($department['name']); ?></label>
									</div>
								<?php } ?>
							</div>
							<?php $rel_id = (isset($member) ? $member->staffid : false); ?>
							<?php echo render_custom_fields('staff', $rel_id); ?>

							<div class="row">
								<div class="col-md-12">
									<hr class="hr-10" />
									<?php if (is_admin()) { ?>
										<div class="checkbox checkbox-primary hide">
											<?php
											$isadmin = '';
											if (isset($member) && ($member->staffid == get_staff_user_id() || is_admin($member->staffid))) {
												$isadmin = ' checked';
											}
											?>
											<input type="checkbox" name="administrator" id="administrator"
											<?php echo new_html_entity_decode($isadmin); ?>>
											<label
											for="administrator"><?php echo _l('staff_add_edit_administrator'); ?></label>
										</div>
									<?php } ?>
									<?php if (!isset($member) && is_email_template_active('new-staff-created')) { ?>
										<div class="checkbox checkbox-primary">
											<input type="checkbox" name="send_welcome_email" id="send_welcome_email"
											checked>
											<label
											for="send_welcome_email"><?php echo _l('staff_send_welcome_email'); ?></label>
										</div>
									<?php } ?>

								</div>
							</div>
							<?php if (!isset($member) || is_admin() || !is_admin() && $member->admin == 0) { ?>
								<!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
								<input type="text" class="fake-autofill-field" name="fakeusernameremembered" value=''
								tabindex="-1" />
								<input type="password" class="fake-autofill-field" name="fakepasswordremembered"
								value='' tabindex="-1" />
								<div class="clearfix form-group"></div>
								<label for="password"
								class="control-label"><?php echo _l('staff_add_edit_password'); ?></label>
								<div class="input-group">
									<input type="password" class="form-control password" name="password"
									autocomplete="off">
									<span class="input-group-addon tw-border-l-0">
										<a href="#password" class="show_password"
										onclick="showPassword('password'); return false;"><i
										class="fa fa-eye"></i></a>
									</span>
									<span class="input-group-addon">
										<a href="#" class="generate_password"
										onclick="generatePassword(this);return false;"><i
										class="fa fa-refresh"></i></a>
									</span>
								</div>
								<?php if (isset($member)) { ?>
									<p class="text-muted"><?php echo _l('staff_add_edit_password_note'); ?></p>
									<?php if ($member->last_password_change != null) { ?>
										<?php echo _l('staff_add_edit_password_last_changed'); ?>:
										<span class="text-has-action" data-toggle="tooltip"
										data-title="<?php echo _dt($member->last_password_change); ?>">
										<?php echo time_ago($member->last_password_change); ?>
									</span>
								<?php } } ?>
							<?php } ?>
						</div>
						<?php if($related_type == 'company'){ ?>
							<div role="tabpanel" class="tab-pane hide" id="staff_permissions">
								<?php
								hooks()->do_action('staff_render_permissions');
								$selected = '';
								foreach ($roles as $role) {
									if (isset($member)) {
										if ($member->role == $role['roleid']) {
											$selected = $role['roleid'];
										}
									} else {
										$default_staff_role = get_option('default_staff_role');
										if ($default_staff_role == $role['roleid']) {
											$selected = $role['roleid'];
										}
									}
								}
								?>
								<?php echo render_select('role', $roles, ['roleid', 'name'], 'staff_add_edit_role', $selected); ?>
								<hr />
								<h4 class="font-medium mbot15 bold"><?php echo _l('staff_add_edit_permissions'); ?></h4>
								<?php
								$permissionsData = [ 'funcData' => ['staff_id' => isset($member) ? $member->staffid : null ] ];
								if (isset($member)) {
									$permissionsData['member'] = $member;
								}
								$this->load->view('admin/staff/permissions', $permissionsData);
								?>
							</div>
						<?php } ?>
					<?php } ?>
				</div>
			</div>
			<?php echo form_close(); ?>
		</div>
