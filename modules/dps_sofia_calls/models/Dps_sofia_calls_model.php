<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_sofia_calls_model extends App_Model
{
    private $api_key;
    private $phone_number_id;

    public function __construct()
    {
        parent::__construct();
        $this->api_key         = get_option('sofia_calls_elevenlabs_api_key');
        $this->phone_number_id = get_option('sofia_calls_phone_number_id');
        $this->_create_tables();
    }

    private function _create_tables()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_sofia_campaigns` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `lead_status_id` int(11) NOT NULL,
            `staff_id` int(11) NOT NULL DEFAULT 0,
            `agent_id` varchar(100) NOT NULL DEFAULT 'agent_9901kv1pvewveh9s9ebs1rys274k',
            `focus_text` text DEFAULT NULL,
            `status` enum('active','paused','completed','stopped') NOT NULL DEFAULT 'paused',
            `total_leads` int(11) NOT NULL DEFAULT 0,
            `calls_made` int(11) NOT NULL DEFAULT 0,
            `calls_answered` int(11) NOT NULL DEFAULT 0,
            `calls_failed` int(11) NOT NULL DEFAULT 0,
            `created_by` int(11) NOT NULL DEFAULT 0,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $this->db->query("ALTER TABLE `" . db_prefix() . "dps_sofia_campaigns`
            ADD COLUMN IF NOT EXISTS `staff_id` int(11) NOT NULL DEFAULT 0 AFTER `lead_status_id`;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_sofia_call_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `campaign_id` int(11) NOT NULL,
            `lead_id` int(11) NOT NULL,
            `lead_name` varchar(255) DEFAULT NULL,
            `phone_number` varchar(50) NOT NULL,
            `elevenlabs_call_id` varchar(100) DEFAULT NULL,
            `status` enum('pending','calling','answered','no_answer','failed','busy') NOT NULL DEFAULT 'pending',
            `duration` int(11) DEFAULT NULL,
            `started_at` datetime DEFAULT NULL,
            `ended_at` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `campaign_id` (`campaign_id`),
            KEY `lead_id` (`lead_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    public function get_lead_statuses()
    {
        return $this->db->get(db_prefix() . 'leads_status')->result_array();
    }

    public function get_staff_list()
    {
        $this->db->select('staffid, CONCAT(firstname, " ", lastname) as fullname');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');
        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

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
        $this->db->where('status', $data['lead_status_id']);
        $this->db->where('phonenumber !=', '');
        $this->db->where('phonenumber IS NOT NULL', null, false);
        if (!empty($data['staff_id'])) {
            $this->db->where('assigned', (int)$data['staff_id']);
        }
        $count = $this->db->count_all_results(db_prefix() . 'leads');

        $insert = [
            'name'           => $data['name'],
            'lead_status_id' => $data['lead_status_id'],
            'staff_id'       => isset($data['staff_id']) ? (int)$data['staff_id'] : 0,
            'agent_id'       => isset($data['agent_id']) && $data['agent_id'] ? $data['agent_id'] : 'agent_9901kv1pvewveh9s9ebs1rys274k',
            'focus_text'     => isset($data['focus_text']) ? $data['focus_text'] : null,
            'status'         => 'paused',
            'total_leads'    => $count,
            'calls_made'     => 0,
            'calls_answered' => 0,
            'calls_failed'   => 0,
            'created_by'     => get_staff_user_id(),
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(db_prefix() . 'dps_sofia_campaigns', $insert);
        $campaign_id = $this->db->insert_id();

        $this->db->select('id, name, phonenumber');
        $this->db->where('status', $data['lead_status_id']);
        $this->db->where('phonenumber !=', '');
        $this->db->where('phonenumber IS NOT NULL', null, false);
        if (!empty($data['staff_id'])) {
            $this->db->where('assigned', (int)$data['staff_id']);
        }
        $leads = $this->db->get(db_prefix() . 'leads')->result_array();

        foreach ($leads as $lead) {
            $phone = $this->_normalize_phone($lead['phonenumber']);
            if ($phone) {
                $this->db->insert(db_prefix() . 'dps_sofia_call_logs', [
                    'campaign_id'  => $campaign_id,
                    'lead_id'      => $lead['id'],
                    'lead_name'    => $lead['name'],
                    'phone_number' => $phone,
                    'status'       => 'pending',
                    'created_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $campaign_id;
    }

    public function update_campaign($id, $data)
    {
        $update = [
            'name'           => $data['name'],
            'lead_status_id' => $data['lead_status_id'],
            'staff_id'       => isset($data['staff_id']) ? (int)$data['staff_id'] : 0,
            'focus_text'     => isset($data['focus_text']) ? $data['focus_text'] : null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'dps_sofia_campaigns', $update);
        return $this->db->affected_rows() >= 0;
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

    public function get_call_logs($campaign_id, $limit = 100)
    {
        $this->db->where('campaign_id', $campaign_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'dps_sofia_call_logs')->result_array();
    }

    public function make_immediate_call($campaign_id)
    {
        $campaign = $this->get_campaign($campaign_id);
        if (!$campaign || $campaign['status'] !== 'active') {
            return ['success' => false, 'message' => 'Campanha nao esta ativa. Inicie a campanha primeiro.'];
        }

        $this->db->where('campaign_id', $campaign_id);
        $this->db->where('status', 'pending');
        $this->db->limit(1);
        $call = $this->db->get(db_prefix() . 'dps_sofia_call_logs')->row_array();

        if (!$call) {
            $this->update_campaign_status($campaign_id, 'completed');
            return ['success' => false, 'message' => 'Sem chamadas pendentes. Campanha concluida.'];
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
                'status'             => 'calling',
                'elevenlabs_call_id' => $result['call_id'],
                'started_at'         => date('Y-m-d H:i:s'),
            ]);
            $this->db->where('id', $campaign_id);
            $this->db->set('calls_made', 'calls_made + 1', false);
            $this->db->update(db_prefix() . 'dps_sofia_campaigns');
            return ['success' => true, 'call_id' => $result['call_id'], 'lead' => $call['lead_name']];
        }

        return ['success' => false, 'message' => 'Erro ao iniciar chamada', 'detail' => $result];
    }

    public function delete_campaign($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'dps_sofia_campaigns');
        $this->db->where('campaign_id', $id);
        $this->db->delete(db_prefix() . 'dps_sofia_call_logs');
        return true;
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

    private function _make_call($phone, $agent_id, $focus_text = null, $lead_name = null)
    {
        $api_key         = $this->api_key         ?: 'e632bad54e6bf1bfb697cf7d095a6d0aa514fc4c03a77e1180b4ccd544d50348';
        $phone_number_id = $this->phone_number_id ?: 'phnum_6701kvea8mbhe4vbdz75jf1wd1y7';

        $payload = [
            'agent_id'              => $agent_id,
            'agent_phone_number_id' => $phone_number_id,
            'to_number'             => $phone,
        ];

        if ($focus_text || $lead_name) {
            $payload['conversation_initiation_client_data'] = [
                'dynamic_variables' => [
                    'lead_name'  => $lead_name  ? $lead_name  : '',
                    'focus_text' => $focus_text ? $focus_text : '',
                ],
            ];
        }

        return $this->_api_post('https://api.elevenlabs.io/v1/convai/twilio-outbound-call', $payload, $api_key);
    }

    private function _normalize_phone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', trim($phone));
        if (empty($phone)) return null;
        if (preg_match('/^[92]\d{8}$/', $phone)) {
            $phone = '+351' . $phone;
        } elseif (substr($phone, 0, 5) === '00351') {
            $phone = '+' . substr($phone, 2);
        } elseif (preg_match('/^351\d{9}$/', $phone)) {
            $phone = '+' . $phone;
        }
        return $phone;
    }

    private function _api_post($url, $data, $api_key = null)
    {
        if (!$api_key) $api_key = $this->api_key;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'xi-api-key: ' . $api_key,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response ? json_decode($response, true) : null;
    }
}
