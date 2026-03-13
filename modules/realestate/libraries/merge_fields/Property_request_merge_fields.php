<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Property_request_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
                [
                    'name'      => 'Request Link',
                    'key'       => '{request_link}',
                    'available' => [
                        'block_time',
                    ],
                ],
                [
                    'name'      => 'Request Number',
                    'key'       => '{request_number}',
                    'available' => [
                        'block_time',
                    ],
                ],
                
                [
                    'name'      => 'End Date',
                    'key'       => '{request_expirydate}',
                    'available' => [
                        'block_time',
                    ],
                ],
                [
                    'name'      => 'Start Date',
                    'key'       => '{request_date}',
                    'available' => [
                        'block_time',
                    ],
                ],
                [
                    'name'      => 'Request Status',
                    'key'       => '{request_status}',
                    'available' => [
                        'block_time',
                    ],
                ],
                
                [
                    'name'      => 'Total Amount',
                    'key'       => '{request_total}',
                    'available' => [
                        'block_time',
                    ],
                ],

                [
                    'name'      => 'Contact Firstname',
                    'key'       => '{contact_firstname}',
                    'available' => [
                        'block_time',
                    ],
                ],
                [
                    'name'      => 'Contact Lastname',
                    'key'       => '{contact_lastname}',
                    'available' => [
                        'block_time',
                    ],
                ],
                
                
            ];
    }

    /**
    * Merge field for staff members
    * @param  mixed $staff_id staff id
    * @param  string $password password is used only when sending welcome email, 1 time
    * @return array
    */
    public function format($request_id)
    {
        $fields = [];
        $this->ci->db->where('id', $request_id);
        $request = $this->ci->db->get(db_prefix().'real_requests')->row();

        if (!$request) {
            return $fields;
        }

        $currency = get_currency($request->currency);

        $fields['{request_total}']        = e(app_format_money($request->total, $currency));
        $fields['{request_link}']         = site_url('realestate/client/property_request/' . $request->code);
        $fields['{request_number}']       = $request->code;
        $fields['{request_expirydate}']   = e(_d($request->duedate));
        $fields['{request_date}']         = e(_d($request->date));
        $fields['{request_status}']       = e(render_property_request_status_html($request->id, 'order', $request->status, false));


        return hooks()->apply_filters('property_request_merge_fields', $fields, [
        'id'       => $request_id,
        'request' => $request,
     ]);
    }

    
}
