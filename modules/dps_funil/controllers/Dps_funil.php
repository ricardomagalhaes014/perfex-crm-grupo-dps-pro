<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Funil de Leads — vista de fluxo por fases.
 */
class Dps_funil extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_model');
    }

    /**
     * Definição das fases do funil.
     * Cada fase agrupa um conjunto de estados (por id) na ordem do negócio.
     * Estados que não estejam mapeados aqui aparecem numa fase "Outros"
     * automática, para nunca desaparecerem.
     */
    private function phases()
    {
        return [
            ['key' => 'entrada',       'title' => 'Entrada',                      'icon' => 'fa fa-inbox',        'statuses' => [4]],
            ['key' => 'contacto',      'title' => 'Em contacto',                  'icon' => 'fa fa-phone',        'statuses' => [7, 6, 9, 1, 12]],
            ['key' => 'vip',           'title' => 'VIP (quentes)',                'icon' => 'fa fa-star',         'statuses' => [17, 14, 18]],
            ['key' => 'oportunidades', 'title' => 'Oportunidades / Necessidades', 'icon' => 'fa fa-bullseye',     'statuses' => [3, 2, 19, 16]],
            /*
             * PROPOSTAS ENVIADAS é uma fase do funil, e faltava.
             *
             * O estado #20 não estava mapeado em fase nenhuma: caía no balde
             * "Outros" e 176 leads desapareciam do funil — precisamente as que
             * estão mais perto de fechar. Regra do dono (05/08/2026): entrada,
             * em contacto, VIP, propostas enviadas, fechos.
             */
            ['key' => 'propostas',     'title' => 'Propostas enviadas',           'icon' => 'fa fa-paper-plane',  'statuses' => [20]],
            ['key' => 'fecho',         'title' => 'Fechos',                       'icon' => 'fa fa-check-circle', 'statuses' => [13, 10]],
            ['key' => 'perdidas',      'title' => 'Perdidas',                     'icon' => 'fa fa-times-circle', 'statuses' => [5]],
        ];
    }

    public function index()
    {
        if (! is_staff_member()) {
            access_denied('leads');
        }

        // Todos os estados (id => meta) — direto da tabela (robusto).
        $statuses = [];
        foreach ($this->db->order_by('statusorder')->get(db_prefix() . 'leads_status')->result() as $s) {
            $statuses[(int) $s->id] = ['id' => (int) $s->id, 'name' => $s->name, 'color' => $s->color];
        }

        // Filtro por comercial (só admins / quem vê todas as leads podem escolher).
        $can_view_all = is_admin() || staff_can('view', 'leads');
        $comercial    = (int) $this->input->get('comercial');
        if (! $can_view_all) {
            $comercial = get_staff_user_id(); // não-admin: só as suas
        }

        // Filtro inicial por fonte da lead.
        $fonte  = (int) $this->input->get('fonte');
        $fontes = $this->db->select('id, name')->order_by('name')
            ->get(db_prefix() . 'leads_sources')->result_array();

        // Lista de comerciais (com leads atribuídas) para o dropdown.
        $comerciais = [];
        if ($can_view_all) {
            $comerciais = $this->db->query(
                'SELECT s.staffid, s.firstname, s.lastname, COUNT(l.id) AS c
                 FROM ' . db_prefix() . 'staff s
                 JOIN ' . db_prefix() . 'leads l ON l.assigned = s.staffid
                 GROUP BY s.staffid ORDER BY s.firstname, s.lastname'
            )->result_array();
        }

        // Condições comuns (fonte + comercial) aplicadas a todas as contagens.
        $conds = [];
        if ($comercial > 0) {
            $conds[] = 'assigned = ' . (int) $comercial;
        }
        if ($fonte > 0) {
            $conds[] = 'source = ' . (int) $fonte;
        }
        $where_sql = $conds ? (' WHERE ' . implode(' AND ', $conds)) : '';

        // Contagem por estado.
        $counts = [];
        foreach ($this->db->query(
            'SELECT status, COUNT(id) AS c FROM ' . db_prefix() . 'leads' . $where_sql . ' GROUP BY status'
        )->result_array() as $r) {
            $counts[(int) $r['status']] = (int) $r['c'];
        }

        // Distribuição por comercial, por estado (para o gráfico ao expandir).
        // Uma só query dá a matriz estado→comercial→nº.
        $breakdown = [];
        foreach ($this->db->query(
            'SELECT status, assigned, COUNT(id) AS c FROM ' . db_prefix() . 'leads' . $where_sql . ' GROUP BY status, assigned'
        )->result_array() as $r) {
            $breakdown[(int) $r['status']][(int) $r['assigned']] = (int) $r['c'];
        }

        // Nomes dos comerciais para o gráfico.
        $staff_map = [];
        foreach ($this->db->select('staffid, firstname, lastname')
            ->get(db_prefix() . 'staff')->result() as $s) {
            $staff_map[(int) $s->staffid] = trim($s->firstname . ' ' . $s->lastname);
        }

        // Montar fases + apanhar estados não mapeados.
        $phases = $this->phases();
        $mapped = [];
        foreach ($phases as &$phase) {
            $phase['items'] = [];
            $phase['total'] = 0;
            foreach ($phase['statuses'] as $sid) {
                $sid = (int) $sid;
                if (! isset($statuses[$sid])) {
                    continue;
                }
                $mapped[$sid]    = true;
                $c               = isset($counts[$sid]) ? $counts[$sid] : 0;
                $phase['items'][] = array_merge($statuses[$sid], ['count' => $c]);
                $phase['total']  += $c;
            }
        }
        unset($phase);

        // Fase "Outros" para estados novos/não mapeados.
        $others = [];
        $othersTotal = 0;
        foreach ($statuses as $sid => $meta) {
            if (! isset($mapped[$sid])) {
                $c        = isset($counts[$sid]) ? $counts[$sid] : 0;
                $others[] = array_merge($meta, ['count' => $c]);
                $othersTotal += $c;
            }
        }
        if (! empty($others)) {
            $phases[] = ['key' => 'outros', 'title' => 'Outros', 'icon' => 'fa fa-folder-o', 'items' => $others, 'total' => $othersTotal];
        }

        $data['title']        = 'Funil de Leads';
        $data['phases']       = $phases;
        $data['total_leads']  = array_sum($counts);
        $data['can_view_all'] = $can_view_all;
        $data['comercial']    = $comercial;
        $data['comerciais']   = $comerciais;
        $data['fonte']        = $fonte;
        $data['fontes']       = $fontes;
        $data['breakdown']    = $breakdown;
        $data['staff_map']    = $staff_map;

        /*
         * QUEM TEM O QUÊ, EM CADA FASE.
         *
         * O breakdown por estado já existia, mas ninguém trabalha por estado —
         * trabalha-se por fase. Somam-se aqui os estados de cada fase, para o
         * quadro poder mostrar, fase a fase, quantas leads tem cada comercial.
         * Sem isto via-se o total da fase e não se sabia de quem era.
         * Pedido do dono (05/08/2026).
         */
        $por_fase = [];

        foreach ($phases as $ph) {
            $ids = [];

            foreach ($ph['items'] as $it) {
                $ids[] = (int) $it['id'];
            }

            $soma = [];

            foreach ($ids as $sid) {
                foreach (($breakdown[$sid] ?? []) as $staff => $n) {
                    $staff = (int) $staff;
                    $soma[$staff] = ($soma[$staff] ?? 0) + (int) $n;
                }
            }

            // Sem leads não há gráfico — um quadro vazio só ocupa espaço.
            $soma = array_filter($soma, function ($n) { return $n > 0; });
            arsort($soma);

            $etiquetas = [];
            $valores   = [];

            foreach ($soma as $staff => $n) {
                $etiquetas[] = $staff_map[$staff] ?? ($staff > 0 ? 'Comercial #' . $staff : 'Sem comercial');
                $valores[]   = (int) $n;
            }

            $por_fase[$ph['key']] = [
                'titulo'    => $ph['title'],
                'total'     => (int) $ph['total'],
                'etiquetas' => $etiquetas,
                'valores'   => $valores,
            ];
        }

        $data['por_fase'] = $por_fase;
        $this->load->view('funil', $data);
    }

    /**
     * Lista as leads de um estado específico.
     */
    public function estado($status_id = '')
    {
        if (! is_staff_member()) {
            access_denied('leads');
        }

        $status_id = (int) $status_id;
        $status    = $this->db->where('id', $status_id)->get(db_prefix() . 'leads_status')->row();
        if (! $status) {
            show_404();
        }

        // Filtro por comercial (mesma regra do funil).
        $can_view_all = is_admin() || staff_can('view', 'leads');
        $comercial    = (int) $this->input->get('comercial');
        if (! $can_view_all) {
            $comercial = get_staff_user_id(); // não-admin: só as suas
        }

        $fonte = (int) $this->input->get('fonte');

        // Query base (com filtro de comercial e fonte, se aplicável).
        $where = ['status' => $status_id];
        if ($comercial > 0) {
            $where['assigned'] = $comercial;
        }
        if ($fonte > 0) {
            $where['source'] = $fonte;
        }

        $this->db->select('id, name, phonenumber, email, assigned, dateadded, lastcontact');
        $this->db->where($where);
        $this->db->order_by('lastcontact', 'DESC');
        $this->db->limit(500);
        $leads = $this->db->get(db_prefix() . 'leads')->result_array();

        $total = (int) $this->db->where($where)->count_all_results(db_prefix() . 'leads');

        $data['title']        = $status->name;
        $data['status']       = $status;
        $data['leads']        = $leads;
        $data['total']        = $total;
        $data['showing']      = count($leads);
        $data['comercial']    = $comercial;
        $data['comercial_nome'] = $comercial > 0 ? get_staff_full_name($comercial) : '';
        $data['fonte']        = $fonte;
        $this->load->view('estado', $data);
    }
}
