<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Retorna o ID do staff logado
 */
if (!function_exists('get_staff_user_id')) {
    function get_staff_user_id()
    {
        $CI = &get_instance();
        return $CI->session->userdata('staff_user_id');
    }
}

/**
 * Verifica se o staff atual é administrador
 */
if (!function_exists('is_admin')) {
    function is_admin($staff_id = null)
    {
        $CI = &get_instance();
        return $CI->staff_model->is_admin($staff_id);
    }
}

/**
 * Verifica se o staff tem permissão
 */
if (!function_exists('staff_can')) {
    function staff_can($permission, $feature, $staff_id = null)
    {
        $CI = &get_instance();
        return $CI->staff_model->has_permission($permission, $feature, $staff_id);
    }
}

/**
 * Retorna uma opção da configuração
 */
if (!function_exists('get_option')) {
    function get_option($name, $default = '')
    {
        $CI = &get_instance();
        $value = $CI->db->select('value')->where('name', $name)->get(db_prefix() . 'options')->row('value');
        return $value !== null ? $value : $default;
    }
}

// Coloque aqui outras funções personalizadas, sempre com function_exists()