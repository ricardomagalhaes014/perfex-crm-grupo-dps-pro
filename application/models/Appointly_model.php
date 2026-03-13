<?php defined('BASEPATH') or exit('No direct script access allowed');

class Appointly_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointly/appointly_attendees_model', 'atm');
    }

    public function insertHandleCustomFieldsAndNotifications($data, $attendees)
    {
        $data = array_merge($data, convertDateForDatabase($data['date']));
        $data['hash'] = app_generate_hash();

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        if (isset($data['rel_id'])) {
            unset($data['rel_id']);
        }

        $data = $this->validateInsertRecurring($data);

        $this->db->insert(db_prefix() . 'appointly_appointments', $data);
        $appointment_id = $this->db->insert_id();

        if (isset($custom_fields)) {
            handle_custom_fields_post($appointment_id, $custom_fields);

            if (isset($custom_fields[13]) && in_array(strtolower(trim($custom_fields[13])), ['sim', 'sim.', 'sim,'])) {
                $this->load->model('projects_model');

                $project_data = [
                    'name'             => 'Projeto Telecom/Energia - ' . date('d-m-Y H:i'),
                    'description'      => 'Criado automaticamente a partir de agendamento com interesse em telecomunicações ou energia.',
                    'start_date'       => date('Y-m-d'),
                    'deadline'         => date('Y-m-d', strtotime('+30 days')),
                    'project_created'  => date('Y-m-d H:i:s'),
                    'progress'         => 0,
                    'status'           => 1,
                    'billing_type'     => 1,
                    'project_manager'  => 75,
                    'members'          => [75]
                ];

                $this->projects_model->add($project_data);
                log_activity('✅ Projeto automático criado com base no agendamento #' . $appointment_id);
            }
        }

        $this->atm->create($appointment_id, $attendees);
        $this->appointment_approve_notification_and_sms_triggers($appointment_id);

        $responsiblePerson = get_option('appointly_responsible_person');
        if (!empty($responsiblePerson)) {
            add_notification([
                'description' => 'appointment_new_appointment_submitted',
                'touserid'    => $responsiblePerson,
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment_id,
            ]);
            pusher_trigger_notification(array_unique([$responsiblePerson]));
        }

        return true;
    }

    public function fetch_todays_appointments()
    {
        $today = (new DateTime())->format('Y-m-d');

        $staff_has_permissions = !staff_can('view', 'appointments') || !staff_can('view_own', 'appointments');

        if ($staff_has_permissions) {
            $this->db->where('id IN (
                SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees 
                WHERE staff_id=' . get_staff_user_id() . ')');
        }

        $this->db->where('date', $today);
        $this->db->where('approved', 1);

        return $this->db->get(db_prefix() . 'appointly_appointments')->result_array();
    }

    public function get_by_client($client_id)
    {
        $this->db->where('contact_id', $client_id);
        $this->db->order_by('date', 'DESC');
        return $this->db->get(db_prefix() . 'appointly_appointments')->result_array();
    }

    public function get_appointment_data($id)
    {
        return $this->db->get_where(db_prefix() . 'appointly_appointments', ['id' => $id])->row_array();
    }

    public function delete_appointment($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'appointly_appointments');
        return $this->db->affected_rows() > 0;
    }

    public function update_appointment($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix() . 'appointly_appointments', $data);
        return $this->db->affected_rows() > 0;
    }

    public function approve_appointment($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['approved' => 1]);
        return $this->db->affected_rows() > 0;
    }

    public function mark_as_finished($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['finished' => 1]);
        return $this->db->affected_rows() > 0;
    }

    public function mark_as_ongoing($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update(db_prefix() . 'appointly_appointments', ['ongoing' => 1]);
        return $this->db->affected_rows() > 0;
    }

    public function cancel_appointment($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['cancelled' => 1]);
        return $this->db->affected_rows() > 0;
    }

    public function send_appointment_early_reminders($id)
    {
        return true;
    }

    public function update_internal_crm_appointment($data)
    {
        return $this->update_appointment($data);
    }

    public function insert_appointment($data)
    {
        $this->db->insert(db_prefix() . 'appointly_appointments', $data);
        return $this->db->insert_id();
    }

    public function insert_internal_crm_appointment($data)
    {
        return $this->insert_appointment($data);
    }

    public function apply_contact_data($id, $is_lead = false)
    {
        return ['id' => $id, 'is_lead' => $is_lead];
    }

    public function update_appointment_types($data, $meta)
    {
        foreach ($meta as $key => $value) {
            update_staff_meta(get_staff_user_id(), $key, $value);
        }
        return true;
    }

    public function getBusyTimes($date)
    {
        if (!$date) {
            return [];
        }

        $this->db->select('start_hour, end_hour');
        $this->db->from(db_prefix() . 'appointly_appointments');
        $this->db->where('date', $date);
        $this->db->where('approved', 1);

        return $this->db->get()->result_array();
    }
}