<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Webmail
Description: Webmail integrado para acesso às caixas de correio @grupo-dps.com directamente no CRM
Version: 1.0.0
Requires at least: 2.3.*
*/

define('DPS_WEBMAIL_MODULE_NAME', 'dps_webmail');
define('DPS_WEBMAIL_IMAP_HOST', 'imap.hostinger.com');
define('DPS_WEBMAIL_IMAP_PORT', 993);
define('DPS_WEBMAIL_SMTP_HOST', 'smtp.hostinger.com');
define('DPS_WEBMAIL_SMTP_PORT', 587);

hooks()->add_action('admin_init', 'dps_webmail_init');
hooks()->add_action('app_menu_client_area_before_logout', 'dps_webmail_menu_item');

function dps_webmail_init()
{
    $CI = &get_instance();
    $CI->load->helper('dps_webmail');
}

function dps_webmail_menu_item()
{
    // Menu item adicionado via register_menu_items
}

hooks()->add_action('register_menu_items', 'dps_webmail_register_menu');

function dps_webmail_register_menu()
{
    $CI = &get_instance();
    if (is_staff_logged_in()) {
        add_menu_item([
            'slug'     => 'dps_webmail',
            'name'     => 'Webmail',
            'icon'     => 'fa fa-envelope',
            'href'     => admin_url('dps_webmail'),
            'position' => 25,
            'parent'   => null,
        ]);
    }
}
