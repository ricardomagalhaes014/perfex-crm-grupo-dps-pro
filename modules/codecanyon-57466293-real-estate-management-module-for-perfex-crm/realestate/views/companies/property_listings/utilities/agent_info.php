<?php 
$CI            = & get_instance();
$viewuri = $_SERVER['REQUEST_URI'];
$apply_button = false;
if (!(strpos($viewuri, '/realestate/client') === false)){
	$apply_button = true;
}

foreach ($request_brokers as $request_key => $request_broker) {

	$agent_name = '';
	$agent_address = '';
	$agent_image = '';
	$broker_type = 'staff';
	$broker_id = 0;
	$public_agent_id = 0;
	if(is_numeric($request_broker['company_id']) && $request_broker['company_id'] != 0){
		$get_agent = real_get_company_name($request_broker['company_id'], true, true, false, false);
		$agent_name = $get_agent->name;
		$agent_address = $get_agent->company_address;
		$agent_image = company_profile_image($get_agent->id, ['img', 'img-responsive agent-image']);
		$agent_employees = $request_broker['agent_employees'];
		$broker_type = 'company';
		$public_agent_id = $request_broker['company_id'];
	}
	if(is_numeric($request_broker['broker_id']) && $request_broker['broker_id'] != 0){
		$get_company = real_get_company_name($request_broker['broker_id'], true, true, false, false);
		$agent_name = $get_company->name;
		$agent_address = $get_company->company_address;
		$agent_image = company_profile_image($request_broker['broker_id'], ['img', 'img-responsive agent-image']);
		$get_construction_company = $CI->realestate_model->get_construction_company($request_broker['broker_id']);
		$agent_employees = $get_construction_company->broker_staffs;
		$broker_type = 'business_broker';
		$public_agent_id = $request_broker['broker_id'];

	}
	if(is_numeric($request_broker['company_id']) && $request_broker['company_id'] == 0 && is_numeric($request_broker['broker_id']) && $request_broker['broker_id'] == 0 && $request_key == 'company'){
		$agent_image =  real_get_company_logo('', 'img img-responsive agent-image');
		$agent_name = '';
		$agent_address = format_organization_info();
		$agent_employees = $request_broker['agent_employees'];
		$broker_type = 'staff';
		$public_agent_id = 0;
	}

	?>
	<div class="layout__sidebar layout__sidebar--large-and-bigger layout__sidebar--collapsed">
		<div class="layout__sidebar-primary mtop45" >
			<div class="contact-agent-panel">
				<div class="">
					<?php if($public_agent_id == 0){ ?>
						<a href="<?php echo site_url('realestate/client/company/'.$public_agent_id); ?>" target="_blank">
							<?php echo html_entity_decode($agent_image ?? ''); ?>
						</a>
					<?php }else{ ?>
						<a href="<?php echo site_url('realestate/client/agent/'.$public_agent_id); ?>" target="_blank">
							<?php echo html_entity_decode($agent_image ?? ''); ?>
						</a>
					<?php } ?>
				</div>
				<div class="padding-10">
					<?php foreach ($agent_employees as $key => $agent_employee) { ?>
						<?php 
						$agent_employee_image = '';
						if(is_numeric($request_broker['broker_id']) && $request_broker['broker_id'] != 0){
							$agent_employee_image = broker_profile_image($agent_employee['id'], ['staff-profile-image-small mright10']);
							$broker_id = $agent_employee['id'];
							$agent_employee_name = $agent_employee['firstname'].' '. $agent_employee['lastname'];
							$agent_employee_phonenumber = $agent_employee['phonenumber'];
						}

						if(is_numeric($request_broker['company_id']) && $request_broker['company_id'] != 0){
							$agent_employee_image = staff_profile_image($agent_employee['staff_id'], ['staff-profile-image-small mright10']);
							$broker_id = $agent_employee['staff_id'];
							$agent_employee_name = get_staff_full_name($agent_employee['staff_id']);
							$agent_employee_phonenumber = get_staff_phonenumber($agent_employee['staff_id']);
						}

						if(is_numeric($request_broker['company_id']) && $request_broker['company_id'] == 0 && is_numeric($request_broker['broker_id']) && $request_broker['broker_id'] == 0  && $request_key == 'company'){
							$agent_employee_image = staff_profile_image($agent_employee['staff_id'], ['staff-profile-image-small mright10']);
							$broker_id = $agent_employee['staff_id'];
							$agent_employee_name = get_staff_full_name($agent_employee['staff_id']);
							$agent_employee_phonenumber = get_staff_phonenumber($agent_employee['staff_id']);

						}

						if($broker_id == 0){
							continue;
						}

						?>
						<ul class="">
							<li class="tw-flex tw-items-center">
								<div class="agent-info__photo">
									<?php if(is_numeric($request_broker['broker_id']) && $request_broker['broker_id'] != 0){
										$public_staff_url = site_url('realestate/client/agent/'.$request_broker['broker_id']);
										?>
										<a href="<?php echo html_entity_decode($public_staff_url); ?>">
											<?php echo html_entity_decode($agent_employee_image); ?>
										</a>
									<?php }else{ 
										$public_staff_url = site_url('realestate/client/staff/'.$agent_employee['staff_id']);

										?>
										<a href="<?php echo html_entity_decode($public_staff_url); ?>">
											<?php echo html_entity_decode($agent_employee_image); ?>
										</a>
									<?php } ?>
								</div>
								<div class="agent-info__contact-info">
									<div>
										<a class="tw-font-semibold text-black tw-text-base" href="<?php echo html_entity_decode($public_staff_url); ?>"><?php echo html_entity_decode($agent_employee_name); ?></a>
										<div class=" mbot5 hide">
											<i class="fa-solid fa-star color-FFD43B"></i>
											<span class="text-muted">0.0</span>
											<span class="text-muted">(0 reviews)</span>
										</div>
										<div class="phone">
											<a class="agent-phone-number tw-font-semibold " title="Click to reveal phone number">
												<i class="fa-solid fa-phone text-muted"></i>
												<span class="text-muted"><?php echo html_entity_decode($agent_employee_phonenumber ?? ''); ?></span>
											</a>
										</div>
									</div>
								</div>
							</li>
							<div class="tw-flex tw-flex-col ">
								<a href="#" class="btn btn-default text-right mbot5 hide"><?php echo _l('real_get_in_touch'); ?></a>
								<a href="#" class="btn btn-default text-right hide"><?php echo _l('real_save_property'); ?></a>
								<?php if($apply_button){ ?>
									<?php if($property_listing->transaction_type == 'Rent'){ ?>
										<a href="<?php echo site_url('realestate/client/property_request?request_type=rent&property='.$property_listing->id.'&broker_type='.$broker_type.'&broker_id='.$broker_id); ?>" class="btn btn-danger text-right mtop5"><?php echo _l('real_request'); ?></a>
									<?php }elseif($property_listing->transaction_type == 'Sale'){ ?>
										<a href="<?php echo site_url('realestate/client/property_request?request_type=buy&property='.$property_listing->id.'&broker_type='.$broker_type.'&broker_id='.$broker_id); ?>" class="btn btn-danger text-right mtop5"><?php echo _l('real_request'); ?></a>
									<?php } ?>
								<?php } ?>
							</div>
							<hr class="mtop5 mbot5">
						</ul>
					<?php } ?>

					<div class="mtop15">
						<a href="#" class="tw-font-semibold text-black"><?php echo html_entity_decode($agent_name); ?></a>
						<div class=""><?php echo html_entity_decode($agent_address); ?></div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php } ?>