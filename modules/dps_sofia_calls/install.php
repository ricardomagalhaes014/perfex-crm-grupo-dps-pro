<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Tabela de campanhas Sofia
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_sofia_campaigns` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `lead_status_id` int(11) NOT NULL,
    `agent_id` varchar(100) NOT NULL DEFAULT 'agent_9901kv1pvewveh9s9ebs1rys274k',
    `focus_text` text DEFAULT NULL,
    `status` enum('active','paused','completed','stopped') NOT NULL DEFAULT 'active',
    `total_leads` int(11) NOT NULL DEFAULT 0,
    `calls_made` int(11) NOT NULL DEFAULT 0,
    `calls_answered` int(11) NOT NULL DEFAULT 0,
    `calls_failed` int(11) NOT NULL DEFAULT 0,
    `created_by` int(11) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Tabela de chamadas individuais
$CI->db->query("CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_sofia_call_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `campaign_id` int(11) NOT NULL,
    `lead_id` int(11) NOT NULL,
    `lead_name` varchar(255) DEFAULT NULL,
    `phone_number` varchar(50) NOT NULL,
    `elevenlabs_call_id` varchar(100) DEFAULT NULL,
    `status` enum('pending','calling','answered','no_answer','failed','busy') NOT NULL DEFAULT 'pending',
    `duration` int(11) DEFAULT NULL,
    `started_at` datetime DEFAULT NULL,
    `ended_at` datetime DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `campaign_id` (`campaign_id`),
    KEY `lead_id` (`lead_id`),
    KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Configurações do módulo
/*
 * A chave NAO e semeada aqui.
 *
 * Estava cravada no codigo e por isso viajava no repositorio: quem lesse
 * o ficheiro tinha a chave da conta ElevenLabs. Agora nasce vazia e
 * escreve-se no ecra de definicoes do modulo, que a guarda na base de dados.
 */
add_option('sofia_calls_elevenlabs_api_key', '');
add_option('sofia_calls_phone_number_id', 'phnum_6701kvea8mbhe4vbdz75jf1wd1y7');
add_option('sofia_calls_default_agent_id', 'agent_9901kv1pvewveh9s9ebs1rys274k');
add_option('sofia_calls_delay_between_calls', '3');
