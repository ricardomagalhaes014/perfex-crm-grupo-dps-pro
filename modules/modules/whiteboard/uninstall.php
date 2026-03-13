<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

delete_option('staff_members_create_inline_whiteboard_group');

$CI->db->query('DROP TABLE `' . db_prefix() . 'whiteboard`');
$CI->db->query('DROP TABLE `' . db_prefix() . 'whiteboard_groups`');
$CI->db->query('DROP TABLE `' . db_prefix() . 'whiteboardcomments`');
