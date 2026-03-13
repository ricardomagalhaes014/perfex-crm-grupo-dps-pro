<?php

defined('BASEPATH') or exit('No direct script access allowed');

delete_option('qb_client_id');
delete_option('qb_client_secret');
delete_option('qb_client_id_demo');
delete_option('qb_client_secret_demo');
delete_option('qb_test_mode_enabled');
delete_option('qb_active');
delete_option('qb_sales_tax_model');
delete_option('qb_auto_operations_hour');
delete_option('qb_default_asset_account');
delete_option('qb_default_income_account');
delete_option('qb_default_payment_account');
delete_option('qb_manual_cron_sync_enabled');

delete_option('qb_auto_cron_sync_clients_enabled');
delete_option('qb_auto_cron_sync_items_enabled');
delete_option('qb_auto_cron_sync_taxes_enabled');
delete_option('qb_auto_cron_sync_invoices_enabled');
delete_option('qb_auto_cron_sync_payments_enabled');
delete_option('qb_auto_cron_sync_expenses_enabled');

//Quickbooks Access Params
delete_option('qb_access_token');
delete_option('qb_refresh_token');
delete_option('qb_expiration_date');
delete_option('qb_is_expired');
delete_option('qb_realmid');
delete_option('qb_refresh_token_expires_in');

//Database
$CI = &get_instance();

if ($CI->db->table_exists(db_prefix() . 'qb_chart_accounts')) {
	$CI->db->query('DROP TABLE `' . db_prefix() . 'qb_chart_accounts`;');
}

// qb Items
if ($CI->db->table_exists(db_prefix() . 'qb_items')) {
	$CI->db->query('DROP TABLE `' . db_prefix() . 'qb_items`;');
}

if ($CI->db->field_exists("qb_incomeAccountRef", db_prefix() . 'items')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'items' . " DROP COLUMN qb_incomeAccountRef");
}

// qb Clients
if ($CI->db->field_exists("qb_id", db_prefix() . 'clients')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'clients' . " DROP COLUMN qb_id");
}

// qb Invoices
if ($CI->db->field_exists("qb_id", db_prefix() . 'invoices')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'invoices' . " DROP COLUMN qb_id");
}

// qb Payment Methods
if ($CI->db->field_exists("qb_id", db_prefix() . 'payment_modes')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'payment_modes' . " DROP COLUMN qb_id");
}

// qb Payment Record
if ($CI->db->field_exists("qb_payment_id", db_prefix() . 'invoicepaymentrecords')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'invoicepaymentrecords' . " DROP COLUMN qb_payment_id");
}

// qb Expenses
if ($CI->db->field_exists("qb_expense_id", db_prefix() . 'expenses')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses' . " DROP COLUMN qb_expense_id");
}

if ($CI->db->field_exists("qb_paymentAccountRef", db_prefix() . 'expenses')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses' . " DROP COLUMN qb_paymentAccountRef");
}

// qb Expense Categories
if ($CI->db->field_exists("qb_id", db_prefix() . 'expenses_categories')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses_categories' . " DROP COLUMN qb_id");
}

if ($CI->db->field_exists("agency_id", db_prefix() . 'taxes')) {
	$CI->db->query("ALTER TABLE " . db_prefix() . 'taxes' . " DROP COLUMN agency_id");
}

// qb Taxes
if ($CI->db->table_exists(db_prefix() . 'qb_taxes')) {
	$CI->db->query('DROP TABLE `' . db_prefix() . 'qb_taxes`;');
}

// qb Tax Agency
if ($CI->db->table_exists(db_prefix() . 'qb_agencies')) {
	$CI->db->query('DROP TABLE `' . db_prefix() . 'qb_agencies`;');
}

//Remove from modules register
$CI->db->where('module_name', 'quickbooks');
$CI->db->delete(db_prefix() . 'modules');

//Remove items non importable field: qb_incomeAccountRef

/**
 * Restore core files
 * @return [type] [description]
 */
function qb_modFile($fname, $searchF, $replaceW) {
	$fhandle = fopen($fname, "r");
	$content = fread($fhandle, filesize($fname));
	if (strstr($content, $searchF)) {
		$content = str_replace($searchF, $replaceW, $content);
		$fhandle = fopen($fname, "w");
		fwrite($fhandle, $content);
	}
	fclose($fhandle);
	return true;
}

//Remove items non importable field: qb_incomeAccountRef
$fname1 = LIBSPATH . "import/Import_items.php";
$searchF1 = "notImportableFields = ['id', 'qb_incomeAccountRef'];";
$replaceW1 = "notImportableFields = ['id'];";
qb_modFile($fname1, $searchF1, $replaceW1);

//Modify tax model
$fname2 = APPPATH . "models/Taxes_model.php";
$searchF2 = "\$this->db->select('id, name, taxrate');";
$replaceW2 = "";
qb_modFile($fname2, $searchF2, $replaceW2);