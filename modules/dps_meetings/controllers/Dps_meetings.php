<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_meetings extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_meetings/dps_meetings_model');
    }

    public function index()
    {
        if (!staff_can('view', DPS_MEETINGS_MODULE_NAME) && !is_admin()) {
            access_denied(DPS_MEETINGS_MODULE_NAME);
        }

        $data['title']    = _l('dps_meetings');
        $data['meetings'] = $this->dps_meetings_model->get_all();
        $staff = $this->staff_model->get();
        $data['staff'] = [];
        foreach ($staff as $member) {
            $data['staff'][] = ['id' => $member['staffid'], 'name' => trim($member['firstname'] . ' ' . $member['lastname'])];
        }
        $data['meeting']  = null;

        $this->load->view('dps_meetings/manage', $data);
    }

    public function edit($id)
    {
        if (!staff_can('edit', DPS_MEETINGS_MODULE_NAME) && !is_admin()) {
            access_denied(DPS_MEETINGS_MODULE_NAME);
        }

        $data['title']    = _l('dps_meeting_edit');
        $data['meetings'] = $this->dps_meetings_model->get_all();
        $staff = $this->staff_model->get();
        $data['staff'] = [];
        foreach ($staff as $member) {
            $data['staff'][] = ['id' => $member['staffid'], 'name' => trim($member['firstname'] . ' ' . $member['lastname'])];
        }
        $data['meeting']  = $this->dps_meetings_model->get($id);

        if (!$data['meeting']) {
            show_404();
        }

        $this->load->view('dps_meetings/manage', $data);
    }

    public function save()
    {
        $id = $this->input->post('id');

        if ($id) {
            if (!staff_can('edit', DPS_MEETINGS_MODULE_NAME) && !is_admin()) {
                access_denied(DPS_MEETINGS_MODULE_NAME);
            }
        } else {
            if (!staff_can('create', DPS_MEETINGS_MODULE_NAME) && !is_admin()) {
                access_denied(DPS_MEETINGS_MODULE_NAME);
            }
        }

        $payload = [
            'title'         => $this->input->post('title', true),
            'provider'      => $this->input->post('provider', true),
            'meeting_url'   => $this->input->post('meeting_url', false),
            'room_name'     => $this->input->post('room_name', true),
            'related_type'  => $this->input->post('related_type', true),
            'related_id'    => (int) $this->input->post('related_id'),
            'contact_name'  => $this->input->post('contact_name', true),
            'contact_email' => $this->input->post('contact_email', true),
            'host_staff_id' => (int) $this->input->post('host_staff_id'),
            'start_at'      => to_sql_date($this->input->post('start_at', true), true),
            'end_at'        => to_sql_date($this->input->post('end_at', true), true),
            'status'        => $this->input->post('status', true),
            'description'   => $this->input->post('description', false),
            'result_notes'  => $this->input->post('result_notes', false),
        ];

        if (empty($payload['meeting_url'])) {
            $payload['meeting_url'] = dps_meetings_build_url($payload['provider'], $payload['room_name']);
        }

        if ($id) {
            $ok = $this->dps_meetings_model->update($id, $payload);
            set_alert($ok ? 'success' : 'warning', $ok ? _l('updated_successfully', _l('dps_meeting')) : _l('problem_updating'));
        } else {
            $newId = $this->dps_meetings_model->add($payload);
            set_alert($newId ? 'success' : 'warning', $newId ? _l('added_successfully', _l('dps_meeting')) : _l('problem_adding'));
        }

        redirect(admin_url('dps_meetings'));
    }

    public function delete($id)
    {
        if (!staff_can('delete', DPS_MEETINGS_MODULE_NAME) && !is_admin()) {
            access_denied(DPS_MEETINGS_MODULE_NAME);
        }

        $ok = $this->dps_meetings_model->delete($id);
        set_alert($ok ? 'success' : 'warning', $ok ? _l('deleted', _l('dps_meeting')) : _l('problem_deleting'));
        redirect(admin_url('dps_meetings'));
    }
}
