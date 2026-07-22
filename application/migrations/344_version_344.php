<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração 344 — Limpeza e consolidação de fontes de lead:
 *
 *  1. "DPS Portugal" → fundido em "IMO Portugal" (leads re-apontadas, registo apagado)
 *  2. Duplicados "IMO Portugal" → mantém o ID mais baixo, apaga os restantes
 *  3. "Media" e "Dentária" (todas as variantes) → fontes apagadas;
 *     leads que as usavam ficam sem fonte (source = NULL)
 */
class Migration_Version_344 extends CI_Migration
{
    public function up(): void
    {
        $prefix = db_prefix();

        /* ----------------------------------------------------------------
         * 1. Fundir "DPS Portugal" em "IMO Portugal"
         * ---------------------------------------------------------------- */
        $imoPortugal = $this->db->like('name', 'IMO Portugal', 'none')
            ->or_like('name', 'Imo Portugal', 'none')
            ->order_by('id', 'asc')
            ->limit(1)
            ->get($prefix . 'leads_sources')
            ->row_array();

        $dpsPortugal = $this->db->like('name', 'DPS Portugal', 'none')
            ->get($prefix . 'leads_sources')
            ->result_array();

        if ($imoPortugal && !empty($dpsPortugal)) {
            $dpsIds = array_column($dpsPortugal, 'id');
            // Re-apontar leads de DPS Portugal para IMO Portugal
            $this->db->where_in('source', $dpsIds)
                ->update($prefix . 'leads', ['source' => $imoPortugal['id']]);
            // Apagar registos DPS Portugal
            $this->db->where_in('id', $dpsIds)->delete($prefix . 'leads_sources');
        }

        /* ----------------------------------------------------------------
         * 2. Consolidar todos os nomes duplicados (inclui IMO Portugal x2)
         * ---------------------------------------------------------------- */
        $duplicates = $this->db->query("
            SELECT name, MIN(id) as keep_id, GROUP_CONCAT(id ORDER BY id) as all_ids
            FROM {$prefix}leads_sources
            GROUP BY name
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
         * 3. Apagar Media e Dentária (leads ficam sem fonte)
         * ---------------------------------------------------------------- */
        $toDelete = $this->db->query("
            SELECT id FROM {$prefix}leads_sources
            WHERE name IN ('Media','MEDIA','Média','Dentaria','Dentária','DENTARIA','Dentaria','Dent\xc3\xa1ria')
        ")->result_array();

        if (!empty($toDelete)) {
            $deleteIds = array_column($toDelete, 'id');
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
