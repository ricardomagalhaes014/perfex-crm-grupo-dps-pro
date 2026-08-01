<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Web Push Notifications
Description: Notificacoes Web Push nativas para o CRM Grupo DPS (PWA)
Version: 1.0.0
Author: Grupo DPS
Requires at least: 2.7.*
*/

define('DPS_WEBPUSH', 'dps_webpush');

hooks()->add_action('admin_init',              'dps_webpush_setup_menu');
hooks()->add_action('lead_created',            'dps_webpush_on_lead_created');
hooks()->add_action('after_add_task',          'dps_webpush_on_task_created');
hooks()->add_action('task_comment_added',      'dps_webpush_on_task_comment');
hooks()->add_action('after_announcement_added','dps_webpush_on_announcement');
hooks()->add_action('ticket_created',          'dps_webpush_on_ticket_created');

register_activation_hook(DPS_WEBPUSH, 'dps_webpush_activation');

function dps_webpush_activation() {
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

register_language_files(DPS_WEBPUSH, [DPS_WEBPUSH]);

function dps_webpush_setup_menu() {
    if (is_admin()) {
        $CI = &get_instance();
        if (isset($CI->app)) {
            $CI->app->add_settings_section('dps_webpush', [
                'name'     => 'Web Push (PWA)',
                'view'     => DPS_WEBPUSH . '/admin/settings/dps_webpush_settings',
                'icon'     => 'fa fa-bell',
                'position' => 95,
            ]);
        }
    }
}

function _dps_webpush_load() {
    $CI = &get_instance();
    $CI->load->library(DPS_WEBPUSH . '/Dps_webpush_module');
}

function dps_webpush_on_lead_created($id) {
    _dps_webpush_load();
    $CI = &get_instance();
    $lead_id = is_array($id) ? (isset($id['lead_id']) ? $id['lead_id'] : (isset($id['id']) ? $id['id'] : 0)) : $id;
    if ($lead_id) { $CI->dps_webpush_module->notify_lead_created($lead_id); }
}

function dps_webpush_on_task_created($id) {
    _dps_webpush_load();
    $CI = &get_instance();
    $task_id = is_array($id) ? (isset($id['task_id']) ? $id['task_id'] : (isset($id['id']) ? $id['id'] : 0)) : $id;
    if ($task_id) { $CI->dps_webpush_module->notify_task_created($task_id); }
}

function dps_webpush_on_task_comment($data) {
    _dps_webpush_load();
    $CI = &get_instance();
    $CI->dps_webpush_module->notify_task_comment($data);
}

function dps_webpush_on_announcement($id) {
    _dps_webpush_load();
    $CI = &get_instance();
    $CI->dps_webpush_module->notify_announcement($id);
}

function dps_webpush_on_ticket_created($id) {
    _dps_webpush_load();
    $CI = &get_instance();
    $CI->dps_webpush_module->notify_ticket_created($id);
}