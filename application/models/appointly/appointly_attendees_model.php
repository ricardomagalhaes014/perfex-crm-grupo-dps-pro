<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointly_attendees_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Cria os registos de participantes (staff) para uma marcação.
     *
     * @param int   $appointment_id
     * @param array $attendees
     */
    public function create($appointment_id, $attendees)
    {
        if (!empty($attendees)) {
            foreach ($attendees as $staff_id) {
                $this->db->insert(db_prefix() . 'appointly_attendees', [
                    'appointment_id' => $appointment_id,
                    'staff_id'       => $staff_id,
                ]);
            }
        }
    }

    /**
     * Obtém IDs dos participantes (staff) de uma marcação.
     *
     * @param int $appointment_id
     * @return array
     */
    public function attendees($appointment_id)
    {
        $this->db->select('staff_id');
        $this->db->where('appointment_id', $appointment_id);
        $query = $this->db->get(db_prefix() . 'appointly_attendees');

        return array_column($query->result_array(), 'staff_id');
    }

    /**
     * Detalhes dos participantes (opcional, útil para AJAX ou debug).
     *
     * @param array $appointment_ids
     * @return array
     */
    public function details($appointment_ids)
    {
        if (!is_array($appointment_ids)) {
            $appointment_ids = [$appointment_ids];
        }

        $this->db->select('*');
        $this->db->where_in('appointment_id', $appointment_ids);

        return $this->db->get(db_prefix() . 'appointly_attendees')->result_array();
    }
}