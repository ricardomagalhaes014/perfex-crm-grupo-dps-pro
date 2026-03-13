<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Ultimate Dark Theme | Tema Escuro Definitivo
Description: Tema escuro definitivo para Perfex CRM
Version: 2.2.0
Author: Dweb Digital Solutions | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
Requires at least: 2.3.2
*/

define('ULTIMATE_DARK_THEME_MODULE_NAME', 'ultimate_dark_theme');
define('ULTIMATE_DARK_THEME_CSS', module_dir_path(ULTIMATE_DARK_THEME_MODULE_NAME, 'assets/css/theme_styles.css'));

$CI = &get_instance();

register_activation_hook(ULTIMATE_DARK_THEME_MODULE_NAME, 'ultimate_dark_theme_activation_hook');

function ultimate_dark_theme_activation_hook()
{
	require(__DIR__ . '/install.php');
}
$CI->load->helper(ULTIMATE_DARK_THEME_MODULE_NAME . '/ultimate_dark_theme');
