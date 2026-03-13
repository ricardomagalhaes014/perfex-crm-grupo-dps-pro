<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
//Mailchimp Table
$create_mailchimp_table = "CREATE TABLE ".db_prefix() . "mailchimp (
  `id` int NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) DEFAULT NULL,
  `server_prefix` varchar(120) DEFAULT NULL,
  `is_active` int DEFAULT NULL,
  `mailchimp_store_id` varchar(256) DEFAULT NULL,
  `limit_for_sync_jm_to_mc` int DEFAULT '10',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3";

if (!$CI->db->table_exists(db_prefix() . 'mailchimp')) {
  $CI->db->query($create_mailchimp_table);
    
}else{
  $CI->db->query("DROP TABLE ".db_prefix() . "mailchimp");
  $CI->db->query($create_mailchimp_table);
  $CI->db->query($insert_mailchimp_table);
}

//Mailchimp activity logs table
$create_mailchimp_activity_logs_table = "CREATE TABLE ".db_prefix() . "mailchimp_activity_logs (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(256) DEFAULT NULL,
  `description` text,
  `date` datetime DEFAULT NULL,
  `staff` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$CI->db->table_exists(db_prefix() . 'mailchimp_activity_logs')) {
  $CI->db->query($create_mailchimp_activity_logs_table);   
}else{
  $CI->db->query("DROP TABLE ".db_prefix() . "mailchimp_activity_logs");
  $CI->db->query($create_mailchimp_activity_logs_table);
}

//Mailchimp Audience table
$create_mailchimp_audience_table = "CREATE TABLE ".db_prefix() . "mailchimp_audience (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(120) DEFAULT NULL,
  `state` varchar(120) DEFAULT NULL,
  `zip` varchar(120) DEFAULT NULL,
  `country` varchar(120) DEFAULT NULL,
  `phone` varchar(120) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `from_subject` text CHARACTER SET utf8mb4 ,
  `mailchimp_id` varchar(256) DEFAULT NULL,
  `is_default` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$CI->db->table_exists(db_prefix() . 'mailchimp_audience')) {
  $CI->db->query($create_mailchimp_audience_table);   
}else{
  $CI->db->query("DROP TABLE ".db_prefix() . "mailchimp_audience");
  $CI->db->query($create_mailchimp_audience_table);
}

//Mailchimp Stores table
$create_mailchimp_stores_table = "CREATE TABLE ".db_prefix() . "mailchimp_stores (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(256) DEFAULT NULL,
  `mailchimp_store_id` varchar(255) DEFAULT NULL,
  `mailchimp_audience_id` varchar(256) DEFAULT NULL,
  `is_default` int DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$CI->db->table_exists(db_prefix() . 'mailchimp_stores')) {
  $CI->db->query($create_mailchimp_stores_table);   
}else{
  $CI->db->query("DROP TABLE ".db_prefix() . "mailchimp_stores");
  $CI->db->query($create_mailchimp_stores_table);
}
//Mailchimp contact audience table
$create_contact_audience = "CREATE TABLE ".db_prefix() . "mailchimp_contact_audience (
  `id` INT(20) NOT NULL AUTO_INCREMENT , 
  `mailchimp_audience_id` VARCHAR(256) NOT NULL , 
  `contact_id` INT(20) NOT NULL , 
  `mailchimp_subscribed_status` VARCHAR(256) NULL DEFAULT NULL , 
  `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP , 
  `updated_at` DATETIME NULL DEFAULT NULL , 
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$CI->db->table_exists(db_prefix() . 'mailchimp_contact_audience')) {
  $CI->db->query($create_contact_audience);   
}else{
  $CI->db->query("DROP TABLE ".db_prefix() . "mailchimp_contact_audience");
  $CI->db->query($create_contact_audience);
}
//Add mailchimp estimate id in estimate table
if (!$CI->db->field_exists('mailchimp_estimate_id', db_prefix().'estimates')) {
  $CI->db->query("ALTER TABLE ".db_prefix() ."estimates  ADD mailchimp_estimate_id VARCHAR(256) NULL DEFAULT NULL AFTER short_link"); 
}
if (!$CI->db->field_exists('mailchimp_audience_id', db_prefix().'estimates')) {
  $CI->db->query("ALTER TABLE ".db_prefix() ."estimates ADD mailchimp_audience_id VARCHAR(256) NULL DEFAULT NULL AFTER mailchimp_estimate_id"); 
}
;

