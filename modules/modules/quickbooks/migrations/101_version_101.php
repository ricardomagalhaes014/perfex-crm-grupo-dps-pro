<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration {
	public function up() {
		add_option('qb_client_id', '');
		add_option('qb_client_secret', '');
		add_option('qb_client_id_demo', '');
		add_option('qb_client_secret_demo', '');
		add_option('qb_test_mode_enabled', '1');
		add_option('qb_active', '0');
		add_option('qb_sales_tax_model', 'non_us_locales');
		add_option('qb_auto_operations_hour', '21');
		add_option('qb_default_asset_account', '');
		add_option('qb_default_income_account', '');
		add_option('qb_default_payment_account', '');
		add_option('qb_manual_cron_sync_enabled', '0');

		//Quickbooks Access Params
		add_option('qb_access_token', '');
		add_option('qb_refresh_token', '');
		add_option('qb_expiration_date', '');
		add_option('qb_is_expired', '');
		add_option('qb_realmid', '');
		add_option('qb_refresh_token_expires_in', '');

		$CI = &get_instance();
		//Items
		if (!$CI->db->table_exists(db_prefix() . 'qb_items')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "qb_items` (
						  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						  `item_id` int(11) NOT NULL UNIQUE KEY,
						  `qb_id` int(11) NOT NULL UNIQUE KEY
					) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		if (!$CI->db->field_exists("qb_incomeAccountRef", db_prefix() . 'items')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'items' . " ADD qb_incomeAccountRef int(11) DEFAULT NULL");
		}

		//Tax Agencies
		if (!$CI->db->table_exists(db_prefix() . 'qb_agencies')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "qb_agencies` (
					  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					  `agency_name` varchar(100) NOT NULL,
					  `qb_id` int(11) DEFAULT 0,
					  `dateadded` timestamp NOT NULL DEFAULT current_timestamp()
					) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		//Tax Rate table
		if (!$CI->db->field_exists("agency_id", db_prefix() . 'taxes')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'taxes' . " ADD agency_id int(11) DEFAULT NULL");
		}

		if (!$CI->db->field_exists("qb_id", db_prefix() . 'taxes')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'taxes' . " ADD qb_id int(11) DEFAULT 0");
		}

		if (!$CI->db->field_exists("qb_id", db_prefix() . 'clients')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'clients' . " ADD qb_id int(11) DEFAULT 0");
		}

		//Invoices
		if (!$CI->db->field_exists("qb_id", db_prefix() . 'invoices')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'invoices' . " ADD qb_id int(11) DEFAULT 0");
		}

		if (!$CI->db->field_exists("qb_id", db_prefix() . 'invoicepaymentrecords')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'invoicepaymentrecords' . " ADD qb_id int(11) DEFAULT 0");
		}

		if (!$CI->db->field_exists("qb_id", db_prefix() . 'payment_modes')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'payment_modes' . " ADD qb_id int(11) DEFAULT 0");
		}

		if (!$CI->db->field_exists("qb_id", db_prefix() . 'expenses')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses' . " ADD qb_id int(11) DEFAULT 0");
		}

		if (!$CI->db->field_exists("qb_id", db_prefix() . 'expenses_categories')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses_categories' . " ADD qb_id int(11) DEFAULT 0");
		}

		if (!$CI->db->field_exists("qb_paymentAccountRef", db_prefix() . 'expenses')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses' . " ADD qb_paymentAccountRef int(11) DEFAULT NULL");
		}

		// Quickbooks Chart Of Accounts
		if (!$CI->db->table_exists(db_prefix() . 'qb_chart_accounts')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "qb_chart_accounts` (
					     `chat_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					     `Id` int(11) NOT NULL,
						 `Name` varchar(100) NOT NULL,
						 `Description` TEXT DEFAULT NULL,
						 `FullyQualifiedName` varchar(100) NOT NULL,
						 `Active` varchar(100) NOT NULL,
						 `Classification` varchar(100) NOT NULL,
						 `AccountType` varchar(100) NOT NULL,
						 `AccountSubType` varchar(100) NOT NULL,
						 `AccountPurposes` varchar(100) DEFAULT NULL,
						 `AcctNum` varchar(100) DEFAULT NULL,
						 `AcctNumExtn` varchar(100) DEFAULT NULL,
						 `OpeningBalance` varchar(100) DEFAULT NULL,
						 `OpeningBalanceDate` varchar(100) DEFAULT NULL,
						 `CurrentBalance` varchar(100) DEFAULT NULL,
						 `CurrentBalanceWithSubAccounts` varchar(100) DEFAULT NULL,
						 `CurrencyRef` varchar(100) DEFAULT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}
	}
}