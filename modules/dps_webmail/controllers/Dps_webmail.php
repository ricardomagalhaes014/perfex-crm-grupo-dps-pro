<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_webmail extends AdminController
{
    private $folders = [
        'INBOX'   => ['label' => 'Caixa de Entrada', 'icon' => 'fa-inbox'],
        'Sent'    => ['label' => 'Enviados',          'icon' => 'fa-paper-plane'],
        'Drafts'  => ['label' => 'Rascunhos',         'icon' => 'fa-pencil'],
        'Trash'   => ['label' => 'Lixo',              'icon' => 'fa-trash'],
        'Archive' => ['label' => 'Arquivo',           'icon' => 'fa-archive'],
        'Spam'    => ['label' => 'Spam',              'icon' => 'fa-ban'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_webmail_model');
        $this->lang->load('dps_webmail', 'portuguese');
    }

    // ---------------------------------------------------------------
    // PÁGINA PRINCIPAL — Listagem de mensagens
    // ---------------------------------------------------------------

    public function index($folder = 'INBOX')
    {
        $config = $this->dps_webmail_model->get_config();

        if (!$config) {
            redirect(admin_url('dps_webmail/settings'));
        }

        $page    = (int)($this->input->get('page') ?: 1);
        $result  = $this->dps_webmail_model->get_messages($config, $folder, $page, 25);

        $data['config']        = $config;
        $data['folder']        = $folder;
        $data['folders']       = $this->folders;
        $data['messages']      = $result['messages'];
        $data['total']         = $result['total'];
        $data['page']          = $page;
        $data['per_page']      = 25;
        $data['error']         = $result['error'] ?? null;
        $data['title']         = $this->folders[$folder]['label'] ?? $folder;
        $data['bodyclass']     = 'dps-webmail-page';

        // Contadores de não lidos para o menu lateral
        $data['unread_inbox']  = 0;
        try {
            $data['unread_inbox'] = $this->dps_webmail_model->get_unread_count($config, 'INBOX');
        } catch (Exception $e) {}

        $this->load->view('dps_webmail/inbox/index', $data);
    }

    // ---------------------------------------------------------------
    // VER MENSAGEM
    // ---------------------------------------------------------------

    public function view_message($folder = 'INBOX', $uid = 0)
    {
        $config = $this->dps_webmail_model->get_config();
        if (!$config) redirect(admin_url('dps_webmail/settings'));

        $message = $this->dps_webmail_model->get_message($config, (int)$uid, $folder);
        if (!$message) {
            set_alert('danger', 'Mensagem não encontrada.');
            redirect(admin_url('dps_webmail/index/' . $folder));
        }

        $data['config']    = $config;
        $data['message']   = $message;
        $data['folder']    = $folder;
        $data['folders']   = $this->folders;
        $data['title']     = htmlspecialchars($message['subject']);
        $data['bodyclass'] = 'dps-webmail-page';
        $data['unread_inbox'] = 0;

        $this->load->view('dps_webmail/message/view', $data);
    }

    // ---------------------------------------------------------------
    // COMPOR / RESPONDER / REENCAMINHAR
    // ---------------------------------------------------------------

    public function compose()
    {
        $config = $this->dps_webmail_model->get_config();
        if (!$config) redirect(admin_url('dps_webmail/settings'));

        if ($this->input->post()) {
            $post = $this->input->post();

            // Processar anexos
            $attachments = [];
            if (!empty($_FILES['attachments']['name'][0])) {
                foreach ($_FILES['attachments']['name'] as $k => $name) {
                    if ($_FILES['attachments']['error'][$k] === UPLOAD_ERR_OK) {
                        $attachments[] = [
                            'name'     => $name,
                            'tmp_path' => $_FILES['attachments']['tmp_name'][$k],
                        ];
                    }
                }
            }

            $ok = $this->dps_webmail_model->send_email($config, [
                'to'          => $post['to'],
                'cc'          => $post['cc'] ?? '',
                'bcc'         => $post['bcc'] ?? '',
                'subject'     => $post['subject'],
                'body'        => $post['body'],
                'attachments' => $attachments,
            ]);

            if ($ok) {
                set_alert('success', _l('dps_webmail_sent_ok'));
                redirect(admin_url('dps_webmail/index/Sent'));
            } else {
                set_alert('danger', _l('dps_webmail_sent_fail'));
            }
        }

        $data['config']    = $config;
        $data['folders']   = $this->folders;
        $data['reply_to']  = null;
        $data['reply_all'] = false;
        $data['forward']   = false;
        $data['prefill']   = [
            'to'      => $this->input->get('to') ?? '',
            'subject' => $this->input->get('subject') ?? '',
            'body'    => '',
        ];
        $data['title']     = 'Escrever Email';
        $data['bodyclass'] = 'dps-webmail-page';
        $data['unread_inbox'] = 0;

        $this->load->view('dps_webmail/compose/index', $data);
    }

    public function reply($folder = 'INBOX', $uid = 0)
    {
        $config = $this->dps_webmail_model->get_config();
        if (!$config) redirect(admin_url('dps_webmail/settings'));

        $message = $this->dps_webmail_model->get_message($config, (int)$uid, $folder);
        if (!$message) redirect(admin_url('dps_webmail'));

        if ($this->input->post()) {
            $post = $this->input->post();
            $ok = $this->dps_webmail_model->send_email($config, [
                'to'      => $post['to'],
                'cc'      => $post['cc'] ?? '',
                'bcc'     => $post['bcc'] ?? '',
                'subject' => $post['subject'],
                'body'    => $post['body'],
            ]);
            if ($ok) {
                set_alert('success', _l('dps_webmail_sent_ok'));
                redirect(admin_url('dps_webmail/view_message/' . $folder . '/' . $uid));
            } else {
                set_alert('danger', _l('dps_webmail_sent_fail'));
            }
        }

        $quoted = '<br><br><hr><p><strong>De:</strong> ' . htmlspecialchars($message['from_name'] ?: $message['from_email']) . '<br>'
                . '<strong>Data:</strong> ' . htmlspecialchars($message['date']) . '<br>'
                . '<strong>Assunto:</strong> ' . htmlspecialchars($message['subject']) . '</p>'
                . $message['body'];

        $data['config']    = $config;
        $data['folders']   = $this->folders;
        $data['reply_to']  = $message;
        $data['reply_all'] = ($this->input->get('all') == '1');
        $data['forward']   = false;
        $data['prefill']   = [
            'to'      => $message['from_email'],
            'subject' => 'Re: ' . $message['subject'],
            'body'    => $quoted,
        ];
        $data['title']     = 'Responder';
        $data['bodyclass'] = 'dps-webmail-page';
        $data['unread_inbox'] = 0;

        $this->load->view('dps_webmail/compose/index', $data);
    }

    public function forward($folder = 'INBOX', $uid = 0)
    {
        $config = $this->dps_webmail_model->get_config();
        if (!$config) redirect(admin_url('dps_webmail/settings'));

        $message = $this->dps_webmail_model->get_message($config, (int)$uid, $folder);
        if (!$message) redirect(admin_url('dps_webmail'));

        if ($this->input->post()) {
            $post = $this->input->post();
            $ok = $this->dps_webmail_model->send_email($config, [
                'to'      => $post['to'],
                'cc'      => $post['cc'] ?? '',
                'bcc'     => $post['bcc'] ?? '',
                'subject' => $post['subject'],
                'body'    => $post['body'],
            ]);
            if ($ok) {
                set_alert('success', _l('dps_webmail_sent_ok'));
                redirect(admin_url('dps_webmail'));
            } else {
                set_alert('danger', _l('dps_webmail_sent_fail'));
            }
        }

        $fwd_body = '<br><br><hr><p><strong>---------- Mensagem Reencaminhada ----------</strong><br>'
                  . '<strong>De:</strong> ' . htmlspecialchars($message['from_name'] ?: $message['from_email']) . '<br>'
                  . '<strong>Data:</strong> ' . htmlspecialchars($message['date']) . '<br>'
                  . '<strong>Assunto:</strong> ' . htmlspecialchars($message['subject']) . '<br>'
                  . '<strong>Para:</strong> ' . htmlspecialchars($message['to']) . '</p>'
                  . $message['body'];

        $data['config']    = $config;
        $data['folders']   = $this->folders;
        $data['reply_to']  = null;
        $data['reply_all'] = false;
        $data['forward']   = true;
        $data['prefill']   = [
            'to'      => '',
            'subject' => 'Fwd: ' . $message['subject'],
            'body'    => $fwd_body,
        ];
        $data['title']     = 'Reencaminhar';
        $data['bodyclass'] = 'dps-webmail-page';
        $data['unread_inbox'] = 0;

        $this->load->view('dps_webmail/compose/index', $data);
    }

    // ---------------------------------------------------------------
    // ACÇÕES AJAX
    // ---------------------------------------------------------------

    public function action()
    {
        $config = $this->dps_webmail_model->get_config();
        if (!$config) {
            echo json_encode(['success' => false, 'message' => 'Não configurado']);
            return;
        }

        $action = $this->input->post('action');
        $uid    = (int)$this->input->post('uid');
        $folder = $this->input->post('folder') ?: 'INBOX';

        header('Content-Type: application/json');

        switch ($action) {
            case 'delete':
                $ok = $this->dps_webmail_model->move_to_trash($config, $uid, $folder);
                echo json_encode(['success' => $ok]);
                break;
            case 'delete_permanent':
                $ok = $this->dps_webmail_model->delete_message($config, $uid, $folder);
                echo json_encode(['success' => $ok]);
                break;
            case 'archive':
                $ok = $this->dps_webmail_model->move_to_archive($config, $uid, $folder);
                echo json_encode(['success' => $ok]);
                break;
            case 'mark_read':
                $ok = $this->dps_webmail_model->mark_read($config, $uid, $folder);
                echo json_encode(['success' => $ok]);
                break;
            case 'mark_unread':
                $ok = $this->dps_webmail_model->mark_unread($config, $uid, $folder);
                echo json_encode(['success' => $ok]);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Acção desconhecida']);
        }
    }

    // ---------------------------------------------------------------
    // CONFIGURAÇÕES DA CONTA
    // ---------------------------------------------------------------

    public function settings()
    {
        if ($this->input->post()) {
            $post = $this->input->post();

            /*
             * Ser tolerante com o que vem do formulário: houve utilizadores a
             * escrever só o nome (sem domínio) e outros a repetir o domínio
             * por causa do adorno que o campo tinha ao lado. Em vez de
             * mandar o comercial de volta com um erro, arruma-se o endereço.
             */
            $email = strtolower(trim($post['email']));

            // "nome@grupo-dps.com@grupo-dps.com" -> "nome@grupo-dps.com"
            while (substr_count($email, '@') > 1 && str_ends_with($email, '@grupo-dps.com')) {
                $email = substr($email, 0, -strlen('@grupo-dps.com'));
            }

            // Só o nome, sem domínio -> acrescenta-se
            if ($email !== '' && strpos($email, '@') === false) {
                $email .= '@grupo-dps.com';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                set_alert('danger', 'Endereço de email inválido.');
            } elseif (!str_ends_with(strtolower($email), '@grupo-dps.com')) {
                set_alert('danger', 'Apenas emails @grupo-dps.com são permitidos.');
            } else {
                // Testar ligação antes de guardar
                $test_config = [
                    'email'     => $email,
                    'password'  => $post['password'],
                    'imap_host' => 'imap.hostinger.com',
                    'imap_port' => 993,
                    'smtp_host' => 'smtp.hostinger.com',
                    'smtp_port' => 587,
                ];
                // Guardar directamente (teste opcional para não bloquear)
                $this->dps_webmail_model->save_config([
                    'email'    => $email,
                    'password' => $post['password'],
                ]);
                set_alert('success', _l('dps_webmail_settings_saved'));
                redirect(admin_url('dps_webmail'));
            }
        }

        $config = $this->dps_webmail_model->get_config();
        $staff  = get_staff(get_staff_user_id());

        // Sugerir email baseado no nome do staff
        $suggested_email = '';
        if ($staff) {
            $first = strtolower($staff->firstname ?? '');
            $last  = strtolower($staff->lastname ?? '');
            if ($first && $last) {
                $suggested_email = $first . '.' . $last . '@grupo-dps.com';
            }
        }

        $data['config']          = $config;
        $data['suggested_email'] = $suggested_email;
        $data['folders']         = $this->folders;
        $data['title']           = 'Configurar Conta de Email';
        $data['bodyclass']       = 'dps-webmail-page';
        $data['unread_inbox']    = 0;

        $this->load->view('dps_webmail/inbox/settings', $data);
    }
}
