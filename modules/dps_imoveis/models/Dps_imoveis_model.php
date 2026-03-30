<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_imoveis_model extends CI_Model
{
    private $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'dps_imoveis';
    }

    // ---------------------------------------------------------------
    // LISTAGEM
    // ---------------------------------------------------------------
    public function get_all($filters = [])
    {
        $this->db->select('i.*, CONCAT(s.firstname, " ", s.lastname) AS agente_nome, s.phonenumber AS agente_telefone, s.email AS agente_email, s.profile_image AS agente_foto');
        $this->db->from($this->table . ' i');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = i.agente_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('i.status', $filters['status']);
        }
        if (!empty($filters['tipo'])) {
            $this->db->where('i.tipo', $filters['tipo']);
        }
        if (!empty($filters['distrito'])) {
            $this->db->where('i.distrito', $filters['distrito']);
        }
        if (!empty($filters['agente_id'])) {
            $this->db->where('i.agente_id', $filters['agente_id']);
        }
        if (!empty($filters['published'])) {
            $this->db->where('i.published_website', 1);
        }

        // Não-admin e não-aprovadores só vêem os seus próprios imóveis
        if (!is_admin() && !has_permission('dps_imoveis', '', 'approve')) {
            $this->db->where('i.agente_id', get_staff_user_id());
        }

        $this->db->order_by('i.datecreated', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_by_id($id)
    {
        $this->db->select('i.*, CONCAT(s.firstname, " ", s.lastname) AS agente_nome, s.phonenumber AS agente_telefone, s.email AS agente_email, s.profile_image AS agente_foto');
        $this->db->from($this->table . ' i');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = i.agente_id', 'left');
        $this->db->where('i.id', $id);
        $row = $this->db->get()->row_array();
        if ($row) {
            $row['fotos_array']          = !empty($row['fotos'])            ? (json_decode($row['fotos'], true) ?: [])            : [];
            $row['areas_quartos']        = !empty($row['areas_quartos_json'])   ? $row['areas_quartos_json']   : '[]';
            $row['areas_suites']         = !empty($row['areas_suites_json'])    ? $row['areas_suites_json']    : '[]';
            $row['areas_salas']          = !empty($row['areas_salas_json'])     ? $row['areas_salas_json']     : '[]';
            $row['areas_cozinhas']       = !empty($row['areas_cozinhas_json'])  ? $row['areas_cozinhas_json']  : '[]';
            $row['areas_casas_banho']    = !empty($row['areas_casasbanho_json'])? $row['areas_casasbanho_json']: '[]';
        }
        return $row;
    }

    // ---------------------------------------------------------------
    // CRIAR / ACTUALIZAR
    // ---------------------------------------------------------------
    public function create($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['created_by']  = get_staff_user_id();
        $data['status']      = 'pendente';
        $data['published_website'] = 0;

        // Processar áreas individuais das divisões
        $data = $this->_process_areas($data);

        // Tratar fotos
        $fotos = $this->_handle_fotos();
        if (!empty($fotos['principal'])) {
            $data['foto_principal'] = $fotos['principal'];
        }
        if (!empty($fotos['galeria'])) {
            $data['fotos'] = json_encode($fotos['galeria']);
        }

        // Tratar documentos privados
        $docs = $this->_handle_documentos();
        foreach ($docs as $campo => $path) {
            $data[$campo] = $path;
        }

        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
        if ($id) {
            log_activity('DPS Imóvel criado [ID: ' . $id . '] - ' . $data['titulo']);
        }
        return $id;
    }

    public function update($id, $data)
    {
        // Processar áreas individuais das divisões
        $data = $this->_process_areas($data);

        // Tratar fotos novas (se enviadas)
        $fotos = $this->_handle_fotos();
        if (!empty($fotos['principal'])) {
            $data['foto_principal'] = $fotos['principal'];
        }
        if (!empty($fotos['galeria'])) {
            // Merge com fotos existentes
            $existing = $this->get_by_id($id);
            $existing_fotos = $existing['fotos_array'] ?? [];
            $merged = array_merge($existing_fotos, $fotos['galeria']);
            // Máximo 20 fotos
            $merged = array_slice($merged, 0, 20);
            $data['fotos'] = json_encode($merged);
        }

        // Tratar documentos
        $docs = $this->_handle_documentos();
        foreach ($docs as $campo => $path) {
            $data[$campo] = $path;
        }

        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        log_activity('DPS Imóvel actualizado [ID: ' . $id . ']');
        return $this->db->affected_rows() > 0;
    }

    public function delete($id)
    {
        $imovel = $this->get_by_id($id);
        if (!$imovel) return false;

        // Apagar ficheiros
        $this->_delete_files($imovel);

        $this->db->where('id', $id);
        $this->db->delete($this->table);
        log_activity('DPS Imóvel apagado [ID: ' . $id . '] - ' . $imovel['titulo']);
        return true;
    }

    // ---------------------------------------------------------------
    // APROVAÇÃO
    // ---------------------------------------------------------------
    public function aprovar($id, $notas = '')
    {
        $data = [
            'status'           => 'aprovado',
            'published_website'=> 1,
            'approver_id'      => get_staff_user_id(),
            'date_approval'    => date('Y-m-d H:i:s'),
            'notas_aprovacao'  => $notas,
        ];
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        log_activity('DPS Imóvel APROVADO [ID: ' . $id . '] por staff #' . get_staff_user_id());
        return $this->db->affected_rows() > 0;
    }

    public function rejeitar($id, $notas = '')
    {
        $data = [
            'status'           => 'rejeitado',
            'published_website'=> 0,
            'approver_id'      => get_staff_user_id(),
            'date_approval'    => date('Y-m-d H:i:s'),
            'notas_aprovacao'  => $notas,
        ];
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        log_activity('DPS Imóvel REJEITADO [ID: ' . $id . '] por staff #' . get_staff_user_id());
        return $this->db->affected_rows() > 0;
    }

    public function despublicar($id)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, ['published_website' => 0, 'status' => 'arquivado']);
        return $this->db->affected_rows() > 0;
    }

    public function remover_foto($id, $foto_path)
    {
        $imovel = $this->get_by_id($id);
        if (!$imovel) return false;

        $fotos = $imovel['fotos_array'];
        $fotos = array_filter($fotos, function($f) use ($foto_path) { return $f !== $foto_path; });
        $fotos = array_values($fotos);

        // Apagar ficheiro físico
        $full_path = FCPATH . $foto_path;
        if (file_exists($full_path)) {
            @unlink($full_path);
        }

        $this->db->where('id', $id);
        $this->db->update($this->table, ['fotos' => json_encode($fotos)]);
        return true;
    }

    // ---------------------------------------------------------------
    // ESTATÍSTICAS
    // ---------------------------------------------------------------
    public function get_stats()
    {
        $stats = [];
        foreach (['pendente', 'aprovado', 'rejeitado', 'arquivado'] as $s) {
            $this->db->where('status', $s);
            $stats[$s] = $this->db->count_all_results($this->table);
        }
        $this->db->where('published_website', 1);
        $stats['publicados'] = $this->db->count_all_results($this->table);
        return $stats;
    }

    // ---------------------------------------------------------------
    // API PÚBLICA - para imoveis-api.php
    // ---------------------------------------------------------------
    public function get_for_api($filters = [])
    {
        // NOTA: dados privados do proprietário (nome, telefone, email) NUNCA são incluídos na API pública
        $this->db->select('i.id, i.titulo, i.tipo, i.tipologia, i.distrito, i.cidade, i.preco, i.area_total, i.nr_quartos, i.nr_suites, i.nr_salas, i.nr_casas_banho, i.garagem, i.ano_construcao, i.texto_livre, i.equipamento, i.foto_principal, i.fotos, i.datecreated, CONCAT(s.firstname, " ", s.lastname) AS agente_nome, s.phonenumber AS agente_telefone, s.profile_image AS agente_foto, LOWER(REPLACE(CONCAT(s.firstname, "-", s.lastname), " ", "-")) AS agente_slug');
        $this->db->from($this->table . ' i');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = i.agente_id', 'left');
        $this->db->where('i.published_website', 1);
        $this->db->where('i.status', 'aprovado');

        if (!empty($filters['tipo'])) {
            $this->db->where('i.tipo', $filters['tipo']);
        }
        if (!empty($filters['distrito'])) {
            $this->db->where('i.distrito', $filters['distrito']);
        }
        if (!empty($filters['tipologia'])) {
            $this->db->where('i.tipologia', $filters['tipologia']);
        }

        $this->db->order_by('i.date_approval', 'DESC');
        return $this->db->get()->result_array();
    }

    // ---------------------------------------------------------------
    // HELPERS PRIVADOS
    // ---------------------------------------------------------------

    /**
     * Processa os arrays de áreas individuais vindos do POST:
     * - Filtra valores vazios/zero
     * - Guarda como JSON nos campos areas_*_json
     * - Calcula nr_* e area_* (soma) automaticamente
     * - Define tipologia com base no número de quartos
     * - Remove os campos array do $data (não existem como colunas directas)
     */
    private function _process_areas($data)
    {
        $map = [
            'areas_quartos'    => ['json_col' => 'areas_quartos_json',    'nr_col' => 'nr_quartos',     'area_col' => 'area_quartos'],
            'areas_suites'     => ['json_col' => 'areas_suites_json',     'nr_col' => 'nr_suites',      'area_col' => 'area_suites'],
            'areas_salas'      => ['json_col' => 'areas_salas_json',      'nr_col' => 'nr_salas',       'area_col' => 'area_salas'],
            'areas_cozinhas'   => ['json_col' => 'areas_cozinhas_json',   'nr_col' => null,             'area_col' => 'area_cozinha'],
            'areas_casas_banho'=> ['json_col' => 'areas_casasbanho_json', 'nr_col' => 'nr_casas_banho', 'area_col' => 'area_casas_banho'],
        ];

        foreach ($map as $post_key => $cols) {
            $raw = isset($data[$post_key]) ? (array)$data[$post_key] : [];
            // Filtrar valores vazios ou zero
            $filtered = [];
            foreach ($raw as $v) {
                $v = trim($v);
                if ($v !== '' && floatval($v) > 0) {
                    $filtered[] = floatval($v);
                }
            }
            // Guardar JSON
            $data[$cols['json_col']] = json_encode($filtered);
            // Calcular nr_*
            if ($cols['nr_col']) {
                $data[$cols['nr_col']] = count($filtered);
            }
            // Calcular área total da divisão (soma)
            $data[$cols['area_col']] = !empty($filtered) ? array_sum($filtered) : null;
            // Remover o campo array do data (não é coluna da tabela)
            unset($data[$post_key]);
        }

        // Tipologia automática baseada no número de quartos
        $nr_quartos = isset($data['nr_quartos']) ? (int)$data['nr_quartos'] : 0;
        // Só sobrescrever se não foi enviada manualmente (ou se estava vazia)
        $tipologias = ['T0','T1','T2','T3','T4','T4+'];
        $data['tipologia'] = $nr_quartos >= 5 ? 'T4+' : $tipologias[$nr_quartos];

        return $data;
    }

    private function _handle_fotos()
    {
        $result = ['principal' => null, 'galeria' => []];
        $upload_path = FCPATH . DPS_IMOVEIS_UPLOAD_PATH . 'fotos/';

        // Foto principal
        if (!empty($_FILES['foto_principal']['name'])) {
            $filename = $this->_upload_file('foto_principal', $upload_path);
            if ($filename) {
                $result['principal'] = DPS_IMOVEIS_UPLOAD_PATH . 'fotos/' . $filename;
            }
        }

        // Galeria (até 20 fotos)
        if (!empty($_FILES['fotos_galeria']['name'][0])) {
            $count = count($_FILES['fotos_galeria']['name']);
            for ($i = 0; $i < min($count, 20); $i++) {
                if ($_FILES['fotos_galeria']['error'][$i] === 0) {
                    $tmp = [
                        'name'     => $_FILES['fotos_galeria']['name'][$i],
                        'type'     => $_FILES['fotos_galeria']['type'][$i],
                        'tmp_name' => $_FILES['fotos_galeria']['tmp_name'][$i],
                        'error'    => $_FILES['fotos_galeria']['error'][$i],
                        'size'     => $_FILES['fotos_galeria']['size'][$i],
                    ];
                    $filename = $this->_save_file($tmp, $upload_path);
                    if ($filename) {
                        $result['galeria'][] = DPS_IMOVEIS_UPLOAD_PATH . 'fotos/' . $filename;
                    }
                }
            }
        }
        return $result;
    }

    private function _handle_documentos()
    {
        $docs = [];
        $upload_path = FCPATH . DPS_IMOVEIS_UPLOAD_PATH . 'documentos/';
        $campos = ['doc_cmi', 'doc_cc_proprietarios', 'doc_caderneta_predial'];
        foreach ($campos as $campo) {
            if (!empty($_FILES[$campo]['name'])) {
                $filename = $this->_upload_file($campo, $upload_path);
                if ($filename) {
                    $docs[$campo] = DPS_IMOVEIS_UPLOAD_PATH . 'documentos/' . $filename;
                }
            }
        }
        return $docs;
    }

    private function _upload_file($field, $upload_path)
    {
        if (empty($_FILES[$field]['name'])) return null;
        $tmp = $_FILES[$field];
        return $this->_save_file($tmp, $upload_path);
    }

    private function _save_file($file, $upload_path)
    {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) return null;

        $filename = uniqid('dps_') . '_' . time() . '.' . $ext;
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        if (move_uploaded_file($file['tmp_name'], $upload_path . $filename)) {
            return $filename;
        }
        return null;
    }

    private function _delete_files($imovel)
    {
        $paths = [];
        if (!empty($imovel['foto_principal'])) $paths[] = $imovel['foto_principal'];
        if (!empty($imovel['fotos_array'])) $paths = array_merge($paths, $imovel['fotos_array']);
        if (!empty($imovel['doc_cmi'])) $paths[] = $imovel['doc_cmi'];
        if (!empty($imovel['doc_cc_proprietarios'])) $paths[] = $imovel['doc_cc_proprietarios'];
        if (!empty($imovel['doc_caderneta_predial'])) $paths[] = $imovel['doc_caderneta_predial'];

        foreach ($paths as $p) {
            $full = FCPATH . $p;
            if (file_exists($full)) @unlink($full);
        }
    }

    // ---------------------------------------------------------------
    // NECESSIDADES
    // ---------------------------------------------------------------

    public function get_necessidades()
    {
        $tbl = db_prefix() . 'dps_necessidades';
        $this->db->select('n.*, CONCAT(s.firstname, " ", s.lastname) AS staff_nome');
        $this->db->from($tbl . ' n');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = n.staff_id', 'left');

        // Não-admin e não-aprovadores só vêem as suas próprias necessidades
        if (!is_admin() && !has_permission('dps_imoveis', '', 'approve')) {
            $this->db->where('n.staff_id', get_staff_user_id());
        }

        $this->db->order_by('n.data_criacao', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_necessidade_by_id($id)
    {
        $tbl = db_prefix() . 'dps_necessidades';
        $this->db->select('n.*, CONCAT(s.firstname, " ", s.lastname) AS staff_nome');
        $this->db->from($tbl . ' n');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = n.staff_id', 'left');
        $this->db->where('n.id', $id);
        return $this->db->get()->row_array();
    }

    public function create_necessidade($data)
    {
        $tbl = db_prefix() . 'dps_necessidades';
        $insert = [
            'staff_id'        => get_staff_user_id(),
            'nome_cliente'    => $data['nome_cliente'] ?? '',
            'contacto_cliente'=> $data['contacto_cliente'] ?? '',
            'email_cliente'   => $data['email_cliente'] ?? '',
            'tipo'            => $data['tipo'] ?? '',
            'tipologia'       => $data['tipologia'] ?? '',
            'distrito'        => $data['distrito'] ?? '',
            'cidade'          => $data['cidade'] ?? '',
            'preco_min'       => !empty($data['preco_min']) ? (float)$data['preco_min'] : null,
            'preco_max'       => !empty($data['preco_max']) ? (float)$data['preco_max'] : null,
            'area_min'        => !empty($data['area_min']) ? (float)$data['area_min'] : null,
            'nr_quartos_min'  => isset($data['nr_quartos_min']) && $data['nr_quartos_min'] !== '' ? (int)$data['nr_quartos_min'] : null,
            'garagem'         => isset($data['garagem']) && $data['garagem'] !== '' ? (int)$data['garagem'] : null,
            'urgencia'        => $data['urgencia'] ?? 'normal',
            'observacoes'     => $data['observacoes'] ?? '',
            'data_criacao'    => date('Y-m-d H:i:s'),
        ];
        $this->db->insert($tbl, $insert);
        return $this->db->insert_id();
    }

    public function update_necessidade($id, $data)
    {
        $tbl = db_prefix() . 'dps_necessidades';
        $update = [
            'nome_cliente'    => $data['nome_cliente'] ?? '',
            'contacto_cliente'=> $data['contacto_cliente'] ?? '',
            'email_cliente'   => $data['email_cliente'] ?? '',
            'tipo'            => $data['tipo'] ?? '',
            'tipologia'       => $data['tipologia'] ?? '',
            'distrito'        => $data['distrito'] ?? '',
            'cidade'          => $data['cidade'] ?? '',
            'preco_min'       => !empty($data['preco_min']) ? (float)$data['preco_min'] : null,
            'preco_max'       => !empty($data['preco_max']) ? (float)$data['preco_max'] : null,
            'area_min'        => !empty($data['area_min']) ? (float)$data['area_min'] : null,
            'nr_quartos_min'  => isset($data['nr_quartos_min']) && $data['nr_quartos_min'] !== '' ? (int)$data['nr_quartos_min'] : null,
            'garagem'         => isset($data['garagem']) && $data['garagem'] !== '' ? (int)$data['garagem'] : null,
            'urgencia'        => $data['urgencia'] ?? 'normal',
            'observacoes'     => $data['observacoes'] ?? '',
        ];
        $this->db->where('id', $id);
        $this->db->update($tbl, $update);
        return $this->db->affected_rows() >= 0;
    }

    public function delete_necessidade($id)
    {
        $tbl = db_prefix() . 'dps_necessidades';
        $this->db->where('id', $id);
        $this->db->delete($tbl);
        return $this->db->affected_rows() > 0;
    }
}
