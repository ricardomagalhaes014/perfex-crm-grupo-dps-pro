<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tabela das reuniões online.
 *
 * Uma linha por reunião. Os dados do cliente ficam copiados (nome, email,
 * telefone) de propósito: quem for avisado é quem estava marcado, mesmo que
 * a lead mude de contacto ou seja apagada mais tarde. O aviso tem de saber a
 * quem foi enviado, não a quem seria enviado hoje.
 */
$CI = &get_instance();

if (!$CI->db->table_exists(db_prefix() . 'dps_reunioes')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_reunioes` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,

        -- A quem pertence: uma lead ou um cliente.
        `rel_type` VARCHAR(20) NOT NULL DEFAULT 'lead',
        `rel_id` INT(11) NOT NULL,

        `assunto` VARCHAR(191) NULL,
        `data_hora` DATETIME NOT NULL,
        `duracao_min` INT(11) NOT NULL DEFAULT 30,

        -- O comercial que conduz a reunião.
        `staff_id` INT(11) NOT NULL,

        -- Administrador convidado (opcional) e a resposta dele.
        `convidado_id` INT(11) NULL,
        `convite_estado` VARCHAR(20) NULL,
        `convite_em` DATETIME NULL,

        -- Sala do Jitsi. Guarda-se o link inteiro porque é o que vai no
        -- email e no WhatsApp, e tem de ser exactamente o mesmo nos dois.
        `sala` VARCHAR(120) NOT NULL,
        `link` VARCHAR(255) NOT NULL,

        `estado` VARCHAR(20) NOT NULL DEFAULT 'agendada',

        -- Cópia dos contactos no momento da marcação.
        `cliente_nome` VARCHAR(191) NULL,
        `cliente_email` VARCHAR(191) NULL,
        `cliente_telefone` VARCHAR(60) NULL,

        -- Controlo dos automatismos: cada um corre uma vez só.
        `lembrete_30_em` DATETIME NULL,
        `followup_task_id` INT(11) NULL,

        `duracao_real_min` INT(11) NULL,
        `notas` TEXT NULL,

        `date_created` DATETIME NOT NULL,
        `created_by` INT(11) NULL,

        PRIMARY KEY (`id`),
        KEY `rel` (`rel_type`, `rel_id`),
        KEY `staff_id` (`staff_id`),
        KEY `data_hora` (`data_hora`),
        KEY `estado` (`estado`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}
