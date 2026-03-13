<?php
defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'item_id',
	'staff_id',
	2,
	'commission',
	'status',
	'date_created',
	'date_updated',
	1,
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_request_brokerages';

$where = [];
$join= [];
$where[] = 'AND '.db_prefix().'real_request_brokerages.item_id = '.$item_id;

if(is_broker_logged_in()){
	$business_broker_id = get_business_broker_id();
	$where[] = 'AND '.db_prefix().'real_request_brokerages.broker_id = '.$business_broker_id;
}else{
	$staff_in_company = rel_check_staff_in_company();
	$get_staff_user_id = get_staff_user_id();
	if(is_admin()){
			// is admin: view all
	}elseif($staff_in_company){
			// staff in company
		if(has_permission('real_request_broker', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_request_brokerages.related_type = "company"';
			$where[] = 'AND '.db_prefix().'real_request_brokerages.related_id = '.$get_staff_user_id;
		}elseif(has_permission('real_request_broker', '', 'view')){
			$where[] = 'AND '.db_prefix().'real_request_brokerages.related_company_id = '.$staff_in_company;
		}else{
			$where[] = 'AND 1=2';
		}

	}else{
			// staff not in construction company
		if(has_permission('real_request_broker', '', 'view')){
				// get all
		}elseif(has_permission('real_request_broker', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_request_brokerages.created_id = '.$get_staff_user_id;

		}else{
			$where[] = 'AND 1=2';
		}
	}
}


$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['company_id', 'broker_id', 'is_company_admin']);

$output = $result['output'];
$rResult = $result['rResult'];


foreach ($rResult as $aRow) {
	$row = [];
	$row[] = $aRow['id'];
	$row[] = $aRow['item_id'];
	if($aRow['staff_id'] != 0){
		$row[] = get_staff_full_name($aRow['staff_id']);
	}else{
		$row[] = '---';
	}

	$related = '';
	if(is_numeric($aRow['company_id']) && $aRow['company_id'] != 0){
		$related = real_get_company_name($aRow['company_id']);
	}elseif(is_numeric($aRow['broker_id']) && $aRow['broker_id'] != 0){
		$related = real_get_company_name($aRow['broker_id']);
	}elseif(is_numeric($aRow['is_company_admin']) && $aRow['is_company_admin'] != 0){
		$related = _l('real_company_staff');
	}
	
	$row[] = $related;
	$row[] = $aRow['commission'];
	$row[] = $aRow['status'];
	$row[] = _dt($aRow['date_created']);
	$row[] = _dt($aRow['date_updated']);

	$options = '';

	if((has_permission('real_request_broker', '', 'delete') || is_admin() || is_broker_logged_in())){
		$options .= icon_btn('realestate/delete_request_broker/' . $aRow['id'].'/'.$aRow['item_id'], 'fa fa-remove', 'btn-danger _delete', ['data-original-title' => _l('delete'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']);
	}

	$row[] = $options;

	$output['aaData'][] = $row;
}

