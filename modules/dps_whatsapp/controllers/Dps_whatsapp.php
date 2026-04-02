<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_whatsapp extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_whatsapp/Dps_whatsapp_model');
    }

    // Página principal
    public function index()
    {
        $staff_id = get_staff_user_id();
        $data['title']         = 'WhatsApp';
        $data['staff_id']      = $staff_id;
        $data['config']        = $this->Dps_whatsapp_model->get_config($staff_id);
        $data['lead_statuses'] = $this->Dps_whatsapp_model->get_lead_statuses();
        $data['automations']   = $this->Dps_whatsapp_model->get_automations($staff_id);
        $data['followups']     = $this->Dps_whatsapp_model->get_followups(
            is_admin() ? null : $staff_id,
            100
        );
        $this->load->view('dps_whatsapp/whatsapp/index', $data);
    }

    // AJAX: estado da ligação
    public function ajax_status()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $status   = $this->Dps_whatsapp_model->get_wa_status($staff_id);
        if (!empty($status['connected'])) {
            $this->Dps_whatsapp_model->save_config($staff_id, [
                'is_connected' => 1,
                'phone_number' => $status['phone'] ?? null,
            ]);
        } else {
            $this->Dps_whatsapp_model->save_config($staff_id, ['is_connected' => 0]);
        }
        header('Content-Type: application/json');
        echo json_encode($status);
        exit;
    }

    // AJAX: iniciar ligação
    public function ajax_connect()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $result   = $this->Dps_whatsapp_model->wa_connect($staff_id);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // AJAX: obter QR
    public function ajax_qr()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $result   = $this->Dps_whatsapp_model->get_wa_qr($staff_id);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // AJAX: desligar
    public function ajax_disconnect()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $result   = $this->Dps_whatsapp_model->wa_disconnect($staff_id);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // AJAX: processar follow-ups (admin)
    public function ajax_process_followups()
    {
        if (!$this->input->is_ajax_request() || !is_admin()) show_404();
        $this->Dps_whatsapp_model->process_pending_followups();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Follow-ups processados com sucesso.']);
        exit;
    }

    // AJAX: guardar automação (criar ou editar)
    public function ajax_save_automation()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();

        $id      = (int)$this->input->post('id');
        $name    = trim($this->input->post('name'));
        $status  = (int)$this->input->post('lead_status_id');
        $days    = max(1, (int)$this->input->post('days_delay'));
        $message = trim($this->input->post('message'));

        if (!$name || !$message || !$status) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Campos obrigatórios em falta.']);
            exit;
        }

        $data = [
            'staff_id'       => $staff_id,
            'name'           => $name,
            'lead_status_id' => $status,
            'days_delay'     => $days,
            'message'        => $message,
        ];

        // Se editar, verificar que pertence ao staff
        if ($id) {
            $existing = $this->Dps_whatsapp_model->get_automation($id, $staff_id);
            if (!$existing) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Automação não encontrada.']);
                exit;
            }
        }

        $saved_id = $this->Dps_whatsapp_model->save_automation($data, $id ?: null);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $saved_id]);
        exit;
    }

    // AJAX: apagar automação
    public function ajax_delete_automation()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $id       = (int)$this->input->post('id');
        $result   = $this->Dps_whatsapp_model->delete_automation($id, $staff_id);
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
        exit;
    }

    // AJAX: activar/desactivar automação
    public function ajax_toggle_automation()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id  = get_staff_user_id();
        $id        = (int)$this->input->post('id');
        $is_active = (int)$this->input->post('is_active');
        $this->Dps_whatsapp_model->toggle_automation($id, $staff_id, $is_active);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    // AJAX: enviar automação imediatamente a todos os leads do estado configurado
    public function ajax_send_automation_now()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id     = get_staff_user_id();
        $automation_id = (int)$this->input->post('automation_id');

        if (!$automation_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID da automação inválido.']);
            exit;
        }

        // Verificar que a automação pertence ao staff
        $automation = $this->Dps_whatsapp_model->get_automation($automation_id, $staff_id);
        if (!$automation) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Automação não encontrada.']);
            exit;
        }

        // Verificar se o WhatsApp está ligado
        $status = $this->Dps_whatsapp_model->get_wa_status($staff_id);
        if (empty($status['connected'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'WhatsApp não está ligado. Ligue primeiro.']);
            exit;
        }

        $result = $this->Dps_whatsapp_model->send_automation_to_all_leads($staff_id, $automation);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // AJAX: guardar configuração da Evolution API
    public function ajax_save_evolution_config()
    {
        if (!$this->input->is_ajax_request() || !is_admin()) show_404();

        $url     = trim($this->input->post('evolution_url'));
        $api_key = trim($this->input->post('evolution_api_key'));

        if (!$url || !$api_key) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'URL e API Key são obrigatórios.']);
            exit;
        }

        update_option('dps_whatsapp_evolution_url', $url);
        update_option('dps_whatsapp_evolution_api_key', $api_key);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Configuração guardada com sucesso.']);
        exit;
    }

    // AJAX: envio manual de mensagem
    public function ajax_send_message()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();

        $phone   = trim($this->input->post('phone'));
        $message = trim($this->input->post('message'));

        if (!$phone || !$message) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Número e mensagem são obrigatórios.']);
            exit;
        }

        // Verificar se o WhatsApp está ligado
        $status = $this->Dps_whatsapp_model->get_wa_status($staff_id);
        if (empty($status['connected'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'WhatsApp não está ligado. Ligue primeiro.']);
            exit;
        }

        $result = $this->Dps_whatsapp_model->wa_send($staff_id, $phone, $message);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
}
