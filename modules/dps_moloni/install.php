<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$charset = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

if (!$CI->db->table_exists(db_prefix() . 'dps_moloni_settings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_moloni_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `value` longtext NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT {$charset};");
}

if (!$CI->db->table_exists(db_prefix() . 'dps_moloni_links')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_moloni_links` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sale_id` int(11) NOT NULL DEFAULT 0,
        `kind` varchar(30) NOT NULL DEFAULT 'receipt',
        `document_id` bigint(20) NOT NULL DEFAULT 0,
        `document_type` varchar(60) NULL,
        `document_set` varchar(120) NULL,
        `number` varchar(120) NULL,
        `net_value` decimal(15,2) NOT NULL DEFAULT 0.00,
        `total_value` decimal(15,2) NOT NULL DEFAULT 0.00,
        `doc_date` date NULL,
        `status` tinyint(1) NOT NULL DEFAULT 0,
        `source` varchar(30) NOT NULL DEFAULT 'manual',
        `staff_id` int(11) NOT NULL DEFAULT 0,
        `date_create` datetime NULL,
        PRIMARY KEY (`id`),
        KEY `sale_id` (`sale_id`),
        UNIQUE KEY `document_id` (`document_id`)
    ) ENGINE=InnoDB DEFAULT {$charset};");
}

// Colunas acrescentadas depois da 1.0.0 (o upgrade_database volta a correr
// este ficheiro, por isso tem de ser idempotente).
if ($CI->db->table_exists(db_prefix() . 'dps_moloni_links')
    && !$CI->db->field_exists('is_paid', db_prefix() . 'dps_moloni_links')) {
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'dps_moloni_links`
        ADD COLUMN `is_paid` tinyint(1) NOT NULL DEFAULT 0 AFTER `status`;');
}

if (!$CI->db->table_exists(db_prefix() . 'dps_moloni_entities')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_moloni_entities` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `vat` varchar(40) NOT NULL,
        `kind` varchar(20) NOT NULL DEFAULT 'customer',
        `customer_id` bigint(20) NOT NULL DEFAULT 0,
        `name` varchar(190) NULL,
        `date_sync` datetime NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `vat_kind` (`vat`,`kind`)
    ) ENGINE=InnoDB DEFAULT {$charset};");
}

if (!$CI->db->table_exists(db_prefix() . 'dps_moloni_log')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_moloni_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `endpoint` varchar(120) NULL,
        `request` mediumtext NULL,
        `response` mediumtext NULL,
        `status` varchar(20) NULL,
        `message` varchar(500) NULL,
        `staff_id` int(11) NOT NULL DEFAULT 0,
        `date_create` datetime NULL,
        PRIMARY KEY (`id`),
        KEY `date_create` (`date_create`)
    ) ENGINE=InnoDB DEFAULT {$charset};");
}
