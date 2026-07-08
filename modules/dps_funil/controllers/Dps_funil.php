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
            ['key' => 'fecho',         'title' => 'Fecho',                        'icon' => 'fa fa-check-circle', 'statuses' => [13, 10]],
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

        // Contagem por estado (opcionalmente filtrada por comercial).
        $counts = [];
        $sql    = 'SELECT status, COUNT(id) AS c FROM ' . db_prefix() . 'leads';
        if ($comercial > 0) {
            $sql .= ' WHERE assigned = ' . (int) $comercial;
        }
        $sql .= ' GROUP BY status';
        foreach ($this->db->query($sql)->result_array() as $r) {
            $counts[(int) $r['status']] = (int) $r['c'];
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

        // Query base (com filtro de comercial, se aplicável).
        $where = ['status' => $status_id];
        if ($comercial > 0) {
            $where['assigned'] = $comercial;
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
        $this->load->view('estado', $data);
    }
}
