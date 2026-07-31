<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_envio_massa extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_admin() && !staff_can('view', 'dps_envio_massa')) {
            access_denied('dps_envio_massa');
        }
        $this->load->model('dps_webmail/dps_webmail_model');
    }

    // ---------------------------------------------------------------
    // PÁGINA PRINCIPAL — Formulário de envio em massa
    // ---------------------------------------------------------------
    public function index()
    {
        $p          = db_prefix();
        $staff_id   = get_staff_user_id();
        $is_admin   = is_admin();

        // Estados das tarefas no Perfex
        $task_statuses = [
            1 => 'Não Iniciada',
            2 => 'Em Progresso',
            3 => 'Em Revisão',
            4 => 'Concluída',
            5 => 'Cancelada',
        ];

        $data['title']          = 'Envio em Massa';
        $data['task_statuses']  = $task_statuses;
        $data['is_admin']       = $is_admin;
        $data['staff_id']       = $staff_id;

        $this->load->view('dps_envio_massa/form', $data);
    }

    // ---------------------------------------------------------------
    // AJAX — Obter destinatários com base no estado da tarefa
    // ---------------------------------------------------------------
    public function get_destinatarios()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $p          = db_prefix();
        $staff_id   = (int)get_staff_user_id();
        $is_admin   = is_admin();
        $status     = (int)$this->input->post('task_status');

        if ($status < 1 || $status > 5) {
            echo json_encode(['success' => false, 'message' => 'Estado inválido.']);
            return;
        }

        // Filtro por comercial: admin vê todas, comercial vê só as suas
        $staff_filter = '';
        if (!$is_admin) {
            $staff_filter = "AND ta.staffid = {$staff_id}";
        }

        // Obter tarefas com o estado seleccionado e os emails dos leads/contactos associados
        $sql = "
            SELECT DISTINCT
                t.id          AS task_id,
                t.name        AS task_name,
                t.status      AS task_status,
                t.rel_type,
                t.rel_id,
                ta.staffid,
                CONCAT(s.firstname, ' ', s.lastname) AS comercial,
                CASE
                    WHEN t.rel_type = 'lead'    THEN l.name
                    WHEN t.rel_type = 'customer' THEN CONCAT(c.firstname, ' ', c.lastname)
                    ELSE t.name
                END AS contacto_nome,
                CASE
                    WHEN t.rel_type = 'lead'    THEN l.email
                    WHEN t.rel_type = 'customer' THEN c.email
                    ELSE NULL
                END AS contacto_email
            FROM {$p}tasks t
            INNER JOIN {$p}task_assigned ta ON ta.taskid = t.id
            INNER JOIN {$p}staff s ON s.staffid = ta.staffid
            LEFT JOIN {$p}leads l ON l.id = t.rel_id AND t.rel_type = 'lead'
            LEFT JOIN {$p}contacts c ON c.userid = t.rel_id AND t.rel_type = 'customer'
            WHERE t.status = {$status}
            {$staff_filter}
            HAVING contacto_email IS NOT NULL AND contacto_email != ''
            ORDER BY comercial ASC, contacto_nome ASC
        ";

        $result = $this->db->query($sql);
        $rows   = $result ? $result->result_array() : [];

        // Remover duplicados por email
        $seen      = [];
        $destinatarios = [];
        foreach ($rows as $row) {
            $email = trim(strtolower($row['contacto_email']));
            if (!isset($seen[$email])) {
                $seen[$email] = true;
                $destinatarios[] = [
                    'task_id'    => $row['task_id'],
                    'task_name'  => $row['task_name'],
                    'nome'       => $row['contacto_nome'],
                    'email'      => $row['contacto_email'],
                    'comercial'  => $row['comercial'],
                    'rel_type'   => $row['rel_type'],
                ];
            }
        }

        echo json_encode([
            'success'       => true,
            'destinatarios' => $destinatarios,
            'total'         => count($destinatarios),
        ]);
    }

    // ---------------------------------------------------------------
    // POST — Processar envio em massa
    // ---------------------------------------------------------------
    public function enviar()
    {
        if (!$this->input->post()) {
            redirect(admin_url('dps_envio_massa'));
        }

        if (!is_admin() && !staff_can('create', 'dps_envio_massa')) {
            set_alert('danger', 'Sem permissão para enviar emails.');
            redirect(admin_url('dps_envio_massa'));
        }

        $p          = db_prefix();
        $staff_id   = (int)get_staff_user_id();
        $is_admin   = is_admin();
        $status     = (int)$this->input->post('task_status');
        $assunto    = $this->input->post('assunto');
        $corpo      = $this->input->post('corpo');
        $emails_raw = $this->input->post('emails_selecionados');

        if (empty($assunto) || empty($corpo) || empty($emails_raw)) {
            set_alert('danger', 'Por favor preencha todos os campos obrigatórios.');
            redirect(admin_url('dps_envio_massa'));
        }

        // Processar emails seleccionados
        $emails_lista = array_filter(array_map('trim', explode(',', $emails_raw)));
        if (empty($emails_lista)) {
            set_alert('danger', 'Nenhum destinatário seleccionado.');
            redirect(admin_url('dps_envio_massa'));
        }

        // Processar upload do documento
        $attachment = null;
        if (!empty($_FILES['documento']['name'])) {
            $upload_dir = FCPATH . 'uploads/modules/dps_envio_massa/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $ext       = pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION);
            $safe_name = 'doc_' . time() . '_' . uniqid() . '.' . $ext;
            $dest      = $upload_dir . $safe_name;

            if (move_uploaded_file($_FILES['documento']['tmp_name'], $dest)) {
                $attachment = [
                    'tmp_path' => $dest,
                    'name'     => $_FILES['documento']['name'],
                ];
            }
        }

        // Obter configuração SMTP do webmail
        $config = $this->dps_webmail_model->get_config();
        if (!$config) {
            set_alert('danger', 'Configuração de email não encontrada. Configure o Webmail primeiro.');
            redirect(admin_url('dps_envio_massa'));
        }

        // Enviar emails
        $enviados  = 0;
        $falhados  = [];

        foreach ($emails_lista as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $mail_data = [
                'to'          => $email,
                'subject'     => $assunto,
                'body'        => nl2br(htmlspecialchars_decode($corpo)),
                'attachments' => $attachment ? [$attachment] : [],
            ];

            $ok = $this->dps_webmail_model->send_email($config, $mail_data);
            if ($ok) {
                $enviados++;
            } else {
                $falhados[] = $email;
            }
        }

        // Limpar ficheiro temporário após envio
        if ($attachment && file_exists($attachment['tmp_path'])) {
            unlink($attachment['tmp_path']);
        }

        // Resultado
        if ($enviados > 0) {
            $msg = "Enviado com sucesso para {$enviados} destinatário(s).";
            if (!empty($falhados)) {
                $msg .= ' Falhou para: ' . implode(', ', $falhados);
            }
            set_alert('success', $msg);
        } else {
            set_alert('danger', 'Falha no envio. Verifique a configuração SMTP no Webmail.');
        }

        redirect(admin_url('dps_envio_massa'));
    }
}
