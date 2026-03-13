<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'name',
	'monthly_listing_number',
	'rate',
	'description',
	'payment_type',
	'created_id',
	'date_created',
	'1',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_plans';

$where = [];
$join= [];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'name', 'monthly_listing_number',  'admin_id', 'created_id', 'active', 'date_created', 'date_updated', 'rate', 'description', 'role_id', 'read_only'
]);

$output = $result['output'];
$rResult = $result['rResult'];


foreach ($rResult as $aRow) {
	$row = [];
	$row[] = $aRow['id'];
	$row[] = $aRow['name'];
	$row[] = $aRow['monthly_listing_number'];
	$row[] = $aRow['rate'];
	$row[] = $aRow['description'];
	$row[] = _l($aRow['payment_type'] ?? '');
	$row[] = get_staff_full_name($aRow['created_id']);
	$row[] = _dt($aRow['date_created']);

	$options = '';

	if((has_permission('real_permission', '', 'edit') || has_permission('real_permission', '', 'create') || is_admin())){
		$options .= icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
			'onclick'    => 'edit_plan(this,' . $aRow['id'] . '); return false;',
			'data-payment_type' => $aRow['payment_type'],
			'data-name'  => $aRow['name'],
			'data-monthly_listing_number'  => $aRow['monthly_listing_number'],
			'data-read_only'  => $aRow['read_only'],
			'data-rate'  => $aRow['rate'],
			'data-description'  => $aRow['description'],
			'data-role_id'  => $aRow['role_id'],

		]);
	}

	if((has_permission('real_permission', '', 'delete') || is_admin())){
		$options .= icon_btn('realestate/delete_plan/' . $aRow['id'], 'fa fa-remove', 'btn-danger _delete', ['data-original-title' => _l('delete'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']);
	}

	$row[] = $options;

	$output['aaData'][] = $row;
}

