<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reuniões online.
 *
 * Marcar, ver, responder a um convite e fechar. Nada aqui exige permissões
 * novas: quem pode abrir a lead ou o cliente pode marcar-lhe uma reunião.
 */
class Dps_reunioes extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_staff_logged_in()) {
            access_denied('dps_reunioes');
        }

        $this->load->model('dps_reunioes_model');
    }

    public function index()
    {
        $data['reunioes'] = $this->db
            ->select('r.*, CONCAT(s.firstname," ",s.lastname) AS comercial')
            ->from(db_prefix() . 'dps_reunioes r')
            ->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left')
            ->order_by('r.data_hora', 'DESC')
            ->limit(200)
            ->get()->result_array();

        $data['title'] = 'Reuniões online';
        $this->load->view('lista', $data);
    }

    /**
     * Marca a reunião. Só POST — é uma escrita.
     */
    public function marcar()
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $post = $this->input->post();

        $data_hora = trim((string) ($post['data'] ?? '')) . ' ' . trim((string) ($post['hora'] ?? '')) . ':00';
        if (strtotime($data_hora) === false) {
            set_alert('danger', 'Data ou hora inválidas.');
            redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
        }

        /*
         * Marcar para trás não é erro de digitação nenhum: é uma reunião que
         * nunca vai acontecer, e o lembrete dos 30 minutos já não dispara.
         * Mais vale recusar já do que deixar o comercial à espera.
         */
        if (strtotime($data_hora) < time()) {
            set_alert('warning', 'Essa data já passou. Escolha uma data futura.');
            redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
        }

        /*
         * QUEM PUBLICOU AGENDA MANDA NA SUA AGENDA.
         *
         * Se se convida alguém que publicou horários, a reunião tem de cair
         * num deles. Sem isto, a agenda partilhada era decorativa: o colega
         * definia "quartas das 14h às 18h" e recebia na mesma um convite para
         * sexta às 8h — e quem tem a agenda cheia é precisamente quem a
         * publica. Pedido do dono (03/08/2026).
         *
         * Só se valida quando a pessoa publicou. Quem não publicou continua a
         * poder ser convidado a qualquer hora, como sempre.
         */
        $convidado = (int) ($post['convidado_id'] ?? 0);

        if ($convidado > 0) {
            $regras = $this->dps_reunioes_model->get_partilha($convidado);

            if (!empty($regras['publicada'])) {
                $quando  = date('Y-m-d H:i:s', strtotime($data_hora));
                $duracao = (int) ($post['duracao_min'] ?? $regras['duracao_min']);

                if (!$this->dps_reunioes_model->slot_valido($convidado, $quando, $duracao)) {
                    $nome = get_staff_full_name($convidado);

                    set_alert('warning',
                        $nome . ' só aceita reuniões nos horários que publicou, e '
                        . dps_reunioes_quando($quando) . ' não é um deles. '
                        . 'Escolha uma hora livre na agenda dele — nada foi marcado.');

                    redirect(admin_url('dps_reunioes/agenda/' . $convidado));
                }
            }
        }

        $id = $this->dps_reunioes_model->criar([
            'rel_type'         => $post['rel_type'] ?? 'lead',
            'rel_id'           => (int) ($post['rel_id'] ?? 0),
            'assunto'          => $post['assunto'] ?? '',
            'data_hora'        => date('Y-m-d H:i:s', strtotime($data_hora)),
            'duracao_min'      => (int) ($post['duracao_min'] ?? 30),
            'staff_id'         => (int) ($post['staff_id'] ?? get_staff_user_id()),
            'convidado_id'     => $post['convidado_id'] ?? null,
            'cliente_nome'     => $post['cliente_nome'] ?? '',
            'cliente_email'    => $post['cliente_email'] ?? '',
            'cliente_telefone' => $post['cliente_telefone'] ?? '',
        ]);

        if (!$id) {
            set_alert('danger', 'Não foi possível marcar a reunião.');
            redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
        }

        $r = $this->dps_reunioes_model->get($id);

        dps_reunioes_avisar_cliente($r, 'marcada');
        dps_reunioes_convidar($r);

        /*
         * Vai também para o Google Calendar do comercial, se ele tiver a conta
         * ligada. O cliente entra como convidado e recebe o convite do próprio
         * Google — com o lembrete dele, no telemóvel dele.
         *
         * Guarda-se o id do evento para que uma alteração o encontre em vez de
         * criar um segundo ao lado. Falhando, a reunião fica marcada na mesma:
         * o calendário é um extra, não a fonte da verdade.
         */
        if (function_exists('dps_google_evento_guardar')) {
            $convidados = [$r['cliente_email']];
            if (!empty($r['convidado_id'])) {
                $convidados[] = $this->db->select('email')->where('staffid', (int) $r['convidado_id'])
                                         ->get(db_prefix() . 'staff')->row('email');
            }

            $ev_id = dps_google_evento_guardar((int) $r['staff_id'], [
                'titulo'     => 'Reunião — ' . $r['cliente_nome'],
                'descricao'  => "Reunião online marcada no CRM.\n\nEntrar: " . $r['link']
                                . "\nFicha: " . admin_url('dps_reunioes/ver/' . (int) $r['id']),
                'inicio'     => $r['data_hora'],
                'fim'        => date('Y-m-d H:i:s',
                                    strtotime($r['data_hora']) + ((int) $r['duracao_min'] * 60)),
                'local'      => $r['link'],
                'convidados' => array_filter($convidados),
            ]);

            if ($ev_id) {
                $this->dps_reunioes_model->actualizar($id, ['google_event_id' => $ev_id]);
            }
        }

        log_activity('Reunião #' . $id . ' marcada com ' . $r['cliente_nome']
            . ' para ' . dps_reunioes_quando($r['data_hora']));

        set_alert('success', 'Reunião marcada. O cliente foi avisado por email e WhatsApp.');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dps_reunioes'));
    }

    public function ver($id)
    {
        $data['r'] = $this->dps_reunioes_model->get($id);
        if (!$data['r']) {
            show_404();
        }

        // Reuniões internas não têm cliente: o que interessa mostrar são as
        // pessoas de casa que lá vão estar.
        $data['participantes'] = $this->dps_reunioes_model->participantes($id);
        $data['interna']       = ($data['r']['rel_type'] ?? '') === 'interna';

        $data['title'] = $data['interna']
            ? $data['r']['assunto']
            : 'Reunião com ' . $data['r']['cliente_nome'];

        $this->load->view('ver', $data);
    }

    /**
     * O convidado aceita ou recusa. Só ele — nem o comercial responde por ele.
     */
    public function responder($id, $resposta)
    {
        $r = $this->dps_reunioes_model->get($id);
        if (!$r) {
            show_404();
        }

        if ((int) $r['convidado_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_reunioes');
        }

        if (!in_array($resposta, ['aceite', 'recusado'], true)) {
            show_404();
        }

        $this->dps_reunioes_model->actualizar($id, [
            'convite_estado' => $resposta,
            'convite_em'     => date('Y-m-d H:i:s'),
        ]);

        add_notification([
            'description' => get_staff_full_name() . ' ' . $resposta . ' o convite para a reunião com '
                . $r['cliente_nome'] . '.',
            'touserid'    => (int) $r['staff_id'],
            'link'        => 'dps_reunioes/ver/' . (int) $id,
            'fromcompany' => true,
        ]);

        set_alert('success', 'Resposta registada.');
        redirect(admin_url('dps_reunioes/ver/' . (int) $id));
    }

    /**
     * Fecha a reunião: realizada ou não compareceu, com a duração.
     *
     * É à mão de propósito. Nem o Jitsi nem o Meet dizem ao CRM quem entrou e
     * quanto tempo ficou sem infraestrutura própria a ouvir os eventos da
     * sala — prometer isso automático era prometer um número inventado.
     */
    /**
     * Ao cancelar, a reunião sai também da agenda de toda a gente — senão
     * ficava lá um compromisso que já não existe, e no Google também.
     */
    private function limpar_agenda_se_cancelada($id, $estado)
    {
        if ($estado === 'cancelada' && function_exists('dps_reunioes_apagar_eventos')) {
            dps_reunioes_apagar_eventos((int) $id);
        }
    }

    public function fechar($id)
    {
        if ($this->input->method() !== 'post') {
            show_404();
        }

        $r = $this->dps_reunioes_model->get($id);
        if (!$r) {
            show_404();
        }

        $estado = $this->input->post('estado');
        if (!in_array($estado, ['realizada', 'nao_compareceu', 'cancelada'], true)) {
            show_404();
        }

        $this->dps_reunioes_model->actualizar($id, [
            'estado'           => $estado,
            'duracao_real_min' => (int) $this->input->post('duracao_real_min') ?: null,
            'notas'            => $this->input->post('notas'),
        ]);

        set_alert('success', 'Reunião actualizada.');
        $this->limpar_agenda_se_cancelada($id, $estado);

        redirect(admin_url('dps_reunioes/ver/' . (int) $id));
    }

    /**
     * Página própria de marcação.
     *
     * Existe porque os botões que injectei por JavaScript na janela da lead
     * nunca apareceram: este tema reconstrói a barra de separadores e deita
     * fora o que lá se pendura. Um link para uma página não depende de nada
     * disso — e serve tanto a lista de leads como a ficha.
     */
    public function nova($rel_type = 'lead', $rel_id = 0)
    {
        $rel_type = in_array($rel_type, ['lead', 'customer'], true) ? $rel_type : 'lead';
        $rel_id   = (int) $rel_id;

        if (!$rel_id) {
            show_404();
        }

        if ($rel_type === 'lead') {
            $f = $this->db->select('name, email, phonenumber')->where('id', $rel_id)
                          ->get(db_prefix() . 'leads')->row_array();
            $nome = $f['name'] ?? '';
            $mail = $f['email'] ?? '';
            $tel  = $f['phonenumber'] ?? '';
        } else {
            $c = $this->db->select('company, phonenumber')->where('userid', $rel_id)
                          ->get(db_prefix() . 'clients')->row_array();
            $ct = $this->db->select('firstname, lastname, email, phonenumber')
                           ->where('userid', $rel_id)->where('is_primary', 1)
                           ->get(db_prefix() . 'contacts')->row_array();
            $nome = trim((string) ($c['company'] ?? ''))
                    ?: trim(($ct['firstname'] ?? '') . ' ' . ($ct['lastname'] ?? ''));
            $mail = $ct['email'] ?? '';
            $tel  = $ct['phonenumber'] ?? ($c['phonenumber'] ?? '');
        }

        if (!$nome && !$mail && !$tel) {
            show_404();
        }

        $data = [
            'rel_type'  => $rel_type,
            'rel_id'    => $rel_id,
            'pre_nome'  => $nome,
            'pre_email' => $mail,
            'pre_tel'   => $tel,
            'title'     => 'Marcar reunião — ' . $nome,
        ];

        $this->load->view('nova', $data);
    }

    /* =====================================================================
     * AGENDA PARTILHADA
     * ================================================================== */

    /**
     * A minha disponibilidade: horário semanal, excepções e regras.
     *
     * Cada um trata da sua. Não há aqui "editar a agenda de outro" de
     * propósito — quem define quando aceita reuniões é quem as vai ter.
     */
    public function disponibilidade()
    {
        $eu = (int) get_staff_user_id();

        if ($this->input->post('accao') === 'guardar') {
            $blocos = [];

            /*
             * O formulário devolve três vectores paralelos (dia, início, fim).
             * Percorre-se pelo dia, que é o que sempre existe: percorrer pelas
             * horas deixava cair a última linha quando ela vinha vazia.
             */
            $dias  = (array) $this->input->post('dia_semana');
            $inis  = (array) $this->input->post('hora_inicio');
            $fins  = (array) $this->input->post('hora_fim');

            foreach ($dias as $i => $dia) {
                $blocos[] = [
                    'dia_semana'  => $dia,
                    'hora_inicio' => $inis[$i] ?? '',
                    'hora_fim'    => $fins[$i] ?? '',
                ];
            }

            $this->dps_reunioes_model->guardar_horario($eu, $blocos);
            $this->dps_reunioes_model->guardar_partilha($eu, $this->input->post());

            set_alert('success', 'Disponibilidade guardada.');
            redirect(admin_url('dps_reunioes/disponibilidade'));
        }

        if ($this->input->post('accao') === 'bloquear') {
            $ok = $this->dps_reunioes_model->add_bloqueio(
                $eu,
                $this->data_iso($this->input->post('data')),
                $this->input->post('bl_inicio') ?: null,
                $this->input->post('bl_fim') ?: null,
                $this->input->post('motivo')
            );

            set_alert($ok ? 'success' : 'danger',
                $ok ? 'Período bloqueado.' : 'Não consegui bloquear — verifique a data e as horas.');
            redirect(admin_url('dps_reunioes/disponibilidade'));
        }

        $data['partilha']  = $this->dps_reunioes_model->get_partilha($eu);
        $data['horario']   = $this->dps_reunioes_model->get_horario($eu);
        $data['bloqueios'] = $this->dps_reunioes_model->get_bloqueios($eu, date('Y-m-d'));
        $data['livres']    = $this->dps_reunioes_model->slots_livres($eu);
        $data['title']     = 'A minha disponibilidade';

        $this->load->view('disponibilidade', $data);
    }

    public function desbloquear($id)
    {
        $this->dps_reunioes_model->del_bloqueio($id, get_staff_user_id());
        set_alert('success', 'Bloqueio removido.');
        redirect(admin_url('dps_reunioes/disponibilidade'));
    }

    /**
     * A agenda de um colega, como o Calendly: os horários livres, para escolher.
     */
    public function agenda($staff_id = null)
    {
        $eu       = (int) get_staff_user_id();
        $staff_id = (int) $staff_id;

        $data['agendas'] = $this->dps_reunioes_model->agendas_publicadas($eu);

        if ($staff_id) {
            $regras = $this->dps_reunioes_model->get_partilha($staff_id);

            if (empty($regras['publicada'])) {
                set_alert('warning', 'Essa pessoa ainda não publicou a agenda.');
                redirect(admin_url('dps_reunioes/agenda'));
            }

            $data['dono']   = $this->db->select('staffid, CONCAT(firstname," ",lastname) AS nome')
                                       ->where('staffid', $staff_id)
                                       ->get(db_prefix() . 'staff')->row_array();
            $data['regras'] = $regras;
            $data['livres'] = $this->dps_reunioes_model->slots_livres($staff_id);
        }

        $data['staff_id'] = $staff_id;
        $data['title']    = 'Agenda partilhada';

        $this->load->view('agenda', $data);
    }

    /**
     * Marca a reunião no horário escolhido.
     *
     * Confirma-se o horário OUTRA VEZ aqui. Entre desenhar a página e carregar
     * no botão pode ter entrado outra reunião — e duas pessoas com a mesma
     * página aberta escolhem invariavelmente a mesma hora.
     */
    public function agendar()
    {
        if (!$this->input->post()) {
            show_404();
        }

        $eu       = (int) get_staff_user_id();
        $dono     = (int) $this->input->post('staff_id');
        $inicio   = (int) $this->input->post('inicio');
        $assunto  = trim((string) $this->input->post('assunto'));

        $regras  = $this->dps_reunioes_model->get_partilha($dono);
        $duracao = (int) $regras['duracao_min'];

        if (!$dono || !$inicio || empty($regras['publicada'])) {
            set_alert('danger', 'Pedido incompleto.');
            redirect(admin_url('dps_reunioes/agenda'));
        }

        $data_hora = date('Y-m-d H:i:s', $inicio);

        if (!$this->dps_reunioes_model->slot_valido($dono, $data_hora, $duracao)) {
            set_alert('danger', 'Esse horário deixou de estar livre. Escolha outro — nada foi marcado.');
            redirect(admin_url('dps_reunioes/agenda/' . $dono));
        }

        $nome_eu = get_staff_full_name($eu);

        /*
         * Quem marca é o anfitrião da reunião (staff_id) e o dono da agenda
         * entra como convidado: é a leitura certa do que aconteceu — o
         * comercial pediu tempo ao Cláudio, não o contrário.
         */
        $id = $this->dps_reunioes_model->criar([
            'rel_type'     => 'interna',
            'rel_id'       => 0,
            'assunto'      => $assunto ?: ('Reunião interna — ' . $nome_eu),
            'data_hora'    => $data_hora,
            'duracao_min'  => $duracao,
            'staff_id'     => $eu,
            'convidado_id' => $dono,
            'cliente_nome' => 'Reunião interna',
        ]);

        if (!$id) {
            set_alert('danger', 'Não consegui marcar a reunião.');
            redirect(admin_url('dps_reunioes/agenda/' . $dono));
        }

        $this->dps_reunioes_model->set_participantes($id, [$dono]);

        $r = $this->dps_reunioes_model->get($id);
        dps_reunioes_avisar_interno($r, [$dono], 'marcada');

        set_alert('success', 'Reunião marcada para ' . dps_reunioes_quando($data_hora) . '.');
        redirect(admin_url('dps_reunioes/ver/' . $id));
    }

    /**
     * Reunião de equipa: convidar alguns ou toda a gente.
     */
    public function equipa()
    {
        $eu = (int) get_staff_user_id();

        if ($this->input->post()) {
            $quando  = $this->input->post('data');
            $hora    = $this->input->post('hora');
            $duracao = (int) $this->input->post('duracao_min') ?: 45;
            $assunto = trim((string) $this->input->post('assunto'));
            $alcance = $this->input->post('alcance');

            $convidados = $alcance === 'todos'
                ? array_column($this->equipa_activa(), 'staffid')
                : array_map('intval', (array) $this->input->post('participantes'));

            $convidados = array_values(array_diff(array_map('intval', $convidados), [$eu]));

            $quando    = $this->data_iso($quando);
            $data_hora = $quando . ' ' . ($hora ?: '00:00') . ':00';

            if (!$quando || !$hora) {
                set_alert('danger', 'Falta a data ou a hora.');
                redirect(admin_url('dps_reunioes/equipa'));
            }
            if (empty($convidados)) {
                set_alert('danger', 'Escolha pelo menos uma pessoa.');
                redirect(admin_url('dps_reunioes/equipa'));
            }
            if (strtotime($data_hora) < time()) {
                set_alert('danger', 'Essa data já passou.');
                redirect(admin_url('dps_reunioes/equipa'));
            }

            $id = $this->dps_reunioes_model->criar([
                'rel_type'     => 'interna',
                'rel_id'       => 0,
                'assunto'      => $assunto ?: 'Reunião de equipa',
                'data_hora'    => $data_hora,
                'duracao_min'  => $duracao,
                'staff_id'     => $eu,
                'cliente_nome' => 'Reunião de equipa',
            ]);

            if (!$id) {
                set_alert('danger', 'Não consegui criar a reunião.');
                redirect(admin_url('dps_reunioes/equipa'));
            }

            $this->dps_reunioes_model->set_participantes($id, $convidados);

            $r = $this->dps_reunioes_model->get($id);
            dps_reunioes_avisar_interno($r, $convidados, 'equipa');

            set_alert('success', 'Reunião de equipa marcada — ' . count($convidados) . ' convidado(s).');
            redirect(admin_url('dps_reunioes/ver/' . $id));
        }

        $data['equipa']   = $this->equipa_activa();
        $data['internas'] = $this->dps_reunioes_model->minhas_internas($eu);
        $data['title']    = 'Reunião de equipa';

        $this->load->view('equipa', $data);
    }

    /**
     * Aceita só AAAA-MM-DD, que é o que os campos <input type="date"> mandam.
     *
     * Não se usa to_sql_date(): essa função converte a partir do formato de
     * data configurado no CRM (dd/mm/aaaa) e, aplicada a uma data que já vem
     * em ISO, troca o dia pelo mês em silêncio nos primeiros doze dias do mês.
     */
    private function data_iso($valor)
    {
        $valor = trim((string) $valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : '';
    }

    /** Toda a gente activa, menos eu — quem marca já lá está. */
    private function equipa_activa()
    {
        return $this->db->select('staffid, CONCAT(firstname," ",lastname) AS nome, email')
                        ->where('active', 1)
                        ->where('staffid <>', (int) get_staff_user_id())
                        ->order_by('firstname')
                        ->get(db_prefix() . 'staff')->result_array();
    }
}
