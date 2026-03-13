<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Define the columns to be selected
$aColumns     = ['title', 'meeting_date', 'service_id', 'meeting_url', 'created_by', 'created_datetime'];
$sIndexColumn = 'id';
$sTable       = db_prefix() . 'meetings';
$where = [];



if (!is_admin()){
    // $join = [
    //     'LEFT JOIN ' . db_prefix() . 'meeting_participants ON ' . db_prefix() . 'meeting_participants.meeting_id = ' . db_prefix() . 'meetings.id',
    // ];
    $ids = implode(',',staff_meeting_ids());

    $where        = ['AND id in ('.$ids.')'];
}



// Initialize the data table
$result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], $where, ['id', 'meeting_time']);
$output = $result['output'];
$rResult = $result['rResult'];

$aColumns     = ['title', 'meeting_date', 'service_id', 'meeting_url', 'created_by', 'created_datetime'];

// Process each row
foreach ($rResult as $aRow) {
    $row = [];
    $metting_url = '#';
    // Loop through the defined columns
    foreach ($aColumns as $column) {
        
        
        // Handle each column separately based on its specific requirements
        switch ($column) {
            case 'service_id':
                // Get the service name by ID and display it as a link to open a modal
                $_data = get_service_name_by_id($aRow[$column]);
                break;

            case 'meeting_url':
                $metting_url =$aRow[$column];
                // Display the meeting URL as a clickable link to join the meeting
                $_data = '<a href="' . $aRow[$column] . '" target="_blank" data-id="' . $aRow['id'] . '"  data-toggle="tooltip" data-original-title="'._l('Join via the link').'"> ' . _l('join') . '</a>';
                break;

            case 'created_by':
                // Display the full name of the staff member who created the meeting
                $_data = get_staff_full_name($aRow[$column]);
                break;

            case 'meeting_date':
                // Combine meeting date and time and format it
                $_data = _dt($aRow[$column] . ' ' . $aRow['meeting_time']);
                break;

            case 'created_datetime':
                // Format the created datetime
                $_data = _dt($aRow[$column]);
                break;

            default:
                // For any other columns, just display the raw data
                $_data = $aRow[$column];
                break;
        }

        // Add the processed data to the row
        $row[] = $_data;
    }

    // Define action buttons (Edit and Delete)

    $options = icon_btn('#', 'fa fa-copy', 'btn-info copy_url', [
        'data-url' =>  $metting_url,
        'data-toggle'=>"tooltip",
        'data-original-title'=>_l('meet_tooltips_copy_link'),
    ]);
    $options .= icon_btn('#', 'fa fa-eye', 'btn-default view_details', [
        'data-toggle' => 'tooltip', 
        'data-original-title'=>_l('view_meeting'),
        'data-id' => $aRow['id']
    ]);
    if (has_permission('meetlink_manager', '', 'edit')) { 
        $options .= icon_btn('meetlink_manager/edit/' . $aRow['id'], 'fa fa-pencil', 'btn-default', [
            'data-toggle' => 'tooltip', 
            'data-original-title'=>_l('edit_meeting'),
            'data-id' => $aRow['id']
        ]);
    }
    if (has_permission('meetlink_manager', '', 'delete')) { 
        // Add the delete button with a confirmation
        $options .= icon_btn('meetlink_manager/delete/' . $aRow['id'], 'fa fa-times', 'btn-danger _delete',[
            'data-toggle' => 'tooltip', 
            'data-original-title'=>_l(line: 'delete_meeting'),
        ]);
    }
    // Add the action buttons to the row
    $row[] = $options;

    // Add the row to the output
    $output['aaData'][] = $row;
}
