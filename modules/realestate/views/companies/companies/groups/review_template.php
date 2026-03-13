<div class="row">
	<div class="col-md-12">
		<?php 
		$base_currency_id = get_base_currency_id();
		$code = isset($construction_company) ? $construction_company->code.' '.$construction_company->name : '';

		if($construction_company->related_type == 'company'){
			if(isset($construction_company->is_company_admin) && $construction_company->is_company_admin == 1){
				$format_organization_info = format_organization_info();
				$agent_address = _maybe_remove_first_and_last_br_tag(str_replace('<b  class="company-name-formatted">' . get_option('invoice_company_name') . '</b>','', $format_organization_info));

				$agent_image =  real_get_company_logo('', 'img img-responsive agent-image');
				$related_type = 'company';
				if(isset($construction_company->company_staffs)){
					$agent_employees = $construction_company->company_staffs;
				}
			}else{
				if(isset($construction_company->company_staffs)){
					$agent_employees = $construction_company->company_staffs;
				}
				$agent_address = real_get_company_name($construction_company->id, true, true, true, false);
				$agent_image = company_profile_image($construction_company->id, ['img', 'img-responsive agent-image']);
				$related_type = 'company';
			}
		}else{
			if(isset($construction_company->company_staffs)){
				$agent_employees = $construction_company->broker_staffs; 
			}
			$agent_address = real_get_company_name($construction_company->id, true, true, true, false);
			$agent_image = company_profile_image($construction_company->id, ['img', 'img-responsive agent-image']);
			$related_type = 'business_broker';
		}
		?>
		<?php if($related_type == 'company'){ ?>
			<h4 class="tw-font-semibold">
				<?php echo html_entity_decode($code); ?>
				<?php if(isset($construction_company->is_company_admin) || (isset($construction_company->verification_status) && $construction_company->verification_status == 'verified')){ ?>
					<img src="<?php echo  site_url('modules/realestate/assets/images/verified.svg') ?>" class="tw-inline-block tw-h-5 tw-w-5 tw-rounded-full tw-ring-2 tw-ring-white mbot10">
				<?php } ?>
			</h4>
			<p class="text-muted"><?php echo html_entity_decode($agent_address); ?></p>
		<?php } ?>

		<div class="tw-items-center ltr:tw-space-x-2 tw-inline-flex">
			<div class="tw-flex -tw-space-x-1">
				<?php if(isset($agent_employees)) { ?>
				<?php foreach ($agent_employees as $member) { ?>
					<?php 
					if($related_type == 'company'){
						$agent_employee_name = get_staff_full_name($member['staffid']);
						$agent_employee_image = staff_profile_image($member['staffid'], ['tw-inline-block tw-h-7 tw-w-7 tw-rounded-full tw-ring-2 tw-ring-white']);
					}else{
						$agent_employee_image = broker_profile_image($member['id'], ['tw-inline-block tw-h-9 tw-w-9 tw-rounded-full tw-ring-2 tw-ring-white team-profile-image-thumb']);
						$agent_employee_name = $member['firstname'].' '. $member['lastname'];
					}
					?>
					<span class="tw-group tw-relative"
					data-title="<?php echo new_html_entity_decode($agent_employee_name); ?>"
					data-toggle="tooltip">
					<?php echo new_html_entity_decode($agent_employee_image); ?>
				</span>
			<?php } ?>
			<?php } ?>
		</div>
		<span class="tw-mt-1.5 rtl:tw-mr-3"></span>
		<?php if($related_type == 'business_broker'){ ?>
			<div>
				<h4 class="tw-font-semibold">
					<?php echo html_entity_decode($code); ?>
					<?php if(isset($construction_company->is_company_admin) || (isset($construction_company->verification_status) && $construction_company->verification_status == 'verified')){ ?>
						<img src="<?php echo  site_url('modules/realestate/assets/images/verified.svg') ?>" class="tw-inline-block tw-h-5 tw-w-5 tw-rounded-full tw-ring-2 tw-ring-white mbot10">
					<?php } ?>
				</h4>
				<p class="text-muted"><?php echo html_entity_decode($agent_address); ?></p>
			</div>
		<?php } ?>
	</div>

</div>
</div>
<div class="row mtop25">
	<div class="col-md-9">
		<div class="row">
			<div class="col-md-12 contact-agent-panel padding-10">
				<h4 class="tw-font-semibold"><?php echo _l('real_sale_performance'); ?></h4>
				<div class="row">
					<div class="col-md-2">
						<a href="javascript:void(0);" class="text-color475569">
							<div class="card-block card-block-center">
								<i class="fa-solid fa-circle-dollar-to-slot tw-text-xl" aria-hidden="true"></i> 
								<h4 class="tw-font-semibold tw-overflow-x-hidden tw-text-ellipsis tw-truncate"  data-placement="bottom" data-toggle="tooltip" data-title="<?php echo app_format_money($sale_performance['median_sold_price'], $base_currency_id); ?>"><?php echo app_format_money($sale_performance['median_sold_price'], $base_currency_id); ?></h4>
								<h5><?php echo _l('real_median_sold_price'); ?></h5>
							</div>
						</a>
					</div>
					<div class="col-md-2">
						<a href="javascript:void(0);" class="text-color475569">
							<div class="card-block card-block-center">
								<i class="fa-solid fa-sun tw-text-xl" aria-hidden="true"></i> 
								<h4 class="tw-font-semibold tw-overflow-x-hidden tw-text-ellipsis tw-truncate"  data-placement="bottom" data-toggle="tooltip" data-title="<?php echo app_format_number($sale_performance['properties_sold']); ?>"><?php echo app_format_number($sale_performance['properties_sold']); ?></h4>
								<h5><?php echo _l('real_properties_sold'); ?></h5>
							</div>
						</a>
					</div>
					<div class="col-md-2">
						<a href="javascript:void(0);" class="text-color475569">
							<div class="card-block card-block-center">
								<i class="fa fa-home tw-text-xl" aria-hidden="true"></i> 
								<h4 class="tw-font-semibold tw-overflow-x-hidden tw-text-ellipsis tw-truncate"  data-placement="bottom" data-toggle="tooltip" data-title="<?php echo app_format_number($sale_performance['properties_for_sale']); ?>"><?php echo app_format_number($sale_performance['properties_for_sale']); ?></h4>
								<h5><?php echo _l('real_properties_for_sale'); ?></h5>
							</div>
						</a>
					</div>
					<?php if($related_type == 'company'){ ?>
					<div class="col-md-2">
						<a href="javascript:void(0);" class="text-color475569">
							<div class="card-block card-block-center">
								<i class="fa-solid fa-circle-dollar-to-slot tw-text-xl" aria-hidden="true"></i> 
								<h4 class="tw-font-semibold tw-overflow-x-hidden tw-text-ellipsis tw-truncate"  data-placement="bottom" data-toggle="tooltip" data-title="<?php echo app_format_money($sale_performance['median_leased_price'], $base_currency_id); ?>"><?php echo app_format_money($sale_performance['median_leased_price'], $base_currency_id); ?></h4>
								<h5><?php echo _l('real_median_leased_price'); ?></h5>
							</div>
						</a>
					</div>
					<div class="col-md-2">
						<a href="javascript:void(0);" class="text-color475569">
							<div class="card-block card-block-center">
								<i class="fa fa-home tw-text-xl" aria-hidden="true"></i> 
								<h4 class="tw-font-semibold tw-overflow-x-hidden tw-text-ellipsis tw-truncate"  data-placement="bottom" data-toggle="tooltip" data-title="<?php echo app_format_number($sale_performance['properties_leased']); ?>"><?php echo app_format_number($sale_performance['properties_leased']); ?></h4>
								<h5><?php echo _l('real_properties_leased'); ?></h5>
							</div>
						</a>
					</div>
					<div class="col-md-2">
						<a href="javascript:void(0);" class="text-color475569">
							<div class="card-block card-block-center">
								<i class="fa-solid fa-people-group tw-text-xl" aria-hidden="true"></i> 
								<h4 class="tw-font-semibold tw-overflow-x-hidden tw-text-ellipsis tw-truncate"  data-placement="bottom" data-toggle="tooltip" data-title="<?php echo app_format_number($sale_performance['properties_for_rent']); ?>"><?php echo app_format_number($sale_performance['properties_for_rent']); ?></h4>
								<h5><?php echo _l('real_properties_for_rent'); ?></h5>
							</div>
						</a>
					</div>
				<?php } ?>
				</div>
			</div>

			<div class="col-md-12 mtop25  contact-agent-panel padding-10">
				<h4 class="tw-font-semibold"><?php echo _l('real_our_properties'); ?></h4>
				<div class="">
					<?php if(count($map_property_listing) > 0){ ?>
						<div id="map_area" class="listing_review_map"></div>
					<?php }else{ ?>
						<h4><?php echo _l('real_No_entries_found'); ?></h4>
					<?php } ?>
				</div>
				<div class="row mtop15">
					<div id="grid_dt_view" >

					</div>
					<div class="col-md-12">
						<div class="text-right">
							<ul id="pagination-demo" class="pagination-lg"></ul>
						</div>
						<input type="hidden" name="page_number" value=""> 
					</div>
				</div>
			</div>

			<?php if($related_type == 'company' && isset($construction_company->public_company_staffs) && count($construction_company->public_company_staffs) > 0){ ?>
				<div class="col-md-12 mtop25  contact-agent-panel padding-10">
					<h4 class="tw-font-semibold"><?php echo _l('real_about_the_team'); ?></h4>
					<div class="row">
						<?php foreach ($construction_company->public_company_staffs as $member) { ?>

							<div class="col-md-3 tw-p-2">
								<a href="<?php echo site_url('realestate/client/staff/'.$member['staffid']); ?>" class="text-color475569" data-title="<?php echo e(get_staff_full_name($member['staffid'])); ?>" data-toggle="tooltip">
									<div class="card-block card-block-center team-member">
										<?php echo staff_profile_image($member['staffid'], ['tw-inline-block tw-h-9 tw-w-9 tw-rounded-full tw-ring-2 tw-ring-white team-profile-image-thumb', '']); ?>
										<h5 class="tw-font-semibold"><?php echo e(get_staff_full_name($member['staffid'])); ?></h5>
										<a href="mailto:'<?php echo new_html_entity_decode($member['email']); ?>'" class="" title="Click to reveal phone number">
											<span class="text-muted"><?php echo new_html_entity_decode($member['email']); ?></span>
										</a>
										<a href="tel:'<?php echo new_html_entity_decode($member['phonenumber']); ?>'" class="tw-font-semibold " title="Click to reveal phone number">
											<span class="text-muted"><?php echo new_html_entity_decode($member['phonenumber']); ?></span>
										</a>
									</div>
								</a>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if(new_strlen($construction_company->about_information) > 0){ ?>
				<div class="col-md-12 mtop25  contact-agent-panel padding-10 tw-text-justify">
					<h4 class="tw-font-semibold"><?php echo _l('real_about_information'); ?></h4>

					<div class="">
						<p class=""><?php echo new_html_entity_decode($construction_company->about_information); ?></p>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>

	<div class="col-md-3">
		
		<div class="layout__sidebar layout__sidebar--large-and-bigger layout__sidebar--collapsed">
			<div class="layout__sidebar-primary" >
				<div class="contact-agent-panel">
					<div class="">
						<a href="#">
							<?php echo html_entity_decode($agent_image ?? ''); ?>
						</a>
					</div>
					<div class="padding-10 ">
						<?php if($construction_company->email != '' && $construction_company->email != null){ ?>

							<ul class="">
								<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
									<div class="agent-info__photo">
										<i class="fa-regular fa-envelope text-muted tw-text-xl mright10"></i>
									</div>
									<div class="agent-info__contact-info">
										<div>
											<div class="phone">
												<a href="mailto:'<?php echo new_html_entity_decode($construction_company->email); ?>'" class="tw-font-semibold " title="Click to reveal phone number">
													<span class="text-muted"><?php echo new_html_entity_decode($construction_company->email); ?></span>
												</a>
											</div>
										</div>
									</div>
								</li>
								<hr class="mtop5 mbot5">
							</ul>
						<?php } ?>
						<?php if($construction_company->phonenumber != '' && $construction_company->phonenumber != null){ ?>

							<ul class="">
								<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
									<div class="agent-info__photo">
										<i class="fa-solid fa-phone text-muted tw-text-xl mright10"></i>
									</div>
									<div class="agent-info__contact-info">
										<div>
											<div class="phone">
												<a href="tel:'<?php echo new_html_entity_decode($construction_company->phonenumber); ?>'" class="tw-font-semibold " title="Click to reveal phone number">
													<span class="text-muted"><?php echo new_html_entity_decode($construction_company->phonenumber); ?></span>
												</a>
											</div>
										</div>
									</div>
								</li>
								<hr class="mtop5 mbot5">
							</ul>
						<?php } ?>
						<?php if($construction_company->facebook_url != '' && $construction_company->facebook_url != null){ ?>

							<ul class="">
								<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
									<div class="agent-info__photo">
										<i class="fa-brands fa-facebook text-muted tw-text-xl mright10"></i>
									</div>
									<div class="agent-info__contact-info">
										<div>
											<div class="phone">
												<a href="<?php echo new_html_entity_decode($construction_company->facebook_url); ?>" target="_blank" class="tw-font-semibold " title="Click to reveal phone number">
													<span class="text-muted"><?php echo new_html_entity_decode($construction_company->facebook_url); ?></span>
												</a>
											</div>
										</div>
									</div>
								</li>
								<hr class="mtop5 mbot5">
							</ul>
						<?php } ?>
						<?php if($construction_company->instagram_url != '' && $construction_company->instagram_url != null){ ?>

							<ul class="">
								<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
									<div class="agent-info__photo">
										<i class="fa-brands fa-instagram text-muted tw-text-xl mright10"></i>
									</div>
									<div class="agent-info__contact-info">
										<div>
											<div class="phone">
												<a href="<?php echo new_html_entity_decode($construction_company->instagram_url); ?>" target="_blank" class="tw-font-semibold " title="Click to reveal phone number">
													<span class="text-muted"><?php echo new_html_entity_decode($construction_company->instagram_url); ?></span>
												</a>
											</div>
										</div>
									</div>
								</li>
								<hr class="mtop5 mbot5">
							</ul>
						<?php } ?>
						<?php if($construction_company->whatsapp_url != '' && $construction_company->whatsapp_url != null){ ?>

							<ul class="">
								<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
									<div class="agent-info__photo">
										<i class="fa-brands fa-whatsapp text-muted tw-text-xl mright10"></i>
									</div>
									<div class="agent-info__contact-info">
										<div>
											<div class="phone">
												<a href="<?php echo new_html_entity_decode($construction_company->whatsapp_url); ?>" target="_blank" class="tw-font-semibold " title="<?php echo new_html_entity_decode($construction_company->whatsapp_url); ?>">
													<span class="text-muted"><?php echo new_html_entity_decode($construction_company->whatsapp_url); ?></span>
												</a>
											</div>
										</div>
									</div>
								</li>
								<hr class="mtop5 mbot5">
							</ul>
						<?php } ?>

						<?php if($construction_company->website != '' && $construction_company->website != null){ ?>
							<ul class="">
								<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
									<div class="agent-info__photo">
										<i class="fa-solid fa-earth-americas text-muted tw-text-xl mright10"></i>
									</div>
									<div class="agent-info__contact-info">
										<div>
											<div class="phone">
												<a href="<?php echo new_html_entity_decode($construction_company->website); ?>" class="tw-font-semibold " target="_blank" title="<?php echo new_html_entity_decode($construction_company->website); ?>">
													<span class="text-muted "><?php echo new_html_entity_decode($construction_company->website); ?></span>
												</a>
											</div>
										</div>
									</div>
								</li>
							</ul>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
