<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
delete_option('limit_for_sync_jm_to_mc');

if ($CI->db->table_exists(db_prefix() . 'mailchimp')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'mailchimp`');
}
if ($CI->db->table_exists(db_prefix() . 'mailchimp_activity_logs')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'mailchimp_activity_logs`');
}
if ($CI->db->table_exists(db_prefix() . 'mailchimp_audience')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'mailchimp_audience`');
}
if ($CI->db->table_exists(db_prefix() . 'mailchimp_stores')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'mailchimp_stores`');
}
if ($CI->db->table_exists(db_prefix() . 'mailchimp_contact_audience')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'mailchimp_contact_audience`');
}
//Remove mailchimp estimate id from estimate table
if ($CI->db->field_exists('mailchimp_estimate_id', db_prefix().'estimates')) {
  $CI->db->query("ALTER TABLE ".db_prefix() ."estimates  DROP COLUMN mailchimp_estimate_id"); 
}
if ($CI->db->field_exists('mailchimp_audience_id', db_prefix().'estimates')) {
  $CI->db->query("ALTER TABLE ".db_prefix() ."estimates  DROP COLUMN mailchimp_audience_id"); 
}

