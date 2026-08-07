<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

if (! $CI->db->table_exists(db_prefix() . 'dps_propostas')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_propostas` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `lead_id` INT(11) NOT NULL,
        `staff_id` INT(11) NULL DEFAULT NULL,
        `tipo` ENUM('info','proposta') NOT NULL DEFAULT 'info',
        `empreendimento` VARCHAR(150) NULL DEFAULT NULL,
        `unidade` VARCHAR(100) NULL DEFAULT NULL,
        `lead_status_id` INT(11) NULL DEFAULT NULL,
        `lead_status_nome` VARCHAR(150) NULL DEFAULT NULL,
        `ficheiro` VARCHAR(255) NULL DEFAULT NULL,
        `detalhe` TEXT NULL,
        `wa_ok` TINYINT(1) NOT NULL DEFAULT 0,
        `outcome` ENUM('pendente','aceite','recusado') NOT NULL DEFAULT 'pendente',
        `motivo_perda` VARCHAR(40) NULL DEFAULT NULL,
        `valor` DECIMAL(15,2) NULL DEFAULT NULL,
        `outcome_at` DATETIME NULL DEFAULT NULL,
        `created_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `lead_id` (`lead_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}
