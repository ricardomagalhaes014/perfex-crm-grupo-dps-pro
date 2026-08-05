<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Queries de leads e do registo de envios.
 *
 * Regra de ouro: o filtro assigned=staffid é a ÚNICA fronteira entre
 * comerciais — quem chama passa $staff_id=null apenas quando o servidor já
 * confirmou is_admin().
 */
class Dps_automacao_model extends App_Model
{
    /**
     * Expressão SQL da "última interação" de uma lead: lastcontact, com
     * fallback ao MAX(date) do activity log. NÃO cai para dateadded — sem
     * interação registada, a lead fica fora dos follow-ups por decisão
     * explícita (enviar "sentimos a sua falta" sem histórico é pior).
     */
    private function sql_ultima_interacao()
    {
        $log = db_prefix() . 'lead_activity_log';

        return 'COALESCE(l.lastcontact, (SELECT MAX(al.date) FROM `' . $log . '` al WHERE al.leadid = l.id))';
    }

    /* -----------------------------------------------------------------
     * Listas de apoio à UI
     * -------------------------------------------------------------- */

    public function get_estados_lead()
    {
        return $this->db->order_by('statusorder', 'ASC')
            ->get(db_prefix() . 'leads_status')
            ->result_array();
    }

    public function get_comerciais()
    {
        return $this->db->select('staffid, firstname, lastname')
            ->where('active', 1)
            ->order_by('firstname', 'ASC')
            ->get(db_prefix() . 'staff')
            ->result_array();
    }

    /* -----------------------------------------------------------------
     * Envio em massa
     * -------------------------------------------------------------- */

    /**
     * Leads alvo do envio em massa, paginadas por cursor (l.id > $last_id)
     * para os lotes AJAX sequenciais não repetirem nem saltarem leads.
     *
     * O contacto exigido pelo canal é filtrado já no SQL: uma lead sem o
     * contacto certo aparece no preview como excluída e nunca rebenta o lote.
     */
    public function get_leads_por_estados($estado_ids, $staff_id, $canal, $last_id = 0, $limite = 50)
    {
        $estado_ids = array_values(array_filter(array_map('intval', (array) $estado_ids)));
        if (empty($estado_ids)) {
            return [];
        }

        $this->db->select('l.id, l.name, l.email, l.phonenumber, l.assigned, l.status, CONCAT(s.firstname, " ", s.lastname) AS comercial')
            ->from(db_prefix() . 'leads l')
            ->join(db_prefix() . 'staff s', 's.staffid = l.assigned', 'left')
            ->where_in('l.status', $estado_ids)
            ->where('l.lost', 0)
            ->where('l.junk', 0)
            // Converter uma lead em cliente preenche date_converted mas mantém
            // a linha com o status antigo — sem este filtro, clientes já
            // ganhos receberiam campanhas de prospeção (mesma regra dos
            // follow-ups em leads_para_followup).
            ->where('l.date_converted IS NULL', null, false)
            ->where('l.id >', (int) $last_id);

        if ($staff_id !== null) {
            $this->db->where('l.assigned', (int) $staff_id);
        }

        if ($canal === 'email') {
            $this->db->where('l.email IS NOT NULL', null, false)
                ->where('l.email !=', '');
        } else {
            // whatsapp e sms precisam de telefone
            $this->db->where('l.phonenumber IS NOT NULL', null, false)
                ->where('l.phonenumber !=', '');
        }

        return $this->db->order_by('l.id', 'ASC')
            ->limit((int) $limite)
            ->get()
            ->result_array();
    }

    /**
     * Contagens para o preview: por estado, o total de leads alvo e quantas
     * têm o contacto exigido pelo canal. Nada é enviado aqui.
     */
    /**
     * Empreendimentos a que já se enviou proposta ou informação.
     *
     * O empreendimento NÃO é um atributo da lead — é o do documento que lhe
     * foi enviado, e isso vive em dps_propostas (lead_id + empreendimento).
     * Uma primeira versão deste filtro usou as etiquetas da lead e estava
     * errada: as etiquetas dizem de que campanha a lead veio, não o que já
     * lhe mandámos. Corrigido a 05/08/2026, por indicação do dono.
     *
     * Conta PROPOSTAS — quantos documentos saíram, não a quantas pessoas.
     * É a pergunta que se faz antes de um envio: quanto já se mandou daquele
     * empreendimento. Regra do dono (05/08/2026).
     *
     * Devolve a matriz inteira, por COMERCIAL e por empreendimento, para o
     * ecrã poder actualizar as contagens quando se muda de comercial sem ir
     * outra vez ao servidor. staff_id 0 é a linha do TOTAL.
     */
    public function get_empreendimentos_propostas()
    {
        /*
         * DOIS números, não um: propostas e leads.
         *
         * Uma proposta não é uma pessoa — a Cátia enviou 114 propostas de
         * Boavista Towers a 56 leads. Mostrando só as propostas, o ecrã
         * prometia 114 e o envio chegava a 56, e isso lê-se como uma
         * limitação do sistema quando é só a diferença entre documentos e
         * pessoas. Mostram-se os dois. Corrigido a 05/08/2026.
         *
         * As leads contam-se com DISTINCT por comercial e outra vez no total:
         * somar os distintos de cada comercial daria a mesma pessoa duas
         * vezes se dois comerciais lhe tivessem enviado.
         */
        $linhas = $this->db->query(
            'SELECT p.empreendimento AS nome, p.staff_id,
                    COUNT(*) AS propostas, COUNT(DISTINCT p.lead_id) AS leads
               FROM ' . db_prefix() . 'dps_propostas p
              WHERE p.empreendimento IS NOT NULL AND p.empreendimento <> ""
                AND p.lead_id > 0
           GROUP BY p.empreendimento, p.staff_id'
        )->result_array();

        $por_comercial = [];

        foreach ($linhas as $l) {
            $por_comercial[(int) $l['staff_id']][(string) $l['nome']] = [
                'propostas' => (int) $l['propostas'],
                'leads'     => (int) $l['leads'],
            ];
        }

        $totais = [];

        foreach ($this->db->query(
            'SELECT p.empreendimento AS nome,
                    COUNT(*) AS propostas, COUNT(DISTINCT p.lead_id) AS leads
               FROM ' . db_prefix() . 'dps_propostas p
              WHERE p.empreendimento IS NOT NULL AND p.empreendimento <> ""
                AND p.lead_id > 0
           GROUP BY p.empreendimento
           ORDER BY propostas DESC'
        )->result_array() as $l) {
            $totais[(string) $l['nome']] = [
                'propostas' => (int) $l['propostas'],
                'leads'     => (int) $l['leads'],
            ];
        }

        return ['totais' => $totais, 'por_comercial' => $por_comercial];
    }

    /**
     * Condição SQL que restringe às leads que já receberam alguma coisa deste
     * empreendimento. Vazia quando não há filtro.
     *
     * Escrita uma vez e usada nos três sítios — contagem, teste e envio — para
     * não haver hipótese de a contagem prometer um número e o envio fazer
     * outro.
     */
    private function sql_empreendimento($nome, $alias = 'l')
    {
        $nome = trim((string) $nome);

        if ($nome === '') {
            return '';
        }

        return ' AND EXISTS (SELECT 1 FROM ' . db_prefix() . 'dps_propostas p'
             . ' WHERE p.lead_id = ' . $alias . '.id'
             . ' AND p.empreendimento = ' . $this->db->escape($nome) . ')';
    }

    public function contar_leads($estado_ids, $staff_id, $canal, $empreendimento = '')
    {
        $estado_ids = array_values(array_filter(array_map('intval', (array) $estado_ids)));

        /*
         * Sem estados E sem empreendimento não se conta nada — devolver tudo
         * era pôr toda a base de dados no alvo de um engano. Com
         * empreendimento, o alvo já está definido e os estados são opcionais.
         */
        if (empty($estado_ids) && trim((string) $empreendimento) === '') {
            return [];
        }

        $campo_contacto = ($canal === 'email') ? 'l.email' : 'l.phonenumber';

        $sql = 'SELECT l.status AS estado_id, st.name AS estado_nome,
                COUNT(*) AS total,
                SUM(CASE WHEN ' . $campo_contacto . ' IS NOT NULL AND ' . $campo_contacto . " != '' THEN 1 ELSE 0 END) AS com_contacto
            FROM `" . db_prefix() . 'leads` l
            LEFT JOIN `' . db_prefix() . 'leads_status` st ON st.id = l.status
            WHERE l.lost = 0 AND l.junk = 0
              AND l.date_converted IS NULL'
            . (!empty($estado_ids) ? ' AND l.status IN (' . implode(',', $estado_ids) . ')' : '')
            . $this->sql_empreendimento($empreendimento);

        $binds = [];
        if ($staff_id !== null) {
            $sql    .= ' AND l.assigned = ?';
            $binds[] = (int) $staff_id;
        }

        $sql .= ' GROUP BY l.status, st.name ORDER BY st.name';

        return $this->db->query($sql, $binds)->result_array();
    }

    /* -----------------------------------------------------------------
     * Propostas em massa (PDFs carregados)
     * -------------------------------------------------------------- */

    public function registar_proposta(array $dados)
    {
        $dados['dateadded'] = date('Y-m-d H:i:s');

        $this->db->insert(db_prefix() . 'dps_automacao_propostas', $dados);

        return $this->db->insert_id();
    }

    /**
     * Propostas carregadas. $staff_id limita ao próprio (comercial);
     * null = admin vê todas — a mesma convenção de get_envios.
     */
    public function get_propostas($staff_id = null)
    {
        $this->db->select('p.*, CONCAT(s.firstname, " ", s.lastname) AS staff_nome')
            ->from(db_prefix() . 'dps_automacao_propostas p')
            ->join(db_prefix() . 'staff s', 's.staffid = p.staff_id', 'left');

        if ($staff_id !== null) {
            $this->db->where('p.staff_id', (int) $staff_id);
        }

        return $this->db->order_by('p.id', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_proposta($id)
    {
        return $this->db->where('id', (int) $id)
            ->get(db_prefix() . 'dps_automacao_propostas')
            ->row_array();
    }

    public function apagar_proposta($id)
    {
        $this->db->where('id', (int) $id)->delete(db_prefix() . 'dps_automacao_propostas');
    }

    /**
     * Leads alvo da proposta em massa — a mesma query de get_leads_por_estados
     * MAIS a guarda de deduplicação: um LEFT JOIN ao registo de envios com
     * tipo='proposta_massa' e o id DESTA proposta no detalhe (convenção
     * "proposta:<id> ..."). Quem já tem registo (mesmo falhado — o registo é
     * inserido ANTES do envio, tal como nos follow-ups) fica fora, por isso
     * repetir a campanha nunca duplica a mesma proposta na mesma lead.
     */
    /**
     * @param bool $repetir inclui quem JÁ recebeu esta proposta.
     *        Pedido do Ricardo (29/07/2026): a mesma proposta pode ser
     *        reenviada porque o conteúdo relevante pode ter mudado (preços,
     *        unidades disponíveis). Desligado por omissão — reenviar sem
     *        querer é irritante para o cliente e queima a marca.
     */
    public function get_leads_para_proposta($estado_ids, $staff_id, $canal, $proposta_id, $last_id = 0, $limite = 50, $repetir = false, $empreendimento = '')
    {
        $estado_ids = array_values(array_filter(array_map('intval', (array) $estado_ids)));

        // Mesma guarda da contagem: um dos dois tem de estar preenchido.
        if (empty($estado_ids) && trim((string) $empreendimento) === '') {
            return [];
        }

        $this->db->select('l.id, l.name, l.email, l.phonenumber, l.assigned, l.status, CONCAT(s.firstname, " ", s.lastname) AS comercial')
            ->from(db_prefix() . 'leads l')
            ->join(db_prefix() . 'staff s', 's.staffid = l.assigned', 'left');

        if (!$repetir) {
            // Guarda de deduplicação (o id é (int) — seguro dentro do LIKE).
            $this->db->join(
                db_prefix() . 'dps_automacao_envios e',
                // e.ok = 1: só um envio BEM SUCEDIDO bloqueia a lead. Sem isto, uma
                // falha temporária (SMTP em baixo, WhatsApp desligado) marcava a
                // lead para sempre e ela nunca receberia a proposta — foi o que
                // aconteceu a 121 leads num envio em que o servidor de correio
                // cortou a meio.
                "e.lead_id = l.id AND e.tipo = 'proposta_massa' AND e.ok = 1 AND e.detalhe LIKE 'proposta:" . (int) $proposta_id . " %'",
                'left',
                false
            )->where('e.id IS NULL', null, false);
        }

        /*
         * Nota sobre repetir: a paginação por l.id > last_id continua a
         * impedir que a MESMA lead apareça duas vezes dentro do mesmo envio.
         * O que se dispensa aqui é só a memória de envios anteriores.
         */
        if (!empty($estado_ids)) {
            $this->db->where_in('l.status', $estado_ids);
        }

        $this->db->where('l.lost', 0)
            ->where('l.junk', 0)
            // Mesma regra do envio em massa: clientes já convertidos ficam fora.
            ->where('l.date_converted IS NULL', null, false)
            ->where('l.id >', (int) $last_id);

        // Filtro por empreendimento — o do documento já enviado à lead.
        $cond_emp = $this->sql_empreendimento($empreendimento);

        if ($cond_emp !== '') {
            $this->db->where(ltrim($cond_emp, ' AND '), null, false);
        }

        if ($staff_id !== null) {
            $this->db->where('l.assigned', (int) $staff_id);
        }

        if ($canal === 'email') {
            $this->db->where('l.email IS NOT NULL', null, false)
                ->where('l.email !=', '');
        } else {
            // whatsapp precisa de telefone
            $this->db->where('l.phonenumber IS NOT NULL', null, false)
                ->where('l.phonenumber !=', '');
        }

        return $this->db->order_by('l.id', 'ASC')
            ->limit((int) $limite)
            ->get()
            ->result_array();
    }

    /* -----------------------------------------------------------------
     * Registo de envios
     * -------------------------------------------------------------- */

    /**
     * Insere uma tentativa de envio e devolve o id do registo (permite
     * inserir ANTES de enviar e atualizar ok/detalhe depois — a guarda de
     * idempotência dos follow-ups depende desta ordem).
     */
    public function registar_envio(array $dados)
    {
        $dados['dateadded'] = date('Y-m-d H:i:s');

        $this->db->insert(db_prefix() . 'dps_automacao_envios', $dados);

        return $this->db->insert_id();
    }

    public function atualizar_envio($id, $ok, $detalhe)
    {
        $this->db->where('id', (int) $id)->update(db_prefix() . 'dps_automacao_envios', [
            'ok'      => $ok ? 1 : 0,
            'detalhe' => substr((string) $detalhe, 0, 255),
        ]);
    }

    /**
     * Apaga um registo-guarda quando o envio nem sequer foi tentado (falha
     * de pré-condição): a guarda de deduplicação só deve queimar leads com
     * tentativa real — apagar permite o retry depois de corrigida a causa.
     */
    public function apagar_envio($id)
    {
        $this->db->where('id', (int) $id)->delete(db_prefix() . 'dps_automacao_envios');
    }

    /**
     * Registo de envios para a página de consulta. $so_do_staff limita ao
     * próprio (comercial); null = admin vê tudo, com filtros opcionais.
     */
    public function get_envios($filtros = [], $so_do_staff = null)
    {
        $this->db->select('e.*, l.name AS lead_nome, CONCAT(s.firstname, " ", s.lastname) AS staff_nome')
            ->from(db_prefix() . 'dps_automacao_envios e')
            ->join(db_prefix() . 'leads l', 'l.id = e.lead_id', 'left')
            ->join(db_prefix() . 'staff s', 's.staffid = e.staff_id', 'left');

        if ($so_do_staff !== null) {
            $this->db->where('e.staff_id', (int) $so_do_staff);
        } elseif (!empty($filtros['staff_id'])) {
            $this->db->where('e.staff_id', (int) $filtros['staff_id']);
        }

        if (!empty($filtros['canal'])) {
            $this->db->where('e.canal', $filtros['canal']);
        }

        return $this->db->order_by('e.id', 'DESC')
            ->limit(300)
            ->get()
            ->result_array();
    }

    /* -----------------------------------------------------------------
     * Follow-ups
     * -------------------------------------------------------------- */

    public function ja_enviado_followup($lead_id, $marco)
    {
        return $this->db->where('tipo', 'followup')
            ->where('marco', (int) $marco)
            ->where('lead_id', (int) $lead_id)
            ->count_all_results(db_prefix() . 'dps_automacao_envios') > 0;
    }

    /**
     * Leads elegíveis para o follow-up de um marco: com email, não lost/junk,
     * não convertidas em cliente, sem registo prévio deste marco (LEFT JOIN
     * de guarda) e cuja última interação cai na janela [marco, seguinte[.
     *
     * Leads sem QUALQUER interação registada ficam de fora (ver
     * sql_ultima_interacao) — sem histórico, não há "sentimos a sua falta".
     */
    public function leads_para_followup($marco, $limite = 50, $marco_seguinte = null)
    {
        $ultima = $this->sql_ultima_interacao();

        $sql = 'SELECT l.id, l.name, l.email, l.assigned, l.status,
                CONCAT(s.firstname, " ", s.lastname) AS comercial,
                ' . $ultima . ' AS ultima_interacao
            FROM `' . db_prefix() . 'leads` l
            LEFT JOIN `' . db_prefix() . 'staff` s ON s.staffid = l.assigned
            LEFT JOIN `' . db_prefix() . "dps_automacao_envios` e
                ON e.lead_id = l.id AND e.tipo = 'followup' AND e.marco = ?
            WHERE e.id IS NULL
              AND l.email IS NOT NULL AND l.email != ''
              AND l.lost = 0 AND l.junk = 0
              AND l.date_converted IS NULL
            HAVING ultima_interacao IS NOT NULL
              AND DATEDIFF(NOW(), ultima_interacao) >= ?";

        $binds = [(int) $marco, (int) $marco];

        if ($marco_seguinte !== null) {
            $sql    .= ' AND DATEDIFF(NOW(), ultima_interacao) < ?';
            $binds[] = (int) $marco_seguinte;
        }

        $sql .= ' ORDER BY l.id ASC LIMIT ' . (int) $limite;

        return $this->db->query($sql, $binds)->result_array();
    }

    /* =====================================================================
     * ENVIO EM MASSA POR ESTADO DE TAREFA
     *
     * O irmão do envio por estado de LEAD. A diferença está em como se chega
     * ao destinatário: uma lead tem email próprio, uma tarefa não — chega-se
     * ao email pela coisa a que a tarefa está ligada (a lead ou o cliente).
     * Tarefas soltas, sem ligação, não têm a quem escrever e ficam de fora.
     * ================================================================== */

    /**
     * SQL comum às duas consultas (contar e listar), para não haver duas
     * definições de "quem entra" a divergir com o tempo.
     */
    private function tarefas_base($estado_ids, $staff_id)
    {
        $estado_ids = array_values(array_filter(array_map('intval', (array) $estado_ids)));
        if (empty($estado_ids)) {
            return null;
        }

        $p = db_prefix();

        /*
         * O email vem da lead OU do contacto principal do cliente. COALESCE
         * escolhe o primeiro que existir; sem nenhum, a tarefa não tem
         * destinatário e é contada como "sem contacto".
         */
        $sql = "FROM `{$p}tasks` t
            LEFT JOIN `{$p}leads` l
                   ON t.rel_type = 'lead' AND l.id = t.rel_id
            LEFT JOIN `{$p}contacts` ct
                   ON t.rel_type = 'customer' AND ct.userid = t.rel_id AND ct.is_primary = 1
            WHERE t.status IN (" . implode(',', $estado_ids) . ')';

        $binds = [];

        /*
         * Um comercial só envia para as SUAS tarefas. A restrição é a mesma
         * que o Perfex usa na lista de tarefas: estar atribuído.
         */
        if ($staff_id !== null) {
            $sql    .= " AND EXISTS (SELECT 1 FROM `{$p}task_assigned` ta
                                     WHERE ta.taskid = t.id AND ta.staffid = ?)";
            $binds[] = (int) $staff_id;
        }

        return [$sql, $binds];
    }

    /** Contagens por estado, para o ecrã de confirmação. Nada sai daqui. */
    public function contar_tarefas($estado_ids, $staff_id)
    {
        $base = $this->tarefas_base($estado_ids, $staff_id);
        if ($base === null) {
            return [];
        }
        list($sql, $binds) = $base;

        $email = "COALESCE(NULLIF(l.email, ''), NULLIF(ct.email, ''))";

        $q = "SELECT t.status AS estado_id, COUNT(*) AS total,
                     SUM(CASE WHEN {$email} IS NOT NULL THEN 1 ELSE 0 END) AS com_contacto
              " . $sql . ' GROUP BY t.status ORDER BY t.status';

        return $this->db->query($q, $binds)->result_array();
    }

    /**
     * As tarefas a quem se vai mesmo escrever.
     *
     * Uma pessoa com duas tarefas no mesmo estado receberia dois emails
     * iguais — daí agrupar por email e ficar só com uma linha por
     * destinatário. O nome é o da lead/contacto, não o da tarefa: quem lê o
     * email é a pessoa, não a tarefa.
     */
    public function get_tarefas_para_envio($estado_ids, $staff_id, $limite = 500)
    {
        $base = $this->tarefas_base($estado_ids, $staff_id);
        if ($base === null) {
            return [];
        }
        list($sql, $binds) = $base;

        $email = "COALESCE(NULLIF(l.email, ''), NULLIF(ct.email, ''))";
        $nome  = "COALESCE(NULLIF(l.name, ''), TRIM(CONCAT(ct.firstname, ' ', ct.lastname)))";

        $q = "SELECT MIN(t.id) AS id, {$email} AS email, MIN({$nome}) AS name,
                     MIN(t.name) AS tarefa,
                     (SELECT ta.staffid FROM `" . db_prefix() . "task_assigned` ta
                       WHERE ta.taskid = MIN(t.id) LIMIT 1) AS assigned
              " . $sql . " AND {$email} IS NOT NULL
              GROUP BY {$email}
              ORDER BY MIN(t.id) DESC
              LIMIT " . (int) $limite;

        return $this->db->query($q, $binds)->result_array();
    }

    /** Os estados de tarefa do Perfex, para o formulário. */
    public function get_estados_tarefa()
    {
        $this->load->model('tasks_model');

        $saida = [];
        foreach ($this->tasks_model->get_statuses() as $e) {
            $saida[] = ['id' => (int) $e['id'], 'name' => $e['name'], 'color' => $e['color']];
        }

        return $saida;
    }

    /**
     * Quem tem tarefas, com a contagem — para o selector do envio por tarefa.
     *
     * Listar os 22 activos quando só 9 têm tarefas dá um selector cheio de
     * nomes que não levam a lado nenhum. E a contagem ao lado poupa a escolher
     * um nome só para descobrir que não tem nada.
     */
    public function get_comerciais_com_tarefas()
    {
        $p = db_prefix();

        return $this->db->query(
            "SELECT s.staffid, CONCAT(s.firstname, ' ', s.lastname) AS nome, COUNT(DISTINCT ta.taskid) AS n
             FROM `{$p}task_assigned` ta
             JOIN `{$p}staff` s ON s.staffid = ta.staffid
             GROUP BY s.staffid
             HAVING n > 0
             ORDER BY n DESC"
        )->result_array();
    }

    /**
     * Guarda o que passa do tecto diário, em lotes de 24 em 24 horas.
     *
     * Guarda-se o TEXTO da mensagem, não uma referência: se alguém editar o
     * texto no ecrã amanhã, os emails que faltam devem sair como foram
     * aprovados hoje, não com outra coisa qualquer.
     */
    public function agendar_envio_tarefa($destinos, $staff_id, $assunto, $mensagem, $anexo, $anexo_nome, $por_lote = 100, $lote = null)
    {
        if (empty($destinos)) {
            return 0;
        }

        $t = db_prefix() . 'dps_envio_tarefa_fila';
        // O lote vem de fora para os que saem hoje e os que ficam para amanhã
        // aparecerem juntos no registo, que é como se lê um envio.
        $lote = $lote ?: uniqid('lote', false);
        $n    = 0;

        foreach (array_values($destinos) as $i => $d) {
            /*
             * O primeiro lote sai JÁ (fica vencido no instante), os seguintes
             * um dia por cada 100. Antes o primeiro lote era enviado ali mesmo,
             * dentro do pedido do browser: 100 emails levam minutos, a ligação
             * caía e o comercial via "Erro de comunicação" num envio que tinha
             * corrido bem — e ficava sem saber se devia repetir. Agora ninguém
             * espera: o cron leva-os nos minutos seguintes.
             */
            $lote_n = (int) floor($i / max(1, (int) $por_lote));
            $dias   = $lote_n;

            $this->db->insert($t, [
                'lote'          => $lote,
                'staff_id'      => (int) $staff_id,
                // Sem isto não havia como voltar da fila à tarefa: a linha só
                // guardava o email, e é a tarefa que tem de mudar de estado
                // quando a mensagem sai.
                'task_id'       => (int) ($d['id'] ?? 0) ?: null,
                'email'         => $d['email'],
                'nome'          => $d['name'],
                'assunto'       => $assunto,
                'mensagem'      => $mensagem,
                'anexo'         => $anexo,
                'anexo_nome'    => $anexo_nome,
                'agendado_para' => $dias === 0
                    ? date('Y-m-d H:i:s')
                    : date('Y-m-d H:i:s', strtotime('+' . $dias . ' day')),
                'estado'        => 'pendente',
            ]);
            $n++;
        }

        return $n;
    }

    /** O que já pode sair: vencido, pendente, e nunca mais de $limite. */
    public function fila_tarefa_por_enviar($limite = 100)
    {
        return $this->db
            ->where('estado', 'pendente')
            ->where('agendado_para <=', date('Y-m-d H:i:s'))
            ->order_by('agendado_para', 'ASC')
            ->limit((int) $limite)
            ->get(db_prefix() . 'dps_envio_tarefa_fila')
            ->result_array();
    }

    public function fila_tarefa_marcar($id, $ok, $detalhe = '')
    {
        $linha = $this->db->select('task_id')->where('id', (int) $id)
            ->get(db_prefix() . 'dps_envio_tarefa_fila')->row_array();

        $this->db->where('id', (int) $id)->update(db_prefix() . 'dps_envio_tarefa_fila', [
            'estado'     => $ok ? 'enviado' : 'falhou',
            'enviado_em' => date('Y-m-d H:i:s'),
            'detalhe'    => mb_substr((string) $detalhe, 0, 250),
        ]);

        if ($ok && !empty($linha['task_id'])) {
            $this->tarefa_em_progresso((int) $linha['task_id']);
        }
    }

    /**
     * A tarefa passa a "Em progresso" quando a mensagem sai.
     *
     * O ponto é este: uma tarefa em "Não iniciada" depois de a mensagem já ter
     * saído mente a quem olha para a lista — e leva o colega a escrever outra
     * vez à mesma pessoa. Assim que o email sai, a tarefa deixa de estar por
     * começar.
     *
     * Só se mexe em tarefas que ainda NÃO arrancaram. Uma tarefa já concluída,
     * ou que alguém pôs à espera de resposta, é informação que a pessoa
     * escreveu à mão e não é um envio automático que a vai apagar.
     */
    public function tarefa_em_progresso($task_id)
    {
        $task_id = (int) $task_id;
        if (!$task_id) {
            return false;
        }

        $t = $this->db->select('id, status')->where('id', $task_id)
            ->get(db_prefix() . 'tasks')->row_array();

        if (!$t || (int) $t['status'] !== DPS_AUTOMACAO_TAREFA_NAO_INICIADA) {
            return false;
        }

        $this->db->where('id', $task_id)->update(db_prefix() . 'tasks', [
            'status' => DPS_AUTOMACAO_TAREFA_EM_PROGRESSO,
        ]);

        return true;
    }

    /** Ainda falta alguma coisa deste lote? Serve para saber quando apagar o anexo. */
    public function fila_tarefa_lote_pendente($lote)
    {
        return $this->db->where('lote', $lote)->where('estado', 'pendente')
            ->count_all_results(db_prefix() . 'dps_envio_tarefa_fila') > 0;
    }

    /** O que está à espera, para o ecrã mostrar. */
    public function fila_tarefa_resumo($staff_id = null)
    {
        $this->db->select('DATE(agendado_para) dia, COUNT(*) n', false)
            ->where('estado', 'pendente');
        if ($staff_id !== null) {
            $this->db->where('staff_id', (int) $staff_id);
        }

        return $this->db->group_by('dia')->order_by('dia', 'ASC')
            ->get(db_prefix() . 'dps_envio_tarefa_fila')->result_array();
    }

    /**
     * Regista um envio que já saiu (ou falhou) na hora.
     *
     * Os adiados já ficavam na fila; os primeiros 100 saíam e não deixavam
     * rasto nenhum. Sem registo não há como responder a "a quem foi?" nem
     * "porque é que aquele não recebeu?" — e num envio de centenas essa é a
     * primeira pergunta quando alguma coisa corre mal.
     */
    public function registar_envio_tarefa($lote, $staff_id, $email, $nome, $assunto, $mensagem, $anexo_nome, $ok, $detalhe = '')
    {
        $this->db->insert(db_prefix() . 'dps_envio_tarefa_fila', [
            'lote'          => $lote,
            'staff_id'      => (int) $staff_id,
            'email'         => $email,
            'nome'          => $nome,
            'assunto'       => $assunto,
            'mensagem'      => $mensagem,
            'anexo_nome'    => $anexo_nome,
            'agendado_para' => date('Y-m-d H:i:s'),
            'enviado_em'    => date('Y-m-d H:i:s'),
            'estado'        => $ok ? 'enviado' : 'falhou',
            'detalhe'       => mb_substr((string) $detalhe, 0, 250),
        ]);
    }

    /**
     * O registo, lote a lote: quando, quem mandou, quantos saíram, quantos
     * falharam e quantos ainda estão para sair.
     */
    public function registo_envios_tarefa($staff_id = null, $limite = 20)
    {
        $t = db_prefix() . 'dps_envio_tarefa_fila';

        $this->db->select("lote, MIN(assunto) assunto, MIN(staff_id) staff_id,
            MIN(agendado_para) inicio, MAX(enviado_em) fim, COUNT(*) total,
            SUM(estado = 'enviado') enviados,
            SUM(estado = 'falhou') falhas,
            SUM(estado = 'pendente') pendentes,
            MIN(anexo_nome) anexo", false);
        $this->db->from($t);
        if ($staff_id !== null) {
            $this->db->where('staff_id', (int) $staff_id);
        }
        $this->db->group_by('lote')->order_by('inicio', 'DESC')->limit((int) $limite);

        return $this->db->get()->result_array();
    }

    /** As linhas de um lote — a quem foi, quem recebeu, quem falhou. */
    public function detalhe_envio_tarefa($lote)
    {
        return $this->db->where('lote', $lote)
            ->order_by("FIELD(estado,'falhou','pendente','enviado')", '', false)
            ->order_by('email', 'ASC')
            ->get(db_prefix() . 'dps_envio_tarefa_fila')->result_array();
    }

    /* ---------------------------------------------------------------------
     * ENVIO EM MASSA A CLIENTES, por empreendimento
     *
     * O destinatário aqui não é uma lead: é quem já comprou. A lista sai das
     * VENDAS e não de um campo copiado para a ficha do cliente — quem comprou
     * em dois empreendimentos tem de aparecer nos dois envios, e um campo
     * único fazia o segundo apagar o primeiro.
     * ------------------------------------------------------------------ */

    /** Empreendimentos que já têm clientes, com a contagem. */
    public function empreendimentos_com_clientes()
    {
        return $this->db
            ->select('v.empreendimento, COUNT(DISTINCT v.client_id) AS n', false)
            ->from(db_prefix() . 'simulador_vendas v')
            ->where('v.client_id IS NOT NULL')
            ->group_by('v.empreendimento')
            ->order_by('v.empreendimento', 'ASC')
            ->get()->result_array();
    }

    /**
     * Clientes de um empreendimento, um por linha, já com o email do contacto
     * principal e o que compraram.
     *
     * @param string $empreendimento vazio = todos os empreendimentos
     */
    public function clientes_para_envio($empreendimento = '')
    {
        $this->db
            ->select('c.userid, c.company, ct.email, ct.firstname,
                      GROUP_CONCAT(DISTINCT v.empreendimento ORDER BY v.empreendimento SEPARATOR ", ") AS empreendimentos,
                      GROUP_CONCAT(DISTINCT v.unidade ORDER BY v.unidade SEPARATOR ", ") AS unidades', false)
            ->from(db_prefix() . 'simulador_vendas v')
            ->join(db_prefix() . 'clients c', 'c.userid = v.client_id')
            ->join(db_prefix() . 'contacts ct', 'ct.userid = c.userid AND ct.is_primary = 1', 'left')
            ->where('v.client_id IS NOT NULL');

        if (trim((string) $empreendimento) !== '') {
            $this->db->where('v.empreendimento', $empreendimento);
        }

        $this->db->group_by('c.userid')->order_by('c.company', 'ASC');

        return $this->db->get()->result_array();
    }

    /** Regista um envio a cliente, para o registo de envios. */
    public function registar_envio_cliente($cliente_id, $staff_id, $assunto, $ok, $detalhe = null)
    {
        $this->db->insert(db_prefix() . 'dps_automacao_envios', [
            'lead_id'   => 0,
            'cliente_id'=> (int) $cliente_id,
            'staff_id'  => (int) $staff_id,
            'canal'     => 'email',
            'tipo'      => 'massa_cliente',
            'mensagem'  => mb_substr((string) $assunto, 0, 500),
            'ok'        => $ok ? 1 : 0,
            'detalhe'   => $detalhe ? mb_substr($detalhe, 0, 255) : null,
            'dateadded' => date('Y-m-d H:i:s'),
        ]);
    }

}
