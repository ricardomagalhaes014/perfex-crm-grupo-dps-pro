<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Automacoes_dps extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        // Qualquer staff autenticado entra; as ações sensíveis continuam com
        // o seu próprio portão de admin mais abaixo.
        if (!is_staff_logged_in()) {
            access_denied('automacoes_dps');
        }
    }

    public function index()
    {
        smsc_ensure_schema();
        $data['api_url']  = smsc_api_url();
        $data['eleven_key']      = smsc_eleven_key();
        $data['eleven_phone_id'] = smsc_eleven_phone_id();
        $data['agents']          = smsc_eleven_agents();
        $data['scripts']         = $this->db->order_by('id', 'DESC')->get(db_prefix() . 'smsc_sofia_scripts')->result();
        $data['scripts']         = $this->db->order_by('id', 'DESC')->get(db_prefix() . 'smsc_scripts')->result();
        $data['is_admin']        = is_admin();
        $data['me']              = get_staff_user_id();
        $data['users']    = smsc_users();
        $data['staff']    = $this->db->select('staffid, firstname, lastname, phonenumber')
                                     ->where('active', 1)
                                     ->get(db_prefix() . 'staff')->result();
        $data['statuses'] = $this->db->get(db_prefix() . 'leads_status')->result();
        $data['autos']    = $this->db->select('a.*, s.name as status_name')
                                     ->from(db_prefix() . 'smsc_automations a')
                                     ->join(db_prefix() . 'leads_status s', 's.id = a.status_id', 'left')
                                     ->order_by('a.id', 'DESC')
                                     ->get()->result();
        $data['queue']    = $this->db->select('q.*, l.name as lead_name, st.firstname, st.lastname, ls.name as status_name')
                                     ->from(db_prefix() . 'smsc_queue q')
                                     ->join(db_prefix() . 'leads l', 'l.id = q.lead_id', 'left')
                                     ->join(db_prefix() . 'staff st', 'st.staffid = q.staff_id', 'left')
                                     ->join(db_prefix() . 'leads_status ls', 'ls.id = l.status', 'left')
                                     ->order_by('q.scheduled_at', 'DESC')
                                     ->limit(150)
                                     ->get()->result();
        $data['title'] = 'SMS Central';
        $this->load->view('automacoes_dps/manage', $data);
    }

    // ---------- Configuração ----------

    public function save_config()
    {
        $url = trim((string) $this->input->post('api_url'));
        if ($url !== '') {
            smsc_set_option('smsc_api_url', $url);
        }
        smsc_set_option('smsc_eleven_key', trim((string) $this->input->post('eleven_key')));
        smsc_set_option('smsc_eleven_phone_id', trim((string) $this->input->post('eleven_phone_id')));
        set_alert('success', 'Configuração guardada.');
        redirect(admin_url('automacoes_dps'));
    }

    public function save_user()
    {
        $staffId  = (int) $this->input->post('staff_id');
        if (!is_admin()) {
            $staffId = get_staff_user_id(); // cada comercial liga o SEU telefone ao SEU user
        }
        $login    = trim((string) $this->input->post('login'));
        $password = trim((string) $this->input->post('password'));
        $phone    = preg_replace('/\D/', '', (string) $this->input->post('phone'));
        $name     = trim((string) $this->input->post('name'));

        if (!$staffId || $login === '' || $password === '') {
            set_alert('warning', 'Preenche comercial, login e password do gateway.');
            redirect(admin_url('automacoes_dps'));
        }

        $users = smsc_users();
        $users[$staffId] = [
            'name'     => $name !== '' ? $name : ('Staff #' . $staffId),
            'login'    => $login,
            'password' => $password,
            'phone'    => $phone,
        ];
        smsc_set_option('smsc_users', json_encode($users));
        set_alert('success', 'Gateway do comercial guardado.');
        redirect(admin_url('automacoes_dps'));
    }

    public function delete_user($staffId)
    {
        if (!is_admin() && (int) $staffId !== (int) get_staff_user_id()) {
            access_denied('automacoes_dps');
        }
        $users = smsc_users();
        unset($users[(int) $staffId]);
        smsc_set_option('smsc_users', json_encode($users));
        set_alert('success', 'Gateway removido.');
        redirect(admin_url('automacoes_dps'));
    }

    // ---------- Envio manual ----------

    public function send_now()
    {
        $phone   = smsc_normalize_phone((string) $this->input->post('phone'));
        $message = trim((string) $this->input->post('message'));
        $staffId = (int) $this->input->post('sender');

        if (!$phone || $message === '') {
            set_alert('warning', 'Número ou mensagem em falta.');
            redirect(admin_url('automacoes_dps'));
        }
        $user = smsc_user_for_staff($staffId);
        if (!$user) {
            set_alert('danger', 'Nenhum gateway configurado.');
            redirect(admin_url('automacoes_dps'));
        }
        $fakeLead = (object) ['name' => ''];
        $ok = smsc_send_sms($user, $phone, smsc_render($message, $fakeLead, $user));
        set_alert($ok ? 'success' : 'danger', $ok ? 'SMS enviado por ' . $user['name'] . '.' : 'Falha no envio — verificar gateway.');
        redirect(admin_url('automacoes_dps'));
    }

    // ---------- Automações ----------

    public function automation_save()
    {
        $id       = (int) $this->input->post('id');
        $name     = trim((string) $this->input->post('name'));
        $message  = trim((string) $this->input->post('message'));
        $statusId = (int) $this->input->post('status_id');
        $days     = max(0, (int) $this->input->post('days'));
        $active   = $this->input->post('active') ? 1 : 0;
        $channel  = in_array($this->input->post('channel'), ['sms', 'email', 'sofia'], true) ? $this->input->post('channel') : 'sms';
        $subject  = trim((string) $this->input->post('subject'));
        $agentId  = trim((string) $this->input->post('agent_id'));
        $scriptId = (int) $this->input->post('script_id');
        $scriptId = (int) $this->input->post('script_id');
        if ($channel === 'sofia') {
            if (!$scriptId || $agentId === '') {
                set_alert('warning', 'Para a Sofia escolhe o agente e um guião pré-definido.');
                redirect(admin_url('automacoes_dps'));
            }
            $message = $message !== '' ? $message : '(guião pré-definido)';
        }

        if ($channel === 'sofia') {
            if ($name === '' || !$statusId || !$scriptId) {
                set_alert('warning', 'Preenche nome, estado e escolhe um guião Sofia.');
                redirect(admin_url('automacoes_dps'));
            }
            $this->db->where('id', $scriptId);
            $sc = $this->db->get(db_prefix() . 'smsc_sofia_scripts')->row();
            $message = $sc ? $sc->text : $message;
        } elseif ($name === '' || $message === '' || !$statusId) {
            set_alert('warning', 'Preenche nome, mensagem e estado.');
            redirect(admin_url('automacoes_dps'));
        }
        $row = [
            'name'      => $name,
            'channel'   => $channel,
            'subject'   => $subject !== '' ? $subject : null,
            'agent_id'  => $agentId !== '' ? $agentId : null,
            'script_id' => $scriptId ?: null,
            'script_id' => $scriptId ?: null,
            'message'   => $message,
            'status_id' => $statusId,
            'days'      => $days,
            'active'    => $active,
        ];
        if ($id) {
            $this->db->where('id', $id)->update(db_prefix() . 'smsc_automations', $row);
        } else {
            $this->db->insert(db_prefix() . 'smsc_automations', $row);
        }
        set_alert('success', 'Automação guardada.');
        redirect(admin_url('automacoes_dps'));
    }

    public function automation_toggle($id)
    {
        $this->db->query('UPDATE `' . db_prefix() . 'smsc_automations` SET active = 1 - active WHERE id = ' . (int) $id);
        redirect(admin_url('automacoes_dps'));
    }

    public function automation_delete($id)
    {
        $this->db->where('id', (int) $id)->delete(db_prefix() . 'smsc_automations');
        $this->db->where('automation_id', (int) $id)->where('status', 'Pendente')
                 ->update(db_prefix() . 'smsc_queue', ['status' => 'Cancelado']);
        set_alert('success', 'Automação eliminada.');
        redirect(admin_url('automacoes_dps'));
    }

    // Envio imediato da automação a todas as leads no estado (cap 150)
    public function automation_send($id)
    {
        @set_time_limit(0);
        $this->db->where('id', (int) $id);
        $auto = $this->db->get(db_prefix() . 'smsc_automations')->row();
        if (!$auto) {
            set_alert('danger', 'Automação não encontrada.');
            redirect(admin_url('automacoes_dps'));
        }
        $this->db->where('assigned', get_staff_user_id());
        $this->db->where('status', $auto->status_id)
                 ->where('lost', 0)
                 ->limit(150);
        $leads = $this->db->get(db_prefix() . 'leads')->result();

        $queued = 0;
        foreach ($leads as $lead) {
            $dest = smsc_destination_for($auto->channel, $lead);
            if (!$dest) {
                continue;
            }
            $this->db->insert(db_prefix() . 'smsc_queue', [
                'lead_id'       => $lead->id,
                'automation_id' => $auto->id,
                'channel'       => $auto->channel,
                'destination'   => $dest,
                'staff_id'      => (int) ($lead->assigned ?? 0),
                'requested_by'  => (int) get_staff_user_id(),
                'scheduled_at'  => date('Y-m-d H:i:s'),
                'status'        => 'Pendente',
            ]);
            $queued++;
        }
        $res = smsc_process_queue(150);
        set_alert('success', 'Automação "' . $auto->name . '": ' . $queued . ' em fila, ' . $res['sent'] . ' enviados, ' . $res['failed'] . ' falhados.');
        redirect(admin_url('automacoes_dps'));
    }

    // ---------- Guiões Sofia (apenas admin) ----------

    public function script_save()
    {
        if (!is_admin()) {
            access_denied('automacoes_dps');
        }
        smsc_ensure_schema();
        $name = trim((string) $this->input->post('name'));
        $text = trim((string) $this->input->post('text'));
        if ($name === '' || $text === '') {
            set_alert('warning', 'Preenche nome e texto do guião.');
            redirect(admin_url('automacoes_dps'));
        }
        $this->db->insert(db_prefix() . 'smsc_scripts', ['name' => $name, 'text' => $text]);
        set_alert('success', 'Guião Sofia guardado.');
        redirect(admin_url('automacoes_dps'));
    }

    public function script_delete($id)
    {
        if (!is_admin()) {
            access_denied('automacoes_dps');
        }
        $this->db->where('id', (int) $id)->delete(db_prefix() . 'smsc_scripts');
        set_alert('success', 'Guião eliminado.');
        redirect(admin_url('automacoes_dps'));
    }

    // ---------- Fila ----------

    public function process_queue()
    {
        @set_time_limit(0);
        $res = smsc_process_queue(150);
        set_alert('success', 'Fila processada: ' . $res['sent'] . ' enviados, ' . $res['failed'] . ' falhados, ' . $res['processed'] . ' itens.');
        redirect(admin_url('automacoes_dps'));
    }

    public function queue_cancel($id)
    {
        $this->db->where('id', (int) $id)->where('status', 'Pendente')
                 ->update(db_prefix() . 'smsc_queue', ['status' => 'Cancelado']);
        redirect(admin_url('automacoes_dps'));
    }
}
