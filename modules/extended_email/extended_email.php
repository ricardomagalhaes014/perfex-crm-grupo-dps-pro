<?php

defined('BASEPATH') || exit('No direct script access allowed');
/*
    Module Name: Advanced Email System Module
    Module URI: https://codecanyon.net/item/webhooks-module-for-perfex-crm/39695653
    Description: Expand built-in possibilities of Perfex CRM's email system
    Version: 1.4.0
    Requires at least: 2.9.*
*/

/*
* Define module name
* Module Name Must be in CAPITAL LETTERS
*/
define('EXTENDED_EMAIL_MODULE', 'extended_email');

require __DIR__ . '/vendor/autoload.php';
//modules\extended_email\core\Apiinit::the_da_vinci_code(EXTENDED_EMAIL_MODULE);
require_once __DIR__ . '/install.php';

// get codeigniter instance
$CI = &get_instance();

/*
 *  Register activation module hook
 */
register_activation_hook(EXTENDED_EMAIL_MODULE, 'extended_email_module_activation_hook');
function extended_email_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__.'/install.php';
}

/*
*  Register language files, must be registered if the module is using languages
*/
register_language_files(EXTENDED_EMAIL_MODULE, [EXTENDED_EMAIL_MODULE]);

/*
     *  Load module helper file
    */
$CI->load->helper(EXTENDED_EMAIL_MODULE.'/extended_email');

hooks()->add_action('module_activated', 'mark_as_activated');
function mark_as_activated($module)
{
    update_option('extended_email_module_activated', 1);
}

hooks()->add_action('module_deactivated', 'mark_as_de_activated');
function mark_as_de_activated($module)
{
    update_option('extended_email_module_activated', 0);
}

require_once __DIR__ . '/includes/assets.php';
require_once __DIR__ . '/includes/sidebar_menu_links.php';

if (!is_admin()) {
    hooks()->add_filter('before_send_simple_email', 'add_config_for_simple_mail');
    function add_config_for_simple_mail($conf)
    {
        $CI = &get_instance();
        $CI->config->load('extended_email/email', true);
        $config_item        = $CI->config->item('email');
        $staff              = $CI->staff_model->get(get_staff_user_id());
        $conf['reply_to']   = $config_item['smtp_user'];
        $conf['from_email'] = $config_item['smtp_user'];
        $conf['from_name']  = get_staff_full_name(get_staff_user_id());

        return $conf;
    }

    hooks()->add_filter('email_template_from_headers', 'get_staff_mail_settings', 0, 2);
    function get_staff_mail_settings($email_settings, $template)
    {
        $CI = &get_instance();
        $CI->config->load('extended_email/email', true);
        $config_item = $CI->config->item('email');
        $email_settings['fromemail'] = $config_item['smtp_user'];
        $email_settings['fromname'] = get_staff_full_name(get_staff_user_id());

        return $email_settings;
    }
}
