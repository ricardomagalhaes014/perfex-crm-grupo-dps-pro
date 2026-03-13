<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: QuickBooks® Online
Description: Module for handling QuickBooks® Online Integration.
Version: 1.0.5
Requires at least: 2.8.4^
Author: Swivernet
 */

require __DIR__ . '/vendor/autoload.php';

define('QUICKBOOKS_MODULE_NAME', 'quickbooks');

hooks()->add_action('before_js_scripts_render', 'intruit_script');
hooks()->add_action('settings_group_end', 'connect_script');
hooks()->add_action('settings_tab_footer', 'connect_script');
hooks()->add_action('admin_init', 'add_qb_to_settings_tabs');
hooks()->add_action('admin_init', 'quickbooks_permissions');
hooks()->add_action('after_cron_run', 'qb_cron_perform');
hooks()->add_filter('numbers_of_features_using_cron_job', 'quickbooks_numbers_of_features_using_cron_job');
hooks()->add_filter('used_cron_features', 'quickbooks_used_cron_features');
hooks()->add_filter('before_settings_updated', 'qb_monitor_settings_update');
hooks()->add_filter('not_importable_clients_fields', 'qb_not_importable_clients_fields');
hooks()->add_filter('module_quickbooks_action_links', 'module_quickbooks_action_links');

if ((bool) get_option('qb_active') && get_option('qb_access_token') && time() < get_option('qb_expiration_date')) {

	hooks()->add_action('admin_init', 'quickbooks_module_init_menu_items');

	hooks()->add_action('before_js_scripts_render', 'quickbooks_variable_script', 9);
	hooks()->add_action('before_js_scripts_render', 'quickbooks_script', 10);

	//Items
	hooks()->add_action('item_created', 'add_item_to_qb'); //add to qb;
	hooks()->add_action('item_updated', 'update_item_in_qb'); //update in qb;
	hooks()->add_action('item_deleted', 'delete_item_in_qb'); //delete in qb;

	//Invoices
	hooks()->add_action('after_invoice_added', 'add_invoice_to_qb'); //add to qb;
	hooks()->add_action('after_invoice_updated', 'update_invoice_in_qb'); //update in qb;
	hooks()->add_action('before_invoice_deleted', 'delete_invoice_in_qb'); //delete in qb;

	//Payments
	hooks()->add_action('after_payment_added', 'add_payment_to_qb'); //add to qb;
	hooks()->add_filter('before_payment_updated', 'update_payment_in_qb', 10, 2); //update in qb;
	hooks()->add_action('before_payment_deleted', 'delete_payment_in_qb'); //delete in qb;

}

$CI = &get_instance();

/*
 * Load the module helper
 */
$CI->load->helper(QUICKBOOKS_MODULE_NAME . '/quickbooks');

/*
 * Load the module library
 */
$CI->load->library(QUICKBOOKS_MODULE_NAME . '/quickbooks_module');

/*
 * Load the module model
 */
$CI->load->model(QUICKBOOKS_MODULE_NAME . '/quickbooks_model');

/*
 * Register activation module hook
 */
register_activation_hook(QUICKBOOKS_MODULE_NAME, 'quickbooks_activation_hook');

function quickbooks_activation_hook() {
	$CI = &get_instance();
	require_once __DIR__ . '/install.php';
}

/**
 * Register deactivation module hook
 */
register_uninstall_hook(QUICKBOOKS_MODULE_NAME, 'quickbooks_uninstall_hook');

function quickbooks_uninstall_hook() {
	$CI = &get_instance();
	require_once __DIR__ . '/uninstall.php';
}

/**
 * Register deactivation module hook
 */
register_deactivation_hook(QUICKBOOKS_MODULE_NAME, 'quickbooks_deactivation_hook');

function quickbooks_deactivation_hook() {
	$CI = &get_instance();
	require_once __DIR__ . '/deactivate.php';
}

/*
 * Register language files, must be registered if the module is using languages
 */
register_language_files(QUICKBOOKS_MODULE_NAME, [QUICKBOOKS_MODULE_NAME]);

function quickbooks_variable_script() {
	echo "<script> var qb_sales_tax_model = '" . get_option('qb_sales_tax_model') . "'; var qb_manual_cron_sync_enabled = '" . get_option('xero_manual_cron_sync_enabled') . "'; </script>";
}

/**
 * Add additional settings for this module in the module list area
 * @param  array $actions current actions
 * @return array
 */
function module_quickbooks_action_links($actions) {
	$actions[] = '<a href="' . admin_url('settings?group=quickbooks') . '">' . _l('quickbooks_settings') . '</a>';

	return $actions;
}

function quickbooks_script() {
	$CI = &get_instance();
	return $CI->app_scripts->add('quickbooks-js', module_dir_url('quickbooks', 'assets/js/quickbooks.js') . '?v=' . $CI->app_scripts->core_version(), 'admin', ['app-js']);
}

function intruit_script() {
	$CI = &get_instance();
	if ($CI->input->get('group', true) == 'quickbooks') {
		return $CI->app_scripts->add('intruit-js', 'https://appcenter.intuit.com/Content/IA/intuit.ipp.anywhere-1.3.3.js', 'admin', ['app-js']);
	}
}

function connect_script($tab) {
	$CI = &get_instance();
	if ($tab['slug'] == 'quickbooks') {
		echo "<script>
	  'use strict';
      intuit.ipp.anywhere.setup({
              grantUrl: '" . site_url('quickbooks/quickbooks_auth') . "',
              datasources: {
                  quickbooks : true,
                  payments   : true
              },
              paymentOptions:{
                  intuitReferred : true
              }
      });
      </script>";
	}
}

function add_qb_to_settings_tabs() {
	$CI = &get_instance();
	$CI->app_tabs->add_settings_tab('quickbooks', [
		'name' => _l('quickbooks'),
		'view' => 'quickbooks/settings/quickbooks',
		'position' => 84,
	]);
}

function quickbooks_numbers_of_features_using_cron_job($number) {
	$feature = get_option('qb_active');
	$number += $feature;

	return $number;
}

function quickbooks_used_cron_features($features) {
	$feature = get_option('qb_active');
	if ($feature > 0) {
		array_push($features, 'Auto quickbooks connection');
	}

	return $features;
}

/*
 * Quickbooks cron job
 */
function qb_cron_perform($manually) {
	$CI = &get_instance();
	$CI->quickbooks_model->hourly_scheduled_synch($manually);
	$CI->quickbooks_model->daily_scheduled_synch($manually);
}

/**
 * Monitor Quickbooks Settings Changes
 * @param  [type] $data
 */
function qb_monitor_settings_update($data) {
	$_posted = $data['settings'];
	if (isset($data['settings']['qb_active'])) {
		$_stored = array(
			"qb_active" => get_option("qb_active"),
			"qb_client_id" => get_option("qb_client_id"),
			"qb_client_secret" => get_option("qb_client_secret"),
			"qb_client_id_demo" => get_option("qb_client_id_demo"),
			"qb_client_secret_demo" => get_option("qb_client_secret_demo"),
			"qb_test_mode_enabled" => get_option("qb_test_mode_enabled"),
		);

		$_posted_check = array(
			"qb_active" => $_posted["qb_active"],
			"qb_client_id" => $_posted["qb_client_id"],
			"qb_client_secret" => $_posted["qb_client_secret"],
			"qb_client_id_demo" => $_posted["qb_client_id_demo"],
			"qb_client_secret_demo" => $_posted["qb_client_secret_demo"],
			"qb_test_mode_enabled" => $_posted["qb_test_mode_enabled"],
		);

		$diff_result = array_diff($_posted_check, $_stored);

		if (count($diff_result) > 0) {
			update_option('qb_access_token', '');
			update_option('qb_refresh_token', '');
			update_option('qb_expiration_date', '');
			update_option('qb_is_expired', '');
			update_option('qb_realmid', '');
			update_option('qb_refresh_token_expires_in', '');
		}
	}

	return $data;
}

/**
 * Add qb_id to non_importable_clients_fields
 * @param  array $data
 */
function qb_not_importable_clients_fields($data) {
	array_push($data, 'qb_id');
	return $data;
}

/*
 * Init quickbooks module menu items in setup in admin_init hook
 */
function quickbooks_module_init_menu_items() {
	$CI = &get_instance();

	$CI->app->add_quick_actions_link([
		'name' => _l('quickbooks_short'),
		'permission' => 'quickbooks',
		'url' => 'quickbooks',
		'position' => 2,
	]);

	if (has_permission('quickbooks', '', 'view')) {

		$CI->app_menu->add_sidebar_menu_item('quickbooks', [
			'name' => _l('quickbooks_short'),
			'href' => admin_url('quickbooks'),
			'icon' => 'fa fa-cloud',
			'position' => 21,
		]);

		$CI->app_menu->add_setup_children_item('finance', [
			'slug' => 'quickbooks',
			'name' => _l('quickbooks'),
			'href' => admin_url('quickbooks'),
			'position' => 2,
		]);

		if (get_option('qb_sales_tax_model') == 'non_us_locales') {
			$CI->app_menu->add_setup_children_item('finance', [
				'slug' => 'tax_agency',
				'name' => _l('qb_tax_agency'),
				'href' => admin_url('quickbooks/tax_agency'),
				'position' => 3,
			]);
		}
	}
}
/*
 * Quickbooks Permissions
 */
function quickbooks_permissions() {
	$capabilities = [];

	$capabilities['capabilities'] = [
		'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
		'create' => _l('permission_create'),
		'edit' => _l('permission_edit'),
		'delete' => _l('permission_delete'),
	];

	register_staff_capabilities('quickbooks', $capabilities, _l('quickbooks'));
}

/******************** ITEM **********************/
/*
 * Add Item
 */
function add_item_to_qb($id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_item($id);

}

/*
 * Update Item
 */
function update_item_in_qb($id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_item($id);
}

/*
 * Delete Item
 */
function delete_item_in_qb($id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_item($id, 'delete');
}

/******************** PAYMENT **********************/
/*
 * Add Payment
 */
function add_payment_to_qb($id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_payment($id);
}

/*
 * Update Payment
 */
function update_payment_in_qb($data, $id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_payment($id, 'add_edit', $data);
	return $data;
}

/*
 * Delete Payment
 */
function delete_payment_in_qb($data = array()) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_payment($data['paymentid'], 'delete');
}

/******************** INVOICE **********************/
/*
 * Add Invoice
 */
function add_invoice_to_qb($id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_invoice($id);
}

/*
 * Update Invoice
 */
function update_invoice_in_qb($id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_invoice($id);
}

/*
 * Delete Invoice
 */
function delete_invoice_in_qb($id) {
	$CI = &get_instance();
	$CI->quickbooks_model->qb_invoice($id, 'delete');
}