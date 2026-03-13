<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meeting_services_model extends CI_Model {
    
    private $table = 'meeting_services';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function get($id = NULL) {
        if ($id !== NULL) {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }
        return $this->db->get($this->table)->result_array();
    }
    
    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }
    
    public function service_delete($id)
    {
        $original_service = $this->get($id);
        $this->db->where('id', $id);
        $this->db->delete(db_prefix().'meeting_services');
        if ($this->db->affected_rows() > 0) {
            log_activity('Meeting service deleted[ID:'.$id.', '.$original_service->service_name.', Staff id '.get_staff_user_id().']');
            return true;
        }
        return false;
    }
}
