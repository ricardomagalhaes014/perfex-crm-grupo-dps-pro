<?php
defined('BASEPATH') or exit('No direct script access allowed');

add_option('real_Gogle_Map_API_Code', '', 1);
add_option('real_property_prefix', 'MLS#', 1);
add_option('real_property_number', '1', 1);
add_option('real_company_prefix', 'AG#', 1);
add_option('real_company_number', '1', 1);
add_option('real_property_owner_prefix', 'OW#', 1);
add_option('real_property_owner_number', '1', 1);
add_option('real_business_broker_prefix', 'BROKER#', 1);
add_option('real_business_broker_number', '1', 1);
add_option('staff_code_prefix', 'EC#', 1);
add_option('staff_code_number', '1', 1);
add_option('real_broker_staff_prefix', 'BS#', 1);
add_option('real_broker_staff_number', '1', 1);
add_option('real_show_broker_portal', '1', 1);


if (!$CI->db->table_exists(db_prefix() . "real_companies")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_companies` (
		`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` varchar(250) NOT NULL,
		`active` tinyint(1),
		`email` varchar(100) NOT NULL,
		`phonenumber` varchar(30) DEFAULT NULL,
		`country` int(11) NOT NULL DEFAULT '0',
		`city` TEXT NULL,
		`zip` TEXT NULL,
		`state` TEXT NULL,
		`address` TEXT NULL,
		`website` TEXT NULL,
		`vat` TEXT NULL,
		`default_language` varchar(40) NULL,
		`default_currency` INT(11) NOT NULL DEFAULT '0',
		`upload_file_size` int(11) DEFAULT NULL,
		`billing_street` TEXT NULL,
		`billing_city` TEXT NULL,
		`billing_state` TEXT NULL,
		`billing_zip` TEXT NULL,
		`billing_country` int(11) NULL DEFAULT '0',
		`shipping_street` TEXT NULL,
		`shipping_city` TEXT NULL,
		`shipping_state` TEXT NULL,
		`shipping_zip` TEXT NULL,
		`shipping_country` int(11) NULL DEFAULT '0',
		`related_type` VARCHAR(15) NULL DEFAULT 'company' COMMENT 'company or business_broker',
		`construction_permission` INT(11) NULL,
		`code` TEXT NULL,
		`facebook_url` TEXT NULL,
		`instagram_url` TEXT NULL,
		`whatsapp_url` TEXT NULL,
		`plan_id` INT(11) NULL,
		`announcement_message` TEXT NULL,
		`privacy` VARCHAR(15) NULL DEFAULT 'private',
		`verification_status` VARCHAR(15) NULL DEFAULT 'verified',
		`about_information` TEXT NULL,
		`hash` varchar(32) DEFAULT NULL,
		`client_id` INT(11) NULL,
		`approval_managers` TEXT NULL,

		`created_date` datetime NOT NULL,
		`staff_id` int(11) NULL,

		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->field_exists("role_type", db_prefix() . "roles")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "roles`
		ADD COLUMN `role_type` TEXT NULL
		;");
}

if (!$CI->db->table_exists(db_prefix() . "real_plans")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_plans` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` TEXT NULL,
		`monthly_listing_number` INT(11) NULL DEFAULT 0,
		`read_only` tinyint(1),
		`admin_id` int(11) NULL DEFAULT 0,
		`rate` DECIMAL(15,2) NULL DEFAULT '0.00',
		`description` TEXT NULL,
		`long_description` TEXT NULL,
		`role_id` INT(11) NULL,
		`payment_type` VARCHAR(16) NULL DEFAULT 'one_time_payment',
		`created_id` int(11) NULL DEFAULT 0,
		`active` tinyint(1),
		`date_created` datetime NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->field_exists("company_id", db_prefix() . "staff")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "staff`
		ADD COLUMN `company_id` int(11) NULL DEFAULT '0',
		ADD COLUMN `staff_type` VARCHAR(200) NULL DEFAULT 'staff' COMMENT 'company or staff',
		ADD COLUMN `mark_public` tinyint(1) NULL DEFAULT '0'

		;");
}

if (!$CI->db->field_exists("staff_identifi", db_prefix() . "staff")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "staff`
		ADD COLUMN `staff_identifi` varchar(20) NULL
		;");
}

if (!$CI->db->table_exists(db_prefix() . "real_broker_staffs")) {
	$CI->db->query("CREATE TABLE " . db_prefix() . "real_broker_staffs (
		`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`email` varchar(100) NOT NULL,
		`code` TEXT NULL,
		`firstname` varchar(50) NOT NULL,
		`lastname` varchar(50) NOT NULL,
		`facebook` mediumtext NULL,
		`linkedin` mediumtext NULL,
		`phonenumber` varchar(30) DEFAULT NULL,
		`skype` varchar(50) DEFAULT NULL,
		`password` varchar(250) NOT NULL,
		`datecreated` datetime NOT NULL,
		`profile_image` varchar(191) DEFAULT NULL,
		`last_ip` varchar(40) DEFAULT NULL,
		`last_login` datetime DEFAULT NULL,
		`last_activity` datetime DEFAULT NULL,
		`last_password_change` datetime DEFAULT NULL,
		`new_pass_key` varchar(32) DEFAULT NULL,
		`new_pass_key_requested` datetime DEFAULT NULL,
		`active` int(11) NOT NULL DEFAULT '1',
		`default_language` varchar(40) DEFAULT NULL,
		`direction` varchar(3) DEFAULT NULL,
		`media_path_slug` varchar(191) DEFAULT NULL,
		`hourly_rate` decimal(15,2) NOT NULL DEFAULT '0.00',
		`introduce_yourself` mediumtext NULL,
		`company_id` int(11) NOT NULL,
		`is_primary` int(11) NOT NULL DEFAULT '1',
		`email_signature` mediumtext NULL,


		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->table_exists(db_prefix() . "real_notifications")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_notifications` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`isread` int(11) NOT NULL DEFAULT '0',
		`isread_inline` tinyint(1) NOT NULL DEFAULT '0',
		`date` datetime NOT NULL,
		`description` text NOT NULL,
		`fromuserid` int(11) NOT NULL,
		`fromclientid` int(11) NOT NULL DEFAULT '0',
		`from_fullname` varchar(100) NOT NULL,
		`touserid` int(11) NOT NULL,
		`fromcompany` int(11) DEFAULT NULL,
		`link` mediumtext,
		`additional_data` text,

		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

create_email_template('Your password has been changed','<span style=\"font-size: 14pt;\"><strong>You have changed your password.<br /></strong></span><br /> Please, keep it in your records so you don\'t forget it.<br /> <br /> Your email address for login is: {staff_email}<br /><br /> If this wasnt you, please contact us.<br /><br />{email_signature}', 'realestate', 'Password Reset - Confirmation', 'broker-staff-password-reseted');

if (!$CI->db->field_exists("listing_type" ,db_prefix() . "items")) { 
	$CI->db->query("ALTER TABLE `" . db_prefix() . "items`

		ADD COLUMN `listing_type` TEXT NULL,
		ADD COLUMN `listing_service_type` TEXT NULL,
		ADD COLUMN `transaction_type` VARCHAR(200) NULL COMMENT 'Sale or Rent or Sale_and_Rent',
		ADD COLUMN `rent_price` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `property_style` TEXT NULL,
		ADD COLUMN `proj_completion_date` date NULL,
		ADD COLUMN `floor_location` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `street_number` TEXT NULL,
		ADD COLUMN `street_dir_pre` TEXT NULL,
		ADD COLUMN `street_name` TEXT NULL,
		ADD COLUMN `street_type` TEXT NULL,
		ADD COLUMN `street_dir_pos` TEXT NULL,
		ADD COLUMN `unit_number` TEXT NULL,
		ADD COLUMN `city` TEXT NULL,
		ADD COLUMN `state` TEXT NULL,
		ADD COLUMN `zip` TEXT NULL,
		ADD COLUMN `zip_4` TEXT NULL,
		ADD COLUMN `country` TEXT NULL,
		ADD COLUMN `total_of_floors` DECIMAL(15,2) NULL,
		ADD COLUMN `latitude` TEXT NULL,
		ADD COLUMN `longitude` TEXT NULL,
		
		ADD COLUMN `operating_expenses` DECIMAL(15,2) NULL,
		ADD COLUMN `net_operating_income` DECIMAL(15,2) NULL,
		ADD COLUMN `net_operating_income_type` TEXT NULL,
		ADD COLUMN `sale_includes` TEXT NULL,
		ADD COLUMN `annual_expenses` DECIMAL(15,2) NULL,
		ADD COLUMN `annual_TTL_schedule_income` DECIMAL(15,2) NULL,
		ADD COLUMN `annual_income_type` TEXT NULL,
		ADD COLUMN `number_of_tenants` TEXT NULL,
		
		ADD COLUMN `beds` INT(11) NULL,
		ADD COLUMN `full_baths` INT(11) NULL,
		ADD COLUMN `half_baths` INT(11) NULL,
		ADD COLUMN `sqFt_heated` DECIMAL(15,2) NULL,
		ADD COLUMN `sqFt_heated_source` TEXT NULL,
		ADD COLUMN `sqFt_total` DECIMAL(15,2) NULL,
		ADD COLUMN `SqFt_total_source` TEXT NULL,
		ADD COLUMN `fireplace` TEXT NULL,
		ADD COLUMN `fireplace_description` TEXT NULL,
		ADD COLUMN `kitchen` TEXT NULL,
		ADD COLUMN `appliances_included` TEXT NULL,
		ADD COLUMN `utilities` TEXT NULL,
		ADD COLUMN `sewer` TEXT NULL,
		ADD COLUMN `water` TEXT NULL,
		ADD COLUMN `heating_and_fuel` TEXT NULL,
		ADD COLUMN `air_conditioning` TEXT NULL,
		ADD COLUMN `electrical_Service` TEXT NULL,
		ADD COLUMN `security_features` TEXT NULL,
		ADD COLUMN `accessibility_features` TEXT NULL,
		ADD COLUMN `floor_covering` TEXT NULL,
		ADD COLUMN `ceiling_type` TEXT NULL,
		ADD COLUMN `window_Features` TEXT NULL,
		ADD COLUMN `furnished` VARCHAR(100) NULL,
		ADD COLUMN `finishing` TEXT NULL,
		ADD COLUMN `hydro_included` TEXT NULL,
		ADD COLUMN `water_included` TEXT NULL,
		ADD COLUMN `gas_included` TEXT NULL,

		ADD COLUMN `owner_name` TEXT NULL,
		ADD COLUMN `owner_phone` TEXT NULL,
		ADD COLUMN `ownership` TEXT NULL,

		ADD COLUMN `realtor_information` TEXT NULL,
		ADD COLUMN `realtor_information_confidential` TEXT NULL,
		ADD COLUMN `disclosures` TEXT NULL,
		ADD COLUMN `possession` TEXT NULL,
		ADD COLUMN `status` TEXT NULL,
		ADD COLUMN `can_be_property_listing` TEXT NULL,
		ADD COLUMN `commission` TEXT NULL,

		ADD COLUMN `listing_privacy` VARCHAR(40) NOT NULL DEFAULT 'internal_for_company_users',
		ADD COLUMN `hash` varchar(32) DEFAULT NULL,

		ADD COLUMN `energy_efficiency` DECIMAL(15,2) NULL DEFAULT 0,

		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `company_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `broker_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or broker',
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0,

		ADD COLUMN `date_sold` datetime NULL,
		ADD COLUMN `date_created` datetime NULL,
		ADD COLUMN `date_update` date NULL
		;");
}

if (!$CI->db->field_exists("rental_value" ,db_prefix() . "items")) { 
	$CI->db->query("ALTER TABLE `" . db_prefix() . "items`
		ADD COLUMN `rental_value` DECIMAL(15,2) NULL DEFAULT '0',
		ADD COLUMN `rental_type` VARCHAR(6) NULL,
		ADD COLUMN `owner_email` TEXT NULL
		;");
}


if (!$CI->db->table_exists(db_prefix() . "real_property_listing_rooms")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_property_listing_rooms` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`item_id` int(11) NOT NULL DEFAULT '0',
		`room_type` text NOT NULL,
		`rooms_level` text NOT NULL,
		`room_demension_width` DECIMAL(15,2),
		`room_demension_lenght` DECIMAL(15,2),
		`room_benefits` TEXT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}
if (!$CI->db->field_exists('commodity_code' ,db_prefix() . 'items')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "items`
		ADD COLUMN `commodity_code` varchar(100) NOT NULL;
		");
}

if (!$CI->db->field_exists('use_code' ,db_prefix() . 'items')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "items`
		ADD COLUMN `use_code` TEXT NULL,
		ADD COLUMN `new_construction` TEXT NULL,
		ADD COLUMN `reservation_payment` DECIMAL(15,2) NULL DEFAULT '0',
		ADD COLUMN `contract_payment` DECIMAL(15,2) NULL DEFAULT '0',
		ADD COLUMN `maintenance_fee` DECIMAL(15,2) NULL DEFAULT '0',
		ADD COLUMN `property_condition` TEXT NULL,
		ADD COLUMN `year_built` INT(11) NULL,
		ADD COLUMN `gas_emission` DECIMAL(15,2) NULL DEFAULT '0',
		ADD COLUMN `egenry_efficient` DECIMAL(15,2) NULL DEFAULT '0',
		ADD COLUMN `levels` TEXT NULL ,
		ADD COLUMN `cable_TV` TEXT NULL,
		ADD COLUMN `computer` TEXT NULL,
		ADD COLUMN `heating` TEXT NULL,
		ADD COLUMN `internet` TEXT NULL
		;");
}

if (!$CI->db->field_exists("school", db_prefix() . "items")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "items`
		ADD COLUMN `school` TEXT NULL,
		ADD COLUMN `hopspital` TEXT NULL,
		ADD COLUMN `landmarks` TEXT NULL
		;");
}

if (!$CI->db->field_exists('primary_image' ,db_prefix() . 'items')){
	$CI->db->query('ALTER TABLE `' . db_prefix() . "items`
		ADD COLUMN `primary_image` TEXT NULL,
		ADD COLUMN `garage` INT(11) NULL,
		ADD COLUMN `lot_size_acres`DECIMAL(15,2) NULL DEFAULT '0.00',
		ADD COLUMN `dom` DECIMAL(15,2) NULL DEFAULT '0.00'
		;");
}

if (!$CI->db->table_exists(db_prefix() . "real_saved_properties")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_saved_properties` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`item_id` int(11) NOT NULL DEFAULT '0',
		`is_company_admin` int(11) NULL DEFAULT 0,
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or broker',
		`related_id` INT(11) NULL DEFAULT 0,
		`date_created` datetime NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->field_exists('profile_image' ,db_prefix() . 'real_companies')){
	$CI->db->query('ALTER TABLE `' . db_prefix() . "real_companies`
		ADD COLUMN `profile_image` TEXT NULL

		;");
}

if (!$CI->db->table_exists(db_prefix() . "real_request_brokerages")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_request_brokerages` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`item_id` int(11) NOT NULL DEFAULT '0',
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`staff_id` int(11) NULL DEFAULT 0,
		`commission` DECIMAL(15,2) NULL DEFAULT '0.00',
		`created_id` int(11) NULL DEFAULT 0,
		`status` int(11) NULL DEFAULT 1,
		`is_company_admin` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`related_id` INT(11) NULL DEFAULT 0,
		`date_created` datetime NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->field_exists('is_company_admin' ,db_prefix() . 'real_request_brokerages')){
	$CI->db->query('ALTER TABLE `' . db_prefix() . "real_request_brokerages`
		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0

		;");
}

if (!$CI->db->field_exists('related_company_id' ,db_prefix() . 'real_request_brokerages')){
	$CI->db->query('ALTER TABLE `' . db_prefix() . "real_request_brokerages`
		ADD COLUMN `related_company_id` INT(11) NULL DEFAULT 0

		;");
}

if (!$CI->db->field_exists('birthday' ,db_prefix() . 'contacts')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "contacts`
		ADD COLUMN `birthday` date NULL,
		ADD COLUMN `introduce_yourself` TEXT NULL,
		ADD COLUMN `not_employed` INT(11) NULL DEFAULT 0,
		ADD COLUMN `employment_type` TEXT NULL,
		ADD COLUMN `emergency_contact_name` TEXT NULL,
		ADD COLUMN `emergency_contact_relationship` TEXT NULL,
		ADD COLUMN `emergency_contact_email` TEXT NULL,
		ADD COLUMN `emergency_contact_phonenumber` TEXT NULL,
		ADD COLUMN `live_with_other_occupants` INT(11) NULL DEFAULT '0',
		ADD COLUMN `dogs` INT(11) NULL,
		ADD COLUMN `cats` INT(11) NULL,
		ADD COLUMN `other_pets` INT(11) NULL,
		ADD COLUMN `describe_your_pets` TEXT NULL
		;");
}

if (!$CI->db->table_exists(db_prefix() . "real_contact_address_histories")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_contact_address_histories` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`contact_id` int(11) NOT NULL DEFAULT '0',
		`address` TEXT NULL,
		`latitude` TEXT NULL,
		`longitude` TEXT NULL,
		`move_in` DATE NULL,
		`move_out` DATE NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->table_exists(db_prefix() . "real_contact_incomes")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_contact_incomes` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`contact_id` int(11) NOT NULL DEFAULT '0',
		`income_type` TEXT NULL,
		`income_frequency` TEXT NULL,
		`amount` DECIMAL(15,2) NULL DEFAULT '0.00',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}
if (!$CI->db->table_exists(db_prefix() . "real_contact_occupants")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_contact_occupants` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`contact_id` int(11) NOT NULL DEFAULT '0',
		`occupants_name` TEXT NULL,
		`occupants_age` INT(11) NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->table_exists(db_prefix() . "real_property_owners")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_property_owners` (
		`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` varchar(250) NOT NULL,
		`active` tinyint(1),
		`email` varchar(100) NOT NULL,
		`phonenumber` varchar(30) DEFAULT NULL,
		`country` int(11) NOT NULL DEFAULT '0',
		`city` TEXT NULL,
		`zip` TEXT NULL,
		`state` TEXT NULL,
		`address` TEXT NULL,
		`website` TEXT NULL,
		`vat` TEXT NULL,
		`default_language` varchar(40) NULL,
		`default_currency` INT(11) NOT NULL DEFAULT '0',
		`upload_file_size` int(11) DEFAULT NULL,
		`billing_street` TEXT NULL,
		`billing_city` TEXT NULL,
		`billing_state` TEXT NULL,
		`billing_zip` TEXT NULL,
		`billing_country` int(11) NULL DEFAULT '0',
		`shipping_street` TEXT NULL,
		`shipping_city` TEXT NULL,
		`shipping_state` TEXT NULL,
		`shipping_zip` TEXT NULL,
		`shipping_country` int(11) NULL DEFAULT '0',
		`code` TEXT NULL,
		`facebook_url` TEXT NULL,
		`instagram_url` TEXT NULL,
		`whatsapp_url` TEXT NULL,
		`profile_image` TEXT NULL,
		`is_company_admin` int(11) NULL DEFAULT 0,
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`related_id` INT(11) NULL DEFAULT 0,
		`created_date` datetime NOT NULL,
		`hash` varchar(32) DEFAULT NULL,

		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}


if (!$CI->db->field_exists('email_signature' ,db_prefix() . 'real_broker_staffs')){
	$CI->db->query('ALTER TABLE `' . db_prefix() . "real_broker_staffs`
		ADD COLUMN `email_signature` mediumtext  NULL

		;");
}
if (!$CI->db->field_exists("pool_features" ,db_prefix() . "items")) { 
	$CI->db->query("ALTER TABLE `" . db_prefix() . "items`
		ADD COLUMN `pool_features` TEXT NULL,
		ADD COLUMN `spa` TEXT NULL,
		ADD COLUMN `spa_features` TEXT NULL,
		ADD COLUMN `front_exposure` TEXT NULL,
		ADD COLUMN `easements` TEXT NULL,
		ADD COLUMN `road_frontage` TEXT NULL,
		ADD COLUMN `road_surface_type` TEXT NULL,
		ADD COLUMN `road_responsibility` TEXT NULL,
		ADD COLUMN `signage` TEXT NULL,
		ADD COLUMN `adjoining_property` TEXT NULL,
		ADD COLUMN `other_structures` TEXT NULL,
		ADD COLUMN `other_equipment` TEXT NULL,
		ADD COLUMN `vegetation` TEXT NULL,
		ADD COLUMN `lot_features` TEXT NULL,
		ADD COLUMN `exterior_construction` TEXT NULL,
		ADD COLUMN `roof` TEXT NULL,
		ADD COLUMN `building_features` TEXT NULL,
		ADD COLUMN `garage_parking_features` TEXT NULL,
		ADD COLUMN `foundation` TEXT NULL,
		ADD COLUMN `basement` TEXT NULL,
		ADD COLUMN `balcony` TEXT NULL,
		ADD COLUMN `lift` TEXT NULL,
		ADD COLUMN `grill` TEXT NULL,
		ADD COLUMN `parking` TEXT NULL,
		ADD COLUMN `private_pool` TEXT NULL,
		ADD COLUMN `pool_dimensions` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `door_height` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `door_width` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `eaves_height` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `road_frontage_feet` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `garage_door_height` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `Garden_SqM` DECIMAL(15,2) NULL DEFAULT 0,
		ADD COLUMN `Front_Yard_SqM` DECIMAL(15,2) NULL DEFAULT 0

		;");
}

if (!$CI->db->field_exists("property_owner_id" ,db_prefix() . "items")) { 
	$CI->db->query("ALTER TABLE `" . db_prefix() . "items`
		ADD COLUMN `property_owner_id` INT(11) NULL DEFAULT 0

		;");
}


if (!$CI->db->field_exists('is_company_admin' ,db_prefix() . 'items_groups')){
	$CI->db->query('ALTER TABLE `' . db_prefix() . "items_groups`
		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `company_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `broker_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0

		;");
}

if (!$CI->db->table_exists(db_prefix() . "real_schools")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_schools` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` TEXT NULL,
		`is_company_admin` int(11) NULL DEFAULT 0,
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`related_id` INT(11) NULL DEFAULT 0,
		`active` tinyint(1),
		`date_created` datetime NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->table_exists(db_prefix() . "real_landmarks")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_landmarks` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` TEXT NULL,
		`is_company_admin` int(11) NULL DEFAULT 0,
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`related_id` INT(11) NULL DEFAULT 0,
		`active` tinyint(1),
		`date_created` datetime NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->table_exists(db_prefix() . "real_hopspitals")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_hopspitals` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` TEXT NULL,
		`is_company_admin` int(11) NULL DEFAULT 0,
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`related_id` INT(11) NULL DEFAULT 0,
		`active` tinyint(1),
		`date_created` datetime NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}

if (!$CI->db->field_exists("is_approval_manager", db_prefix() . "staff")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "staff`
		ADD COLUMN `is_approval_manager` int(11) NULL DEFAULT '0'
		;");
}
if (!$CI->db->field_exists("require_approvals", db_prefix() . "staff")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "staff`
		ADD COLUMN `require_approvals` int(11) NULL DEFAULT '0'
		;");
}

if (!$CI->db->field_exists("approver_id", db_prefix() . "items")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "items`
		ADD COLUMN `approver_id` INT(11) NULL DEFAULT 0;

		");
}
if (!$CI->db->field_exists("date_approval", db_prefix() . "items")) {
	$CI->db->query("ALTER TABLE `" . db_prefix() . "items`
		ADD COLUMN `date_approval` datetime NULL;
		");
}

if (!$CI->db->table_exists(db_prefix() . "real_requests")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_requests` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`sent` tinyint(1) NOT NULL DEFAULT 0,
		`datesend` datetime DEFAULT NULL,
		`clientid` int(11) NOT NULL,
		`item_id` int(11) NOT NULL,
		`deleted_customer_name` varchar(100) DEFAULT NULL,
		`code` TEXT NULL,
		`datecreated` datetime NOT NULL,
		`date` date NOT NULL,
		`duedate` date DEFAULT NULL,
		`currency` int(11) NOT NULL,
		`property_price` decimal(15,2) NULL DEFAULT 0.00,
		`subtotal` decimal(15,2) NULL DEFAULT 0.00,
		`total_tax` decimal(15,2) NULL DEFAULT 0.00,
		`total` decimal(15,2) NOT NULL,
		`contract_total` decimal(15,2) NOT NULL,
		`adjustment` decimal(15,2) DEFAULT NULL,
		`hash` varchar(32) NOT NULL,
		`status` int(11) DEFAULT 1,
		`clientnote` mediumtext DEFAULT NULL,
		`adminnote` mediumtext DEFAULT NULL,
		`last_overdue_reminder` date DEFAULT NULL,
		`last_due_reminder` date DEFAULT NULL,
		`cancel_overdue_reminders` int(11) NOT NULL DEFAULT 0,
		`allowed_payment_modes` longtext DEFAULT NULL,
		`discount_percent` decimal(15,2) DEFAULT 0.00,
		`discount_total` decimal(15,2) DEFAULT 0.00,
		`discount_type` varchar(30) NOT NULL,
		`recurring` int(11) NOT NULL DEFAULT 0,
		`recurring_type` varchar(10) DEFAULT NULL,
		`custom_recurring` tinyint(1) NOT NULL DEFAULT 0,
		`cycles` int(11) NOT NULL DEFAULT 0,
		`total_cycles` int(11) NOT NULL DEFAULT 0,
		`is_recurring_from` int(11) DEFAULT NULL,
		`last_recurring_date` date DEFAULT NULL,

		`contract_is_recurring` int(11) NULL DEFAULT 0,
		`contract_recurring_value` int(11) NOT NULL DEFAULT 0,
		`contract_recurring_type` varchar(10) DEFAULT NULL,
		`terms` mediumtext DEFAULT NULL,
		`billing_street` varchar(200) DEFAULT NULL,
		`billing_city` varchar(100) DEFAULT NULL,
		`billing_state` varchar(100) DEFAULT NULL,
		`billing_zip` varchar(100) DEFAULT NULL,
		`billing_country` int(11) DEFAULT NULL,
		`shipping_street` varchar(200) DEFAULT NULL,
		`shipping_city` varchar(100) DEFAULT NULL,
		`shipping_state` varchar(100) DEFAULT NULL,
		`shipping_zip` varchar(100) DEFAULT NULL,
		`shipping_country` int(11) DEFAULT NULL,
		`include_shipping` tinyint(1) NOT NULL,
		`show_shipping_on_contract_estimate` tinyint(1) NOT NULL DEFAULT 1,
		`show_quantity_as` int(11) NOT NULL DEFAULT 1,
		`project_id` int(11) DEFAULT 0,
		`subscription_id` int(11) NOT NULL DEFAULT 0,
		`short_link` varchar(100) DEFAULT NULL,
		`frequency_id` INT(11) DEFAULT NULL,
		`frequency_value` DECIMAL(15,2) NULL DEFAULT '0',
		`frequency_type` VARCHAR(6) NULL,
		`contract_id` INT(11) NULL DEFAULT 0,
		`invoice_id` INT(11) NULL DEFAULT 0,
		`invoiced_date` datetime DEFAULT NULL,
		`contrated_date` datetime DEFAULT NULL,
		`term_month` INT(11) NULL DEFAULT 0,
		`inspect_property` INT(11) NULL DEFAULT 0,
		`inspection_date` DATE NULL,
		`request_type` VARCHAR(4) NULL DEFAULT 'buy' COMMENT 'buy or rent',
		`broker_related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`broker_related_id` INT(11) NULL DEFAULT 0,
		`broker_phone` INT(11) NULL DEFAULT 0,

		`is_company_admin` int(11) NULL DEFAULT 0,
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`related_id` INT(11) NULL DEFAULT 0,


		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");

}

if (!$CI->db->field_exists('property_request_id' ,db_prefix() . 'contracts')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "contracts`
		ADD COLUMN `property_request_id` INT(11) NULL DEFAULT 0,
		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `company_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `broker_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0

		;");
}

if (!$CI->db->field_exists('is_company_admin' ,db_prefix() . 'invoices')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "invoices`
		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `company_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `broker_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0

		;");
}
if (!$CI->db->field_exists('property_request_id' ,db_prefix() . 'invoices')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "invoices`
		ADD COLUMN `property_request_id` INT(11) NULL DEFAULT 0
		;");
}


create_email_template('Property Request # {request_number} created','<span style=\"font-size: 12pt;\">Dear {contact_firstname} {contact_lastname}</span><br /><br /><span style=\"font-size: 12pt;\">Please find the attached Property request <strong># {request_number}</strong></span><br /><br /><span style=\"font-size: 12pt;\"><strong>Property Request status:</strong> {request_status}</span><br /><br /><span style=\"font-size: 12pt;\">You can view the Property Request on the following link: <a href="{request_link}">{request_number}</a></span><br /><br /><span style=\"font-size: 12pt;\">We look forward to your communication.</span><br /><br /><span style=\"font-size: 12pt;\">Kind Regards,</span><br /><span style=\"font-size: 12pt;\">{email_signature}<br /></span>', 'realestate', 'Send Property Request to Customer', 'property-request-send-to-client');

if (!$CI->db->field_exists('is_company_admin' ,db_prefix() . 'contracts_types')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "contracts_types`
		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `company_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `broker_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0

		;");
}

if (!$CI->db->field_exists('is_company_admin' ,db_prefix() . 'contract_comments')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "contract_comments`
		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `company_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `broker_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0

		;");
}
if (!$CI->db->field_exists('is_company_admin' ,db_prefix() . 'real_companies')) { 
	$CI->db->query('ALTER TABLE `' . db_prefix() . "real_companies`
		ADD COLUMN `is_company_admin` int(11) NULL DEFAULT 0,
		ADD COLUMN `company_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `broker_id` int(11) NULL DEFAULT 0,
		ADD COLUMN `related_id` INT(11) NULL DEFAULT 0

		;");
}

if (!$CI->db->table_exists(db_prefix() . "real_activity")) {
	$CI->db->query("CREATE TABLE `" . db_prefix() . "real_activity` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`rel_type` varchar(20) DEFAULT NULL,
		`rel_id` int(11) NOT NULL,
		`description` mediumtext NOT NULL,
		`additional_data` mediumtext,
		`staffid` varchar(11) DEFAULT NULL,
		`full_name` varchar(100) DEFAULT NULL,
		`date` datetime NOT NULL,
		`is_company_admin` int(11) NULL DEFAULT 0,
		`company_id` int(11) NULL DEFAULT 0,
		`broker_id` int(11) NULL DEFAULT 0,
		`related_type` VARCHAR(15) NULL DEFAULT 'staff' COMMENT 'staff or company or business_broker',
		`related_id` INT(11) NULL DEFAULT 0,

		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
}
