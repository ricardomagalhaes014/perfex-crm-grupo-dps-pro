<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_interacoes extends AdminController
{
    // Objectivos base
    const OBJ_SEMANA = 200;  // 200 interacções por semana (7 dias)
    const OBJ_MES    = 800;  // 800 interacções por mês (30 dias)

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

        // Calcular datas do período e objectivo proporcional
        switch ($periodo) {
            case 'today':
                $date_from = date('Y-m-d 00:00:00');
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Hoje';
                // Proporcional: 200 / 7 dias
                $objectivo = round(self::OBJ_SEMANA / 7, 1);
                break;
            case 'today_yesterday':
                $date_from = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Hoje e Ontem';
                // Proporcional: 200 / 7 * 2 dias
                $objectivo = round(self::OBJ_SEMANA / 7 * 2, 1);
                break;
            case 'last_15':
                $date_from = date('Y-m-d 00:00:00', strtotime('-14 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 15 dias';
                // Proporcional: 800 / 30 * 15 dias
                $objectivo = round(self::OBJ_MES / 30 * 15, 1);
                break;
            case 'last_30':
                $date_from = date('Y-m-d 00:00:00', strtotime('-29 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 30 dias';
                $objectivo = self::OBJ_MES;
                break;
            case 'last_3m':
                $date_from = date('Y-m-d 00:00:00', strtotime('-3 months'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 3 meses';
                $objectivo = self::OBJ_MES * 3;
                break;
            default: // last_7
                $periodo   = 'last_7';
                $date_from = date('Y-m-d 00:00:00', strtotime('-6 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 7 dias';
                $objectivo = self::OBJ_SEMANA;
        }

        // Filtro de status
        $status_clause_leads = '';
        if ($status_id > 0) {
            $status_clause_leads = 'AND l.status = ' . (int)$status_id;
        }

        // Obter todos os status de leads
        $statuses = $this->db->get($p . 'leads_status')->result_array();

        // PASSO 1: Obter todos os staff activos
        $staff_result = $this->db->query("SELECT staffid, CONCAT(firstname, ' ', lastname) AS nome FROM {$p}staff WHERE active = 1 ORDER BY firstname ASC");
        $staff_list   = $staff_result ? $staff_result->result_array() : [];

        // PASSO 2: Para cada staff, contar interacções no período
        $comerciais = [];
        foreach ($staff_list as $s) {
            $sid = (int)$s['staffid'];

            // Contar interacções: notas manuais na tabela lead_activity_log
            $count_sql = "
                SELECT COUNT(al.id) AS total
                FROM {$p}lead_activity_log al
                INNER JOIN {$p}leads l ON l.id = al.leadid
                WHERE l.assigned = {$sid}
                {$status_clause_leads}
                AND al.description LIKE '? Nota%'
                AND al.date >= '{$date_from}'
                AND al.date <= '{$date_to}'
            ";
            $count_res = $this->db->query($count_sql);
            $total     = 0;
            if ($count_res) {
                $row   = $count_res->row_array();
                $total = (int)($row['total'] ?? 0);
            }

            // Calcular percentagem do objectivo
            $pct = ($objectivo > 0) ? round(($total / $objectivo) * 100, 1) : 0;

            // Obter leads com interacção (apenas se total > 0)
            $leads = [];
            if ($total > 0) {
                $leads_sql = "
                    SELECT
                        l.id,
                        l.name,
                        l.email,
                        l.phonenumber,
                        ls.name AS status_name,
                        COUNT(al.id) AS num_interacoes
                    FROM {$p}leads l
                    LEFT JOIN {$p}leads_status ls ON ls.id = l.status
                    INNER JOIN {$p}lead_activity_log al ON al.leadid = l.id
                        AND al.description LIKE '? Nota%'
                        AND al.date >= '{$date_from}'
                        AND al.date <= '{$date_to}'
                    WHERE l.assigned = {$sid}
                    {$status_clause_leads}
                    GROUP BY l.id, l.name, l.email, l.phonenumber, ls.name
                    ORDER BY num_interacoes DESC
                ";
                $leads_res = $this->db->query($leads_sql);
                $leads     = $leads_res ? $leads_res->result_array() : [];
            }

            $comerciais[] = array(
                'staff_id'         => $sid,
                'nome'             => $s['nome'],
                'total_interacoes' => $total,
                'objectivo'        => $objectivo,
                'pct'              => $pct,
                'leads'            => $leads,
            );
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
        $data['objectivo']  = $objectivo;

        $this->load->view('interacoes', $data);
    }
}
