<?php defined('BASEPATH') or exit('No direct script access allowed');?>

<div class="col-md-12">
	<div class="horizontal-scrollable-tabs">
		<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
		<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
		<div class="horizontal-tabs">
			<ul class="nav nav-tabs profile-tabs row customer-profile-tabs nav-tabs-horizontal" role="tablist">
				<li role="presentation" class="active">
					<a href="#re_listing" aria-controls="re_listing" role="tab" data-toggle="tab">
						<span class="text-danger">(*)</span><?php echo _l( 'real_listing'); ?>
					</a>
				</li>
				<li role="presentation" class="">
					<a href="#re_finance" aria-controls="re_finance" role="tab" data-toggle="tab">
						<?php echo _l('real_expenses'); ?>
					</a>
				</li>
				<li role="presentation">
					<a href="#rel_pool_exterior" aria-controls="rel_pool_exterior" role="tab" data-toggle="tab">
						<?php echo _l( 'rel_pool_exterior'); ?>
					</a>
				</li>

				<li role="presentation">
					<a href="#rel_interior" aria-controls="rel_interior" role="tab" data-toggle="tab">
						<?php echo _l('real_interior'); ?>
					</a>
				</li>
				<li role="presentation">
					<a href="#rel_rooms" aria-controls="rel_rooms" role="tab" data-toggle="tab">
						<?php echo _l('real_rooms'); ?>
					</a>
				</li>

				<li role="presentation">
					<a href="#rel_owner" aria-controls="rel_owner" role="tab" data-toggle="tab">
						</span><?php echo _l('real_owner'); ?>
					</a>
				</li>

				<li role="presentation">
					<a href="#rel_realtor" aria-controls="rel_realtor" role="tab" data-toggle="tab">
						<?php echo _l('real_realtor'); ?>
					</a>
				</li>
				<li role="presentation">
					<a href="#rel_status" aria-controls="rel_status" role="tab" data-toggle="tab">
						<?php echo _l('real_attachments'); ?>
					</a>
				</li>

			</ul>
		</div>
	</div>
	<div class="tab-content ">

		<div role="tabpanel" class="tab-pane active" id="re_listing">
			<?php 

			$rel_listing_type = real_listing_type();
			$rel_transaction_type = real_transaction_type();
			if(is_broker_logged_in()){
				if(isset($rel_transaction_type[1])){
					unset($rel_transaction_type[1]);
				}
			}
			$rel_property_style = real_property_style();
			$rel_property_condition = rel_property_condition();
			$rel_street_dir_pre = rel_street_dir_pre();
			$rel_street_type = rel_street_type();
			$rel_Egypt_Provincial_divisions = rel_Egypt_Provincial_divisions();
			$rel_levels = rel_levels();
			$rel_net_operating_income_type = rel_net_operating_income_type();
			$rel_sale_includes = rel_sale_includes();
			$rel_number_of_tenants = rel_number_of_tenants();
			$rel_spa = rel_spa();
			$rel_balcony = rel_balcony();
			$rel_sqFt_heated_source = rel_sqFt_heated_source();
			$rel_fireplace_description = rel_fireplace_description();
			$rel_appliances_included = rel_appliances_included();
			$rel_utilities = rel_utilities();
			$rel_sewer = rel_sewer();
			$rel_water = rel_water();
			$rel_heating_and_fuel = rel_heating_and_fuel();
			$rel_air_conditioning = rel_air_conditioning();
			$rel_electrical_Service = rel_electrical_Service();
			$rel_security_features = rel_security_features();
			$rel_accessibility_features = rel_accessibility_features();
			$rel_floor_covering = rel_floor_covering();
			$rel_ceiling_type = rel_ceiling_type();
			$rel_window_features = rel_window_features();
			$rel_furnished = rel_furnished();
			$rel_finishing = rel_finishing();
			$rel_ownership = rel_ownership();
			$rel_realtor_information = rel_realtor_information();
			$rel_realtor_information_confidential = rel_realtor_information_confidential();
			$rel_disclosures = rel_disclosures();
			$rel_possession = rel_possession();
			$rel_rent_status = rel_rent_status();
			$countries       = get_all_countries();

			$rel_spa = real_spa();
			$rel_pool_features = real_pool_features();
			$rel_spa_features = real_spa_features();
			$rel_front_exposure = real_front_exposure();
			$rel_easements = real_easements();
			$rel_road_frontage = real_road_frontage();
			$rel_road_surface_type = real_road_surface_type();
			$rel_road_responsibility = real_road_responsibility();
			$rel_signage = real_signage();
			$rel_adjoining_property = real_adjoining_property();
			$rel_other_structures = real_other_structures();
			$rel_other_equipment = real_other_equipment();
			$rel_vegetation = real_vegetation();
			$rel_lot_features = real_lot_features();
			$rel_exterior_construction = real_exterior_construction();
			$rel_roof = real_roof();
			$rel_building_features = real_building_features();
			$rel_garage_parking_features = real_garage_parking_features();
			$rel_foundation = real_foundation();
			$rel_basement = real_basement();
			$rel_balcony = real_balcony();

			$commodity_code = isset($property_listing) ? $property_listing->commodity_code : $_property_code; 
			$description = isset($property_listing) ? $property_listing->description : ''; 
			$rate = isset($property_listing) ? $property_listing->rate : 0; 
			$listing_type = isset($property_listing) ? $property_listing->listing_type : ''; 
			$transaction_type = isset($property_listing) ? $property_listing->transaction_type : 'Sale'; 
			$street_number = isset($property_listing) ? $property_listing->street_number : ''; 
			$street_dir_pre = isset($property_listing) ? $property_listing->street_dir_pre : ''; 
			$street_name = isset($property_listing) ? $property_listing->street_name : ''; 
			$street_type = isset($property_listing) ? $property_listing->street_type : ''; 
			$street_dir_pos = isset($property_listing) ? $property_listing->street_dir_pos : ''; 
			$unit_number = isset($property_listing) ? $property_listing->unit_number : ''; 
			$city = isset($property_listing) ? $property_listing->city : ''; 
			$state = isset($property_listing) ? $property_listing->state : ''; 
			$zip = isset($property_listing) ? $property_listing->zip : ''; 
			$zip_4 = isset($property_listing) ? $property_listing->zip_4 : ''; 
			$country = isset($property_listing) ? $property_listing->country : 0; 

			$levels = isset($property_listing) ? $property_listing->levels : ''; 
			$total_of_floors = isset($property_listing) ? (int)$property_listing->total_of_floors : ''; 
			$operating_expenses = isset($property_listing) ? $property_listing->operating_expenses : ''; 
			$net_operating_income = isset($property_listing) ? $property_listing->net_operating_income : ''; 
			$net_operating_income_type = isset($property_listing) ? $property_listing->net_operating_income_type : ''; 
			$sale_includes = isset($property_listing) && new_strlen($property_listing->sale_includes) > 0 ? new_explode(',', $property_listing->sale_includes) : '';

			$annual_expenses = isset($property_listing) ? $property_listing->annual_expenses : ''; 
			$annual_TTL_schedule_income = isset($property_listing) ? $property_listing->annual_TTL_schedule_income : ''; 
			$annual_income_type = isset($property_listing) ? $property_listing->annual_income_type : ''; 


			$property_style = isset($property_listing) ? $property_listing->property_style : ''; 
			$use_code = isset($property_listing) ? $property_listing->use_code : ''; 
			$new_construction = isset($property_listing) ? $property_listing->new_construction : ''; 
			$property_condition = isset($property_listing) ? $property_listing->property_condition : ''; 
			$proj_completion_date = isset($property_listing) ? $property_listing->proj_completion_date : ''; 
			$year_built = isset($property_listing) ? $property_listing->year_built : '';
			$lot_size_acres = isset($property_listing) ? $property_listing->lot_size_acres : '';
			$beds = isset($property_listing) ? $property_listing->beds : '';
			$full_baths = isset($property_listing) ? $property_listing->full_baths : '';
			$half_baths = isset($property_listing) ? $property_listing->half_baths : '';
			$garage = isset($property_listing) ? $property_listing->garage : '';

			$sqFt_heated = isset($property_listing) ? $property_listing->sqFt_heated : '';
			$sqFt_heated_source = isset($property_listing) ? $property_listing->sqFt_heated_source : '';
			$sqFt_total = isset($property_listing) ? $property_listing->sqFt_total : '';
			$fireplace = isset($property_listing) ? $property_listing->fireplace : '';
			$SqFt_total_source = isset($property_listing) ? $property_listing->SqFt_total_source : '';

			$owner_name = isset($property_listing) ? $property_listing->owner_name : '';
			$owner_phone = isset($property_listing) ? $property_listing->owner_phone : '';
			$owner_email = isset($property_listing) ? $property_listing->owner_email : '';

			$ownership = isset($property_listing) ? $property_listing->ownership : 'Sole_Proprietor';
			$property_owner_id = isset($property_listing) ? $property_listing->property_owner_id : '';
			$status  = isset($property_listing) ? $property_listing->status : 'active';
			$latitude  = isset($property_listing) ? $property_listing->latitude : '';
			$longitude  = isset($property_listing) ? $property_listing->longitude : '';
			$rent_price  = isset($property_listing) ? $property_listing->rent_price : 0;
			$show_rental_price  = isset($property_listing) &&  ($property_listing->transaction_type == 'Rent' ||$property_listing->transaction_type == 'Sale_and_Rent' ) ? '' : 'hide';
			$show_sell_price  = (isset($property_listing) &&  $property_listing->transaction_type == 'Sale' ) || !isset($property_listing) ? '' : 'hide';

			$gas_emission  = isset($property_listing) ? $property_listing->gas_emission : '';
			$egenry_efficient  = isset($property_listing) ? $property_listing->egenry_efficient : '';
			$cable_TV  = isset($property_listing) ? $property_listing->cable_TV : '';
			$computer  = isset($property_listing) ? $property_listing->computer : '';
			$heating  = isset($property_listing) ? $property_listing->heating : '';
			$internet  = isset($property_listing) ? $property_listing->internet : '';
			$floor_location  = isset($property_listing) ? (int)$property_listing->floor_location : '';
			$energy_efficiency  = isset($property_listing) ? $property_listing->energy_efficiency : '';

			$number_of_tenants = isset($property_listing) && new_strlen($property_listing->number_of_tenants) > 0 ? new_explode(',', $property_listing->number_of_tenants) : '';

			$fireplace_description = isset($property_listing) && new_strlen($property_listing->fireplace_description) > 0 ? new_explode(',', $property_listing->fireplace_description) : '';
			$appliances_included = isset($property_listing) && new_strlen($property_listing->appliances_included) > 0 ? new_explode(',', $property_listing->appliances_included) : '';
			$utilities = isset($property_listing) && new_strlen($property_listing->utilities) > 0 ? new_explode(',', $property_listing->utilities) : '';
			$sewer = isset($property_listing) && new_strlen($property_listing->sewer) > 0 ? new_explode(',', $property_listing->sewer) : '';
			$water = isset($property_listing) && new_strlen($property_listing->water) > 0 ? new_explode(',', $property_listing->water) : '';
			$heating_and_fuel = isset($property_listing) && new_strlen($property_listing->heating_and_fuel) > 0 ? new_explode(',', $property_listing->heating_and_fuel) : '';
			$air_conditioning = isset($property_listing) && new_strlen($property_listing->air_conditioning) > 0 ? new_explode(',', $property_listing->air_conditioning) : '';
			$electrical_Service = isset($property_listing) && new_strlen($property_listing->electrical_Service) > 0 ? new_explode(',', $property_listing->electrical_Service) : '';
			$security_features = isset($property_listing) && new_strlen($property_listing->security_features) > 0 ? new_explode(',', $property_listing->security_features) : '';
			$accessibility_features = isset($property_listing) && new_strlen($property_listing->accessibility_features) > 0 ? new_explode(',', $property_listing->accessibility_features) : '';
			$floor_covering = isset($property_listing) && new_strlen($property_listing->floor_covering) > 0 ? new_explode(',', $property_listing->floor_covering) : '';
			$ceiling_type = isset($property_listing) && new_strlen($property_listing->ceiling_type) > 0 ? new_explode(',', $property_listing->ceiling_type) : '';
			$window_features = isset($property_listing) && new_strlen($property_listing->window_Features) > 0 ? new_explode(',', $property_listing->window_Features) : '';
			$realtor_information = isset($property_listing) && new_strlen($property_listing->realtor_information) > 0 ? new_explode(',', $property_listing->realtor_information) : '';
			$realtor_information_confidential = isset($property_listing) && new_strlen($property_listing->realtor_information_confidential) > 0 ? new_explode(',', $property_listing->realtor_information_confidential) : '';
			$disclosures = isset($property_listing) && new_strlen($property_listing->disclosures) > 0 ? new_explode(',', $property_listing->disclosures) : '';
			$possession = isset($property_listing) && new_strlen($property_listing->possession) > 0 ? new_explode(',', $property_listing->possession) : '';
			$listing_privacy = isset($property_listing) ? $property_listing->listing_privacy : '';
			$group = isset($property_listing) ? $property_listing->group_id : '';
			$school = isset($property_listing) && new_strlen($property_listing->school) > 0 ? new_explode(',', $property_listing->school) : '';
			$landmark = isset($property_listing) && new_strlen($property_listing->landmarks) > 0 ? new_explode(',', $property_listing->landmarks) : '';
			$hopspital = isset($property_listing) && new_strlen($property_listing->hopspital) > 0 ? new_explode(',', $property_listing->hopspital) : '';

			$finishing = isset($property_listing) ? $property_listing->finishing : '';
			$furnished = isset($property_listing) ? $property_listing->furnished : '';
			$commission = isset($property_listing) ? $property_listing->commission : '';
			$kitchen = isset($property_listing) ? $property_listing->kitchen : '';
			$hydro_included = isset($property_listing) ? $property_listing->hydro_included : '';
			$water_included = isset($property_listing) ? $property_listing->water_included : '';
			$gas_included = isset($property_listing) ? $property_listing->gas_included : '';
			$long_description = isset($property_listing) ? $property_listing->long_description : '';
			$reservation_payment = isset($property_listing) ? $property_listing->reservation_payment : '';
			$maintenance_fee = isset($property_listing) ? $property_listing->maintenance_fee : '';
			$contract_payment = isset($property_listing) ? $property_listing->contract_payment : '';

			$pool_features = isset($property_listing) && new_strlen($property_listing->pool_features) > 0 ? new_explode(',', $property_listing->pool_features) : '';
			$spa = isset($property_listing) && new_strlen($property_listing->spa) > 0 ? new_explode(',', $property_listing->spa) : '';
			$spa_features = isset($property_listing) && new_strlen($property_listing->spa_features) > 0 ? new_explode(',', $property_listing->spa_features) : '';
			$front_exposure = isset($property_listing) && new_strlen($property_listing->front_exposure) > 0 ? new_explode(',', $property_listing->front_exposure) : '';
			$easements = isset($property_listing) && new_strlen($property_listing->easements) > 0 ? new_explode(',', $property_listing->easements) : '';
			$road_frontage = isset($property_listing) && new_strlen($property_listing->road_frontage) > 0 ? new_explode(',', $property_listing->road_frontage) : '';
			$road_surface_type = isset($property_listing) && new_strlen($property_listing->road_surface_type) > 0 ? new_explode(',', $property_listing->road_surface_type) : '';
			$road_responsibility = isset($property_listing) && new_strlen($property_listing->road_responsibility) > 0 ? new_explode(',', $property_listing->road_responsibility) : '';
			$signage = isset($property_listing) && new_strlen($property_listing->signage) > 0 ? new_explode(',', $property_listing->signage) : '';
			$adjoining_property = isset($property_listing) && new_strlen($property_listing->adjoining_property) > 0 ? new_explode(',', $property_listing->adjoining_property) : '';
			$other_structures = isset($property_listing) && new_strlen($property_listing->other_structures) > 0 ? new_explode(',', $property_listing->other_structures) : '';
			$other_equipment = isset($property_listing) && new_strlen($property_listing->other_equipment) > 0 ? new_explode(',', $property_listing->other_equipment) : '';
			$vegetation = isset($property_listing) && new_strlen($property_listing->vegetation) > 0 ? new_explode(',', $property_listing->vegetation) : '';
			$lot_features = isset($property_listing) && new_strlen($property_listing->lot_features) > 0 ? new_explode(',', $property_listing->lot_features) : '';
			$exterior_construction = isset($property_listing) && new_strlen($property_listing->exterior_construction) > 0 ? new_explode(',', $property_listing->exterior_construction) : '';
			$roof = isset($property_listing) && new_strlen($property_listing->roof) > 0 ? new_explode(',', $property_listing->roof) : '';
			$building_features = isset($property_listing) && new_strlen($property_listing->building_features) > 0 ? new_explode(',', $property_listing->building_features) : '';
			$garage_parking_features = isset($property_listing) && new_strlen($property_listing->garage_parking_features) > 0 ? new_explode(',', $property_listing->garage_parking_features) : '';
			$foundation = isset($property_listing) && new_strlen($property_listing->foundation) > 0 ? new_explode(',', $property_listing->foundation) : '';
			$basement = isset($property_listing) && new_strlen($property_listing->basement) > 0 ? new_explode(',', $property_listing->basement) : '';
			$balcony = isset($property_listing) && new_strlen($property_listing->balcony) > 0 ? new_explode(',', $property_listing->balcony) : '';
			$lift = isset($property_listing) && new_strlen($property_listing->lift) > 0 ? new_explode(',', $property_listing->lift) : '';
			$grill = isset($property_listing) && new_strlen($property_listing->grill) > 0 ? new_explode(',', $property_listing->grill) : '';
			
			$parking = isset($property_listing) && new_strlen($property_listing->parking) > 0 ? new_explode(',', $property_listing->parking) : '';
			$private_pool = isset($property_listing) ? $property_listing->private_pool : '';
			$pool_dimensions = isset($property_listing) ? $property_listing->pool_dimensions : '';
			$door_height = isset($property_listing) ? $property_listing->door_height : '';
			$door_width = isset($property_listing) ? $property_listing->door_width : '';
			$eaves_height = isset($property_listing) ? $property_listing->eaves_height : '';
			$road_frontage_feet = isset($property_listing) ? $property_listing->road_frontage_feet : '';
			$garage_door_height = isset($property_listing) ? $property_listing->garage_door_height : '';
			$Garden_SqM = isset($property_listing) ? $property_listing->Garden_SqM : '';
			$Front_Yard_SqM = isset($property_listing) ? $property_listing->Front_Yard_SqM : '';

			?>
			<div class="row">
				<div class="col-md-12">
					<h4 class="tw-font-semibold"><?php echo _l('real_listing_information'); ?></h4>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					<?php echo render_input( 'commodity_code', _l('real_property_code'), $commodity_code, 'text', ['readonly' => true]); ?>
				</div>
				<div class="col-md-9">
					<?php echo render_input('description', 'real_property_name', $description); ?>
				</div>
				<div class="col-md-3 hide">
					<label for="">
						<i class="fa fa-question-circle i_tooltip" data-toggle="tooltip" title="" data-original-title="<?php echo _l('real_tooltip_'.'listing_privacy'); ?>"></i> <?php echo _l('real_listing_privacy'); ?>
					</label>
					<div class="clearfix mtop5">
						<div class="radio radio-primary radio-inline pull-left ">
							<input type="radio" id="visible_everywhere" name="listing_privacy" value="visible_everywhere" <?php echo (($listing_privacy == 'visible_everywhere') ? 'checked' : '') ?>>
							<label for="visible_everywhere"><?php echo _l('real_visible_everywhere'); ?></label>
						</div>
						<div class="radio radio-primary radio-inline pull-left ">
							<input type="radio" id="internal_for_company_users" name="listing_privacy" value="internal_for_company_users" <?php echo (($listing_privacy == 'internal_for_company_users' || $property_id == '') ? 'checked' : '') ?>>
							<label for="internal_for_company_users"><?php echo _l('real_internal_for_company_users'); ?></label>
						</div>

					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-3">
					<?php echo render_select('group_id', $groups, ['id', 'name'], _l('real_group').'<a href="#" onclick="add_group_form_manage(); return false;" class=" pull-right display-block">'. _l('real_create_new').'</a>', $group, ['data-live-search' => true], [], '', ''); ?>
				</div>
				<div class="col-md-3">
					<?php echo render_select('listing_type', $rel_listing_type, ['name', 'label'], _l('real_listing_type'), $listing_type); ?>
				</div>
				<div class="col-md-3">
					<?php echo render_select('property_style', $rel_property_style, ['name', 'label'], _l('real_property_style'), $property_style, ['data-live-search' => true], [], 'property_style', ''); ?>
				</div>

				<div class="col-md-3">
					<?php 
					$rel_property_listing_status = rel_property_listing_status();
					?>
					<?php
					if(!isset($property_listing)){ 
						foreach ($rel_property_listing_status as $key => $property_listing_status) {
							if($property_listing_status['id'] == 'new' ||$property_listing_status['id'] == 'sold' ||$property_listing_status['id'] == 'expired'){
								unset($rel_property_listing_status[$key]);
							}
						}
					}else{ 
						foreach ($rel_property_listing_status as $key => $property_listing_status) {
							if($status == 'new'){
								if($property_listing_status['id'] == 'sold' ||$property_listing_status['id'] == 'expired'){
									unset($rel_property_listing_status[$key]);
								}
							}elseif($status == 'sold'){
								if($property_listing_status['id'] == 'new' ||$property_listing_status['id'] == 'expired'){
									unset($rel_property_listing_status[$key]);
								}
							}elseif($status == 'expired'){
								if($property_listing_status['id'] == 'new' ||$property_listing_status['id'] == 'sold'){
									unset($rel_property_listing_status[$key]);
								}
							}

						}
					}
					?>

					<?php echo render_select('status', $rel_property_listing_status, ['id', 'name'], _l('real_status'), $status, ['data-live-search' => true], [], '', '', false); ?>
				</div>

			</div>

			<div class="row">
				<div class="col-md-3">
					<div class="row">

						<div class="col-md-6">
							<?php echo render_select('transaction_type', $rel_transaction_type, ['name', 'label'], _l('real_transaction_type'), $transaction_type, [], [], '' , '', false ); ?>
						</div>
						<div class="show_sell_price <?php echo html_entity_decode($show_sell_price); ?>">
							<div class="col-md-6">
								<?php echo render_input( 'rate', _l('real_list_price'), $rate, 'number', ['step' => 'any', 'min' => 0]); ?>
							</div>
						</div>
						<div class="show_rental_price <?php echo html_entity_decode($show_rental_price); ?>">
							<div class="col-md-6">
								<?php echo render_input( 'rent_price', _l('real_rental_price'), $rent_price, 'number', ['step' => 'any', 'min' => 0]); ?>
							</div>
						</div>
					</div>
				</div>

				<div class="show_rental_price <?php echo html_entity_decode($show_rental_price); ?>">
					<div class="col-md-3">
						<div class="row">
							<div class="col-md-6 hide">
								<?php $rental_value = (isset($property_listing) && $property_listing->transaction_type == "Rent" ? $property_listing->rental_value : 1); ?>
								<?php echo render_input('rental_value', 'real_rental_value', $rental_value, 'number', ['min' => 1]); ?>
							</div>
							<div class="col-md-12">
								<label><?php echo _l('real_rental_type'); ?></label>
								<select name="rental_type" id="rental_type" class="selectpicker"
								data-width="100%"
								data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
								<option value="day" <?php if (isset($property_listing) && $property_listing->rental_type == 'day') {
									echo 'selected';
								} ?>><?php echo _l('invoice_recurring_days'); ?></option>
								<option value="week" <?php if (isset($property_listing) && $property_listing->rental_type == 'week') {
									echo 'selected';
								} ?>><?php echo _l('invoice_recurring_weeks'); ?></option>
								<option value="month" <?php if (isset($property_listing) && $property_listing->rental_type == 'month') {
									echo 'selected';
								} ?>><?php echo _l('invoice_recurring_months'); ?></option>
								<option value="year" <?php if (isset($property_listing) && $property_listing->rental_type == 'year') {
									echo 'selected';
								} ?>><?php echo _l('invoice_recurring_years'); ?></option>
							</select>
						</div>
					</div>
				</div>

			</div>

			<div class="col-md-3">
				<?php echo render_input( 'use_code', _l('real_use_code'), $use_code, 'text'); ?>
			</div>

			<div class="col-md-3">
				<?php echo render_select('property_condition', $rel_property_condition, ['name', 'label'], _l('real_property_condition'), $property_condition, ['data-live-search' => true], [], '', ''); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3">
				<div class="row">

					<div class="col-md-6">
						<?php echo render_select('new_construction', $rel_spa, ['name', 'label'], _l('real_new_construction'), $new_construction, ['data-live-search' => true], [], '', ''); ?>
					</div>
					<div class="col-md-6">
						<?php echo render_input( 'year_built', _l('real_year_built'), $year_built, 'number', ['step' => 1]); ?>
					</div>
				</div>
			</div>

			<div class="col-md-3">
				<?php echo render_date_input( 'proj_completion_date', _l('real_proj_completion_date'), $proj_completion_date); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'lot_size_acres', _l('rel_lot_size_acres'), $lot_size_acres, 'number', ['step' => 'any']); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'total_of_floors', _l('real_total_of_floors'), $total_of_floors, 'number', ['step' => '1', 'min' => 0]); ?>
			</div>

		</div>

		<div class="row">
			
			<div class="col-md-3">
				<div class="form-group">
					<label for="energy_efficiency"> <?php echo _l('real_energy_efficiency'); ?></label>
					<div class="input-group">
						<input type="number" step="any" min="0" class="form-control" name="energy_efficiency" value="<?php echo new_html_entity_decode($energy_efficiency); ?>">
						<div class="input-group-addon">
							<div class="dropdown">
								<span class="discount-type-selected">
									kWh EP / m2, year
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="form-group">
					<label for="gas_emission"> <?php echo _l('real_gas_emission'); ?></label>
					<div class="input-group">
						<input type="number" step="any" min="0" class="form-control" name="gas_emission" value="<?php echo new_html_entity_decode($gas_emission); ?>">
						<div class="input-group-addon">
							<div class="dropdown">
								<span class="discount-type-selected">
									kg CO2 / m², year
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-md-3">
				<div class="form-group">
					<label for="egenry_efficient"> <?php echo _l('real_egenry_efficient'); ?></label>
					<div class="input-group">
						<input type="number" step="any" min="0" class="form-control" name="egenry_efficient" value="<?php echo new_html_entity_decode($egenry_efficient); ?>">
						<div class="input-group-addon">
							<div class="dropdown">
								<span class="discount-type-selected">
									kWh EP / m2, year
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_address'); ?></h4>
			</div>
			<div class="col-md-12">
				<div class="row">
					<div class="col-md-6">
						<?php echo render_input( 'unit_number', _l('real_building'), $unit_number, 'text'); ?>
					</div>
				</div>
			</div>

			<div class="col-md-2">
				<?php echo render_input( 'street_number', _l('real_street_number'), $street_number, 'number'); ?>
			</div>
			<div class="col-md-6">
				<?php echo render_input( 'street_name', _l('real_street_name'), $street_name); ?>
			</div>
			<div class="col-md-2 hide">
				<?php echo render_select('street_dir_pre', $rel_street_dir_pre, ['name', 'label'], _l('real_street_dir_pre'), $street_dir_pre, [], [], '', ''); ?>
			</div>
			<div class="col-md-2">
				<?php echo render_select('street_type', $rel_street_type, ['name', 'label'], _l('real_street_type'), $street_type, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-2">
				<?php echo render_input( 'street_dir_pos', _l('real_street_dir_pos'), $street_dir_pos, 'number'); ?>
			</div>

			<div class="col-md-12">
				<div class="row">
					
					<div class="col-md-4">
						<?php echo render_input( 'city', _l('real_city'), $city); ?>
					</div>
					<div class="col-md-2">
						<?php echo render_input( 'state', _l('real_state'), $state); ?>
					</div>
					<div class="col-md-2">
						<?php echo render_input( 'zip', _l('real_zip'), $zip, 'text'); ?>

					</div>
					<div class="col-md-2">
						<?php echo render_input( 'zip_4', _l('real_zip_4'), $zip_4, 'text'); ?>
					</div>
					<div class="col-md-2">
						<?php echo render_select( 'country',$countries,array( 'country_id',array( 'short_name')), _l('real_country'),$country,array('data-none-selected-text'=>_l('dropdown_non_selected_tex'))); ?>

					</div>											
				</div>													
			</div>													
		</div>
		<div class="row">
			<div class="col-md-12 hide">
				<a href="https://www.latlong.net/" target="_blank"><?php echo _l('real_get_latitude_longitude'); ?></a>
			</div>
			<div class="col-md-4">
				<?php echo render_input( 'latitude', _l('real_latitude'), $latitude, 'text'); ?>
			</div>
			<div class="col-md-4">
				<?php echo render_input( 'longitude', _l('real_longitude'), $longitude, 'text'); ?>
			</div>
			<div class="col-md-3">
				<br>
				<button type="button" class="btn btn-primary mtop4" onclick="select_on_map()">
					<i class="fa fa-map"></i> <?php echo _l('real_select_on_map'); ?>
				</button>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_nearby_schools_child_care'); ?></h4>
			</div>

			<div class="col-md-4">
				<?php echo render_select('hopspital', $hopspitals, ['id', 'name'], _l('real_nearest_hopspitals').'<a href="#" onclick="add_hopspital_form_manage(); return false;" class=" pull-right display-block">'. _l('real_create_new').'</a>', $hopspital, ['data-live-search' => true,  'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-4">
				<?php echo render_select('school', $schools, ['id', 'name'], _l('real_nearest_schools').'<a href="#" onclick="add_school_form_manage(); return false;" class=" pull-right display-block">'. _l('real_create_new').'</a>', $school, ['data-live-search' => true,  'multiple' => true], [], '', '', false); ?>
			</div>

			<div class="col-md-4">
				<?php echo render_select('landmarks', $landmarks, ['id', 'name'], _l('real_nearest_landmarks').'<a href="#" onclick="add_landmark_form_manage(); return false;" class=" pull-right display-block">'. _l('real_create_new').'</a>', $landmark, ['data-live-search' => true,  'multiple' => true], [], '', '', false); ?>
			</div>
		</div>
		
		<div class="row">
			<div class="col-md-12">
				<?php echo render_textarea('long_description', _l('real_description'), $long_description, [], [], '', 'tinymce'); ?>

			</div>
		</div>


	</div>
	<div role="tabpanel" class="tab-pane payment-item" id="re_finance">

		<div class="row">
			<div class="col-md-3">
				<?php echo render_input( 'reservation_payment', _l('real_reservation_payment'), $reservation_payment, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'maintenance_fee', _l('real_maintenance_fee'), $maintenance_fee, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'contract_payment', _l('real_contract_payment'), $contract_payment, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
		</div>
		<div class="row hide">

			<div class="col-md-3">
				<?php echo render_input( 'operating_expenses', _l('real_operating_expenses'), $operating_expenses, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'net_operating_income', _l('real_net_operating_income'), $net_operating_income, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('net_operating_income_type', $rel_net_operating_income_type, ['name', 'label'], _l('real_net_operating_income_type'), $net_operating_income_type, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('sale_includes', $rel_sale_includes, ['name', 'label'], _l('real_sale_includes'), $sale_includes, ['multiple' => true], [], '', '', false); ?>
			</div>

			<div class="col-md-3">
				<?php echo render_input( 'annual_expenses', _l('real_annual_expenses'), $annual_expenses, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'annual_TTL_schedule_income', _l('real_annual_TTL_schedule_income'), $annual_TTL_schedule_income, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('annual_income_type', $rel_net_operating_income_type, ['name', 'label'], _l('real_annual_income_type'), $annual_income_type, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('number_of_tenants', $rel_number_of_tenants, ['name', 'label'], _l('real_number_of_tenants'), $number_of_tenants, ['multiple' => true], [], '', '', false); ?>
			</div>
		</div>

	</div>

	<div role="tabpanel" class="tab-pane" id="rel_pool_exterior">
		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('rel_pool'); ?></h4>
			</div>

			<div class="col-md-3">
				<?php echo render_select('private_pool', $rel_spa, ['name', 'label'], _l('rel_private_pool'), $private_pool, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'pool_dimensions', _l('rel_pool_dimensions'), $pool_dimensions, 'text'); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('pool_features', $rel_pool_features, ['name', 'label'], _l('rel_pool_features'), $pool_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3">
				<?php echo render_select('spa', $rel_spa, ['name', 'label'], _l('rel_spa'), $spa, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('spa_features', $rel_spa_features, ['name', 'label'], _l('rel_spa_features'), $spa_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
		</div>

		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('rel_exterior_information'); ?></h4>
			</div>
		</div>
		<div class="row">
			<div class="col-md-3">
				<?php echo render_input( 'door_height', _l('rel_door_height'), $door_height, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'door_width', _l('rel_door_width'), $door_width, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'eaves_height', _l('rel_eaves_height'), $eaves_height, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'road_frontage_feet', _l('rel_road_frontage_feet'), $road_frontage_feet, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'garage', _l('real_garage'), $garage, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'garage_door_height', _l('rel_garage_door_height'), $garage_door_height, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
		</div>

		<div class="row">

			<div class="col-md-3">
				<?php echo render_select('front_exposure', $rel_front_exposure, ['name', 'label'], _l('rel_front_exposure'), $front_exposure, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('easements', $rel_easements, ['name', 'label'], _l('rel_easements'), $easements, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('road_frontage', $rel_road_frontage, ['name', 'label'], _l('rel_road_frontage'), $road_frontage, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('road_surface_type', $rel_road_surface_type, ['name', 'label'], _l('rel_road_surface_type'), $road_surface_type, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('road_responsibility', $rel_road_responsibility, ['name', 'label'], _l('rel_road_responsibility'), $road_responsibility, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('signage', $rel_signage, ['name', 'label'], _l('rel_signage'), $signage, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('adjoining_property', $rel_adjoining_property, ['name', 'label'], _l('rel_adjoining_property'), $adjoining_property, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('other_structures', $rel_other_structures, ['name', 'label'], _l('rel_other_structures'), $other_structures, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('other_equipment', $rel_other_equipment, ['name', 'label'], _l('rel_other_equipment'), $other_equipment, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('vegetation', $rel_vegetation, ['name', 'label'], _l('rel_vegetation'), $vegetation, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('lot_features', $rel_lot_features, ['name', 'label'], _l('rel_lot_features'), $lot_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('exterior_construction', $rel_exterior_construction, ['name', 'label'], _l('rel_exterior_construction'), $exterior_construction, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('roof', $rel_roof, ['name', 'label'], _l('rel_roof'), $roof, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('building_features', $rel_building_features, ['name', 'label'], _l('rel_building_features'), $building_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('garage_parking_features', $rel_garage_parking_features, ['name', 'label'], _l('rel_garage_parking_features'), $garage_parking_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('foundation', $rel_foundation, ['name', 'label'], _l('rel_foundation'), $foundation, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('basement', $rel_basement, ['name', 'label'], _l('rel_basement'), $basement, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'Garden_SqM', _l('real_Garden_SqM'), $Garden_SqM, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'Front_Yard_SqM', _l('real_Front_Yard_SqM'), $Front_Yard_SqM, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>

		</div>

		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_outdoor_amenities'); ?></h4>
			</div>
			<div class="col-md-3">
				<?php echo render_select('balcony', $rel_balcony, ['name', 'label'], _l('real_balcony'), $balcony, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('lift', $rel_spa, ['name', 'label'], _l('real_lift'), $lift, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('grill', $rel_spa, ['name', 'label'], _l('real_grill'), $grill, ['data-live-search' => true], [], '', ''); ?>
			</div>
			
			<div class="col-md-3">
				<?php echo render_select('parking', $rel_balcony, ['name', 'label'], _l('real_parking'), $parking, ['data-live-search' => true], [], '', ''); ?>
			</div>

		</div>

	</div>


	<div role="tabpanel" class="tab-pane" id="rel_interior">
		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_interior_information'); ?></h4>
			</div>

			<div class="col-md-3">
				<?php echo render_input( 'beds', _l('real_Beds'), $beds, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'full_baths', _l('real_full_baths'), $full_baths, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'half_baths', _l('real_half_baths'), $half_baths, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('kitchen', $rel_balcony, ['name', 'label'], _l('real_kitchen'), $kitchen, ['data-live-search' => true], [], '', '', true); ?>
			</div>
		</div>
		<div class="row">

			<div class="col-md-3">
				<?php echo render_input( 'sqFt_heated', _l('real_sqFt_heated'), $sqFt_heated, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('sqFt_heated_source', $rel_sqFt_heated_source, ['name', 'label'], _l('real_sqFt_heated_source'), $sqFt_heated_source, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_input( 'sqFt_total', _l('real_sqFt_total'), $sqFt_total, 'number', ['step' => 'any', 'min' => 0]); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('SqFt_total_source', $rel_sqFt_heated_source, ['name', 'label'], _l('real_SqFt_total_source'), $SqFt_total_source, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('fireplace', $rel_spa, ['name', 'label'], _l('real_fireplace'), $fireplace, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('fireplace_description', $rel_fireplace_description, ['name', 'label'], _l('real_fireplace_description'), $fireplace_description, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>



		</div>

		<div class="row">
			<div class="col-md-3">
				<?php echo render_select('appliances_included', $rel_appliances_included, ['name', 'label'], _l('real_appliances_included'), $appliances_included, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('utilities', $rel_utilities, ['name', 'label'], _l('real_utilities'), $utilities, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('sewer', $rel_sewer, ['name', 'label'], _l('real_sewer'), $sewer, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('water', $rel_water, ['name', 'label'], _l('real_water'), $water, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('heating_and_fuel', $rel_heating_and_fuel, ['name', 'label'], _l('real_heating_and_fuel'), $heating_and_fuel, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('air_conditioning', $rel_air_conditioning, ['name', 'label'], _l('real_air_conditioning'), $air_conditioning, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('electrical_Service', $rel_electrical_Service, ['name', 'label'], _l('real_electrical_Service'), $electrical_Service, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('security_features', $rel_security_features, ['name', 'label'], _l('real_security_features'), $security_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('accessibility_features', $rel_accessibility_features, ['name', 'label'], _l('real_accessibility_features'), $accessibility_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('floor_covering', $rel_floor_covering, ['name', 'label'], _l('real_floor_covering'), $floor_covering, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('ceiling_type', $rel_ceiling_type, ['name', 'label'], _l('real_ceiling_type'), $ceiling_type, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('window_Features', $rel_window_features, ['name', 'label'], _l('real_window_features'), $window_features, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>

		</div>

		<div class="row">
			<div class="col-md-3">
				<?php echo render_select('furnished', $rel_furnished, ['name', 'label'], _l('real_furnished'), $furnished, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('finishing', $rel_finishing, ['name', 'label'], _l('real_finishing'), $finishing, ['data-live-search' => true], [], '', ''); ?>
			</div>

			<div class="col-md-3">
				<?php echo render_select('hydro_included', $rel_spa, ['name', 'label'], _l('real_hydro_included'), $hydro_included, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('water_included', $rel_spa, ['name', 'label'], _l('real_water_included'), $water_included, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('gas_included', $rel_spa, ['name', 'label'], _l('real_gas_included'), $gas_included, ['data-live-search' => true], [], '', ''); ?>
			</div>

		</div>

		<div class="row show_rental_price <?php echo html_entity_decode($show_rental_price); ?>">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_indoor_amenities'); ?></h4>
			</div>
			<div class="col-md-3">
				<?php echo render_select('cable_TV', $rel_spa, ['name', 'label'], _l('real_cable_TV'), $cable_TV, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('computer', $rel_spa, ['name', 'label'], _l('real_computer'), $computer, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('heating', $rel_spa, ['name', 'label'], _l('real_heating'), $heating, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-3">
				<?php echo render_select('internet', $rel_spa, ['name', 'label'], _l('real_internet'), $internet, ['data-live-search' => true], [], '', ''); ?>
			</div>

		</div>

	</div>
	<div role="tabpanel" class="tab-pane invoice-item" id="rel_rooms">
		<div class="row">
			<div class="col-md-12">
				<div class="table-responsive s_table ">
					<table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
						<thead>
							<tr>
								<th></th>
								<th width="20%" align="left">  <?php echo _l('real_room_type'); ?></th>
								<th width="20%" align="left"> <?php echo _l('real_rooms_level'); ?></th>
								<th width="40%" align="right"> <?php echo _l('real_room_benefits'); ?></th>
								<th width="10%" align="right" class="qty"> <?php echo _l('real_room_demension_width'); ?></th>
								<th width="10%" align="right"> <?php echo _l('real_room_demension_lenght'); ?></th>
								<th align="center"><i class="fa fa-cog"></i></th>
								<th align="center"></th>
							</tr>
						</thead>
						<tbody>
							<?php echo html_entity_decode($room_templates); ?>
						</tbody>
					</table>
				</div>

			</div>
		</div>
	</div>

	<div role="tabpanel" class="tab-pane" id="rel_owner">
		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_owner'); ?></h4>
			</div>
			<div class="col-md-12">
				<?php echo render_select('property_owner_id', $property_owners, ['id', 'name'], _l('real_property_owner'), $property_owner_id, ['data-live-search' => true], [], '', ''); ?>
			</div>
			<div class="col-md-4">
				<?php echo render_input( 'owner_name', _l('real_owner_name'), $owner_name); ?>
			</div>
			<div class="col-md-2">
				<?php echo render_input( 'owner_phone', _l('real_owner_phone'), $owner_phone); ?>
			</div>
			<div class="col-md-2">
				<?php echo render_input( 'owner_email', _l('real_owner_email'), $owner_email); ?>
			</div>
			<div class="col-md-4">
				<?php echo render_select('ownership', $rel_ownership, ['name', 'label'], _l('real_ownership'), $ownership, ['data-live-search' => true], [], '', ''); ?>
			</div>

		</div>
	</div>

	<div role="tabpanel" class="tab-pane" id="rel_realtor">
		<div class="row">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_business_broker_information'); ?></h4>
			</div>

			<div class="col-md-3 hide">
				<?php echo render_select('realtor_information', $rel_realtor_information, ['name', 'label'], _l('real_realtor_information'), $realtor_information, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3 hide">
				<?php echo render_select('realtor_information_confidential', $rel_realtor_information_confidential, ['name', 'label'], _l('real_realtor_information_confidential'), $realtor_information_confidential, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3 hide">
				<?php echo render_select('disclosures', $rel_disclosures, ['name', 'label'], _l('real_disclosures'), $disclosures, ['data-live-search' => true, 'multiple' => true], [], '', '', false); ?>
			</div>
			<div class="col-md-3 hide">
				<?php echo render_select('possession', $rel_possession, ['name', 'label'], _l('real_possession'), $possession, ['data-live-search' => true], [], '', '', true); ?>
			</div>
			<div class="col-md-6">
				<?php echo render_input( 'commission', _l('real_commission').'%', $commission, 'text',); ?>
			</div>
		</div>
		<?php if(isset($property_listing) && !is_broker_logged_in() ){ ?>
			<div class="row mbot15">
				<div class="col-md-9">
					<h4 class="h4-color no-margin"><?php echo _l('real_assign_property_to_bussines_broker'); ?></h4>
				</div>
				<?php if(has_permission('real_request_broker', '', 'create') || check_assign_property_to_broker($property_listing->id)){ ?>
					<div class="col-md-3">
						<div class="_buttons">
							<a href="#" onclick="new_request_broker(); return false;" class="btn btn-primary pull-right display-block">
								<?php echo _l('real_assign_property_to_bussines_broker'); ?>
							</a>
						</div>
					</div>
				<?php } ?>
			</div>
			<?php $this->load->view('companies/property_listings/request_brokerages/table_html'); ?>

		<?php } ?>
	</div>
	<div role="tabpanel" class="tab-pane" id="rel_status">
		<div class="row">

			<div class="col-md-2">
				<h4 class="tw-font-semibold"><?php echo _l('real_main_photo'); ?></h4>
			</div>
			<div class="col-md-10">
				<h4 class="tw-font-semibold"><?php echo _l('real_other_picture'); ?></h4>
			</div>

			<div class="col-md-2">
				<div id="dropzoneDragArea2" class="dz-default dz-message">
					<span><?php echo _l('real_attach_image'); ?></span>
				</div>
				<div class="dropzone-previews2">

					<div id="images_old_preview">
						<?php if(isset($property_listing->primary_image) && $property_listing->primary_image != ''){ ?>


							<div class="dz-preview dz-image-preview image_old <?php echo new_html_entity_decode($property_listing->id) ?>">
								<div class="dz-image">
									<?php if(file_exists(PROPERTY_MAIN_IMAGE_UPLOAD . $property_listing->id . '/' . $property_listing->primary_image)){ ?>

										<img class="dz-image" src="<?php echo site_url('modules/realestate/uploads/main_images/' . $property_listing->id . '/' . $property_listing->primary_image) . '" alt="' . $property_listing->primary_image ?>" >

									<?php } ?>

									<div class="dz-error-mark">
										<a class="dz-remove" data-dz-remove>Remove file</a>
									</div>
									<div class="remove_file">
										<a href="#" class="text-danger" onclick="delete_property_listing_attachment(this,<?php echo new_html_entity_decode($property_listing->id); ?>); return false;"><i class="fa fa fa-times"></i></a>
									</div>

								</div>
							</div>

						<?php } ?>
					</div>
				</div>
			</div>


			<div class="col-md-10">
				<div id="dropzoneDragArea" class="dz-default dz-message">
					<span><?php echo _l('real_attach_image'); ?></span>
				</div>
				<div class="dropzone-previews"></div>

				<div id="images_old_preview">

					<?php if( isset($product_attachments) && count($product_attachments) > 0){ ?>
						<?php foreach ($product_attachments as $product_attachment) { ?>
							<?php $rel_type = 'real_estate' ;?>

							<?php if($rel_type != ''){ ?>
								<div class="dz-preview dz-image-preview image_old <?php echo new_html_entity_decode($product_attachment['id']) ?>">
									<div class="dz-image">
										<?php if(file_exists(PROPERTY_UPLOAD . $product_attachment['rel_id'] . '/' . $product_attachment['file_name'])){ ?>

											<img class="dz-image" src="<?php echo site_url('modules/realestate/uploads/property_listings/' . $product_attachment['rel_id'] . '/' . $product_attachment['file_name']) . '" alt="' . $product_attachment['file_name'] ?>" >

										<?php }elseif(file_exists('modules/warehouse/uploads/item_img/' . $product_attachment["rel_id"] . '/' . $product_attachment["file_name"])){ ?>

											<img class="dz-image" src="<?php echo site_url('modules/warehouse/uploads/item_img/' . $product_attachment['rel_id'] . '/' . $product_attachment['file_name']) . '" alt="' . $product_attachment['file_name'] ?>" >

										<?php }elseif(file_exists('modules/purchase/uploads/item_img/'. $product_attachment["rel_id"] . '/' . $product_attachment["file_name"])){ ?>

											<img class="dz-image" src="<?php echo site_url('modules/purchase/uploads/item_img/' . $product_attachment['rel_id'] . '/' . $product_attachment['file_name']) . '" alt="' . $product_attachment['file_name'] ?>" >

										<?php } ?>
									</div>

									<div class="dz-error-mark">
										<a class="dz-remove" data-dz-remove>Remove file</a>
									</div>
									<div class="remove_file">
										<a href="#" class="text-danger" onclick="delete_property_listing_attachment(this,<?php echo new_html_entity_decode($product_attachment['id']); ?>); return false;"><i class="fa fa fa-times"></i></a>
									</div>
								</div>
							<?php } ?>

						<?php } ?>
					<?php } ?>
				</div>
			</div>
		</div>

		<div class="row mtop15">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_property_videos'); ?></h4>
			</div>
			<div class="col-md-12">
				<div id="dropzoneDragArea3" class="dz-default dz-message">
					<span><?php echo _l('real_property_attach_video'); ?></span>
				</div>
				<div class="dropzone-previews3"></div>
				<div id="images_old_preview">

					<?php if( isset($product_videos) && count($product_videos) > 0){ ?>
						<?php foreach ($product_videos as $product_video) { ?>

							<div class="dz-preview dz-image-preview image_old <?php echo new_html_entity_decode($product_video['id']) ?>">
								<div class="dz-image">
									<?php if(file_exists(PROPERTY_VIDEO_UPLOAD . $product_video['rel_id'] . '/' . $product_video['file_name'])){ ?>

										<video width="100%" height="100%" src="<?php echo site_url('download/preview_video?path='.protected_file_url_by_path(PROPERTY_VIDEO_UPLOAD . $product_video['rel_id'] . '/' . $product_video['file_name']).'&type='.$product_video['filetype']); ?>" controls>
											Your browser does not support the video tag.
										</video>
									<?php } ?>
								</div>

								<div class="dz-error-mark">
									<a class="dz-remove" data-dz-remove>Remove file</a>
								</div>
								<div class="remove_file">
									<a href="#" class="text-danger" onclick="delete_property_video_attachment(this,<?php echo new_html_entity_decode($product_video['id']); ?>); return false;"><i class="fa fa fa-times"></i></a>
								</div>
							</div>

						<?php } ?>
					<?php } ?>
				</div>
			</div>
		</div>

		<div class="row mtop15">
			<div class="col-md-12">
				<h4 class="tw-font-semibold"><?php echo _l('real_pdf_files'); ?></h4>
			</div>
			<div class="col-md-12">
				<div id="dropzoneDragArea1" class="dz-default dz-message">
					<span><?php echo _l('real_attach_pdf_files'); ?></span>
				</div>
				<div class="dropzone-previews1"></div>
			</div>
		</div>


		<?php if(isset($product_attachment_pdfs) && count($product_attachment_pdfs) > 0){ ?>
			<div class="row">
				<div class="col-md-12">
					<div id="contract_attachments" class="mtop30 ">

						<?php
						$data = '<div class="row" id="attachment_file">';
						foreach($product_attachment_pdfs as $attachment) {
							$data .= '<div class="col-md-6 pdf_attachment">';
							$href_url = site_url('modules/realestate/uploads/property_listing_pdfs/'.$attachment['rel_id'].'/'.$attachment['file_name']).'" download';
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
							if(is_admin() || has_permission('real_property', '', 'delete') ){
								$data .= '<a href="#" class="text-danger btn btn-sm" onclick="delete_listing_attachment_pdf_file(this,'.$attachment['id'].'); return false;"><i class="fa fa fa-times"></i></a>';
							}
							$data .= '</div>';
							$data .= '<div class="clearfix"></div><hr class="mtop1 mbot5">';
							$data .= '</div>';
						}
						$data .= '</div>';
						echo new_html_entity_decode($data);
						?>

						<!-- check if edit contract => display attachment file end-->

					</div>
					<div id="pdf_file_data"></div>
				</div>
			</div>
		<?php } ?>

	</div>
</div>
</div>