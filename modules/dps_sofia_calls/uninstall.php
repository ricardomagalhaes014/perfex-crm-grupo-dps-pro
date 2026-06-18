<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "dps_sofia_call_logs`");
$CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "dps_sofia_campaigns`");
delete_option('sofia_calls_elevenlabs_api_key');
delete_option('sofia_calls_phone_number_id');
delete_option('sofia_calls_default_agent_id');
delete_option('sofia_calls_delay_between_calls');
