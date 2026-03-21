<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_teams_model extends App_Model
{
    // ─── Equipas ────────────────────────────────────────────────────────────

    /**
     * Retorna todas as equipas ou uma equipa específica por id
     */
    public function get_teams($id = null)
    {
        if ($id) {
            $this->db->where('id', (int)$id);
            return $this->db->get(db_prefix() . 'dps_teams')->row_array();
        }
        $this->db->order_by('area', 'asc');
        return $this->db->get(db_prefix() . 'dps_teams')->result_array();
    }

    /**
     * Cria ou actualiza uma equipa
     */
    public function save_team($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int)$id);
            $this->db->update(db_prefix() . 'dps_teams', $data);
            return $this->db->affected_rows() > 0;
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'dps_teams', $data);
        return $this->db->insert_id();
    }

    // ─── Membros ─────────────────────────────────────────────────────────────

    /**
     * Retorna todos os membros de uma equipa com dados do staff
     */
    public function get_team_members($team_id)
    {
        $this->db->select(
            db_prefix() . 'dps_team_members.id as member_id, ' .
            db_prefix() . 'dps_team_members.staff_id, ' .
            db_prefix() . 'dps_team_members.role, ' .
            'CONCAT(' . db_prefix() . 'staff.firstname, \' \', ' . db_prefix() . 'staff.lastname) as full_name, ' .
            db_prefix() . 'staff.email, ' .
            db_prefix() . 'staff.profile_image'
        );
        $this->db->from(db_prefix() . 'dps_team_members');
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'dps_team_members.staff_id');
        $this->db->where(db_prefix() . 'dps_team_members.team_id', (int)$team_id);
        $this->db->order_by('role', 'asc');
        return $this->db->get()->result_array();
    }

    /**
     * Retorna apenas os comerciais de uma equipa
     */
    public function get_team_commercials($team_id)
    {
        $this->db->select('staff_id');
        $this->db->where('team_id', (int)$team_id);
        $this->db->where('role', 'commercial');
        return $this->db->get(db_prefix() . 'dps_team_members')->result_array();
    }

    /**
     * Retorna o membro (com role e team_id) de um staff
     * Devolve o registo com role mais elevada se estiver em várias equipas
     */
    public function get_member($staff_id)
    {
        $this->db->where('staff_id', (int)$staff_id);
        // manager tem prioridade
        $this->db->order_by("FIELD(role,'manager','commercial')", null, false);
        $this->db->limit(1);
        return $this->db->get(db_prefix() . 'dps_team_members')->row_array();
    }

    /**
     * Adiciona um membro a uma equipa
     */
    public function add_member($team_id, $staff_id, $role)
    {
        // Verificar se já existe
        $exists = $this->db
            ->where('team_id', (int)$team_id)
            ->where('staff_id', (int)$staff_id)
            ->count_all_results(db_prefix() . 'dps_team_members');

        if ($exists > 0) {
            // Actualizar role
            $this->db->where('team_id', (int)$team_id);
            $this->db->where('staff_id', (int)$staff_id);
            $this->db->update(db_prefix() . 'dps_team_members', ['role' => $role]);
            return true;
        }

        $this->db->insert(db_prefix() . 'dps_team_members', [
            'team_id'    => (int)$team_id,
            'staff_id'   => (int)$staff_id,
            'role'       => $role,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    /**
     * Remove um membro de uma equipa
     */
    public function remove_member($member_id)
    {
        $this->db->where('id', (int)$member_id);
        $this->db->delete(db_prefix() . 'dps_team_members');
        return $this->db->affected_rows() > 0;
    }

    /**
     * Retorna todos os staff activos não presentes numa equipa (para o select de adicionar)
     */
    public function get_available_staff($team_id)
    {
        // IDs já na equipa
        $in_team = $this->db
            ->select('staff_id')
            ->where('team_id', (int)$team_id)
            ->get(db_prefix() . 'dps_team_members')
            ->result_array();
        $in_team_ids = array_column($in_team, 'staff_id');

        $this->db->select('staffid, CONCAT(firstname, \' \', lastname) as full_name, email');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        if (!empty($in_team_ids)) {
            $this->db->where_not_in('staffid', $in_team_ids);
        }
        $this->db->order_by('firstname', 'asc');
        return $this->db->get()->result_array();
    }

    /**
     * Retorna todos os staff activos (para o super admin)
     */
    public function get_all_staff()
    {
        $this->db->select('staffid, CONCAT(firstname, \' \', lastname) as full_name, email');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'asc');
        return $this->db->get()->result_array();
    }

    /**
     * Retorna as equipas onde um staff é gestor
     */
    public function get_managed_teams($staff_id)
    {
        $this->db->select(db_prefix() . 'dps_teams.*');
        $this->db->from(db_prefix() . 'dps_teams');
        $this->db->join(
            db_prefix() . 'dps_team_members',
            db_prefix() . 'dps_team_members.team_id = ' . db_prefix() . 'dps_teams.id'
        );
        $this->db->where(db_prefix() . 'dps_team_members.staff_id', (int)$staff_id);
        $this->db->where(db_prefix() . 'dps_team_members.role', 'manager');
        return $this->db->get()->result_array();
    }
}
