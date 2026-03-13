<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Add_bedrooms_to_realestate extends CI_Migration
{
    public function up()
    {
        $CI = &get_instance();

        // Tabelas mais comuns nos módulos imobiliários de Perfex.
        $candidates = [
            'tblproperties',
            'tblrealestate_properties',
            'tblrealestate',
            'properties',
        ];

        foreach ($candidates as $table) {
            if ($this->table_exists($table)) {
                // adiciona a coluna se não existir
                $exists = $CI->db->query("
                    SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME   = '{$table}'
                      AND COLUMN_NAME  = 'bedrooms'
                ")->row()->c > 0;

                if (!$exists) {
                    $CI->db->query("ALTER TABLE `{$table}` ADD COLUMN `bedrooms` INT NULL DEFAULT 0 AFTER `price`");
                }
            }
        }
    }

    public function down()
    {
        $CI = &get_instance();
        $candidates = ['tblproperties','tblrealestate_properties','tblrealestate','properties'];

        foreach ($candidates as $table) {
            if ($this->table_exists($table)) {
                $exists = $CI->db->query("
                    SELECT COUNT(*) AS c FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME   = '{$table}'
                      AND COLUMN_NAME  = 'bedrooms'
                ")->row()->c > 0;

                if ($exists) {
                    $CI->db->query("ALTER TABLE `{$table}` DROP COLUMN `bedrooms`");
                }
            }
        }
    }

    private function table_exists($table)
    {
        $CI = &get_instance();
        return $CI->db->query("
            SELECT COUNT(*) AS c FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = '{$table}'
        ")->row()->c > 0;
    }
}