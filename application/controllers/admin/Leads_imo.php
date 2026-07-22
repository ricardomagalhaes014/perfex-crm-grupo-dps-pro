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
     *  - group: expansao_imo | imo | imo_dubai | bsx | contacto_pessoal
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
     *  - group:     expansao_imo | imo | imo_dubai | bsx | contacto_pessoal
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

        // Neutraliza injecção de fórmulas: um valor que comece por = + - @ é
        // interpretado como fórmula pelo Excel/Sheets ao abrir o CSV.
        $csvSafe = function ($value) {
            $value = (string) $value;
            if ($value !== '' && strpbrk($value[0], '=+-@') !== false) {
                return "'" . $value;
            }
            return $value;
        };

        $header = ['Comercial'];
        foreach ($report['statuses'] as $s) {
            $header[] = $csvSafe($s['name']);
        }
        $header[] = 'Total';
        fputcsv($out, $header, $separator);

        foreach ($report['rows'] as $row) {
            $line = [$csvSafe($row['staff_name'])];
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
     * Normaliza um nome de fonte de forma robusta a variações "invisíveis":
     * espaços não-separáveis (NBSP), espaços largos unicode, zero-width chars,
     * espaços duplicados, maiúsculas/minúsculas.
     */
    private function normalizeSourceName(string $name): string
    {
        // Substitui vários tipos de espaço unicode "escondidos" por espaço normal
        $s = preg_replace('/[\x{00A0}\x{1680}\x{2000}-\x{200D}\x{202F}\x{205F}\x{3000}\x{FEFF}]/u', ' ', $name);
        // Colapsa espaços múltiplos
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);
        return mb_strtolower($s, 'UTF-8');
    }

    /**
     * Consolidação de fontes — corre uma vez pelo browser (só admin).
     * URL: crm.grupo-dps.com/admin/leads_imo/consolidar_fontes
     *
     * GET mostra uma página de confirmação; a acção só executa em POST
     * (evita que um link/imagem partilhado dispare a consolidação sem querer —
     * um pedido GET nunca consegue accionar um formulário POST sozinho).
     *
     * Usa normalização robusta em PHP (não confia em LOWER(TRIM()) do MySQL,
     * que não remove espaços não-separáveis/unicode escondidos e por isso
     * pode falhar a detectar "duplicados" visualmente idênticos).
     */
    public function consolidar_fontes()
    {
        if (!is_admin()) {
            access_denied('Leads IMO');
        }

        if ($this->input->method() !== 'post') {
            echo '<div style="font-family:sans-serif;padding:30px;max-width:500px;">';
            echo '<h3>Consolidação de fontes de lead</h3>';
            echo '<p>Isto vai fundir fontes duplicadas (IMO Portugal, DPS Portugal) e apagar Media/Dentária. Confirmar?</p>';
            echo form_open(current_url());
            echo '<button type="submit" class="btn btn-primary" style="padding:8px 20px;">Executar consolidação</button>';
            echo form_close();
            echo '</div>';
            exit;
        }

        $prefix = db_prefix();
        $log    = [];

        $this->db->trans_start();

        // Estado ANTES — para diagnóstico, mostra bytes escondidos se existirem
        $before = $this->db->select('id, name')->order_by('id', 'asc')->get($prefix . 'leads_sources')->result_array();

        // Nomes-alvo a fundir todos em "IMO Portugal"
        $imoAliases = ['imo portugal', 'expansao imo', 'expansão imo', 'dps portugal', 'dps-portugal'];

        $imoRows = array_values(array_filter($before, function ($r) use ($imoAliases) {
            return in_array($this->normalizeSourceName($r['name']), $imoAliases, true);
        }));

        if (count($imoRows) >= 2) {
            usort($imoRows, fn($a, $b) => $a['id'] <=> $b['id']);
            $keepId    = (int) $imoRows[0]['id'];
            $removeIds = array_map(fn($r) => (int) $r['id'], array_slice($imoRows, 1));

            $this->db->where('id', $keepId)->update($prefix . 'leads_sources', ['name' => 'IMO Portugal']);

            $affected = 0;
            foreach ($removeIds as $rid) {
                $this->db->where('source', $rid)->update($prefix . 'leads', ['source' => $keepId]);
                $affected += $this->db->affected_rows();
            }
            $this->db->where_in('id', $removeIds)->delete($prefix . 'leads_sources');

            $removedNames = implode(', ', array_map(
                fn($r) => '"' . htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8') . "\" (id={$r['id']})",
                array_slice($imoRows, 1)
            ));
            $log[] = "✅ IMO Portugal consolidado — manteve id=$keepId, fundiu: $removedNames. Leads re-apontadas: $affected.";
        } elseif (count($imoRows) === 1) {
            $keepId = (int) $imoRows[0]['id'];
            $this->db->where('id', $keepId)->update($prefix . 'leads_sources', ['name' => 'IMO Portugal']);
            $log[] = "✅ IMO Portugal — já único (id=$keepId), nome normalizado.";
        } else {
            $log[] = "ℹ️ Nenhuma fonte com variante de 'IMO Portugal' encontrada.";
        }

        // Consolidar quaisquer outros duplicados exactos (por nome normalizado), à parte do bloco IMO Portugal já tratado
        $remaining = $this->db->select('id, name')->get($prefix . 'leads_sources')->result_array();
        $groups    = [];
        foreach ($remaining as $r) {
            $key = $this->normalizeSourceName($r['name']);
            $groups[$key][] = $r;
        }

        $otherDupsFound = false;
        foreach ($groups as $key => $rows) {
            if (count($rows) < 2) continue;
            $otherDupsFound = true;
            usort($rows, fn($a, $b) => $a['id'] <=> $b['id']);
            $keepId    = (int) $rows[0]['id'];
            $removeIds = array_map(fn($r) => (int) $r['id'], array_slice($rows, 1));

            $this->db->where_in('source', $removeIds)->update($prefix . 'leads', ['source' => $keepId]);
            $this->db->where_in('id', $removeIds)->delete($prefix . 'leads_sources');
            $safeName = htmlspecialchars($rows[0]['name'], ENT_QUOTES, 'UTF-8');
            $log[] = "✅ Duplicado '$safeName' consolidado em id=$keepId (removidos: " . implode(',', $removeIds) . ").";
        }
        if (!$otherDupsFound) {
            $log[] = "ℹ️ Sem outros duplicados encontrados.";
        }

        // Apagar Media e Dentária (todas as variantes)
        $mediaAliases = ['media', 'média', 'dentaria', 'dentária'];
        $stillLeft    = $this->db->select('id, name')->get($prefix . 'leads_sources')->result_array();
        $toDelete     = array_values(array_filter($stillLeft, function ($r) use ($mediaAliases) {
            return in_array($this->normalizeSourceName($r['name']), $mediaAliases, true);
        }));

        if (!empty($toDelete)) {
            $deleteIds = array_map(fn($r) => (int) $r['id'], $toDelete);
            $this->db->where_in('source', $deleteIds)->update($prefix . 'leads', ['source' => null]);
            $this->db->where_in('id', $deleteIds)->delete($prefix . 'leads_sources');
            $names = implode(', ', array_map(fn($r) => htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'), $toDelete));
            $log[] = "🗑️ Fontes apagadas: $names.";
        } else {
            $log[] = "ℹ️ Media/Dentária já não existem.";
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            echo '<pre style="font-family:monospace;font-size:15px;padding:20px;color:red;">';
            echo "<b>Erro — a transacção falhou, nada foi alterado.</b>\n\n";
            echo implode("\n", $log);
            echo '</pre>';
            exit;
        }

        // Estado final — lista todas as fontes com o respectivo comprimento em bytes
        // (ajuda a detectar no futuro nomes visualmente iguais mas com caracteres escondidos)
        $after = $this->db->select('id, name')->order_by('id', 'asc')->get($prefix . 'leads_sources')->result_array();

        echo '<pre style="font-family:monospace;font-size:14px;padding:20px;">';
        echo "<b>Consolidação de fontes de lead</b>\n\n";
        echo implode("\n", $log);
        echo "\n\n<b>Fontes finais (id | nome | bytes):</b>\n";
        foreach ($after as $r) {
            $safeName = htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8');
            echo str_pad($r['id'], 4) . ' | ' . str_pad($safeName, 25) . ' | ' . strlen($r['name']) . "\n";
        }
        echo "\n<b>Concluído.</b> Pode fechar esta página.";
        echo '</pre>';
        exit;
    }
}
