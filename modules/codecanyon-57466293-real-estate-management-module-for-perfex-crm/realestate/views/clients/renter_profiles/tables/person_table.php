<?php

defined('BASEPATH') or exit('No direct script access allowed');
$aColumns = [
	'id',
	'occupants_name',
	'1',
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_contact_occupants';

$where = [];
$join= [];
$where = ['AND contact_id=' . $contact_id];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'occupants_name', 'occupants_age', 'contact_id']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$row = [];
	$row[] = $aRow['id'];
	$row[] = '<span class="tw-font-semibold">'.$aRow['occupants_name'].'</span><br>'.$aRow['occupants_age'].' . '._l('real_years_of_age');

	$options = '';

	$options .= icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
		'onclick'    => 'edit_person(this,' . $aRow['id'] . '); return false;',
		'data-occupants_name'  => $aRow['occupants_name'],
		'data-occupants_age'  => $aRow['occupants_age'],
	]);

	$options .= icon_btn('#', 'fa fa-remove', 'btn-danger _delete', [
		'onclick'    => 'delete_person(' . $aRow['id'] . '); return false;',
		'data-original-title' => _l('delete'),
		'data-toggle' => 'tooltip',
		'data-placement' => 'top']);

	$row[] = $options;

	$output['aaData'][] = $row;
}

