<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row mbot15">
                            <div class="col-md-6">
                                <a href="<?php echo admin_url('dps_vendas/form'); ?>" class="btn btn-info">
                                    <i class="fa fa-plus"></i> Nova Venda
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <?php if (is_admin()) { ?>
                                    <a href="<?php echo admin_url('dps_vendas/sincronizar_clientes'); ?>"
                                       class="btn btn-default"
                                       onclick="return confirm('Passar a cliente todas as vendas concluídas que ainda não o são?\n\nPode correr isto as vezes que quiser — não duplica ninguém.');"
                                       data-toggle="tooltip"
                                       title="Cria a ficha de cliente das vendas já fechadas. As novas passam sozinhas ao serem concluídas.">
                                        <i class="fa fa-user-plus"></i> Passar concluídas a clientes
                                    </a>
                                <?php } ?>
                                <a href="<?php echo admin_url('dps_vendas/comissoes'); ?>" class="btn btn-default">
                                    <i class="fa fa-eur"></i> Quadro de Comissões
                                </a>
                            </div>
                        </div>

                        <hr>

                        <form method="get" action="<?php echo admin_url('dps_vendas'); ?>">
                            <div class="row mbot15">
                                <div class="col-md-3">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control selectpicker">
                                        <option value="">Todos</option>
                                        <?php foreach (Dps_vendas_model::$fluxo as $estado) { ?>
                                            <option value="<?php echo $estado; ?>" <?php echo $filtros['estado'] === $estado ? 'selected' : ''; ?>>
                                                <?php echo dps_vendas_nome_estado($estado); ?>
                                            </option>
                                        <?php } ?>
                                        <option value="historico" <?php echo $filtros['estado'] === 'historico' ? 'selected' : ''; ?>>
                                            Histórico (sem workflow)
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Empreendimento</label>
                                    <select name="empreendimento" class="form-control selectpicker" data-live-search="true">
                                        <option value="">Todos</option>
                                        <?php foreach ($empreendimentos as $emp) { ?>
                                            <option value="<?php echo html_escape($emp); ?>" <?php echo $filtros['empreendimento'] === $emp ? 'selected' : ''; ?>>
                                                <?php echo html_escape($emp); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <?php if ($pode_ver_todas) { ?>
                                    <div class="col-md-3">
                                        <label>Comercial</label>
                                        <select name="comercial_id" class="form-control selectpicker" data-live-search="true">
                                            <option value="">Todos</option>
                                            <?php foreach ($comerciais as $c) { ?>
                                                <option value="<?php echo $c['staffid']; ?>" <?php echo (string) $filtros['comercial_id'] === (string) $c['staffid'] ? 'selected' : ''; ?>>
                                                    <?php echo html_escape($c['nome']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                <?php } ?>
                                <div class="col-md-3">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-default">Filtrar</button>
                                    <a href="<?php echo admin_url('dps_vendas'); ?>" class="btn btn-link">Limpar</a>
                                </div>
                            </div>
                        </form>

                        <table class="table dt-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Empreendimento</th>
                                    <th>Unidade</th>
                                    <th>Cliente</th>
                                    <th>Valor</th>
                                    <th>Comercial</th>
                                    <th>Estado</th>
                                    <?php if (is_admin()) { ?>
                                        <th title="Marque quando o promotor pagar à DPS">Recebido</th>
                                    <?php } ?>
                                    <th>Data</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                /*
                                 * A LISTA SAI AGRUPADA POR EMPREENDIMENTO.
                                 *
                                 * Corrida, com 100 e tal vendas de cinco
                                 * empreendimentos misturadas, obrigava a ler
                                 * linha a linha para encontrar uma fracção.
                                 *
                                 * Regras de ordem, pedidas pelo dono
                                 * (04/08/2026):
                                 *   - o empreendimento com venda mais recente
                                 *     fica em cima;
                                 *   - o Belo Horizonte vai sempre para o fim,
                                 *     é o maior e o menos consultado;
                                 *   - dentro de cada um, a mais recente
                                 *     primeiro;
                                 *   - as canceladas não entram. Continuam a
                                 *     existir e a ver-se pelo filtro de estado
                                 *     — o que se tira é o ruído da lista de
                                 *     todos os dias.
                                 */
                                $grupos = [];

                                foreach ($vendas as $_v) {
                                    if (($_v['estado'] ?? '') === 'cancelado') {
                                        continue;
                                    }
                                    $grupos[trim((string) $_v['empreendimento']) ?: 'Sem empreendimento'][] = $_v;
                                }

                                $recencia = [];
                                foreach ($grupos as $ge => $lista) {
                                    usort($lista, function ($a, $b) {
                                        return [$b['data_venda'] ?? '', (int) $b['id']]
                                           <=> [$a['data_venda'] ?? '', (int) $a['id']];
                                    });
                                    $grupos[$ge]   = $lista;
                                    $recencia[$ge] = $lista[0]['data_venda'] ?? '';
                                }

                                uksort($grupos, function ($a, $b) use ($recencia) {
                                    $bh_a = stripos($a, 'belo') !== false ? 1 : 0;
                                    $bh_b = stripos($b, 'belo') !== false ? 1 : 0;

                                    if ($bh_a !== $bh_b) {
                                        return $bh_a <=> $bh_b;   // Belo Horizonte por último
                                    }

                                    return ($recencia[$b] ?? '') <=> ($recencia[$a] ?? '');
                                });
                                ?>
                                <?php foreach ($grupos as $g_nome => $g_lista) { ?>
                                    <tr style="background:rgba(0,0,0,.04);">
                                        <td colspan="20">
                                            <strong><?php echo html_escape($g_nome); ?></strong>
                                            <small class="text-muted">
                                                — <?php echo count($g_lista); ?> venda(s)<?php
                                                $soma_g = array_sum(array_map(function ($x) { return (float) $x['valor']; }, $g_lista));
                                                echo ' · ' . app_format_money($soma_g, get_base_currency()); ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php foreach ($g_lista as $v) { ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo admin_url('dps_vendas/view/' . $v['id']); ?>">
                                                <?php echo $v['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo html_escape($v['empreendimento']); ?></td>
                                        <td><?php echo html_escape($v['unidade']); ?>
                                            <?php
                                            /*
                                             * Número da factura emitida ao promotor, por baixo da
                                             * unidade. Fica na lista e não só na ficha: quem varre o
                                             * mapa à procura do que falta facturar não tem de abrir
                                             * venda a venda para descobrir.
                                             */
                                            $fa = [];
                                            if (!empty($v['fatura_moloni_cpcv'])) {
                                                $fa[] = $v['fatura_moloni_cpcv'];
                                            }
                                            if (!empty($v['fatura_moloni_escritura'])) {
                                                $fa[] = $v['fatura_moloni_escritura'];
                                            }
                                            if ($fa) { ?>
                                                <br><small class="text-muted" title="Factura emitida ao promotor">
                                                    <i class="fa fa-file-text-o"></i> <?php echo html_escape(implode(' · ', $fa)); ?>
                                                </small>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo html_escape($v['cliente']); ?></td>
                                        <td><?php echo app_format_money($v['valor'], get_base_currency()); ?></td>
                                        <td><?php echo html_escape($v['comercial_nome']); ?></td>
                                        <td>
                                            <?php if (is_admin()) { ?>
                                                <?php
                                                // Mudança de estado directamente na lista — SÓ admin:
                                                // as vendas entram como "Pedido — Por Confirmar" e é a
                                                // direção que as promove. O form_open leva o CSRF.
                                                echo form_open(admin_url('dps_vendas/change_status/' . $v['id']), [
                                                    'class' => 'dps-estado-form',
                                                    'style' => 'margin:0;',
                                                ]);
                                                ?>
                                                <input type="hidden" name="voltar" value="lista">
                                                <select name="estado" class="form-control input-sm dps-estado-sel"
                                                        style="min-width:150px;padding:3px 6px;height:auto;font-size:.85em;"
                                                        onchange="this.form.submit();">
                                                    <?php foreach (Dps_vendas_model::$fluxo as $estado) { ?>
                                                        <option value="<?php echo $estado; ?>" <?php echo $v['estado'] === $estado ? 'selected' : ''; ?>>
                                                            <?php echo dps_vendas_nome_estado($estado); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                                <?php echo form_close(); ?>
                                            <?php } else { ?>
                                                <span class="label <?php echo dps_vendas_cor_estado($v['estado']); ?>">
                                                    <?php echo dps_vendas_nome_estado($v['estado']); ?>
                                                </span>
                                            <?php } ?>
                                            <?php if (!empty($v['cpcv_assinado'])) { ?>
                                                <br><span class="label label-success" style="font-size:.7em;" title="CPCV assinado">CPCV assinado</span>
                                            <?php } ?>
                                            <?php if (!empty($v['pago'])) { ?>
                                                <span class="label label-success" style="font-size:.7em;" title="Pagamento confirmado">Pago</span>
                                            <?php } ?>
                                        </td>
                                        <?php if (is_admin()) { ?>
                                            <?php
                                            /*
                                             * RECEBIDO DA DPS — só admin.
                                             *
                                             * É esta marca que faz o dinheiro entrar em caixa no
                                             * Painel do Negócio. Antes o painel adivinhava pelo mês
                                             * previsto na regra; um prazo no papel não é dinheiro na
                                             * conta, por isso passou a exigir confirmação à mão.
                                             *
                                             * POST (leva o CSRF do form_open) porque é escrita, e leva
                                             * a query string para voltar à lista com os mesmos filtros.
                                             */
                                            $qs_actual = $_SERVER['QUERY_STRING'] ?? '';
                                            ?>
                                            <td style="white-space:nowrap;">
                                                <?php if (!empty($v['recebido_dps'])) { ?>
                                                    <?php echo form_open(admin_url('dps_vendas/desmarcar_recebido/' . $v['id']), ['style' => 'margin:0;']); ?>
                                                        <input type="hidden" name="voltar" value="lista">
                                                        <input type="hidden" name="qs" value="<?php echo html_escape($qs_actual); ?>">
                                                        <button type="submit" class="btn btn-success btn-xs"
                                                                title="Recebido em <?php echo $v['recebido_dps_em'] ? _d($v['recebido_dps_em']) : '—'; ?> — clique para retirar a marca"
                                                                onclick="return confirm('Retirar a marca de recebido da venda #<?php echo (int) $v['id']; ?>?');">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                    <?php echo form_close(); ?>
                                                    <br><small class="text-muted"><?php echo $v['recebido_dps_em'] ? _d($v['recebido_dps_em']) : ''; ?></small>
                                                <?php } else { ?>
                                                    <?php echo form_open(admin_url('dps_vendas/marcar_recebido/' . $v['id']), ['style' => 'margin:0;']); ?>
                                                        <input type="hidden" name="voltar" value="lista">
                                                        <input type="hidden" name="qs" value="<?php echo html_escape($qs_actual); ?>">
                                                        <input type="date" name="data" value="<?php echo date('Y-m-d'); ?>"
                                                               class="input-sm" style="width:118px;padding:2px 4px;font-size:.8em;"
                                                               title="Data em que a DPS recebeu">
                                                        <button type="submit" class="btn btn-default btn-xs"
                                                                title="Marcar como recebido pela DPS">
                                                            <i class="fa fa-check text-muted"></i>
                                                        </button>
                                                    <?php echo form_close(); ?>
                                                <?php } ?>
                                            </td>
                                        <?php } ?>
                                        <?php /* A comissão saiu do mapa de vendas de propósito: este mapa é o
                                                estado comercial das frações. As comissões vivem no seu próprio
                                                quadro (Vendas &gt; Comissões), que só entra quando a venda fica
                                                concluída. Misturar as duas coisas era o que tornava esta lista
                                                confusa. */ ?>
                                        <td><?php echo $v['data_venda'] ? _d($v['data_venda']) : '—'; ?></td>
                                        <td class="text-right" style="white-space:nowrap;">
                                            <a href="<?php echo admin_url('dps_vendas/view/' . $v['id']); ?>"
                                               class="btn btn-default btn-xs" title="Abrir ficha">
                                                <i class="fa fa-folder-open-o"></i>
                                            </a>
                                            <?php if (is_admin() || staff_can('edit', 'dps_vendas')) { ?>
                                                <a href="<?php echo admin_url('dps_vendas/form/' . $v['id']); ?>"
                                                   class="btn btn-default btn-xs" title="Editar">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (is_admin()) { ?>
                                                <a href="<?php echo admin_url('dps_vendas/delete/' . $v['id']); ?>"
                                                   class="btn btn-danger btn-xs _delete" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } /* vendas do grupo */ ?>
                                <?php } /* grupos */ ?>
                            </tbody>
                        </table>

                        <?php if (empty($vendas)) { ?>
                            <p class="text-muted text-center">Não há vendas que correspondam a este filtro.</p>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
