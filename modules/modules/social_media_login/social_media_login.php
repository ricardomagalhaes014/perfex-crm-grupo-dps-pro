<?php

/**
 * Ensures that the module init file can't be accessed directly, only within the application.
 */
defined('BASEPATH') or exit('No direct script access allowed');

/*
	Module Name: Social Media Login module | Módulo de login de mídia social
	Description: Permita que os clientes se registrem e façam login no Perfex CRM por meio de suas contas do Google, Facebook, LinkedIn e Twitter.
	Version: 1.0.0
	Requires at least: 2.3.*
	Author: Themesic Interactive | Grupo Liquida I.A no Telegram
	Author URI: https://t.me/crmperfex
*/

define('SOCIAL_MEDIA_LOGIN_MODULE_NAME', 'social_media_login');

hooks()->add_action('before_client_logout','google_session_logout');
hooks()->add_action('before_client_logout','facebook_session_logout');
hooks()->add_action('before_client_logout','linkedin_session_logout');
hooks()->add_action('before_client_logout','twitter_session_logout');

register_activation_hook(SOCIAL_MEDIA_LOGIN_MODULE_NAME, 'social_media_login_activation_hook');
register_deactivation_hook(SOCIAL_MEDIA_LOGIN_MODULE_NAME, 'social_media_login_deactivation_hook');

register_language_files(SOCIAL_MEDIA_LOGIN_MODULE_NAME, [SOCIAL_MEDIA_LOGIN_MODULE_NAME]);

/**
 * Add additional settings for this module in the module list area
 * @param  array $actions current actions
 * @return array
 */
hooks()->add_filter('module_social_media_login_action_links', 'module_social_media_login_action_links');

function module_social_media_login_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=social_media_login') . '">' . _l('social_login_menu_name') . '</a>';

    return $actions;
}

/*
 * Check if can have permissions then apply new tab in settings
 */
hooks()->add_action('admin_init', 'social_media_login_add_settings_tab');

/**
 * [social_media_login_add_settings_tab net menu item in setup->settings].
 *
 * @return void
 */
function social_media_login_add_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('social_media_login', [
        'name' => _l('social_login_menu_name'),
        'view' => 'social_media_login/settings',
        'position' => 36,
    ]);
}

function google_session_logout()
{
	redirect(site_url('social_media_login/google_logout'));
}

function facebook_session_logout()
{
	redirect(site_url('social_media_login/facebook_logout'));
}

function linkedin_session_logout()
{
	redirect(site_url('social_media_login/linkedin_logout'));
}

function twitter_session_logout()
{
	redirect(site_url('social_media_login/twitter_logout'));
}

function social_media_login_activation_hook()
{
	$curent_theme = FCPATH."/application/views/themes/".active_clients_theme()."/views/login.php";
    $my_login = FCPATH."/application/views/themes/".active_clients_theme()."/views/my_login.php";

    copy($curent_theme, $my_login);

    $filename = $my_login;
    $code_add = file_get_contents(__DIR__."/views/code.php");
    $string_to_replace = "<?php if(get_option('allow_registration') == 1) { ?>";
    $replace_with = "<?php if(get_option('allow_registration') == 1) { ?>\n".$code_add;
    $content = file_get_contents($filename);
    $content_chunks = explode($string_to_replace, $content);
    $content = implode($replace_with, $content_chunks);
    file_put_contents($filename, $content);
    $css_add = "<link href='<?php echo module_dir_url('social_media_login', 'assets/css/custom.css'); ?>' rel='stylesheet'>";
    file_put_contents($filename, $css_add . PHP_EOL, FILE_APPEND);

    $options = array(
        'google_key' => "",
        'google_id' => "",
        'google_btn_status' => "Inactive",
        'linkedin_key' => "",
        'linkedin_id' => "",
        'linkedin_btn_status' => "Inactive",
        'twitter_key' => "",
        'twitter_id' => "",
        'twitter_btn_status' => "Inactive",
        'facebook_key' => "",
        'facebook_id' => "",
        'facebook_btn_status' => "Inactive",
        'social_media_login_module_status' => "Inactive"
    );
    
    foreach ($options as $key => $value)
    {
        update_option($key, $value);
    }
}

function social_media_login_deactivation_hook()
{
	$File   = FCPATH.'/application/views/themes/perfex/views/my_login.php';
	if(file_exists($File))
	{
		unlink($File);
	}
	
}

hooks()->add_action('app_init',SOCIAL_MEDIA_LOGIN_MODULE_NAME.'_actLib');
function social_media_login_actLib()
{
    /*$CI = & get_instance();
    $CI->load->library(SOCIAL_MEDIA_LOGIN_MODULE_NAME.'/Envapi');
    $envato_res = $CI->envapi->validatePurchase(SOCIAL_MEDIA_LOGIN_MODULE_NAME);
    if (!$envato_res) {
        set_alert('danger', "One of your modules failed its verification and got deactivated. Please reactivate or contact support.");
        redirect(admin_url('modules'));
    }*/
}

hooks()->add_action('pre_activate_module', SOCIAL_MEDIA_LOGIN_MODULE_NAME.'_sidecheck');
function social_media_login_sidecheck($module_name)
{
    /**if ($module_name['system_name'] == SOCIAL_MEDIA_LOGIN_MODULE_NAME) {
        if (!option_exists(SOCIAL_MEDIA_LOGIN_MODULE_NAME.'_verified') && empty(get_option(SOCIAL_MEDIA_LOGIN_MODULE_NAME.'_verified')) && !option_exists(SOCIAL_MEDIA_LOGIN_MODULE_NAME.'_verification_id') && empty(get_option(SOCIAL_MEDIA_LOGIN_MODULE_NAME.'_verification_id'))) {
            $CI = & get_instance();
            $data['submit_url'] = $module_name['system_name'].'/env_ver/activate';
            $data['original_url'] = admin_url('modules/activate/'.SOCIAL_MEDIA_LOGIN_MODULE_NAME);
            $data['module_name'] = SOCIAL_MEDIA_LOGIN_MODULE_NAME;
            $data['title'] = "Module activation";
            echo $CI->load->view($module_name['system_name'].'/activate', $data, true);
            exit();
        }
    }*/
}

hooks()->add_action('pre_deactivate_module', SOCIAL_MEDIA_LOGIN_MODULE_NAME.'_deregister');
function social_media_login_deregister($module_name)
{
    if ($module_name['system_name'] == SOCIAL_MEDIA_LOGIN_MODULE_NAME) {
        delete_option(SOCIAL_MEDIA_LOGIN_MODULE_NAME."_verified");
        delete_option(SOCIAL_MEDIA_LOGIN_MODULE_NAME."_verification_id");
        delete_option(SOCIAL_MEDIA_LOGIN_MODULE_NAME."_last_verification");
        if(file_exists(__DIR__."/config/token.php")){
            unlink(__DIR__."/config/token.php");
        }
    }
}