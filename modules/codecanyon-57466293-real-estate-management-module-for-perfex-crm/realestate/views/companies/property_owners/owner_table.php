<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'profile_image',
	'code',
	'name',
	'email',
	'phonenumber',
	'related_id',
	'active',
	'created_date',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_property_owners';

$where = [];
$join= [];
if($company_id != 0 && $company_id != 'undefined'){
	$where = ['AND company_id=' . $company_id];
}

if(is_broker_logged_in()){
	$business_broker_id = get_business_broker_id();
	$where[] = 'AND '.db_prefix().'real_property_owners.broker_id = '.$business_broker_id;
}else{
	$staff_in_company = rel_check_staff_in_company();
	$get_staff_user_id = get_staff_user_id();
	if(is_admin()){
			// is admin: view all
	}elseif($staff_in_company){
			// staff in company
		if(has_permission('real_property_owner', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_property_owners.related_type = "company"';
			$where[] = 'AND '.db_prefix().'real_property_owners.related_id = '.$get_staff_user_id;
		}elseif(has_permission('real_property_owner', '', 'view')){
			$where[] = 'AND '.db_prefix().'real_property_owners.company_id = '.$staff_in_company;
		}else{
			$where[] = 'AND 1=2';
		}

	}else{
			// staff not in construction company
		if(has_permission('real_property_owner', '', 'view')){
				// get all
			$where[] = 'AND '.db_prefix().'real_property_owners.is_company_admin = 1';
		}elseif(has_permission('real_property_owner', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_property_owners.related_type = "staff"';
			$where[] = 'AND '.db_prefix().'real_property_owners.related_id = '.$get_staff_user_id;

		}else{
			$where[] = 'AND 1=2';
		}
	}
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'related_type']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$row = [];

	for ($i = 0; $i < count($aColumns); $i++) {

		if($aColumns[$i] == 'id') {
			$_data = $aRow['id'];

		}if($aColumns[$i] == 'code') {
			$_data = $aRow['code'];

		}if($aColumns[$i] == 'profile_image') {
			$_data = owner_profile_image($aRow['id'], ['property-owner-img']);

		}elseif ($aColumns[$i] == 'name') {
			if (has_permission('real_property_owner', '', 'edit') || is_broker_logged_in()) {
				$code = '<a href="' . $site_url.('add_edit_owner/' . $aRow['id']) . '">' . $aRow['name'] . '</a>';
			}else{
				$code = $aRow['name'];
			}

			$code .= '<div class="row-options">';

			if (has_permission('real_property_owner', '', 'create') || has_permission('real_property_owner', '', 'edit') || is_broker_logged_in()) {
				$code .= '<a href="' . $site_url.('add_edit_owner/' . $aRow['id']) . '">' . _l('edit') . '</a>';
			}
			if (has_permission('real_property_owner', '', 'delete') || is_broker_logged_in()) {
				$code .= ' | <a href="' . $site_url.('delete_owner/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
			}
			$code .= '</div>';

			$_data = $code;

		}elseif($aColumns[$i] == 'email'){
			$_data =  $aRow['email'];

		}elseif($aColumns[$i] == 'phonenumber'){

			$_data =  $aRow['phonenumber'];

		}elseif($aColumns[$i] == 'related_id'){
			if($aRow['related_type'] == 'business_broker'){
				$_data =  get_broker_name($aRow['related_id']);
			}else{
				$_data =  get_staff_full_name($aRow['related_id']);
			}

		}elseif($aColumns[$i] == 'active'){

			$checked = '';
			if ($aRow['active'] == 1) {
				$checked = 'checked';
			}

			$toggleActive = '<div class="onoffswitch">
			<input type="checkbox" ' . (( !has_permission('real_property_owner', '', 'edit') && !is_admin() && !is_broker_logged_in()) ? 'disabled' : '') . ' data-switch-url="' . $site_url . 'change_owner_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . $checked . '>
			<label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
			</div>';

    		// For exporting
			$toggleActive .= '<span class="hide">' . ($aRow['active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

			$_data = $toggleActive;

		}elseif($aColumns[$i] == 'created_date'){
			$_data =  _dt($aRow['created_date']);

		}

		$row[] = $_data;
	}

	$output['aaData'][] = $row;
}

