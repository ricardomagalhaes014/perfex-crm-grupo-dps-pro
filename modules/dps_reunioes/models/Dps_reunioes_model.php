<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_reunioes_model extends App_Model
{
    public function tabela()
    {
        return db_prefix() . 'dps_reunioes';
    }

    /**
     * Cria a sala e o link do Jitsi.
     *
     * O nome da sala leva um pedaço aleatório de propósito. Uma sala com nome
     * previsível — "dps-lead-431" — é uma sala em que qualquer pessoa que
     * adivinhe o número entra sem ser convidada, e no Jitsi não há porteiro
     * por omissão. Com 12 caracteres aleatórios isso deixa de ser um risco.
     *
     * O prefixo legível serve só para quem vê o link perceber do que se trata.
     */
    public function gerar_sala($nome_cliente)
    {
        $limpo = strtolower((string) $nome_cliente);
        $limpo = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $limpo) ?: $limpo;
        $limpo = preg_replace('/[^a-z0-9]+/', '-', $limpo);
        $limpo = trim(substr($limpo, 0, 24), '-');

        if ($limpo === '') {
            $limpo = 'reuniao';
        }

        return 'dps-' . $limpo . '-' . bin2hex(random_bytes(6));
    }

    public function criar(array $d)
    {
        $sala = $this->gerar_sala($d['cliente_nome'] ?? '');

        $reuniao = [
            'rel_type'         => $d['rel_type'] ?? 'lead',
            'rel_id'           => (int) ($d['rel_id'] ?? 0),
            'assunto'          => trim((string) ($d['assunto'] ?? '')) ?: 'Reunião online',
            'data_hora'        => $d['data_hora'],
            'duracao_min'      => (int) ($d['duracao_min'] ?? 30) ?: 30,
            'staff_id'         => (int) $d['staff_id'],
            'convidado_id'     => !empty($d['convidado_id']) ? (int) $d['convidado_id'] : null,
            'convite_estado'   => !empty($d['convidado_id']) ? 'pendente' : null,
            'sala'             => $sala,
            'link'             => 'https://meet.jit.si/' . $sala,
            'estado'           => 'agendada',
            'cliente_nome'     => $d['cliente_nome'] ?? null,
            'cliente_email'    => $d['cliente_email'] ?? null,
            'cliente_telefone' => $d['cliente_telefone'] ?? null,
            'date_created'     => date('Y-m-d H:i:s'),
            'created_by'       => get_staff_user_id(),
        ];

        $this->db->insert($this->tabela(), $reuniao);

        return $this->db->insert_id();
    }

    public function get($id)
    {
        return $this->db->select('r.*, CONCAT(s.firstname," ",s.lastname) AS comercial,
                                  s.phonenumber AS comercial_tel, s.email AS comercial_email,
                                  CONCAT(c.firstname," ",c.lastname) AS convidado')
                        ->from($this->tabela() . ' r')
                        ->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left')
                        ->join(db_prefix() . 'staff c', 'c.staffid = r.convidado_id', 'left')
                        ->where('r.id', (int) $id)
                        ->get()->row_array();
    }

    /** Reuniões de uma lead ou de um cliente, mais recentes primeiro. */
    public function da_ficha($rel_type, $rel_id)
    {
        return $this->db->select('r.*, CONCAT(s.firstname," ",s.lastname) AS comercial,
                                  CONCAT(c.firstname," ",c.lastname) AS convidado')
                        ->from($this->tabela() . ' r')
                        ->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left')
                        ->join(db_prefix() . 'staff c', 'c.staffid = r.convidado_id', 'left')
                        ->where('r.rel_type', $rel_type)
                        ->where('r.rel_id', (int) $rel_id)
                        ->order_by('r.data_hora', 'DESC')
                        ->get()->result_array();
    }

    public function actualizar($id, array $d)
    {
        $this->db->where('id', (int) $id)->update($this->tabela(), $d);

        return $this->db->affected_rows() >= 0;
    }
}
