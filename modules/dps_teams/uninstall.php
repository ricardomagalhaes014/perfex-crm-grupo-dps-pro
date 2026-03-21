<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if ($CI->db->table_exists(db_prefix() . 'dps_team_members')) {
    $CI->db->query('DROP TABLE `' . db_prefix() . 'dps_team_members`');
}
if ($CI->db->table_exists(db_prefix() . 'dps_teams')) {
    $CI->db->query('DROP TABLE `' . db_prefix() . 'dps_teams`');
}
