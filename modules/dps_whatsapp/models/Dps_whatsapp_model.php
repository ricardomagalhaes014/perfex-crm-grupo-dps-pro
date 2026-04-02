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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
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
     */
    public function wa_connect($staff_id)
    {
        $instance_name = $this->get_instance_name($staff_id);
        
        // Verificar se a instância já existe
        $status = $this->evolution_request('GET', "/instance/connectionState/{$instance_name}");
        
        // Se a instância não existe (404) ou está desconectada, criar/reconectar
        if (!empty($status['error']) || empty($status['instance'])) {
            // Criar nova instância
            $create_result = $this->evolution_request('POST', '/instance/create', [
                'instanceName' => $instance_name,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS'
            ]);
            
            if (!empty($create_result['error'])) {
                return ['error' => 'Erro ao criar instância: ' . ($create_result['error'] ?? 'Desconhecido')];
            }
            
            return ['success' => true, 'instance' => $instance_name, 'message' => 'Instância criada. Aguarde o QR code.'];
        }
        
        // Instância já existe
        $state = $status['instance']['state'] ?? 'close';
        
        if ($state === 'open') {
            return ['success' => true, 'connected' => true, 'instance' => $instance_name];
        }
        
        // Conectar instância existente
        $connect_result = $this->evolution_request('GET', "/instance/connect/{$instance_name}");
        
        return [
            'success' => true,
            'instance' => $instance_name,
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
     * Obtém o QR code para ligar o WhatsApp
     */
    public function get_wa_qr($staff_id)
    {
        $instance_name = $this->get_instance_name($staff_id);
        
        // Primeiro conectar (se não estiver)
        $this->wa_connect($staff_id);
        
        // Aguardar 2 segundos para o QR ser gerado
        sleep(2);
        
        // Obter o QR code (a API devolve objecto com informações do QR)
        $result = $this->evolution_request('GET', "/instance/connect/{$instance_name}");

        // Se houve erro na chamada, devolver o erro imediatamente
        if (!empty($result['error'])) {
            return ['error' => $result['error']];
        }

        $qr_base64 = null;

        /*
         * A partir da versão 2.x da Evolution API, o endpoint /instance/connect
         * retorna um objecto "qrcode" com várias propriedades (ex: base64, code,
         * pairingCode, count). Para manter compatibilidade com versões mais
         * antigas que devolviam directamente uma string em "qrcode" ou
         * "base64", verificamos todas as possibilidades e extraímos o valor
         * correcto.
         */
        if (isset($result['base64']) && is_string($result['base64'])) {
            // Versões antigas devolviam o base64 directamente neste campo
            $qr_base64 = $result['base64'];
        } elseif (isset($result['qrcode'])) {
            // O campo qrcode pode ser uma string ou um array
            if (is_string($result['qrcode'])) {
                $qr_base64 = $result['qrcode'];
            } elseif (is_array($result['qrcode'])) {
                // Nova estrutura: procurar pelo campo base64 dentro do objecto
                if (!empty($result['qrcode']['base64'])) {
                    // Preferir a imagem base64, se estiver disponível
                    $qr_base64 = $result['qrcode']['base64'];
                } elseif (!empty($result['qrcode']['code'])) {
                    /*
                     * Alguns provedores devolvem o código no campo "code". Este valor pode ser uma imagem
                     * em base64 (com prefixo data:image/...)
                     * ou um código de emparelhamento (ex: "2@abc123...").
                     * Neste último caso devemos devolver o código mesmo sem o prefixo para que o frontend
                     * mostre como pairing code. O JavaScript decide se desenha a imagem ou apresenta
                     * o texto consoante o prefixo.
                     */
                    $qr_base64 = $result['qrcode']['code'];
                } elseif (!empty($result['qrcode']['pairingCode'])) {
                    // Alguns retornam explicitamente "pairingCode"
                    $qr_base64 = $result['qrcode']['pairingCode'];
                }
            }
        } elseif (isset($result['pairingCode'])) {
            // Para as versões mais recentes, pode ser devolvido apenas o código de emparelhamento
            $qr_base64 = $result['pairingCode'];
        }

        // Se ainda não temos base64, tentar o endpoint /instance/qrcode/{instance}
        if (!$qr_base64) {
            $qr_result = $this->evolution_request('GET', "/instance/qrcode/{$instance_name}");
            if (!empty($qr_result['base64']) && is_string($qr_result['base64'])) {
                // Versões antigas podem devolver directamente o base64 neste campo
                $qr_base64 = $qr_result['base64'];
            } elseif (!empty($qr_result['qrcode'])) {
                if (is_string($qr_result['qrcode'])) {
                    // O campo "qrcode" sozinho pode ser uma imagem ou um código
                    $qr_base64 = $qr_result['qrcode'];
                } elseif (is_array($qr_result['qrcode'])) {
                    // Nova estrutura: extrair base64, code ou pairingCode
                    if (!empty($qr_result['qrcode']['base64'])) {
                        $qr_base64 = $qr_result['qrcode']['base64'];
                    } elseif (!empty($qr_result['qrcode']['code'])) {
                        $qr_base64 = $qr_result['qrcode']['code'];
                    } elseif (!empty($qr_result['qrcode']['pairingCode'])) {
                        $qr_base64 = $qr_result['qrcode']['pairingCode'];
                    }
                }
            }
        }

        // Se ainda não conseguimos obter o QR, a instância poderá já estar ligada ou ocorreu erro
        if (!$qr_base64) {
            return ['error' => 'QR code não disponível. A instância pode já estar conectada ou a API não retornou um código.'];
        }

        return [
            'success' => true,
            'qr' => $qr_base64
        ];
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
        
        // Normalizar número de telefone (remover espaços, traços, etc.)
        $to = preg_replace('/[^0-9]/', '', $to);
        
        // Adicionar @s.whatsapp.net se não tiver
        if (strpos($to, '@') === false) {
            $to = $to . '@s.whatsapp.net';
        }
        
        $result = $this->evolution_request('POST', "/message/sendText/{$instance_name}", [
            'number' => $to,
            'text' => $message
        ]);
        
        if (!empty($result['error'])) {
            return ['success' => false, 'error' => $result['error']];
        }
        
        // Evolution API retorna diferentes formatos de sucesso
        if (!empty($result['key']) || !empty($result['message'])) {
            return ['success' => true, 'message' => 'Mensagem enviada'];
        }
        
        return ['success' => false, 'error' => 'Resposta inesperada da API'];
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

            // Pequena pausa para não sobrecarregar o WhatsApp (500ms entre mensagens)
            usleep(500000);
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
