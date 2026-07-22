<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_vendas_model extends App_Model
{
    /**
     * Estados da venda. O admin escolhe livremente entre eles.
     * A comissão é fixada ao chegar a "concluido".
     */
    public static $fluxo = ['pendente', 'contrato', 'concluido', 'falhou'];

    public function __construct()
    {
        parent::__construct();
    }

    public function tabela_vendas()
    {
        return db_prefix() . 'simulador_vendas';
    }

    public function tabela_docs()
    {
        return db_prefix() . 'vendas_docs';
    }

    public function tabela_historico()
    {
        return db_prefix() . 'vendas_historico';
    }

    public function tabela_regras()
    {
        return db_prefix() . 'comissao_regras';
    }

    /* ---------------------------------------------------------------------
     * Vendas
     * ------------------------------------------------------------------ */

    /**
     * @param array $filtros estado, empreendimento, comercial_id
     * @param bool  $apenas_minhas restringe ao staff autenticado
     */
    public function get_vendas($filtros = [], $apenas_minhas = false)
    {
        $this->db->select('v.*, CONCAT(s.firstname, " ", s.lastname) AS comercial_nome');
        $this->db->from($this->tabela_vendas() . ' v');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = v.staff_id', 'left');

        if ($apenas_minhas) {
            $this->db->where('v.staff_id', get_staff_user_id());
        }

        if (!empty($filtros['estado'])) {
            if ($filtros['estado'] === 'historico') {
                $this->db->where('v.estado IS NULL');
            } else {
                $this->db->where('v.estado', $filtros['estado']);
            }
        }

        if (!empty($filtros['empreendimento'])) {
            $this->db->where('v.empreendimento', $filtros['empreendimento']);
        }

        if (!empty($filtros['comercial_id'])) {
            $this->db->where('v.staff_id', $filtros['comercial_id']);
        }

        $this->db->order_by('v.id', 'DESC');

        return $this->db->get()->result_array();
    }

    public function get_venda($id)
    {
        $this->db->select('v.*, CONCAT(s.firstname, " ", s.lastname) AS comercial_nome');
        $this->db->from($this->tabela_vendas() . ' v');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = v.staff_id', 'left');
        $this->db->where('v.id', $id);

        return $this->db->get()->row_array();
    }

    public function add_venda($data)
    {
        $agora = date('Y-m-d H:i:s');

        $venda = [
            'empreendimento'   => $data['empreendimento'] ?? '',
            'unidade'          => $data['unidade'] ?? '',
            'cliente'          => $data['cliente'] ?? '',
            'cliente_morada'   => $data['cliente_morada'] ?? null,
            'cliente_telefone' => $data['cliente_telefone'] ?? null,
            'cliente_email'    => $data['cliente_email'] ?? null,
            'regime_civil'     => $data['regime_civil'] ?? null,
            'valor'            => $this->limpar_numero($data['valor'] ?? 0),
            'data_venda'       => !empty($data['data_venda']) ? to_sql_date($data['data_venda']) : null,
            'estado'           => 'pendente',
            'origem'           => $data['origem'] ?? 'formulario',
            'lead_id'          => !empty($data['lead_id']) ? (int) $data['lead_id'] : null,
            'staff_id'         => !empty($data['staff_id']) ? (int) $data['staff_id'] : get_staff_user_id(),
            'comissao_estado'  => 'na',
            'date_created'     => $agora,
            'created_by'       => get_staff_user_id(),
        ];

        // A taxa vem da regra do empreendimento, mas fica gravada na venda como
        // snapshot: mudar a regra amanhã não deve reescrever vendas de hoje.
        $regra = $this->get_regra($venda['empreendimento']);
        if ($regra) {
            $venda['taxa']           = $regra['taxa'];
            $venda['cpcv_taxa']      = $regra['cpcv_taxa'];
            $venda['escritura_taxa'] = $regra['escritura_taxa'];
        }

        // Permitir sobrepor a taxa caso a caso (negociações fora da regra)
        if (isset($data['taxa']) && $data['taxa'] !== '') {
            $venda['taxa'] = $this->limpar_numero($data['taxa']);
        }

        $this->db->insert($this->tabela_vendas(), $venda);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            $this->registar_historico($insert_id, null, 'pendente', 'Venda criada');
            hooks()->do_action('dps_venda_criada', $insert_id);
        }

        return $insert_id;
    }

    public function update_venda($id, $data)
    {
        $update = [
            'empreendimento'   => $data['empreendimento'] ?? '',
            'unidade'          => $data['unidade'] ?? '',
            'cliente'          => $data['cliente'] ?? '',
            'cliente_morada'   => $data['cliente_morada'] ?? null,
            'cliente_telefone' => $data['cliente_telefone'] ?? null,
            'cliente_email'    => $data['cliente_email'] ?? null,
            'regime_civil'     => $data['regime_civil'] ?? null,
            'valor'            => $this->limpar_numero($data['valor'] ?? 0),
            'data_venda'       => !empty($data['data_venda']) ? to_sql_date($data['data_venda']) : null,
            'dateupdated'      => date('Y-m-d H:i:s'),
        ];

        if (isset($data['taxa']) && $data['taxa'] !== '') {
            $update['taxa'] = $this->limpar_numero($data['taxa']);
        }

        if (!empty($data['lead_id'])) {
            $update['lead_id'] = (int) $data['lead_id'];
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_vendas(), $update);

        return $this->db->affected_rows() > 0;
    }

    public function delete_venda($id)
    {
        $docs = $this->get_docs($id);
        foreach ($docs as $doc) {
            $this->delete_doc($doc['id']);
        }

        $this->db->where('venda_id', $id)->delete($this->tabela_historico());

        $this->db->where('id', $id)->delete($this->tabela_vendas());

        return $this->db->affected_rows() > 0;
    }

    /* ---------------------------------------------------------------------
     * Workflow de estados
     * ------------------------------------------------------------------ */

    public function transicao_valida($de, $para)
    {
        // O admin escolhe livremente qualquer estado do fluxo.
        return in_array($para, self::$fluxo, true);
    }

    public function mudar_estado($id, $novo_estado, $nota = null)
    {
        $venda = $this->get_venda($id);
        if (!$venda) {
            return ['ok' => false, 'erro' => 'Venda não encontrada.'];
        }

        $estado_atual = $venda['estado'];

        if ($estado_atual === $novo_estado) {
            return ['ok' => false, 'erro' => 'A venda já se encontra nesse estado.'];
        }

        if (!$this->transicao_valida($estado_atual, $novo_estado)) {
            return ['ok' => false, 'erro' => 'Estado inválido.'];
        }

        $update = [
            'estado'      => $novo_estado,
            'dateupdated' => date('Y-m-d H:i:s'),
        ];

        // É ao chegar a "concluído" que a comissão passa a ser devida.
        if ($novo_estado === 'concluido') {
            $calculo = $this->calcular_comissao($venda);

            // Fixar uma comissão de 0 € em silêncio seria o pior desfecho:
            // ninguém repara e o comercial não recebe. Travamos e dizemos porquê.
            if ($calculo['taxa'] <= 0) {
                return [
                    'ok'   => false,
                    'erro' => 'Não há taxa de comissão definida para "' . $venda['empreendimento'] . '". '
                        . 'Defina a regra em Vendas &gt; Regras de Comissão, ou indique a taxa na própria venda, '
                        . 'antes de a marcar como Concluída.',
                ];
            }

            $update['comissao_total']  = $calculo['valor'];
            $update['comissao_estado'] = 'a_receber';
        } elseif ($novo_estado === 'falhou') {
            // Venda falhada não gera comissão.
            $update['comissao_estado'] = 'na';
            $update['comissao_total']  = 0;
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_vendas(), $update);

        $this->registar_historico($id, $estado_atual, $novo_estado, $nota);

        hooks()->do_action('dps_venda_estado_alterado', [
            'venda_id'    => $id,
            'estado_de'   => $estado_atual,
            'estado_para' => $novo_estado,
        ]);

        return ['ok' => true];
    }

    public function registar_historico($venda_id, $de, $para, $nota = null)
    {
        $this->db->insert($this->tabela_historico(), [
            'venda_id'    => $venda_id,
            'estado_de'   => $de,
            'estado_para' => $para,
            'staff_id'    => get_staff_user_id(),
            'nota'        => $nota,
            'dateadded'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function get_historico($venda_id)
    {
        $this->db->select('h.*, CONCAT(s.firstname, " ", s.lastname) AS staff_nome');
        $this->db->from($this->tabela_historico() . ' h');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = h.staff_id', 'left');
        $this->db->where('h.venda_id', $venda_id);
        $this->db->order_by('h.id', 'ASC');

        return $this->db->get()->result_array();
    }

    /* ---------------------------------------------------------------------
     * Comissões
     * ------------------------------------------------------------------ */

    /**
     * A taxa gravada na venda ganha sempre à regra: é o snapshot do que foi
     * acordado. A regra só entra quando a venda não tem taxa própria.
     */
    public function calcular_comissao($venda)
    {
        $valor = (float) $venda['valor'];
        $taxa  = isset($venda['taxa']) ? (float) $venda['taxa'] : 0;
        $fonte = 'venda';

        if ($taxa <= 0) {
            $regra = $this->get_regra($venda['empreendimento']);
            if ($regra) {
                $taxa  = (float) $regra['taxa'];
                $fonte = 'regra';
            }
        }

        return [
            'valor' => round($valor * $taxa / 100, 2),
            'taxa'  => $taxa,
            'fonte' => $fonte,
        ];
    }

    public function get_comissoes($apenas_minhas = false)
    {
        $this->db->select('v.*, CONCAT(s.firstname, " ", s.lastname) AS comercial_nome');
        $this->db->from($this->tabela_vendas() . ' v');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = v.staff_id', 'left');
        $this->db->where_in('v.comissao_estado', ['a_receber', 'recebida']);

        if ($apenas_minhas) {
            $this->db->where('v.staff_id', get_staff_user_id());
        }

        $this->db->order_by('v.staff_id', 'ASC');
        $this->db->order_by('v.id', 'DESC');

        return $this->db->get()->result_array();
    }

    public function marcar_comissao_recebida($venda_id)
    {
        $this->db->where('id', $venda_id);
        $this->db->update($this->tabela_vendas(), [
            'comissao_estado' => 'recebida',
            'dateupdated'     => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    /* ---------------------------------------------------------------------
     * Regras de comissão
     * ------------------------------------------------------------------ */

    /**
     * Garante que todo o empreendimento que já teve uma venda tem uma regra na
     * tabela (mesmo que a 0%), para aparecer sempre em Regras de Comissão.
     */
    public function sincronizar_regras_com_vendas()
    {
        $emps = $this->db->select('DISTINCT(empreendimento) AS empreendimento')
            ->where('empreendimento IS NOT NULL')
            ->where('empreendimento <>', '')
            ->where('empreendimento <>', 'undefined')
            ->get($this->tabela_vendas())
            ->result_array();

        foreach ($emps as $e) {
            $emp = trim($e['empreendimento']);
            $existe = $this->db
                ->where('LOWER(TRIM(empreendimento))', strtolower($emp))
                ->count_all_results($this->tabela_regras());

            if (!$existe) {
                $this->db->insert($this->tabela_regras(), [
                    'empreendimento' => $emp,
                    'taxa'           => 0,
                    'ativo'          => 1,
                    'notas'          => 'TAXA POR DEFINIR — apareceu numa venda.',
                    'dateadded'      => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function get_regras()
    {
        $this->db->order_by('empreendimento', 'ASC');

        return $this->db->get($this->tabela_regras())->result_array();
    }

    public function get_regra($empreendimento)
    {
        if (empty($empreendimento)) {
            return null;
        }

        // Comparação insensível a maiúsculas e espaços: o campo empreendimento
        // é texto livre e o histórico tem variações de escrita.
        $this->db->where('LOWER(TRIM(empreendimento))', strtolower(trim($empreendimento)));
        $this->db->where('ativo', 1);

        return $this->db->get($this->tabela_regras())->row_array();
    }

    public function guardar_regra($data, $id = null)
    {
        $payload = [
            'empreendimento' => trim($data['empreendimento']),
            'taxa'           => $this->limpar_numero($data['taxa'] ?? 0),
            'cpcv_taxa'      => ($data['cpcv_taxa'] ?? '') !== '' ? $this->limpar_numero($data['cpcv_taxa']) : null,
            'escritura_taxa' => ($data['escritura_taxa'] ?? '') !== '' ? $this->limpar_numero($data['escritura_taxa']) : null,
            'ativo'          => isset($data['ativo']) ? 1 : 0,
            'notas'          => $data['notas'] ?? null,
            'updated_by'     => get_staff_user_id(),
        ];

        // O empreendimento é UNIQUE. Se já existir uma regra com esse nome
        // (ex.: editar uma das regras semeadas), actualizamos essa em vez de
        // tentar inserir — senão o INSERT rebenta com erro de chave duplicada
        // (que dava página em branco).
        if (!$id) {
            $existente = $this->db
                ->where('LOWER(TRIM(empreendimento))', strtolower(trim($payload['empreendimento'])))
                ->get($this->tabela_regras())
                ->row_array();

            if ($existente) {
                $id = $existente['id'];
            }
        }

        if ($id) {
            $this->db->where('id', $id);
            $this->db->update($this->tabela_regras(), $payload);

            return $id;
        }

        $payload['dateadded'] = date('Y-m-d H:i:s');
        $this->db->insert($this->tabela_regras(), $payload);

        return $this->db->insert_id();
    }

    public function delete_regra($id)
    {
        $this->db->where('id', $id)->delete($this->tabela_regras());

        return $this->db->affected_rows() > 0;
    }

    /**
     * Lista de empreendimentos para os dropdowns: junta os que têm regra com os
     * que já aparecem em vendas, para não perder nenhum do histórico.
     */
    public function get_empreendimentos()
    {
        $das_regras = $this->db->select('empreendimento')->get($this->tabela_regras())->result_array();

        $das_vendas = $this->db->select('DISTINCT(empreendimento) AS empreendimento')
            ->where('empreendimento IS NOT NULL')
            ->where('empreendimento <>', '')
            ->get($this->tabela_vendas())
            ->result_array();

        $todos = array_merge(
            array_column($das_regras, 'empreendimento'),
            array_column($das_vendas, 'empreendimento')
        );

        $todos = array_values(array_unique(array_filter(array_map('trim', $todos))));
        sort($todos);

        return $todos;
    }

    /* ---------------------------------------------------------------------
     * Documentos
     * ------------------------------------------------------------------ */

    public function get_docs($venda_id)
    {
        $this->db->where('venda_id', $venda_id);
        $this->db->order_by('id', 'ASC');

        return $this->db->get($this->tabela_docs())->result_array();
    }

    public function get_doc($id)
    {
        $this->db->where('id', $id);

        return $this->db->get($this->tabela_docs())->row_array();
    }

    public function add_doc($venda_id, $tipo, $filename, $original_name)
    {
        $this->db->insert($this->tabela_docs(), [
            'venda_id'      => $venda_id,
            'tipo'          => $tipo,
            'filename'      => $filename,
            'original_name' => $original_name,
            'uploaded_by'   => get_staff_user_id(),
            'dateadded'     => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id();
    }

    public function delete_doc($id)
    {
        $doc = $this->get_doc($id);
        if (!$doc) {
            return false;
        }

        $caminho = FCPATH . DPS_VENDAS_UPLOAD_PATH . $doc['venda_id'] . '/' . $doc['filename'];
        if (file_exists($caminho)) {
            unlink($caminho);
        }

        $this->db->where('id', $id)->delete($this->tabela_docs());

        return $this->db->affected_rows() > 0;
    }

    /**
     * Aceita "250.000,00", "250000.00" e "250 000" — o utilizador escreve
     * valores em euros à maneira portuguesa e não vale a pena chateá-lo com isso.
     */
    private function limpar_numero($valor)
    {
        if (is_numeric($valor)) {
            return (float) $valor;
        }

        $valor = preg_replace('/[^0-9,.\-]/', '', (string) $valor);

        // Se tem vírgula e ponto, o último separador é o decimal
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
