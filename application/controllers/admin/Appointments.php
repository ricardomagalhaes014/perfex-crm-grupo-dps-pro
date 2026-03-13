<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointments extends AdminController
{
    private $staff_no_view_permissions;

    public function __construct()
    {
        parent::__construct();

        // 🔒 Verifica permissões
        $this->staff_no_view_permissions = !staff_can('view', 'appointments') 
                                           && !staff_can('view_own', 'appointments');

        // 📌 Carrega modelos necessários
        $this->load->model('appointly/Appointly_model', 'apm');
        $this->load->model('appointly/appointly_attendees_model', 'atm');
        $this->load->model('clients_model');
        $this->load->model('contacts_model');
    }

    /**
     * Página inicial de compromissos
     */
   public function index()
{
    if ($this->staff_no_view_permissions) {
        access_denied('Appointments');
    }

    // Captura os parâmetros da URL
    $rel_type = $this->input->get('rel_type');
    $rel_id   = $this->input->get('rel_id');

    if ($rel_type && $rel_id) {
        // 🔎 Buscar compromissos filtrados pelo cliente
        $data['appointments'] = $this->apm->get('', [
            'rel_type' => $rel_type,
            'rel_id'   => $rel_id,
        ]);
    } else {
        // Sem filtro -> retorna todos
        $data['appointments'] = $this->apm->get();
    }

    // Mantém compatibilidade com o que já tinhas
    $data['client_id']       = $rel_id ?? null;
    $data['td_appointments'] = $this->getTodaysAppointments();
    $data['title']           = _l('appointments');

    $this->load->view('index', $data);
}

    /**
     * Visualizar compromisso
     */
    public function view()
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }

        $this->session->unset_userdata('from_view_id');
        $appointment_id = $this->input->get('appointment_id');

        if (!$appointment_id) {
            show_404();
        }

        $attendees = $this->atm->attendees($appointment_id);

        // 🔒 Verifica se o utilizador pode ver
        if (!in_array(get_staff_user_id(), $attendees)) {
            if (!staff_can('view', 'appointments')) {
                access_denied('Appointments');
            }
        }

        $data['appointment'] = $this->apm->get_appointment_data($appointment_id);

        if (!$data['appointment']) {
            appointly_redirect_after_event('warning', _l('appointment_not_exists'));
            show_404();
        }

        // 🌐 Link público do agendamento
        $data['appointment']['public_url'] = site_url(
            'appointly/appointments_public/client_hash?hash=' . $data['appointment']['hash']
        );

        $this->load->view('tables/appointment', $data);
    }

    /**
     * Histórico de compromissos (pesquisa por empresa, NIF ou contacto)
     */
    public function history()
    {
        $data['appointments'] = [];
        $data['client_id']    = null;

        $search_type  = $this->input->get('type');
        $search_value = trim($this->input->get('q') ?? '');

        if (!$search_type || $search_value === '') {
            $this->load->view('appointments/history', $data);
            return;
        }

        if ($search_type === 'empresa') {
            $this->db->like('company', $search_value);
            $company = $this->db->get(db_prefix() . 'clients')->row_array();

            if ($company) {
                $contacts = $this->clients_model->get_contacts($company['userid']);
                if (!empty($contacts)) {
                    $contact               = reset($contacts);
                    $data['appointments']  = $this->apm->get_by_client($contact['id']);
                    $data['client_id']     = $contact['id'];
                }
            }

        } elseif ($search_type === 'nif') {
            $this->db->where('vat', $search_value);
            $company = $this->db->get(db_prefix() . 'clients')->row_array();

            if ($company) {
                $contacts = $this->clients_model->get_contacts($company['userid']);
                if (!empty($contacts)) {
                    $contact               = reset($contacts);
                    $data['appointments']  = $this->apm->get_by_client($contact['id']);
                    $data['client_id']     = $contact['id'];
                }
            }

        } elseif ($search_type === 'contacto') {
            $this->db->like('firstname', $search_value);
            $contact = $this->db->get(db_prefix() . 'contacts')->row_array();

            if ($contact) {
                $data['appointments']  = $this->apm->get_by_client($contact['id']);
                $data['client_id']     = $contact['id'];
            }
        }

        $this->load->view('appointments/history', $data);
    }

    /**
     * Obter compromissos do dia
     */
    public function getTodaysAppointments()
    {
        return $this->apm->fetch_todays_appointments();
    }

    /**
     * Pesquisa de clientes (AJAX)
     */
    public function search_clients()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $type  = $this->input->get('type');
        $query = trim($this->input->get('q'));

        $results = [];

        if ($type === 'contacto') {
            $this->db->like('CONCAT(firstname, " ", lastname)', $query);
            $contacts = $this->contacts_model->get('', ['limit' => 10]);

            foreach ($contacts as $contact) {
                $results[] = [
                    'id'   => $contact['id'],
                    'text' => $contact['firstname'] . ' ' . $contact['lastname']
                ];
            }

        } elseif ($type === 'empresa') {
            $this->db->like('company', $query);
            $clients = $this->clients_model->get('', ['limit' => 10]);

            foreach ($clients as $client) {
                $contacts = $this->clients_model->get_contacts($client['userid']);
                if (!empty($contacts)) {
                    $results[] = [
                        'id'   => $contacts[0]['id'],
                        'text' => $client['company']
                    ];
                }
            }

        } elseif ($type === 'nif') {
            $this->db->like('vat', $query);
            $clients = $this->clients_model->get('', ['limit' => 10]);

            foreach ($clients as $client) {
                $contacts = $this->clients_model->get_contacts($client['userid']);
                if (!empty($contacts)) {
                    $results[] = [
                        'id'   => $contacts[0]['id'],
                        'text' => $client['vat'] . ' - ' 
                                . $contacts[0]['firstname'] . ' ' 
                                . $contacts[0]['lastname']
                    ];
                }
            }
        }

        echo json_encode(['results' => $results]);
    }
}