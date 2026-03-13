<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php 
	$table_name = 'property_listing_table';
	if(isset($table_name_custom)){
	$table_name = $table_name_custom;
	}
 ?>
<?php render_datatable(array(
	_l('id'),
	_l('real_photo'),
	_l('real_property_code'),
	array(
		'name'=>_l('real_listing'),
		'th_attrs' => [
			'style' => 'min-width:100px',
		],
	),
	_l('real_status'),
	_l('real_city'),
	_l('real_state'),
	_l('real_property_style'),
	_l('real_listing_type'),
	array(
		'name'=>_l('real_ownership'),
		'th_attrs' => [
			'style' => 'min-width:100px',
		],
	),
	_l('rel_lot_size_acres'),
	_l('real_sqFt_total'),
	_l('real_transaction_type'),
	_l('real_property_price'),
	_l('real_reservation_payment'),
	_l('real_maintenance_fee'),
	_l('real_property_condition'),
	_l('real_new_construction'),
	_l('real_furnished'),
	_l('real_finishing'),
	array(
		'name'=>_l('real_utilities'),
		'th_attrs' => [
			'style' => 'min-width:180px',
		],
	),
	_l('real_appliances_included'),
	_l('real_kitchen'),
	_l('real_Beds'),
	_l('real_full_baths'),
	_l('real_half_baths'),
	_l('real_garage'),
	array(
		'name'=>_l('real_address'),
		'th_attrs' => [
			'style' => 'min-width:150px',
		],
	),
	_l('real_year_built'),
	array(
		'name'=>_l('real_created_by'),
		'th_attrs' => [
			'style' => 'min-width:150px',
		],
	),
	array(
		'name'=>_l('real_created_date'),
		'th_attrs' => [
			'style' => 'min-width:150px',
		],
	),
	_l('real_fireplace_description'),
	array(
		'name'=>_l('real_sewer'),
		'th_attrs' => [
			'style' => 'min-width:200px',
		],
	),
	array(
		'name'=>_l('real_water'),
		'th_attrs' => [
			'style' => 'min-width:100px',
		],
	),
	_l('real_heating_and_fuel'),
	_l('real_air_conditioning'),
	_l('real_electrical_Service'),
	_l('real_security_features'),
	array(
		'name'=>_l('real_accessibility_features'),
		'th_attrs' => [
			'style' => 'min-width:220px',
		],
	),
	_l('real_floor_covering'),
	array(
		'name'=>_l('real_ceiling_type'),
		'th_attrs' => [
			'style' => 'min-width:220px',
		],
	),
	_l('real_window_Features'),
	array(
		'name'=>_l('real_school'),
		'th_attrs' => [
			'style' => 'min-width:220px',
		],
	),
	array(
		'name'=>_l('real_landmarks'),
		'th_attrs' => [
			'style' => 'min-width:150px',
		],
	),

	_l('real_street_number'),
	_l('real_street_type'),
	_l('real_street_dir_pos'),
	_l('real_unit_number'),
	_l('real_zip'),
	_l('real_zip_4'),
	_l('real_country'),
	_l('real_total_of_floors'),
	_l('real_latitude'),
	_l('real_longitude'),
	_l('real_use_code'),
	_l('real_proj_completion_date'),
	_l('real_sqFt_heated'),
	_l('real_sqFt_heated_source'),
	_l('real_SqFt_total_source'),
	_l('real_fireplace'),
	_l('real_owner_name'),
	_l('real_owner_phone'),
	_l('real_gas_emission'),
	_l('real_egenry_efficient'),
	_l('real_cable_TV'),
	_l('real_computer'),
	_l('real_heating'),
	_l('real_internet'),
	_l('real_energy_efficiency'),
	_l('real_commission'),
	_l('real_hydro_included'),
	_l('real_water_included'),
	_l('real_gas_included'),
	_l('real_contract_payment'),


), $table_name,

array('customizable-table'),
array(
	'id'=>'table-'.$table_name,
	'data-last-order-identifier'=> $table_name,
	'data-default-order'=>get_table_last_order($table_name),
	)); ?>