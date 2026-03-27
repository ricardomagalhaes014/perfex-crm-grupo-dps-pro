<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_teams extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $dps_super_admin_email = 'ricardomagalhaes014@gmail.com';
        $staff = $this->db->select('email')->where('staffid', get_staff_user_id())->get(db_prefix() . 'staff')->row_array();
        $is_dps_owner = isset($staff['email']) && $staff['email'] === $dps_super_admin_email;
        if (!is_admin() && !$is_dps_owner) {
            access_denied('DPS Teams');
        }
        $this->load->model('dps_teams/Dps_teams_model', 'dps_teams_model');
    }

    /**
     * Página principal: lista de equipas com membros
     */
    public function index()
    {
        $data['title'] = 'Gestão de Equipas DPS';
        $data['teams'] = $this->dps_teams_model->get_teams();

        foreach ($data['teams'] as &$team) {
            $team['members'] = $this->dps_teams_model->get_team_members($team['id']);
        }
        unset($team);

        $this->load->view('dps_teams/admin/teams', $data);
    }

    /**
     * Página de objectivos mensais de uma equipa
     * GET: team_id, year, month
     */
    public function objectives($team_id = null)
    {
        if (!$team_id) { show_404(); }

        $year  = (int)($this->input->get('year')  ?: date('Y'));
        $month = (int)($this->input->get('month') ?: date('n'));

        $team = $this->dps_teams_model->get_teams($team_id);
        if (!$team) { show_404(); }

        $data['title']      = 'Objectivos — ' . $team['name'];
        $data['team']       = $team;
        $data['year']       = $year;
        $data['month']      = $month;
        $data['dashboard']  = $this->dps_teams_model->get_objectives_dashboard($team_id, $year, $month);
        $data['objectives'] = $this->dps_teams_model->get_objectives($team_id, $year, $month);

        $this->load->view('dps_teams/admin/objectives', $data);
    }

    /**
     * AJAX: gravar objectivo mensal
     * POST: team_id, staff_id, year, month, target
     */
    public function save_objective()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $team_id  = (int)$this->input->post('team_id');
        $staff_id = (int)$this->input->post('staff_id');
        $year     = (int)$this->input->post('year');
        $month    = (int)$this->input->post('month');
        $target   = (int)$this->input->post('target');

        if ($team_id <= 0 || $staff_id <= 0 || $year < 2020 || $month < 1 || $month > 12 || $target < 0) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
            return;
        }

        $ok = $this->dps_teams_model->save_objective($team_id, $staff_id, $year, $month, $target, get_staff_user_id());
        echo json_encode(['success' => (bool)$ok]);
    }

    /**
     * AJAX: obter dashboard de objectivos (para actualização dinâmica ao mudar mês)
     * POST: team_id, year, month
     */
    public function objectives_data()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $team_id = (int)$this->input->post('team_id');
        $year    = (int)$this->input->post('year');
        $month   = (int)$this->input->post('month');

        $dashboard  = $this->dps_teams_model->get_objectives_dashboard($team_id, $year, $month);
        $objectives = $this->dps_teams_model->get_objectives($team_id, $year, $month);

        echo json_encode([
            'success'    => true,
            'dashboard'  => $dashboard,
            'objectives' => $objectives,
        ]);
    }

    /**
     * AJAX: adicionar membro a uma equipa
     */
    public function add_member()
    {
        if (!$this->input->is_ajax_request() || !$this->input->post()) {
            show_404();
        }

        $team_id  = (int)$this->input->post('team_id');
        $staff_id = (int)$this->input->post('staff_id');
        $role     = $this->input->post('role');

        if (!in_array($role, ['manager', 'commercial'])) {
            echo json_encode(['success' => false, 'message' => 'Papel inválido']);
            return;
        }

        $result = $this->dps_teams_model->add_member($team_id, $staff_id, $role);

        if ($result) {
            $members = $this->dps_teams_model->get_team_members($team_id);
            echo json_encode(['success' => true, 'members' => $members]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar membro']);
        }
    }

    /**
     * AJAX: remover membro de uma equipa
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
     * AJAX: alterar o papel de um membro
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
