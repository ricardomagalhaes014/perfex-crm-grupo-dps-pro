<?php

defined('BASEPATH') or exit('No direct script access allowed');
$base_currency_id = get_base_currency_id();
$is_broker_logged_in = is_broker_logged_in();
$CI = &get_instance();
$CI->load->model('realestate/realestate_model');
$arr_images = $CI->realestate_model->item_attachments();

$search_template = [];
$favarite_listings = [];
$company_ids = [];
$rel_property_style_color = [];
$listing_attachment = [];

$aColumns = [
	'1',
	'2',
	'commodity_code',
	'description',
	'status',
	'city',
	'state',
	'property_style',
	'listing_type',
	'ownership',
	'lot_size_acres',
	'sqFt_total',
	'transaction_type',
	'rent_price',
	'reservation_payment',
	'maintenance_fee',
	'property_condition',
	'new_construction',
	'furnished',
	'finishing',
	'utilities',
	'appliances_included',
	'kitchen',
	'beds',
	'full_baths',
	'half_baths',
	'garage',
	'street_name',
	'year_built',
	'related_id',
	'date_created',
	'fireplace_description',
	'sewer',
	'water',
	'heating_and_fuel',
	'air_conditioning',
	'electrical_Service',
	'security_features',
	'accessibility_features',
	'floor_covering',
	'ceiling_type',
	'window_Features',
	'school',
	'landmarks',
	'street_number',
	'street_type',
	'street_dir_pos',
	'unit_number',
	'zip',
	'zip_4',
	'country',
	'total_of_floors',
	'latitude',
	'longitude',
	'use_code',
	'proj_completion_date',
	'sqFt_heated',
	'sqFt_heated_source',
	'SqFt_total_source',
	'fireplace',
	'owner_name',
	'owner_phone',
	'gas_emission',
	'egenry_efficient',
	'cable_TV',
	'computer',
	'heating',
	'internet',
	'energy_efficiency',
	'commission',
	'hydro_included',
	'water_included',
	'gas_included',
	'contract_payment',
	
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'items';

$where = [];
$join= [];

$where = ['AND can_be_property_listing = "can_be_property_listing" AND status != "pending"'];

if($related_type == 'company'){
	$where[] = 'AND ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' ) )';
}else{
	// business_broker
	$where[] = 'AND ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.') )';
}


$search_input = $this->ci->input->post('search_input');
$transaction_type_search = $this->ci->input->post('transaction_type');
$listing_type = $this->ci->input->post('listing_type');
$property_style = $this->ci->input->post('property_style');
$min_price_search = $this->ci->input->post('min_price');
$max_price_search = $this->ci->input->post('max_price');
$status_search = $this->ci->input->post('status');
$beds_search = $this->ci->input->post('min_bed');
$max_bed_search = $this->ci->input->post('max_bed');
$filter_bath = $this->ci->input->post('filter_bath');
$filter_garage = $this->ci->input->post('filter_garage');
$filter_land_min = $this->ci->input->post('filter_land_min');
$filter_land_max = $this->ci->input->post('filter_land_max');
$min_total_of_floors = $this->ci->input->post('min_total_of_floors');
$max_total_of_floors = $this->ci->input->post('max_total_of_floors');
$appliances_included = $this->ci->input->post('appliances_included');
$utilities = $this->ci->input->post('utilities');
$sewer = $this->ci->input->post('sewer');
$water = $this->ci->input->post('water');
$air_conditioning = $this->ci->input->post('air_conditioning');
$electrical_service = $this->ci->input->post('electrical_service');
$security_features = $this->ci->input->post('security_features');
$accessibility_features = $this->ci->input->post('accessibility_features');


$listing_price_from = $min_price_search;
$listing_price_to = $max_price_search;
$rent_price_from = $min_price_search;
$rent_price_to = $max_price_search;

if($search_input){
	$where[] = 'AND (UPPER('.db_prefix().'items.description) LIKE "%'.strtoupper($search_input).'%"
	OR '.db_prefix().'items.commodity_code LIKE "%'.strtoupper($search_input).'%"
	OR UPPER('.db_prefix().'items.status) LIKE "%'.strtoupper($search_input).'%"
	OR UPPER('.db_prefix().'items.city) LIKE "%'.strtoupper($search_input).'%"
	OR UPPER('.db_prefix().'items.state) LIKE "%'.strtoupper($search_input).'%"
	)
	';
}

if($transaction_type_search){
	$arr_transaction_type = explode(',', $transaction_type_search);
	if(in_array('Sale', $arr_transaction_type) || in_array('sold', $arr_transaction_type)){
		$listing_price_from = $min_price_search;
		$listing_price_to = $max_price_search;
	}
	if(in_array('Rent', $arr_transaction_type) || in_array('rented', $arr_transaction_type) ){
		$rent_price_from = $min_price_search;
		$rent_price_to = $max_price_search;
	}
}

$price_query = [];
if(is_numeric($listing_price_from) && $listing_price_from > 0 && is_numeric($listing_price_to) && $listing_price_to > 0 ){
	$price_query[] = '('.db_prefix().'items.rate >= '.$listing_price_from.' AND '.db_prefix().'items.rate <= '.$listing_price_to.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
}elseif(is_numeric($listing_price_from) && $listing_price_from > 0){
	$price_query[] = '('.db_prefix().'items.rate >= '.$listing_price_from.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
}elseif(is_numeric($listing_price_to) && $listing_price_to > 0){
	$price_query[] = '('.db_prefix().'items.rate <= '.$listing_price_to.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
}

if(is_numeric($rent_price_from) && $rent_price_from > 0 && is_numeric($rent_price_to) && $rent_price_to > 0 ){
	$price_query[] = '('.db_prefix().'items.rent_price >= '.$rent_price_from.' AND '.db_prefix().'items.rent_price <= '.$rent_price_to.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
}elseif(is_numeric($rent_price_from) && $rent_price_from > 0){
	$price_query[] = '('.db_prefix().'items.rent_price >= '.$rent_price_from.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
}elseif(is_numeric($rent_price_to) && $rent_price_to > 0){
	$price_query[] = '('.db_prefix().'items.rent_price <= '.$rent_price_to.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
}

if(count($price_query) > 0){
	$where[] = 'AND ('.implode(' OR ', $price_query).')';
}

$bed_query = [];
if(is_numeric($beds_search) && $beds_search > 0 && is_numeric($max_bed_search) && $max_bed_search > 0 ){
	$bed_query[] = '('.db_prefix().'items.beds >= '.$beds_search.' AND '.db_prefix().'items.beds <= '.$max_bed_search.')';		
}elseif(is_numeric($beds_search) && $beds_search > 0){
	$bed_query[] = '('.db_prefix().'items.beds >= '.$beds_search.')';		
}elseif(is_numeric($max_bed_search) && $max_bed_search > 0){
	$bed_query[] = '('.db_prefix().'items.beds <= '.$max_bed_search.')';		
}

if(count($bed_query) > 0){
	$where[] = 'AND ('.implode(' OR ', $bed_query).')';
}

$land_size_query = [];
if(is_numeric($filter_land_min) && $filter_land_min > 0 && is_numeric($filter_land_max) && $filter_land_max > 0 ){
	$land_size_query[] = '('.db_prefix().'items.lot_size_acres >= '.$filter_land_min.' AND '.db_prefix().'items.lot_size_acres <= '.$filter_land_max.')';		
}elseif(is_numeric($filter_land_min) && $filter_land_min > 0){
	$land_size_query[] = '('.db_prefix().'items.lot_size_acres >= '.$filter_land_min.')';		
}elseif(is_numeric($filter_land_max) && $filter_land_max > 0){
	$land_size_query[] = '('.db_prefix().'items.lot_size_acres <= '.$filter_land_max.')';		
}

if(count($land_size_query) > 0){
	$where[] = 'AND ('.implode(' OR ', $land_size_query).')';
}

if ($filter_garage) {
	$where[] = 'AND '.db_prefix().'items.garage >= '.$filter_garage;
}

if ($filter_bath) {
	$where[] = 'AND '.db_prefix().'items.full_baths >= '.$filter_bath;
}

// property filter by mutilple value
$property_listing_where = '';
$fields = [
	'transaction_type',
	'listing_type',
	'property_style',
	'appliances_included',
	'utilities',
	'sewer',
	'water',
	'air_conditioning',
	'electrical_service',
	'security_features',
	'accessibility_features',
];

foreach ($fields as $key => $field_name) {

	if(($this->ci->input->post($field_name))){
		if(strlen($property_listing_where) == 0){
			$property_listing_where .= ' AND (';
		}
		if($property_listing_where != ' AND ('){
			$property_listing_where .= ' AND';
		}
		$arr_search_value  = explode(',', $this->ci->input->post($field_name));

		foreach ($arr_search_value as $key => $search_value) {
			if ($search_value != '') {

				if($key == 0 && count($arr_search_value) > 1){
					$property_listing_where .= ' ( find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.') ';

				}elseif(count($arr_search_value) == 1){
					$property_listing_where .= ' ( find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.')) ';

				}elseif($key == count($arr_search_value) - 1){
					$property_listing_where .= ' OR find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.')) ';

				}else{
					$property_listing_where .= ' OR find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.') ';
				}
			}
		}
	}
}

if ($property_listing_where != '' && $property_listing_where != ' AND (' && $property_listing_where != ' AND ()') {
	$property_listing_where .= ')';

	$where[] = $property_listing_where;
}

if($this->ci->input->post('my_favourite_filter') != null && $this->ci->input->post('my_favourite_filter') == 'favourite'){
	$arr_favourite = array_keys($favarite_listings);
	if(count($arr_favourite) > 0){
		$where[] = 'AND '.db_prefix().'items.id IN ('.implode(',', $arr_favourite).')';
	}else{
		$where[] = 'AND 1=2';
	}
}

if($status_search){
	$where[] = 'AND ('.db_prefix().'items.status = "'.$status_search.'")';
}

if($this->ci->input->post('my_listing') != null && $this->ci->input->post('my_listing') == 1){
	$where[] = 'AND (related_id=' .get_staff_user_id().')';
}else{
	$where[] = 'AND (status != "pending")';

}

if($this->ci->input->post('has_attachment') != null && $this->ci->input->post('has_attachment') == 'true'){
	if(count($listing_attachment) > 0){
		$list_item_id = [];
		foreach ($listing_attachment as $key => $value) {
			if(!in_array($key, $list_item_id)){
				$list_item_id[] = $key;
			}
		}
		$where[] = 'AND '.db_prefix().'items.id IN ('.implode(',', $list_item_id).')';
	}else{
		$where[] = 'AND 1=2';
	}
}

$query1 = [];
if($this->ci->input->post('on_sale') != null && $this->ci->input->post('on_sale') == 'true'){
	if($this->ci->input->post('date_on_sale') != null && $this->ci->input->post('date_on_sale') != ''){
		$query1[] = '('.db_prefix().'items.status = "active" AND date('.db_prefix().'items.date_update) = "'.to_sql_date($this->ci->input->post('date_on_sale')).'")';			
	}
	else{
		$query1[] = db_prefix().'items.status = "active"';			
	}
}
if($this->ci->input->post('pending_sale') != null && $this->ci->input->post('pending_sale') == 'true'){
	if($this->ci->input->post('date_pending_sale') != null && $this->ci->input->post('date_pending_sale') != ''){
		$query1[] = '('.db_prefix().'items.status = "pending_sale" AND date('.db_prefix().'items.date_update) = "'.to_sql_date($this->ci->input->post('date_pending_sale')).'")';		
	}
	else{
		$query1[] = db_prefix().'items.status = "pending_sale"';			
	}
}
if($this->ci->input->post('rented') != null && $this->ci->input->post('rented') == 'true'){
	if($this->ci->input->post('date_rented') != null && $this->ci->input->post('date_rented') != ''){
		$query1[] = '('.db_prefix().'items.status = "rented" AND date('.db_prefix().'items.date_update) = "'.to_sql_date($this->ci->input->post('date_rented')).'")';		
	}
	else{
		$query1[] = db_prefix().'items.status = "rented"';			
	}
}
if($this->ci->input->post('expired') != null && $this->ci->input->post('expired') == 'true'){
	if($this->ci->input->post('date_expired') != null && $this->ci->input->post('date_expired') != ''){
		$query1[] = '('.db_prefix().'items.status = "expired" AND date('.db_prefix().'items.date_update) = "'.to_sql_date($this->ci->input->post('date_expired')).'")';		
	}
	else{
		$query1[] = db_prefix().'items.status = "expired"';			
	}
}

if(count($query1) > 0){
	$where[] = 'AND ('.implode(' OR ', $query1).')';
}

if($this->ci->input->post('item_id') != null && $this->ci->input->post('item_id') != ''){
	$item_id_arr = explode(',', $this->ci->input->post('item_id'));
	$add_query = [];
	foreach ($item_id_arr as $item_id) {
		$add_query[] = db_prefix().'items.id = '.$item_id;
	}
	if(count($add_query) > 0){
		$new_query = implode(' OR ', $add_query);
		$where[] = 'AND ('.$new_query.')';
	}
}

$staff_type = rel_check_staff_type();


$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'related_id', 'company_id', 'latitude', 'longitude', 'property_style','primary_image', 'rate']);

$output = $result['output'];
$rResult = $result['rResult'];
$arr_images = $this->ci->realestate_model->item_attachments();
$staff_id = get_staff_user_id();

if($this->ci->input->post('latitude_filter') != null && strlen($this->ci->input->post('latitude_filter')) > 0 && $this->ci->input->post('longitude_filter') != null && strlen($this->ci->input->post('longitude_filter')) > 0 ){

	$latitude_filter = $this->ci->input->post('latitude_filter');
	$longitude_filter = $this->ci->input->post('longitude_filter');
	$radius_filter = $this->ci->input->post('radius_filter');
}

foreach ($rResult as $aRow) {

	if(isset($latitude_filter)){
		if(!check_lat_long_within_circle($latitude_filter, $longitude_filter, $aRow['latitude'], $aRow['longitude'], $radius_filter)){
			$output['iTotalRecords']--;
			$output['iTotalDisplayRecords']--;
			continue;
		}
	}

	$allow_action = true;

	$row = [];

	for ($i = 0; $i < count($aColumns); $i++) {

		if($aColumns[$i] == '1') {
			$_data = (int)$aRow['id'];
		}
		elseif($aColumns[$i] == '3'){
			$_data = '<div class="checkbox"><input type="checkbox" class="checkbox_item" onclick="checkbox_select_item('.$aRow['id'].',this)" value="'.$aRow['id'].'"><label></label></div>';
		}
		elseif ($aColumns[$i] == '2') {
			$_data = '<img class="images_w_table" src="' . main_photo($aRow['id'], $aRow['primary_image']) . '" alt="'.$aRow['primary_image'].'" >';
		}elseif ($aColumns[$i] == 'commodity_code') {
			$_data = $aRow['commodity_code'];
		}
		elseif ($aColumns[$i] == 'description') {

			if($allow_action){

					$code = $aRow['description'];

				$code .= '<div class="row-options">';
				if (has_permission('real_property', '', 'view') || has_permission('real_property', '', 'view_own') || $is_broker_logged_in) {
					$code .= '<a href="' . $site_url . ('property_listing_detail/' . $aRow['id']) . '" >' . _l('view') . '</a>';
				}

				if (has_permission('real_property', '', 'edit') || $is_broker_logged_in) {
					$code .= ' | <a href="' . $site_url . ('add_edit_property_listing/' . $aRow['id'] ) . '" >' . _l('edit') . '</a>';
				}
				if (has_permission('real_property', '', 'delete') || $is_broker_logged_in) {
					$code .= ' | <a href="' . $site_url . ('delete_property_listing/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
				}
				$code .= '</div>';
			}else{
				
					$code = $aRow['description'];

				$code .= '<div class="row-options">';
				if (has_permission('real_property', '', 'view') || has_permission('real_property', '', 'view_own')) {
					$code .= '<a href="' . $site_url . ('property_listing_detail/' . $aRow['id']) . '" >' . _l('view') . '</a>';
				}
				$code .= '</div>';
			}

			$_data = $code;

		}elseif($aColumns[$i] == 'transaction_type'){
			if(!is_null($aRow['transaction_type'])){
				$_data = _l('rel_'.$aRow['transaction_type']);
			}else{
				$_data = '';
			}
		}elseif($aColumns[$i] == 'property_style'){
			if(!is_null($aRow['property_style'])){
				$_data = _l('rel_'.$aRow['property_style']);
			}else{
				$_data = '';
			}
		}elseif($aColumns[$i] == 'status'){
			$_data = _l('rel_'.$aRow['status']);
		}elseif($aColumns[$i] == 'rate'){
			$_data = ($aRow['rate']);
		}elseif($aColumns[$i] == 'rent_price'){
			if($aRow['transaction_type'] == 'Sale' || $aRow['transaction_type'] == 'sold'){
				$_data = app_format_money($aRow['rate'], $base_currency_id);
			}else{
				$_data = app_format_money($aRow['rent_price'], $base_currency_id);
			}
		}elseif($aColumns[$i] == 'street_name'){
			$_data = $aRow['street_name'];
		}elseif($aColumns[$i] == 'city'){
			$_data = $aRow['city'];
		}elseif($aColumns[$i] == 'state'){
			$_data = real_remove_underscore($aRow['state']);
		}elseif($aColumns[$i] == 'sqFt_total'){
			$_data = real_remove_underscore($aRow['sqFt_total']);
		}elseif($aColumns[$i] == 'beds'){
			$_data = real_remove_underscore($aRow['beds']);
		}elseif($aColumns[$i] == 'full_baths'){
			$_data = real_remove_underscore($aRow['full_baths']);
		}elseif($aColumns[$i] == 'half_baths'){
			$_data = real_remove_underscore($aRow['half_baths']);
		}elseif($aColumns[$i] == 'garage'){
			$_data = $aRow['garage'];
		}elseif($aColumns[$i] == 'private_pool'){
			$_data = real_remove_underscore($aRow['private_pool']);
		}elseif($aColumns[$i] == 'ownership'){
			$_data = real_remove_underscore($aRow['ownership']);
		}elseif($aColumns[$i] == 'housing_for_older_persons'){
			$_data = real_remove_underscore($aRow['housing_for_older_persons']);
		}elseif($aColumns[$i] == 'date_created'){
			$_data = _dt($aRow['date_created']);
		}elseif($aColumns[$i] == 'related_id'){
			$_data = get_staff_full_name($aRow['related_id']);
		}elseif($aColumns[$i] == 'financing_available'){
			$_data = real_remove_underscore($aRow['financing_available']);
		}elseif($aColumns[$i] == 'foundation'){
			$_data = real_remove_underscore($aRow['foundation']);
		}elseif($aColumns[$i] == 'year_built'){
			$_data = real_remove_underscore($aRow['year_built']);
		}elseif($aColumns[$i] == '2'){

			$checked = '';
			if (isset($favarite_listings[$aRow['id']])) {
				$checked = 'checked';
			}

			$toggleActive = '<div class="onoffswitch">
			<input type="checkbox" data-switch-url="' . $site_url . 'change_favorite" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . $checked . '>
			<label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
			</div>';

			$_data = $toggleActive;
		}elseif($aColumns[$i] == 'sale_includes'){
			$_data = real_remove_underscore($aRow['sale_includes']);
		}elseif($aColumns[$i] == 'pool_features'){
			$_data = real_remove_underscore($aRow['pool_features']);
		}elseif($aColumns[$i] == 'spa_features'){
			$_data = real_remove_underscore($aRow['spa_features']);
		}elseif($aColumns[$i] == 'signage'){
			$_data = real_remove_underscore($aRow['signage']);
		}elseif($aColumns[$i] == 'number_of_tenants'){
			$_data = real_remove_underscore($aRow['number_of_tenants']);
		}elseif($aColumns[$i] == 'front_exposure'){
			$_data = real_remove_underscore($aRow['front_exposure']);
		}elseif($aColumns[$i] == 'road_frontage'){
			$_data = real_remove_underscore($aRow['road_frontage']);
		}elseif($aColumns[$i] == 'road_surface_type'){
			$_data = real_remove_underscore($aRow['road_surface_type']);
		}elseif($aColumns[$i] == 'road_responsibility'){
			$_data = real_remove_underscore($aRow['road_responsibility']);
		}elseif($aColumns[$i] == 'adjoining_property'){
			$_data = real_remove_underscore($aRow['adjoining_property']);
		}elseif($aColumns[$i] == 'other_structures'){
			$_data = real_remove_underscore($aRow['other_structures']);
		}elseif($aColumns[$i] == 'other_equipment'){
			$_data = real_remove_underscore($aRow['other_equipment']);
		}elseif($aColumns[$i] == 'vegetation'){
			$_data = real_remove_underscore($aRow['vegetation']);
		}elseif($aColumns[$i] == 'lot_features'){
			$_data = real_remove_underscore($aRow['lot_features']);
		}elseif($aColumns[$i] == 'exterior_construction'){
			$_data = real_remove_underscore($aRow['exterior_construction']);
		}elseif($aColumns[$i] == 'roof'){
			$_data = real_remove_underscore($aRow['roof']);
		}elseif($aColumns[$i] == 'building_features'){
			$_data = real_remove_underscore($aRow['building_features']);
		}elseif($aColumns[$i] == 'garage_parking_features'){
			$_data = real_remove_underscore($aRow['garage_parking_features']);
		}elseif($aColumns[$i] == 'basement'){
			$_data = real_remove_underscore($aRow['basement']);
		}elseif($aColumns[$i] == 'fireplace_description'){
			$_data = real_remove_underscore($aRow['fireplace_description']);
		}elseif($aColumns[$i] == 'appliances_included'){
			$_data = real_remove_underscore($aRow['appliances_included']);
		}elseif($aColumns[$i] == 'utilities'){
			$_data = real_remove_underscore($aRow['utilities']);
		}elseif($aColumns[$i] == 'sewer'){
			$_data = real_remove_underscore($aRow['sewer']);
		}elseif($aColumns[$i] == 'water'){
			$_data = real_remove_underscore($aRow['water']);
		}elseif($aColumns[$i] == 'heating_and_fuel'){
			$_data = real_remove_underscore($aRow['heating_and_fuel']);
		}elseif($aColumns[$i] == 'air_conditioning'){
			$_data = real_remove_underscore($aRow['air_conditioning']);
		}elseif($aColumns[$i] == 'electrical_Service'){
			$_data = real_remove_underscore($aRow['electrical_Service']);
		}elseif($aColumns[$i] == 'security_features'){
			$_data = real_remove_underscore($aRow['security_features']);
		}elseif($aColumns[$i] == 'accessibility_features'){
			$_data = real_remove_underscore($aRow['accessibility_features']);
		}elseif($aColumns[$i] == 'floor_covering'){
			$_data = real_remove_underscore($aRow['floor_covering']);
		}elseif($aColumns[$i] == 'ceiling_type'){
			$_data = real_remove_underscore($aRow['ceiling_type']);
		}elseif($aColumns[$i] == 'window_Features'){
			$_data = real_remove_underscore($aRow['window_Features']);
		}elseif($aColumns[$i] == 'water_access'){
			$_data = real_remove_underscore($aRow['water_access']);
		}elseif($aColumns[$i] == 'water_view'){
			$_data = real_remove_underscore($aRow['water_view']);
		}elseif($aColumns[$i] == 'water_extras'){
			$_data = real_remove_underscore($aRow['water_extras']);
		}elseif($aColumns[$i] == 'water_frontage'){
			$_data = real_remove_underscore($aRow['water_frontage']);
		}elseif($aColumns[$i] == 'green_sustainability'){
			$_data = real_remove_underscore($aRow['green_sustainability']);
		}elseif($aColumns[$i] == 'green_energy_generation'){
			$_data = real_remove_underscore($aRow['green_energy_generation']);
		}elseif($aColumns[$i] == 'green_energy_features'){
			$_data = real_remove_underscore($aRow['green_energy_features']);
		}elseif($aColumns[$i] == 'green_water_features'){
			$_data = real_remove_underscore($aRow['green_water_features']);
		}elseif($aColumns[$i] == 'disaster_mitigation'){
			$_data = real_remove_underscore($aRow['disaster_mitigation']);
		}elseif($aColumns[$i] == 'indoor_air_quality'){
			$_data = real_remove_underscore($aRow['indoor_air_quality']);
		}elseif($aColumns[$i] == 'community_features'){
			$_data = real_remove_underscore($aRow['community_features']);
		}elseif($aColumns[$i] == 'association_amenities'){
			$_data = real_remove_underscore($aRow['association_amenities']);
		}elseif($aColumns[$i] == 'fee_includes'){
			$_data = real_remove_underscore($aRow['fee_includes']);
		}elseif($aColumns[$i] == 'pets_allowed'){
			$_data = real_remove_underscore($aRow['pets_allowed']);
		}elseif($aColumns[$i] == 'school'){
			$_data = rel_convert_to_school_name($aRow['school'], false);
		}elseif($aColumns[$i] == 'landmarks'){
			$_data = rel_convert_to_landmark_name($aRow['landmarks'], false);
		}elseif($aColumns[$i] == 'listing_contract_date'){
			$_data = real_remove_underscore($aRow['listing_contract_date']);
		}elseif($aColumns[$i] == 'expiration_date'){
			$_data = real_remove_underscore($aRow['expiration_date']);
		}elseif($aColumns[$i] == 'special_sale_provision'){
			$_data = real_remove_underscore($aRow['special_sale_provision']);
		}elseif($aColumns[$i] == 'listing_type'){
			$_data = real_remove_underscore($aRow['listing_type']);
		}elseif($aColumns[$i] == 'representation'){
			$_data = real_remove_underscore($aRow['representation']);
		}elseif($aColumns[$i] == 'street_number'){
			$_data = real_remove_underscore($aRow['street_number']);
		}elseif($aColumns[$i] == 'street_dir_pre'){
			$_data = real_remove_underscore($aRow['street_dir_pre']);
		}elseif($aColumns[$i] == 'street_type'){
			$_data = real_remove_underscore($aRow['street_type']);
		}elseif($aColumns[$i] == 'street_dir_pos'){
			$_data = real_remove_underscore($aRow['street_dir_pos']);
		}elseif($aColumns[$i] == 'unit_number'){
			$_data = real_remove_underscore($aRow['unit_number']);
		}elseif($aColumns[$i] == 'zip'){
			$_data = real_remove_underscore($aRow['zip']);
		}elseif($aColumns[$i] == 'zip_4'){
			$_data = real_remove_underscore($aRow['zip_4']);
		}elseif($aColumns[$i] == 'country'){
			$_data = get_country_name($aRow['country']);
		}elseif($aColumns[$i] == 'development'){
			$_data = real_remove_underscore($aRow['development']);
		}elseif($aColumns[$i] == 'total_of_floors'){
			$_data = real_remove_underscore($aRow['total_of_floors']);
		}elseif($aColumns[$i] == 'latitude'){
			$_data = real_remove_underscore($aRow['latitude']);
		}elseif($aColumns[$i] == 'longitude'){
			$_data = real_remove_underscore($aRow['longitude']);
		}elseif($aColumns[$i] == 'operating_expenses'){
			$_data = real_remove_underscore($aRow['operating_expenses']);
		}elseif($aColumns[$i] == 'net_operating_income'){
			$_data = real_remove_underscore($aRow['net_operating_income']);
		}elseif($aColumns[$i] == 'net_operating_income_type'){
			$_data = real_remove_underscore($aRow['net_operating_income_type']);
		}elseif($aColumns[$i] == 'annual_expenses'){
			$_data = real_remove_underscore($aRow['annual_expenses']);
		}elseif($aColumns[$i] == 'annual_TTL_schedule_income'){
			$_data = real_remove_underscore($aRow['annual_TTL_schedule_income']);
		}elseif($aColumns[$i] == 'annual_income_type'){
			$_data = real_remove_underscore($aRow['annual_income_type']);
		}elseif($aColumns[$i] == 'auction_type'){
			$_data = real_remove_underscore($aRow['auction_type']);
		}elseif($aColumns[$i] == 'auction_property_access'){
			$_data = real_remove_underscore($aRow['auction_property_access']);
		}elseif($aColumns[$i] == 'buyer_premium'){
			$_data = real_remove_underscore($aRow['buyer_premium']);
		}elseif($aColumns[$i] == 'auction_firm'){
			$_data = real_remove_underscore($aRow['auction_firm']);
		}elseif($aColumns[$i] == 'pool_dimensions'){
			$_data = real_remove_underscore($aRow['pool_dimensions']);
		}elseif($aColumns[$i] == 'spa'){
			$_data = real_remove_underscore($aRow['spa']);
		}elseif($aColumns[$i] == 'use_code'){
			$_data = real_remove_underscore($aRow['use_code']);
		}elseif($aColumns[$i] == 'new_construction'){
			$_data = real_remove_underscore($aRow['new_construction']);
		}elseif($aColumns[$i] == 'property_condition'){
			$_data = real_remove_underscore($aRow['property_condition']);
		}elseif($aColumns[$i] == 'proj_completion_date'){
			$_data = real_remove_underscore($aRow['proj_completion_date']);
		}elseif($aColumns[$i] == 'door_height'){
			$_data = real_remove_underscore($aRow['door_height']);
		}elseif($aColumns[$i] == 'door_width'){
			$_data = real_remove_underscore($aRow['door_width']);
		}elseif($aColumns[$i] == 'eaves_height'){
			$_data = real_remove_underscore($aRow['eaves_height']);
		}elseif($aColumns[$i] == 'road_frontage_feet'){
			$_data = real_remove_underscore($aRow['road_frontage_feet']);
		}elseif($aColumns[$i] == 'garage_door_height'){
			$_data = real_remove_underscore($aRow['garage_door_height']);
		}elseif($aColumns[$i] == 'easements'){
			$_data = real_remove_underscore($aRow['easements']);
		}elseif($aColumns[$i] == 'tax_ID'){
			$_data = real_remove_underscore($aRow['tax_ID']);
		}elseif($aColumns[$i] == 'tax_year'){
			$_data = real_remove_underscore($aRow['tax_year']);
		}elseif($aColumns[$i] == 'taxes_annual_amount'){
			$_data = real_remove_underscore($aRow['taxes_annual_amount']);
		}elseif($aColumns[$i] == 'additional_parcels'){
			$_data = real_remove_underscore($aRow['additional_parcels']);
		}elseif($aColumns[$i] == 'total_number_of_parcels'){
			$_data = real_remove_underscore($aRow['total_number_of_parcels']);
		}elseif($aColumns[$i] == 'additional_tax_IDs'){
			$_data = real_remove_underscore($aRow['additional_tax_IDs']);
		}elseif($aColumns[$i] == 'zoning'){
			$_data = real_remove_underscore($aRow['zoning']);
		}elseif($aColumns[$i] == 'zoning_compatible'){
			$_data = real_remove_underscore($aRow['zoning_compatible']);
		}elseif($aColumns[$i] == 'legal_description'){
			$_data = real_remove_underscore($aRow['legal_description']);
		}elseif($aColumns[$i] == 'section'){
			$_data = real_remove_underscore($aRow['section']);
		}elseif($aColumns[$i] == 'township'){
			$_data = real_remove_underscore($aRow['township']);
		}elseif($aColumns[$i] == 'range'){
			$_data = real_remove_underscore($aRow['range']);
		}elseif($aColumns[$i] == 'plat_book_page'){
			$_data = real_remove_underscore($aRow['plat_book_page']);
		}elseif($aColumns[$i] == 'block_parcel'){
			$_data = real_remove_underscore($aRow['block_parcel']);
		}elseif($aColumns[$i] == 'lot'){
			$_data = real_remove_underscore($aRow['lot']);
		}elseif($aColumns[$i] == 'alt_key_folio'){
			$_data = real_remove_underscore($aRow['alt_key_folio']);
		}elseif($aColumns[$i] == 'legal_subdivision_name'){
			$_data = real_remove_underscore($aRow['legal_subdivision_name']);
		}elseif($aColumns[$i] == 'subdivision'){
			$_data = real_remove_underscore($aRow['subdivision']);
		}elseif($aColumns[$i] == 'subdivision_section'){
			$_data = real_remove_underscore($aRow['subdivision_section']);
		}elseif($aColumns[$i] == 'complex_community_name'){
			$_data = real_remove_underscore($aRow['complex_community_name']);
		}elseif($aColumns[$i] == 'census_tract'){
			$_data = real_remove_underscore($aRow['census_tract']);
		}elseif($aColumns[$i] == 'census_block'){
			$_data = real_remove_underscore($aRow['census_block']);
		}elseif($aColumns[$i] == 'condo_land_included'){
			$_data = real_remove_underscore($aRow['condo_land_included']);
		}elseif($aColumns[$i] == 'flood_zone_code'){
			$_data = real_remove_underscore($aRow['flood_zone_code']);
		}elseif($aColumns[$i] == 'flood_zone_date'){
			$_data = real_remove_underscore($aRow['flood_zone_date']);
		}elseif($aColumns[$i] == 'flood_zone_panel'){
			$_data = real_remove_underscore($aRow['flood_zone_panel']);
		}elseif($aColumns[$i] == 'total_acreage'){
			$_data = real_remove_underscore($aRow['total_acreage']);
		}elseif($aColumns[$i] == 'lot_dimensions'){
			$_data = real_remove_underscore($aRow['lot_dimensions']);
		}elseif($aColumns[$i] == 'lot_size_square_footage'){
			$_data = real_remove_underscore($aRow['lot_size_square_footage']);
		}elseif($aColumns[$i] == 'lot_size_acres'){
			$_data = real_remove_underscore($aRow['lot_size_acres']);
		}elseif($aColumns[$i] == 'total_units_on_property'){
			$_data = real_remove_underscore($aRow['total_units_on_property']);
		}elseif($aColumns[$i] == 'total_number_of_buildings'){
			$_data = real_remove_underscore($aRow['total_number_of_buildings']);
		}elseif($aColumns[$i] == 'future_land_use'){
			$_data = real_remove_underscore($aRow['future_land_use']);
		}elseif($aColumns[$i] == 'sqFt_heated'){
			$_data = real_remove_underscore($aRow['sqFt_heated']);
		}elseif($aColumns[$i] == 'sqFt_heated_source'){
			$_data = real_remove_underscore($aRow['sqFt_heated_source']);
		}elseif($aColumns[$i] == 'SqFt_total_source'){
			$_data = real_remove_underscore($aRow['SqFt_total_source']);
		}elseif($aColumns[$i] == 'fireplace'){
			$_data = real_remove_underscore($aRow['fireplace']);
		}elseif($aColumns[$i] == 'water_access_yn'){
			$_data = real_remove_underscore($aRow['water_access_yn']);
		}elseif($aColumns[$i] == 'water_view_yn'){
			$_data = real_remove_underscore($aRow['water_view_yn']);
		}elseif($aColumns[$i] == 'water_extras_yn'){
			$_data = real_remove_underscore($aRow['water_extras_yn']);
		}elseif($aColumns[$i] == 'water_frontage_yn'){
			$_data = real_remove_underscore($aRow['water_frontage_yn']);
		}elseif($aColumns[$i] == 'water_name'){
			$_data = real_remove_underscore($aRow['water_name']);
		}elseif($aColumns[$i] == 'additional_water_information'){
			$_data = real_remove_underscore($aRow['additional_water_information']);
		}elseif($aColumns[$i] == 'owner_name'){
			$_data = real_remove_underscore($aRow['owner_name']);
		}elseif($aColumns[$i] == 'owner_phone'){
			$_data = real_remove_underscore($aRow['owner_phone']);
		}elseif($aColumns[$i] == 'tenant_name'){
			$_data = real_remove_underscore($aRow['tenant_name']);
		}elseif($aColumns[$i] == 'tenant_phone'){
			$_data = real_remove_underscore($aRow['tenant_phone']);
		}elseif($aColumns[$i] == 'amenities_w_Additional_fees'){
			$_data = real_remove_underscore($aRow['amenities_w_Additional_fees']);
		}elseif($aColumns[$i] == 'number_of_pets_allowed'){
			$_data = real_remove_underscore($aRow['number_of_pets_allowed']);
		}elseif($aColumns[$i] == 'pet_size'){
			$_data = real_remove_underscore($aRow['pet_size']);
		}elseif($aColumns[$i] == 'max_pet_weight'){
			$_data = real_remove_underscore($aRow['max_pet_weight']);
		}elseif($aColumns[$i] == 'pet_restrictions'){
			$_data = real_remove_underscore($aRow['pet_restrictions']);
		}elseif($aColumns[$i] == 'group'){
			$_data = real_remove_underscore($aRow['group']);
		}elseif($aColumns[$i] == 'gas_emission'){
			$_data = real_remove_underscore($aRow['gas_emission']);
		}elseif($aColumns[$i] == 'egenry_efficient'){
			$_data = real_remove_underscore($aRow['egenry_efficient']);
		}elseif($aColumns[$i] == 'cable_TV'){
			$_data = real_remove_underscore($aRow['cable_TV']);
		}elseif($aColumns[$i] == 'computer'){
			$_data = real_remove_underscore($aRow['computer']);
		}elseif($aColumns[$i] == 'heating'){
			$_data = real_remove_underscore($aRow['heating']);
		}elseif($aColumns[$i] == 'internet'){
			$_data = real_remove_underscore($aRow['internet']);
		}elseif($aColumns[$i] == 'balcony'){
			$_data = real_remove_underscore($aRow['balcony']);
		}elseif($aColumns[$i] == 'grill'){
			$_data = real_remove_underscore($aRow['grill']);
		}elseif($aColumns[$i] == 'pool'){
			$_data = real_remove_underscore($aRow['pool']);
		}elseif($aColumns[$i] == 'parking'){
			$_data = real_remove_underscore($aRow['parking']);
		}elseif($aColumns[$i] == 'beach'){
			$_data = real_remove_underscore($aRow['beach']);
		}elseif($aColumns[$i] == 'train'){
			$_data = real_remove_underscore($aRow['train']);
		}elseif($aColumns[$i] == 'metro'){
			$_data = real_remove_underscore($aRow['metro']);
		}elseif($aColumns[$i] == 'bus'){
			$_data = real_remove_underscore($aRow['bus']);
		}elseif($aColumns[$i] == 'pharmacies'){
			$_data = real_remove_underscore($aRow['pharmacies']);
		}elseif($aColumns[$i] == 'bakery'){
			$_data = real_remove_underscore($aRow['bakery']);
		}elseif($aColumns[$i] == 'restraunt'){
			$_data = real_remove_underscore($aRow['restraunt']);
		}elseif($aColumns[$i] == 'coffee'){
			$_data = real_remove_underscore($aRow['coffee']);
		}elseif($aColumns[$i] == 'energy_efficiency'){
			$_data = real_remove_underscore($aRow['energy_efficiency']);
		}elseif($aColumns[$i] == 'property_manager'){
			$_data = real_remove_underscore($aRow['property_manager']);
		}elseif($aColumns[$i] == 'finishing'){
			$_data = real_remove_underscore($aRow['finishing']);
		}elseif($aColumns[$i] == 'furnished'){
			$_data = real_remove_underscore($aRow['furnished']);
		}elseif($aColumns[$i] == 'rent_availability'){
			$_data = real_remove_underscore($aRow['rent_availability']);
		}elseif($aColumns[$i] == 'rent_status'){
			$_data = real_remove_underscore($aRow['rent_status']);
		}elseif($aColumns[$i] == 'commission'){
			$_data = real_remove_underscore($aRow['commission']);
		}elseif($aColumns[$i] == 'Garden_SqM'){
			$_data = real_remove_underscore($aRow['Garden_SqM']);
		}elseif($aColumns[$i] == 'Front_Yard_SqM'){
			$_data = real_remove_underscore($aRow['Front_Yard_SqM']);
		}elseif($aColumns[$i] == 'kitchen'){
			$_data = real_remove_underscore($aRow['kitchen']);
		}elseif($aColumns[$i] == 'hydro_included'){
			$_data = real_remove_underscore($aRow['hydro_included']);
		}elseif($aColumns[$i] == 'water_included'){
			$_data = real_remove_underscore($aRow['water_included']);
		}elseif($aColumns[$i] == 'gas_included'){
			$_data = real_remove_underscore($aRow['gas_included']);
		}elseif($aColumns[$i] == 'reservation_payment'){
			$_data = real_remove_underscore($aRow['reservation_payment']);
		}elseif($aColumns[$i] == 'maintenance_fee'){
			$_data = real_remove_underscore($aRow['maintenance_fee']);
		}elseif($aColumns[$i] == 'contract_payment'){
			$_data = real_remove_underscore($aRow['contract_payment']);
		}elseif($aColumns[$i] == 'property_notes'){
			$_data = real_remove_underscore($aRow['property_notes']);
		}elseif($aColumns[$i] == 'compound'){
			$_data = real_remove_underscore($aRow['compound']);
		}

		$row[] = $_data;
	}

	if (isset($rel_property_style_color[$aRow['property_style']])) {
		$row['DT_RowClass'] = $rel_property_style_color[$aRow['property_style']];
	}

	$output['aaData'][] = $row;
}

