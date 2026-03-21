<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migração 341:
 * - Renomeia as fontes de leads "Google" (id=1) e "Meta" (id=2) para "Imo Brasil"
 * - Renomeia "EXPANSAO IMO" (id=5) para "Imo Portugal"
 * - Garante que a fonte "Imo Dubai" existe (id=6 ou nova inserção)
 */
class Migration_Version_341 extends CI_Migration
{
    public function up(): void
    {
        // Renomear Google → Imo Brasil
        $this->db->where('id', 1);
        $this->db->update(db_prefix() . 'leads_sources', ['name' => 'Imo Brasil']);

        // Renomear Meta → Imo Brasil (2ª entrada — pode ser usada para distinguir origem)
        $this->db->where('id', 2);
        $this->db->update(db_prefix() . 'leads_sources', ['name' => 'Imo Brasil']);

        // Renomear EXPANSAO IMO → Imo Portugal
        $this->db->where('id', 5);
        $this->db->update(db_prefix() . 'leads_sources', ['name' => 'Imo Portugal']);

        // Garantir que Imo Dubai existe (id=6)
        $exists = $this->db->where('id', 6)->count_all_results(db_prefix() . 'leads_sources');
        if ($exists == 0) {
            // Inserir com id específico se possível
            $this->db->query(
                "INSERT IGNORE INTO `" . db_prefix() . "leads_sources` (`id`, `name`) VALUES (6, 'Imo Dubai')"
            );
        } else {
            $this->db->where('id', 6);
            $this->db->update(db_prefix() . 'leads_sources', ['name' => 'Imo Dubai']);
        }
    }
}
