<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->database();

// Tabela de Grupos de Chat
if (!$CI->db->table_exists(db_prefix() . 'dps_chat_groups')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_chat_groups` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(191) NOT NULL,
        `description` TEXT NULL,
        `created_by` INT(11) NOT NULL,
        `is_public` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Tabela de Membros dos Grupos
if (!$CI->db->table_exists(db_prefix() . 'dps_chat_group_members')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_chat_group_members` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `group_id` INT(11) NOT NULL,
        `staff_id` INT(11) NOT NULL,
        `added_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `group_idx` (`group_id`),
        KEY `staff_idx` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

// Tabela de Mensagens
if (!$CI->db->table_exists(db_prefix() . 'dps_chat_messages')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_chat_messages` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `sender_id` INT(11) NOT NULL,
        `receiver_id` INT(11) NULL, -- NULL se for mensagem de grupo
        `group_id` INT(11) NULL,    -- NULL se for mensagem direta
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `sender_idx` (`sender_id`),
        KEY `receiver_idx` (`receiver_id`),
        KEY `group_msg_idx` (`group_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}
