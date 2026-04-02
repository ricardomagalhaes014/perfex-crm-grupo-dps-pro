<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_chatbot extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_chatbot/dps_chatbot_model');
    }

    public function index()
    {
        if (!staff_can('view', DPS_CHATBOT_MODULE_NAME) && !is_admin()) {
            access_denied(DPS_CHATBOT_MODULE_NAME);
        }

        $data['title'] = _l('dps_chatbot');
        $data['groups'] = $this->dps_chatbot_model->get_groups();
        $data['staff'] = $this->staff_model->get();
        
        $this->load->view('dps_chatbot/manage', $data);
    }

    public function create_group()
    {
        if (!is_admin() && !staff_can('manage_groups', DPS_CHATBOT_MODULE_NAME)) {
            access_denied(DPS_CHATBOT_MODULE_NAME);
        }

        $payload = [
            'name' => $this->input->post('name', true),
            'description' => $this->input->post('description', true),
            'is_public' => (int) $this->input->post('is_public')
        ];

        $group_id = $this->dps_chatbot_model->create_group($payload);
        if ($group_id) {
            set_alert('success', _l('added_successfully', _l('dps_chatbot_group')));
        }

        redirect(admin_url('dps_chatbot'));
    }

    public function add_member()
    {
        if (!is_admin() && !staff_can('manage_groups', DPS_CHATBOT_MODULE_NAME)) {
            echo json_encode(['status' => 'error', 'message' => 'Sem permissão']);
            return;
        }
        $group_id = $this->input->post('group_id');
        $staff_id = $this->input->post('staff_id');
        if (!$group_id || !$staff_id) {
            echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
            return;
        }
        if ($this->dps_chatbot_model->is_group_member($group_id, $staff_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Já é membro do grupo']);
            return;
        }
        $this->dps_chatbot_model->add_group_member($group_id, $staff_id);
        echo json_encode(['status' => 'success']);
    }

    public function remove_member()
    {
        if (!is_admin() && !staff_can('manage_groups', DPS_CHATBOT_MODULE_NAME)) {
            echo json_encode(['status' => 'error', 'message' => 'Sem permissão']);
            return;
        }
        $group_id = $this->input->post('group_id');
        $staff_id = $this->input->post('staff_id');
        $this->dps_chatbot_model->remove_group_member($group_id, $staff_id);
        echo json_encode(['status' => 'success']);
    }

    public function get_group_members()
    {
        $group_id = $this->input->get('group_id');
        $members = $this->dps_chatbot_model->get_group_members($group_id);
        echo json_encode($members);
    }

    public function send_message()
    {
        $payload = [
            'message' => $this->input->post('message', true),
            'receiver_id' => $this->input->post('receiver_id') ?: null,
            'group_id' => $this->input->post('group_id') ?: null
        ];

        $message_id = $this->dps_chatbot_model->send_message($payload);
        if ($message_id) {
            echo json_encode(['status' => 'success', 'message_id' => $message_id]);
        } else {
            echo json_encode(['status' => 'error']);
        }
    }

    public function get_messages()
    {
        $receiver_id = $this->input->get('receiver_id');
        $group_id = $this->input->get('group_id');
        $messages = $this->dps_chatbot_model->get_messages($receiver_id, $group_id);
        echo json_encode($messages);
    }
}
