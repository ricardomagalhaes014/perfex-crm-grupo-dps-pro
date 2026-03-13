<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

delete_option('staff_members_create_inline_mindmap_group');

$CI->db->query('DROP TABLE `' . db_prefix() . 'mindmap`');
$CI->db->query('DROP TABLE `' . db_prefix() . 'mindmap_groups`');
$CI->db->query('DROP TABLE `' . db_prefix() . 'mindmapcomments`');
