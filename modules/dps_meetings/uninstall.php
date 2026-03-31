<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->database();

$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'dps_meeting_logs`');
$CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'dps_meetings`');
