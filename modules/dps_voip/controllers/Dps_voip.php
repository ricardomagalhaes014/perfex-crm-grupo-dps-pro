<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_voip extends AdminController
{
    private $account_sid;
    private $auth_token;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_voip/Dps_voip_model');
        $this->account_sid = get_option('dps_voip_twilio_account_sid');
        $this->auth_token  = get_option('dps_voip_twilio_auth_token');
    }

    // ===================== PAINEL PRINCIPAL =====================

    public function index()
    {
        $staff_id = get_staff_user_id();
        $data['title']        = 'VoIP Central';
        $data['numbers']      = $this->Dps_voip_model->get_all_numbers();
        $data['staff_list']   = $this->Dps_voip_model->get_staff_list();
        $data['calls']        = $this->Dps_voip_model->get_calls(100);
        $data['stats']        = $this->Dps_voip_model->get_stats();
        $data['my_number']    = $this->Dps_voip_model->get_staff_number($staff_id);
        $data['is_admin']     = is_admin();
        $this->load->view('dps_voip/dps_voip/index', $data);
    }

    // ===================== GESTÃO DE NÚMEROS =====================

    public function add_number()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão']); exit;
        }

        $number   = trim($this->input->post('twilio_number'));
        $name     = trim($this->input->post('friendly_name'));
        $staff_id = (int)$this->input->post('staff_id');

        if (empty($number)) {
            echo json_encode(['success' => false, 'message' => 'Número obrigatório']); exit;
        }

        // Normalizar para E.164
        if (substr($number, 0, 1) !== '+') $number = '+' . $number;

        // Verificar se já existe
        $existing = $this->db->get_where(db_prefix() . 'dps_voip_numbers', ['twilio_number' => $number])->row_array();
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Número já registado']); exit;
        }

        $id = $this->Dps_voip_model->add_number([
            'twilio_number' => $number,
            'friendly_name' => $name,
            'staff_id'      => $staff_id ?: null,
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Número adicionado com sucesso']);
        exit;
    }

    public function update_number()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!is_admin()) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão']); exit;
        }

        $id       = (int)$this->input->post('id');
        $name     = trim($this->input->post('friendly_name'));
        $staff_id = $this->input->post('staff_id');
        $active   = (int)$this->input->post('is_active');

        $ok = $this->Dps_voip_model->update_number($id, [
            'friendly_name' => $name,
            'staff_id'      => $staff_id !== '' ? (int)$staff_id : null,
            'is_active'     => $active,
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Atualizado' : 'Erro ao atualizar']);
        exit;
    }

    public function delete_number($id)
    {
        if (!is_admin()) show_404();
        $this->Dps_voip_model->delete_number((int)$id);
        set_alert('success', 'Número removido');
        redirect(admin_url('dps_voip'));
    }

    // ===================== TOKEN TWILIO PARA BROWSER SDK =====================

    public function get_token()
    {
        header('Content-Type: application/json');
        $staff_id = get_staff_user_id();
        if (!$staff_id) {
            echo json_encode(['success' => false, 'message' => 'Não autenticado']); exit;
        }

        $app_sid = get_option('dps_voip_twilio_app_sid');
        if (empty($app_sid)) {
            echo json_encode(['success' => false, 'message' => 'TwiML App SID não configurado. Configure nas definições do módulo.']); exit;
        }

        // Gerar Access Token via API Twilio (sem SDK PHP - usa JWT manual)
        $token = $this->_generate_twilio_token($staff_id, $app_sid);
        if (!$token) {
            echo json_encode(['success' => false, 'message' => 'Erro ao gerar token']); exit;
        }

        echo json_encode(['success' => true, 'token' => $token]);
        exit;
    }

    private function _generate_twilio_token($staff_id, $app_sid)
    {
        // Gerar JWT para Twilio Client SDK
        $now     = time();
        $exp     = $now + 3600; // 1 hora
        $jti     = $this->account_sid . '-' . $now;
        $identity = 'staff_' . $staff_id;

        // Grant para Voice
        $grant = [
            'voice' => [
                'incoming' => ['allow' => true],
                'outgoing' => ['application_sid' => $app_sid],
            ]
        ];

        $header  = base64_encode(json_encode(['cty' => 'twilio-fpa;v=1', 'typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'jti'      => $jti,
            'iss'      => $this->account_sid,
            'sub'      => $this->account_sid,
            'nbf'      => $now,
            'exp'      => $exp,
            'grants'   => $grant,
            'identity' => $identity,
        ]));

        // Remover padding base64
        $header  = rtrim(strtr($header, '+/', '-_'), '=');
        $payload = rtrim(strtr($payload, '+/', '-_'), '=');

        $sig_input = $header . '.' . $payload;
        $sig = hash_hmac('sha256', $sig_input, $this->auth_token, true);
        $sig = rtrim(strtr(base64_encode($sig), '+/', '-_'), '=');

        return $sig_input . '.' . $sig;
    }

    // ===================== WEBHOOK TWILIO - CHAMADA DE SAÍDA =====================

    public function twiml_outbound()
    {
        // Endpoint TwiML para chamadas de saída via browser SDK
        // Twilio chama este endpoint quando o browser inicia uma chamada
        header('Content-Type: text/xml');

        $to       = $this->input->post('To') ?: $this->input->get('To');
        $from     = $this->input->post('From') ?: $this->input->get('From');
        $staff_id = $this->input->post('staff_id') ?: $this->input->get('staff_id');

        if (empty($to)) {
            echo '<?xml version="1.0" encoding="UTF-8"?><Response><Say language="pt-BR">Número de destino não especificado.</Say></Response>';
            exit;
        }

        // Registar chamada
        $call_sid = $this->input->post('CallSid') ?: $this->input->get('CallSid');
        if ($call_sid) {
            $this->Dps_voip_model->log_call([
                'call_sid'    => $call_sid,
                'direction'   => 'outbound',
                'from_number' => $from,
                'to_number'   => $to,
                'staff_id'    => $staff_id ? (int)$staff_id : null,
                'status'      => 'initiated',
            ]);
        }

        $record = get_option('dps_voip_record_calls') == '1';
        $timeout = (int)(get_option('dps_voip_default_timeout') ?: 30);

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Response>';
        if ($record) {
            echo '<Dial callerId="' . htmlspecialchars($from) . '" timeout="' . $timeout . '" record="record-from-ringing">';
        } else {
            echo '<Dial callerId="' . htmlspecialchars($from) . '" timeout="' . $timeout . '">';
        }
        echo '<Number>' . htmlspecialchars($to) . '</Number>';
        echo '</Dial>';
        echo '</Response>';
        exit;
    }

    // ===================== WEBHOOK TWILIO - CHAMADA DE ENTRADA =====================

    public function twiml_inbound()
    {
        // Endpoint TwiML para chamadas de entrada
        header('Content-Type: text/xml');

        $from     = $this->input->post('From') ?: $this->input->get('From');
        $to       = $this->input->post('To') ?: $this->input->get('To');
        $call_sid = $this->input->post('CallSid') ?: $this->input->get('CallSid');

        // Encontrar o comercial atribuído a este número
        $number_row = $this->db->get_where(db_prefix() . 'dps_voip_numbers', ['twilio_number' => $to, 'is_active' => 1])->row_array();
        $staff_id   = $number_row['staff_id'] ?? null;

        // Registar chamada
        if ($call_sid) {
            $this->Dps_voip_model->log_call([
                'call_sid'    => $call_sid,
                'direction'   => 'inbound',
                'from_number' => $from,
                'to_number'   => $to,
                'staff_id'    => $staff_id,
                'status'      => 'ringing',
            ]);
        }

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<Response>';

        if ($staff_id) {
            // Encaminhar para o browser do comercial (Twilio Client)
            $identity = 'staff_' . $staff_id;
            echo '<Dial timeout="30">';
            echo '<Client>' . htmlspecialchars($identity) . '</Client>';
            echo '</Dial>';
        } else {
            // Sem comercial atribuído - mensagem de espera
            echo '<Say language="pt-BR">Obrigado por contactar o Grupo DPS. Não foi possível encaminhar a sua chamada. Por favor tente mais tarde.</Say>';
        }

        echo '</Response>';
        exit;
    }

    // ===================== WEBHOOK STATUS DE CHAMADA =====================

    public function call_status()
    {
        $call_sid     = $this->input->post('CallSid');
        $status       = $this->input->post('CallStatus');
        $duration     = $this->input->post('CallDuration');
        $recording    = $this->input->post('RecordingUrl');

        if ($call_sid && $status) {
            $this->Dps_voip_model->update_call_status($call_sid, $status, $duration, $recording);
        }

        http_response_code(204);
        exit;
    }

    // ===================== INICIAR CHAMADA DE SAÍDA (REST API) =====================

    public function make_call()
    {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');

        $staff_id = get_staff_user_id();
        $to       = trim($this->input->post('to'));
        $lead_id  = (int)$this->input->post('lead_id');

        if (empty($to)) {
            echo json_encode(['success' => false, 'message' => 'Número de destino obrigatório']); exit;
        }

        // Normalizar número
        if (substr($to, 0, 1) !== '+') $to = '+' . $to;

        // Obter número do comercial
        $staff_number = $this->Dps_voip_model->get_staff_number($staff_id);
        if (!$staff_number) {
            echo json_encode(['success' => false, 'message' => 'Não tem um número Twilio atribuído. Contacte o administrador.']); exit;
        }

        $from       = $staff_number['twilio_number'];
        $status_url = site_url('admin/dps_voip/call_status');
        $twiml_url  = site_url('admin/dps_voip/twiml_outbound') . '?staff_id=' . $staff_id;

        // Fazer chamada via Twilio REST API
        $result = $this->_twilio_make_call($from, $to, $twiml_url, $status_url);

        if ($result['success']) {
            $this->Dps_voip_model->log_call([
                'call_sid'    => $result['call_sid'],
                'direction'   => 'outbound',
                'from_number' => $from,
                'to_number'   => $to,
                'staff_id'    => $staff_id,
                'lead_id'     => $lead_id ?: null,
                'status'      => 'initiated',
            ]);
            echo json_encode(['success' => true, 'call_sid' => $result['call_sid'], 'message' => 'Chamada iniciada']);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
        exit;
    }

    private function _twilio_make_call($from, $to, $url, $status_callback)
    {
        $endpoint = 'https://api.twilio.com/2010-04-01/Accounts/' . $this->account_sid . '/Calls.json';
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $this->account_sid . ':' . $this->auth_token,
            CURLOPT_POSTFIELDS     => http_build_query([
                'From'                  => $from,
                'To'                    => $to,
                'Url'                   => $url,
                'StatusCallback'        => $status_callback,
                'StatusCallbackMethod'  => 'POST',
                'Timeout'               => (int)(get_option('dps_voip_default_timeout') ?: 30),
            ]),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $http     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);
        if ($http === 201 && !empty($data['sid'])) {
            return ['success' => true, 'call_sid' => $data['sid']];
        }
        $msg = $data['message'] ?? 'Erro Twilio HTTP ' . $http;
        return ['success' => false, 'message' => $msg];
    }

    // ===================== CONFIGURAÇÕES =====================

    public function settings()
    {
        if (!is_admin()) show_404();

        if ($this->input->post()) {
            update_option('dps_voip_twilio_account_sid', $this->input->post('account_sid'));
            update_option('dps_voip_twilio_auth_token',  $this->input->post('auth_token'));
            update_option('dps_voip_twilio_app_sid',     $this->input->post('app_sid'));
            update_option('dps_voip_record_calls',       $this->input->post('record_calls') ? '1' : '0');
            update_option('dps_voip_default_timeout',    (int)$this->input->post('timeout'));
            set_alert('success', 'Configurações guardadas');
            redirect(admin_url('dps_voip/settings'));
        }

        $data['title']       = 'VoIP - Configurações';
        $data['account_sid'] = get_option('dps_voip_twilio_account_sid');
        $data['auth_token']  = get_option('dps_voip_twilio_auth_token');
        $data['app_sid']     = get_option('dps_voip_twilio_app_sid');
        $data['record']      = get_option('dps_voip_record_calls');
        $data['timeout']     = get_option('dps_voip_default_timeout') ?: 30;
        $this->load->view('dps_voip/dps_voip/settings', $data);
    }

    // ===================== AJAX - HISTÓRICO =====================

    public function get_calls_ajax()
    {
        if (!$this->input->is_ajax_request()) show_404();
        header('Content-Type: application/json');
        $filters = [
            'staff_id'  => (int)$this->input->get('staff_id') ?: null,
            'direction' => $this->input->get('direction'),
            'date_from' => $this->input->get('date_from'),
            'date_to'   => $this->input->get('date_to'),
        ];
        $calls = $this->Dps_voip_model->get_calls(200, array_filter($filters));
        echo json_encode(['success' => true, 'calls' => $calls]);
        exit;
    }
}
