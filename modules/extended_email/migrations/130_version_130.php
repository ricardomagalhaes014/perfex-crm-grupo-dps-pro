<?php

defined('BASEPATH') || exit('No direct script access allowed');

class Migration_Version_130 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $mail_queue_table = db_prefix() . 'mail_queue';
        if (!$CI->db->field_exists('sender_id', $mail_queue_table)) {
            $CI->db->query('ALTER TABLE `' . $mail_queue_table . '` ADD `sender_id` INT NULL DEFAULT NULL AFTER `id`');
        }
    }
}