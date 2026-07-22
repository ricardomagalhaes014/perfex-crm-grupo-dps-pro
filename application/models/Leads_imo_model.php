<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leads_imo_model extends App_Model
{
    /**
     * Grupos de fontes definidos por nome (não por ID fixo).
     * Após a migração 344 os nomes duplicados e "DPS Portugal" estão consolidados.
     */
    private $sourceGroupNames = [
        'expansao_imo'     => ['IMO Portugal', 'Imo Portugal'],
        'imo'              => ['Imo Brasil', 'IMO Brasil'],
        'imo_dubai'        => ['IMO Dubai', 'Imo Dubai'],
        'bsx'              => ['BSX'],
        'contacto_pessoal' => ['Contacto Pessoal', 'Contacto pessoal'],
    ];

    /**
     * Rótulos legíveis para o frontend.
     */
    public function get_source_groups(): array
    {
        return [
            'expansao_imo'     => 'IMO Portugal',
            'imo'              => 'IMO Brasil',
            'imo_dubai'        => 'IMO Dubai',
            'bsx'              => 'BSX',
            'contacto_pessoal' => 'Contacto Pessoal',
        ];
    }

    /**
     * Resolve os IDs da tabela leads_sources a partir dos nomes do grupo.
     * Robusto contra IDs que variam por instalação.
     */
    private function resolveSourceIds(string $group): array
    {
        if (!isset($this->sourceGroupNames[$group])) {
            return [];
        }
        $names = $this->sourceGroupNames[$group];
        $rows  = $this->db
            ->select('id')
            ->where_in('name', $names)
            ->get(db_prefix() . 'leads_sources')
            ->result_array();
        return array_column($rows, 'id');
    }

    /**
     * Retorna:
     *  - lista de statuses (Quente, Morno, Frio, etc.)
     *  - linhas por comercial com contagem de cada status
     * para um grupo de fonte.
     */
    public function report_by_source_group($group, $fromDate = null, $toDate = null)
    {
        $sourceIds = $this->resolveSourceIds($group);
        if (empty($sourceIds)) {
            return ['statuses' => [], 'rows' => []];
        }

        // Todos os status de lead
        $statuses = $this->db
            ->order_by('statusorder', 'asc')
            ->get(db_prefix() . 'leads_status')
            ->result_array();

        // Contagem por comercial + status
        $this->db->select('l.assigned as staff_id, s.id as status_id, COUNT(*) as total');
        $this->db->from(db_prefix() . 'leads l');
        $this->db->join(db_prefix() . 'leads_status s', 's.id = l.status', 'left');
        $this->db->where_in('l.source', $sourceIds);
        $this->db->where('l.assigned IS NOT NULL', null, false);

        if (!empty($fromDate)) {
            $this->db->where('DATE(l.last_status_change) >=', $fromDate);
        }
        if (!empty($toDate)) {
            $this->db->where('DATE(l.last_status_change) <=', $toDate);
        }

        $this->db->group_by(['l.assigned', 's.id']);
        $rows = $this->db->get()->result_array();

        $byStaff = [];
        foreach ($rows as $r) {
            $sid = (int) $r['staff_id'];
            $byStaff[$sid][(int) $r['status_id']] = (int) $r['total'];
        }

        $staff = $this->db->where('active', 1)->get(db_prefix() . 'staff')->result_array();

        $outRows = [];
        foreach ($staff as $st) {
            $sid = (int) $st['staffid'];
            $row = [
                'staff_id'   => $sid,
                'staff_name' => trim($st['firstname'] . ' ' . $st['lastname']),
                'counts'     => [],
                'total'      => 0,
            ];
            foreach ($statuses as $s) {
                $sid2 = (int) $s['id'];
                $cnt  = $byStaff[$sid][$sid2] ?? 0;
                $row['counts'][$sid2] = $cnt;
                $row['total']        += $cnt;
            }
            if ($row['total'] > 0) {
                $outRows[] = $row;
            }
        }

        usort($outRows, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'statuses' => array_map(fn($s) => ['id' => (int) $s['id'], 'name' => $s['name']], $statuses),
            'rows'     => $outRows,
        ];
    }

    /**
     * Distribuição de leads por comercial para um status específico,
     * filtrada por grupo de fonte e datas opcionais.
     */
    public function get_status_distribution(int $statusId, string $group, $fromDate = null, $toDate = null): array
    {
        $sourceIds = $this->resolveSourceIds($group);
        if (empty($sourceIds)) {
            return ['status_name' => '', 'rows' => []];
        }

        $statusRow  = $this->db->where('id', $statusId)->get(db_prefix() . 'leads_status')->row_array();
        $statusName = $statusRow ? $statusRow['name'] : '';

        $this->db->select('l.assigned as staff_id, COUNT(*) as total');
        $this->db->from(db_prefix() . 'leads l');
        $this->db->where('l.status', $statusId);
        $this->db->where_in('l.source', $sourceIds);
        $this->db->where('l.assigned IS NOT NULL', null, false);

        if (!empty($fromDate)) {
            $this->db->where('DATE(l.last_status_change) >=', $fromDate);
        }
        if (!empty($toDate)) {
            $this->db->where('DATE(l.last_status_change) <=', $toDate);
        }

        $this->db->group_by('l.assigned');
        $rows = $this->db->get()->result_array();

        $byStaff = [];
        foreach ($rows as $r) {
            $byStaff[(int) $r['staff_id']] = (int) $r['total'];
        }

        $staff   = $this->db->where('active', 1)->get(db_prefix() . 'staff')->result_array();
        $outRows = [];
        foreach ($staff as $st) {
            $sid = (int) $st['staffid'];
            $cnt = $byStaff[$sid] ?? 0;
            if ($cnt > 0) {
                $outRows[] = [
                    'staff_id'   => $sid,
                    'staff_name' => trim($st['firstname'] . ' ' . $st['lastname']),
                    'total'      => $cnt,
                ];
            }
        }

        usort($outRows, fn($a, $b) => $b['total'] <=> $a['total']);

        return ['status_name' => $statusName, 'rows' => $outRows];
    }
}
