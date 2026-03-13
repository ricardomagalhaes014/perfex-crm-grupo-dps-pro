<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
init_head();
?>

<div id="wrapper">
	<div class="content">

		<div class="row row tw-mt-2 sm:tw-mt-4">
			<div class="col-md-12" id="small-table">
				<div class="panel_s">
					<div class="panel-body">
						<div class="row">
							<div class="col-md-3 property_container">
								<h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-invoices">
									<?php echo _l('real_property_rental_request'); ?>
								</h4>
								<?php if(isset($property)){ ?>
									<?php $this->load->view('companies/property_listings/utilities/room_item', ['properties' => $property, 'property_col' => '']) ?>
								<?php } ?>
							</div>
							<?php 
							$inspect_label = '';
							$term_month_label = isset($rental_type) ? $rental_type : _l('real_months');
							if($property_request->term_month > 1){
								$term_month_label = $term_month_label.'s';
							}
							if($property_request->inspect_property == 0){
								$inspect_label = _l('rel_no');

							}else{
								$inspect_label = _l('rel_yes');
							}

							?>
							<div class="col-md-9">
								<h4 class="tw-font-semibold tw-text-neutral-700 section-heading section-heading-invoices">
									<?php echo _l('real_tenant_information_submitted'); ?>
								</h4>
								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_lease_details'); ?></div>
									</div>
									<div class="col-md-5">
										<div class="mbot5"><?php  echo _l('real_inspected_question'); ?></div>
										<div class="mbot5"><?php  echo _l('real_preferred_lease_start_date'); ?></div>
										<div class="mbot5"><?php  echo _l('real_initial_lease_term'); ?></div>
									</div>
									<div class="col-md-3 text-right">
										<div class="tw-font-semibold mbot5"><?php  echo html_entity_decode($inspect_label); ?></div>
										<div class="tw-font-semibold mbot5"><?php  echo _d($property_request->date); ?></div>
										<div class="tw-font-semibold mbot5"><?php  echo html_entity_decode($property_request->term_month).' '.$term_month_label; ?></div>
									</div>
								</div>
								<hr class="hr-10">
								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_personal_details'); ?></div>
									</div>
									<div class="col-md-5">
										<div class="mbot5"><?php  echo new_html_entity_decode($renter_profile->firstname ?? '') .' '. new_html_entity_decode($renter_profile->lastname ?? ''); ?></div>
										<div class="mbot5"><?php  echo _l('clients_date_of_birth').' : '. new_html_entity_decode($renter_profile->birthday ?? ''); ?></div>
										<div class="mbot5"><?php  echo new_html_entity_decode($renter_profile->phonenumber ?? ''); ?></div>
										<div class="mbot5"><?php  echo new_html_entity_decode($renter_profile->email ?? ''); ?></div>
									</div>
									<div class="col-md-3 text-right">
									</div>
								</div>
								<hr class="hr-10">
								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_address_history'); ?></div>
									</div>
									<div class="col-md-5">
										<?php foreach ($address_histores as $key => $address_history) { ?>
											<div class="mbot5">
												<span class="tw-font-semibold"><?php echo html_entity_decode($address_history['address']); ?></span><br>
												<?php echo html_entity_decode($address_history['move_in'].' - '.$address_history['move_out']); ?>
											</div>
										<?php } ?>
									</div>
									<div class="col-md-3 text-right">
									</div>
								</div>
								<hr class="hr-10">

								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_employment'); ?></div>
									</div>
									<div class="col-md-5">
										<?php if(isset($renter_profile->not_employed) && $renter_profile->not_employed == 1){ ?>
											<div class="mbot5">
												<?php echo _l('real_currently_not_employed'); ?>
											</div>
										<?php }else{ ?>
											<div class="mbot5">
												<?php echo html_entity_decode($renter_profile->employment_type ?? ''); ?>
											</div>
										<?php } ?>
									</div>
									<div class="col-md-3 text-right">
									</div>
								</div>
								<hr class="hr-10">
								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_income'); ?></div>
									</div>
									<div class="col-md-5">
										<?php foreach ($income_sources as $key => $income_source) { ?>
											<div class="mbot5">

												<span class="tw-font-semibold"><?php echo _l('real_'.$income_source['income_type']); ?></span><br>
												<?php echo html_entity_decode(app_format_money($income_source['amount'],$base_currency_id).'  '._l('real_per').' '.$income_source['income_frequency'].' . '._l('real_after_tax')); ?>
											</div>
										<?php } ?>

										<div id="contract_attachments" class="">
											<?php if(isset($proof_incomes)){ ?>

												<?php
												$data = '<div class="row" id="proof_income_attachment_file">';
												foreach($proof_incomes as $attachment) {

													$data .= '<div class="col-md-12 pdf_attachment">';
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
									<div class="col-md-3 text-right">
									</div>
								</div>
								<hr class="hr-10">

								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_identity_documents'); ?></div>
									</div>
									<div class="col-md-5">
										<div id="contract_attachments" class="">
											<?php if(isset($identity_documents)){ ?>

												<?php
												$data = '<div class="row" id="identity_document_attachment_file">';
												foreach($identity_documents as $attachment) {

													$data .= '<div class="col-md-12 pdf_attachment">';
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
									<div class="col-md-3 text-right">
									</div>
								</div>
								<hr class="hr-10">

								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_emergency_contact'); ?></div>
									</div>
									<div class="col-md-5">
										<div class="mbot5"><?php  echo new_html_entity_decode($renter_profile->emergency_contact_name ?? ''); ?></div>
										<div class="mbot5"><?php  echo new_html_entity_decode($renter_profile->emergency_contact_relationship ?? ''); ?></div>
										<div class="mbot5"><?php  echo new_html_entity_decode($renter_profile->emergency_contact_email ?? ''); ?></div>
										<div class="mbot5"><?php  echo new_html_entity_decode($renter_profile->emergency_contact_phonenumber ?? ''); ?></div>
									</div>
									<div class="col-md-3 text-right">
									</div>
								</div>
								<hr class="hr-10">
								<?php if(isset($renter_profile->live_with_other_occupants) && $renter_profile->live_with_other_occupants == 1){ ?>
									<div class="row">
										<div class="col-md-4">
											<div class="mbot5"><?php  echo _l('real_people'); ?></div>
										</div>
										<div class="col-md-5">
											<?php foreach ($persons as $key => $person) { ?>
												<div class="mbot5">
													<span class="tw-font-semibold"><?php echo html_entity_decode($person['occupants_name']); ?></span><br>
													<?php echo html_entity_decode($person['occupants_age'].' - '._l('real_years_of_age')); ?>
												</div>
											<?php } ?>
										</div>
										<div class="col-md-3 text-right">
										</div>
									</div>
									<hr class="hr-10">
								<?php } ?>

								<div class="row">
									<div class="col-md-4">
										<div class="mbot5"><?php  echo _l('real_pets'); ?></div>
									</div>
									<div class="col-md-5">
										<?php if(isset($renter_profile->dogs) && $renter_profile->dogs > 0){ ?>
											<div class="mbot5">
												<span class="tw-font-semibold"><?php echo _l('real_Dogs'); ?> : </span><?php echo html_entity_decode($renter_profile->dogs); ?>
											</div>
										<?php } ?>
										<?php if(isset($renter_profile->cats) && $renter_profile->cats > 0){ ?>
											<div class="mbot5">
												<span class="tw-font-semibold"><?php echo _l('real_Cats'); ?> : </span><?php echo html_entity_decode($renter_profile->cats); ?>
											</div>
										<?php } ?>
										<?php if(isset($renter_profile->other_pets) && $renter_profile->other_pets > 0){ ?>
											<div class="mbot5">
												<span class="tw-font-semibold"><?php echo _l('real_Other_pets'); ?> : </span><?php echo html_entity_decode($renter_profile->other_pets); ?>
											</div>
										<?php } ?>
										<?php if(isset($renter_profile->describe_your_pets) && new_strlen($renter_profile->describe_your_pets > 0)){ ?>
											<div class="mbot5">
												<span class="tw-font-semibold"><?php echo _l('real_describe_your_pets_in_more_detail'); ?></span><br><?php echo html_entity_decode($renter_profile->describe_your_pets); ?>
											</div>
										<?php } ?>


									</div>
									<div class="col-md-3 text-right">
									</div>
								</div>
								<hr class="hr-10">
							</div>

							<div class="row contact-profile-save-section">
								<div class="col-md-12 text-right mtop20">
									<div class="form-group">
										<a class="btn btn-default" href="<?php echo html_entity_decode($site_url) . 'rent_requests#'.$request_id ?>"><?php echo _l('close'); ?></a>
									</div>
								</div>
							</div>


						</div>
						<div id="rental_file_data"></div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php 
init_tail();
?>
	<?php require 'modules/realestate/assets/js/companies/tenants/tenant_detail_js.php';?>

</body>
</html>

