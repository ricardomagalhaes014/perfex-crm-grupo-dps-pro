<?php

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Retorna todos os colaboradores que não são administradores.
 *
 * @return array
 */
function get_all_staff_members()
{
    $CI = &get_instance();
    $CI->db->where('admin', 0);

    return $CI->db->get(db_prefix() . 'staff')->result_array();
}

/**
 * Verifica se um colaborador está ativo.
 *
 * @param int|null $staffID
 * @return bool
 */
function is_staff_active($staffID)
{
    if (empty($staffID)) {
        return false;
    }

    $CI = &get_instance();
    $CI->load->model('staff_model');

    $staff = $CI->staff_model->get($staffID);

    return ($staff && $staff->active == 1);
}

/**
 * Retorna as configurações SMTP personalizadas do colaborador, se existirem.
 *
 * @param int $staffId
 * @return array
 */
function get_config_array($staffId)
{
    $CI = &get_instance();

    $CI->load->model('extended_email/Extended_email_model', 'extended_email_model');

    $config = ['has_setting' => false];

    $email_settings = $CI->extended_email_model->get_staff_extended_email_settings($staffId);

    if (
        $email_settings &&
        !empty($email_settings->smtp_password) &&
        !empty($email_settings->smtp_host) &&
        (is_staff_active($staffId) || is_admin())
    ) {
        $config['has_setting']  = true;
        $config['useragent']    = $email_settings->mail_engine;
        $config['protocol']     = $email_settings->email_protocol;
        $config['smtp_host']    = trim($email_settings->smtp_host);
        $config['smtp_user']    = trim($email_settings->smtp_username) ?: trim($email_settings->email);
        $config['smtp_pass']    = $CI->encryption->decrypt($email_settings->smtp_password);
        $config['smtp_port']    = trim($email_settings->smtp_port);
        $config['smtp_crypto']  = $email_settings->smtp_encryption;

        // Charset
        $charset = strtoupper(trim($email_settings->email_charset));
        $config['charset'] = (empty($charset) || strcasecmp($charset, 'UTF8') === 0) ? 'utf-8' : $charset;

        $config['mailtype'] = 'html';
    }

    return $config;
}

/* End of file extended_email_helper.php */