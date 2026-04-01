<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Script de instalação para o módulo DPS Calendar Sync.
 * Este ficheiro é incluído automaticamente aquando da activação do módulo.
 */
$CI = &get_instance();

// Criar tabela para armazenar tokens OAuth por utilizador
if (!$CI->db->table_exists(db_prefix() . 'dps_calendar_tokens')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_calendar_tokens" . '` (
        `staff_id` INT NOT NULL,
        `calendar_id` VARCHAR(191) NULL DEFAULT 'primary',
        `calendar_name` VARCHAR(191) NULL,
        `access_token` TEXT NULL,
        `refresh_token` TEXT NULL,
        `token_type` VARCHAR(50) NULL,
        `expires_at` DATETIME NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ' COLLATE=' . $CI->db->dbcollat . ';');
}

// Criar tabela para armazenar eventos sincronizados
if (!$CI->db->table_exists(db_prefix() . 'dps_calendar_events')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_calendar_events" . '` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `staff_id` INT NOT NULL,
        `google_event_id` VARCHAR(191) NULL,
        `title` VARCHAR(191) NOT NULL,
        `description` TEXT NULL,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `location` TEXT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NULL,
        PRIMARY KEY (`id`),
        INDEX (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ' COLLATE=' . $CI->db->dbcollat . ';');
}

// Adicionar opções de configuração para cliente Google
add_option('dps_calendar_sync_client_id', '');
add_option('dps_calendar_sync_client_secret', '');
add_option('dps_calendar_sync_redirect_uri', '');
