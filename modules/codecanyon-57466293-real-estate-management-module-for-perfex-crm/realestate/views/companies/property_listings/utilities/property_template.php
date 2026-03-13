<?php defined('BASEPATH') or exit('No direct script access allowed');?>
<?php 
$total_bath = (int)$property_listing->full_baths + (int)$property_listing->half_baths;
$base_currency_id = get_base_currency_id();

?>

<?php if(is_mobile()){  ?>

	<div class="row ">
	<div class="col-md-3">
		<div class="room-items property-detail">
			<div class="room-text !tw-px-0">
				<div class="room-details">
					<div class="room-title">
						<h4 class="tw-font-semibold"><?php echo new_html_entity_decode($property_listing->description); ?></h4>

						<p><?php echo new_html_entity_decode($property_listing->street_number.' '.$property_listing->street_dir_pre.' '.$property_listing->street_name.' '.$property_listing->city.' '.real_remove_underscore($property_listing->state).' '.get_country_name($property_listing->country)); ?></p>
						<a href="javascript:void(0)"><i class="fa-solid fa-location-dot mtop5"></i> <span><?php echo get_country_name($property_listing->country); ?></span></a>
						<a href="https://www.google.com/maps/search/?api=1&query=<?php echo html_entity_decode($property_listing->latitude) ?>,<?php echo html_entity_decode($property_listing->longitude) ?>" class="large-width" target="_blank"><i class="fa-solid fa-location-arrow mtop5"></i> <span><?php echo _l('real_show_on_map'); ?></span></a>
					</div>
				</div>

				<div class="room-features">
					<div class="room-info tw-flex tw-justify-between">
						<div class="size">
							<p><?php echo _l('real_lot_size'); ?></p>
							<img src="#" alt="">
							<i class="fa-solid fa-expand"></i>
							<span><?php echo new_html_entity_decode($property_listing->lot_size_acres ?? 0); ?> sqM</span>
						</div>
						<div class="beds">
							<p><?php echo _l('real_Beds'); ?></p>
							<i class="fa-solid fa-bed"></i>
							<span><?php echo new_html_entity_decode($property_listing->beds ?? 0); ?></span>

						</div>
						<div class="baths">
							<p><?php echo _l('real_baths'); ?></p>
							<i class="fa-solid fa-bath"></i>
							<span><?php echo new_html_entity_decode($total_bath); ?></span>

						</div>
						<div class="garage">
							<p><?php echo _l('real_garage'); ?></p>
							<i class="fa-solid fa-warehouse"></i>
							<span><?php echo new_html_entity_decode($property_listing->garage ?? 0); ?></span>
						</div>
					</div>
				</div>
				<div class="room-price">
					<?php if($property_listing->transaction_type == 'Sale'){ ?>
						<p><?php echo _l('real_for_sale'); ?></p>
						<span><?php echo app_format_money($property_listing->rate, $base_currency_id); ?></span>
					<?php }else{ ?>
						<p><?php echo _l('real_for_rent'); ?></p>
						<span><?php echo app_format_money($property_listing->rent_price, $base_currency_id).' / '.(int)$property_listing->rental_value.'('.$property_listing->rental_type.')'; ?></span>
					<?php } ?>
				</div>
				<a href="#" class="site-btn btn-line hide">View Property</a>
			</div>
		</div>
	</div>
	<div class="col-md-7">

		<?php
		if(isset($property_assets)){
			$folder = 'commodity_item_file';

			$large_img_list = '';
			$small_img_list = '';
			if(count($property_assets) > 0){
				$large_img_list .= '<div class="preview-pic tab-content">';
				$small_img_list .= '<div class=""><ul class="preview-thumbnail nav nav-tabs no-mbot tw-flex">';
				foreach ($property_assets as $kimg => $f) {

					if($f['type'] == 'main_image'){

						$src = $f['site_url'];
						$small_src = $f['site_url'];

					}else{
						$src = site_url(PROPERTY_UPLOAD_PATH.$f['rel_id'].'/'.$f['file_name']);
						$small_src = site_url(PROPERTY_UPLOAD_PATH.$f['rel_id'].'/'.$f['file_name']);
					}


					$large_img_list .= '<a href="'.$src.'" class="contain_image containt-image tab-pane '.($kimg == 0 ? 'active' : '').'" id="pic-'.$kimg.'" data-lightbox="roadtrip"><img class="w-100 img img-rounded img-thumbnail property-view" src="'.$src.'"></a>';
					if($kimg < 3){
						$small_img_list .= '<div data-target="#pic-'.$kimg.'" data-toggle="tab" aria-expanded="true"><img class="w-100 img img-rounded img-thumbnail property-thumbnail" src="'.$small_src.'"></div>';
					}elseif($kimg == 3){
						$remaining_images = count($product_attachments)-3;
						$small_img_list .= '<div data-target="#pic-'.$kimg.'" data-toggle="tab" aria-expanded="true" class="epl-gallery-item epl-gallery-item--desktop epl-gallery-item-4"><img class="w-100 img img-rounded img-thumbnail property-thumbnail" src="'.$small_src.'">
						<div class="epl-gallery-remaining epl-gallery-remaining--desktop"><div class="epl-gallery-remaining__symbol"> <span class="epl-gallery-remaining__symbol">+</span><span class="epl-gallery-remaining__value">'.$remaining_images.'</span></div></div>
						</div>';
					}else{
						$small_img_list .= '<div data-target="#pic-'.$kimg.'" data-toggle="tab" aria-expanded="true"><img class="w-100 img img-rounded img-thumbnail property-thumbnail hide" src="'.$small_src.'"></div>';
					}

				}
				$large_img_list .= '</div>';
				$small_img_list .= '</ul></div>';
				echo new_html_entity_decode($large_img_list);
			}
			?>
		<?php } ?>
	</div>
	<div class="col-md-1 tw-flex">
		<?php echo new_html_entity_decode($small_img_list); ?>
	</div>
	<div class="col-md-12 hide">
		<div role="tabpanel" class="tab-pane" id="rel_pool_exterior">
			<?php 
			if($property_listing->latitude != '' && $property_listing->longitude != ''){ ?>
				<input type="hidden" name="lat" value="<?php echo new_html_entity_decode($property_listing->latitude); ?>">
				<input type="hidden" name="lng" value="<?php echo new_html_entity_decode($property_listing->longitude); ?>">
				<div id="mapCanvas" class="img img-thumbnail" ></div>
			<?php } ?>
		</div>
	</div>
</div>

<?php }else{ ?>
<div class="row tw-flex tw-items-center">
	<div class="col-md-3">
		<div class="room-items property-detail">
			<div class="room-text">
				<div class="room-details">
					<div class="room-title">
						<h4 class="tw-font-semibold"><?php echo new_html_entity_decode($property_listing->description); ?></h4>

						<p><?php echo new_html_entity_decode($property_listing->street_number.' '.$property_listing->street_dir_pre.' '.$property_listing->street_name.' '.$property_listing->city.' '.real_remove_underscore($property_listing->state).' '.get_country_name($property_listing->country)); ?></p>
						<a href="javascript:void(0)"><i class="fa-solid fa-location-dot mtop5"></i> <span><?php echo get_country_name($property_listing->country); ?></span></a>
						<a href="https://www.google.com/maps/search/?api=1&query=<?php echo html_entity_decode($property_listing->latitude) ?>,<?php echo html_entity_decode($property_listing->longitude) ?>" class="large-width" target="_blank"><i class="fa-solid fa-location-arrow mtop5"></i> <span><?php echo _l('real_show_on_map'); ?></span></a>
					</div>
				</div>

				<div class="room-features">
					<div class="room-info tw-flex tw-justify-between">
						<div class="size">
							<p><?php echo _l('real_lot_size'); ?></p>
							<img src="#" alt="">
							<i class="fa-solid fa-expand"></i>
							<span><?php echo new_html_entity_decode($property_listing->lot_size_acres ?? 0); ?> sqM</span>
						</div>
						<div class="beds">
							<p><?php echo _l('real_Beds'); ?></p>
							<i class="fa-solid fa-bed"></i>
							<span><?php echo new_html_entity_decode($property_listing->beds ?? 0); ?></span>

						</div>
						<div class="baths">
							<p><?php echo _l('real_baths'); ?></p>
							<i class="fa-solid fa-bath"></i>
							<span><?php echo new_html_entity_decode($total_bath); ?></span>

						</div>
						<div class="garage">
							<p><?php echo _l('real_garage'); ?></p>
							<i class="fa-solid fa-warehouse"></i>
							<span><?php echo new_html_entity_decode($property_listing->garage ?? 0); ?></span>
						</div>
					</div>
				</div>
				<div class="room-price">
					<?php if($property_listing->transaction_type == 'Sale'){ ?>
						<p><?php echo _l('real_for_sale'); ?></p>
						<span><?php echo app_format_money($property_listing->rate, $base_currency_id); ?></span>
					<?php }else{ ?>
						<p><?php echo _l('real_for_rent'); ?></p>
						<span><?php echo app_format_money($property_listing->rent_price, $base_currency_id).' / '.(int)$property_listing->rental_value.'('.$property_listing->rental_type.')'; ?></span>
					<?php } ?>
				</div>
				<a href="#" class="site-btn btn-line hide">View Property</a>
			</div>
		</div>
	</div>
	<div class="col-md-7">

		<?php
		if(isset($property_assets)){
			$folder = 'commodity_item_file';

			$large_img_list = '';
			$small_img_list = '';
			if(count($property_assets) > 0){
				$large_img_list .= '<div class="preview-pic tab-content">';
				$small_img_list .= '<div class=""><ul class="preview-thumbnail nav nav-tabs no-mbot">';
				foreach ($property_assets as $kimg => $f) {

					if($f['type'] == 'main_image'){

						$src = $f['site_url'];
						$small_src = $f['site_url'];

					}else{
						$src = site_url(PROPERTY_UPLOAD_PATH.$f['rel_id'].'/'.$f['file_name']);
						$small_src = site_url(PROPERTY_UPLOAD_PATH.$f['rel_id'].'/'.$f['file_name']);
					}


					$large_img_list .= '<a href="'.$src.'" class="contain_image containt-image tab-pane '.($kimg == 0 ? 'active' : '').'" id="pic-'.$kimg.'" data-lightbox="roadtrip"><img class="w-100 img img-rounded img-thumbnail property-view" src="'.$src.'"></a>';
					if($kimg < 3){
						$small_img_list .= '<div data-target="#pic-'.$kimg.'" data-toggle="tab" aria-expanded="true"><img class="w-100 img img-rounded img-thumbnail property-thumbnail" src="'.$small_src.'"></div>';
					}elseif($kimg == 3){
						$remaining_images = count($product_attachments)-3;
						$small_img_list .= '<div data-target="#pic-'.$kimg.'" data-toggle="tab" aria-expanded="true" class="epl-gallery-item epl-gallery-item--desktop epl-gallery-item-4"><img class="w-100 img img-rounded img-thumbnail property-thumbnail" src="'.$small_src.'">
						<div class="epl-gallery-remaining epl-gallery-remaining--desktop"><div class="epl-gallery-remaining__symbol"> <span class="epl-gallery-remaining__symbol">+</span><span class="epl-gallery-remaining__value">'.$remaining_images.'</span></div></div>
						</div>';
					}else{
						$small_img_list .= '<div data-target="#pic-'.$kimg.'" data-toggle="tab" aria-expanded="true"><img class="w-100 img img-rounded img-thumbnail property-thumbnail hide" src="'.$small_src.'"></div>';
					}

				}
				$large_img_list .= '</div>';
				$small_img_list .= '</ul></div>';
				echo new_html_entity_decode($large_img_list);
			}
			?>
		<?php } ?>
	</div>
	<div class="col-md-2">
		<?php echo new_html_entity_decode($small_img_list); ?>
	</div>
	<div class="col-md-12 hide">
		<div role="tabpanel" class="tab-pane" id="rel_pool_exterior">
			<?php 
			if($property_listing->latitude != '' && $property_listing->longitude != ''){ ?>
				<input type="hidden" name="lat" value="<?php echo new_html_entity_decode($property_listing->latitude); ?>">
				<input type="hidden" name="lng" value="<?php echo new_html_entity_decode($property_listing->longitude); ?>">
				<div id="mapCanvas" class="img img-thumbnail" ></div>
			<?php } ?>
		</div>
	</div>
</div>
<?php } ?>

<div class="row">
	<div class="col-md-9">
		<div class="row tw-text-justify">
			<div class="col-md-12">
				<h4 class="tw-font-semibold">
					<?php echo _l('real_property_description'); ?>
				</h4>
				<p class=""><?php echo new_html_entity_decode($property_listing->long_description); ?></p>
			</div>
		</div>

		<div class="row">
			<div class="col-md-6 tw-text-justify">
				<h4 class="tw-font-semibold">
					<?php echo _l('real_property_summary'); ?>
				</h4>
				<div class="row">
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_listing_type'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->listing_type); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_property_condition'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->property_condition); ?></span>
						<?php if(is_staff_logged_in() || is_broker_logged_in()){ ?>
							<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_dom'); ?></div>
							<span><?php echo new_html_entity_decode($property_listing->dom); ?></span>
						<?php } ?>

					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_property_style'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->property_style); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_new_construction'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->new_construction); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_bedrooms'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->beds); ?></span>


					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_total_of_floors'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->total_of_floors); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_year_built'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->year_built); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_bathrooms'); ?></div>
						<span><?php echo new_html_entity_decode($total_bath); ?></span>

					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_sqFt_total'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->sqFt_total); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_proj_completion_date'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->proj_completion_date); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_garage'); ?></div>
						<span><?php echo new_html_entity_decode($property_listing->garage); ?></span>

					</div>
				</div>
			</div>
			<div class="col-md-6">

				<?php 
				if($property_listing->latitude != '' && $property_listing->longitude != ''){ ?>
					<input type="hidden" name="lat" value="<?php echo new_html_entity_decode($property_listing->latitude); ?>">
					<input type="hidden" name="lng" value="<?php echo new_html_entity_decode($property_listing->longitude); ?>">
					<div id="map" class="img img-thumbnail property_detail_map" ></div>

				<?php } ?>

			</div>
		</div>
		<div class="row">
			<div class="col-md-12 mtop10">
				<h4 class="tw-font-semibold">
					<?php echo _l('real_interior_features'); ?>
				</h4>
				<div class="clearfix"></div>
				<hr class="mtop5 mbot5">
				<div class="clearfix"></div>

				<div class="row">
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_appliances_included'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->appliances_included); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_heating_and_fuel'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->heating_and_fuel); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_accessibility_features'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->accessibility_features); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_furnished'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->furnished); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_gas_included'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->gas_included); ?></span>

					</div>

					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_utilities'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->utilities); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_air_conditioning'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->air_conditioning); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_floor_covering'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->floor_covering); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_finishing'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->finishing); ?></span>

					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_sewer'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->sewer); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_electrical_Service'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->electrical_Service); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_ceiling_type'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->ceiling_type); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_hydro_included'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->hydro_included); ?></span>


					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_water'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->water); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_security_features'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->security_features); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_water_included'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->water_included); ?></span>


					</div>
				</div>

			</div>

			<?php if($property_listing->transaction_type == 'Rent'){ ?>
				<div class="col-md-12 mtop10">
					<h4 class="tw-font-semibold">
						<?php echo _l('real_indoor_amenities'); ?>
					</h4>
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>

					<div class="row">
						<div class="col-md-3">
							<div class="tw-font-semibold mbot5"><?php  echo _l('real_cable_TV'); ?></div>
							<span><?php echo real_remove_underscore($property_listing->cable_TV); ?></span>
						</div>
						<div class="col-md-3">
							<div class="tw-font-semibold mbot5"><?php  echo _l('real_computer'); ?></div>
							<span><?php echo real_remove_underscore($property_listing->computer); ?></span>
						</div>
						<div class="col-md-3">
							<div class="tw-font-semibold mbot5"><?php  echo _l('real_heating'); ?></div>
							<span><?php echo real_remove_underscore($property_listing->heating); ?></span>
						</div>
						<div class="col-md-3">
							<div class="tw-font-semibold mbot5"><?php  echo _l('real_internet'); ?></div>
							<span><?php echo real_remove_underscore($property_listing->internet); ?></span>
						</div>

					</div>
				</div>
			<?php } ?>

			<div class="col-md-12 mtop10">
				<h4 class="tw-font-semibold">
					<?php echo _l('rel_pool'); ?>
				</h4>
				<div class="clearfix"></div>
				<hr class="mtop5 mbot5">
				<div class="clearfix"></div>

				<div class="row">
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_private_pool'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->private_pool); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_spa'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->spa); ?></span>
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_pool_dimensions'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->pool_dimensions); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_spa_features'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->spa_features); ?></span>
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_pool_features'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->pool_features); ?></span>
					</div>
				</div>
			</div>

			<div class="col-md-12 mtop10">
				<h4 class="tw-font-semibold">
					<?php echo _l('rel_exterior_information'); ?>
				</h4>
				<div class="clearfix"></div>
				<hr class="mtop5 mbot5">
				<div class="clearfix"></div>

				<div class="row">
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_door_height'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->door_height); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_front_exposure'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->front_exposure); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_road_responsibility'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->road_responsibility); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_other_equipment'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->other_equipment); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_roof'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->roof); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_basement'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->basement); ?></span>
						

					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_door_width'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->door_width); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_easements'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->easements); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_signage'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->signage); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_vegetation'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->vegetation); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_building_features'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->building_features); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_Garden_SqM'); ?></div>
						<span><?php echo app_format_number($property_listing->Garden_SqM); ?></span>
						
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_eaves_height'); ?></div>
						<span><?php echo app_format_number($property_listing->eaves_height); ?></span>

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_road_frontage'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->road_frontage); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_adjoining_property'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->adjoining_property); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_lot_features'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->lot_features); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_garage_parking_features'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->garage_parking_features); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('real_Front_Yard_SqM'); ?></div>
						<span><?php echo app_format_number($property_listing->Front_Yard_SqM); ?></span>
						
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_garage_door_height'); ?>: <?php echo app_format_number($property_listing->garage_door_height); ?></div>
						<div class="tw-font-semibold mbot5"><?php  echo _l('rel_road_frontage_feet'); ?>: <?php echo app_format_number($property_listing->road_frontage_feet); ?></div>
						

						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_road_surface_type'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->road_surface_type); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_other_structures'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->other_structures); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_exterior_construction'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->exterior_construction); ?></span>
						<div class="tw-font-semibold mbot5 mtop10"><?php  echo _l('rel_foundation'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->foundation); ?></span>
						
					</div>
					

				</div>
			</div>
			<div class="col-md-12 mtop10">
				<h4 class="tw-font-semibold">
					<?php echo _l('real_outdoor_amenities'); ?>
				</h4>
				<div class="clearfix"></div>
				<hr class="mtop5 mbot5">
				<div class="clearfix"></div>

				<div class="row">
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_balcony'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->balcony); ?></span>
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_lift'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->lift); ?></span>
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_grill'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->grill); ?></span>
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_parking'); ?></div>
						<span><?php echo real_remove_underscore($property_listing->parking); ?></span>
					</div>
					
				</div>
			</div>

			<div class="col-md-12 mtop10">
				<h4 class="tw-font-semibold">
					<?php echo _l('real_expenses'); ?>
				</h4>
				<div class="clearfix"></div>
				<hr class="mtop5 mbot5">
				<div class="clearfix"></div>

				<div class="row">
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_reservation_payment'); ?></div>
						<span><?php echo app_format_money($property_listing->reservation_payment, $base_currency_id); ?></span>
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_maintenance_fee'); ?></div>
						<span><?php echo app_format_money($property_listing->maintenance_fee, $base_currency_id); ?></span>
					</div>
					<div class="col-md-3">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_contract_payment'); ?></div>
						<span><?php echo app_format_money($property_listing->contract_payment, $base_currency_id); ?></span>
					</div>

				</div>
			</div>
			<!-- Rooms -->
			<?php if(isset($property_listing->listing_rooms) && count($property_listing->listing_rooms) > 0){ ?>
				<div class="col-md-12 mtop10">
					<h4 class="tw-font-semibold">
						<?php echo _l('real_rooms'); ?>
					</h4>
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>

					<div class="row">
						<div class="col-md-12">
							<div class="table-responsive">
								<table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
									<thead>
										<tr>
											<th width="15%" align="left"><?php echo _l('real_room_type'); ?></th>
											<th width="15%" align="left"><?php echo _l('real_rooms_level'); ?></th>
											<th width="15%" align="left" class="qty"><?php echo _l('real_room_demension_width'); ?></th>
											<th width="15%" align="left"><?php echo _l('real_room_demension_lenght'); ?></th>
											<th width="15%" align="left"><?php echo _l('real_room_benefits'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($property_listing->listing_rooms as $listing_room) { ?>
											<tr>
												<td class="tw-font-semibold"><?php echo _l('rel_'.$listing_room['room_type']) ?></td>
												<td class="tw-font-semibold"><?php echo _l('rel_'.$listing_room['rooms_level']) ?></td>
												<td class="tw-font-semibold"><?php echo new_html_entity_decode($listing_room['room_demension_width']) ?></td>
												<td class="tw-font-semibold"><?php echo new_html_entity_decode($listing_room['room_demension_lenght']) ?></td>
												<td class="tw-font-semibold"><?php echo new_str_replace(',', ', ', $listing_room['room_benefits']) ?></td>

											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>

					</div>
				</div>
			<?php } ?>

			<div class="col-md-12">
				<h4 class="tw-font-semibold">
					<?php echo _l('real_nearby_schools_child_care'); ?>
				</h4>
				<div class="clearfix"></div>
				<hr class="mtop5 mbot5">
				<div class="clearfix"></div>

				<div class="row">
					<div class="col-md-4">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_nearest_schools'); ?></div>
						<span><?php echo (rel_convert_to_school_name($property_listing->school, false)); ?></span>
					</div>
					<div class="col-md-4">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_nearest_hopspitals'); ?></div>
						<span><?php echo real_convert_to_hopspital_name($property_listing->hopspital, false); ?></span>
					</div>
					<div class="col-md-4">
						<div class="tw-font-semibold mbot5"><?php  echo _l('real_nearest_landmarks'); ?></div>
						<span><?php echo rel_convert_to_landmark_name($property_listing->landmarks, false); ?></span>
					</div>

				</div>
			</div>

		</div>

		<?php if(isset($product_attachment_pdfs) && count($product_attachment_pdfs) > 0){ ?>
			<div class="row">
				<div class="col-md-12">
					<h4 class="tw-font-semibold">
						<?php echo _l('real_pdf_files'); ?>
					</h4>
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

		<?php if( isset($product_videos) && count($product_videos) > 0){ ?>

			<div class="row">
				<div class="col-md-12">
					<h4 class="tw-font-semibold">
						<?php echo _l('real_property_videos'); ?>
					</h4>
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
							<?php if(is_admin() || has_permission('real_property', '', 'delete') ){ ?>
								<div class="remove_file">
									<a href="#" class="text-danger" onclick="delete_property_video_attachment(this,<?php echo new_html_entity_decode($product_video['id']); ?>); return false;"><i class="fa fa fa-times"></i></a>
								</div>
							<?php } ?>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php } ?>

		<?php if((is_staff_logged_in() || is_broker_logged_in()) && isset($activities) && count($activities) > 0 ){ ?>
			<div class="row">
				<div class="col-md-12">
					<h4 class="tw-font-semibold">
						<?php echo _l('real_property_history'); ?>
					</h4>
					<div class="clearfix"></div>
					<hr class="mtop5 mbot5">
					<div class="clearfix"></div>

					<div class="row">
						<div class="col-md-12">
							<div class="activity-feed">
								<?php foreach ($activities as $activity) {
									$_custom_data = false; ?>
									<div class="feed-item" data-sale-activity-id="<?php echo e($activity['id']); ?>">
										<div class="date">
											<span class="text-has-action" data-toggle="tooltip"
											data-title="<?php echo e(_dt($activity['date'])); ?>">
											<?php echo e(time_ago($activity['date'])); ?>
										</span>
									</div>
									<div class="text">
										<?php if (is_numeric($activity['is_company_admin']) && ($activity['company_id'] != 0 || $activity['is_company_admin'] != 0) ) { ?>
											<a href="#" data-toggle="tooltip" data-title="<?php echo e(get_staff_full_name($activity['related_id'])); ?>"><?php echo staff_profile_image($activity['related_id'], ['staff-profile-xs-image pull-left mright5']);
											?>
										</a>
									<?php }elseif(is_numeric($activity['broker_id']) && $activity['broker_id'] != 0){ ?>
										<a href="#" data-toggle="tooltip" data-title="<?php echo e(get_broker_name($activity['related_id'])); ?>"><?php echo broker_profile_image($activity['related_id'], ['staff-profile-xs-image pull-left mright5']);
										?>
									</a>
								<?php } ?>
								<?php
								$additional_data = '';
								if (!empty($activity['additional_data']) && $additional_data = unserialize($activity['additional_data'])) {
									$i               = 0;
									foreach ($additional_data as $data) {
										if (strpos($data ?? '', '<original_status>') !== false) {
											$original_status     = get_string_between($data, '<original_status>', '</original_status>');
											$additional_data[$i] = format_invoice_status($original_status, '', false);
										} elseif (strpos($data ?? '', '<new_status>') !== false) {
											$new_status          = get_string_between($data, '<new_status>', '</new_status>');
											$additional_data[$i] = format_invoice_status($new_status, '', false);
										} elseif (strpos($data ?? '', '<custom_data>') !== false) {
											$_custom_data = get_string_between($data, '<custom_data>', '</custom_data>');
											unset($additional_data[$i]);
										}
										$i++;
									}
								}

								$_formatted_activity = _l($activity['description'], $additional_data);

								if ($_custom_data !== false) {
									$_formatted_activity .= ' - ' . $_custom_data;
								}

								if (!empty($activity['full_name'])) {
									$_formatted_activity = e($activity['full_name']) . ' - ' . $_formatted_activity;
								}

								echo new_html_entity_decode($_formatted_activity);

								if (is_admin() || (is_broker_logged_in() && $activity['broker_id'] == get_business_broker_id() )) {
									echo '<a href="#" class="pull-right text-danger" onclick="delete_property_activity(' . $activity['id'] . '); return false;"><i class="fa fa-remove"></i></a>';
								} ?>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>

		</div>
	</div>
</div>
<?php } ?>

</div>
<div class="col-md-3">
	<!-- right column -->
	<?php $this->load->view('companies/property_listings/utilities/agent_info'); ?>
</div>
</div>