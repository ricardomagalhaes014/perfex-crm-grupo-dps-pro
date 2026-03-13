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
     * Retorna o relatório em JSON para o popup (usado pelo fetch no JavaScript)
     * GET params:
     *  - group: imo | expansao_imo | media | dental
     *  - from:  YYYY-MM-DD (opcional)
     *  - to:    YYYY-MM-DD (opcional)
     */
    public function source_report()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }

        $group = $this->input->get('group') ?: 'imo';

        $from  = $this->input->get('from');
        $to    = $this->input->get('to');

        // Deixa null se vier vazio
        $from = $from ? $from : null;
        $to   = $to ? $to : null;

        $data = $this->leads_imo_model->report_by_source_group($group, $from, $to);

        echo json_encode($data);
        die;
    }

    /**
     * Exporta o mesmo relatório para CSV (abre no Excel)
     * Usa os mesmos parâmetros de filtro do source_report
     */
    public function export_excel()
    {
        if (!is_admin()) {
            access_denied('Leads IMO Export');
        }

        $group = $this->input->get('group') ?: 'imo';
        $from  = $this->input->get('from');
        $to    = $this->input->get('to');

        $from = $from ? $from : null;
        $to   = $to ? $to : null;

        $report = $this->leads_imo_model->report_by_source_group($group, $from, $to);

        // Monta nome do arquivo
        $filenameParts = ['leads', $group];
        if ($from) {
            $filenameParts[] = 'de_' . $from;
        }
        if ($to) {
            $filenameParts[] = 'ate_' . $to;
        }
        $filenameParts[] = date('Ymd_His');
        $filename = implode('_', $filenameParts) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Em PT normalmente o Excel prefere ; como separador
        $separator = ';';

        $out = fopen('php://output', 'w');

        // Cabeçalho
        $header = ['Comercial'];
        foreach ($report['statuses'] as $s) {
            $header[] = $s['name'];
        }
        $header[] = 'Total';

        fputcsv($out, $header, $separator);

        // Linhas
        foreach ($report['rows'] as $row) {
            $line   = [$row['staff_name']];
            foreach ($report['statuses'] as $s) {
                $statusId = $s['id'];
                $line[]   = isset($row['counts'][$statusId]) ? $row['counts'][$statusId] : 0;
            }
            $line[] = $row['total'];

            fputcsv($out, $line, $separator);
        }

        fclose($out);
        exit;
    }
}
