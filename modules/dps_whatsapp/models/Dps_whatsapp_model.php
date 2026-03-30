<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_whatsapp_model extends CI_Model
{
    const WA_SERVICE_URL = 'http://127.0.0.1:3001';
    const WA_API_KEY     = 'dps-wa-secret-2026';

    // ─── Configuração do staff ────────────────────────────────────────────────

    public function get_config($staff_id)
    {
        return $this->db->where('staff_id', (int)$staff_id)
            ->get(db_prefix() . 'dps_whatsapp_config')->row_array();
    }

    public function save_config($staff_id, $data)
    {
        $existing = $this->get_config($staff_id);
        $data['staff_id'] = (int)$staff_id;
        if ($existing) {
            $this->db->where('staff_id', (int)$staff_id)
                ->update(db_prefix() . 'dps_whatsapp_config', $data);
        } else {
            $this->db->insert(db_prefix() . 'dps_whatsapp_config', $data);
        }
    }

    // ─── Comunicação com o microserviço ──────────────────────────────────────

    public function wa_request($method, $endpoint, $body = [])
    {
        $url = self::WA_SERVICE_URL . $endpoint;
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . self::WA_API_KEY,
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);
        if ($err) return ['error' => $err];
        return json_decode($response, true) ?: ['error' => 'Resposta inválida'];
    }

    public function get_wa_status($staff_id)
    {
        return $this->wa_request('GET', '/status?staff_id=' . (int)$staff_id . '&api_key=' . self::WA_API_KEY);
    }

    public function get_wa_qr($staff_id)
    {
        return $this->wa_request('GET', '/qr?staff_id=' . (int)$staff_id . '&api_key=' . self::WA_API_KEY);
    }

    public function wa_connect($staff_id)
    {
        return $this->wa_request('POST', '/connect', ['staff_id' => (int)$staff_id]);
    }

    public function wa_disconnect($staff_id)
    {
        $result = $this->wa_request('POST', '/disconnect', ['staff_id' => (int)$staff_id]);
        $this->save_config($staff_id, ['is_connected' => 0, 'phone_number' => null]);
        return $result;
    }

    public function wa_send($staff_id, $to, $message)
    {
        return $this->wa_request('POST', '/send', [
            'staff_id' => (int)$staff_id,
            'to'       => $to,
            'message'  => $message,
        ]);
    }

    // ─── Automações personalizadas ────────────────────────────────────────────

    public function get_automations($staff_id = null)
    {
        $this->db->select('a.*, ls.name as status_name');
        $this->db->from(db_prefix() . 'dps_whatsapp_automations a');
        $this->db->join(db_prefix() . 'leads_status ls', 'ls.id = a.lead_status_id', 'left');
        if ($staff_id) {
            $this->db->where('a.staff_id', (int)$staff_id);
        }
        $this->db->order_by('a.created_at', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_automation($id, $staff_id = null)
    {
        $this->db->where('id', (int)$id);
        if ($staff_id) $this->db->where('staff_id', (int)$staff_id);
        return $this->db->get(db_prefix() . 'dps_whatsapp_automations')->row_array();
    }

    public function save_automation($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', (int)$id)->update(db_prefix() . 'dps_whatsapp_automations', $data);
            return $id;
        } else {
            $this->db->insert(db_prefix() . 'dps_whatsapp_automations', $data);
            return $this->db->insert_id();
        }
    }

    public function delete_automation($id, $staff_id)
    {
        $this->db->where('id', (int)$id)
            ->where('staff_id', (int)$staff_id)
            ->delete(db_prefix() . 'dps_whatsapp_automations');
        return $this->db->affected_rows() > 0;
    }

    public function toggle_automation($id, $staff_id, $is_active)
    {
        $this->db->where('id', (int)$id)
            ->where('staff_id', (int)$staff_id)
            ->update(db_prefix() . 'dps_whatsapp_automations', ['is_active' => (int)$is_active]);
    }

    // ─── Agendamento de follow-ups baseado em automações ─────────────────────

    /**
     * Obtém todas as automações activas para um staff e estado de lead
     */
    private function get_active_automations_for($staff_id, $lead_status_id)
    {
        return $this->db
            ->where('staff_id', (int)$staff_id)
            ->where('lead_status_id', (int)$lead_status_id)
            ->where('is_active', 1)
            ->get(db_prefix() . 'dps_whatsapp_automations')
            ->result_array();
    }

    /**
     * Obtém todos os estados de lead que têm automações activas para um staff
     */
    private function get_active_status_ids_for_staff($staff_id)
    {
        $rows = $this->db
            ->select('DISTINCT lead_status_id')
            ->where('staff_id', (int)$staff_id)
            ->where('is_active', 1)
            ->get(db_prefix() . 'dps_whatsapp_automations')
            ->result_array();
        return array_column($rows, 'lead_status_id');
    }

    /**
     * Quando uma lead é criada ou o estado muda:
     * - Cancelar follow-ups pendentes anteriores
     * - Agendar novos follow-ups para cada automação activa que corresponda ao estado
     */
    public function schedule_followup($lead_id)
    {
        $lead = $this->db->where('id', (int)$lead_id)->get(db_prefix() . 'leads')->row_array();
        if (!$lead) return;
        $this->_schedule_for_lead($lead);
    }

    public function reschedule_followup($lead_id)
    {
        $lead = $this->db->where('id', (int)$lead_id)->get(db_prefix() . 'leads')->row_array();
        if (!$lead) return;

        // Cancelar follow-ups pendentes anteriores
        $this->db->where('lead_id', (int)$lead_id)
            ->where('status', 'pending')
            ->update(db_prefix() . 'dps_whatsapp_followups', ['status' => 'cancelled']);

        $this->_schedule_for_lead($lead);
    }

    private function _schedule_for_lead($lead)
    {
        $staff_id  = (int)$lead['assigned'];
        $status_id = (int)$lead['status'];

        // Obter automações activas para este staff e estado
        $automations = $this->get_active_automations_for($staff_id, $status_id);
        if (empty($automations)) return;

        foreach ($automations as $auto) {
            $days         = max(1, (int)$auto['days_delay']);
            $scheduled_at = date('Y-m-d H:i:s', strtotime("+{$days} days"));

            $this->db->insert(db_prefix() . 'dps_whatsapp_followups', [
                'lead_id'        => (int)$lead['id'],
                'staff_id'       => $staff_id,
                'scheduled_at'   => $scheduled_at,
                'status'         => 'pending',
                'lead_status_id' => $status_id,
                'automation_id'  => (int)$auto['id'],
            ]);
        }
    }

    /**
     * Processar follow-ups pendentes (cron)
     */
    public function process_pending_followups()
    {
        $now = date('Y-m-d H:i:s');
        $followups = $this->db
            ->where('status', 'pending')
            ->where('scheduled_at <=', $now)
            ->get(db_prefix() . 'dps_whatsapp_followups')
            ->result_array();

        foreach ($followups as $followup) {
            $this->process_single_followup($followup);
        }
    }

    private function process_single_followup($followup)
    {
        $lead_id      = (int)$followup['lead_id'];
        $staff_id     = (int)$followup['staff_id'];
        $automation_id = isset($followup['automation_id']) ? (int)$followup['automation_id'] : null;

        // Verificar estado actual da lead
        $lead = $this->db->where('id', $lead_id)->get(db_prefix() . 'leads')->row_array();
        if (!$lead) { $this->mark_followup($followup['id'], 'cancelled'); return; }

        $current_status = (int)$lead['status'];

        // Obter a automação (personalizada ou por estado)
        $message = null;
        if ($automation_id) {
            $auto = $this->get_automation($automation_id);
            // Verificar se a automação ainda está activa e se o estado ainda corresponde
            if (!$auto || !$auto['is_active'] || (int)$auto['lead_status_id'] !== $current_status) {
                $this->mark_followup($followup['id'], 'cancelled');
                return;
            }
            $message = $auto['message'];
        } else {
            // Fallback: verificar automações para o estado actual
            $autos = $this->get_active_automations_for($staff_id, $current_status);
            if (empty($autos)) { $this->mark_followup($followup['id'], 'cancelled'); return; }
            $message = $autos[0]['message'];
        }

        // Verificar se o WhatsApp está ligado
        $config = $this->get_config($staff_id);
        if (!$config || !$config['is_connected']) {
            $status = $this->get_wa_status($staff_id);
            if (empty($status['connected'])) {
                $this->mark_followup($followup['id'], 'failed');
                return;
            }
            $this->save_config($staff_id, ['is_connected' => 1]);
        }

        // Obter número de telefone
        $phone = $lead['phonenumber'] ?? '';
        if (empty($phone)) { $this->mark_followup($followup['id'], 'failed'); return; }

        // Substituir variáveis na mensagem
        $message = str_replace('{{nome}}', $lead['name'] ?? '', $message);

        // Enviar
        $result = $this->wa_send($staff_id, $phone, $message);
        $this->mark_followup($followup['id'], !empty($result['success']) ? 'sent' : 'failed');
    }

    private function mark_followup($id, $status)
    {
        $data = ['status' => $status];
        if ($status === 'sent') $data['sent_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int)$id)->update(db_prefix() . 'dps_whatsapp_followups', $data);
    }

    // ─── Listagem de follow-ups ───────────────────────────────────────────────

    public function get_followups($staff_id = null, $limit = 100)
    {
        $this->db->select('f.*, l.name as lead_name, l.phonenumber, ls.name as status_name, s.firstname, s.lastname');
        $this->db->from(db_prefix() . 'dps_whatsapp_followups f');
        $this->db->join(db_prefix() . 'leads l', 'l.id = f.lead_id', 'left');
        $this->db->join(db_prefix() . 'leads_status ls', 'ls.id = f.lead_status_id', 'left');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = f.staff_id', 'left');
        if ($staff_id) $this->db->where('f.staff_id', (int)$staff_id);
        $this->db->order_by('f.scheduled_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result_array();
    }

    // ─── Estados de leads ─────────────────────────────────────────────────────

    public function get_lead_statuses()
    {
        return $this->db->order_by('name', 'ASC')
            ->get(db_prefix() . 'leads_status')->result_array();
    }
}
