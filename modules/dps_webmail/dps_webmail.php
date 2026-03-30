<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Webmail
Description: Webmail integrado para acesso às caixas de correio @grupo-dps.com directamente no CRM
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_WEBMAIL_MODULE_NAME', 'dps_webmail');
define('DPS_WEBMAIL_IMAP_HOST', 'imap.hostinger.com');
define('DPS_WEBMAIL_IMAP_PORT', 993);
define('DPS_WEBMAIL_SMTP_HOST', 'smtp.hostinger.com');
define('DPS_WEBMAIL_SMTP_PORT', 587);

// Registar o menu lateral via admin_init (igual ao módulo DPS Imóveis)
hooks()->add_action('admin_init', 'dps_webmail_menu');

/**
 * Menu lateral — visível para TODOS os membros de staff autenticados.
 */
function dps_webmail_menu()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_webmail', [
        'slug'     => 'dps_webmail',
        'name'     => 'Webmail',
        'icon'     => 'fa fa-envelope',
        'href'     => admin_url('dps_webmail'),
        'position' => 26,
    ]);
}
