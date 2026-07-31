<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_propostas extends AdminController
{
    /**
     * A lead, com tudo o que os envios precisam de saber sobre ela.
     *
     * O `email` faltava nesta lista. Como o PHP devolve vazio para uma
     * propriedade que não existe, o envio por email respondia "a lead não tem
     * email válido" a TODAS as leads — mesmo às 6.036 que têm email bom. O erro
     * não estava nos dados nem na validação: estava em nunca se ter ido buscar
     * o campo.
     *
     * Quem acrescentar aqui um canal novo tem de acrescentar também a coluna:
     * um campo em falta não dá erro, dá uma mensagem errada.
     */
    private function lead_or_die($lead_id)
    {
        $lead = $this->db->select('id, name, phonenumber, email, status')
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
    /**
     * Marca um contacto com a lead: atualiza o "último contacto" e regista a
     * interação no log de atividade (para contar na Visão Geral / interações).
     */
    private function dps_marcar_contacto($lead_id, $staff_id, $desc)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->where('id', (int) $lead_id)->update(db_prefix() . 'leads', ['lastcontact' => $now]);
        $this->db->insert(db_prefix() . 'lead_activity_log', [
            'leadid'          => (int) $lead_id,
            'description'     => $desc,
            'date'            => $now,
            'staffid'         => (int) $staff_id,
            'full_name'       => get_staff_full_name($staff_id),
            'additional_data' => '',
        ]);
    }

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

        /*
         * Canal escolhido pelo comercial no momento do envio, como já acontece
         * nas propostas. Antes isto era WhatsApp e mais nada — se o cliente não
         * tinha telefone, ou a instância estava em baixo, não havia alternativa.
         */
        $canal = $this->input->post('canal') === 'email' ? 'email' : 'whatsapp';

        $lead = $this->lead_or_die($lead_id);
        if (! $lead) {
            echo json_encode(['success' => false, 'message' => 'Lead não encontrada.']);
            return;
        }

        $number = preg_replace('/[^0-9]/', '', (string) $lead->phonenumber);
        $email  = trim((string) $lead->email);

        if ($canal === 'whatsapp') {
            if (! dps_propostas_staff_connected($staff_id)) {
                echo json_encode(['success' => false, 'message' => 'O teu WhatsApp não está ligado (Definições → WhatsApp). Podes enviar por email.']);
                return;
            }
            if ($number === '') {
                echo json_encode(['success' => false, 'message' => 'A lead não tem telefone. Podes enviar por email.']);
                return;
            }
        } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'A lead não tem email válido. Podes enviar por WhatsApp.']);
            return;
        }

        $disp = dps_propostas_disponibilidade($key);

        /*
         * TRAVÃO: se não se conseguiu ler as disponibilidades, NÃO se envia.
         * Antes seguia em frente e anunciava "0 unidades disponíveis" ao
         * cliente — chegou a acontecer com 119 unidades livres no Boavista.
         * Uma mensagem errada a um cliente custa mais do que um envio adiado.
         */
        if (empty($disp['ok'])) {
            echo json_encode([
                'success' => false,
                'message' => $disp['erro']
                    ?? 'Não consegui ler as unidades disponíveis. Tente de novo — nada foi enviado.',
            ]);
            return;
        }

        if ((int) $disp['count'] === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Não há unidades disponíveis neste empreendimento neste momento — '
                    . 'confirme no simulador antes de enviar.',
            ]);
            return;
        }

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

        // O PDF da tabela ao vivo é o mesmo nos dois canais: gera-se uma vez.
        $b64 = null;
        if (! empty($disp['unidades'])) {
            try {
                $b64 = dps_propostas_gerar_pdf_disponiveis($emp['nome'], $disp['unidades']);
            } catch (\Throwable $e) {
                $b64 = null;
            }
        }

        $pdf_ok  = false;
        $erro_wa = null;

        if ($canal === 'whatsapp') {
            $r  = dps_propostas_send_text($staff_id, $number, $msg);
            $ok = $r['ok'];
            if (! $ok) {
                $erro_wa = dps_propostas_erro_wa($r, $number);
            }

            if ($b64 !== null) {
                try {
                    $rp = dps_propostas_send_document_b64(
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
        } else {
            list($ok, $erro_wa, $pdf_ok) = $this->enviar_info_email($staff_id, $email, $emp, $disp, $lead, $b64);
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
            'canal'            => $canal,
            'wa_ok'            => $ok ? 1 : 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        // Envio conta como contacto: atualiza último contacto + interação.
        $this->dps_marcar_contacto($lead_id, $staff_id, '📤 Informação enviada — ' . $emp['nome']);

        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? ('Informação enviada por ' . ($canal === 'email' ? 'email' : 'WhatsApp') . ' para ' . $lead->name
                    . ' — ' . $disp['count'] . ' unidades disponíveis' . ($pdf_ok ? ' + tabela em anexo' : '') . '.')
                : ($erro_wa ?: 'Não foi possível enviar.'),
        ]);
    }

    /**
     * Envia as disponibilidades por email, pelo SMTP do próprio comercial.
     *
     * Reaproveita o mailer do dps_automacao (webmail do comercial, com recurso
     * ao SMTP global) em vez de duplicar a lógica de credenciais. Quando não há
     * nenhum SMTP configurado devolve o erro em texto, para o comercial saber o
     * que fazer em vez de ver "não foi possível enviar".
     *
     * @return array [bool enviado, string|null erro, bool anexo_incluido]
     */
    private function enviar_info_email($staff_id, $email, $emp, $disp, $lead, $b64)
    {
        if (! function_exists('dps_automacao_mailer')) {
            $this->load->helper('dps_automacao/dps_automacao');
        }
        if (! function_exists('dps_automacao_mailer')) {
            return [false, 'O envio por email não está disponível (módulo de automação em falta).', false];
        }

        $mail = dps_automacao_mailer($staff_id);
        if ($mail === null) {
            return [false, 'Não tens SMTP configurado no teu Webmail — configura em Webmail → Definições, ou envia por WhatsApp.', false];
        }

        $this->db->select('email, CONCAT(firstname, " ", lastname) AS nome');
        $this->db->where('staffid', (int) $staff_id);
        $s = $this->db->get(db_prefix() . 'staff')->row_array() ?: [];

        $com = [
            'nome'     => $s['nome'] ?? '',
            'email'    => $s['email'] ?? '',
            'telefone' => dps_propostas_telefone_staff($staff_id),
        ];

        $anexo = false;

        try {
            $mail->clearAllRecipients();
            $mail->clearAttachments();
            $mail->addAddress($email, (string) $lead->name);
            $mail->isHTML(true);
            $mail->Subject = $emp['nome'] . ' — unidades disponíveis';
            $mail->Body    = dps_propostas_email_disponiveis($emp, $disp, (string) $lead->name, $com);
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $mail->Body));

            if ($b64 !== null) {
                $mail->addStringAttachment(
                    base64_decode($b64),
                    'Unidades Disponiveis - ' . $emp['nome'] . '.pdf',
                    'base64',
                    'application/pdf'
                );
                $anexo = true;
            }

            $mail->send();

            return [true, null, $anexo];
        } catch (\Throwable $e) {
            return [false, 'O servidor de email recusou o envio: ' . $e->getMessage(), false];
        }
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

        /*
         * Resultados por comercial: aceites, recusadas e pendentes.
         *
         * Ao contrário da matriz por empreendimento (que compara sempre a
         * equipa toda), este RESPEITA o filtro — é o quadro que a direção usa
         * para olhar comercial a comercial. E um comercial sem permissão de
         * ver tudo só se vê a si, porque o $comercial já foi forçado acima.
         */
        $this->db->select('p.staff_id, CONCAT(s.firstname," ",s.lastname) AS nome,
            COALESCE(NULLIF(p.outcome, ""), "pendente") AS resultado, COUNT(*) AS n', false);
        $this->db->from(db_prefix() . 'dps_propostas p');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = p.staff_id', 'left');
        $this->db->where('p.tipo', 'proposta');
        if ($comercial > 0) {
            $this->db->where('p.staff_id', $comercial);
        }
        $this->db->group_by(['p.staff_id', 'resultado']);
        $linhas_res = $this->db->get()->result_array();

        $r_nomes = [];
        $r_dados = [];
        foreach ($linhas_res as $l) {
            $sid = (int) $l['staff_id'];
            $r_nomes[$sid] = trim((string) $l['nome']) ?: ('Staff #' . $sid);
            if (!isset($r_dados[$sid])) {
                $r_dados[$sid] = ['aceite' => 0, 'recusado' => 0, 'pendente' => 0];
            }
            $chave = in_array($l['resultado'], ['aceite', 'recusado'], true) ? $l['resultado'] : 'pendente';
            $r_dados[$sid][$chave] += (int) $l['n'];
        }

        // Mais propostas primeiro: é o que se quer ver de relance.
        uksort($r_nomes, function ($a, $b) use ($r_dados) {
            return array_sum($r_dados[$b]) <=> array_sum($r_dados[$a]);
        });

        $data['r_nomes'] = $r_nomes;
        $data['r_dados'] = $r_dados;

        /*
         * Matriz comercial x empreendimento para o gráfico.
         * NÃO respeita o filtro de comercial de propósito: o gráfico serve
         * para comparar a equipa toda. Vai sempre a toda a base (não à lista
         * limitada a 1000 acima), senão os números não batiam com a realidade.
         */
        $matriz = $this->db->query(
            'SELECT s.staffid,
                    TRIM(CONCAT(s.firstname, " ", s.lastname)) AS comercial,
                    COALESCE(NULLIF(TRIM(p.empreendimento), ""), "(sem empreendimento)") AS emp,
                    COUNT(*) AS total
             FROM ' . db_prefix() . 'dps_propostas p
             JOIN ' . db_prefix() . 'staff s ON s.staffid = p.staff_id
             WHERE p.tipo = "proposta"
             GROUP BY s.staffid, emp
             ORDER BY s.firstname, s.lastname'
        )->result_array();

        $g_comerciais = [];   // staffid => nome
        $g_emps       = [];   // nome do empreendimento
        $g_valores    = [];   // staffid => [emp => total]

        /*
         * Normalizar o nome do empreendimento: as propostas antigas gravaram
         * ora a chave interna ("boavista") ora o nome ("Boavista Towers"), e
         * no gráfico apareciam como dois empreendimentos distintos.
         */
        $canonico = function ($nome) {
            $k = strtolower(trim((string) $nome));
            $k = strtr($k, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ç'=>'c']);
            if ($k === '' ) { return '(sem empreendimento)'; }
            if (strpos($k, 'boavista') !== false)                              { return 'Boavista Towers'; }
            if (strpos($k, 'belo horizonte') !== false || $k === 'bh')          { return 'Belo Horizonte'; }
            if (strpos($k, 'raizes') !== false)                                { return 'Raízes Fanzeres'; }
            if (strpos($k, 'gaia douro') !== false || strpos($k, 'gaiadouro') !== false
                || strpos($k, 'douro mar') !== false || strpos($k, 'douromar') !== false) { return 'Gaia Douro'; }
            if (strpos($k, 'aura') !== false)                                  { return 'Aura Residence'; }
            if (strpos($k, 'lake') !== false)                                  { return 'Lake Towers'; }
            if (strpos($k, 'gaia premium') !== false || $k === 'gp')           { return 'Gaia Premium'; }
            return $nome;
        };

        foreach ($matriz as $m) {
            $sid = (int) $m['staffid'];
            $emp = $canonico($m['emp']);
            $g_comerciais[$sid] = $m['comercial'];
            $g_emps[$emp]       = true;
            // somar: variantes do mesmo empreendimento juntam-se aqui
            $g_valores[$sid][$emp] = ($g_valores[$sid][$emp] ?? 0) + (int) $m['total'];
        }

        $g_emps = array_keys($g_emps);
        sort($g_emps);

        // Ordenar comerciais por total de propostas (mais activos primeiro).
        uksort($g_comerciais, function ($a, $b) use ($g_valores) {
            return array_sum($g_valores[$b] ?? []) <=> array_sum($g_valores[$a] ?? []);
        });

        /*
         * Totais de resultado. Vão a toda a base (não à lista limitada a
         * 1000) para os números serem os reais. "Sem resultado" = enviadas
         * que ainda não foram marcadas como aceites nem recusadas.
         */
        $res = $this->db->query(
            'SELECT COUNT(*) AS enviadas,
                    SUM(CASE WHEN outcome = "aceite"   THEN 1 ELSE 0 END) AS aceites,
                    SUM(CASE WHEN outcome = "recusado" THEN 1 ELSE 0 END) AS recusadas
             FROM ' . db_prefix() . 'dps_propostas
             WHERE tipo = "proposta"'
        )->row_array();

        $data['t_enviadas']  = (int) ($res['enviadas'] ?? 0);
        $data['t_aceites']   = (int) ($res['aceites'] ?? 0);
        $data['t_recusadas'] = (int) ($res['recusadas'] ?? 0);
        $data['t_abertas']   = $data['t_enviadas'] - $data['t_aceites'] - $data['t_recusadas'];

        $data['g_comerciais'] = $g_comerciais;
        $data['g_emps']       = $g_emps;
        $data['g_valores']    = $g_valores;

        $data['title']        = 'Propostas Enviadas';
        $data['propostas']    = $propostas;
        $data['can_view_all'] = $can_view_all;
        $data['comercial']    = $comercial;
        $data['comerciais']   = $comerciais;
        $this->load->view('todas', $data);
    }

    /**
     * Contexto mínimo de uma lead para os botões "Proposta" e "Disponíveis"
     * da tabela de leads agirem DIRETAMENTE, sem abrir a ficha do cliente.
     *
     * Devolve o token do simulador, os dados da lead e a lista de
     * empreendimentos — tudo o que o painel da ficha usa, mas sem a ficha.
     */
    public function contexto_lead($lead_id = '')
    {
        if (! is_staff_member()) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }

        $lead = $this->db->select('id, name, phonenumber, email')
            ->where('id', (int) $lead_id)
            ->get(db_prefix() . 'leads')->row();

        if (! $lead) {
            echo json_encode(['success' => false, 'message' => 'Lead não encontrada.']);
            return;
        }

        $staff_id = get_staff_user_id();
        $emps     = [];

        foreach (dps_propostas_empreendimentos() as $k => $e) {
            $emps[] = [
                'k'            => $k,
                'nome'         => $e['nome'],
                'tem_proposta' => ! empty($e['tem_proposta']),
            ];
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'  => true,
            'lead_id'  => (int) $lead->id,
            'nome'     => (string) $lead->name,
            'telefone' => (string) $lead->phonenumber,
            'email'    => (string) $lead->email,
            'staff_id' => (int) $staff_id,
            'token'    => dps_propostas_proposta_token($lead->id, $staff_id),
            'emps'     => $emps,
        ], JSON_UNESCAPED_UNICODE);
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
            /*
             * Aceite a proposta, a reserva abre-se logo.
             *
             * A venda já fica criada aqui, mas nasce só com o valor e a taxa —
             * falta-lhe o cliente completo, a unidade e os documentos. Antes,
             * quem aceitava ficava no mesmo ecrã e a venda ficava a metade até
             * alguém se lembrar dela. Agora o formulário abre a seguir, com a
             * venda já lá, e o caminho até ao mapa de vendas fica fechado.
             */
            echo json_encode([
                'success'  => true,
                'redirect' => admin_url('dps_vendas/form/' . (int) $venda['id']),
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

        // jsPDF devolve "data:application/pdf;filename=generated.pdf;base64,..." — cortar tudo até "base64,".
        $pos     = strpos($pdf, 'base64,');
        $media   = $pos !== false ? substr($pdf, $pos + 7) : $pdf;
        $media   = preg_replace('/\s+/', '', $media); // remover quebras de linha do datauri
        $site    = dps_propostas_site_por_nome($emp);
        $caption = 'Proposta' . ($emp ? ' — ' . $emp : '') . ($unidade ? ' — Unidade ' . $unidade : '')
            . ($site ? "\n\n🌐 Mais informação:\n" . $site : '');

        if ($media === '' || strlen($media) < 100) {
            echo json_encode(['success' => false, 'message' => 'O PDF da proposta veio vazio — gera de novo.']);
            return;
        }

        $r   = dps_propostas_send_document_b64($staff_id, $number, $media, $file_name, $caption);
        // 2xx só confirma que a Evolution aceitou; a entrega real traz uma "key" na resposta.
        $raw = (string) ($r['raw'] ?? '');
        $ok  = $r['ok'] && strpos($raw, '"key"') !== false;

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

        // Envio conta como contacto: atualiza último contacto + interação.
        $this->dps_marcar_contacto($lead_id, $staff_id, '📄 Proposta enviada — ' . $emp . ($unidade ? ' ' . $unidade : ''));

        // Boavista Tower: nota com a fração enviada + estado -> VIP 1 (17).
        if (stripos($emp, 'boavista') !== false) {
            $this->db->insert(db_prefix() . 'notes', [
                'rel_id'      => $lead_id,
                'rel_type'    => 'lead',
                'description' => 'Proposta enviada — ' . $emp . ($unidade !== '' ? ' — Fração ' . $unidade : ''),
                'dateadded'   => date('Y-m-d H:i:s'),
                'addedfrom'   => $staff_id,
            ]);
            if ((int) $lead->status !== 13) {
                $this->dps_set_lead_status($lead_id, 17); // VIP 1 (dispara hook -> sync WhatsApp)
            }
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Proposta enviada ao cliente e registada.' : dps_propostas_erro_wa($r, $number),
        ]);
    }
}
