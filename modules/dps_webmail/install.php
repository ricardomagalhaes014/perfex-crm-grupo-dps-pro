<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Criar tabela de configurações de email por utilizador
$CI = &get_instance();

$CI->db->query("
CREATE TABLE IF NOT EXISTS `" . db_prefix() . "dps_webmail_config` (
    `id`          INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id`    INT(11) NOT NULL,
    `email`       VARCHAR(255) NOT NULL,
    `password`    TEXT NOT NULL COMMENT 'Encrypted password',
    `imap_host`   VARCHAR(255) DEFAULT 'imap.hostinger.com',
    `imap_port`   INT(5) DEFAULT 993,
    `smtp_host`   VARCHAR(255) DEFAULT 'smtp.hostinger.com',
    `smtp_port`   INT(5) DEFAULT 587,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
