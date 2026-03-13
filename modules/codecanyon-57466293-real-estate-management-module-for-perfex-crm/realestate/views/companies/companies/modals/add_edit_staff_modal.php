<div class="modal fade" id="appointmentModal">
	<div class="modal-dialog modal-lg modal-width">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<div class="member">

					<?php 
					$title = '';
					$staffid = '';
					if(isset($member)){
						$title .= _l('real_update_staff');
						$staffid    = $member->staffid;

						echo form_hidden('staffid',$staffid);
						echo form_hidden('isedit');

					}else{
						echo form_hidden('staffid',$staffid);
						$title .= _l('real_add_new_staff');

					}
					?>
					<?php echo form_hidden('memberid', $staffid); ?>
				</div>
				
				<h4 class="modal-title" id="myModalLabel"><?php echo html_entity_decode($title); ?><br /><small id=""><?php echo rel_get_construction_company_name($company_id,true); ?></small></h4>

			</div>

			<?php echo form_open_multipart(admin_url('realestate/add_edit_construction_staff/'.$staffid), array('id' => 'staff-form', 'class' => 'staff-form', 'autocomplete'=>'off')); ?>
			<div class="modal-body">

				<div class="horizontal-scrollable-tabs">
					<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
					<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
					<div class="horizontal-tabs">
						<ul class="nav nav-tabs profile-tabs row customer-profile-tabs nav-tabs-horizontal" role="tablist">
							<li role="presentation" class="active">
								<a href="#tab_staff_profile" aria-controls="tab_staff_profile" role="tab" data-toggle="tab">
									<span class="text-danger">(*)</span><?php echo _l('real_real_estate_agent_user_profile'); ?>
								</a>
							</li>
							<li role="presentation"  class="<?php echo (is_admin()) ?  '' : ' hide'; ?>">
								<a href="#staff_permissions" aria-controls="staff_permissions" role="tab" data-toggle="tab">
									<?php echo _l('real_user_permissions'); ?>
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
										href="<?php echo admin_url('staff/remove_staff_profile_image/' . $member->staffid); ?>"><i
										class="fa fa-remove"></i></a>
									</div>
								</div>
							</div>
						<?php } ?>
						<?php $value = (isset($member) ? $member->firstname : ''); ?>
						<?php $staff_identifi = (isset($member) ? $member->staff_identifi : $staff_identifi_code); ?>
						<?php $attrs = (isset($member) ? [] : ['autofocus' => true]); ?>

						<?php echo render_input('staff_identifi','real_code_label',$staff_identifi,'text',['readonly' =>true]); ?>
						<?php echo render_input('firstname', 'staff_add_edit_firstname', $value, 'text', $attrs); ?>
						<?php $value = (isset($member) ? $member->lastname : ''); ?>
						<?php echo render_input('lastname', 'staff_add_edit_lastname', $value, 'text', $attrs); ?>
						<?php $value = (isset($member) ? $member->email : ''); ?>
						<?php echo render_input('email', 'staff_add_edit_email', $value, 'email', ['autocomplete' => 'off']); ?>
						<div class="hide">
							<?php if(!is_admin()){ ?>
								<?php
								hooks()->do_action('staff_render_permissions');
								if(isset($company_role_id) && $company_role_id != 0){
									$selected = $company_role_id;
								}else{
									$selected = isset($member) ? $member->role : '';
								}
								$selected = '';
								foreach ($roles as $role) {
									if (isset($member)) {
										if ($member->role == $role['roleid']) {
											$selected = $role['roleid'];
										}
									}
								}
								?>
								<?php echo render_select('role', $roles, ['roleid', 'name'], 'staff_add_edit_role', $selected); ?>
							<?php } ?>
						</div>

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
								<div role="tabpanel" class="tab-pane <?php echo (is_admin() ) ? '' : ' hide'; ?>" id="staff_permissions">
									<?php if(is_admin()){ ?>
										<?php
										hooks()->do_action('staff_render_permissions');

										if(isset($company_role_id) && $company_role_id != 0){
											$selected = $company_role_id;
										}else{
											$selected = isset($member) ? $member->role : '';
										}
										foreach ($roles as $role) {
											if (isset($member)) {
												if ($member->role == $role['roleid']) {
													$selected = $role['roleid'];
												}
											} 
										}
										?>
										<?php echo render_select('role', $roles, ['roleid', 'name'], 'staff_add_edit_role', $selected); ?>
									<?php } ?>

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
				require 'modules/realestate/assets/js/companies/companies/add_edit_staff_js.php';
			?>