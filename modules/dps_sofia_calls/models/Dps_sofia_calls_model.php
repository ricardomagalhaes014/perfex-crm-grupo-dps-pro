<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_sofia_calls_model extends App_Model
{
    private $api_key;
    private $phone_number_id;

    public function __construct()
    {
        parent::__construct();
        $this->api_key        = get_option('sofia_calls_elevenlabs_api_key');
        $this->phone_number_id = get_option('sofia_calls_phone_number_id');
    }

    // ─── AGENTES ────────────────────────────────────────────────────────────────

    public function get_agents()
    {
        $response = $this->_api_get('https://api.elevenlabs.io/v1/convai/agents');
        if ($response && isset($response['agents'])) {
            return $response['agents'];
        }
        return [];
    }

    // ─── LEAD STATUSES ──────────────────────────────────────────────────────────

    public function get_lead_statuses()
    {
        return $this->db->get(db_prefix() . 'leads_status')->result_array();
    }

    // ─── CAMPANHAS ──────────────────────────────────────────────────────────────

    public function get_campaigns($limit = 50)
    {
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'dps_sofia_campaigns')->result_array();
    }

    public function get_campaign($id)
    {
        return $this->db->get_where(db_prefix() . 'dps_sofia_campaigns', ['id' => $id])->row_array();
    }

    public function create_campaign($data)
    {
        // Contar leads com o status selecionado que têm telefone
        $this->db->where('status', $data['lead_status_id']);
        $this->db->where('phonenumber !=', '');
        $this->db->where('phonenumber IS NOT NULL', null, false);
        $count = $this->db->count_all_results(db_prefix() . 'leads');

        $insert = [
            'name'           => $data['name'],
            'lead_status_id' => $data['lead_status_id'],
            'agent_id'       => $data['agent_id'] ?? get_option('sofia_calls_default_agent_id'),
            'focus_text'     => $data['focus_text'] ?? null,
            'status'         => 'active',
            'total_leads'    => $count,
            'calls_made'     => 0,
            'calls_answered' => 0,
            'calls_failed'   => 0,
            'created_by'     => get_staff_user_id(),
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'dps_sofia_campaigns', $insert);
        $campaign_id = $this->db->insert_id();

        // Criar registos de chamada pendentes para cada lead
        $this->db->select('id, name, phonenumber');
        $this->db->where('status', $data['lead_status_id']);
        $this->db->where('phonenumber !=', '');
        $this->db->where('phonenumber IS NOT NULL', null, false);
        $leads = $this->db->get(db_prefix() . 'leads')->result_array();

        foreach ($leads as $lead) {
            $phone = $this->_normalize_phone($lead['phonenumber']);
            if ($phone) {
                $this->db->insert(db_prefix() . 'dps_sofia_call_logs', [
                    'campaign_id' => $campaign_id,
                    'lead_id'     => $lead['id'],
                    'lead_name'   => $lead['name'],
                    'phone_number' => $phone,
                    'status'      => 'pending',
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $campaign_id;
    }

    public function update_campaign_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'dps_sofia_campaigns', [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affected_rows() > 0;
    }

    // ─── CHAMADAS ───────────────────────────────────────────────────────────────

    public function get_call_logs($campaign_id, $limit = 100)
    {
        $this->db->where('campaign_id', $campaign_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'dps_sofia_call_logs')->result_array();
    }

    public function process_pending_calls()
    {
        // Buscar campanhas ativas
        $campaigns = $this->db->get_where(
            db_prefix() . 'dps_sofia_campaigns',
            ['status' => 'active']
        )->result_array();

        $delay = (int) get_option('sofia_calls_delay_between_calls') ?: 3;

        foreach ($campaigns as $campaign) {
            // Buscar próxima chamada pendente
            $this->db->where('campaign_id', $campaign['id']);
            $this->db->where('status', 'pending');
            $this->db->limit(1);
            $call = $this->db->get(db_prefix() . 'dps_sofia_call_logs')->row_array();

            if (!$call) {
                // Campanha concluída
                $this->update_campaign_status($campaign['id'], 'completed');
                continue;
            }

            // Fazer a chamada
            $result = $this->_make_call(
                $call['phone_number'],
                $campaign['agent_id'],
                $campaign['focus_text'],
                $call['lead_name']
            );

            $new_status = 'failed';
            $call_id    = null;

            if ($result && isset($result['call_id'])) {
                $new_status = 'calling';
                $call_id    = $result['call_id'];
            }

            // Atualizar registo da chamada
            $this->db->where('id', $call['id']);
            $this->db->update(db_prefix() . 'dps_sofia_call_logs', [
                'status'            => $new_status,
                'elevenlabs_call_id' => $call_id,
                'started_at'        => date('Y-m-d H:i:s'),
            ]);

            // Atualizar contadores da campanha
            $this->db->where('id', $campaign['id']);
            $this->db->set('calls_made', 'calls_made + 1', false);
            if ($new_status === 'failed') {
                $this->db->set('calls_failed', 'calls_failed + 1', false);
            }
            $this->db->update(db_prefix() . 'dps_sofia_campaigns');

            // Pausa entre chamadas
            if ($delay > 0) {
                sleep($delay);
            }
        }
    }

    public function make_immediate_call($campaign_id)
    {
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign || $campaign['status'] !== 'active') {
            return ['success' => false, 'message' => 'Campanha não ativa'];
        }

        // Buscar próxima chamada pendente
        $this->db->where('campaign_id', $campaign_id);
        $this->db->where('status', 'pending');
        $this->db->limit(1);
        $call = $this->db->get(db_prefix() . 'dps_sofia_call_logs')->row_array();

        if (!$call) {
            $this->update_campaign_status($campaign_id, 'completed');
            return ['success' => false, 'message' => 'Sem chamadas pendentes'];
        }

        $result = $this->_make_call(
            $call['phone_number'],
            $campaign['agent_id'],
            $campaign['focus_text'],
            $call['lead_name']
        );

        if ($result && isset($result['call_id'])) {
            $this->db->where('id', $call['id']);
            $this->db->update(db_prefix() . 'dps_sofia_call_logs', [
                'status'            => 'calling',
                'elevenlabs_call_id' => $result['call_id'],
                'started_at'        => date('Y-m-d H:i:s'),
            ]);
            $this->db->where('id', $campaign_id);
            $this->db->set('calls_made', 'calls_made + 1', false);
            $this->db->update(db_prefix() . 'dps_sofia_campaigns');
            return ['success' => true, 'call_id' => $result['call_id'], 'lead' => $call['lead_name']];
        }

        return ['success' => false, 'message' => 'Erro ao iniciar chamada', 'detail' => $result];
    }

    public function get_campaign_stats($campaign_id)
    {
        $this->db->select('
            COUNT(*) as total,
            SUM(status = "pending") as pending,
            SUM(status = "calling") as calling,
            SUM(status = "answered") as answered,
            SUM(status = "no_answer") as no_answer,
            SUM(status = "failed") as failed,
            SUM(status = "busy") as busy
        ');
        $this->db->where('campaign_id', $campaign_id);
        return $this->db->get(db_prefix() . 'dps_sofia_call_logs')->row_array();
    }

    // ─── HELPERS ────────────────────────────────────────────────────────────────

    private function _make_call($phone, $agent_id, $focus_text = null, $lead_name = null)
    {
        $prompt = '';
        if ($focus_text) {
            $prompt = $focus_text;
        }
        if ($lead_name) {
            $prompt = "O nome do lead é {$lead_name}. " . $prompt;
        }

        $payload = [
            'agent_id'    => $agent_id,
            'agent_phone_number_id' => $this->phone_number_id,
            'to_number'   => $phone,
        ];

        if ($prompt) {
            $payload['conversation_initiation_client_data'] = [
                'dynamic_variables' => [
                    'lead_name'  => $lead_name ?? '',
                    'focus_text' => $focus_text ?? '',
                ],
            ];
        }

        return $this->_api_post('https://api.elevenlabs.io/v1/convai/twilio-outbound-call', $payload);
    }

    private function _normalize_phone($phone)
    {
        // Remover espaços e caracteres não numéricos exceto +
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));
        if (empty($phone)) return null;

        // Se começa com 9 ou 2 (Portugal sem código), adicionar +351
        if (preg_match('/^[92]\d{8}$/', $phone)) {
            $phone = '+351' . $phone;
        }
        // Se começa com 00351
        if (substr($phone, 0, 5) === '00351') {
            $phone = '+' . substr($phone, 2);
        }
        // Se começa com 351 sem +
        if (preg_match('/^351\d{9}$/', $phone)) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    private function _api_get($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'xi-api-key: ' . $this->api_key,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ? json_decode($response, true) : null;
    }

    private function _api_post($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'xi-api-key: ' . $this->api_key,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ? json_decode($response, true) : null;
    }
}
