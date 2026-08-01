<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_webpush_module {
    private $ci;
    private $vapid_public;
    private $vapid_private;
    private $enabled;

    public function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->database();
        $this->ci->load->model('leads_model');
        $this->ci->load->model('tasks_model');
        $this->enabled       = get_option('dps_webpush_enabled') == '1';
        $this->vapid_public  = get_option('dps_webpush_vapid_public');
        $this->vapid_private = get_option('dps_webpush_vapid_private');
    }

    public function notify_lead_created($id) {
        if (!$this->enabled || get_option('dps_webpush_notify_leads') != '1') return;
        $lead = $this->ci->leads_model->get($id);
        if (!$lead) return;
        $title = 'Nova Lead: ' . $lead->name;
        $body  = ($lead->email ? $lead->email : '') . ($lead->phonenumber ? ' | ' . $lead->phonenumber : '');
        $url   = admin_url('leads/index/' . $id);
        $this->send_to_all_staff($title, $body, $url, 'lead');
    }

    public function notify_task_created($id) {
        if (!$this->enabled || get_option('dps_webpush_notify_tasks') != '1') return;
        $task = $this->ci->tasks_model->get($id);
        if (!$task) return;
        $title = 'Nova Tarefa: ' . $task->name;
        $body  = $task->description ? strip_tags(substr($task->description, 0, 100)) : '';
        $url   = admin_url('tasks/view/' . $id);
        $assignees = $this->ci->tasks_model->get_task_assignees($id);
        if (!empty($assignees)) {
            foreach ($assignees as $assignee) {
                $this->send_to_staff($assignee->staffid, $title, $body, $url, 'task');
            }
        } else {
            $this->send_to_all_staff($title, $body, $url, 'task');
        }
    }

    public function notify_task_comment($data) {
        if (!$this->enabled || get_option('dps_webpush_notify_comments') != '1') return;
        $task_id = isset($data['task_id']) ? $data['task_id'] : (isset($data['taskid']) ? $data['taskid'] : 0);
        if (!$task_id) return;
        $task = $this->ci->tasks_model->get($task_id);
        if (!$task) return;
        $title = 'Comentario na Tarefa: ' . $task->name;
        $body  = isset($data['content']) ? strip_tags(substr($data['content'], 0, 100)) : '';
        $url   = admin_url('tasks/view/' . $task_id);
        $this->send_to_all_staff($title, $body, $url, 'task_comment');
    }

    public function notify_announcement($id) {
        if (!$this->enabled || get_option('dps_webpush_notify_announcements') != '1') return;
        $announcement = $this->ci->db->where('announcementid', $id)->get('tbl_announcements')->row();
        if (!$announcement) return;
        $title = 'Comunicado: ' . $announcement->name;
        $body  = strip_tags(substr($announcement->message, 0, 120));
        $url   = admin_url('announcements');
        $this->send_to_all_staff($title, $body, $url, 'announcement');
    }

    public function notify_ticket_created($id) {
        if (!$this->enabled || get_option('dps_webpush_notify_tickets') != '1') return;
        $this->ci->load->model('tickets_model');
        $ticket = $this->ci->tickets_model->get_ticket_by_id($id);
        if (!$ticket) return;
        $title = 'Novo Ticket #' . $id . ': ' . $ticket->subject;
        $body  = strip_tags(substr($ticket->message, 0, 100));
        $url   = admin_url('tickets/ticket/' . $id);
        $this->send_to_all_staff($title, $body, $url, 'ticket');
    }

    public function send_to_staff($staff_id, $title, $body, $url = '/admin', $type = 'general') {
        $this->ci->db->where('staff_id', $staff_id);
        $subscriptions = $this->ci->db->get('tbl_push_subscriptions')->result();
        foreach ($subscriptions as $sub) { $this->_send_push($sub, $title, $body, $url, $type); }
    }

    public function send_to_all_staff($title, $body, $url = '/admin', $type = 'general') {
        $subscriptions = $this->ci->db->get('tbl_push_subscriptions')->result();
        foreach ($subscriptions as $sub) { $this->_send_push($sub, $title, $body, $url, $type); }
    }

    private function _send_push($sub, $title, $body, $url, $type) {
        $payload = json_encode([
            'title'     => $title,
            'body'      => $body,
            'url'       => $url,
            'type'      => $type,
            'timestamp' => time() * 1000,
            'tag'       => 'dps-crm-' . $type . '-' . time()
        ]);
        $subscription = json_encode([
            'endpoint' => $sub->endpoint,
            'keys'     => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth]
        ]);
        $python_script = '/home/u172337921/domains/crm.grupo-dps.com/public_html/send_push.py';
        if (file_exists($python_script) && function_exists('shell_exec')) {
            $cmd = '/usr/bin/python3 ' . escapeshellarg($python_script) . ' '
                 . escapeshellarg($subscription) . ' '
                 . escapeshellarg($payload) . ' 2>&1';
            $output = shell_exec($cmd);
            log_activity('DPS WebPush: ' . ($output ? trim($output) : 'sent'));
        }
    }
}