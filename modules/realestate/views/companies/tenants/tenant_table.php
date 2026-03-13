<?php

defined('BASEPATH') or exit('No direct script access allowed');

$get_base_currency =  get_base_currency();
if($get_base_currency){
	$base_currency_id = $get_base_currency->id;
}else{
	$base_currency_id = 0;
}

$aColumns = [
	'clientid',
	db_prefix() . 'real_requests.id as id',
	db_prefix() . 'items.commodity_code as property_code',
	'item_id',
	'total',
	'code',
	'CONCAT(firstname, " ", lastname) as fullname',
	
	'invoice_id',
	'contract_id',
	'contract_total',
	'term_month',
	'date',
	db_prefix() . 'real_requests.status as status',
	db_prefix() . 'real_requests.datecreated as datecreated',

];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_requests';

$where = [];
$join= [];

$join = [
	'LEFT JOIN ' . db_prefix() . 'items ON '.db_prefix().'items.id = ' . db_prefix() . 'real_requests.item_id',
	'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'contacts.userid=' . db_prefix() . 'real_requests.clientid AND ' . db_prefix() . 'contacts.is_primary=1',
	'LEFT JOIN ' . db_prefix() . 'clients ON ' . db_prefix() . 'clients.userid=' . db_prefix() . 'real_requests.clientid',
];

array_push($where, 'AND '.db_prefix().'real_requests.request_type = "'.$request_type.'" AND '.db_prefix().'real_requests.status != 1 AND '.db_prefix().'real_requests.status != 3 AND '.db_prefix().'real_requests.status != 4 AND '.db_prefix().'real_requests.status != 5');

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
		if(has_permission('real_tenant', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_requests.related_type = "staff"';
			$where[] = 'AND '.db_prefix().'real_requests.related_id = '.$get_staff_user_id;
		}elseif(has_permission('real_tenant', '', 'view')){
			$where[] = 'AND '.db_prefix().'real_requests.company_id = '.$staff_in_company;
		}else{
			$where[] = 'AND 1=2';
		}

	}else{
			// staff not in construction company
		if(has_permission('real_tenant', '', 'view')){
				// get all
			$where[] = 'AND '.db_prefix().'real_requests.is_company_admin = 1 ';
				
		}elseif(has_permission('real_tenant', '', 'view_own')){
			$where[] = 'AND '.db_prefix().'real_requests.related_type = "staff"';
			$where[] = 'AND '.db_prefix().'real_requests.related_id = '.$get_staff_user_id;

		}else{
			$where[] = 'AND 1=2';
		}
	}
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['code', 'contract_is_recurring', 'term_month', db_prefix().'items.id as itemid', db_prefix() . 'contacts.id as contact_id', 'duedate','email',
	db_prefix() . 'clients.phonenumber as phonenumber',]);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$rental_type = get_property_name($aRow['item_id'], false, true);
	$rental_type_s = '';

	$_rental_type = '';
	$rental_type_s = $rental_type;
	if($rental_type != '' && $aRow['term_month'] > 1){
		$rental_type_s = $rental_type.'s';
		$_rental_type = ' per '.$rental_type;
	}

	$unpaid_invoices = $this->ci->realestate_model->get_unpaid_invoices($aRow['id']);

	$row = [];

	$row[] = '<a href="' . admin_url('clients/client/' . $aRow['clientid'] ).'" >' .get_company_name($aRow['clientid']). '</a>';
	$row[] = $aRow['property_code'];

	$row[] = '<a href="' . $site_url.('property_listing_detail/' . $aRow['item_id'] ).'" >' .get_property_name($aRow['item_id']). '</a>';
	$row[] = app_format_money($aRow['total'], $base_currency_id). $_rental_type;

	$name = '<a href="'.$site_url.('rent_requests#'.$aRow['id']).'" onclick="init_property_request(' . $aRow['id'] . '); return false;">' . $aRow['code'] . '</a>';
	$row[] = $name;

	// Primary contact
	$row[] = ($aRow['contact_id'] ? '<a href="' . admin_url('clients/client/' . $aRow['clientid'] . '?contactid=' . $aRow['contact_id']) . '" target="_blank">' . e(trim($aRow['fullname'])) . '</a>' : '').'<br>'.($aRow['email'] ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>' : '').'<br>'.($aRow['phonenumber'] ? '<a href="tel:' . e($aRow['phonenumber']) . '">' . e($aRow['phonenumber']) . '</a>' : '');

	if(count($unpaid_invoices) > 0){
		$list_unpaid_invoice = '';
		$list_unpaid_invoice = '<ul class="">';
		foreach ($unpaid_invoices as $key => $unpaid_invoice) {
			$list_unpaid_invoice .= '<li class="padding-5">
			<a href="'.admin_url('invoices/list_invoices/' . $unpaid_invoice['id']).'"
			class="tw-font-semibold" target="_blank">'. e(format_invoice_number($unpaid_invoice['id'])).'
			</a>';
			$list_unpaid_invoice .= '<span class="pull-right bold text-danger">'. e(app_format_money($unpaid_invoice['total'], $unpaid_invoice['currency_name'])).'</span>';
			$list_unpaid_invoice .= '<br><span class="bold">' . e(_d($unpaid_invoice['date'])) . '</span>';
			$list_unpaid_invoice .= '</li>';
		}
		$list_unpaid_invoice .= '</ul>';
		$row[] = $list_unpaid_invoice;
	}else{
		$row[] = '';
	}

	if($aRow['contract_id'] != 0){
		$row[] = '<a href="'.admin_url('contracts/contract/'.$aRow['contract_id']).'" data-original-title="View Contract" data-toggle="tooltip" data-placement="top">
		'.get_contract_name($aRow['contract_id']).'
		</a>';
	}else{
		$row[] = '';
	}

	$row[] = app_format_money($aRow['contract_total'], $base_currency_id);
	$row[] = ($aRow['term_month']).$rental_type_s;
	$row[] = _d($aRow['date']) .' - '._d($aRow['duedate']);
	$row[] = render_property_request_status_html($aRow['id'], 'order', $aRow['status'] );


	$row[] = _dt($aRow['datecreated']);

	$output['aaData'][] = $row;
}

