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

        if ($resultado['criou_processo']) {
            $this->dps_credito_model->notificar_novo_processo($resultado['credito_id']);
        }

        $mensagem = 'Questionário de crédito guardado.';
        if ($resultado['criou_processo']) {
            $mensagem .= ' Foi aberto um processo em DPS Crédito e a equipa foi notificada.';
        }

        echo json_encode([
            'success'        => true,
            'message'        => $mensagem,
            'credito_id'     => $resultado['credito_id'],
            'criou_processo' => $resultado['criou_processo'],
        ]);
        die;
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

        $data['estados_fecho']   = dps_credito_estados_fecho();
        $data['notificar_staff'] = array_filter(array_map('intval', explode(',', (string) get_option('dps_credito_notificar_staff'))));
        $data['bloqueio_ativo']  = get_option('dps_credito_bloqueio_ativo') == '1';
        $data['title']           = 'Definições — DPS Crédito';

        $this->load->view('definicoes', $data);
    }

    /* ---------------------------------------------------------------------
     * Auxiliares
     * ------------------------------------------------------------------ */

    private function validar_resposta($post)
    {
        $erros = [];

        if (empty($post['abordado']) || !in_array($post['abordado'], ['sim', 'nao'], true)) {
            $erros[] = 'Indique se o crédito foi abordado.';

            return $erros;
        }

        if ($post['abordado'] === 'nao') {
            return $erros;
        }

        if (empty($post['situacao'])) {
            $erros[] = 'Indique se é um novo pedido ou já tem financiamento.';
        }

        if (!isset($post['montante']) || trim($post['montante']) === '') {
            $erros[] = 'Indique o montante.';
        }

        if (empty($post['interessado_proposta'])) {
            $erros[] = 'Indique se o cliente está interessado em proposta.';
        }

        // O banco só faz sentido exigir quando já existe financiamento
        if (($post['situacao'] ?? '') === 'financiamento_existente' && empty(trim($post['banco'] ?? ''))) {
            $erros[] = 'Indique o banco onde está financiado.';
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
}
