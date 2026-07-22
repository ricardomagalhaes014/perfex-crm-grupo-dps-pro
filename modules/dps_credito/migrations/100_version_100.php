<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_100 extends App_module_migration
{
    public function up()
    {
        $CI  = &get_instance();
        $docs = db_prefix() . 'dps_credito_docs';
        $titulares = db_prefix() . 'dps_credito_titulares';

        // Extend docs table with typed-document columns
        if ($CI->db->table_exists($docs)) {
            $existing = array_map(fn($f) => $f->name, $CI->db->field_data($docs));
            foreach (['num_titular' => 'TINYINT NULL DEFAULT NULL', 'tipo_doc' => 'VARCHAR(60) NULL DEFAULT NULL', 'size' => 'INT NULL DEFAULT NULL'] as $col => $def) {
                if (!in_array($col, $existing, true)) {
                    $CI->db->query("ALTER TABLE `{$docs}` ADD `{$col}` {$def}");
                }
            }
        }

        // Create titulares table
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
    }
}
