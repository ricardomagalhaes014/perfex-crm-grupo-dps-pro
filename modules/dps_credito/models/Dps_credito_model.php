<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_credito_model extends App_Model
{
    public function tabela_respostas()
    {
        return db_prefix() . 'dps_credito_respostas';
    }

    public function tabela_processos()
    {
        return db_prefix() . 'simulador_credito';
    }

    public function tabela_docs()
    {
        return db_prefix() . 'dps_credito_docs';
    }

    public function tabela_titulares()
    {
        return db_prefix() . 'dps_credito_titulares';
    }

    /* ---------------------------------------------------------------------
     * Respostas ao questionário
     * ------------------------------------------------------------------ */

    public function get_resposta_por_lead($lead_id)
    {
        $this->db->where('lead_id', (int) $lead_id);

        return $this->db->get($this->tabela_respostas())->row_array();
    }

    /**
     * Guarda a resposta e, se houver interesse em proposta, abre o processo de
     * crédito. É este o momento em que a lead passa a ser trabalho do Ricardo.
     *
     * @return array [resposta_id, credito_id|null, criou_processo]
     */
    public function guardar_resposta($lead_id, $data)
    {
        $lead_id  = (int) $lead_id;
        $abordado = $data['abordado'] === 'sim' ? 'sim' : 'nao';

        $payload = [
            'lead_id'  => $lead_id,
            'abordado' => $abordado,
            'staff_id' => get_staff_user_id(),
        ];

        if ($abordado === 'sim') {
            $payload['situacao']             = $data['situacao'] ?? null;
            $payload['banco']                = !empty($data['banco']) ? trim($data['banco']) : null;
            $payload['montante']             = isset($data['montante']) && $data['montante'] !== ''
                ? $this->limpar_numero($data['montante'])
                : null;
            $payload['interessado_proposta'] = $data['interessado_proposta'] ?? null;
        } else {
            // Mudou de ideias para "não": limpamos o resto para não ficarem
            // dados órfãos a sugerir um crédito que afinal não existe.
            $payload['situacao']             = null;
            $payload['banco']                = null;
            $payload['montante']             = null;
            $payload['interessado_proposta'] = null;
        }

        $payload['observacoes'] = $data['observacoes'] ?? null;

        $existente = $this->get_resposta_por_lead($lead_id);

        if ($existente) {
            $payload['dateupdated'] = date('Y-m-d H:i:s');
            $this->db->where('id', $existente['id']);
            $this->db->update($this->tabela_respostas(), $payload);
            $resposta_id = $existente['id'];
        } else {
            $payload['dateadded'] = date('Y-m-d H:i:s');
            $this->db->insert($this->tabela_respostas(), $payload);
            $resposta_id = $this->db->insert_id();
        }

        $credito_id     = null;
        $criou_processo = false;

        if ($abordado === 'sim' && ($data['interessado_proposta'] ?? null) === 'sim') {
            $ja_existe = $this->get_processo_por_lead($lead_id);

            if ($ja_existe) {
                $credito_id = $ja_existe['id'];
                $this->atualizar_processo_da_resposta($credito_id, $lead_id, $resposta_id, $payload);
            } else {
                $credito_id     = $this->criar_processo($lead_id, $resposta_id, $payload);
                $criou_processo = true;
            }
        }

        return [
            'resposta_id'    => $resposta_id,
            'credito_id'     => $credito_id,
            'criou_processo' => $criou_processo,
        ];
    }

    /* ---------------------------------------------------------------------
     * Processos de crédito
     * ------------------------------------------------------------------ */

    public function get_processo_por_lead($lead_id)
    {
        $this->db->where('lead_id', (int) $lead_id);

        return $this->db->get($this->tabela_processos())->row_array();
    }

    private function criar_processo($lead_id, $resposta_id, $payload)
    {
        $lead = $this->db->where('id', $lead_id)->get(db_prefix() . 'leads')->row_array();

        $this->db->insert($this->tabela_processos(), [
            'cliente'      => $lead['name'] ?? 'Lead #' . $lead_id,
            'lead_id'      => $lead_id,
            'resposta_id'  => $resposta_id,
            'banco'        => $payload['banco'],
            'situacao'     => $payload['situacao'],
            'montante'     => $payload['montante'],
            'valor'        => $payload['montante'] ?: 0,
            'estado'       => 'submetido',
            'origem'       => 'lead',
            'staff_id'     => $payload['staff_id'],
            'date_created' => date('Y-m-d H:i:s'),
        ]);

        $credito_id = $this->db->insert_id();

        hooks()->do_action('dps_credito_processo_criado', $credito_id);

        return $credito_id;
    }

    private function atualizar_processo_da_resposta($credito_id, $lead_id, $resposta_id, $payload)
    {
        $this->db->where('id', $credito_id);
        $this->db->update($this->tabela_processos(), [
            'banco'       => $payload['banco'],
            'situacao'    => $payload['situacao'],
            'montante'    => $payload['montante'],
            'resposta_id' => $resposta_id,
            'dateupdated' => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_processos($filtros = [], $apenas_meus = false)
    {
        $this->db->select('c.*, CONCAT(s.firstname, " ", s.lastname) AS staff_nome, l.name AS lead_nome, l.phonenumber AS lead_telefone, l.email AS lead_email');
        $this->db->from($this->tabela_processos() . ' c');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = c.staff_id', 'left');
        $this->db->join(db_prefix() . 'leads l', 'l.id = c.lead_id', 'left');

        if ($apenas_meus) {
            $this->db->where('c.staff_id', get_staff_user_id());
        }

        if (!empty($filtros['estado'])) {
            $this->db->where('c.estado', $filtros['estado']);
        }

        if (!empty($filtros['banco'])) {
            $this->db->where('c.banco', $filtros['banco']);
        }

        $this->db->order_by('c.id', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_processo($id)
    {
        $this->db->select('c.*, CONCAT(s.firstname, " ", s.lastname) AS staff_nome, l.name AS lead_nome, l.phonenumber AS lead_telefone, l.email AS lead_email');
        $this->db->from($this->tabela_processos() . ' c');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = c.staff_id', 'left');
        $this->db->join(db_prefix() . 'leads l', 'l.id = c.lead_id', 'left');
        $this->db->where('c.id', $id);

        return $this->db->get()->row_array();
    }

    public function update_processo($id, $data)
    {
        $update = [
            'banco'       => $data['banco'] ?? null,
            'situacao'    => $data['situacao'] ?? null,
            'montante'    => isset($data['montante']) && $data['montante'] !== '' ? $this->limpar_numero($data['montante']) : null,
            'observacoes' => $data['observacoes'] ?? null,
            'dateupdated' => date('Y-m-d H:i:s'),
        ];

        $this->db->where('id', $id);
        $this->db->update($this->tabela_processos(), $update);

        return $this->db->affected_rows() > 0;
    }

    /* ---------------------------------------------------------------------
     * Fluxo de estados
     * ------------------------------------------------------------------ */

    /**
     * Admin: pedir documentos em falta. Guarda o que falta (o comercial vê-o)
     * e devolve o processo a "documentos_em_falta".
     */
    public function marcar_documentos_em_falta($id, $nota)
    {
        $this->db->where('id', $id);
        $this->db->update($this->tabela_processos(), [
            'estado'        => 'documentos_em_falta',
            'docs_em_falta' => $nota,
            'dateupdated'   => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Comercial: voltar a submeter depois de anexar o que faltava.
     */
    public function resubmeter($id)
    {
        $this->db->where('id', $id);
        $this->db->update($this->tabela_processos(), [
            'estado'        => 'submetido',
            'docs_em_falta' => null,
            'dateupdated'   => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    public function marcar_estado($id, $estado)
    {
        if (!in_array($estado, dps_credito_estados_processo(), true)) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_processos(), [
            'estado'      => $estado,
            'dateupdated' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Admin: aprovar. Regista o valor do crédito recebido e fixa a comissão do
     * comercial (0,5% por omissão). É o que alimenta o mapa de comissões.
     */
    public function marcar_sucesso($id, $valor_credito)
    {
        $valor = $this->limpar_numero($valor_credito);
        $taxa  = dps_credito_taxa_comissao();

        $this->db->where('id', $id);
        $this->db->update($this->tabela_processos(), [
            'estado'         => 'sucesso',
            'valor_credito'  => $valor,
            'taxa'           => $taxa,
            'comissao_total' => round($valor * $taxa / 100, 2),
            'dateupdated'    => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Mapa de comissões de crédito (só as que chegaram a "sucesso").
     */
    public function get_comissoes($apenas_minhas = false)
    {
        $this->db->select('c.*, CONCAT(s.firstname, " ", s.lastname) AS staff_nome, l.name AS lead_nome');
        $this->db->from($this->tabela_processos() . ' c');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = c.staff_id', 'left');
        $this->db->join(db_prefix() . 'leads l', 'l.id = c.lead_id', 'left');
        $this->db->where('c.estado', 'sucesso');

        if ($apenas_minhas) {
            $this->db->where('c.staff_id', get_staff_user_id());
        }

        $this->db->order_by('c.staff_id', 'ASC');
        $this->db->order_by('c.id', 'DESC');

        return $this->db->get()->result_array();
    }

    public function delete_processo($id)
    {
        foreach ($this->get_docs($id) as $doc) {
            $this->delete_doc($doc['id']);
        }

        $this->db->where('id', $id)->delete($this->tabela_processos());

        return $this->db->affected_rows() > 0;
    }

    /* ---------------------------------------------------------------------
     * Documentos
     * ------------------------------------------------------------------ */

    public function get_docs($credito_id)
    {
        $this->db->where('credito_id', $credito_id);
        $this->db->order_by('id', 'ASC');

        return $this->db->get($this->tabela_docs())->result_array();
    }

    public function get_doc($id)
    {
        $this->db->where('id', $id);

        return $this->db->get($this->tabela_docs())->row_array();
    }

    public function add_doc($credito_id, $filename, $original_name, $descricao = null)
    {
        $this->db->insert($this->tabela_docs(), [
            'credito_id'    => $credito_id,
            'filename'      => $filename,
            'original_name' => $original_name,
            'descricao'     => $descricao,
            'uploaded_by'   => get_staff_user_id(),
            'dateadded'     => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id();
    }

    public function add_doc_tipado($credito_id, $filename, $original_name, $size, $num_titular, $tipo_doc)
    {
        $this->db->insert($this->tabela_docs(), [
            'credito_id'    => (int) $credito_id,
            'filename'      => $filename,
            'original_name' => $original_name,
            'size'          => (int) $size,
            'num_titular'   => (int) $num_titular,
            'tipo_doc'      => $tipo_doc,
            'uploaded_by'   => get_staff_user_id(),
            'dateadded'     => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id();
    }

    public function get_docs_tipados($credito_id)
    {
        $this->db->where('credito_id', (int) $credito_id);
        $this->db->where('num_titular IS NOT NULL');
        $this->db->order_by('num_titular ASC, tipo_doc ASC, id ASC');

        return $this->db->get($this->tabela_docs())->result_array();
    }

    /* ---------------------------------------------------------------------
     * Titulares
     * ------------------------------------------------------------------ */

    public function get_titular($credito_id, $num_titular)
    {
        $this->db->where('credito_id', (int) $credito_id);
        $this->db->where('num_titular', (int) $num_titular);

        return $this->db->get($this->tabela_titulares())->row_array();
    }

    public function get_titulares($credito_id)
    {
        $this->db->where('credito_id', (int) $credito_id);
        $this->db->order_by('num_titular', 'ASC');

        return $this->db->get($this->tabela_titulares())->result_array();
    }

    public function guardar_titular($credito_id, $num_titular, $data)
    {
        $payload = [
            'nome'             => $data['nome'] ?? null,
            'nif'              => $data['nif'] ?? null,
            'data_nascimento'  => !empty($data['data_nascimento']) ? $data['data_nascimento'] : null,
            'morada'           => $data['morada'] ?? null,
            'regime_casamento' => $data['regime_casamento'] ?? null,
            'profissao'        => $data['profissao'] ?? null,
            'rendimento_mensal'=> isset($data['rendimento_mensal']) && $data['rendimento_mensal'] !== ''
                ? $this->limpar_numero($data['rendimento_mensal']) : null,
            'telefone'         => $data['telefone'] ?? null,
            'email'            => $data['email'] ?? null,
        ];

        $existente = $this->get_titular($credito_id, $num_titular);

        if ($existente) {
            $payload['dateupdated'] = date('Y-m-d H:i:s');
            $this->db->where('id', $existente['id']);
            $this->db->update($this->tabela_titulares(), $payload);

            return $existente['id'];
        }

        $payload['credito_id']  = (int) $credito_id;
        $payload['num_titular'] = (int) $num_titular;
        $payload['dateadded']   = date('Y-m-d H:i:s');
        $this->db->insert($this->tabela_titulares(), $payload);

        return $this->db->insert_id();
    }

    public function delete_titular($credito_id, $num_titular)
    {
        $this->db->where('credito_id', (int) $credito_id);
        $this->db->where('num_titular', (int) $num_titular);
        $this->db->delete($this->tabela_titulares());
    }

    public function delete_doc($id)
    {
        $doc = $this->get_doc($id);
        if (!$doc) {
            return false;
        }

        $caminho = FCPATH . DPS_CREDITO_UPLOAD_PATH . $doc['credito_id'] . '/' . $doc['filename'];
        if (file_exists($caminho)) {
            unlink($caminho);
        }

        $this->db->where('id', $id)->delete($this->tabela_docs());

        return $this->db->affected_rows() > 0;
    }

    /* ---------------------------------------------------------------------
     * Notificação
     * ------------------------------------------------------------------ */

    /**
     * Avisa quem trata do crédito de que há um processo novo. Sem isto, o
     * processo nasce e fica à espera que alguém repare nele.
     */
    public function notificar_novo_processo($credito_id)
    {
        $processo = $this->get_processo($credito_id);
        if (!$processo) {
            return;
        }

        $destinatarios = $this->get_destinatarios();

        foreach ($destinatarios as $staff_id) {
            add_notification([
                'description'     => 'Novo processo de crédito: ' . $processo['cliente'],
                'touserid'        => $staff_id,
                'fromcompany'     => 1,
                'fromuserid'      => null,
                'link'            => 'dps_credito/view/' . $credito_id,
                'additional_data' => serialize([$processo['cliente']]),
            ]);
        }

        if (!empty($destinatarios)) {
            pusher_trigger_notification($destinatarios);
        }
    }

    /**
     * Avisa o comercial (dono do processo) de que faltam documentos.
     */
    public function notificar_documentos_em_falta($credito_id)
    {
        $processo = $this->get_processo($credito_id);
        if (!$processo || empty($processo['staff_id'])) {
            return;
        }

        add_notification([
            'description'     => 'Crédito: faltam documentos — ' . $processo['cliente'],
            'touserid'        => (int) $processo['staff_id'],
            'fromcompany'     => 1,
            'fromuserid'      => null,
            'link'            => 'leads/index/' . $processo['lead_id'],
            'additional_data' => serialize([$processo['cliente']]),
        ]);

        pusher_trigger_notification([(int) $processo['staff_id']]);
    }

    private function get_destinatarios()
    {
        $configurado = get_option('dps_credito_notificar_staff');

        if (!empty($configurado)) {
            return array_filter(array_map('intval', explode(',', $configurado)));
        }

        // Sem configuração explícita, avisamos os administradores
        $admins = $this->db->select('staffid')
            ->where('admin', 1)
            ->where('active', 1)
            ->get(db_prefix() . 'staff')
            ->result_array();

        return array_map('intval', array_column($admins, 'staffid'));
    }

    private function limpar_numero($valor)
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $valor = preg_replace('/[^0-9,.\-]/', '', (string) $valor);

        if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
            if (strrpos($valor, ',') > strrpos($valor, '.')) {
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
            } else {
                $valor = str_replace(',', '', $valor);
            }
        } elseif (strpos($valor, ',') !== false) {
            $valor = str_replace(',', '.', $valor);
        }

        return (float) $valor;
    }
}
