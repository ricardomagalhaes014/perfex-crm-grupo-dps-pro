<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_meetings_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id)
    {
        return $this->db->where('id', $id)->get(db_prefix() . 'dps_meetings')->row();
    }

    public function get_all()
    {
        $this->db->select(db_prefix() . 'dps_meetings.*, CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as host_name');
        $this->db->from(db_prefix() . 'dps_meetings');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'dps_meetings.host_staff_id', 'left');

        if (!is_admin()) {
            $this->db->group_start();
            $this->db->where(db_prefix() . 'dps_meetings.host_staff_id', get_staff_user_id());
            $this->db->or_where(db_prefix() . 'dps_meetings.created_by', get_staff_user_id());
            $this->db->group_end();
        }

        $this->db->order_by('start_at', 'DESC');

        return $this->db->get()->result_array();
    }

    public function add($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = get_staff_user_id();

        $this->db->insert(db_prefix() . 'dps_meetings', $data);
        $id = $this->db->insert_id();

        if ($id) {
            $this->log($id, 'created', 'Reunião criada.');
        }

        return $id;
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_by'] = get_staff_user_id();

        $this->db->where('id', $id);
        $ok = $this->db->update(db_prefix() . 'dps_meetings', $data);

        if ($ok) {
            $this->log($id, 'updated', 'Reunião atualizada.');
        }

        return $ok;
    }

    public function delete($id)
    {
        $this->db->where('meeting_id', $id);
        $this->db->delete(db_prefix() . 'dps_meeting_logs');

        $this->db->where('id', $id);
        return $this->db->delete(db_prefix() . 'dps_meetings');
    }

    public function log($meetingId, $action, $description = null)
    {
        $this->db->insert(db_prefix() . 'dps_meeting_logs', [
            'meeting_id'  => $meetingId,
            'action'      => $action,
            'description' => $description,
            'staff_id'    => get_staff_user_id(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
