<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_voip_model extends CI_Model
{
    // ===================== NÚMEROS =====================

    public function get_all_numbers()
    {
        $this->db->select('n.*, s.firstname, s.lastname, s.email')
            ->from(db_prefix() . 'dps_voip_numbers n')
            ->join(db_prefix() . 'staff s', 's.staffid = n.staff_id', 'left')
            ->order_by('n.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_number($id)
    {
        return $this->db->get_where(db_prefix() . 'dps_voip_numbers', ['id' => $id])->row_array();
    }

    public function get_staff_number($staff_id)
    {
        return $this->db->get_where(db_prefix() . 'dps_voip_numbers', [
            'staff_id'  => $staff_id,
            'is_active' => 1,
        ])->row_array();
    }

    public function add_number($data)
    {
        $this->db->insert(db_prefix() . 'dps_voip_numbers', [
            'twilio_number' => $data['twilio_number'],
            'friendly_name' => $data['friendly_name'] ?? null,
            'twilio_sid'    => $data['twilio_sid'] ?? null,
            'staff_id'      => !empty($data['staff_id']) ? (int)$data['staff_id'] : null,
            'is_active'     => 1,
        ]);
        return $this->db->insert_id();
    }

    public function update_number($id, $data)
    {
        $update = [];
        if (isset($data['friendly_name'])) $update['friendly_name'] = $data['friendly_name'];
        if (isset($data['staff_id']))      $update['staff_id'] = !empty($data['staff_id']) ? (int)$data['staff_id'] : null;
        if (isset($data['is_active']))     $update['is_active'] = (int)$data['is_active'];
        if (empty($update)) return false;
        $this->db->where('id', $id)->update(db_prefix() . 'dps_voip_numbers', $update);
        return $this->db->affected_rows() > 0;
    }

    public function delete_number($id)
    {
        $this->db->where('id', $id)->delete(db_prefix() . 'dps_voip_numbers');
        return $this->db->affected_rows() > 0;
    }

    // ===================== CHAMADAS =====================

    public function get_calls($limit = 50, $filters = [])
    {
        $this->db->select('c.*, s.firstname, s.lastname, l.firstname as lead_fname, l.lastname as lead_lname')
            ->from(db_prefix() . 'dps_voip_calls c')
            ->join(db_prefix() . 'staff s', 's.staffid = c.staff_id', 'left')
            ->join(db_prefix() . 'leads l', 'l.id = c.lead_id', 'left')
            ->order_by('c.created_at', 'DESC')
            ->limit($limit);

        if (!empty($filters['staff_id']))    $this->db->where('c.staff_id', $filters['staff_id']);
        if (!empty($filters['direction']))   $this->db->where('c.direction', $filters['direction']);
        if (!empty($filters['status']))      $this->db->where('c.status', $filters['status']);
        if (!empty($filters['date_from']))   $this->db->where('DATE(c.created_at) >=', $filters['date_from']);
        if (!empty($filters['date_to']))     $this->db->where('DATE(c.created_at) <=', $filters['date_to']);

        return $this->db->get()->result_array();
    }

    public function log_call($data)
    {
        $this->db->insert(db_prefix() . 'dps_voip_calls', [
            'call_sid'    => $data['call_sid'] ?? null,
            'direction'   => $data['direction'] ?? 'outbound',
            'from_number' => $data['from_number'] ?? null,
            'to_number'   => $data['to_number'] ?? null,
            'staff_id'    => $data['staff_id'] ?? null,
            'lead_id'     => $data['lead_id'] ?? null,
            'contact_id'  => $data['contact_id'] ?? null,
            'status'      => $data['status'] ?? 'initiated',
            'started_at'  => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function update_call_status($call_sid, $status, $duration = null, $recording_url = null)
    {
        $update = ['status' => $status];
        if ($duration !== null)       $update['duration'] = (int)$duration;
        if ($recording_url !== null)  $update['recording_url'] = $recording_url;
        if (in_array($status, ['completed', 'failed', 'busy', 'no-answer', 'canceled'])) {
            $update['ended_at'] = date('Y-m-d H:i:s');
        }
        $this->db->where('call_sid', $call_sid)->update(db_prefix() . 'dps_voip_calls', $update);
        return $this->db->affected_rows() > 0;
    }

    public function get_call_by_sid($call_sid)
    {
        return $this->db->get_where(db_prefix() . 'dps_voip_calls', ['call_sid' => $call_sid])->row_array();
    }

    // ===================== STAFF =====================

    public function get_staff_list()
    {
        return $this->db->select('staffid, firstname, lastname, email')
            ->from(db_prefix() . 'staff')
            ->where('active', 1)
            ->order_by('firstname', 'ASC')
            ->get()->result_array();
    }

    // ===================== ESTATÍSTICAS =====================

    public function get_stats($staff_id = null)
    {
        $stats = [];

        $base = $this->db->from(db_prefix() . 'dps_voip_calls');
        if ($staff_id) $this->db->where('staff_id', $staff_id);
        $stats['total'] = $this->db->count_all_results(db_prefix() . 'dps_voip_calls');

        $this->db->where('direction', 'outbound');
        if ($staff_id) $this->db->where('staff_id', $staff_id);
        $stats['outbound'] = $this->db->count_all_results(db_prefix() . 'dps_voip_calls');

        $this->db->where('direction', 'inbound');
        if ($staff_id) $this->db->where('staff_id', $staff_id);
        $stats['inbound'] = $this->db->count_all_results(db_prefix() . 'dps_voip_calls');

        $this->db->where('status', 'completed');
        if ($staff_id) $this->db->where('staff_id', $staff_id);
        $stats['completed'] = $this->db->count_all_results(db_prefix() . 'dps_voip_calls');

        // Duração média
        $q = $this->db->select_avg('duration', 'avg_duration')
            ->where('status', 'completed')
            ->where('duration >', 0);
        if ($staff_id) $this->db->where('staff_id', $staff_id);
        $row = $this->db->get(db_prefix() . 'dps_voip_calls')->row_array();
        $stats['avg_duration'] = round($row['avg_duration'] ?? 0);

        return $stats;
    }
}
