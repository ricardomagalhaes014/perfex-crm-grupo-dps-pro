<?php

defined('BASEPATH') or exit('No direct script access allowed');

$get_base_currency =  get_base_currency();
if($get_base_currency){
	$base_currency_id = $get_base_currency->id;
}else{
	$base_currency_id = 0;
}

$aColumns = [
	'id',
	'code',
	'item_id',
	'clientid',
	'total',
	'contract_total',
	'term_month',
	'datecreated',
	'date',
	'duedate',
	'status',
	'contract_id',
	'invoice_id',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_requests';

$where = [];
$join= [];

array_push($where, 'AND '.db_prefix().'real_requests.request_type = "'.$request_type.'"');

$client_filter = $this->ci->input->post('client_filter');
if (isset($client_filter)) {
	$where_client_ft = '';
	foreach ($client_filter as $client_id) {
		if ($client_id != '') {
			if ($where_client_ft == '') {
				$where_client_ft .= 'AND ('.db_prefix().'real_requests.clientid = "' . $client_id . '"';
			} else {
				$where_client_ft .= 'or '.db_prefix().'real_requests.clientid = "' . $client_id . '"';
			}
		}
	}
	if ($where_client_ft != '') {
		$where_client_ft .= ')';
		array_push($where, $where_client_ft);
	}
}

if(isset($contract_id) && $contract_id > 0){
}

if(isset($client_project) && $client_project > 0){
	array_push($where, 'AND '.db_prefix().'real_requests.clientid = '.$client_project);
}

if(is_broker_logged_in()){
	$business_broker_id = get_business_broker_id();
	$where[] = 'AND '.db_prefix().'real_requests.broker_id = '.$business_broker_id;
}else{
	$staff_in_company = rel_check_staff_in_company();
	$get_staff_user_id = get_staff_user_id();
	if(is_admin()){
			// is admin: view all
	}elseif($staff_in_company){
			// staff in company
		if(has_permission('real_buy_request', '', 'view_own') || has_permission('real_rent_request', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_requests.related_type = "staff"';
			$where[] = 'AND '.db_prefix().'real_requests.related_id = '.$get_staff_user_id;
		}elseif(has_permission('real_buy_request', '', 'view') || has_permission('real_rent_request', '', 'view')){
			$where[] = 'AND '.db_prefix().'real_requests.company_id = '.$staff_in_company;
		}else{
			$where[] = 'AND 1=2';
		}

	}else{
			// staff not in construction company
		if(has_permission('real_buy_request', '', 'view') || has_permission('real_rent_request', '', 'view')){
				$where[] = 'AND '.db_prefix().'real_requests.is_company_admin = 1';
		}elseif(has_permission('real_buy_request', '', 'view_own') || has_permission('real_rent_request', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_requests.related_type = "staff"';
			$where[] = 'AND '.db_prefix().'real_requests.related_id = '.$get_staff_user_id;

		}else{
			$where[] = 'AND 1=2';
		}
	}
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['code', 'contract_is_recurring', 'term_month','request_type']);

$output = $result['output'];
$rResult = $result['rResult'];
$viewuri = $_SERVER['REQUEST_URI'];

foreach ($rResult as $aRow) {
	$rental_type = get_property_name($aRow['item_id'], false, true);
	$rental_type_s = '';

	$_rental_type = '';
	$rental_type_s = $rental_type;
	if($rental_type != '' && $aRow['term_month'] > 1){
		$rental_type_s = $rental_type.'s';
		$_rental_type = ' per '.$rental_type;
	}

	$row = [];
	$name = '<a href="'.$site_url.('get_property_request_data_ajax/'.$aRow['id']).'" onclick="init_property_request(' . $aRow['id'] . '); return false;">' . $aRow['code'] . '</a>';

	$name .= '<div class="row-options">';
	$name .= '<a href="#" onclick="init_property_request(' . $aRow['id'] . '); return false;">' . _l('view') . '</a>';

	if((has_permission('real_buy_request', '', 'edit') || has_permission('real_rent_request', '', 'edit') || is_admin() || is_broker_logged_in() )){

		$name .= ' | <a href="' . $site_url.('add_edit_property_request/' . $aRow['id'] ).'" >' . _l('edit') . '</a>';
	}

	if ((has_permission('real_buy_request', '', 'delete') || has_permission('real_rent_request', '', 'delete') || is_admin() || is_broker_logged_in() ) ) {
		if($aRow['contract_id'] == 0){
			$name .= ' | <a href="' . $site_url.('delete_property_request/' . $aRow['id'] ).'" class="text-danger _delete" >' . _l('delete') . '</a>';
		}
	}
	$name .= '</div>';
	$row[] = $name;

	$row[] = '<a href="' . $site_url.('property_listing_detail/' . $aRow['item_id'] ).'" >' .get_property_name($aRow['item_id']). '</a>';

	$row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid'] ).'" >' .get_company_name($aRow['clientid']). '</a>';

	$row[] = app_format_money($aRow['total'], $base_currency_id);
	$row[] = app_format_money($aRow['contract_total'], $base_currency_id);
	$row[] = ($aRow['term_month']).$rental_type_s;
	$row[] = _dt($aRow['datecreated']);
	$row[] = _d($aRow['date']);
	$row[] = _d($aRow['duedate']);

	$row[] = render_property_request_status_html($aRow['id'], 'order', $aRow['status'] );

	if($aRow['contract_id'] != 0){
		if(is_broker_logged_in()){
			$row[] = '<a href="'.site_url('realestate/broker/contract/'.$aRow['contract_id']).'" data-original-title="View Contract" data-toggle="tooltip" data-placement="top">
			'.get_contract_name($aRow['contract_id']).'
			</a>';
		}else{

			$row[] = '<a href="'.admin_url('contracts/contract/'.$aRow['contract_id']).'" data-original-title="View Contract" data-toggle="tooltip" data-placement="top">
			'.get_contract_name($aRow['contract_id']).'
			</a>';
		}
	}elseif($aRow['contract_id'] == 0 && $aRow['status'] == 2){
		$_convert_to_contract = '';
		if((has_permission('real_buy_request', '', 'create') || has_permission('real_buy_request', '', 'edit') ||has_permission('real_rent_request', '', 'create') || has_permission('real_rent_request', '', 'edit') || is_admin() || is_broker_logged_in() )){
			$_convert_to_contract = '<a href="'.$site_url.('convert_to_contract/'.$aRow['id']).'" class="btn btn-sm btn-success text-right mright5">'._l('real_request_convert_to_contract').'</a>';
		}
		$row[] = $_convert_to_contract;
	}else{
		$row[] = '';
	}

	if($aRow['invoice_id'] != 0){
		if(is_broker_logged_in()){
			$row[] = '<a href="'.admin_url('realestate/broker/list_invoices#'.$aRow['invoice_id']).'" data-original-title="View Invoice" data-toggle="tooltip" data-placement="top">
			'.format_invoice_number($aRow['invoice_id']).'
			</a>';
		}else{

			$row[] = '<a href="'.admin_url('invoices/list_invoices/'.$aRow['invoice_id']).'" data-original-title="View Invoice" data-toggle="tooltip" data-placement="top">
			'.format_invoice_number($aRow['invoice_id']).'
			</a>';
		}
	}elseif($aRow['invoice_id'] == 0 && $aRow['status'] == 2){
		$_convert_to_invoice = '';
		if((has_permission('real_buy_request', '', 'create') || has_permission('real_buy_request', '', 'edit') || has_permission('real_rent_request', '', 'create') || has_permission('real_rent_request', '', 'edit') || is_admin() || is_broker_logged_in() )){
			$_convert_to_invoice = '<a href="'.$site_url.('convert_to_invoice/'.$aRow['id']).'" class="btn btn-sm btn-success text-right mright5">'._l('real_request_convert_to_invoice').'</a>';
		}
		$row[] = $_convert_to_invoice;

	}else{
		$row[] = '';
	}

	if($aRow['request_type'] == 'rent'){
		$_date_end = date('Y-m-d', strtotime($aRow['duedate']));
		if ($_date_end < date('Y-m-d')) {
			$row['DT_RowClass'] = 'danger';
		}
	}

	$output['aaData'][] = $row;
}

