<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leads_imo_model extends App_Model
{
    /**
     * Grupos de fontes (de acordo com a tua tela):
     *
     *  id 1 = Google
     *  id 2 = Meta
     *  id 3 = DENTARIA
     *  id 4 = MEDIA
     *  id 5 = EXPANSAO IMO
     */
    private $sourceGroups = [
        // IMO = Google + Meta
        'imo'          => [1, 2],

        // EXPANSAO IMO separado
        'expansao_imo' => [5],

        // MEDIA
        'media'        => [4],

        // DENTARIA
        'dental'       => [3],
    ];

    /**
     * Retorna:
     *  - lista de statuses (Quente, Morno, Frio, etc.)
     *  - linhas por comercial com contagem de cada status
     * para um grupo de fonte (imo, expansao_imo, media, dental)
     *
     * @param string      $group    Chave do grupo de fontes (imo, expansao_imo, media, dental)
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

        // FILTRO POR DATA
        // Aqui estamos a usar a coluna last_status_change para medir "movimentação".
        // Se quiser filtrar pela data de criação da lead, troque por: DATE(l.dateadded)
        if (!empty($fromDate)) {
            $this->db->where('DATE(l.last_status_change) >=', $fromDate);
        }
        if (!empty($toDate)) {
            $this->db->where('DATE(l.last_status_change) <=', $toDate);
        }

        $this->db->group_by(['l.assigned', 's.id']);

        $rows = $this->db->get()->result_array();

        // indexar contagens: [staff_id][status_id] = total
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

            // Só entra se tiver pelo menos 1 lead nessa fonte
            if ($row['total'] > 0) {
                $outRows[] = $row;
            }
        }

        // Ordena por total desc
        usort($outRows, function ($a, $b) {
            if ($a['total'] == $b['total']) {
                return 0;
            }
            return ($a['total'] < $b['total']) ? 1 : -1;
        });

        return [
            'statuses' => array_map(function ($s) {
                return [
                    'id'   => (int) $s['id'],
                    'name' => $s['name'],
                ];
            }, $statuses),
            'rows' => $outRows,
        ];
    }
}
