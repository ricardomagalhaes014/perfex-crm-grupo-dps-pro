<?php

defined('BASEPATH') or exit('No direct script access allowed');

$total_staffs = total_rows(db_prefix() . 'staff', ['company_id' => $company_id]);

$aColumns = [
	'staffid',
	'staff_identifi',
	'firstname',
	'email',
	'phonenumber',
	'is_approval_manager',
	'require_approvals',
	'active',
	'mark_public',
	'last_login',
	'name',
];

$sIndexColumn = 'staffid';
$sTable       = db_prefix() . 'staff';
$join         = [];
$join = [
	'LEFT JOIN ' . db_prefix() . 'roles t1 ON t1.roleid = ' . db_prefix() . 'staff.role'
];

$where = ['AND (company_id=' . $company_id.')'];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['company_id', 'lastname', 'staff_type']);

$output  = $result['output'];
$rResult = $result['rResult'];
$get_staff_user_id = get_staff_user_id();
$staff_in_company = rel_check_staff_in_company();

foreach ($rResult as $aRow) {
	$row = [];

	$row[] = $aRow['staffid'];
	$row[] = $aRow['staff_identifi'];

	if (has_permission('real_estate_agent_staff', '', 'edit') || has_permission('staff', '', 'edit') || is_admin() || $get_staff_user_id == $aRow['staffid']) {	
		if(!$staff_in_company){
			// company admin
			$rowName = '<a href="'.admin_url('staff/member/'.$aRow['staffid']).'" >' . $aRow['firstname'].' '.$aRow['lastname'] . '</a>';
		}else{
			if($staff_in_company){
			// agent
				$rowName = '<a href="#" onclick="add_staff(' . (int)$aRow['company_id'] . ',' . $aRow['staffid'] . ',\'updated\');return false;">' . $aRow['firstname'].' '.$aRow['lastname'] . '</a>';
			}elseif(has_permission('real_estate_agent_staff', '', 'edit')){
			// agent
				$rowName = '<a href="#" onclick="add_staff(' . (int)$aRow['company_id'] . ',' . $aRow['staffid'] . ',\'updated\');return false;">' . $aRow['firstname'].' '.$aRow['lastname'] . '</a>';
			}
		}
	}else{
		$rowName = $aRow['firstname'].' '.$aRow['lastname'];
	}

	$rowName .= '<div class="row-options">';

	if (has_permission('real_estate_agent_staff', '', 'edit') || has_permission('staff', '', 'edit') || is_admin() || $get_staff_user_id == $aRow['staffid']) {
		if(!$staff_in_company){
			// company admin
			$rowName .= '<a href="'.admin_url('staff/member/'.$aRow['staffid']).'" >' . _l('edit') . '</a>';
		}else{
			if($staff_in_company){
			// agent
				$rowName .= '<a href="#" onclick="add_staff(' . (int)$aRow['company_id'] . ',' . $aRow['staffid'] . ',\'updated\');return false;">' . _l('edit') . '</a>';
			}elseif(has_permission('real_estate_agent_staff', '', 'edit')){
			// agent
				$rowName .= '<a href="#" onclick="add_staff(' . (int)$aRow['company_id'] . ',' . $aRow['staffid'] . ',\'updated\');return false;">' . _l('edit') . '</a>';
			}
		}
	}

	if ((has_permission('real_estate_agent_staff', '', 'delete') || has_permission('staff', '', 'delete') ) && $output['iTotalRecords'] > 1 && $aRow['staffid'] != $get_staff_user_id) {
		$rowName .= ' | <a href="#" onclick="delete_staff_member(' . $aRow['staffid'] . '); return false;" class="text-danger">' . _l('delete') . '</a>';
	}

	$rowName .= '</div>';


	$row[] = $rowName;


	$row[] = '<a href="mailto:' . $aRow['email'] . '">' . $aRow['email'] . '</a>';

	$row[] = '<a href="tel:' . $aRow['phonenumber'] . '">' . $aRow['phonenumber'] . '</a>';


	$checked = '';
	if ($aRow['is_approval_manager'] == 1) {
		$checked = 'checked';
	}
	$outputActive = '<div class="onoffswitch">
	<input type="checkbox" ' . (( ( !has_permission('real_estate_agent_staff', '', 'edit') && !has_permission('staff', '', 'edit')) && !is_admin()) ? 'disabled' : '') . ' data-switch-url="' . admin_url() . 'realestate/change_staff_is_approval_manager" name="onoffswitch" class="onoffswitch-checkbox" id="cstaff_is_approval_manager_' . $aRow['staffid'] . '" data-id="' . $aRow['staffid'] . '" ' . $checked . '>
	<label class="onoffswitch-label" for="cstaff_is_approval_manager_' . $aRow['staffid'] . '"></label>
	</div>';
	$row[] = $outputActive;

	$checked = '';
	if ($aRow['require_approvals'] == 1) {
		$checked = 'checked';
	}
	$outputActive = '<div class="onoffswitch">
	<input type="checkbox" ' . (( ( !has_permission('real_estate_agent_staff', '', 'edit') && !has_permission('staff', '', 'edit')) && !is_admin()) ? 'disabled' : '') . ' data-switch-url="' . admin_url() . 'realestate/change_staff_require_approvals" name="onoffswitch" class="onoffswitch-checkbox" id="cstaff_require_approvals_' . $aRow['staffid'] . '" data-id="' . $aRow['staffid'] . '" ' . $checked . '>
	<label class="onoffswitch-label" for="cstaff_require_approvals_' . $aRow['staffid'] . '"></label>
	</div>';
	$row[] = $outputActive;


	$checked = '';
	if ($aRow['active'] == 1) {
		$checked = 'checked';
	}
	$outputActive = '<div class="onoffswitch">
	<input type="checkbox" ' . ((( !has_permission('real_estate_agent_staff', '', 'edit') ) && !is_admin() || ( $aRow['staffid'] == get_staff_user_id() )) ? 'disabled' : '') . ' data-switch-url="' . admin_url() . 'realestate/change_staff_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['staffid'] . '" data-id="' . $aRow['staffid'] . '" ' . $checked . '>
	<label class="onoffswitch-label" for="c_' . $aRow['staffid'] . '"></label>
	</div>';

	$outputActive .= '<span class="hide">' . ($checked == 'checked' ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

	$row[] = $outputActive;

	$checked = '';
	if ($aRow['mark_public'] == 1) {
		$checked = 'checked';
	}
	$outputActive = '<div class="onoffswitch">
	<input type="checkbox" ' . (( ( !has_permission('real_estate_agent_staff', '', 'edit') && !has_permission('staff', '', 'edit'))) ? 'disabled' : '') . ' data-switch-url="' . admin_url() . 'realestate/change_staff_public" name="onoffswitch" class="onoffswitch-checkbox" id="cstaff_' . $aRow['staffid'] . '" data-id="' . $aRow['staffid'] . '" ' . $checked . '>
	<label class="onoffswitch-label" for="cstaff_' . $aRow['staffid'] . '"></label>
	</div>';
	$row[] = $outputActive;


	if ($aRow['last_login'] != null) {
		$row[] = '<span class="text-has-action is-date" data-toggle="tooltip" data-title="' . _dt($aRow['last_login']) . '">' . time_ago($aRow['last_login']) . '</span>';
	} else {
		$row[] = 'Never';
	}

	$row[] = $aRow['name'];


	$row['DT_RowClass'] = 'has-row-options';

	$output['aaData'][] = $row;
}
