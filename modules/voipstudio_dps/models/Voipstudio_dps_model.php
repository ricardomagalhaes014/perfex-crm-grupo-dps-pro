<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Voipstudio_dps_model extends App_Model
{
    protected $api;

    public function __construct()
    {
        parent::__construct();
        require_once __DIR__ . '/../libraries/Voipstudio_api.php';
        $this->api = new Voipstudio_api();
    }

    public function api()
    {
        return $this->api;
    }

    /** Normaliza para E.164 (PT por omissão). */
    public function e164($number)
    {
        $d = preg_replace('/\D+/', '', (string) $number);
        if ($d === '') {
            return '';
        }
        if (strlen($d) === 9) {
            $d = '351' . $d;
        }
        if (substr($d, 0, 2) === '00') {
            $d = substr($d, 2);
        }
        return $d;
    }

    /** Faz a chamada e regista imediatamente na tabela local. */
    public function click_to_call($number, $rel_type = null, $rel_id = null, $staff_id = null)
    {
        $to = $this->e164($number);
        if (strlen($to) < 9) {
            return ['success' => false, 'message' => 'Número inválido: ' . $number];
        }
        $caller = get_option('voipstudio_dps_caller_id');
        $r      = $this->api->call($to, $caller ?: null);
        if ($r['code'] === 201) {
            $vid = isset($r['body']['data']['id']) ? (string) $r['body']['data']['id'] : null;
            $this->db->insert(db_prefix() . 'voipstudio_calls', [
                'voip_id'     => $vid ? 'live-' . $vid : null,
                'calldate'    => date('Y-m-d H:i:s'),
                'direction'   => 'outbound',
                'src'         => 'CRM',
                'dst'         => $to,
                'duration'    => 0,
                'disposition' => 'INICIADA',
                'rel_type'    => $rel_type ?: null,
                'rel_id'      => $rel_id ?: null,
                'staff_id'    => $staff_id,
            ]);
            // nota na lead
            if ($rel_type === 'lead' && $rel_id) {
                $this->db->insert(db_prefix() . 'notes', [
                    'rel_id'         => (int) $rel_id,
                    'rel_type'       => 'lead',
                    'description'    => '📞 Chamada VoIPstudio iniciada para ' . $to,
                    'addedfrom'      => $staff_id ?: 0,
                    'dateadded'      => date('Y-m-d H:i:s'),
                ]);
            }
            return ['success' => true];
        }
        $msg = isset($r['body']['message']) ? $r['body']['message'] : ('HTTP ' . $r['code'] . ($r['error'] ? ' / ' . $r['error'] : ''));
        return ['success' => false, 'message' => $msg];
    }

    /** Importa CDRs recentes e tenta associar a leads/clientes pelo nº. */
    public function sync_cdrs()
    {
        $r = $this->api->cdrs(1, 100);
        if ($r['code'] !== 200 || !isset($r['body']['data'])) {
            throw new Exception('CDR fetch falhou (HTTP ' . $r['code'] . ')');
        }
        $n = 0;
        foreach ($r['body']['data'] as $cdr) {
            $vid = isset($cdr['id']) ? (string) $cdr['id'] : null;
            if (!$vid) {
                continue;
            }
            $exists = $this->db->get_where(db_prefix() . 'voipstudio_calls', ['voip_id' => $vid])->row();
            if ($exists) {
                continue;
            }
            $src = isset($cdr['src']) ? preg_replace('/\D+/', '', $cdr['src']) : '';
            $dst = isset($cdr['dst']) ? preg_replace('/\D+/', '', $cdr['dst']) : '';
            $dir = (isset($cdr['type']) && stripos((string) $cdr['type'], 'in') !== false) ? 'inbound' : 'outbound';
            $ext = $dir === 'inbound' ? $src : $dst; // número externo
            $rel = $this->match_number($ext);
            $this->db->insert(db_prefix() . 'voipstudio_calls', [
                'voip_id'     => $vid,
                'calldate'    => isset($cdr['calldate']) ? date('Y-m-d H:i:s', is_numeric($cdr['calldate']) ? (int) $cdr['calldate'] : strtotime($cdr['calldate'])) : date('Y-m-d H:i:s'),
                'direction'   => $dir,
                'src'         => $src,
                'dst'         => $dst,
                'duration'    => isset($cdr['duration']) ? (int) $cdr['duration'] : 0,
                'disposition' => isset($cdr['disposition']) ? (string) $cdr['disposition'] : null,
                'rel_type'    => $rel ? $rel['type'] : null,
                'rel_id'      => $rel ? $rel['id'] : null,
                'raw'         => json_encode($cdr),
            ]);
            $n++;
        }
        return $n;
    }

    /** Associa um número externo a lead ou cliente (últimos 9 dígitos). */
    public function match_number($number)
    {
        $d = preg_replace('/\D+/', '', (string) $number);
        if (strlen($d) < 9) {
            return null;
        }
        $last9 = substr($d, -9);
        $lead  = $this->db->query(
            'SELECT id FROM ' . db_prefix() . "leads WHERE REPLACE(REPLACE(REPLACE(phonenumber,' ',''),'+',''),'-','') LIKE ? ORDER BY id DESC LIMIT 1",
            ['%' . $last9]
        )->row();
        if ($lead) {
            return ['type' => 'lead', 'id' => (int) $lead->id];
        }
        $contact = $this->db->query(
            'SELECT userid FROM ' . db_prefix() . "contacts WHERE REPLACE(REPLACE(REPLACE(phonenumber,' ',''),'+',''),'-','') LIKE ? LIMIT 1",
            ['%' . $last9]
        )->row();
        if ($contact) {
            return ['type' => 'customer', 'id' => (int) $contact->userid];
        }
        return null;
    }

    public function get_calls($rel_type = null, $rel_id = null, $limit = 200, $staff_id = null, $from = null, $to = null)
    {
        if ($rel_type && $rel_id) {
            $this->db->where('rel_type', $rel_type)->where('rel_id', (int) $rel_id);
        }
        if ($staff_id) {
            $this->db->where('staff_id', (int) $staff_id);
        }
        if ($from) {
            $this->db->where('calldate >=', $from . ' 00:00:00');
        }
        if ($to) {
            $this->db->where('calldate <=', $to . ' 23:59:59');
        }
        return $this->db->order_by('calldate', 'DESC')->limit((int) $limit)
            ->get(db_prefix() . 'voipstudio_calls')->result();
    }

    /** Estatísticas agregadas por comercial (staff). */
    public function get_stats($from = null, $to = null, $staff_id = null)
    {
        $where = '1=1';
        $binds = [];
        if ($from) { $where .= ' AND c.calldate >= ?'; $binds[] = $from . ' 00:00:00'; }
        if ($to)   { $where .= ' AND c.calldate <= ?'; $binds[] = $to . ' 23:59:59'; }
        if ($staff_id) { $where .= ' AND c.staff_id = ?'; $binds[] = (int) $staff_id; }
        $sql = 'SELECT c.staff_id,
                       CONCAT(IFNULL(s.firstname,""), " ", IFNULL(s.lastname,"")) AS staff_name,
                       COUNT(*) AS total,
                       SUM(CASE WHEN c.duration > 0 THEN 1 ELSE 0 END) AS atendidas,
                       SUM(CASE WHEN c.duration = 0 THEN 1 ELSE 0 END) AS nao_atendidas,
                       SUM(c.duration) AS dur_total,
                       ROUND(AVG(NULLIF(c.duration,0))) AS dur_media,
                       SUM(CASE WHEN c.direction = "inbound" THEN 1 ELSE 0 END) AS recebidas,
                       SUM(CASE WHEN c.direction = "outbound" THEN 1 ELSE 0 END) AS efetuadas
                FROM ' . db_prefix() . 'voipstudio_calls c
                LEFT JOIN ' . db_prefix() . 'staff s ON s.staffid = c.staff_id
                WHERE ' . $where . '
                GROUP BY c.staff_id, staff_name
                ORDER BY total DESC';
        return $this->db->query($sql, $binds)->result();
    }

    /** Totais globais para o período. */
    public function get_totals($from = null, $to = null, $staff_id = null)
    {
        $where = '1=1';
        $binds = [];
        if ($from) { $where .= ' AND calldate >= ?'; $binds[] = $from . ' 00:00:00'; }
        if ($to)   { $where .= ' AND calldate <= ?'; $binds[] = $to . ' 23:59:59'; }
        if ($staff_id) { $where .= ' AND staff_id = ?'; $binds[] = (int) $staff_id; }
        $sql = 'SELECT COUNT(*) AS total,
                       SUM(CASE WHEN duration > 0 THEN 1 ELSE 0 END) AS atendidas,
                       SUM(duration) AS dur_total,
                       ROUND(AVG(NULLIF(duration,0))) AS dur_media
                FROM ' . db_prefix() . 'voipstudio_calls WHERE ' . $where;
        return $this->db->query($sql, $binds)->row();
    }
}
