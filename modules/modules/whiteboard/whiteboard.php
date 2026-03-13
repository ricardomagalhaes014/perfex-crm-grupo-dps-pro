<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: WhiteBoard | Quadro Branco
Description: Módulo para desenhar WhiteBoard
Version: 1.0.4
Author: Weeb Digital | Grupo Liquida I.A no Telegram
Author URI: ttps://t.me/crmperfex
Requires at least: 2.3.*
*/
$CI = &get_instance();
define('WHITEBOARD_MODULE_NAME', 'whiteboard');
define('WHITEBOARD_DISCUSSION_ATTACHMENT_FOLDER', FCPATH . 'uploads/whiteboard' . '/');
hooks()->add_action('admin_init', 'whiteboard_module_init_menu_items');
hooks()->add_action('admin_init', 'whiteboard_permissions');

hooks()->add_filter('global_search_result_query', 'whiteboard_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'whiteboard_global_search_result_output', 10, 2);
hooks()->add_filter('migration_tables_to_replace_old_links', 'whiteboard_migration_tables_to_replace_old_links');
$CI->load->helper(WHITEBOARD_MODULE_NAME . '/whiteboard');

function whiteboard_global_search_result_output($output, $data)
{
    if ($data['type'] == 'whiteboard') {
        $output = '<a href="' . admin_url('whiteboard/preview/' . $data['result']['id']) . '">' . $data['result']['title'] . '</a>';
    }

    return $output;
}

function whiteboard_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('whiteboard', '', 'view')) {
        $CI->db->select()->from(db_prefix() . 'whiteboard')->like('description', $q)->or_like('title', $q)->limit($limit);

        $CI->db->order_by('title', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'whiteboard',
                'search_heading' => _l('whiteboard'),
            ];
    }

    return $result;
}

function whiteboard_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'whiteboard',
            ];

    return $tables;
}

function whiteboard_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('whiteboard', $capabilities, _l('whiteboard'));
}

/**
* Register activation module hook
*/
register_activation_hook(WHITEBOARD_MODULE_NAME, 'whiteboard_module_activation_hook');

function whiteboard_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}


/**
 * Register uninstall module hook
 */
register_uninstall_hook(WHITEBOARD_MODULE_NAME, 'whiteboard_module_uninstall_hook');

function whiteboard_module_uninstall_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/uninstall.php');
}


/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(WHITEBOARD_MODULE_NAME, [WHITEBOARD_MODULE_NAME]);

/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
function whiteboard_module_init_menu_items()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('whiteboard_menu', [
        'name' => 'WhiteBoard', // The name if the item
        'href' => admin_url('whiteboard'), // URL of the item
        'position' => 10, // The menu position, see below for default positions.
        'icon' => 'fa fa-clone', // Font awesome icon
    ]);

    if (staff_can('view', 'settings')) {
        $CI = &get_instance();
        $CI->app_tabs->add_settings_tab('whiteboard', [
            'name'     => '' . _l('whiteboard_settings_name') . '',
            'view'     => 'whiteboard/admin/settings',
            'position' => 36,
        ]);
    }


    if (is_admin()) {
        $CI->app_menu->add_setup_menu_item('whiteboard', [
            'collapse' => true,
            'name' => _l('whiteboard'),
            'position' => 10,
        ]);

        $CI->app_menu->add_setup_children_item('whiteboard', [
            'slug' => 'whiteboard-groups',
            'name' => _l('whiteboard_groups'),
            'href' => admin_url('whiteboard/groups'),
            'position' => 5,
        ]);
        $CI->app_tabs->add_project_tab('whiteboard', [
        'name'                      => _l('whiteboard'),
        'icon'                      => 'fa fa-clone menu-icon',
        'view'                      => 'whiteboard/admin/project_whiteboard',
        'position'                  => 55,
    ]);
    }
}
