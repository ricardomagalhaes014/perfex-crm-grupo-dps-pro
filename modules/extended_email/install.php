<?php

defined('BASEPATH') || exit('No direct script access allowed');

add_option('extended_email_enabled', 1);

$my_files_list = [
    APPPATH.'config/my_hooks.php'      => module_dir_path('extended_email', '/resources/application/config/my_hooks.php'),
];

// Copy each file in $my_files_list to its actual path if it doesn't already exist
foreach ($my_files_list as $actual_path => $resource_path) {
    if (!file_exists($actual_path)) {
        copy($resource_path, $actual_path);
    }
}

// get codeigniter instance
$CI = &get_instance();

if (!$CI->db->table_exists(db_prefix().'extended_email_settings')) {
    $CI->db->query('CREATE TABLE `'.db_prefix().'extended_email_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `userid` int(11) NOT NULL,
        `mail_engine` varchar(150) NOT NULL,
        `email_protocol` varchar(150) NOT NULL,
        `smtp_encryption` varchar(150) NOT NULL,
        `smtp_host` varchar(150) NOT NULL,
        `smtp_port` varchar(150) NOT NULL,
        `email` varchar(150) NOT NULL,
        `smtp_username` varchar(150) NOT NULL,
        `smtp_password` TEXT NOT NULL,
        `email_charset` varchar(150) NOT NULL,
        `active` TINYINT(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}

if (!$CI->db->table_exists(db_prefix().'extended_email_log_activity')) {
    $CI->db->query('CREATE TABLE `'.db_prefix().'extended_email_log_activity` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staffid` int(11) NOT NULL,
        `email_userid` int(11) NOT NULL,
        `description` varchar(255) NOT NULL,
        `datetime` DATETIME NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET='.$CI->db->char_set.';');
}

// An array of files to backup
$backup_files_list = [
    APPPATH.'libraries/App_Email.php'    => module_dir_path(EXTENDED_EMAIL_MODULE, '/resources/application/libraries/App_Email.php')
];

// Backup each file in $backup_files_list by renaming it with a '.backup' suffix if it exists, then copy the new version from the resources directory
foreach ($backup_files_list as $actual_path => $resource_path) {
    if (file_exists($actual_path.'.backup')) {
        @unlink($actual_path.'.backup');
    }
    if (file_exists($actual_path)) {
        rename($actual_path, $actual_path.'.backup');
    }
    if (!file_exists($actual_path)) {
        copy($resource_path, $actual_path);
    }
}

$mail_queue_table = db_prefix() . 'mail_queue';
if (!$CI->db->field_exists('sender_id', $mail_queue_table)) {
    $CI->db->query('ALTER TABLE `' . $mail_queue_table . '` ADD `sender_id` INT NULL DEFAULT NULL AFTER `id`');
}

/* End of file install.php */
