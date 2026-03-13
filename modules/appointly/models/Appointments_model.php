<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointments_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Retorna todas as marcações de um cliente
     *
     * @param int $client_id
     * @return array
     */
    public function get_appointments_by_client($client_id)
    {
        $this->db->where('client_id', $client_id);
        return $this->db->get(db_prefix() . 'appointly_appointments')->result_array();
    }

    /**
     * Retorna apenas os compromissos aprovados (para horários ocupados)
     */
    public function get_busy_times()
    {
        $this->db->where('status', 'approved');
        return $this->db->get(db_prefix() . 'appointly_appointments')->result_array();
    }
}