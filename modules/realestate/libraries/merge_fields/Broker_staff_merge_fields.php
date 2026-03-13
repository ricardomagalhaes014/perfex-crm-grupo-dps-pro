<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Broker_staff_merge_fields extends App_merge_fields
{
    public function build()
    {
        return [
        [
                    'name'      => 'Staff Firstname',
                    'key'       => '{staff_firstname}',
                    'available' => [
                        'realestate',
                        'broker_gdpr',
                    ],
                    
                ],
                [
                    'name'      => 'Staff Lastname',
                    'key'       => '{staff_lastname}',
                    'available' => [
                        'realestate',
                        'broker_gdpr',
                    ],
                    
                ],
                [
                    'name'      => 'Staff Email',
                    'key'       => '{staff_email}',
                    'available' => [
                        'realestate',
                    ],
                    
                ],
                [
                    'name'      => 'Staff Date Created',
                    'key'       => '{staff_datecreated}',
                    'available' => [
                        'realestate',
                    ],
                ],
                [
                    'name'      => 'Reset Password Url',
                    'key'       => '{reset_password_url}',
                    'available' => [
                    ],
                    'templates' => [
                        'broker-staff-forgot-password',
                    ],
                ],
                [
                    'name'      => 'Two Factor Authentication Code',
                    'key'       => '{two_factor_auth_code}',
                    'available' => [
                    ],
                    'templates' => [
                        'broker-two-factor-authentication',
                    ],
                ],
                [
                    'name'      => 'Password',
                    'key'       => '{password}',
                    'available' => [
                    ],
                    'templates' => [
                        'broker-new-staff-created',
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
    public function format($staff_id, $password = '')
    {
        $fields = [];

        $this->ci->db->where('id', $staff_id);
        $staff = $this->ci->db->get(db_prefix().'real_broker_staffs')->row();

        $fields['{password}']          = '';
        $fields['{staff_firstname}']   = '';
        $fields['{staff_lastname}']    = '';
        $fields['{staff_email}']       = '';
        $fields['{staff_datecreated}'] = '';

        if (!$staff) {
            return $fields;
        }

        if ($password != '') {
            $fields['{password}'] = htmlentities($password);
        }

        if ($staff->two_factor_auth_code) {
            $fields['{two_factor_auth_code}'] = $staff->two_factor_auth_code;
        }

        $fields['{staff_firstname}']   = e($staff->firstname);
        $fields['{staff_lastname}']    = e($staff->lastname);
        $fields['{staff_email}']       = e($staff->email);
        $fields['{staff_datecreated}'] = e($staff->datecreated);

        return hooks()->apply_filters('broker_staff_merge_fields', $fields, [
        'id'    => $staff_id,
        'staff' => $staff,
     ]);
    }

    public function password($data, $type)
    {
        $fields['{reset_password_url}'] = '';
        $fields['{set_password_url}']   = '';

        if ($type == 'forgot') {
            $fields['{reset_password_url}'] = admin_url('realestate/authentication_broker/reset_password/1/' . $data['userid'] . '/' . $data['new_pass_key']);
        }

        return $fields;
    }
}
