<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dps_interacoes extends AdminController
{
    // Objectivos base
    const OBJ_SEMANA = 200;  // 200 interacções por semana (7 dias)
    const OBJ_MES    = 800;  // 800 interacções por mês (30 dias)

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $p = db_prefix();

        // Parâmetros GET
        /*
         * Por omissão, últimos 15 dias. Uma semana é pouco para se ver um
         * padrão de trabalho — um par de dias maus ou um feriado chegavam
         * para distorcer a leitura. Pedido do dono (13/08/2026).
         */
        $periodo   = $this->input->get('periodo') ?: 'last_15';
        $status_id = (int)$this->input->get('status_id');

        /*
         * Filtro por comercial. Sem nada escolhido mostram-se todos — que é o
         * que a direcção quer ver quando abre a página. Escolhendo um, a
         * página passa a ser o relatório dessa pessoa e o gráfico deixa de
         * comparar colegas para mostrar o dia-a-dia dela.
         */
        $comercial_id = (int) $this->input->get('comercial');

        // Calcular datas do período e objectivo proporcional
        switch ($periodo) {
            case 'today':
                $date_from = date('Y-m-d 00:00:00');
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Hoje';
                // Proporcional: 200 / 7 dias
                $objectivo = round(self::OBJ_SEMANA / 7, 1);
                break;
            case 'today_yesterday':
                $date_from = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Hoje e Ontem';
                // Proporcional: 200 / 7 * 2 dias
                $objectivo = round(self::OBJ_SEMANA / 7 * 2, 1);
                break;
            case 'last_15':
                $date_from = date('Y-m-d 00:00:00', strtotime('-14 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 15 dias';
                // Proporcional: 800 / 30 * 15 dias
                $objectivo = round(self::OBJ_MES / 30 * 15, 1);
                break;
            case 'last_30':
                $date_from = date('Y-m-d 00:00:00', strtotime('-29 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 30 dias';
                $objectivo = self::OBJ_MES;
                break;
            case 'last_3m':
                $date_from = date('Y-m-d 00:00:00', strtotime('-3 months'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 3 meses';
                $objectivo = self::OBJ_MES * 3;
                break;
            default: // last_7
                $periodo   = 'last_7';
                $date_from = date('Y-m-d 00:00:00', strtotime('-6 days'));
                $date_to   = date('Y-m-d 23:59:59');
                $label     = 'Últimos 7 dias';
                $objectivo = self::OBJ_SEMANA;
        }

        // Filtro de status
        $status_clause_leads = '';
        if ($status_id > 0) {
            $status_clause_leads = 'AND l.status = ' . (int)$status_id;
        }

        // Obter todos os status de leads
        $statuses = $this->db->get($p . 'leads_status')->result_array();

        // PASSO 1: Obter todos os staff activos
        $filtro_staff = $comercial_id > 0 ? ' AND staffid = ' . $comercial_id : '';
        $staff_result = $this->db->query("SELECT staffid, CONCAT(firstname, ' ', lastname) AS nome FROM {$p}staff WHERE active = 1{$filtro_staff} ORDER BY firstname ASC");
        $staff_list   = $staff_result ? $staff_result->result_array() : [];

        // PASSO 2: Para cada staff, contar interacções no período
        $comerciais = [];
        foreach ($staff_list as $s) {
            $sid = (int)$s['staffid'];

            // Contar interacções: notas manuais na tabela lead_activity_log
            /*
             * O QUE CONTA COMO INTERACÇÃO (regra do dono, 03/08/2026):
             *
             *   um CLIENTE TOCADO num DIA. Seja por uma nota escrita na lead,
             *   seja por uma proposta enviada. Duas notas ao mesmo cliente no
             *   mesmo dia contam uma; duas propostas também; uma nota mais uma
             *   proposta, também uma.
             *
             * Mede-se o alcance — a quantas pessoas se chegou — e não quantas
             * vezes se mexeu no teclado. É por isso que é DISTINCT lead + dia.
             *
             * Duas armadilhas do registo, medidas na Cátia (135 -> 43):
             *
             *  1. al.staffid, e não l.assigned: contava-se por DONO da lead, e
             *     as 46 notas que o Ricardo escreveu nas leads dela entravam
             *     no número dela. O trabalho é de quem o faz.
             *  2. cada nota fica gravada DUAS vezes ("Nota: X" e "Nota gravada
             *     por Fulano: X", no mesmo segundo). Sem excluir a segunda,
             *     tudo vinha a dobrar.
             */
            $filtro_interaccao = "(al.description LIKE '? Nota%'
                                   OR al.description LIKE '%Proposta enviada%')
                                  AND al.description NOT LIKE '%Nota gravada por%'";

            /*
             * TERCEIRA ARMADILHA: a mesma frase repetida em dezenas de leads.
             *
             * A 13/08/2026 a Cátia aparecia com 96 interacções num dia. Eram
             * reais no sentido de existirem — 102 notas em 96 leads entre as
             * 7h e as 10h — mas 95 delas eram duas frases copiadas:
             * "Encaminhei SMS e Email" (50 vezes) e "Enviei email e SMS" (45).
             * Percorrer uma lista a colar a mesma frase não é falar com 96
             * clientes, e o quadro dizia que era.
             *
             * Passa a contar-se DIA + TEXTO em vez de DIA + LEAD: a mesma
             * frase no mesmo dia conta uma vez, escreva-se em duas leads ou em
             * cinquenta. Quem escreve uma nota diferente por cliente continua
             * a contar uma por cliente — que é exactamente a diferença que
             * este quadro deve mostrar. Decisão do dono (13/08/2026).
             *
             * O texto é normalizado antes de comparar: tira-se o prefixo
             * "? Nota:" que o registo acrescenta, e ignoram-se maiúsculas e
             * espaços a mais. Sem isso, "Enviei email" e "enviei  email"
             * contariam como duas coisas.
             */
            $texto_normalizado = "LOWER(TRIM(REPLACE(REPLACE(REPLACE("
                . "SUBSTRING_INDEX(al.description, ': ', -1), '\r', ' '), '\n', ' '), '  ', ' ')))";

            $count_sql = "
                SELECT COUNT(*) AS total FROM (
                    SELECT DISTINCT DATE(al.date) AS dia, {$texto_normalizado} AS texto
                      FROM {$p}lead_activity_log al
                INNER JOIN {$p}leads l ON l.id = al.leadid
                     WHERE al.staffid = {$sid}
                       {$status_clause_leads}
                       AND {$filtro_interaccao}
                       AND al.date >= '{$date_from}'
                       AND al.date <= '{$date_to}'
                ) AS toques
            ";
            $count_res = $this->db->query($count_sql);
            $total     = 0;
            if ($count_res) {
                $row   = $count_res->row_array();
                $total = (int)($row['total'] ?? 0);
            }

            // Calcular percentagem do objectivo
            $pct = ($objectivo > 0) ? round(($total / $objectivo) * 100, 1) : 0;

            // Obter leads com interacção (apenas se total > 0)
            $leads = [];
            if ($total > 0) {
                $leads_sql = "
                    SELECT
                        l.id,
                        l.name,
                        l.email,
                        l.phonenumber,
                        ls.name AS status_name,
                        COUNT(DISTINCT DATE(al.date)) AS num_interacoes
                    FROM {$p}leads l
                    LEFT JOIN {$p}leads_status ls ON ls.id = l.status
                    INNER JOIN {$p}lead_activity_log al ON al.leadid = l.id
                        AND al.staffid = {$sid}
                        -- Le as MESMAS linhas que o total, senao o detalhe
                        -- nao soma o cabecalho: ha notas de proposta que nao
                        -- comecam por Nota e ficavam de fora daqui mas
                        -- contadas la em cima (11 de diferenca na Catia).
                        AND (al.description LIKE '? Nota%'
                             OR al.description LIKE '%Proposta enviada%')
                        AND al.description NOT LIKE '%Nota gravada por%'
                        AND al.date >= '{$date_from}'
                        AND al.date <= '{$date_to}'
                    WHERE 1 = 1
                    {$status_clause_leads}
                    GROUP BY l.id, l.name, l.email, l.phonenumber, ls.name
                    ORDER BY num_interacoes DESC
                ";
                $leads_res = $this->db->query($leads_sql);
                $leads     = $leads_res ? $leads_res->result_array() : [];
            }

            $comerciais[] = array(
                'staff_id'         => $sid,
                'nome'             => $s['nome'],
                'total_interacoes' => $total,
                'objectivo'        => $objectivo,
                'pct'              => $pct,
                'leads'            => $leads,
            );
        }

        // Ordenar por total de interacções (desc)
        usort($comerciais, function($a, $b) {
            return $b['total_interacoes'] - $a['total_interacoes'];
        });

        $data['title']      = 'Interacções por Comercial';
        $data['comerciais'] = $comerciais;
        $data['statuses']   = $statuses;
        $data['periodo']    = $periodo;
        $data['status_id']  = $status_id;
        $data['label']      = $label;
        $data['date_from']  = $date_from;
        $data['date_to']    = $date_to;
        $data['objectivo']  = $objectivo;

        /* ------------------------------------------------------------------
         * O GRÁFICO
         *
         * Muda de assunto conforme o filtro, porque a pergunta também muda:
         *   - sem comercial escolhido -> quem fez quantas (comparação)
         *   - com um comercial        -> como se portou dia a dia (evolução)
         * ---------------------------------------------------------------- */
        $g_etiquetas = [];
        $g_valores   = [];

        if ($comercial_id > 0) {
            $por_dia = [];
            /*
             * Mesma regra do total, dia a dia: as notas contam uma vez por
             * cliente (COUNT DISTINCT leadid), as propostas contam todas.
             * As duas metades somam-se por dia.
             */
            $q = $this->db->query("
                SELECT DATE(al.date) AS dia, COUNT(DISTINCT al.leadid) AS n
                  FROM {$p}lead_activity_log al
            INNER JOIN {$p}leads l ON l.id = al.leadid
                 WHERE al.staffid = {$comercial_id}
                   AND (al.description LIKE '? Nota%'
                        OR al.description LIKE '%Proposta enviada%')
                   AND al.description NOT LIKE '%Nota gravada por%'
                   AND al.date >= '{$date_from}' AND al.date <= '{$date_to}'
              GROUP BY DATE(al.date)
            ");
            foreach (($q ? $q->result_array() : []) as $linha) {
                $por_dia[$linha['dia']] = (int) $linha['n'];
            }

            // Os dias sem nenhuma interacção têm de aparecer a zero: um gráfico
            // que salta de terça para sexta esconde precisamente os dias maus.
            $dia = strtotime(substr($date_from, 0, 10));
            $fim = strtotime(substr($date_to, 0, 10));
            while ($dia <= $fim) {
                $chave         = date('Y-m-d', $dia);
                $g_etiquetas[] = date('d/m', $dia);
                $g_valores[]   = $por_dia[$chave] ?? 0;
                $dia           = strtotime('+1 day', $dia);
            }
        } else {
            $ordenados = $comerciais;
            usort($ordenados, fn ($a, $b) => $b['total_interacoes'] <=> $a['total_interacoes']);
            foreach ($ordenados as $linha) {
                $g_etiquetas[] = $linha['nome'];
                $g_valores[]   = (int) $linha['total_interacoes'];
            }
        }

        // Toda a equipa, para o seletor — independente do filtro aplicado.
        $todos = $this->db->query("SELECT staffid, CONCAT(firstname,' ',lastname) AS nome
                                     FROM {$p}staff WHERE active = 1 ORDER BY firstname ASC");
        $data['equipa']       = $todos ? $todos->result_array() : [];
        $data['comercial_id'] = $comercial_id;
        $data['g_etiquetas']  = $g_etiquetas;
        $data['g_valores']    = $g_valores;
        $data['objectivo']    = $objectivo;


        $this->load->view('interacoes', $data);
    }
}
