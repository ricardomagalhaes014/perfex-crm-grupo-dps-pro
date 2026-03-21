<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração 343:
 * Activa automaticamente o módulo dps_teams na tabela de módulos do Perfex.
 * Isto evita ter de ir a Admin → Módulos e activar manualmente.
 */
class Migration_Version_343 extends CI_Migration
{
    public function up(): void
    {
        $module_name = 'dps_teams';

        // Verificar se o módulo já existe na tabela
        $exists = $this->db
            ->where('module_name', $module_name)
            ->count_all_results(db_prefix() . 'modules');

        if ($exists > 0) {
            // Já existe: garantir que está activo
            $this->db->where('module_name', $module_name);
            $this->db->update(db_prefix() . 'modules', [
                'active'            => 1,
                'installed_version' => '1.0.0',
            ]);
        } else {
            // Não existe: inserir e activar
            $this->db->insert(db_prefix() . 'modules', [
                'module_name'       => $module_name,
                'active'            => 1,
                'installed_version' => '1.0.0',
            ]);
        }
    }
}
