<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_painel_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function t_vendas()
    {
        return db_prefix() . 'simulador_vendas';
    }

    private function t_overlay()
    {
        return db_prefix() . 'dps_painel_vendas';
    }

    private function t_despesas()
    {
        return db_prefix() . 'dps_painel_despesas';
    }

    private function t_recebimento()
    {
        return db_prefix() . 'dps_painel_recebimento';
    }

    /* =====================================================================
     * REGRAS DE NEGÓCIO (definições do painel)
     *
     * Quatro parâmetros guardados em options, todos editáveis em
     * /admin/dps_painel/definicoes:
     *   - director_id      quem leva o override da direcção
     *   - director_pct     percentagem do override
     *   - director_base    sobre o quê incide (recebida | paga | venda)
     *   - comerciais_100   quem leva 100% do que a DPS recebe
     * ================================================================== */

    const DIRECTOR_ID_OMISSAO      = 46;   // Cláudio
    const DIRECTOR_PCT_OMISSAO     = 0.5;  // 0,5%
    const DIRECTOR_BASE_OMISSAO    = 'venda'; // 0,5 pontos do preço da venda
    const COMERCIAIS_100_OMISSAO   = '17'; // Samara
    /*
     * Comerciais a 0%: vendem, mas a comissão NÃO sai de casa — fica na
     * empresa. É o caso do próprio Ricardo (staff 1). Não é uma comissão de
     * zero euros a pagar: é não haver pagamento nenhum, nem agora nem no
     * futuro, e por isso também não gera override para a direção.
     */
    const COMERCIAIS_0_OMISSAO     = '1';  // Ricardo (a comissão fica na casa)

    public function regras_config()
    {
        /*
         * get_option() devolve '' tanto para "opção nunca definida" como para
         * "definida deliberadamente a vazio". Sem distinguir os dois casos,
         * limpar a lista dos 100% voltaria a impor a Samara na gravação
         * seguinte. Daí o marcador: só antes da primeira gravação é que os
         * valores de fábrica entram.
         */
        $ja_guardado = (string) get_option('dps_painel_regras_guardadas') === '1';

        $id   = (string) get_option('dps_painel_director_id');
        $pct  = (string) get_option('dps_painel_director_pct');
        $base = (string) get_option('dps_painel_director_base');
        $cem  = (string) get_option('dps_painel_comerciais_100');
        $zero = (string) get_option('dps_painel_comerciais_0');

        if (!$ja_guardado) {
            if ($id === '')   { $id   = (string) self::DIRECTOR_ID_OMISSAO; }
            if ($pct === '')  { $pct  = (string) self::DIRECTOR_PCT_OMISSAO; }
            if ($cem === '')  { $cem  = self::COMERCIAIS_100_OMISSAO; }
            if ($zero === '') { $zero = self::COMERCIAIS_0_OMISSAO; }
        }
        /*
         * Por omissão o override incide sobre o VALOR DA VENDA, não sobre a
         * comissão. Regra do dono, dita assim: "se a comissão for 5% é 0,5;
         * se for 4 é 0,5; seja qual for é 0,5". Ou seja, os 0,5 vivem na mesma
         * escala das taxas do empreendimento — são meio ponto percentual do
         * preço, e a DPS fica com (taxa − 0,5) pontos.
         */
        if (!in_array($base, ['recebida', 'paga', 'venda'], true)) {
            $base = self::DIRECTOR_BASE_OMISSAO;
        }

        return [
            'director_id'    => (int) $id,
            'director_pct'   => $pct === '' ? 0.0 : $this->num($pct),
            'director_base'  => $base,
            'comerciais_100' => $this->csv_ids($cem),
            'comerciais_0'   => $this->csv_ids($zero),
        ];
    }

    /**
     * RECIBOS À ESPERA DE PAGAMENTO.
     *
     * Um comercial (ou o Cláudio, pelo override) entrega o recibo e fica a
     * aguardar que a direção pague. Até agora esse estado só se via entrando
     * no quadro de comissões e procurando linha a linha — e o que não se vê
     * não se paga. Regra do dono (05/08/2026): "o comercial emite recibo, é
     * por pagar; quando eu pago e valido como pago, é paga".
     *
     * Junta as duas origens numa lista só, porque a pergunta é uma: a quem é
     * que eu devo dinheiro agora.
     *
     * @return array linhas prontas a mostrar, mais antigas primeiro
     */
    public function recibos_a_pagamento()
    {
        $v = db_prefix() . 'simulador_vendas';
        $s = db_prefix() . 'staff';
        $d = db_prefix() . 'vendas_docs';

        $linhas = [];

        /* 1) COMERCIAL — recibo entregue, parcela por pagar. */
        $sql = "SELECT ve.id, ve.empreendimento, ve.unidade, ve.cliente, ve.valor,
                       ve.comissao_total, ve.cpcv_taxa, ve.escritura_taxa,
                       ve.cpcv_pago, ve.escritura_paga, ve.comissao_recibo_doc,
                       ve.staff_id, CONCAT(st.firstname,' ',st.lastname) AS quem,
                       doc.dateadded AS recibo_em
                  FROM {$v} ve
             LEFT JOIN {$s} st ON st.staffid = ve.staff_id
             LEFT JOIN {$d} doc ON doc.id = ve.comissao_recibo_doc
                 WHERE ve.estado = 'concluido'
                   AND ve.pago = 1
                   AND ve.comissao_recibo_doc IS NOT NULL
                   AND ve.comissao_recibo_doc > 0";

        foreach ($this->db->query($sql)->result_array() as $r) {
            $partes = [];

            // Uma linha por parcela por pagar: podem estar em estados diferentes.
            if ((float) $r['cpcv_taxa'] > 0 && empty($r['cpcv_pago'])) {
                $partes['cpcv'] = ['CPCV', (float) $r['comissao_total'] * (float) $r['cpcv_taxa'] / 100];
            }
            if ((float) $r['escritura_taxa'] > 0 && empty($r['escritura_paga'])) {
                $partes['escritura'] = ['Escritura', (float) $r['comissao_total'] * (float) $r['escritura_taxa'] / 100];
            }
            // Sem repartição definida, a comissão é uma só e vive no slot do CPCV.
            if (empty($partes) && (float) $r['cpcv_taxa'] <= 0 && (float) $r['escritura_taxa'] <= 0
                && empty($r['cpcv_pago'])) {
                $partes['cpcv'] = ['Comissão total', (float) $r['comissao_total']];
            }

            foreach ($partes as $chave => $parte) {
                if ($parte[1] <= 0.004) {
                    continue;
                }

                $linhas[] = [
                    'venda_id'    => (int) $r['id'],
                    'origem'      => 'comercial',
                    'quem'        => trim((string) $r['quem']) ?: 'Sem comercial',
                    'staff_id'    => (int) $r['staff_id'],
                    'empreendimento' => $r['empreendimento'],
                    'unidade'     => $r['unidade'],
                    'cliente'     => $r['cliente'],
                    'parcela'     => $chave,
                    'etiqueta'    => $parte[0],
                    'valor'       => round($parte[1], 2),
                    'doc'         => (int) $r['comissao_recibo_doc'],
                    'recibo_em'   => $r['recibo_em'],
                ];
            }
        }

        /* 2) DIREÇÃO — override com recibo entregue e por pagar. */
        $regras = $this->regras_config();

        if ((int) $regras['director_id'] > 0) {
            $sql = "SELECT ve.id, ve.empreendimento, ve.unidade, ve.cliente, ve.valor,
                           ve.staff_id, ve.direcao_recibo_doc, doc.dateadded AS recibo_em
                      FROM {$v} ve
                 LEFT JOIN {$d} doc ON doc.id = ve.direcao_recibo_doc
                     WHERE ve.estado = 'concluido'
                       AND ve.pago = 1
                       AND ve.direcao_pago = 0
                       AND ve.direcao_recibo_doc IS NOT NULL
                       AND ve.direcao_recibo_doc > 0";

            $nome_dir = $this->db->select('firstname, lastname')
                ->where('staffid', (int) $regras['director_id'])
                ->get(db_prefix() . 'staff')->row();

            foreach ($this->db->query($sql)->result_array() as $r) {
                $staff = (int) $r['staff_id'];

                // As mesmas exclusões de sempre: 0% e 100% não geram override.
                if (in_array($staff, $regras['comerciais_0'], true)
                    || in_array($staff, $regras['comerciais_100'], true)) {
                    continue;
                }

                $valor = round((float) $r['valor'] * $regras['director_pct'] / 100, 2);

                if ($valor <= 0.004) {
                    continue;
                }

                $linhas[] = [
                    'venda_id'    => (int) $r['id'],
                    'origem'      => 'direcao',
                    'quem'        => $nome_dir ? trim($nome_dir->firstname . ' ' . $nome_dir->lastname) : 'Direção',
                    'staff_id'    => (int) $regras['director_id'],
                    'empreendimento' => $r['empreendimento'],
                    'unidade'     => $r['unidade'],
                    'cliente'     => $r['cliente'],
                    'parcela'     => 'direcao',
                    'etiqueta'    => 'Direção (' . rtrim(rtrim(number_format($regras['director_pct'], 2, ',', ''), '0'), ',') . '%)',
                    'valor'       => $valor,
                    'doc'         => (int) $r['direcao_recibo_doc'],
                    'recibo_em'   => $r['recibo_em'],
                ];
            }
        }

        // Mais antigos primeiro: quem espera há mais tempo aparece à frente.
        usort($linhas, function ($a, $b) {
            return [(string) $a['recibo_em'], $a['venda_id']] <=> [(string) $b['recibo_em'], $b['venda_id']];
        });

        return $linhas;
    }

    public function regras_save_config($data)
    {
        update_option('dps_painel_director_id', (string) (int) ($data['director_id'] ?? 0));
        update_option('dps_painel_director_pct', (string) $this->num($data['director_pct'] ?? 0));

        $base = $data['director_base'] ?? 'recebida';
        update_option('dps_painel_director_base', in_array($base, ['recebida', 'paga', 'venda'], true) ? $base : 'recebida');

        // O campo pode vir de um <select multiple>, daí aceitar array ou CSV.
        $cem = $data['comerciais_100'] ?? '';
        if (is_array($cem)) {
            $cem = implode(',', $cem);
        }
        update_option('dps_painel_comerciais_100', implode(',', $this->csv_ids($cem)));

        $zero = $data['comerciais_0'] ?? '';
        if (is_array($zero)) {
            $zero = implode(',', $zero);
        }
        update_option('dps_painel_comerciais_0', implode(',', $this->csv_ids($zero)));

        update_option('dps_painel_regras_guardadas', '1');

        return true;
    }

    /**
     * "17, 46, lixo" -> [17, 46]. Sem duplicados e sem zeros.
     */
    private function csv_ids($csv)
    {
        $ids = [];
        foreach (explode(',', (string) $csv) as $parte) {
            $n = (int) trim($parte);
            if ($n > 0 && !in_array($n, $ids, true)) {
                $ids[] = $n;
            }
        }

        return $ids;
    }

    /* =====================================================================
     * O QUE A DPS RECEBE POR EMPREENDIMENTO
     * ================================================================== */

    public function get_recebimentos()
    {
        $this->db->order_by('empreendimento', 'ASC');
        $linhas = $this->db->get($this->t_recebimento())->result_array();

        /*
         * Os PRAZOS (mês do CPCV e da escritura) vivem nas Regras de Comissão
         * do dps_vendas e são lidos daí — não se duplicam aqui. Dois sítios a
         * dizer datas diferentes para o mesmo empreendimento seria pior do que
         * não as ter: ninguém saberia qual valia.
         */
        $meses = [];
        $t_regras = db_prefix() . 'comissao_regras';

        if ($this->db->table_exists($t_regras)) {
            foreach ($this->db->get($t_regras)->result_array() as $r) {
                $meses[$this->chave_emp($r['empreendimento'])] = [
                    'cpcv'      => $r['cpcv_mes_previsto'] ?? null,
                    'escritura' => $r['escritura_mes_previsto'] ?? null,
                    'taxa_paga' => $r['taxa'] ?? null,
                ];
            }
        }

        foreach ($linhas as &$l) {
            $m = $meses[$this->chave_emp($l['empreendimento'])] ?? null;
            $l['mes_cpcv']      = $m['cpcv'] ?? null;
            $l['mes_escritura'] = $m['escritura'] ?? null;
            $l['taxa_paga']     = $m['taxa_paga'] ?? null;

            // Repartição efectiva: sem nada definido, entra tudo no CPCV.
            $cp = isset($l['cpcv_pct']) ? (float) $l['cpcv_pct'] : null;
            $es = isset($l['escritura_pct']) ? (float) $l['escritura_pct'] : null;

            if ($cp === null && $es === null) {
                $cp = 100.0;
                $es = 0.0;
            } else {
                $cp = $cp ?? 0.0;
                $es = $es ?? 0.0;
            }

            $l['cpcv_efectivo']      = $cp;
            $l['escritura_efectivo'] = $es;
            $l['reparticao_ok']      = abs(($cp + $es) - 100) < 0.001;
        }
        unset($l);

        return $linhas;
    }

    public function get_recebimento($id)
    {
        return $this->db->where('id', (int) $id)->get($this->t_recebimento())->row_array();
    }

    /**
     * Chave de comparação de empreendimentos.
     *
     * O campo é texto livre no simulador e o histórico tem variações de
     * escrita ("Gaia Douro" / "gaia douro "). Compara-se sempre normalizado,
     * tal como o dps_vendas faz em get_regra().
     */
    private function chave_emp($nome)
    {
        return strtolower(trim((string) $nome));
    }

    /**
     * Mapa empreendimento-normalizado => linha de recebimento, para não fazer
     * uma query por venda. Leva a linha toda (taxa, repartição CPCV/escritura e
     * os meses que vêm das Regras de Comissão), não só a taxa.
     */
    public function mapa_taxas_recebidas()
    {
        $mapa = [];
        foreach ($this->get_recebimentos() as $r) {
            $mapa[$this->chave_emp($r['empreendimento'])] = $r;
        }

        return $mapa;
    }

    /**
     * Traz para a lista qualquer empreendimento que já tenha vendas, a 0%,
     * para a direcção ver logo o que falta definir (mesma ideia do
     * sincronizar_regras_com_vendas do dps_vendas).
     */
    public function sincronizar_recebimento_com_vendas()
    {
        $emps = $this->db->select('DISTINCT(empreendimento) AS empreendimento')
            ->where('empreendimento IS NOT NULL')
            ->where('empreendimento <>', '')
            ->where('empreendimento <>', 'undefined')
            ->get($this->t_vendas())
            ->result_array();

        foreach ($emps as $e) {
            $emp = trim($e['empreendimento']);
            if ($emp === '') {
                continue;
            }
            $existe = $this->db
                ->where('LOWER(TRIM(empreendimento))', $this->chave_emp($emp))
                ->count_all_results($this->t_recebimento());

            if (!$existe) {
                $this->db->insert($this->t_recebimento(), [
                    'empreendimento' => $emp,
                    'taxa_recebida'  => 0,
                    'notas'          => 'POR DEFINIR — apareceu numa venda.',
                    'updated_by'     => get_staff_user_id(),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * @return string mensagem de erro, ou '' se estiver tudo bem
     */
    public function validar_recebimento($data)
    {
        if (trim((string) ($data['empreendimento'] ?? '')) === '') {
            return 'O empreendimento é obrigatório.';
        }
        $taxa = $this->num($data['taxa_recebida'] ?? 0);
        if ($taxa < 0) {
            return 'A percentagem que recebemos não pode ser negativa.';
        }
        if ($taxa > 100) {
            return 'A percentagem que recebemos não pode ser maior que 100%.';
        }

        // Repartição: ou se deixam as duas vazias (recebe-se tudo no CPCV) ou
        // somam 100% da verba recebida.
        $tem_cp = ($data['cpcv_pct'] ?? '') !== '';
        $tem_es = ($data['escritura_pct'] ?? '') !== '';

        if ($tem_cp || $tem_es) {
            $cp = $this->num($data['cpcv_pct'] ?? 0);
            $es = $this->num($data['escritura_pct'] ?? 0);

            if ($cp < 0 || $es < 0) {
                return 'As percentagens da repartição não podem ser negativas.';
            }
            if (abs(($cp + $es) - 100) > 0.001) {
                return 'A repartição do que recebemos tem de somar 100%: '
                    . rtrim(rtrim(number_format($cp, 2, ',', ''), '0'), ',') . '% no CPCV + '
                    . rtrim(rtrim(number_format($es, 2, ',', ''), '0'), ',') . '% na escritura dá '
                    . rtrim(rtrim(number_format($cp + $es, 2, ',', ''), '0'), ',') . '%. '
                    . '(Deixe ambas vazias para receber tudo no CPCV.)';
            }
        }

        return '';
    }

    public function guardar_recebimento($data, $id = null)
    {
        $payload = [
            'empreendimento' => trim((string) $data['empreendimento']),
            'taxa_recebida'  => $this->num($data['taxa_recebida'] ?? 0),
            // Repartição da verba recebida, em % DA VERBA (66/34), tal como a
            // repartição da comissão. Vazio = tudo de uma vez, no CPCV.
            'cpcv_pct'       => ($data['cpcv_pct'] ?? '') !== '' ? $this->num($data['cpcv_pct']) : null,
            'escritura_pct'  => ($data['escritura_pct'] ?? '') !== '' ? $this->num($data['escritura_pct']) : null,
            'notas'          => trim((string) ($data['notas'] ?? '')) ?: null,
            'updated_by'     => get_staff_user_id(),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        // O empreendimento é UNIQUE: gravar um nome que já existe tem de
        // actualizar essa linha, senão o INSERT rebenta com chave duplicada
        // (é o caso normal de editar uma das linhas semeadas a 0%).
        if (!$id) {
            $existente = $this->db
                ->where('LOWER(TRIM(empreendimento))', $this->chave_emp($payload['empreendimento']))
                ->get($this->t_recebimento())
                ->row_array();
            if ($existente) {
                $id = $existente['id'];
            }
        }

        if ($id) {
            $this->db->where('id', (int) $id)->update($this->t_recebimento(), $payload);

            return (int) $id;
        }

        $this->db->insert($this->t_recebimento(), $payload);

        return $this->db->insert_id();
    }

    /**
     * Grava os PRAZOS (mês do CPCV e da escritura) do empreendimento.
     *
     * Escreve na tabela das Regras de Comissão, que é onde os prazos vivem —
     * não se duplicam aqui. Poder editá-los a partir do ecrã do recebimento é
     * comodidade; ter dois sítios a guardar a mesma data seria uma armadilha,
     * porque mais cedo ou mais tarde diriam coisas diferentes e ninguém sabia
     * qual valia.
     *
     * Um mês vazio é uma resposta válida e significa "na conclusão / imediato".
     * Por isso grava-se NULL em vez de se ignorar o campo: sem isso não havia
     * maneira de apagar um prazo depois de o ter posto.
     */
    public function guardar_prazos($empreendimento, $mes_cpcv, $mes_escritura)
    {
        $t = db_prefix() . 'comissao_regras';

        if (!$this->db->table_exists($t)) {
            return false;
        }

        $limpar = function ($m) {
            $m = trim((string) $m);

            return preg_match('/^\d{4}-\d{2}$/', $m) ? $m : null;
        };

        $dados = [
            'cpcv_mes_previsto'      => $limpar($mes_cpcv),
            'escritura_mes_previsto' => $limpar($mes_escritura),
        ];

        $linha = $this->db
            ->where('LOWER(TRIM(empreendimento))', $this->chave_emp($empreendimento))
            ->get($t)->row_array();

        if ($linha) {
            $this->db->where('id', (int) $linha['id'])->update($t, $dados);

            return true;
        }

        /*
         * Sem regra ainda criada, cria-se uma só com os prazos. A taxa da
         * comissão do comercial fica a zero de propósito: quem a define é o
         * ecrã das Regras de Comissão, e inventar aqui um valor era pior do
         * que deixar à vista que falta preencher.
         */
        $this->db->insert($t, array_merge($dados, [
            'empreendimento' => trim((string) $empreendimento),
        ]));

        return true;
    }

    public function delete_recebimento($id)
    {
        $this->db->where('id', (int) $id)->delete($this->t_recebimento());

        return $this->db->affected_rows() > 0;
    }

    /**
     * Staff activo, para os selects das definições.
     */
    public function staff_ativo()
    {
        $this->db->select('staffid, firstname, lastname');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');

        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

    /* ---------------------------------------------------------------------
     * Vendas do painel — importadas do mapa (dps_vendas) + a camada de negócio
     * (comissão recebida do promotor, recibo emitido).
     * ------------------------------------------------------------------ */

    public function get_vendas($filtros = [])
    {
        $this->db->select('v.id, v.empreendimento, v.unidade, v.cliente, v.valor, v.taxa,
            v.comissao_total, v.estado, v.data_venda, v.staff_id,
            v.cpcv_pago, v.escritura_paga, v.cpcv_taxa, v.escritura_taxa,
            v.cpcv_mes_previsto, v.escritura_mes_previsto,
            v.recebido_dps, v.recebido_dps_em, v.pago,
            v.date_created, v.direcao_pago, v.direcao_pago_em,
            v.fatura_moloni_cpcv, v.fatura_moloni_escritura,
            CONCAT(s.firstname, " ", s.lastname) AS comercial_nome,
            o.comissao_recebida, o.recibo_emitido, o.recibo_numero, o.recibo_data, o.notas, o.moloni_doc_id', false);
        $this->db->from($this->t_vendas() . ' v');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = v.staff_id', 'left');
        $this->db->join($this->t_overlay() . ' o', 'o.venda_id = v.id', 'left');
        $this->db->where_in('v.estado', ['vendido', 'concluido']);

        if (!empty($filtros['ano'])) {
            $this->db->where('YEAR(v.data_venda)', (int) $filtros['ano']);
        }
        if (!empty($filtros['mes'])) {
            $this->db->where('MONTH(v.data_venda)', (int) $filtros['mes']);
        }

        /*
         * Intervalo de datas — é o que serve o "últimos 3 meses", que não se
         * escreve com ano/mês porque atravessa a viragem do ano.
         */
        if (!empty($filtros['de'])) {
            $this->db->where('v.data_venda >=', $filtros['de']);
        }
        if (!empty($filtros['ate'])) {
            $this->db->where('v.data_venda <=', $filtros['ate']);
        }
        if (!empty($filtros['comercial'])) {
            $this->db->where('v.staff_id', (int) $filtros['comercial']);
        }
        if (!empty($filtros['empreendimento'])) {
            $this->db->where('v.empreendimento', $filtros['empreendimento']);
        }
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $this->db->where('v.estado', $filtros['estado']);
        }

        /*
         * Filtro pelo MÊS EM QUE A DPS RECEBEU (não o da venda). É o que
         * responde a "quanto entrou em Julho", que é uma pergunta diferente de
         * "quanto se vendeu em Julho".
         */
        if (!empty($filtros['mes_recebido'])) {
            $this->db->where('v.recebido_dps', 1)
                ->where("DATE_FORMAT(v.recebido_dps_em, '%Y-%m') =", $filtros['mes_recebido']);
        }
        if (!empty($filtros['so_recebidas'])) {
            $this->db->where('v.recebido_dps', 1);
        }

        /*
         * Recebido / Por receber. Lê-se da marca de recebido — a mesma que a
         * direcção põe no mapa de vendas — e não do balde do quadro de cima:
         * uma verba pode estar recebida e ainda assim viver em "a emitir
         * factura" por lhe faltar o número.
         */
        if (($filtros['recebimento'] ?? '') === 'sim') {
            $this->db->where('v.recebido_dps', 1);
        } elseif (($filtros['recebimento'] ?? '') === 'nao') {
            $this->db->where('v.recebido_dps', 0);
        }

        $this->db->order_by('v.data_venda', 'DESC');
        $this->db->order_by('v.id', 'DESC');
        $vendas = $this->db->get()->result_array();

        // Uma leitura só das taxas e das regras, reutilizada em todas as linhas.
        $taxas  = $this->mapa_taxas_recebidas();
        $regras = $this->regras_config();

        foreach ($vendas as &$v) {
            $this->aplicar_regras_venda($v, $taxas, $regras);
        }
        unset($v); // matar a referência do foreach — senão a última linha fica exposta

        return $vendas;
    }

    /**
     * Aplica a economia da venda: o que entra (promotor), o que sai (comercial
     * + direcção) e o que sobra.
     *
     * @param array $v      linha da venda, por referência
     * @param array $taxas  mapa empreendimento-normalizado => % que recebemos
     * @param array $regras regras_config()
     */
    private function aplicar_regras_venda(&$v, $taxas, $regras)
    {
        $valor    = (float) $v['valor'];
        $staff_id = (int) $v['staff_id'];
        $chave    = $this->chave_emp($v['empreendimento']);

        $receb = $taxas[$chave] ?? null;

        $v['taxa_recebida'] = $receb ? (float) $receb['taxa_recebida'] : null;

        /* 1) O QUE RECEBEMOS -------------------------------------------------
         * O valor escrito à mão no overlay é o real (veio do extracto do
         * promotor) e ganha sempre. Sem ele, estimamos pela taxa do
         * empreendimento. Sem taxa definida não inventamos nada: fica a zero e
         * a linha é assinalada, porque um palpite aqui contamina o resultado.
         */
        $v['recebida'] = $v['comissao_recebida'] !== null ? (float) $v['comissao_recebida'] : null;

        if ($v['recebida'] !== null) {
            $v['recebido_previsto'] = $v['recebida'];
            $v['recebido_fonte']    = 'real';
        } elseif ($v['taxa_recebida'] !== null && $v['taxa_recebida'] > 0) {
            $v['recebido_previsto'] = round($valor * $v['taxa_recebida'] / 100, 2);
            $v['recebido_fonte']    = 'estimado';
        } else {
            $v['recebido_previsto'] = 0.0;
            $v['recebido_fonte']    = 'sem_taxa';
        }

        /* 1b) QUANDO RECEBEMOS -----------------------------------------------
         * O promotor também nos paga em duas tranches. As percentagens são da
         * verba recebida (mesma semântica das Regras de Comissão) e os MESES
         * vêm dos prazos: primeiro os da própria venda, e só na falta deles os
         * da regra do empreendimento. Sem repartição definida, tudo no CPCV.
         *
         * PORQUE É QUE ISTO IMPORTA: uma tranche cujo mês ainda não chegou é
         * dinheiro que AINDA NÃO ENTROU. Contá-la como recebida inflacionava o
         * "Recebemos" e, pior, fazia o override de 0,5% da direcção incidir
         * sobre dinheiro que a DPS não tem em caixa (foi o caso do Belo
         * Horizonte, só pago na data do CPCV).
         */
        $cp_pct = $receb ? (float) $receb['cpcv_efectivo'] : 100.0;
        $es_pct = $receb ? (float) $receb['escritura_efectivo'] : 0.0;

        $v['recebido_cpcv']          = round($v['recebido_previsto'] * $cp_pct / 100, 2);
        $v['recebido_escritura']     = round($v['recebido_previsto'] - $v['recebido_cpcv'], 2);
        $v['recebido_cpcv_pct']      = $cp_pct;
        $v['recebido_escritura_pct'] = $es_pct;

        // Prazo da venda primeiro; a regra do empreendimento é o fallback.
        $v['mes_recebido_cpcv'] = !empty($v['cpcv_mes_previsto'])
            ? $v['cpcv_mes_previsto'] : ($receb['mes_cpcv'] ?? null);
        $v['mes_recebido_escritura'] = !empty($v['escritura_mes_previsto'])
            ? $v['escritura_mes_previsto'] : ($receb['mes_escritura'] ?? null);

        /*
         * Um valor lançado à mão é dinheiro que já entrou (foi lido do extracto
         * do promotor), por isso conta todo como recebido, sem olhar a prazos.
         * Sem valor lançado, só entram as tranches cujo mês já chegou. Mês
         * vazio = sem prazo combinado = paga-se de imediato.
         */
        $mes_agora = date('Y-m');
        $venceu = function ($mes) use ($mes_agora) {
            return trim((string) $mes) === '' || $mes <= $mes_agora;
        };

        /*
         * EM CAIXA = só o que a direção marcou como recebido no mapa de vendas.
         *
         * Antes isto era deduzido do mês previsto na regra do empreendimento:
         * se o mês já tinha passado, assumia-se que o dinheiro tinha entrado.
         * Era um palpite otimista — punha em caixa verbas que podiam estar
         * atrasadas. Regra do dono (29/07/2026): "só recebemos em caixa quando
         * tem esse visto que eu coloco".
         *
         * O $venceu() continua a servir os PRAZOS (em que mês se espera cada
         * tranche), que é outra pergunta: o que se espera, e para quando.
         */
        $v['recebido_marcado'] = !empty($v['recebido_dps']);
        $v['recebido_em']      = $v['recebido_dps_em'] ?? null;

        /*
         * O CIRCUITO DO DINHEIRO, TAL COMO A DIREÇÃO O DESCREVE (31/07/2026)
         *
         *   perspectiva     — o comprovativo de pagamento ainda não foi
         *                     validado. Não há nada a cobrar ao promotor.
         *   a emitir        — pagamento validado, mas ainda sem factura
         *                     emitida. É trabalho nosso por fazer.
         *   por receber     — já há factura, falta o promotor pagar. É aqui
         *                     que está o dinheiro que se anda a cobrar.
         *   em caixa        — há factura E a direção pôs o visto de recebido.
         *
         * Palavras do dono: "a receber é o que foi validado o pagamento e
         * emitida factura, mas ainda não tem visto de recebido. E o que falta
         * emitir é o que foi pago mas ainda não tem número de factura."
         *
         * Repare-se que a factura entra na conta do que já está em caixa: sem
         * ela, o dinheiro pode ter entrado mas não está titulado, e a direção
         * quer isso à vista.
         *
         * Tudo isto é por TRANCHE — CPCV e escritura facturam-se em momentos
         * diferentes e podem estar em estados diferentes na mesma venda.
         */
        $v['validada'] = !empty($v['pago']);

        $tem_factura = [
            'cpcv'      => trim((string) ($v['fatura_moloni_cpcv'] ?? '')) !== '',
            'escritura' => trim((string) ($v['fatura_moloni_escritura'] ?? '')) !== '',
        ];

        $v['tem_factura_cpcv']      = $tem_factura['cpcv'];
        $v['tem_factura_escritura'] = $tem_factura['escritura'];

        $baldes = [
            'recebido'    => 0.0,   // visto de recebido = em caixa
            'por_receber' => 0.0,   // vencido, com factura, sem visto
            'a_emitir'    => 0.0,   // vencido e validado, sem factura
            'futuro'      => 0.0,   // validado, mas o mês da tranche ainda não chegou
            'perspectiva' => 0.0,   // sem pagamento validado
        ];
        $por_tranche = [];

        foreach (['cpcv' => $v['recebido_cpcv'], 'escritura' => $v['recebido_escritura']] as $qual => $montante) {
            $montante = (float) $montante;
            if ($montante <= 0) {
                $por_tranche[$qual] = ['estado' => null, 'valor' => 0.0];
                continue;
            }

            /*
             * O VISTO DE RECEBIDO MANDA, com ou sem número de factura.
             *
             * A ordem era outra: sem número de factura a verba caía em "a
             * emitir", mesmo com o visto posto. Nasceu de uma regra certa —
             * dinheiro que entrou sem estar titulado tem de se ver — mas
             * aplicada no sítio errado: escondia dos totais dinheiro que a
             * direcção já tinha declarado em caixa. Foi o caso das 21 do Lake
             * Towers, recebidas em Fevereiro e a aparecer como por facturar.
             *
             * A falta de factura não desaparece: fica marcada na linha e
             * contada à parte, em "recebido sem factura". Vê-se o que falta
             * titular sem mentir sobre o que está em caixa.
             *
             * Regra do dono (04/08/2026).
             */
            /*
             * O MÊS DA TRANCHE ENTRA ANTES DA FACTURA.
             *
             * Uma tranche cujo mês ainda não chegou NÃO PODE ter factura — não
             * se factura o que ainda não venceu. Como o teste da factura vinha
             * primeiro, essas tranches caíam em "a emitir" e o cartão "A
             * receber no futuro" dava sempre zero, por construção. Não havia
             * dado nenhum que o pudesse encher.
             *
             * Agora o calendário decide primeiro: validado e por vencer é
             * FUTURO. O Belo Horizonte é o caso — CPCV em 12/2026, escritura em
             * 01/2029, tudo validado e nada por facturar ainda.
             *
             * Regra do dono (04/08/2026).
             */
            $mes_desta = $qual === 'cpcv'
                ? $v['mes_recebido_cpcv']
                : $v['mes_recebido_escritura'];

            if ($v['recebido_marcado']) {
                $estado = 'recebido';
            } elseif (!$v['validada']) {
                $estado = 'perspectiva';
            } elseif (!$venceu($mes_desta)) {
                $estado = 'futuro';
            } elseif (!$tem_factura[$qual]) {
                $estado = 'a_emitir';
            } else {
                $estado = 'por_receber';
            }

            $baldes[$estado] += $montante;
            $por_tranche[$qual] = ['estado' => $estado, 'valor' => round($montante, 2)];
        }

        $v['tranches'] = $por_tranche;

        $v['recebido']    = round($baldes['recebido'], 2);
        $v['a_emitir']    = round($baldes['a_emitir'], 2);
        $v['perspectiva'] = round($baldes['perspectiva'], 2);

        /*
         * Recebido mas por titular: está em caixa e ainda não tem número de
         * factura. Não sai dos totais — sai à parte, para se saber o que
         * falta emitir sem que isso mexa no dinheiro.
         */
        $sem_titulo = 0.0;

        if ($v['recebido_marcado']) {
            foreach (['cpcv', 'escritura'] as $qual) {
                if (($por_tranche[$qual]['valor'] ?? 0) > 0 && !$tem_factura[$qual]) {
                    $sem_titulo += (float) $por_tranche[$qual]['valor'];
                }
            }
        }

        $v['recebido_sem_factura'] = round($sem_titulo, 2);

        /*
         * O que se cobra HOJE e o que é calendário — dois problemas diferentes.
         * O futuro parte-se por tranche, porque um CPCV é o próximo horizonte e
         * uma escritura pode ser anos depois.
         */
        $agora = $futuro_cpcv = $futuro_esc = 0.0;

        /*
         * NÃO reutilizar $valor aqui: é o PREÇO DA VENDA e ainda é preciso
         * mais abaixo, no override da direção (0,5% do preço). Chamar-lhe
         * $valor dentro deste ciclo esmagava-o com o valor da tranche, e a
         * direção passava a receber 0,5% da comissão em vez de 0,5% da venda —
         * 148,25 € onde deviam estar 17.759,50 €. Custou duas horas a
         * encontrar, e não deu erro nenhum: só um número mais pequeno.
         */
        foreach (['cpcv', 'escritura'] as $qual) {
            $estado_tr = $por_tranche[$qual]['estado'] ?? null;
            $valor_tr  = (float) ($por_tranche[$qual]['valor'] ?? 0);

            if ($estado_tr === 'futuro') {
                if ($qual === 'cpcv') {
                    $futuro_cpcv += $valor_tr;
                } else {
                    $futuro_esc += $valor_tr;
                }
            } elseif ($estado_tr === 'por_receber') {
                $agora += $valor_tr;
            }
        }

        $v['a_receber_agora']            = round($agora, 2);
        $v['a_receber_futuro_cpcv']      = round($futuro_cpcv, 2);
        $v['a_receber_futuro_escritura'] = round($futuro_esc, 2);
        $v['a_receber_futuro']           = round($futuro_cpcv + $futuro_esc, 2);
        $v['por_receber']                = round($baldes['por_receber'], 2);

        // Repartição da perspectiva e do que falta facturar, para os cartões
        // poderem dizer de que é feito o seu zero.
        $v['perspectiva_cpcv']       = ($por_tranche['cpcv']['estado'] ?? null) === 'perspectiva'
            ? $por_tranche['cpcv']['valor'] : 0.0;
        $v['perspectiva_escritura']  = ($por_tranche['escritura']['estado'] ?? null) === 'perspectiva'
            ? $por_tranche['escritura']['valor'] : 0.0;

        /* 2) O QUE PAGAMOS AO COMERCIAL --------------------------------------
         * Acordo dos comerciais a 100% (Samara): a DPS entrega exactamente o
         * que recebeu do promotor — ganha zero na venda. Não é um erro de
         * cálculo, é o acordo; o resultado da linha dá mesmo 0.
         */
        $v['comercial_100'] = in_array($staff_id, $regras['comerciais_100'], true);
        $v['comercial_0']   = in_array($staff_id, $regras['comerciais_0'], true);

        /*
         * Três acordos diferentes:
         *
         *   0%    (Ricardo)  — a comissão fica na casa. Não há nada a pagar,
         *                      nem agora nem no futuro. O resultado da venda é
         *                      tudo o que recebemos.
         *   100%  (Samara)   — a DPS entrega exactamente o que recebeu do
         *                      promotor; ganha zero na venda. É o acordo, não
         *                      um erro de cálculo.
         *   normal           — a comissão da regra do empreendimento.
         *
         * Nos comerciais a 100% o devido é o PREVISTO, não só a parte já em
         * caixa: o que muda com os prazos é quando se paga, não quanto se deve.
         */
        if ($v['comercial_0']) {
            $v['comissao_comercial'] = 0.0;
        } elseif ($v['comercial_100']) {
            $v['comissao_comercial'] = $v['recebido_previsto'];
        } else {
            $v['comissao_comercial'] = (float) $v['comissao_total'];
        }

        /* 3) OVERRIDE DA DIRECÇÃO --------------------------------------------
         * Percentagem sobre cada venda da casa. Nas vendas dos comerciais a
         * 100% não há override — não sobra nada de onde o tirar.
         *
         * Sem director escolhido ("— ninguém —" no select das definições,
         * director_id = 0) também não há override: descontar uma percentagem
         * que não é atribuída a ninguém tirava dinheiro do resultado sem
         * destino. Antes o director_id era só copiado para a linha e nunca
         * entrava no cálculo, e a opção "— ninguém —" não desligava nada.
         *
         * Se o próprio director for o comercial da venda, leva as duas coisas:
         * a comissão de comercial (já contada em cima) E o override. É
         * intencional e foi o que a direcção pediu.
         */
        /*
         * O QUE PAGAMOS AO COMERCIAL, pelos mesmos prazos.
         *
         * cpcv_taxa/escritura_taxa são percentagens DA COMISSÃO (66/34), não
         * do valor da venda — documentado em Dps_vendas_model::calcular_comissao.
         * Sem repartição definida, a comissão é uma parcela só, no CPCV.
         */
        $cp_taxa = (float) ($v['cpcv_taxa'] ?? 0);
        $es_taxa = (float) ($v['escritura_taxa'] ?? 0);

        if ($cp_taxa + $es_taxa <= 0) {
            $cp_taxa = 100.0;
            $es_taxa = 0.0;
        }

        $com       = (float) $v['comissao_comercial'];
        $parcela_cp = round($com * $cp_taxa / 100, 2);
        $parcela_es = round($com - $parcela_cp, 2);

        $com_agora       = 0.0;
        $com_futuro_cp   = 0.0;
        $com_futuro_es   = 0.0;

        // Comercial a 0%: não há parcelas por pagar. O $com já é zero, mas
        // deixa-se explícito para quem ler não ter de o deduzir.
        if ($v['comercial_0']) {
            $parcela_cp = 0.0;
            $parcela_es = 0.0;
        }

        if (!$v['comercial_0'] && empty($v['cpcv_pago'])) {
            if ($venceu($v['mes_recebido_cpcv'])) {
                $com_agora += $parcela_cp;
            } else {
                $com_futuro_cp += $parcela_cp;
            }
        }

        if (!$v['comercial_0'] && $parcela_es > 0 && empty($v['escritura_paga'])) {
            if ($venceu($v['mes_recebido_escritura'])) {
                $com_agora += $parcela_es;
            } else {
                $com_futuro_es += $parcela_es;
            }
        }

        /*
         * A comissão do comercial segue a venda: se ela ainda não foi validada,
         * o que lhe é devido também é perspectiva. Sem isto o resultado de agora
         * levava com o custo de vendas cujo proveito tinha saído para
         * perspectiva — dava prejuízo onde não há.
         */
        $v['comerciais_perspectiva'] = 0.0;

        if (!$v['validada']) {
            $v['comerciais_perspectiva'] = round($com_agora + $com_futuro_cp + $com_futuro_es, 2);
            $com_agora     = 0.0;
            $com_futuro_cp = 0.0;
            $com_futuro_es = 0.0;
        }

        $v['comerciais_agora']            = round($com_agora, 2);
        $v['comerciais_futuro_cpcv']      = round($com_futuro_cp, 2);
        $v['comerciais_futuro_escritura'] = round($com_futuro_es, 2);
        $v['comerciais_futuro']           = round($com_futuro_cp + $com_futuro_es, 2);

        $v['tem_director'] = (int) $regras['director_id'] > 0;

        /*
         * O override da direção não existe nas vendas da Samara (100%: não
         * sobra de onde o tirar) nem nas do Ricardo (0%: a comissão nunca sai
         * de casa, logo não há de que descontar meio ponto para outra pessoa).
         * Regra do dono, 30/07/2026.
         */
        if ($v['comercial_100'] || $v['comercial_0'] || !$v['tem_director']) {
            $v['direcao_ja_paga']      = 0.0;
            $v['direcao_paga']         = false;
            $v['direcao']              = 0.0;
            $v['direcao_prevista']     = 0.0;
            $v['direcao_base']         = 0.0;
            $v['direcao_agora']        = 0.0;
            $v['direcao_futuro']       = 0.0;
            $v['direcao_perspectiva']  = 0.0;
        } else {
            /*
             * Duas contas, não uma: o que o director JÁ ganhou (sobre dinheiro
             * que entrou) e o que virá a ganhar quando a venda render tudo.
             * Só a base 'recebida' distingue as duas — nas outras o override não
             * depende de o promotor já ter pago, por isso são iguais.
             */
            switch ($regras['director_base']) {
                case 'paga':
                    $base = $base_prevista = $v['comissao_comercial'];
                    break;

                case 'recebida':
                    $base          = $v['recebido'];
                    $base_prevista = $v['recebido_previsto'];
                    break;

                default: // 'venda' — meio ponto do preço, seja qual for a taxa
                    $base_prevista = $valor;
                    /*
                     * Pago quando o dinheiro entra. Passou a seguir a marca de
                     * recebido em vez do mês previsto: o override é pago no
                     * CPCV, e a prova de que o CPCV foi pago é a direção ter
                     * marcado o recebimento — não o calendário.
                     */
                    $base = $v['recebido_marcado'] ? $valor : 0.0;
            }
            $v['direcao_base']     = (float) $base;
            $v['direcao']          = round($base * $regras['director_pct'] / 100, 2);
            $v['direcao_prevista'] = round($base_prevista * $regras['director_pct'] / 100, 2);

            /*
             * O override é pago no CPCV, por isso viaja com essa tranche: se o
             * mês do CPCV já chegou é devido agora, senão é futuro. Quando a
             * venda já está marcada como recebida, saiu de "por pagar" e está
             * contabilizado em $v['direcao'].
             */
            /*
             * O override viaja com a tranche do CPCV: chegado o mês, é devido.
             *
             * Houve aqui uma tentativa de o prender também à existência de
             * factura — revertida no mesmo dia. A regra do dono é mais simples
             * do que eu a tinha lido: em casa é o que já foi recebido; à espera
             * de cobrança é o que já foi facturado e não pago; futuro é o que
             * ainda não concluiu, mais o Belo Horizonte. A factura já decide o
             * balde da verba, não precisa de decidir outra vez o do override.
             */
            /*
             * O OVERRIDE JÁ PAGO DEIXA DE SER DEVIDO.
             *
             * O quadro de comissões marca o pagamento do override em
             * direcao_pago, e o Painel não olhava para lá: pagas as vendas #22
             * e #23 ao Cláudio, continuavam aqui a aparecer por pagar, com o
             * valor inteiro. Duas partes do CRM a dizer coisas opostas sobre o
             * mesmo dinheiro. Corrigido a 05/08/2026.
             */
            $v['direcao_paga'] = !empty($v['direcao_pago']);

            /*
             * O QUE JÁ SAIU PARA A DIREÇÃO.
             *
             * Circuito, nas palavras do dono (05/08/2026): "o comercial emite
             * recibo, é por pagar; quando eu pago e valido como pago, é paga".
             * O Painel mostrava só o que falta — e o dinheiro que já saiu não
             * aparecia em lado nenhum, como se nunca tivesse sido pago.
             */
            $v['direcao_ja_paga'] = $v['direcao_paga']
                ? round((float) $v['direcao_prevista'], 2)
                : 0.0;

            $falta_dir = $v['direcao_paga']
                ? 0.0
                : round($v['direcao_prevista'] - $v['direcao'], 2);

            $dir_vencida = $venceu($v['mes_recebido_cpcv']);

            $v['direcao_agora']  = $dir_vencida ? $falta_dir : 0.0;
            $v['direcao_futuro'] = $dir_vencida ? 0.0 : $falta_dir;

            /*
             * Mesma condição das outras rubricas: numa venda por validar o
             * override também é perspectiva. E como é pago no CPCV, o futuro
             * da direção é sempre da tranche do CPCV — nunca da escritura.
             */
            $v['direcao_perspectiva'] = 0.0;

            if (!$v['validada']) {
                $v['direcao_perspectiva'] = round($v['direcao_agora'] + $v['direcao_futuro'], 2);
                $v['direcao_agora']       = 0.0;
                $v['direcao_futuro']      = 0.0;
            }
        }

        $v['direcao_futuro_cpcv']      = $v['direcao_futuro'];
        $v['direcao_futuro_escritura'] = 0.0;

        $v['director_id'] = $regras['director_id'];

        /* 4) RESULTADO DA LINHA ----------------------------------------------
         * As despesas não são imputáveis a uma venda concreta (não há rateio
         * na base), por isso só entram no resultado global, em totais().
         */
        // Resultado da venda inteira: compara o que ela rende com o que custa.
        // Usa o previsto dos dois lados — misturar caixa (recebido) com dívida
        // (comissão devida) dava linhas artificialmente negativas só porque o
        // promotor ainda não pagou.
        $v['resultado'] = round($v['recebido_previsto'] - $v['comissao_comercial'] - $v['direcao_prevista'], 2);


        /* Informativo: quanto do que é devido ao comercial já lhe foi pago.
         *
         * cpcv_taxa/escritura_taxa são percentagens DA COMISSÃO (66/34), não do
         * valor da venda — é o que o Dps_vendas_model::calcular_comissao()
         * documenta. Antes multiplicava-se pelo valor da venda e uma venda de
         * 200.000 € aparecia com 200.000 € "pagos" ao comercial.
         *
         * A base é comissao_comercial e não comissao_total de propósito: nas
         * vendas dos comerciais a 100% o devido é o que a DPS recebeu (ponto 2),
         * e a vista imprime as duas na mesma célula. Com bases diferentes lia-se
         * "10.000 € devidos, 1.980 € pagos" quando esses 1.980 € eram tranches
         * de uma comissão (3.000 €) que ali não se aplica. Nas restantes vendas
         * comissao_comercial é igual a comissao_total, logo nada muda.
         */
        $devido = (float) $v['comissao_comercial'];
        if ((float) $v['escritura_taxa'] > 0) {
            $pago = 0.0;
            if (!empty($v['cpcv_pago'])) {
                $pago += $devido * (float) $v['cpcv_taxa'] / 100;
            }
            if (!empty($v['escritura_paga'])) {
                $pago += $devido * (float) $v['escritura_taxa'] / 100;
            }
            $v['comissao_paga'] = round($pago, 2);
        } else {
            $v['comissao_paga'] = !empty($v['cpcv_pago']) ? $devido : 0.0;
        }

        /*
         * TESOURARIA — o que falta pagar SOBRE o dinheiro que já entrou.
         *
         * Só conta nas vendas cuja verba já está em caixa. Uma comissão devida
         * de uma venda ainda por receber não sai do dinheiro que temos: sai do
         * que vier. Misturar as duas dava um saldo de caixa que não existe.
         *
         * Duas parcelas: o que ainda se deve ao comercial (o devido menos o
         * que já lhe foi pago) e o override da direção sobre esse dinheiro.
         */
        $v['devido_do_recebido'] = 0.0;

        $v['devido_ao_comercial'] = 0.0;
        $v['devido_a_direcao']    = 0.0;

        if ($v['recebido'] > 0) {
            $falta_comercial = max(0.0, (float) $v['comissao_comercial'] - (float) $v['comissao_paga']);

            /*
             * As duas parcelas ficam SEPARADAS e com nome.
             *
             * Somadas num número só, uma venda com a comissão do comercial já
             * paga aparecia na lista com o nome dele ao lado de uma dívida que
             * é da direção — e lia-se como se ainda se lhe devesse alguma
             * coisa. Foi o caso da 1_S do Gaia Douro: comissão do Miguel paga a
             * 28/07, e os 1.699,50 € que sobravam eram os 0,5% do Cláudio.
             * Regra do dono (04/08/2026): o valor fica, o que faltava era dizer
             * a quem.
             */
            // Override já pago não é dívida — ver direcao_paga mais abaixo.
            $dir_por_pagar = !empty($v['direcao_pago']) ? 0.0 : (float) $v['direcao'];

            $v['devido_ao_comercial'] = round($falta_comercial, 2);
            $v['devido_a_direcao']    = round($dir_por_pagar, 2);
            $v['devido_do_recebido']  = round($falta_comercial + $dir_por_pagar, 2);
        }

        /*
         * Resultado em dois horizontes, com as MESMAS condições dos
         * recebimentos: o que já está (ou devia estar) resolvido, e o que só se
         * resolve em datas futuras. Somar tudo num número escondia que uma
         * venda "positiva" pode estar positiva só em 2028.
         *
         * Fica aqui, no fim, porque precisa do comissao_paga calculado acima —
         * quando estava mais para cima lia-o vazio e dava sempre a comissão
         * como não paga.
         */
        $v['resultado_agora'] = round(
            ($v['recebido'] + $v['a_receber_agora'])
            - ((float) $v['comissao_paga'] + $v['comerciais_agora'])
            - ($v['direcao'] + $v['direcao_agora']),
            2
        );
        /*
         * O futuro parte-se em dois porque são dois calendários: o CPCV é o
         * próximo horizonte, a escritura pode ser anos depois. O override da
         * direção desconta-se todo no CPCV, que é onde é pago.
         */
        $v['resultado_futuro_cpcv'] = round(
            $v['a_receber_futuro_cpcv'] - $v['comerciais_futuro_cpcv'] - $v['direcao_futuro_cpcv'],
            2
        );
        $v['resultado_futuro_escritura'] = round(
            $v['a_receber_futuro_escritura'] - $v['comerciais_futuro_escritura'],
            2
        );
        $v['resultado_futuro'] = round(
            $v['resultado_futuro_cpcv'] + $v['resultado_futuro_escritura'],
            2
        );

        // O que a venda renderia se o pagamento vier a ser validado.
        $v['resultado_perspectiva'] = round(
            $v['perspectiva'] - $v['comerciais_perspectiva'] - $v['direcao_perspectiva'],
            2
        );
    }

    public function save_overlay($venda_id, $data)
    {
        $venda_id = (int) $venda_id;
        $payload  = ['dateupdated' => date('Y-m-d H:i:s')];

        /*
         * Só se escrevem os campos que vieram no formulário.
         *
         * A tabela de vendas do painel não publica recibo_data nem notas; com
         * um payload fixo, cada gravação de uma linha apagava-os. Um checkbox
         * não marcado não chega no POST, por isso recibo_emitido tem de ser
         * tratado à parte: presume-se 0 quando o formulário traz o campo
         * companheiro recibo_numero (é o mesmo bloco da vista).
         */
        if (array_key_exists('comissao_recebida', $data)) {
            $payload['comissao_recebida'] = trim((string) $data['comissao_recebida']) !== ''
                ? $this->num($data['comissao_recebida'])
                : null;
        }
        if (array_key_exists('recibo_numero', $data)) {
            $payload['recibo_numero']  = trim((string) $data['recibo_numero']) ?: null;
            $payload['recibo_emitido'] = !empty($data['recibo_emitido']) ? 1 : 0;
        } elseif (array_key_exists('recibo_emitido', $data)) {
            $payload['recibo_emitido'] = !empty($data['recibo_emitido']) ? 1 : 0;
        }
        if (array_key_exists('recibo_data', $data)) {
            // Data em formato ISO ou nada — não deixamos passar lixo para uma coluna DATE.
            $d = trim((string) $data['recibo_data']);
            $payload['recibo_data'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
        }
        if (array_key_exists('notas', $data)) {
            $payload['notas'] = trim((string) $data['notas']) ?: null;
        }

        $existe = $this->db->where('venda_id', $venda_id)->count_all_results($this->t_overlay());
        if ($existe) {
            $this->db->where('venda_id', $venda_id)->update($this->t_overlay(), $payload);
        } else {
            $payload['venda_id'] = $venda_id;
            $this->db->insert($this->t_overlay(), $payload);
        }

        return true;
    }

    /* ---------------------------------------------------------------------
     * Despesas
     * ------------------------------------------------------------------ */

    public function get_despesas($filtros = [])
    {
        if (!empty($filtros['ano'])) {
            $this->db->where('YEAR(data)', (int) $filtros['ano']);
        }
        if (!empty($filtros['mes'])) {
            $this->db->where('MONTH(data)', (int) $filtros['mes']);
        }
        $this->db->order_by('data', 'DESC')->order_by('id', 'DESC');

        return $this->db->get($this->t_despesas())->result_array();
    }

    public function add_despesa($data)
    {
        $this->db->insert($this->t_despesas(), [
            'data'          => $this->data_para_sql($data['data'] ?? ''),
            'categoria'     => trim((string) ($data['categoria'] ?? '')) ?: null,
            'descricao'     => trim((string) ($data['descricao'] ?? '')) ?: null,
            'valor'         => $this->num($data['valor'] ?? 0),
            'fatura_numero' => trim((string) ($data['fatura_numero'] ?? '')) ?: null,
            'doc'           => $data['doc'] ?? null,
            'dateadded'     => date('Y-m-d H:i:s'),
            'created_by'    => get_staff_user_id(),
        ]);

        return $this->db->insert_id();
    }

    /**
     * Data do formulário -> coluna DATE.
     *
     * O campo da vista é um `.datepicker` do Perfex, que escreve no formato da
     * option `dateformat` — num CRM em português é `28-07-2026`, não ISO.
     * Enfiar isso numa coluna DATE dá erro 1292 com STRICT_TRANS_TABLES (o
     * default do MySQL 5.7/8) e um `0000-00-00` silencioso sem strict mode, que
     * depois escapa aos filtros YEAR()/MONTH() do get_despesas().
     *
     * É a mesma conversão que o core faz em Expenses_model::add() e o
     * dps_vendas em guardar_venda(). to_sql_date() deixa passar o que já vier
     * em ISO e devolve o valor original quando não consegue interpretar, daí a
     * validação final: a coluna é NOT NULL, sem data válida fica a de hoje.
     */
    private function data_para_sql($valor)
    {
        $valor = trim((string) $valor);
        $iso   = $valor !== '' ? to_sql_date($valor) : null;

        if (!$iso || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $iso)) {
            return date('Y-m-d');
        }

        return $iso;
    }

    public function get_despesa($id)
    {
        return $this->db->where('id', (int) $id)->get($this->t_despesas())->row_array();
    }

    public function delete_despesa($id)
    {
        $this->db->where('id', (int) $id)->delete($this->t_despesas());

        return $this->db->affected_rows() > 0;
    }

    /* ---------------------------------------------------------------------
     * Totais
     * ------------------------------------------------------------------ */

    public function totais($vendas, $despesas)
    {
        $t = [
            'recebido'           => 0.0,   // já em caixa
            'recebido_previsto'  => 0.0,   // o que a venda rende quando entrar tudo
            'por_receber'        => 0.0,
            'a_receber_agora'    => 0.0,
            'a_receber_futuro'   => 0.0,
            'comerciais_agora'   => 0.0,
            'comerciais_futuro'  => 0.0,
            'direcao_prevista'   => 0.0,
            'direcao_agora'      => 0.0,
            'direcao_futuro'     => 0.0,
            'resultado_agora'    => 0.0,
            'resultado_futuro'   => 0.0,

            // Futuro aberto por tranche: o CPCV é o próximo horizonte, a
            // escritura pode ser anos depois.
            'a_receber_futuro_cpcv'       => 0.0,
            'a_receber_futuro_escritura'  => 0.0,
            'comerciais_futuro_cpcv'      => 0.0,
            'comerciais_futuro_escritura' => 0.0,
            'direcao_futuro_cpcv'         => 0.0,
            'direcao_futuro_escritura'    => 0.0,
            'resultado_futuro_cpcv'       => 0.0,
            'resultado_futuro_escritura'  => 0.0,

            // Vendas ainda por validar: não são dívida do promotor.
            'perspectiva'             => 0.0,
            'a_emitir'                => 0.0,   // pago, mas ainda sem factura
            'devido_do_recebido'      => 0.0,   // por pagar, sobre dinheiro já em caixa
            'perspectiva_cpcv'        => 0.0,
            'perspectiva_escritura'   => 0.0,
            'comerciais_perspectiva'  => 0.0,
            'direcao_perspectiva'     => 0.0,
            'resultado_perspectiva'   => 0.0,
            'vendas_por_validar'      => 0,
            'pago_comercial'     => 0.0,
            'comissao_comercial' => 0.0,
            'direcao'            => 0.0,
            // Override que JA saiu para a direccao.
            'direcao_ja_paga'    => 0.0,
            'despesas'           => 0.0,
            'volume'             => 0.0,
            'sem_taxa'           => 0,
            'estimado'           => 0.0,
            // Em caixa mas ainda sem numero de factura.
            'recebido_sem_factura'  => 0.0,
            'vendas_sem_factura'    => 0,
        ];

        foreach ($vendas as $v) {
            /*
             * Duas colunas de dinheiro, deliberadamente separadas:
             *  - recebido           = o que já entrou em caixa
             *  - recebido_previsto  = o que a venda rende quando o promotor
             *                         pagar todas as tranches
             * O previsto inclui o real lançado à mão e a estimativa pela taxa —
             * sem isso o resultado dava sempre negativo enquanto a direcção não
             * lançasse à mão todas as comissões do promotor.
             */
            $t['recebido']           += (float) $v['recebido'];
            $t['recebido_previsto']  += (float) $v['recebido_previsto'];

            $t['a_receber_futuro_cpcv']       += (float) $v['a_receber_futuro_cpcv'];
            $t['a_receber_futuro_escritura']  += (float) $v['a_receber_futuro_escritura'];
            $t['comerciais_futuro_cpcv']      += (float) $v['comerciais_futuro_cpcv'];
            $t['comerciais_futuro_escritura'] += (float) $v['comerciais_futuro_escritura'];
            $t['direcao_futuro_cpcv']         += (float) $v['direcao_futuro_cpcv'];
            $t['direcao_futuro_escritura']    += (float) $v['direcao_futuro_escritura'];
            $t['resultado_futuro_cpcv']       += (float) $v['resultado_futuro_cpcv'];
            $t['resultado_futuro_escritura']  += (float) $v['resultado_futuro_escritura'];

            $t['perspectiva']            += (float) $v['perspectiva'];
            $t['a_emitir']               += (float) $v['a_emitir'];
            $t['devido_do_recebido']     += (float) $v['devido_do_recebido'];
            $t['perspectiva_cpcv']       += (float) $v['perspectiva_cpcv'];
            $t['perspectiva_escritura']  += (float) $v['perspectiva_escritura'];
            $t['comerciais_perspectiva'] += (float) $v['comerciais_perspectiva'];
            $t['direcao_perspectiva']    += (float) $v['direcao_perspectiva'];
            $t['resultado_perspectiva']  += (float) $v['resultado_perspectiva'];
            if (empty($v['validada'])) {
                $t['vendas_por_validar']++;
            }
            $t['por_receber']        += (float) $v['por_receber'];
            $t['a_receber_agora']    += (float) $v['a_receber_agora'];
            $t['a_receber_futuro']   += (float) $v['a_receber_futuro'];
            $t['comerciais_agora']   += (float) $v['comerciais_agora'];
            $t['comerciais_futuro']  += (float) $v['comerciais_futuro'];
            $t['direcao_agora']      += (float) $v['direcao_agora'];
            $t['direcao_futuro']     += (float) $v['direcao_futuro'];
            $t['resultado_agora']    += (float) $v['resultado_agora'];
            $t['resultado_futuro']   += (float) $v['resultado_futuro'];
            $t['pago_comercial']     += (float) $v['comissao_paga'];
            $t['comissao_comercial'] += (float) $v['comissao_comercial'];
            $t['direcao']            += (float) $v['direcao'];
            $t['direcao_ja_paga']    += (float) ($v['direcao_ja_paga'] ?? 0);
            $t['direcao_prevista']   += (float) $v['direcao_prevista'];
            $t['volume']             += (float) $v['valor'];

            if (!empty($v['recebido_sem_factura'])) {
                $t['recebido_sem_factura'] += (float) $v['recebido_sem_factura'];
                $t['vendas_sem_factura']++;
            }

            if ($v['recebido_fonte'] === 'sem_taxa') {
                $t['sem_taxa']++;
            } elseif ($v['recebido_fonte'] === 'estimado') {
                $t['estimado'] += (float) $v['recebido_previsto'];
            }
        }

        foreach ($despesas as $d) {
            $t['despesas'] += (float) $d['valor'];
        }

        /*
         * Dois resultados, e os dois honestos:
         *  - resultado  : a economia completa das vendas feitas (previsto de um
         *                 lado, comissões devidas do outro). É o número de
         *                 gestão e o que sempre esteve neste cartão.
         *  - em_caixa   : só dinheiro que entrou menos dinheiro que saiu. Serve
         *                 para tesouraria e pode muito bem ser negativo — é
         *                 normal enquanto o promotor não pagar e as comissões
         *                 já tiverem sido pagas.
         */
        $t['resultado'] = round(
            $t['recebido_previsto'] - $t['comissao_comercial'] - $t['direcao_prevista'] - $t['despesas'],
            2
        );
        $t['em_caixa'] = round(
            $t['recebido'] - $t['pago_comercial'] - $t['direcao'] - $t['despesas'],
            2
        );

        return $t;
    }

    /**
     * Quadro-resumo por empreendimento: uma linha por empreendimento com o que
     * entrou, o que saiu e o que sobrou, mais a linha de totais.
     *
     * As despesas ficam de fora: não há rateio por empreendimento na base, e
     * distribuí-las por palpite dava um resultado que não bate com o global.
     */
    public function resumo_por_empreendimento($vendas)
    {
        $linhas = [];

        foreach ($vendas as $v) {
            $nome  = trim((string) $v['empreendimento']);
            $chave = $nome !== '' ? $this->chave_emp($nome) : '';

            if (!isset($linhas[$chave])) {
                $linhas[$chave] = [
                    'empreendimento' => $nome !== '' ? $nome : '(sem empreendimento)',
                    'vendas'         => 0,
                    'volume'         => 0.0,
                    'recebido'       => 0.0,
                    'recebido_previsto'  => 0.0,
                    'por_receber'        => 0.0,
                    'a_receber_agora'    => 0.0,
                    'a_receber_futuro'   => 0.0,
                            'recebido_cpcv'      => 0.0,
                    'recebido_escritura' => 0.0,
                    'mes_cpcv'           => $v['mes_recebido_cpcv'] ?? null,
                    'mes_escritura'      => $v['mes_recebido_escritura'] ?? null,
                    'comerciais'     => 0.0,
                    'direcao'          => 0.0,   // já devida (o CPCV já chegou)
                    'direcao_prevista' => 0.0,   // total, quando o CPCV chegar
                    'resultado'      => 0.0,
                    'taxa_recebida'  => $v['taxa_recebida'],
                    'sem_taxa'       => 0,
                ];
            }

            $linhas[$chave]['vendas']++;
            $linhas[$chave]['volume']     += (float) $v['valor'];
            $linhas[$chave]['recebido']   += (float) $v['recebido'];
            $linhas[$chave]['recebido_previsto']  += (float) $v['recebido_previsto'];
            $linhas[$chave]['por_receber']        += (float) $v['por_receber'];
            $linhas[$chave]['a_receber_agora']    += (float) $v['a_receber_agora'];
            $linhas[$chave]['a_receber_futuro']   += (float) $v['a_receber_futuro'];
            $linhas[$chave]['recebido_cpcv']      += (float) ($v['recebido_cpcv'] ?? 0);
            $linhas[$chave]['recebido_escritura'] += (float) ($v['recebido_escritura'] ?? 0);
            $linhas[$chave]['comerciais'] += (float) $v['comissao_comercial'];
            $linhas[$chave]['direcao']    += (float) $v['direcao'];
            $linhas[$chave]['direcao_prevista'] += (float) $v['direcao_prevista'];
            $linhas[$chave]['resultado']  += (float) $v['resultado'];

            if ($v['recebido_fonte'] === 'sem_taxa') {
                $linhas[$chave]['sem_taxa']++;
            }
        }

        ksort($linhas);

        $totais = [
            'vendas' => 0, 'volume' => 0.0, 'recebido' => 0.0,
            'recebido_previsto' => 0.0, 'por_receber' => 0.0,
            'a_receber_agora' => 0.0, 'a_receber_futuro' => 0.0,
            'recebido_cpcv' => 0.0, 'recebido_escritura' => 0.0,
            'comerciais' => 0.0, 'direcao' => 0.0, 'direcao_prevista' => 0.0, 'resultado' => 0.0,
        ];
        foreach ($linhas as $l) {
            foreach (array_keys($totais) as $k) {
                $totais[$k] += $l[$k];
            }
        }

        return ['linhas' => array_values($linhas), 'totais' => $totais];
    }

    /* ---------------------------------------------------------------------
     * Opções para filtros
     * ------------------------------------------------------------------ */

    public function opcoes_filtros()
    {
        $anos = $this->db->query('SELECT DISTINCT YEAR(data_venda) a FROM ' . $this->t_vendas()
            . " WHERE estado IN ('vendido','concluido') AND data_venda IS NOT NULL ORDER BY a DESC")->result_array();
        $emps = $this->db->query('SELECT DISTINCT empreendimento e FROM ' . $this->t_vendas()
            . " WHERE estado IN ('vendido','concluido') AND empreendimento<>'' ORDER BY e")->result_array();
        $com  = $this->db->query('SELECT DISTINCT v.staff_id, CONCAT(s.firstname," ",s.lastname) nome FROM ' . $this->t_vendas()
            . ' v LEFT JOIN ' . db_prefix() . "staff s ON s.staffid=v.staff_id WHERE v.estado IN ('vendido','concluido') ORDER BY nome")->result_array();

        // Meses com vendas, para o selector de período não oferecer meses vazios.
        $meses = $this->db->query("SELECT DISTINCT DATE_FORMAT(data_venda,'%Y-%m') m FROM " . $this->t_vendas()
            . " WHERE estado IN ('vendido','concluido') AND data_venda IS NOT NULL ORDER BY m DESC LIMIT 24")->result_array();

        return [
            'anos'       => array_column($anos, 'a'),
            'meses'      => array_column($meses, 'm'),
            'emps'       => array_column($emps, 'e'),
            'comerciais' => $com,
        ];
    }

    private function num($v)
    {
        $v = str_replace([' ', '€'], '', (string) $v);
        if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } else {
            $v = str_replace(',', '.', $v);
        }

        return (float) preg_replace('/[^0-9.\-]/', '', $v);
    }

    /* =====================================================================
     * MOLONI
     * ================================================================== */

    public function moloni_config()
    {
        return [
            'dev_id'     => get_option('dps_painel_moloni_dev_id'),
            'secret'     => get_option('dps_painel_moloni_secret'),
            'email'      => get_option('dps_painel_moloni_email'),
            'password'   => get_option('dps_painel_moloni_password'),
            'company_id' => get_option('dps_painel_moloni_company_id'),
        ];
    }

    public function moloni_save_config($data)
    {
        update_option('dps_painel_moloni_dev_id', trim((string) ($data['dev_id'] ?? '')));
        update_option('dps_painel_moloni_secret', trim((string) ($data['secret'] ?? '')));
        update_option('dps_painel_moloni_email', trim((string) ($data['email'] ?? '')));
        if (($data['password'] ?? '') !== '') {
            update_option('dps_painel_moloni_password', trim((string) $data['password']));
        }
        update_option('dps_painel_moloni_company_id', trim((string) ($data['company_id'] ?? '')));

        return true;
    }

    /**
     * Pede um access_token à Moloni (grant password). Devolve o token ou um erro.
     */
    public function moloni_token()
    {
        $c = $this->moloni_config();
        if (empty($c['dev_id']) || empty($c['secret']) || empty($c['email']) || empty($c['password'])) {
            return ['ok' => false, 'error' => 'Faltam credenciais Moloni nas definições.'];
        }

        $url = 'https://api.moloni.pt/v1/grant/?' . http_build_query([
            'grant_type'    => 'password',
            'client_id'     => $c['dev_id'],
            'client_secret' => $c['secret'],
            'username'      => $c['email'],
            'password'      => $c['password'],
        ]);

        $resp = $this->http_get($url);
        $json = json_decode($resp['body'], true);

        if (isset($json['access_token'])) {
            return ['ok' => true, 'access_token' => $json['access_token'], 'refresh_token' => $json['refresh_token'] ?? null];
        }

        $msg = is_array($json) && isset($json['error_description']) ? $json['error_description']
            : ('Resposta inesperada da Moloni (HTTP ' . $resp['code'] . ').');

        return ['ok' => false, 'error' => $msg, 'raw' => $resp['body']];
    }

    /**
     * Testa a ligação: pede token e lista as empresas da conta.
     */
    public function moloni_test()
    {
        $tok = $this->moloni_token();
        if (!$tok['ok']) {
            return $tok;
        }

        $url  = 'https://api.moloni.pt/v1/companies/getAll/?access_token=' . urlencode($tok['access_token']);
        $resp = $this->http_get($url);
        $json = json_decode($resp['body'], true);

        if (is_array($json) && isset($json[0]['company_id'])) {
            $empresas = array_map(function ($e) {
                return ['id' => $e['company_id'], 'nome' => $e['name'] ?? ('#' . $e['company_id'])];
            }, $json);

            return ['ok' => true, 'empresas' => $empresas];
        }

        return ['ok' => false, 'error' => 'Ligou, mas não devolveu empresas. Verifica as credenciais/empresa.', 'raw' => $resp['body']];
    }

    private function http_get($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['body' => $body, 'code' => $code];
    }

    /**
     * Meses que têm despesas lançadas, do mais recente para trás.
     * Alimenta o selector — só se oferecem meses que existem.
     */
    public function meses_com_despesas()
    {
        $r = $this->db->select("DATE_FORMAT(data, '%Y-%m') AS mes, COUNT(*) n, SUM(valor) total", false)
                      ->from($this->t_despesas())
                      ->group_by('mes')
                      ->order_by('mes', 'DESC')
                      ->get()->result_array();

        // O mês corrente aparece sempre, mesmo vazio: é onde se lança.
        $agora = date('Y-m');
        foreach ($r as $x) {
            if ($x['mes'] === $agora) {
                return $r;
            }
        }
        array_unshift($r, ['mes' => $agora, 'n' => 0, 'total' => 0]);

        return $r;
    }

    /** Soma por categoria, para o quadro e para o PDF. */
    public function totais_despesas_por_categoria($despesas)
    {
        $t = ['total' => 0];
        foreach ($despesas as $d) {
            $c = trim((string) $d['categoria']) ?: 'Outros';
            $t[$c] = ($t[$c] ?? 0) + (float) $d['valor'];
            $t['total'] += (float) $d['valor'];
        }

        return $t;
    }
}
