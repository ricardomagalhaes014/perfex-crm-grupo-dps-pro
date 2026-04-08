<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_whatsapp_model extends CI_Model
{
    // Evolution API Configuration
    // Será configurado via variáveis de ambiente ou config do módulo
    private function get_evolution_url()
    {
        // Pode ser configurado no painel do módulo ou via env
        $url = get_option('dps_whatsapp_evolution_url');
        return $url ?: 'https://your-evolution-api.railway.app';
    }

    private function get_evolution_api_key()
    {
        $key = get_option('dps_whatsapp_evolution_api_key');
        return $key ?: '';
    }

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

    // ─── Comunicação com a Evolution API ──────────────────────────────────────

    /**
     * Faz um pedido HTTP à Evolution API
     */
    private function evolution_request($method, $endpoint, $body = [], $instance_name = null)
    {
        $base_url = rtrim($this->get_evolution_url(), '/');
        $api_key  = $this->get_evolution_api_key();
        
        // Se o endpoint não começa com /, adiciona
        if (substr($endpoint, 0, 1) !== '/') {
            $endpoint = '/' . $endpoint;
        }
        
        $url = $base_url . $endpoint;
        $ch  = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $api_key,
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err      = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            return ['error' => $err, 'http_code' => $http_code];
        }
        
        $decoded = json_decode($response, true);
        if ($decoded === null) {
            return ['error' => 'Resposta inválida da API', 'raw' => $response, 'http_code' => $http_code];
        }
        
        return array_merge($decoded, ['http_code' => $http_code]);
    }

    /**
     * Obtém o nome da instância para um staff_id
     * Formato: staff-{id}
     */
    private function get_instance_name($staff_id)
    {
        return 'staff-' . (int)$staff_id;
    }

    /**
     * Cria ou obtém uma instância Evolution para o staff
     * Retorna o QR code se disponível na resposta
     */
    public function wa_connect($staff_id)
    {
        $instance_name = $this->get_instance_name($staff_id);
        
        // Verificar se a instância já existe
        $status = $this->evolution_request('GET', "/instance/connectionState/{$instance_name}");
        
        // Se a instância não existe (404) ou está desconectada, criar/reconectar
        if (!empty($status['error']) || empty($status['instance'])) {
            // Criar nova instância — v1.8.4 retorna o QR code directamente na resposta
            $create_result = $this->evolution_request('POST', '/instance/create', [
                'instanceName' => $instance_name,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS'
            ]);
            
            if (!empty($create_result['error'])) {
                return ['error' => 'Erro ao criar instância: ' . ($create_result['error'] ?? 'Desconhecido')];
            }
            
            // Extrair QR code da resposta de criação (v1.8.4 retorna em qrcode.base64)
            $qr = $this->extract_qr_from_response($create_result);
            
            return [
                'success' => true,
                'instance' => $instance_name,
                'qr' => $qr,
                'message' => 'Instância criada. Aguarde o QR code.'
            ];
        }
        
        // Instância já existe
        $state = $status['instance']['state'] ?? 'close';
        
        if ($state === 'open') {
            return ['success' => true, 'connected' => true, 'instance' => $instance_name];
        }
        
        // Instância existe mas não está ligada — obter QR code
        $connect_result = $this->evolution_request('GET', "/instance/connect/{$instance_name}");
        $qr = $this->extract_qr_from_response($connect_result);
        
        return [
            'success' => true,
            'instance' => $instance_name,
            'qr' => $qr,
            'message' => 'A conectar... Aguarde o QR code.'
        ];
    }

    /**
     * Obtém o estado de ligação do WhatsApp
     */
    public function get_wa_status($staff_id)
    {
        $instance_name = $this->get_instance_name($staff_id);
        $result = $this->evolution_request('GET', "/instance/connectionState/{$instance_name}");
        
        if (!empty($result['error'])) {
            return ['connected' => false, 'error' => $result['error']];
        }
        
        $state = $result['instance']['state'] ?? 'close';
        $phone = $result['instance']['owner'] ?? null;
        
        return [
            'connected' => ($state === 'open'),
            'connecting' => ($state === 'connecting'),
            'phone' => $phone,
            'state' => $state
        ];
    }

    /**
     * Extrai o QR base64 de uma resposta da Evolution API
     */
    private function extract_qr_from_response($result)
    {
        if (empty($result) || !empty($result['error'])) {
            return null;
        }
        // Verificar count:0 (QR ainda não gerado)
        if (isset($result['count']) && $result['count'] === 0 && count($result) === 1) {
            return null;
        }
        if (isset($result['base64']) && is_string($result['base64']) && strlen($result['base64']) > 50) {
            return $result['base64'];
        }
        if (isset($result['qrcode'])) {
            if (is_string($result['qrcode']) && strlen($result['qrcode']) > 50) {
                return $result['qrcode'];
            }
            if (is_array($result['qrcode'])) {
                if (!empty($result['qrcode']['base64']) && strlen($result['qrcode']['base64']) > 50) {
                    return $result['qrcode']['base64'];
                }
                if (!empty($result['qrcode']['code']) && strlen($result['qrcode']['code']) > 10) {
                    return $result['qrcode']['code'];
                }
                if (!empty($result['qrcode']['pairingCode'])) {
                    return $result['qrcode']['pairingCode'];
                }
            }
        }
        if (isset($result['pairingCode']) && !empty($result['pairingCode'])) {
            return $result['pairingCode'];
        }
        return null;
    }

    /**
     * Obtém o QR code para ligar o WhatsApp
     * Retorna imediatamente - o polling é feito pelo JavaScript no frontend
     * Compatível com Evolution API v1.8.4 (HTTP polling)
     */
    public function get_wa_qr($staff_id)
    {
        $instance_name = $this->get_instance_name($staff_id);

        // Verificar se já está conectado
        $state_check = $this->evolution_request('GET', "/instance/connectionState/{$instance_name}");
        if (!empty($state_check['instance']['state']) && $state_check['instance']['state'] === 'open') {
            return ['success' => true, 'connected' => true, 'qr' => null];
        }

        // Se a instância não existe, criar e retornar o QR da resposta de criação
        if (!empty($state_check['error']) || empty($state_check['instance'])) {
            $connect_result = $this->wa_connect($staff_id);
            if (!empty($connect_result['qr'])) {
                return ['success' => true, 'qr' => $connect_result['qr']];
            }
            return ['success' => true, 'qr' => null, 'pending' => true];
        }

        // Instância existe mas não está ligada — obter QR via /instance/connect
        // v1.8.4: GET /instance/connect/{name} retorna {base64, code, count}
        $result = $this->evolution_request('GET', "/instance/connect/{$instance_name}");
        $qr_base64 = $this->extract_qr_from_response($result);

        if ($qr_base64) {
            return ['success' => true, 'qr' => $qr_base64];
        }

        // QR ainda não disponível - o JS vai tentar novamente em 3 segundos
        return ['success' => true, 'qr' => null, 'pending' => true];
    }

    /**
     * Desconecta o WhatsApp
     */
    public function wa_disconnect($staff_id)
    {
        $instance_name = $this->get_instance_name($staff_id);
        
        // Logout da instância
        $result = $this->evolution_request('DELETE', "/instance/logout/{$instance_name}");
        
        // Actualizar config local
        $this->save_config($staff_id, ['is_connected' => 0, 'phone_number' => null]);
        
        if (!empty($result['error'])) {
            return ['error' => $result['error']];
        }
        
        return ['success' => true, 'message' => 'WhatsApp desconectado com sucesso.'];
    }

    /**
     * Envia uma mensagem via WhatsApp
     */
    public function wa_send($staff_id, $to, $message)
    {
        $instance_name = $this->get_instance_name($staff_id);
        
        // Normalizar número de telefone (remover espaços, traços, +, etc.)
        $to = preg_replace('/[^0-9]/', '', $to);
        
        $result = $this->evolution_request('POST', "/message/sendText/{$instance_name}", [
            'number' => $to,
            'textMessage' => ['text' => $message]
        ]);
        
        // Erro de rede/curl (sem http_code)
        if (!empty($result['error']) && empty($result['http_code'])) {
            return ['success' => false, 'error' => $result['error']];
        }
        
        $http_code = (int)($result['http_code'] ?? 0);
        
        // Sucesso: HTTP 2xx (200 ou 201) — a v1.8.4 retorna 201 com key.id
        if ($http_code >= 200 && $http_code < 300) {
            // Verificar se a resposta tem key (mensagem enviada)
            if (!empty($result['key'])) {
                return ['success' => true, 'message' => 'Mensagem enviada'];
            }
            // HTTP 2xx mas sem key — considerar sucesso mesmo assim
            return ['success' => true, 'message' => 'Mensagem enviada'];
        }
        
        // Erro: número não existe no WhatsApp
        if ($http_code === 400 && !empty($result['response']['message'])) {
            $msgs = $result['response']['message'];
            if (is_array($msgs) && isset($msgs[0]) && is_array($msgs[0]) && isset($msgs[0]['exists']) && $msgs[0]['exists'] === false) {
                return ['success' => false, 'error' => 'Número não registado no WhatsApp'];
            }
            $err_text = is_array($msgs) ? json_encode($msgs) : (string)$msgs;
            return ['success' => false, 'error' => 'Erro API: ' . $err_text];
        }
        
        // Outros erros HTTP
        if ($http_code >= 400) {
            $err_msg = '';
            if (!empty($result['response']['message'])) {
                $m = $result['response']['message'];
                $err_msg = is_array($m) ? json_encode($m) : (string)$m;
            } elseif (!empty($result['error'])) {
                $err_msg = (string)$result['error'];
            } else {
                $err_msg = 'Erro HTTP ' . $http_code;
            }
            return ['success' => false, 'error' => $err_msg];
        }
        
        return ['success' => false, 'error' => 'Resposta inesperada da API (HTTP ' . $http_code . ')'];
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

    // ─── Envio imediato de automação a todos os leads do estado ─────────────────

    public function send_automation_to_all_leads($staff_id, $automation)
    {
        // Migração: garantir que a coluna automation_id existe
        $this->db->query("ALTER TABLE `" . db_prefix() . "dps_whatsapp_followups` ADD COLUMN IF NOT EXISTS `automation_id` int(11) DEFAULT NULL");

        $lead_status_id = (int)$automation['lead_status_id'];
        $message_tpl    = $automation['message'];

        // Buscar todos os leads no estado configurado, atribuídos a este staff, com telefone
        $leads = $this->db
            ->where('assigned', (int)$staff_id)
            ->where('status', $lead_status_id)
            ->where('phonenumber !=', '')
            ->get(db_prefix() . 'leads')
            ->result_array();

        if (empty($leads)) {
            return ['success' => false, 'error' => 'Nenhum lead encontrado no estado configurado com número de telefone.'];
        }

        // Verificar se o WhatsApp está ligado antes de enviar
        $wa_status = $this->get_wa_status($staff_id);
        if (empty($wa_status['connected'])) {
            return ['success' => false, 'error' => 'WhatsApp não está ligado. Por favor ligue o WhatsApp primeiro.'];
        }

        $sent    = 0;
        $failed  = 0;
        $skipped = 0;

        foreach ($leads as $lead) {
            $phone = trim($lead['phonenumber'] ?? '');
            if (empty($phone)) { $skipped++; continue; }

            // Substituir variáveis
            $message = str_replace('{{nome}}', $lead['name'] ?? '', $message_tpl);

            $result = $this->wa_send($staff_id, $phone, $message);

            if (!empty($result['success'])) {
                $sent++;
                // Registar como follow-up enviado
                $this->db->insert(db_prefix() . 'dps_whatsapp_followups', [
                    'lead_id'        => (int)$lead['id'],
                    'staff_id'       => (int)$staff_id,
                    'scheduled_at'   => date('Y-m-d H:i:s'),
                    'sent_at'        => date('Y-m-d H:i:s'),
                    'status'         => 'sent',
                    'lead_status_id' => $lead_status_id,
                    'automation_id'  => (int)$automation['id'],
                ]);
            } else {
                $failed++;
            }

            // Pequena pausa para não sobrecarregar o WhatsApp (200ms entre mensagens)
            usleep(200000);
        }

        return [
            'success' => true,
            'sent'    => $sent,
            'failed'  => $failed,
            'skipped' => $skipped,
            'total'   => count($leads),
            'message' => "Enviado para {$sent} lead(s). Falhou: {$failed}. Sem telefone: {$skipped}.",
        ];
    }

    // ─── Estados de leads ─────────────────────────────────────────────────────

    public function get_lead_statuses()
    {
        return $this->db->order_by('name', 'ASC')
            ->get(db_prefix() . 'leads_status')->result_array();
    }
}
