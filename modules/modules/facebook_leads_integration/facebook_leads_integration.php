<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Facebook leads integration | Integração de Leads do Facebook
Description: Sincronize leads entre Facebook Leads e Perfex Leads
Version: 1.0.0
Requires at least: 2.3.*
Author: Themesic Interactive | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
*/

define('FACEBOOK_LEADS_INTEGRATION_MODULE_NAME', 'facebook_leads_integration');

hooks()->add_action('admin_init', 'facebook_leads_integration_module_init_menu_items');
hooks()->add_action('admin_init', 'add_settings_tab');
hooks()->add_action('admin_init', 'exclude_uri');


// exclude urls from csrf
function exclude_uri() {

    $CI = &get_instance();
    $CI->load->config('migration');
    $update_info = $CI->config->item('migration_version');
    if(!get_option('current_perfex_version'))
    {
        update_option('current_perfex_version',$update_info);
    }
    if(!get_option('excluded_uri_for_facebook_leads_integration_once') || get_option('current_perfex_version') != $update_info)
    {
        
        
        $myfile = fopen(APPPATH."config/config.php", "a") or die("Unable to open file!");
        $txt = "if(!isset(\$config['csrf_exclude_uris']))
        {
            \$config['csrf_exclude_uris']=[];
        }";
        fwrite($myfile, "\n". $txt);
        $txt = "\$config['csrf_exclude_uris'] = array_merge(\$config['csrf_exclude_uris'],array('facebook_leads_integration/webhook'));";
        fwrite($myfile, "\n". $txt);
        $txt = "\$config['csrf_exclude_uris'] = array_merge(\$config['csrf_exclude_uris'],array('facebook_leads_integration/get_lead_data'));";
        fwrite($myfile, "\n". $txt);
        fclose($myfile);
        update_option('current_perfex_version',$update_info);
        update_option('excluded_uri_for_facebook_leads_integration_once', 1);
    }
    
    
}

function add_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('facebook_leads_integration', [
        'name'     => _l('facebook_leads_integration'),
        'view'     => FACEBOOK_LEADS_INTEGRATION_MODULE_NAME . '/facebook_leads_integration_view',
        'position' => 101,
    ]);
}


/**
 * Register activation module hook
 */
register_activation_hook(FACEBOOK_LEADS_INTEGRATION_MODULE_NAME, 'facebook_leads_integration_module_activation_hook');

function facebook_leads_integration_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(FACEBOOK_LEADS_INTEGRATION_MODULE_NAME, [FACEBOOK_LEADS_INTEGRATION_MODULE_NAME]);

/**
 * Init FACEBOOK LEADS INTEGRATION module menu items in setup in admin_init hook
 * @return null
 */
function facebook_leads_integration_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
        'name'       => _l('facebook_leads_integration'),
        'permission' => 'facebook_leads_integration',
        'url'        => 'facebook_leads_integration',
        'position'   => 69,
    ]);
}


