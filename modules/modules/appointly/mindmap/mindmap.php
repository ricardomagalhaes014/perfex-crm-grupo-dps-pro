<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: MindMap
Description: Module to draw MindMap
Version: 1.0.5
Author: Weeb Digital
Author URI: https://weebdigital.com/
Requires at least: 2.3.*
*/
$CI = &get_instance();
define('MINDMAP_MODULE_NAME', 'mindmap');
define('MINDMAP_DISCUSSION_ATTACHMENT_FOLDER', FCPATH . 'uploads/mindmap' . '/');
hooks()->add_action('admin_init', 'mindmap_module_init_menu_items');
hooks()->add_action('admin_init', 'mindmap_permissions');

hooks()->add_filter('global_search_result_query', 'mindmap_global_search_result_query', 10, 3);
hooks()->add_filter('global_search_result_output', 'mindmap_global_search_result_output', 10, 2);
hooks()->add_filter('migration_tables_to_replace_old_links', 'mindmap_migration_tables_to_replace_old_links');
$CI->load->helper(MINDMAP_MODULE_NAME . '/mindmap');

function mindmap_global_search_result_output($output, $data)
{
    if ($data['type'] == 'mindmap') {
        $output = '<a href="' . admin_url('mindmap/preview/' . $data['result']['id']) . '">' . $data['result']['title'] . '</a>';
    }

    return $output;
}

function mindmap_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('mindmap', '', 'view')) {
        $CI->db->select()->from(db_prefix() . 'mindmap')->like('description', $q)->or_like('title', $q)->limit($limit);

        $CI->db->order_by('title', 'ASC');

        $result[] = [
                'result'         => $CI->db->get()->result_array(),
                'type'           => 'mindmap',
                'search_heading' => _l('mindmap'),
            ];
    }

    return $result;
}

function mindmap_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix() . 'mindmap',
            ];

    return $tables;
}

function mindmap_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('mindmap', $capabilities, _l('mindmap'));
}

/**
* Register activation module hook
*/
register_activation_hook(MINDMAP_MODULE_NAME, 'mindmap_module_activation_hook');

function mindmap_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}


/**
 * Register uninstall module hook
 */
register_uninstall_hook(MINDMAP_MODULE_NAME, 'mindmap_module_uninstall_hook');

function mindmap_module_uninstall_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/uninstall.php');
}


/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(MINDMAP_MODULE_NAME, [MINDMAP_MODULE_NAME]);

/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
function mindmap_module_init_menu_items()
{
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('mindmap_menu', [
        'name' => 'MindMap', // The name if the item
        'href' => admin_url('mindmap'), // URL of the item
        'position' => 10, // The menu position, see below for default positions.
        'icon' => 'fa fa-map', // Font awesome icon
    ]);

    if (staff_can('view', 'settings')) {
        $CI = &get_instance();
        $CI->app_tabs->add_settings_tab('mindmap', [
            'name'     => '' . _l('mindmap_settings_name') . '',
            'view'     => 'mindmap/admin/settings',
            'position' => 36,
        ]);
    }


    if (is_admin()) {
        $CI->app_menu->add_setup_menu_item('mindmap', [
            'collapse' => true,
            'name' => _l('mindmap'),
            'position' => 10,
        ]);

        $CI->app_menu->add_setup_children_item('mindmap', [
            'slug' => 'mindmap-groups',
            'name' => _l('mindmap_groups'),
            'href' => admin_url('mindmap/groups'),
            'position' => 5,
        ]);
    }
    $CI->app_tabs->add_project_tab('mindmap', [
        'name'                      => _l('mindmap'),
        'icon'                      => 'fa fa-map',
        'view'                      => 'mindmap/admin/project_mindmap',
        'position'                  => 55,
    ]);
}
