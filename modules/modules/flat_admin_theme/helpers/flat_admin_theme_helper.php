<?php

/*
Module Name: Flat Admin Theme
Description: Flat aesthetics for Perfex CRM
Version: 1.0
Author: Themesic Interactive
Author URI: https://themesic.com
Requires at least: 2.3.2
*/

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_action('app_admin_head', 'admin_theme_head_component');
hooks()->add_action('app_admin_footer', 'flat_admin_theme_footer_js__component');
hooks()->add_action('app_admin_authentication_head', 'admin_theme_staff_login');

/**
 * Staff login includes
 * @return stylesheet / script
 */
function admin_theme_staff_login()
{
    echo '<link href="' . base_url('modules/flat_admin_theme/assets/css/staff-login.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<link href="' . base_url('modules/flat_admin_theme/assets/css/font-awesome.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<script src="' . module_dir_url('flat_admin_theme', 'assets/js/sign_in.js') . '"></script>';
}


/**
 * Injects theme's CSS
 * @return null
 */
function admin_theme_head_component()
{
    echo '<link href="' . base_url('modules/flat_admin_theme/assets/css/fonts.css') . '" rel="stylesheet">';
    echo '<link href="' . base_url('modules/flat_admin_theme/assets/css/main-style.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<link href="' . base_url('modules/flat_admin_theme/assets/css/animated.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<script src="' . module_dir_url('flat_admin_theme', 'assets/js/third-party/nanobar.js') . '"></script>';
    echo '<script src="' . module_dir_url('flat_admin_theme', 'assets/js/third-party/waves076.min.js') . '"></script>';
}

/**
 * Injects theme's JS components in footer
 * @return null
 */
function flat_admin_theme_footer_js__component()
{
    echo '<script src="' . module_dir_url('flat_admin_theme', 'assets/js/admins.js') . '"></script>';
}