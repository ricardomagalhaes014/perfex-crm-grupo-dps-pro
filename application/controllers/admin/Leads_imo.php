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
}
