<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
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
    }
}