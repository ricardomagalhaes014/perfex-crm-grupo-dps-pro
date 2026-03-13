<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Lead_manager_lead_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
            [
                'name'      => 'Lead Name',
                'key'       => '{lead_name}',
                'available' => [
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Email',
                'key'       => '{lead_email}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Position',
                'key'       => '{lead_position}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Website',
                'key'       => '{lead_website}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Description',
                'key'       => '{lead_description}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Phone Number',
                'key'       => '{lead_phonenumber}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Company',
                'key'       => '{lead_company}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Country',
                'key'       => '{lead_country}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Zip',
                'key'       => '{lead_zip}',
                'available' => [
                    
                ],
            ],
            [
                'name'      => 'Lead City',
                'key'       => '{lead_city}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead State',
                'key'       => '{lead_state}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Address',
                'key'       => '{lead_address}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Assigned',
                'key'       => '{lead_assigned}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Status',
                'key'       => '{lead_status}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Souce',
                'key'       => '{lead_source}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Lead Link',
                'key'       => '{lead_link}',
                'available' => [
                    
                ],
                'templates' => [
                    'lead-manager-send-email-to-lead',
                    'lead-manager-send-to-lead'
                ],
            ],
            // [
            //     'name'      => 'Staff Name',
            //     'key'       => '{staff_name}',
            //     'available' => [],
            //     'templates' => [
            //         'lead-manager-send-email-to-lead'
            //     ],
            // ],
            [
                'name'      => 'Topic',
                'key'       => '{topic}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Meeting ID',
                'key'       => '{meeting_id}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Meeting Time',
                'key'       => '{meeting_time}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Duration',
                'key'       => '{meeting_duration}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Password',
                'key'       => '{meeting_password}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Join Link',
                'key'       => '{join_url}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Description',
                'key'       => '{meeting_description}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            [
                'name'      => 'Created at',
                'key'       => '{created_at}',
                'available' => [],
                'templates' => [
                    'lead-manager-send-to-customer',
                    'lead-manager-send-to-lead'
                ],
            ],
            // [
            //     'name'      => 'Staff email',
            //     'key'       => '{staff_email}',
            //     'available' => [],
            //     'templates' => [
            //         'lead-manager-send-email-to-lead'
            //     ],
            // ],
            // [
            //     'name'      => 'Staff contact no.',
            //     'key'       => '{staff_phonenumber}',
            //     'available' => [],
            //     'templates' => [
            //         'lead-manager-send-email-to-lead'
            //     ],
            // ],
        ];
    }

    /**
     * Lead merge fields
     * @param  mixed $id lead id
     * @return array
     */
    public function format($id, $data)
    {
        $fields = [];

        $fields['{lead_name}']               = '';
        $fields['{lead_email}']              = '';
        $fields['{lead_position}']           = '';
        $fields['{lead_company}']            = '';
        $fields['{lead_country}']            = '';
        $fields['{lead_zip}']                = '';
        $fields['{lead_city}']               = '';
        $fields['{lead_state}']              = '';
        $fields['{lead_address}']            = '';
        $fields['{lead_assigned}']           = '';
        $fields['{lead_status}']             = '';
        $fields['{lead_source}']             = '';
        $fields['{lead_phonenumber}']        = '';
        $fields['{lead_link}']               = '';
        $fields['{lead_website}']            = '';
        $fields['{lead_description}']        = '';
        $fields['{lead_public_form_url}']    = '';
        $fields['{lead_public_consent_url}'] = '';
        $fields['{staff_name}'] = '';
        $fields['{topic}'] = '';
        $fields['{meeting_id}'] = '';
        $fields['{meeting_time}'] = '';
        $fields['{meeting_duration}'] = '';
        $fields['{meeting_password}'] = '';
        $fields['{join_url}'] = '';
        $fields['{meeting_description}'] = '';
        $fields['{created_at}'] = '';
        $fields['{staff_email}'] = '';
        $fields['{staff_phonenumber}'] = '';
        if ($template_type = 'zoom') {
            return $this->generateMergeFieldForZoom($id, $data, $fields);
        }elseif($template_type = 'email'){
            return $this->generateMergeFieldForEmail($id, $data, $fields);
        }
        return $fields;
    }

    public function generateMergeFieldForZoom($id, $meeting_data, $fields)
    {
        if (is_numeric($id) && !$meeting_data->is_client) {
            $this->ci->db->where('id', $id);
            $lead = $this->ci->db->get(db_prefix() . 'leads')->row();
        } 
        // else {
        //     $this->ci->db->where(['id' => $id, 'is_primary' => 1]);
        //     $lead = $this->ci->db->get(db_prefix() . 'contacts')->row();
        // }

        if (!$lead) {
            return $fields;
        }
        if (is_numeric($meeting_data->staff_id)) {
            $this->ci->load->model('staff_model');
            $staff = $this->ci->staff_model->get($meeting_data->staff_id);
            $fields['{staff_email}'] = $staff->email;
            $fields['{staff_phonenumber}'] = $staff->phonenumber;
        }
        $dateTime = new DateTime();
        $dateTime->setTimeZone(new DateTimeZone($meeting_data->timezone));
        $time_zone_abbreviation = $dateTime->format('T');
        if (!$meeting_data->is_client) {
            $fields['{lead_public_form_url}']    = leads_public_url($lead->id);
            $fields['{lead_public_consent_url}'] = lead_consent_url($lead->id);
            $fields['{lead_link}']               = admin_url('leads/index/' . $lead->id);
            $fields['{lead_name}']               = $lead->name;
            $fields['{lead_email}']              = $lead->email;
            $fields['{lead_position}']           = $lead->title;
            $fields['{lead_phonenumber}']        = $lead->phonenumber;
            $fields['{lead_company}']            = $lead->company;
            $fields['{lead_zip}']                = $lead->zip;
            $fields['{lead_city}']               = $lead->city;
            $fields['{lead_state}']              = $lead->state;
            $fields['{lead_address}']            = $lead->address;
            $fields['{lead_website}']            = $lead->website;
            $fields['{lead_description}']        = $lead->description;
        } else {
            $fields['{lead_name}']               = $meeting_data->name;
            $fields['{lead_email}']              = $meeting_data->email;
            $fields['{lead_position}']           = $lead->title;
            $fields['{lead_phonenumber}']        = $lead->phonenumber;
        }
        $fields['{staff_name}'] = $meeting_data->staff_name;
        $fields['{topic}'] = $meeting_data->meeting_agenda;
        $fields['{meeting_id}'] = $meeting_data->meeting_id;
        $fields['{meeting_time}'] = _dt($meeting_data->meeting_date) . ' ' . $time_zone_abbreviation;
        $fields['{meeting_duration}'] = $meeting_data->meeting_duration;
        $fields['{meeting_password}'] = $meeting_data->password;
        $fields['{join_url}'] = $meeting_data->join_url;
        $fields['{meeting_description}'] = $meeting_data->meeting_description;
        $fields['{created_at}'] = _dt($meeting_data->created_at);
        return $fields;
    }

    public function generateMergeFieldForEmail($id, $mail_data, $fields)
    {
        if (is_numeric($id) && !$mail_data->is_client) {
            $this->ci->db->where('id', $id);
            $lead = $this->ci->db->get(db_prefix() . 'leads')->row();
        } else {
            $this->ci->db->where(['id' => $id, 'is_primary' => 1]);
            $lead = $this->ci->db->get(db_prefix() . 'contacts')->row();
        }

        if (!$lead) {
            return $fields;
        }
        if (is_numeric($mail_data->staff_id)) {
            $this->ci->load->model('staff_model');
            $staff = $this->ci->staff_model->get($mail_data->staff_id);
            $fields['{staff_email}'] = $staff->email;
            $fields['{staff_phonenumber}'] = $staff->phonenumber;
        }
        $dateTime = new DateTime();
        $dateTime->setTimeZone(new DateTimeZone($mail_data->timezone));
        $time_zone_abbreviation = $dateTime->format('T');
        if (!$mail_data->is_client) {
            $fields['{lead_public_form_url}']    = leads_public_url($lead->id);
            $fields['{lead_public_consent_url}'] = lead_consent_url($lead->id);
            $fields['{lead_link}']               = admin_url('leads/index/' . $lead->id);
            $fields['{lead_name}']               = $lead->name;
            $fields['{lead_email}']              = $lead->email;
            $fields['{lead_position}']           = $lead->title;
            $fields['{lead_phonenumber}']        = $lead->phonenumber;
            $fields['{lead_company}']            = $lead->company;
            $fields['{lead_zip}']                = $lead->zip;
            $fields['{lead_city}']               = $lead->city;
            $fields['{lead_state}']              = $lead->state;
            $fields['{lead_address}']            = $lead->address;
            $fields['{lead_website}']            = $lead->website;
            $fields['{lead_description}']        = $lead->description;
        } else {
            $fields['{lead_name}']               = $mail_data->name;
            $fields['{lead_email}']              = $mail_data->email;
            $fields['{lead_position}']           = $lead->title;
            $fields['{lead_phonenumber}']        = $lead->phonenumber;
        }
        $fields['{staff_name}'] = $mail_data->staff_name;
        $fields['{topic}'] = $mail_data->meeting_agenda;
        $fields['{meeting_id}'] = $mail_data->meeting_id;
        $fields['{meeting_time}'] = _dt($mail_data->meeting_date) . ' ' . $time_zone_abbreviation;
        $fields['{meeting_duration}'] = $mail_data->meeting_duration;
        $fields['{meeting_password}'] = $mail_data->password;
        $fields['{join_url}'] = $mail_data->join_url;
        $fields['{meeting_description}'] = $mail_data->meeting_description;
        $fields['{created_at}'] = _dt($mail_data->created_at);
        return $fields;
    }
}
