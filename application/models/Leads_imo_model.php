<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leads_imo_model extends App_Model
{
    /**
     * Grupos de fontes:
     *
     *  id 1 = Imo Brasil (Google)
     *  id 2 = Imo Brasil (Meta)
     *  id 3 = DENTARIA
     *  id 4 = MEDIA
     *  id 5 = Imo Portugal (após migração 344, o duplicado é consolidado aqui)
     *  id 6 = Imo Dubai
     */
    private $sourceGroups = [
        'imo'          => [1, 2], // IMO BRASIL (Google + Meta)
        'expansao_imo' => [5],    // IMO PORTUGAL (duplicado consolidado pela migração 344)
        'imo_dubai'    => [6],    // IMO DUBAI
        'media'        => [4],    // MEDIA
        'dental'       => [3],    // DENTARIA
    ];

    /**
     * Retorna a lista de grupos de fontes disponíveis com rótulos legíveis.
     */
    public function get_source_groups(): array
    {
        return [
            'expansao_imo' => 'IMO Portugal',
            'imo'          => 'IMO Brasil',
            'imo_dubai'    => 'IMO Dubai',
            'media'        => 'Media',
            'dental'       => 'Dentária',
        ];
    }

    /**
     * Retorna:
     *  - lista de statuses (Quente, Morno, Frio, etc.)
     *  - linhas por comercial com contagem de cada status
     * para um grupo de fonte (imo, expansao_imo, media, dental)
     *
     * @param string      $group    Chave do grupo de fontes
     * @param string|null $fromDate Data início (YYYY-MM-DD) ou null
     * @param string|null $toDate   Data fim (YYYY-MM-DD) ou null
     */
    public function report_by_source_group($group, $fromDate = null, $toDate = null)
    {
        if (!isset($this->sourceGroups[$group])) {
            return ['statuses' => [], 'rows' => []];
        }

        $sourceIds = $this->sourceGroups[$group];

        // 1) Todos os status de lead
        $statuses = $this->db
            ->order_by('statusorder', 'asc')
            ->get(db_prefix() . 'leads_status')
            ->result_array();

        // 2) Contagem de leads por comercial + status, filtrando pelas fontes
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
            $sid       = (int) $r['staff_id'];
            $status_id = (int) $r['status_id'];
            if (!isset($byStaff[$sid])) {
                $byStaff[$sid] = [];
            }
            $byStaff[$sid][$status_id] = (int) $r['total'];
        }

        // 3) Todos os comerciais ativos
        $staff = $this->db
            ->where('active', 1)
            ->get(db_prefix() . 'staff')
            ->result_array();

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
                $status_id = (int) $s['id'];
                $cnt       = isset($byStaff[$sid][$status_id]) ? (int) $byStaff[$sid][$status_id] : 0;
                $row['counts'][$status_id] = $cnt;
                $row['total']              += $cnt;
            }

            if ($row['total'] > 0) {
                $outRows[] = $row;
            }
        }

        usort($outRows, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return [
            'statuses' => array_map(function ($s) {
                return ['id' => (int) $s['id'], 'name' => $s['name']];
            }, $statuses),
            'rows' => $outRows,
        ];
    }

    /**
     * Distribuição de leads por comercial para um status específico,
     * filtrada por grupo de fonte e datas opcionais.
     *
     * @param int         $statusId   ID do status de lead
     * @param string      $group      Chave do grupo de fontes
     * @param string|null $fromDate
     * @param string|null $toDate
     */
    public function get_status_distribution(int $statusId, string $group, $fromDate = null, $toDate = null): array
    {
        if (!isset($this->sourceGroups[$group])) {
            return ['status_name' => '', 'rows' => []];
        }

        $sourceIds = $this->sourceGroups[$group];

        // Nome do status
        $statusRow = $this->db
            ->where('id', $statusId)
            ->get(db_prefix() . 'leads_status')
            ->row_array();

        $statusName = $statusRow ? $statusRow['name'] : '';

        // Contagem por comercial
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

        // Índice por staff_id
        $byStaff = [];
        foreach ($rows as $r) {
            $byStaff[(int) $r['staff_id']] = (int) $r['total'];
        }

        // Todos os comerciais ativos
        $staff = $this->db->where('active', 1)->get(db_prefix() . 'staff')->result_array();

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
