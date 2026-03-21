<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_teams extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        // Apenas super admins (Ricardo) acedem a este módulo
        if (!is_admin()) {
            access_denied('DPS Teams');
        }
        $this->load->model('dps_teams/Dps_teams_model', 'dps_teams_model');
    }

    /**
     * Página principal: lista de equipas com membros
     */
    public function index()
    {
        $data['title']  = 'Gestão de Equipas DPS';
        $data['teams']  = $this->dps_teams_model->get_teams();

        // Para cada equipa, carregar membros
        foreach ($data['teams'] as &$team) {
            $team['members'] = $this->dps_teams_model->get_team_members($team['id']);
        }
        unset($team);

        $this->load->view('dps_teams/admin/teams', $data);
    }

    /**
     * AJAX: adicionar membro a uma equipa
     * POST: team_id, staff_id, role
     */
    public function add_member()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $team_id  = (int)$this->input->post('team_id');
        $staff_id = (int)$this->input->post('staff_id');
        $role     = $this->input->post('role'); // 'manager' ou 'commercial'

        if (!in_array($role, ['manager', 'commercial'])) {
            echo json_encode(['success' => false, 'message' => 'Papel inválido']);
            return;
        }

        $result = $this->dps_teams_model->add_member($team_id, $staff_id, $role);

        if ($result) {
            // Recarregar membros da equipa
            $members = $this->dps_teams_model->get_team_members($team_id);
            echo json_encode(['success' => true, 'members' => $members]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar membro']);
        }
    }

    /**
     * AJAX: remover membro de uma equipa
     * POST: member_id, team_id
     */
    public function remove_member()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $member_id = (int)$this->input->post('member_id');
        $team_id   = (int)$this->input->post('team_id');

        $result = $this->dps_teams_model->remove_member($member_id);

        if ($result) {
            $members = $this->dps_teams_model->get_team_members($team_id);
            echo json_encode(['success' => true, 'members' => $members]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao remover membro']);
        }
    }

    /**
     * AJAX: obter staff disponível para adicionar a uma equipa
     * GET: team_id
     */
    public function available_staff()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $team_id = (int)$this->input->get('team_id');
        $staff   = $this->dps_teams_model->get_available_staff($team_id);
        echo json_encode($staff);
    }

    /**
     * AJAX: alterar o papel (role) de um membro
     * POST: member_id, team_id, role
     */
    public function change_role()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $member_id = (int)$this->input->post('member_id');
        $team_id   = (int)$this->input->post('team_id');
        $role      = $this->input->post('role');

        if (!in_array($role, ['manager', 'commercial'])) {
            echo json_encode(['success' => false, 'message' => 'Papel inválido']);
            return;
        }

        $this->db->where('id', $member_id);
        $this->db->update(db_prefix() . 'dps_team_members', ['role' => $role]);

        if ($this->db->affected_rows() > 0) {
            $members = $this->dps_teams_model->get_team_members($team_id);
            echo json_encode(['success' => true, 'members' => $members]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sem alterações']);
        }
    }
}
