<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Voipstudio_dps extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('voipstudio_dps/voipstudio_dps_model');
    }

    /** Página principal: definições + registo de chamadas */
    public function index()
    {
        if (!is_admin() && !has_permission('voipstudio_dps', '', 'view')) {
            access_denied('voipstudio_dps');
        }
        if ($this->input->post()) {
            if (!is_admin()) {
                access_denied('voipstudio_dps');
            }
            update_option('voipstudio_dps_email', $this->input->post('vs_email', true));
            $pass = $this->input->post('vs_password', false);
            if ($pass !== '' && $pass !== null) {
                update_option('voipstudio_dps_password', $pass);
            }
            update_option('voipstudio_dps_caller_id', preg_replace('/\D+/', '', (string) $this->input->post('vs_caller_id', true)));
            // força novo login na próxima chamada
            update_option('voipstudio_dps_token', '');
            update_option('voipstudio_dps_token_time', '');
            set_alert('success', 'Definições VoIPstudio guardadas.');
            redirect(admin_url('voipstudio_dps'));
        }
        $data['title'] = 'VoIPstudio';
        $data['calls'] = $this->voipstudio_dps_model->get_calls();
        $this->load->view('voipstudio_dps/settings', $data);
    }

    /** AJAX: click-to-call */
    public function call()
    {
        if (!is_staff_logged_in()) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão']);
            return;
        }
        $number   = $this->input->post('number', true);
        $rel_type = $this->input->post('rel_type', true);
        $rel_id   = (int) $this->input->post('rel_id', true);
        $res      = $this->voipstudio_dps_model->click_to_call($number, $rel_type ?: null, $rel_id ?: null, get_staff_user_id());
        echo json_encode($res);
    }

    /**
     * Confirmar o código 2FA recebido por email — completa o login e guarda
     * o token; a partir daí o módulo usa o token até ele morrer (401), sem
     * novos logins nem novos emails.
     */
    public function confirmar_2fa()
    {
        if (!is_admin()) {
            access_denied('voipstudio_dps');
        }
        if (!$this->input->post()) {
            redirect(admin_url('voipstudio_dps'));
        }
        try {
            $this->voipstudio_dps_model->api()->login_com_codigo($this->input->post('vs_2fa_code', true));
            set_alert('success', 'Código aceite — ligação ao VoIPstudio estabelecida. Não vai receber mais emails de código.');
        } catch (Exception $e) {
            set_alert('danger', $e->getMessage());
        }
        redirect(admin_url('voipstudio_dps'));
    }

    /** Testar ligação (login) */
    public function test()
    {
        if (!is_admin()) {
            access_denied('voipstudio_dps');
        }
        try {
            $this->voipstudio_dps_model->api()->token(true);
            set_alert('success', 'Ligação ao VoIPstudio OK — login efetuado com sucesso.');
        } catch (Exception $e) {
            set_alert('danger', 'Falhou: ' . $e->getMessage());
        }
        redirect(admin_url('voipstudio_dps'));
    }

    /** Relatório de chamadas por comercial */
    public function report()
    {
        $is_admin = is_admin();
        // Qualquer staff entra: um comercial sem a permissão explícita via o
        // botão no menu e levava com "access denied"; os dados dele já são
        // filtrados mais abaixo (não-admin só vê as próprias chamadas).
        $from  = $this->input->get('from', true) ?: date('Y-m-01');
        $to    = $this->input->get('to', true) ?: date('Y-m-d');
        $staff = (int) $this->input->get('staff', true);
        // comercial não-admin só vê o próprio
        if (!$is_admin) {
            $staff = get_staff_user_id();
        }
        $data['title']    = 'VoIPstudio — Relatório';
        $data['is_admin'] = $is_admin;
        $data['from']     = $from;
        $data['to']       = $to;
        $data['staff']    = $staff;
        $data['stats']    = $this->voipstudio_dps_model->get_stats($from, $to, $staff ?: null);
        $data['totals']   = $this->voipstudio_dps_model->get_totals($from, $to, $staff ?: null);
        $data['calls']    = $this->voipstudio_dps_model->get_calls(null, null, 500, $staff ?: null, $from, $to);
        $data['staff_members'] = $this->db->select('staffid, firstname, lastname')
            ->where('active', 1)->get(db_prefix() . 'staff')->result();
        $this->load->view('voipstudio_dps/report', $data);
    }

    /** Sincronizar CDRs manualmente */
    public function sync()
    {
        if (!is_admin()) {
            access_denied('voipstudio_dps');
        }
        try {
            $n = $this->voipstudio_dps_model->sync_cdrs();
            set_alert('success', 'Sincronização concluída: ' . $n . ' chamadas novas importadas.');
        } catch (Exception $e) {
            set_alert('danger', 'Sync falhou: ' . $e->getMessage());
        }
        redirect(admin_url('voipstudio_dps'));
    }
}
