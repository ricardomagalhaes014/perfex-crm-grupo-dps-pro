<?php

$CI          = & get_instance();
$custom_fields = get_table_custom_fields('mindmap');

$CI->db->query("SET sql_mode = ''");
$aColumns = [
    'title',
    db_prefix() . 'mindmap.description',
    'staffid',
    db_prefix() . 'mindmap_groups.name',
    db_prefix() . 'mindmap.dateadded',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'mindmap';
$join = [
    'LEFT JOIN ' . db_prefix() . 'mindmap_groups ON ' . db_prefix() . 'mindmap_groups.id = ' . db_prefix() . 'mindmap.mindmap_group_id',
];
// $where        = [];
$where        = [
    'AND project_id=' . $_GET['project_id'],
    ];
// Add blank where all filter can be stored
$filter = [];

foreach ($custom_fields as $key => $field) {
    $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
    array_push($customFieldsColumns, $selectAs);
    array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
    array_push($join, 'LEFT JOIN '.db_prefix().'customfieldsvalues as ctable_' . $key . ' ON '.db_prefix().'clients.userid = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
}

$join = hooks()->apply_filters('mindmap_table_sql_join', $join);

// Filter by Staff
$staffIds = [];
if ($CI->input->post('assigned')) {
    array_push($staffIds, $CI->input->post('assigned'));
}

if (count($staffIds) > 0) {
    array_push($filter, 'AND '.db_prefix().'mindmap.staffid IN (' . implode(', ', $staffIds) . ')');
}

// Filter by Group
$groupIds = [];
if ($CI->input->post('group')) {
    array_push($groupIds, $CI->input->post('group'));
}

if (count($groupIds) > 0) {
    array_push($filter, 'AND '.db_prefix().'mindmap.mindmap_group_id IN (' . implode(', ', $groupIds) . ')');
}


if (count($filter) > 0) {
    array_push($where, 'AND (' . prepare_dt_filter($filter) . ')');
}
if ($CI->input->post('my_mindmap')) {
    array_push($where, 'AND '.db_prefix().'mindmap.staffid = ' . get_staff_user_id());
}

$aColumns = hooks()->apply_filters('mindmap_table_sql_columns', $aColumns);
// Fix for big queries. Some hosting have max_join_limit
if (count($custom_fields) > 4) {
    @$CI->db->query('SET SQL_BIG_SELECTS=1');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'mindmap.id']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {
        $_data = $aRow[$aColumns[$i]];
        if ($aColumns[$i] == 'title') {
            $hrefAttr = 'href="' . admin_url('mindmap/index/' . $aRow['id']) . '" onclick="init_mindmap_modal(' . $aRow['id'] . ');return false;"';

            $_data = '<a href="' . admin_url('mindmap/preview/' . $aRow['id']) . '" >' . $_data . '</a>';
            $_data .= '<div class="row-options">';
            $_data .= '<a ' . $hrefAttr . '>' . _l('mindmap_view') . '</a>';
            $_data .= ' | <a href="' . admin_url('mindmap/mindmap_create/' . $aRow['id']) . '">' . _l('mindmap_edit') . '</a>';

            if (has_permission('mindmap', '', 'delete')) {
                $_data .= ' | <a href="' . admin_url('mindmap/delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('mindmap_delete') . '</a>';
            }
            $_data .= '</div>';
        } elseif ($aColumns[$i] == db_prefix() . 'mindmap.description' || $aColumns[$i] == 'description') {
            $_data = $_data;
        }elseif ($aColumns[$i] == 'staffid') {
            if($_data > 0) {
                $oStaff = $this->ci->staff_model->get($_data);
                $_data = staff_profile_image($oStaff->staffid, array('img', 'img-responsive', 'staff-profile-image-small', 'pull-left')) . '<a href="' . admin_url('profile/' . $oStaff->staffid) . '">' . $oStaff->firstname . ' ' . $oStaff->lastname . '</a><br>';
            }else{
                $_data = '';
            }
        }elseif ($aColumns[$i] == db_prefix() . 'mindmap.dateadded' || $aColumns[$i] == 'dateadded') {
            $_data = _dt($_data);
        }else{
            $_data = $_data;
        }
        $row[] = $_data;
    }
    ob_start();
    ?>

    <?php
    $progress = ob_get_contents();
    ob_end_clean();
    $row[]              = $progress;
    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
