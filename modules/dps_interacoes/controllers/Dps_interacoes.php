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

        // Filtro de status (cláusula SQL segura)
        $status_clause = '';
        if ($status_id > 0) {
            $status_clause = 'AND l.status = ' . (int)$status_id;
        }

        // Obter todos os status de leads
        $statuses = $this->db->get($p . 'leads_status')->result_array();

        // Query única: agrupa interacções por comercial
        // O campo de data na tabela lead_activity_log é 'date'
        $sql = "
            SELECT
                s.staffid,
                CONCAT(s.firstname, ' ', s.lastname) AS nome,
                COUNT(al.id) AS total_interacoes
            FROM {$p}staff s
            LEFT JOIN {$p}leads l ON l.assigned = s.staffid {$status_clause}
            LEFT JOIN {$p}lead_activity_log al
                ON al.leadid = l.id
                AND al.description LIKE '? Nota%'
                AND al.date BETWEEN '{$date_from}' AND '{$date_to}'
            WHERE s.active = 1
            GROUP BY s.staffid, s.firstname, s.lastname
            ORDER BY total_interacoes DESC, s.firstname ASC
        ";

        $result = $this->db->query($sql);
        $comerciais_raw = $result ? $result->result_array() : [];

        // Para cada comercial com interacções, obter as leads com interacção
        $comerciais = [];
        foreach ($comerciais_raw as $c) {
            $sid = (int)$c['staffid'];
            $leads_com_int = [];

            if ((int)$c['total_interacoes'] > 0) {
                $leads_sql = "
                    SELECT
                        l.id, l.name, l.email, l.phonenumber,
                        ls.name AS status_name,
                        COUNT(al.id) AS interacoes
                    FROM {$p}leads l
                    LEFT JOIN {$p}leads_status ls ON ls.id = l.status
                    LEFT JOIN {$p}lead_activity_log al
                        ON al.leadid = l.id
                        AND al.description LIKE '? Nota%'
                        AND al.date BETWEEN '{$date_from}' AND '{$date_to}'
                    WHERE l.assigned = {$sid}
                    {$status_clause}
                    GROUP BY l.id, l.name, l.email, l.phonenumber, ls.name
                    HAVING interacoes > 0
                    ORDER BY interacoes DESC
                ";
                $lr = $this->db->query($leads_sql);
                $leads_com_int = $lr ? $lr->result_array() : [];
            }

            $comerciais[] = array(
                'staff_id'         => $sid,
                'nome'             => $c['nome'],
                'total_interacoes' => (int)$c['total_interacoes'],
                'leads'            => $leads_com_int,
            );
        }

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
