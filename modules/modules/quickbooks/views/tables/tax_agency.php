<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
	'id',
	'agency_name',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'qb_agencies';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable);
$output = $result['output'];
$rResult = $result['rResult'];
$k = 1;
foreach ($rResult as $aRow) {

	$row = [];
	for ($i = 0; $i < count($aColumns); $i++) {
		$_data = $aRow[$aColumns[$i]];

		if ($aColumns[$i] == 'id') {
			$_data = $k;
		}

		$row[] = $_data;
	}

	$options = icon_btn('#' . $aRow['id'], 'pencil-square-o', 'btn-default', [
		'data-toggle' => 'modal',
		'data-target' => '#tax_agency_modal',
		'data-id' => $aRow['id'],
	]);

	$row[] = $options .= icon_btn('quickbooks/tax_agency/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');

	$output['aaData'][] = $row;
	$k++;
}
