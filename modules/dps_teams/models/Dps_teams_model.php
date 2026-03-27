<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_teams_model extends App_Model
{
    // ─── Equipas ────────────────────────────────────────────────────────────

    public function get_teams($id = null)
    {
        if ($id) {
            $this->db->where('id', (int)$id);
            return $this->db->get(db_prefix() . 'dps_teams')->row_array();
        }
        $this->db->order_by('area', 'asc');
        return $this->db->get(db_prefix() . 'dps_teams')->result_array();
    }

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

    /**
     * Obter as sub-equipas directas de uma equipa pai
     */
    public function get_sub_teams($parent_id)
    {
        $p   = db_prefix();
        $sql = "SELECT id, name, area, parent_id, source_ids, description, created_at
                FROM {$p}dps_teams
                WHERE parent_id = ?
                ORDER BY name ASC";
        return $this->db->query($sql, [(int)$parent_id])->result_array();
    }

    // ─── Membros ─────────────────────────────────────────────────────────────

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

    public function get_team_commercials($team_id)
    {
        $this->db->select('staff_id');
        $this->db->where('team_id', (int)$team_id);
        $this->db->where('role', 'commercial');
        return $this->db->get(db_prefix() . 'dps_team_members')->result_array();
    }

    public function get_member($staff_id)
    {
        $this->db->where('staff_id', (int)$staff_id);
        $this->db->order_by("FIELD(role,'manager','commercial')", null, false);
        $this->db->limit(1);
        return $this->db->get(db_prefix() . 'dps_team_members')->row_array();
    }

    public function add_member($team_id, $staff_id, $role)
    {
        $exists = $this->db
            ->where('team_id', (int)$team_id)
            ->where('staff_id', (int)$staff_id)
            ->count_all_results(db_prefix() . 'dps_team_members');

        if ($exists > 0) {
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

    public function remove_member($member_id)
    {
        $this->db->where('id', (int)$member_id);
        $this->db->delete(db_prefix() . 'dps_team_members');
        return $this->db->affected_rows() > 0;
    }

    public function get_available_staff($team_id)
    {
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

    public function get_all_staff()
    {
        $this->db->select('staffid, CONCAT(firstname, \' \', lastname) as full_name, email');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'asc');
        return $this->db->get()->result_array();
    }

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

    // ─── Objectivos Mensais ───────────────────────────────────────────────────

    /**
     * Gravar ou actualizar o objectivo mensal de um comercial numa equipa
     */
    public function save_objective($team_id, $staff_id, $year, $month, $target, $created_by)
    {
        $existing = $this->db
            ->where('team_id', (int)$team_id)
            ->where('staff_id', (int)$staff_id)
            ->where('year', (int)$year)
            ->where('month', (int)$month)
            ->get('tbldps_objectives')
            ->row_array();

        if ($existing) {
            $this->db->where('id', $existing['id']);
            $this->db->update('tbldps_objectives', ['target_interactions' => (int)$target]);
            return true;
        }

        $this->db->insert('tbldps_objectives', [
            'team_id'             => (int)$team_id,
            'staff_id'            => (int)$staff_id,
            'year'                => (int)$year,
            'month'               => (int)$month,
            'target_interactions' => (int)$target,
            'created_by'          => (int)$created_by,
        ]);
        return $this->db->insert_id() > 0;
    }

    /**
     * Obter objectivos de uma equipa num determinado mês/ano
     * Retorna array indexado por staff_id
     */
    public function get_objectives($team_id, $year, $month)
    {
        $rows = $this->db
            ->where('team_id', (int)$team_id)
            ->where('year', (int)$year)
            ->where('month', (int)$month)
            ->get('tbldps_objectives')
            ->result_array();

        $result = [];
        foreach ($rows as $r) {
            $result[$r['staff_id']] = (int)$r['target_interactions'];
        }
        return $result;
    }

    /**
     * Contar as interacções reais de um comercial num mês/ano.
     * Interacção = nota escrita numa lead SEM alteração de estado
     * (entradas no lead_activity_log com description que começa por "Nota"
     *  e que NÃO têm uma entrada de alteração de estado no mesmo segundo)
     */
    public function count_interactions($staff_id, $year, $month)
    {
        $start = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01 00:00:00';
        $end   = date('Y-m-t 23:59:59', strtotime($start));
        $p     = db_prefix();

        // Conta entradas de "Nota" do comercial que NÃO sejam acompanhadas
        // de uma alteração de estado na mesma lead no mesmo segundo
        $sql = "SELECT COUNT(*) as cnt
                FROM {$p}lead_activity_log note_log
                WHERE note_log.staffid = ?
                  AND note_log.date >= ?
                  AND note_log.date <= ?
                  AND note_log.description LIKE 'Nota%'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM {$p}lead_activity_log status_log
                      WHERE status_log.leadid    = note_log.leadid
                        AND status_log.staffid   = note_log.staffid
                        AND status_log.date      = note_log.date
                        AND status_log.description LIKE 'Status alterado%'
                  )";

        $row = $this->db->query($sql, [(int)$staff_id, $start, $end])->row_array();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Obter dashboard de objectivos para uma equipa simples (sem sub-equipas).
     * Retorna array de comerciais com: staff_id, full_name, target, real, pct
     */
    private function build_dashboard_for_team($team_id, $year, $month)
    {
        $p = db_prefix();

        // Obter todos os comerciais da equipa via SQL raw (evita problemas CI3/MariaDB)
        $sql = "SELECT m.staff_id,
                       CONCAT(s.firstname, ' ', s.lastname) AS full_name,
                       s.profile_image
                FROM {$p}dps_team_members m
                JOIN {$p}staff s ON s.staffid = m.staff_id
                WHERE m.team_id = ?
                  AND m.role = 'commercial'
                ORDER BY s.firstname ASC";

        $commercials = $this->db->query($sql, [(int)$team_id])->result_array();

        // Obter objectivos definidos para esta sub-equipa
        $objectives = $this->get_objectives($team_id, $year, $month);

        $result = [];
        foreach ($commercials as $c) {
            $sid    = (int)$c['staff_id'];
            $target = isset($objectives[$sid]) ? (int)$objectives[$sid] : 0;
            $real   = $this->count_interactions($sid, $year, $month);
            $pct    = $target > 0 ? min(100, round($real / $target * 100)) : ($real > 0 ? 100 : 0);

            $result[] = [
                'staff_id'      => $sid,
                'full_name'     => $c['full_name'],
                'profile_image' => $c['profile_image'],
                'target'        => $target,
                'real'          => $real,
                'pct'           => $pct,
            ];
        }
        return $result;
    }

    /**
     * Obter dashboard completo de objectivos vs real para uma equipa num mês/ano.
     *
     * Se a equipa tiver sub-equipas, devolve um array agrupado:
     *   [ ['sub_team' => [...], 'rows' => [...]], ... ]
     *   com a chave 'has_sub_teams' => true
     *
     * Se a equipa não tiver sub-equipas, devolve o array simples de comerciais
     *   com a chave 'has_sub_teams' => false
     */
    public function get_objectives_dashboard($team_id, $year, $month)
    {
        $sub_teams = $this->get_sub_teams($team_id);

        if (!empty($sub_teams)) {
            // Equipa pai com sub-equipas: agrupar por sub-equipa
            $groups = [];
            foreach ($sub_teams as $sub) {
                $rows = $this->build_dashboard_for_team($sub['id'], $year, $month);
                $groups[] = [
                    'sub_team' => $sub,
                    'rows'     => $rows,
                ];
            }
            return [
                'has_sub_teams' => true,
                'groups'        => $groups,
            ];
        }

        // Equipa simples: retornar lista directa
        return [
            'has_sub_teams' => false,
            'rows'          => $this->build_dashboard_for_team($team_id, $year, $month),
        ];
    }
}
