<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Telegram Chat | Chat do Telegram
Description: Módulo padrão para envio de chat do Telegram
Version: 1.0.0
Requires at least: 2.3.*
Author: Unknown | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
*/

define('TELEGRAM_MODULE_NAME', 'telegram_chat');
$CI = &get_instance();

/**
 * Register activation module hook
 */
register_activation_hook(TELEGRAM_MODULE_NAME, 'telegram_chat_module_activation_hook');

function telegram_chat_module_activation_hook()
{
    $CI = &get_instance();
    require_once (__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(TELEGRAM_MODULE_NAME, [TELEGRAM_MODULE_NAME]);



hooks()->add_action('admin_init', 'telegram_module_init_menu_items');


/**
 * Init telegram module menu items in setup in admin_init hook
 * @return null
 */
function telegram_module_init_menu_items()
{
    $CI = &get_instance();

    $CI->app->add_quick_actions_link([
            'name'       => 'telegram',
            'permission' => 'telegram_chat',
            'url'        => 'telegram_chat',
            'position'   => 79,
            ]);

    if (has_permission('telegram_chat', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('utilities', [
                'slug'     => 'telegram_chat',
                'name'     => 'Telegram chat',
                'href'     => admin_url('telegram_chat'),
                'position' => 36,
        ]);
    }
}