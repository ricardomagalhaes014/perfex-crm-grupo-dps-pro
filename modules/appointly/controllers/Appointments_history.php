<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointments_history extends AdminController
{
    private $staff_no_view_permissions;

    public function __construct()
    {
        parent::__construct();

        $this->staff_no_view_permissions = !staff_can('view', 'appointments') && !staff_can('view_own', 'appointments');

        $this->load->model('appointly/Appointly_model', 'apm');
    }

    /**
     * Página principal do histórico (vista geral)
     */
    public function index()
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }

        $this->load->view('appointly/appointments/history_view');
    }

    /**
     * Retorna agendamentos do staff atual (para tabela)
     */
    public function table()
    {
        if ($this->staff_no_view_permissions) {
            access_denied();
        }

        $staff_id = get_staff_user_id();
        $appointments = $this->apm->get_appointments_by_staff($staff_id);

        $data = [];

        foreach ($appointments as $appointment) {
            $data[] = [
                $appointment['id'],
                $appointment['subject'],
                _dt($appointment['date'] . ' ' . $appointment['start_hour']),
                get_staff_full_name($appointment['created_by']),
                $appointment['description'],
                $appointment['source'],
                '<a href="' . admin_url('appointly/appointments/view?appointment_id=' . $appointment['id']) . '" class="btn btn-default btn-sm">' . _l('view') . '</a>',
            ];
        }

        echo json_encode(['data' => $data]);
    }

    /**
     * Histórico de agendamentos de um cliente específico (render view)
     */
    public function get_appointments_history($client_id)
{
    // Verifica permissões de acesso
    if ($this->staff_no_view_permissions) {
        access_denied('Appointments');
    }

    // Valida se o ID é numérico
    if (!is_numeric($client_id)) {
        show_404();
    }

    // Tenta buscar as marcações
    $appointments = $this->apm->get_appointments_by_client($client_id);

    // Prepara os dados a enviar para a view
    $data = [
        'appointments' => is_array($appointments) ? $appointments : [],
        'client_id'    => $client_id
    ];

    // Renderiza a view com segurança
    if ($this->load->view('appointly/appointments/history_view', $data, true)) {
        $this->load->view('appointly/appointments/history_view', $data);
    } else {
        show_error('Erro ao carregar a vista de histórico de marcações.', 500);
    }
}

    /**
     * Endpoint direto para retornar JSON com histórico por cliente (AJAX/API)
     */
    public function debug_get_appointments_by_client($client_id)
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }

        if (!is_numeric($client_id)) {
            show_404();
        }

        try {
            $appointments = $this->apm->get_appointments_by_client($client_id);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'appointments' => $appointments,
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtém nota de uma marcação
     */
    public function get_notes($appointment_id)
    {
        if (!is_numeric($appointment_id)) {
            show_404();
        }

        $this->db->select('notes, subject');
        $this->db->where('id', $appointment_id);
        $note = $this->db->get(db_prefix() . 'appointly_appointments')->row();

        echo json_encode($note);
    }

    /**
     * Atualiza nota de uma marcação
     */
    public function update_note()
    {
        $appointment_id = $this->input->post('appointment_id');
        $notes = $this->input->post('notes');

        if (!is_numeric($appointment_id)) {
            echo json_encode(['result' => false]);
            return;
        }

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['notes' => $notes]);

        echo json_encode(['result' => true]);
    }

    /**
     * Teste de funcionamento
     */
    public function test()
    {
        echo "Appointments_history controller ativo!";
    }
}