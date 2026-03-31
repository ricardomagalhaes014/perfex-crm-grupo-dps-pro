<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->database();

if (!$CI->db->table_exists(db_prefix() . 'dps_meetings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_meetings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `title` VARCHAR(191) NOT NULL,
        `provider` VARCHAR(50) NOT NULL DEFAULT 'jitsi',
        `meeting_url` TEXT NULL,
        `room_name` VARCHAR(191) NULL,
        `related_type` VARCHAR(50) NULL,
        `related_id` INT(11) NULL,
        `contact_name` VARCHAR(191) NULL,
        `contact_email` VARCHAR(191) NULL,
        `host_staff_id` INT(11) NOT NULL,
        `start_at` DATETIME NOT NULL,
        `end_at` DATETIME NOT NULL,
        `status` VARCHAR(50) NOT NULL DEFAULT 'scheduled',
        `description` TEXT NULL,
        `result_notes` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        `created_by` INT(11) NOT NULL,
        `updated_at` DATETIME NULL,
        `updated_by` INT(11) NULL,
        PRIMARY KEY (`id`),
        KEY `related_idx` (`related_type`,`related_id`),
        KEY `host_idx` (`host_staff_id`),
        KEY `status_idx` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'dps_meeting_logs')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_meeting_logs` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `meeting_id` INT(11) NOT NULL,
        `action` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `staff_id` INT(11) NOT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `meeting_idx` (`meeting_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}
