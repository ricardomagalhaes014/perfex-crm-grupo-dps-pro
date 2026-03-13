<?php
defined('BASEPATH') or exit('No direct script access allowed');
$months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
$today = date('Y-m-d H:i:s');
$aColumns = [
    'template_id',
    'template_name',
    'language',
    'status',
    '1'
];
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'lead_manager_whatsapp_templates';
$join         = [];
$where  = [];
$additionalColumns = [];
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalColumns);
$output  = $result['output'];
$rResult = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    $row[] = $aRow['template_id'];
    $row[] = $aRow['template_name'];
    $row[] = $aRow['language'];
    $row[] = $aRow['status'];
    $row[]= '<ul class="list-inline"><li><a href="javascript:void(0);" onclick="banner('.$aRow['id'].', this);" data-title="'.$aRow['alt_name'].'" data-toggle="tooltip" data-title="'._l('mlm_edit_banner_link_tooltip').'" ><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a><li><a href="javascript:void(0);" onclick="delBanner(this);" data-toggle="tooltip" data-title="'._l('mlm_delete_banner_link_tooltip').'" data-url="'.admin_url('mlm/tools/delBanner/'.$aRow['id']).'"><i class="fa fa-trash" aria-hidden="true"></i></a></li></ul>';
    $row['DT_RowId'] = 'withdrawal_' . $aRow['id'];
    $row['DT_RowClass'] = 'alert-info';
    if (isset($row['DT_RowClass'])) {
        $row['DT_RowClass'] .= 'has-row-options';
    } else {
        $row['DT_RowClass'] .= 'has-row-options';
    }
    $output['aaData'][] = $row;
}
