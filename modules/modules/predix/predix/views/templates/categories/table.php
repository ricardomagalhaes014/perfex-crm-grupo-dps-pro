<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'category_name',
    'category_description',
    'is_enabled',
    'created_at'
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'predix_template_categories';

$join = [];
$where = [];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'id'
]);

$output  = $result['output'];
$rResult = $result['rResult'];


foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {

        $row[] = '<strong>'.$aRow['category_name'] .'</strong><br>'._l('predix_total_template_category_templates', total_rows(db_prefix() . 'predix_templates', ['template_category_id' => $aRow['id']]));
        $row[] = $aRow['category_description'];

        $checked = '';
        if ($aRow['is_enabled'] == 1) {
            $checked = 'checked';
        }
        $row[]= '<div class="onoffswitch">
                <input type="checkbox" data-switch-url="' . admin_url() . 'predix/update_template_category_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . $checked . '>
                <label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
            </div>';

        $row[] = $aRow['created_at'];


        $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
        $options .= '<a href="' . admin_url('predix/create_template_category/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
        <i class="fa-regular fa-pen-to-square fa-lg"></i>
    </a>';

        $options .= '<a href="' . admin_url('predix/delete_template_category/' . $aRow['id']) . '"
    class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
        <i class="fa-regular fa-trash-can fa-lg"></i>
    </a>';

        $options .= '</div>';

        $row[]              = $options;
    }

    $output['aaData'][] = $row;
}
