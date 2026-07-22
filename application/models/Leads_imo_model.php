<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leads_imo_model extends App_Model
{
    /**
     * Grupos de fontes definidos por nome NORMALIZADO (minúsculas, espaços
     * unicode/escondidos colapsados) — não por ID fixo, e não sensível a
     * maiúsculas/espaços invisíveis que possam existir nos dados reais.
     * "expansao_imo" inclui todas as variantes históricas que ainda podem
     * existir enquanto a consolidação de fontes não corre/não apanha tudo.
     */
    private $sourceGroupNames = [
        'expansao_imo'     => ['imo portugal', 'expansao imo', 'expansão imo', 'dps portugal', 'dps-portugal'],
        'imo'              => ['imo brasil'],
        'imo_dubai'        => ['imo dubai'],
        'bsx'              => ['bsx leads', 'bsx'],
        'contacto_pessoal' => ['contacto pessoal'],
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
     * Normaliza um nome de fonte: colapsa espaços unicode/escondidos e minúsculas.
     * Tem de ser idêntica à usada em Leads_imo::normalizeSourceName() no controller
     * para que a consolidação e os relatórios concordem sobre o que é "o mesmo nome".
     */
    private function normalizeSourceName(string $name): string
    {
        $s = preg_replace('/[\x{00A0}\x{1680}\x{2000}-\x{200D}\x{202F}\x{205F}\x{3000}\x{FEFF}]/u', ' ', $name);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);
        return mb_strtolower($s, 'UTF-8');
    }

    /**
     * Resolve os IDs da tabela leads_sources a partir dos nomes do grupo.
     * Busca TODAS as fontes e compara por nome normalizado em PHP — não confia
     * em WHERE name IN (...) do SQL, que falha com espaços unicode escondidos
     * ou depende da collation da coluna para (in)sensibilidade a maiúsculas.
     */
    private function resolveSourceIds(string $group): array
    {
        if (!isset($this->sourceGroupNames[$group])) {
            return [];
        }
        $aliases = $this->sourceGroupNames[$group];
        $all     = $this->db->select('id, name')->get(db_prefix() . 'leads_sources')->result_array();

        $ids = [];
        foreach ($all as $row) {
            if (in_array($this->normalizeSourceName($row['name']), $aliases, true)) {
                $ids[] = (int) $row['id'];
            }
        }
        return $ids;
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

        // Contagem por comercial + status (assigned=0 = não atribuído, incluído propositadamente)
        $this->db->select('l.assigned as staff_id, s.id as status_id, COUNT(*) as total');
        $this->db->from(db_prefix() . 'leads l');
        $this->db->join(db_prefix() . 'leads_status s', 's.id = l.status', 'left');
        $this->db->where_in('l.source', $sourceIds);

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

        // Adiciona um "comercial" virtual para leads sem atribuição (staff_id = 0),
        // para que o total bata certo com o badge nativo do Perfex (que não filtra por assigned).
        $staff[] = ['staffid' => 0, 'firstname' => 'Não atribuído', 'lastname' => ''];

        $outRows = [];
        foreach ($staff as $st) {
            $sid = (int) $st['staffid'];
            $row = [
                'staff_id'   => $sid,
                'staff_name' => $sid === 0 ? 'Não atribuído' : trim($st['firstname'] . ' ' . $st['lastname']),
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

        $staff = $this->db->where('active', 1)->get(db_prefix() . 'staff')->result_array();
        $staff[] = ['staffid' => 0, 'firstname' => 'Não atribuído', 'lastname' => ''];

        $outRows = [];
        foreach ($staff as $st) {
            $sid = (int) $st['staffid'];
            $cnt = $byStaff[$sid] ?? 0;
            if ($cnt > 0) {
                $outRows[] = [
                    'staff_id'   => $sid,
                    'staff_name' => $sid === 0 ? 'Não atribuído' : trim($st['firstname'] . ' ' . $st['lastname']),
                    'total'      => $cnt,
                ];
            }
        }

        usort($outRows, fn($a, $b) => $b['total'] <=> $a['total']);

        return ['status_name' => $statusName, 'rows' => $outRows];
    }
}
