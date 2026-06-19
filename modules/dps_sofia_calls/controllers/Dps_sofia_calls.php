<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_sofia_calls extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_sofia_calls/Dps_sofia_calls_model');
    }

    public function index()
    {
        $data['title']         = 'Sofia Calls';
        $data['lead_statuses'] = $this->Dps_sofia_calls_model->get_lead_statuses();
        $data['staff_list']    = $this->Dps_sofia_calls_model->get_staff_list();
        $data['campaigns']     = $this->Dps_sofia_calls_model->get_campaigns(20);
        $this->load->view('dps_sofia_calls/sofia_calls/index', $data);
    }

    public function create_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $data = [
            'name'           => $this->input->post('name'),
            'lead_status_id' => (int) $this->input->post('lead_status_id'),
            'staff_id'       => (int) $this->input->post('staff_id'),
            'focus_text'     => $this->input->post('focus_text'),
            'agent_id'       => $this->input->post('agent_id'),
        ];

        if (empty($data['name']) || empty($data['lead_status_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nome e estado obrigatorios']);
            exit;
        }

        $campaign_id = $this->Dps_sofia_calls_model->create_campaign($data);

        header('Content-Type: application/json');
        echo json_encode([
            'success'     => true,
            'campaign_id' => $campaign_id,
            'message'     => 'Campanha criada em estado pausado. Clique em Iniciar quando quiser comecar.',
        ]);
        exit;
    }

    public function campaign_action()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id     = (int) $this->input->post('id');
        $action = $this->input->post('action');

        $allowed = ['active', 'paused', 'stopped'];
        if (!in_array($action, $allowed)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acao invalida']);
            exit;
        }

        $ok = $this->Dps_sofia_calls_model->update_campaign_status($id, $action);

        // Quando inicia a campanha, disparar imediatamente a primeira chamada
        $call_result = null;
        if ($action === 'active' && $ok) {
            $call_result = $this->Dps_sofia_calls_model->make_immediate_call($id);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'call' => $call_result]);
        exit;
    }

    public function make_call()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $campaign_id = (int) $this->input->post('campaign_id');
        $result      = $this->Dps_sofia_calls_model->make_immediate_call($campaign_id);

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function update_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id = (int) $this->input->post('id');
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ID invalido']);
            exit;
        }

        $data = [
            'name'           => $this->input->post('name'),
            'lead_status_id' => (int) $this->input->post('lead_status_id'),
            'staff_id'       => (int) $this->input->post('staff_id'),
            'focus_text'     => $this->input->post('focus_text'),
        ];

        if (empty($data['name']) || empty($data['lead_status_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nome e estado obrigatorios']);
            exit;
        }

        $ok = $this->Dps_sofia_calls_model->update_campaign($id, $data);

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }

    public function campaign_detail()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id    = (int) $this->input->post('id');
        $stats = $this->Dps_sofia_calls_model->get_campaign_stats($id);
        $logs  = $this->Dps_sofia_calls_model->get_call_logs($id, 50);

        header('Content-Type: application/json');
        echo json_encode(['stats' => $stats, 'logs' => $logs]);
        exit;
    }
}
