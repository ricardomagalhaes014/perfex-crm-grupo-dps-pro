<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('app_customers_portal_head'); ?>
<div class="col-md-12">
	<?php echo form_open_multipart('realestate/client/renter_profile',array('autocomplete'=>'off')); ?>

	<h2 class="tw-mt-0 tw-font-semibold tw-text-neutral-700 section-heading section-heading-invoices">
		<?php echo e($title); ?>
	</h2>
	<p><?php echo _l('real_renter_description'); ?></p>
	<h4 class="tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
		<?php echo _l('real_personal'); ?>
	</h4>
	<p><?php echo _l('real_renter_long_description'); ?></p>


	<div class="panel_s">
		<div class="panel-body">
			<div class="accordion" id="accordionExample">
				<div class="card mbot20">
					<div class="card-header" id="heading1">
						<h4 data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_personal_details'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse1" class="collapse " aria-labelledby="heading1" data-parent="#accordionExample">
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('firstname', 'clients_firstname', $renter_profile->firstname); ?>
								</div>
								<div class="col-md-6">
									<?php echo render_input('lastname', 'clients_lastname', $renter_profile->lastname); ?>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('title', 'contact_position', $renter_profile->title); ?>
								</div>
								<div class="col-md-6">
									<?php echo render_input('email', 'clients_email', $renter_profile->email); ?>
								</div>
							</div>
							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('phonenumber', 'clients_phone', $renter_profile->phonenumber); ?>
								</div>
								<?php 
								$birthday = ($renter_profile->birthday != '' && $renter_profile->birthday != null) ? _d($renter_profile->birthday) : '';
								 ?>
								<div class="col-md-6">
									<?php echo render_date_input('birthday', 'clients_date_of_birth', $birthday); ?>
								</div>
							</div>
							
						</div>
					</div>
				</div>

				<div class="card mbot20">
					<div class="card-header" id="heading2">
						<h4 data-toggle="collapse" data-target="#collapse2" aria-expanded="true" aria-controls="collapse2" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_about_me'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse2" class="collapse " aria-labelledby="heading2" data-parent="#accordionExample">
						<div class="card-body">
							<?php $introduce_yourself = (isset($renter_profile) ? $renter_profile->introduce_yourself : '');
							$rows=[];
							$rows['rows'] = 6;
							echo render_textarea('introduce_yourself', 'real_introduce_yourself', $introduce_yourself, $rows)?>
						</div>
						<div class="row">
							<div class="col-md-12">
								<h5 class="tw-font-semibold"><?php echo _l('real_optional_supporting_documents'); ?></h5>
								<p>
									<?php echo _l('real_optional_supporting_detail'); ?>
								</p>
								<?php echo render_input('introduce_yourself_file', '', '', 'file') ?>
								<div class="row">
									<div id="contract_attachments" class="col-md-12">
										<?php if(isset($supporting_documents)){ ?>

											<?php
											$data = '<div class="row" id="supporting_attachment_file">';
											foreach($supporting_documents as $attachment) {

												$data .= '<div class="col-md-6 pdf_attachment">';
												$href_url = site_url('modules/realestate/uploads/supporting_documents/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
												if(!empty($attachment['external'])){
													$href_url = $attachment['external_link'];
												}
												$data .= '<div class="col-md-9">';

												$data .= '<div>';
												$data .= '<div class="pull-left"><i class="'.get_mime_class($attachment['filetype']).'"></i></div>';
												$data .= '<a href="'.$href_url.'>'.$attachment['file_name'].'</a>';
												$data .= '</div>';
												$data .= '</div>';
												$data .= '<div class="col-md-3 text-right">';
												$data .= '<a class="btn btn-sm" name="preview-btn" onclick="preview_file(this); return false;" rel_id = "'.$attachment['rel_id'].'" id = "'.$attachment['id'].'" data-toggle="tooltip" title data-original-title="'._l("preview_file").'"><i class="fa fa-eye"></i></a>';
												if(is_client_logged_in()){
													$data .= '<a href="#" class="text-danger btn btn-sm" onclick="delete_renter_attachment_pdf_file(this,'.$attachment['id'].', \'SUPPORTING_DOCUMENT_UPLOAD\'); return false;"><i class="fa fa fa-times"></i></a>';
												}
												$data .= '</div>';
												$data .= '<div class="clearfix"></div><hr class="mtop1 mbot5">';
												$data .= '</div>';
											}

											$data .= '</div>';
											echo new_html_entity_decode($data);
											?>
										<?php } ?>
										<!-- check if edit contract => display attachment file end-->

									</div>

								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="card mbot20">
					<div class="card-header" id="heading3">
						<h4 data-toggle="collapse" data-target="#collapse3" aria-expanded="true" aria-controls="collapse3" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_address_history'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse3" class="collapse " aria-labelledby="heading3" data-parent="#accordionExample">
						<div class="card-body">
							<div class="row">
								<div class="col-md-12">
									<h5 class="tw-font-semibold"><?php echo _l('real_address_history_description'); ?></h5>
									<p>
										<?php echo _l('real_address_history_long_description'); ?>
									</p>
								</div>
								<div class="col-md-12">
									<?php 
									render_datatable(
										array(
											_l('id'),
											_l('address'),
											_l('options'),
										),'address_history_table'
									);
									?>
								</div>
								<div class="col-md-12">
									<button type="button"  onclick="new_address_history(); return false;" class="btn btn-secondary btn-block"><?php echo _l('real_add_previous_address'); ?></button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="card mbot20">
					<div class="card-header" id="heading4">
						<h4 data-toggle="collapse" data-target="#collapse4" aria-expanded="true" aria-controls="collapse4" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_employment'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse4" class="collapse " aria-labelledby="heading4" data-parent="#accordionExample">
						<div class="card-body">
							<?php 
							$employment_types = [
								[
									'name' => 'employed',
									'label' => _l('real_employed'),
								],
								[
									'name' => 'self_employed',
									'label' => _l('real_self_employed'),
								],
							];

							$not_employed_check = '';
							if($renter_profile->not_employed == 1){
								$not_employed_check = 'checked';
							}
							?>
							<div class="form-group">
								<div class="checkbox checkbox-primary">
									<input type="checkbox" id="not_employed" name="not_employed" value="1" <?php echo html_entity_decode($not_employed_check); ?>>
									<label for="not_employed"><?php echo _l('real_currently_not_employed'); ?></label>
								</div>
							</div>

							<?php echo render_select('employment_type', $employment_types, ['name', 'label'], 'real_employment_type', $renter_profile->employment_type); ?>
						</div>
					</div>
				</div>
				<div class="card mbot20">
					<div class="card-header" id="heading5">
						<h4 data-toggle="collapse" data-target="#collapse5" aria-expanded="true" aria-controls="collapse5" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_income'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse5" class="collapse " aria-labelledby="heading5" data-parent="#accordionExample">
						<div class="card-body">
							<div class="row">
								<div class="col-md-12">
									<h5 class="tw-font-semibold"><?php echo _l('real_income_source_description'); ?></h5>
								</div>
								<div class="col-md-12">
									<?php 
									render_datatable(
										array(
											_l('id'),
											_l('real_income_type'),
											_l('options'),
										),'income_source_table'
									);
									?>
								</div>
								<div class="col-md-12">
									<button type="button"  onclick="new_income_source(); return false;" class="btn btn-secondary btn-block"><?php echo _l('real_add_additional_income'); ?></button>
								</div>
							</div>

							<div class="row">
								<div class="col-md-12">
									<h5 class="tw-font-semibold"><?php echo _l('real_recent_proof_of_income'); ?></h5>
									<p>
										<?php echo _l('real_recent_proof_of_income_description'); ?>
									</p>
									<?php echo render_input('income_source_file', '', '', 'file') ?>
									<div class="row">
										<div id="contract_attachments" class="col-md-12">
											<?php if(isset($proof_incomes)){ ?>

												<?php
												$data = '<div class="row" id="proof_income_attachment_file">';
												foreach($proof_incomes as $attachment) {

													$data .= '<div class="col-md-6 pdf_attachment">';
													$href_url = site_url('modules/realestate/uploads/proof_incomes/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
													if(!empty($attachment['external'])){
														$href_url = $attachment['external_link'];
													}
													$data .= '<div class="col-md-9">';

													$data .= '<div>';
													$data .= '<div class="pull-left"><i class="'.get_mime_class($attachment['filetype']).'"></i></div>';
													$data .= '<a href="'.$href_url.'>'.$attachment['file_name'].'</a>';
													$data .= '</div>';
													$data .= '</div>';
													$data .= '<div class="col-md-3 text-right">';
													$data .= '<a class="btn btn-sm" name="preview-btn" onclick="preview_file(this); return false;" rel_id = "'.$attachment['rel_id'].'" id = "'.$attachment['id'].'" data-toggle="tooltip" title data-original-title="'._l("preview_file").'"><i class="fa fa-eye"></i></a>';
													if(is_client_logged_in()){
														$data .= '<a href="#" class="text-danger btn btn-sm" onclick="delete_renter_attachment_pdf_file(this,'.$attachment['id'].', \'PROOF_INCOME_UPLOAD\'); return false;"><i class="fa fa fa-times"></i></a>';
													}
													$data .= '</div>';
													$data .= '<div class="clearfix"></div><hr class="mtop1 mbot5">';
													$data .= '</div>';
												}
												$data .= '</div>';
												echo new_html_entity_decode($data);
												?>
											<?php } ?>
											<!-- check if edit contract => display attachment file end-->

										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="card mbot20">
					<div class="card-header" id="heading7">
						<h4 data-toggle="collapse" data-target="#collapse7" aria-expanded="true" aria-controls="collapse7" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_identity_documents'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse7" class="collapse " aria-labelledby="heading7" data-parent="#accordionExample">
						<div class="card-body">
							<div class="row">
								<div class="col-md-12">
									<h5 class="tw-font-semibold"><?php echo _l('real_add_your_ID'); ?></h5>
									<p>
										<?php echo _l('real_add_your_ID_description'); ?>
									</p>
									<?php echo render_input('identity_document_file', '', '', 'file') ?>
									<div class="row">
										<div id="contract_attachments" class="col-md-12">
											<?php if(isset($identity_documents)){ ?>

												<?php
												$data = '<div class="row" id="identity_document_attachment_file">';
												foreach($identity_documents as $attachment) {

													$data .= '<div class="col-md-6 pdf_attachment">';
													$href_url = site_url('modules/realestate/uploads/identity_documents/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
													if(!empty($attachment['external'])){
														$href_url = $attachment['external_link'];
													}
													$data .= '<div class="col-md-9">';

													$data .= '<div>';
													$data .= '<div class="pull-left"><i class="'.get_mime_class($attachment['filetype']).'"></i></div>';
													$data .= '<a href="'.$href_url.'>'.$attachment['file_name'].'</a>';
													$data .= '</div>';
													$data .= '</div>';
													$data .= '<div class="col-md-3 text-right">';
													$data .= '<a class="btn btn-sm" name="preview-btn" onclick="preview_file(this); return false;" rel_id = "'.$attachment['rel_id'].'" id = "'.$attachment['id'].'" data-toggle="tooltip" title data-original-title="'._l("preview_file").'"><i class="fa fa-eye"></i></a>';
													if(is_client_logged_in()){
														$data .= '<a href="#" class="text-danger btn btn-sm" onclick="delete_renter_attachment_pdf_file(this,'.$attachment['id'].', \'IDENTIFY_DOCUMENT_UPLOAD\'); return false;"><i class="fa fa fa-times"></i></a>';
													}
													$data .= '</div>';
													$data .= '<div class="clearfix"></div><hr class="mtop1 mbot5">';
													$data .= '</div>';
												}

												$data .= '</div>';
												echo new_html_entity_decode($data);
												?>
											<?php } ?>
											<!-- check if edit contract => display attachment file end-->

										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="card mbot20">
					<div class="card-header" id="heading8">
						<h4 data-toggle="collapse" data-target="#collapse8" aria-expanded="true" aria-controls="collapse8" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_emergency_contact'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse8" class="collapse " aria-labelledby="heading8" data-parent="#accordionExample">
						<div class="card-body">
							<div class="row">
								<div class="col-md-12">
									<h5 class="tw-font-semibold"><?php echo _l('real_add_your_ID'); ?></h5>
									<p>
										<?php echo _l('real_emergency_contact_description'); ?>
									</p>
									<p>
										<?php echo _l('real_emergency_contact_long_description'); ?>
									</p>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('emergency_contact_name', 'real_emergency_contact', $renter_profile->emergency_contact_name); ?>
								</div>
								<div class="col-md-6">
									<?php 
									$contact_relationships = contact_relationships();
									
									?>
									<?php echo render_select('emergency_contact_relationship', $contact_relationships, ['name', 'label'], 'real_emergency_contact_relationship', $renter_profile->emergency_contact_relationship); ?>
								</div>
								
							</div>
							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('emergency_contact_email', 'clients_email', $renter_profile->emergency_contact_email); ?>
								</div>
								<div class="col-md-6">
									<?php echo render_input('emergency_contact_phonenumber', 'clients_phone', $renter_profile->emergency_contact_phonenumber); ?>
								</div>
							</div>

							
						</div>
					</div>
				</div>
				

			</div>
		</div>
	</div>

	<h2 class="tw-mt-0 tw-font-semibold tw-text-neutral-700 section-heading section-heading-invoices">
		<?php echo _l('real_household'); ?>
	</h2>
	<p><?php echo _l('real_household_description'); ?></p>

	<div class="panel_s">
		<div class="panel-body">
			<div class="accordion" id="accordionExample1">
				<div class="card mbot20">
					<div class="card-header" id="heading9">
						<h4 data-toggle="collapse" data-target="#collapse9" aria-expanded="true" aria-controls="collapse9" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_people'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse9" class="collapse " aria-labelledby="heading9" data-parent="#accordionExample1">
						<div class="card-body">
							<div class="row">
								<div class="col-md-12">
									<h5 class="tw-font-semibold"><?php echo _l('real_live_with_other_occupants'); ?></h5>
								</div>
								<?php 
								$live_with_other_yes_checked = '';
								$live_with_other_no_checked = 'checked';
								if($renter_profile->live_with_other_occupants == 0){
									$live_with_other_no_checked = 'checked';
								}
								if($renter_profile->live_with_other_occupants == 1){
									$live_with_other_yes_checked = 'checked';
									$live_with_other_no_checked = '';
								}
								
								?>
								<div class="col-md-12">
									<div class="form-group ">
										<div class="radio radio-primary radio-inline" >
											<input type="radio" id="live_with_other_yes" name="live_with_other_occupants" value="1" <?php echo html_entity_decode($live_with_other_yes_checked); ?>>
											<label for="live_with_other_yes"><?php echo _l('real_yes'); ?></label>
										</div>

										<div class="radio radio-primary radio-inline" >
											<input type="radio" id="live_with_other_no" name="live_with_other_occupants" value="0" <?php echo html_entity_decode($live_with_other_no_checked); ?>>
											<label for="live_with_other_no"><?php echo _l('real_no'); ?></label>
										</div>
									</div>
								</div>

								<div class="col-md-12">
									<?php 
									render_datatable(
										array(
											_l('id'),
											_l('real_occupants_name'),
											_l('options'),
										),'person_table'
									);
									?>
								</div>

								<div class="col-md-12">
									<p>
										<?php echo _l('real_live_with_other_occupants_description'); ?>
									</p>
									<button type="button"  onclick="new_person(); return false;" class="btn btn-secondary btn-block"><?php echo _l('real_add_occupant'); ?></button>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="card mbot20">
					<div class="card-header" id="heading10">
						<h4 data-toggle="collapse" data-target="#collapse10" aria-expanded="true" aria-controls="collapse10" class="cursor_pointer tw-mt-0 tw-mb-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
							<?php echo _l('real_pets'); ?><span class="caret pull-right"></span>
						</h4>
					</div>

					<div id="collapse10" class="collapse " aria-labelledby="heading10" data-parent="#accordionExample1">
						<div class="card-body">

							<div class="row">
								<div class="col-md-12">
									<h5 class="tw-font-semibold"><?php echo _l('real_pet_description'); ?></h5>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('dogs', 'real_Dogs', $renter_profile->dogs, 'number'); ?>
								</div>
								<div class="col-md-6">
									<?php echo render_input('cats', 'real_Cats', $renter_profile->cats, 'number'); ?>
								</div>
							</div>
							<?php echo render_input('other_pets', 'real_Other_pets', $renter_profile->other_pets, 'number'); ?>

							<?php $describe_your_pets = (isset($renter_profile) ? $renter_profile->describe_your_pets : '');
							$rows=[];
							$rows['rows'] = 7;
							echo render_textarea('describe_your_pets', 'real_describe_your_pets_in_more_detail', $describe_your_pets, $rows)?>
						</div>
					</div>
				</div>
				
			</div>
		</div>
	</div>

	<div class="row contact-profile-save-section">
		<div class="col-md-12 text-right mtop20">
			<div class="form-group">
				<button type="submit" class="btn btn-info contact-profile-save"><?php echo _l('clients_edit_profile_update_btn'); ?></button>
			</div>
		</div>
	</div>

	<?php echo form_close(); ?>


</div>
<div id="rental_file_data"></div>

<?php $this->load->view('clients/renter_profiles/address_history_modal'); ?>
<?php $this->load->view('clients/renter_profiles/income_source_modal'); ?>
<?php $this->load->view('clients/renter_profiles/person_modal'); ?>

<?php real_client_init_tail(); ?>

<?php require('modules/realestate/assets/js/clients/renter_profiles/renter_profile_js.php'); ?>

