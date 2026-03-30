<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Tabela de configurações WhatsApp por staff
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_whatsapp_config` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `staff_id` int(11) NOT NULL,
    `phone_number` varchar(30) DEFAULT NULL,
    `is_connected` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Tabela de follow-ups agendados
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_whatsapp_followups` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lead_id` int(11) NOT NULL,
    `staff_id` int(11) NOT NULL,
    `scheduled_at` datetime NOT NULL,
    `status` enum('pending','sent','cancelled','failed') NOT NULL DEFAULT 'pending',
    `lead_status_id` int(11) DEFAULT NULL,
    `sent_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `lead_id` (`lead_id`),
    KEY `staff_id` (`staff_id`),
    KEY `scheduled_at` (`scheduled_at`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
