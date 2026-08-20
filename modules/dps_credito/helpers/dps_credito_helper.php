<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Os estados de lead que contam como "fechar". Guardados em opção para o
 * administrador ajustar sem tocar em código.
 */
function dps_credito_estados_fecho()
{
    $raw = get_option('dps_credito_estados_fecho');

    if (empty($raw)) {
        return [];
    }

    return array_filter(array_map('intval', explode(',', $raw)));
}

function dps_credito_estado_e_fecho($status_id)
{
    return in_array((int) $status_id, dps_credito_estados_fecho(), true);
}

/**
 * Fontes de lead a que o questionário se aplica. O crédito só faz sentido para
 * leads de imobiliário em Portugal — as de outros países não devem ser
 * incomodadas com esta pergunta.
 *
 * Se o administrador não escolher fontes, tentamos adivinhar pelas que têm
 * "portugal" no nome. Melhor um palpite razoável do que bloquear tudo.
 */
function dps_credito_fontes_aplicaveis()
{
    $raw = get_option('dps_credito_fontes');

    if (!empty($raw)) {
        return array_filter(array_map('intval', explode(',', $raw)));
    }

    $CI = &get_instance();
    $CI->db->like('LOWER(name)', 'portugal');
    $fontes = $CI->db->get(db_prefix() . 'leads_sources')->result_array();

    return array_map('intval', array_column($fontes, 'id'));
}

/**
 * Fontes de FORA de Portugal — as únicas excluídas do questionário.
 *
 * Antes exigia-se que a fonte fosse explicitamente "IMO Portugal"; as leads
 * com a fonte por preencher (as novas, por exemplo) ficavam de fora e a
 * coluna mostrava só "—", sem forma de responder. Passa a ser ao contrário:
 * entra tudo excepto o que é claramente de outro país.
 */
function dps_credito_fontes_excluidas()
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $CI  = &get_instance();
    $ids = [];

    foreach ($CI->db->get(db_prefix() . 'leads_sources')->result_array() as $f) {
        $n = strtoupper((string) $f['name']);
        if (strpos($n, 'BRASIL') !== false || strpos($n, 'BRAZIL') !== false || strpos($n, 'DUBAI') !== false) {
            $ids[] = (int) $f['id'];
        }
    }

    $cache = $ids;

    return $cache;
}

/**
 * Esta lead entra no questionário de crédito?
 * Se o administrador definiu fontes explícitas, manda essa lista; caso
 * contrário entra tudo menos as fontes de fora de Portugal.
 */
function dps_credito_fonte_entra($source_id)
{
    $source_id = (int) $source_id;

    $raw = get_option('dps_credito_fontes');
    if (!empty($raw)) {
        return in_array($source_id, array_filter(array_map('intval', explode(',', $raw))), true);
    }

    return !in_array($source_id, dps_credito_fontes_excluidas(), true);
}

/**
 * Esta lead está sujeita ao questionário de crédito? Só se a sua fonte estiver
 * na lista aplicável. Se não houver nenhuma fonte configurada nem detectável,
 * ninguém é bloqueado — assim uma configuração em falta nunca trava o CRM todo.
 */
function dps_credito_lead_aplicavel($lead_id)
{
    $CI = &get_instance();
    $CI->db->select('source');
    $CI->db->where('id', (int) $lead_id);
    $lead = $CI->db->get(db_prefix() . 'leads')->row_array();

    if (!$lead) {
        return false;
    }

    // Mesma regra da coluna: entra tudo menos as fontes de fora de Portugal.
    // Assim as leads sem fonte também exigem a resposta antes de mudar de
    // estado — antes escapavam por completo à obrigatoriedade.
    return dps_credito_fonte_entra($lead['source']);
}

/**
 * Uma resposta só conta como completa se for coerente: dizer "sim, abordei" e
 * deixar o resto em branco não deve destrancar o fecho da lead.
 */
function dps_credito_lead_tem_resposta($lead_id)
{
    $CI = &get_instance();

    $CI->db->where('lead_id', (int) $lead_id);
    $resposta = $CI->db->get(db_prefix() . 'dps_credito_respostas')->row_array();

    if (!$resposta) {
        return false;
    }

    /*
     * "Não" e "não atendeu" fecham a resposta — não há mais nada a perguntar.
     * O "sim" também deixou de exigir os campos: o que segue para o parceiro é
     * a ficha da lead, e o questionário deixou de trancar a mudança de estado.
     */
    return in_array($resposta['abordado'], ['nao', 'nao_atendeu', 'sim'], true);
}

function dps_credito_pedido_ajax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function dps_credito_nome_situacao($situacao)
{
    $nomes = [
        'financiamento_existente' => 'Já financiado',
        'novo_pedido'             => 'Novo pedido',
    ];

    return $nomes[$situacao] ?? '—';
}

/**
 * Estados do processo de crédito, pela ordem do fluxo:
 * submetido → (documentos_em_falta ↔ submetido) → em_analise → sucesso / recusado
 */
function dps_credito_estados_processo()
{
    return ['submetido', 'documentos_em_falta', 'em_analise', 'sucesso', 'recusado'];
}

function dps_credito_nome_estado($estado)
{
    $nomes = [
        'submetido'           => 'Submetida',
        'documentos_em_falta' => 'Documentos em falta',
        'em_analise'          => 'Em análise',
        'sucesso'             => 'Sucesso',
        'recusado'            => 'Recusado',
    ];

    return $nomes[$estado] ?? ucfirst(str_replace('_', ' ', (string) $estado));
}

function dps_credito_cor_estado($estado)
{
    $cores = [
        'submetido'           => 'label-info',
        'documentos_em_falta' => 'label-warning',
        'em_analise'          => 'label-primary',
        'sucesso'             => 'label-success',
        'recusado'            => 'label-danger',
    ];

    return $cores[$estado] ?? 'label-default';
}

/**
 * Percentagem de comissão do comercial sobre o valor do crédito recebido.
 * Configurável nas Definições; por omissão 0,5%.
 */
function dps_credito_taxa_comissao()
{
    $t = get_option('dps_credito_taxa_comissao');

    return ($t === '' || $t === null) ? 0.5 : (float) $t;
}

/**
 * Bancos mais usados, para o campo não virar texto livre com dez grafias do
 * mesmo banco. Continua a aceitar-se qualquer outro valor escrito à mão.
 */
function dps_credito_bancos()
{
    return [
        'Caixa Geral de Depósitos',
        'Millennium BCP',
        'Santander Totta',
        'Novo Banco',
        'BPI',
        'Bankinter',
        'Crédito Agrícola',
        'Montepio',
        'Abanca',
        'EuroBic',
        'Banco CTT',
        'UCI',
    ];
}

/**
 * Dados da análise comercial de crédito, por comercial.
 *
 * Vive no helper (e não no controller) para que a "Visão Geral" das
 * propostas possa mostrar o mesmo sem duplicar a lógica — havendo duas
 * cópias, mais tarde ou mais cedo divergiam e os números deixavam de bater.
 *
 * @param string $de   Y-m-d
 * @param string $ate  Y-m-d
 * @param int    $comercial  0 = todos
 */
function dps_credito_analise_dados($de, $ate, $comercial = 0)
{
    $CI   = &get_instance();
    $p    = db_prefix();
    $deS  = $CI->db->escape($de . ' 00:00:00');
    $ateS = $CI->db->escape($ate . ' 23:59:59');

    $fontes  = dps_credito_fontes_aplicaveis();
    $filtroF = !empty($fontes) ? ' AND l.source IN (' . implode(',', array_map('intval', $fontes)) . ')' : '';
    $filtroC = $comercial > 0 ? ' AND s.staffid = ' . (int) $comercial : '';

    $linhas = $CI->db->query("
        SELECT s.staffid,
               CONCAT(s.firstname,' ',s.lastname) AS comercial,
               COUNT(DISTINCT l.id) AS leads_total,
               COUNT(DISTINCT CASE WHEN r.abordado = 'sim' THEN l.id END) AS sim,
               COUNT(DISTINCT CASE WHEN r.abordado = 'nao' THEN l.id END) AS nao,
               COUNT(DISTINCT CASE WHEN r.abordado = 'nao_atendeu' THEN l.id END) AS nao_atendeu,
               COUNT(DISTINCT CASE WHEN r.abordado IS NULL  THEN l.id END) AS indefinido,
               COUNT(DISTINCT CASE WHEN r.interessado_proposta = 'sim' THEN l.id END) AS interessados,
               COALESCE(SUM(DISTINCT r.montante),0) AS montante_total
        FROM {$p}staff s
        LEFT JOIN {$p}leads l ON l.assigned = s.staffid {$filtroF}
        LEFT JOIN {$p}dps_credito_respostas r ON r.lead_id = l.id
        WHERE s.active = 1 {$filtroC}
        GROUP BY s.staffid
        HAVING leads_total > 0
        ORDER BY sim DESC, leads_total DESC")->result_array();

    $props = [];
    if ($CI->db->table_exists($p . 'dps_propostas')) {
        foreach ($CI->db->query("
            SELECT staff_id, COUNT(*) AS n, COUNT(DISTINCT lead_id) AS leads
            FROM {$p}dps_propostas
            WHERE tipo = 'proposta' AND created_at BETWEEN {$deS} AND {$ateS}
            GROUP BY staff_id")->result_array() as $r) {
            $props[(int) $r['staff_id']] = $r;
        }
    }

    $hist = [];
    if ($CI->db->table_exists($p . 'dps_credito_historico')) {
        foreach ($CI->db->query("
            SELECT staff_id, COUNT(*) AS respostas,
                   SUM(CASE WHEN abordado = 'sim' AND mudou = 1 THEN 1 ELSE 0 END) AS passou_a_sim
            FROM {$p}dps_credito_historico
            WHERE dateadded BETWEEN {$deS} AND {$ateS}
            GROUP BY staff_id")->result_array() as $r) {
            $hist[(int) $r['staff_id']] = $r;
        }
    }

    foreach ($linhas as &$l) {
        $id = (int) $l['staffid'];
        $l['propostas']    = isset($props[$id]) ? (int) $props[$id]['n'] : 0;
        $l['props_leads']  = isset($props[$id]) ? (int) $props[$id]['leads'] : 0;
        $l['respostas']    = isset($hist[$id]) ? (int) $hist[$id]['respostas'] : 0;
        $l['passou_a_sim'] = isset($hist[$id]) ? (int) $hist[$id]['passou_a_sim'] : 0;

        /*
         * QUEM NÃO ATENDE SAI DA CONTA.
         *
         * A taxa de abordagem mede o que o comercial fez com quem conseguiu
         * falar. Contar no denominador as leads que nunca atenderam faz o
         * número descer por uma razão que não é dele — e era isso que
         * estragava a leitura. Regra do dono (19/08/2026).
         *
         * O total continua à vista, e as não atendidas têm coluna própria:
         * muitas chamadas sem resposta são um problema, só que outro.
         */
        $l['nao_atendeu']  = (int) ($l['nao_atendeu'] ?? 0);
        $l['contactaveis'] = max(0, (int) $l['leads_total'] - $l['nao_atendeu']);

        $l['pct_abordagem']  = $l['contactaveis'] > 0
            ? round($l['sim'] / $l['contactaveis'] * 100, 1) : 0;
        $l['pct_respondido'] = $l['contactaveis'] > 0
            ? round(($l['sim'] + $l['nao']) / $l['contactaveis'] * 100, 1) : 0;
        $l['pct_proposta']   = $l['sim'] > 0 ? round($l['props_leads'] / $l['sim'] * 100, 1) : 0;
        $l['pct_interesse']  = $l['sim'] > 0 ? round($l['interessados'] / $l['sim'] * 100, 1) : 0;
    }
    unset($l);

    return $linhas;
}

/* =====================================================================
 * ENVIO DA LEAD AO PARCEIRO DE CRÉDITO
 *
 * Quando o comercial responde SIM, a lead segue por email para o parceiro que
 * trata do crédito habitação. Substitui o questionário que antes se abria a
 * seguir ao "sim": as perguntas (situação, banco, montante) eram respondidas
 * de cor pelo comercial e o parceiro voltava a fazê-las ao cliente na mesma.
 * O que serve é a ficha da lead. Pedido do dono (19/08/2026).
 * ================================================================== */

/** Para quem vai a lead. Em opção, para se mudar sem tocar no código. */
function dps_credito_email_parceiro()
{
    $e = trim((string) get_option('dps_credito_email_parceiro'));

    return $e !== '' ? $e : 'nuno.moreira@twinloo.com';
}

/**
 * Manda a ficha da lead ao parceiro. Uma vez por lead — não a cada gravação.
 *
 * @return bool true quando o email saiu
 */
function dps_credito_enviar_ao_parceiro($lead_id)
{
    $CI      = &get_instance();
    $lead_id = (int) $lead_id;

    if ($lead_id <= 0) {
        return false;
    }

    $lead = $CI->db->where('id', $lead_id)->get(db_prefix() . 'leads')->row_array();

    if (! $lead) {
        return false;
    }

    /*
     * Uma lead só se envia uma vez. O comercial pode gravar o questionário
     * várias vezes — e cada gravação a repetir o email seria ruído na caixa do
     * parceiro e uma lead a parecer nova de cada vez.
     */
    $tabela = db_prefix() . 'dps_credito_respostas';

    // A coluna nasce à primeira passagem — o módulo é anterior a esta função.
    if (! $CI->db->field_exists('enviado_parceiro_em', $tabela)) {
        $CI->db->query('ALTER TABLE `' . $tabela . '` ADD COLUMN `enviado_parceiro_em` DATETIME NULL DEFAULT NULL');
    }

    $ja = $CI->db->select('id, enviado_parceiro_em')->where('lead_id', $lead_id)
                 ->get($tabela)->row();

    if ($ja && ! empty($ja->enviado_parceiro_em)) {
        return false;
    }

    // Nome legível para os campos que são id: estado, fonte e responsável.
    $nome_de = function ($tabela, $id, $coluna = 'name') use ($CI) {
        $id = (int) $id;
        if ($id <= 0) { return '—'; }
        $r = $CI->db->where('id', $id)->get(db_prefix() . $tabela)->row_array();

        return $r[$coluna] ?? ('#' . $id);
    };

    $linhas = [
        'Nome'            => $lead['name'] ?? '',
        'Empresa'         => $lead['company'] ?? '',
        'Telefone'        => $lead['phonenumber'] ?? '',
        'Email'           => $lead['email'] ?? '',
        'Morada'          => trim(($lead['address'] ?? '') . ' ' . ($lead['zip'] ?? '') . ' ' . ($lead['city'] ?? '')),
        'Estado da lead'  => $nome_de('leads_status', $lead['status'] ?? 0),
        'Origem'          => $nome_de('leads_sources', $lead['source'] ?? 0),
        'Responsável'     => $lead['assigned'] ? get_staff_full_name((int) $lead['assigned']) : '—',
        'Data de entrada' => $lead['dateadded'] ?? '',
        'Último contacto' => $lead['lastcontact'] ?: '—',
    ];

    $html = '<p>Olá,</p><p>Envio lead de cliente interessado em proposta de crédito habitação.</p>'
          . '<table cellpadding="6" style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:14px;">';

    foreach ($linhas as $rotulo => $valor) {
        $valor = trim((string) $valor);
        if ($valor === '') { continue; }
        $html .= '<tr><td style="color:#666;"><strong>' . html_escape($rotulo) . '</strong></td>'
               . '<td>' . html_escape($valor) . '</td></tr>';
    }

    $html .= '</table>';

    // A descrição da lead leva o contexto todo — o que o cliente pediu.
    if (trim((string) ($lead['description'] ?? '')) !== '') {
        $html .= '<p style="margin-top:16px;"><strong>Notas da lead</strong><br>'
               . nl2br(html_escape($lead['description'])) . '</p>';
    }

    $html .= '<p style="margin-top:20px;">Com os melhores cumprimentos,<br><strong>'
           . html_escape(get_option('companyname') ?: 'DPS Imobiliário') . '</strong></p>';

    $CI->load->library('email');
    $CI->email->clear(true);
    $CI->email->from(get_option('smtp_email') ?: get_option('email'),
                     get_option('companyname') ?: 'DPS Imobiliário');
    $CI->email->to(dps_credito_email_parceiro());
    // O dono quer ficar a par de cada lead que sai para o parceiro.
    $CI->email->cc('ricardomagalhaes@grupo-dps.com');
    $CI->email->subject('Lead para crédito habitação — ' . ($lead['name'] ?? ('#' . $lead_id)));
    $CI->email->message($html);
    $CI->email->set_mailtype('html');

    $saiu = (bool) $CI->email->send(false);

    if ($saiu) {
        // Marca para não repetir, e para se saber quando foi.
        $CI->db->where('lead_id', $lead_id)
               ->update($tabela, ['enviado_parceiro_em' => date('Y-m-d H:i:s')]);

        $CI->db->insert(db_prefix() . 'lead_activity_log', [
            'leadid'      => $lead_id,
            'staffid'     => (int) get_staff_user_id(),
            'full_name'   => get_staff_full_name(get_staff_user_id()),
            'date'        => date('Y-m-d H:i:s'),
            'description' => '🏦 Lead enviada para crédito habitação — ' . dps_credito_email_parceiro(),
        ]);
    } else {
        log_activity('DPS Crédito: falhou o envio da lead #' . $lead_id . ' para '
            . dps_credito_email_parceiro());
    }

    return $saiu;
}
