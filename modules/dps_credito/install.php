<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$respostas = db_prefix() . 'dps_credito_respostas';
$processos = db_prefix() . 'simulador_credito';
$docs      = db_prefix() . 'dps_credito_docs';

/*
 * -------------------------------------------------------------------------
 * 1. Respostas ao questionário de crédito (uma por lead)
 * -------------------------------------------------------------------------
 * É isto que o comercial preenche antes de poder fechar a lead. Fica separado
 * do processo de crédito porque a resposta existe sempre — mesmo quando é
 * "não abordado" — e o processo só nasce quando há interesse real.
 */
if (!$CI->db->table_exists($respostas)) {
    $CI->db->query('CREATE TABLE `' . $respostas . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `lead_id` INT NOT NULL,
        `abordado` ENUM('sim','nao','nao_atendeu') NOT NULL,
        `situacao` ENUM('financiamento_existente','novo_pedido') NULL DEFAULT NULL,
        `banco` VARCHAR(191) NULL DEFAULT NULL,
        `montante` DECIMAL(15,2) NULL DEFAULT NULL,
        `interessado_proposta` ENUM('sim','nao') NULL DEFAULT NULL,
        `observacoes` TEXT NULL,
        `staff_id` INT NULL,
        `dateadded` DATETIME NOT NULL,
        `dateupdated` DATETIME NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `lead_id` (`lead_id`),
        KEY `abordado` (`abordado`),
        KEY `interessado_proposta` (`interessado_proposta`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

/*
 * -------------------------------------------------------------------------
 * 2. Estender a tabela de crédito que já existe (do simulador_comissoes)
 * -------------------------------------------------------------------------
 * Mesma abordagem das vendas: acrescentar, nunca remover. A tabela tinha
 * cliente/valor/taxa/comissao_total e servia de calculadora; passa a ser
 * também o processo de crédito, ligado à lead que lhe deu origem.
 */
if ($CI->db->table_exists($processos)) {
    $existing = array_map(function ($f) {
        return $f->name;
    }, $CI->db->field_data($processos));

    $novas_colunas = [
        'lead_id'     => 'INT NULL DEFAULT NULL',
        'resposta_id' => 'INT NULL DEFAULT NULL',
        'banco'       => 'VARCHAR(191) NULL DEFAULT NULL',
        'situacao'    => "ENUM('financiamento_existente','novo_pedido') NULL DEFAULT NULL",
        'montante'    => 'DECIMAL(15,2) NULL DEFAULT NULL',
        'estado'      => "VARCHAR(30) NOT NULL DEFAULT 'submetido'",
        'valor_credito' => 'DECIMAL(15,2) NULL DEFAULT NULL',
        'docs_em_falta' => 'TEXT NULL',
        'origem'      => "ENUM('lead','manual') NOT NULL DEFAULT 'manual'",
        'observacoes' => 'TEXT NULL',
        'dateupdated' => 'DATETIME NULL DEFAULT NULL',
    ];

    foreach ($novas_colunas as $coluna => $definicao) {
        if (!in_array($coluna, $existing, true)) {
            $CI->db->query("ALTER TABLE `{$processos}` ADD `{$coluna}` {$definicao}");
        }
    }

    $indices = $CI->db->query("SHOW INDEX FROM `{$processos}`")->result_array();
    $nomes   = array_column($indices, 'Key_name');

    if (!in_array('lead_id', $nomes, true)) {
        $CI->db->query("ALTER TABLE `{$processos}` ADD INDEX `lead_id` (`lead_id`)");
    }
    if (!in_array('estado', $nomes, true)) {
        $CI->db->query("ALTER TABLE `{$processos}` ADD INDEX `estado` (`estado`)");
    }
}

/*
 * -------------------------------------------------------------------------
 * 3. Documentos do processo de crédito
 * -------------------------------------------------------------------------
 */
if (!$CI->db->table_exists($docs)) {
    $CI->db->query('CREATE TABLE `' . $docs . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `credito_id` INT NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `original_name` VARCHAR(255) NULL,
        `descricao` VARCHAR(191) NULL,
        `num_titular` TINYINT NULL DEFAULT NULL,
        `tipo_doc` VARCHAR(60) NULL DEFAULT NULL,
        `size` INT NULL DEFAULT NULL,
        `uploaded_by` INT NULL,
        `dateadded` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `credito_id` (`credito_id`),
        KEY `titular_tipo` (`credito_id`,`num_titular`,`tipo_doc`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
} else {
    $existing_docs = array_map(fn($f) => $f->name, $CI->db->field_data($docs));
    foreach (['num_titular' => 'TINYINT NULL DEFAULT NULL', 'tipo_doc' => 'VARCHAR(60) NULL DEFAULT NULL', 'size' => 'INT NULL DEFAULT NULL'] as $col => $def) {
        if (!in_array($col, $existing_docs, true)) {
            $CI->db->query("ALTER TABLE `{$docs}` ADD `{$col}` {$def}");
        }
    }
}

/*
 * -------------------------------------------------------------------------
 * 3b. Titulares do processo de crédito
 * -------------------------------------------------------------------------
 */
$titulares = db_prefix() . 'dps_credito_titulares';
if (!$CI->db->table_exists($titulares)) {
    $CI->db->query('CREATE TABLE `' . $titulares . "` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `credito_id` INT NOT NULL,
        `num_titular` TINYINT NOT NULL,
        `nome` VARCHAR(191) NULL,
        `nif` VARCHAR(20) NULL,
        `data_nascimento` DATE NULL,
        `morada` TEXT NULL,
        `regime_casamento` VARCHAR(60) NULL,
        `profissao` VARCHAR(191) NULL,
        `rendimento_mensal` DECIMAL(10,2) NULL,
        `telefone` VARCHAR(30) NULL,
        `email` VARCHAR(191) NULL,
        `dateadded` DATETIME NOT NULL,
        `dateupdated` DATETIME NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `credito_titular` (`credito_id`,`num_titular`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

/*
 * -------------------------------------------------------------------------
 * 4. Opções
 * -------------------------------------------------------------------------
 * Quais os estados de lead que contam como "fechar". Ficam em opção para o
 * Ricardo ajustar sem mexer em código — os IDs abaixo são os estados de saída
 * do funil (Concretizado, Sem interesse, Para outras oportunidades).
 */
if (get_option('dps_credito_estados_fecho') === '') {
    add_option('dps_credito_estados_fecho', '13,5,3');
}

if (get_option('dps_credito_notificar_staff') === '') {
    // Quem recebe aviso quando nasce um processo de crédito. Vazio = todos os admins.
    add_option('dps_credito_notificar_staff', '');
}

if (get_option('dps_credito_bloqueio_ativo') === '') {
    add_option('dps_credito_bloqueio_ativo', '1');
}

/*
 * -------------------------------------------------------------------------
 * 5. Pasta de uploads protegida
 * -------------------------------------------------------------------------
 */
$upload_path = FCPATH . 'uploads/dps_credito/';
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0755, true);
}

if (!file_exists($upload_path . '.htaccess')) {
    file_put_contents($upload_path . '.htaccess', "Deny from all\n");
}

if (!file_exists($upload_path . 'index.html')) {
    file_put_contents($upload_path . 'index.html', '');
}
