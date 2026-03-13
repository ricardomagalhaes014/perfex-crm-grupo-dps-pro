<?php defined('BASEPATH') or exit('No direct script access allowed');?>
<?php 
$_entity_show = 8;
$_entity_show1 = 6;
$real_listing_type = real_listing_type();
$real_property_style = real_property_style();
$beds = rel_bed_filter();
$property_status = rel_property_listing_status();
$baths = rel_baths_filter();
$garages = rel_garages_filter();
$lands_min = rel_lands_min_filter();
$lands_max = rel_lands_max_filter();
$rel_appliances_included = rel_appliances_included();
$rel_utilities = rel_utilities();
$rel_sewer = rel_sewer();
$rel_water = rel_water();
$rel_air_conditioning = rel_air_conditioning();
$rel_electrical_service = rel_electrical_service();
$rel_security_features = rel_security_features();
$rel_accessibility_features = rel_accessibility_features();
?>
<div class="modal fade" id="filter_modal" tabindex="-1" role="dialog">
	<?php echo form_open(admin_url('realestate/form_filter'), array('id' => 'form_filter', 'autocomplete'=>'off')); ?>
<div class="_filters _hidden_inputs ">
	<input type="hidden" name="filter_appliances_included_value">
	<input type="hidden" name="filter_transaction_type_value" value="Sale,Rent,sold,rented">
	<input type="hidden" name="filter_listing_type_value">
	<input type="hidden" name="filter_property_style_value">
	<input type="hidden" name="filter_utilities_value">
	<input type="hidden" name="filter_sewer_value">
	<input type="hidden" name="filter_water_value">
	<input type="hidden" name="filter_air_conditioning_value">
	<input type="hidden" name="filter_electrical_service_value">
	<input type="hidden" name="filter_security_features_value">
	<input type="hidden" name="filter_accessibility_features_value">
</div>
<div class="modal-dialog modal-lg">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="font-bold text-center"><?php echo _l('real_filters'); ?></h4>
		</div>
		
		<div class="modal-body">

			<div class="row">
				<div class="col-md-12">
					<?php echo render_input('filter_input', 'real_search_input'); ?>
					
				</div>
				<div class="col-md-12">
					<div class="row">
						<div class=" boxed-check-group boxed-check-outline-success">
							<div class="col-12 col-sm-6">
								<label class="boxed-check">
									<input class="boxed-check-input" type="checkbox" id="transaction_type_1" name="transaction_type" value="Sale" checked>
									<div class="boxed-check-label text-center">
										<img src="<?php echo site_url('modules/realestate/assets/images/sale.png') ?>" class="img img-responsive display-inline" width=30>
										<span class="tw-text-xl"><?php echo _l('rel_Sale'); ?></span>
									</div>
								</label>
							</div>
						</div>
						<div class=" boxed-check-group boxed-check-outline-success">
							<div class="col-12 col-sm-6">
								<label class="boxed-check">
									<input class="boxed-check-input" type="checkbox" id="transaction_type_2" name="transaction_type" value="Rent" checked>
									<div class="boxed-check-label text-center">
										<img src="<?php echo site_url('modules/realestate/assets/images/rent.png') ?>" class="img img-responsive display-inline" width=30>
										<span class="tw-text-xl"><?php echo _l('rel_Rent'); ?></span>
									</div>
								</label>
							</div>
						</div>
						<div class=" boxed-check-group boxed-check-outline-success hide">
							<div class="col-12 col-sm-3">
								<label class="boxed-check">
									<input class="boxed-check-input" type="checkbox" id="transaction_type_3" name="transaction_type" value="sold" checked>
									<div class="boxed-check-label text-center">
										<img src="<?php echo site_url('modules/realestate/assets/images/sold.png') ?>" class="img img-responsive display-inline" width=30>
										<span class="tw-text-xl"><?php echo _l('real_sold'); ?></span>
									</div>
								</label>
							</div>
						</div>
						<div class=" boxed-check-group boxed-check-outline-success hide">
							<div class="col-12 col-sm-3">
								<label class="boxed-check">
									<input class="boxed-check-input" type="checkbox" id="transaction_type_4" name="transaction_type" value="rented" checked>
									<div class="boxed-check-label text-center">
										<img src="<?php echo site_url('modules/realestate/assets/images/rented.png') ?>" class="img img-responsive display-inline" width=30>
										<span class="tw-text-xl"><?php echo _l('real_rented'); ?></span>
									</div>
								</label>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-12">
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>
					<h4 class="tw-font-semibold"><?php echo _l('real_status'); ?></h4>
					<div class="row">
						<div class="col-md-12">
							<?php echo render_select('status', $property_status, ['id', 'name'], 'real_status', '', ['data-none-selected-text' => _l('real_status')], [], '', '', true); ?>
						</div>
					</div>

				</div>
				<div class="col-md-6">
					<h4 class="tw-font-semibold"><?php echo _l('real_listing_type'); ?></h4>
					<?php foreach ($real_listing_type as $key => $value) { ?>
						<div class="form-group">
							<div class="checkbox checkbox-primary">
								<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="listing_type" value="<?php echo html_entity_decode($value['name']); ?>">
								<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
							</div>
						</div>
					<?php } ?>

				</div>
				<div class="col-md-6">
					<h4 class="tw-font-semibold"><?php echo _l('real_property_style'); ?></h4>
					<div class="row">
						
						<?php if(count($real_property_style) > 10){ ?>
							<div class="col-md-12">
								<div class="form-group">
									<div class="checkbox checkbox-primary">
										<input type="checkbox" id="property_style_all" name="property_style" value="all">
										<label for="property_style_all"><?php echo _l('real_all'); ?></label>
									</div>
								</div>
							</div>
						<?php } ?>

						<?php foreach ($real_property_style as $key => $value) { ?>
							<?php if($key == $_entity_show){ ?>
								<span id="real_property_style" class="hide">
								<?php } ?>
								<div class="col-md-6">
									<div class="form-group">
										<div class="checkbox checkbox-primary no-mtop">
											<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="property_style" value="<?php echo html_entity_decode($value['name']); ?>">
											<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
										</div>
									</div>
								</div>
								<?php if($key > $_entity_show && count($real_property_style) == ($key+1)){ ?>
								</span>
								<div class="col-md-12">
									<a id="real_property_style_button" class="label text-primary" data-title="<?php echo _l('real_property_style'); ?>" onclick="changeReadMore('real_property_style')"><?php echo _l('real_show_more'); ?></a>
								</div>
							<?php } ?>

						<?php } ?>
					</div>

				</div>
				<div class="col-md-12">
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>
					<h4 class="tw-font-semibold"><?php echo _l('real_price'); ?></h4>
					<div class="row">
						<div class="col-md-6">
							<?php echo render_input('min_price', 'real_min_price', '', 'number', ['step' => 'any', 'min' => 0]); ?>
						</div>
						<div class="col-md-6">
							<?php echo render_input('max_price', 'real_max_price', '', 'number', ['step' => 'any', 'min' => 0]); ?>
							
						</div>
					</div>
				</div>
				<div class="col-md-12">
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>
					<h4 class="tw-font-semibold"><?php echo _l('real_bedrooms'); ?></h4>
					<div class="row">
						<div class="col-md-6">
							<?php echo render_select('min_bed', $beds, ['name', 'label'], 'real_min', '', [], [], '', '', false); ?>
						</div>
						<div class="col-md-6">
							<?php echo render_select('max_bed', $beds, ['name', 'label'], 'real_max', '', [], [], '', '', false); ?>
						</div>
					</div>

				</div>
				<div class="col-md-12">
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>

					<div class="row">
						<div class="col-md-6">
							<h4 class="tw-font-semibold"><?php echo _l('real_bathrooms'); ?></h4>
							<?php echo render_select('filter_bath', $baths, ['name', 'label'], '', '', [], [], '', '', false); ?>
						</div>
						<div class="col-md-6">
							<h4 class="tw-font-semibold"><?php echo _l('real_garage'); ?></h4>
							<?php echo render_select('filter_garage', $garages, ['name', 'label'], '', '', [], [], '', '', false); ?>
						</div>
					</div>

				</div>
				
				<div class="col-md-12">
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>
					<h4 class="tw-font-semibold"><?php echo _l('real_land_size'); ?></h4>

					<div class="row">
						<div class="col-md-6">
							<?php echo render_select('filter_land_min', $lands_min, ['name', 'label'], 'real_min', '', [], [], '', '', false); ?>
						</div>
						<div class="col-md-6">
							<?php echo render_select('filter_land_max', $lands_max, ['name', 'label'], 'real_max', '', [], [], '', '', false); ?>
						</div>
					</div>
				</div>

				<div class="col-md-12">
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>
					<h4 class="tw-font-semibold"><?php echo _l('real_floor_size'); ?></h4>
					<div class="row">
						<div class="col-md-6">
							<?php echo render_input('min_total_of_floors', 'real_min', '', 'number', ['step' => 'any', 'min' => 0]); ?>
						</div>
						<div class="col-md-6">
							<?php echo render_input('min_total_of_floors', 'real_max', '', 'number', ['step' => 'any', 'min' => 0]); ?>
							
						</div>
					</div>
				</div>

				<div class="col-md-12">
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>
					<h4 class="tw-font-semibold"><?php echo _l('real_indoor_features'); ?></h4>
					<div class="row">
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('rel_appliances_included'); ?></h5>
							<div class="row">
								<div class="col-md-3">
									<?php 
									$_column = 4;
									$_entity = count($rel_appliances_included);
									$_entity_per_column = round($_entity / $_column);
									?>
									<?php foreach ($rel_appliances_included as $key => $value) { ?>
										<div class="form-group">
											<div class="checkbox checkbox-primary">
												<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="appliances_included" value="<?php echo html_entity_decode($value['name']); ?>">
												<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
											</div>
										</div>
										<!-- new column -->
										<?php if(($key+1) % $_entity_per_column == 0){ ?>
										</div>
										<div class="col-md-3">
										<?php } ?>
									<?php } ?>

								</div>
							</div>
						</div>
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('rel_utilities'); ?></h5>
							<div class="row">
								<?php foreach ($rel_utilities as $key => $value) { ?>
									<?php if($key == $_entity_show){ ?>
										<span id="rel_utilities" class="hide">
										<?php } ?>
										<div class="col-md-3">
											<div class="form-group">
												<div class="checkbox checkbox-primary no-mtop">
													<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="utilities" value="<?php echo html_entity_decode($value['name']); ?>">
													<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
												</div>
											</div>
										</div>
										<?php if($key > $_entity_show && count($rel_utilities) == ($key+1)){ ?>
										</span>
										<div class="col-md-12">
											<a id="rel_utilities_button" class="label text-primary" data-title="<?php echo _l('rel_utilities'); ?>" onclick="changeReadMore('rel_utilities')"><?php echo _l('real_show_more'); ?></a>
										</div>
									<?php } ?>

								<?php } ?>
							</div>
						</div>
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('rel_sewer'); ?></h5>
							<div class="row">
								<?php foreach ($rel_sewer as $key => $value) { ?>
									<?php if($key == $_entity_show){ ?>
										<span id="rel_sewer" class="hide">
										<?php } ?>
										<div class="col-md-3">
											<div class="form-group">
												<div class="checkbox checkbox-primary no-mtop">
													<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="sewer" value="<?php echo html_entity_decode($value['name']); ?>">
													<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
												</div>
											</div>
										</div>
										<?php if($key > $_entity_show && count($rel_sewer) == ($key+1)){ ?>
										</span>
										<div class="col-md-12">
											<a id="rel_sewer_button" class="label text-primary" data-title="<?php echo _l('rel_sewer'); ?>" onclick="changeReadMore('rel_sewer')"><?php echo _l('real_show_more'); ?></a>
										</div>
									<?php } ?>

								<?php } ?>
							</div>
						</div>
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('rel_water'); ?></h5>
							<div class="row">
								<?php foreach ($rel_water as $key => $value) { ?>
									<?php if($key == $_entity_show){ ?>
										<span id="rel_water" class="hide">
										<?php } ?>
										<div class="col-md-3">
											<div class="form-group">
												<div class="checkbox checkbox-primary no-mtop">
													<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="water" value="<?php echo html_entity_decode($value['name']); ?>">
													<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
												</div>
											</div>
										</div>
										<?php if($key > $_entity_show && count($rel_water) == ($key+1)){ ?>
										</span>
										<div class="col-md-12">
											<a id="rel_water_button" class="label text-primary" data-title="<?php echo _l('rel_water'); ?>" onclick="changeReadMore('rel_water')"><?php echo _l('real_show_more'); ?></a>
										</div>
									<?php } ?>

								<?php } ?>
							</div>
						</div>
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('rel_air_conditioning'); ?></h5>
							<div class="row">
								<?php foreach ($rel_air_conditioning as $key => $value) { ?>
									<?php if($key == $_entity_show){ ?>
										<span id="rel_air_conditioning" class="hide">
										<?php } ?>
										<div class="col-md-3">
											<div class="form-group">
												<div class="checkbox checkbox-primary no-mtop">
													<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="air_conditioning" value="<?php echo html_entity_decode($value['name']); ?>">
													<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
												</div>
											</div>
										</div>
										<?php if($key > $_entity_show && count($rel_air_conditioning) == ($key+1)){ ?>
										</span>
										<div class="col-md-12">
											<a id="rel_air_conditioning_button" class="label text-primary" data-title="<?php echo _l('rel_air_conditioning'); ?>" onclick="changeReadMore('rel_air_conditioning')"><?php echo _l('real_show_more'); ?></a>
										</div>
									<?php } ?>

								<?php } ?>
							</div>
						</div>
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('real_electrical_Service'); ?></h5>
							<div class="row">
								<?php foreach ($rel_electrical_service as $key => $value) { ?>
									<?php if($key == $_entity_show){ ?>
										<span id="rel_electrical_service" class="hide">
										<?php } ?>
										<div class="col-md-3">
											<div class="form-group">
												<div class="checkbox checkbox-primary no-mtop">
													<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="electrical_service" value="<?php echo html_entity_decode($value['name']); ?>">
													<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
												</div>
											</div>
										</div>
										<?php if($key > $_entity_show && count($rel_electrical_service) == ($key+1)){ ?>
										</span>
										<div class="col-md-12">
											<a id="rel_electrical_service_button" class="label text-primary" data-title="<?php echo _l('rel_electrical_Service'); ?>" onclick="changeReadMore('rel_electrical_service')"><?php echo _l('real_show_more'); ?></a>
										</div>
									<?php } ?>

								<?php } ?>
							</div>
						</div>
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('rel_security_features'); ?></h5>
							<div class="row">
								<?php foreach ($rel_security_features as $key => $value) { ?>
									<?php if($key == $_entity_show){ ?>
										<span id="rel_security_features" class="hide">
										<?php } ?>
										<div class="col-md-3">
											<div class="form-group">
												<div class="checkbox checkbox-primary no-mtop">
													<input type="checkbox" id="<?php echo html_entity_decode('security_features'.$value['name'].$key); ?>" name="security_features" value="<?php echo html_entity_decode($value['name']); ?>">
													<label for="<?php echo html_entity_decode('security_features'.$value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
												</div>
											</div>
										</div>
										<?php if($key > $_entity_show && count($rel_security_features) == ($key+1)){ ?>
										</span>
										<div class="col-md-12">
											<a id="rel_security_features_button" class="label text-primary" data-title="<?php echo _l('rel_security_features'); ?>" onclick="changeReadMore('rel_security_features')"><?php echo _l('real_show_more'); ?></a>
										</div>
									<?php } ?>

								<?php } ?>
							</div>
						</div>
						<div class="col-md-12">
							<h5 class="tw-font-semibold"><?php echo _l('rel_accessibility_features'); ?></h5>
							<div class="row">
								<?php foreach ($rel_accessibility_features as $key => $value) { ?>
									<?php if($key == $_entity_show1){ ?>
										<span id="rel_accessibility_features" class="hide">
										<?php } ?>
										<div class="col-md-4">
											<div class="form-group">
												<div class="checkbox checkbox-primary no-mtop">
													<input type="checkbox" id="<?php echo html_entity_decode($value['name'].$key); ?>" name="accessibility_features" value="<?php echo html_entity_decode($value['name']); ?>">
													<label for="<?php echo html_entity_decode($value['name'].$key); ?>"><?php echo html_entity_decode($value['label']); ?></label>
												</div>
											</div>
										</div>
										<?php if($key > $_entity_show1 && count($rel_accessibility_features) == ($key+1)){ ?>
										</span>
										<div class="col-md-12">
											<a id="rel_accessibility_features_button" class="label text-primary" data-title="<?php echo _l('rel_accessibility_features'); ?>" onclick="changeReadMore('rel_accessibility_features')"><?php echo _l('real_show_more'); ?></a>
										</div>
									<?php } ?>

								<?php } ?>
							</div>
						</div>


					</div>
				</div>

			</div>
		</div>
		<div class="modal-footer">
				<a href="javascript:void(0)" class="btn btn-primary pull-right mright10 property_filter" ><?php echo _l('real_show_results'); ?></a>
				<a href="javascript:void(0)" class="btn btn-default pull-right mright10 clear_all_filter" ><?php echo _l('real_clear_all_filters'); ?></a>
		</div>
	</div>
</div>
<?php echo form_close(); ?>
</div>
