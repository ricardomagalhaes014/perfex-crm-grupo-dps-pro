<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Criar tabela principal de imóveis DPS
if (!$CI->db->table_exists(db_prefix() . 'dps_imoveis')) {
    $CI->db->query("CREATE TABLE `" . db_prefix() . "dps_imoveis` (
        `id`                      INT(11) NOT NULL AUTO_INCREMENT,
        `titulo`                  VARCHAR(255) NOT NULL,
        `tipo`                    ENUM('Apartamento','Moradia','Loja','Terreno','Escritório','Armazém','Outro') NOT NULL DEFAULT 'Apartamento',
        `tipologia`               VARCHAR(10) NULL DEFAULT NULL COMMENT 'T0, T1, T2, T3, T4, T4+',
        `distrito`                VARCHAR(100) NULL DEFAULT NULL,
        `cidade`                  VARCHAR(100) NULL DEFAULT NULL,
        `morada`                  VARCHAR(255) NULL DEFAULT NULL,
        `preco`                   DECIMAL(15,2) NULL DEFAULT NULL,
        `area_total`              DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'm²',
        `nr_quartos`              INT(3) NULL DEFAULT 0,
        `area_quartos`            DECIMAL(10,2) NULL DEFAULT NULL,
        `nr_suites`               INT(3) NULL DEFAULT 0,
        `area_suites`             DECIMAL(10,2) NULL DEFAULT NULL,
        `nr_salas`                INT(3) NULL DEFAULT 0,
        `area_salas`              DECIMAL(10,2) NULL DEFAULT NULL,
        `nr_casas_banho`          INT(3) NULL DEFAULT 0,
        `area_casas_banho`        DECIMAL(10,2) NULL DEFAULT NULL,
        `area_cozinha`            DECIMAL(10,2) NULL DEFAULT NULL,
        `garagem`                 TINYINT(1) NULL DEFAULT 0,
        `lugar_garagem`           TINYINT(1) NULL DEFAULT 0,
        `ano_construcao`          INT(4) NULL DEFAULT NULL,
        `equipamento`             TEXT NULL DEFAULT NULL,
        `texto_livre`             TEXT NULL DEFAULT NULL COMMENT 'Descrição livre do imóvel',
        `foto_principal`          VARCHAR(255) NULL DEFAULT NULL,
        `fotos`                   TEXT NULL DEFAULT NULL COMMENT 'JSON array de paths de fotos',
        `doc_cmi`                 VARCHAR(255) NULL DEFAULT NULL COMMENT 'Caderneta de Mais-Valias Imobiliárias',
        `doc_cc_proprietarios`    VARCHAR(255) NULL DEFAULT NULL COMMENT 'CC dos Proprietários',
        `doc_caderneta_predial`   VARCHAR(255) NULL DEFAULT NULL COMMENT 'Caderneta Predial',
        `nome_proprietarios`      VARCHAR(255) NULL DEFAULT NULL,
        `contacto_proprietario`   VARCHAR(50) NULL DEFAULT NULL,
        `mail_proprietario`       VARCHAR(191) NULL DEFAULT NULL,
        `agente_id`               INT(11) NULL DEFAULT NULL COMMENT 'staffid do agente responsável',
        `status`                  ENUM('pendente','aprovado','rejeitado','arquivado') NOT NULL DEFAULT 'pendente',
        `published_website`       TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = visível no dpsimobiliario.pt',
        `approver_id`             INT(11) NULL DEFAULT NULL COMMENT 'staffid do gestor que aprovou',
        `date_approval`           DATETIME NULL DEFAULT NULL,
        `notas_aprovacao`         TEXT NULL DEFAULT NULL,
        `datecreated`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `datemodified`            DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `created_by`              INT(11) NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_status` (`status`),
        KEY `idx_published` (`published_website`),
        KEY `idx_agente` (`agente_id`),
        KEY `idx_tipo` (`tipo`),
        KEY `idx_distrito` (`distrito`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
}
