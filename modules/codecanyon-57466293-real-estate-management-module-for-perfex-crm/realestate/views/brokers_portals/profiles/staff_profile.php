<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo broker_init_head(); ?>
<div id="wrapper" >
	<div class="content">

		<div class="row section-heading section-profile">
			<div class="col-md-8">
				<?php echo form_open_multipart('realestate/broker/profile',array('autocomplete'=>'off', 'id' =>'edit_profile')); ?>
				<?php echo form_hidden('profile',true); ?>
				<div class="panel_s">
					<div class="panel-body">
						<h4 class="no-margin section-text"><?php echo html_entity_decode($broker->code.' '.$broker->firstname.' '.$broker->lastname); ?></h4>
					</div>
				</div>
				<?php hooks()->do_action('before_client_profile_form_loaded'); ?>
				<div class="panel_s">
					<div class="panel-body">
						<div class="row">
							<div class="col-md-12">

								<h4 class="text-danger"><?php echo _l('broker_register_primary_contact_info'); ?></h4>
								<div class="form-group">
									<?php if($broker->profile_image == NULL){ ?>
										<div class="form-group profile-image-upload-group">
											<label for="profile_image" class="profile-image"><?php echo _l('client_profile_image'); ?></label>
											<input type="file" name="profile_image" class="form-control" id="profile_image">
										</div>
									<?php } ?>
									<?php if($broker->profile_image != NULL){ ?>
										<div class="form-group profile-image-group">
											<div class="row">
												<div class="col-md-9">
													<?php echo broker_profile_image(get_broker_id(),[
														'client-profile-image-thumb',
													], 'small', ['data-toggle' => 'tooltip', 'data-title' => get_broker_name(get_broker_id()), 'data-placement' => 'bottom' ]); ?>

												</div>
												<div class="col-md-3 text-right">
													<a href="<?php echo site_url('realestate/broker/remove_profile_image/'.$broker->id); ?>"><i class="fa fa-remove text-danger"></i></a>
												</div>
											</div>
										</div>
									<?php } ?>

								</div>
								<?php echo render_input('code', 'real_code_label', $broker->code, '', ['disabled' => true]); ?>

								<div class="form-group profile-firstname-group">
									<label for="firstname"><?php echo _l('clients_firstname'); ?></label>
									<input type="text" class="form-control" name="firstname" id="firstname" value="<?php echo set_value('firstname',$broker->firstname); ?>">
									<?php echo form_error('firstname'); ?>
								</div>
								
								<?php echo render_input('lastname', 'clients_lastname', $broker->lastname); ?>
								
								<div class="row">
									<div class="col-md-6">
										
										<div class="form-group profile-email-group">
											<label for="email"><?php echo _l('clients_email'); ?></label>
											<input type="email" name="email" class="form-control" id="email" value="<?php echo new_html_entity_decode($broker->email); ?>" disabled>
											<?php echo form_error('email'); ?>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group profile-phone-group">
											<label for="phonenumber"><?php echo _l('clients_phone'); ?></label>
											<input type="text" class="form-control" name="phonenumber" id="phonenumber" value="<?php echo new_html_entity_decode($broker->phonenumber); ?>">
										</div>
									</div>
									
								</div>

								<div class="accordion" id="accordionExample">
									<div class="card mbot20">
										<div class="card-header" id="headingOne">
											<h4 data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne" class="cursor_pointer text-danger">
												<?php echo _l('real_summary'); ?><span class="caret pull-right"></span>
											</h4>
										</div>

										<div id="collapseOne" class="collapse " aria-labelledby="headingOne" data-parent="#accordionExample">
											<div class="card-body">
												<?php $introduce_yourself = (isset($broker) ? $broker->introduce_yourself : '');
												$rows=[];
												$rows['rows'] = 6;
												echo render_textarea('introduce_yourself', 'real_introduce_yourself', $introduce_yourself, $rows)?>
											</div>
										</div>
									</div>
									

									<div class="card mbot20">
										<div class="card-header" id="headingTwo">
											<h4 data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" class="cursor_pointer text-danger">
												<?php echo _l('real_other_information'); ?><span class="caret pull-right"></span>
											</h4>
										</div>
										<div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
											<div class="card-body">
												<div class="row">
													<div class="col-md-12">
														<?php $skype = (isset($broker) ? $broker->skype : '');
														echo render_input('skype', 'staff_add_edit_skype', $skype);?>
													</div>
													<div class="col-md-12">
														<?php $facebook = (isset($broker) ? $broker->facebook : '');
														echo render_input('facebook', 'real_facebook_url', $facebook);?>
													</div>

													<div class="col-md-12">
														<?php $linkedin = (isset($broker) ? $broker->linkedin : '');
														echo render_input('linkedin', 'real_instagram_url', $linkedin);?>
													</div>
													
												</div>
											</div>
										</div>
									</div>

								</div>


							</div>
							<div class="row p15 contact-profile-save-section">
								<div class="col-md-12 text-right mtop20">
									<div class="form-group">
										<button type="submit" class="btn btn-info contact-profile-save"><?php echo _l('clients_edit_profile_update_btn'); ?></button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php echo form_close(); ?>
			</div>
			<div class="col-md-4 contact-profile-change-password-section">
				<div class="panel_s section-heading section-change-password">
					<div class="panel-body">
						<h4 class="no-margin section-text"><?php echo _l('clients_edit_profile_change_password_heading'); ?></h4>
					</div>
				</div>
				<div class="panel_s">
					<div class="panel-body">
						<?php echo form_open('realestate/broker/profile'); ?>
						<?php echo form_hidden('change_password',true); ?>
						<div class="form-group">
							<label for="oldpassword"><?php echo _l('clients_edit_profile_old_password'); ?></label>
							<input type="password" class="form-control" name="oldpassword" id="oldpassword">
							<?php echo form_error('oldpassword'); ?>
						</div>
						<div class="form-group">
							<label for="newpassword"><?php echo _l('clients_edit_profile_new_password'); ?></label>
							<input type="password" class="form-control" name="newpassword" id="newpassword">
							<?php echo form_error('newpassword'); ?>
						</div>
						<div class="form-group">
							<label for="newpasswordr"><?php echo _l('clients_edit_profile_new_password_repeat'); ?></label>
							<input type="password" class="form-control" name="newpasswordr" id="newpasswordr">
							<?php echo form_error('newpasswordr'); ?>
						</div>
						<div class="form-group">
							<button type="submit" class="btn btn-info btn-block"><?php echo _l('clients_edit_profile_change_password_btn'); ?></button>
						</div>
						<?php echo form_close(); ?>
					</div>
					<?php if($broker->last_password_change !== NULL){ ?>
						<div class="panel-footer last-password-change">
							<?php echo _l('clients_profile_last_changed_password',time_ago($broker->last_password_change)); ?>
						</div>
					<?php } ?>
				</div>
				<?php hooks()->do_action('after_broker_profile_password_form_loaded'); ?>
			</div>

		</div>
	</div>
</div>
<?php broker_init_tail(); ?>

<?php require 'modules/realestate/assets/js/brokers/profiles/broker_profile_js.php';?>
