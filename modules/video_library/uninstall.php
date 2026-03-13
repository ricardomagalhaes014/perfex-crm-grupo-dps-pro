<?php
defined('BASEPATH') or exit('No direct script access allowed');

if(is_dir(FCPATH . 'uploads/video_library' . '/')){
  delete_dir(FCPATH . 'uploads/video_library' . '/');
}
if(is_dir(FCPATH . 'uploads/video_library/discussions/attachment' . '/')){
  delete_dir(FCPATH . 'uploads/video_library/discussions/attachment' . '/');
}

$CI = &get_instance();

if ($CI->db->table_exists(db_prefix() . 'upload_video')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'upload_video`');
}
if ($CI->db->table_exists(db_prefix() . 'video_category')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'video_category`');
}
if ($CI->db->table_exists(db_prefix() . 'video_library_videos_comments')) {
  $CI->db->query('DROP TABLE `' . db_prefix() . 'video_library_videos_comments`');
}

delete_option('vl_google_client_id');
delete_option('vl_google_client_secret');
delete_option('vl_google_client_redirect_uri');
delete_option('is_vl_google_drive');
