<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_propostas extends AdminController
{
    private function lead_or_die($lead_id)
    {
        $lead = $this->db->select('id, name, phonenumber, status')
            ->where('id', (int) $lead_id)->get(db_prefix() . 'leads')->row();
        return $lead;
    }

    private function status_name($id)
    {
        $r = $this->db->select('name')->where('id', (int) $id)->get(db_prefix() . 'leads_status')->row();
        return $r ? $r->name : null;
    }

    /**
     * Envia informação (dossier + unidades disponíveis) pela WhatsApp do comercial.
     */
    public function enviar_info()
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }

        $lead_id = (int) $this->input->post('lead_id');
        $key     = $this->input->post('empreendimento');
        $emps    = dps_propostas_empreendimentos();

        if (! $lead_id || ! isset($emps[$key])) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $emp      = $emps[$key];
        $staff_id = get_staff_user_id();

        if (! dps_propostas_staff_connected($staff_id)) {
            echo json_encode(['success' => false, 'message' => 'O teu WhatsApp não está ligado (Definições → WhatsApp).']);
            return;
        }

        $lead = $this->lead_or_die($lead_id);
        if (! $lead) {
            echo json_encode(['success' => false, 'message' => 'Lead não encontrada.']);
            return;
        }
        $number = preg_replace('/[^0-9]/', '', (string) $lead->phonenumber);
        if ($number === '') {
            echo json_encode(['success' => false, 'message' => 'A lead não tem telefone.']);
            return;
        }

        $disp = dps_propostas_disponibilidade($key);

        $primeiro = trim(explode(' ', (string) $lead->name)[0]);
        $msg  = 'Olá' . ($primeiro ? " {$primeiro}" : '') . "! 👋\n\n";
        $msg .= '🏢 *' . $emp['nome'] . "*\n\n";
        if (! empty($emp['dossier'])) {
            $msg .= "📄 Dossier comercial:\n" . $emp['dossier'] . "\n\n";
        }
        $msg .= "🌐 Mais informação:\n" . $emp['site'] . "\n\n";
        $msg .= '🏠 *' . $disp['count'] . ' unidade' . ($disp['count'] === 1 ? '' : 's') . ' disponíve' . ($disp['count'] === 1 ? 'l' : 'is') . '*';
        if (! empty($disp['por_tipologia'])) {
            $msg .= ":\n";
            foreach ($disp['por_tipologia'] as $t) {
                $linha = '• ' . $t['tipologia'] . ' — ' . $t['n'];
                if (! empty($t['min'])) {
                    $linha .= ' (desde ' . number_format($t['min'], 0, ',', '.') . ' €)';
                }
                $msg .= $linha . "\n";
            }
        }

        $r  = dps_propostas_send_text($staff_id, $number, $msg);
        $ok = $r['ok'];

        // Gerar e enviar a tabela de DISPONÍVEIS ao vivo (PDF), extraída do simulador.
        $pdf_ok = false;
        if (! empty($disp['unidades'])) {
            try {
                $b64 = dps_propostas_gerar_pdf_disponiveis($emp['nome'], $disp['unidades']);
                $rp  = dps_propostas_send_document_b64(
                    $staff_id,
                    $number,
                    $b64,
                    'Unidades Disponiveis - ' . $emp['nome'] . '.pdf',
                    'Unidades disponíveis — ' . $emp['nome']
                );
                $pdf_ok = $rp['ok'];
            } catch (\Throwable $e) {
                $pdf_ok = false;
            }
        }

        $this->db->insert(db_prefix() . 'dps_propostas', [
            'lead_id'          => $lead_id,
            'staff_id'         => $staff_id,
            'tipo'             => 'info',
            'empreendimento'   => $emp['nome'],
            'unidade'          => null,
            'lead_status_id'   => (int) $lead->status,
            'lead_status_nome' => $this->status_name($lead->status),
            'ficheiro'         => $emp['dossier'],
            'detalhe'          => $disp['count'] . ' unidades disponíveis' . ($pdf_ok ? ' (tabela PDF enviada)' : ''),
            'wa_ok'            => $ok ? 1 : 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? ('Informação enviada para ' . $lead->name . ' — ' . $disp['count'] . ' unidades disponíveis' . ($pdf_ok ? ' + tabela PDF' : '') . '.')
                : dps_propostas_erro_wa($r, $number),
        ]);
    }

    /**
     * Regista + envia uma proposta (PDF). Usado pelo callback do simulador (Fase B)
     * ou com um URL de PDF já existente.
     */
    public function registar_proposta()
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }

        $lead_id   = (int) $this->input->post('lead_id');
        $emp_nome  = $this->input->post('empreendimento');
        $unidade   = $this->input->post('unidade');
        $file_url  = $this->input->post('file_url');
        $file_name = $this->input->post('file_name') ?: 'Proposta.pdf';

        if (! $lead_id || ! $unidade) {
            echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
            return;
        }

        $staff_id = get_staff_user_id();
        if (! dps_propostas_staff_connected($staff_id)) {
            echo json_encode(['success' => false, 'message' => 'O teu WhatsApp não está ligado.']);
            return;
        }

        $lead = $this->lead_or_die($lead_id);
        if (! $lead) {
            echo json_encode(['success' => false, 'message' => 'Lead não encontrada.']);
            return;
        }
        $number = preg_replace('/[^0-9]/', '', (string) $lead->phonenumber);

        $ok = false;
        if ($file_url && $number !== '') {
            $primeiro = trim(explode(' ', (string) $lead->name)[0]);
            $caption  = 'Proposta' . ($emp_nome ? ' — ' . $emp_nome : '') . ' — Unidade ' . $unidade;
            $r  = dps_propostas_send_document($staff_id, $number, $file_url, $file_name, $caption);
            $ok = $r['ok'];
        }

        $this->db->insert(db_prefix() . 'dps_propostas', [
            'lead_id'          => $lead_id,
            'staff_id'         => $staff_id,
            'tipo'             => 'proposta',
            'empreendimento'   => $emp_nome,
            'unidade'          => $unidade,
            'lead_status_id'   => (int) $lead->status,
            'lead_status_nome' => $this->status_name($lead->status),
            'ficheiro'         => $file_url,
            'detalhe'          => null,
            'wa_ok'            => $ok ? 1 : 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['success' => true, 'message' => $ok ? 'Proposta enviada e registada.' : 'Proposta registada (envio WhatsApp não confirmado).']);
    }

    /**
     * Lista global de propostas enviadas, com filtro por comercial.
     * Admin (ou quem vê todas as leads) vê todas; os restantes só as suas.
     */
    public function todas()
    {
        if (! is_staff_member()) {
            access_denied('propostas');
        }

        $can_view_all = is_admin() || staff_can('view', 'leads');
        $comercial    = (int) $this->input->get('comercial');
        if (! $can_view_all) {
            $comercial = get_staff_user_id();
        }

        // Comerciais com propostas (para o dropdown).
        $comerciais = [];
        if ($can_view_all) {
            $comerciais = $this->db->query(
                'SELECT s.staffid, s.firstname, s.lastname, COUNT(p.id) AS c
                 FROM ' . db_prefix() . 'dps_propostas p
                 JOIN ' . db_prefix() . 'staff s ON s.staffid = p.staff_id
                 WHERE p.tipo = "proposta"
                 GROUP BY s.staffid ORDER BY s.firstname, s.lastname'
            )->result_array();
        }

        // Propostas (com estado ATUAL da lead).
        $this->db->select('p.*, l.name AS lead_nome, ls.name AS estado_atual');
        $this->db->from(db_prefix() . 'dps_propostas p');
        $this->db->join(db_prefix() . 'leads l', 'l.id = p.lead_id', 'left');
        $this->db->join(db_prefix() . 'leads_status ls', 'ls.id = l.status', 'left');
        $this->db->where('p.tipo', 'proposta');
        if ($comercial > 0) {
            $this->db->where('p.staff_id', $comercial);
        }
        $this->db->order_by('p.id', 'DESC');
        $this->db->limit(1000);
        $propostas = $this->db->get()->result();

        $data['title']        = 'Propostas Enviadas';
        $data['propostas']    = $propostas;
        $data['can_view_all'] = $can_view_all;
        $data['comercial']    = $comercial;
        $data['comerciais']   = $comerciais;
        $this->load->view('todas', $data);
    }

    /**
     * Painel de proposta/informação de uma lead (carregado num modal a partir
     * da lista de leads, sem abrir a lead).
     */
    public function painel($lead_id = '')
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }
        $lead = $this->db->select('id, name, phonenumber, status')
            ->where('id', (int) $lead_id)->get(db_prefix() . 'leads')->row();
        if (! $lead) {
            echo '<p class="text-danger">Lead não encontrada.</p>';
            return;
        }
        $staff_id = get_staff_user_id();
        $rows = $this->db->where('lead_id', (int) $lead->id)->order_by('id', 'DESC')
            ->get(db_prefix() . 'dps_propostas')->result();

        $this->load->view('painel', [
            'lead'     => $lead,
            'emps'     => dps_propostas_empreendimentos(),
            'rows'     => $rows,
            'staff_id' => $staff_id,
            'token'    => dps_propostas_proposta_token($lead->id, $staff_id),
        ]);
    }

    /**
     * Visão geral (admin): KPIs + gráficos do pipeline, volume e eficácia.
     */
    public function visao()
    {
        if (! is_staff_member()) {
            access_denied('visao');
        }
        $p = db_prefix();

        $can_view_all = is_admin() || staff_can('view', 'leads');
        $comercial    = (int) $this->input->get('comercial');
        if (! $can_view_all) {
            $comercial = get_staff_user_id();
        }
        $wLead = $comercial > 0 ? (' AND ' . $p . 'leads.assigned = ' . $comercial) : '';

        $scalar = function ($sql) {
            $r = $this->db->query($sql)->row();
            return $r ? (int) $r->c : 0;
        };

        // KPIs
        $total      = $scalar("SELECT COUNT(*) c FROM {$p}leads WHERE 1=1{$wLead}");
        $concret    = $scalar("SELECT COUNT(*) c FROM {$p}leads WHERE status=13{$wLead}");
        $perd       = $scalar("SELECT COUNT(*) c FROM {$p}leads WHERE status=5{$wLead}");
        $novas_hoje = $scalar("SELECT COUNT(*) c FROM {$p}leads WHERE DATE(dateadded)=CURDATE(){$wLead}");
        $novas_7    = $scalar("SELECT COUNT(*) c FROM {$p}leads WHERE dateadded >= DATE_SUB(CURDATE(), INTERVAL 7 DAY){$wLead}");
        $interacoes = $scalar("SELECT COUNT(*) c FROM {$p}lead_activity_log a JOIN {$p}leads ON {$p}leads.id=a.leadid WHERE 1=1{$wLead}");
        $wProp      = $comercial > 0 ? (' AND staff_id=' . $comercial) : '';
        $propostas  = $scalar("SELECT COUNT(*) c FROM {$p}dps_propostas WHERE tipo='proposta'{$wProp}");
        $taxa       = $total > 0 ? round($concret / $total * 100, 1) : 0;

        // Por estado
        $por_estado = $this->db->query(
            "SELECT s.name, s.color, COUNT(l.id) AS n
             FROM {$p}leads_status s
             LEFT JOIN {$p}leads l ON l.status=s.id" . ($comercial > 0 ? ' AND l.assigned=' . $comercial : '') . "
             GROUP BY s.id ORDER BY s.statusorder, s.id"
        )->result_array();

        // Novas por dia (últimos 30 dias) — série contígua
        $rowsDay = $this->db->query(
            "SELECT DATE(dateadded) AS dia, COUNT(*) AS n FROM {$p}leads
             WHERE dateadded >= DATE_SUB(CURDATE(), INTERVAL 29 DAY){$wLead}
             GROUP BY DATE(dateadded)"
        )->result_array();
        $mapDay = [];
        foreach ($rowsDay as $r) { $mapDay[$r['dia']] = (int) $r['n']; }
        $por_dia = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $por_dia[] = ['dia' => $d, 'n' => isset($mapDay[$d]) ? $mapDay[$d] : 0];
        }

        // Por comercial (só admins)
        $por_comercial = [];
        if ($can_view_all) {
            $por_comercial = $this->db->query(
                "SELECT s.staffid, CONCAT(s.firstname,' ',s.lastname) AS nome,
                        COUNT(l.id) AS total,
                        SUM(CASE WHEN l.status=13 THEN 1 ELSE 0 END) AS concret,
                        SUM(CASE WHEN l.status=5 THEN 1 ELSE 0 END) AS perd
                 FROM {$p}staff s
                 JOIN {$p}leads l ON l.assigned=s.staffid
                 GROUP BY s.staffid HAVING total > 0 ORDER BY total DESC"
            )->result_array();
        }

        $comerciais = [];
        if ($can_view_all) {
            $comerciais = $this->db->query(
                "SELECT s.staffid, s.firstname, s.lastname, COUNT(l.id) AS c
                 FROM {$p}staff s JOIN {$p}leads l ON l.assigned=s.staffid
                 GROUP BY s.staffid ORDER BY s.firstname, s.lastname"
            )->result_array();
        }

        $this->load->view('visao', [
            'title'         => 'Visão Geral',
            'can_view_all'  => $can_view_all,
            'comercial'     => $comercial,
            'comerciais'    => $comerciais,
            'kpi'           => compact('total', 'concret', 'perd', 'novas_hoje', 'novas_7', 'interacoes', 'propostas', 'taxa'),
            'por_estado'    => $por_estado,
            'por_dia'       => $por_dia,
            'por_comercial' => $por_comercial,
        ]);
    }

    /**
     * Marca uma proposta como ACEITE ou RECUSADA e move o estado da lead:
     *  - aceite  -> Concretizado (13) + guarda o valor (para comissões)
     *  - recusado-> Para outras oportunidades (3)
     */
    public function resultado_proposta()
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }
        $id        = (int) $this->input->post('proposta_id');
        $outcome   = $this->input->post('outcome');
        $valor_raw = $this->input->post('valor');

        $prop = $this->db->where('id', $id)->where('tipo', 'proposta')->get(db_prefix() . 'dps_propostas')->row();
        if (! $prop) {
            echo json_encode(['success' => false, 'message' => 'Proposta não encontrada.']);
            return;
        }

        if ($outcome === 'aceite') {
            $valor = (float) preg_replace('/[^0-9]/', '', (string) $valor_raw);
            if ($valor <= 0) {
                echo json_encode(['success' => false, 'message' => 'Indica o valor da proposta aceite.']);
                return;
            }
            $venda = $this->dps_criar_venda($prop, $valor);
            $this->db->where('id', $id)->update(db_prefix() . 'dps_propostas', [
                'outcome'    => 'aceite',
                'valor'      => $valor,
                'outcome_at' => date('Y-m-d H:i:s'),
                'venda_id'   => $venda['id'],
            ]);
            $this->dps_set_lead_status((int) $prop->lead_id, 13);
            echo json_encode([
                'success' => true,
                'message' => 'Proposta ACEITE — Concretizado. Venda registada nas comissões: ' . number_format($valor, 0, ',', '.') . ' € · comissão ' . number_format($venda['comissao'], 2, ',', '.') . ' € (' . rtrim(rtrim(number_format($venda['taxa'], 3, ',', '.'), '0'), ',') . '%).',
            ]);
            return;
        }

        if ($outcome === 'recusado') {
            $this->db->where('id', $id)->update(db_prefix() . 'dps_propostas', [
                'outcome' => 'recusado', 'valor' => null, 'outcome_at' => date('Y-m-d H:i:s'),
            ]);
            $this->dps_set_lead_status((int) $prop->lead_id, 3);
            echo json_encode(['success' => true, 'message' => 'Proposta RECUSADA — lead movida para "Para outras oportunidades".']);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    }

    private function dps_set_lead_status($lead_id, $new_status)
    {
        $old = $this->db->select('status')->where('id', $lead_id)->get(db_prefix() . 'leads')->row();
        if (! $old || (int) $old->status === (int) $new_status) {
            return;
        }
        $this->db->where('id', $lead_id)->update(db_prefix() . 'leads', [
            'status'             => (int) $new_status,
            'last_status_change' => date('Y-m-d H:i:s'),
        ]);
        $sid = get_staff_user_id();
        $this->db->insert(db_prefix() . 'lead_activity_log', [
            'leadid'      => $lead_id,
            'staffid'     => $sid,
            'full_name'   => get_staff_full_name($sid),
            'date'        => date('Y-m-d H:i:s'),
            'description' => 'Estado alterado (resultado de proposta) para ' . ($this->status_name($new_status) ?: $new_status),
        ]);
        hooks()->do_action('lead_status_changed', [
            'lead_id'    => $lead_id,
            'old_status' => (int) $old->status,
            'new_status' => (int) $new_status,
        ]);
    }

    /**
     * Cria uma venda no módulo de comissões a partir de uma proposta aceite.
     * Calcula a comissão pela taxa do empreendimento.
     */
    private function dps_criar_venda($prop, $valor)
    {
        $emp  = $this->db->where('nome', $prop->empreendimento)->get(db_prefix() . 'simulador_empreendimentos')->row();
        $taxa = $emp ? (float) $emp->taxa : 0;
        $comissao = round($valor * $taxa / 100, 2);

        $lead = $this->db->select('name')->where('id', (int) $prop->lead_id)->get(db_prefix() . 'leads')->row();

        $this->db->insert(db_prefix() . 'simulador_vendas', [
            'empreendimento' => $prop->empreendimento,
            'taxa'           => $taxa,
            'unidade'        => $prop->unidade,
            'cliente'        => $lead ? $lead->name : '',
            'valor'          => $valor,
            'comissao_total' => $comissao,
            'data_venda'     => date('Y-m-d'),
            'staff_id'       => (int) $prop->staff_id,
            'date_created'   => date('Y-m-d H:i:s'),
        ]);

        return ['id' => $this->db->insert_id(), 'comissao' => $comissao, 'taxa' => $taxa];
    }

    /**
     * Envia a proposta (PDF gerado no simulador) pelo WhatsApp do utilizador
     * AUTENTICADO no CRM e regista. Chamado pela ficha da lead (sessão logada).
     */
    public function enviar_proposta_pdf()
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }
        $lead_id   = (int) $this->input->post('lead_id');
        $emp       = trim((string) $this->input->post('empreendimento'));
        $unidade   = trim((string) $this->input->post('unidade'));
        $file_name = $this->input->post('file_name') ?: 'Proposta.pdf';
        $pdf       = (string) $this->input->post('pdf_base64');

        $staff_id = get_staff_user_id(); // utilizador LOGADO

        if (! $lead_id || $pdf === '') {
            echo json_encode(['success' => false, 'message' => 'Dados da proposta em falta.']);
            return;
        }
        if (! dps_propostas_staff_connected($staff_id)) {
            echo json_encode(['success' => false, 'message' => 'O teu WhatsApp não está ligado (Definições → WhatsApp).']);
            return;
        }

        $lead = $this->db->select('name, phonenumber, status')->where('id', $lead_id)->get(db_prefix() . 'leads')->row();
        if (! $lead) {
            echo json_encode(['success' => false, 'message' => 'Lead não encontrada.']);
            return;
        }
        $number = preg_replace('/[^0-9]/', '', (string) $lead->phonenumber);
        if ($number === '') {
            echo json_encode(['success' => false, 'message' => 'A lead não tem telefone.']);
            return;
        }

        $media   = preg_replace('#^data:[^;]+;base64,#', '', $pdf);
        $site    = dps_propostas_site_por_nome($emp);
        $caption = 'Proposta' . ($emp ? ' — ' . $emp : '') . ($unidade ? ' — Unidade ' . $unidade : '')
            . ($site ? "\n\n🌐 Mais informação:\n" . $site : '');

        $r  = dps_propostas_send_document_b64($staff_id, $number, $media, $file_name, $caption);
        $ok = $r['ok'];

        $this->db->insert(db_prefix() . 'dps_propostas', [
            'lead_id'          => $lead_id,
            'staff_id'         => $staff_id,
            'tipo'             => 'proposta',
            'empreendimento'   => $emp,
            'unidade'          => $unidade,
            'lead_status_id'   => (int) $lead->status,
            'lead_status_nome' => $this->status_name($lead->status),
            'ficheiro'         => $file_name,
            'detalhe'          => 'Enviada pelo comercial (simulador)',
            'wa_ok'            => $ok ? 1 : 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Proposta enviada ao cliente e registada.' : dps_propostas_erro_wa($r, $number),
        ]);
    }
}
