<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_110 extends App_module_migration
{
	public function up()
	{
		$CI = &get_instance();

		// Check if version 1.0.4 is not available yet
		$CI = &get_instance();   
		add_option('omni_show_products_by_department', 0);
		if (!$CI->db->field_exists('department' ,db_prefix() . 'sales_channel_detailt')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'sales_channel_detailt`
				ADD COLUMN `department` text
				');
		}
		add_option('bill_header_pos', '<div class="cls_003" style="text-align: center;"><span class="cls_003"><strong>PURCHASE RECEIPT</strong></span></div>');
		add_option('bill_footer_pos', '<div class="cls_004"><span class="cls_004">Thank you for shopping with us. Please come again</span></div>');

		// Check if version 1.0.5 is not available yet
		if (!$CI->db->table_exists(db_prefix() . 'omni_shift')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "omni_shift` (
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`staff_id` int(11) not null,
				`shift_code` varchar(150) null,
				`granted_amount` DECIMAL(15,2) not null default 0.00,
				`incurred_amount` DECIMAL(15,2) not null default 0.00,
				`closing_amount` DECIMAL(15,2) not null default 0.00,
				`status` int not null DEFAULT 1,
				`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		if (!$CI->db->table_exists(db_prefix() . 'omni_shift_history')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "omni_shift_history` (
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`shift_id` int(11) not null,
				`action` varchar(150) null,
				`granted_amount` DECIMAL(15,2) not null default 0.00,
				`current_amount` DECIMAL(15,2) not null default 0.00,
				`customer_amount` DECIMAL(15,2) not null default 0.00,
				`balance_amount` DECIMAL(15,2) not null default 0.00,
				`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		if (!$CI->db->field_exists('staff_id' ,db_prefix() . 'omni_shift_history')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'omni_shift_history`
				ADD COLUMN `staff_id` int(11) null           
				');
		}

		if (!$CI->db->field_exists('customer_id' ,db_prefix() . 'omni_shift_history')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'omni_shift_history`
				ADD COLUMN `customer_id` int(11) null           
				');
		}

		if (!$CI->db->field_exists('type' ,db_prefix() . 'omni_shift_history')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'omni_shift_history`
				ADD COLUMN `type` varchar(50) null           
				');
		}

		if (!$CI->db->field_exists('order_value' ,db_prefix() . 'omni_shift_history')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'omni_shift_history`
				ADD COLUMN order_value DECIMAL(15,2) not null default 0.00          
				');
		}
		
		if (!$CI->db->field_exists('order_value' ,db_prefix() . 'omni_shift')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'omni_shift`
				ADD COLUMN order_value DECIMAL(15,2) not null default 0.00          
				');
		}

		if (!$CI->db->field_exists('tax' ,db_prefix() . 'cart_detailt')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart_detailt`
				ADD COLUMN `tax` text NULL
				');
		}

		if (!$CI->db->field_exists('discount_type_str' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `discount_type_str` text null
				');
		}

		if (!$CI->db->field_exists('discount_percent' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `discount_percent` DECIMAL(15,2) null
				');
		}

		if (!$CI->db->field_exists('adjustment' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `adjustment` DECIMAL(15,2) null
				');
		}

		if (!$CI->db->field_exists('currency' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `currency` INT(11) null
				');
		}

		if (!$CI->db->field_exists('discount_total' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				CHANGE COLUMN `discount_total` `discount_total` DECIMAL(15,2) NULL;
				');
		}

		if (!$CI->db->field_exists('currency' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `currency` INT(11) null
				');
		}

		if (!$CI->db->field_exists('terms' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `terms` TEXT null
				');
		}
		if (!$CI->db->table_exists(db_prefix() . 'omni_cart_payment')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "omni_cart_payment` (
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`cart_id` int(11) not null,
				`payment_id` varchar(30) not null,
				`payment_name` varchar(100) null,
				`customer_pay` DECIMAL(15,2) not null default \"0.00\",
				`datecreator` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		// Check if version 1.0.6 is not available yet
		$CI->db->where('channel', 'pre_order');
		$sales_channel = $CI->db->get(db_prefix().'sales_channel')->row();
		if(!$sales_channel){
			$data['channel'] = 'pre_order';
			$data['status'] = 'deactive';
			$CI->db->insert(db_prefix().'sales_channel' , $data);
		}
		if (!$CI->db->field_exists('enable' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `enable` int not null default 1
				');
		}
		add_option('omni_default_seller', '');
		if (!$CI->db->field_exists('duedate' ,db_prefix() . 'cart')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
				ADD COLUMN `duedate` date NULL
				');
		}

		if (!$CI->db->table_exists(db_prefix() . 'omni_pre_order_product_setting')) {
			$CI->db->query('CREATE TABLE `' . db_prefix() . "omni_pre_order_product_setting` (
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`channel_id` int(11) not null,
				`customer_group` text null,
				`customer` text null,
				`group_product_id` int(11) NULL,
				`datecreator` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
		}

		if (!$CI->db->field_exists('pre_order_product_st_id' ,db_prefix() . 'sales_channel_detailt')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'sales_channel_detailt`
				ADD COLUMN `pre_order_product_st_id` int(11) null
				');
		}

		if (!$CI->db->field_exists('customer_group' ,db_prefix() . 'sales_channel_detailt')) {
			$CI->db->query('ALTER TABLE `' . db_prefix() . 'sales_channel_detailt`
				ADD COLUMN `customer_group` text null,
				ADD COLUMN `customer` text null
				');
		}
		if ($CI->db->field_exists('type' ,db_prefix() . 'emailtemplates')) {
			$CI->db->where('type', "purchase_receipt");
			$CI->db->update( db_prefix() . 'emailtemplates', ['type' => "omni_sales"]);
		}
		create_email_template('Pre-orders notify', 'Hi {seller_name}! <br /><br />You have a new order from {buyer_name}, the order is created at {create_at}. View order details: {link}.<br />', 'omni_sales', 'Pre-orders notify (Sent to seller)', 'pre-orders-notify');
		create_email_template('Pre-orders handover', 'Hi {to_name}! <br /><br />{from_name} has handed over an order to you. View order details: {link}.<br />', 'omni_sales', 'Pre-orders handover', 'pre-orders-handover');

		// Check if version 1.0.7 is not available yet
		add_option('number_of_days_to_save_diary_sync', 30);
		
		// Check if version 1.0.8 is not available yet
        if (!$CI->db->field_exists('shipping_tax_json' ,db_prefix() . 'cart')) {
          $CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
            ADD COLUMN `shipping_tax_json` varchar(150) NULL
            ');
        }

		// Check if version 1.0.9 is not available yet
        if (!$CI->db->field_exists('woo_customer_id' ,db_prefix() . 'clients')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'clients`
              ADD COLUMN `woo_customer_id` int NULL DEFAULT 0,
              ADD COLUMN `woo_channel_id` int NULL DEFAULT 0
              ');
        }

        // version 1.1.0
		if (!$CI->db->field_exists('shipping_tax_json' ,db_prefix() . 'cart')) {
		    $CI->db->query('ALTER TABLE `' . db_prefix() . 'cart`
		      ADD COLUMN `shipping_tax_json` varchar(150) NULL
		      ');
		}
		
	}
}
