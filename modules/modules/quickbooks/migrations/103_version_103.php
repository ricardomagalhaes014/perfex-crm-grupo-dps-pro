<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration {
	public function up() {
		add_option('qb_auto_cron_sync_clients_enabled', '1');
		add_option('qb_auto_cron_sync_items_enabled', '1');
		add_option('qb_auto_cron_sync_taxes_enabled', '1');
		add_option('qb_auto_cron_sync_invoices_enabled', '1');
		add_option('qb_auto_cron_sync_payments_enabled', '1');
		add_option('qb_auto_cron_sync_expenses_enabled', '1');

		$CI = &get_instance();
		if ($CI->db->field_exists("qb_id", db_prefix() . 'taxes')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'taxes' . " DROP COLUMN qb_id");
		}

		if (!$CI->db->table_exists(db_prefix() . 'qb_taxes')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "qb_taxes` (
					  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					  `tax_id` int(11) NOT NULL UNIQUE KEY,
					  `qb_id` int(11) DEFAULT NULL,
					  `dateadded` timestamp NOT NULL DEFAULT current_timestamp()
					) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		//Payments
		if ($CI->db->field_exists("qb_id", db_prefix() . 'invoicepaymentrecords')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'invoicepaymentrecords' . " DROP COLUMN qb_id");
		}

		if (!$CI->db->field_exists("qb_payment_id", db_prefix() . 'invoicepaymentrecords')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'invoicepaymentrecords' . " ADD qb_payment_id int(11) DEFAULT 0");
		}

		//Expenses
		if ($CI->db->field_exists("qb_id", db_prefix() . 'expenses')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses' . " DROP COLUMN qb_id");
		}

		if (!$CI->db->field_exists("qb_expense_id", db_prefix() . 'expenses')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses' . " ADD qb_expense_id int(11) DEFAULT 0");
		}

		if ($CI->db->field_exists("name", db_prefix() . 'expenses_categories')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'expenses_categories' . " ADD UNIQUE (name);");
		}

	}
}