<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ligar e desligar a conta Google de cada comercial.
 */
class Dps_google extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_staff_logged_in()) {
            access_denied('dps_google');
        }
    }

    public function index()
    {
        $data['conta']         = dps_google_conta(get_staff_user_id());
        $data['configurado']   = get_option('dps_google_client_id') !== ''
                                 && get_option('dps_google_client_secret') !== '';
        $data['redirect_uri']  = dps_google_redirect_uri();
        $data['title']         = 'Google Calendar';

        // O admin vê quem já ligou; um comercial vê só a sua.
        $data['todas'] = is_admin()
            ? $this->db->select('g.*, CONCAT(s.firstname," ",s.lastname) AS nome')
                       ->from(db_prefix() . 'dps_google_contas g')
                       ->join(db_prefix() . 'staff s', 's.staffid = g.staff_id', 'left')
                       ->get()->result_array()
            : [];

        $this->load->view('definicoes', $data);
    }

    /** Guarda as credenciais da aplicação. Só admin. */
    public function guardar_credenciais()
    {
        if (!is_admin() || $this->input->method() !== 'post') {
            show_404();
        }

        update_option('dps_google_client_id', trim((string) $this->input->post('client_id')));
        update_option('dps_google_client_secret', trim((string) $this->input->post('client_secret')));

        set_alert('success', 'Credenciais guardadas. Já pode ligar a sua conta.');
        redirect(admin_url('dps_google'));
    }

    /** Manda a pessoa ao Google para autorizar. */
    public function ligar()
    {
        $client_id = get_option('dps_google_client_id');
        if ($client_id === '') {
            set_alert('warning', 'Faltam as credenciais da aplicação. Fale com a direção.');
            redirect(admin_url('dps_google'));
        }

        /*
         * access_type=offline + prompt=consent são obrigatórios os DOIS.
         * Sem offline não vem refresh_token; sem consent o Google só o manda
         * na primeira autorização de sempre — e quem voltasse a ligar ficava
         * sem ele, com a ligação a morrer uma hora depois.
         *
         * O state leva o id de quem pediu, para o retorno saber a quem
         * pertence a conta.
         */
        $params = [
            'client_id'     => $client_id,
            'redirect_uri'  => dps_google_redirect_uri(),
            'response_type' => 'code',
            'scope'         => DPS_GOOGLE_SCOPE,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => (int) get_staff_user_id(),
        ];

        redirect(DPS_GOOGLE_AUTH_URL . '?' . http_build_query($params));
    }

    /** O Google devolve aqui. */
    public function callback()
    {
        $codigo   = $this->input->get('code');
        $staff_id = (int) $this->input->get('state');

        if ($this->input->get('error')) {
            set_alert('warning', 'Autorização cancelada: ' . $this->input->get('error'));
            redirect(admin_url('dps_google'));
        }

        // O state tem de bater com quem está autenticado. Sem isto, um link
        // preparado por outra pessoa podia ligar a conta dela à ficha nossa.
        if (!$codigo || $staff_id !== (int) get_staff_user_id()) {
            set_alert('danger', 'Pedido inválido.');
            redirect(admin_url('dps_google'));
        }

        $r = dps_google_post(DPS_GOOGLE_TOKEN_URL, [
            'code'          => $codigo,
            'client_id'     => get_option('dps_google_client_id'),
            'client_secret' => get_option('dps_google_client_secret'),
            'redirect_uri'  => dps_google_redirect_uri(),
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($r['access_token'])) {
            set_alert('danger', 'O Google recusou: '
                . ($r['error_description'] ?? $r['error'] ?? 'sem detalhe'));
            redirect(admin_url('dps_google'));
        }

        // O email vem dentro do id_token, que é um JWT. Só se lê a parte do
        // meio — não se valida assinatura porque o token veio directamente do
        // Google por HTTPS, não do browser.
        $email = '';
        if (!empty($r['id_token'])) {
            $partes = explode('.', $r['id_token']);
            if (isset($partes[1])) {
                $carga = json_decode(base64_decode(strtr($partes[1], '-_', '+/')), true);
                $email = $carga['email'] ?? '';
            }
        }

        $linha = [
            'staff_id'     => $staff_id,
            'email'        => $email,
            'access_token' => $r['access_token'],
            'expires_at'   => date('Y-m-d H:i:s', time() + (int) ($r['expires_in'] ?? 3600)),
            'ultimo_erro'  => null,
            'date_created' => date('Y-m-d H:i:s'),
        ];

        // Só se grava o refresh_token quando vem: numa reautorização sem
        // prompt=consent o Google não o manda, e gravá-lo vazio apagava o bom.
        if (!empty($r['refresh_token'])) {
            $linha['refresh_token'] = $r['refresh_token'];
        }

        $existe = dps_google_conta($staff_id);
        if ($existe) {
            unset($linha['date_created']);
            $this->db->where('staff_id', $staff_id)
                     ->update(db_prefix() . 'dps_google_contas', $linha);
        } else {
            $this->db->insert(db_prefix() . 'dps_google_contas', $linha);
        }

        log_activity('Google Calendar ligado por ' . get_staff_full_name() . ' (' . $email . ')');
        set_alert('success', 'Conta ligada: ' . $email);
        redirect(admin_url('dps_google'));
    }

    public function desligar()
    {
        $this->db->where('staff_id', get_staff_user_id())
                 ->delete(db_prefix() . 'dps_google_contas');

        set_alert('success', 'Conta desligada. As reuniões deixam de ir para o seu calendário.');
        redirect(admin_url('dps_google'));
    }
}
