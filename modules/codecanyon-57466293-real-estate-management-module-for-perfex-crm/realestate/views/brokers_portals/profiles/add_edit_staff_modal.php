0<div class="modal fade" id="appointmentModal">
	<div class="modal-dialog modal-lg modal-width">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<div class="member">

					<?php 
					$title = '';
					$id = '';
					if(isset($member)){
						$title .= _l('real_update_staff');
						$id    = $member->id;

						echo form_hidden('id',$id);
						echo form_hidden('isedit');

					}else{
						echo form_hidden('id',$id);
						$title .= _l('real_add_new_staff');

					}
					?>
					<?php echo form_hidden('memberid', $id); ?>
				</div>
				
				<h4 class="modal-title" id="myModalLabel"><?php echo html_entity_decode($title); ?><br /><small id=""><?php echo rel_get_construction_company_name($company_id,true); ?></small></h4>

			</div>

			<?php echo form_open_multipart(admin_url('realestate/add_edit_broker_staff/'.$id), array('id' => 'staff-form', 'class' => 'staff-form', 'autocomplete'=>'off')); ?>
			<div class="modal-body">

				<div class="horizontal-scrollable-tabs">
					<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
					<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
					<div class="horizontal-tabs">
						<ul class="nav nav-tabs profile-tabs row customer-profile-tabs nav-tabs-horizontal" role="tablist">
							<li role="presentation" class="active">
								<a href="#tab_staff_profile" aria-controls="tab_staff_profile" role="tab" data-toggle="tab">
									<span class="text-danger">(*)</span><?php echo _l('real_business_broker_staff_profile'); ?>
								</a>
							</li>
							

						</ul>
					</div>
				</div>

				<div class="tab-content">
					<div class="manage_staff hide">
						<?php 
						echo form_hidden('company_id', $company_id); 
						?>
					</div>

					<div role="tabpanel" class="tab-pane active" id="tab_staff_profile">
						
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
										<?php echo broker_profile_image($member->id, ['img', 'img-responsive', 'broker-profile-image-thumb'], 'thumb'); ?>
									</div>
									<div class="col-md-3 text-right">
										<a
										href="<?php echo admin_url('realestate/remove_broker_profile_image/' . $member->id .'/'.$member->company_id); ?>"><i
										class="fa fa-remove"></i></a>
									</div>
								</div>
							</div>
						<?php } ?>
						<?php $value = (isset($member) ? $member->firstname : ''); ?>
						<?php if(isset($member) && $member->company_id > 0 && !is_admin()){ ?>
							<?php $attrs = ['autofocus' => true, 'disabled' => true]; ?>
						<?php }else{ ?>
							<?php $attrs = (isset($member) ? [] : ['autofocus' => true]); ?>
						<?php } ?>

						<?php echo render_input('firstname', 'staff_add_edit_firstname', $value, 'text', $attrs); ?>
						<?php $value = (isset($member) ? $member->lastname : ''); ?>
						<?php echo render_input('lastname', 'staff_add_edit_lastname', $value, 'text', $attrs); ?>
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
									
									<div class="row">
										<div class="col-md-12">
											<hr class="hr-10" />
											
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
									<?php if (!isset($member) || is_admin() || !is_admin() ) { ?>
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

							</div>

						</div><!-- /.modal-content -->
						<div class="modal-footer">
							<button type="button" class="btn btn-default close_btn" data-dismiss="modal"><?php echo _l('close'); ?></button>
							<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
						</div>
						<?php echo form_close(); ?>

					</div>
				</div>
				<?php 
				require 'modules/realestate/assets/js/companies/business_brokers/add_edit_staff_js.php';
			?>