<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Customer Loyalty & Membership | Fidelidade do Cliente e Associação
Description: Descrição: Um programa de fidelidade é um programa de recompensas oferecido normalmente a clientes e até mesmo funcionários que realizam compras frequentemente.
Version: 1.0.0
Requires at least: 2.3.*
Author: GreenTech Solutions | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
 */

define('LOYALTY_MODULE_NAME', 'loyalty');
define('LOYALTY_MODULE_UPLOAD_FOLDER', module_dir_path(LOYALTY_MODULE_NAME, 'uploads'));
define('LOYALTY_REVISION', 100);
hooks()->add_action('admin_init', 'loyalty_permissions');
hooks()->add_action('app_admin_footer', 'loyalty_head_components');
hooks()->add_action('app_admin_footer', 'loyalty_add_footer_components');
hooks()->add_action('admin_init', 'loyalty_module_init_menu_items');
hooks()->add_filter('after_payment_added', 'add_transation');
hooks()->add_action('customers_navigation_end', 'init_loyalty_portal_menu');

// redeem feature hook
hooks()->add_action('omni_sale_discount', 'init_redemp_omni_sale');
hooks()->add_action('client_pt_footer_js','init_loyalty_omni_sale_js');
hooks()->add_action('after_cart_added', 'apply_redeem_log_program',10,2);  
hooks()->add_action('omni_sale_pos_redeem', 'init_redemp_pos');
hooks()->add_action('head_element_client', 'init_head_element');

// apply voucher hook
hooks()->add_filter('apply_other_voucher', 'apply_voucher_to_portal',10,3);

// apply membership program discount
hooks()->add_filter('apply_mbs_program_discount', 'apply_mbs_program_discount',10,2);

define('LOYALTY_PATH', 'modules/loyalty/uploads/');
/**
 * Register activation module hook
 */
register_activation_hook(LOYALTY_MODULE_NAME, 'loyalty_module_activation_hook');
/**
 * Load the module helper
 */
$CI = &get_instance();
$CI->load->helper(LOYALTY_MODULE_NAME . '/loyalty');

function loyalty_module_activation_hook() {
	$CI = &get_instance();
	require_once __DIR__ . '/install.php';
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(LOYALTY_MODULE_NAME, [LOYALTY_MODULE_NAME]);

/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function loyalty_module_init_menu_items() {

	$CI = &get_instance();
	if (has_permission('loyalty', '', 'view')) {

		$CI->app_menu->add_sidebar_menu_item('loyalty', [
			'name' => _l('loyalty'),
			'icon' => 'fa fa-handshake-o',
			'position' => 20,
		]);

		$CI->app_menu->add_sidebar_children_item('loyalty', [
            'slug'     => 'loyalty-user',
            'name'     => _l('user'),
            'icon'     => 'fa fa-user-circle menu-icon',
            'href'     => admin_url('loyalty/user'),
            'position' => 1,
            ]);
      
        
        $CI->app_menu->add_sidebar_children_item('loyalty', [
            'slug'     => 'loyalty-transation',
            'name'     => _l('transation'),
            'icon'     => 'fa fa-backward',
            'href'     => admin_url('loyalty/transation'),
            'position' => 2,
        ]);

        $CI->app_menu->add_sidebar_children_item('loyalty', [
            'slug'     => 'loyalty-mbs',
            'name'     => _l('membership'),
            'icon'     => 'fa fa-address-book',
            'href'     => admin_url('loyalty/membership?group=membership_rule'),
            'position' => 3,
        ]);

        $CI->app_menu->add_sidebar_children_item('loyalty', [
            'slug'     => 'loyalty-rule',
            'name'     => _l('loyalty_programs'),
            'icon'     => 'fa fa-address-book-o',
            'href'     => admin_url('loyalty/loyalty_rule'),
            'position' => 4,
        ]);

         $CI->app_menu->add_sidebar_children_item('loyalty', [
            'slug'     => 'loyalty-config',
            'name'     => _l('configuration'),
            'icon'     => 'fa fa-gears',
            'href'     => admin_url('loyalty/configruration'),
            'position' => 5,
        ]);
	}

}

/**
 * error log permissions
 * @return
 */
function loyalty_permissions() {
	$capabilities = [];
	$capabilities['capabilities'] = [
		'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
	];

	register_staff_capabilities('loyalty', $capabilities, _l('loyalty'));

}

/**
 * add head components
 */
function loyalty_head_components() {
	$CI = &get_instance();
	$viewuri = $_SERVER['REQUEST_URI'];
	if(!(strpos($viewuri, '/admin/loyalty/configruration') === false)){
        echo '<link href="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/css/configuration.css') . '?v=' . LOYALTY_REVISION . '"  rel="stylesheet" type="text/css" />';
    }

    if(!(strpos($viewuri, '/admin/loyalty/create_card') === false)){
        echo '<link href="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/css/create_card.css') . '?v=' . LOYALTY_REVISION . '"  rel="stylesheet" type="text/css" />';
    }

    if(!(strpos($viewuri, '/admin/loyalty/create_loyalty_rule') === false)){
        echo '<link href="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/css/loyalty_rule.css') . '?v=' . LOYALTY_REVISION . '"  rel="stylesheet" type="text/css" />';
    }

    if(!(strpos($viewuri, '/loyalty/loyalty_portal') === false)){
        echo '<link href="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/css/home_portal.css') . '?v=' . LOYALTY_REVISION . '"  rel="stylesheet" type="text/css" />';
    }

    if(!(strpos($viewuri, '/loyalty/mbs_program') === false)){
        echo '<link href="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/css/mbs_program.css') . '?v=' . LOYALTY_REVISION . '"  rel="stylesheet" type="text/css" />';
    }

    if(!(strpos($viewuri, '/loyalty/membership_program') === false)){
        echo '<link href="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/css/mbs_program_detail.css') . '?v=' . LOYALTY_REVISION . '"  rel="stylesheet" type="text/css" />';
    }

    if(!(strpos($viewuri, '/loyalty/loyalty_program_detail') === false)){
        echo '<link href="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/css/mbs_program_detail.css') . '?v=' . LOYALTY_REVISION . '"  rel="stylesheet" type="text/css" />';
    }
}

/**
 * add footer components
 * @return
 */
function loyalty_add_footer_components() {
	$CI = &get_instance();
	$viewuri = $_SERVER['REQUEST_URI'];	

	if(!(strpos($viewuri, '/admin/loyalty/create_card') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/create_card.js') . '?v=' . LOYALTY_REVISION . '" ></script>';
    }

    if(!(strpos($viewuri, '/admin/loyalty/create_loyalty_rule') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/loyalty_rule.js') . '?v=' . LOYALTY_REVISION . '"></script>';
    }

    if(!(strpos($viewuri, '/admin/loyalty/loyalty_rule') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/manage_loyalty_rule.js') . '?v=' . LOYALTY_REVISION . '"></script>';
    }

    if(!(strpos($viewuri, '/admin/loyalty/membership?group=membership_rule') === false) || !(strpos($viewuri, '/admin/loyalty/membership') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/manage_membership_rule.js') . '?v=' . LOYALTY_REVISION . '"></script>';
    }

    if(!(strpos($viewuri, '/admin/loyalty/membership?group=membership_program') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/manage_membership_program.js') . '?v=' . LOYALTY_REVISION . '"></script>';
    }

    if(!(strpos($viewuri, '/admin/loyalty/transation') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/mange_transation.js') . '?v=' . LOYALTY_REVISION . '"></script>';
    }
    
    if(!(strpos($viewuri, '/admin/loyalty/user') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/manage_user.js') . '?v=' . LOYALTY_REVISION . '"></script>';
    }

    if(!(strpos($viewuri, '/admin/loyalty/mbs_program') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/membership_program.js') . '?v=' . LOYALTY_REVISION . '"></script>';
    }
}

/**
 * add transation
 * @param integer $payment_id
 */
function add_transation($payment_id) {
    if(get_option('loyalty_setting') == 1 || get_option('loyalty_setting') == '1'){
        add_transation_loy($payment_id);
    }
    return $payment_id;
}

/**
 * init loyalty portal menu
 * 
 *       
 */
function init_loyalty_portal_menu()
{
    $item ='';
    if(is_client_logged_in()){
        $item .= '<li class="customers-nav-item">';
                      $item .= '<a href="'.site_url('loyalty/loyalty_portal').'">'._l("membership").'';        
                      $item .= '</a>';
                   $item .= '</li>';
    }
    echo html_entity_decode($item);

}

/**
 * Initializes the redemp omni sale.
 *
 * @param      <type>  $client  The client
 */
function init_redemp_omni_sale($client){
    $CI = &get_instance();
    if($client != ''){
        require "modules/loyalty/views/redeem.php"; 
    }
}

/**
 * Initializes the loyalty omni sale js.
 */
function init_loyalty_omni_sale_js(){
    $CI = &get_instance();
    $viewuri = $_SERVER['REQUEST_URI'];

    if(!(strpos($viewuri, '/omni_sales/omni_sales_client/view_overview') === false)){
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/omni_redeem.js') . '"></script>';
    }

    if(!(strpos($viewuri, '/admin/omni_sales/pos') === false)){
        render_admin_js_variables();
        echo '<script src="' . module_dir_url(LOYALTY_MODULE_NAME, 'assets/js/omni_redeem.js') . '"></script>';
    }
}

/**
 * { apply credit mbs program }
 *
 * @param    $invoice_id  The invoice identifier
 * 
 
 */
function apply_redeem_log_program($iv_number,$data){
    $CI = &get_instance();
    $CI->load->model('loyalty/loyalty_model');
    if($iv_number){
        $CI->loyalty_model->add_redeem_log_program($iv_number,$data);
    }
}

/**
 * Initializes the redemp to POS (Omni Sales).
 */
function init_redemp_pos(){
    require "modules/loyalty/views/pos_redeem.php"; 
}


/**
 * Apply voucher to portal (Omni Sales)
 * @param integer $data, $client, $voucher
 */
function apply_voucher_to_portal($data, $client, $voucher) {
    $CI = &get_instance();
    if(isset($data)){
        return $data;
    }else{
        $CI->load->model('loyalty/loyalty_model');
        $voucher = $CI->loyalty_model->apply_voucher_to_portal($client, $voucher);
        return $voucher;
    }   
}

/**
 * Initializes the head element.
 */
function init_head_element(){
    $viewuri = $_SERVER['REQUEST_URI'];
    if(!(strpos($viewuri, '/admin/omni_sales/pos') === false)){
        echo '<script src="'.site_url().'assets/plugins/jquery/jquery.min.js"></script>';
    }
}

/**
 * Apply membership program discount
 * @param $data, $client
 */
function apply_mbs_program_discount($data, $client) {
    $CI = &get_instance();
    if(isset($data) && count($data) > 0){
        return $data;
    }else{
        $CI->load->model('loyalty/loyalty_model');
        $program = $CI->loyalty_model->apply_mbs_program_discount($client);
        return $program;
    } 
}

if (defined('APP_CSRF_PROTECTION') && APP_CSRF_PROTECTION) {
    hooks()->add_action('post_redeem_head', 'csrf_jquery_token');
}