<?php

if (!function_exists('mailflow_encryption')) {
    function mailflow_encryption($data = false, $type = 0)
    {

        try {

            $encryptConfigs = json_decode('{
    "CipherMethod": "AES-256-CBC"
}', true);

            $configsKey = "92a9314ebd9deed8608c4c6103476966e95d1feb495e00541af3e880c0f82badbff719c981f7896ee567e071ec9b509094fc1c200e75c441"; // This should be a secure, randomly generated key for encryption/decryption

            switch ($type) {
                case 0:
                    // Encryption
                    $ivLength = openssl_cipher_iv_length($encryptConfigs['CipherMethod']);
                    $iv = openssl_random_pseudo_bytes($ivLength);
                    $encryptedData = openssl_encrypt($data, $encryptConfigs['CipherMethod'], $configsKey, 0, $iv);
                    return bin2hex($iv . $encryptedData);
                    break;
                case 1:
                    // Decryption
                    $data = hex2bin($data);
                    $ivLength = openssl_cipher_iv_length($encryptConfigs['CipherMethod']);
                    $iv = substr($data, 0, $ivLength);
                    $encryptedData = substr($data, $ivLength);
                    return openssl_decrypt($encryptedData, $encryptConfigs['CipherMethod'], $configsKey, 0, $iv);
                    break;
                default:
                    return false; // Invalid type
            }


        } catch (Exception $e) {

            show_404();

        }

    }
}

if (!function_exists('mailflow_send_email')) {
    function mailflow_send_email($email, $subject, $message, $smtp_integration)
    {
        if (defined('DEMO') && DEMO) {
            return true;
        }

        $CI = &get_instance();

        $cnf = [
            'from_email' => get_option('smtp_email'),
            'from_name' => get_option('companyname'),
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
        ];

        $template = new StdClass();
        $template->message = get_option('email_header') . $cnf['message'] . get_option('email_footer');
        $template->fromname = $cnf['from_name'];
        $template->subject = $cnf['subject'];

        $template = parse_email_template($template);

        $cnf['message'] = $template->message;
        $cnf['from_name'] = $template->fromname;
        $cnf['subject'] = $template->subject;

        $cnf['message'] = check_for_links($cnf['message']);

        $cnf = hooks()->apply_filters('before_send_simple_email', $cnf);

        if (isset($cnf['prevent_sending']) && $cnf['prevent_sending'] == true) {
            return false;
        }

        $CI->load->config('email');

        if (!empty($smtp_integration) && $smtp_integration !== 'system') {

            $CI->load->model('mailflow/mailflow_model');
            $integrationData = $CI->mailflow_model->getIntegration($smtp_integration);

            $emailConfig = $CI->config->item('email');

            $emailConfig['useragent'] = 'phpmailer';
            $emailConfig['protocol'] = 'smtp';
            $emailConfig['smtp_host'] = trim($integrationData->smtp_host);

            if ($integrationData->smtp_username == '') {
                $emailConfig['smtp_user'] = trim($integrationData->email);
            } else {
                $emailConfig['smtp_user'] = trim($integrationData->smtp_username);
            }

            $emailConfig['smtp_pass'] = get_instance()->encryption->decrypt($integrationData->smtp_password);
            $emailConfig['smtp_port'] = trim($integrationData->smtp_port);
            $emailConfig['smtp_crypto'] = $integrationData->email_encryption;

            $charset = strtoupper($integrationData->email_charset);
            $charset = trim($charset);
            if ($charset == '' || strcasecmp($charset, 'utf8') == 'utf8') {
                $charset = 'utf-8';
            }

            $emailConfig['charset'] = $charset;

            $CI->email->initialize($emailConfig);
        }

        $CI->email->clear(true);
        $CI->email->set_newline(config_item('newline'));
        $CI->email->from($cnf['from_email'], $cnf['from_name']);
        $CI->email->to($cnf['email']);

        $bcc = '';
        // Used for action hooks
        if (isset($cnf['bcc'])) {
            $bcc = $cnf['bcc'];
            if (is_array($bcc)) {
                $bcc = implode(', ', $bcc);
            }
        }

        $systemBCC = get_option('bcc_emails');
        if ($systemBCC != '') {
            if ($bcc != '') {
                $bcc .= ', ' . $systemBCC;
            } else {
                $bcc .= $systemBCC;
            }
        }
        if ($bcc != '') {
            $CI->email->bcc($bcc);
        }

        if (isset($cnf['cc'])) {
            $CI->email->cc($cnf['cc']);
        }

        if (isset($cnf['reply_to'])) {
            $CI->email->reply_to($cnf['reply_to']);
        }

        $CI->email->subject($cnf['subject']);
        $CI->email->message($cnf['message']);

        $CI->email->set_alt_message(strip_html_tags($cnf['message'], '<br/>, <br>, <br />'));

        if ($CI->email->send()) {
            log_activity('Email sent to: ' . $cnf['email'] . ' Subject: ' . $cnf['subject']);

            return true;
        }

        return false;
    }
}

if (!function_exists('mailflow_get_email_integrations')) {
    function mailflow_get_email_integrations($integration = '')
    {
        $CI = &get_instance();
        $CI->load->model('mailflow/mailflow_model');

        if ($integration !== '') {

            if (is_numeric($integration)) {
                return $CI->mailflow_model->getIntegration($integration);
            }

            return "System SMTP";
        }

        $integrationsDatabaseList = $CI->mailflow_model->getIntegrations();

        $integrationsDatabaseList[] = ['id' => 'system', 'name' => 'System SMTP'];

        return $integrationsDatabaseList;
    }
}

if (!function_exists('mailflow_send_sms')) {
    function mailflow_send_sms($number, $content, bool $isCron = false)
    {
        $content = strip_tags($content);

        if (empty($number)) {
            return false;
        }

        if ($isCron) {
            app_init_sms_gateways();
        }

        $CI     = &get_instance();
        $gateway = $CI->app_sms->get_active_gateway();

        if ($gateway !== false) {
            $className = 'sms_' . $gateway['id'];

            $message = clear_textarea_breaks($content);

            $retval = $CI->{$className}->send($number, $message);

            if ($retval) {
                return true;
            }

            return false;
        }

        return false;
    }
}

if (!function_exists('mailflow_validate_phone_number')) {
    function mailflow_validate_phone_number($number)
    {
        // Regular expression to validate E.164 phone number format
        $pattern = '/^\+?[1-9]\d{1,14}$/';
        return preg_match($pattern, $number);
    }
}

if (!function_exists('mailflow_campaign_statuses')) {
    function mailflow_campaign_statuses($status = '')
    {
        $statusesList = [
            0 => [
                'name' => _l('mailflow_campaign_scheduled_status'),
                'badge' => '<span class="label project-status-allocate" style="color:#eab308;border:1px solid #fde047;background: #fffbeb;">' . _l('mailflow_campaign_scheduled_status') . '</span>',
            ],
            1 => [
                'name' => _l('mailflow_campaign_running_status'),
                'badge' => '<span class="label project-status-repair" style="color:#3b82f6;border:1px solid #93c5fd;background: #eff6ff;">' . _l('mailflow_campaign_running_status') . '</span>',
            ],
            2 => [
                'name' => _l('mailflow_campaign_completed_status'),
                'badge' => '<span class="label project-status-found" style="color:#10b981;border:1px solid #6ee7b7;background: #ecfdf5;">' . _l('mailflow_campaign_completed_status') . '</span>'
            ],
            3 => [
                'name' => _l('mailflow_campaign_failed_status'),
                'badge' => '<span class="label project-status-broken" style="color:#dc2626;border:1px solid #f87171;background: #fef2f2;">' . _l('mailflow_campaign_failed_status') . '</span>'
            ]
        ];

        if ($status !== '') {
            return $statusesList[$status];
        }

        return $statusesList;
    }
}

if (!function_exists('mailflow_human_readable_time_difference')) {
    function mailflow_human_readable_time_difference($scheduled_to)
    {
        $now = new DateTime();
        $scheduledTime = new DateTime($scheduled_to);

        if ($scheduledTime < $now) {
            return "";
        }

        $interval = $now->diff($scheduledTime);

        $parts = [];

        if ($interval->y > 0) {
            $parts[] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
        }

        if ($interval->m > 0) {
            $parts[] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
        }

        if ($interval->d > 0) {
            $parts[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
        }

        if ($interval->h > 0) {
            $parts[] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
        }

        if ($interval->i > 0) {
            $parts[] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        }

        if ($interval->s > 0 && empty($parts)) {
            $parts[] = $interval->s . ' second' . ($interval->s > 1 ? 's' : '');
        }

        return implode(' and ', $parts) . ' from now';
    }
}