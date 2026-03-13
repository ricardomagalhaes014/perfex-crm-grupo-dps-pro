<?php

defined('BASEPATH') or exit('No direct script access allowed');
$aColumns     = ['service_name', 'created_by','created_datetime'];
$sIndexColumn = 'id';
$sTable       = db_prefix().'meeting_services';
$result       = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], ['id']);
$output       = $result['output'];
$rResult      = $result['rResult'];
foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); ++$i) {
        if($aColumns[$i] == 'service_name'){
            $_data = '<a href="#" data-toggle="modal" data-target="#m_service_modal" data-id="'.$aRow['id'].'">'.$aRow[$aColumns[$i]].'</a>';
        }elseif($aColumns[$i] == 'created_by'){
            $_data = get_staff_full_name($aRow[$aColumns[$i]]);
        }elseif($aColumns[$i] == 'created_datetime'){
            $_data = _dt($aRow[$aColumns[$i]]);
        }else{
            $_data = $aRow[$aColumns[$i]];

        }
        $row[] = $_data;
    }
    $options = '';
    if (has_permission('meetlink_manager', '', 'edit_service')) { 
        $options        = icon_btn('#', 'fa fa-pencil', 'btn-default', ['data-toggle' => 'modal', 'data-target' => '#m_service_modal', 'data-id' => $aRow['id']]);
    }
    if (has_permission('meetlink_manager', '', 'delete_service')) { 
        $options .= icon_btn('meetlink_manager/services/delete_services/'.$aRow['id'], 'fa fa-times', 'btn-danger _delete');
    }
    $row[]    = $options; 
    $output['aaData'][] = $row;
}
