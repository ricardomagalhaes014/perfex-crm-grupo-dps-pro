<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_whatsapp_model extends CI_Model
{
    // IDs dos estados que activam follow-up
    // 4=Novos, 1=QUENTE, 14=VIP
    const FOLLOWUP_STATUS_IDS = [1, 4, 14];

    // URL do microserviço Node.js
    const WA_SERVICE_URL = 'http://127.0.0.1:3001';
    const WA_API_KEY     = 'dps-wa-secret-2026';

    // Mensagens de follow-up por estado
    const MESSAGES = [
        4  => "Olá. Espero que esteja tudo bem.\n\nEstou a entrar em contacto porque há uns dias demonstrou interesse numa oportunidade imobiliária connosco e queria perceber se ainda faz sentido para si receber opções ajustadas ao que procura.\n\nTemos soluções muito interessantes para habitação própria e investimento, com especial destaque para oportunidades em Portugal, Brasil e Dubai.\n\nSe ainda estiver à procura, diga-me apenas:\nzona, tipologia e valor de investimento.\n\nAssim envio-lhe algo realmente alinhado consigo.\nDPS Imobiliário",
        1  => "Olá,\n\nEstou a voltar ao seu contacto porque, pelo seu interesse, não queria que perdesse boas oportunidades que possam encaixar no seu perfil.\n\nNeste momento temos algumas opções com condições muito competitivas, excelente potencial de valorização e, em certos casos, pagamento faseado ao longo da obra.\n\nComo o mercado está dinâmico e a disponibilidade muda rapidamente, diga-me se quer que lhe envie já as melhores opções disponíveis para si.\n\nFico desse lado.\nDPS Imobiliário",
        14 => "Olá,\n\nEspero que esteja bem.\n\nEstou a falar consigo novamente porque, atendendo ao seu perfil, acredito que faz sentido apresentar-lhe oportunidades realmente diferenciadas que estão neste momento no mercado.\n\nTemos ativos com localizações premium, condições muito atrativas para investimento e forte potencial de valorização, incluindo opções com estrutura de pagamento bastante inteligente.\n\nSe entender, envio-lhe hoje uma seleção mais exclusiva e alinhada com o que pretende.\n\nFico ao seu dispor.\nDPS Imobiliário",
    ];

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

    // ─── Agendamento de follow-ups ────────────────────────────────────────────

    /**
     * Quando uma lead é criada, agendar follow-up para 7 dias depois
     * se o estado for Nova/Quente/VIP
     */
    public function schedule_followup($lead_id)
    {
        $lead = $this->db->where('id', (int)$lead_id)->get(db_prefix() . 'leads')->row_array();
        if (!$lead) return;

        // Só agendar se o estado for um dos estados de follow-up
        if (!in_array((int)$lead['status'], self::FOLLOWUP_STATUS_IDS)) return;

        // Cancelar follow-ups anteriores pendentes para esta lead
        $this->db->where('lead_id', (int)$lead_id)
            ->where('status', 'pending')
            ->update(db_prefix() . 'dps_whatsapp_followups', ['status' => 'cancelled']);

        // Agendar novo follow-up para 7 dias após a criação
        $scheduled_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        $this->db->insert(db_prefix() . 'dps_whatsapp_followups', [
            'lead_id'        => (int)$lead_id,
            'staff_id'       => (int)$lead['assigned'],
            'scheduled_at'   => $scheduled_at,
            'status'         => 'pending',
            'lead_status_id' => (int)$lead['status'],
        ]);
    }

    /**
     * Quando o estado de uma lead muda:
     * - Se for para Nova/Quente/VIP → agendar/reagendar follow-up de 7 dias
     * - Se for para outro estado → cancelar follow-up pendente
     */
    public function reschedule_followup($lead_id)
    {
        $lead = $this->db->where('id', (int)$lead_id)->get(db_prefix() . 'leads')->row_array();
        if (!$lead) return;

        // Cancelar follow-ups anteriores pendentes
        $this->db->where('lead_id', (int)$lead_id)
            ->where('status', 'pending')
            ->update(db_prefix() . 'dps_whatsapp_followups', ['status' => 'cancelled']);

        // Se o novo estado não é de follow-up, não agendar
        if (!in_array((int)$lead['status'], self::FOLLOWUP_STATUS_IDS)) return;

        // Agendar novo follow-up para 7 dias a partir de agora
        $scheduled_at = date('Y-m-d H:i:s', strtotime('+7 days'));
        $this->db->insert(db_prefix() . 'dps_whatsapp_followups', [
            'lead_id'        => (int)$lead_id,
            'staff_id'       => (int)$lead['assigned'],
            'scheduled_at'   => $scheduled_at,
            'status'         => 'pending',
            'lead_status_id' => (int)$lead['status'],
        ]);
    }

    /**
     * Processar follow-ups pendentes (chamado pelo cron do Perfex)
     * Verifica se a lead ainda está no estado correcto e envia a mensagem
     */
    public function process_pending_followups()
    {
        $now = date('Y-m-d H:i:s');

        // Obter follow-ups pendentes cuja hora já passou
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
        $lead_id  = (int)$followup['lead_id'];
        $staff_id = (int)$followup['staff_id'];

        // Verificar estado actual da lead
        $lead = $this->db->where('id', $lead_id)->get(db_prefix() . 'leads')->row_array();
        if (!$lead) {
            $this->mark_followup($followup['id'], 'cancelled');
            return;
        }

        $current_status = (int)$lead['status'];

        // Se o estado já não é Nova/Quente/VIP → cancelar
        if (!in_array($current_status, self::FOLLOWUP_STATUS_IDS)) {
            $this->mark_followup($followup['id'], 'cancelled');
            return;
        }

        // Verificar se o staff tem WhatsApp ligado
        $config = $this->get_config($staff_id);
        if (!$config || !$config['is_connected']) {
            // Verificar estado real no microserviço
            $status = $this->get_wa_status($staff_id);
            if (empty($status['connected'])) {
                $this->mark_followup($followup['id'], 'failed');
                return;
            }
            // Actualizar config
            $this->save_config($staff_id, ['is_connected' => 1]);
        }

        // Obter número de telefone da lead
        $phone = $lead['phonenumber'] ?? '';
        if (empty($phone)) {
            $this->mark_followup($followup['id'], 'failed');
            return;
        }

        // Obter mensagem para o estado actual
        $message = self::MESSAGES[$current_status] ?? null;
        if (!$message) {
            $this->mark_followup($followup['id'], 'cancelled');
            return;
        }

        // Enviar mensagem
        $result = $this->wa_send($staff_id, $phone, $message);

        if (!empty($result['success'])) {
            $this->mark_followup($followup['id'], 'sent');
        } else {
            $this->mark_followup($followup['id'], 'failed');
        }
    }

    private function mark_followup($id, $status)
    {
        $data = ['status' => $status];
        if ($status === 'sent') $data['sent_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', (int)$id)->update(db_prefix() . 'dps_whatsapp_followups', $data);
    }

    // ─── Listagem de follow-ups ───────────────────────────────────────────────

    public function get_followups($staff_id = null, $limit = 50)
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
}
