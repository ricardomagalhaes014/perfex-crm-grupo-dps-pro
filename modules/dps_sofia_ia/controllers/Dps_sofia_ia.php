<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_sofia_ia extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('dps_sofia_ia/dps_sofia_ia_model');
    }

    /* ------------------------------------------------------------------ */
    /* Chat                                                                */
    /* ------------------------------------------------------------------ */

    public function index()
    {
        if (!dps_sofia_ia_pode_perguntar()) {
            access_denied('dps_sofia_ia');
        }

        $conversa = $this->dps_sofia_ia_model->get_conversa_atual(get_staff_user_id());

        $dados = [
            'title'     => 'A Sofia responde',
            'conversa'  => $conversa,
            'mensagens' => $this->dps_sofia_ia_model->get_mensagens($conversa['id']),
            'pronta'    => $this->dps_sofia_ia_model->esta_pronta(),
            'modo'      => $this->dps_sofia_ia_model->fornecedor(),
        ];

        $this->load->view('dps_sofia_ia/chat', $dados);
    }

    public function nova_conversa()
    {
        if (!dps_sofia_ia_pode_perguntar()) {
            access_denied('dps_sofia_ia');
        }

        $this->dps_sofia_ia_model->nova_conversa(get_staff_user_id());

        redirect(admin_url('dps_sofia_ia'));
    }

    /**
     * O pedido do chat. Responde sempre em JSON, incluindo nos erros — quem
     * está do outro lado é o JavaScript da caixa de conversa.
     */
    public function perguntar()
    {
        if (!dps_sofia_ia_pode_perguntar()) {
            $this->json(['ok' => false, 'erro' => 'Sem permissão.']);

            return;
        }

        $staff_id = get_staff_user_id();
        $conversa = $this->dps_sofia_ia_model->get_conversa_atual($staff_id);

        $resultado = $this->dps_sofia_ia_model->perguntar(
            $this->input->post('pergunta', true),
            $conversa['id'],
            $staff_id
        );

        $this->json($resultado);
    }

    public function reportar()
    {
        if (!dps_sofia_ia_pode_perguntar()) {
            $this->json(['ok' => false, 'erro' => 'Sem permissão.']);

            return;
        }

        $id = $this->dps_sofia_ia_model->reportar_resposta(
            (int) $this->input->post('mensagem_id'),
            $this->input->post('nota', true),
            get_staff_user_id()
        );

        if (!$id) {
            $this->json(['ok' => false, 'erro' => 'Não consegui registar o reporte.']);

            return;
        }

        $this->json(['ok' => true]);
    }

    /* ------------------------------------------------------------------ */
    /* Base de conhecimento                                                */
    /* ------------------------------------------------------------------ */

    public function conhecimento()
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        $this->load->view('dps_sofia_ia/conhecimento', [
            'title'         => 'Base de conhecimento da Sofia',
            'conhecimentos' => $this->dps_sofia_ia_model->get_conhecimentos(),
        ]);
    }

    public function ficha($id = null)
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        if ($this->input->post()) {
            $dados = $this->input->post();

            if (trim($dados['titulo']) === '' || trim($dados['conteudo']) === '') {
                set_alert('warning', 'O título e o conteúdo são obrigatórios.');
            } else {
                $novo_id = $this->dps_sofia_ia_model->guardar_conhecimento($dados, $id);
                set_alert('success', 'Conhecimento guardado. A Sofia já o usa nas próximas perguntas.');

                redirect(admin_url('dps_sofia_ia/ficha/' . $novo_id));
            }
        }

        $this->load->view('dps_sofia_ia/ficha', [
            'title' => $id ? 'Editar conhecimento' : 'Novo conhecimento',
            'ficha' => $id ? $this->dps_sofia_ia_model->get_conhecimento($id) : null,
        ]);
    }

    public function apagar_ficha($id)
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        if ($this->dps_sofia_ia_model->apagar_conhecimento($id)) {
            set_alert('success', 'Conhecimento apagado.');
        } else {
            set_alert('warning', 'Não encontrei esse conhecimento.');
        }

        redirect(admin_url('dps_sofia_ia/conhecimento'));
    }

    /**
     * Carregar um PDF, Word ou ficheiro de texto e transformá-lo em
     * conhecimento. O ficheiro original fica guardado fora da web, para se
     * poder voltar a ele.
     */
    public function importar_ficheiro()
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        if (empty($_FILES['documento']['name'])) {
            set_alert('warning', 'Escolha um ficheiro.');
            redirect(admin_url('dps_sofia_ia/conhecimento'));
        }

        $ficheiro = $_FILES['documento'];

        if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
            set_alert('danger', 'O carregamento falhou (erro ' . $ficheiro['error'] . '). Se o ficheiro for muito grande, o servidor pode estar a recusá-lo.');
            redirect(admin_url('dps_sofia_ia/conhecimento'));
        }

        $extensao = strtolower(pathinfo($ficheiro['name'], PATHINFO_EXTENSION));
        $aceites  = ['pdf', 'docx', 'txt', 'md', 'csv'];

        if (!in_array($extensao, $aceites, true)) {
            set_alert('warning', 'Formatos aceites: ' . strtoupper(implode(', ', $aceites)) . '.');
            redirect(admin_url('dps_sofia_ia/conhecimento'));
        }

        /*
         * A extensão é escolhida por quem carrega; o tipo real vem do conteúdo.
         * Sem esta verificação, um .php renomeado para .pdf entrava na pasta.
         */
        if (!$this->tipo_confere($ficheiro['tmp_name'], $extensao)) {
            set_alert('danger', 'O conteúdo do ficheiro não corresponde à extensão. Não foi carregado.');
            redirect(admin_url('dps_sofia_ia/conhecimento'));
        }

        $nome_guardado = 'doc_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extensao;
        $destino       = FCPATH . DPS_SOFIA_IA_UPLOAD_PATH . $nome_guardado;

        if (!is_dir(dirname($destino))) {
            @mkdir(dirname($destino), 0755, true);
        }

        if (!move_uploaded_file($ficheiro['tmp_name'], $destino)) {
            set_alert('danger', 'Não consegui guardar o ficheiro no servidor.');
            redirect(admin_url('dps_sofia_ia/conhecimento'));
        }

        $extraido = dps_sofia_ia_extrair_texto($destino, $extensao);

        $titulo = trim((string) $this->input->post('titulo', true));
        if ($titulo === '') {
            $titulo = pathinfo($ficheiro['name'], PATHINFO_FILENAME);
        }

        $id = $this->dps_sofia_ia_model->guardar_conhecimento([
            'titulo'         => $titulo,
            'categoria'      => $this->input->post('categoria', true),
            'conteudo'       => $extraido['texto'],
            'fonte'          => 'ficheiro',
            'ficheiro'       => $nome_guardado,
            'sempre_incluir' => $this->input->post('sempre_incluir'),
            'ativo'          => 1,
        ]);

        if ($extraido['aviso']) {
            /*
             * A ficha é criada à mesma, com o aviso: assim o admin abre-a e
             * cola lá o texto, em vez de ficar sem sítio onde o pôr.
             */
            set_alert('warning', $extraido['aviso']);
        } else {
            set_alert('success', 'Documento lido e guardado.');
        }

        redirect(admin_url('dps_sofia_ia/ficha/' . $id));
    }

    private function tipo_confere($caminho, $extensao)
    {
        if (!function_exists('finfo_open')) {
            return true;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipo  = finfo_file($finfo, $caminho);
        finfo_close($finfo);

        $permitidos = [
            'pdf'  => ['application/pdf'],
            // Um .docx é um zip, e é assim que o finfo o vê.
            'docx' => ['application/zip', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt'  => ['text/plain'],
            'md'   => ['text/plain', 'text/markdown'],
            'csv'  => ['text/plain', 'text/csv', 'application/csv'],
        ];

        return isset($permitidos[$extensao]) && in_array($tipo, $permitidos[$extensao], true);
    }

    public function importar_elevenlabs()
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        $resultado = $this->dps_sofia_ia_model->importar_da_elevenlabs();

        if ($resultado['ok']) {
            $mensagem = 'Importadas ' . $resultado['importadas'] . ' fichas da ElevenLabs.';

            /*
             * Os documentos sem texto acessível (normalmente entradas do tipo
             * URL ou PDF externo) são nomeados. Dizer só "importadas 17" quando
             * lá estavam 19 deixava duas ausências por explicar.
             */
            if (!empty($resultado['falhadas'])) {
                $mensagem .= ' Sem texto acessível, carregue-os à mão: '
                           . implode(', ', array_map('e', $resultado['falhadas'])) . '.';
            }

            set_alert('success', $mensagem);
        } else {
            set_alert('warning', $resultado['erro']);
        }

        redirect(admin_url('dps_sofia_ia/conhecimento'));
    }

    public function reindexar()
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        $trechos = $this->dps_sofia_ia_model->reindexar_tudo();
        set_alert('success', 'Índice reconstruído (' . $trechos . ' trechos).');

        redirect(admin_url('dps_sofia_ia/conhecimento'));
    }

    /* ------------------------------------------------------------------ */
    /* Por responder                                                       */
    /* ------------------------------------------------------------------ */

    public function pendentes($estado = 'aberta')
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        $this->load->view('dps_sofia_ia/pendentes', [
            'title'     => 'Sofia IA — por responder',
            'estado'    => $estado,
            'pendentes' => $this->dps_sofia_ia_model->get_pendentes($estado === 'todos' ? null : $estado),
        ]);
    }

    public function responder($id)
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        if ($this->input->post()) {
            $conhecimento_id = $this->dps_sofia_ia_model->responder_pendente($id, $this->input->post());

            if ($conhecimento_id) {
                set_alert('success', 'Resposta guardada. A Sofia já sabe responder a isto.');
                redirect(admin_url('dps_sofia_ia/pendentes'));
            }

            set_alert('warning', 'Não consegui guardar. Verifique se escreveu a resposta e se a pergunta ainda está em aberto.');
        }

        $pendente = $this->dps_sofia_ia_model->get_pendente($id);

        if (!$pendente) {
            set_alert('warning', 'Pergunta não encontrada.');
            redirect(admin_url('dps_sofia_ia/pendentes'));
        }

        $this->load->view('dps_sofia_ia/responder', [
            'title'    => 'Responder à Sofia',
            'pendente' => $pendente,
        ]);
    }

    public function ignorar($id)
    {
        if (!dps_sofia_ia_pode_gerir()) {
            access_denied('dps_sofia_ia');
        }

        $this->dps_sofia_ia_model->ignorar_pendente($id);
        set_alert('success', 'Marcada como ignorada.');

        redirect(admin_url('dps_sofia_ia/pendentes'));
    }

    /* ------------------------------------------------------------------ */
    /* Definições                                                          */
    /* ------------------------------------------------------------------ */

    public function definicoes()
    {
        if (!is_admin()) {
            access_denied('dps_sofia_ia');
        }

        if ($this->input->post()) {
            $fornecedor = (string) $this->input->post('fornecedor');
            update_option(
                'dps_sofia_ia_fornecedor',
                in_array($fornecedor, ['claude', 'openai', 'local'], true) ? $fornecedor : 'claude'
            );
            update_option('dps_sofia_ia_modelo', $this->input->post('modelo'));
            update_option('dps_sofia_ia_modelo_openai', $this->input->post('modelo_openai'));
            update_option('dps_sofia_ia_limite_hora', (int) $this->input->post('limite_hora'));
            update_option('dps_sofia_ia_persona', $this->input->post('persona', false));

            $notificar = $this->input->post('notificar_staff');
            update_option('dps_sofia_ia_notificar_staff', is_array($notificar) ? implode(',', array_map('intval', $notificar)) : '');

            /*
             * Campo em branco quer dizer "não mexer". Sem isto, abrir as
             * definições para trocar o modelo apagava a chave, porque o campo
             * da chave nunca mostra o valor guardado.
             */
            foreach (['claude', 'openai'] as $fornecedor) {
                $nova = trim((string) $this->input->post('api_key_' . $fornecedor));
                if ($nova !== '') {
                    update_option('dps_sofia_ia_api_key_' . $fornecedor, $nova);
                }
            }

            // Mesma regra para a chave da ElevenLabs: em branco não apaga.
            $chave_el = trim((string) $this->input->post('elevenlabs_key'));
            if ($chave_el !== '') {
                update_option('dps_sofia_ia_elevenlabs_key', $chave_el);
            }
            update_option('dps_sofia_ia_elevenlabs_agente', trim((string) $this->input->post('elevenlabs_agente')));

            set_alert('success', 'Definições guardadas.');
            redirect(admin_url('dps_sofia_ia/definicoes'));
        }

        $this->load->model('staff_model');

        $this->load->view('dps_sofia_ia/definicoes', [
            'title'      => 'Sofia IA — Definições',
            'staff'      => $this->staff_model->get('', ['active' => 1]),
            'chave_pt'   => $this->pista_da_chave('dps_sofia_ia_api_key_claude'),
            'chave_oai'  => $this->pista_da_chave('dps_sofia_ia_api_key_openai'),
            'chave_el'   => $this->pista_da_chave('dps_sofia_ia_elevenlabs_key'),
        ]);
    }

    /**
     * Mostra só o suficiente para se reconhecer a chave sem a revelar.
     */
    private function pista_da_chave($opcao)
    {
        $chave = (string) get_option($opcao);

        if ($chave === '') {
            return null;
        }

        return ['tamanho' => strlen($chave), 'fim' => substr($chave, -4)];
    }

    private function json($dados)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($dados, JSON_UNESCAPED_UNICODE));
    }
}
