<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_credito extends AdminController
{
    private $extensoes_permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];

    private $mimes_permitidos = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    private $tamanho_maximo = 10485760; // 10 MB

    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_credito_model');
    }

    public function index()
    {
        $filtros = [
            'estado' => $this->input->get('estado'),
            'banco'  => $this->input->get('banco'),
        ];

        $data['processos'] = $this->dps_credito_model->get_processos($filtros, !$this->pode_ver_todos());
        $data['filtros']   = $filtros;
        $data['title']     = 'DPS Crédito';

        $this->load->view('manage', $data);
    }

    public function view($id)
    {
        $processo = $this->dps_credito_model->get_processo($id);
        if (!$processo) {
            show_404();
        }

        if (!$this->pode_ver($processo)) {
            access_denied('dps_credito');
        }

        $data['processo']      = $processo;
        $data['docs']          = $this->dps_credito_model->get_docs($id);
        $data['docs_tipados']  = $this->dps_credito_model->get_docs_tipados($id);
        $data['titulares']     = $this->dps_credito_model->get_titulares($id);
        $data['pode_download'] = is_admin() || staff_can('download_docs', 'dps_credito');
        $data['title']         = 'Processo de Crédito #' . $id;

        $this->load->view('view', $data);
    }

    public function update($id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        if (!is_admin() && !staff_can('edit', 'dps_credito')) {
            access_denied('dps_credito');
        }

        $this->dps_credito_model->update_processo($id, $this->input->post());

        $erro = $this->processar_uploads($id);
        if ($erro) {
            set_alert('warning', 'Processo guardado, mas houve um problema com os documentos: ' . $erro);
        } else {
            set_alert('success', 'Processo actualizado.');
        }

        redirect(admin_url('dps_credito/view/' . $id));
    }

    public function delete($id)
    {
        if (!is_admin() && !staff_can('delete', 'dps_credito')) {
            access_denied('dps_credito');
        }

        $this->dps_credito_model->delete_processo($id);
        set_alert('success', 'Processo eliminado.');

        redirect(admin_url('dps_credito'));
    }

    /* ---------------------------------------------------------------------
     * Questionário (chamado da ficha da lead e da listagem)
     * ------------------------------------------------------------------ */

    /**
     * Devolve o formulário já preenchido para o modal. É usado tanto a partir
     * da listagem de leads como de dentro da ficha.
     */
    public function form_lead($lead_id)
    {
        $lead = $this->db->where('id', (int) $lead_id)->get(db_prefix() . 'leads')->row_array();
        if (!$lead) {
            show_404();
        }

        $data['lead']     = $lead;
        $data['resposta'] = $this->dps_credito_model->get_resposta_por_lead($lead_id);

        $this->load->view('form_questionario', $data);
    }

    /**
     * Consulta rápida (GET, sem CSRF) usada pelo JS antes de deixar fechar uma
     * lead: diz se esta lead precisa mesmo do questionário respondido.
     */
    public function estado_lead($lead_id)
    {
        $aplicavel  = dps_credito_lead_aplicavel($lead_id);
        $respondido = dps_credito_lead_tem_resposta($lead_id);
        $ativo      = get_option('dps_credito_bloqueio_ativo') == '1';

        echo json_encode([
            'aplicavel'  => $aplicavel,
            'respondido' => $respondido,
            'precisa'    => $ativo && $aplicavel && !$respondido,
        ]);
        die;
    }

    /**
     * Resposta rápida a partir da tabela de leads, sem abrir a lead.
     * Só aceita "nao" — dizer "sim" obriga aos restantes campos e por isso
     * passa pelo questionário completo.
     */
    public function responder_rapido($lead_id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        $abordado = $this->input->post('abordado');

        /*
         * Só "não" e "não atendeu" se gravam sem abrir nada.
         *
         * O "sim" tem de passar pelo questionário, porque falta saber se o
         * cliente tem interesse — e é o interesse que decide se a ficha segue
         * para o parceiro. Gravá-lo aqui mandava o email sem essa resposta.
         */
        if (!in_array($abordado, ['nao', 'nao_atendeu'], true)) {
            echo json_encode(['success' => false,
                'message' => 'Para responder "sim" abra o questionário: falta indicar se há interesse.']);
            die;
        }

        $this->dps_credito_model->guardar_resposta((int) $lead_id, ['abordado' => $abordado]);

        echo json_encode(['success' => true, 'message' => $abordado === 'nao_atendeu'
            ? 'Marcado como não atendeu — não conta como crédito não abordado.'
            : 'Crédito marcado como não abordado.']);
        die;
    }

    public function guardar_resposta($lead_id)
    {
        if (!$this->input->post()) {
            show_404();
        }

        $post  = $this->input->post();
        $erros = $this->validar_resposta($post);

        if (!empty($erros)) {
            echo json_encode(['success' => false, 'message' => implode(' ', $erros)]);
            die;
        }

        $resultado = $this->dps_credito_model->guardar_resposta($lead_id, $post);

        // Documentos anexados pelo comercial no próprio questionário.
        if (!empty($resultado['credito_id']) && !empty($_FILES['documentos']['name'][0])) {
            $this->processar_uploads($resultado['credito_id']);
        }

        if ($resultado['criou_processo']) {
            $this->dps_credito_model->notificar_novo_processo($resultado['credito_id']);
        }

        $mensagem = 'Questionário de crédito guardado.';

        /*
         * O email só sai com INTERESSE. Abordar não é querer: mandar para o
         * parceiro quem disse que não queria era mandar-lhe trabalho e dados
         * de clientes sem razão nenhuma.
         */
        if (($post['abordado'] ?? '') === 'sim' && ($post['interessado_proposta'] ?? '') === 'sim') {
            $mensagem .= dps_credito_enviar_ao_parceiro((int) $lead_id)
                ? ' A lead foi enviada ao parceiro de crédito habitação.'
                : ' (Já tinha sido enviada ao parceiro, ou o email falhou.)';
        } elseif (($post['abordado'] ?? '') === 'sim') {
            $mensagem .= ' Sem interesse — não segue para o parceiro.';
        }

        if ($resultado['criou_processo']) {
            $mensagem .= ' Processo aberto em DPS Crédito; a equipa foi notificada.';
        }

        echo json_encode([
            'success'        => true,
            'message'        => $mensagem,
            'credito_id'     => $resultado['credito_id'],
            'criou_processo' => $resultado['criou_processo'],
        ]);
        die;
    }

    /**
     * Comercial: anexar documentos em falta e voltar a submeter.
     */
    public function resubmeter($credito_id)
    {
        $processo = $this->dps_credito_model->get_processo($credito_id);
        if (!$processo) {
            show_404();
        }

        $e_dono = (int) $processo['staff_id'] === (int) get_staff_user_id();
        if (!is_admin() && !staff_can('edit', 'dps_credito') && !$e_dono) {
            access_denied('dps_credito');
        }

        if (!empty($_FILES['documentos']['name'][0])) {
            $this->processar_uploads($credito_id);
        }

        $this->dps_credito_model->resubmeter($credito_id);

        if (dps_credito_pedido_ajax()) {
            echo json_encode(['success' => true, 'message' => 'Documentos submetidos.']);
            die;
        }

        set_alert('success', 'Documentos submetidos.');
        redirect(admin_url('leads/index/' . $processo['lead_id']));
    }

    /* ---------------------------------------------------------------------
     * Ações de estado (admin)
     * ------------------------------------------------------------------ */

    public function documentos_em_falta($credito_id)
    {
        $this->so_admin();
        $this->dps_credito_model->marcar_documentos_em_falta($credito_id, $this->input->post('nota'));
        $this->dps_credito_model->notificar_documentos_em_falta($credito_id);
        set_alert('success', 'Pedido de documentos enviado ao comercial.');
        redirect(admin_url('dps_credito/view/' . $credito_id));
    }

    public function estado($credito_id)
    {
        $this->so_admin();
        $novo = $this->input->post('estado');

        if ($novo === 'sucesso') {
            $valor = $this->input->post('valor_credito');
            if ($valor === null || trim($valor) === '') {
                set_alert('danger', 'Para marcar Sucesso tem de indicar o valor do crédito recebido.');
                redirect(admin_url('dps_credito/view/' . $credito_id));
            }
            $this->dps_credito_model->marcar_sucesso($credito_id, $valor);
            set_alert('success', 'Processo concluído com sucesso. Comissão gerada para o comercial.');
        } else {
            $this->dps_credito_model->marcar_estado($credito_id, $novo);
            set_alert('success', 'Estado actualizado.');
        }

        redirect(admin_url('dps_credito/view/' . $credito_id));
    }

    /* ---------------------------------------------------------------------
     * Mapa de comissões
     * ------------------------------------------------------------------ */

    /**
     * As leads em que o comercial disse SIM ao crédito.
     *
     * É o passo anterior ao processo: a lead seguiu para o parceiro e ainda
     * não há nada aberto do lado dele. Antes não se via em lado nenhum — para
     * saber quem tinha dito que sim era preciso abrir as leads uma a uma.
     */
    public function propostas()
    {
        if (!is_admin() && !staff_can('view', 'dps_credito')) {
            access_denied('dps_credito');
        }

        $t = db_prefix() . 'dps_credito_respostas';

        if (!$this->db->field_exists('enviado_parceiro_em', $t)) {
            $this->db->query('ALTER TABLE `' . $t . '` ADD COLUMN `enviado_parceiro_em` DATETIME NULL DEFAULT NULL');
        }

        $this->db->select('r.*, l.name AS lead_nome, l.email AS lead_email, l.phonenumber AS lead_tel,
                           l.assigned, ls.name AS estado_lead,
                           CONCAT(s.firstname," ",s.lastname) AS quem_respondeu', false);
        $this->db->from($t . ' r');
        $this->db->join(db_prefix() . 'leads l', 'l.id = r.lead_id', 'left');
        $this->db->join(db_prefix() . 'leads_status ls', 'ls.id = l.status', 'left');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left');
        $this->db->where('r.abordado', 'sim');

        /*
         * Um comercial vê as suas; a direcção vê tudo. A mesma regra do resto
         * do CRM — não é aqui que se abre a carteira de toda a gente.
         */
        if (!is_admin()) {
            $this->db->where('l.assigned', get_staff_user_id());
        }

        $this->db->order_by('COALESCE(r.dateupdated, r.dateadded)', 'DESC', false);

        $data['propostas'] = $this->db->get()->result();
        $data['parceiro']  = dps_credito_email_parceiro();
        $data['title']     = 'Propostas de crédito';

        $this->load->view('dps_credito/propostas', $data);
    }

    public function comissoes()
    {
        $comissoes = $this->dps_credito_model->get_comissoes(!$this->pode_ver_todos());

        $por_comercial = [];
        foreach ($comissoes as $c) {
            $chave = $c['staff_id'] ?: 0;
            if (!isset($por_comercial[$chave])) {
                $por_comercial[$chave] = ['nome' => $c['staff_nome'] ?: 'Sem comercial', 'linhas' => [], 'total' => 0];
            }
            $por_comercial[$chave]['linhas'][] = $c;
            $por_comercial[$chave]['total'] += (float) $c['comissao_total'];
        }

        $data['por_comercial'] = $por_comercial;
        $data['title']         = 'Comissões — DPS Crédito';
        $this->load->view('comissoes', $data);
    }

    private function so_admin()
    {
        if (!is_admin() && !staff_can('edit', 'dps_credito')) {
            access_denied('dps_credito');
        }
    }

    /* ---------------------------------------------------------------------
     * Documentos
     * ------------------------------------------------------------------ */

    public function download_doc($doc_id)
    {
        $doc = $this->dps_credito_model->get_doc($doc_id);
        if (!$doc) {
            show_404();
        }

        $processo = $this->dps_credito_model->get_processo($doc['credito_id']);
        if (!$processo) {
            show_404();
        }

        $e_dono = (int) $processo['staff_id'] === (int) get_staff_user_id();

        if (!is_admin() && !staff_can('download_docs', 'dps_credito') && !$e_dono) {
            access_denied('dps_credito');
        }

        $caminho = FCPATH . DPS_CREDITO_UPLOAD_PATH . $doc['credito_id'] . '/' . $doc['filename'];

        if (!file_exists($caminho)) {
            set_alert('danger', 'O ficheiro já não existe no servidor.');
            redirect(admin_url('dps_credito/view/' . $doc['credito_id']));
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
        $doc = $this->dps_credito_model->get_doc($doc_id);
        if (!$doc) {
            show_404();
        }

        if (!is_admin() && !staff_can('edit', 'dps_credito')) {
            access_denied('dps_credito');
        }

        $this->dps_credito_model->delete_doc($doc_id);
        set_alert('success', 'Documento removido.');

        redirect(admin_url('dps_credito/view/' . $doc['credito_id']));
    }

    /* ---------------------------------------------------------------------
     * AJAX: eliminar documento tipado
     * ------------------------------------------------------------------ */

    public function delete_doc_ajax($doc_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $doc = $this->dps_credito_model->get_doc($doc_id);
        if (!$doc) {
            echo json_encode(['success' => false, 'message' => 'Documento não encontrado.']);
            return;
        }

        $processo = $this->dps_credito_model->get_processo($doc['credito_id']);
        $e_dono   = $processo && (int) $processo['staff_id'] === (int) get_staff_user_id();

        if (!is_admin() && !staff_can('edit', 'dps_credito') && !$e_dono) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }

        $ok = $this->dps_credito_model->delete_doc($doc_id);
        echo json_encode(['success' => $ok]);
    }

    /* ---------------------------------------------------------------------
     * AJAX: upload de documento tipado por titular/tipo
     * ------------------------------------------------------------------ */

    public function upload_doc_tipado($processo_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $processo = $this->dps_credito_model->get_processo($processo_id);
        if (!$processo) {
            echo json_encode(['success' => false, 'message' => 'Processo não encontrado.']);
            return;
        }

        $e_dono = (int) $processo['staff_id'] === (int) get_staff_user_id();
        if (!is_admin() && !staff_can('edit', 'dps_credito') && !$e_dono) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }

        if (empty($_FILES['doc']['name'])) {
            echo json_encode(['success' => false, 'message' => 'Nenhum ficheiro recebido.']);
            return;
        }

        $num_titular = (int) ($this->input->post('num_titular') ?? 0);
        $tipo_doc    = preg_replace('/[^a-z0-9_]/', '', strtolower($this->input->post('tipo_doc') ?? ''));

        $ext = strtolower(pathinfo($_FILES['doc']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->extensoes_permitidas, true)) {
            echo json_encode(['success' => false, 'message' => 'Extensão não permitida: ' . $ext]);
            return;
        }

        if ($_FILES['doc']['size'] > $this->tamanho_maximo) {
            echo json_encode(['success' => false, 'message' => 'Ficheiro demasiado grande (máx 10 MB).']);
            return;
        }

        $dir = FCPATH . DPS_CREDITO_UPLOAD_PATH . $processo_id . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = uniqid('doc_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['doc']['tmp_name'], $dir . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Erro ao guardar o ficheiro.']);
            return;
        }

        $doc_id = $this->dps_credito_model->add_doc_tipado(
            $processo_id,
            $filename,
            $_FILES['doc']['name'],
            $_FILES['doc']['size'],
            $num_titular,
            $tipo_doc
        );

        echo json_encode([
            'success'       => true,
            'doc_id'        => $doc_id,
            'original_name' => $_FILES['doc']['name'],
            'size'          => $_FILES['doc']['size'],
        ]);
    }

    /* ---------------------------------------------------------------------
     * AJAX: guardar dados de titular
     * ------------------------------------------------------------------ */

    public function guardar_titular($processo_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $processo = $this->dps_credito_model->get_processo($processo_id);
        if (!$processo) {
            echo json_encode(['success' => false, 'message' => 'Processo não encontrado.']);
            return;
        }

        $e_dono = (int) $processo['staff_id'] === (int) get_staff_user_id();
        if (!is_admin() && !staff_can('edit', 'dps_credito') && !$e_dono) {
            echo json_encode(['success' => false, 'message' => 'Sem permissão.']);
            return;
        }

        $num_titular = (int) $this->input->post('num_titular');
        if (!in_array($num_titular, [1, 2], true)) {
            echo json_encode(['success' => false, 'message' => 'Titular inválido.']);
            return;
        }

        $id = $this->dps_credito_model->guardar_titular($processo_id, $num_titular, $this->input->post());
        echo json_encode(['success' => (bool) $id, 'titular_id' => $id]);
    }

    /* ---------------------------------------------------------------------
     * Definições
     * ------------------------------------------------------------------ */

    public function definicoes()
    {
        if (!is_admin() && !staff_can('definicoes', 'dps_credito')) {
            access_denied('dps_credito');
        }

        if ($this->input->post()) {
            // Os selects são múltiplos: chegam como array e guardam-se
            // como lista separada por vírgulas.
            $estados = $this->input->post('estados_fecho');
            $staff   = $this->input->post('notificar_staff');

            update_option(
                'dps_credito_estados_fecho',
                is_array($estados) ? implode(',', array_map('intval', $estados)) : ''
            );
            update_option(
                'dps_credito_notificar_staff',
                is_array($staff) ? implode(',', array_map('intval', $staff)) : ''
            );
            update_option('dps_credito_bloqueio_ativo', $this->input->post('bloqueio_ativo') ? '1' : '0');

            $taxa = str_replace(',', '.', (string) $this->input->post('taxa_comissao'));
            update_option('dps_credito_taxa_comissao', $taxa !== '' ? (float) $taxa : 0.5);

            $fontes = $this->input->post('fontes');
            update_option(
                'dps_credito_fontes',
                is_array($fontes) ? implode(',', array_map('intval', $fontes)) : ''
            );

            set_alert('success', 'Definições guardadas.');
            redirect(admin_url('dps_credito/definicoes'));
        }

        $data['estados_lead'] = $this->db->select('id, name')
            ->order_by('name', 'ASC')
            ->get(db_prefix() . 'leads_status')
            ->result_array();

        $data['staff'] = $this->db->select('staffid, CONCAT(firstname, " ", lastname) AS nome')
            ->where('active', 1)
            ->order_by('firstname', 'ASC')
            ->get(db_prefix() . 'staff')
            ->result_array();

        $data['fontes'] = $this->db->select('id, name')
            ->order_by('name', 'ASC')
            ->get(db_prefix() . 'leads_sources')
            ->result_array();

        $data['estados_fecho']    = dps_credito_estados_fecho();
        $data['notificar_staff']  = array_filter(array_map('intval', explode(',', (string) get_option('dps_credito_notificar_staff'))));
        $data['fontes_aplicaveis'] = dps_credito_fontes_aplicaveis();
        $data['bloqueio_ativo']   = get_option('dps_credito_bloqueio_ativo') == '1';
        $data['title']            = 'Definições — DPS Crédito';

        $this->load->view('definicoes', $data);
    }

    /* ---------------------------------------------------------------------
     * Auxiliares
     * ------------------------------------------------------------------ */

    private function validar_resposta($post)
    {
        $erros = [];

        if (empty($post['abordado']) || !in_array($post['abordado'], ['sim', 'nao', 'nao_atendeu'], true)) {
            $erros[] = 'Indique se o crédito foi abordado.';

            return $erros;
        }

        /*
         * Nem "não" nem "sim" exigem mais nada.
         *
         * O "sim" abria um questionário — situação, banco, montante, interesse
         * — que o comercial respondia de cor e o parceiro voltava a perguntar
         * ao cliente na mesma. Passou a valer a ficha da lead, que segue por
         * email para o parceiro. Regra do dono (19/08/2026).
         *
         * Os campos continuam a ser gravados quando vierem preenchidos: quem
         * os souber pode dá-los, mas já não trancam a resposta.
         */
        /*
         * Dito "sim", o interesse é obrigatório — é ele que decide o envio.
         */
        if ($post['abordado'] === 'sim'
            && !in_array($post['interessado_proposta'] ?? '', ['sim', 'nao'], true)) {
            $erros[] = 'Indique se o cliente tem interesse na proposta de crédito.';
        }

        return $erros;
    }

    private function processar_uploads($credito_id)
    {
        if (empty($_FILES['documentos']['name'][0])) {
            return null;
        }

        $destino = FCPATH . DPS_CREDITO_UPLOAD_PATH . $credito_id . '/';

        if (!file_exists($destino)) {
            mkdir($destino, 0755, true);
        }

        $total = count($_FILES['documentos']['name']);

        for ($i = 0; $i < $total; $i++) {
            $ficheiro = [
                'name'     => $_FILES['documentos']['name'][$i],
                'tmp_name' => $_FILES['documentos']['tmp_name'][$i],
                'error'    => $_FILES['documentos']['error'][$i],
                'size'     => $_FILES['documentos']['size'][$i],
            ];

            $erro = $this->guardar_ficheiro($ficheiro, $credito_id, $destino);
            if ($erro) {
                return $erro;
            }
        }

        return null;
    }

    private function guardar_ficheiro($ficheiro, $credito_id, $destino)
    {
        if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
            return 'Falha no envio de "' . $ficheiro['name'] . '".';
        }

        if ($ficheiro['size'] > $this->tamanho_maximo) {
            return 'O ficheiro "' . $ficheiro['name'] . '" excede 10 MB.';
        }

        $extensao = strtolower(pathinfo($ficheiro['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $this->extensoes_permitidas, true)) {
            return 'Formato não permitido em "' . $ficheiro['name'] . '".';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $ficheiro['tmp_name']);
        finfo_close($finfo);

        // O Office manda por vezes application/zip nos ficheiros novos
        $mimes = array_merge($this->mimes_permitidos, ['application/zip', 'application/octet-stream']);

        if (!in_array($mime, $mimes, true)) {
            return 'O conteúdo de "' . $ficheiro['name'] . '" não corresponde a um documento válido.';
        }

        $nome_disco = 'doc_' . bin2hex(random_bytes(8)) . '.' . $extensao;

        if (!move_uploaded_file($ficheiro['tmp_name'], $destino . $nome_disco)) {
            return 'Não foi possível guardar "' . $ficheiro['name'] . '".';
        }

        $this->dps_credito_model->add_doc($credito_id, $nome_disco, $ficheiro['name']);

        return null;
    }

    private function pode_ver_todos()
    {
        return is_admin() || staff_can('view', 'dps_credito');
    }

    private function pode_ver($processo)
    {
        return $this->pode_ver_todos() || (int) $processo['staff_id'] === (int) get_staff_user_id();
    }

    /* ---------------------------------------------------------------------
     * Análise comercial (direcção)
     * ------------------------------------------------------------------ */

    public function analise()
    {
        if (!is_admin() && !staff_can('view', 'dps_credito')) {
            access_denied('dps_credito');
        }

        $de  = $this->input->get('de')  ?: date('Y-m-01', strtotime('-2 months'));
        $ate = $this->input->get('ate') ?: date('Y-m-d');

        $data['linhas'] = dps_credito_analise_dados($de, $ate, 0);
        $data['de']     = $de;
        $data['ate']    = $ate;
        $data['tem_historico'] = $this->db->table_exists(db_prefix() . 'dps_credito_historico');
        $data['title']  = 'Análise Comercial — DPS Crédito';

        $this->load->view('analise', $data);
    }

}
