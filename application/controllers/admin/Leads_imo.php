<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Leads_imo extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('leads_imo_model');
    }

    /**
     * Retorna o relatório em JSON para o popup (comerciais × statuses)
     * GET params:
     *  - group: expansao_imo | imo | imo_dubai | media | dental
     *  - from:  YYYY-MM-DD (opcional)
     *  - to:    YYYY-MM-DD (opcional)
     */
    public function source_report()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $group = $this->input->get('group') ?: 'expansao_imo';
        $from  = $this->input->get('from') ?: null;
        $to    = $this->input->get('to')   ?: null;

        $data = $this->leads_imo_model->report_by_source_group($group, $from, $to);

        echo json_encode($data);
        die;
    }

    /**
     * Distribuição de leads por comercial para um status específico.
     * GET params:
     *  - status_id: int
     *  - group:     expansao_imo | imo | imo_dubai | media | dental
     *  - from:      YYYY-MM-DD (opcional)
     *  - to:        YYYY-MM-DD (opcional)
     */
    public function status_distribution()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $statusId = (int) $this->input->get('status_id');
        $group    = $this->input->get('group') ?: 'expansao_imo';
        $from     = $this->input->get('from')  ?: null;
        $to       = $this->input->get('to')    ?: null;

        if ($statusId <= 0) {
            echo json_encode(['status_name' => '', 'rows' => []]);
            die;
        }

        $data = $this->leads_imo_model->get_status_distribution($statusId, $group, $from, $to);

        echo json_encode($data);
        die;
    }

    /**
     * Exporta o relatório comerciais × statuses para CSV
     */
    public function export_excel()
    {
        if (!is_admin()) {
            access_denied('Leads IMO Export');
        }

        $group = $this->input->get('group') ?: 'expansao_imo';
        $from  = $this->input->get('from')  ?: null;
        $to    = $this->input->get('to')    ?: null;

        $report = $this->leads_imo_model->report_by_source_group($group, $from, $to);

        $filenameParts = ['leads', $group];
        if ($from) { $filenameParts[] = 'de_' . $from; }
        if ($to)   { $filenameParts[] = 'ate_' . $to; }
        $filenameParts[] = date('Ymd_His');
        $filename = implode('_', $filenameParts) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $separator = ';';
        $out = fopen('php://output', 'w');

        $header = ['Comercial'];
        foreach ($report['statuses'] as $s) {
            $header[] = $s['name'];
        }
        $header[] = 'Total';
        fputcsv($out, $header, $separator);

        foreach ($report['rows'] as $row) {
            $line = [$row['staff_name']];
            foreach ($report['statuses'] as $s) {
                $line[] = $row['counts'][$s['id']] ?? 0;
            }
            $line[] = $row['total'];
            fputcsv($out, $line, $separator);
        }

        fclose($out);
        exit;
    }

    /**
     * Consolidação de fontes — corre uma vez pelo browser (só admin).
     * URL: crm.grupo-dps.com/admin/leads_imo/consolidar_fontes
     */
    public function consolidar_fontes()
    {
        if (!is_admin()) {
            access_denied('Leads IMO');
        }

        $prefix = db_prefix();
        $log    = [];

        // 1. Fundir todas as variantes de IMO Portugal + DPS Portugal
        $imoAll = $this->db->query("
            SELECT id, name FROM {$prefix}leads_sources
            WHERE LOWER(TRIM(name)) IN (
                'imo portugal','expansao imo','expansão imo','dps portugal','dps-portugal'
            )
            ORDER BY id ASC
        ")->result_array();

        if (count($imoAll) >= 2) {
            $keepId    = (int) $imoAll[0]['id'];
            $removeIds = array_map('intval', array_slice(array_column($imoAll, 'id'), 1));
            $this->db->where('id', $keepId)->update($prefix . 'leads_sources', ['name' => 'IMO Portugal']);
            $affected = 0;
            foreach ($removeIds as $rid) {
                $this->db->where('source', $rid)->update($prefix . 'leads', ['source' => $keepId]);
                $affected += $this->db->affected_rows();
            }
            $this->db->where_in('id', $removeIds)->delete($prefix . 'leads_sources');
            $log[] = "✅ IMO Portugal consolidado (manteve id=$keepId, removeu " . implode(',', $removeIds) . "). Leads re-apontadas: $affected.";
        } elseif (count($imoAll) === 1) {
            $keepId = (int) $imoAll[0]['id'];
            $this->db->where('id', $keepId)->update($prefix . 'leads_sources', ['name' => 'IMO Portugal']);
            $log[] = "✅ IMO Portugal — já único (id=$keepId), nome normalizado.";
        } else {
            $log[] = "ℹ️ Nenhuma fonte com variante de 'IMO Portugal' encontrada.";
        }

        // 2. Consolidar quaisquer outros nomes duplicados exactos
        $dups = $this->db->query("
            SELECT LOWER(TRIM(name)) as norm_name, MIN(id) as keep_id, GROUP_CONCAT(id ORDER BY id) as all_ids
            FROM {$prefix}leads_sources
            GROUP BY LOWER(TRIM(name))
            HAVING COUNT(*) > 1
        ")->result_array();

        foreach ($dups as $dup) {
            $keepId    = (int) $dup['keep_id'];
            $allIds    = array_map('intval', explode(',', $dup['all_ids']));
            $removeIds = array_filter($allIds, fn($id) => $id !== $keepId);
            if (empty($removeIds)) continue;
            $this->db->where_in('source', $removeIds)->update($prefix . 'leads', ['source' => $keepId]);
            $this->db->where_in('id', $removeIds)->delete($prefix . 'leads_sources');
            $log[] = "✅ Duplicado '{$dup['norm_name']}' consolidado em id=$keepId.";
        }

        if (empty($dups)) {
            $log[] = "ℹ️ Sem outros duplicados encontrados.";
        }

        // 3. Apagar Media e Dentária
        $toDelete = $this->db->query("
            SELECT id, name FROM {$prefix}leads_sources
            WHERE LOWER(TRIM(name)) IN ('media','média','dentaria','dentária')
        ")->result_array();

        if (!empty($toDelete)) {
            $deleteIds = array_map('intval', array_column($toDelete, 'id'));
            $this->db->where_in('source', $deleteIds)->update($prefix . 'leads', ['source' => null]);
            $this->db->where_in('id', $deleteIds)->delete($prefix . 'leads_sources');
            $names = implode(', ', array_column($toDelete, 'name'));
            $log[] = "🗑️ Fontes apagadas: $names.";
        } else {
            $log[] = "ℹ️ Media/Dentária já não existem.";
        }

        // Resultado
        echo '<pre style="font-family:monospace;font-size:15px;padding:20px;">';
        echo "<b>Consolidação de fontes de lead</b>\n\n";
        echo implode("\n", $log);
        echo "\n\n<b>Concluído.</b> Pode fechar esta página.";
        echo '</pre>';
        exit;
    }
}
