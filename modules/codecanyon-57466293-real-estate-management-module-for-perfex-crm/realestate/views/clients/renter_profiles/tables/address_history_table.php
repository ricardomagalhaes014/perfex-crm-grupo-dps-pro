<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'address',
	'1',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'real_contact_address_histories';

$where = [];
$join= [];
$where = ['AND contact_id=' . $contact_id];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'contact_id', 'latitude', 'longitude', 'move_in', 'move_out']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
	$row = [];
	$row[] = $aRow['id'];
	$row[] = '<span class="tw-font-semibold">'.$aRow['address'].'</span><br>'.$aRow['move_in'].' - '.$aRow['move_out'];
	$options = '';

	$options .= icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
		'onclick'    => 'edit_address_history(this,' . $aRow['id'] . '); return false;',
		'data-address' => $aRow['address'],
		'data-latitude'  => $aRow['latitude'],
		'data-longitude'  => $aRow['longitude'],
		'data-move_in'  => ($aRow['move_in'] != '' && $aRow['move_in'] != null) ? _d($aRow['move_in']) : '',
		'data-move_out'  => _d($aRow['move_out']),
	]);

	$options .= icon_btn('#', 'fa fa-remove', 'btn-danger _delete', [
		'onclick'    => 'delete_address_history(' . $aRow['id'] . '); return false;',
		'data-original-title' => _l('delete'),
		 'data-toggle' => 'tooltip',
		  'data-placement' => 'top']);

	$row[] = $options;

	$output['aaData'][] = $row;
}

