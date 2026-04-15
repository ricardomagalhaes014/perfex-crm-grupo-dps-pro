<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_interacoes extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $p = db_prefix();

        // Parâmetros GET
        $periodo   = $this->input->get('periodo') ?: 'last_7';
        $status_id = (int)$this->input->get('status_id');

        // Calcular datas do período
        switch ($periodo) {
            case 'today':
                $date_from = date('Y-m-d 00:00:00');
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Hoje';
                break;
            case 'today_yesterday':
                $date_from = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Hoje e Ontem';
                break;
            case 'last_7':
                $date_from = date('Y-m-d 00:00:00', strtotime('-6 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 7 dias';
                break;
            case 'last_15':
                $date_from = date('Y-m-d 00:00:00', strtotime('-14 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 15 dias';
                break;
            case 'last_30':
                $date_from = date('Y-m-d 00:00:00', strtotime('-29 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 30 dias';
                break;
            case 'last_3m':
                $date_from = date('Y-m-d 00:00:00', strtotime('-3 months'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 3 meses';
                break;
            default:
                $date_from = date('Y-m-d 00:00:00', strtotime('-6 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 7 dias';
        }

        // Filtro de status
        $status_clause = '';
        if ($status_id > 0) {
            $status_clause = "AND l.status = " . (int)$status_id;
        }

        // Obter todos os status de leads
        $statuses = $this->db->get($p . 'leads_status')->result_array();

        // Obter staff activo
        $staff_list = $this->db
            ->select('staffid, CONCAT(firstname, " ", lastname) as full_name')
            ->where('active', 1)
            ->order_by('firstname', 'asc')
            ->get($p . 'staff')
            ->result_array();

        // Para cada comercial: contar interacções e obter leads
        $comerciais = [];
        foreach ($staff_list as $staff) {
            $sid = (int)$staff['staffid'];

            // Leads atribuídas a este comercial
            $leads_sql = "SELECT l.id, l.name, l.email, l.phonenumber, l.status
                          FROM {$p}leads l
                          WHERE l.assigned = {$sid}
                          {$status_clause}";
            $leads_raw = $this->db->query($leads_sql)->result_array();

            if (empty($leads_raw)) {
                $comerciais[] = [
                    'staff_id'         => $sid,
                    'nome'             => $staff['full_name'],
                    'total_interacoes' => 0,
                    'leads'            => [],
                ];
                continue;
            }

            $lead_ids = array_column($leads_raw, 'id');
            $ids_str  = implode(',', array_map('intval', $lead_ids));

            // Contar interacções no período
            $int_sql = "SELECT leadid, COUNT(*) as cnt
                        FROM {$p}lead_activity_log
                        WHERE leadid IN ({$ids_str})
                          AND date_created BETWEEN '{$date_from}' AND '{$date_to}'
                        GROUP BY leadid";
            $int_rows = $this->db->query($int_sql)->result_array();

            $int_map = [];
            $total   = 0;
            foreach ($int_rows as $row) {
                $int_map[(int)$row['leadid']] = (int)$row['cnt'];
                $total += (int)$row['cnt'];
            }

            // Montar lista de leads com interacções
            $leads_com_int = [];
            foreach ($leads_raw as $lead) {
                $cnt = $int_map[(int)$lead['id']] ?? 0;
                if ($cnt > 0) {
                    $lead['interacoes'] = $cnt;
                    $leads_com_int[]    = $lead;
                }
            }

            $comerciais[] = [
                'staff_id'         => $sid,
                'nome'             => $staff['full_name'],
                'total_interacoes' => $total,
                'leads'            => $leads_com_int,
            ];
        }

        // Ordenar por total de interacções (desc)
        usort($comerciais, function($a, $b) {
            return $b['total_interacoes'] - $a['total_interacoes'];
        });

        $data['title']      = 'Interacções por Comercial';
        $data['comerciais'] = $comerciais;
        $data['statuses']   = $statuses;
        $data['periodo']    = $periodo;
        $data['status_id']  = $status_id;
        $data['label']      = $label;
        $data['date_from']  = $date_from;
        $data['date_to']    = $date_to;

        $this->load->view('interacoes', $data);
    }
}
