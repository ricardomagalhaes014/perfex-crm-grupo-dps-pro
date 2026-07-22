<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração 344:
 * Consolida fontes de lead com nomes duplicados.
 * Para cada grupo de duplicados, mantém o ID mais baixo e re-aponta
 * todos os leads para esse ID, eliminando os registos repetidos.
 */
class Migration_Version_344 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        // Encontrar nomes com mais do que uma entrada
        $duplicates = $this->db->query("
            SELECT name, MIN(id) as keep_id, GROUP_CONCAT(id ORDER BY id) as all_ids
            FROM {$prefix}leads_sources
            GROUP BY name
            HAVING COUNT(*) > 1
        ")->result_array();

        foreach ($duplicates as $dup) {
            $keepId = (int) $dup['keep_id'];
            $allIds = array_map('intval', explode(',', $dup['all_ids']));
            $removeIds = array_filter($allIds, fn($id) => $id !== $keepId);

            if (empty($removeIds)) {
                continue;
            }

            // Re-apontar todos os leads dos IDs duplicados para o ID que fica
            $this->db->query(
                "UPDATE {$prefix}leads SET source = ? WHERE source IN (" . implode(',', $removeIds) . ")",
                [$keepId]
            );

            // Apagar os registos duplicados
            $this->db->where_in('id', $removeIds);
            $this->db->delete($prefix . 'leads_sources');
        }
    }

    public function down(): void
    {
        // Não reversível — os registos duplicados foram apagados
    }
}
