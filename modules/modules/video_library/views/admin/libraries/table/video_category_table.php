<?php
defined('BASEPATH') or exit('No direct script access allowed');
$this->ci->db->query("SET sql_mode = ''");
$aColumns = [
    'id',
    'category',
    '1',
];
$sIndexColumn = 'id';
$sTable       = db_prefix().'video_category';

$where  = [];
$filter = [];
$join = [];
$result =   data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    db_prefix().'video_category.id',
    db_prefix().'video_category.category',
    
]);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    $row[] = $aRow['id']; 
    $row[] = $aRow['category'];
    $edit_delete_link = '';
    if (has_permission('video_library', '', 'edit')) { 
     $edit_delete_link .= '<a href="javascript:void(0);" onclick="edit_category(this);" data-id="'.$aRow['id'].'">Edit</a>'; 
 }
 if (has_permission('video_library', '', 'delete')) { 
    $edit_delete_link .= ' <a href="javascript:void(0);" onclick="delete_category(this);" data-id="'.$aRow['id'].'">Delete</a>';
}
$row[] = $edit_delete_link;
$output['aaData'][] = $row;
}