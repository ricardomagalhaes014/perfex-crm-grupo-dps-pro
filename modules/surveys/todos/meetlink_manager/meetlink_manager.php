<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: MeetLink Manager
Description: Easily schedule and manage meetings with custom URLs for various platforms.
Version: 1.0.0
Requires at least: 2.3.*
Author: Hopperstack
Author URI: https://codecanyon.net/user/hopperstack/portfolio
*/

//Module name
define('MEETLINK_MANAGER_MODULE_NAME', 'meetlink_manager');


// Get codeigniter instance
$CI = &get_instance();

// Register activation module hook
register_activation_hook(MEETLINK_MANAGER_MODULE_NAME, 'meetlink_manager_module_activation_hook');
function meetlink_manager_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__.'/install.php';
}

// Register language files, must be registered if the module is using languages
register_language_files(MEETLINK_MANAGER_MODULE_NAME, [MEETLINK_MANAGER_MODULE_NAME]);

// Load module helper file
$CI->load->helper(MEETLINK_MANAGER_MODULE_NAME.'/meetlink_manager');



//inject permissions Feature and Capabilities for meetlink module
hooks()->add_filter('staff_permissions', 'meetlink_manager_module_permissions_for_staff');
function meetlink_manager_module_permissions_for_staff($permissions)
{
    $viewGlobalName      = _l('permission_view').'('._l('permission_global').')';
    $allPermissionsArray = [
        'view'     => $viewGlobalName,
        'create'   => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete'   => _l('permission_delete'),
        'view_service'   => _l('permission_view_service').'('._l('permission_global').')',
        'create_service'   => _l('permission_create_service'),
        'edit_service'   => _l('permission_edit_service'),
        'delete_service'   => _l('permission_delete_service'),

    ];
    $permissions['meetlink_manager'] = [
                'name'         => _l('meetlink_manager'),
                'capabilities' => $allPermissionsArray,
            ];

    return $permissions;
}

// Inject sidebar menu and links for meetlink_manager module
hooks()->add_action('admin_init', 'meetlink_manager_module_init_menu_items');
function meetlink_manager_module_init_menu_items()
{
    $CI = &get_instance();
    if (has_permission('meetlink_manager', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('meetlink_manager', [
            'slug'     => 'meetlink_manager',
            'name'     => _l('meetlink_manager'),
            'icon'     => 'fa fa-users',
            'href'     => '#',
            'position' => 30,
        ]);
    }

    if (has_permission('meetlink_manager', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('meetlink_manager', [
            'slug'     => 'meetings',
            'name'     => _l('meetings'),
            'href'     => admin_url('meetlink_manager'),
            'position' => 1,
        ]);
    }

    if (has_permission('meetlink_manager', '', 'view_service')) {
        $CI->app_menu->add_sidebar_children_item('meetlink_manager', [
            'slug'     => 'meeting_services',
            'name'     => _l('meeting_services'),
            'href'     => admin_url('meetlink_manager/services'),
            'position' => 5,
        ]);
    }
    if (is_admin()) {   
        $CI->app_menu->add_setup_menu_item('meetlink_manager', [
            'slug' => 'meetlink-setting',
            'name' => _l('settings_meetlink'),
            'href' => admin_url('meetlink_manager/settings'),
            'position' => 35,
        ]);
    }

 
}


// Add Menu In Customer Side
hooks()->add_action('customers_navigation_start', 'add_meeting_menu');
function add_meeting_menu()
{
    if (0 == get_option('meetlink_manager_menu_disabled') && is_client_logged_in()) {
        echo '<li class="customers-nav-item-contracts">
            <a href="'.site_url('meetlink_manager/client').'">'._l('meetlink_manager').'</a>
        </li>';
    }
}

// Add settings menu(tab menu) In Admin Side


hooks()->add_action('settings_tab_footer', 'add_meetlink_manager_setting_footer');
function add_meetlink_manager_setting_footer($tab)
{
    if ($tab['slug'] == "meetlink_manager") {
        echo '<script type="text/javascript">
                $(function() {
                    $(`select.selectpicker.tax`).removeAttr("multiple");
                    $(`select.selectpicker.tax`).parent().removeClass("show-tick");
                    init_selectpicker();
                });
            </script>';
    }
}


// Inject email template for meetlink module
hooks()->add_action('after_email_templates', 'meetlink_add_email_template_send');
function meetlink_add_email_template_send()
{
    $CI                        = &get_instance();
    $data['hasPermissionEdit'] = has_permission('email_templates', '', 'edit');
    $data['meetlink']            = $CI->emails_model->get([
        'type'     => 'meetlink',
        'language' => 'english',
    ]);
    $CI->load->view('meetlink_manager/mail_lists/email_templates_list', $data, false);
}


// Inject merge fields that will be used email templates for meetlink module
register_merge_fields('meetlink_manager/meetlink_merge_fields');

hooks()->add_filter('available_merge_fields', 'meetlink_fields_merge');
function meetlink_fields_merge($fields)
{

    foreach ($fields as $key => $value) {
        if (isset($value['other'])) {
            foreach ($value['other'] as $s_key => $s_value) {
                if (!empty($value['other'][$s_key]['available'])) {
                    array_push($value['other'][$s_key]['available'], 'meetlink');
                }
            }
        }
        
        if (isset($value['leads'])) {
            foreach ($value['leads'] as $s_key => $s_value) {
                if (!empty($value['leads'][$s_key]['available'])) {
                    array_push($value['leads'][$s_key]['available'], 'meetlink');
                }
            }
        }
       
        
        $final_fields[$key] = $value;
    }

    return $final_fields;
}