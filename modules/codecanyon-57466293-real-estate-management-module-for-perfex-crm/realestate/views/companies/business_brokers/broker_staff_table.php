<?php

defined('BASEPATH') or exit('No direct script access allowed');

$total_staffs = total_rows(db_prefix() . 'real_broker_staffs', ['company_id' => $company_id]);

$aColumns = [
	'id',
	'code',
	'firstname',
	'email',
	'phonenumber',
	'last_login',
	'active',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'real_broker_staffs';
$join         = [];
$join = [
];

$where = ['AND (company_id=' . $company_id.')'];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['company_id', 'lastname']);

$output  = $result['output'];
$rResult = $result['rResult'];
$get_broker_id = get_broker_id();
foreach ($rResult as $aRow) {
	$row = [];

	$row[] = $aRow['id'];
	$row[] = $aRow['code'];

	if (has_permission('real_estate_agent_staff', '', 'edit') || is_admin() || $get_broker_id = $aRow['id']) {	

		$rowName = '<a href="#" onclick="add_broker_staff(' . (int)$aRow['company_id'] . ',' . $aRow['id'] . ',\'updated\');return false;">' . $aRow['firstname'].' '.$aRow['lastname'] . '</a>';
	}else{
		$rowName = $aRow['firstname'].' '.$aRow['lastname'];
	}
	

	$rowName .= '<div class="row-options">';

	if (has_permission('real_estate_agent_staff', '', 'edit') || is_admin() || $get_broker_id = $aRow['id']) {	
		$rowName .= '<a href="#" onclick="add_broker_staff(' . (int)$aRow['company_id'] . ',' . $aRow['id'] . ',\'updated\');return false;">' . _l('edit') . '</a>';
	}
	

	if ((has_permission('rel_company_staff', '', 'delete') || has_permission('real_estate_agent_staff', '', 'delete') ) && $output['iTotalRecords'] > 1 && $aRow['id'] != $get_broker_id) {
		$rowName .= ' | <a href="#" onclick="delete_staff_member(' . $aRow['id'] . '); return false;" class="text-danger">' . _l('delete') . '</a>';
	}

	$rowName .= '</div>';


	$row[] = $rowName;


	$row[] = '<a href="mailto:' . $aRow['email'] . '">' . $aRow['email'] . '</a>';

	$row[] = '<a href="tel:' . $aRow['phonenumber'] . '">' . $aRow['phonenumber'] . '</a>';

	if ($aRow['last_login'] != null) {
		$row[] = '<span class="text-has-action is-date" data-toggle="tooltip" data-title="' . _dt($aRow['last_login']) . '">' . time_ago($aRow['last_login']) . '</span>';
	} else {
		$row[] = 'Never';
	}

	$checked = '';
	if ($aRow['active'] == 1) {
		$checked = 'checked';
	}
	$outputActive = '<div class="onoffswitch">
	<input type="checkbox" ' . ((( !has_permission('rel_company_staff', '', 'edit') && !has_permission('real_estate_agent_staff', '', 'edit') ) && !is_admin() || ( $aRow['id'] == get_broker_id() )) ? 'disabled' : '') . ' data-switch-url="' . admin_url() . 'realestate/change_staff_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . $checked . '>
	<label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
	</div>';

	$outputActive .= '<span class="hide">' . ($checked == 'checked' ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

	$row[] = $outputActive;

	$row['DT_RowClass'] = 'has-row-options';

	$output['aaData'][] = $row;
}
