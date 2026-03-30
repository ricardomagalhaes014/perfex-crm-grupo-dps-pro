<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_whatsapp extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_whatsapp/Dps_whatsapp_model');
    }

    // Página principal — painel WhatsApp do utilizador
    public function index()
    {
        $staff_id = get_staff_user_id();
        $data['title']    = 'WhatsApp';
        $data['staff_id'] = $staff_id;
        $data['config']   = $this->Dps_whatsapp_model->get_config($staff_id);
        $data['followups'] = $this->Dps_whatsapp_model->get_followups(
            is_admin() ? null : $staff_id,
            100
        );
        $this->load->view('dps_whatsapp/whatsapp/index', $data);
    }

    // AJAX: obter estado da ligação
    public function ajax_status()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $status   = $this->Dps_whatsapp_model->get_wa_status($staff_id);

        // Actualizar BD se mudou
        if (!empty($status['connected'])) {
            $this->Dps_whatsapp_model->save_config($staff_id, [
                'is_connected' => 1,
                'phone_number' => $status['phone'] ?? null,
            ]);
        } else {
            $this->Dps_whatsapp_model->save_config($staff_id, ['is_connected' => 0]);
        }

        echo json_encode($status);
        exit;
    }

    // AJAX: iniciar ligação (gera QR)
    public function ajax_connect()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $result   = $this->Dps_whatsapp_model->wa_connect($staff_id);
        echo json_encode($result);
        exit;
    }

    // AJAX: obter QR code
    public function ajax_qr()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $result   = $this->Dps_whatsapp_model->get_wa_qr($staff_id);
        echo json_encode($result);
        exit;
    }

    // AJAX: desligar
    public function ajax_disconnect()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id = get_staff_user_id();
        $result   = $this->Dps_whatsapp_model->wa_disconnect($staff_id);
        echo json_encode($result);
        exit;
    }

    // AJAX: processar follow-ups manualmente (admin)
    public function ajax_process_followups()
    {
        if (!$this->input->is_ajax_request() || !is_admin()) show_404();
        $this->Dps_whatsapp_model->process_pending_followups();
        echo json_encode(['success' => true, 'message' => 'Follow-ups processados']);
        exit;
    }
}
