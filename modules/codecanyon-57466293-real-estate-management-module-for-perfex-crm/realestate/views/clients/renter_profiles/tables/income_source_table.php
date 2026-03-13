<?php

defined('BASEPATH') or exit('No direct script access allowed');
$base_currency_id = get_base_currency_id();

$aColumns = [
	'id',
	'income_type',
	'1',
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_contact_incomes';

$where = [];
$join= [];
$where = ['AND contact_id=' . $contact_id];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'income_frequency', 'amount', 'contact_id']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$row = [];
	$row[] = $aRow['id'];
	$row[] = '<span class="tw-font-semibold">'.$aRow['income_type'].'</span><br>'.app_format_money($aRow['amount'],$base_currency_id).'  '._l('real_per').' '.$aRow['income_frequency'].' . '._l('real_after_tax');

	$options = '';

	$options .= icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
		'onclick'    => 'edit_income_source(this,' . $aRow['id'] . '); return false;',
		'data-income_type' => $aRow['income_type'],
		'data-income_frequency'  => $aRow['income_frequency'],
		'data-amount'  => $aRow['amount'],
	]);

	$options .= icon_btn('#', 'fa fa-remove', 'btn-danger _delete', [
		'onclick'    => 'delete_income_source(' . $aRow['id'] . '); return false;',
		'data-original-title' => _l('delete'),
		'data-toggle' => 'tooltip',
		'data-placement' => 'top']);

	$row[] = $options;

	$output['aaData'][] = $row;
}

