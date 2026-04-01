<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controlador principal do módulo DPS Calendar Sync.
 * Responsável por lidar com as páginas de eventos, definições e autenticação OAuth.
 */
class Dps_calendar_sync extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_calendar_sync/Dps_calendar_sync_model', 'dps_calendar_sync_model');
    }

    /**
     * Página principal do módulo: lista de eventos e formulário para criar novo evento.
     */
    public function index()
    {
        if (!staff_can('view', 'dps_calendar_sync')) {
            access_denied('dps_calendar_sync');
        }
        $staff_id = get_staff_user_id();

        // Processar criação de evento
        if ($this->input->post('add_event')) {
            if (!staff_can('create', 'dps_calendar_sync')) {
                access_denied('dps_calendar_sync');
            }
            $start_input = $this->input->post('start_time');
            $end_input   = $this->input->post('end_time');
            $start_sql   = $start_input ? date('Y-m-d H:i:s', strtotime($start_input)) : null;
            $end_sql     = $end_input   ? date('Y-m-d H:i:s', strtotime($end_input))   : null;
            $data = [
                'title'       => $this->input->post('title'),
                'description' => $this->input->post('description'),
                'start_time'  => $start_sql,
                'end_time'    => $end_sql,
                'location'    => $this->input->post('location'),
            ];
            $id = $this->dps_calendar_sync_model->create_event($staff_id, $data, true);
            if ($id) {
                set_alert('success', _l('dps_calendar_sync_event_created'));
            } else {
                set_alert('warning', _l('dps_calendar_sync_event_create_failed'));
            }
            redirect(admin_url('dps_calendar_sync'));
        }

        $tokens    = $this->dps_calendar_sync_model->get_tokens($staff_id);
        $calendars = null;
        if ($tokens) {
            $calendars = $this->dps_calendar_sync_model->get_calendars_list($staff_id);
        }

        $data['events']    = $this->dps_calendar_sync_model->get_events($staff_id);
        $data['tokens']    = $tokens;
        $data['calendars'] = $calendars;
        $data['title']     = _l('dps_calendar_sync_events');
        $this->load->view('dps_calendar_sync/calendar_sync/index', $data);
    }

    /**
     * AJAX: Guardar a escolha de calendário do utilizador.
     */
    public function ajax_save_calendar()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $staff_id      = get_staff_user_id();
        $calendar_id   = $this->input->post('calendar_id');
        $calendar_name = $this->input->post('calendar_name');
        if (!$calendar_id) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID do calendário em falta.']);
            exit;
        }
        $this->dps_calendar_sync_model->save_calendar_choice($staff_id, $calendar_id, $calendar_name);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Definições do módulo: inserir client_id, client_secret e redirect_uri.
     */
    public function settings()
    {
        if (!is_admin()) {
            access_denied('settings');
        }
        if ($this->input->post()) {
            update_option('dps_calendar_sync_client_id',     $this->input->post('client_id'));
            update_option('dps_calendar_sync_client_secret', $this->input->post('client_secret'));
            update_option('dps_calendar_sync_redirect_uri',  $this->input->post('redirect_uri'));
            set_alert('success', _l('settings_updated'));
            redirect(admin_url('dps_calendar_sync/settings'));
        }
        $data['client_id']     = get_option('dps_calendar_sync_client_id');
        $data['client_secret'] = get_option('dps_calendar_sync_client_secret');
        $data['redirect_uri']  = get_option('dps_calendar_sync_redirect_uri');
        $data['title']         = _l('dps_calendar_sync_settings');
        $this->load->view('dps_calendar_sync/calendar_sync/settings', $data);
    }

    /**
     * Inicia o processo de autenticação Google OAuth2 para o utilizador actual.
     */
    public function connect()
    {
        $config = $this->dps_calendar_sync_model->get_client_config();
        if (empty($config['client_id']) || empty($config['client_secret']) || empty($config['redirect_uri'])) {
            set_alert('warning', _l('dps_calendar_sync_config_error'));
            redirect(admin_url('dps_calendar_sync'));
        }
        $state = md5(time() . uniqid());
        $this->session->set_userdata('dps_calendar_sync_oauth_state', $state);
        // Pedir também permissão para listar calendários
        $scope = urlencode('https://www.googleapis.com/auth/calendar https://www.googleapis.com/auth/calendar.events');
        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?response_type=code'
            . '&access_type=offline&prompt=consent'
            . '&client_id=' . urlencode($config['client_id'])
            . '&redirect_uri=' . urlencode($config['redirect_uri'])
            . '&scope=' . $scope
            . '&state=' . urlencode($state);
        redirect($authUrl);
    }

    /**
     * Endpoint de callback para o OAuth Google.
     */
    public function oauth_callback()
    {
        $state      = $this->input->get('state');
        $code       = $this->input->get('code');
        $savedState = $this->session->userdata('dps_calendar_sync_oauth_state');
        $this->session->unset_userdata('dps_calendar_sync_oauth_state');
        if (!$code || !$state || $state !== $savedState) {
            set_alert('warning', _l('dps_calendar_sync_oauth_invalid_state'));
            redirect(admin_url('dps_calendar_sync'));
        }
        $config = $this->dps_calendar_sync_model->get_client_config();
        $params = [
            'code'          => $code,
            'client_id'     => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri'  => $config['redirect_uri'],
            'grant_type'    => 'authorization_code',
        ];
        $response = $this->dps_calendar_sync_model->curl_post('https://oauth2.googleapis.com/token', $params);
        if (!isset($response['access_token'])) {
            set_alert('warning', _l('dps_calendar_sync_oauth_failed'));
            redirect(admin_url('dps_calendar_sync'));
        }
        $expires = isset($response['expires_in']) ? time() + $response['expires_in'] : null;
        $data = [
            'access_token'  => $response['access_token'],
            'refresh_token' => isset($response['refresh_token']) ? $response['refresh_token'] : null,
            'token_type'    => isset($response['token_type']) ? $response['token_type'] : null,
            'expires_at'    => $expires ? date('Y-m-d H:i:s', $expires) : null,
        ];
        $staff_id = get_staff_user_id();
        $this->dps_calendar_sync_model->save_tokens($staff_id, $data);
        set_alert('success', _l('dps_calendar_sync_connected'));
        redirect(admin_url('dps_calendar_sync'));
    }

    /**
     * Desliga a conta Google do utilizador actual.
     */
    public function disconnect()
    {
        $staff_id = get_staff_user_id();
        $this->dps_calendar_sync_model->delete_tokens($staff_id);
        set_alert('success', _l('dps_calendar_sync_disconnected'));
        redirect(admin_url('dps_calendar_sync'));
    }

    /**
     * Sincroniza eventos do Google Calendar para o CRM (importação).
     */
    public function sync_google()
    {
        $staff_id = get_staff_user_id();
        $events   = $this->dps_calendar_sync_model->fetch_google_events($staff_id);
        if ($events === null) {
            set_alert('warning', _l('dps_calendar_sync_no_tokens'));
        } else {
            $count = 0;
            foreach ($events as $gEvent) {
                if (isset($gEvent['status']) && $gEvent['status'] === 'cancelled') {
                    continue;
                }
                $title = isset($gEvent['summary']) ? $gEvent['summary'] : '(sem título)';
                $start = isset($gEvent['start']['dateTime']) ? $gEvent['start']['dateTime'] : null;
                $end   = isset($gEvent['end']['dateTime'])   ? $gEvent['end']['dateTime']   : null;
                if ($start && $end) {
                    $exists = $this->db->where('google_event_id', $gEvent['id'])
                                       ->where('staff_id', $staff_id)
                                       ->get(db_prefix() . 'dps_calendar_events')
                                       ->row();
                    if (!$exists) {
                        $data = [
                            'title'       => $title,
                            'description' => isset($gEvent['description']) ? $gEvent['description'] : '',
                            'start_time'  => date('Y-m-d H:i:s', strtotime($start)),
                            'end_time'    => date('Y-m-d H:i:s', strtotime($end)),
                            'location'    => isset($gEvent['location']) ? $gEvent['location'] : '',
                        ];
                        $local_id = $this->dps_calendar_sync_model->create_event($staff_id, $data, false);
                        $this->db->where('id', $local_id)
                                 ->update(db_prefix() . 'dps_calendar_events', ['google_event_id' => $gEvent['id']]);
                        $count++;
                    }
                }
            }
            set_alert('success', sprintf(_l('dps_calendar_sync_imported_events'), $count));
        }
        redirect(admin_url('dps_calendar_sync'));
    }
}
