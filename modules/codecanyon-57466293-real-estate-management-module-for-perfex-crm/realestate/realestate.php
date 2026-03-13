<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Real Estate Management
Description: This module streamlines property management tasks, offering features like tenant screening, lease management, rent collection, accounting, and maintenance requests, ultimately saving time and improving efficiency. 
Version: 1.0.0
Requires at least: 2.3.*
Author: GreenTech Solutions
Author URI: https://codecanyon.net/user/greentech_solutions
*/

define('REALESTATE_MODULE_NAME', 'realestate');
define('REALESTATE_VERSION', 100);
define('REALESTATE_MODULE_UPLOAD_FOLDER', module_dir_path(REALESTATE_MODULE_NAME, 'uploads'));

/*add folder upload link on here*/
define('REALESTATE_PRODUCT_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/products/'));

/*link view on here*/
define('EMPLOYEE_PROFILE_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/employee_profiles/'));
define('EMPLOYEE_PATH_PROFILE_UPLOAD', 'modules/realestate/uploads/employee_profiles/');
define('PROPERTY_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/property_listings/'));
define('PROPERTY_UPLOAD_PATH', 'modules/realestate/uploads/property_listings/');
define('PROPERTY_MAIN_IMAGE_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/main_images/'));
define('PROPERTY_MAIN_IMAGE_UPLOAD_PATH', 'modules/realestate/uploads/main_images/');
define('PROPERTY_VIDEO_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/property_videos/'));
define('PROPERTY_VIDEO_UPLOAD_PATH', 'modules/realestate/uploads/property_videos/');

define('LISTING_MAP_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/listing_maps/'));
define('LISTING_MAP_PATH_UPLOAD', 'modules/realestate/uploads/listing_maps/');
define('COMPANY_PDF_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/company_pdfs/'));
define('COMPANY_PDF_UPLOAD_PATH', 'modules/realestate/uploads/company_pdfs/');

define('PROPERTY_PDF_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/property_listing_pdfs/'));
define('PROPERTY_PDF_UPLOAD_PATH', 'modules/realestate/uploads/property_listing_pdfs/');
define('BROKER_PROFILE_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/broker_profile_images/'));
define('BROKER_PATH_PROFILE_UPLOAD', 'modules/realestate/uploads/broker_profile_images/');
define('COMPANY_PROFILE_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/company_profile_images/'));
define('COMPANY_PATH_PROFILE_UPLOAD', 'modules/realestate/uploads/company_profile_images/');

define('OWNER_PROFILE_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/owner_profile_images/'));
define('OWNER_PATH_PROFILE_UPLOAD', 'modules/realestate/uploads/owner_profile_images/');
define('SUPPORTING_DOCUMENT_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/supporting_documents/'));
define('SUPPORTING_DOCUMENT_PATH_UPLOAD', 'modules/realestate/uploads/supporting_documents/');
define('PROOF_INCOME_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/proof_incomes/'));
define('PROOF_INCOME_PATH_UPLOAD', 'modules/realestate/uploads/proof_incomes/');
define('IDENTIFY_DOCUMENT_UPLOAD', module_dir_path(REALESTATE_MODULE_NAME, 'uploads/identity_documents/'));
define('IDENTIFY_DOCUMENT_PATH_UPLOAD', 'modules/realestate/uploads/identity_documents/');

// hooks
hooks()->add_action('admin_init', 'realestate_permissions');
hooks()->add_action('app_admin_head', 'realestate_add_head_components');
hooks()->add_action('app_admin_footer', 'realestate_load_js');
hooks()->add_action('app_search', 'realestate_load_search');
hooks()->add_action('admin_init', 'realestate_module_init_menu_items');

hooks()->add_action('app_broker_portal_head', 'broker_add_head_components');
hooks()->add_action('app_broker_portal_footer', 'broker_load_js');
hooks()->add_action('app_broker_portal_head', 'csrf_jquery_token');

hooks()->add_action('app_customers_head', 'realestate_portal_add_head_components');
hooks()->add_action('real_customers_portal_footer', 'realestate_portal_add_footer_components');

// client portal menu
hooks()->add_action('customers_navigation_end', 'init_realestate_portal_menu');
hooks()->add_action('customers_navigation_end', 'realestate_broker_icon');

// Reload language for sales broker portal
hooks()->add_action('after_load_admin_language', 'reload_broker_language');

hooks()->add_action('after_email_templates', 'add_realestate_email_templates');
register_merge_fields('realestate/merge_fields/broker_staff_merge_fields');
register_merge_fields('realestate/merge_fields/property_request_merge_fields');
hooks()->add_filter('other_merge_fields_available_for', 'realestate_register_other_merge_fields');

hooks()->add_action('after_payment_added', 'real_after_payment_added');
hooks()->add_filter('organization_info_text', 'realestate_organization_info_text');
hooks()->add_filter('pdf_logo_url', 'realestate_pdf_logo_url');
hooks()->add_action('realestate_init',REALESTATE_MODULE_NAME.'_appint');
hooks()->add_action('pre_activate_module', REALESTATE_MODULE_NAME.'_preactivate');
hooks()->add_action('pre_deactivate_module', REALESTATE_MODULE_NAME.'_predeactivate');
/**
 * Register activation module hook
 */
register_activation_hook(REALESTATE_MODULE_NAME, 'realestate_module_activation_hook');

function realestate_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(REALESTATE_MODULE_NAME, [REALESTATE_MODULE_NAME]);

$CI = &get_instance();
$CI->load->helper(REALESTATE_MODULE_NAME . '/realestate');
$CI->load->helper(REALESTATE_MODULE_NAME . '/broker');

/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function realestate_module_init_menu_items()
{
    $CI               = &get_instance();
    $staff_in_company = rel_check_staff_in_company();

    /*add menu on here*/

    if (has_permission('real_dashboard', '', 'view') || has_permission('real_property_owner', '', 'view') || has_permission('real_property_owner', '', 'view_own') || has_permission('real_estate_agent', '', 'view') || has_permission('real_estate_agent', '', 'view_own') || has_permission('staff', '', 'view') || has_permission('staff', '', 'view_own') || has_permission('real_business_broker', '', 'view') || has_permission('real_business_broker', '', 'view_own') || has_permission('real_property', '', 'view') || has_permission('real_property', '', 'view_own') || has_permission('real_property_approval', '', 'view') || has_permission('real_buy_request', '', 'view') || has_permission('real_buy_request', '', 'view_own') || has_permission('real_rent_request', '', 'view') || has_permission('real_rent_request', '', 'view_own') || has_permission('real_tenant', '', 'view') || has_permission('real_permission', '', 'view') || has_permission('real_report', '', 'view')) {

        $CI->app_menu->add_sidebar_menu_item('realestate', [
            'name'     => _l('realestate_name'),
            'icon'     => 'fa-solid fa-house-circle-check',
            'position' => 10,
        ]);
    }

    if (has_permission('real_dashboard', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_dashboard',
            'name'     => _l('reale_dashboard'),
            'icon'     => 'fa fa-dashboard',
            'href'     => admin_url('realestate/dashboard'),
            'position' => 1,
        ]);
    }

    if (has_permission('real_property_owner', '', 'view') || has_permission('real_property_owner', '', 'view_own')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_property_owners',
            'name'     => _l('real_property_owners'),
            'icon'     => 'fa-solid fa-people-group',
            'href'     => admin_url('realestate/property_owners'),
            'position' => 1,
        ]);
    }

    if ($staff_in_company) {
        if (has_permission('real_estate_agent', '', 'view') || has_permission('real_estate_agent', '', 'view_own')) {
            $CI->app_menu->add_sidebar_children_item('realestate', [
                'slug'     => 'realestate_real_estate_agents',
                'name'     => _l('real_my_real_estate_agent'),
                'icon'     => 'fa-solid fa-building',
                'href'     => admin_url('realestate/add_edit_company/' . $staff_in_company),
                'position' => 1,
            ]);
        }
    } else {
        if (has_permission('staff', '', 'view') || has_permission('staff', '', 'view_own')) {
            $CI->app_menu->add_sidebar_children_item('realestate', [
                'slug'     => 'realestate_my_staffs',
                'name'     => _l('real_my_staffs'),
                'icon'     => 'fa-regular fa-user menu-icon',
                'href'     => admin_url('realestate/company_staffs'),
                'position' => 1,
            ]);
        }

        if (has_permission('real_estate_agent', '', 'view') || has_permission('real_estate_agent', '', 'view_own')) {
            $CI->app_menu->add_sidebar_children_item('realestate', [
                'slug'     => 'realestate_real_estate_agents',
                'name'     => _l('real_real_estate_agents'),
                'icon'     => 'fa-solid fa-building',
                'href'     => admin_url('realestate/companies'),
                'position' => 1,
            ]);
        }
    }

    if (has_permission('real_business_broker', '', 'view') || has_permission('real_business_broker', '', 'view_own')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_business_brokers',
            'name'     => _l('real_business_brokers'),
            'icon'     => 'fa-solid fa-handshake-angle',
            'href'     => admin_url('realestate/business_brokers'),
            'position' => 1,
        ]);
    }

    if (has_permission('real_property', '', 'view') || has_permission('real_property', '', 'view_own')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_properties',
            'name'     => _l('real_properties'),
            'icon'     => 'fa-regular fa-rectangle-list',
            'href'     => admin_url('realestate/properties'),
            'position' => 1,
        ]);
    }

    if (has_permission('real_property_approval', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_property_approvals',
            'name'     => _l('real_approvals'),
            'icon'     => 'fa-solid fa-check-to-slot menu-icon menu-icon',
            'href'     => admin_url('realestate/approvals'),
            'position' => 1,
        ]);
    }

    if (has_permission('real_buy_request', '', 'view') || has_permission('real_buy_request', '', 'view_own')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_buy_requests',
            'name'     => _l('real_buy_requests'),
            'icon'     => 'fa-solid fa-house-circle-exclamation',
            'href'     => admin_url('realestate/requests'),
            'position' => 1,
        ]);
    }
    if (has_permission('real_rent_request', '', 'view') || has_permission('real_rent_request', '', 'view_own')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_rent_requests',
            'name'     => _l('real_rent_requests'),
            'icon'     => 'fa-solid fa-person-circle-question',
            'href'     => admin_url('realestate/rent_requests'),
            'position' => 1,
        ]);
    }

    if (has_permission('real_tenant', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_tenants',
            'name'     => _l('real_tenants'),
            'icon'     => 'fa-solid fa-address-book',
            'href'     => admin_url('realestate/tenants'),
            'position' => 1,
        ]);
    }

    if (has_permission('real_report', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_reports',
            'name'     => _l('real_reports'),
            'icon'     => 'fa fa-list-alt',
            'href'     => admin_url('realestate/reports'),
            'position' => 1,
        ]);
    }

    if (is_admin()) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_setting',
            'name'     => _l('reale_settings'),
            'icon'     => 'fa fa-cog menu-icon',
            'href'     => admin_url('realestate/settings?tab=general'),
            'position' => 10,
        ]);
    } elseif ( ! $staff_in_company && has_permission('real_permission', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_permissions',
            'name'     => _l('real_permissions'),
            'icon'     => 'fa fa-cog menu-icon',
            'href'     => admin_url('realestate/settings?tab=permissions'),
            'position' => 10,
        ]);
    } elseif ($staff_in_company && has_permission('real_permission', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('realestate', [
            'slug'     => 'realestate_permissions',
            'name'     => _l('real_permissions'),
            'icon'     => 'fa fa-cog menu-icon',
            'href'     => admin_url('realestate/settings?tab=permissions'),
            'position' => 10,
        ]);
    }

}

/**
 * realestate load js
 */
function realestate_load_js()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    if ( ! (strpos($viewuri, '/realestate') === false) || ! (strpos($viewuri, '/staff/member') === false)) {
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/properties/real_main.js') . '?v=' . REALESTATE_VERSION . '"></script>';
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/properties/main.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }
    if ( ! (strpos($viewuri, '/realestate/add_edit_property_listing') === false) || ! (strpos($viewuri, '/realestate/properties') === false) || ! (strpos($viewuri, '/realestate/property_listing_detail') === false) || ! (strpos($viewuri, '/realestate/add_edit_company/') === false)) {

        $api_key = get_option('real_Gogle_Map_API_Code');
        if ($api_key && $api_key != '') {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&callback=initMap&libraries=drawing,geometry,places,marker&v=weekly&region=EG" defer></script>';
        }
    }

    if ( ! (strpos($viewuri, '/realestate/add_edit_company/') === false)) {
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/Pagination/jquery.twbsPagination.min.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }

    if ( ! (strpos($viewuri, 'admin/realestate/dashboard') === false)) {

        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }

}

/**
 * realestate add head components
 */
function realestate_add_head_components()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    /*change this code*/
    if ( ! (strpos($viewuri, 'admin/realestate') === false)) {
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/styles.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/checkbox-radio-grouped-button/boxed-check.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
    }

    if ( ! (strpos($viewuri, 'admin/realestate/properties') === false)) {
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/properties/filter.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
    }
    if ( ! (strpos($viewuri, 'admin/realestate/property_listing_detail') === false) || ! (strpos($viewuri, 'admin/realestate/properties') === false) || ! (strpos($viewuri, 'admin/realestate/add_edit_property_request') === false) || ! (strpos($viewuri, 'admin/realestate/add_edit_company') === false) || ! (strpos($viewuri, 'admin/realestate/renter_profile') === false)) {
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/properties/room_item.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
    }
}

/**
 * realestate load js
 */
function broker_load_js()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    if ( ! (strpos($viewuri, '/realestate/broker') === false)) {
        echo '<script src="' . base_url('assets/plugins/internal/validation/app-form-validation.js') . '?v=' . REALESTATE_VERSION . '"></script>';
        echo '<script src="' . site_url('assets/plugins/metisMenu/metisMenu.js') . '"></script>';
        echo '<script src="' . site_url('assets/plugins/tinymce/tinymce.min.js') . '"></script>';

        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/properties/broker_main.js') . '?v=' . REALESTATE_VERSION . '"></script>';
        echo '<script src="' . base_url('assets/plugins/internal/desktop-notifications/notifications.js') . '?v=' . REALESTATE_VERSION . '"></script>';
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/properties/main.js') . '?v=' . REALESTATE_VERSION . '"></script>';
        echo '<script type="text/javascript" src="' . site_url('assets/plugins/accounting.js/accounting.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }

    if ( ! (strpos($viewuri, '/realestate/broker/add_edit_property_listing') === false) || ! (strpos($viewuri, '/realestate/broker/properties') === false) || ! (strpos($viewuri, '/realestate/broker/property_listing_detail') === false)) {

        $api_key = get_option('real_Gogle_Map_API_Code');
        if ($api_key && $api_key != '') {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&callback=initMap&libraries=drawing,geometry,places,marker&v=weekly&region=EG" defer></script>';
        }
    }

    if ( ! (strpos($viewuri, '/realestate/broker/dashboard') === false)) {
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }

}

/**
 * realestate add head components
 */
function broker_add_head_components()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    /*change this code*/
    if ( ! (strpos($viewuri, 'realestate/broker') === false)) {
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/styles.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/properties/room_item.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/checkbox-radio-grouped-button/boxed-check.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
    }

    if ( ! (strpos($viewuri, 'realestate/broker/properties') === false)) {
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/properties/filter.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';

    }

}

/**
 * realestate portal add head components
 * @return [type]
 */
function realestate_portal_add_head_components()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    if ( ! (strpos($viewuri, 'realestate/client') === false)) {
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/properties/room_item.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/client_portals/styles.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/checkbox-radio-grouped-button/boxed-check.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
    }

    if ( ! (strpos($viewuri, 'realestate/client/properties') === false)) {
        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/properties/filter.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';

    }
}

/**
 * realestate portal add footer components
 * @return [type]
 */
function realestate_portal_add_footer_components()
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    if ( ! (strpos($viewuri, 'realestate/client') === false)) {
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/clients/client_main.js') . '?v=' . REALESTATE_VERSION . '"></script>';
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/properties/main.js') . '?v=' . REALESTATE_VERSION . '"></script>';
        echo '<script type="text/javascript" src="' . site_url('assets/plugins/accounting.js/accounting.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }

    if ( ! (strpos($viewuri, 'realestate/client/renter_profile') === false)) {
        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/clients/datatable.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }
    if ( ! (strpos($viewuri, 'realestate/client/properties') === false) || ! (strpos($viewuri, 'realestate/client/property_listing_detail') === false) || ! (strpos($viewuri, 'realestate/client/agent') === false) || ! (strpos($viewuri, 'realestate/client/staff') === false) || ! (strpos($viewuri, 'realestate/client/company') === false)) {

        $api_key = get_option('real_Gogle_Map_API_Code');
        if ($api_key && $api_key != '') {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&callback=initMap&libraries=drawing,geometry,places,marker&v=weekly&region=EG" defer></script>';
        }

        echo '<link href="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/css/styles.css') . '?v=' . REALESTATE_VERSION . '"  rel="stylesheet" type="text/css" />';
    }

    if ( ! (strpos($viewuri, 'realestate/client/properties') === false) || ! (strpos($viewuri, 'realestate/client/agent') === false) || ! (strpos($viewuri, 'realestate/client/staff') === false) || ! (strpos($viewuri, 'realestate/client/company') === false)) {

        echo '<script src="' . module_dir_url(REALESTATE_MODULE_NAME, 'assets/plugins/Pagination/jquery.twbsPagination.min.js') . '?v=' . REALESTATE_VERSION . '"></script>';
    }
}

/**
 * realestate permissions
 */
function realestate_permissions()
{

    $capabilities = [];

    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    $capabilities_create['capabilities'] = [
        'create' => _l('permission_create'),
    ];

    $capabilities_view['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
    ];

    $capabilities_global['capabilities'] = [
        'view_own' => _l('permission_view_own'),
        'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create'   => _l('permission_create'),
        'edit'     => _l('permission_edit'),
        'delete'   => _l('permission_delete'),
    ];

    $capabilities_without_view_own['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit'   => _l('edit'),
        'delete' => _l('permission_delete'),
    ];

    $capabilities_without_create_delete['capabilities'] = [
        'view_own' => _l('permission_view_own'),
        'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
        'edit'     => _l('edit'),
    ];
    $capabilities_without_view_own_edit_delete['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('real_approval_property'),
    ];

    $capabilities_without_edit['capabilities'] = [
        'view_own' => _l('permission_view_own'),
        'view'     => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create'   => _l('permission_create'),
        'delete'   => _l('permission_delete'),
    ];

    register_staff_capabilities('real_dashboard', $capabilities_view, _l('real_permission_dashboard'));
    register_staff_capabilities('real_estate_agent', $capabilities_global, _l('real_permission_estate_agents'));
    register_staff_capabilities('real_estate_agent_staff', $capabilities_without_view_own, _l('real_permission_estate_agent_staffs'));
    register_staff_capabilities('real_property_owner', $capabilities_global, _l('real_permission_property_owners'));
    register_staff_capabilities('real_business_broker', $capabilities_global, _l('real_permission_business_brokers'));
    register_staff_capabilities('real_request_broker', $capabilities_without_edit, _l('real_permission_assign_to_business_brokers'));
    register_staff_capabilities('real_property', $capabilities_global, _l('real_permission_properties'));
    register_staff_capabilities('real_property_approval', $capabilities_without_view_own_edit_delete, _l('real_permission_property_approvals'));
    register_staff_capabilities('real_buy_request', $capabilities_global, _l('real_permission_buy_requests'));
    register_staff_capabilities('real_rent_request', $capabilities_global, _l('real_permission_rent_requests'));
    register_staff_capabilities('real_tenant', $capabilities_without_create_delete, _l('real_permission_tenants'));
    register_staff_capabilities('real_report', $capabilities_view, _l('real_permission_reports'));
    register_staff_capabilities('real_permission', $capabilities_without_view_own, _l('real_permission_permission'));
}

function reload_broker_language($language)
{
    $CI = &get_instance();
    if ($CI instanceof AdminController) {
        $CI->lang->load($language . '_lang', $language);
        if (file_exists(APPPATH . 'language/' . $language . '/custom_lang.php')) {
            $CI->lang->load('custom_lang', $language);
        }

        $GLOBALS['language'] = $language;
        $GLOBALS['locale']   = get_locale_key($language);
    } else {
        if ($CI instanceof Broker) {
            $broker_id = get_broker_id();

            if ($broker_id != 0) {
                $CI->db->select('default_language');
                $CI->db->where('id', $broker_id);
                $lang = $CI->db->get(db_prefix() . 'real_broker_staffs')->row();
                if ($lang && $lang->default_language != '') {
                    $CI->lang->load($lang->default_language . '_lang', $lang->default_language);
                    $CI->lang->load('realestate' . '/' . 'realestate', $lang->default_language);

                    if (file_exists(APPPATH . 'language/' . $lang->default_language . '/custom_lang.php')) {
                        $CI->lang->load('custom_lang', $lang->default_language);
                    }
                    $GLOBALS['language'] = $lang->default_language;
                    $GLOBALS['locale']   = get_locale_key($lang->default_language);
                } else {
                    $CI->lang->load($language . '_lang', $language);
                    if (file_exists(APPPATH . 'language/' . $language . '/custom_lang.php')) {
                        $CI->lang->load('custom_lang', $language);
                    }
                    $GLOBALS['language'] = $language;
                    $GLOBALS['locale']   = get_locale_key($language);
                }
            } else {
                $CI->lang->load($language . '_lang', $language);
                if (file_exists(APPPATH . 'language/' . $language . '/custom_lang.php')) {
                    $CI->lang->load('custom_lang', $language);
                }
                $GLOBALS['language'] = $language;
                $GLOBALS['locale']   = get_locale_key($language);
            }
        }
    }
}

/**
 * add realestate email templates
 */
function add_realestate_email_templates()
{
    $CI = &get_instance();

    $data['realestate_templates'] = $CI->emails_model->get(['type' => 'realestate', 'language' => 'english']);

    $CI->load->view('realestate/email_templates/realestate_email_template', $data);
}

/**
 * change candidate status register other merge fields
 * @param  [type] $for
 * @return [type]
 */
function realestate_register_other_merge_fields($for)
{
    $for[] = 'realestate';

    return $for;
}

/**
 * init realestate portal menu
 * @return [type]
 */
function init_realestate_portal_menu()
{
    $item = '';
    if (is_client_logged_in()) {
        $item .= '<li class=" customers-nav-item">';
        $item .= ' <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true"
        aria-expanded="false"><i class="fa-solid fa-house-circle-check menu-icon"></i>' . _l('realestate_name');
        $item .= '</a>';
        $item .= ' <ul class="dropdown-menu animated fadeIn client-ul">';
        $item .= '<li class="customers-nav-item-properties">';
        $item .= '<a href="' . site_url('realestate/client/properties') . '">' . _l('real_properties') . '</a>';
        $item .= '</li>';

        $item .= '<li class="customers-nav-item-my_saved_properties hide">';
        $item .= '<a href="' . site_url('realestate/client/pre_alert_list') . '">' . _l('real_my_saved_properties') . '</a>';
        $item .= '</li>';

        $item .= '<li class="customers-nav-item-rental_profile">';
        $item .= '<a href="' . site_url('realestate/client/renter_profile') . '">' . _l('real_renter_profile') . '</a>';
        $item .= '</li>';

        $item .= '<li class="customers-nav-item-rental_application">';
        $item .= '<a href="' . site_url('realestate/client/rents') . '">' . _l('real_rental_application') . '</a>';
        $item .= '</li>';
        $item .= '<li class="customers-nav-item-rental_application">';
        $item .= '<a href="' . site_url('realestate/client/buy') . '">' . _l('real_buy_application') . '</a>';
        $item .= '</li>';

        $item .= '<li class="customers-nav-item-rental_listings hide">';
        $item .= '<a href="' . site_url('realestate/client/packages') . '">' . _l('real_my_rental_listings') . '</a>';
        $item .= '</li>';

        $item .= '</ul>';

        $item .= '</li>';
    }
    echo new_html_entity_decode($item);

}

/**
 * realestate broker icon
 * @return [type]
 */
function realestate_broker_icon()
{
    $realestate_icon = '';
    if ( ! is_client_logged_in() && get_option('real_show_broker_portal') == 1) {
        $realestate_icon .= '<li class="customers-nav-item-login">
        <a href="' . site_url('realestate/authentication_broker/login') . '" class="btn btn-primary text-white"><i class="glyphicon glyphicon-home"></i>' . _l('real_broker_portal') . '</a></li>
        ';
    }
    echo html_entity_decode($realestate_icon);
}

/**
 * real after payment added
 * @param  [type] $payment_id
 * @return [type]
 */
function real_after_payment_added($payment_id)
{
    $CI = &get_instance();
    $CI->load->model('realestate/realestate_model');
    $CI->realestate_model->create_property_log_for_request($payment_id);

    return true;
}

function realestate_organization_info_text($data)
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];
    if ( ! (strpos($viewuri, '/invoice') === false)) {
        $arr_viewuri = explode('/', $viewuri);
        if (isset($arr_viewuri[2]) && is_numeric($arr_viewuri[2])) {

            $CI->load->model('invoices_model');
            $invoice = $CI->invoices_model->get($arr_viewuri[2]);
            if ($invoice->broker_id > 0) {
                $data = real_get_company_name($invoice->broker_id, true, false, true);
            }
        }
    }

    if ( ! (strpos($viewuri, '/realestate/broker/invoice_pdf') === false)) {
        $viewuri     = str_replace('/realestate/broker/invoice_pdf/', '', $viewuri);
        $arr_viewuri = explode('?', $viewuri);
        if (isset($arr_viewuri[0]) && is_numeric($arr_viewuri[0])) {

            $CI->load->model('invoices_model');
            $invoice = $CI->invoices_model->get($arr_viewuri[0]);
            if ($invoice->broker_id > 0) {
                $data = real_get_company_name($invoice->broker_id, true, false, true);
            }
        }
    }

    if ( ! (strpos($viewuri, '/realestate/broker/payment') === false)) {
        $viewuri     = str_replace('/realestate/broker/payment/', '', $viewuri);
        $arr_viewuri = explode('?', $viewuri);
        if (isset($arr_viewuri[0]) && is_numeric($arr_viewuri[0])) {

            $CI->load->model('payments_model');
            $payment = $CI->payments_model->get($arr_viewuri[0]);
            if ($payment) {
                $invoice_id = $payment->invoiceid;
                $CI->load->model('invoices_model');
                $invoice = $CI->invoices_model->get($invoice_id);
                if ($invoice->broker_id > 0) {
                    $data = real_get_company_name($invoice->broker_id, true, false, true);
                }
            }
        }
    }

    if ( ! (strpos($viewuri, '/realestate/broker/payment_pdf') === false)) {
        $viewuri     = str_replace('/realestate/broker/payment_pdf/', '', $viewuri);
        $arr_viewuri = explode('?', $viewuri);
        if (isset($arr_viewuri[0]) && is_numeric($arr_viewuri[0])) {

            $CI->load->model('payments_model');
            $payment = $CI->payments_model->get($arr_viewuri[0]);
            if ($payment) {
                $invoice_id = $payment->invoiceid;
                $CI->load->model('invoices_model');
                $invoice = $CI->invoices_model->get($invoice_id);
                if ($invoice->broker_id > 0) {
                    $data = real_get_company_name($invoice->broker_id, true, false, true);
                }
            }
        }
    }

    if ( ! (strpos($viewuri, '/contract') === false)) {
        $arr_viewuri = explode('/', $viewuri);
        if (isset($arr_viewuri[2]) && is_numeric($arr_viewuri[2])) {
            $CI->load->model('contracts_model');
            $contract = $CI->contracts_model->get($arr_viewuri[2]);
            if ($contract->broker_id > 0) {
                $data = real_get_company_name($contract->broker_id, true, false, true);
            }
        }
    }

    return $data;
}

function realestate_pdf_logo_url($data)
{
    $CI      = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];
    if ( ! (strpos($viewuri, '/invoice') === false)) {
        $arr_viewuri = explode('/', $viewuri);
        if (isset($arr_viewuri[2]) && is_numeric($arr_viewuri[2])) {

            $CI->load->model('invoices_model');
            $invoice = $CI->invoices_model->get($arr_viewuri[2]);
            if ($invoice->broker_id > 0) {
                $data = company_profile_image($invoice->broker_id, true, false, true);
            }
        }
    }
    if ( ! (strpos($viewuri, '/contract') === false)) {
        $arr_viewuri = explode('/', $viewuri);
        if (isset($arr_viewuri[2]) && is_numeric($arr_viewuri[2])) {
            $CI->load->model('contracts_model');
            $contract = $CI->contracts_model->get($arr_viewuri[2]);
            if ($contract->broker_id > 0) {
                $data = company_profile_image($contract->broker_id, true, false, true);
            }
        }
    }

    return $data;
}
function realestate_appint(){
    $CI = & get_instance();    
    require_once 'libraries/gtsslib.php';
    $re_api = new RealEstateLic();
    $re_gtssres = $re_api->verify_license(true);    
    if(!$re_gtssres || ($re_gtssres && isset($re_gtssres['status']) && !$re_gtssres['status'])){
         $CI->app_modules->deactivate(REALESTATE_MODULE_NAME);
        set_alert('danger', "One of your modules failed its verification and got deactivated. Please reactivate or contact support.");
        redirect(admin_url('modules'));
    }    
}

function realestate_preactivate($module_name){
    if ($module_name['system_name'] == REALESTATE_MODULE_NAME) {             
        require_once 'libraries/gtsslib.php';
        $re_api = new RealEstateLic();
        $re_gtssres = $re_api->verify_license();          
        if(!$re_gtssres || ($re_gtssres && isset($re_gtssres['status']) && !$re_gtssres['status'])){
             $CI = & get_instance();
            $data['submit_url'] = $module_name['system_name'].'/gtsverify/activate'; 
            $data['original_url'] = admin_url('modules/activate/'.REALESTATE_MODULE_NAME); 
            $data['module_name'] = REALESTATE_MODULE_NAME; 
            $data['title'] = "Module License Activation"; 
            echo $CI->load->view($module_name['system_name'].'/activate', $data, true);
            exit();
        }        
    }
}

function realestate_predeactivate($module_name){
    if ($module_name['system_name'] == REALESTATE_MODULE_NAME) {
        require_once 'libraries/gtsslib.php';
        $re_api = new RealEstateLic();
        $re_api->deactivate_license();
    }
}