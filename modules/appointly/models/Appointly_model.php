<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Appointly_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('appointly/appointly_attendees_model', 'atm');
    }

    /**
     * Insere uma nova marcação no sistema
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function insert_appointment($data)
    {
        $attendees = [];
        $relation = $data['rel_type'] ?? null;
        $external_cid = null;

        unset($data['rel_type']);

        if ($relation === 'lead_related') {
            $this->load->model('leads_model');
            $lead = $this->leads_model->get($data['rel_id']);

            if ($lead) {
                $data['contact_id'] = $data['rel_id'];
                $data['name']       = $lead->name;
                $data['phone']      = $lead->phonenumber ?? '';
                $data['address']    = $lead->address ?? '';
                $data['email']      = $lead->email ?? '';
                $attendees          = $data['attendees'] ?? [];
                $data['source']     = 'lead_related';
                $data['created_by'] = get_staff_user_id();
            }

            unset($data['rel_lead_type'], $data['rel_id'], $data['attendees']);
        } else {
            unset($data['rel_lead_type']);
        }

        if ($relation === 'internal') {
            $data['created_by'] = get_staff_user_id();
            $data['source']     = 'internal';
            $attendees          = $data['attendees'] ?? [get_staff_user_id()];
            unset($data['attendees']);
        } elseif ($relation === 'external') {
            $data['created_by'] = get_staff_user_id();
            $data['source']     = 'external';
            $attendees          = $data['attendees'] ?? [get_staff_user_id()];

            if (isset($data['contact_id'])) {
                $external_cid = $data['contact_id'];
                $data['contact_id'] = null;
            }

            unset($data['attendees']);

            if (is_admin() || staff_can('view', 'appointments') || staff_can('view_own', 'appointments')) {
                $data['approved'] = 1;
            }
        }
        // 🔒 Validação obrigatória para client_id em externos
if (isset($data['source']) && $data['source'] === 'external' && empty($data['client_id'])) {
    log_message('error', '❌ client_id ausente em agendamento externo: ' . json_encode($data));
    return false; // ou: throw new Exception('client_id obrigatório para agendamentos externos');
}

        if (
            (is_admin() && in_array($relation, ['internal', 'lead_related'])) ||
            ((staff_can('view', 'appointments') || staff_can('view_own', 'appointments')) && $relation === 'internal')
        ) {
            $data['approved'] = 1;
        }

        if (!empty($data['phone'])) {
            $data['phone'] = preg_replace('/\s+/', '', $data['phone']);
        }

        if ($data['source'] === 'internal' && empty($data['email'])) {
            $contact_data = get_appointment_contact_details($data['contact_id'] ?? '');

            if ($contact_data) {
                $data['email'] = $contact_data['email'] ?? '';
                $data['name']  = $contact_data['full_name'] ?? '';
                $data['phone'] = $contact_data['phone'] ?? '';
            }
        }

        if (appointlyGoogleAuth() && isset($data['google'])) {
            $data['external_contact_id'] = $external_cid;

            $googleEvent = insertAppointmentToGoogleCalendar($data, $attendees);

            if ($googleEvent && is_array($googleEvent)) {
                if (isset($googleEvent['google_event_id'])) {
                    $data['google_event_id'] = $googleEvent['google_event_id'];
                }
                if (isset($googleEvent['htmlLink'])) {
                    $data['google_calendar_link'] = $googleEvent['htmlLink'];
                }
                if (isset($googleEvent['hangoutLink'])) {
                    $data['google_meet_link'] = $googleEvent['hangoutLink'];
                }

                $data['google_added_by_id'] = get_staff_user_id();
            }

            unset($data['google'], $data['external_contact_id']);
        }

        return $this->insertHandleCustomFieldsAndNotifications($data, $attendees);
    }
        private function insertHandleCustomFieldsAndNotifications($data, $attendees)
    {
        $data = array_merge($data, convertDateForDatabase($data['date']));
        $data['hash'] = app_generate_hash();

        $custom_fields = $data['custom_fields'] ?? null;
        unset($data['custom_fields'], $data['rel_id']);

        $data = $this->validateInsertRecurring($data);

        $this->db->insert(db_prefix() . 'appointly_appointments', $data);
        $appointment_id = $this->db->insert_id();

        if ($custom_fields) {
            handle_custom_fields_post($appointment_id, $custom_fields);
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

    public function getAppointmentsByStaff($staff_id)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'appointly_appointments');
        $this->db->where('id IN (
            SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees 
            WHERE staff_id = ' . $this->db->escape($staff_id) . ')');
        $this->db->order_by('date', 'DESC');

        return $this->db->get()->result_array();
    }

    public function recurringAddGoogleNewEvent($data, $attendees)
    {
        $googleInsertData = [];
        $googleEvent = insertAppointmentToGoogleCalendar($data, $attendees);

        if ($googleEvent && is_array($googleEvent)) {
            $googleInsertData['google_event_id'] = $googleEvent['google_event_id'];
            $googleInsertData['google_calendar_link'] = $googleEvent['htmlLink'];

            if (isset($googleEvent['hangoutLink'])) {
                $googleInsertData['google_meet_link'] = $googleEvent['hangoutLink'];
            }
        }

        return $googleInsertData;
    }

    public function add_event_to_google_calendar($data)
    {
        $result = ['result' => 'error', 'message' => _l('Oops, something went wrong, please try again...')];

        if (appointlyGoogleAuth()) {
            if (isset($data['google_added_by_id']) && is_null($data['google_added_by_id'])) {
                unset($data['rel_type']);

                $googleEvent = insertAppointmentToGoogleCalendar($data, $data['attendees'] ?? []);

                if ($googleEvent && is_array($googleEvent)) {
                    $data['google_event_id'] = $googleEvent['google_event_id'] ?? null;
                    $data['google_calendar_link'] = $googleEvent['htmlLink'] ?? null;
                    $data['google_meet_link'] = $googleEvent['hangoutLink'] ?? null;
                    $data['google_added_by_id'] = get_staff_user_id();

                    $data['id'] = $data['appointment_id'];
                    $data = array_merge($data, convertDateForDatabase($data['date']));

                    unset(
                        $data['selected_contact'],
                        $data['appointment_id'],
                        $data['attendees'],
                        $data['custom_fields'],
                        $data['repeat_type_custom'],
                        $data['repeat_every_custom']
                    );

                    $this->db->where('id', $data['id']);
                    $this->db->update(db_prefix() . 'appointly_appointments', $data);

                    if ($this->db->affected_rows() > 0) {
                        return ['result' => 'success', 'message' => _l('appointments_added_to_google_calendar')];
                    }
                }
            }
        }

        return $result;
    }
        public function insert_external_appointment($data)
    {
        $data['hash'] = app_generate_hash();

        if (!empty($data['phone'])) {
            $data['phone'] = preg_replace('/\s+/', '', $data['phone']);
        }

        $data = array_merge($data, convertDateForDatabase($data['date']));

        $responsiblePerson = get_option('appointly_responsible_person');
        $approvedByDefault = get_option('appointly_client_meeting_approved_default');

        if ($approvedByDefault) {
            $data['approved'] = 1;
        }

        $custom_fields = $data['custom_fields'] ?? null;
        unset($data['custom_fields']);

        $this->db->insert(db_prefix() . 'appointly_appointments', $data);
        $appointment_id = $this->db->insert_id();

        if ($custom_fields) {
            handle_custom_fields_post($appointment_id, $custom_fields);
        }

        if ($approvedByDefault) {
            $data['id'] = $appointment_id;
            $this->atm->send_notifications_to_appointment_contact($data);
            $this->atm->create($appointment_id, [$responsiblePerson ?: '1']);
        }

        if (!empty($responsiblePerson)) {
            $appointment = $this->apm->get_appointment_data($appointment_id);
            $staff = appointly_get_staff($responsiblePerson);

            send_mail_template(
                'appointly_appointment_new_appointment_submitted',
                'appointly',
                array_to_object($staff),
                array_to_object($appointment)
            );

            add_notification([
                'description' => 'appointment_new_appointment_submitted',
                'touserid'    => $responsiblePerson,
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment_id,
            ]);

            pusher_trigger_notification([$responsiblePerson]);
        }

        $appointment_link = site_url('appointly/appointments/view?appointment_id=' . $appointment_id);
        hooks()->do_action('send_sms_after_external_appointment_submitted', $appointment_link);

        return true;
    }

    public function update_appointment($data)
    {
        unset($data['rel_type']);

        $appointment_id = $data['appointment_id'];
        $originalAppointment = $this->get_appointment_data($appointment_id);
        $current_attendees = $this->atm->attendees($appointment_id);
        $current_contact = $this->atm->get_contact_email($data);

        if (!empty($data['phone'])) {
            $data['phone'] = preg_replace('/\s+/', '', $data['phone']);
        }

        $data = handleDataReminderFields($data);
        $contact_form_email = $data['email'] ?? '';

        if (isset($data['contact_id']) && $data['contact_id'] == 0) {
            unset($data['contact_id']);
        }

        if (appointlyGoogleAuth()) {
            if (!empty($data['google_event_id']) && $data['google_added_by_id'] == get_staff_user_id()) {
                updateAppointmentToGoogleCalendar($data);
                unset($data['google_event_id'], $data['selected_contact']);
            } elseif (!isset($data['google_event_id']) && isset($data['google']) && $data['approved'] == '1') {
                $googleEvent = insertAppointmentToGoogleCalendar($data, $data['attendees']);
                $data['google_event_id'] = $googleEvent['google_event_id'] ?? null;
                $data['google_calendar_link'] = $googleEvent['htmlLink'] ?? null;
                $data['google_added_by_id'] = get_staff_user_id();
            }
        }

        unset($data['google']);

        $data = array_merge($data, convertDateForDatabase($data['date']));
        $attendees = $data['attendees'] ?? [];
        $attendee_difference = array_diff($attendees, $current_attendees);
        $new_attendees = [];

        if (!empty($attendee_difference) && $data['approved'] == '1') {
            foreach ($attendee_difference as $new_attendee) {
                $new_attendees[] = appointly_get_staff($new_attendee);
            }

            $data['id'] = $appointment_id;
            $this->atm->send_notifications_to_new_attenddees($new_attendees, $data);
            unset($data['id']);
        }

        unset($data['appointment_id'], $data['attendees'], $data['selected_contact']);

        if (isset($data['google_added_by_id']) && $data['google_added_by_id'] == 0) {
            unset($data['google_added_by_id']);
        }

        if (isset($data['custom_fields'])) {
            handle_custom_fields_post($appointment_id, $data['custom_fields']);
            unset($data['custom_fields']);
        }

        $data = $this->validateRecurringData($originalAppointment, $data);

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', $data);
        $this->atm->update($appointment_id, $attendees);

        if (($originalAppointment['source'] ?? '') === 'external' && !empty($contact_form_email)) {
            if (($current_contact['email'] ?? '') !== $contact_form_email) {
                $updated_appointment = $this->get_appointment_data($appointment_id);
                $updated_appointment['id'] = $appointment_id;
                $this->atm->send_notifications_to_appointment_contact($updated_appointment);
            }
        }

        return true;
    }
        public function update_internal_crm_appointment($data)
    {
        $appointment_id = $data['appointment_id'];
        $originalAppointment = $this->get_appointment_data($appointment_id);
        $current_attendees = $this->atm->attendees($appointment_id);

        $data = handleDataReminderFields($data);

        if (appointlyGoogleAuth()) {
            if (!empty($data['google_event_id']) && $data['google_added_by_id'] == get_staff_user_id()) {
                updateAppointmentToGoogleCalendar($data);
                unset($data['google_event_id'], $data['selected_contact']);
            } elseif (!isset($data['google_event_id']) && isset($data['google']) && $data['approved'] == '1') {
                $googleEvent = insertAppointmentToGoogleCalendar($data, $data['attendees']);
                $data['google_event_id'] = $googleEvent['google_event_id'] ?? null;
                $data['google_calendar_link'] = $googleEvent['htmlLink'] ?? null;
                $data['google_added_by_id'] = get_staff_user_id();
            }
        }

        unset($data['google']);

        $data = array_merge($data, convertDateForDatabase($data['date']));
        $attendees = $data['attendees'] ?? [];
        $attendee_difference = array_diff($attendees, $current_attendees);
        $new_attendees = [];

        if (!empty($attendee_difference) && $data['approved'] == '1') {
            foreach ($attendee_difference as $new_attendee) {
                $new_attendees[] = appointly_get_staff($new_attendee);
            }

            $data['id'] = $appointment_id;
            $this->atm->send_notifications_to_new_attenddees($new_attendees, $data);
            unset($data['id']);
        }

        unset($data['appointment_id'], $data['attendees']);

        if (isset($data['google_added_by_id']) && $data['google_added_by_id'] == 0) {
            unset($data['google_added_by_id']);
        }

        if (isset($data['custom_fields'])) {
            handle_custom_fields_post($appointment_id, $data['custom_fields']);
            unset($data['custom_fields']);
        }

        $data = $this->validateRecurringData($originalAppointment, $data);

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', $data);

        $this->atm->update($appointment_id, $attendees);

        return true;
    }

    public function delete_appointment($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);

        if ($appointment['created_by'] != get_staff_user_id() && !is_admin() && !staff_appointments_responsible()) {
            set_alert('danger', _l('appointments_no_delete_permissions'));
            $redirect = $_SERVER['HTTP_REFERER'] ?? admin_url('access_denied');
            redirect($redirect);
        }

        if (get_option('appointly_also_delete_in_google_calendar') == 1) {
            if (!empty($appointment['google_event_id']) && $appointment['google_added_by_id'] == get_staff_user_id()) {
                $this->load->model('googlecalendar');
                if (appointlyGoogleAuth()) {
                    $this->googlecalendar->deleteEvent($appointment['google_event_id']);
                }
            }
        }

        $this->atm->deleteAll($appointment_id);

        $this->db->where('id', $appointment_id);
        if (!is_admin() && !staff_appointments_responsible()) {
            $this->db->where('created_by', get_staff_user_id());
        }

        $this->db->delete(db_prefix() . 'appointly_appointments');

        return $this->db->affected_rows() !== 0;
    }

    public function fetch_todays_appointments()
    {
        $today = (new DateTime())->format('Y-m-d');

        if (!staff_can('view', 'appointments') && !staff_can('view_own', 'appointments')) {
            $this->db->where('id IN (
                SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees 
                WHERE staff_id = ' . get_staff_user_id() . ')');
        }

        $this->db->where('date', $today);
        $this->db->where('approved', 1);

        return $this->db->get(db_prefix() . 'appointly_appointments')->result_array();
    }

    public function getBusyTimes()
    {
        $time_format = get_option('time_format');
        $format = $time_format === '24' ? '"%H:%i"' : '"%h:%i %p"';
        $hour_format = $time_format === '24' ? '24' : '12';

        $this->db->select('TIME_FORMAT(start_hour, ' . $format . ') as start_hour, date, source, created_by', false);
        $this->db->from(db_prefix() . 'appointly_appointments');
        $this->db->where('approved', 1);

        $appointments = $this->db->get()->result_array();

        if ($hour_format === '12') {
            foreach ($appointments as &$appt) {
                $appt['start_hour'] = substr($appt['start_hour'], 1);
            }
        }

        if (appointlyGoogleAuth()) {
            $this->load->model('googlecalendar');
            $google_events = $this->googlecalendar->getEvents();
            $converted = [];

            foreach ($google_events as $event) {
                $datetime = _dt($event['start']);
                $parts = explode(' ', $datetime);

                if (!empty($parts[0])) {
                    $dt_str = $hour_format === '24' ? "$parts[0] $parts[1]" : "$parts[0] $parts[1] $parts[2]";
                    $converted[] = convertDateForValidation($dt_str, $hour_format);
                }
            }

            $appointments = array_merge($appointments, $converted);
        }

        echo json_encode($appointments);
    }
        public function getCalendarData($start, $end, $data)
    {
        $this->db->select('subject as title, date, hash, start_hour, id, type_id');
        $this->db->from(db_prefix() . 'appointly_appointments');
        $this->db->where('finished', 0);
        $this->db->where('cancelled', 0);
        $this->db->where('CONCAT(date, " ", start_hour) BETWEEN "' . $start . '" AND "' . $end . '"');

        if (!is_client_logged_in()) {
            if (!staff_appointments_responsible()) {
                $this->db->where('id IN (
                    SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees 
                    WHERE staff_id = ' . get_staff_user_id() . ')');
            }
        } else {
            $this->db->where('id IN (
                SELECT appointment_id FROM ' . db_prefix() . 'appointly_attendees 
                WHERE contact_id = ' . get_contact_user_id() . ')');
        }

        $appointments = $this->db->get()->result_array();

        foreach ($appointments as $appointment) {
            $appointment['url'] = admin_url('appointly/appointments/view?appointment_id=' . $appointment['id']);

            if (is_client_logged_in()) {
                $appointment['url'] = admin_url('appointly/appointments_public/client_hash?hash=' . $appointment['hash']);
                $appointment['_tooltip'] = $appointment['title'];
            } else {
                $appointment_type = get_appointment_type($appointment['type_id']);
                $appointment['_tooltip'] = $appointment_type
                    ? _l('appointments_type_heading') . ': ' . $appointment_type
                    : $appointment['title'];
            }

            $appointment['date'] = $appointment['date'] . ' ' . $appointment['start_hour'] . ':00';
            $appointment['color'] = get_appointment_color_type($appointment['type_id']);
            $data[] = $appointment;
        }

        return $data;
    }

    public function apply_contact_data($contact_id, $is_lead)
    {
        if ($is_lead === 'false' || $is_lead === false) {
            return $this->clients_model->get_contact($contact_id);
        }

        $this->load->model('leads_model');
        return $this->leads_model->get($contact_id);
    }

    public function get_appointment_data($appointment_id)
    {
        $this->db->where('id', $appointment_id);
        $appointment = $this->db->get(db_prefix() . 'appointly_appointments')->row_array();

        if ($appointment) {
            $appointment['attendees'] = $this->atm->get($appointment_id);
            return $appointment;
        }

        return false;
    }

    public function cancel_appointment($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);
        $notified_users = [];

        foreach ($appointment['attendees'] as $staff) {
            if ($staff['staffid'] === get_staff_user_id()) {
                continue;
            }

            add_notification([
                'description' => 'appointment_is_cancelled',
                'touserid'    => $staff['staffid'],
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment['id'],
            ]);

            $notified_users[] = $staff['staffid'];
            send_mail_template(
                'appointly_appointment_notification_cancelled_to_staff',
                'appointly',
                array_to_object($appointment),
                array_to_object($staff)
            );
        }

        pusher_trigger_notification(array_unique($notified_users));

        $template = mail_template(
            'appointly_appointment_notification_cancelled_to_contact',
            'appointly',
            array_to_object($appointment)
        );

        if (!empty($appointment['phone'])) {
            $merge_fields = $template->get_merge_fields();
            $this->app_sms->trigger(APPOINTLY_SMS_APPOINTMENT_CANCELLED_TO_CLIENT, $appointment['phone'], $merge_fields);
        }

        $template->send();

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['cancelled' => 1]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function approve_appointment($appointment_id)
    {
        $this->appointment_approve_notification_and_sms_triggers($appointment_id);

        $this->db->where('id', $appointment_id);
        $this->db->update(db_prefix() . 'appointly_appointments', [
            'finished' => 0,
            'approved' => 1,
            'external_notification_date' => date('Y-m-d'),
        ]);

        return true;
    }

    public function getByHash($hash)
    {
        $this->db->where('hash', $hash);
        $appointment = $this->db->get(db_prefix() . 'appointly_appointments')->row_array();

        if ($appointment) {
            $appointment['feedbacks'] = json_decode(get_option('appointly_default_feedbacks'));
            $appointment['selected_contact'] = $appointment['contact_id'] ?? null;

            if (!empty($appointment['selected_contact'])) {
                $appointment['details'] = get_appointment_contact_details($appointment['selected_contact']);
            }

            $appointment['attendees'] = $this->atm->get($appointment['id']);
            return $appointment;
        }

        return false;
    }
        public function mark_as_finished($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'appointly_appointments', ['finished' => 1]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $this->db->affected_rows() !== 0]);
    }

    public function mark_as_ongoing($appointment)
    {
        $this->appointment_approve_notification_and_sms_triggers($appointment['id']);

        $this->db->where('id', $appointment['id']);
        $this->db->update(db_prefix() . 'appointly_appointments', [
            'cancelled'     => 0,
            'finished'      => 0,
            'cancel_notes'  => null,
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => $this->db->affected_rows() !== 0]);
    }

    private function appointment_approve_notification_and_sms_triggers($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);
        $attendees = $appointment['attendees'];
        $notified_users = [];

        if (count($attendees) === 0) {
            $this->atm->create($appointment_id, [get_staff_user_id()]);
            $attendees = $this->atm->get($appointment_id);
        }

        foreach ($attendees as $staff) {
            if ($staff['staffid'] === get_staff_user_id()) {
                continue;
            }

            add_notification([
                'description' => 'appointment_is_approved',
                'touserid'    => $staff['staffid'],
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment['id'],
            ]);

            $notified_users[] = $staff['staffid'];

            send_mail_template(
                'appointly_appointment_approved_to_staff_attendees',
                'appointly',
                array_to_object($appointment),
                array_to_object($staff)
            );
        }

        pusher_trigger_notification(array_unique($notified_users));

        $template = mail_template('appointly_appointment_approved_to_contact', 'appointly', array_to_object($appointment));

        if (!empty($appointment['phone'])) {
            $merge_fields = $template->get_merge_fields();
            $this->app_sms->trigger(APPOINTLY_SMS_APPOINTMENT_APPROVED_TO_CLIENT, $appointment['phone'], $merge_fields);
        }

        $template->send();
    }

    public function applyForAppointmentCancellation($hash, $notes)
    {
        $this->db->where('hash', $hash);
        $this->db->update(db_prefix() . 'appointly_appointments', ['cancel_notes' => $notes]);

        if ($this->db->affected_rows() !== 0) {
            return [
                'response' => [
                    'message' => _l('appointments_thank_you_cancel_request'),
                    'success' => true,
                ]
            ];
        }

        return [
            'response' => [
                'message' => _l('appointments_cancel_request_failed'),
                'success' => false,
            ]
        ];
    }

    public function checkIfCancellationIsInProgress($hash)
    {
        $this->db->select('cancel_notes');
        $this->db->where('hash', $hash);
        return $this->db->get(db_prefix() . 'appointly_appointments')->row_array();
    }

    public function send_appointment_early_reminders($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);

        if ($appointment['cancelled'] == 1 || $appointment['finished'] == 1) {
            return false;
        }

        $notified_users = [];

        foreach ($appointment['attendees'] as $staff) {
            add_notification([
                'description' => 'appointment_you_have_new_appointment',
                'touserid'    => $staff['staffid'],
                'fromcompany' => true,
                'link'        => 'appointly/appointments/view?appointment_id=' . $appointment_id,
            ]);

            $notified_users[] = $staff['staffid'];

            send_mail_template(
                'appointly_appointment_cron_reminder_to_staff',
                'appointly',
                array_to_object($appointment),
                array_to_object($staff)
            );
        }

        $template = mail_template(
            'appointly_appointment_cron_reminder_to_contact',
            'appointly',
            array_to_object($appointment)
        );

        $template->send();

        pusher_trigger_notification(array_unique($notified_users));

        if (!empty($appointment['by_sms'])) {
            $merge_fields = $template->get_merge_fields();
            $this->app_sms->trigger(APPOINTLY_SMS_APPOINTMENT_APPOINTMENT_REMINDER_TO_CLIENT, $appointment['phone'], $merge_fields);
        }

        return true;
    }
        public function new_appointment_type($type, $color)
    {
        return $this->db->insert(db_prefix() . 'appointly_appointment_types', [
            'type'  => $type,
            'color' => $color,
        ]);
    }

    public function delete_appointment_type($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'appointly_appointment_types');

        header('Content-Type: application/json');
        echo json_encode(['success' => $this->db->affected_rows() !== 0]);
    }

    public function update_appointment_types($data, $meta)
    {
        $types = [];

        foreach ($data as $key => $color) {
            if (strpos($key, 'type_id') === 0) {
                $type_id = substr($key, 8);
                $types[$type_id] = ['id' => $type_id, 'color' => $color];
            }
        }

        foreach ($types as $type) {
            $this->db->where('id', $type['id']);
            $this->db->update(db_prefix() . 'appointly_appointment_types', ['color' => $type['color']]);
        }

        handleAppointlyUserMeta($meta);
    }

    public function request_appointment_feedback($appointment_id)
    {
        $appointment = $this->get_appointment_data($appointment_id);

        if (is_array($appointment) && !empty($appointment)) {
            send_mail_template('appointly_appointment_request_feedback', 'appointly', array_to_object($appointment));
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function handle_feedback_post($id, $feedback, $comment = null)
    {
        $data = ['feedback' => $feedback];

        $responsible = get_option('appointly_responsible_person') ?: 1;
        $notified_users[] = $responsible;
        $staff = appointly_get_staff($responsible);
        $appointment = $this->apm->get_appointment_data($id);

        $template = $appointment['feedback'] !== null
            ? 'appointly_appointment_feedback_updated'
            : 'appointly_appointment_feedback_received';

        $lang_key = $appointment['feedback'] !== null
            ? 'appointly_feedback_updated'
            : 'appointment_new_feedback_added';

        send_mail_template($template, 'appointly', array_to_object($staff), array_to_object($appointment));

        add_notification([
            'description' => $lang_key,
            'touserid'    => $responsible,
            'fromcompany' => true,
            'link'        => 'appointly/appointments/view?appointment_id=' . $id,
        ]);

        pusher_trigger_notification($notified_users);

        if ($comment !== null) {
            $data['feedback_comment'] = $comment;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'appointly_appointments', $data);

        return $this->db->affected_rows() !== 0;
    }

    public function insertNewOutlookEvent($data)
    {
        $last = $this->db->get(db_prefix() . 'appointly_appointments')->last_row();
        if (!$last) {
            return false;
        }

        $this->db->where('id', $last->id);
        $this->db->update(db_prefix() . 'appointly_appointments', [
            'outlook_event_id'      => $data['outlook_event_id'],
            'outlook_calendar_link' => $data['outlook_calendar_link'],
            'outlook_added_by_id'   => get_staff_user_id(),
        ]);

        return true;
    }

    public function updateAndAddExistingOutlookEvent($data)
    {
        $this->db->where('id', $data['appointment_id']);
        $this->db->update(db_prefix() . 'appointly_appointments', [
            'outlook_event_id'      => $data['outlook_event_id'],
            'outlook_calendar_link' => $data['outlook_calendar_link'],
            'outlook_added_by_id'   => get_staff_user_id(),
        ]);

        return $this->db->affected_rows() !== 0;
    }

    public function sendGoogleMeetRequestEmail($data)
    {
        $this->load->model('emails_model');
        $attendees = json_decode($data['attendees']);
        $message = $data['message'];
        $sender = appointly_get_staff($this->session->userdata('staff_user_id'));

        if (is_array($attendees) && count($attendees) > 1) {
            foreach ($attendees as $email) {
                if ($email !== $sender['email']) {
                    $this->emails_model->send_simple_email($email, _l('appointment_connect_via_google_meet'), $message);
                }
            }
        }

        return $this->emails_model->send_simple_email(
            $data['to'],
            _l('appointment_connect_via_google_meet'),
            $message
        );
    }
        private function validateRecurringData(array $original, array $data)
    {
        if (
            isset($original['repeat_every']) && $original['repeat_every'] !== '' &&
            isset($data['repeat_every']) && $data['repeat_every'] === ''
        ) {
            $data['cycles'] = 0;
            $data['total_cycles'] = 0;
            $data['last_recurring_date'] = null;
        }

        if (!empty($data['repeat_every'])) {
            $data['recurring'] = 1;

            if ($data['repeat_every'] === 'custom') {
                $data['repeat_every'] = $data['repeat_every_custom'];
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp = explode('-', $data['repeat_every']);
                $data['repeat_every'] = $_temp[0];
                $data['recurring_type'] = $_temp[1];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }

        $data['cycles'] = !isset($data['cycles']) || $data['recurring'] == 0 ? 0 : $data['cycles'];

        unset($data['repeat_type_custom'], $data['repeat_every_custom']);
        return $data;
    }

    private function validateInsertRecurring(array $data)
    {
        if (!empty($data['repeat_every'])) {
            $data['recurring'] = 1;

            if ($data['repeat_every'] === 'custom') {
                $data['repeat_every'] = $data['repeat_every_custom'];
                $data['recurring_type'] = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp = explode('-', $data['repeat_every']);
                $data['repeat_every'] = $_temp[0];
                $data['recurring_type'] = $_temp[1];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }

        unset($data['repeat_type_custom'], $data['repeat_every_custom']);
        return $data;
    }

    public function get_appointments_by_client($client_id)
    {
        $this->db->from('view_client_appointments');
        $this->db->group_start();
            $this->db->where('company_userid', $client_id);
            $this->db->or_where('contact_userid', $client_id);
        $this->db->group_end();
        $this->db->order_by('date', 'DESC');

        return $this->db->get()->result_array();
    }
}
