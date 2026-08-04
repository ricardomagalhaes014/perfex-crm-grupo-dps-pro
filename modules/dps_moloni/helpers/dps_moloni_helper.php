<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Totais para o painel financeiro.
 */
function dps_moloni_totals($sales, $links_by_sale = [])
{
    $totals = [
        'sale_value'       => 0.0,
        'commission_due'   => 0.0,  // comissao a pagar aos comerciais
        'commission_recv'  => 0.0,  // comissao recebida do promotor
        'result'           => 0.0,
        'count'            => 0,
        'with_document'    => 0,
        'without_document' => 0,
        'draft_documents'  => 0,
        // Facturado ao promotor mas ainda por liquidar (Fatura sem Recibo).
        'invoiced_open'    => 0.0,
    ];

    foreach ($sales as $sale) {
        $totals['count']++;
        $totals['sale_value']      += (float) $sale['sale_value'];
        $totals['commission_due']  += (float) $sale['commission'];
        $totals['commission_recv'] += (float) $sale['received'];

        $links = isset($links_by_sale[(int) $sale['id']]) ? $links_by_sale[(int) $sale['id']] : [];

        if (!empty($links)) {
            $totals['with_document']++;

            foreach ($links as $link) {
                if ((int) $link['status'] === 0) {
                    $totals['draft_documents']++;
                }

                if (isset($link['kind']) && $link['kind'] === 'invoice'
                    && (int) (isset($link['is_paid']) ? $link['is_paid'] : 0) !== 1) {
                    $totals['invoiced_open'] += (float) $link['net_value'];
                }
            }
        } else {
            $totals['without_document']++;
        }
    }

    $totals['result'] = $totals['commission_recv'] - $totals['commission_due'];

    return $totals;
}

/**
 * Calcula as comissoes de override.
 *
 * Um override e uma percentagem que alguem recebe sobre as vendas da equipa
 * toda, alem da sua propria comissao — tipicamente um coordenador. A lista
 * de excluidos tira da base as vendas de quem nao conta (as que ficam na
 * empresa, ou quem recebe a 100% e emite recibo proprio).
 *
 * Devolve uma linha por override, com a base, o valor e o que ficou de fora.
 */
function dps_moloni_overrides_calc($sales, $overrides)
{
    $out = [];

    foreach ($overrides as $override) {
        $rate = (float) $override['rate'];

        if ($rate <= 0) {
            continue;
        }

        $excluded = [];
        foreach (explode(',', (string) $override['excluded']) as $name) {
            $name = dps_moloni_norm_key($name);
            if ($name !== '') {
                $excluded[$name] = true;
            }
        }

        $base    = 0.0;
        $counted = 0;
        $skipped = 0;

        foreach ($sales as $sale) {
            $who = dps_moloni_norm_key($sale['commercial']);

            if ($who !== '' && isset($excluded[$who])) {
                $skipped++;
                continue;
            }

            $base += (float) $sale['sale_value'];
            $counted++;
        }

        $out[] = [
            'beneficiary' => $override['beneficiary'],
            'rate'        => $rate,
            'base'        => $base,
            'amount'      => round($base * $rate / 100, 2),
            'counted'     => $counted,
            'skipped'     => $skipped,
            'excluded'    => (string) $override['excluded'],
            'note'        => isset($override['note']) ? $override['note'] : '',
            'id'          => isset($override['id']) ? (int) $override['id'] : 0,
        ];
    }

    return $out;
}

/**
 * Total dos overrides, para somar as comissoes a pagar.
 */
function dps_moloni_overrides_total($rows)
{
    $total = 0.0;

    foreach ($rows as $row) {
        $total += (float) $row['amount'];
    }

    return $total;
}

/**
 * Agrupa vendas por uma chave (empreendimento, comercial, ...).
 */
function dps_moloni_group($sales, $key)
{
    $groups = [];

    foreach ($sales as $sale) {
        $label = isset($sale[$key]) && $sale[$key] !== null && $sale[$key] !== ''
            ? (string) $sale[$key]
            : '—';

        if (!isset($groups[$label])) {
            $groups[$label] = [
                'label'      => $label,
                'count'      => 0,
                'sale_value' => 0.0,
                'commission' => 0.0,
                'received'   => 0.0,
                'result'     => 0.0,
            ];
        }

        $groups[$label]['count']++;
        $groups[$label]['sale_value'] += (float) $sale['sale_value'];
        $groups[$label]['commission'] += (float) $sale['commission'];
        $groups[$label]['received']   += (float) $sale['received'];
    }

    foreach ($groups as &$group) {
        $group['result'] = $group['received'] - $group['commission'];
    }
    unset($group);

    uasort($groups, function ($a, $b) {
        return $b['sale_value'] <=> $a['sale_value'];
    });

    return $groups;
}

/**
 * Propoe correspondencias entre documentos da Moloni e linhas de venda.
 *
 * Estrategia, por ordem de confianca:
 *   1. our_reference == "DPS#<id>"  -> certeza
 *   2. valor exacto + nome da entidade parecido -> alta
 *   3. valor exacto -> media
 *   4. soma de dois documentos da mesma entidade == comissao -> media
 *
 * Devolve uma lista de sugestoes, cada uma com o grau de confianca.
 */
function dps_moloni_match($documents, $sales, $already_linked = [], $promoters = [])
{
    $suggestions = [];
    $linked      = array_flip(array_map('intval', $already_linked));

    // NIFs que sao promotores de algum empreendimento. Um documento emitido
    // a um destes so pode pertencer a vendas desse empreendimento.
    $promoter_vats = array_flip(array_values($promoters));

    // Indexa vendas por valor arredondado para procura rapida.
    $by_commission = [];
    $by_received   = [];

    foreach ($sales as $sale) {
        $by_commission[dps_moloni_key((float) $sale['commission'])][] = $sale;
        if ((float) $sale['received'] > 0) {
            $by_received[dps_moloni_key((float) $sale['received'])][] = $sale;
        }
    }

    // Agrupa documentos por entidade, para o caso da soma de varios.
    $by_entity = [];

    foreach ($documents as $doc) {
        $document_id = isset($doc['document_id']) ? (int) $doc['document_id'] : 0;

        if (!$document_id || isset($linked[$document_id])) {
            continue;
        }

        $vat = isset($doc['entity_vat']) ? $doc['entity_vat'] : '';
        $by_entity[$vat][] = $doc;

        $value = dps_moloni_doc_value($doc);
        $ref   = isset($doc['our_reference']) ? trim((string) $doc['our_reference']) : '';

        // 1. referencia explicita
        if ($ref !== '' && preg_match('/DPS#(\d+)/i', $ref, $m)) {
            $sale = dps_moloni_find_sale($sales, (int) $m[1]);

            if ($sale) {
                $suggestions[] = dps_moloni_suggestion($doc, $sale, 'certeza',
                    'Referencia ' . $ref);
                continue;
            }
        }

        // 2/3. valor exacto — testa a base e o total, porque nem sempre se
        //      sabe se a comissao registada inclui IVA.
        $keys = array_unique([dps_moloni_key($value), dps_moloni_key(dps_moloni_doc_total($doc))]);

        foreach ([['comissao', $by_commission, 'receipt'], ['comissao recebida', $by_received, 'invoice']] as $probe) {
            list($field, $index, $kind) = $probe;

            $hits = [];
            foreach ($keys as $key) {
                if (isset($index[$key])) {
                    $hits = array_merge($hits, $index[$key]);
                }
            }

            foreach ($hits as $sale) {
                $name_hit = dps_moloni_names_match(
                    isset($doc['entity_name']) ? $doc['entity_name'] : '',
                    $kind === 'invoice' ? $sale['client'] : $sale['commercial']
                );

                $suggestions[] = dps_moloni_suggestion($doc, $sale,
                    $name_hit ? 'alta' : 'media',
                    'Valor igual a ' . $field . ($name_hit ? ' + nome coincide' : ''),
                    $kind);
            }
        }

        // 4. Fatura ao promotor: o valor do documento e uma percentagem
        //    redonda do valor da venda. A entidade e o promotor, por isso o
        //    nome cruza-se com o empreendimento e nao com o comprador.
        foreach ($sales as $sale) {
            $sale_value = (float) $sale['sale_value'];

            if ($sale_value <= 0 || $value <= 0) {
                continue;
            }

            $pct = dps_moloni_clean_rate($value, $sale_value);

            if ($pct === null) {
                continue;
            }

            $entity   = isset($doc['entity_name']) ? $doc['entity_name'] : '';
            $doc_vat  = dps_moloni_norm_vat(isset($doc['entity_vat']) ? $doc['entity_vat'] : '');
            $proj_key = dps_moloni_norm_key($sale['project']);

            // O promotor do empreendimento e o sinal mais forte que ha.
            if (isset($promoters[$proj_key])) {
                if ($doc_vat !== '' && $doc_vat === dps_moloni_norm_vat($promoters[$proj_key])) {
                    $suggestions[] = dps_moloni_suggestion($doc, $sale, 'certeza',
                        number_format($pct, 2, ',', '') . '% do valor da venda + NIF do promotor',
                        'invoice');
                }

                // O empreendimento tem promotor conhecido e nao e este.
                continue;
            }

            // Documento de um promotor conhecido, mas de outro empreendimento.
            if ($doc_vat !== '' && isset($promoter_vats[$doc_vat])) {
                continue;
            }

            $name_hit = dps_moloni_names_match($entity, $sale['project'])
                || dps_moloni_names_match($entity, $sale['client']);

            // Sem o nome a bater, uma percentagem redonda e coincidencia
            // demasiado facil (um valor redondo sobre uma venda redonda dá
            // sempre uma percentagem limpa). So propomos com os dois sinais.
            if (!$name_hit) {
                continue;
            }

            $suggestions[] = dps_moloni_suggestion($doc, $sale, 'alta',
                number_format($pct, 2, ',', '') . '% do valor da venda + nome coincide',
                'invoice');
        }
    }

    // 4. soma de dois documentos da mesma entidade
    foreach ($by_entity as $vat => $docs) {
        if (count($docs) < 2 || $vat === '') {
            continue;
        }

        $count = count($docs);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $sum = dps_moloni_doc_value($docs[$i]) + dps_moloni_doc_value($docs[$j]);
                $key = dps_moloni_key($sum);

                if (!isset($by_commission[$key])) {
                    continue;
                }

                foreach ($by_commission[$key] as $sale) {
                    // O nome da entidade emissora e o melhor sinal para
                    // distinguir vendas diferentes com a mesma comissao.
                    $name_hit = dps_moloni_names_match(
                        isset($docs[$i]['entity_name']) ? $docs[$i]['entity_name'] : '',
                        $sale['commercial']
                    );

                    foreach ([$docs[$i], $docs[$j]] as $doc) {
                        $suggestions[] = dps_moloni_suggestion($doc, $sale,
                            $name_hit ? 'alta' : 'media',
                            'Soma de 2 documentos da mesma entidade = comissao'
                            . ($name_hit ? ' + nome coincide' : ''));
                    }
                }
            }
        }
    }

    return dps_moloni_dedupe_suggestions($suggestions);
}

function dps_moloni_suggestion($doc, $sale, $confidence, $reason, $kind = 'receipt')
{
    return [
        'confidence' => $confidence,
        'reason'     => $reason,
        'kind'       => $kind,
        'sale'       => $sale,
        'document'   => $doc,
        // Vai num campo de formulario: base64 evita que o filtro XSS global
        // do Perfex mexa nas aspas do JSON.
        'payload'    => base64_encode(json_encode([
            'sale_id'       => (int) $sale['id'],
            'kind'          => $kind,
            'document_id'   => isset($doc['document_id']) ? (int) $doc['document_id'] : 0,
            'document_type' => isset($doc['document_type']['name']) ? $doc['document_type']['name'] : '',
            'document_set'  => isset($doc['document_set']['name']) ? $doc['document_set']['name'] : '',
            'number'        => dps_moloni_doc_number($doc),
            'net_value'     => dps_moloni_doc_value($doc),
            'total_value'   => dps_moloni_doc_total($doc),
            'date'          => isset($doc['date']) ? $doc['date'] : '',
            'status'        => isset($doc['status']) ? (int) $doc['status'] : 0,
            'is_paid'       => dps_moloni_doc_is_paid($doc) ? 1 : 0,
        ], JSON_UNESCAPED_UNICODE)),
    ];
}

/**
 * Remove sugestoes repetidas (mesmo par venda/documento), guardando a de
 * maior confianca.
 */
function dps_moloni_dedupe_suggestions($suggestions)
{
    $rank = ['certeza' => 3, 'alta' => 2, 'media' => 1];
    $best = [];

    foreach ($suggestions as $suggestion) {
        $key = (int) $suggestion['sale']['id'] . ':' . (int) $suggestion['document']['document_id'];

        if (!isset($best[$key]) || $rank[$suggestion['confidence']] > $rank[$best[$key]['confidence']]) {
            $best[$key] = $suggestion;
        }
    }

    uasort($best, function ($a, $b) use ($rank) {
        return $rank[$b['confidence']] <=> $rank[$a['confidence']];
    });

    return array_values($best);
}

function dps_moloni_find_sale($sales, $id)
{
    foreach ($sales as $sale) {
        if ((int) $sale['id'] === (int) $id) {
            return $sale;
        }
    }

    return null;
}

/**
 * Base tributavel do documento — o valor sobre o qual as comissoes sao
 * acordadas.
 *
 * Atencao a nomenclatura da Moloni, que e o contrario do que parece:
 * 'gross_value' e o valor ANTES de impostos e 'net_value' e o total do
 * documento (ja com IVA). Confirmado numa fatura real: gross 16.745,00,
 * net 20.596,35, exactamente 16.745 x 1,23.
 */
function dps_moloni_doc_value($doc)
{
    if (isset($doc['gross_value']) && (float) $doc['gross_value'] > 0) {
        return (float) $doc['gross_value'];
    }

    return isset($doc['net_value']) ? (float) $doc['net_value'] : 0.0;
}

/**
 * O documento representa dinheiro efectivamente recebido?
 *
 * Uma Fatura e uma promessa de pagamento, nao um pagamento. So contam como
 * recebido os documentos que liquidam: Fatura-Recibo, Recibo, Venda a
 * Dinheiro. Na duvida assume-se que NAO esta pago — e melhor subestimar a
 * tesouraria do que dar por recebido o que ainda esta por cobrar.
 */
function dps_moloni_doc_is_paid($doc)
{
    $name = '';

    if (isset($doc['document_type']['name'])) {
        $name = $doc['document_type']['name'];
    } elseif (isset($doc['document_type_name'])) {
        $name = $doc['document_type_name'];
    }

    if ($name !== '') {
        return (bool) preg_match('/recibo|dinheiro/i', $name);
    }

    // Sem nome de tipo, a unica coisa segura e assumir por liquidar.
    return false;
}

/**
 * Total do documento, impostos incluidos.
 */
function dps_moloni_doc_total($doc)
{
    if (isset($doc['net_value']) && (float) $doc['net_value'] > 0) {
        return (float) $doc['net_value'];
    }

    return dps_moloni_doc_value($doc);
}

function dps_moloni_doc_number($doc)
{
    $set  = isset($doc['document_set']['name']) ? $doc['document_set']['name'] : '';
    $num  = isset($doc['number']) ? $doc['number'] : '';

    return trim($set . ($set !== '' && $num !== '' ? '/' : '') . $num);
}

/**
 * Se $value for uma percentagem "limpa" de $base, devolve essa percentagem.
 *
 * Limpa quer dizer: entre 0,5% e 12%, multipla de 0,25, e a reconstruir o
 * valor ao centimo. Serve para reconhecer comissoes (2,5% ao comercial, 5%
 * ao promotor) sem ter de configurar taxas em lado nenhum.
 *
 * @return float|null
 */
function dps_moloni_clean_rate($value, $base)
{
    if ($base <= 0) {
        return null;
    }

    $pct = ($value / $base) * 100;

    if ($pct < 0.5 || $pct > 12) {
        return null;
    }

    // Multipla de 0,25 pontos percentuais.
    if (abs($pct * 4 - round($pct * 4)) > 0.0005) {
        return null;
    }

    $pct = round($pct * 4) / 4;

    // E tem de reconstruir o valor exactamente.
    if (dps_moloni_key($base * $pct / 100) !== dps_moloni_key($value)) {
        return null;
    }

    return $pct;
}

/**
 * Chave de comparacao de valores, tolerante a centimos.
 */
function dps_moloni_key($value)
{
    return number_format((float) $value, 2, '.', '');
}

/**
 * Chave de comparacao de nomes (empreendimentos, etc.): sem acentos,
 * minusculas, so letras e digitos.
 */
function dps_moloni_norm_key($value)
{
    $value = (string) $value;

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    return preg_replace('/[^a-z0-9]/', '', strtolower($value));
}

/**
 * NIF normalizado para comparacao.
 */
function dps_moloni_norm_vat($vat)
{
    return preg_replace('/[^0-9A-Za-z]/', '', (string) $vat);
}

/**
 * Comparacao "suficientemente parecida" de nomes de pessoas/empresas.
 */
function dps_moloni_names_match($a, $b)
{
    $normalize = function ($s) {
        $s = (string) $s;

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $s);
            if ($converted !== false) {
                $s = $converted;
            }
        }

        $s = strtolower($s);
        $s = preg_replace('/\b(lda|unipessoal|sa|s\.a\.|limitada|ltd)\b/', ' ', $s);
        $s = preg_replace('/[^a-z0-9 ]/', ' ', $s);

        return preg_replace('/\s+/', ' ', trim($s));
    };

    $a = $normalize($a);
    $b = $normalize($b);

    if ($a === '' || $b === '') {
        return false;
    }

    if ($a === $b) {
        return true;
    }

    // Tokens em comum: nomes proprios costumam bastar.
    $ta = array_filter(explode(' ', $a), function ($t) { return strlen($t) > 2; });
    $tb = array_filter(explode(' ', $b), function ($t) { return strlen($t) > 2; });

    if (empty($ta) || empty($tb)) {
        return false;
    }

    // Tokens contam como iguais tambem quando um e prefixo do outro, para
    // apanhar singular/plural e abreviaturas ("tower" vs "towers").
    $hits = 0;

    foreach ($ta as $x) {
        foreach ($tb as $y) {
            if ($x === $y || (strlen($x) >= 4 && strlen($y) >= 4
                && (strpos($x, $y) === 0 || strpos($y, $x) === 0))) {
                $hits++;
                break;
            }
        }
    }

    // Dois tokens em comum, ou os dois nomes reduzidos ao mesmo token unico.
    // Um so token em comum entre nomes com varias palavras nao chega:
    // "DM Towers" e "Boavista Towers" sao promotores diferentes.
    return $hits >= 2 || ($hits === 1 && count($ta) === 1 && count($tb) === 1);
}

/**
 * Formata um valor em euros para os ecras do modulo.
 */
function dps_moloni_money($value)
{
    return number_format((float) $value, 2, ',', ' ') . ' &euro;';
}

/**
 * Etiqueta visual do grau de confianca.
 */
function dps_moloni_confidence_label($confidence)
{
    $map = [
        'certeza' => ['label' => _l('dps_moloni_conf_certain'), 'class' => 'success'],
        'alta'    => ['label' => _l('dps_moloni_conf_high'), 'class' => 'info'],
        'media'   => ['label' => _l('dps_moloni_conf_medium'), 'class' => 'warning'],
    ];

    return isset($map[$confidence]) ? $map[$confidence] : ['label' => $confidence, 'class' => 'default'];
}
