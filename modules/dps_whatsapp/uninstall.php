<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
$CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "dps_whatsapp_config`");
$CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "dps_whatsapp_followups`");
