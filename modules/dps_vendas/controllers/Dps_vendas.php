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
            if (!$this->pode_mexer($id)) {
                access_denied('dps_vendas');
            }

            $data['venda'] = $this->dps_vendas_model->get_venda($id);
            if (!$data['venda']) {
                show_404();
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

        if (!is_admin() && !staff_can('view', 'dps_vendas')) {
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

        if (!$this->pode_mexer($id)) {
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

    public function delete($id)
    {
        if (!is_admin() && !staff_can('delete', 'dps_vendas')) {
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

        $this->guardar_documento_unico($id, 'cpcv_file', 'cpcv', 'CPCV');
        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /**
     * Visto de "assinado". Fica ao alcance do comercial dono da venda (é ele
     * que lida com o cliente) e do admin.
     */
    public function marcar_cpcv_assinado($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!$this->pode_mexer($id)) {
            access_denied('dps_vendas');
        }

        $this->dps_vendas_model->marcar_cpcv_assinado($id, true);
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

        $this->guardar_documento_unico($id, 'comprovativo_file', 'comprovativo', 'Comprovativo de pagamento');
        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /**
     * Visto de "pago": exige comprovativo carregado e conclui a venda.
     */
    public function marcar_pago($id)
    {
        $venda = $this->dps_vendas_model->get_venda($id);
        if (!$venda) {
            show_404();
        }

        if (!$this->pode_mexer($id)) {
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
        set_alert('success', 'Pagamento confirmado. Venda marcada como Concluída.');
        redirect(admin_url('dps_vendas/view/' . $id));
    }

    /* ---------------------------------------------------------------------
     * Documentos
     * ------------------------------------------------------------------ */

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

    public function comissoes()
    {
        $comissoes = $this->dps_vendas_model->get_comissoes(!$this->pode_ver_todas());

        // Agrupar por comercial para a vista
        $por_comercial = [];
        foreach ($comissoes as $c) {
            $chave = $c['staff_id'] ?: 0;
            if (!isset($por_comercial[$chave])) {
                $por_comercial[$chave] = [
                    'nome'  => $c['comercial_nome'] ?: 'Sem comercial atribuído',
                    'linhas' => [],
                    'total' => 0,
                ];
            }
            $por_comercial[$chave]['linhas'][] = $c;
            $por_comercial[$chave]['total'] += (float) $c['comissao_total'];
        }

        $data['por_comercial'] = $por_comercial;
        $data['pode_ver_todas'] = $this->pode_ver_todas();
        $data['title']         = 'Comissões a Receber';

        $this->load->view('comissoes', $data);
    }

    public function marcar_comissao_recebida($venda_id)
    {
        if (!is_admin() && !staff_can('marcar_recebido', 'dps_vendas')) {
            access_denied('dps_vendas');
        }

        $this->dps_vendas_model->marcar_comissao_recebida($venda_id);
        set_alert('success', 'Comissão marcada como recebida.');

        redirect(admin_url('dps_vendas/comissoes'));
    }

    public function export_comissoes()
    {
        $comissoes = $this->dps_vendas_model->get_comissoes(!$this->pode_ver_todas());

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="comissoes-' . date('Y-m-d') . '.csv"');

        $saida = fopen('php://output', 'w');

        // BOM para o Excel abrir os acentos correctamente
        fprintf($saida, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($saida, ['Venda', 'Comercial', 'Empreendimento', 'Unidade', 'Cliente', 'Valor', 'Taxa %', 'Comissão', 'Estado comissão', 'Data venda'], ';');

        foreach ($comissoes as $c) {
            fputcsv($saida, [
                $c['id'],
                $c['comercial_nome'],
                $c['empreendimento'],
                $c['unidade'],
                $c['cliente'],
                $c['valor'],
                $c['taxa'],
                $c['comissao_total'],
                $c['comissao_estado'],
                $c['data_venda'],
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

            if (empty(trim($post['empreendimento']))) {
                set_alert('danger', 'O empreendimento é obrigatório.');
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
            return;
        }

        $destino = FCPATH . DPS_VENDAS_UPLOAD_PATH . $id . '/';
        if (!file_exists($destino)) {
            mkdir($destino, 0755, true);
        }

        $erro = $this->guardar_ficheiro($_FILES[$campo], $id, $tipo, $destino);
        if ($erro) {
            set_alert('danger', $erro);
        } else {
            set_alert('success', $label . ' carregado.');
        }
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

    private function pode_ver_todas()
    {
        return is_admin() || staff_can('view', 'dps_vendas');
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
}
