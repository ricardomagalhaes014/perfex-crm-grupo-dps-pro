<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração 344 — Limpeza e consolidação de fontes de lead:
 *
 *  1. Todas as variantes de "IMO Portugal" e "DPS Portugal" → um único registo
 *  2. Todos os outros duplicados por nome exacto → consolidados
 *  3. "Media" e "Dentária" (variantes) → apagadas; leads ficam sem fonte
 */
class Migration_Version_344 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        /* ----------------------------------------------------------------
         * 1. Consolidar TODAS as variantes de IMO Portugal + DPS Portugal
         *    num único registo (o de ID mais baixo entre eles)
         * ---------------------------------------------------------------- */
        $imoPortugalAll = $this->db->query("
            SELECT id, name FROM {$prefix}leads_sources
            WHERE LOWER(TRIM(name)) IN (
                'imo portugal', 'expansao imo', 'expansão imo',
                'dps portugal', 'dps-portugal'
            )
            ORDER BY id ASC
        ")->result_array();

        if (count($imoPortugalAll) > 1) {
            $keepId    = (int) $imoPortugalAll[0]['id'];
            $removeIds = array_slice(array_column($imoPortugalAll, 'id'), 1);
            $removeIds = array_map('intval', $removeIds);

            // Renomear o registo que fica para "IMO Portugal"
            $this->db->where('id', $keepId)
                ->update($prefix . 'leads_sources', ['name' => 'IMO Portugal']);

            // Re-apontar leads para o ID que fica
            $this->db->where_in('source', $removeIds)
                ->update($prefix . 'leads', ['source' => $keepId]);

            // Apagar os restantes
            $this->db->where_in('id', $removeIds)->delete($prefix . 'leads_sources');

        } elseif (count($imoPortugalAll) === 1) {
            // Garantir nome canónico
            $this->db->where('id', (int) $imoPortugalAll[0]['id'])
                ->update($prefix . 'leads_sources', ['name' => 'IMO Portugal']);
        }

        /* ----------------------------------------------------------------
         * 2. Consolidar quaisquer outros nomes duplicados exactos
         * ---------------------------------------------------------------- */
        $duplicates = $this->db->query("
            SELECT name, MIN(id) as keep_id, GROUP_CONCAT(id ORDER BY id) as all_ids
            FROM {$prefix}leads_sources
            GROUP BY LOWER(TRIM(name))
            HAVING COUNT(*) > 1
        ")->result_array();

        foreach ($duplicates as $dup) {
            $keepId    = (int) $dup['keep_id'];
            $allIds    = array_map('intval', explode(',', $dup['all_ids']));
            $removeIds = array_filter($allIds, fn($id) => $id !== $keepId);
            if (empty($removeIds)) continue;

            $this->db->where_in('source', $removeIds)
                ->update($prefix . 'leads', ['source' => $keepId]);
            $this->db->where_in('id', $removeIds)->delete($prefix . 'leads_sources');
        }

        /* ----------------------------------------------------------------
         * 3. Apagar Media e Dentária (leads ficam com source = NULL)
         * ---------------------------------------------------------------- */
        $toDelete = $this->db->query("
            SELECT id FROM {$prefix}leads_sources
            WHERE LOWER(TRIM(name)) IN ('media','média','dentaria','dentária','dentaria')
        ")->result_array();

        if (!empty($toDelete)) {
            $deleteIds = array_map('intval', array_column($toDelete, 'id'));
            $this->db->where_in('source', $deleteIds)
                ->update($prefix . 'leads', ['source' => null]);
            $this->db->where_in('id', $deleteIds)->delete($prefix . 'leads_sources');
        }
    }

    public function down(): void
    {
        // Não reversível
    }
}
