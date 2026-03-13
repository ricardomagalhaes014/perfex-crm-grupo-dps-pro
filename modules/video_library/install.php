<?php defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
if (!$CI->db->table_exists(db_prefix() . 'video_category')) {
   $CI->db->query('CREATE TABLE `' . db_prefix() . 'video_category` (
      `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
      `category` varchar(255) NOT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
   }
   if (!$CI->db->table_exists(db_prefix() . 'upload_video')) {
      $CI->db->query('CREATE TABLE `' . db_prefix() . 'upload_video` (
         `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
         `title` varchar(255) NOT NULL,
         `upload_video` varchar(255) DEFAULT NULL,
         `category` varchar(255) NOT NULL,
         `description` TEXT DEFAULT NULL,
         `project_id` int(11) DEFAULT NULL,
         `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
         ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
      }
      if(!is_dir(VIDEO_LIBRARY_UPLOADS_FOLDER)){
         mkdir(VIDEO_LIBRARY_UPLOADS_FOLDER, 0777, TRUE);
         fopen(VIDEO_LIBRARY_UPLOADS_FOLDER . 'index.html', 'w');
         $fp = fopen(VIDEO_LIBRARY_UPLOADS_FOLDER . 'index.html', 'a+');
         if ($fp) {
            fclose($fp);
         }
      }
      if(!is_dir(VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER)){
         mkdir(VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER, 0777, TRUE);
         fopen(VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER . 'index.html', 'w');
         $fp = fopen(VIDEO_LIBRARY_DISCUSSIONS_ATTACHMENT_FOLDER . 'index.html', 'a+');
         if ($fp) {
            fclose($fp);
         }
      }
      if (!$CI->db->table_exists(db_prefix() . 'video_library_videos_comments')) {
         $CI->db->query('CREATE TABLE `' . db_prefix() . 'video_library_videos_comments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `video_id` int(11) NOT NULL,
            `discussion_type` varchar(10) NOT NULL,
            `parent` int(11) DEFAULT NULL,
            `created` datetime NOT NULL,
            `modified` datetime DEFAULT NULL,
            `content` text NOT NULL,
            `user_id` varchar(195) NOT NULL,
            `user_type` varchar(195) DEFAULT NULL,
            `contact_id` int(11) DEFAULT "0",
            `fullname` varchar(191) DEFAULT NULL,
            `file_name` varchar(191) DEFAULT NULL,
            `file_mime_type` varchar(70) DEFAULT NULL,
            PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
         }
         add_option('vl_google_client_id','');
         add_option('vl_google_client_secret','');
         add_option('vl_google_client_redirect_uri','');
         add_option('is_vl_google_drive','no');
         
         $table = db_prefix() . 'upload_video';
         
         if (!$CI->db->field_exists('upload_type', $table)) {
            
            $CI->db->query("ALTER TABLE `" . $table . "` ADD `upload_type` enum('file','link') NOT NULL;");
            
         }
         
         if (!$CI->db->field_exists('google_drive_upload_id', $table)) {
            
            $CI->db->query("ALTER TABLE `" . $table . "` ADD `google_drive_upload_id` text NULL DEFAULT NULL;");
            
         }
         
         