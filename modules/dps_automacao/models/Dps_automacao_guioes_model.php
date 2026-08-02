<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Guiões da Sofia: o admin cria-os, o comercial escolhe um para as suas
 * leads. A execução real pela Sofia fica fora do âmbito — quem quiser reagir
 * à escolha liga-se ao hook 'dps_sofia_guiao_escolhido'.
 */
class Dps_automacao_guioes_model extends App_Model
{
    private function tabela()
    {
        return db_prefix() . 'dps_automacao_guioes';
    }

    private function tabela_escolhas()
    {
        return db_prefix() . 'dps_automacao_guiao_escolhas';
    }

    public function get_guioes($apenas_ativos = false)
    {
        if ($apenas_ativos) {
            $this->db->where('ativo', 1);
        }

        return $this->db->order_by('nome', 'ASC')
            ->get($this->tabela())
            ->result_array();
    }

    public function get_guiao($id)
    {
        return $this->db->where('id', (int) $id)
            ->get($this->tabela())
            ->row_array();
    }

    public function guardar(array $dados, $id = null)
    {
        $registo = [
            'nome'       => $dados['nome'],
            'descricao'  => isset($dados['descricao']) ? $dados['descricao'] : null,
            'instrucoes' => isset($dados['instrucoes']) ? $dados['instrucoes'] : null,
            'ativo'      => !empty($dados['ativo']) ? 1 : 0,
        ];

        if ($id) {
            $registo['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id)->update($this->tabela(), $registo);

            return (int) $id;
        }

        $registo['created_by'] = (int) get_staff_user_id();
        $registo['dateadded']  = date('Y-m-d H:i:s');
        $this->db->insert($this->tabela(), $registo);

        return $this->db->insert_id();
    }

    /**
     * Apagar com rede: se já houver escolhas a apontar para o guião, só o
     * desativa (soft delete) para não deixar escolhas órfãs a referenciar um
     * id inexistente. Devolve 'apagado' ou 'desativado'.
     */
    public function apagar($id)
    {
        $id = (int) $id;

        $tem_escolhas = $this->db->where('guiao_id', $id)
            ->count_all_results($this->tabela_escolhas()) > 0;

        if ($tem_escolhas) {
            $this->db->where('id', $id)->update($this->tabela(), [
                'ativo'      => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return 'desativado';
        }

        $this->db->where('id', $id)->delete($this->tabela());

        return 'apagado';
    }

    /**
     * A escolha atual do comercial, com o nome do guião (para a UI destacar
     * o cartão certo). null se ainda não escolheu nada.
     */
    public function get_escolha_do_staff($staff_id)
    {
        return $this->db->select('esc.*, g.nome AS guiao_nome, g.ativo AS guiao_ativo')
            ->from($this->tabela_escolhas() . ' esc')
            ->join($this->tabela() . ' g', 'g.id = esc.guiao_id', 'left')
            ->where('esc.staff_id', (int) $staff_id)
            ->get()
            ->row_array();
    }

    /**
     * Grava/substitui a escolha do comercial. O REPLACE apoia-se na UNIQUE
     * de staff_id — uma escolha ativa por comercial, a nova apaga a antiga.
     * Dispara o hook a que a Sofia (ou outro módulo) se pode ligar.
     */
    public function escolher($guiao_id, $staff_id)
    {
        $this->db->replace($this->tabela_escolhas(), [
            'guiao_id'  => (int) $guiao_id,
            'staff_id'  => (int) $staff_id,
            'dateadded' => date('Y-m-d H:i:s'),
        ]);

        hooks()->do_action('dps_sofia_guiao_escolhido', [
            'guiao_id' => (int) $guiao_id,
            'staff_id' => (int) $staff_id,
        ]);

        return true;
    }
}
