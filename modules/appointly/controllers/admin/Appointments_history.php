<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointments_history extends AdminController
{
    private $staff_no_view_permissions;

    public function __construct()
    {
        parent::__construct();

        $this->staff_no_view_permissions = !staff_can('view', 'appointments') && !staff_can('view_own', 'appointments');

        $this->load->model('appointly_model', 'apm');
    }

    /**
     * Exibe a página principal do histórico de marcações
     */
    public function index()
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }

        $this->load->view(module_views_path(APPOINTLY_MODULE_NAME, 'history_view'));
    }

    /**
     * Retorna os dados da tabela de histórico das marcações do utilizador atual
     */
    public function table()
    {
        if ($this->staff_no_view_permissions) {
            access_denied();
        }

        $staff_id = get_staff_user_id();
        $appointments = $this->apm->getAppointmentsByStaff($staff_id);
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
     * Retorna notas e assunto de uma marcação
     */
    public function get_notes($appointment_id)
    {
        if (!is_numeric($appointment_id)) {
            show_404();
        }

        $this->db->select('notes, subject');
        $this->db->where('id', $appointment_id);
        $result = $this->db->get(db_prefix() . 'appointly_appointments')->row();

        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Atualiza as notas de uma marcação
     */
    public function update_note()
    {
        $appointment_id = $this->input->post('appointment_id');
        $notes = $this->input->post('notes');

        $success = false;

        if (!empty($appointment_id)) {
            $this->db->where('id', $appointment_id);
            $this->db->update(db_prefix() . 'appointly_appointments', ['notes' => $notes]);
            $success = $this->db->affected_rows() > 0;
        }

        echo json_encode(['result' => $success]);
    }

    /**
     * Exibe o histórico de marcações de um cliente específico
     */
    public function get_appointments_history($client_id)
    {
        if ($this->staff_no_view_permissions) {
            access_denied('Appointments');
        }

        if (!is_numeric($client_id)) {
            show_404();
        }

        $appointments = $this->apm->get_by_client($client_id);

        $data = [
            'appointments' => $appointments,
            'client_id'    => $client_id,
        ];

        $this->load->view(module_views_path(APPOINTLY_MODULE_NAME, 'appointments/history_view'), $data);
    }

    /**
     * DEBUG: Retorna o histórico de marcações por cliente (JSON)
     */
    public function debug_get_appointments_by_client($client_id)
    {
        if (!is_numeric($client_id)) {
            show_404();
        }

        $this->load->model('Appointments_model');
        $appointments = $this->Appointments_model->get_appointments_by_client($client_id);

        header('Content-Type: application/json');
        echo json_encode($appointments);
    }
}