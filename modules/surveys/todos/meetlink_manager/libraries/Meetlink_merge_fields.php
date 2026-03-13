<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Meetlink_merge_fields extends App_merge_fields
{
    public function build()
    {
        return  [
                    [
                        'name'      => 'Title',
                        'key'       => '{title}',
                        'available' => ['meetlink'],
                    ],
                    [
                        'name'      => 'Meeting Date',
                        'key'       => '{date}',
                        'available' => ['meetlink'],
                    ],
                    [
                        'name'      => 'Meeting Time',
                        'key'       => '{time}',
                        'available' => ['meetlink'],
                    ],
                    [
                        'name'      => 'Service Type',
                        'key'       => '{service_type}',
                        'available' => ['meetlink'],
                    ],
                    [
                        'name'      => 'Meeting Link',
                        'key'       => '{meeting_link}',
                        'available' => ['meetlink'],
                    ],
                    [
                        'name'      => 'Meeting Created Name',
                        'key'       => '{created_by_name}',
                        'available' => ['meetlink'],
                    ],
                    
                ];
    }

    public function format($id)
    {
        $this->ci->load->model('meetlink_manager/meetings_model');
        $fields                     = [];
        $data                       = $this->ci->meetings_model->get($id);
     
        if (empty($data)) {
            $fields['{title}']    = '';
            $fields['{date}']  = '';
            $fields['{time}']  = '';
            $fields['{service_type}']      = '';
            $fields['{meeting_link}']      = '';
            $fields['{created_by_name}']      = '';
            
            return $fields;
        }
        $fields['{title}']      = $data->title;
        $fields['{date}']       = _d($data->meeting_date);
        $fields['{time}']       = $data->meeting_date;
        $fields['{service_type}']    = get_service_name_by_id($data->service_id);
        $fields['{meeting_link}']       = $data->meeting_url;
        $fields['{created_by_name}']      = get_staff_full_name();

        return $fields;
    }
}
