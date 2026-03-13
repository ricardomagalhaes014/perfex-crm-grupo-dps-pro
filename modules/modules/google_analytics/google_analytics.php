<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Google Analytics | Módulo de Google Analytics para Perfex CRM
Description: Este módulo permite a integração do Google Analytics com o Perfex CRM.
Version: 1.0
Requires at least: 2.3.
Author: unknown | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
*/

define('google_analytics_MODULE_NAME', 'google_analytics');

$CI = &get_instance();

/**
 * Load the module helper
 */
$CI->load->helper(google_analytics_MODULE_NAME . '/google_analytics');

/**
 * Register activation module hook
 */
register_activation_hook(google_analytics_MODULE_NAME, 'google_analytics_activation_hook');

function google_analytics_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(google_analytics_MODULE_NAME, [google_analytics_MODULE_NAME]);

/**
 * Actions for inject the custom styles
 */
hooks()->add_action('app_admin_footer', 'google_analytics_admin_head');
hooks()->add_action('app_customers_footer', 'google_analytics_clients_area_head');
hooks()->add_filter('module_google_analytics_action_links', 'module_google_analytics_action_links');
hooks()->add_action('admin_init', 'google_analytics_init_menu_items');

/**
 * Add additional settings for this module in the module list area
 * @param  array $actions current actions
 * @return array
 */
function module_google_analytics_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('google_analytics') . '">' . _l('settings') . '</a>';

    return $actions;
}
/**
 * Admin area applied styles
 * @return null
 */
function google_analytics_admin_head()
{
    google_analytics_script('google_analytics_admin_area');
}

/**
 * Clients area theme applied styles
 * @return null
 */
function google_analytics_clients_area_head()
{
    google_analytics_script('google_analytics_clients_area');
}

/**
 * Custom CSS
 * @param  string $main_area clients or admin area options
 * @return null
 */
function google_analytics_script($main_area)
{
    $clients_or_admin_area             = get_option($main_area);
    if (get_option('google_analytics') == 'enable') {
        $google_analytics_admin_and_clients_area = get_option('google_analytics_clients_and_admin_area');
        if (!empty($clients_or_admin_area) || !empty($google_analytics_admin_and_clients_area)) {
            if (!empty($clients_or_admin_area)) {
                $clients_or_admin_area = html_entity_decode(clear_textarea_breaks($clients_or_admin_area));
                echo $clients_or_admin_area . PHP_EOL;
            }
            if (!empty($google_analytics_admin_and_clients_area)) {
                $google_analytics_admin_and_clients_area = html_entity_decode(clear_textarea_breaks($google_analytics_admin_and_clients_area));
                echo $google_analytics_admin_and_clients_area . PHP_EOL;
            }
        }
    }
}

/**
 * Init theme style module menu items in setup in admin_init hook
 * @return null
 */
function google_analytics_init_menu_items()
{
    if (is_admin()) {
        $CI = &get_instance();
        /**
         * If the logged in user is administrator, add custom menu in Setup
         */
        $CI->app_menu->add_setup_menu_item('google-analytics', [
            'href'     => admin_url('google_analytics'),
            'name'     => _l('google_analytics'),
            'position' => 66,
        ]);
    }
}