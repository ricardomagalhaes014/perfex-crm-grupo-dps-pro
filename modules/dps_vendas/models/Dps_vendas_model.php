<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Dps_vendas_model extends App_Model
{
    /**
     * Estados da venda:
     *  reservado -> submetido (automático ao enviar o email ao promotor)
     *            -> vendido (rótulo "CPCV" — o admin muda manualmente depois
     *               de o promotor aceitar; fixa a comissão)
     *            -> concluido (automático ao confirmar o pagamento)
     *  Qualquer estado pode ir para "cancelado".
     */
    public static $fluxo = ['pedido', 'reservado', 'submetido', 'vendido', 'concluido', 'cancelado'];

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
            'cliente_codigo_postal' => $data['cliente_codigo_postal'] ?? null,
            /*
             * Dados do CPCV. Só o Aura os pede no formulário, mas as colunas
             * aceitam-se sempre: se um dia outro empreendimento precisar, é só
             * mostrar os campos — o modelo já os guarda.
             */
            'cliente_nif'           => $data['cliente_nif'] ?? null,
            'cliente_cc'            => $data['cliente_cc'] ?? null,
            'cliente_cc_validade'   => !empty($data['cliente_cc_validade']) ? $data['cliente_cc_validade'] : null,
            'cliente_naturalidade'  => $data['cliente_naturalidade'] ?? null,
            'cliente_nacionalidade' => $data['cliente_nacionalidade'] ?? null,
            'cliente_freguesia'     => $data['cliente_freguesia'] ?? null,
            'cliente_concelho'      => $data['cliente_concelho'] ?? null,
            // Particular ou Empresa. Decide como o comprador é identificado
            // no CPCV e na declaração de cedência.
            'cliente_tipo'              => $data['cliente_tipo'] ?? null,
            'cliente_crc'               => $data['cliente_crc'] ?? null,
            'cliente_representante'     => $data['cliente_representante'] ?? null,
            'cliente_representante_nif' => $data['cliente_representante_nif'] ?? null,
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

        // A taxa vem da regra do empreendimento e fica gravada na venda como
        // snapshot, para o mapa não depender da regra a cada leitura. Quem
        // alterar a regra mais tarde reescreve estas colunas nas vendas ainda
        // não pagas — ver reaplicar_regra_as_vendas().
        $regra = $this->get_regra($venda['empreendimento']);
        if ($regra) {
            $venda['taxa']           = $regra['taxa'];
            $venda['cpcv_taxa']      = $regra['cpcv_taxa'];
            $venda['escritura_taxa'] = $regra['escritura_taxa'];

            // Meses previstos por omissão: a venda nasce já com a previsão da
            // regra, e a direção só corrige as excepções em vez de preencher
            // venda a venda.
            $venda['cpcv_mes_previsto']      = $regra['cpcv_mes_previsto'] ?? null;
            $venda['escritura_mes_previsto'] = $regra['escritura_mes_previsto'] ?? null;
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
            'cliente_codigo_postal' => $data['cliente_codigo_postal'] ?? null,
            /*
             * Dados do CPCV. Só o Aura os pede no formulário, mas as colunas
             * aceitam-se sempre: se um dia outro empreendimento precisar, é só
             * mostrar os campos — o modelo já os guarda.
             */
            'cliente_nif'           => $data['cliente_nif'] ?? null,
            'cliente_cc'            => $data['cliente_cc'] ?? null,
            'cliente_cc_validade'   => !empty($data['cliente_cc_validade']) ? $data['cliente_cc_validade'] : null,
            'cliente_naturalidade'  => $data['cliente_naturalidade'] ?? null,
            'cliente_nacionalidade' => $data['cliente_nacionalidade'] ?? null,
            'cliente_freguesia'     => $data['cliente_freguesia'] ?? null,
            'cliente_concelho'      => $data['cliente_concelho'] ?? null,
            // Particular ou Empresa. Decide como o comprador é identificado
            // no CPCV e na declaração de cedência.
            'cliente_tipo'              => $data['cliente_tipo'] ?? null,
            'cliente_crc'               => $data['cliente_crc'] ?? null,
            'cliente_representante'     => $data['cliente_representante'] ?? null,
            'cliente_representante_nif' => $data['cliente_representante_nif'] ?? null,
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

        /*
         * Alterar o valor (ou a taxa) tem de refazer a comissão: se se corrige
         * um preço para menos e a comissão continuasse calculada sobre o
         * anterior, pagava-se a mais — e ninguém dava por isso.
         *
         * Só se recalcula enquanto a comissão ainda NÃO foi paga. Uma comissão
         * já "recebida" é histórico e não se reescreve.
         */
        $venda = $this->get_venda($id);

        /*
         * Inclui os estados ANTES do CPCV (pedido, reservado, pendente,
         * submetido). Antes só recalculava de "vendido" para cima, e as vendas
         * que nascem de uma proposta aceite já trazem uma comissão escrita: a
         * venda #42 ficou com 314.900 € de valor e 0 € de comissão porque o
         * valor foi corrigido enquanto ela ainda estava em "reservado", e o
         * número velho ficou lá a mentir no mapa.
         *
         * Recalcular aqui não toca em nada de definitivo: a comissão só fica
         * congelada no CPCV (ver snapshot_taxas) e uma comissão já recebida
         * continua protegida pela condição de cima.
         */
        if ($venda && $venda['comissao_estado'] !== 'recebida'
            && !in_array($venda['estado'], ['cancelado'], true)) {
            $calculo = $this->calcular_comissao($venda);

            if ((float) $calculo['valor'] !== (float) $venda['comissao_total']) {
                $this->db->where('id', $id);
                $this->db->update($this->tabela_vendas(), ['comissao_total' => $calculo['valor']]);

                $this->registar_historico(
                    $id,
                    null,
                    $venda['estado'],
                    'Comissão recalculada para ' . number_format($calculo['valor'], 2, ',', '.')
                        . ' € (valor da venda alterado para '
                        . number_format((float) $venda['valor'], 2, ',', '.') . ' €)'
                );
            }
        }

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

        $aviso = null;

        // É ao passar a "vendido" (CPCV) que a comissão passa a ser devida.
        if ($novo_estado === 'vendido') {
            $calculo = $this->calcular_comissao($venda);

            // Sem taxa definida a comissão fica a 0. Antes travávamos a
            // mudança de estado; isso impedia o admin de trabalhar quando a
            // regra ainda não estava criada. Agora deixa passar e AVISA — o
            // risco de uma comissão a zero passar despercebida fica coberto
            // pelo aviso, e a venda pode seguir o seu circuito.
            if ($calculo['taxa'] <= 0) {
                $aviso = 'Estado actualizado, mas não há taxa de comissão definida para "'
                    . $venda['empreendimento'] . '" — a comissão ficou a 0 €. '
                    . 'Defina a regra em Vendas &gt; Regras de Comissão (ou a taxa na própria venda) '
                    . 'e volte a guardar para a recalcular.';
            }

            /*
             * No CPCV fixa-se o VALOR (a taxa de hoje, não a de amanhã), mas a
             * comissão ainda NÃO é devida. Só passa a "a receber" no fim do
             * circuito: concluída + pagamento validado pela direção + recibo
             * do comercial entregue. Ver avaliar_comissao().
             */
            $update['comissao_total']  = $calculo['valor'];

            // Congela também as taxas: a partir do CPCV, mexer na regra do
            // empreendimento não pode reescrever esta venda (nem o seu total,
            // nem a repartição em parcelas).
            $update += $this->snapshot_taxas($venda, $calculo);
        } elseif ($novo_estado === 'concluido') {
            // Rede de segurança para vendas que nunca passaram por "vendido"
            // (histórico, correcções à mão): ao concluir, fixa-se o que estava
            // em vigor.
            $update += $this->snapshot_taxas($venda, $this->calcular_comissao($venda));

            /*
             * Negócio fechado: quem comprou deixa de ser lead e passa a
             * cliente. A criação fica para depois de o estado estar mesmo
             * gravado — criá-lo antes deixaria uma ficha órfã se a gravação
             * falhasse.
             */
            $passar_a_cliente = true;
        } elseif ($novo_estado === 'cancelado') {
            // Venda cancelada não gera comissão.
            $update['comissao_estado'] = 'na';
            $update['comissao_total']  = 0;
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_vendas(), $update);

        /*
         * A passagem a cliente é "melhor esforço": se falhar, a venda fica
         * concluída na mesma e o botão de sincronizar recupera-a depois. Uma
         * falha a criar a ficha do cliente não pode travar o fecho de um
         * negócio.
         */
        if (!empty($passar_a_cliente)) {
            try {
                $this->garantir_cliente($id);
            } catch (\Throwable $e) {
                log_activity('Venda #' . (int) $id . ' concluída, mas falhou a passagem a cliente: '
                    . $e->getMessage());
            }
        }

        /*
         * O simulador acompanha SEMPRE o CRM (todos os empreendimentos):
         *   pedido/reservado/submetido -> Reservado
         *   CPCV/concluído             -> Vendido
         *   cancelado                  -> Disponível (volta ao mercado)
         *
         * Sem isto a montra mentia: uma unidade com CPCV assinado continuava
         * a aparecer reservada, e uma venda cancelada ficava presa para
         * sempre. O administrador continua a poder sobrepor à mão no
         * simulador com a password do Modo de Edição.
         */
        $alvo = self::estado_simulador($novo_estado, $venda['empreendimento'] ?? '');

        if ($alvo !== null
            && !$this->sincronizar_unidade_simulador($venda['empreendimento'], $venda['unidade'], $novo_estado)) {
            $extra = 'Estado actualizado, mas não consegui pôr a fração como "' . $alvo
                . '" no simulador — verifique com o Modo de Edição.';
            $aviso = $aviso ? $aviso . ' ' . $extra : $extra;
        }

        $this->registar_historico($id, $estado_atual, $novo_estado, $nota);

        // A mudança de estado pode ter completado (ou desfeito) o circuito.
        $this->avaliar_comissao($id);

        hooks()->do_action('dps_venda_estado_alterado', [
            'venda_id'    => $id,
            'estado_de'   => $estado_atual,
            'estado_para' => $novo_estado,
        ]);

        return ['ok' => true, 'aviso' => $aviso];
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
     * Circuito CPCV / Pagamento
     * ------------------------------------------------------------------ */

    /**
     * Visto de "assinado" no CPCV. O admin carrega o CPCV, o comercial envia-o
     * ao cliente e, quando volta assinado, é aqui que fica o registo — e passa
     * a aparecer assinado do lado do admin.
     */
    public function marcar_cpcv_assinado($id, $assinado = true)
    {
        $venda = $this->get_venda($id);
        if (!$venda) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_vendas(), [
            'cpcv_assinado'    => $assinado ? 1 : 0,
            'cpcv_assinado_em' => $assinado ? date('Y-m-d H:i:s') : null,
            'dateupdated'      => date('Y-m-d H:i:s'),
        ]);

        $this->registar_historico(
            $id,
            null,
            $venda['estado'],
            $assinado ? 'CPCV assinado' : 'CPCV marcado como não assinado'
        );

        return true;
    }

    /**
     * Desfaz o visto de "pago" (enganos acontecem: marcar a fração errada).
     * A venda volta ao estado anterior e a comissão deixa de ser devida.
     */
    public function desmarcar_pago($id, $motivo = '')
    {
        $venda = $this->get_venda($id);
        if (!$venda) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_vendas(), [
            'pago'        => 0,
            'pago_em'     => null,
            'dateupdated' => date('Y-m-d H:i:s'),
        ]);

        $this->registar_historico(
            $id,
            null,
            $venda['estado'],
            'Pagamento DESMARCADO' . ($motivo !== '' ? ' — ' . $motivo : '')
        );

        // Sem pagamento validado a comissão volta a não ser devida.
        $this->avaliar_comissao($id);

        return true;
    }

    /**
     * Desfaz o visto de "CPCV assinado".
     */
    public function desmarcar_cpcv_assinado($id, $motivo = '')
    {
        $venda = $this->get_venda($id);
        if (!$venda) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_vendas(), [
            'cpcv_assinado'    => 0,
            'cpcv_assinado_em' => null,
            'dateupdated'      => date('Y-m-d H:i:s'),
        ]);

        $this->registar_historico(
            $id,
            null,
            $venda['estado'],
            'CPCV desmarcado como assinado' . ($motivo !== '' ? ' — ' . $motivo : '')
        );

        return true;
    }

    /**
     * Confirma o pagamento (o comercial já carregou o comprovativo) e conclui a
     * venda automaticamente. A comissão não é tocada aqui: foi fixada quando a
     * venda passou a "vendido".
     */
    public function marcar_pago($id)
    {
        $venda = $this->get_venda($id);
        if (!$venda) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update($this->tabela_vendas(), [
            'pago'        => 1,
            'pago_em'     => date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s'),
        ]);

        if ($venda['estado'] !== 'concluido') {
            // mudar_estado trata do histórico da transição (e reavalia a comissão)
            $this->mudar_estado($id, 'concluido', 'Pagamento confirmado — concluída automaticamente');
        } else {
            $this->registar_historico($id, null, 'concluido', 'Pagamento confirmado');
            // Já estava concluída: reavaliar aqui, senão a validação do
            // pagamento não desbloqueava a comissão.
            $this->avaliar_comissao($id);
        }

        return true;
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
        $valor     = (float) $venda['valor'];
        $taxa      = isset($venda['taxa']) ? (float) $venda['taxa'] : 0;
        $cpcv      = isset($venda['cpcv_taxa']) ? (float) $venda['cpcv_taxa'] : 0;
        $escritura = isset($venda['escritura_taxa']) ? (float) $venda['escritura_taxa'] : 0;
        $fonte     = 'venda';

        if ($taxa <= 0) {
            $regra = $this->get_regra($venda['empreendimento']);
            if ($regra) {
                $taxa      = (float) $regra['taxa'];
                $cpcv      = (float) ($regra['cpcv_taxa'] ?? 0);
                $escritura = (float) ($regra['escritura_taxa'] ?? 0);
                $fonte     = 'regra';
            }
        }

        if ($cpcv < 0) {
            $cpcv = 0;
        }
        if ($escritura < 0) {
            $escritura = 0;
        }

        /*
         * ATENÇÃO à unidade das taxas — foi aqui que se enganou uma versão
         * anterior deste código:
         *
         *   taxa            -> percentagem do VALOR DA VENDA (ex.: 2,5%)
         *   cpcv_taxa       -> percentagem DA COMISSÃO paga no CPCV (ex.: 66%)
         *   escritura_taxa  -> percentagem DA COMISSÃO paga na escritura (34%)
         *
         * É assim que a direção as define ("a comissão é 1,5%, paga 66% no
         * CPCV e 34% na escritura"). Tratar 66 como percentagem da venda dava
         * uma parcela de 66% do preço do imóvel — números absurdos.
         */
        $comissao = round($valor * $taxa / 100, 2);

        $soma_partes  = $cpcv + $escritura;
        $reconciliado = false;

        if ($soma_partes <= 0) {
            // Sem repartição definida: tudo numa parcela só (o CPCV).
            $cpcv        = 100.0;
            $escritura   = 0.0;
            $soma_partes = 100.0;
        } elseif (abs($soma_partes - 100) > 0.001) {
            // A repartição tem de somar 100% da comissão. Se não somar,
            // reproporciona-se e avisa-se — melhor do que pagar a mais ou a menos.
            $cpcv         = round($cpcv * 100 / $soma_partes, 6);
            $escritura    = round(100 - $cpcv, 6);
            $reconciliado = true;
        }

        $valor_cpcv = round($comissao * $cpcv / 100, 2);
        // O cêntimo do arredondamento fica na escritura, para as duas parcelas
        // somarem exactamente a comissão.
        $valor_escritura = round($comissao - $valor_cpcv, 2);

        return [
            'valor'           => $comissao,
            'taxa'            => $taxa,
            'fonte'           => $fonte,
            // Percentagens DA COMISSÃO (não da venda).
            'cpcv_taxa'       => $cpcv,
            'escritura_taxa'  => $escritura,
            'valor_cpcv'      => $valor_cpcv,
            'valor_escritura' => $valor_escritura,
            'reconciliado'    => $reconciliado,
            'soma_parciais'   => $soma_partes,
        ];
    }

    /**
     * Snapshot das taxas em vigor, para a venda deixar de depender da regra.
     *
     * Enquanto a venda não tem taxa própria, calcular_comissao() vai à regra a
     * CADA leitura — e como o conjunto de parcelas é derivado em tempo de
     * leitura (e a parcela única partilha as colunas do CPCV), editar a regra
     * mudava retroactivamente as parcelas de vendas antigas: uma comissão já
     * paga por inteiro reabria-se com metade por pagar. Congelar as taxas na
     * venda corta essa dependência.
     *
     * @return array colunas a gravar (vazio quando não há nada a congelar)
     */
    /**
     * Público desde a importação de vendas por ficheiro: uma venda importada
     * já vem no estado em que está, não percorre o circuito, e por isso o
     * controlador tem de fixar as taxas por fora do mudar_estado().
     */
    public function snapshot_taxas($venda, $calculo)
    {
        if ((float) ($venda['taxa'] ?? 0) > 0 || $calculo['taxa'] <= 0) {
            return [];
        }

        return [
            'taxa'           => $calculo['taxa'],
            'cpcv_taxa'      => $calculo['cpcv_taxa'],
            'escritura_taxa' => $calculo['escritura_taxa'],
        ];
    }

    /** Congela na venda as taxas que a regra está a ditar neste momento. */
    public function congelar_taxas($venda_id)
    {
        $venda = $this->get_venda($venda_id);
        if (!$venda) {
            return false;
        }

        $snapshot = $this->snapshot_taxas($venda, $this->calcular_comissao($venda));
        if (empty($snapshot)) {
            return false;
        }

        $this->db->where('id', (int) $venda_id);
        $this->db->update($this->tabela_vendas(), $snapshot);

        return true;
    }

    /**
     * Decide se a comissão já é devida, e actualiza o estado em conformidade.
     *
     * Regra da direção — só é "a receber" quando os TRÊS passos estão feitos:
     *   1. a venda está Concluída;
     *   2. o pagamento do cliente foi validado pela direção (marcado PAGO);
     *   3. o comercial entregou o recibo da comissão.
     *
     * Enquanto faltar um deles fica "na" (ainda não devida). Uma comissão já
     * marcada como "recebida" nunca é mexida — isso é histórico.
     *
     * @return string o estado em que a comissão ficou
     */
    public function avaliar_comissao($venda_id)
    {
        $venda = $this->get_venda($venda_id);

        if (!$venda) {
            return 'na';
        }

        if ($venda['comissao_estado'] === 'recebida') {
            return 'recebida';
        }

        $completo = $venda['estado'] === 'concluido'
            && !empty($venda['pago'])
            && !empty($venda['comissao_recibo_doc']);

        $novo = $completo ? 'a_receber' : 'na';

        if ($novo !== $venda['comissao_estado']) {
            $this->db->where('id', (int) $venda_id);
            $this->db->update($this->tabela_vendas(), ['comissao_estado' => $novo]);
        }

        return $novo;
    }

    /**
     * O que falta para a comissão desta venda ser devida (texto curto para a
     * listagem). Devolve '' quando já não falta nada.
     */
    public static function falta_para_comissao($venda, $parcela = '')
    {
        if ($venda['estado'] !== 'concluido') {
            return 'Aguarda conclusão da venda';
        }
        if (empty($venda['pago'])) {
            return 'Aguarda validação do pagamento';
        }

        /*
         * CADA UM ESPERA PELO SEU RECIBO.
         *
         * O override da direção tem recibo próprio, em coluna própria — e a
         * linha dele estava a olhar para o recibo do COMERCIAL. O Cláudio
         * entregou o dele e as vendas #22 e #23 continuavam a dizer "aguarda
         * recibo", porque o Sérgio ainda não tinha entregue o seu. Um
         * bloqueava o outro sem que nada o dissesse. Corrigido a 05/08/2026.
         */
        if ($parcela === 'direcao') {
            return empty($venda['direcao_recibo_doc']) ? 'Aguarda recibo da direção' : '';
        }

        if (empty($venda['comissao_recibo_doc'])) {
            return 'Aguarda recibo do comercial';
        }

        return '';
    }

    /**
     * Vendas que entram no quadro de comissões.
     *
     * Regra da direção: "as comissões é o quadro de comissões que entra quando
     * fica concluído". Só entram vendas CONCLUÍDAS — enquanto a venda está em
     * curso é o mapa de vendas que manda, e as comissões não o poluem.
     *
     * Continuam a entrar as concluídas ainda sem comissão devida (falta o
     * recibo ou a validação do pagamento): é neste quadro que o comercial
     * anexa o recibo, por isso se as escondêssemos o circuito nunca fechava.
     *
     * @param array $filtros empreendimento, comercial_id
     */
    /**
     * @param array $estados que estados de venda entram. Por omissão só as
     *                       concluídas (as que já podem gerar pagamento). O
     *                       quadro de comissões junta-lhes as que estão em
     *                       CPCV, para se ver o que aí vem — marcadas como
     *                       futuras, nunca misturadas com o que é devido.
     */
    public function get_comissoes($apenas_minhas = false, $filtros = [], $estados = ['concluido'])
    {
        $this->db->select('v.*, CONCAT(s.firstname, " ", s.lastname) AS comercial_nome');
        $this->db->from($this->tabela_vendas() . ' v');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = v.staff_id', 'left');
        $this->db->where_in('v.estado', $estados);

        if ($apenas_minhas) {
            $this->db->where('v.staff_id', get_staff_user_id());
        }

        if (!empty($filtros['empreendimento'])) {
            $this->db->where('v.empreendimento', $filtros['empreendimento']);
        }

        if (!empty($filtros['comercial_id'])) {
            $this->db->where('v.staff_id', (int) $filtros['comercial_id']);
        }

        $this->db->order_by('v.staff_id', 'ASC');
        $this->db->order_by('v.id', 'DESC');

        return $this->db->get()->result_array();
    }

    /* ---------------------------------------------------------------------
     * Parcelas da comissão (CPCV / Escritura) e previsão por mês
     * ------------------------------------------------------------------ */

    /**
     * Onde vive cada parcela na tabela de vendas.
     *
     * A parcela 'total' é a retrocompatibilidade: vendas antigas (e regras sem
     * repartição) têm uma comissão só, e essa reutiliza as colunas do CPCV —
     * são o primeiro slot e não haveria segunda parcela a disputá-las.
     */
    public static $colunas_parcela = [
        'cpcv' => [
            'etiqueta' => 'CPCV',
            'mes'      => 'cpcv_mes_previsto',
            'pago'     => 'cpcv_pago',
            'pago_em'  => 'cpcv_pago_em',
        ],
        'escritura' => [
            'etiqueta' => 'Escritura',
            'mes'      => 'escritura_mes_previsto',
            'pago'     => 'escritura_paga',
            'pago_em'  => 'escritura_paga_em',
        ],
        'total' => [
            'etiqueta' => 'Comissão total',
            'mes'      => 'cpcv_mes_previsto',
            'pago'     => 'cpcv_pago',
            'pago_em'  => 'cpcv_pago_em',
        ],
        /*
         * O override da direção viaja pelo mesmo circuito das comissões: tem
         * recibo para carregar, validação da direção e marca de pago. Vive em
         * colunas próprias para não disputar as do comercial — a mesma venda
         * pode ter a comissão paga e o override por pagar, que é o caso mais
         * comum. Pedido do dono (04/08/2026).
         */
        'direcao' => [
            'etiqueta' => 'Direção (0,5%)',
            'mes'      => 'cpcv_mes_previsto',
            'pago'     => 'direcao_pago',
            'pago_em'  => 'direcao_pago_em',
        ],
    ];

    /**
     * O override da direção nesta venda: quanto, e a quem.
     *
     * Devolve null quando não há override — vendas do comercial a 0% (a
     * comissão fica em casa, não há de onde tirar meio ponto) ou a 100% (não
     * sobra nada), e quando não há director definido.
     */
    public static function direcao_da_venda($venda)
    {
        $director = (int) get_option('dps_painel_director_id');

        if ($director <= 0) {
            return null;
        }

        $lista = function ($nome) {
            $out = [];
            foreach (explode(',', (string) get_option($nome)) as $x) {
                $x = (int) trim($x);
                if ($x > 0) { $out[] = $x; }
            }

            return $out;
        };

        $staff = (int) ($venda['staff_id'] ?? 0);

        if (in_array($staff, $lista('dps_painel_comerciais_0'), true)
            || in_array($staff, $lista('dps_painel_comerciais_100'), true)) {
            return null;
        }

        $pct = (string) get_option('dps_painel_director_pct');
        $pct = $pct === '' ? 0.5 : (float) str_replace(',', '.', $pct);

        $valor = round((float) ($venda['valor'] ?? 0) * $pct / 100, 2);

        if ($valor <= 0) {
            return null;
        }

        return ['director_id' => $director, 'pct' => $pct, 'valor' => $valor];
    }

    /**
     * Divide a comissão de uma venda nas parcelas que a direção paga.
     *
     * Devolve ['cpcv' => [...], 'escritura' => [...]] quando a regra reparte a
     * comissão, ou ['total' => [...]] quando não há repartição definida.
     *
     * Um mês VAZIO significa "pago na hora" — é a convenção pedida: só se
     * escreve o mês quando o recebimento é diferido.
     */
    /**
     * Marca/desmarca o recebimento da DPS. A data é o que o Painel do Negócio
     * usa para o filtro por mês, por isso é gravada explicitamente.
     */
    public function marcar_recebido_dps($id, $data)
    {
        $this->db->where('id', (int) $id)->update($this->tabela_vendas(), [
            'recebido_dps'     => 1,
            'recebido_dps_em'  => $data,
            'recebido_dps_por' => get_staff_user_id(),
        ]);

        return $this->db->affected_rows() > 0;
    }

    public function desmarcar_recebido_dps($id)
    {
        $this->db->where('id', (int) $id)->update($this->tabela_vendas(), [
            'recebido_dps'     => 0,
            'recebido_dps_em'  => null,
            'recebido_dps_por' => null,
        ]);

        return $this->db->affected_rows() > 0;
    }

    public function parcelas_comissao($venda)
    {
        $calculo = $this->calcular_comissao($venda);

        $definicao = [];

        if ((float) $calculo['cpcv_taxa'] > 0 || (float) $calculo['escritura_taxa'] > 0) {
            $definicao['cpcv'] = [
                'taxa'  => (float) $calculo['cpcv_taxa'],
                'valor' => (float) $calculo['valor_cpcv'],
            ];
            $definicao['escritura'] = [
                'taxa'  => (float) $calculo['escritura_taxa'],
                'valor' => (float) $calculo['valor_escritura'],
            ];
        } else {
            // Sem repartição: a comissão é uma parcela só, com a taxa cheia.
            $definicao['total'] = [
                'taxa'  => (float) $calculo['taxa'],
                'valor' => (float) $calculo['valor'],
            ];
        }

        /*
         * Prazos: manda o mês gravado na própria venda; na falta dele vale o da
         * regra do empreendimento.
         *
         * As vendas antigas foram criadas antes de as regras terem meses e
         * ficaram com os campos vazios — sem este recurso, as comissões do Belo
         * Horizonte apareciam como "assim que concluir" quando na verdade têm
         * data marcada (12/2026 e 12/2028) na regra.
         */
        $regra = $this->get_regra($venda['empreendimento'] ?? '');

        $mes_de = function ($campo) use ($venda, $regra) {
            $da_venda = isset($venda[$campo]) ? trim((string) $venda[$campo]) : '';
            if ($da_venda !== '') {
                return $da_venda;
            }

            return $regra && !empty($regra[$campo]) ? (string) $regra[$campo] : '';
        };

        $parcelas = [];

        foreach ($definicao as $chave => $dados) {
            $cols = self::$colunas_parcela[$chave];

            $parcelas[$chave] = [
                'chave'    => $chave,
                'etiqueta' => $cols['etiqueta'],
                'taxa'     => $dados['taxa'],
                'valor'    => $dados['valor'],
                'mes'      => $mes_de($cols['mes']),
                'mes_da_regra' => (isset($venda[$cols['mes']]) && trim((string) $venda[$cols['mes']]) !== '') ? false : true,
                'pago'     => !empty($venda[$cols['pago']]),
                'pago_em'  => $venda[$cols['pago_em']] ?? null,
            ];
        }

        return $parcelas;
    }

    /** Aceita 'YYYY-MM' ou vazio (pago na hora). Devolve null quando é lixo. */
    public static function mes_valido($mes)
    {
        $mes = trim((string) $mes);

        if ($mes === '') {
            return '';
        }

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) ? $mes : null;
    }

    /**
     * Previsão de comissões agregada por mês.
     *
     * As parcelas sem mês (pagas na hora) não se perdem: vão para 'sem_data',
     * senão o total da previsão não batia certo com o que há a pagar.
     *
     * @param array $filtros empreendimento, comercial_id
     */
    /**
     * @param bool $incluir_futuras junta as vendas em CPCV (ainda não
     *        concluídas). A comissão delas já tem valor e data, mas ainda não
     *        é devida — vai para o balde 'futuro' e nunca para 'por_pagar',
     *        senão o total a pagar prometia dinheiro que ainda não venceu.
     */
    public function previsao_comissoes($filtros = [], $apenas_minhas = false, $incluir_futuras = true)
    {
        $estados = $incluir_futuras ? ['concluido', 'vendido'] : ['concluido'];

        /*
         * O DIRECTOR VÊ TUDO, mas só as linhas que são dele.
         *
         * O override incide sobre vendas dos OUTROS — se a leitura ficasse
         * restrita às vendas dele, o Cláudio nunca via os 0,5% que lhe são
         * devidos, que é precisamente o que este quadro existe para mostrar.
         * Lê-se tudo e filtram-se as linhas no fim: as dele como comercial,
         * mais as da direção de toda a gente.
         */
        $eu        = (int) get_staff_user_id();
        $sou_dir   = $eu > 0 && $eu === (int) get_option('dps_painel_director_id');
        $so_minhas = $apenas_minhas && !$sou_dir;

        $vendas = $this->get_comissoes($so_minhas, $filtros, $estados);

        $vazio = ['previsto' => 0.0, 'pago' => 0.0, 'por_pagar' => 0.0, 'futuro' => 0.0, 'retido' => 0.0, 'linhas' => []];

        $meses    = [];
        $sem_data = $vazio;
        $totais   = ['previsto' => 0.0, 'pago' => 0.0, 'por_pagar' => 0.0, 'futuro' => 0.0, 'retido' => 0.0];
        $linhas   = [];

        foreach ($vendas as $venda) {
            $bloqueio = self::falta_para_comissao($venda);

            foreach ($this->parcelas_comissao($venda) as $parcela) {
                // Parcelas a zero não são previsão nenhuma — só ruído na tabela.
                if ($parcela['valor'] <= 0 && !$parcela['pago']) {
                    continue;
                }

                $linha = [
                    'venda_id'       => (int) $venda['id'],
                    'empreendimento' => $venda['empreendimento'],
                    'unidade'        => $venda['unidade'],
                    'cliente'        => $venda['cliente'],
                    'comercial_id'   => (int) $venda['staff_id'],
                    'comercial_nome' => $venda['comercial_nome'] ?: 'Sem comercial atribuído',
                    'parcela'        => $parcela['chave'],
                    'etiqueta'       => $parcela['etiqueta'],
                    'taxa'           => $parcela['taxa'],
                    'valor'          => $parcela['valor'],
                    'mes'            => $parcela['mes'],
                    'mes_da_regra'   => !empty($parcela['mes_da_regra']),
                    'pago'           => $parcela['pago'],
                    'pago_em'        => $parcela['pago_em'],
                    // O que ainda falta no circuito (recibo, validação do
                    // pagamento). Vazio = a parcela já pode ser paga.
                    'bloqueio'       => $bloqueio,
                    // Venda ainda em CPCV: a comissão está contratada e datada,
                    // mas o circuito de pagamento ainda não abriu.
                    'futura'         => ($venda['estado'] ?? '') !== 'concluido',
                    /*
                     * Número da factura que a DPS emitiu ao promotor por esta
                     * parcela. Vem do Moloni pelo botão do quadro de comissões,
                     * ou escrito à mão. Vazio significa "ainda não facturada" —
                     * é o que distingue uma comissão devida de uma já titulada.
                     */
                    'fatura_moloni'  => (string) ($venda[
                        $parcela['chave'] === 'escritura' ? 'fatura_moloni_escritura' : 'fatura_moloni_cpcv'
                    ] ?? ''),
                    'venda'          => $venda,
                ];

                /*
                 * Quatro estados, não dois.
                 *
                 *   pago       — já saiu.
                 *   futuro     — venda ainda em CPCV: valor e data fixados, mas
                 *                o circuito de pagamento nem abriu.
                 *   retido     — venda concluída mas SEM comprovativo de
                 *                pagamento validado pela direção. Não é uma
                 *                comissão a pagar: é uma comissão que ainda não
                 *                nasceu. Contá-la em "por pagar" prometia
                 *                dinheiro assente num pagamento por confirmar.
                 *   por_pagar  — comprovativo validado: é devida a sério.
                 *
                 * Regra do dono (30/07/2026): "o por receber só deve
                 * contabilizar o que tem o comprovativo de pagamento validado".
                 */
                if ($linha['pago']) {
                    $onde = 'pago';
                } elseif ($linha['futura']) {
                    $onde = 'futuro';
                } elseif (empty($venda['pago'])) {
                    $onde = 'retido';
                } else {
                    $onde = 'por_pagar';
                }

                $linha['retido'] = ($onde === 'retido');

                $linhas[] = $linha;

                $balde = &$sem_data;
                if ($linha['mes'] !== '') {
                    if (!isset($meses[$linha['mes']])) {
                        $meses[$linha['mes']] = $vazio;
                    }
                    $balde = &$meses[$linha['mes']];
                }

                $balde['previsto'] += $linha['valor'];
                $balde[$onde]      += $linha['valor'];
                $balde['linhas'][]  = $linha;
                unset($balde);

                $totais['previsto'] += $linha['valor'];
                $totais[$onde]      += $linha['valor'];
            }

            /*
             * A LINHA DA DIREÇÃO, a seguir às do comercial.
             *
             * Entra na mesma lista e com a mesma forma, para percorrer o mesmo
             * circuito — recibo carregado, direção valida, marca de pago — sem
             * precisar de um ecrã à parte. O que muda é o dono: a linha é
             * atribuída ao DIRECTOR, não ao comercial da venda, senão aparecia
             * na lista dele uma dívida que não é dele. Pedido do dono
             * (04/08/2026).
             */
            $dir = self::direcao_da_venda($venda);

            /*
             * A LINHA DA DIREÇÃO NÃO ENTRA NO QUADRO DE OUTRO COMERCIAL.
             *
             * Filtrado o ecrã por um comercial, carregam-se as vendas dele — e
             * a linha do override, que é do DIRECTOR, vinha atrás e somava na
             * previsão mensal dele. O Miguel via 10.197,00 € previstos para
             * Julho: 8.497,50 da comissão dele mais 1.699,50 do override do
             * Cláudio sobre a mesma venda. Dinheiro de outra pessoa na conta
             * dele. Corrigido a 05/08/2026.
             *
             * Sem filtro entram todas, que é o quadro geral.
             */
            $filtro_com = (int) ($filtros['comercial_id'] ?? 0);

            if ($dir && $filtro_com > 0 && $filtro_com !== (int) $dir['director_id']) {
                $dir = null;
            }

            if ($dir) {
                $dir_paga = !empty($venda['direcao_pago']);

                $nome_dir = $this->db->select('firstname, lastname')
                    ->where('staffid', $dir['director_id'])
                    ->get(db_prefix() . 'staff')->row();

                $linha = [
                    'venda_id'       => (int) $venda['id'],
                    'empreendimento' => $venda['empreendimento'],
                    'unidade'        => $venda['unidade'],
                    'cliente'        => $venda['cliente'],
                    'comercial_id'   => (int) $dir['director_id'],
                    'comercial_nome' => $nome_dir
                        ? trim($nome_dir->firstname . ' ' . $nome_dir->lastname) : 'Direção',
                    'parcela'        => 'direcao',
                    'etiqueta'       => 'Direção (' . rtrim(rtrim(number_format($dir['pct'], 2, ',', ''), '0'), ',') . '%)',
                    'taxa'           => $dir['pct'],
                    'valor'          => $dir['valor'],
                    'mes'            => (string) ($venda['cpcv_mes_previsto'] ?? ''),
                    'mes_da_regra'   => false,
                    'pago'           => $dir_paga,
                    'pago_em'        => (string) ($venda['direcao_pago_em'] ?? ''),
                    'bloqueio'       => self::falta_para_comissao($venda, 'direcao'),
                    'futura'         => ($venda['estado'] ?? '') !== 'concluido',
                    'fatura_moloni'  => (string) ($venda['fatura_moloni_cpcv'] ?? ''),
                    'e_direcao'      => true,
                    'venda'          => $venda,
                ];

                if ($linha['pago']) {
                    $onde = 'pago';
                } elseif ($linha['futura']) {
                    $onde = 'futuro';
                } elseif (empty($venda['pago'])) {
                    $onde = 'retido';
                } else {
                    $onde = 'por_pagar';
                }

                $linha['retido'] = ($onde === 'retido');
                $linhas[] = $linha;

                $balde = &$sem_data;
                if ($linha['mes'] !== '') {
                    if (!isset($meses[$linha['mes']])) {
                        $meses[$linha['mes']] = $vazio;
                    }
                    $balde = &$meses[$linha['mes']];
                }

                $balde['previsto'] += $linha['valor'];
                $balde[$onde]      += $linha['valor'];
                $balde['linhas'][]  = $linha;
                unset($balde);

                $totais['previsto'] += $linha['valor'];
                $totais[$onde]      += $linha['valor'];
            }
        }

        /*
         * Filtro final para o director: fica com o que é dele, venha da sua
         * própria venda ou do override sobre a de outro.
         */
        if ($apenas_minhas && $sou_dir) {
            $linhas = array_values(array_filter($linhas, function ($l) use ($eu) {
                return (int) $l['comercial_id'] === $eu;
            }));

            $meses    = [];
            $sem_data = $vazio;
            $totais   = ['previsto' => 0.0, 'pago' => 0.0, 'por_pagar' => 0.0, 'futuro' => 0.0, 'retido' => 0.0];

            foreach ($linhas as $l) {
                $onde = $l['pago'] ? 'pago' : ($l['futura'] ? 'futuro' : ($l['retido'] ? 'retido' : 'por_pagar'));

                $balde = &$sem_data;
                if ($l['mes'] !== '') {
                    if (!isset($meses[$l['mes']])) { $meses[$l['mes']] = $vazio; }
                    $balde = &$meses[$l['mes']];
                }
                $balde['previsto'] += $l['valor'];
                $balde[$onde]      += $l['valor'];
                $balde['linhas'][]  = $l;
                unset($balde);

                $totais['previsto'] += $l['valor'];
                $totais[$onde]      += $l['valor'];
            }
        }

        ksort($meses); // 'YYYY-MM' ordena bem como texto

        return [
            'meses'    => $meses,
            'sem_data' => $sem_data,
            'totais'   => $totais,
            'linhas'   => $linhas,
        ];
    }

    /**
     * Define (ou limpa) o mês previsto de uma parcela.
     * Mês vazio = recebimento imediato, sem data a prever.
     */
    public function definir_mes_parcela($venda_id, $parcela, $mes)
    {
        $venda = $this->get_venda($venda_id);

        if (!$venda || !isset(self::$colunas_parcela[$parcela])) {
            return false;
        }

        $mes = self::mes_valido($mes);
        if ($mes === null) {
            return false;
        }

        $cols = self::$colunas_parcela[$parcela];

        $this->db->where('id', (int) $venda_id);
        $this->db->update($this->tabela_vendas(), [
            $cols['mes']  => $mes !== '' ? $mes : null,
            'dateupdated' => date('Y-m-d H:i:s'),
        ]);

        $this->registar_historico(
            (int) $venda_id,
            null,
            $venda['estado'],
            'Comissão — ' . $cols['etiqueta'] . ': mês previsto '
                . ($mes !== '' ? 'definido para ' . $mes : 'limpo (recebimento imediato)')
        );

        return true;
    }

    /**
     * Marca uma parcela como paga ao comercial.
     *
     * Só se paga o que já é devido: se o circuito ainda não fechou (venda
     * concluída + pagamento validado + recibo entregue), recusa. Sem isto
     * pagava-se comissão de uma venda sem recibo — precisamente o que a
     * validação do circuito existe para evitar.
     */
    public function marcar_parcela_paga($venda_id, $parcela, $data = null)
    {
        $venda = $this->get_venda($venda_id);

        if (!$venda || !isset(self::$colunas_parcela[$parcela])) {
            return ['ok' => false, 'erro' => 'Parcela inválida.'];
        }

        /*
         * A DIREÇÃO tem circuito próprio: colunas próprias, e não faz parte da
         * repartição da comissão do comercial. Por isso não passa pela
         * verificação das parcelas nem pelo congelamento das taxas — congelar
         * ali mexia na comissão de outra pessoa.
         */
        if ($parcela === 'direcao') {
            $dir = self::direcao_da_venda($venda);

            if (!$dir) {
                return ['ok' => false, 'erro' => 'Esta venda não gera override da direção.'];
            }

            // O circuito é o mesmo dos comerciais: só se paga depois do recibo.
            $falta_dir = self::falta_para_comissao($venda, 'direcao');

            if ($falta_dir !== '') {
                return ['ok' => false, 'erro' => $falta_dir . ' — ainda não se pode pagar esta parcela.'];
            }

            $data_d = trim((string) $data);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_d)) {
                $data_d = date('Y-m-d');
            }

            $this->db->where('id', (int) $venda_id)->update($this->tabela_vendas(), [
                'direcao_pago'    => 1,
                'direcao_pago_em' => $data_d,
                'dateupdated'     => date('Y-m-d H:i:s'),
            ]);

            $this->registar_historico((int) $venda_id, null, $venda['estado'],
                'Direção — override de ' . number_format($dir['valor'], 2, ',', '.')
                . ' € PAGO em ' . $data_d);

            return ['ok' => true];
        }

        // A parcela tem de existir MESMO nesta venda: 'total' e 'cpcv'
        // partilham colunas, e pagar uma parcela que a venda não tem escrevia
        // um pagamento fantasma numa coluna alheia.
        $parcelas = $this->parcelas_comissao($venda);
        if (!isset($parcelas[$parcela])) {
            return ['ok' => false, 'erro' => 'Essa parcela não existe nesta venda.'];
        }

        $falta = self::falta_para_comissao($venda);
        if ($falta !== '') {
            return ['ok' => false, 'erro' => $falta . ' — ainda não se pode pagar esta parcela.'];
        }

        /*
         * A partir do momento em que se paga, o conjunto de parcelas desta
         * venda deixa de poder mudar: sem isto, acrescentar a repartição à
         * regra do empreendimento transformava uma "Comissão total" já paga
         * numa CPCV paga a menos + uma Escritura por pagar que nunca existiu.
         */
        $this->congelar_taxas($venda_id);

        $data = trim((string) $data);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            $data = date('Y-m-d');
        }

        $cols = self::$colunas_parcela[$parcela];

        $this->db->where('id', (int) $venda_id);
        $this->db->update($this->tabela_vendas(), [
            $cols['pago']    => 1,
            $cols['pago_em'] => $data,
            'dateupdated'    => date('Y-m-d H:i:s'),
        ]);

        $this->registar_historico(
            (int) $venda_id,
            null,
            $venda['estado'],
            'Comissão — ' . $cols['etiqueta'] . ' PAGA ao comercial em ' . $data
        );

        $this->sincronizar_comissao_paga($venda_id);

        return ['ok' => true];
    }

    /** Desfaz o pagamento de uma parcela (enganos acontecem). */
    public function desmarcar_parcela_paga($venda_id, $parcela)
    {
        $venda = $this->get_venda($venda_id);

        if (!$venda || !isset(self::$colunas_parcela[$parcela])) {
            return false;
        }

        $cols = self::$colunas_parcela[$parcela];

        $this->db->where('id', (int) $venda_id);
        $this->db->update($this->tabela_vendas(), [
            $cols['pago']    => 0,
            $cols['pago_em'] => null,
            'dateupdated'    => date('Y-m-d H:i:s'),
        ]);

        $this->registar_historico(
            (int) $venda_id,
            null,
            $venda['estado'],
            'Comissão — ' . $cols['etiqueta'] . ': pagamento DESMARCADO por ' . get_staff_full_name()
        );

        $this->sincronizar_comissao_paga($venda_id);

        return true;
    }

    /**
     * Mantém o antigo `comissao_pago_dps` coerente com as parcelas.
     *
     * A coluna antiga passou a significar "está tudo pago": fica a 1 só quando
     * todas as parcelas estão pagas, com a data da última. Assim tudo o que já
     * dependia dela (avisos, exportações antigas) continua a dizer a verdade,
     * sem ficar a mentir logo à primeira parcela paga.
     */
    private function sincronizar_comissao_paga($venda_id)
    {
        $venda = $this->get_venda($venda_id);
        if (!$venda) {
            return;
        }

        $parcelas = $this->parcelas_comissao($venda);

        $tudo_pago = !empty($parcelas);
        $ultima    = null;

        foreach ($parcelas as $p) {
            if (!$p['pago']) {
                $tudo_pago = false;
                continue;
            }
            if ($ultima === null || $p['pago_em'] > $ultima) {
                $ultima = $p['pago_em'];
            }
        }

        $this->db->where('id', (int) $venda_id);
        $this->db->update($this->tabela_vendas(), [
            'comissao_pago_dps'    => $tudo_pago ? 1 : 0,
            'comissao_pago_dps_em' => $tudo_pago ? $ultima : null,
        ]);
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

    /**
     * A repartição (CPCV + Escritura) tem de somar a taxa total.
     *
     * É aqui que se apanha a divergência na origem: se a regra guardar 3% + 2%
     * com uma taxa total de 4%, o quadro de comissões passa a vida a
     * reproporcionar parcelas e ninguém percebe porquê.
     *
     * @return string mensagem de erro, ou '' quando está tudo bem
     */
    public function validar_regra($data)
    {
        $taxa      = $this->limpar_numero($data['taxa'] ?? 0);
        $cpcv      = ($data['cpcv_taxa'] ?? '') !== '' ? $this->limpar_numero($data['cpcv_taxa']) : null;
        $escritura = ($data['escritura_taxa'] ?? '') !== '' ? $this->limpar_numero($data['escritura_taxa']) : null;

        // Sem repartição não há nada a conciliar: a comissão é uma parcela só.
        if ($cpcv === null && $escritura === null) {
            return '';
        }

        if ($taxa < 0 || (float) $cpcv < 0 || (float) $escritura < 0) {
            return 'As taxas de comissão não podem ser negativas.';
        }

        /*
         * A repartição é em percentagem DA COMISSÃO, não do valor da venda —
         * "66% no CPCV e 34% na escritura". Portanto tem de somar 100%, e não
         * a taxa total. (Uma versão anterior exigia que somasse a taxa, o que
         * impedia guardar a regra correcta de 66/34 com taxa de 1,5%.)
         */
        $soma = (float) $cpcv + (float) $escritura;

        if ($soma > 0 && abs($soma - 100) > 0.0001) {
            $fmt = function ($n) {
                return rtrim(rtrim(number_format((float) $n, 4, ',', '.'), '0'), ',');
            };

            return 'A repartição da comissão tem de somar 100%: ' . $fmt($cpcv) . '% no CPCV + '
                . $fmt($escritura) . '% na escritura dá ' . $fmt($soma) . '%. '
                . 'Exemplo: 66% no CPCV e 34% na escritura. (Deixe ambos vazios para pagar tudo de uma vez.)';
        }

        return '';
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

        /*
         * Meses previstos por omissão (formato 'YYYY-MM'): é a regra que diz
         * quando cada parcela costuma ser recebida, e as vendas novas herdam-nos.
         * Vazio = recebida na conclusão, sem data futura.
         *
         * Só se escrevem quando vêm no formulário: sem esta guarda, gravar a
         * regra a partir de um ecrã que não tenha os campos apagava meses que
         * a direção já tinha definido.
         */
        foreach (['cpcv_mes_previsto', 'escritura_mes_previsto'] as $campo) {
            if (!array_key_exists($campo, $data)) {
                continue;
            }
            $mes = trim((string) $data[$campo]);
            $payload[$campo] = preg_match('/^\d{4}-\d{2}$/', $mes) ? $mes : null;
        }

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
     * Reescreve nas vendas as taxas que a regra acabou de passar a ditar.
     *
     * Cada venda guarda um retrato das taxas (ver snapshot_taxas) para não
     * depender da regra a cada leitura. O efeito colateral era este: a direção
     * corrigia a regra do Aura e o mapa continuava a mostrar a repartição
     * velha, porque a venda tinha a antiga escrita nas suas colunas. Pedido do
     * dono (05/08/2026): "quando altero a regra tem de alterar em todo lado".
     *
     * O que NÃO se toca, e é de propósito:
     *   - vendas canceladas (histórico morto);
     *   - comissões já marcadas como recebidas;
     *   - vendas com uma parcela já paga (ao comercial ou à direção) — mexer
     *     na taxa depois de pagar reabria uma comissão liquidada.
     * Essas ficam com a taxa que tinham no momento em que se pagou, e são
     * devolvidas na lista de ignoradas para quem guarda a regra saber que
     * existem.
     *
     * @return array ['alteradas' => [...], 'ignoradas' => [...]]
     */
    public function reaplicar_regra_as_vendas($regra)
    {
        $resultado = ['alteradas' => [], 'ignoradas' => []];

        if (empty($regra['empreendimento'])) {
            return $resultado;
        }

        $vendas = $this->db
            ->where('LOWER(TRIM(empreendimento))', strtolower(trim($regra['empreendimento'])))
            ->get($this->tabela_vendas())
            ->result_array();

        foreach ($vendas as $venda) {
            if ($venda['estado'] === 'cancelado') {
                continue;
            }

            $pago = $venda['comissao_estado'] === 'recebida'
                || !empty($venda['cpcv_pago'])
                || !empty($venda['escritura_paga'])
                || !empty($venda['direcao_pago']);

            if ($pago) {
                $resultado['ignoradas'][] = $venda;
                continue;
            }

            // calcular_comissao() lê as taxas da venda; damos-lhe as da regra
            // para saber com que números é que ela vai ficar.
            $futura = $venda;
            $futura['taxa']           = (float) $regra['taxa'];
            $futura['cpcv_taxa']      = $regra['cpcv_taxa'] !== null ? (float) $regra['cpcv_taxa'] : 0;
            $futura['escritura_taxa'] = $regra['escritura_taxa'] !== null ? (float) $regra['escritura_taxa'] : 0;

            $calculo = $this->calcular_comissao($futura);

            $novo = [
                'taxa'           => $calculo['taxa'],
                'cpcv_taxa'      => $calculo['cpcv_taxa'],
                'escritura_taxa' => $calculo['escritura_taxa'],
                'comissao_total' => $calculo['valor'],
            ];

            $mudou = false;
            foreach ($novo as $coluna => $valor_novo) {
                if (abs((float) ($venda[$coluna] ?? 0) - (float) $valor_novo) > 0.0001) {
                    $mudou = true;
                    break;
                }
            }

            if (!$mudou) {
                continue;
            }

            $this->db->where('id', $venda['id']);
            $this->db->update($this->tabela_vendas(), $novo);

            $this->registar_historico(
                $venda['id'],
                null,
                $venda['estado'],
                'Regra de comissão alterada: taxa ' . rtrim(rtrim(number_format((float) $venda['taxa'], 2, ',', '.'), '0'), ',')
                    . '% → ' . rtrim(rtrim(number_format((float) $calculo['taxa'], 2, ',', '.'), '0'), ',') . '%, '
                    . 'comissão ' . number_format((float) $venda['comissao_total'], 2, ',', '.')
                    . ' € → ' . number_format((float) $calculo['valor'], 2, ',', '.') . ' € '
                    . '(CPCV ' . round($calculo['cpcv_taxa']) . '% / escritura ' . round($calculo['escritura_taxa']) . '%)'
            );

            $venda['comissao_nova'] = $calculo['valor'];
            $resultado['alteradas'][] = $venda;
        }

        return $resultado;
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

    /**
     * Estado que a unidade deve ter no simulador para um dado estado de venda.
     *
     * O simulador é a montra: tem de refletir o que está no CRM, senão dois
     * comerciais vendem a mesma fração. A regra pedida pela direção:
     *   pedido / reservado / submetido  -> Reservado
     *   vendido (CPCV)                  -> Vendido
     *   concluido                       -> DPS  (foi a casa que a vendeu)
     *   cancelado                       -> Disponível (volta ao mercado)
     *
     * Devolve null quando o estado não deve mexer no simulador.
     */
    public static function estado_simulador($estado_venda, $empreendimento = '')
    {
        $mapa = [
            'pedido'    => 'Reservado',
            'reservado' => 'Reservado',
            'submetido' => 'Reservado',
            'vendido'   => 'Vendido',     // "vendido" é o CPCV no circuito interno
            /*
             * CONCLUÍDA -> DPS, e não "Vendido".
             *
             * Na montra as duas palavras dizem coisas diferentes: "Vendido" é
             * uma unidade que saiu do mercado, venha de onde vier; "DPS" é uma
             * que saiu porque fomos nós a vendê-la. Uma venda concluída no CRM
             * é sempre nossa — mostrá-la como "Vendido" apagava a única parte
             * da informação que interessa ao promotor. Pedido do dono
             * (04/08/2026).
             */
            'concluido' => 'DPS',
            'cancelado' => 'Disponível',
        ];

        $alvo = $mapa[$estado_venda] ?? null;

        /*
         * BELO HORIZONTE: uma reserva conta como DPS na montra.
         *
         * Ali a DPS não anda a reservar unidades à espera — o que reserva,
         * vende. Mostrá-las como "Reservado" dava a ideia de que ainda podiam
         * voltar ao mercado, e o promotor conta-as como colocadas. Pedido do
         * dono (02/08/2026).
         *
         * Só no Belo Horizonte: nos outros, reservado é mesmo reservado.
         */
        if ($alvo === 'Reservado' && stripos((string) $empreendimento, 'belo') !== false) {
            return 'DPS';
        }

        return $alvo;
    }

    /**
     * Chave interna do empreendimento no simulador, a partir do nome livre
     * gravado na venda ("Boavista Towers", "Gaia Douro", ...).
     */
    public static function chave_empreendimento($empreendimento)
    {
        $nome = mb_strtolower((string) $empreendimento);
        $nome = strtr($nome, ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e',
                              'í'=>'i','ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ç'=>'c']);

        // Ordem importa: "gaia premium" tem de bater antes de "gaia" (douro).
        $pedacos = [
            'boavista'  => 'boavista',
            'premium'   => 'gp',
            'douro'     => 'gaiadouro',
            'gaia'      => 'gaiadouro',
            'horizonte' => 'bh',
            'belo'      => 'bh',
            'raiz'      => 'raizes',
            'fanzeres'  => 'raizes',
            'aura'      => 'aura',
            'lake'      => 'lake',
        ];

        foreach ($pedacos as $pedaco => $chave) {
            if (strpos($nome, $pedaco) !== false) {
                return $chave;
            }
        }

        return null;
    }

    /**
     * Escreve no simulador (dpsimobiliario.pt) o estado que a unidade deve ter.
     *
     * O save_states.php SUBSTITUI o mapa inteiro do empreendimento, por isso
     * lê-se primeiro o mapa actual e altera-se só esta unidade — caso
     * contrário apagavam-se os estados de todas as outras.
     *
     * @return bool true quando a alteração chegou ao simulador
     */
    public function sincronizar_unidade_simulador($empreendimento, $unidade, $estado_venda)
    {
        $unidade = trim((string) $unidade);
        $novo    = self::estado_simulador($estado_venda, $empreendimento);
        $chave   = self::chave_empreendimento($empreendimento);

        if ($unidade === '' || $novo === null || $chave === null) {
            return false;
        }

        $url = 'https://dpsimobiliario.pt/simuladorportugal/save_states.php';

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12]);
        $atual = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        if (!is_array($atual)) {
            return false;
        }

        $mapa = isset($atual[$chave . '_states']) && is_array($atual[$chave . '_states'])
            ? $atual[$chave . '_states']
            : [];

        /*
         * O simulador nem sempre escreve a fracção como o CRM.
         *
         * No Lake Towers a torre leva um "A" à frente — o simulador guarda
         * "A2_CH" onde o CRM tem "2_CH". Escrever a chave do CRM não corrigia
         * nada: acrescentava uma entrada nova ao lado da verdadeira, e a
         * montra continuava a mostrar o estado antigo.
         *
         * Procura-se a chave existente que corresponda, ignorando símbolos e o
         * "A" da torre. Se nenhuma corresponder devolve-se falso — NÃO se
         * inventa uma unidade que o simulador não conhece.
         */
        $alvo = null;

        if (isset($mapa[$unidade])) {
            $alvo = $unidade;
        } else {
            $limpo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $unidade));

            foreach (array_keys($mapa) as $existente) {
                $k = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $existente));

                if ($k === $limpo || $k === 'A' . $limpo) {
                    $alvo = $existente;
                    break;
                }
            }
        }

        if ($alvo === null) {
            return false;
        }

        // Já está como deve ser: não gastar um pedido.
        if (($mapa[$alvo] ?? null) === $novo) {
            return true;
        }

        $mapa[$alvo] = $novo;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode([$chave . '_states' => $mapa], JSON_UNESCAPED_UNICODE),
        ]);
        $resposta = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        return is_array($resposta) && !empty($resposta['ok']);
    }

    /* ---------------------------------------------------------------------
     * Da venda concluída para cliente do CRM
     * ------------------------------------------------------------------ */

    /**
     * Garante que existe um cliente no CRM para esta venda, e devolve o id.
     *
     * Uma venda concluída é um negócio fechado e pago: a pessoa deixou de ser
     * uma lead e passou a ser cliente. Sem esta passagem, quem comprou fica
     * preso no funil comercial e não há forma de lhe falar como cliente de um
     * empreendimento — que é o que o acompanhamento de obra exige.
     *
     * É IDEMPOTENTE, de propósito: pode ser chamada as vezes que forem
     * precisas — pelo circuito da venda, por um botão, ou pelas duas coisas ao
     * mesmo tempo — que nunca cria um cliente a dobrar.
     *
     * A procura por cliente existente é feita primeiro pelo contribuinte e só
     * depois pelo email, porque duas pessoas partilham um email de casa muito
     * mais vezes do que partilham um NIF. Foi o que se viu nos dados: a mesma
     * morada de email em vendas de compradores diferentes.
     *
     * @return int|false id do cliente, ou false se não deu
     */
    public function garantir_cliente($venda_id)
    {
        $venda = $this->get_venda($venda_id);
        if (!$venda) {
            return false;
        }

        // Já ligada? Confirma que o cliente ainda lá está antes de acreditar.
        if (!empty($venda['client_id'])) {
            $existe = $this->db->where('userid', (int) $venda['client_id'])
                               ->count_all_results(db_prefix() . 'clients');
            if ($existe) {
                return (int) $venda['client_id'];
            }
        }

        $nome  = trim((string) $venda['cliente']);
        $nif   = trim((string) ($venda['cliente_nif'] ?? ''));
        $email = trim((string) ($venda['cliente_email'] ?? ''));
        $tel   = trim((string) ($venda['cliente_telefone'] ?? ''));

        if ($nome === '') {
            return false;                       // sem nome não se cria ficha
        }

        /*
         * Memória da passagem em curso.
         *
         * Duas vendas do mesmo comprador processadas no mesmo ciclo: a
         * segunda procura tem de encontrar a ficha que a primeira acabou de
         * criar. Aconteceu não encontrar — o Tiago de Castro ficou com duas
         * fichas, uma por fracção, enquanto a Particula Veloz (caso idêntico)
         * ficou com uma. Seja qual for a razão de a leitura não ter visto a
         * escrita, deixa de importar: o que se cria fica aqui, e a mesma
         * chave devolve sempre o mesmo cliente dentro da mesma passagem.
         */
        static $criados_agora = [];

        $chave = $nif !== ''
            ? 'nif:' . $nif
            : 'nome:' . mb_strtolower(preg_replace('/\s+/u', ' ', trim($nome)), 'UTF-8')
              . '|' . mb_strtolower($email, 'UTF-8');

        if (isset($criados_agora[$chave])) {
            $client_id = $criados_agora[$chave];
        } else {
            $client_id = $this->procurar_cliente($nif, $email, $nome);
        }

        if (!$client_id) {
            $this->load->model('clients_model');

            /*
             * Sendo empresa, o nome do comprador É a denominação social e o
             * contacto é quem assina por ela. Sendo particular, a empresa fica
             * com o nome da pessoa — é como o Perfex guarda clientes
             * singulares.
             */
            $e_empresa = strcasecmp(trim((string) ($venda['cliente_tipo'] ?? '')), 'empresa') === 0;
            $contacto  = $e_empresa && !empty($venda['cliente_representante'])
                ? trim((string) $venda['cliente_representante'])
                : $nome;

            $partes    = preg_split('/\s+/u', $contacto, 2);
            $primeiro  = $partes[0] ?? $contacto;
            $ultimo    = $partes[1] ?? '';

            $client_id = $this->clients_model->add([
                'company'     => $nome,
                'vat'         => $nif,
                'phonenumber' => $tel,
                'address'     => trim((string) ($venda['cliente_morada'] ?? '')),
                'zip'         => trim((string) ($venda['cliente_codigo_postal'] ?? '')),
                'city'        => trim((string) ($venda['cliente_concelho'] ?? '')),
                'active'      => 1,
                // Contacto principal. Sem email não se envia convite nenhum,
                // e não se cria palavra-passe: estes clientes não usam o
                // portal, só recebem o acompanhamento da obra.
                'firstname'   => $primeiro,
                'lastname'    => $ultimo,
                'email'       => $email,
                'is_primary'  => 1,
                'donotsendwelcomeemail' => true,
            ], true);

            if (!$client_id) {
                return false;
            }

            $criados_agora[$chave] = (int) $client_id;

            log_activity('Venda #' . (int) $venda_id . ' — criado o cliente #' . $client_id
                . ' (' . $nome . ') a partir do mapa de vendas');
        }

        $this->db->where('id', (int) $venda_id)
                 ->update($this->tabela_vendas(), ['client_id' => (int) $client_id]);

        return (int) $client_id;
    }

    /**
     * Procura um cliente já existente. Contribuinte primeiro, email depois,
     * nome em último — e o nome só quando é exacto, para não juntar dois
     * "Silva" diferentes na mesma ficha.
     */
    private function procurar_cliente($nif, $email, $nome)
    {
        if ($nif !== '') {
            $r = $this->db->select('userid')->where('vat', $nif)
                          ->get(db_prefix() . 'clients')->row();
            if ($r) {
                return (int) $r->userid;
            }
        }

        /*
         * Pelo email SÓ SE O NOME TAMBÉM BATER.
         *
         * Um email não identifica ninguém: nos dados reais desta casa há um
         * "Luís Filipe Magno Correia da Silva" e uma "DELZAGE - GESTÃO E
         * ADMINISTRAÇÃO" a partilhar o lcs@parkhotel.pt — é o gestor a usar o
         * seu email para a empresa dele. Casar só pelo email fundia os dois
         * na mesma ficha, e separá-los depois é trabalho à mão.
         *
         * Com o nome a confirmar, o email deixa de ser suficiente e passa a
         * ser corroboração.
         */
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $candidatos = $this->db->select('c.userid, c.company')
                                   ->from(db_prefix() . 'contacts ct')
                                   ->join(db_prefix() . 'clients c', 'c.userid = ct.userid')
                                   ->where('ct.email', $email)
                                   ->get()->result_array();

            $limpo = function ($t) {
                $t = mb_strtolower(trim((string) $t), 'UTF-8');
                $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t) ?: $t;

                return preg_replace('/[^a-z0-9]+/', ' ', $t);
            };

            foreach ($candidatos as $cand) {
                if ($limpo($cand['company']) === $limpo($nome)) {
                    return (int) $cand['userid'];
                }
            }
        }

        $r = $this->db->select('userid')->where('company', $nome)
                      ->get(db_prefix() . 'clients')->row();

        return $r ? (int) $r->userid : 0;
    }

    /**
     * Passa a cliente todas as vendas concluídas que ainda não o são.
     *
     * Serve para recuperar o que ficou para trás: as vendas fechadas antes de
     * esta passagem existir. Depois disto, o circuito trata de cada uma no
     * momento em que é concluída.
     *
     * @return array ['criados' => int, 'ja_existiam' => int, 'falhados' => array]
     */
    public function sincronizar_clientes()
    {
        $vendas = $this->db->select('id, cliente, client_id')
                           ->where('estado', 'concluido')
                           ->get($this->tabela_vendas())->result_array();

        $r = ['criados' => 0, 'ja_existiam' => 0, 'falhados' => []];

        foreach ($vendas as $v) {
            $ja = !empty($v['client_id']);
            $id = $this->garantir_cliente((int) $v['id']);

            if (!$id) {
                $r['falhados'][] = '#' . $v['id'] . ' ' . $v['cliente'];
            } elseif ($ja) {
                $r['ja_existiam']++;
            } else {
                $r['criados']++;
            }
        }

        return $r;
    }

    /**
     * Clientes de um empreendimento, com o email do contacto principal.
     *
     * A ligação é feita pelas vendas e não por um campo copiado para a ficha
     * do cliente: assim quem compra no Gaia Douro E no Boavista aparece nos
     * dois envios, em vez de o segundo apagar o primeiro.
     *
     * @param string $empreendimento vazio = todos
     */
    public function clientes_por_empreendimento($empreendimento = '')
    {
        $this->db->select('c.userid, c.company, c.vat, c.phonenumber,
                           ct.email, ct.firstname, ct.lastname,
                           GROUP_CONCAT(DISTINCT v.empreendimento ORDER BY v.empreendimento SEPARATOR ", ") AS empreendimentos,
                           GROUP_CONCAT(DISTINCT v.unidade ORDER BY v.unidade SEPARATOR ", ") AS unidades', false);
        $this->db->from($this->tabela_vendas() . ' v');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = v.client_id');
        $this->db->join(db_prefix() . 'contacts ct', 'ct.userid = c.userid AND ct.is_primary = 1', 'left');
        $this->db->where('v.estado', 'concluido');
        $this->db->where('v.client_id IS NOT NULL');

        if ($empreendimento !== '') {
            $this->db->where('v.empreendimento', $empreendimento);
        }

        $this->db->group_by('c.userid');
        $this->db->order_by('c.company', 'ASC');

        return $this->db->get()->result_array();
    }

}
