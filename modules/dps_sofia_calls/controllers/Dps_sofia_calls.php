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
        
        // Lista de agentes com IDs reais da ElevenLabs
        $data['agents_list'] = [
            ['agent_id' => 'agent_0901kv03vzc4eqnvzt5758mms6t8', 'name' => 'Sofia - Assistente DPS Imobiliario'],
            ['agent_id' => 'agent_9901kv1pvewveh9s9ebs1rys274k', 'name' => 'Sofia - Outbound Belo Horizonte'],
            ['agent_id' => 'agent_4301kv1pv8g8e259bbdyfk7mrefb', 'name' => 'Sofia - Outbound Raizes'],
            ['agent_id' => 'agent_7501kv0dj084fmbahfdafsfmgcfv', 'name' => 'Sofia - Raizes DPS'],
            ['agent_id' => 'agent_1901kv0dj4m0fxnr5pxqdhqzjf26', 'name' => 'Sofia - Belo Horizonte DPS'],
            ['agent_id' => 'agent_2901kv39h680esb9wtrx6yk291sw', 'name' => 'Sofia - Lake Towers DPS'],
            ['agent_id' => 'agent_9501kv39wjr4etjre7118p0ejncp', 'name' => 'Sofia - DPS Brasil'],
            ['agent_id' => 'agent_4301kv39h81keckrf9dkd1cb7mxk', 'name' => 'Sofia - DPS Brasil (2)'],
            ['agent_id' => 'agent_9201kv3brmpcehrtyhahfq214bq8', 'name' => 'Sofia - Sky Marine Towers DPS']
        ];
        
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
            'agent_id'       => $this->input->post('agent_id'),
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

    public function delete_campaign()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id = (int) $this->input->post('id');
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false]);
            exit;
        }

        $ok = $this->Dps_sofia_calls_model->delete_campaign($id);

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

    /**
     * Endpoint de diagnóstico — acessível via GET:
     * https://crm.grupo-dps.com/admin/dps_sofia_calls/diag
     * https://crm.grupo-dps.com/admin/dps_sofia_calls/diag?run=1  (executa process_pending_calls)
     */
    public function diag()
    {
        // Apenas admins
        if (!is_admin()) show_404();

        $api_key = 'e632bad54e6bf1bfb697cf7d095a6d0aa514fc4c03a77e1180b4ccd544d50348';
        $out     = [];

        // Chamadas em 'calling'
        $this->db->where('status', 'calling');
        $this->db->select('id, campaign_id, lead_name, phone_number, elevenlabs_call_id, started_at,
            TIMESTAMPDIFF(SECOND, started_at, NOW()) as elapsed_secs');
        $calling = $this->db->get(db_prefix() . 'dps_sofia_call_logs')->result_array();
        $out['calling_count'] = count($calling);
        $out['calling_calls'] = $calling;

        // Campanhas ativas
        $this->db->where('status', 'active');
        $out['active_campaigns'] = $this->db->get(db_prefix() . 'dps_sofia_campaigns')->result_array();

        // Verificar hook cron
        $this->db->where('hook_name', 'perfex_cron');
        $out['cron_hooks'] = $this->db->get(db_prefix() . 'hooks')->result_array();

        // Testar ElevenLabs para a primeira chamada presa
        if (!empty($calling) && !empty($calling[0]['elevenlabs_call_id'])) {
            $conv_id = $calling[0]['elevenlabs_call_id'];
            $ch = curl_init('https://api.elevenlabs.io/v1/convai/conversations/' . $conv_id);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ['xi-api-key: ' . $api_key],
                CURLOPT_TIMEOUT        => 10,
            ]);
            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);
            $out['elevenlabs_test'] = [
                'conv_id'    => $conv_id,
                'response'   => $resp ? json_decode($resp, true) : null,
                'curl_error' => $err ?: null,
            ];
        }

        // Executar process_pending_calls se ?run=1
        if ($this->input->get('run') == '1') {
            $out['process_result'] = 'A executar process_pending_calls...';
            $this->Dps_sofia_calls_model->process_pending_calls();
            $out['process_result'] = 'Concluido. Verifique os logs.';

            // Estado após execução
            $this->db->where('status', 'calling');
            $out['calling_after'] = $this->db->count_all_results(db_prefix() . 'dps_sofia_call_logs');
        }

        header('Content-Type: application/json');
        echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
