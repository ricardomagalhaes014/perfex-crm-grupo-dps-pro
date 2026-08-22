<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Estados de lead usados aqui. Os números vivem em tblleads_status e são
 * editáveis no CRM; ter o 10 e o 13 espalhados pelo código era garantir que um
 * dia alguém mexia nos estados e ninguém ligava os pontos.
 */
defined('DPS_PROPOSTAS_ESTADO_CONTRATO') || define('DPS_PROPOSTAS_ESTADO_CONTRATO', 10);      // PARA CONTRATO
defined('DPS_PROPOSTAS_ESTADO_CONCRETIZADO') || define('DPS_PROPOSTAS_ESTADO_CONCRETIZADO', 13);  // CONCRETIZADO
defined('DPS_PROPOSTAS_ESTADO_VIP1') || define('DPS_PROPOSTAS_ESTADO_VIP1', 17);                // VIP 1

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

    /**
     * Mensagem livre para a lead, escrita no CRM e enviada pelo WhatsApp do
     * próprio comercial.
     *
     * O botão verde da lista abria o wa.me e mais nada: escrevia-se a mensagem
     * no WhatsApp, voltava-se aqui e escrevia-se outra vez na nota — ou, o que
     * acontecia mais vezes, não se escrevia, e a ficha ficava sem rasto de uma
     * conversa que houve. Escreve-se uma vez, sai pelo WhatsApp e fica na
     * ficha. Pedido do dono (22/08/2026).
     *
     * A nota grava-se ANTES do envio, e de propósito. Se a Evolution falhar,
     * fica escrito o que se tentou dizer ao cliente; ao contrário, uma mensagem
     * entregue sem nota é uma conversa que desapareceu.
     */
    public function mensagem_whatsapp()
    {
        if (! is_staff_member()) {
            ajax_access_denied();
        }

        $lead_id = (int) $this->input->post('lead_id');
        $texto   = trim((string) $this->input->post('texto'));
        $staff_id = get_staff_user_id();

        if (! $lead_id || $texto === '') {
            echo json_encode(['success' => false, 'message' => 'Escreva a mensagem primeiro.']);
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

        if (! dps_propostas_staff_connected($staff_id)) {
            echo json_encode([
                'success' => false,
                'message' => 'O seu WhatsApp não está ligado. Vá a Definições → WhatsApp e leia o QR.',
            ]);
            return;
        }

        /*
         * A nota primeiro — ver o comentário acima. Pelo modelo do núcleo e não
         * por um INSERT à mão: é ele que trata das quebras de linha e dispara o
         * note_created que o resto do CRM escuta.
         */
        $this->load->model('misc_model');
        $this->misc_model->add_note(
            ['description' => '📱 WhatsApp: ' . $texto, 'date_contacted' => date('Y-m-d H:i:s')],
            'lead',
            $lead_id
        );

        $r = dps_propostas_send_text($staff_id, $number, $texto);

        if (! $r['ok']) {
            /*
             * Deixa-se rasto do falhanço na mesma linha de tempo da lead: quem
             * abrir a ficha vê a mensagem que se quis mandar e vê, logo a
             * seguir, que ela não chegou.
             */
            $this->dps_marcar_contacto(
                $lead_id,
                $staff_id,
                '⚠️ Mensagem de WhatsApp não entregue — ' . dps_propostas_erro_wa($r, $number)
            );

            echo json_encode([
                'success' => false,
                'nota'    => true,
                'message' => dps_propostas_erro_wa($r, $number) . ' A nota ficou guardada.',
            ]);
            return;
        }

        /*
         * O "? Nota:" à frente não é engano nem emoji partido de agora: é
         * assim que estão gravadas as 2762 linhas que já lá existem, e é esse
         * prefixo que a régua das interacções procura. Escrever outra coisa
         * fazia com que estas mensagens não contassem como contacto — que é
         * precisamente o que elas são.
         */
        $this->dps_marcar_contacto(
            $lead_id,
            $staff_id,
            '? Nota: WhatsApp — ' . mb_substr($texto, 0, 120)
        );

        echo json_encode([
            'success' => true,
            'message' => 'Mensagem enviada por WhatsApp e guardada nas notas.',
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
        // A chave viaja com o empreendimento: o construtor do email
        // precisa dela para ir buscar o texto de apresentação.
        $emp['chave'] = $key;
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

        /*
         * Texto de apresentação escrito pela direcção, quando o empreendimento
         * tem um. Substitui a linha genérica do site porque já traz o seu
         * próprio link — repeti-la punha o mesmo endereço duas vezes na mesma
         * mensagem. O dossier fica, que é outro documento.
         */
        $apresentacao = dps_propostas_apresentacao($key);
        if ($apresentacao !== '') {
            $msg .= $apresentacao . "\n\n";
        }

        if (! empty($emp['dossier'])) {
            $msg .= "📄 Dossier comercial:\n" . $emp['dossier'] . "\n\n";
        }
        if ($apresentacao === '') {
            $msg .= "🌐 Mais informação:\n" . $emp['site'] . "\n\n";
        }
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

        /*
         * Nota na lead e passagem a VIP 1. Pedido do dono (11/08/2026).
         *
         * A nota fica escrita com o empreendimento porque, três semanas depois,
         * "enviadas disponibilidades" sem dizer de quê não vale nada — e quem
         * abre a ficha precisa de saber o que é que o cliente já viu.
         *
         * Só se escreve quando o envio SAIU. Uma nota a dizer que se enviou
         * uma coisa que falhou é pior do que não haver nota nenhuma.
         */
        if ($ok) {
            $this->misc_model->add_note(
                ['description' => 'Enviadas disponibilidades — ' . $emp['nome']],
                'lead',
                $lead_id
            );

            $this->dps_promover_vip1($lead_id);
        }

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

        $ok      = false;
        $detalhe = null;

        if ($file_url && $number !== '') {
            $primeiro = trim(explode(' ', (string) $lead->name)[0]);
            $caption  = 'Proposta' . ($emp_nome ? ' — ' . $emp_nome : '') . ' — Unidade ' . $unidade;
            $r        = dps_propostas_send_document($staff_id, $number, $file_url, $file_name, $caption);

            /*
             * 2xx só diz que a Evolution aceitou o pedido. A prova de que o
             * WhatsApp criou mesmo a mensagem é vir uma "key" na resposta —
             * o mesmo critério do envio pelo simulador. Sem isso, dar como
             * enviada é mentir ao comercial.
             */
            $raw     = (string) ($r['raw'] ?? '');
            $ok      = ! empty($r['ok']) && strpos($raw, '"key"') !== false;
            $detalhe = 'HTTP ' . ($r['http'] ?? 0) . ' ' . substr($raw ?: (string) ($r['error'] ?? ''), 0, 800);
        } elseif ($number === '') {
            $detalhe = 'A lead não tem telefone.';
        } else {
            $detalhe = 'Sem ficheiro para enviar.';
        }

        $this->db->insert(db_prefix() . 'dps_propostas', $this->dps_wa_recibo($raw ?? '') + [
            'lead_id'          => $lead_id,
            'staff_id'         => $staff_id,
            'tipo'             => 'proposta',
            'empreendimento'   => $emp_nome,
            'unidade'          => $unidade,
            'lead_status_id'   => (int) $lead->status,
            'lead_status_nome' => $this->status_name($lead->status),
            'ficheiro'         => $file_url,
            'detalhe'          => $detalhe,
            'wa_ok'            => $ok ? 1 : 0,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        if ($ok) {
            $this->dps_promover_proposta_enviada($lead_id, $lead->status);
        }

        /*
         * Isto respondia sempre 'success' => true, mesmo com o envio falhado.
         * O comercial via a confirmação verde, dava a proposta por entregue e
         * seguia em frente — e o cliente nunca tinha recebido nada. O registo
         * fica de qualquer maneira (é o histórico), mas quem carregou no botão
         * passa a saber a verdade.
         */
        echo json_encode([
            'success' => $ok,
            'message' => $ok
                ? 'Proposta enviada e registada.'
                : 'Proposta REGISTADA mas NÃO enviada por WhatsApp — ' . dps_propostas_erro_wa($r ?? [], $number),
        ]);
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

        /*
         * Filtro por empreendimento. Texto e não id: a coluna é texto livre,
         * escrita por quem enviou a proposta. A lista do dropdown sai dos
         * valores que existem mesmo, para não oferecer empreendimentos sem
         * uma única proposta.
         */
        $empreendimento = trim((string) $this->input->get('empreendimento'));

        /*
         * Filtro por resultado: aceites, recusadas ou ainda sem resposta.
         * 'pendente' é o vazio e o literal — as duas formas existem na tabela.
         */
        $resultado = trim((string) $this->input->get('resultado'));
        if (! in_array($resultado, ['aceite', 'recusado', 'cancelado', 'pendente'], true)) {
            $resultado = '';
        }

        /*
         * A CANCELADA QUE JÁ FOI RESOLVIDA SAI DA LISTA.
         *
         * Uma proposta cai porque a fracção foi vendida, a lead vai para VIP 1
         * e o comercial manda outra. A partir daí a cancelada é história: o
         * cliente já tem proposta nova. Deixá-la ali fazia com que o mesmo
         * cliente ocupasse duas linhas, uma delas a pedir uma acção que já foi
         * feita. Pedido do dono (22/08/2026).
         *
         * A régua é a data e não o id: só conta como substituição uma proposta
         * enviada DEPOIS do cancelamento. Sem isso, duas propostas do mesmo
         * cliente canceladas no mesmo lote davam-se por substituídas uma à
         * outra — foi o que aconteceu com quatro leads que tinham duas fracções
         * a cair ao mesmo tempo.
         */
        $sem_substituidas = 'NOT (COALESCE(p.outcome, "") = "cancelado" AND p.outcome_at IS NOT NULL
             AND EXISTS (SELECT 1 FROM ' . db_prefix() . 'dps_propostas n
                         WHERE n.lead_id = p.lead_id AND n.tipo = "proposta"
                         AND n.created_at > p.outcome_at))';

        $emps_filtro = $this->db->query(
            'SELECT p.empreendimento, COUNT(*) AS c
             FROM ' . db_prefix() . 'dps_propostas p
             WHERE p.tipo = "proposta" AND p.empreendimento IS NOT NULL AND p.empreendimento <> ""'
            . ($comercial > 0 ? ' AND p.staff_id = ' . (int) $comercial : '') .
            ' GROUP BY p.empreendimento ORDER BY c DESC'
        )->result_array();

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

        /*
         * Pesquisa. Escreve-se o número como ele vier à cabeça — com espaços,
         * com +351, com traços — e tem de encontrar na mesma.
         *
         * Os números estão guardados de todas as maneiras: "912345678",
         * "912 345 678", "+351 912345678". Comparar texto com texto não
         * encontrava nada. Por isso limpa-se dos dois lados e compara-se pelos
         * últimos 9 algarismos, que é o número português sem indicativo.
         *
         * Se o que se escreveu não tiver algarismos, procura-se pelo nome —
         * ninguém tem de saber qual é o campo certo.
         */
        $procura = trim((string) $this->input->get('q'));
        $so_digitos = preg_replace('/\D+/', '', $procura);

        if ($procura !== '') {
            if ($so_digitos !== '') {
                $agulha = strlen($so_digitos) > 9 ? substr($so_digitos, -9) : $so_digitos;
                $limpo  = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE("
                        . "l.phonenumber,' ',''),'-',''),'.',''),'(',''),')',''),'+','')";
                $this->db->where($limpo . " LIKE " . $this->db->escape('%' . $agulha . '%'), null, false);
            } else {
                $this->db->like('l.name', $procura);
            }
        }

        // Propostas (com estado ATUAL da lead).
        $this->db->select('p.*, l.name AS lead_nome, l.phonenumber AS lead_telefone, ls.name AS estado_atual');
        $this->db->from(db_prefix() . 'dps_propostas p');
        $this->db->join(db_prefix() . 'leads l', 'l.id = p.lead_id', 'left');
        $this->db->join(db_prefix() . 'leads_status ls', 'ls.id = l.status', 'left');
        $this->db->where('p.tipo', 'proposta');
        $this->db->where($sem_substituidas, null, false);
        if ($comercial > 0) {
            $this->db->where('p.staff_id', $comercial);
        }
        if ($empreendimento !== '') {
            $this->db->where('p.empreendimento', $empreendimento);
        }
        if ($resultado === 'pendente') {
            $this->db->where('(p.outcome IS NULL OR p.outcome = "" OR p.outcome = "pendente")', null, false);
        } elseif ($resultado !== '') {
            $this->db->where('p.outcome', $resultado);
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
        $this->db->where($sem_substituidas, null, false);
        if ($comercial > 0) {
            $this->db->where('p.staff_id', $comercial);
        }
        if ($empreendimento !== '') {
            $this->db->where('p.empreendimento', $empreendimento);
        }
        if ($resultado === 'pendente') {
            $this->db->where('(p.outcome IS NULL OR p.outcome = "" OR p.outcome = "pendente")', null, false);
        } elseif ($resultado !== '') {
            $this->db->where('p.outcome', $resultado);
        }
        /*
         * Agrupa-se pela EXPRESSÃO, não pelo alias: com ONLY_FULL_GROUP_BY
         * ligado (o normal no MySQL 8) agrupar por alias faz a consulta falhar,
         * e uma consulta falhada aqui devolvia vazio em silêncio — o gráfico
         * simplesmente não aparecia, sem erro nenhum à vista.
         */
        $this->db->group_by('p.staff_id, COALESCE(NULLIF(p.outcome, ""), "pendente")', false);
        $linhas_res = $this->db->get()->result_array();

        $r_nomes = [];
        $r_dados = [];
        foreach ($linhas_res as $l) {
            $sid = (int) $l['staff_id'];
            $r_nomes[$sid] = trim((string) $l['nome']) ?: ('Staff #' . $sid);
            if (!isset($r_dados[$sid])) {
                $r_dados[$sid] = ['aceite' => 0, 'recusado' => 0, 'cancelado' => 0, 'pendente' => 0];
            }
            $chave = in_array($l['resultado'], ['aceite', 'recusado', 'cancelado'], true)
                ? $l['resultado']
                : 'pendente';
            $r_dados[$sid][$chave] += (int) $l['n'];
        }

        // Mais propostas primeiro: é o que se quer ver de relance.
        uksort($r_nomes, function ($a, $b) use ($r_dados) {
            return array_sum($r_dados[$b]) <=> array_sum($r_dados[$a]);
        });

        /*
         * E os dados TÊM de seguir a mesma ordem dos nomes.
         *
         * A vista faz array_values() nos dois. Ordenar só os nomes deixava as
         * barras a dizer o nome de uma pessoa com os números de outra: as 4
         * propostas aceites do Ricardo apareciam desenhadas na barra da Catia.
         * Um gráfico errado é pior do que não haver gráfico, porque ninguém
         * desconfia dele.
         */
        $r_ordenados = [];
        foreach ($r_nomes as $sid => $nome) {
            $r_ordenados[$sid] = $r_dados[$sid];
        }
        $r_dados = $r_ordenados;

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
                || strpos($k, 'douro mar') !== false || strpos($k, 'douromar') !== false) { return 'Douro Mar'; }
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
         *
         * SEGUEM O FILTRO — comercial, empreendimento e pesquisa.
         *
         * Não seguiam: escolhia-se um comercial, o título passava a dizer
         * "152 propostas · Ricardo Magalhaes" e por baixo continuavam os 637
         * da empresa toda. Lidos em conjunto davam a percentagem errada da
         * pessoa que se estava a ver. Pior: um comercial sem permissão de ver
         * tudo via aqui os números da equipa inteira, que a lista lhe esconde.
         *
         * O filtro do RESULTADO fica de fora de propósito: é um mergulho
         * dentro destes quatro números, e aplicá-lo punha três deles a zero.
         */
        $this->db->select('COUNT(*) AS enviadas,
            SUM(CASE WHEN p.outcome = "aceite"    THEN 1 ELSE 0 END) AS aceites,
            SUM(CASE WHEN p.outcome = "recusado"  THEN 1 ELSE 0 END) AS recusadas,
            SUM(CASE WHEN p.outcome = "cancelado" THEN 1 ELSE 0 END) AS canceladas', false);
        $this->db->from(db_prefix() . 'dps_propostas p');
        $this->db->join(db_prefix() . 'leads l', 'l.id = p.lead_id', 'left');
        $this->db->where('p.tipo', 'proposta');
        $this->db->where($sem_substituidas, null, false);
        if ($comercial > 0) {
            $this->db->where('p.staff_id', $comercial);
        }
        if ($empreendimento !== '') {
            $this->db->where('p.empreendimento', $empreendimento);
        }
        if ($procura !== '') {
            if ($so_digitos !== '') {
                $agulha = strlen($so_digitos) > 9 ? substr($so_digitos, -9) : $so_digitos;
                $limpo  = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE("
                        . "l.phonenumber,' ',''),'-',''),'.',''),'(',''),')',''),'+','')";
                $this->db->where($limpo . " LIKE " . $this->db->escape('%' . $agulha . '%'), null, false);
            } else {
                $this->db->like('l.name', $procura);
            }
        }
        $res = $this->db->get()->row_array();

        $data['t_enviadas']   = (int) ($res['enviadas'] ?? 0);
        $data['t_aceites']    = (int) ($res['aceites'] ?? 0);
        $data['t_recusadas']  = (int) ($res['recusadas'] ?? 0);
        $data['t_canceladas'] = (int) ($res['canceladas'] ?? 0);
        $data['t_abertas']    = $data['t_enviadas'] - $data['t_aceites']
                              - $data['t_recusadas'] - $data['t_canceladas'];

        $data['g_comerciais'] = $g_comerciais;
        $data['g_emps']       = $g_emps;
        $data['g_valores']    = $g_valores;

        $data['title']        = 'Propostas Enviadas';
        $data['propostas']    = $propostas;
        $data['can_view_all'] = $can_view_all;
        $data['comercial']    = $comercial;
        $data['comerciais']   = $comerciais;
        $data['procura']      = $procura;
        $data['resultado']      = $resultado;
        $data['empreendimento'] = $empreendimento;
        $data['emps_filtro']    = $emps_filtro;
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
     *  - aceite  -> PARA CONTRATO (10)
     *  - recusado-> Para outras oportunidades (3)
     *
     * CONCRETIZADO fica para o fim do circuito, e não para aqui: uma proposta
     * aceite é uma palavra dada, não é dinheiro em casa. Só quando o
     * comprovativo de pagamento é carregado E a direção o confirma
     * (Dps_vendas::marcar_pago) é que a lead passa a Concretizado. Antes disto,
     * uma proposta aceite que caísse a seguir ficava para sempre contada como
     * negócio fechado. Regra do dono (03/08/2026).
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
            /*
             * O valor deixou de ser obrigatório aqui.
             *
             * Vinha de um prompt no browser e a seguir abria-se a ficha da
             * venda, onde o valor volta a ser definido ao escolher a unidade —
             * aí com o preço real da fração. Ter dois sítios a dizer quanto
             * vale a mesma venda só serve para eles divergirem.
             *
             * O valor vem sozinho do preço de tabela da fracção — a unidade
             * já foi escolhida ao enviar a proposta. Só quando a fracção não
             * está no catálogo é que a venda nasce a zero e se preenche no
             * formulário que abre a seguir. A comissão não sofre: só é fixada
             * quando a venda passa a CPCV, e aí lê o valor que lá estiver.
             */
            $valor = (float) preg_replace('/[^0-9]/', '', (string) $valor_raw);
            $venda = $this->dps_criar_venda($prop, $valor);
            // O valor descoberto no catálogo também fica na proposta, senão a
            // lista mostrava "valor por definir" numa venda que já tem preço.
            $valor = $valor > 0 ? $valor : (float) ($venda['valor'] ?? 0);
            $unidade_msg = (string) $prop->unidade;

            $this->db->where('id', $id)->update(db_prefix() . 'dps_propostas', [
                'outcome'    => 'aceite',
                'valor'      => $valor,
                'outcome_at' => date('Y-m-d H:i:s'),
                'venda_id'   => $venda['id'],
            ]);
            $this->dps_set_lead_status((int) $prop->lead_id, DPS_PROPOSTAS_ESTADO_CONTRATO);

            /*
             * Apagar o recado que o dps_vendas deixa quando a lead entra em
             * PARA CONTRATO. Esse recado serve para abrir o quadro de reserva
             * e criar a venda — e aqui a venda JÁ foi criada. Sem isto, o
             * comercial que voltasse à lista de leads nos dois minutos
             * seguintes abria o quadro e criava uma segunda venda para a mesma
             * proposta.
             */
            $this->session->unset_userdata('dps_vendas_reserva_lead');
            /*
             * Aceite a proposta, a reserva abre-se logo.
             *
             * A venda já fica criada aqui, com o cliente, a unidade, o valor
             * de tabela e a taxa — falta-lhe o resto dos dados de contrato e
             * os documentos. Antes,
             * quem aceitava ficava no mesmo ecrã e a venda ficava a metade até
             * alguém se lembrar dela. Agora o formulário abre a seguir, com a
             * venda já lá, e o caminho até ao mapa de vendas fica fechado.
             */
            echo json_encode([
                'success'  => true,
                'redirect' => admin_url('dps_vendas/form/' . (int) $venda['id']),
                // Um aviso quando a montra não foi actualizada: a venda existe,
                // mas a fracção continua a aparecer livre a quem a consultar.
                'aviso'   => empty($venda['na_montra'])
                    ? 'ATENÇÃO: não consegui marcar a fracção ' . $unidade_msg . ' no simulador. Confirme lá o estado.'
                    : null,
                'message' => $valor > 0
                    ? 'Proposta ACEITE — lead em PARA CONTRATO. Venda registada: '
                      . number_format($valor, 0, ',', '.') . ' € · comissão '
                      . number_format($venda['comissao'], 2, ',', '.') . ' €.'
                    : 'Proposta ACEITE — lead em PARA CONTRATO. A fracção não tem preço no catálogo:'
                      . ' preencha o valor na ficha da venda que abre a seguir.',
            ]);
            return;
        }

        if ($outcome === 'recusado') {
            /*
             * O motivo é obrigatório, e é validado AQUI e não só no ecrã.
             *
             * Uma lista obrigatória no browser é uma sugestão: o pedido pode
             * chegar por outro caminho e a proposta ficaria perdida sem se
             * saber porquê — que é exactamente o dado que se quer recolher.
             */
            $motivo  = (string) $this->input->post('motivo_perda');
            $motivos = dps_propostas_motivos_perda();

            if (! isset($motivos[$motivo])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Escolha o motivo da perda — é obrigatório.',
                ]);
                return;
            }

            $recusada_em = date('Y-m-d H:i:s');

            $this->db->where('id', $id)->update(db_prefix() . 'dps_propostas', [
                'outcome'      => 'recusado',
                'motivo_perda' => $motivo,
                'valor'        => null,
                'outcome_at'   => $recusada_em,
            ]);

            // O motivo fica também no histórico da lead: quem a abrir daqui a
            // três meses percebe porque é que ela parou, sem ir à tabela.
            $sid = get_staff_user_id();
            $this->db->insert(db_prefix() . 'lead_activity_log', [
                'leadid'      => (int) $prop->lead_id,
                'staffid'     => $sid,
                'full_name'   => get_staff_full_name($sid),
                'date'        => date('Y-m-d H:i:s'),
                'description' => '❌ Proposta recusada — motivo: ' . $motivos[$motivo]
                    . ($prop->empreendimento ? ' (' . $prop->empreendimento . ')' : ''),
            ]);

            $this->dps_set_lead_status((int) $prop->lead_id, 3);
            echo json_encode([
                'success' => true,
                'message' => 'Proposta RECUSADA (' . $motivos[$motivo]
                    . ') — lead movida para "Para outras oportunidades".',
                /*
                 * O ecrã acerta a linha sem recarregar a página — recarregar
                 * mandava o comercial de volta ao topo da lista. Por isso o
                 * servidor tem de dizer TUDO o que mudou: a hora, e o estado
                 * novo da lead. Sem o estado, a coluna "Estado da lead" ficava
                 * a dizer "PROPOSTAS ENVIADAS" até alguém recarregar à mão,
                 * quando a lead já tinha mudado na base de dados.
                 */
                'outcome_at'  => $recusada_em,
                'lead_id'     => (int) $prop->lead_id,
                'lead_estado' => $this->status_name(3) ?: 'PARA OUTRAS OPORTUNIDADES',
                'rotulo'      => 'Recusada',
                'cor'         => 'danger',
            ]);
            return;
        }

        if ($outcome === 'cancelado') {
            /*
             * Cancelar é diferente de recusar, e por isso tem estado próprio.
             *
             * Recusada é o cliente a dizer que não. Cancelada é a casa a ficar
             * sem o que vender: a fracção saiu do mercado e a proposta morreu
             * sem ninguém ter respondido. Somá-las dava uma taxa de recusa
             * inflacionada por coisas que não foram perdidas a vender.
             *
             * A lead vai na mesma para "Para outras oportunidades" — continua
             * a ser alguém a quem há que apresentar outra coisa.
             */
            $cancelada_em = date('Y-m-d H:i:s');

            $this->db->where('id', $id)->update(db_prefix() . 'dps_propostas', [
                'outcome'      => 'cancelado',
                'motivo_perda' => 'unidade_indisponivel',
                'valor'        => null,
                'outcome_at'   => $cancelada_em,
            ]);

            $sid = get_staff_user_id();
            $this->db->insert(db_prefix() . 'lead_activity_log', [
                'leadid'      => (int) $prop->lead_id,
                'staffid'     => $sid,
                'full_name'   => get_staff_full_name($sid),
                'date'        => $cancelada_em,
                'description' => '🚫 Proposta cancelada — a fracção ' . $prop->unidade
                    . ($prop->empreendimento ? ' (' . $prop->empreendimento . ')' : '')
                    . ' já não está disponível.',
            ]);

            // O cliente é avisado, como no cancelamento automático.
            $lead = $this->db->select('name, email')
                             ->where('id', (int) $prop->lead_id)
                             ->get(db_prefix() . 'leads')
                             ->row();

            $avisado = dps_propostas_avisar_cliente_unidade_saiu(
                (object) [
                    'lead_email'     => $lead->email ?? '',
                    'lead_nome'      => $lead->name ?? '',
                    'staff_id'       => (int) $prop->staff_id,
                    'unidade'        => $prop->unidade,
                    'empreendimento' => $prop->empreendimento,
                ],
                dps_propostas_estado_montra($prop->empreendimento, $prop->unidade) ?: 'Vendido'
            );

            /*
             * VIP 1, e NÃO "Para outras oportunidades".
             *
             * Aquele estado é para quem disse que não. Aqui ninguém disse
             * nada: o cliente continua interessado e foi a casa que ficou sem
             * a fracção. Mandá-lo para "outras oportunidades" arrumava-o com
             * os perdidos e tirava-o da frente do comercial, quando é
             * precisamente quem tem de ser contactado já com outra proposta —
             * e volta a PROPOSTAS ENVIADAS assim que ela sair.
             * Regra do dono (14/08/2026).
             */
            $this->dps_set_lead_status((int) $prop->lead_id, DPS_PROPOSTAS_ESTADO_VIP1);

            echo json_encode([
                'success'     => true,
                'message'     => 'Proposta CANCELADA — a fracção já não está disponível. '
                    . 'A lead voltou a VIP 1 para lhe enviar nova proposta.'
                    . ($avisado ? ' O cliente foi avisado por email.' : ' (sem email do cliente para avisar)'),
                'outcome_at'  => $cancelada_em,
                'lead_id'     => (int) $prop->lead_id,
                'lead_estado' => $this->status_name(DPS_PROPOSTAS_ESTADO_VIP1) ?: 'VIP 1',
                'rotulo'      => 'Cancelada',
                'cor'         => 'warning',
            ]);
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
        /*
         * O valor vem do preço de tabela da fracção, e não de alguém o
         * escrever de cor. A unidade já foi escolhida ao enviar a proposta,
         * portanto o preço é um facto que está no catálogo do simulador — o
         * mesmo que o cliente viu.
         *
         * Se a unidade não estiver no catálogo (fracção antiga, nome trocado),
         * a venda nasce a zero e preenche-se no formulário, que abre a seguir.
         * Preferível a inventar um número.
         */
        if ($valor <= 0) {
            $valor = dps_propostas_preco_unidade($prop->empreendimento, $prop->unidade);
        }

        /*
         * A venda guarda a fracção com o nome do CATÁLOGO, não com o que veio
         * na proposta.
         *
         * O Douro Mar é "1_AL" no catálogo e no simulador; a proposta trazia
         * "AL". Guardar "AL" na venda fazia falhar tudo o que viesse a seguir —
         * o preço, e depois a mudança de estado para DPS na montra, que procura
         * a fracção pelo nome. Foi o que aconteceu às vendas AL e V em
         * 09/08/2026: nasceram a zero e a montra continuou a mostrá-las livres.
         */
        $unidade = $prop->unidade;
        $slug    = dps_propostas_slug($prop->empreendimento);

        if ($slug !== null) {
            $canonica = dps_propostas_chave_catalogo($slug, $prop->unidade);
            if ($canonica !== null) {
                $unidade = $canonica;
            }
        }

        $emp  = $this->db->where('nome', $prop->empreendimento)->get(db_prefix() . 'simulador_empreendimentos')->row();
        $taxa = $emp ? (float) $emp->taxa : 0;
        $comissao = round($valor * $taxa / 100, 2);

        /*
         * Leva-se a ficha do cliente inteira, não só o nome.
         *
         * A venda ia com o nome e mais nada. Quem depois precisava de falar com
         * o comprador — para o CPCV, para a declaração, para marcar a escritura —
         * tinha de voltar à lead à procura do contacto, e a venda ficava a valer
         * menos do que a lead que lhe deu origem. O lead_id é o que fecha o
         * caminho de volta.
         */
        $lead = $this->db->select('name, email, phonenumber')
            ->where('id', (int) $prop->lead_id)
            ->get(db_prefix() . 'leads')->row();

        $this->db->insert(db_prefix() . 'simulador_vendas', [
            'empreendimento'   => $prop->empreendimento,
            'taxa'             => $taxa,
            'unidade'          => $unidade,
            'cliente'          => $lead ? $lead->name : '',
            'cliente_email'    => $lead ? ($lead->email ?: null) : null,
            'cliente_telefone' => $lead ? ($lead->phonenumber ?: null) : null,
            'lead_id'          => (int) $prop->lead_id ?: null,
            'valor'            => $valor,
            'comissao_total'   => $comissao,
            'data_venda'       => date('Y-m-d'),
            'staff_id'         => (int) $prop->staff_id,
            'date_created'     => date('Y-m-d H:i:s'),
            'created_by'       => get_staff_user_id(),
        ]);

        $venda_id = $this->db->insert_id();

        /*
         * Marcar a fracção na montra, como faz o quadro de reserva.
         *
         * Aceitar uma proposta É uma reserva: a unidade sai do mercado nesse
         * instante. Mas esta função cria a venda com um INSERT directo e nunca
         * passava pelo quadro, que é quem chamava a sincronização — resultado,
         * a montra continuava a mostrar disponível uma fracção já vendida, e
         * outro comercial podia prometê-la a outro cliente nos minutos
         * seguintes. Aconteceu com as fracções AL e V do Douro Mar em
         * 09/08/2026.
         */
        $na_montra = false;

        if (file_exists(FCPATH . 'modules/dps_vendas/models/Dps_vendas_model.php')) {
            try {
                $this->load->model('dps_vendas/dps_vendas_model');
                $na_montra = (bool) $this->dps_vendas_model->sincronizar_unidade_simulador(
                    $prop->empreendimento,
                    $unidade,
                    'reservado'
                );
            } catch (\Throwable $e) {
                log_activity('Propostas: falha ao marcar ' . $unidade . ' na montra — ' . $e->getMessage());
            }
        }

        if (!$na_montra) {
            log_activity('Propostas: venda ' . $venda_id . ' criada, mas a fracção "'
                . $unidade . '" NÃO foi marcada no simulador.');
        }

        /*
         * Avisar a direcção. Aceitar uma proposta cria uma reserva, e uma
         * reserva tem de dar sinal — era o que já acontecia quando o cliente
         * reservava no simulador, e não acontecia por aqui.
         *
         * A função avisa pelo sino, por email e por WhatsApp, e salta quem
         * estiver a fazer a operação: um administrador que aceite a proposta
         * não precisa de mensagem a dizer-lhe o que acabou de fazer.
         */
        if (function_exists('dps_vendas_notificar_admins')) {
            $aviso = 'Nova reserva: ' . $prop->empreendimento . ' ' . $unidade
                   . ' — ' . ($lead ? $lead->name : 'cliente');

            dps_vendas_notificar_admins($aviso, 'dps_vendas/view/' . $venda_id);

            /*
             * E o comercial da proposta, quando não é ele a aceitar. Sem isto,
             * um administrador que aceitasse a proposta de um colega criava-lhe
             * uma venda sem o avisar.
             */
            $dono = (int) $prop->staff_id;

            if ($dono > 0 && $dono !== (int) get_staff_user_id() && !is_admin($dono)) {
                dps_vendas_notificar($dono, $aviso, 'dps_vendas/view/' . $venda_id);
            }
        }

        return [
            'id'        => $venda_id,
            'comissao'  => $comissao,
            'taxa'      => $taxa,
            // O valor resolvido (do catálogo, quando não veio de fora): quem
            // chama precisa dele para gravar na proposta e para a mensagem.
            'valor'     => $valor,
            'na_montra' => $na_montra,
        ];
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
        // Normalizado a entrada: o simulador tanto manda a chave como o nome.
        $emp       = dps_propostas_nome_canonico($this->input->post('empreendimento'));
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
        $rec = $this->dps_wa_recibo($raw);

        $this->db->insert(db_prefix() . 'dps_propostas', $rec + [
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

        /*
         * Nota na ficha com a fracção enviada, para qualquer empreendimento —
         * era só o Boavista a tê-la, e três semanas depois ninguém sabe o que
         * foi proposto a quem.
         */
        $this->db->insert(db_prefix() . 'notes', [
            'rel_id'      => $lead_id,
            'rel_type'    => 'lead',
            'description' => 'Proposta enviada — ' . $emp . ($unidade !== '' ? ' — Fração ' . $unidade : ''),
            'dateadded'   => date('Y-m-d H:i:s'),
            'addedfrom'   => $staff_id,
        ]);

        // E a lead passa a "Propostas Enviadas", venha de onde vier.
        if ($ok) {
            $this->dps_promover_proposta_enviada($lead_id, $lead->status);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Proposta enviada ao cliente e registada.' : dps_propostas_erro_wa($r, $number),
        ]);
    }


    /**
     * Id da mensagem que o WhatsApp devolve, e o estado inicial.
     *
     * Sem isto os recibos de entrega chegam a dps_wa_status.php e não têm a
     * que se agarrar: ficam no diário de eventos e a proposta continua eterna
     * em "PENDING". É esta chave que liga uma coisa à outra.
     */
    private function dps_wa_recibo($raw)
    {
        $j = json_decode((string) $raw, true);

        return [
            'wa_msg_id' => $j['key']['id'] ?? null,
            'wa_status' => strtoupper((string) ($j['status'] ?? 'PENDING')) ?: 'PENDING',
        ];
    }

    /**
     * Enviar proposta é um passo em frente no funil: a lead passa a
     * "Propostas Enviadas".
     *
     * Não recua ninguém — quem já está em crédito, contrato ou concretizado
     * está mais à frente, e descê-lo para registar um progresso seria
     * estragar o funil. Antes disto só o Boavista mexia no estado, e mesmo
     * esse punha a lead em VIP 1: quem enviava proposta de outro
     * empreendimento via a lead ficar em "Novos" como se nada fosse.
     */
    private function dps_promover_proposta_enviada($lead_id, $estado_actual)
    {
        $a_frente = [20, 21, 10, 13];   // propostas enviadas, crédito, contrato, concretizado

        if (in_array((int) $estado_actual, $a_frente, true)) {
            return;
        }

        $this->dps_set_lead_status((int) $lead_id, 20);
    }

    /**
     * Passa a lead a VIP 1, sem nunca a fazer recuar.
     *
     * Enviar disponibilidades é um passo em frente, mas quem já está em
     * proposta enviada, em contrato ou concretizado está mais à frente do que
     * VIP 1 — descê-los seria estragar o funil para registar um progresso.
     */
    private function dps_promover_vip1($lead_id)
    {
        $VIP1 = 17;

        // Estados que já estão em VIP 1 ou depois dele.
        $ja_a_frente = [
            17,  // VIP 1
            14,  // VIP 2
            18,  // VIP 3
            20,  // PROPOSTAS ENVIADAS
            21,  // Crédito
            10,  // PARA CONTRATO
            13,  // CONCRETIZADO
        ];

        $lead = $this->db->select('status')->where('id', (int) $lead_id)
            ->get(db_prefix() . 'leads')->row();

        if (! $lead || in_array((int) $lead->status, $ja_a_frente, true)) {
            return;
        }

        $this->dps_set_lead_status((int) $lead_id, $VIP1);
    }

}
