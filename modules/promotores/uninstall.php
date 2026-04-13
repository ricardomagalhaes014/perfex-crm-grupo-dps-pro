<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
// Para manter os dados ao desinstalar, deixamos sem DROP.
// $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'promotores`');
