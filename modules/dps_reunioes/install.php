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

/* ---------------------------------------------------------------------------
 * Propostas de reunião em massa
 *
 * O comercial escolhe um estado de lead e um dia; o sistema dá a cada lead um
 * horário diferente e envia o convite. A reunião só nasce quando o cliente
 * aceita — por isso a proposta vive numa tabela própria e não na de reuniões,
 * que só deve conter compromissos reais.
 * ------------------------------------------------------------------------ */

if (!$CI->db->table_exists(db_prefix() . 'dps_reunioes_campanhas')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_reunioes_campanhas` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11) NOT NULL,
        `lead_status_id` INT(11) NOT NULL,
        `dia_inicio` DATE NOT NULL,
        `canal` VARCHAR(20) NOT NULL DEFAULT 'ambos',
        `mensagem` TEXT NULL,
        `total` INT(11) NOT NULL DEFAULT 0,
        `date_created` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `staff_id` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'dps_reunioes_propostas')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "dps_reunioes_propostas` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `campanha_id` INT(11) NOT NULL,
        `lead_id` INT(11) NOT NULL,
        `staff_id` INT(11) NOT NULL,

        -- O horário reservado para esta lead. Enquanto a proposta estiver
        -- pendente ou aceite, mais ninguém o recebe.
        `data_hora` DATETIME NOT NULL,

        -- Chave do link público. É o único segredo que protege a página de
        -- aceitação, por isso nasce de random_bytes e nunca de algo previsível.
        `chave` VARCHAR(64) NOT NULL,

        `estado` VARCHAR(20) NOT NULL DEFAULT 'pendente',
        `canal` VARCHAR(20) NOT NULL DEFAULT 'ambos',

        `cliente_nome` VARCHAR(191) NULL,
        `cliente_email` VARCHAR(191) NULL,
        `cliente_telefone` VARCHAR(60) NULL,

        `enviado_em` DATETIME NULL,
        `enviado_por` VARCHAR(20) NULL,
        `erro_envio` VARCHAR(255) NULL,
        `respondido_em` DATETIME NULL,
        `reuniao_id` INT(11) NULL,

        `date_created` DATETIME NOT NULL,

        PRIMARY KEY (`id`),
        UNIQUE KEY `chave` (`chave`),
        KEY `campanha_id` (`campanha_id`),
        KEY `lead_id` (`lead_id`),
        KEY `estado_envio` (`estado`, `enviado_em`),
        KEY `slot` (`staff_id`, `data_hora`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

add_option('dps_reunioes_hora_inicio', '09:00');
add_option('dps_reunioes_hora_fim', '19:30');
add_option('dps_reunioes_wa_por_dia', '20');
add_option('dps_reunioes_texto_convite', dps_reunioes_texto_convite_por_omissao());
