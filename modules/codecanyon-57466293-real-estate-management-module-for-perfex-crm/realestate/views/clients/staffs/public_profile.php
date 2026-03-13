<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('app_customers_portal_head'); ?>

<div class="panel_s">
	<div class="panel-body">
		<?php echo form_hidden('staff_id', $staff->staffid); ?>
		
		<div class="row">
			<div class="col-md-12">
				<div class="tw-items-center ltr:tw-space-x-2 tw-inline-flex">
					<?php 
					$base_currency_id = get_base_currency_id();
					$agent_image_1 =  staff_profile_image($staff->staffid, ['tw-inline-block tw-h-9 tw-w-9 tw-rounded-full tw-ring-2 tw-ring-white team-profile-image-thumb mright10']);
					$agent_image =  staff_profile_image($staff->staffid, ['staff-profile-image-small mright10']);

					$full_name = $staff->firstname.' '.$staff->lastname;
					$agent_name = rel_get_construction_company_name($staff->company_id);
					?>
					<div class="tw-flex -tw-space-x-1">
						<span class="tw-group tw-relative" data-title="<?php echo new_html_entity_decode($full_name); ?>" data-toggle="tooltip">
							<?php echo new_html_entity_decode($agent_image_1); ?>
						</span>
					</div>

					<span class="tw-mt-1.5 rtl:tw-mr-3"></span>
					<div>
						<h4 class="tw-font-semibold">
							<?php echo html_entity_decode($full_name); ?>
						</h4>
						<p class="text-muted"><?php echo html_entity_decode($agent_name); ?></p>
					</div>
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
						<div class="row mtop25">
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
								<span class="tw-font-semibold"><?php echo html_entity_decode($staff->firstname.' '.$staff->lastname); ?></span>
							</div>
							<div class="padding-10 ">
								<?php if($staff->email != '' && $staff->email != null){ ?>

									<ul class="">
										<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
											<div class="agent-info__photo">
												<i class="fa-regular fa-envelope text-muted tw-text-xl mright10"></i>
											</div>
											<div class="agent-info__contact-info">
												<div>
													<div class="phone">
														<a href="mailto:'<?php echo new_html_entity_decode($staff->email); ?>'" class="tw-font-semibold " title="Click to reveal phone number">
															<span class="text-muted"><?php echo new_html_entity_decode($staff->email); ?></span>
														</a>
													</div>
												</div>
											</div>
										</li>
										<hr class="mtop5 mbot5">
									</ul>
								<?php } ?>
								<?php if($staff->phonenumber != '' && $staff->phonenumber != null){ ?>

									<ul class="">
										<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
											<div class="agent-info__photo">
												<i class="fa-solid fa-phone text-muted tw-text-xl mright10"></i>
											</div>
											<div class="agent-info__contact-info">
												<div>
													<div class="phone">
														<a href="tel:'<?php echo new_html_entity_decode($staff->phonenumber); ?>'" class="tw-font-semibold " title="Click to reveal phone number">
															<span class="text-muted"><?php echo new_html_entity_decode($staff->phonenumber); ?></span>
														</a>
													</div>
												</div>
											</div>
										</li>
										<hr class="mtop5 mbot5">
									</ul>
								<?php } ?>
								<?php if($staff->facebook != '' && $staff->facebook != null){ ?>

									<ul class="">
										<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
											<div class="agent-info__photo">
												<i class="fa-brands fa-facebook text-muted tw-text-xl mright10"></i>
											</div>
											<div class="agent-info__contact-info">
												<div>
													<div class="phone">
														<a href="<?php echo new_html_entity_decode($staff->facebook); ?>" target="_blank" class="tw-font-semibold " title="Click to reveal phone number">
															<span class="text-muted"><?php echo new_html_entity_decode($staff->facebook); ?></span>
														</a>
													</div>
												</div>
											</div>
										</li>
										<hr class="mtop5 mbot5">
									</ul>
								<?php } ?>
								<?php if($staff->linkedin != '' && $staff->linkedin != null){ ?>

									<ul class="">
										<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
											<div class="agent-info__photo">
												<i class="fa-brands fa-linkedin text-muted tw-text-xl mright10"></i>
											</div>
											<div class="agent-info__contact-info">
												<div>
													<div class="phone">
														<a href="<?php echo new_html_entity_decode($staff->linkedin); ?>" target="_blank" class="tw-font-semibold " title="Click to reveal phone number">
															<span class="text-muted"><?php echo new_html_entity_decode($staff->linkedin); ?></span>
														</a>
													</div>
												</div>
											</div>
										</li>
										<hr class="mtop5 mbot5">
									</ul>
								<?php } ?>
								<?php if($staff->skype != '' && $staff->skype != null){ ?>

									<ul class="">
										<li class="tw-flex tw-items-center tw-overflow-x-hidden tw-text-ellipsis tw-truncate">
											<div class="agent-info__photo">
												<i class="fa-brands fa-skype text-muted tw-text-xl mright10"></i>
											</div>
											<div class="agent-info__contact-info">
												<div>
													<div class="phone">
														<a href="<?php echo new_html_entity_decode($staff->skype); ?>" target="_blank" class="tw-font-semibold " title="<?php echo new_html_entity_decode($staff->skype); ?>">
															<span class="text-muted"><?php echo new_html_entity_decode($staff->skype); ?></span>
														</a>
													</div>
												</div>
											</div>
										</li>
										<hr class="mtop5 mbot5">
									</ul>
								<?php } ?>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

<?php real_client_init_tail(); ?>
<?php 
require 'modules/realestate/assets/js/companies/companies/staff_review_js.php';
require('modules/realestate/assets/js/map_js.php');

?>