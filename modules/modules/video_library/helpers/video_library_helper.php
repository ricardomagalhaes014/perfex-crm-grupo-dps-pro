<?php defined('BASEPATH') or exit('No direct script access allowed');

function prepare_grid_query_for_video_library($aColumns, $sIndexColumn, $sTable, $join = [], $where = [], $additionalSelect = [], $sGroupBy = '', $searchAs = [])
{
    $CI = & get_instance();
    $__post = $CI
    ->input
    ->post();
    $havingCount = '';
    $sLimit = '';
    if ((is_numeric($CI
        ->input
        ->post('start', true))) && $CI
        ->input
        ->post('length', true) != '-1')
    {
      $offset = $CI
      ->input
      ->post('start', true)*$CI
      ->input
      ->post('length', true);
      $sLimit = 'LIMIT ' . intval($offset). ', ' . intval($CI
        ->input
        ->post('length', true));
  }
  $_aColumns = [];
  foreach ($aColumns as $column)
  {
    if (substr_count($column, '.') == 1 && strpos($column, ' as ') === false)
    {
        $_column = explode('.', $column);
        if (isset($_column[1]))
        {
            if (startsWith($_column[0], db_prefix()))
            {
                $_prefix = prefixed_table_fields_wildcard($_column[0], $_column[0], $_column[1]);
                array_push($_aColumns, $_prefix);
            }
            else
            {
                array_push($_aColumns, $column);
            }
        }
        else
        {
            array_push($_aColumns, $_column[0]);
        }
    }
    else
    {
        array_push($_aColumns, $column);
    }
}
$nullColumnsAsLast = get_null_columns_that_should_be_sorted_as_last();
$sOrder = '';
if ($CI
    ->input
    ->post('order', true))
{
    $sOrder = 'ORDER BY ';
    foreach ($CI
        ->input
        ->post('order', true) as $key => $val)
    {
        $columnName = $aColumns[intval($__post['order'][$key]['column']) ];
        $dir = strtoupper($__post['order'][$key]['dir']);
        if (strpos($columnName, ' as ') !== false)
        {
            $columnName = strbefore($columnName, ' as');
        }
        if ((in_array($sTable . '.' . $columnName, $nullColumnsAsLast) || in_array($columnName, $nullColumnsAsLast)))
        {
            $sOrder .= $columnName . ' IS NULL ' . $dir . ', ' . $columnName;
        }
        else
        {
            $sOrder .= hooks()->apply_filters('datatables_query_order_column', $columnName, $sTable);
        }
        $sOrder .= ' ' . $dir . ', ';
    }
    if (trim($sOrder) == 'ORDER BY')
    {
        $sOrder = '';
    }
    $sOrder = rtrim($sOrder, ', ');
    if (get_option('save_last_order_for_tables') == '1' && $CI
        ->input
        ->post('last_order_identifier', true) && $CI
        ->input
        ->post('order', true))
    {
        $indexedOnly = [];
        foreach ($CI
            ->input
            ->post('order', true) as $row)
        {
            $indexedOnly[] = array_values($row);
        }
        $meta_name = $CI
        ->input
        ->post('last_order_identifier', true) . '-table-last-order';
        update_staff_meta(get_staff_user_id() , $meta_name, json_encode($indexedOnly, JSON_NUMERIC_CHECK));
    }
}
$sWhere = '';
if ((isset($__post['search'])) && $__post['search'] != '')
{
    $search_value = $CI
    ->input
    ->post('search', true);
    $search_value = trim($search_value);
    $sWhere = 'WHERE (';
    $sMatchCustomFields = [];
    $useMatchForCustomFieldsTableSearch = hooks()->apply_filters('use_match_for_custom_fields_table_search', 'false');
    for ($i = 0;$i < count($aColumns);$i++)
    {
        $columnName = $aColumns[$i];
        if (strpos($columnName, ' as ') !== false)
        {
            $columnName = strbefore($columnName, ' as');
        }
        if (stripos($columnName, 'AVG(') !== false || stripos($columnName, 'SUM(') !== false)
        {
        }
        else
        {
            if (isset($searchAs[$i]))
            {
                $columnName = $searchAs[$i];
            }
            if ($useMatchForCustomFieldsTableSearch === 'true' && startsWith($columnName, 'ctable_'))
            {
                $sMatchCustomFields[] = $columnName;
            }
            else
            {
                $sWhere .= 'convert(' . $columnName . ' USING utf8)' . " LIKE '%" . $CI
                ->db
                ->escape_like_str($search_value) . "%' OR ";
            }
        }
    }
    if (count($sMatchCustomFields) > 0)
    {
        $s = $CI
        ->db
        ->escape_like_str($search_value);
        foreach ($sMatchCustomFields as $matchCustomField)
        {
            $sWhere .= "MATCH ({$matchCustomField}) AGAINST (CONVERT(BINARY('{$s}') USING utf8)) OR ";
        }
    }
    if (count($additionalSelect) > 0)
    {
        foreach ($additionalSelect as $searchAdditionalField)
        {
            if (strpos($searchAdditionalField, ' as ') !== false)
            {
                $searchAdditionalField = strbefore($searchAdditionalField, ' as');
            }
            if (stripos($columnName, 'AVG(') !== false || stripos($columnName, 'SUM(') !== false)
            {
            }
            else
            {
                $sWhere .= 'convert(' . $searchAdditionalField . ' USING utf8)' . " LIKE '%" . $CI
                ->db
                ->escape_like_str($search_value) . "%' OR ";
            }
        }
    }
    $sWhere = substr_replace($sWhere, '', -3);
    $sWhere .= ')';
}
$_additionalSelect = '';
if (count($additionalSelect) > 0)
{
    $_additionalSelect = ',' . implode(',', $additionalSelect);
}
$where = implode(' ', $where);
if ($sWhere == '')
{
    $where = trim($where);
    if (startsWith($where, 'AND') || startsWith($where, 'OR'))
    {
        if (startsWith($where, 'OR'))
        {
            $where = substr($where, 2);
        }
        else
        {
            $where = substr($where, 3);
        }
        $where = 'WHERE ' . $where;
    }
}
$join = implode(' ', $join);
$sQuery = 'SELECT SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $_aColumns)) . ' ' . $_additionalSelect . " FROM $sTable " . $join . " $sWhere " . $where . " $sGroupBy $sOrder $sLimit";

$rResult = $CI
->db
->query($sQuery)->result_array();
$last_query = $CI
->db
->last_query();

$rResult = hooks()->apply_filters('datatables_sql_query_results', $rResult, ['table' => $sTable, 'limit' => $sLimit, 'order' => $sOrder, ]);
$sQuery = 'SELECT FOUND_ROWS()';
$_query = $CI
->db
->query($sQuery)->result_array();
$iFilteredTotal = $_query[0]['FOUND_ROWS()'];
if (startsWith($where, 'AND'))
{
    $where = 'WHERE ' . substr($where, 3);
}
$sQuery = 'SELECT COUNT(' . $sTable . '.' . $sIndexColumn . ") FROM $sTable " . $join . ' ' . $where;
    $_query = $CI->db
    ->query($sQuery)->result_array();
    $iTotal = $_query[0]['COUNT(' . $sTable . '.' . $sIndexColumn . ')'];
    $output = ['draw' => $__post['draw'] ? intval($__post['draw']) : 0, 'iTotalRecords' => $iTotal, 'iTotalDisplayRecords' => $iFilteredTotal, 'aaData' => [], ];
    return ['rResult' => $rResult, 'output' => $output, "query" => $last_query];
}

function get_video_library_category(){
    $CI = &get_instance();
    if (!class_exists('video_library_modal')) {
        $CI->load->model('video_library/video_library_modal');
    }
    return $CI->video_library_modal->show_category();
}
function handle_video_comment_attachments($discussion_id, $post_data, $insert_data)
{
    if (isset($_FILES['file']['name']) && _perfex_upload_error($_FILES['file']['error']))
    {
        header('HTTP/1.0 400 Bad error');
        echo json_encode(['message' => _perfex_upload_error($_FILES['file']['error']) ]);
        die;
    }
    if (isset($_FILES['file']['name']))
    {
        $path = VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER . $discussion_id . '/';
        if (!_upload_extension_allowed($_FILES['file']['name']))
        {
            header('HTTP/1.0 400 Bad error');
            echo json_encode(['message' => _l('file_php_extension_blocked') ]);
            die;
        }
        $tmpFilePath = $_FILES['file']['tmp_name'];
        if (!empty($tmpFilePath) && $tmpFilePath != '')
        {
            _maybe_create_upload_path($path);
            $filename = unique_filename($path, $_FILES['file']['name']);
            $newFilePath = $path . $filename;
            if (move_uploaded_file($tmpFilePath, $newFilePath))
            {
                $insert_data['file_name'] = $filename;
                if (isset($_FILES['file']['type']))
                {
                    $insert_data['file_mime_type'] = $_FILES['file']['type'];
                }
                else
                {
                    $insert_data['file_mime_type'] = get_mime_by_extension($filename);
                }
            }
        }
    }
    return $insert_data;
}
function handle_video_library_video_upload($vlid = '')
{
    if (is_numeric($vlid)) {
        if (isset($_FILES['upload_video']['name']) && $_FILES['upload_video']['name'] != '') {
            $path = get_upload_path_by_type('video_library');
            $tmpFilePath = $_FILES['upload_video']['tmp_name'];
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                $extension          = strtolower(pathinfo($_FILES['upload_video']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'mp4',
                ];
                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', _l('file_php_extension_blocked'));
                    return false;
                }
                _maybe_create_upload_path($path);
                $filename    = unique_filename($path, $_FILES['upload_video']['name']);
                $newFilePath = $path . $filename;
                // Upload the file into the video library uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI             = &get_instance();
                    $CI->db->where('id', $vlid);
                    $CI->db->update(db_prefix() . 'upload_video', [
                        'upload_video' => $filename,
                    ]);
                    return true;
                }
                print_r('false'.$path); die;
            }
        }
    }

    return false;
}

function video_discussion_count($video_id=''){
    $CI = & get_instance();
    $total = 0;
    if(is_numeric($video_id)){
        if (!class_exists('video_library_modal')) {
        $CI->load->model('video_library_modal');
    }
    $total=$CI->video_library_modal->video_discussion_count($video_id);
}
return $total;
}