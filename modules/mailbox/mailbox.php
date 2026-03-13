<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Mailbox
Module URI: https://codecanyon.net/item/mailbox-webmail-client-for-perfex-crm/25308081
Description: Mailbox is a webmail client for Perfex's dashboard.
Version: 2.0.3
Requires at least: 3.0
Author: Themesic Interactive
Author URI: https://1.envato.market/themesic
*/

define('MAILBOX_MODULE', 'mailbox');
define('MAILBOX_MODULE_UPLOAD_FOLDER', module_dir_path(MAILBOX_MODULE, 'uploads'));
require_once __DIR__.'/vendor/autoload.php';

hooks()->add_action('after_cron_run', 'scan_email_server');
hooks()->add_action('app_admin_head', 'mailbox_add_head_components');
hooks()->add_action('app_admin_footer', 'mailbox_load_js');
hooks()->add_action('admin_init', 'mailbox_add_settings_tab');
hooks()->add_action('admin_init', 'mailbox_module_init_menu_items');
hooks()->add_filter('migration_tables_to_replace_old_links', 'mailbox_migration_tables_to_replace_old_links');
hooks()->add_action('after_lead_lead_tabs', 'mailbox_after_lead_lead_tabs');
hooks()->add_action('after_lead_tabs_content', 'mailbox_after_lead_tabs_content');

/**
 * Injects CSS for mailbox.
 *
 * @return null
 */
function mailbox_add_head_components()
{
    if ('1' == get_option('mailbox_enabled')) {
        $CI = &get_instance();
        echo '<link href="'.base_url('modules/mailbox/assets/css/mailbox_styles.css').'?v='.$CI->app_scripts->core_version().'" rel="stylesheet" type="text/css" />';
    }
}

/**
 * Injects JavaScript for mailbox.
 *
 * @return null
 */
function mailbox_load_js()
{
    if ('1' == get_option('mailbox_enabled')) {
        $CI = &get_instance();
        echo '<script src="'.module_dir_url('mailbox', 'assets/js/mailbox_js.js').'?v='.$CI->app_scripts->core_version().'"></script>';
    }
}

/**
 * Initialize mailbox module menu items in setup.
 *
 * @return null
 */
function mailbox_module_init_menu_items()
{
    $CI = &get_instance();
    if ('1' == get_option('mailbox_enabled')) {
        $badge = '';
        $num_unread = total_rows(db_prefix().'mail_inbox', ['read' => '0', 'to_staff_id' => get_staff_user_id()]);
        if ($num_unread > 0) {
            $badge = ' • '.total_rows(db_prefix().'mail_inbox', ['read' => '0', 'to_staff_id' => get_staff_user_id()]).'';
        }

        $CI->app_menu->add_sidebar_menu_item('mailbox', [
            'name'     => _l('mailbox').$badge,
            'href'     => admin_url('mailbox'),
            'icon'     => 'fa fa-envelope-square',
            'position' => 6,
        ]);
    }
}

/**
 * Initialize mailbox module settings tab.
 *
 * @return null
 */
function mailbox_add_settings_tab()
{
    $CI = &get_instance();
    if (isset($CI->app)) {
        $CI->app->add_settings_section('mailbox-settings', [
            'name'     => _l('mailbox_setting'),
            'view'     => 'mailbox/mailbox_settings',
            'icon'     => 'fa fa-envelope',
            'position' => 36,
        ]);
    }
}

/**
 * Migrate mailbox-related tables to replace old links.
 *
 * @param array $tables
 *
 * @return array
 */
function mailbox_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix().'mail_inbox',
                'field' => 'description',
            ];

    return $tables;
}

/**
 * Scan mailbox from the email server.
 *
 * @return bool
 */
function scan_email_server()
{
    $enabled      = get_option('mailbox_enabled');
    $imap_server  = get_option('mailbox_imap_server');
    $encryption   = get_option('mailbox_encryption');
    $folder_scan  = get_option('mailbox_folder_scan');
    $check_every  = '1';
    $unseen_email = get_option('mailbox_only_loop_on_unseen_emails');

    if ($enabled && !empty($imap_server)) {
        $CI = &get_instance();
        $CI->db->select()
            ->from(db_prefix().'staff')
            ->where(db_prefix().'staff.mail_password !=', '');
        $staffs = $CI->db->get()->result_array();

        if (file_exists(APPPATH . 'third_party/php-imap/Imap.php')) {
            require_once APPPATH . 'third_party/php-imap/Imap.php';
        } else {
            trigger_error('File not found: Imap.php', E_USER_WARNING);
            return false;
        }
        
        include_once APPPATH.'third_party/simple_html_dom.php';

        foreach ($staffs as $staff) {
            $last_run    = $staff['last_email_check'];
            $staff_email = $staff['email'];
            $staff_id    = $staff['staffid'];
            $email_pass  = $staff['mail_password'];

            if (empty($last_run) || (time() > $last_run + ($check_every * 60))) {
                $CI->db->where('staffid', $staff_id);
                $CI->db->update(db_prefix().'staff', [
                    'last_email_check' => time(),
                ]);

                $imap = new Imap($imap_server, $staff_email, $email_pass, $encryption);

                if (!$imap->isConnected()) {
                    log_activity('Failed to connect to IMAP for email: '.$staff_email);
                    continue;
                }

                $folder_scan = $folder_scan ?: 'Inbox';
                $imap->selectFolder($folder_scan);

                $emails = $unseen_email ? $imap->getUnreadMessages() : $imap->getMessages();

                foreach ($emails as $email) {
                    $plainTextBody = $imap->getPlainTextBody($email['uid']);
                    $email['body'] = trim($plainTextBody) ?: $email['body'];
                    $email['body'] = handle_google_drive_links_in_text($email['body']);
                    $email['body'] = prepare_imap_email_body_html($email['body']);
                    
                    // Process attachments
                    $data = ['attachments' => []];
                    if (isset($email['attachments'])) {
                        foreach ($email['attachments'] as $key => $at) {
                            $_at_name = $email['attachments'][$key]['name'];
                            unset($email['attachments'][$key]['name']);
                            $email['attachments'][$key]['filename'] = $_at_name;
                            $email['attachments'][$key]['data']     = $imap->getAttachment($email['uid'], $key)['content'];
                        }
                        $data['attachments'] = $email['attachments'];
                    }

                    // Process sender and recipients
                    $data['fromname'] = trim(preg_replace('/(.*)<(.*)>/', '\\1', $email['from']));
                    $data['to'] = array_map(function($to) {
                        return trim(preg_replace('/(.*)<(.*)>/', '\\2', $to));
                    }, $email['to'] ?? []);
                    $data['cc'] = array_map(function($cc) {
                        return trim(preg_replace('/(.*)<(.*)>/', '\\2', $cc));
                    }, $email['cc'] ?? []);

                    $inbox = [
                        'from_email'    => preg_replace('/(.*)<(.*)>/', '\\2', $email['from']),
                        'to'            => implode(',', $data['to']),
                        'cc'            => implode(',', $data['cc']),
                        'sender_name'   => $data['fromname'],
                        'subject'       => $email['subject'],
                        'body'          => $email['body'],
                        'to_staff_id'   => $staff_id,
                        'date_received' => date('Y-m-d H:i:s'),
                        'folder'        => 'inbox'
                    ];

                    $CI->db->insert(db_prefix().'mail_inbox', $inbox);
                    $inbox_id = $CI->db->insert_id();
                    $path = MAILBOX_MODULE_UPLOAD_FOLDER.'/inbox/'.$inbox_id.'/';

                    // Handle attachments
                    foreach ($data['attachments'] as $attachment) {
                        $filename = unique_filename($path, $attachment['filename']);
                        file_put_contents($path.$filename, $attachment['data']);
                        
                        $matt = [
                            'mail_id'   => $inbox_id,
                            'type'      => 'inbox',
                            'file_name' => $filename,
                            'file_type' => get_mime_by_extension($filename)
                        ];
                        $CI->db->insert(db_prefix().'mail_attachment', $matt);
                    }

                    if ($inbox_id && $imap->setUnseenMessage($email['uid'])) {
                        log_activity('Email processed and marked as unseen: '.$email['subject']);
                    }
                }
            }
        }
    }

    return false;
}

/**
 * Load the module helper.
 */
$CI = &get_instance();
$CI->load->helper(MAILBOX_MODULE.'/mailbox');

/**
 * Activation function for the mailbox module.
 */
register_activation_hook(MAILBOX_MODULE, 'mailbox_activation_hook');
function mailbox_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__.'/install.php';
}

/**
 * Register language files for the mailbox module.
 */
register_language_files(MAILBOX_MODULE, [MAILBOX_MODULE]);

/**
 * Library and setup for the mailbox.
 */
hooks()->add_action('app_init', MAILBOX_MODULE.'_actLib');
function mailbox_actLib()
{
    $CI = &get_instance();
    $CI->load->library(MAILBOX_MODULE.'/Mailbox_aeiou');
}

/**
 * Handle module pre-activation and deregistration events.
 */
hooks()->add_action('pre_activate_module', MAILBOX_MODULE.'_sidecheck');
hooks()->add_action('pre_deactivate_module', MAILBOX_MODULE.'_deregister');
function mailbox_sidecheck($module_name) { /* No action needed */ }
function mailbox_deregister($module_name) { /* Cleanup options */ }