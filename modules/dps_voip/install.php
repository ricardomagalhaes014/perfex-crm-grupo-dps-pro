<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();

// Tabela de números Twilio
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_voip_numbers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `twilio_number` varchar(20) NOT NULL COMMENT 'Número Twilio E.164 ex: +15551234567',
    `friendly_name` varchar(100) DEFAULT NULL COMMENT 'Nome descritivo ex: DPS Lisboa',
    `twilio_sid` varchar(50) DEFAULT NULL COMMENT 'SID do número no Twilio',
    `staff_id` int(11) DEFAULT NULL COMMENT 'Comercial atribuído (NULL = disponível)',
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `twilio_number` (`twilio_number`),
    KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Tabela de registo de chamadas
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_voip_calls` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `call_sid` varchar(50) DEFAULT NULL COMMENT 'SID da chamada no Twilio',
    `direction` enum('outbound','inbound') NOT NULL DEFAULT 'outbound',
    `from_number` varchar(20) DEFAULT NULL,
    `to_number` varchar(20) DEFAULT NULL,
    `staff_id` int(11) DEFAULT NULL COMMENT 'Comercial que fez/recebeu',
    `lead_id` int(11) DEFAULT NULL COMMENT 'Lead associada (se aplicável)',
    `contact_id` int(11) DEFAULT NULL COMMENT 'Contacto associado (se aplicável)',
    `status` enum('initiated','ringing','in-progress','completed','failed','busy','no-answer','canceled') NOT NULL DEFAULT 'initiated',
    `duration` int(11) DEFAULT NULL COMMENT 'Duração em segundos',
    `recording_url` varchar(500) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `started_at` datetime DEFAULT NULL,
    `ended_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `call_sid` (`call_sid`),
    KEY `staff_id` (`staff_id`),
    KEY `lead_id` (`lead_id`),
    KEY `direction` (`direction`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Configurações
add_option('dps_voip_twilio_account_sid', '');
add_option('dps_voip_twilio_auth_token', '');
add_option('dps_voip_twilio_app_sid', '');
add_option('dps_voip_record_calls', '0');
add_option('dps_voip_default_timeout', '30');
