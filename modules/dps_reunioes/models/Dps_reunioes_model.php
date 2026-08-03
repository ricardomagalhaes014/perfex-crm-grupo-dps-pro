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

        $id = (int) $this->db->insert_id();

        /*
         * A agenda é preenchida aqui, e não em cada um dos três sítios que
         * marcam reuniões (ficha da lead, agenda partilhada, reunião de
         * equipa) — um deles esquecer-se-ia, e seria o que ninguém testa.
         */
        if ($id && function_exists('dps_reunioes_criar_eventos')) {
            dps_reunioes_criar_eventos($reuniao + ['id' => $id]);
        }

        return $id;
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

    /* =====================================================================
     * AGENDA PARTILHADA
     *
     * A ideia é a do Calendly, virada para dentro: quem tem a agenda disputada
     * publica os bocados de semana em que aceita reuniões, e os colegas
     * escolhem um sem trocar mensagens a perguntar "podes às três?".
     * ================================================================== */

    public function tabela_horario()      { return db_prefix() . 'dps_reunioes_horario'; }
    public function tabela_bloqueio()     { return db_prefix() . 'dps_reunioes_bloqueio'; }
    public function tabela_partilha()     { return db_prefix() . 'dps_reunioes_partilha'; }
    public function tabela_participante() { return db_prefix() . 'dps_reunioes_participante'; }

    /** Regras de quem publica. Devolve sempre algo — os valores por omissão. */
    public function get_partilha($staff_id)
    {
        $r = $this->db->where('staff_id', (int) $staff_id)
                      ->get($this->tabela_partilha())->row_array();

        return $r ?: [
            'staff_id'       => (int) $staff_id,
            'publicada'      => 0,
            'duracao_min'    => 30,
            'antecedencia_h' => 4,
            'horizonte_dias' => 21,
            'intervalo_min'  => 0,
            'nota'           => null,
        ];
    }

    public function guardar_partilha($staff_id, array $d)
    {
        $linha = [
            'staff_id'       => (int) $staff_id,
            'publicada'      => !empty($d['publicada']) ? 1 : 0,
            'duracao_min'    => max(10, (int) ($d['duracao_min'] ?? 30)),
            'antecedencia_h' => max(0, (int) ($d['antecedencia_h'] ?? 4)),
            'horizonte_dias' => min(90, max(1, (int) ($d['horizonte_dias'] ?? 21))),
            'intervalo_min'  => max(0, (int) ($d['intervalo_min'] ?? 0)),
            'nota'           => trim((string) ($d['nota'] ?? '')) ?: null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $existe = $this->db->where('staff_id', (int) $staff_id)
                           ->count_all_results($this->tabela_partilha());

        if ($existe) {
            $this->db->where('staff_id', (int) $staff_id)->update($this->tabela_partilha(), $linha);
        } else {
            $this->db->insert($this->tabela_partilha(), $linha);
        }
    }

    /** Horário semanal, agrupado por dia (1 = segunda ... 7 = domingo). */
    public function get_horario($staff_id)
    {
        $linhas = $this->db->where('staff_id', (int) $staff_id)
                           ->order_by('dia_semana, hora_inicio')
                           ->get($this->tabela_horario())->result_array();

        $por_dia = array_fill_keys(range(1, 7), []);
        foreach ($linhas as $l) {
            $por_dia[(int) $l['dia_semana']][] = $l;
        }

        return $por_dia;
    }

    /**
     * Substitui o horário inteiro da pessoa.
     *
     * Apaga e volta a escrever em vez de comparar linha a linha: o horário é
     * pequeno e o formulário devolve-o completo, e uma actualização parcial
     * mal feita deixaria bocados de horário antigo a aceitar reuniões.
     */
    public function guardar_horario($staff_id, array $blocos)
    {
        $this->db->where('staff_id', (int) $staff_id)->delete($this->tabela_horario());

        foreach ($blocos as $b) {
            $dia = (int) ($b['dia_semana'] ?? 0);
            $ini = trim((string) ($b['hora_inicio'] ?? ''));
            $fim = trim((string) ($b['hora_fim'] ?? ''));

            if ($dia < 1 || $dia > 7 || $ini === '' || $fim === '' || $ini >= $fim) {
                continue;
            }

            $this->db->insert($this->tabela_horario(), [
                'staff_id'    => (int) $staff_id,
                'dia_semana'  => $dia,
                'hora_inicio' => $ini . (strlen($ini) === 5 ? ':00' : ''),
                'hora_fim'    => $fim . (strlen($fim) === 5 ? ':00' : ''),
            ]);
        }
    }

    public function get_bloqueios($staff_id, $de = null, $ate = null)
    {
        $this->db->where('staff_id', (int) $staff_id);
        if ($de)  { $this->db->where('data >=', $de); }
        if ($ate) { $this->db->where('data <=', $ate); }

        return $this->db->order_by('data, hora_inicio')
                        ->get($this->tabela_bloqueio())->result_array();
    }

    public function add_bloqueio($staff_id, $data, $ini = null, $fim = null, $motivo = null)
    {
        if (!$data) {
            return false;
        }

        // Sem horas = dia inteiro. Com horas, têm de fazer sentido.
        if ($ini && $fim && $ini >= $fim) {
            return false;
        }

        $this->db->insert($this->tabela_bloqueio(), [
            'staff_id'    => (int) $staff_id,
            'data'        => $data,
            'hora_inicio' => $ini ?: null,
            'hora_fim'    => $fim ?: null,
            'motivo'      => trim((string) $motivo) ?: null,
        ]);

        return true;
    }

    public function del_bloqueio($id, $staff_id)
    {
        $this->db->where('id', (int) $id)->where('staff_id', (int) $staff_id)
                 ->delete($this->tabela_bloqueio());
    }

    /** Quem tem agenda publicada, com nome — para a lista de "com quem marcar". */
    public function agendas_publicadas($excepto = 0)
    {
        $this->db->select('p.staff_id, p.nota, p.duracao_min,
                           CONCAT(s.firstname," ",s.lastname) AS nome')
                 ->from($this->tabela_partilha() . ' p')
                 ->join(db_prefix() . 'staff s', 's.staffid = p.staff_id')
                 ->where('p.publicada', 1)
                 ->where('s.active', 1);

        if ($excepto) {
            $this->db->where('p.staff_id <>', (int) $excepto);
        }

        return $this->db->order_by('nome')->get()->result_array();
    }

    /**
     * Intervalos em que a pessoa já está tomada.
     *
     * Conta tudo o que a prende: reuniões que conduz, reuniões para que foi
     * convidada e reuniões de equipa em que participa. Uma agenda que só
     * olhasse para as próprias reuniões oferecia horas em que a pessoa está
     * dentro da reunião de outro.
     */
    public function ocupacao($staff_id, $de, $ate)
    {
        $t = $this->tabela();
        $p = $this->tabela_participante();
        $id = (int) $staff_id;

        $linhas = $this->db->select('r.data_hora, r.duracao_min')
            ->from($t . ' r')
            ->where('r.estado <>', 'cancelada')
            ->where('r.data_hora >=', $de . ' 00:00:00')
            ->where('r.data_hora <=', $ate . ' 23:59:59')
            ->group_start()
                ->where('r.staff_id', $id)
                ->or_where('r.convidado_id', $id)
                ->or_where("r.id IN (SELECT reuniao_id FROM {$p} WHERE staff_id = {$id}
                                      AND estado <> 'recusado')", null, false)
            ->group_end()
            ->get()->result_array();

        $ocupado = [];
        foreach ($linhas as $l) {
            $ini = strtotime($l['data_hora']);
            $ocupado[] = [$ini, $ini + max(10, (int) $l['duracao_min']) * 60];
        }

        return $ocupado;
    }

    /**
     * Os horários livres de alguém, dia a dia.
     *
     * @return array ['2026-08-05' => [['inicio'=>ts,'hhmm'=>'14:30'], ...], ...]
     */
    public function slots_livres($staff_id, $duracao_pedida = null)
    {
        $regras = $this->get_partilha($staff_id);

        if (empty($regras['publicada'])) {
            return [];
        }

        $duracao  = (int) ($duracao_pedida ?: $regras['duracao_min']);
        $passo    = ($duracao + (int) $regras['intervalo_min']) * 60;
        $horizonte = (int) $regras['horizonte_dias'];

        $hoje = date('Y-m-d');
        $fim  = date('Y-m-d', strtotime('+' . $horizonte . ' days'));

        $horario   = $this->get_horario($staff_id);
        $bloqueios = $this->get_bloqueios($staff_id, $hoje, $fim);
        $ocupacao  = $this->ocupacao($staff_id, $hoje, $fim);

        // Cedo demais não vale: ninguém quer uma reunião marcada para daqui a
        // dez minutos por alguém que não sabe onde a pessoa está.
        $nao_antes = time() + ((int) $regras['antecedencia_h'] * 3600);

        $dias = [];

        for ($i = 0; $i <= $horizonte; $i++) {
            $data = date('Y-m-d', strtotime($hoje . ' +' . $i . ' days'));
            $dow  = (int) date('N', strtotime($data));

            if (empty($horario[$dow])) {
                continue;
            }

            $livres = [];

            foreach ($horario[$dow] as $bloco) {
                $ini_bloco = strtotime($data . ' ' . $bloco['hora_inicio']);
                $fim_bloco = strtotime($data . ' ' . $bloco['hora_fim']);

                for ($t = $ini_bloco; $t + $duracao * 60 <= $fim_bloco; $t += $passo) {
                    $t_fim = $t + $duracao * 60;

                    if ($t < $nao_antes) {
                        continue;
                    }
                    if ($this->colide($t, $t_fim, $ocupacao)) {
                        continue;
                    }
                    if ($this->bloqueado($t, $t_fim, $data, $bloqueios)) {
                        continue;
                    }

                    $livres[] = ['inicio' => $t, 'hhmm' => date('H:i', $t)];
                }
            }

            if ($livres) {
                $dias[$data] = $livres;
            }
        }

        return $dias;
    }

    /** Dois intervalos tocam-se? (fim aberto: 14:00-14:30 não colide com 14:30-15:00) */
    private function colide($ini, $fim, array $intervalos)
    {
        foreach ($intervalos as $iv) {
            if ($ini < $iv[1] && $fim > $iv[0]) {
                return true;
            }
        }

        return false;
    }

    private function bloqueado($ini, $fim, $data, array $bloqueios)
    {
        foreach ($bloqueios as $b) {
            if ($b['data'] !== $data) {
                continue;
            }
            // Sem horas = o dia todo.
            if (empty($b['hora_inicio']) || empty($b['hora_fim'])) {
                return true;
            }
            $b_ini = strtotime($data . ' ' . $b['hora_inicio']);
            $b_fim = strtotime($data . ' ' . $b['hora_fim']);

            if ($ini < $b_fim && $fim > $b_ini) {
                return true;
            }
        }

        return false;
    }

    /**
     * O horário ainda está livre?
     *
     * Repetido no momento de gravar, e não só quando o ecrã foi desenhado:
     * dois comerciais com a mesma página aberta escolhem o mesmo horário e o
     * segundo tem de ouvir um não.
     */
    public function slot_valido($staff_id, $data_hora, $duracao)
    {
        $dias = $this->slots_livres($staff_id, $duracao);
        $data = date('Y-m-d', strtotime($data_hora));
        $alvo = strtotime($data_hora);

        foreach ($dias[$data] ?? [] as $s) {
            if ((int) $s['inicio'] === $alvo) {
                return true;
            }
        }

        return false;
    }

    /* ---------------------------------------------------------------------
     * Participantes internos (reunião de equipa)
     * ------------------------------------------------------------------ */

    public function participantes($reuniao_id)
    {
        return $this->db->select('p.*, CONCAT(s.firstname," ",s.lastname) AS nome, s.email, s.phonenumber')
                        ->from($this->tabela_participante() . ' p')
                        ->join(db_prefix() . 'staff s', 's.staffid = p.staff_id')
                        ->where('p.reuniao_id', (int) $reuniao_id)
                        ->order_by('nome')
                        ->get()->result_array();
    }

    public function set_participantes($reuniao_id, array $staff_ids)
    {
        $this->db->where('reuniao_id', (int) $reuniao_id)->delete($this->tabela_participante());

        foreach (array_unique(array_map('intval', $staff_ids)) as $id) {
            if ($id > 0) {
                $this->db->insert($this->tabela_participante(), [
                    'reuniao_id' => (int) $reuniao_id,
                    'staff_id'   => $id,
                    'estado'     => 'convidado',
                ]);
            }
        }
    }

    /** As reuniões internas em que esta pessoa entra. */
    public function minhas_internas($staff_id, $futuras = true)
    {
        $t = $this->tabela();
        $p = $this->tabela_participante();
        $id = (int) $staff_id;

        $this->db->select('r.*, CONCAT(s.firstname," ",s.lastname) AS anfitriao')
                 ->from($t . ' r')
                 ->join(db_prefix() . 'staff s', 's.staffid = r.staff_id', 'left')
                 ->where('r.rel_type', 'interna')
                 ->where('r.estado <>', 'cancelada')
                 ->group_start()
                     ->where('r.staff_id', $id)
                     ->or_where("r.id IN (SELECT reuniao_id FROM {$p} WHERE staff_id = {$id})", null, false)
                 ->group_end();

        if ($futuras) {
            $this->db->where('r.data_hora >=', date('Y-m-d 00:00:00'));
        }

        return $this->db->order_by('r.data_hora')->get()->result_array();
    }
}
