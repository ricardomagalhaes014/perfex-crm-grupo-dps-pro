<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
$moeda   = get_base_currency();
$f_meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho',
            7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
$f_estados = ['vendido' => 'CPCV', 'concluido' => 'Concluído'];

$f_base = [
    'recebida' => 'do que recebemos — e só sobre a parte que já entrou em caixa',
    'paga'     => 'da comissão do comercial',
    'venda'    => 'do valor da venda (meio ponto do preço, seja qual for a taxa do empreendimento) — pago no CPCV',
];
// Sufixo curto para o rótulo do cartão: sem ele lia-se "Direcção (0,5%)" e não
// se percebia 0,5% de quê — foi essa dúvida que motivou tudo isto.
$s_base = ['recebida' => 'do recebido', 'paga' => 'da comissão', 'venda' => 'da venda'];
$pct    = function ($n) {
    return rtrim(rtrim(number_format((float) $n, 4, ',', ''), '0'), ',');
};
?>
<style>
/*
 * Os cartões estavam num grid do Bootstrap 3 com float. Como cada um tem uma
 * legenda de comprimento diferente, ficavam com alturas diferentes e a linha
 * seguinte encavalitava-se nos buracos — o painel lia-se aos solavancos.
 *
 * Com flex, todos os cartões de uma fila ficam com a altura do mais alto e as
 * filas quebram limpas. Cinco por fila em ecrã largo, dois em tablet, um no
 * telemóvel.
 */
.dps-cards { display: flex; flex-wrap: wrap; margin-left: -7px; margin-right: -7px; }
.dps-cards > .dps-card { padding: 0 7px 14px; display: flex; width: 100%; }
.dps-cards > .dps-card > .panel_s { width: 100%; margin-bottom: 0; display: flex; }
.dps-cards > .dps-card > .panel_s > .panel-body { width: 100%; }
@media (min-width: 768px)  { .dps-cards > .dps-card { width: 50%; } }
@media (min-width: 992px)  { .dps-cards > .dps-card { width: 33.333%; } }
@media (min-width: 1400px) { .dps-cards > .dps-card { width: 20%; } }

/* Cabeçalho de cada secção: discreto, só para dar ordem à leitura. */
.dps-seccao { margin: 4px 0 10px; font-size: 12px; letter-spacing: .06em;
              text-transform: uppercase; color: #8a8f94; font-weight: 600; }
.dps-seccao:not(:first-of-type) { margin-top: 6px; }
</style>
<div id="wrapper">
    <div class="content">

        <div class="row mbot15">
            <div class="col-md-6"><h4 class="no-margin"><i class="fa fa-briefcase"></i> Painel do Negócio</h4></div>
            <div class="col-md-6 text-right">
                <a href="<?php echo admin_url('dps_painel/recebimento'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-percent"></i> Comissões a receber
                </a>
                <a href="<?php echo admin_url('dps_painel/definicoes'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-plug"></i> Definições
                    <?php echo !empty($moloni['dev_id']) ? '<span class="label label-success">Moloni on</span>' : '<span class="label label-default">Moloni off</span>'; ?>
                </a>
            </div>
        </div>

        <?php if (!empty($totais['sem_taxa'])) { ?>
            <div class="alert alert-warning">
                <i class="fa fa-warning"></i>
                Há <strong><?php echo (int) $totais['sem_taxa']; ?></strong> venda(s) sem comissão a receber definida —
                entram a zero e puxam o resultado para baixo.
                <a href="<?php echo admin_url('dps_painel/recebimento'); ?>">Definir agora</a>.
            </div>
        <?php } ?>

        <!-- Cartões, agrupados pelo percurso do dinheiro -->
        <div>
            <?php
            /*
             * "Recebemos" é dinheiro EM CAIXA. O que o promotor só paga mais
             * tarde (Belo Horizonte, na data do CPCV) vive no seu próprio
             * cartão — e, sobretudo, NÃO entra no override da direcção: não se
             * paga 0,5% de dinheiro que ainda não entrou.
             *
             * Cada cartão pode levar uma segunda linha (índice 4) a dizer o
             * mesmo número na base do previsto, para se ver os dois planos sem
             * trocar de ecrã.
             */
            $rot_dir = $regras['director_id'] > 0
                ? 'Direcção (' . $pct($regras['director_pct']) . '% ' . $s_base[$regras['director_base']] . ')'
                : 'Direcção (ninguém)';

            $cards = [
                [
                    'Recebemos (em caixa)', $totais['recebido'], 'text-success', 'fa-arrow-down',
                    'com factura emitida e visto de recebido'
                        . ($totais['recebido_previsto'] > 0
                            ? ' · de ' . app_format_money($totais['recebido_previsto'], $moeda) . ' previstos'
                            : ''),
                ],
                /*
                 * A EMITIR FACTURA — pagamento validado, factura por passar.
                 * É trabalho nosso, não do promotor: enquanto não sair factura
                 * não há o que cobrar, e a verba não pode aparecer em "a
                 * receber" a fingir que já se pediu o dinheiro.
                 */
                [
                    'A emitir factura', $totais['a_emitir'], 'text-danger', 'fa-file-o',
                    $totais['a_emitir'] > 0
                        ? 'pagamento validado, falta passar factura'
                        : 'nada por facturar',
                ],
                [
                    'A receber AGORA', $totais['a_receber_agora'], 'text-warning', 'fa-exclamation-triangle',
                    $totais['a_receber_agora'] > 0
                        ? 'com factura, prazo vencido — cobrar ou marcar recebido'
                        : ($totais['a_emitir'] > 0 ? 'zero: falta emitir factura' : 'nada vencido'),
                ],
                /*
                 * O futuro em dois cartões: CPCV e escrituras são calendários
                 * diferentes. Um CPCV é o próximo horizonte (Belo Horizonte
                 * 12/2026); uma escritura pode ser 12/2028 ou, no Aura, 2029.
                 *
                 * Quando um deles está a zero diz-se PORQUÊ. Um zero sem
                 * explicação lê-se como avaria — e neste painel a razão é quase
                 * sempre a mesma: o dinheiro está em Perspectiva, à espera de
                 * que o pagamento seja validado.
                 */
                [
                    'A receber no futuro — CPCV', $totais['a_receber_futuro_cpcv'], 'text-info', 'fa-calendar',
                    $totais['a_receber_futuro_cpcv'] > 0
                        ? 'CPCV com mês marcado à frente'
                        : ($totais['perspectiva_cpcv'] > 0
                            ? 'zero: ' . app_format_money($totais['perspectiva_cpcv'], $moeda) . ' estão em perspectiva, por validar'
                            : 'nada marcado para o futuro'),
                ],
                [
                    'A receber no futuro — Escrituras', $totais['a_receber_futuro_escritura'], 'text-info', 'fa-institution',
                    $totais['a_receber_futuro_escritura'] > 0
                        ? 'escrituras com mês marcado à frente'
                        : ($totais['perspectiva_escritura'] > 0
                            ? 'zero: ' . app_format_money($totais['perspectiva_escritura'], $moeda) . ' estão em perspectiva, por validar'
                            : 'nada marcado para o futuro'),
                ],
                /*
                 * PERSPECTIVA — vendas cujo pagamento ainda não foi validado.
                 *
                 * Não entra em "a receber" nenhum: enquanto a direção não
                 * confere o comprovativo, a venda não passa a concluída e não
                 * há nada a cobrar ao promotor. Regra do dono (31/07/2026).
                 * Fica à vista à mesma, porque é trabalho por fechar.
                 */
                [
                    'Perspectiva (por validar)', $totais['perspectiva'], 'text-muted', 'fa-hourglass-half',
                    $totais['vendas_por_validar'] > 0
                        ? $totais['vendas_por_validar'] . ' venda' . ($totais['vendas_por_validar'] == 1 ? '' : 's')
                            . ' à espera de validação do pagamento'
                        : 'nada por validar',
                ],
                [
                    'Comerciais', $totais['comissao_comercial'], 'text-danger', 'fa-arrow-up',
                    'já pagos: ' . app_format_money($totais['pago_comercial'], $moeda)
                        . ' · a pagar agora: ' . app_format_money($totais['comerciais_agora'], $moeda)
                        . ' · prazo futuro: ' . app_format_money($totais['comerciais_futuro'], $moeda),
                ],
                /*
                 * O número em destaque é o TOTAL a pagar à direção.
                 *
                 * Estava a mostrar só o override sobre vendas já marcadas como
                 * recebidas — dava "0,00" em grande e vermelho enquanto se
                 * deviam 17.759,50, com os números verdadeiros em letra
                 * pequena. Quem lê um cartão lê o número grande.
                 */
                [
                    $rot_dir, $totais['direcao_prevista'], 'text-danger', 'fa-user',
                    'com dinheiro em casa: ' . app_format_money($totais['direcao'], $moeda)
                        . ' · à espera de cobrança: ' . app_format_money($totais['direcao_agora'], $moeda)
                        . ' · prazo futuro: ' . app_format_money($totais['direcao_futuro'], $moeda),
                ],
                ['Despesas', $totais['despesas'], 'text-danger', 'fa-shopping-cart', null],
                /*
                 * TESOURARIA — dois cartões sobre o dinheiro que já entrou, e
                 * só sobre esse. O resto do painel fala de proveitos e custos;
                 * estes dois falam de saldo: quanto entrou, quanto saiu, e o
                 * que fica na conta depois de pagar o que se deve dele.
                 *
                 * Uma comissão de uma venda ainda por receber não sai deste
                 * dinheiro — sai do que vier. Contá-la aqui dava um saldo de
                 * caixa que não existe.
                 */
                [
                    'Em caixa hoje',
                    $totais['recebido'] - $totais['pago_comercial'] - $totais['despesas'],
                    ($totais['recebido'] - $totais['pago_comercial'] - $totais['despesas']) >= 0 ? 'text-success' : 'text-danger',
                    'fa-university',
                    'recebi ' . app_format_money($totais['recebido'], $moeda)
                        . ' · paguei ' . app_format_money($totais['pago_comercial'] + $totais['despesas'], $moeda)
                        . ' (comerciais ' . app_format_money($totais['pago_comercial'], $moeda)
                        . ' · despesas ' . app_format_money($totais['despesas'], $moeda) . ')',
                ],
                [
                    'Por pagar do que recebi',
                    $totais['devido_do_recebido'],
                    'text-warning', 'fa-hand-o-right',
                    $totais['devido_do_recebido'] > 0
                        ? 'quando pagar, ficam '
                          . app_format_money(
                              $totais['recebido'] - $totais['pago_comercial']
                                  - $totais['despesas'] - $totais['devido_do_recebido'], $moeda)
                          . ' em caixa'
                        : 'nada por pagar do dinheiro que entrou',
                ],
                [
                    'Resultado AGORA', $totais['resultado_agora'],
                    $totais['resultado_agora'] >= 0 ? 'text-success' : 'text-danger', 'fa-balance-scale',
                    'do que já está ou devia estar resolvido',
                ],
                /*
                 * O futuro em dois cartões, não num. São dois calendários com
                 * riscos diferentes: o CPCV é o próximo horizonte, a escritura
                 * pode ser anos depois (no Aura, fim de 2029). Somados, uma
                 * verba de 2029 lia-se como se fosse do próximo trimestre.
                 */
                [
                    'Resultado futuro — CPCV', $totais['resultado_futuro_cpcv'],
                    $totais['resultado_futuro_cpcv'] >= 0 ? 'text-success' : 'text-danger', 'fa-calendar-check-o',
                    'CPCV com mês marcado à frente',
                ],
                [
                    'Resultado futuro — Escrituras', $totais['resultado_futuro_escritura'],
                    $totais['resultado_futuro_escritura'] >= 0 ? 'text-success' : 'text-danger', 'fa-institution',
                    'total previsto: ' . app_format_money($totais['resultado'], $moeda),
                ],
            ];
            /*
             * Cada cartão abre para mostrar DE QUE É FEITO o número: a lista das
             * vendas que o compõem, com a parcela de cada uma.
             *
             * O detalhe é renderizado já aqui, escondido, e não por AJAX: os
             * dados estão todos em $vendas e um pedido extra por cartão só
             * acrescentava carga a uma conta que este mês já foi estrangulada
             * por excesso de base de dados.
             *
             * O índice 5 de cada cartão é a função que decide o que entra na
             * lista e com que valor. Sem ele, o cartão não abre.
             */
            $det = [
                'Recebemos (em caixa)' => function ($v) { return $v['recebido']; },
                'A receber AGORA'      => function ($v) { return $v['a_receber_agora']; },
                'A receber no futuro — CPCV'       => function ($v) { return $v['a_receber_futuro_cpcv']; },
                'A receber no futuro — Escrituras' => function ($v) { return $v['a_receber_futuro_escritura']; },
                'A emitir factura'          => function ($v) { return $v['a_emitir']; },
                'Por pagar do que recebi'   => function ($v) { return $v['devido_do_recebido']; },
                'Perspectiva (por validar)' => function ($v) { return $v['perspectiva']; },
                'Comerciais'           => function ($v) { return $v['comissao_comercial']; },
                $rot_dir               => function ($v) { return $v['direcao_prevista']; },
                'Resultado AGORA'      => function ($v) { return $v['resultado_agora']; },
                'Resultado futuro — CPCV'       => function ($v) { return $v['resultado_futuro_cpcv']; },
                'Resultado futuro — Escrituras' => function ($v) { return $v['resultado_futuro_escritura']; },
            ];

            /*
             * Os cartões agrupam-se pelo PERCURSO DO DINHEIRO, não pela ordem
             * em que foram sendo acrescentados: entra, sai, o que sobra em
             * caixa, e o resultado. Assim lê-se de cima para baixo como a
             * história de uma venda, em vez de catorze números lado a lado.
             *
             * A ordem dentro de "o que entra" é a do circuito real:
             * perspectiva -> a emitir factura -> a receber -> em caixa.
             */
            $seccoes = [
                'O que entra — do pedido à caixa' => [
                    'Perspectiva (por validar)',
                    'A emitir factura',
                    'A receber AGORA',
                    'A receber no futuro — CPCV',
                    'A receber no futuro — Escrituras',
                    'Recebemos (em caixa)',
                ],
                'O que sai' => ['Comerciais', $rot_dir, 'Despesas'],
                'Tesouraria — só o dinheiro que já entrou' => ['Em caixa hoje', 'Por pagar do que recebi'],
                'Resultado' => ['Resultado AGORA', 'Resultado futuro — CPCV', 'Resultado futuro — Escrituras'],
            ];

            // Rede de segurança: um cartão novo que se esqueça de arrumar numa
            // secção aparece à mesma, em vez de desaparecer do painel.
            $arrumados = array_merge(...array_values($seccoes));
            $sobras    = [];
            foreach ($cards as $i => $c) {
                if (!in_array($c[0], $arrumados, true)) {
                    $sobras[] = $c[0];
                }
            }
            if ($sobras) {
                $seccoes['Outros'] = $sobras;
            }

            $desenhar = function ($i, $c) use ($det, $moeda) {
                $abre = isset($det[$c[0]]);
                ?>
                <div class="dps-card">
                    <div class="panel_s">
                        <div class="panel-body <?php echo $abre ? 'dps-card-abre' : ''; ?>"
                             <?php if ($abre) { ?>data-alvo="dps-det-<?php echo $i; ?>" style="cursor:pointer;" title="Clique para ver de que é feito"<?php } ?>>
                            <div class="text-muted">
                                <i class="fa <?php echo $c[3]; ?>"></i> <?php echo $c[0]; ?>
                                <?php if ($abre) { ?><i class="fa fa-chevron-down pull-right text-muted" style="font-size:.8em;margin-top:3px;"></i><?php } ?>
                            </div>
                            <h3 class="no-margin <?php echo $c[2]; ?>"><?php echo app_format_money($c[1], $moeda); ?></h3>
                            <?php if (!empty($c[4])) { ?>
                                <small class="text-muted"><?php echo $c[4]; ?></small>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php
            };

            foreach ($seccoes as $titulo => $etiquetas) {
                $desta = [];
                foreach ($etiquetas as $etiqueta) {
                    foreach ($cards as $i => $c) {
                        if ($c[0] === $etiqueta) {
                            $desta[$i] = $c;
                            break;
                        }
                    }
                }
                if (!$desta) {
                    continue;
                }
                ?>
                <div class="dps-seccao"><?php echo html_escape($titulo); ?></div>
                <div class="dps-cards">
                    <?php foreach ($desta as $i => $c) { $desenhar($i, $c); } ?>
                </div>
            <?php } ?>
        </div>

        <?php
        /* Painéis de detalhe — um por cartão, escondidos até se clicar. */
        foreach ($cards as $i => $c) {
            if (!isset($det[$c[0]])) {
                continue;
            }
            $fn    = $det[$c[0]];
            $itens = [];
            foreach ($vendas as $v) {
                $parcela = (float) $fn($v);
                if (abs($parcela) > 0.004) {
                    $itens[] = [$v, $parcela];
                }
            }
            // Maior contribuição primeiro: é o que se quer ver de relance.
            usort($itens, function ($a, $b) { return abs($b[1]) <=> abs($a[1]); });
            ?>
            <div id="dps-det-<?php echo $i; ?>" class="panel_s dps-card-det" style="display:none;">
                <div class="panel-body">
                    <div class="row mbot15">
                        <div class="col-md-8">
                            <h4 class="no-margin">
                                <i class="fa <?php echo $c[3]; ?>"></i> <?php echo $c[0]; ?>
                                <small class="text-muted">— de que é feito</small>
                            </h4>
                        </div>
                        <div class="col-md-4 text-right">
                            <h4 class="no-margin <?php echo $c[2]; ?>"><?php echo app_format_money($c[1], $moeda); ?></h4>
                        </div>
                    </div>

                    <?php if (empty($itens)) { ?>
                        <p class="text-muted">Nenhuma venda contribui para este valor.</p>
                    <?php } else { ?>
                        <div class="table-responsive">
                        <table class="table table-striped">
                            <thead><tr>
                                <th>Venda</th><th>Empreendimento</th><th>Un.</th><th>Cliente</th><th>Comercial</th>
                                <th>Situação</th><th>Factura</th><th>Prazos</th><th class="text-right">Parcela</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($itens as list($v, $parcela)) { ?>
                                <tr>
                                    <td><a href="<?php echo admin_url('dps_vendas/view/' . (int) $v['id']); ?>">#<?php echo (int) $v['id']; ?></a></td>
                                    <td><?php echo html_escape($v['empreendimento']); ?></td>
                                    <td><?php echo html_escape($v['unidade']); ?></td>
                                    <td><?php echo html_escape($v['cliente']); ?></td>
                                    <td>
                                        <?php echo html_escape($v['comercial_nome']); ?>
                                        <?php if (!empty($v['comercial_0'])) { ?>
                                            <br><span class="label label-default" title="Sem comissão — fica na casa">0%</span>
                                        <?php } elseif (!empty($v['comercial_100'])) { ?>
                                            <br><span class="label label-info" title="Leva 100% do que recebemos">100%</span>
                                        <?php } ?>
                                    </td>
                                    <?php
                                    /*
                                     * Factura emitida ao promotor, por tranche. É a coluna que
                                     * distingue "já pedimos o dinheiro" de "ainda temos de passar
                                     * factura" — sem ela as duas coisas liam-se igual.
                                     */
                                    ?>
                                    <td>
                                        <?php if (!empty($v['recebido_marcado'])) { ?>
                                            <span class="label label-success">recebido</span>
                                            <?php if (!empty($v['recebido_em'])) { ?>
                                                <br><small class="text-muted"><?php echo _d($v['recebido_em']); ?></small>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="label label-warning">por receber</span>
                                        <?php } ?>
                                        <?php
                                        /*
                                         * O nome legível do estado vive no helper do dps_vendas.
                                         * Este painel é outro módulo e não há garantia de que o
                                         * helper esteja carregado — sem a guarda, uma chamada a
                                         * função inexistente matava a página inteira.
                                         */
                                        $estado_txt = function_exists('dps_vendas_nome_estado')
                                            ? dps_vendas_nome_estado($v['estado'])
                                            : ucfirst((string) $v['estado']);
                                        ?>
                                        <br><small class="text-muted"><?php echo html_escape($estado_txt); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $fac = [];
                                        if (!empty($v['fatura_moloni_cpcv'])) {
                                            $fac[] = 'CPCV ' . $v['fatura_moloni_cpcv'];
                                        }
                                        if (!empty($v['fatura_moloni_escritura'])) {
                                            $fac[] = 'Esc. ' . $v['fatura_moloni_escritura'];
                                        }
                                        if ($fac) {
                                            // Escapa-se cada número e só depois se junta com <br>,
                                            // senão a própria quebra de linha saía escapada.
                                            echo '<small>' . implode('<br>', array_map('html_escape', $fac)) . '</small>';
                                        } else {
                                            echo '<span class="label label-danger" title="Pagamento validado mas ainda sem factura">a emitir</span>';
                                        }
                                        ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <?php $fm2 = function ($m) { return $m ? substr($m, 5, 2) . '/' . substr($m, 0, 4) : 'imediato'; }; ?>
                                        <small>CPCV: <?php echo $fm2($v['mes_recebido_cpcv']); ?></small>
                                        <?php if ($v['recebido_escritura'] > 0) { ?>
                                            <br><small>Escritura: <?php echo $fm2($v['mes_recebido_escritura']); ?></small>
                                        <?php } ?>
                                    </td>
                                    <td class="text-right">
                                        <strong><?php echo app_format_money($parcela, $moeda); ?></strong>
                                        <br><small class="text-muted">venda <?php echo app_format_money($v['valor'], $moeda); ?></small>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                            <tfoot><tr>
                                <th colspan="7" class="text-right">Total (<?php echo count($itens); ?> vendas)</th>
                                <th class="text-right"><?php echo app_format_money(array_sum(array_column($itens, 1)), $moeda); ?></th>
                            </tr></tfoot>
                        </table>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <script>
        (function () {
            'use strict';
            // Abre um painel de cada vez: dois abertos deixavam de caber no ecrã
            // e obrigavam a rolar para trás para comparar.
            document.querySelectorAll('.dps-card-abre').forEach(function (cartao) {
                cartao.addEventListener('click', function () {
                    var alvo = document.getElementById(cartao.dataset.alvo);
                    if (!alvo) { return; }
                    var abrir = alvo.style.display === 'none';

                    document.querySelectorAll('.dps-card-det').forEach(function (p) { p.style.display = 'none'; });
                    document.querySelectorAll('.dps-card-abre .fa-chevron-up').forEach(function (s) {
                        s.classList.remove('fa-chevron-up');
                        s.classList.add('fa-chevron-down');
                    });

                    if (abrir) {
                        alvo.style.display = '';
                        var seta = cartao.querySelector('.fa-chevron-down');
                        if (seta) { seta.classList.remove('fa-chevron-down'); seta.classList.add('fa-chevron-up'); }
                        alvo.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });
        })();
        </script>

        <?php if ($totais['estimado'] > 0) { ?>
            <p class="text-muted">
                <i class="fa fa-info-circle"></i>
                <?php echo app_format_money($totais['estimado'], $moeda); ?> do total previsto é
                <strong>estimativa</strong> pela taxa do empreendimento — passa a real assim que lançares o valor do promotor na linha da venda.
                <?php if ($totais['por_receber'] > 0) { ?>
                    <br><i class="fa fa-clock-o"></i>
                    <?php echo app_format_money($totais['por_receber'], $moeda); ?> só entram na data
                    combinada (coluna <strong>Por receber</strong> em baixo).
                <?php } ?>
            </p>
        <?php } ?>

        <!-- Resumo por empreendimento -->
        <div class="panel_s"><div class="panel-body">
            <h4 class="no-margin">Por empreendimento</h4>
            <p class="text-muted">
                <strong>Em caixa</strong> é o que já entrou do promotor; <strong>Por receber</strong> é o que
                só entra na data combinada. <strong>Comerciais</strong>, <strong>Direcção</strong> e
                <strong>Resultado</strong> são da venda inteira — contam tudo o que ela rende, mesmo o que
                ainda não entrou —, por isso o Resultado não é igual a "Em caixa menos o resto".
                Na coluna Direcção, o número grande é o total e o pequeno o que já é devido.
                <strong>A receber agora</strong> é prazo vencido — ou se cobra, ou se marca como recebido —
                e só entra aqui depois de o pagamento estar validado e a venda concluída; até lá é
                <strong>Perspectiva</strong>.
                <strong>A receber futuro</strong> é calendário, não atraso: o Belo Horizonte é todo futuro
                (50% em 12/2026 e 50% em 12/2028, conforme as Regras de Comissão).
                As despesas não entram aqui (não são imputáveis a um empreendimento), só no resultado global lá em cima.
            </p>
            <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr>
                    <th>Empreendimento</th>
                    <th class="text-right">Vendas</th>
                    <th class="text-right">Volume</th>
                    <th class="text-right">Em caixa</th>
                    <th class="text-right">A receber agora</th>
                    <th class="text-right">A receber futuro</th>
                    <th class="text-right">&nbsp;&nbsp;no CPCV</th>
                    <th class="text-right">na escritura</th>
                    <th class="text-right">Comerciais</th>
                    <th class="text-right">Direcção</th>
                    <th class="text-right">Resultado</th>
                </tr></thead>
                <tbody>
                <?php if (empty($resumo['linhas'])) { ?>
                    <tr><td colspan="11" class="text-center text-muted">Sem vendas para os filtros escolhidos.</td></tr>
                <?php } ?>
                <?php foreach ($resumo['linhas'] as $r) { ?>
                    <tr>
                        <td>
                            <?php echo html_escape($r['empreendimento']); ?>
                            <?php if (!empty($r['sem_taxa'])) { ?>
                                <a href="<?php echo admin_url('dps_painel/recebimento'); ?>" class="label label-warning" title="Falta definir a comissão que recebemos">falta a taxa</a>
                            <?php } elseif ($r['taxa_recebida'] !== null) { ?>
                                <small class="text-muted">(<?php echo $pct($r['taxa_recebida']); ?>%)</small>
                            <?php } ?>
                        </td>
                        <td class="text-right"><?php echo (int) $r['vendas']; ?></td>
                        <td class="text-right"><?php echo app_format_money($r['volume'], $moeda); ?></td>
                        <td class="text-right text-success"><?php echo app_format_money($r['recebido'], $moeda); ?></td>
                        <td class="text-right <?php echo $r['a_receber_agora'] > 0 ? 'text-warning' : 'text-muted'; ?>">
                            <?php echo $r['a_receber_agora'] > 0 ? app_format_money($r['a_receber_agora'], $moeda) : '—'; ?>
                        </td>
                        <td class="text-right <?php echo $r['a_receber_futuro'] > 0 ? 'text-info' : 'text-muted'; ?>">
                            <?php echo $r['a_receber_futuro'] > 0 ? app_format_money($r['a_receber_futuro'], $moeda) : '—'; ?>
                        </td>
                        <?php
                        /*
                         * O promotor paga-nos em duas tranches, tal como nós pagamos ao
                         * comercial. Os prazos são os das Regras de Comissão — aqui só se
                         * mostram para se saber em que mês entra cada verba.
                         */
                        $mes_leg = function ($m) {
                            return $m ? substr($m, 5, 2) . '/' . substr($m, 0, 4) : 'imediato';
                        };
                        ?>
                        <td class="text-right">
                            <?php echo app_format_money($r['recebido_cpcv'], $moeda); ?>
                            <br><small class="text-muted"><?php echo $mes_leg($r['mes_cpcv']); ?></small>
                        </td>
                        <td class="text-right">
                            <?php if ((float) $r['recebido_escritura'] > 0) { ?>
                                <?php echo app_format_money($r['recebido_escritura'], $moeda); ?>
                                <br><small class="text-muted"><?php echo $mes_leg($r['mes_escritura']); ?></small>
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                        <td class="text-right"><?php echo app_format_money($r['comerciais'], $moeda); ?></td>
                        <td class="text-right">
                            <?php
                            /*
                             * Duas linhas quando ainda não é tudo devido: sem isto lia-se
                             * "Direcção 0" ao lado de um Resultado que JÁ desconta o override
                             * inteiro, e a linha não fechava aos olhos de quem a lê.
                             */
                            echo app_format_money($r['direcao'], $moeda);
                            if ($r['direcao_prevista'] > $r['direcao']) { ?>
                                <br><small class="text-muted" title="Total do override; o resto é devido na data do CPCV">
                                    de <?php echo app_format_money($r['direcao_prevista'], $moeda); ?>
                                </small>
                            <?php } ?>
                        </td>
                        <td class="text-right <?php echo $r['resultado'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <strong><?php echo app_format_money($r['resultado'], $moeda); ?></strong>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
                <?php if (!empty($resumo['linhas'])) { ?>
                <tfoot><tr>
                    <th>Total</th>
                    <th class="text-right"><?php echo (int) $resumo['totais']['vendas']; ?></th>
                    <th class="text-right"><?php echo app_format_money($resumo['totais']['volume'], $moeda); ?></th>
                    <th class="text-right"><?php echo app_format_money($resumo['totais']['recebido'], $moeda); ?></th>
                    <th class="text-right"><?php echo app_format_money($resumo['totais']['a_receber_agora'], $moeda); ?></th>
                    <th class="text-right"><?php echo app_format_money($resumo['totais']['a_receber_futuro'], $moeda); ?></th>
                    <th class="text-right"><?php echo app_format_money($resumo['totais']['recebido_cpcv'], $moeda); ?></th>
                    <th class="text-right"><?php echo app_format_money($resumo['totais']['recebido_escritura'], $moeda); ?></th>
                    <th class="text-right"><?php echo app_format_money($resumo['totais']['comerciais'], $moeda); ?></th>
                    <th class="text-right">
                        <?php echo app_format_money($resumo['totais']['direcao'], $moeda); ?>
                        <?php if ($resumo['totais']['direcao_prevista'] > $resumo['totais']['direcao']) { ?>
                            <br><small class="text-muted">de <?php echo app_format_money($resumo['totais']['direcao_prevista'], $moeda); ?></small>
                        <?php } ?>
                    </th>
                    <th class="text-right <?php echo $resumo['totais']['resultado'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo app_format_money($resumo['totais']['resultado'], $moeda); ?>
                    </th>
                </tr></tfoot>
                <?php } ?>
            </table>
            </div>
        </div></div>

        <!-- Filtros -->
        <div class="panel_s"><div class="panel-body">
            <?php echo form_open(admin_url('dps_painel'), ['method' => 'get', 'class' => 'form-inline']); ?>
            <div class="row">
                <div class="col-md-2 col-sm-4"><label>Ano</label>
                    <select name="ano" class="form-control" style="width:100%;"><option value="">Todos</option>
                        <?php foreach ($opcoes['anos'] as $a) { ?><option value="<?php echo $a; ?>" <?php echo $filtros['ano'] == $a ? 'selected' : ''; ?>><?php echo $a; ?></option><?php } ?>
                    </select></div>
                <div class="col-md-2 col-sm-4"><label>Mês</label>
                    <select name="mes" class="form-control" style="width:100%;"><option value="">Todos</option>
                        <?php foreach ($f_meses as $n => $nm) { ?><option value="<?php echo $n; ?>" <?php echo $filtros['mes'] == $n ? 'selected' : ''; ?>><?php echo $nm; ?></option><?php } ?>
                    </select></div>
                <div class="col-md-3 col-sm-4"><label>Comercial</label>
                    <select name="comercial" class="form-control" style="width:100%;"><option value="">Todos</option>
                        <?php foreach ($opcoes['comerciais'] as $co) { ?><option value="<?php echo (int) $co['staff_id']; ?>" <?php echo $filtros['comercial'] == $co['staff_id'] ? 'selected' : ''; ?>><?php echo html_escape($co['nome']); ?></option><?php } ?>
                    </select></div>
                <div class="col-md-3 col-sm-6"><label>Empreendimento</label>
                    <select name="empreendimento" class="form-control" style="width:100%;"><option value="">Todos</option>
                        <?php foreach ($opcoes['emps'] as $e) { ?><option value="<?php echo html_escape($e); ?>" <?php echo $filtros['empreendimento'] === $e ? 'selected' : ''; ?>><?php echo html_escape($e); ?></option><?php } ?>
                    </select></div>
                <div class="col-md-2 col-sm-6"><label>Estado</label>
                    <select name="estado" class="form-control" style="width:100%;"><option value="">Todos</option>
                        <?php foreach ($f_estados as $k => $nm) { ?><option value="<?php echo $k; ?>" <?php echo $filtros['estado'] === $k ? 'selected' : ''; ?>><?php echo $nm; ?></option><?php } ?>
                    </select></div>
            </div>
            <?php
            /*
             * Filtro pelo mês em que a DPS RECEBEU — pergunta diferente do
             * Ano/Mês acima, que filtram pela data da VENDA. "Quanto entrou em
             * Julho" e "quanto se vendeu em Julho" são coisas distintas, e
             * misturá-las num só filtro dava respostas erradas às duas.
             *
             * A lista sai das datas realmente marcadas, para não oferecer meses
             * onde nunca entrou nada.
             */
            $meses_recebidos = [];
            foreach ($vendas as $_v) {
                if (!empty($_v['recebido_em'])) {
                    $meses_recebidos[substr($_v['recebido_em'], 0, 7)] = true;
                }
            }
            krsort($meses_recebidos);
            ?>
            <div class="row mtop10">
                <div class="col-md-3 col-sm-6">
                    <label>Mês em que recebemos</label>
                    <select name="mes_recebido" class="form-control" style="width:100%;">
                        <option value="">Todos</option>
                        <?php foreach (array_keys($meses_recebidos) as $mr) { ?>
                            <option value="<?php echo html_escape($mr); ?>" <?php echo ($filtros['mes_recebido'] ?? '') === $mr ? 'selected' : ''; ?>>
                                <?php echo substr($mr, 5, 2) . '/' . substr($mr, 0, 4); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <small class="text-muted">Pela marca de recebido, não pela data da venda.</small>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label>&nbsp;</label>
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="so_recebidas" id="so_recebidas" value="1"
                               <?php echo !empty($filtros['so_recebidas']) ? 'checked' : ''; ?>>
                        <label for="so_recebidas">Só vendas já recebidas</label>
                    </div>
                </div>
            </div>

            <div class="mtop15">
                <button type="submit" class="btn btn-info"><i class="fa fa-filter"></i> Filtrar</button>
                <a href="<?php echo admin_url('dps_painel'); ?>" class="btn btn-default">Limpar</a>
            </div>
            <?php echo form_close(); ?>
        </div></div>

        <!-- Vendas -->
        <div class="panel_s"><div class="panel-body">
            <div class="row mbot15">
                <div class="col-md-7">
                    <h4 class="no-margin">Vendas — o que entra e o que sai</h4>
                </div>
                <div class="col-md-5 text-right">
                    <?php
                    /*
                     * Vai ao Moloni buscar o número da factura já emitida a cada fracção.
                     * Por POST porque escreve: um link com efeitos deixava qualquer clique
                     * perdido mexer em números de facturação.
                     */
                    echo form_open(admin_url('dps_vendas/moloni_sincronizar'), ['style' => 'display:inline;']);
                    ?>
                    <input type="hidden" name="voltar" value="painel">
                    <button type="submit" class="btn btn-default btn-sm"
                            title="Procura no Moloni a factura emitida para cada fracção e preenche o número. Só escreve quando a unidade e o valor batem certo; o resto fica assinalado para confirmar à mão.">
                        <i class="fa fa-file-text-o"></i> Buscar facturas ao Moloni
                    </button>
                    <?php echo form_close(); ?>
                </div>
            </div>
            <p class="text-muted">
                <strong>Recebemos</strong> = o valor real do promotor, se o escreveres na linha; senão é
                <em>estimado</em> pela percentagem do empreendimento.
                <strong>Comercial</strong> vem do mapa de vendas — excepto nos comerciais com acordo de 100%,
                em que é igual ao que recebemos (a DPS ganha zero, é o acordo).
                <?php if ($regras['director_id'] > 0) { ?>
                    <?php
                    /*
                     * Distinção que já gerou confusão: o override incide sobre o que
                     * a DPS recebe do PROMOTOR, não sobre o que o cliente paga. Um
                     * cliente pode ter pagado tudo ao promotor e a nossa comissão só
                     * entrar meses depois — e é a nossa entrada que conta.
                     */
                    ?>
                    <strong>Direcção</strong> é o override de <?php echo $pct($regras['director_pct']); ?>%
                    <?php echo $f_base[$regras['director_base']]; ?>.
                <?php } else { ?>
                    <strong>Direcção</strong> está a zero: não há ninguém escolhido para o override nas
                    <a href="<?php echo admin_url('dps_painel/definicoes'); ?>">Definições</a>.
                <?php } ?>
            </p>

            <?php
            /* Um formulário por linha, fora da <table> (forms aninhados em
             * tabelas não são válidos); os inputs ligam-se por form="pv_ID".
             * Levam os filtros em f_* para o guardar voltar ao mesmo ecrã. */
            foreach ($vendas as $v) {
                echo form_open(admin_url('dps_painel/guardar_venda/' . $v['id']), ['id' => 'pv_' . $v['id']]);
                foreach ($filtros as $fk => $fv) {
                    if ($fv !== null && $fv !== '') {
                        echo '<input type="hidden" name="f_' . html_escape($fk) . '" value="' . html_escape($fv) . '">';
                    }
                }
                echo form_close();
            } ?>

            <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr>
                    <th>#</th><th>Empreendimento</th><th>Un.</th><th>Cliente</th><th>Comercial</th>
                    <th class="text-right">Valor venda</th>
                    <th class="text-right" style="min-width:150px;">Recebemos</th>
                    <th class="text-right">Comercial</th>
                    <th class="text-right">Direcção</th>
                    <th style="min-width:160px;">Recibo emitido?</th>
                    <th class="text-right">Resultado</th>
                    <th></th>
                </tr></thead>
                <tbody>
                <?php if (empty($vendas)) { ?>
                    <tr><td colspan="12" class="text-center text-muted">Sem vendas para os filtros escolhidos.</td></tr>
                <?php } ?>
                <?php foreach ($vendas as $v) { $ff = 'pv_' . $v['id']; ?>
                    <tr>
                        <td><a href="<?php echo admin_url('dps_vendas/view/' . $v['id']); ?>">#<?php echo $v['id']; ?></a></td>
                        <td><?php echo html_escape($v['empreendimento']); ?></td>
                        <td><?php echo html_escape($v['unidade']); ?></td>
                        <td><?php echo html_escape($v['cliente']); ?></td>
                        <td>
                            <?php echo html_escape($v['comercial_nome']); ?>
                            <?php if (!empty($v['comercial_100'])) { ?>
                                <br><span class="label label-info" title="Acordo: leva 100% do que a DPS recebe">100%</span>
                            <?php } ?>
                        </td>
                        <td class="text-right"><?php echo app_format_money($v['valor'], $moeda); ?></td>
                        <td class="text-right">
                            <strong><?php echo app_format_money($v['recebido_previsto'], $moeda); ?></strong><br>
                            <?php if ($v['recebido_fonte'] === 'real') { ?>
                                <span class="label label-success">real</span>
                            <?php } elseif ($v['recebido_fonte'] === 'estimado') { ?>
                                <span class="label label-default" title="Estimado a <?php echo $pct($v['taxa_recebida']); ?>% do valor da venda">estimado</span>
                            <?php } else { ?>
                                <a href="<?php echo admin_url('dps_painel/recebimento'); ?>" class="label label-warning">falta definir a taxa</a>
                            <?php } ?>
                            <?php
                            /*
                             * Quando parte da verba só entra mais tarde, diz-se aqui quanto
                             * e em que mês. Sem isto o número de cima lia-se como dinheiro
                             * em caixa, que é o que estava a inflacionar o override.
                             */
                            /*
                             * Número da factura emitida ao promotor, por tranche.
                             *
                             * Vem do Moloni (botão em cima) ou escrito à mão no mapa de
                             * vendas. Fica aqui, colado ao valor, porque é aqui que se olha
                             * para o dinheiro: uma verba com factura já está titulada, uma
                             * sem factura ainda tem de ser emitida. Sem isto era preciso
                             * abrir a venda para saber.
                             */
                            $facturas = [];
                            if (!empty($v['fatura_moloni_cpcv'])) {
                                $facturas[] = 'CPCV ' . $v['fatura_moloni_cpcv'];
                            }
                            if (!empty($v['fatura_moloni_escritura'])) {
                                $facturas[] = 'escritura ' . $v['fatura_moloni_escritura'];
                            }
                            if ($facturas) { ?>
                                <br><small class="text-muted" title="Factura emitida ao promotor">
                                    <i class="fa fa-file-text-o"></i>
                                    <?php echo html_escape(implode(' · ', $facturas)); ?>
                                </small>
                            <?php }

                            if (!empty($v['recebido_marcado'])) { ?>
                                <br><small class="text-success" title="Marcado como recebido no mapa de vendas">
                                    <i class="fa fa-check"></i> em caixa
                                    <?php if (!empty($v['recebido_em'])) { ?>
                                        (<?php echo _d($v['recebido_em']); ?>)
                                    <?php } ?>
                                </small>
                            <?php } elseif ($v['por_receber'] > 0) {
                                $m = $v['mes_recebido_cpcv'];
                                ?>
                                <br><small class="text-warning" title="Sem a marca de recebido no mapa de vendas — não conta para caixa nem para a Direcção">
                                    por receber: <?php echo app_format_money($v['por_receber'], $moeda); ?>
                                    <?php if (!empty($m)) { ?>
                                        (previsto <?php echo substr($m, 5, 2) . '/' . substr($m, 0, 4); ?>)
                                    <?php } ?>
                                    <br><a href="<?php echo admin_url('dps_vendas'); ?>"><i class="fa fa-check"></i> marcar no mapa de vendas</a>
                                </small>
                            <?php } elseif (!empty($v['perspectiva'])) { ?>
                                <?php
                                /*
                                 * Sem esta linha a venda aparecia com tudo a zero e lia-se
                                 * como erro de cálculo. O dinheiro não desapareceu: está em
                                 * perspectiva porque o pagamento ainda não foi validado.
                                 */
                                ?>
                                <br><small class="text-muted" title="Venda ainda não concluída: falta validar o comprovativo de pagamento. Não conta para 'a receber'.">
                                    <i class="fa fa-hourglass-half"></i>
                                    perspectiva: <?php echo app_format_money($v['perspectiva'], $moeda); ?>
                                    <br><a href="<?php echo admin_url('dps_vendas'); ?>">validar o pagamento no mapa de vendas</a>
                                </small>
                            <?php } ?>
                            <input type="text" form="<?php echo $ff; ?>" name="comissao_recebida" class="form-control input-sm text-right mtop5"
                                   value="<?php echo $v['recebida'] !== null ? number_format($v['recebida'], 2, ',', '.') : ''; ?>"
                                   placeholder="valor real €" title="Valor real recebido do promotor. Em branco = estimativa.">
                        </td>
                        <td class="text-right"><?php echo app_format_money($v['comissao_comercial'], $moeda); ?>
                            <?php // O "pago" incide sobre o valor da célula acima (tranches CPCV/escritura), por isso as duas linhas partilham base. ?>
                            <?php if ($v['comissao_paga'] > 0) { ?><br><small class="text-success" title="Tranches já marcadas como pagas no mapa de vendas (CPCV/escritura), calculadas sobre o valor acima.">pago: <?php echo app_format_money($v['comissao_paga'], $moeda); ?></small><?php } ?>
                        </td>
                        <td class="text-right">
                            <?php echo app_format_money($v['direcao'], $moeda); ?>
                            <?php if ($v['direcao_prevista'] > $v['direcao']) { ?>
                                <br><small class="text-muted" title="Total quando o promotor pagar tudo"><?php echo app_format_money($v['direcao_prevista'], $moeda); ?> no total</small>
                            <?php } ?>
                        </td>
                        <td>
                            <label style="font-weight:400;"><input type="checkbox" form="<?php echo $ff; ?>" name="recibo_emitido" value="1" <?php echo !empty($v['recibo_emitido']) ? 'checked' : ''; ?>> emitido</label>
                            <input type="text" form="<?php echo $ff; ?>" name="recibo_numero" class="form-control input-sm" placeholder="nº recibo"
                                   value="<?php echo html_escape($v['recibo_numero']); ?>" style="width:110px;display:inline-block;">
                        </td>
                        <td class="text-right <?php echo $v['resultado'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <strong><?php echo app_format_money($v['resultado'], $moeda); ?></strong>
                        </td>
                        <td><button type="submit" form="<?php echo $ff; ?>" class="btn btn-info btn-xs" title="Guardar"><i class="fa fa-save"></i></button></td>
                    </tr>
                <?php } ?>
                </tbody>
                <?php if (!empty($vendas)) { ?>
                <tfoot><tr>
                    <th colspan="5">Total</th>
                    <th class="text-right"><?php echo app_format_money($totais['volume'], $moeda); ?></th>
                    <th class="text-right">
                        <?php // As linhas mostram o previsto, o rodapé também — senão não somava. ?>
                        <?php echo app_format_money($totais['recebido_previsto'], $moeda); ?>
                        <?php if ($totais['por_receber'] > 0) { ?>
                            <br><small class="text-warning">por receber: <?php echo app_format_money($totais['por_receber'], $moeda); ?></small>
                        <?php } ?>
                    </th>
                    <th class="text-right"><?php echo app_format_money($totais['comissao_comercial'], $moeda); ?></th>
                    <th class="text-right">
                        <?php echo app_format_money($totais['direcao'], $moeda); ?>
                        <?php if ($totais['direcao_prevista'] > $totais['direcao']) { ?>
                            <br><small class="text-muted"><?php echo app_format_money($totais['direcao_prevista'], $moeda); ?> no total</small>
                        <?php } ?>
                    </th>
                    <th></th>
                    <th class="text-right">
                        <?php // Sem despesas: aqui é só a soma das linhas. O resultado com despesas está no cartão. ?>
                        <?php $res_linhas = $totais['recebido_previsto'] - $totais['comissao_comercial'] - $totais['direcao_prevista']; ?>
                        <span class="<?php echo $res_linhas >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo app_format_money($res_linhas, $moeda); ?></span>
                    </th>
                    <th></th>
                </tr></tfoot>
                <?php } ?>
            </table>
            </div>
        </div></div>

        <!-- Despesas -->
        <div class="panel_s"><div class="panel-body">
            <h4 class="no-margin">Despesas</h4>
            <hr>
            <?php echo form_open_multipart(admin_url('dps_painel/despesa_add'), ['class' => 'form-inline mbot15']); ?>
                <?php // O valor tem de estar no formato do datepicker (option dateformat), não em ISO — o model reconverte com to_sql_date(). ?>
                <input type="text" name="data" class="form-control datepicker" placeholder="Data" value="<?php echo _d(date('Y-m-d')); ?>" style="width:120px;">
                <input type="text" name="categoria" class="form-control" placeholder="Categoria" style="width:140px;">
                <input type="text" name="descricao" class="form-control" placeholder="Descrição" style="width:200px;">
                <input type="text" name="valor" class="form-control" placeholder="Valor €" style="width:100px;">
                <input type="text" name="fatura_numero" class="form-control" placeholder="Nº fatura" style="width:110px;">
                <input type="file" name="doc" class="form-control" accept=".pdf,.jpg,.jpeg,.png" style="width:170px;display:inline-block;">
                <button type="submit" class="btn btn-info"><i class="fa fa-plus"></i> Lançar</button>
            <?php echo form_close(); ?>

            <table class="table table-striped">
                <thead><tr><th>Data</th><th>Categoria</th><th>Descrição</th><th class="text-right">Valor</th><th>Fatura</th><th></th><th></th></tr></thead>
                <tbody>
                <?php if (empty($despesas)) { ?>
                    <tr><td colspan="7" class="text-center text-muted">Sem despesas lançadas.</td></tr>
                <?php } ?>
                <?php foreach ($despesas as $d) { ?>
                    <tr>
                        <td><?php echo _d($d['data']); ?></td>
                        <td><?php echo html_escape($d['categoria']); ?></td>
                        <td><?php echo html_escape($d['descricao']); ?></td>
                        <td class="text-right"><?php echo app_format_money($d['valor'], $moeda); ?></td>
                        <td><?php echo html_escape($d['fatura_numero']); ?></td>
                        <td><?php if (!empty($d['doc'])) { ?><a href="<?php echo admin_url('dps_painel/despesa_doc/' . $d['id']); ?>" class="btn btn-default btn-xs"><i class="fa fa-download"></i></a><?php } ?></td>
                        <td>
                            <?php // Destruição só por POST (o form_open leva o token do Perfex). ?>
                            <?php echo form_open(admin_url('dps_painel/despesa_delete/' . $d['id']), ['style' => 'display:inline;']); ?>
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Eliminar esta despesa?');"><i class="fa fa-remove"></i></button>
                            <?php echo form_close(); ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div></div>

    </div>
</div>
<?php init_tail(); ?>

<script>
/*
 * ORDENAR AS TABELAS DO PAINEL AO CLICAR NO CABEÇALHO.
 *
 * Feito à mão e sem biblioteca de propósito: as tabelas deste painel são
 * construídas em PHP, algumas com formulários dentro das células, e um plugin
 * de datatables reconstruiria as linhas e partia esses formulários. Isto
 * limita-se a trocar a ordem das <tr>, que é o que se quer.
 *
 * O tipo de cada coluna é adivinhado pelo conteúdo, não declarado:
 *   - "16.995,00€" e "-1.234" -> número (ponto = milhares, vírgula = decimal)
 *   - "12/2026" e "2026-07-31" -> data
 *   - o resto -> texto, comparado sem acentos nem maiúsculas
 *
 * O <tfoot> nunca se mexe: é a linha dos totais e tem de ficar em baixo.
 */
(function () {
    function texto(td) {
        return (td ? (td.innerText || td.textContent || '') : '').trim();
    }

    function numero(s) {
        // Tira tudo o que não é dígito, sinal, ponto ou vírgula.
        var t = s.replace(/[^\d,.\-]/g, '');
        if (t === '' || t === '-') { return null; }
        // Formato português: ponto separa milhares, vírgula separa decimais.
        t = t.replace(/\./g, '').replace(',', '.');
        var n = parseFloat(t);
        return isNaN(n) ? null : n;
    }

    function data(s) {
        var m = /^(\d{4})-(\d{2})(?:-(\d{2}))?$/.exec(s);      // 2026-07-31 ou 2026-07
        if (m) { return m[1] + m[2] + (m[3] || '00'); }
        m = /^(\d{2})\/(\d{4})$/.exec(s);                       // 12/2026
        if (m) { return m[2] + m[1] + '00'; }
        m = /^(\d{2})-(\d{2})-(\d{4})$/.exec(s);                // 31-07-2026
        if (m) { return m[3] + m[2] + m[1]; }
        return null;
    }

    function chave(s) {
        var d = data(s);
        if (d !== null) { return { tipo: 'd', v: d }; }
        var n = numero(s);
        if (n !== null && /\d/.test(s)) { return { tipo: 'n', v: n }; }
        return {
            tipo: 't',
            v: s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        };
    }

    function ordenar(tabela, indice, desc) {
        var corpo = tabela.tBodies[0];
        if (!corpo) { return; }

        var linhas = Array.prototype.slice.call(corpo.rows);
        linhas.sort(function (a, b) {
            var ka = chave(texto(a.cells[indice]));
            var kb = chave(texto(b.cells[indice]));

            // Vazios vão sempre para o fim, ordene-se como se ordenar: uma
            // célula sem valor não é "o menor", é "não tem".
            var va = texto(a.cells[indice]) === '' || texto(a.cells[indice]) === '—';
            var vb = texto(b.cells[indice]) === '' || texto(b.cells[indice]) === '—';
            if (va !== vb) { return va ? 1 : -1; }

            var r;
            if (ka.tipo === 'n' && kb.tipo === 'n') { r = ka.v - kb.v; }
            else { r = String(ka.v).localeCompare(String(kb.v), 'pt'); }
            return desc ? -r : r;
        });

        linhas.forEach(function (l) { corpo.appendChild(l); });
    }

    document.querySelectorAll('#wrapper table').forEach(function (tabela) {
        var cab = tabela.tHead;
        if (!cab || !tabela.tBodies[0] || tabela.tBodies[0].rows.length < 2) { return; }

        Array.prototype.slice.call(cab.rows[0].cells).forEach(function (th, i) {
            if (texto(th) === '') { return; }        // coluna de botões: não ordena

            th.style.cursor = 'pointer';
            th.title = 'Ordenar por esta coluna';
            var seta = document.createElement('span');
            seta.style.cssText = 'opacity:.35;font-size:.8em;margin-left:5px;';
            seta.textContent = '↕';
            th.appendChild(seta);

            th.addEventListener('click', function () {
                var desc = th.getAttribute('data-desc') === '1';
                desc = !desc;

                Array.prototype.slice.call(cab.rows[0].cells).forEach(function (outro) {
                    outro.removeAttribute('data-desc');
                    var s = outro.querySelector('span:last-child');
                    if (s && (s.textContent === '↑' || s.textContent === '↓' || s.textContent === '↕')) {
                        s.textContent = '↕';
                        s.style.opacity = '.35';
                    }
                });

                th.setAttribute('data-desc', desc ? '1' : '0');
                seta.textContent = desc ? '↓' : '↑';
                seta.style.opacity = '1';

                ordenar(tabela, i, desc);
            });
        });
    });
})();
</script>
