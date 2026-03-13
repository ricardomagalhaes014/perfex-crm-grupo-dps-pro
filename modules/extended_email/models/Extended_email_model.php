<?php

    defined('BASEPATH') || exit('No direct script access allowed');

    class Extended_email_model extends App_Model
    {

        protected $table;

        public function __construct()
        {
            parent::__construct();
            $this->table = db_prefix().'extended_email_settings';
        }

        public function change_email_switch_status($id, $status)
        {
            $where['staffid'] = $id;
            $data             = [
                'email_active' => $status,
            ];

            return $this->db->update(db_prefix().'staff', $data, $where);
        }

        public function save($data)
        {
            if (isset($data['smtp_password'])) {
                $data['smtp_password'] = $this->encryption->encrypt($data['smtp_password']);
            }
            //\modules\extended_email\core\Apiinit::ease_of_mind('extended_email');
            $this->db->insert($this->table, $data);

            return $this->db->insert_id();
        }

        public function update($data, $where)
        {
            if (isset($data['smtp_password'])) {
                $data['smtp_password'] = $this->encryption->encrypt($data['smtp_password']);

            }
            //\modules\extended_email\core\Apiinit::the_da_vinci_code('extended_email');
            //\modules\extended_email\core\Apiinit::ease_of_mind('extended_email');
            return $this->db->update($this->table, $data, $where);
        }

        public function get_staff_extended_email_settings($id, $override_staff_id='')
        {
            $where['userid'] = $id;
            if (!empty($override_staff_id)) {
                $where['userid'] = $override_staff_id;
            }

            return $this->db->get_where($this->table, $where)->row();
        }

        public function add_log_activity($log_data)
        {
            return $this->db->insert(db_prefix().'extended_email_log_activity', $log_data);
        }
    }
