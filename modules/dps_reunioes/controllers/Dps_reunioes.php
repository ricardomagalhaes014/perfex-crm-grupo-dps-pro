<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reuniões online.
 *
 * Marcar, ver, responder a um convite e fechar. Nada aqui exige permissões
 * novas: quem pode abrir a lead ou o cliente pode marcar-lhe uma reunião.
 */
class Dps_reunioes extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_staff_logged_in()) {
            access_denied('dps_reunioes');
        }

        $this->load->model('dps_reunioes_model');
    }

    public function index()
    {
        $data['reunioes'] = $this->db
            ->select('r.*, CONCAT(s.firstname," ",s.lastname) AS comercial')
            ->from(db_prefix() . 'dps_reunioes r')
            ->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left')
            ->order_by('r.data_hora', 'DESC')
            ->limit(200)
            ->get()->result_array();

        $data['title'] = 'Reuniões online';
        $this->load->view('lista', $data);
    }

    /**
     * Marca a reunião. Só POST — é uma escrita.
     */
    public function marcar()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $post = $this->input->post();

        $data_hora = trim((string) ($post['data'] ?? '')) . ' ' . trim((string) ($post['hora'] ?? '')) . ':00';
        if (strtotime($data_hora) === false) {
            set_alert('danger', 'Data ou hora inválidas.');
            redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
        }

        /*
         * Marcar para trás não é erro de digitação nenhum: é uma reunião que
         * nunca vai acontecer, e o lembrete dos 30 minutos já não dispara.
         * Mais vale recusar já do que deixar o comercial à espera.
         */
        if (strtotime($data_hora) < time()) {
            set_alert('warning', 'Essa data já passou. Escolha uma data futura.');
            redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
        }

        $id = $this->dps_reunioes_model->criar([
            'rel_type'         => $post['rel_type'] ?? 'lead',
            'rel_id'           => (int) ($post['rel_id'] ?? 0),
            'assunto'          => $post['assunto'] ?? '',
            'data_hora'        => date('Y-m-d H:i:s', strtotime($data_hora)),
            'duracao_min'      => (int) ($post['duracao_min'] ?? 30),
            'staff_id'         => (int) ($post['staff_id'] ?? get_staff_user_id()),
            'convidado_id'     => $post['convidado_id'] ?? null,
            'cliente_nome'     => $post['cliente_nome'] ?? '',
            'cliente_email'    => $post['cliente_email'] ?? '',
            'cliente_telefone' => $post['cliente_telefone'] ?? '',
        ]);

        if (!$id) {
            set_alert('danger', 'Não foi possível marcar a reunião.');
            redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
        }

        $r = $this->dps_reunioes_model->get($id);

        dps_reunioes_avisar_cliente($r, 'marcada');
        dps_reunioes_convidar($r);

        log_activity('Reunião #' . $id . ' marcada com ' . $r['cliente_nome']
            . ' para ' . dps_reunioes_quando($r['data_hora']));

        set_alert('success', 'Reunião marcada. O cliente foi avisado por email e WhatsApp.');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
    }

    public function ver($id)
    {
        $data['r'] = $this->dps_reunioes_model->get($id);
        if (!$data['r']) {
            show_404();
        }

        $data['title'] = 'Reunião com ' . $data['r']['cliente_nome'];
        $this->load->view('ver', $data);
    }

    /**
     * O convidado aceita ou recusa. Só ele — nem o comercial responde por ele.
     */
    public function responder($id, $resposta)
    {
        $r = $this->dps_reunioes_model->get($id);
        if (!$r) {
            show_404();
        }

        if ((int) $r['convidado_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_reunioes');
        }

        if (!in_array($resposta, ['aceite', 'recusado'], true)) {
            show_404();
        }

        $this->dps_reunioes_model->actualizar($id, [
            'convite_estado' => $resposta,
            'convite_em'     => date('Y-m-d H:i:s'),
        ]);

        add_notification([
            'description' => get_staff_full_name() . ' ' . $resposta . ' o convite para a reunião com '
                . $r['cliente_nome'] . '.',
            'touserid'    => (int) $r['staff_id'],
            'link'        => 'dps_reunioes/ver/' . (int) $id,
            'fromcompany' => true,
        ]);

        set_alert('success', 'Resposta registada.');
        redirect(admin_url('dps_reunioes/ver/' . (int) $id));
    }

    /**
     * Fecha a reunião: realizada ou não compareceu, com a duração.
     *
     * É à mão de propósito. Nem o Jitsi nem o Meet dizem ao CRM quem entrou e
     * quanto tempo ficou sem infraestrutura própria a ouvir os eventos da
     * sala — prometer isso automático era prometer um número inventado.
     */
    public function fechar($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $r = $this->dps_reunioes_model->get($id);
        if (!$r) {
            show_404();
        }

        $estado = $this->input->post('estado');
        if (!in_array($estado, ['realizada', 'nao_compareceu', 'cancelada'], true)) {
            show_404();
        }

        $this->dps_reunioes_model->actualizar($id, [
            'estado'           => $estado,
            'duracao_real_min' => (int) $this->input->post('duracao_real_min') ?: null,
            'notas'            => $this->input->post('notas'),
        ]);

        set_alert('success', 'Reunião actualizada.');
        redirect(admin_url('dps_reunioes/ver/' . (int) $id));
    }

    /**
     * Página própria de marcação.
     *
     * Existe porque os botões que injectei por JavaScript na janela da lead
     * nunca apareceram: este tema reconstrói a barra de separadores e deita
     * fora o que lá se pendura. Um link para uma página não depende de nada
     * disso — e serve tanto a lista de leads como a ficha.
     */
    public function nova($rel_type = 'lead', $rel_id = 0)
    {
        $rel_type = in_array($rel_type, ['lead', 'customer'], true) ? $rel_type : 'lead';
        $rel_id   = (int) $rel_id;

        if (!$rel_id) {
            show_404();
        }

        if ($rel_type === 'lead') {
            $f = $this->db->select('name, email, phonenumber')->where('id', $rel_id)
                          ->get(db_prefix() . 'leads')->row_array();
            $nome = $f['name'] ?? '';
            $mail = $f['email'] ?? '';
            $tel  = $f['phonenumber'] ?? '';
        } else {
            $c = $this->db->select('company, phonenumber')->where('userid', $rel_id)
                          ->get(db_prefix() . 'clients')->row_array();
            $ct = $this->db->select('firstname, lastname, email, phonenumber')
                           ->where('userid', $rel_id)->where('is_primary', 1)
                           ->get(db_prefix() . 'contacts')->row_array();
            $nome = trim((string) ($c['company'] ?? ''))
                    ?: trim(($ct['firstname'] ?? '') . ' ' . ($ct['lastname'] ?? ''));
            $mail = $ct['email'] ?? '';
            $tel  = $ct['phonenumber'] ?? ($c['phonenumber'] ?? '');
        }

        if (!$nome && !$mail && !$tel) {
            show_404();
        }

        $data = [
            'rel_type'  => $rel_type,
            'rel_id'    => $rel_id,
            'pre_nome'  => $nome,
            'pre_email' => $mail,
            'pre_tel'   => $tel,
            'title'     => 'Marcar reunião — ' . $nome,
        ];

        $this->load->view('nova', $data);
    }
}
