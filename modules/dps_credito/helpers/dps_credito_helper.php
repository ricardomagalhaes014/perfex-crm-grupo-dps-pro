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
 * Esta lead está sujeita ao questionário de crédito? Só se a sua fonte estiver
 * na lista aplicável. Se não houver nenhuma fonte configurada nem detectável,
 * ninguém é bloqueado — assim uma configuração em falta nunca trava o CRM todo.
 */
function dps_credito_lead_aplicavel($lead_id)
{
    $aplicaveis = dps_credito_fontes_aplicaveis();

    if (empty($aplicaveis)) {
        return false;
    }

    $CI = &get_instance();
    $CI->db->select('source');
    $CI->db->where('id', (int) $lead_id);
    $lead = $CI->db->get(db_prefix() . 'leads')->row_array();

    if (!$lead) {
        return false;
    }

    return in_array((int) $lead['source'], $aplicaveis, true);
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

    if ($resposta['abordado'] === 'nao') {
        return true;
    }

    // Abordado = sim exige situação, montante e a intenção quanto à proposta
    return !empty($resposta['situacao'])
        && $resposta['montante'] !== null
        && !empty($resposta['interessado_proposta']);
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

        $l['pct_abordagem']  = $l['leads_total'] > 0 ? round($l['sim'] / $l['leads_total'] * 100, 1) : 0;
        $l['pct_respondido'] = $l['leads_total'] > 0 ? round(($l['sim'] + $l['nao']) / $l['leads_total'] * 100, 1) : 0;
        $l['pct_proposta']   = $l['sim'] > 0 ? round($l['props_leads'] / $l['sim'] * 100, 1) : 0;
        $l['pct_interesse']  = $l['sim'] > 0 ? round($l['interessados'] / $l['sim'] * 100, 1) : 0;
    }
    unset($l);

    return $linhas;
}
