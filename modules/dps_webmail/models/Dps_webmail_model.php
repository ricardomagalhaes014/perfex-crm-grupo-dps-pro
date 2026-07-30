<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_webmail_model extends CI_Model
{
    // ---------------------------------------------------------------
    // CONFIGURAÇÕES DA CONTA
    // ---------------------------------------------------------------

    /** Motivo da última falha de envio (para se mostrar ao utilizador). */
    public $ultimo_erro = '';

    public function get_config($staff_id = null)
    {
        if (!$staff_id) $staff_id = get_staff_user_id();
        $this->db->where('staff_id', $staff_id);
        return $this->db->get(db_prefix() . 'dps_webmail_config')->row_array();
    }

    public function save_config($data, $staff_id = null)
    {
        if (!$staff_id) $staff_id = get_staff_user_id();
        $existing = $this->get_config($staff_id);

        $save = [
            'staff_id'  => $staff_id,
            'email'     => $data['email'],
            'password'  => $this->_encrypt($data['password']),
            'imap_host' => $data['imap_host'] ?? 'imap.hostinger.com',
            'imap_port' => (int)($data['imap_port'] ?? 993),
            'smtp_host' => $data['smtp_host'] ?? 'smtp.hostinger.com',
            'smtp_port' => (int)($data['smtp_port'] ?? 587),
        ];

        if ($existing) {
            $this->db->where('staff_id', $staff_id);
            $this->db->update(db_prefix() . 'dps_webmail_config', $save);
        } else {
            $this->db->insert(db_prefix() . 'dps_webmail_config', $save);
        }
        return true;
    }

    // ---------------------------------------------------------------
    // LIGAÇÃO IMAP
    // ---------------------------------------------------------------

    public function get_imap_connection($config, $folder = 'INBOX')
    {
        $host = $config['imap_host'];
        $port = $config['imap_port'];
        $email = $config['email'];
        $pass = $this->_decrypt($config['password']);

        $mailbox = '{' . $host . ':' . $port . '/imap/ssl/novalidate-cert}' . $folder;

        $conn = @imap_open($mailbox, $email, $pass, 0, 1);
        if (!$conn) {
            return false;
        }
        return $conn;
    }

    public function test_connection($config)
    {
        $conn = $this->get_imap_connection($config);
        if ($conn) {
            imap_close($conn);
            return true;
        }
        return false;
    }

    // ---------------------------------------------------------------
    // PASTAS
    // ---------------------------------------------------------------

    public function get_folders($config)
    {
        $host = $config['imap_host'];
        $port = $config['imap_port'];
        $email = $config['email'];
        $pass = $this->_decrypt($config['password']);

        $mailbox = '{' . $host . ':' . $port . '/imap/ssl/novalidate-cert}';
        $conn = @imap_open($mailbox . 'INBOX', $email, $pass, 0, 1);
        if (!$conn) return [];

        $list = @imap_list($conn, $mailbox, '*');
        imap_close($conn);

        if (!$list) return [];

        $folders = [];
        foreach ($list as $folder) {
            $name = str_replace($mailbox, '', $folder);
            $folders[] = $name;
        }
        return $folders;
    }

    // ---------------------------------------------------------------
    // MENSAGENS
    // ---------------------------------------------------------------

    public function get_messages($config, $folder = 'INBOX', $page = 1, $per_page = 20)
    {
        $conn = $this->get_imap_connection($config, $this->_encode_folder($folder));
        if (!$conn) return ['messages' => [], 'total' => 0, 'error' => imap_last_error()];

        $total = imap_num_msg($conn);
        if ($total == 0) {
            imap_close($conn);
            return ['messages' => [], 'total' => 0];
        }

        // Paginação — mais recentes primeiro
        $start = max(1, $total - ($page * $per_page) + 1);
        $end   = max(1, $total - (($page - 1) * $per_page));

        $messages = [];
        for ($i = $end; $i >= $start; $i--) {
            $header = @imap_headerinfo($conn, $i);
            if (!$header) continue;

            $uid = @imap_uid($conn, $i);
            $flags = $header->Unseen === 'U' ? 'unread' : 'read';

            $from_name  = '';
            $from_email = '';
            if (!empty($header->from)) {
                $f = $header->from[0];
                $from_email = isset($f->mailbox) ? $f->mailbox . '@' . $f->host : '';
                $from_name  = isset($f->personal) ? $this->_decode_header($f->personal) : $from_email;
            }

            $messages[] = [
                'uid'        => $uid,
                'msgno'      => $i,
                'subject'    => $this->_decode_header($header->subject ?? '(sem assunto)'),
                'from_name'  => $from_name,
                'from_email' => $from_email,
                'date'       => $header->date ?? '',
                'date_ts'    => isset($header->udate) ? $header->udate : strtotime($header->date ?? ''),
                'seen'       => ($header->Unseen !== 'U'),
                'flagged'    => ($header->Flagged === 'F'),
                'has_attach' => $this->_has_attachments($conn, $i),
            ];
        }

        imap_close($conn);
        return ['messages' => $messages, 'total' => $total];
    }

    public function get_message($config, $uid, $folder = 'INBOX')
    {
        $conn = $this->get_imap_connection($config, $this->_encode_folder($folder));
        if (!$conn) return false;

        $msgno = @imap_msgno($conn, $uid);
        if (!$msgno) {
            imap_close($conn);
            return false;
        }

        $header  = @imap_headerinfo($conn, $msgno);
        $struct  = @imap_fetchstructure($conn, $msgno);

        // Marcar como lido
        @imap_setflag_full($conn, $msgno, '\\Seen');

        $body_html  = '';
        $body_plain = '';
        $attachments = [];

        $this->_parse_parts($conn, $msgno, $struct, $body_html, $body_plain, $attachments);

        $body = $body_html ?: nl2br(htmlspecialchars($body_plain));

        // Cabeçalhos
        $from_name  = '';
        $from_email = '';
        $to_list    = [];
        $cc_list    = [];

        if (!empty($header->from)) {
            $f = $header->from[0];
            $from_email = isset($f->mailbox) ? $f->mailbox . '@' . $f->host : '';
            $from_name  = isset($f->personal) ? $this->_decode_header($f->personal) : $from_email;
        }
        if (!empty($header->to)) {
            foreach ($header->to as $t) {
                $to_list[] = (isset($t->personal) ? $this->_decode_header($t->personal) . ' ' : '') . '<' . $t->mailbox . '@' . $t->host . '>';
            }
        }
        if (!empty($header->cc)) {
            foreach ($header->cc as $c) {
                $cc_list[] = (isset($c->personal) ? $this->_decode_header($c->personal) . ' ' : '') . '<' . $c->mailbox . '@' . $c->host . '>';
            }
        }

        imap_close($conn);

        return [
            'uid'         => $uid,
            'subject'     => $this->_decode_header($header->subject ?? '(sem assunto)'),
            'from_name'   => $from_name,
            'from_email'  => $from_email,
            'to'          => implode(', ', $to_list),
            'cc'          => implode(', ', $cc_list),
            'date'        => $header->date ?? '',
            'body'        => $body,
            'body_plain'  => $body_plain,
            'attachments' => $attachments,
        ];
    }

    // ---------------------------------------------------------------
    // ENVIO DE EMAIL (SMTP via PHPMailer ou mail())
    // ---------------------------------------------------------------

    public function send_email($config, $data)
    {
        $email_addr = $config['email'];
        $pass       = $this->_decrypt($config['password']);
        $smtp_host  = $config['smtp_host'];
        $smtp_port  = (int)$config['smtp_port'];

        /*
         * Onde está mesmo o PHPMailer neste Perfex: application/vendor/ (o
         * composer da aplicação). Os dois caminhos antigos — vendor/ na raiz
         * e application/third_party/ — NÃO existem aqui, por isso o envio caía
         * sempre no recurso de último caso (a classe Email do CI), que estava
         * mal configurada e falhava. Era esta a origem do "erro ao enviar".
         */
        $phpmailer_app  = FCPATH . 'application/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        $phpmailer_path = FCPATH . 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
        $perfex_mailer  = FCPATH . 'application/third_party/PHPMailer/PHPMailerAutoload.php';

        if (file_exists($phpmailer_app)) {
            require_once FCPATH . 'application/vendor/autoload.php';
            return $this->_do_phpmailer($data, $email_addr, $pass, $smtp_host, $smtp_port, $config);
        } elseif (file_exists($phpmailer_path)) {
            return $this->_send_via_phpmailer_composer($config, $data, $email_addr, $pass, $smtp_host, $smtp_port);
        } elseif (file_exists($perfex_mailer)) {
            return $this->_send_via_phpmailer_perfex($config, $data, $email_addr, $pass, $smtp_host, $smtp_port);
        } else {
            return $this->_send_via_ci_email($config, $data, $email_addr, $pass, $smtp_host, $smtp_port);
        }
    }

    private function _send_via_ci_email($config, $data, $email_addr, $pass, $smtp_host, $smtp_port)
    {
        $this->load->library('email');
        $this->email->initialize([
            'protocol'   => 'smtp',
            // A porta 465 é SSL implícito (precisa do prefixo ssl://); a 587 é
            // STARTTLS (o prefixo ssl:// aí faz a ligação encravar).
            'smtp_host'  => ($smtp_port === 465 ? 'ssl://' : '') . $smtp_host,
            'smtp_port'  => $smtp_port,
            'smtp_user'  => $email_addr,
            'smtp_pass'  => $pass,
            'smtp_crypto'=> ($smtp_port === 465 ? 'ssl' : 'tls'),
            'charset'    => 'utf-8',
            'mailtype'   => 'html',
            'newline'    => "\r\n",
        ]);

        $this->email->from($email_addr, $config['from_name'] ?? $email_addr);
        $this->email->to($data['to']);
        if (!empty($data['cc']))  $this->email->cc($data['cc']);
        if (!empty($data['bcc'])) $this->email->bcc($data['bcc']);
        $this->email->subject($data['subject']);
        $this->email->message($data['body']);

        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $att) {
                if (file_exists($att['tmp_path'])) {
                    $this->email->attach($att['tmp_path'], '', $att['name']);
                }
            }
        }

        return $this->email->send();
    }

    private function _send_via_phpmailer_perfex($config, $data, $email_addr, $pass, $smtp_host, $smtp_port)
    {
        require_once FCPATH . 'application/third_party/PHPMailer/PHPMailerAutoload.php';
        return $this->_do_phpmailer($data, $email_addr, $pass, $smtp_host, $smtp_port, $config);
    }

    private function _send_via_phpmailer_composer($config, $data, $email_addr, $pass, $smtp_host, $smtp_port)
    {
        require_once FCPATH . 'vendor/autoload.php';
        return $this->_do_phpmailer($data, $email_addr, $pass, $smtp_host, $smtp_port, $config);
    }

    private function _do_phpmailer($data, $email_addr, $pass, $smtp_host, $smtp_port, $config)
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $email_addr;
            $mail->Password   = $pass;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtp_port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($email_addr, $config['from_name'] ?? $email_addr);
            $mail->addAddress($data['to']);
            if (!empty($data['cc']))  $mail->addCC($data['cc']);
            if (!empty($data['bcc'])) $mail->addBCC($data['bcc']);
            $mail->Subject = $data['subject'];
            $mail->isHTML(true);
            $mail->Body    = $data['body'];
            $mail->AltBody = strip_tags($data['body']);

            if (!empty($data['attachments'])) {
                foreach ($data['attachments'] as $att) {
                    if (file_exists($att['tmp_path'])) {
                        $mail->addAttachment($att['tmp_path'], $att['name']);
                    }
                }
            }

            $mail->send();
            $this->ultimo_erro = '';
            return true;
        } catch (Exception $e) {
            // Guardar o motivo real: sem isto o utilizador via só "erro ao
            // enviar" e não havia forma de saber se era password errada,
            // servidor inacessível ou destinatário inválido.
            $this->ultimo_erro = $mail->ErrorInfo ?: $e->getMessage();
            log_message('error', 'DPS Webmail send error: ' . $this->ultimo_erro);
            return false;
        }
    }

    // ---------------------------------------------------------------
    // ACÇÕES SOBRE MENSAGENS
    // ---------------------------------------------------------------

    public function delete_message($config, $uid, $folder = 'INBOX')
    {
        $conn = $this->get_imap_connection($config, $this->_encode_folder($folder));
        if (!$conn) return false;

        $msgno = @imap_msgno($conn, $uid);
        if ($msgno) {
            @imap_delete($conn, $msgno);
            @imap_expunge($conn);
        }
        imap_close($conn);
        return true;
    }

    public function move_to_trash($config, $uid, $folder = 'INBOX')
    {
        return $this->move_message($config, $uid, $folder, 'Trash');
    }

    public function move_to_archive($config, $uid, $folder = 'INBOX')
    {
        // Tentar pastas comuns de arquivo
        $archive_folders = ['Archive', 'Arquivados', 'INBOX.Archive', 'Archives'];
        $folders = $this->get_folders($config);

        $target = 'Archive';
        foreach ($archive_folders as $af) {
            if (in_array($af, $folders)) {
                $target = $af;
                break;
            }
        }

        return $this->move_message($config, $uid, $folder, $target);
    }

    public function move_message($config, $uid, $from_folder, $to_folder)
    {
        $conn = $this->get_imap_connection($config, $this->_encode_folder($from_folder));
        if (!$conn) return false;

        $host    = $config['imap_host'];
        $port    = $config['imap_port'];
        $mailbox = '{' . $host . ':' . $port . '/imap/ssl/novalidate-cert}';

        $msgno = @imap_msgno($conn, $uid);
        if ($msgno) {
            @imap_mail_move($conn, $msgno, $mailbox . $this->_encode_folder($to_folder));
            @imap_expunge($conn);
        }
        imap_close($conn);
        return true;
    }

    public function mark_read($config, $uid, $folder = 'INBOX')
    {
        $conn = $this->get_imap_connection($config, $this->_encode_folder($folder));
        if (!$conn) return false;
        $msgno = @imap_msgno($conn, $uid);
        if ($msgno) @imap_setflag_full($conn, $msgno, '\\Seen');
        imap_close($conn);
        return true;
    }

    public function mark_unread($config, $uid, $folder = 'INBOX')
    {
        $conn = $this->get_imap_connection($config, $this->_encode_folder($folder));
        if (!$conn) return false;
        $msgno = @imap_msgno($conn, $uid);
        if ($msgno) @imap_clearflag_full($conn, $msgno, '\\Seen');
        imap_close($conn);
        return true;
    }

    public function get_unread_count($config, $folder = 'INBOX')
    {
        $conn = $this->get_imap_connection($config, $this->_encode_folder($folder));
        if (!$conn) return 0;
        $status = @imap_status($conn, '{' . $config['imap_host'] . ':' . $config['imap_port'] . '/imap/ssl/novalidate-cert}' . $this->_encode_folder($folder), SA_UNSEEN);
        imap_close($conn);
        return $status ? $status->unseen : 0;
    }

    // ---------------------------------------------------------------
    // HELPERS PRIVADOS
    // ---------------------------------------------------------------

    private function _encode_folder($folder)
    {
        // Mapeamento de nomes amigáveis para nomes IMAP reais
        $map = [
            'INBOX'    => 'INBOX',
            'Sent'     => 'Sent',
            'Drafts'   => 'Drafts',
            'Trash'    => 'Trash',
            'Archive'  => 'Archive',
            'Spam'     => 'Spam',
            'Junk'     => 'Junk',
        ];
        return $map[$folder] ?? $folder;
    }

    private function _decode_header($str)
    {
        if (empty($str)) return '';
        $decoded = imap_mime_header_decode($str);
        $result  = '';
        foreach ($decoded as $part) {
            $charset = strtolower($part->charset ?? 'default');
            $text    = $part->text;
            if ($charset !== 'default' && $charset !== 'utf-8') {
                $text = @iconv($charset, 'UTF-8//IGNORE', $text);
            }
            $result .= $text;
        }
        return $result;
    }

    private function _has_attachments($conn, $msgno)
    {
        $struct = @imap_fetchstructure($conn, $msgno);
        if (!$struct || empty($struct->parts)) return false;
        foreach ($struct->parts as $part) {
            if ($part->ifdisposition && strtolower($part->disposition) === 'attachment') {
                return true;
            }
        }
        return false;
    }

    private function _parse_parts($conn, $msgno, $struct, &$body_html, &$body_plain, &$attachments, $part_num = '')
    {
        if (!$struct) return;

        $type = $struct->type ?? 0;

        if (empty($struct->parts)) {
            // Parte simples
            $data = ($part_num === '')
                ? @imap_body($conn, $msgno)
                : @imap_fetchbody($conn, $msgno, $part_num);

            $encoding = $struct->encoding ?? 0;
            $data = $this->_decode_body($data, $encoding);

            $charset = 'UTF-8';
            if (!empty($struct->parameters)) {
                foreach ($struct->parameters as $p) {
                    if (strtolower($p->attribute) === 'charset') {
                        $charset = strtoupper($p->value);
                        break;
                    }
                }
            }
            if ($charset !== 'UTF-8') {
                $data = @iconv($charset, 'UTF-8//IGNORE', $data) ?: $data;
            }

            $subtype = strtolower($struct->subtype ?? '');

            // Verificar se é anexo
            if ($struct->ifdisposition && strtolower($struct->disposition) === 'attachment') {
                $filename = '';
                if (!empty($struct->dparameters)) {
                    foreach ($struct->dparameters as $p) {
                        if (strtolower($p->attribute) === 'filename') {
                            $filename = $this->_decode_header($p->value);
                        }
                    }
                }
                if (!empty($struct->parameters)) {
                    foreach ($struct->parameters as $p) {
                        if (strtolower($p->attribute) === 'name') {
                            $filename = $this->_decode_header($p->value);
                        }
                    }
                }
                $attachments[] = [
                    'name'     => $filename ?: 'anexo',
                    'part_num' => $part_num,
                    'size'     => $struct->bytes ?? 0,
                    'subtype'  => $subtype,
                ];
                return;
            }

            if ($subtype === 'html') {
                $body_html = $data;
            } elseif ($subtype === 'plain') {
                $body_plain = $data;
            }
            return;
        }

        // Multipart
        foreach ($struct->parts as $idx => $part) {
            $num = ($part_num === '') ? ($idx + 1) : ($part_num . '.' . ($idx + 1));
            $this->_parse_parts($conn, $msgno, $part, $body_html, $body_plain, $attachments, $num);
        }
    }

    private function _decode_body($data, $encoding)
    {
        switch ($encoding) {
            case 1: return imap_8bit($data);
            case 2: return imap_binary($data);
            case 3: return base64_decode($data);
            case 4: return quoted_printable_decode($data);
            default: return $data;
        }
    }

    private function _encrypt($value)
    {
        $key = md5(get_instance()->config->item('encryption_key') ?: 'dps_webmail_key');
        return base64_encode(openssl_encrypt($value, 'AES-256-CBC', $key, 0, substr($key, 0, 16)));
    }

    private function _decrypt($value)
    {
        $key = md5(get_instance()->config->item('encryption_key') ?: 'dps_webmail_key');
        return openssl_decrypt(base64_decode($value), 'AES-256-CBC', $key, 0, substr($key, 0, 16));
    }
}
