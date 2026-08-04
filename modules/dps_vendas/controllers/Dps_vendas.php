<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_vendas extends AdminController
{
    /** Cartão de Cidadão + comprovativos: imagens e PDF, nada mais. */
    private $extensoes_permitidas = ['pdf', 'jpg', 'jpeg', 'png'];

    private $mimes_permitidos = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    private $tamanho_maximo = 8388608; // 8 MB

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_vendas_model');
    }

    /* ---------------------------------------------------------------------
     * Vendas
     * ------------------------------------------------------------------ */

    /**
     * Abre o simulador (dpsimobiliario.pt) já com a identidade do comercial.
     *
     * O botão da barra lateral aponta para aqui em vez de ir direto ao
     * simulador: como o simulador não tem sessão do CRM, é este redirect —
     * feito com o utilizador já autenticado no CRM — que lhe diz quem é.
     * Passa `crm=1` (o simulador salta o ecrã de password) e `staff_id`
     * (identifica o comercial para reservas/propostas). Nota de honestidade:
     * isto é conveniência de interface, não segurança — o staff_id vai em
     * claro no URL, tal como o link pessoal.
     */
    public function simulador()
    {
        $id  = (int) get_staff_user_id();
        $url = 'https://dpsimobiliario.pt/simuladorportugal/?crm=1&staff_id=' . $id;

        redirect($url);
    }

    /* ---------------------------------------------------------------------
     * Quadro de reserva a partir de uma lead
     *
     * Chega-se aqui quando a lead passa a "PARA CONTRATO". Escolhe-se a
     * unidade entre as que o simulador dá como disponíveis e a venda nasce no
     * mapa, já com o cliente preenchido pela lead e a fração marcada como
     * reservada na montra.
     * ------------------------------------------------------------------ */

    /**
     * Responde ao JS do rodapé: há alguma lead à espera de reserva?
     */
    public function reserva_pendente()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['lead_id' => dps_vendas_reserva_pendente()]);
        exit;
    }

    public function reserva($lead_id = null)
    {
        $lead_id = (int) $lead_id;

        if (!is_admin() && !staff_can('create', 'dps_vendas')) {
            access_denied('dps_vendas');
        }

        $this->load->model('leads_model');
        $lead = $lead_id ? $this->leads_model->get($lead_id) : null;

        if (!$lead) {
            set_alert('warning', 'Lead não encontrada.');
            redirect(admin_url('leads'));
        }

        $this->load->helper('dps_propostas/dps_propostas');

        if ($this->input->post()) {
            $slug    = (string) $this->input->post('slug');
            $unidade = trim((string) $this->input->post('unidade'));
            $valor   = (string) $this->input->post('valor');

            $erro = $this->validar_reserva($slug, $unidade);

            if ($erro) {
                set_alert('danger', $erro);
                redirect(admin_url('dps_vendas/reserva/' . $lead_id));
            }

            $emps  = dps_propostas_empreendimentos();
            $nome  = $this->nome_empreendimento_crm($slug, $emps[$slug]['nome']);

            $venda_id = $this->dps_vendas_model->add_venda([
                'empreendimento'   => $nome,
                'unidade'          => $unidade,
                'cliente'          => $lead->name,
                'cliente_email'    => $lead->email,
                'cliente_telefone' => $lead->phonenumber,
                'valor'            => $valor,
                'data_venda'       => _d(date('Y-m-d')),
                'origem'           => 'manual',
                'lead_id'          => $lead_id,
                // A venda é do dono da lead, não de quem carregou no botão: um
                // admin a arrumar o kanban não fica com a venda do colega.
                'staff_id'         => $lead->assigned ?: get_staff_user_id(),
            ]);

            if (!$venda_id) {
                set_alert('danger', 'Não consegui criar a venda.');
                redirect(admin_url('dps_vendas/reserva/' . $lead_id));
            }

            /*
             * Marcar a fração na montra. A venda fica em "Pendente" (só a
             * direção promove estados), mas o simulador tem de a mostrar já
             * como reservada — senão outro comercial vende a mesma unidade
             * nos minutos seguintes. É o mesmo que o simulador faz quando a
             * reserva é feita de lá.
             */
            $na_montra = $this->dps_vendas_model
                ->sincronizar_unidade_simulador($nome, $unidade, 'reservado');

            if ($na_montra) {
                set_alert('success', 'Reserva criada e fração marcada no simulador.');
            } else {
                set_alert('warning', 'Reserva criada, mas NÃO consegui marcar a fração '
                    . $unidade . ' no simulador — marque-a à mão no Modo de Edição '
                    . 'antes que outro comercial a venda.');
            }

            log_activity('Reserva criada a partir da lead #' . $lead_id
                . ' (' . $nome . ' ' . $unidade . ') — venda #' . $venda_id);

            redirect(admin_url('dps_vendas/view/' . $venda_id));
        }

        $data['lead']            = $lead;
        $data['empreendimentos'] = dps_propostas_empreendimentos();
        $data['slug']            = (string) $this->input->get('emp');
        $data['disponibilidade'] = null;

        if ($data['slug'] && isset($data['empreendimentos'][$data['slug']])) {
            $data['disponibilidade'] = dps_propostas_disponibilidade($data['slug']);
        }

        $data['title'] = 'Reserva — ' . $lead->name;

        $this->load->view('reserva', $data);
    }

    /**
     * A unidade ainda está livre? Relê o simulador em vez de confiar no que o
     * ecrã mostrava: entre abrir o quadro e carregar em confirmar podem ter
     * passado minutos, e é exactamente aí que duas reservas se cruzam.
     */
    private function validar_reserva($slug, $unidade)
    {
        $emps = dps_propostas_empreendimentos();

        if (!isset($emps[$slug])) {
            return 'Empreendimento desconhecido.';
        }
        if ($unidade === '') {
            return 'Escolha a unidade.';
        }

        $disp = dps_propostas_disponibilidade($slug);

        if (empty($disp['ok'])) {
            return 'Não consegui confirmar as unidades disponíveis no simulador. '
                 . 'Nada foi reservado — tente de novo dentro de instantes.';
        }
        if (!in_array($unidade, (array) $disp['codes'], true)) {
            return 'A fração ' . $unidade . ' já não está disponível. '
                 . 'Volte a abrir a lista e escolha outra.';
        }

        return null;
    }

    /**
     * Nome do empreendimento tal como as Regras de Comissão o escrevem.
     *
     * Importa: é por este nome que add_venda() vai buscar a taxa. Se gravasse
     * o nome do catálogo de propostas ("Belo Horizonte") e a regra estivesse
     * como "BELO HORIZONTE — Setúbal", a venda nascia sem taxa e com comissão
     * a zero.
     */
    private function nome_empreendimento_crm($slug, $omissao)
    {
        $chave = Dps_vendas_model::chave_empreendimento($omissao);

        foreach ($this->dps_vendas_model->get_empreendimentos() as $nome) {
            $nome = is_array($nome) ? ($nome['empreendimento'] ?? '') : $nome;
            if ($nome !== '' && Dps_vendas_model::chave_empreendimento($nome) === $chave) {
                return $nome;
            }
        }

        return $omissao;
    }

    public function index()
    {
        $filtros = [
            'estado'         => $this->input->get('estado'),
            'empreendimento' => $this->input->get('empreendimento'),
            'comercial_id'   => $this->input->get('comercial_id'),
        ];

        $data['vendas']          = $this->dps_vendas_model->get_vendas($filtros, !$this->pode_ver_todas());
        $data['empreendimentos'] = $this->dps_vendas_model->get_empreendimentos();
        $data['comerciais']      = $this->get_comerciais();
        $data['filtros']         = $filtros;
        $data['pode_ver_todas']  = $this->pode_ver_todas();
        $data['title']           = 'Vendas';

        $this->load->view('manage', $data);
    }

    public function form($id = null)
    {
        if (!$id && !is_admin() && !staff_can('create', 'dps_vendas')) {
            access_denied('dps_vendas');
        }

        if ($this->input->post()) {
            $post = $this->input->post();

            $erros = $this->validar_venda($post, $id);

            if (empty($erros)) {
                if ($id) {
                    if (!$this->pode_mexer($id)) {
                        access_denied('dps_vendas');
                    }

                    $this->dps_vendas_model->update_venda($id, $post);
                    $venda_id = $id;
                    set_alert('success', 'Venda actualizada.');
                } else {
                    $venda_id = $this->dps_vendas_model->add_venda($post);
                    set_alert('success', 'Venda criada em Pendente.');
                }

                if ($venda_id) {
                    $erro_upload = $this->processar_uploads($venda_id);
                    if ($erro_upload) {
                        set_alert('warning', 'Venda guardada, mas houve um problema com os documentos: ' . $erro_upload);
                    }

                    redirect(admin_url('dps_vendas/view/' . $venda_id));
                }
            } else {
                set_alert('danger', implode('<br>', $erros));
            }
        }

        if ($id) {
            $data['venda'] = $this->dps_vendas_model->get_venda($id);
            if (!$data['venda']) {
                show_404();
            }

            /*
             * O form não é só escrita: mostra o Valor da venda e a Taxa de
             * comissão, ou seja a comissão do colega (valor x taxa). Por isso
             * tem de passar pelo MESMO crivo de leitura do view(), e não só
             * pelo pode_mexer().
             *
             * Sem isto, quem tem escrita mas não leitura global (um admin do
             * CRM sem o mapa completo) via /dps_vendas/view/57 bloqueado mas
             * abria /dps_vendas/form/57 e lia lá os mesmos números — e os ids
             * de tblsimulador_vendas são sequenciais, logo percorrer a casa
             * toda era trivial.
             */
            if (!$this->pode_ver($data['venda']) || !$this->pode_mexer($id)) {
                access_denied('dps_vendas');
            }

            $data['docs']  = $this->dps_vendas_model->get_docs($id);
            $data['title'] = 'Editar Venda';
        } else {
            $data['venda'] = null;
            $data['docs']  = [];
            $data['title'] = 'Nova Venda';
        }

        $data['empreendimentos'] = $this->dps_vendas_model->get_empreendimentos();
        $data['regras']          = $this->dps_vendas_model->get_regras();
        $data['comerciais']      = $this->get_comerciais();

        $this->load->view('form', $data);
    }

    public function view($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!$this->pode_ver($venda)) {
            access_denied('dps_vendas');
        }

        $data['venda']     = $venda;
        $data['docs']      = $this->dps_vendas_model->get_docs($id);
        $data['historico'] = $this->dps_vendas_model->get_historico($id);
        $data['calculo']   = $this->dps_vendas_model->calcular_comissao($venda);
        $data['fluxo']     = Dps_vendas_model::$fluxo;
        $data['pode_download'] = is_admin() || staff_can('download_docs', 'dps_vendas');
        $data['title']     = 'Venda #' . $id;

        $this->load->view('view', $data);
    }

    /**
     * Envia a reserva ao promotor por email, com os documentos anexados.
     * O admin escolhe o destinatário.
     */
    public function enviar_email($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        /*
         * O comercial da venda também envia ao promotor.
         *
         * Estava reservado à direção, para o envio não curto-circuitar a
         * validação interna. Na prática atrasava o circuito: depois do CPCV
         * assinado e do comprovativo carregados, quem tem o processo na mão é
         * quem o deve mandar seguir. Regra do dono (01/08/2026).
         *
         * O email leva SEMPRE todos os documentos da venda em anexo — o CPCV
         * assinado e o comprovativo entram sozinhos assim que existam.
         */
        if (!is_admin() && (int) $venda['staff_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_vendas');
        }

        $para = trim((string) $this->input->post('email_para'));
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            set_alert('danger', 'Indique um email de destinatário válido.');
            redirect(admin_url('dps_vendas/view/' . $id));
        }

        $extra = trim((string) $this->input->post('email_mensagem'));

        $linhas = [
            'Empreendimento: ' . $venda['empreendimento'],
            'Unidade/Fracção: ' . $venda['unidade'],
            'Valor: ' . app_format_money($venda['valor'], get_base_currency()),
            'Estado: ' . dps_vendas_nome_estado($venda['estado']),
            '',
            'CLIENTE',
            'Nome: ' . $venda['cliente'],
            'Tipo: ' . (($venda['cliente_tipo'] ?? '') ?: '—'),
        ];
        if (!empty($venda['cliente_crc'])) {
            $linhas[] = 'CRC: ' . $venda['cliente_crc'];
        }
        $linhas = array_merge($linhas, [
            'Morada: ' . ($venda['cliente_morada'] ?: '—'),
            'Código postal: ' . (($venda['cliente_codigo_postal'] ?? '') ?: '—'),
            'Telefone: ' . ($venda['cliente_telefone'] ?: '—'),
            'Email: ' . ($venda['cliente_email'] ?: '—'),
            'Estado civil: ' . ($venda['regime_civil'] ?: '—'),
        ]);

        $corpo = '<p>' . ($extra !== '' ? nl2br(html_escape($extra)) . '<br><br>' : '') . '</p>';
        $corpo .= '<p>' . implode('<br>', array_map('html_escape', $linhas)) . '</p>';
        $corpo .= '<p style="color:#888;font-size:12px;">Enviado por ' . html_escape(get_staff_full_name()) . ' via CRM DPS.</p>';

        $this->load->library('email');
        $this->email->clear(true);
        $this->email->from(get_option('smtp_email') ?: get_option('email'), get_option('companyname') ?: 'DPS');
        $this->email->to($para);
        $this->email->subject('Reserva — ' . $venda['empreendimento'] . ' — Fracção ' . $venda['unidade']);
        $this->email->message($corpo);

        foreach ($this->dps_vendas_model->get_docs($id) as $doc) {
            $caminho = FCPATH . DPS_VENDAS_UPLOAD_PATH . $id . '/' . $doc['filename'];
            if (file_exists($caminho)) {
                $this->email->attach($caminho, 'attachment', $doc['original_name'] ?: $doc['filename']);
            }
        }

        if ($this->email->send(false)) {
            // Enviar o email ao promotor marca automaticamente a venda como
            // Submetida — só a partir de Reservado, para não sobrepor um
            // estado mais avançado se o email for reenviado mais tarde.
            if ($venda['estado'] === 'reservado') {
                $this->dps_vendas_model->mudar_estado($id, 'submetido', 'Reserva enviada por email ao promotor (' . $para . ')');
            }
            set_alert('success', 'Reserva enviada por email para ' . $para . '.');
        } else {
            set_alert('danger', 'Não foi possível enviar o email. Verifique a configuração de SMTP do CRM.');
        }

        redirect(admin_url('dps_vendas/view/' . $id));
    }

    public function change_status($id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        // Só o admin muda estados: as vendas entram como "Pedido — Por
        // Confirmar" e é a direção que as promove (regra definida em validação).
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $resultado = $this->dps_vendas_model->mudar_estado(
            $id,
            $this->input->post('estado'),
            $this->input->post('nota')
        );

        if ($resultado['ok']) {
            if (!empty($resultado['aviso'])) {
                set_alert('warning', $resultado['aviso']);
            } else {
                set_alert('success', 'Estado actualizado.');
            }
        } else {
            set_alert('danger', $resultado['erro']);
        }

        // Quando a mudança vem da listagem, volta-se para lá — obrigar a
        // passar pela ficha a cada alteração tornava a lista inutilizável.
        if ($this->input->post('voltar') === 'lista') {
            redirect(admin_url('dps_vendas'));
        }

        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /**
     * O comercial cancela a venda quando o cliente desiste.
     *
     * PORQUE É SEPARADO DO change_status():
     * Mudar estados é da direcção — uma venda não sobe sozinha de degrau. Mas
     * DESCER, quando o cliente desiste, tem de ser imediato: a unidade está a
     * ocupar lugar na montra e outro comercial pode estar a perder um negócio
     * por ela aparecer reservada. Pedido do dono (04/08/2026).
     *
     * Só cancela quem é dono da venda, ou um admin. E é só para descer: uma
     * venda já concluída não se cancela por aqui — isso é correcção de
     * contabilidade e passa pela direcção.
     *
     * Ao cancelar, o mudar_estado() trata do resto: devolve a fracção a
     * DISPONÍVEL no simulador e regista no histórico quem cancelou e porquê.
     */
    public function cancelar($id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        $eu     = (int) get_staff_user_id();
        $e_dono = (int) $venda['staff_id'] === $eu;

        if (!is_admin() && !$e_dono) {
            access_denied('dps_vendas');
        }

        if ($venda['estado'] === 'cancelado') {
            set_alert('warning', 'Esta venda já estava cancelada.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        if (in_array($venda['estado'], ['vendido', 'concluido'], true) && !is_admin()) {
            set_alert('warning',
                'Esta venda já tem CPCV. Cancelar a partir daqui é decisão da direcção — '
                . 'fale com eles antes de a unidade voltar ao mercado.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        $motivo = trim((string) $this->input->post('motivo'));

        if ($motivo === '') {
            set_alert('warning', 'Diga porque está a cancelar — é o que fica no histórico.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        $resultado = $this->dps_vendas_model->mudar_estado($id, 'cancelado', $motivo);

        if (empty($resultado['ok'])) {
            set_alert('danger', $resultado['erro'] ?? 'Não foi possível cancelar.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        /*
         * A direcção tem de saber, e depressa: uma unidade que volta ao mercado
         * muda o que se pode vender hoje. Vai por notificação no CRM e por
         * WhatsApp a quem tiver número.
         */
        dps_vendas_notificar_admins(
            'Venda CANCELADA — ' . $venda['empreendimento'] . ' Fracção ' . $venda['unidade']
            . ' (' . $venda['cliente'] . '), por ' . get_staff_full_name($eu) . '. '
            . 'Motivo: ' . $motivo . '. A fracção voltou a DISPONÍVEL no simulador.',
            'dps_vendas/view/' . (int) $id
        );

        log_activity('DPS Vendas: venda #' . (int) $id . ' cancelada por staff ' . $eu . ' — ' . $motivo);

        $aviso = !empty($resultado['aviso']) ? ' ' . $resultado['aviso'] : '';
        set_alert('success',
            'Venda cancelada. A fracção ' . $venda['unidade']
            . ' voltou a estar disponível no simulador e a direcção foi avisada.' . $aviso);

        redirect(admin_url('dps_vendas/view/' . (int) $id));
    }

    public function delete($id)
    {
        // Só admin: os registos de venda devem manter-se na listagem para os
        // comerciais (pedido da validação) — eliminar fica reservado à direção.
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        if ($this->dps_vendas_model->delete_venda($id)) {
            set_alert('success', 'Venda eliminada.');
        } else {
            set_alert('danger', 'Não foi possível eliminar a venda.');
        }

        redirect(admin_url('dps_vendas'));
    }

    /* ---------------------------------------------------------------------
     * CPCV / Pagamento
     * ------------------------------------------------------------------ */

    /**
     * O admin carrega o CPCV. Fica visível para o comercial descarregar e
     * enviar ao cliente.
     */
    public function upload_cpcv($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!is_admin() && !staff_can('edit', 'dps_vendas')) {
            access_denied('dps_vendas');
        }

        if ($this->guardar_documento_unico($id, 'cpcv_file', 'cpcv', 'CPCV')) {
            $rotulo = $venda['empreendimento'] . ' — Fracção ' . $venda['unidade'];
            if (is_admin()) {
                // E7: a direção carregou o CPCV → o comercial é avisado de que
                // está pronto para levar à assinatura do cliente.
                dps_vendas_notificar(
                    (int) $venda['staff_id'],
                    'CPCV pronto para assinatura — ' . $rotulo . '. Descarrega-o e envia ao cliente.',
                    'dps_vendas/view/' . $id
                );
            } else {
                // E8: o comercial carregou o CPCV (assinado) → a direção valida.
                dps_vendas_notificar_admins(
                    'CPCV carregado pelo comercial — ' . $rotulo . '. Confirme e marque como assinado.',
                    'dps_vendas/view/' . $id
                );
            }
        }
        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /**
     * Visto de "assinado". SÓ o admin valida — o comercial anexa o CPCV
     * assinado, mas quem confirma é a direção (regra definida em validação).
     */
    public function marcar_cpcv_assinado($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $this->dps_vendas_model->marcar_cpcv_assinado($id, true);
        dps_vendas_notificar(
            (int) $venda['staff_id'],
            'CPCV validado como ASSINADO pela direção — ' . $venda['empreendimento']
                . ' — Fracção ' . $venda['unidade'] . '.',
            'dps_vendas/view/' . $id
        );
        set_alert('success', 'CPCV marcado como assinado.');
        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /**
     * O comercial carrega o comprovativo de pagamento que o cliente lhe enviou.
     */
    public function upload_comprovativo($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!$this->pode_mexer($id)) {
            access_denied('dps_vendas');
        }

        if ($this->guardar_documento_unico($id, 'comprovativo_file', 'comprovativo', 'Comprovativo de pagamento')) {
            // E9: comprovativo carregado → a direção confirma e marca "pago".
            dps_vendas_notificar_admins(
                'Comprovativo de pagamento carregado — ' . $venda['empreendimento'] . ' — Fracção '
                    . $venda['unidade'] . '. Confirme e marque como pago.',
                'dps_vendas/view/' . $id
            );
        }
        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /**
     * Visto de "pago": exige comprovativo carregado e conclui a venda.
     * SÓ o admin — o comercial anexa o comprovativo, a direção confirma.
     */
    /**
     * A direção marca que o promotor JÁ PAGOU esta venda à DPS.
     *
     * É esta marca — e só ela — que faz o dinheiro entrar em "caixa" no
     * Painel do Negócio. Antes o painel adivinhava pelo mês previsto na regra
     * do empreendimento; agora exige confirmação humana, porque um prazo no
     * papel não é dinheiro na conta.
     *
     * Só admin: é dinheiro da casa, não da venda do comercial.
     */
    /**
     * Descarrega o CPCV do Aura preenchido com os dados da reserva.
     *
     * Sai em Word (.docx) e NÃO se envia a ninguém a partir daqui: é um
     * rascunho para a direção rever, completar o IBAN e a fracção, e só depois
     * decidir o que fazer com ele. Um contrato-promessa gerado por
     * preenchimento automático e enviado sem ninguém ler é a maneira mais
     * rápida de assinar uma obrigação errada.
     */
    public function cpcv($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        // Só o admin ou o comercial da venda.
        if (!is_admin() && (int) $venda['staff_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_vendas');
        }

        if (stripos((string) $venda['empreendimento'], 'aura') === false) {
            set_alert('warning', 'O modelo de contrato automático existe apenas para o Aura. Os outros empreendimentos têm contratos diferentes.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        /*
         * Uma empresa não tem estado civil, naturalidade nem nacionalidade —
         * identifica-se pelo NIPC e pela certidão do registo comercial.
         * Exigir-lhe esses três campos era impedir de vez que o contrato
         * saísse.
         *
         * O Cartão de Cidadão continua a ser exigido: é o do gestor que
         * assina em nome da empresa, e sem ele o contrato não fica completo.
         *
         * O CRC preenchido é o que distingue os dois casos.
         */
        $e_empresa = trim((string) ($venda['cliente_crc'] ?? '')) !== '';

        $obrigatorios = $e_empresa ? [
            // O NIF/NIPC não é exigido: a certidão (CRC) já identifica a
            // sociedade, e pedir os dois era pedir a mesma coisa duas vezes.
            'cliente_crc'           => 'código da certidão (CRC)',
            'cliente_cc'            => 'n.º do Cartão de Cidadão do gestor',
            'cliente_cc_validade'   => 'validade do Cartão de Cidadão do gestor',
            'cliente_morada'        => 'sede',
            'cliente_codigo_postal' => 'código postal',
            // Freguesia e concelho não se exigem a uma empresa: a sede e o
            // código postal já a localizam, e é assim que a certidão a
            // identifica.
        ] : [
            'cliente_nif'           => 'NIF',
            'cliente_cc'            => 'n.º do Cartão de Cidadão',
            'cliente_cc_validade'   => 'validade do Cartão de Cidadão',
            'cliente_naturalidade'  => 'naturalidade',
            'cliente_nacionalidade' => 'nacionalidade',
            'cliente_freguesia'     => 'freguesia',
            'cliente_concelho'      => 'concelho',
            'cliente_morada'        => 'morada',
            'cliente_codigo_postal' => 'código postal',
            'regime_civil'          => 'estado civil',
        ];

        $faltam = [];
        foreach ($obrigatorios as $campo => $etiqueta) {
            if (trim((string) ($venda[$campo] ?? '')) === '') {
                $faltam[] = $etiqueta;
            }
        }

        if ($faltam) {
            set_alert('warning', 'Não dá para gerar o contrato: falta ' . implode(', ', $faltam)
                . '. Estas reservas antigas foram feitas antes de o formulário pedir estes dados — '
                . 'preencha-os na ficha da venda.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        list($ok, $erro, $bytes, $nome) = dps_cpcv_gerar($venda);

        /*
         * Carimba a PRIMEIRA geração. É a partir daqui que se contam as 72h e
         * as 96h sem assinatura — e só da primeira, senão bastava alguém
         * voltar a descarregar o contrato para o relógio recomeçar e o aviso
         * nunca chegar.
         */
        if ($ok && empty($venda['cpcv_gerado_em'])) {
            $this->db->where('id', (int) $venda['id'])
                     ->update(db_prefix() . 'simulador_vendas',
                              ['cpcv_gerado_em' => date('Y-m-d H:i:s')]);
        }

        if (!$ok) {
            set_alert('danger', $erro);
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        log_activity('DPS Vendas: CPCV gerado para a venda #' . (int) $id);

        $this->load->helper('download');
        force_download($nome, $bytes);
    }

    /**
     * CPCV + Declaração de cessão, num único ZIP (Aura).
     *
     * SÓ A DIREÇÃO. Regra do dono (31/07/2026), à pergunta de se o comercial
     * passava a poder enviar directamente ao cliente: "passar pela direcção
     * primeiro". Os dois documentos saem com espaços por preencher — o IBAN do
     * comprador, a fracção — e um contrato-promessa enviado assim, com
     * «PREENCHER» no meio, é pior do que não enviar nada.
     *
     * Vão juntos porque andam juntos: a declaração é o que permite ao comprador
     * ceder a posição antes da escritura, e assina-se com o contrato.
     */
    public function documentos_aura($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        /*
         * O COMERCIAL DA VENDA descarrega sem esperar por ninguém.
         *
         * Esteve reservado à direção durante um dia. Na prática travava o
         * trabalho: quem fecha o negócio precisa dos documentos na hora, para
         * completar o que falta e mandar ao cliente. Regra do dono
         * (01/08/2026): "deve ficar automático sem validação do admin".
         *
         * O que continua a valer: saem em Word, com o IBAN e a fracção por
         * preencher, e é quem os descarrega que os revê antes de os enviar.
         */
        if (!is_admin() && (int) $venda['staff_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_vendas');
        }

        if (stripos((string) $venda['empreendimento'], 'aura') === false) {
            set_alert('warning', 'Estes documentos automáticos existem apenas para o Aura.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        $pecas = [];

        list($ok, $erro, $bytes, $nome) = dps_cpcv_gerar($venda);

        /*
         * Carimba a PRIMEIRA geração. É a partir daqui que se contam as 72h e
         * as 96h sem assinatura — e só da primeira, senão bastava alguém
         * voltar a descarregar o contrato para o relógio recomeçar e o aviso
         * nunca chegar.
         */
        if ($ok && empty($venda['cpcv_gerado_em'])) {
            $this->db->where('id', (int) $venda['id'])
                     ->update(db_prefix() . 'simulador_vendas',
                              ['cpcv_gerado_em' => date('Y-m-d H:i:s')]);
        }
        if (!$ok) {
            set_alert('danger', $erro);
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }
        $pecas[$nome] = $bytes;

        list($ok2, $erro2, $bytes2, $nome2) = dps_declaracao_cessao_gerar($venda);
        if (!$ok2) {
            set_alert('danger', 'O contrato saiu, mas a declaração não: ' . $erro2);
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }
        $pecas[$nome2] = $bytes2;

        $tmp = tempnam(sys_get_temp_dir(), 'auradocs');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            @unlink($tmp);
            set_alert('danger', 'Não foi possível preparar o ficheiro para descarregar.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }
        foreach ($pecas as $n => $b) {
            $zip->addFromString($n, $b);
        }
        $zip->close();

        $conteudo = file_get_contents($tmp);
        @unlink($tmp);

        log_activity('DPS Vendas: CPCV + declaração de cessão gerados para a venda #' . (int) $id);

        $this->load->helper('download');
        force_download(
            'Aura ' . $venda['unidade'] . ' - '
                . preg_replace('/[^\p{L}\p{N} ]+/u', '', (string) $venda['cliente']) . '.zip',
            $conteudo
        );
    }

    /**
     * Declaração de autorização de cessão de posição contratual (Aura).
     *
     * Acompanha o CPCV: é o papel que permite ao comprador passar a posição a
     * outra pessoa antes da escritura. Sai em Word de propósito — vai ser
     * rectificado antes de ir a assinatura, e é isso que se pretende.
     *
     * Exige menos do que o CPCV: aqui só é preciso identificar o comprador. Os
     * dados da vendedora e da fracção vão assinalados para preencher, porque
     * são decisão jurídica e não de software.
     */
    public function declaracao_cessao($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!is_admin() && (int) $venda['staff_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_vendas');
        }

        if (stripos((string) $venda['empreendimento'], 'aura') === false) {
            set_alert('warning', 'A declaração de cessão automática existe apenas para o Aura.');
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        list($ok, $erro, $bytes, $nome) = dps_declaracao_cessao_gerar($venda);

        if (!$ok) {
            set_alert('danger', $erro);
            redirect(admin_url('dps_vendas/view/' . (int) $id));
        }

        log_activity('DPS Vendas: declaração de cessão gerada para a venda #' . (int) $id);

        $this->load->helper('download');
        force_download($nome, $bytes);
    }

    public function marcar_recebido($id)
    {
        if (!$this->input->post()) {
            show_404();   // escrita nunca por GET
        }

        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        // A data pode vir do formulário (recebimentos lançados em atraso);
        // sem ela, assume-se hoje.
        $data = trim((string) $this->input->post('data'));
        if ($data === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $data = date('Y-m-d');
        }

        $this->dps_vendas_model->marcar_recebido_dps($id, $data);
        log_activity('DPS Vendas: venda #' . (int) $id . ' marcada como RECEBIDA da DPS em ' . $data);

        /*
         * O dinheiro entrou: emite-se no Moloni o recibo que liquida a
         * factura. Sai em RASCUNHO — a direcção confirma-o lá.
         *
         * Não trava a marcação: se o Moloni estiver em baixo, ou a venda não
         * tiver factura ligada, a marca de recebido fica na mesma e diz-se
         * porquê. Perder o registo de um recebimento por causa de uma API
         * seria pior do que emitir o recibo mais tarde à mão.
         */
        $aviso_moloni = '';

        if (file_exists(APPPATH . '../modules/dps_moloni/models/Dps_moloni_model.php')) {
            /*
         * O CAMINHO DO MÓDULO É OBRIGATÓRIO AQUI.
         *
         * Sem o prefixo, o CodeIgniter procura primeiro na pasta do módulo
         * ACTUAL — e existe um modules/dps_vendas/models/Dps_moloni_model.php,
         * cópia velha de 31/07. Era essa que carregava, e é por isso que os
         * métodos novos apareciam como "undefined": estavam no ficheiro certo,
         * mas quem era carregado era o outro. Custou dois enganos seguidos:
         * o sincronizar() e depois o emitir_recibo() (04/08/2026).
         */
        $this->load->model('dps_moloni/dps_moloni_model');
            $r = $this->dps_moloni_model->emitir_recibo((int) $id, $data);

            $aviso_moloni = !empty($r['ok'])
                ? ' Recibo criado no Moloni em rascunho (' . number_format((float) $r['valor'], 2, ',', '.') . ' €) — falta confirmá-lo lá.'
                : ' No Moloni não ficou recibo: ' . ($r['erro'] ?? 'motivo desconhecido');
        }

        set_alert('success', 'Venda #' . (int) $id . ' marcada como recebida em ' . _d($data) . '.' . $aviso_moloni);

        redirect($this->voltar_para());
    }

    /**
     * Desfaz a marca de recebido — engano de clique acontece, e sem isto a
     * única saída era mexer na base de dados.
     */
    public function desmarcar_recebido($id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $this->dps_vendas_model->desmarcar_recebido_dps($id);
        log_activity('DPS Vendas: retirada a marca de recebido da venda #' . (int) $id);
        set_alert('success', 'Marca de recebido retirada da venda #' . (int) $id . '.');

        redirect($this->voltar_para());
    }

    /**
     * Para onde voltar depois de marcar: a lista (com os filtros) ou a ficha,
     * conforme de onde veio o clique.
     */
    private function voltar_para()
    {
        // Quem carregou no visto dentro do Painel do Negócio volta para lá —
        // cair na lista de vendas depois de um clique faz duvidar se resultou.
        if ((string) $this->input->post('voltar') === 'painel') {
            return admin_url('dps_painel');
        }

        if ((string) $this->input->post('voltar') === 'lista') {
            $qs = (string) $this->input->post('qs');

            return admin_url('dps_vendas') . ($qs !== '' ? '?' . ltrim($qs, '?') : '');
        }

        return admin_url('dps_vendas');
    }

    public function marcar_pago($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $tem_comprovativo = false;
        foreach ($this->dps_vendas_model->get_docs($id) as $doc) {
            if ($doc['tipo'] === 'comprovativo') {
                $tem_comprovativo = true;
                break;
            }
        }

        if (!$tem_comprovativo) {
            set_alert('warning', 'Carregue primeiro o comprovativo de pagamento antes de marcar como pago.');
            redirect(admin_url('dps_vendas/view/' . $id));
        }

        $this->dps_vendas_model->marcar_pago($id);

        /*
         * É AQUI que a lead passa a Concretizado, e não quando a proposta foi
         * aceite: uma proposta aceite é uma palavra dada, isto é dinheiro
         * confirmado pela direção. Enquanto o negócio esteve entre os dois
         * momentos, a lead ficou em PARA CONTRATO.
         */
        if (!empty($venda['lead_id'])) {
            $this->db->where('id', (int) $venda['lead_id'])
                     ->update(db_prefix() . 'leads', ['status' => DPS_VENDAS_ESTADO_CONCRETIZADO_ID]);

            $this->load->model('leads_model');
            $this->leads_model->log_lead_activity(
                (int) $venda['lead_id'],
                'Pagamento confirmado pela direção — lead passada a Concretizado.'
            );

            hooks()->do_action('lead_status_changed', [
                'lead_id'    => (int) $venda['lead_id'],
                'old_status' => DPS_VENDAS_ESTADO_CONTRATO_ID,
                'new_status' => DPS_VENDAS_ESTADO_CONCRETIZADO_ID,
            ]);
        }

        dps_vendas_notificar(
            (int) $venda['staff_id'],
            'Pagamento confirmado pela direção — ' . $venda['empreendimento'] . ' — Fracção '
                . $venda['unidade'] . '. A venda ficou Concluída. 🎉',
            'dps_vendas/view/' . $id
        );
        set_alert('success', 'Pagamento confirmado. Venda marcada como Concluída.');
        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /* ---------------------------------------------------------------------
     * Documentos
     * ------------------------------------------------------------------ */

    /**
     * Desfazer o visto de "pago" — só admin. Serve para corrigir enganos
     * (marcar a fração errada acontece, e sem isto ficava trancado).
     */
    public function desmarcar_pago($id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        $this->dps_vendas_model->desmarcar_pago($id, 'corrigido por ' . get_staff_full_name());
        set_alert('success', 'Pagamento desmarcado. A venda voltou a aguardar validação.');
        redirect(admin_url('dps_vendas/view/' . (int) $id));
    }

    /** Desfazer o visto de "CPCV assinado" — só admin. */
    public function desmarcar_cpcv($id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        $this->dps_vendas_model->desmarcar_cpcv_assinado($id, 'corrigido por ' . get_staff_full_name());
        set_alert('success', 'CPCV desmarcado como assinado.');
        redirect(admin_url('dps_vendas/view/' . (int) $id));
    }

    /**
     * Os ficheiros estão fora do alcance do browser (.htaccess Deny from all).
     * Este é o único caminho para lá chegar, e verifica quem pede.
     */
    public function download_doc($doc_id)
    {
        $doc = $this->dps_vendas_model->get_doc($doc_id);
        if (!$doc) {
            show_404();
        }

        $venda = $this->dps_vendas_model->get_venda($doc['venda_id']);
        if (!$venda) {
            show_404();
        }

        $e_dono = (int) $venda['staff_id'] === (int) get_staff_user_id();

        if (!is_admin() && !staff_can('download_docs', 'dps_vendas') && !$e_dono) {
            access_denied('dps_vendas');
        }

        $caminho = FCPATH . DPS_VENDAS_UPLOAD_PATH . $doc['venda_id'] . '/' . $doc['filename'];

        if (!file_exists($caminho)) {
            set_alert('danger', 'O ficheiro já não existe no servidor.');
            redirect(admin_url('dps_vendas/view/' . $doc['venda_id']));
        }

        $nome = $doc['original_name'] ?: $doc['filename'];

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nome) . '"');
        header('Content-Length: ' . filesize($caminho));
        header('X-Content-Type-Options: nosniff');
        readfile($caminho);
        exit;
    }

    public function delete_doc($doc_id)
    {
        $doc = $this->dps_vendas_model->get_doc($doc_id);
        if (!$doc) {
            show_404();
        }

        if (!$this->pode_mexer($doc['venda_id'])) {
            access_denied('dps_vendas');
        }

        $this->dps_vendas_model->delete_doc($doc_id);
        set_alert('success', 'Documento removido.');

        redirect(admin_url('dps_vendas/view/' . $doc['venda_id']));
    }

    /* ---------------------------------------------------------------------
     * Comissões
     * ------------------------------------------------------------------ */

    /**
     * Quadro de comissões: previsão por mês + o que está por pagar + o que já
     * foi pago. Só entram vendas concluídas (ver get_comissoes()).
     *
     * O comercial vê apenas as suas; quem pode ver todas tem ainda o filtro
     * por comercial e por empreendimento.
     */
    /**
     * Vai ao Moloni buscar o número da factura já emitida para cada fracção.
     *
     * Só a direção: são números de facturação da empresa, e escrever um número
     * errado num quadro de dinheiro é pior do que deixar o campo vazio.
     *
     * Escreve apenas onde a unidade E o valor da linha batem certo com a
     * parcela da comissão. O que não bate vem no aviso, para se ir ver à mão —
     * nunca se adivinha.
     */
    public function moloni_sincronizar()
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        /*
         * Escrita só por POST: um GET com efeitos deixava qualquer link mexer
         * nos números das facturas.
         *
         * Pergunta-se pelo MÉTODO, não pelo conteúdo. Era `!$this->input->post()`
         * — que é falso quando o POST vem vazio — e o formulário das Comissões
         * não tem campo nenhum (o do Painel tem um "voltar" escondido, e por
         * isso funcionava). Com o CSRF desligado, o corpo ia mesmo vazio: o
         * botão recarregava a página e voltava ao mesmo sítio sem correr nada,
         * sem erro e sem registo. Uma tarde à procura no sítio errado.
         */
        if ($this->input->method(true) !== 'POST') {
            redirect(admin_url('dps_vendas/comissoes'));
        }

        /*
         * O botão existe em dois sítios (quadro de comissões e Painel do
         * Negócio). Volta-se ao sítio de onde se veio — cair noutro ecrã depois
         * de carregar num botão faz duvidar se a coisa correu bem.
         */
        $voltar = $this->input->post('voltar') === 'painel'
            ? admin_url('dps_painel')
            : admin_url('dps_vendas/comissoes');

        /*
         * O CAMINHO DO MÓDULO É OBRIGATÓRIO AQUI.
         *
         * Sem o prefixo, o CodeIgniter procura primeiro na pasta do módulo
         * ACTUAL — e existe um modules/dps_vendas/models/Dps_moloni_model.php,
         * cópia velha de 31/07. Era essa que carregava, e é por isso que os
         * métodos novos apareciam como "undefined": estavam no ficheiro certo,
         * mas quem era carregado era o outro. Custou dois enganos seguidos:
         * o sincronizar() e depois o emitir_recibo() (04/08/2026).
         */
        $this->load->model('dps_moloni/dps_moloni_model');
        $r = $this->dps_moloni_model->sincronizar(true);

        if (empty($r['ok'])) {
            set_alert('warning', $r['erro'] ?? 'Não foi possível falar com o Moloni.');
            redirect($voltar);
        }

        $n = count($r['achados']);
        $msg = $n === 0
            ? 'Li ' . $r['facturas_lidas'] . ' facturas do Moloni e não encontrei nenhuma fracção por preencher.'
            : 'Preenchi ' . $n . ' número' . ($n === 1 ? '' : 's') . ' de factura, de '
              . $r['facturas_lidas'] . ' facturas lidas no Moloni.';

        if (!empty($r['duvidas'])) {
            $msg .= ' Ficaram ' . count($r['duvidas']) . ' por confirmar à mão: ';
            $partes = [];
            foreach (array_slice($r['duvidas'], 0, 5) as $d) {
                $partes[] = 'venda #' . $d['venda'] . ' (' . $d['unidade'] . ') — ' . $d['motivo'];
            }
            $msg .= implode('; ', $partes) . '.';
        }

        set_alert($n > 0 ? 'success' : 'info', $msg);
        redirect($voltar);
    }

    /**
     * Quadro de comissões.
     *
     * Envolvido em try/catch e com um handler de fim de execução porque
     * estava a abrir em branco para alguns utilizadores e uma página em
     * branco não deixa rasto: os registos do Perfex estão desligados desde
     * Novembro de 2025 e o erro morria sem ninguém o ver. Assim, em vez de
     * branco, aparece o que correu mal — e a partir daí resolve-se em
     * minutos em vez de horas.
     */
    public function comissoes()
    {
        register_shutdown_function(function () {
            $e = error_get_last();
            if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                echo '<div style="font-family:sans-serif;padding:24px;">'
                    . '<h3>As comissões não abriram</h3><p>' . htmlspecialchars($e['message'])
                    . '</p><p style="color:#777">' . htmlspecialchars($e['file']) . ':' . (int) $e['line']
                    . '</p><p>Mostre este ecrã a quem trata do CRM.</p></div>';
            }
        });

        try {
            $this->comissoes_correr();
        } catch (Throwable $t) {
            echo '<div style="font-family:sans-serif;padding:24px;">'
                . '<h3>As comissões não abriram</h3><p>' . htmlspecialchars($t->getMessage())
                . '</p><p style="color:#777">' . htmlspecialchars($t->getFile()) . ':' . $t->getLine()
                . '</p><p>Mostre este ecrã a quem trata do CRM.</p></div>';
        }
    }

    private function comissoes_correr()
    {
        $pode_ver_todas = $this->pode_ver_todas();

        $filtros = [
            'empreendimento' => $this->input->get('empreendimento'),
            // Um comercial sem permissão de ver tudo não escolhe de quem vê:
            // a restrição vem do modelo, e o filtro fica ignorado.
            'comercial_id'   => $pode_ver_todas ? $this->input->get('comercial_id') : null,
        ];

        $previsao = $this->dps_vendas_model->previsao_comissoes($filtros, !$pode_ver_todas);

        // Três blocos, agrupados por comercial: o que já se pagou, o que é
        // devido agora, e o que virá de vendas ainda em CPCV. As futuras ficam
        // à parte de propósito — não se pode marcar como pago o que ainda não
        // venceu, e misturá-las inflacionava o "por pagar".
        $por_pagar = [];
        $pagas     = [];
        $futuras   = [];
        $retidas   = [];

        foreach ($previsao['linhas'] as $linha) {
            $lista = &$por_pagar;
            if ($linha['pago']) {
                $lista = &$pagas;
            } elseif (!empty($linha['futura'])) {
                $lista = &$futuras;
            } elseif (!empty($linha['retido'])) {
                // Venda concluída mas sem comprovativo validado: não é devida
                // ainda, por isso sai do bloco "por pagar" e vai para o seu.
                $lista = &$retidas;
            }

            $chave = $linha['comercial_id'] ?: 0;

            if (!isset($lista[$chave])) {
                $lista[$chave] = [
                    'nome'   => $linha['comercial_nome'],
                    'linhas' => [],
                    'total'  => 0.0,
                ];
            }

            $lista[$chave]['linhas'][] = $linha;
            $lista[$chave]['total'] += $linha['valor'];

            // Sem isto a referência sobrevivia à iteração e a próxima escrita
            // caía no grupo errado — o clássico do foreach com &.
            unset($lista);
        }

        $data['previsao']        = $previsao;
        $data['por_pagar']       = $por_pagar;
        $data['pagas']           = $pagas;
        $data['futuras']         = $futuras;
        $data['retidas']         = $retidas;
        $data['filtros']         = $filtros;
        $data['empreendimentos'] = $this->dps_vendas_model->get_empreendimentos();
        $data['comerciais']      = $this->get_comerciais();
        $data['pode_ver_todas']  = $pode_ver_todas;
        $data['mes_corrente']    = date('Y-m');
        $data['title']           = 'Comissões';

        $this->load->view('comissoes', $data);
    }

    /**
     * Define o mês previsto de recebimento de uma parcela (CPCV ou Escritura).
     * Campo vazio = a parcela é paga na hora e não tem mês a prever.
     */
    public function definir_mes_parcela($venda_id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }
        if (!$this->input->post()) {
            show_404();
        }

        $parcela = (string) $this->input->post('parcela', true);
        $mes     = (string) $this->input->post('mes', true);

        if ($this->dps_vendas_model->definir_mes_parcela((int) $venda_id, $parcela, $mes)) {
            set_alert('success', trim($mes) === ''
                ? 'Parcela marcada como recebimento imediato (sem mês).'
                : 'Mês previsto actualizado para ' . dps_vendas_mes_legivel($mes) . '.');
        } else {
            set_alert('danger', 'Não foi possível guardar o mês previsto — use o formato AAAA-MM.');
        }

        redirect(admin_url('dps_vendas/comissoes'));
    }

    /** A direção marca uma parcela da comissão como paga ao comercial. */
    public function marcar_parcela_paga($venda_id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }
        if (!$this->input->post()) {
            show_404();
        }

        $venda = $this->dps_vendas_model->get_venda($venda_id);
        if (!$venda) {
            show_404();
        }

        $parcela   = (string) $this->input->post('parcela', true);
        $data_pag  = (string) $this->input->post('data_pagamento', true);
        $resultado = $this->dps_vendas_model->marcar_parcela_paga((int) $venda_id, $parcela, $data_pag);

        if (empty($resultado['ok'])) {
            set_alert('warning', $resultado['erro']);
            redirect(admin_url('dps_vendas/comissoes'));
        }

        $etiqueta = Dps_vendas_model::$colunas_parcela[$parcela]['etiqueta'] ?? $parcela;

        dps_vendas_notificar(
            (int) $venda['staff_id'],
            'Comissão paga — parcela ' . $etiqueta . ' da venda #' . (int) $venda_id . ' ('
                . $venda['empreendimento'] . ' ' . $venda['unidade'] . ').',
            'dps_vendas/comissoes'
        );

        set_alert('success', 'Parcela ' . $etiqueta . ' marcada como paga.');
        redirect(admin_url('dps_vendas/comissoes'));
    }

    /** Desfaz o pagamento de uma parcela — só admin, e só por POST. */
    public function desmarcar_parcela_paga($venda_id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }
        if (!$this->input->post()) {
            show_404();
        }

        $venda = $this->dps_vendas_model->get_venda($venda_id);
        if (!$venda) {
            show_404();
        }

        $parcela = (string) $this->input->post('parcela', true);

        if (!$this->dps_vendas_model->desmarcar_parcela_paga((int) $venda_id, $parcela)) {
            set_alert('danger', 'Não foi possível desmarcar esta parcela.');
            redirect(admin_url('dps_vendas/comissoes'));
        }

        $etiqueta = Dps_vendas_model::$colunas_parcela[$parcela]['etiqueta'] ?? $parcela;

        dps_vendas_notificar(
            (int) $venda['staff_id'],
            'O pagamento da parcela ' . $etiqueta . ' da comissão da venda #' . (int) $venda_id
                . ' foi desmarcado — houve um engano a corrigir.',
            'dps_vendas/comissoes'
        );

        set_alert('success', 'Parcela ' . $etiqueta . ' desmarcada.');
        redirect(admin_url('dps_vendas/comissoes'));
    }

    /**
     * Fecho contabilístico da comissão da venda.
     *
     * Só por POST: 'recebida' é um estado terminal (avaliar_comissao() sai
     * logo à entrada e não há forma de o desfazer na interface), e o CSRF do
     * CodeIgniter só valida pedidos POST — por GET bastava um <img src=...>
     * numa página aberta por um admin para fechar comissões à vontade.
     */
    public function marcar_comissao_recebida($venda_id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        if (!is_admin() && !staff_can('marcar_recebido', 'dps_vendas')) {
            access_denied('dps_vendas');
        }

        $venda = $this->dps_vendas_model->get_venda($venda_id);
        if (!$venda) {
            show_404();
        }

        // A capacidade 'marcar_recebido' não dá acesso a vendas alheias: sem
        // esta verificação, quem só vê as suas linhas no quadro fechava a
        // comissão de qualquer outro comercial escrevendo o id no URL.
        if (!$this->pode_ver($venda)) {
            access_denied('dps_vendas');
        }

        $this->dps_vendas_model->marcar_comissao_recebida((int) $venda_id);
        set_alert('success', 'Comissão marcada como recebida.');

        redirect(admin_url('dps_vendas/comissoes'));
    }

    /**
     * O comercial anexa o recibo da sua comissão (recibo verde/fatura).
     * Só depois disto é que a direção pode marcar PAGO.
     */
    public function comissao_recibo($venda_id)
    {
        $venda = $this->dps_vendas_model->get_venda($venda_id);
        if (!$venda) {
            show_404();
        }

        // Dono da venda ou admin
        if (!is_admin() && (int) $venda['staff_id'] !== (int) get_staff_user_id()) {
            access_denied('dps_vendas');
        }

        if ($this->guardar_documento_unico($venda_id, 'recibo_file', 'recibo_comissao', 'Recibo da comissão')) {
            // Guardar o id do doc na venda, para a listagem não ter de andar
            // à procura nos documentos linha a linha.
            $doc = $this->db->select('id')
                ->where('venda_id', (int) $venda_id)
                ->where('tipo', 'recibo_comissao')
                ->order_by('id', 'DESC')
                ->get(db_prefix() . 'vendas_docs')->row_array();

            if ($doc) {
                $this->db->where('id', (int) $venda_id);
                $this->db->update(db_prefix() . 'simulador_vendas', ['comissao_recibo_doc' => (int) $doc['id']]);
            }

            // O recibo pode ser a última peça em falta: reavaliar já.
            $this->dps_vendas_model->avaliar_comissao($venda_id);

            dps_vendas_notificar_admins(
                'Recibo da comissão carregado — venda #' . (int) $venda_id . ' ('
                    . $venda['empreendimento'] . ' ' . $venda['unidade'] . ').',
                'dps_vendas/comissoes'
            );
        }

        redirect(admin_url('dps_vendas/comissoes'));
    }

    /**
     * Atalho antigo: marca a comissão INTEIRA como paga, ou seja, todas as
     * parcelas de uma vez.
     *
     * Desde a v1.5.0 quem manda são as parcelas — por isso este método deixou
     * de escrever `comissao_pago_dps` à mão e passa pelo modelo. Se continuasse
     * a escrever a coluna directamente, ficava a dizer "pago" com as parcelas
     * todas por pagar, e o quadro de comissões contradizia-se a si próprio.
     */
    public function comissao_marcar_paga($venda_id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }
        if (!$this->input->post()) {
            show_404();
        }

        $venda = $this->dps_vendas_model->get_venda($venda_id);
        if (!$venda) {
            show_404();
        }

        $data_pag = (string) $this->input->post('data_pagamento', true);

        $erro = null;
        foreach (array_keys($this->dps_vendas_model->parcelas_comissao($venda)) as $parcela) {
            $resultado = $this->dps_vendas_model->marcar_parcela_paga((int) $venda_id, $parcela, $data_pag);
            if (empty($resultado['ok'])) {
                $erro = $resultado['erro'];
                break;
            }
        }

        if ($erro !== null) {
            set_alert('warning', $erro);
            redirect(admin_url('dps_vendas/comissoes'));
        }

        dps_vendas_notificar(
            (int) $venda['staff_id'],
            'A sua comissão da venda #' . (int) $venda_id . ' (' . $venda['empreendimento'] . ' '
                . $venda['unidade'] . ') foi PAGA pela DPS.',
            'dps_vendas/comissoes'
        );

        set_alert('success', 'Comissão marcada como PAGA (todas as parcelas).');
        redirect(admin_url('dps_vendas/comissoes'));
    }

    /**
     * Desfazer o pagamento da comissão inteira — só admin.
     * Marcar a linha errada é fácil (as unidades têm códigos parecidos), e
     * sem isto ficava trancado como pago para sempre.
     *
     * Só por POST, como o gémeo comissao_marcar_paga(): por GET, um link
     * forjado aberto por um admin desmarcava todas as parcelas da venda e
     * ainda disparava notificações falsas ao comercial.
     */
    public function comissao_desmarcar_paga($venda_id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }
        if (!$this->input->post()) {
            show_404();
        }

        $venda = $this->dps_vendas_model->get_venda($venda_id);
        if (!$venda) {
            show_404();
        }

        foreach (array_keys($this->dps_vendas_model->parcelas_comissao($venda)) as $parcela) {
            $this->dps_vendas_model->desmarcar_parcela_paga((int) $venda_id, $parcela);
        }

        dps_vendas_notificar(
            (int) $venda['staff_id'],
            'O pagamento da comissão da venda #' . (int) $venda_id . ' ('
                . $venda['empreendimento'] . ' ' . $venda['unidade'] . ') foi desmarcado — houve um engano a corrigir.',
            'dps_vendas/comissoes'
        );

        set_alert('success', 'Pagamento da comissão desmarcado na venda #' . (int) $venda_id . '.');
        redirect(admin_url('dps_vendas/comissoes'));
    }

    /**
     * Remove o recibo da comissão (foi anexado por engano, ou na venda errada).
     *
     * Além de apagar o ficheiro, limpa a referência na venda e reavalia a
     * comissão — sem isso a venda continuava a contar como "pronta a pagar"
     * com um recibo que já não existe.
     */
    public function comissao_remover_recibo($venda_id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $venda = $this->dps_vendas_model->get_venda($venda_id);
        if (!$venda) {
            show_404();
        }

        if (!empty($venda['comissao_recibo_doc'])) {
            $this->dps_vendas_model->delete_doc((int) $venda['comissao_recibo_doc']);
        }

        $this->db->where('id', (int) $venda_id);
        $this->db->update(db_prefix() . 'simulador_vendas', ['comissao_recibo_doc' => null]);

        $this->dps_vendas_model->registar_historico(
            (int) $venda_id,
            null,
            $venda['estado'],
            'Recibo da comissão removido por ' . get_staff_full_name()
        );

        // Sem recibo a comissão deixa de ser devida.
        $this->dps_vendas_model->avaliar_comissao($venda_id);

        set_alert('success', 'Recibo removido da venda #' . (int) $venda_id . '. Pode anexar o correto.');
        redirect(admin_url('dps_vendas/comissoes'));
    }

    /**
     * Exportação do quadro de comissões: uma linha por PARCELA, não por venda.
     * É assim que a contabilidade precisa — cada recebimento tem o seu mês.
     */
    public function export_comissoes()
    {
        $pode_ver_todas = $this->pode_ver_todas();

        $filtros = [
            'empreendimento' => $this->input->get('empreendimento'),
            'comercial_id'   => $pode_ver_todas ? $this->input->get('comercial_id') : null,
        ];

        $previsao = $this->dps_vendas_model->previsao_comissoes($filtros, !$pode_ver_todas);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="comissoes-' . date('Y-m-d') . '.csv"');

        $saida = fopen('php://output', 'w');

        // BOM para o Excel abrir os acentos correctamente
        fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($saida, [
            'Venda', 'Empreendimento', 'Unidade', 'Cliente', 'Comercial',
            'Parcela', 'Taxa %', 'Valor', 'Mês previsto', 'Pago', 'Data de pagamento',
        ], ';');

        foreach ($previsao['linhas'] as $l) {
            fputcsv($saida, [
                $l['venda_id'],
                $l['empreendimento'],
                $l['unidade'],
                $l['cliente'],
                $l['comercial_nome'],
                $l['etiqueta'],
                $l['taxa'],
                $l['valor'],
                // Sem mês = recebimento imediato; deixa-se a célula explícita
                // para não parecer que faltou preencher.
                $l['mes'] !== '' ? $l['mes'] : 'imediato',
                $l['pago'] ? 'Sim' : 'Não',
                $l['pago_em'] ?: '',
            ], ';');
        }

        fclose($saida);
        exit;
    }

    /* ---------------------------------------------------------------------
     * Regras de comissão
     * ------------------------------------------------------------------ */

    public function regras()
    {
        if (!is_admin() && !staff_can('gerir_regras', 'dps_vendas')) {
            access_denied('dps_vendas');
        }

        if ($this->input->post()) {
            $post = $this->input->post();
            $id   = !empty($post['id']) ? $post['id'] : null;
            unset($post['id']);

            $erro_taxas = $this->dps_vendas_model->validar_regra($post);

            if (empty(trim($post['empreendimento']))) {
                set_alert('danger', 'O empreendimento é obrigatório.');
            } elseif ($erro_taxas !== '') {
                // Guardar uma repartição que não soma a taxa total significa
                // pagar em parcelas um valor diferente da comissão da venda.
                set_alert('danger', $erro_taxas);
            } else {
                $this->dps_vendas_model->guardar_regra($post, $id);
                set_alert('success', 'Regra guardada.');
            }

            redirect(admin_url('dps_vendas/regras'));
        }

        // Traz para a lista qualquer empreendimento que já teve vendas.
        $this->dps_vendas_model->sincronizar_regras_com_vendas();

        $data['regras']          = $this->dps_vendas_model->get_regras();
        $data['empreendimentos'] = $this->dps_vendas_model->get_empreendimentos();
        $data['title']           = 'Regras de Comissão';

        $this->load->view('regras', $data);
    }

    public function delete_regra($id)
    {
        if (!is_admin() && !staff_can('gerir_regras', 'dps_vendas')) {
            access_denied('dps_vendas');
        }

        $this->dps_vendas_model->delete_regra($id);
        set_alert('success', 'Regra eliminada.');

        redirect(admin_url('dps_vendas/regras'));
    }

    /* ---------------------------------------------------------------------
     * Auxiliares
     * ------------------------------------------------------------------ */

    private function validar_venda($post, $id = null)
    {
        $erros = [];

        if (empty(trim($post['empreendimento'] ?? ''))) {
            $erros[] = 'O empreendimento é obrigatório.';
        }

        if (empty(trim($post['unidade'] ?? ''))) {
            $erros[] = 'A unidade/fracção é obrigatória.';
        }

        if (empty(trim($post['cliente'] ?? ''))) {
            $erros[] = 'O nome do cliente é obrigatório.';
        }

        if (!isset($post['valor']) || (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $post['valor'])) <= 0) {
            $erros[] = 'O valor da venda tem de ser maior que zero.';
        }

        // Numa venda nova o CC é obrigatório. Na edição já foi entregue antes,
        // por isso só validamos o que vier de novo.
        if (!$id) {
            foreach (['cc_frente' => 'Cartão de Cidadão (frente)', 'cc_verso' => 'Cartão de Cidadão (verso)'] as $campo => $etiqueta) {
                if (empty($_FILES[$campo]['name'])) {
                    $erros[] = $etiqueta . ' é obrigatório.';
                }
            }
        }

        return $erros;
    }

    private function processar_uploads($venda_id)
    {
        $destino = FCPATH . DPS_VENDAS_UPLOAD_PATH . $venda_id . '/';

        if (!file_exists($destino)) {
            mkdir($destino, 0755, true);
        }

        $campos = [
            'cc_frente' => 'cc_frente',
            'cc_verso'  => 'cc_verso',
        ];

        foreach ($campos as $campo => $tipo) {
            if (!empty($_FILES[$campo]['name'])) {
                $erro = $this->guardar_ficheiro($_FILES[$campo], $venda_id, $tipo, $destino);
                if ($erro) {
                    return $erro;
                }
            }
        }

        // "outros" aceita vários ficheiros
        if (!empty($_FILES['outros']['name'][0])) {
            $total = count($_FILES['outros']['name']);
            for ($i = 0; $i < $total; $i++) {
                $ficheiro = [
                    'name'     => $_FILES['outros']['name'][$i],
                    'type'     => $_FILES['outros']['type'][$i],
                    'tmp_name' => $_FILES['outros']['tmp_name'][$i],
                    'error'    => $_FILES['outros']['error'][$i],
                    'size'     => $_FILES['outros']['size'][$i],
                ];

                $erro = $this->guardar_ficheiro($ficheiro, $venda_id, 'outro', $destino);
                if ($erro) {
                    return $erro;
                }
            }
        }

        return null;
    }

    /**
     * Upload de um único ficheiro (CPCV, comprovativo) fora do fluxo da criação
     * da venda. Reutiliza a validação de conteúdo de guardar_ficheiro().
     */
    private function guardar_documento_unico($id, $campo, $tipo, $label)
    {
        if (empty($_FILES[$campo]['name'])) {
            set_alert('warning', 'Selecione o ficheiro do ' . $label . '.');
            return false;
        }

        $destino = FCPATH . DPS_VENDAS_UPLOAD_PATH . $id . '/';
        if (!file_exists($destino)) {
            mkdir($destino, 0755, true);
        }

        $erro = $this->guardar_ficheiro($_FILES[$campo], $id, $tipo, $destino);
        if ($erro) {
            set_alert('danger', $erro);
            return false;
        }

        set_alert('success', $label . ' carregado.');

        return true;
    }

    private function guardar_ficheiro($ficheiro, $venda_id, $tipo, $destino)
    {
        if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
            return 'Falha no envio de "' . $ficheiro['name'] . '".';
        }

        if ($ficheiro['size'] > $this->tamanho_maximo) {
            return 'O ficheiro "' . $ficheiro['name'] . '" excede 8 MB.';
        }

        $extensao = strtolower(pathinfo($ficheiro['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $this->extensoes_permitidas, true)) {
            return 'Formato não permitido em "' . $ficheiro['name'] . '". Aceita-se PDF, JPG ou PNG.';
        }

        // Não confiamos na extensão nem no type enviado pelo browser
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $ficheiro['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->mimes_permitidos, true)) {
            return 'O conteúdo de "' . $ficheiro['name'] . '" não corresponde a um PDF ou imagem.';
        }

        // Nome aleatório no disco: o nome original nunca chega ao filesystem
        $nome_disco = $tipo . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;

        if (!move_uploaded_file($ficheiro['tmp_name'], $destino . $nome_disco)) {
            return 'Não foi possível guardar "' . $ficheiro['name'] . '".';
        }

        $this->dps_vendas_model->add_doc($venda_id, $tipo, $nome_disco, $ficheiro['name']);

        return null;
    }

    private function get_comerciais()
    {
        $this->db->select('staffid, CONCAT(firstname, " ", lastname) AS nome');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');

        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

    /*
     * Quem vê o mapa completo (vendas e comissões de toda a gente).
     *
     * Ser "admin" no CRM não basta: o Cláudio e a Samara têm conta de
     * administrador por razões operacionais e estavam a ver as comissões dos
     * colegas. A direcção quer que cada comercial veja só as suas.
     *
     * Também não se usa staff_can(): o helper do Perfex devolve sempre true
     * para administradores, o que reabria exactamente o mesmo buraco.
     *
     * Restam duas portas, e são precisas as duas:
     *
     *  1. `dps_vendas_ver_todas_ids` — CSV de staffids numa option. É a porta
     *     que funciona para administradores. A permissão do Perfex NÃO serve
     *     para eles: Staff_model::update() chama update_permissions([], $id)
     *     para quem estiver marcado como administrador e update_permissions()
     *     começa por apagar as linhas do staff em tblstaff_permissions
     *     (application/models/Staff_model.php:646 e 662). Além disso o
     *     formulário do staff desactiva e desmarca as checkboxes de
     *     capacidades enquanto "administrator" estiver ticado. Ou seja, uma
     *     linha inserida à mão para um admin é apagada no save seguinte — não
     *     é escape hatch nenhum. Esta option sobrevive a qualquer gravação de
     *     staff. Para dar o mapa completo a mais alguém (ex.: staff 46):
     *
     *         update_option('dps_vendas_ver_todas_ids', '1,46');
     *
     *  2. permissão `view` de dps_vendas mesmo atribuída em
     *     tblstaff_permissions — a via natural do Perfex, que só funciona para
     *     staff NÃO-administrador.
     *
     * O dono (VER_TODAS_DONO) está sempre dentro, esteja a option como
     * estiver: é o que evita ficar a casa toda sem ninguém a ver o mapa se
     * alguém limpar a option por engano.
     */
    const VER_TODAS_DONO = 1; // Ricardo, dono do CRM

    private function pode_ver_todas()
    {
        /*
         * DECISÃO EXPRESSA DA DIREÇÃO (não alterar sem lhe perguntar):
         * "o Cláudio e a Samara são admin, podem ver — menos o Painel do
         * Negócio". Ou seja, os administradores veem as vendas e comissões de
         * toda a gente; o que fica reservado ao dono é apenas o que a DPS
         * recebe, protegido dentro do módulo dps_painel.
         *
         * Os comerciais não-admin continuam a ver só as suas.
         */
        return is_admin()
            || staff_can('view', 'dps_vendas')
            || $this->na_lista_ver_todas();
    }

    /**
     * O staff autenticado está na lista fechada da option `dps_vendas_ver_todas_ids`?
     */
    private function na_lista_ver_todas()
    {
        $staff_id = (int) get_staff_user_id();
        if ($staff_id <= 0) {
            return false;
        }
        if ($staff_id === self::VER_TODAS_DONO) {
            return true;
        }

        foreach (explode(',', (string) get_option('dps_vendas_ver_todas_ids')) as $parte) {
            if ((int) trim($parte) === $staff_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Permissão mesmo atribuída ao staff em tblstaff_permissions, sem o atalho
     * "é admin, logo pode tudo" do staff_can().
     *
     * Nota: devolve sempre false para contas marcadas como administrador — o
     * Perfex apaga-lhes as linhas desta tabela. Ver o comentário de
     * pode_ver_todas().
     */
    private function tem_permissao_explicita($capability, $feature)
    {
        $staff_id = (int) get_staff_user_id();
        if ($staff_id <= 0) {
            return false;
        }

        return (bool) $this->db
            ->where('staff_id', $staff_id)
            ->where('feature', $feature)
            ->where('capability', $capability)
            ->count_all_results(db_prefix() . 'staff_permissions');
    }

    private function pode_ver($venda)
    {
        return $this->pode_ver_todas() || (int) $venda['staff_id'] === (int) get_staff_user_id();
    }

    private function pode_mexer($venda_id)
    {
        if (is_admin() || staff_can('edit', 'dps_vendas')) {
            return true;
        }

        $venda = $this->dps_vendas_model->get_venda($venda_id);

        return $venda && (int) $venda['staff_id'] === (int) get_staff_user_id();
    }

    /* ---------------------------------------------------------------------
     * Arquivo — documentos por empreendimento.
     * O admin cria as pastas e carrega os ficheiros; qualquer comercial
     * autenticado descarrega. Nada disto é público: tudo passa por aqui.
     * ------------------------------------------------------------------ */

    /**
     * Dossiers e afins: mais formatos do que os docs de identificação.
     * Os Office levam variantes porque o libmagic de muitos alojamentos devolve
     * application/zip para .docx (é um pacote ZIP) e CDFV2/ms-office para os
     * antigos — sem elas, ficheiros Word/Excel válidos eram rejeitados.
     * Upload é só de admin, por isso a folga é aceitável.
     */
    private $arquivo_extensoes = [
        'pdf'  => ['application/pdf'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/CDFV2', 'application/x-ole-storage'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'mp4'  => ['video/mp4'],
    ];

    public function arquivo($pasta_id = 0)
    {
        $pastas = $this->db->order_by('nome')
            ->get(db_prefix() . 'dps_arquivo_pastas')->result_array();

        $pasta_id = (int) $pasta_id;
        if (!$pasta_id && !empty($pastas)) {
            $pasta_id = (int) $pastas[0]['id'];
        }

        $docs = [];
        if ($pasta_id) {
            $docs = $this->db->select('d.*, CONCAT(s.firstname, " ", s.lastname) AS quem')
                ->from(db_prefix() . 'dps_arquivo_docs d')
                ->join(db_prefix() . 'staff s', 's.staffid = d.staff_id', 'left')
                ->where('d.pasta_id', $pasta_id)
                ->order_by('d.nome')
                ->get()->result_array();
        }

        $data['pastas']   = $pastas;
        $data['pasta_id'] = $pasta_id;
        $data['docs']     = $docs;
        $data['title']    = 'Arquivo';

        $this->load->view('arquivo', $data);
    }

    public function arquivo_pasta()
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $nome = trim((string) $this->input->post('nome'));

        if ($nome === '' || mb_strlen($nome) > 150) {
            set_alert('warning', 'Indique o nome do empreendimento.');
            redirect(admin_url('dps_vendas/arquivo'));
        }

        $ja = $this->db->where('nome', $nome)
            ->get(db_prefix() . 'dps_arquivo_pastas')->row_array();

        if ($ja) {
            redirect(admin_url('dps_vendas/arquivo/' . (int) $ja['id']));
        }

        $this->db->insert(db_prefix() . 'dps_arquivo_pastas', [
            'nome'       => $nome,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        set_alert('success', 'Empreendimento "' . $nome . '" criado no Arquivo.');
        redirect(admin_url('dps_vendas/arquivo/' . $this->db->insert_id()));
    }

    public function arquivo_pasta_apagar($id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        // Só por POST: um GET com o cookie de sessão (link forjado enviado a
        // um admin) não pode apagar nada. O POST herda o token CSRF do form.
        if ($this->input->method() !== 'post') {
            redirect(admin_url('dps_vendas/arquivo'));
        }

        $id = (int) $id;

        $tem_docs = $this->db->where('pasta_id', $id)
            ->count_all_results(db_prefix() . 'dps_arquivo_docs');

        if ($tem_docs > 0) {
            set_alert('warning', 'A pasta tem ' . $tem_docs . ' documento(s) — apague-os primeiro.');
            redirect(admin_url('dps_vendas/arquivo/' . $id));
        }

        $this->db->where('id', $id)->delete(db_prefix() . 'dps_arquivo_pastas');
        set_alert('success', 'Pasta removida.');
        redirect(admin_url('dps_vendas/arquivo'));
    }

    public function arquivo_upload()
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $pasta_id = (int) $this->input->post('pasta_id');
        $pasta    = $this->db->where('id', $pasta_id)
            ->get(db_prefix() . 'dps_arquivo_pastas')->row_array();

        if (!$pasta) {
            show_404();
        }

        if (empty($_FILES['ficheiro']) || $_FILES['ficheiro']['error'] !== UPLOAD_ERR_OK) {
            // UPLOAD_ERR_INI_SIZE etc.: o limite real vem do php.ini do alojamento.
            set_alert('danger', 'O envio falhou — o ficheiro pode exceder o limite do servidor (' . ini_get('upload_max_filesize') . ').');
            redirect(admin_url('dps_vendas/arquivo/' . $pasta_id));
        }

        $f        = $_FILES['ficheiro'];
        $extensao = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

        if (!isset($this->arquivo_extensoes[$extensao])) {
            set_alert('danger', 'Formato ".' . $extensao . '" não permitido. Aceita-se: ' . implode(', ', array_keys($this->arquivo_extensoes)) . '.');
            redirect(admin_url('dps_vendas/arquivo/' . $pasta_id));
        }

        // Não confiamos na extensão nem no type do browser (mesmo padrão das vendas)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $f['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->arquivo_extensoes[$extensao], true)) {
            set_alert('danger', 'O conteúdo de "' . $f['name'] . '" não corresponde à extensão .' . $extensao . '.');
            redirect(admin_url('dps_vendas/arquivo/' . $pasta_id));
        }

        $destino = FCPATH . DPS_ARQUIVO_UPLOAD_PATH . $pasta_id . '/';

        if (!is_dir($destino)) {
            mkdir($destino, 0755, true);
        }

        $base = FCPATH . DPS_ARQUIVO_UPLOAD_PATH;
        if (!file_exists($base . '.htaccess')) {
            // As duas sintaxes: Apache 2.4 (Require) e 2.2/LiteSpeed (Order).
            @file_put_contents(
                $base . '.htaccess',
                "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\nOrder Deny,Allow\nDeny from all\n</IfModule>\n"
            );
        }
        if (!file_exists($base . 'index.html')) {
            @file_put_contents($base . 'index.html', '');
        }

        // Nome aleatório no disco: o nome original nunca chega ao filesystem
        $nome_disco = 'arq_' . bin2hex(random_bytes(8)) . '.' . $extensao;

        if (!move_uploaded_file($f['tmp_name'], $destino . $nome_disco)) {
            set_alert('danger', 'Não foi possível guardar o ficheiro no servidor.');
            redirect(admin_url('dps_vendas/arquivo/' . $pasta_id));
        }

        // O nome visível é o que o admin escrever; sem nada, fica o do ficheiro.
        $nome = trim((string) $this->input->post('nome'));
        if ($nome === '') {
            $nome = $f['name'];
        }

        $this->db->insert(db_prefix() . 'dps_arquivo_docs', [
            'pasta_id'   => $pasta_id,
            'nome'       => mb_substr($nome, 0, 191),
            'filename'   => $nome_disco,
            'tamanho'    => (int) $f['size'],
            'staff_id'   => (int) get_staff_user_id(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        set_alert('success', '"' . $nome . '" adicionado a ' . $pasta['nome'] . '.');
        redirect(admin_url('dps_vendas/arquivo/' . $pasta_id));
    }

    public function arquivo_download($doc_id)
    {
        // Qualquer membro autenticado pode descarregar — é o objectivo do Arquivo.
        $doc = $this->db->where('id', (int) $doc_id)
            ->get(db_prefix() . 'dps_arquivo_docs')->row_array();

        if (!$doc) {
            show_404();
        }

        $caminho = FCPATH . DPS_ARQUIVO_UPLOAD_PATH . $doc['pasta_id'] . '/' . $doc['filename'];

        if (!file_exists($caminho)) {
            set_alert('danger', 'O ficheiro já não existe no servidor.');
            redirect(admin_url('dps_vendas/arquivo/' . $doc['pasta_id']));
        }

        // Nome de download legível: o nome visível + a extensão real do disco
        $extensao = pathinfo($doc['filename'], PATHINFO_EXTENSION);
        $nome     = $doc['nome'];
        if (strtolower((string) pathinfo($nome, PATHINFO_EXTENSION)) !== strtolower($extensao)) {
            $nome .= '.' . $extensao;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $nome) . '"');
        header('Content-Length: ' . filesize($caminho));
        header('X-Content-Type-Options: nosniff');
        readfile($caminho);
        exit;
    }

    public function arquivo_apagar($doc_id)
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        // Mesma regra do arquivo_pasta_apagar: destruição só por POST.
        if ($this->input->method() !== 'post') {
            redirect(admin_url('dps_vendas/arquivo'));
        }

        $doc = $this->db->where('id', (int) $doc_id)
            ->get(db_prefix() . 'dps_arquivo_docs')->row_array();

        if (!$doc) {
            show_404();
        }

        @unlink(FCPATH . DPS_ARQUIVO_UPLOAD_PATH . $doc['pasta_id'] . '/' . $doc['filename']);
        $this->db->where('id', (int) $doc_id)->delete(db_prefix() . 'dps_arquivo_docs');

        set_alert('success', 'Documento removido.');
        redirect(admin_url('dps_vendas/arquivo/' . $doc['pasta_id']));
    }

    /**
     * Passa a cliente as vendas concluídas que ainda não o são.
     *
     * O circuito já o faz sozinho quando uma venda é concluída. Isto serve
     * para o que ficou para trás: as vendas fechadas antes desta passagem
     * existir. Pode ser corrido as vezes que quiser — não duplica ninguém.
     */
    public function sincronizar_clientes()
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $r = $this->dps_vendas_model->sincronizar_clientes();

        $msg = $r['criados'] . ' cliente(s) criado(s)';
        if ($r['ja_existiam']) {
            $msg .= ', ' . $r['ja_existiam'] . ' já existia(m)';
        }
        if ($r['falhados']) {
            $msg .= '. Sem dados suficientes: ' . implode(', ', $r['falhados']);
        }

        set_alert($r['falhados'] ? 'warning' : 'success', $msg . '.');
        redirect(admin_url('dps_vendas'));
    }

    /* =====================================================================
     * IMPORTAÇÃO DE VENDAS POR FICHEIRO
     *
     * Cinquenta e cinco vendas à mão são cinquenta e cinco oportunidades de
     * enganar-se numa tecla. O ficheiro é lido, MOSTRADO, e só depois gravado
     * — nada entra sem ser visto primeiro.
     * ================================================================== */

    /** Modelo em CSV, já com as colunas certas e duas linhas de exemplo. */
    public function importar_modelo($empreendimento = 'Belo Horizonte')
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $empreendimento = str_replace('_', ' ', $empreendimento);
        $colunas = $this->colunas_importacao();

        $exemplo1 = ['AH', 'João Pedro Queiroz', '374900', '25/07/2026', 'Breno Gil',
                     'joao@exemplo.pt', '912345678', 'Rua das Flores, 12', '4000-001',
                     '123456789', 'Particular', 'Casado(a)'];
        $exemplo2 = ['CJ', 'FERFEB - SOCIEDADE IMOBILIÁRIA, LDA', '312.900,00', '2026-07-25', '46',
                     'geral@ferfeb.pt', '+351 916 000 000', 'Av. da Boavista, 100', '4100-100',
                     '500123456', 'Empresa', ''];

        // BOM + ponto e vírgula: sem isto o Excel português abre tudo numa
        // coluna só e come os acentos.
        $csv = "\xEF\xBB\xBF" . implode(';', $colunas) . "\n"
             . implode(';', $exemplo1) . "\n"
             . implode(';', $exemplo2) . "\n";

        $this->load->helper('download');
        force_download('modelo-vendas-' . strtolower(str_replace(' ', '-', $empreendimento)) . '.csv', $csv);
    }

    private function colunas_importacao()
    {
        /*
         * O que é preciso para a venda existir e o contrato sair. O estado, a
         * taxa e os meses de CPCV/escritura NÃO vão ao ficheiro: vêm da regra
         * do empreendimento, e pedi-los linha a linha era 55 oportunidades de
         * os escrever ao contrário.
         */
        return [
            'unidade', 'cliente', 'valor', 'data_venda', 'comercial',
            'email', 'telefone', 'morada', 'codigo_postal',
            'nif', 'tipo', 'regime_civil',
        ];
    }

    /** Ecrã de importação: escolher ficheiro, ver o que lá está, confirmar. */
    public function importar()
    {
        if (!is_admin()) {
            access_denied('dps_vendas');
        }

        $data['empreendimentos'] = $this->dps_vendas_model->get_empreendimentos();
        $data['colunas']         = $this->colunas_importacao();
        $data['linhas']          = [];
        $data['erros']           = [];
        $data['empreendimento']  = $this->input->post('empreendimento') ?: 'Belo Horizonte';
        $data['csv_bruto']       = '';

        if ($this->input->post() && !empty($_FILES['ficheiro']['tmp_name'])) {
            $bruto = file_get_contents($_FILES['ficheiro']['tmp_name']);
            list($data['linhas'], $data['erros']) = $this->ler_csv_vendas($bruto);
            $data['csv_bruto'] = $bruto;
        } elseif ($this->input->post('csv_bruto')) {
            // Segunda passagem: confirmar o que já foi mostrado.
            $bruto = $this->input->post('csv_bruto');
            list($linhas, $erros) = $this->ler_csv_vendas($bruto);

            if ($erros) {
                $data['linhas'] = $linhas;
                $data['erros']  = $erros;
                $data['csv_bruto'] = $bruto;
            } else {
                $criadas = 0;
                foreach ($linhas as $l) {
                    $l['empreendimento'] = $data['empreendimento'];
                    $estado = $l['_estado'];
                    unset($l['_estado']);

                    $novo_id = $this->dps_vendas_model->add_venda($l);
                    if (!$novo_id) {
                        continue;
                    }
                    $criadas++;

                    /*
                     * O add_venda cria sempre como "pendente" e o mudar_estado
                     * recusa saltos no circuito. Numa importação de histórico
                     * o circuito não se percorre — a venda JÁ está no estado
                     * em que está. Grava-se directamente, e fixa-se a comissão
                     * quando o estado assim o exige.
                     */
                    if ($estado !== 'pendente') {
                        $up = ['estado' => $estado];

                        if (in_array($estado, ['vendido', 'concluido'], true)) {
                            $v = $this->dps_vendas_model->get_venda($novo_id);
                            $calc = $this->dps_vendas_model->calcular_comissao($v);
                            $up['comissao_total'] = $calc['valor'];
                            $up += $this->dps_vendas_model->snapshot_taxas($v, $calc);
                        }

                        $this->db->where('id', $novo_id)
                                 ->update(db_prefix() . 'simulador_vendas', $up);
                        $this->dps_vendas_model->registar_historico(
                            $novo_id, 'pendente', $estado, 'Importado de ficheiro');
                    }
                }
                log_activity('Importadas ' . $criadas . ' vendas de ' . $data['empreendimento']);
                set_alert('success', $criadas . ' venda(s) importada(s).');
                redirect(admin_url('dps_vendas'));
            }
        }

        $data['title'] = 'Importar vendas';
        $this->load->view('importar', $data);
    }

    /**
     * Lê o CSV e devolve [linhas, erros].
     *
     * Aceita ponto e vírgula ou vírgula como separador, vírgula ou ponto como
     * decimal, e datas em dd/mm/aaaa ou aaaa-mm-dd — porque é o que sai de um
     * Excel português e de um Excel inglês, e não vale a pena obrigar quem
     * preenche a saber qual dos dois tem.
     */
    private function ler_csv_vendas($bruto)
    {
        $bruto = preg_replace('/^\xEF\xBB\xBF/', '', (string) $bruto);
        $linhas = preg_split('/\r\n|\r|\n/', $bruto);
        $linhas = array_values(array_filter($linhas, function ($l) { return trim($l) !== ''; }));

        if (count($linhas) < 2) {
            return [[], ['O ficheiro não tem linhas de dados.']];
        }

        $sep = substr_count($linhas[0], ';') >= substr_count($linhas[0], ',') ? ';' : ',';
        $cab = array_map(function ($x) { return strtolower(trim($x, " \t\"'")); }, explode($sep, $linhas[0]));

        $esperadas = $this->colunas_importacao();
        $faltam = array_diff(['unidade', 'cliente', 'valor'], $cab);
        if ($faltam) {
            return [[], ['Faltam colunas obrigatórias no cabeçalho: ' . implode(', ', $faltam)]];
        }

        $saida = [];
        $erros = [];

        for ($i = 1; $i < count($linhas); $i++) {
            $celulas = str_getcsv($linhas[$i], $sep);
            $l = [];
            foreach ($cab as $k => $nome) {
                $l[$nome] = isset($celulas[$k]) ? trim($celulas[$k]) : '';
            }

            $n = $i + 1;
            if ($l['unidade'] === '' || $l['cliente'] === '') {
                $erros[] = 'Linha ' . $n . ': falta a unidade ou o cliente.';
                continue;
            }

            $valor = str_replace([' ', '€'], '', (string) $l['valor']);
            // "374.900,00" e "374900.00" têm de dar o mesmo número.
            if (strpos($valor, ',') !== false) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            }
            if (!is_numeric($valor) || (float) $valor <= 0) {
                $erros[] = 'Linha ' . $n . ': valor inválido (' . $l['valor'] . ').';
                continue;
            }

            $data_v = trim((string) ($l['data_venda'] ?? ''));
            if ($data_v !== '' && preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $data_v, $m)) {
                $data_v = $m[3] . '-' . $m[2] . '-' . $m[1];
            }
            if ($data_v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_v)) {
                $erros[] = 'Linha ' . $n . ': data inválida (' . $l['data_venda'] . ').';
                continue;
            }

            $estado = strtolower(trim((string) ($l['estado'] ?? ''))) ?: 'reservado';
            if (!in_array($estado, Dps_vendas_model::$fluxo, true)) {
                $erros[] = 'Linha ' . $n . ': estado desconhecido (' . $l['estado'] . ').';
                continue;
            }

            $saida[] = [
                'unidade'                => $l['unidade'],
                'cliente'                => $l['cliente'],
                'valor'                  => $valor,
                'data_venda'             => $data_v,
                'staff_id'               => $this->resolver_comercial($l['comercial'] ?? '', $n, $erros),
                '_estado'                => $estado,
                'cliente_email'          => $l['email'] ?? '',
                'cliente_telefone'       => $l['telefone'] ?? '',
                'cliente_morada'         => $l['morada'] ?? '',
                'cliente_codigo_postal'  => $l['codigo_postal'] ?? '',
                'cliente_nif'            => $l['nif'] ?? '',
                'cliente_tipo'           => $l['tipo'] ?? '',
                'regime_civil'           => $l['regime_civil'] ?? '',
                'taxa'                   => $l['taxa'] ?? '',
                'origem'                 => 'importacao',
            ];
        }

        return [$saida, $erros];
    }

    /**
     * Aceita o comercial pelo número ou pelo nome.
     *
     * Quem preenche o ficheiro sabe o nome, não o id — obrigá-lo a ir buscar
     * números a uma tabela era garantir enganos. O nome é comparado sem
     * acentos nem maiúsculas, e basta o primeiro nome quando não é ambíguo.
     */
    private function resolver_comercial($valor, $linha, array &$erros)
    {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return (int) get_staff_user_id();
        }
        if (ctype_digit($valor)) {
            return (int) $valor;
        }

        static $equipa = null;
        if ($equipa === null) {
            $this->load->model('staff_model');
            $equipa = [];
            foreach ($this->staff_model->get('', ['active' => 1]) as $s) {
                $equipa[(int) $s['staffid']] = trim($s['firstname'] . ' ' . $s['lastname']);
            }
        }

        $normal = function ($t) {
            $t = mb_strtolower(trim((string) $t), 'UTF-8');
            $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t) ?: $t;

            return preg_replace('/\s+/', ' ', $t);
        };

        $procurado = $normal($valor);
        $achados   = [];

        foreach ($equipa as $id => $nome) {
            $n = $normal($nome);
            if ($n === $procurado || strpos($n, $procurado) === 0) {
                $achados[$id] = $nome;
            }
        }

        if (count($achados) === 1) {
            return (int) array_key_first($achados);
        }
        if (count($achados) > 1) {
            $erros[] = 'Linha ' . $linha . ': "' . $valor . '" corresponde a mais do que um comercial ('
                . implode(', ', $achados) . '). Use o nome completo.';

            return (int) get_staff_user_id();
        }

        $erros[] = 'Linha ' . $linha . ': comercial "' . $valor . '" não encontrado.';

        return (int) get_staff_user_id();
    }
}
