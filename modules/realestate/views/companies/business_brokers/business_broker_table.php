<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'profile_image',
	'code',
	'name',
	'email',
	'phonenumber',
	'staff_id',
	'active',
	'created_date',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_companies';

$where = [];
$join= [];

$where[] = 'AND related_type = "business_broker"';

$staff_in_company = rel_check_staff_in_company();
$get_staff_user_id = get_staff_user_id();
if(is_admin()){
			// is admin: view all
}elseif($staff_in_company){
			// staff in company
	if(has_permission('real_business_broker', '', 'view')){
		$where[] = 'AND '.db_prefix().'real_companies.company_id = '.$staff_in_company;
	}elseif(has_permission('real_business_broker', '', 'view_own')){
		$where[] = 'AND '.db_prefix().'real_companies.staff_id = '.$get_staff_user_id;
	}else{
		$where[] = 'AND 1=2';
	}

}else{
			// staff not in construction company
	if(has_permission('real_business_broker', '', 'view')){
				// get all
		$where[] = 'AND '.db_prefix().'real_companies.is_company_admin = 1';
	}elseif(has_permission('real_business_broker', '', 'view_own')){
		$where[] = 'AND '.db_prefix().'real_companies.staff_id = '.$get_staff_user_id;
	}else{
		$where[] = 'AND 1=2';
	}
}


$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id']);

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
			$_data = company_profile_image($aRow['id'], ['property-owner-img']);

		}elseif ($aColumns[$i] == 'name') {
			if (has_permission('real_business_broker', '', 'edit')) {
				$code = '<a href="' . admin_url('realestate/add_edit_company/' . $aRow['id']) . '">' . $aRow['name'] . '</a>';
			}else{
				$code = $aRow['name'];
			}

			$code .= '<div class="row-options">';
			if (has_permission('real_business_broker', '', 'view') ||has_permission('real_business_broker', '', 'view_own') || has_permission('real_business_broker', '', 'create') || has_permission('real_business_broker', '', 'edit')) {
				$code .= '<a href="' . admin_url('realestate/add_edit_company/' . $aRow['id']) . '" >' . _l('view') . '</a>';
			}

			if (has_permission('real_business_broker', '', 'create') || has_permission('real_business_broker', '', 'edit')) {

				$code .= ' | <a href="' . admin_url('realestate/add_edit_company/' . $aRow['id'] . '?group=broker_staffs') . '">' . _l('real_staffs') . '</a>';

			}
			if (has_permission('real_business_broker', '', 'delete')) {
				$code .= ' | <a href="' . admin_url('realestate/delete_broker/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
			}
			$code .= '</div>';

			$_data = $code;


		}elseif($aColumns[$i] == 'email'){
			$_data =  $aRow['email'];

		}elseif($aColumns[$i] == 'phonenumber'){

			$_data =  $aRow['phonenumber'];

		}elseif($aColumns[$i] == 'staff_id'){

			$_data =  get_staff_full_name($aRow['staff_id']);

		}elseif($aColumns[$i] == 'active'){

			$checked = '';
            if ($aRow['active'] == 1) {
                $checked = 'checked';
            }

            $toggleActive = '<div class="onoffswitch">
                <input type="checkbox" ' . (( !has_permission('real_business_broker', '', 'edit') && !is_admin()) ? 'disabled' : '') . ' data-switch-url="' . admin_url() . 'realestate/change_construction_company_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . $checked . '>
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

