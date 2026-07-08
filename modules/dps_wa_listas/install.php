<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (! $CI->db->table_exists(db_prefix() . 'dps_wa_label_queue')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_wa_label_queue` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `lead_id` INT(11) NOT NULL,
        `staff_id` INT(11) NULL DEFAULT NULL,
        `old_status_id` INT(11) NULL DEFAULT NULL,
        `new_status_id` INT(11) NULL DEFAULT NULL,
        `status` ENUM('pending','done','skipped','failed') NOT NULL DEFAULT 'pending',
        `attempts` INT(11) NOT NULL DEFAULT 0,
        `last_error` TEXT NULL,
        `created_at` DATETIME NULL DEFAULT NULL,
        `processed_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `status` (`status`),
        KEY `lead_id` (`lead_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Opções por omissão.
if (get_option('dps_wa_listas_notify_agent') === '') {
    add_option('dps_wa_listas_notify_agent', '0');
}
if (get_option('dps_wa_listas_enabled') === '') {
    add_option('dps_wa_listas_enabled', '1');
}
