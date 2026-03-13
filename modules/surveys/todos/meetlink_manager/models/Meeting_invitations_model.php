<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Meeting_invitations_model extends CI_Model {
    
    private $table = 'meeting_invitations';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function get($id = NULL) {
        if ($id !== NULL) {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }
        return $this->db->get($this->table)->result();
    }
    
    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }
    
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }
}
