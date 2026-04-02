<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_chatbot_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // --- Grupos ---
    public function get_groups($id = '')
    {
        if ($id != '') {
            return $this->db->where('id', $id)->get(db_prefix() . 'dps_chat_groups')->row();
        }
        return $this->db->get(db_prefix() . 'dps_chat_groups')->result_array();
    }

    public function create_group($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = get_staff_user_id();
        $this->db->insert(db_prefix() . 'dps_chat_groups', $data);
        return $this->db->insert_id();
    }

    public function add_group_member($group_id, $staff_id)
    {
        $this->db->insert(db_prefix() . 'dps_chat_group_members', [
            'group_id' => $group_id,
            'staff_id' => $staff_id,
            'added_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function is_group_member($group_id, $staff_id)
    {
        return $this->db->where('group_id', $group_id)->where('staff_id', $staff_id)
            ->count_all_results(db_prefix() . 'dps_chat_group_members') > 0;
    }

    public function get_group_members($group_id)
    {
        $this->db->select(db_prefix() . 'dps_chat_group_members.staff_id, CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as name');
        $this->db->from(db_prefix() . 'dps_chat_group_members');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'dps_chat_group_members.staff_id', 'left');
        $this->db->where('group_id', $group_id);
        return $this->db->get()->result_array();
    }

    public function remove_group_member($group_id, $staff_id)
    {
        $this->db->where('group_id', $group_id)->where('staff_id', $staff_id)
            ->delete(db_prefix() . 'dps_chat_group_members');
    }

    // --- Mensagens ---
    public function send_message($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['sender_id'] = get_staff_user_id();
        $this->db->insert(db_prefix() . 'dps_chat_messages', $data);
        return $this->db->insert_id();
    }

    public function get_messages($receiver_id = null, $group_id = null)
    {
        $this->db->select(db_prefix() . 'dps_chat_messages.*, CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as sender_name');
        $this->db->from(db_prefix() . 'dps_chat_messages');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'dps_chat_messages.sender_id', 'left');

        if ($group_id) {
            $this->db->where('group_id', $group_id);
        } else {
            $this->db->group_start();
            $this->db->where('sender_id', get_staff_user_id());
            $this->db->where('receiver_id', $receiver_id);
            $this->db->or_group_start();
            $this->db->where('sender_id', $receiver_id);
            $this->db->where('receiver_id', get_staff_user_id());
            $this->db->group_end();
            $this->db->group_end();
        }

        $this->db->order_by('created_at', 'ASC');
        return $this->db->get()->result_array();
    }
}
