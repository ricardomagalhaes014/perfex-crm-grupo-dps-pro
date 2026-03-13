<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Telegram_model extends App_Model
{
    /**
     * Add new telegram info
     * @param mixed $data
     */
    public function add($data)
    {
        $this->db->insert(db_prefix() . 'telegram', $data);
        $insert_id = $this->db->insert_id();
        return $insert_id;
    }

    /**
     * Update telegram info
     * @param  array $data role data
     * @param  mixed $id   role id
     * @return boolean
     */
    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'telegram', $data);
        return $this->db->affected_rows() ;
    }

    /**
     * Get telegram info by id
     * @param  mixed $id Optional role id
     * @return mixed  array if not id passed else object
     */
    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('user_id', $id);
            $telegram = $this->db->get(db_prefix() . 'telegram')->row();
            return $telegram;
        }

        return $this->db->get(db_prefix() . 'telegram')->result_array();
    }
   
}
