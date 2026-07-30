<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (!$CI->db->table_exists(db_prefix() . 'voipstudio_calls')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "voipstudio_calls` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `voip_id` VARCHAR(64) DEFAULT NULL,
        `calldate` DATETIME DEFAULT NULL,
        `direction` VARCHAR(16) DEFAULT NULL,
        `src` VARCHAR(32) DEFAULT NULL,
        `dst` VARCHAR(32) DEFAULT NULL,
        `duration` INT(11) DEFAULT 0,
        `disposition` VARCHAR(32) DEFAULT NULL,
        `rel_type` VARCHAR(20) DEFAULT NULL,
        `rel_id` INT(11) DEFAULT NULL,
        `staff_id` INT(11) DEFAULT NULL,
        `raw` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `voip_id` (`voip_id`),
        KEY `rel` (`rel_type`, `rel_id`),
        KEY `calldate` (`calldate`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}

add_option('voipstudio_dps_email', '');
add_option('voipstudio_dps_password', '');
add_option('voipstudio_dps_caller_id', '');
add_option('voipstudio_dps_token', '');
add_option('voipstudio_dps_token_time', '');
add_option('voipstudio_dps_last_sync', '');
