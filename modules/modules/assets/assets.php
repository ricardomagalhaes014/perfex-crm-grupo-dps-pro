<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Asset Management | Gerenciamento de Ativos
Description: Módulo de gerenciamento de ativos, alocação, recuperação, depreciação, status do ativo
Version: 1.1.0
Author: Themesic Interactive | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
Requires at least: 2.3.*
*/

define('ASSETS_MODULE_NAME', 'assets');
define('ASSETS_PATH', 'modules/assets/uploads/');
define('ASSETS_UPLOAD_FOLDER', module_dir_path(ASSETS_MODULE_NAME, 'uploads'));

hooks()->add_action('admin_init', 'assets_permissions');
hooks()->add_action('admin_init', 'assets_module_init_menu_items');
hooks()->add_action('app_admin_head', 'assets_add_head_components');

/**
 * Injects needed CSS.
 */
function assets_add_head_components()
{
    $CI = &get_instance();
    echo '<link href="'.base_url('modules/assets/css/style.css').'?v='.$CI->app_scripts->core_version().'"  rel="stylesheet" type="text/css" />';
}

// Register activation module hook
register_activation_hook(ASSETS_MODULE_NAME, 'assets_module_activation_hook');
/**
 * Load the module helper.
 */
$CI = &get_instance();

function assets_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__.'/install.php';
}

// Register language files, must be registered if the module is using languages
register_language_files(ASSETS_MODULE_NAME, [ASSETS_MODULE_NAME]);

$CI = &get_instance();
$CI->load->helper(ASSETS_MODULE_NAME.'/asset');
/**
 * Init goals module menu items in setup in admin_init hook.
 *
 * @return null
 */
function assets_module_init_menu_items()
{
    $CI = &get_instance();
    if (has_permission('assets', '', 'view') || is_admin()) {
        $CI->app_menu->add_sidebar_menu_item('assets', [
            'name'     => _l('assets'),
            'icon'     => 'fa fa-bank',
            'position' => 40,
        ]);
        $CI->app_menu->add_sidebar_children_item('assets', [
            'slug'     => 'assets_menu',
            'name'     => _l('assets'),
            'icon'     => 'fa fa-bank',
            'href'     => admin_url('assets/manage_assets'),
            'position' => 1,
        ]);

        $CI->app_menu->add_sidebar_children_item('assets', [
            'slug'     => 'allocations',
            'name'     => _l('allocation'),
            'icon'     => 'fa fa-pencil-square-o',
            'href'     => admin_url('assets/allocation'),
            'position' => 2,
        ]);

        $CI->app_menu->add_sidebar_children_item('assets', [
            'slug'     => 'evictions',
            'name'     => _l('eviction'),
            'icon'     => 'fa fa-pencil-square',
            'href'     => admin_url('assets/eviction'),
            'position' => 3,
        ]);

        $CI->app_menu->add_sidebar_children_item('assets', [
            'slug'     => 'depreciations',
            'name'     => _l('depreciation'),
            'icon'     => 'fa fa-legal',
            'href'     => admin_url('assets/depreciation'),
            'position' => 4,
        ]);
        $CI->app_menu->add_sidebar_children_item('assets', [
            'slug'     => 'settings',
            'name'     => _l('setting'),
            'icon'     => 'fa fa-cogs',
            'href'     => admin_url('assets/setting'),
            'position' => 5,
        ]);
    }
}
function assets_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
            'view'   => _l('permission_view').'('._l('permission_global').')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('assets', $capabilities, _l('assets'));
}

// Inject upload folder location for assets module
hooks()->add_filter('get_upload_path_by_type', 'asset_upload_folder', 10, 2);
function asset_upload_folder($path, $type)
{
    if ('assets' == $type) {
        return ASSETS_UPLOAD_FOLDER.'/';
    }

    return $path;
}

// Add Menu In Customer Side
hooks()->add_action('customers_navigation_start', 'add_asset_menu');
function add_asset_menu()
{
    $CI = &get_instance();
    if (is_client_logged_in()) {
        $CI->load->model('assets/assets_model');
        $client_user_id                                              = $CI->session->userdata('client_user_id');
        $where["find_in_set('".$client_user_id."',`belongs_to`) <>"] = 0;
        $allocated_asset                                             = $CI->assets_model->get_clients_assign_assets('assets', $where);
        if (!empty($allocated_asset) && has_contact_permission('asset')) {
            echo '<li class="customers-nav-item-contracts">
                <a href="'.site_url('assets/client').'">'._l('assets').'</a>
            </li>';
        }
    }
}

hooks()->add_filter('get_contact_permissions', 'add_asset_permission');
function add_asset_permission($permissions)
{
    $permissions[] = [
            'id'         => 7,
            'name'       => _l('assets'),
            'short_name' => 'asset',
        ];

    return $permissions;
}
