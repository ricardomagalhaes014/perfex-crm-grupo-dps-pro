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
                                <h4 class="no-margin">Comissões</h4>
                                <small class="text-muted">
                                    Entram aqui as vendas <strong>concluídas</strong>. A comissão é paga em duas
                                    parcelas — CPCV e Escritura. Sem mês indicado, a parcela é recebida na hora.
                                </small>
                            </div>
                            <div class="col-md-6 text-right">
                                <?php
                                /*
                                 * Só a direção: mexe em números de facturação. O botão vai por
                                 * POST porque escreve — um link com efeitos deixava qualquer
                                 * clique perdido alterar os números das facturas.
                                 */
                                if (is_admin()) {
                                    echo form_open(admin_url('dps_vendas/moloni_sincronizar'), ['style' => 'display:inline;']);
                                    ?>
                                    <?php /* O campo diz de onde se veio, e garante que o POST não vai vazio. */ ?>
                                    <input type="hidden" name="voltar" value="comissoes">
                                    <button type="submit" class="btn btn-default"
                                            title="Procura no Moloni a factura já emitida para cada fracção e preenche o número. Só escreve quando a unidade e o valor batem certo; o resto fica assinalado para confirmar à mão.">
                                        <i class="fa fa-refresh"></i> Buscar facturas ao Moloni
                                    </button>
                                    <?php
                                    echo form_close();
                                }
                                ?>
                                <a href="<?php echo admin_url('dps_vendas/export_comissoes'
                                    . '?empreendimento=' . urlencode((string) $filtros['empreendimento'])
                                    . '&comercial_id=' . urlencode((string) $filtros['comercial_id'])); ?>"
                                   class="btn btn-default">
                                    <i class="fa fa-file-excel-o"></i> Exportar CSV
                                </a>
                                <a href="<?php echo admin_url('dps_vendas'); ?>" class="btn btn-default">
                                    <i class="fa fa-list"></i> Mapa de vendas
                                </a>
                            </div>
                        </div>

                        <form method="get" action="<?php echo admin_url('dps_vendas/comissoes'); ?>">
                            <div class="row mbot15">
                                <div class="col-md-4">
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
                                    <div class="col-md-4">
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
                                <div class="col-md-4">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-default">Filtrar</button>
                                    <a href="<?php echo admin_url('dps_vendas/comissoes'); ?>" class="btn btn-link">Limpar</a>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

        <?php
        /*
         * ------------------------------------------------------------------
         * a) PREVISÃO POR MÊS
         * ------------------------------------------------------------------
         * Uma linha por mês previsto de recebimento, mais a linha das parcelas
         * sem data (pagas na hora). Os meses já passados ficam antes de um
         * separador do mês corrente, para se ver de relance o que transitou.
         */
        $tem_previsao = !empty($previsao['meses']) || $previsao['sem_data']['previsto'] > 0;
        $moeda        = get_base_currency();
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <h4 class="no-margin">Previsão por mês</h4>
                        <p class="text-muted mbot15">
                            <strong>Por pagar</strong> é o que já é devido (venda concluída).
                            <strong>Retidas</strong> são de vendas já concluídas mas <strong>sem o comprovativo de
                            pagamento validado</strong> — não contam como devidas, porque assentam num pagamento
                            do cliente que ainda ninguém confirmou.
                            <strong>Futuras</strong> são comissões de vendas ainda em CPCV: o valor e a data já
                            estão fixados, mas o pagamento só abre quando a venda for concluída.
                        </p>

                        <?php if (!$tem_previsao) { ?>
                            <p class="text-muted">
                                Ainda não há comissões previstas. Assim que existir uma venda concluída com taxas
                                definidas, ela aparece aqui — e é nas linhas abaixo que se indica o mês de cada parcela.
                            </p>
                        <?php } else { ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mês</th>
                                        <th class="text-right">Previsto</th>
                                        <th class="text-right">Já pago</th>
                                        <th class="text-right">Por pagar</th>
                                        <th class="text-right">Retidas <small class="text-muted">(sem comprovativo)</small></th>
                                        <th class="text-right">Futuras <small class="text-muted">(em CPCV)</small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($previsao['sem_data']['previsto'] > 0) { ?>
                                        <tr>
                                            <td>
                                                <strong>Sem data (imediato)</strong>
                                                <br><small class="text-muted">parcelas pagas na hora</small>
                                            </td>
                                            <td class="text-right"><?php echo app_format_money($previsao['sem_data']['previsto'], $moeda); ?></td>
                                            <td class="text-right"><?php echo app_format_money($previsao['sem_data']['pago'], $moeda); ?></td>
                                            <td class="text-right"><?php echo app_format_money($previsao['sem_data']['por_pagar'], $moeda); ?></td>
                                            <td class="text-right text-muted"><?php echo app_format_money($previsao['sem_data']['retido'], $moeda); ?></td>
                                            <td class="text-right text-muted"><?php echo app_format_money($previsao['sem_data']['futuro'], $moeda); ?></td>
                                        </tr>
                                    <?php } ?>

                                    <?php $separador_posto = false; ?>
                                    <?php foreach ($previsao['meses'] as $mes => $bloco) { ?>
                                        <?php
                                        // O separador entra uma única vez, mesmo antes do primeiro mês que
                                        // já não é passado. Se todos forem passados, nunca aparece.
                                        if (!$separador_posto && $mes >= $mes_corrente) {
                                            $separador_posto = true;
                                            ?>
                                            <tr class="active">
                                                <td colspan="6">
                                                    <small class="text-muted">
                                                        <i class="fa fa-arrow-down"></i>
                                                        A partir do mês corrente (<?php echo dps_vendas_mes_legivel($mes_corrente); ?>)
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <tr<?php echo $mes < $mes_corrente ? ' class="text-muted"' : ''; ?>>
                                            <td>
                                                <?php echo dps_vendas_mes_legivel($mes); ?>
                                                <?php if ($mes === $mes_corrente) { ?>
                                                    <span class="label label-info">mês corrente</span>
                                                <?php } elseif ($mes < $mes_corrente && $bloco['por_pagar'] > 0) { ?>
                                                    <span class="label label-danger" title="Mês já passado com valores por pagar">em atraso</span>
                                                <?php } ?>
                                            </td>
                                            <td class="text-right"><?php echo app_format_money($bloco['previsto'], $moeda); ?></td>
                                            <td class="text-right"><?php echo app_format_money($bloco['pago'], $moeda); ?></td>
                                            <td class="text-right"><?php echo app_format_money($bloco['por_pagar'], $moeda); ?></td>
                                            <td class="text-right <?php echo $bloco['retido'] > 0 ? 'text-warning' : 'text-muted'; ?>">
                                                <?php echo $bloco['retido'] > 0 ? app_format_money($bloco['retido'], $moeda) : '—'; ?>
                                            </td>
                                            <td class="text-right <?php echo $bloco['futuro'] > 0 ? 'text-info' : 'text-muted'; ?>">
                                                <?php echo $bloco['futuro'] > 0 ? app_format_money($bloco['futuro'], $moeda) : '—'; ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-right"><?php echo app_format_money($previsao['totais']['previsto'], $moeda); ?></th>
                                        <th class="text-right"><?php echo app_format_money($previsao['totais']['pago'], $moeda); ?></th>
                                        <th class="text-right"><?php echo app_format_money($previsao['totais']['por_pagar'], $moeda); ?></th>
                                        <th class="text-right"><?php echo app_format_money($previsao['totais']['retido'], $moeda); ?></th>
                                        <th class="text-right"><?php echo app_format_money($previsao['totais']['futuro'], $moeda); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>

        <?php
        /*
         * ------------------------------------------------------------------
         * b) POR PAGAR — agrupado por comercial, uma linha por parcela
         * ------------------------------------------------------------------
         */
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row mbot15">
                            <div class="col-md-8">
                                <h4 class="no-margin">Por pagar</h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <h4 class="no-margin">
                                    <?php echo app_format_money($previsao['totais']['por_pagar'], $moeda); ?>
                                </h4>
                                <?php if ($previsao['totais']['retido'] > 0) { ?>
                                    <small class="text-warning">
                                        + <?php echo app_format_money($previsao['totais']['retido'], $moeda); ?>
                                        retidas sem comprovativo validado
                                    </small>
                                <?php } ?>
                            </div>
                        </div>

                        <?php if (empty($por_pagar)) { ?>
                            <p class="text-muted">Não há parcelas por pagar.</p>
                        <?php } ?>

                        <?php foreach ($por_pagar as $grupo) { ?>
                            <div class="mbot25">
                                <h5>
                                    <?php echo html_escape($grupo['nome']); ?>
                                    <span class="pull-right">
                                        Subtotal: <strong><?php echo app_format_money($grupo['total'], $moeda); ?></strong>
                                    </span>
                                </h5>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Venda</th>
                                            <th>Empreendimento</th>
                                            <th>Unidade</th>
                                            <th>Parcela</th>
                                            <th class="text-right">Valor</th>
                                            <th style="min-width:170px;">Mês previsto</th>
                                            <th style="min-width:220px;">Pagamento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupo['linhas'] as $l) { ?>
                                            <?php $venda = $l['venda']; ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo admin_url('dps_vendas/view/' . $l['venda_id']); ?>">
                                                        #<?php echo $l['venda_id']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo html_escape($l['empreendimento']); ?></td>
                                                <td><?php echo html_escape($l['unidade']); ?></td>
                                                <td>
                                                    <span class="label label-primary"><?php echo html_escape($l['etiqueta']); ?></span>
                                                    <small class="text-muted"><?php echo $l['taxa']; ?>%</small>
                                                </td>
                                                <td class="text-right"><strong><?php echo app_format_money($l['valor'], $moeda); ?></strong></td>
                                                <td>
                                                    <?php if (is_admin()) { ?>
                                                        <?php echo form_open(admin_url('dps_vendas/definir_mes_parcela/' . $l['venda_id']), ['style' => 'margin:0;display:flex;gap:4px;align-items:center;']); ?>
                                                            <input type="hidden" name="parcela" value="<?php echo html_escape($l['parcela']); ?>">
                                                            <?php /* Deixar em branco = recebida na hora (regra do dono). */ ?>
                                                            <input type="month" name="mes" value="<?php echo html_escape($l['mes']); ?>"
                                                                   class="form-control input-sm" style="width:125px;padding:2px 5px;height:auto;"
                                                                   title="Em branco = recebimento imediato">
                                                            <button type="submit" class="btn btn-default btn-xs" title="Guardar mês previsto">
                                                                <i class="fa fa-check"></i>
                                                            </button>
                                                        <?php echo form_close(); ?>
                                                    <?php } else { ?>
                                                        <?php echo $l['mes'] !== '' ? dps_vendas_mes_legivel($l['mes']) : '<span class="text-muted">imediato</span>'; ?>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    /*
                                                     * Uma parcela só se paga quando a comissão já é devida.
                                                     * Se falta alguma coisa no circuito, mostra-se o motivo em
                                                     * vez do botão — e, quando o que falta é o recibo, o
                                                     * comercial resolve-o aqui mesmo.
                                                     */
                                                    $e_dono = (int) $l['comercial_id'] === (int) get_staff_user_id();
                                                    ?>
                                                    <?php if ($l['bloqueio'] !== '') { ?>
                                                        <span class="label label-default"><?php echo html_escape($l['bloqueio']); ?></span>
                                                        <?php if (empty($venda['comissao_recibo_doc']) && ($e_dono || is_admin())) { ?>
                                                            <?php echo form_open_multipart(admin_url('dps_vendas/comissao_recibo/' . $l['venda_id']), ['style' => 'margin:4px 0 0;display:flex;gap:4px;align-items:center;']); ?>
                                                                <input type="file" name="recibo_file" accept=".pdf,.jpg,.jpeg,.png" required
                                                                       style="font-size:.75em;max-width:140px;">
                                                                <button type="submit" class="btn btn-info btn-xs" title="Anexar recibo da comissão">
                                                                    <i class="fa fa-upload"></i>
                                                                </button>
                                                            <?php echo form_close(); ?>
                                                        <?php } ?>
                                                    <?php } elseif (is_admin()) { ?>
                                                        <?php echo form_open(admin_url('dps_vendas/marcar_parcela_paga/' . $l['venda_id']), ['style' => 'margin:0;display:flex;gap:4px;align-items:center;']); ?>
                                                            <input type="hidden" name="parcela" value="<?php echo html_escape($l['parcela']); ?>">
                                                            <input type="date" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>"
                                                                   class="form-control input-sm" style="width:135px;padding:2px 5px;height:auto;">
                                                            <button type="submit" class="btn btn-success btn-xs"
                                                                    onclick="return confirm('Marcar a parcela <?php echo html_escape($l['etiqueta']); ?> da venda #<?php echo (int) $l['venda_id']; ?> como paga?');">
                                                                Marcar paga
                                                            </button>
                                                        <?php echo form_close(); ?>
                                                    <?php } else { ?>
                                                        <span class="label label-warning">Aguarda pagamento da DPS</span>
                                                    <?php } ?>

                                                    <?php if (!empty($venda['comissao_recibo_doc'])) { ?>
                                                        <a href="<?php echo admin_url('dps_vendas/download_doc/' . (int) $venda['comissao_recibo_doc']); ?>"
                                                           class="btn btn-default btn-xs" title="Descarregar recibo da comissão">
                                                            <i class="fa fa-file-text-o"></i> Recibo
                                                        </a>
                                                        <?php if (is_admin()) { ?>
                                                            <a href="<?php echo admin_url('dps_vendas/comissao_remover_recibo/' . (int) $l['venda_id']); ?>"
                                                               class="btn btn-danger btn-xs"
                                                               title="Recibo anexado por engano — remover"
                                                               onclick="return confirm('Remover o recibo da venda #<?php echo (int) $l['venda_id']; ?> (<?php echo html_escape($venda['unidade']); ?>)? A comissão deixa de estar pronta a pagar até ser anexado o correto.');">
                                                                <i class="fa fa-times"></i>
                                                            </a>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>

        <?php
        /*
         * ------------------------------------------------------------------
         * c) PAGAS — o que já saiu, com a data e o botão de desfazer
         * ------------------------------------------------------------------
         * O "Marcar recebida" (fecho contabilístico da comissão da venda)
         * aparece uma só vez por venda: são as parcelas que se pagam, mas o
         * estado "recebida" é da venda inteira.
         */
        $recebida_mostrada = [];
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row mbot15">
                            <div class="col-md-8">
                                <h4 class="no-margin">Pagas</h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <h4 class="no-margin">
                                    <?php echo app_format_money($previsao['totais']['pago'], $moeda); ?>
                                </h4>
                            </div>
                        </div>

                        <?php if (empty($pagas)) { ?>
                            <p class="text-muted">Ainda não foi paga nenhuma parcela.</p>
                        <?php } ?>

                        <?php foreach ($pagas as $grupo) { ?>
                            <div class="mbot25">
                                <h5>
                                    <?php echo html_escape($grupo['nome']); ?>
                                    <span class="pull-right">
                                        Subtotal: <strong><?php echo app_format_money($grupo['total'], $moeda); ?></strong>
                                    </span>
                                </h5>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Venda</th>
                                            <th>Empreendimento</th>
                                            <th>Unidade</th>
                                            <th>Parcela</th>
                                            <th class="text-right">Valor</th>
                                            <th>Mês previsto</th>
                                            <th>Pago em</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupo['linhas'] as $l) { ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo admin_url('dps_vendas/view/' . $l['venda_id']); ?>">
                                                        #<?php echo $l['venda_id']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo html_escape($l['empreendimento']); ?></td>
                                                <td><?php echo html_escape($l['unidade']); ?></td>
                                                <td>
                                                    <span class="label label-primary"><?php echo html_escape($l['etiqueta']); ?></span>
                                                    <small class="text-muted"><?php echo $l['taxa']; ?>%</small>
                                                </td>
                                                <td class="text-right"><strong><?php echo app_format_money($l['valor'], $moeda); ?></strong></td>
                                                <td>
                                                    <?php echo $l['mes'] !== '' ? dps_vendas_mes_legivel($l['mes']) : '<span class="text-muted">imediato</span>'; ?>
                                                </td>
                                                <td>
                                                    <span class="label label-success">
                                                        <?php echo $l['pago_em'] ? _d($l['pago_em']) : 'pago'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-right" style="white-space:nowrap;">
                                                    <?php
                                                    $venda = $l['venda'];
                                                    $pode_fechar = $venda['comissao_estado'] === 'a_receber'
                                                        && Dps_vendas_model::falta_para_comissao($venda) === ''
                                                        && (is_admin() || staff_can('marcar_recebido', 'dps_vendas'))
                                                        && !isset($recebida_mostrada[$l['venda_id']]);
                                                    ?>
                                                    <?php if ($pode_fechar) { ?>
                                                        <?php $recebida_mostrada[$l['venda_id']] = true; ?>
                                                        <?php echo form_open(admin_url('dps_vendas/marcar_comissao_recebida/' . $l['venda_id']), ['style' => 'margin:0;display:inline-block;']); ?>
                                                            <button type="submit" class="btn btn-default btn-xs" title="Fechar a comissão desta venda"
                                                                    onclick="return confirm('Fechar a comissão da venda #<?php echo (int) $l['venda_id']; ?> como RECEBIDA? Não é possível desfazer.');">
                                                                Marcar recebida
                                                            </button>
                                                        <?php echo form_close(); ?>
                                                    <?php } ?>
                                                    <?php if (is_admin()) { ?>
                                                        <?php echo form_open(admin_url('dps_vendas/desmarcar_parcela_paga/' . $l['venda_id']), ['style' => 'margin:0;display:inline-block;']); ?>
                                                            <input type="hidden" name="parcela" value="<?php echo html_escape($l['parcela']); ?>">
                                                            <button type="submit" class="btn btn-default btn-xs" title="Desfazer o pagamento desta parcela"
                                                                    onclick="return confirm('Desmarcar o pagamento da parcela <?php echo html_escape($l['etiqueta']); ?> da venda #<?php echo (int) $l['venda_id']; ?>?');">
                                                                <i class="fa fa-undo"></i>
                                                            </button>
                                                        <?php echo form_close(); ?>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>

        <?php
        /*
         * RETIDAS — vendas concluídas cujo comprovativo de pagamento ainda não
         * foi validado pela direção.
         *
         * Ficam à parte do "Por pagar" de propósito: prometer uma comissão
         * assente num pagamento que ninguém confirmou é a maneira mais rápida
         * de dever dinheiro que não entrou. Aqui vê-se exactamente o que falta
         * em cada uma, com link para resolver.
         */
        ?>
        <?php if (!empty($retidas)) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">

                            <div class="row mbot15">
                                <div class="col-md-8">
                                    <h4 class="no-margin">
                                        Retidas <small class="text-muted">— falta validar o pagamento</small>
                                    </h4>
                                    <p class="text-muted no-margin">
                                        Vendas concluídas sem comprovativo de pagamento validado. Entram em
                                        "Por pagar" assim que a direção validar o comprovativo.
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <h4 class="no-margin text-warning">
                                        <?php echo app_format_money($previsao['totais']['retido'], $moeda); ?>
                                    </h4>
                                </div>
                            </div>

                            <?php foreach ($retidas as $grupo) { ?>
                                <div class="mbot15">
                                    <div class="row">
                                        <div class="col-md-8"><strong><?php echo html_escape($grupo['nome']); ?></strong></div>
                                        <div class="col-md-4 text-right text-muted">
                                            Subtotal: <?php echo app_format_money($grupo['total'], $moeda); ?>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead><tr>
                                                <th>Venda</th><th>Empreendimento</th><th>Unidade</th>
                                                <th>Parcela</th><th class="text-right">Valor</th>
                                                <th>O que falta</th><th></th>
                                            </tr></thead>
                                            <tbody>
                                            <?php foreach ($grupo['linhas'] as $l) { ?>
                                                <tr>
                                                    <td><a href="<?php echo admin_url('dps_vendas/view/' . (int) $l['venda_id']); ?>">#<?php echo (int) $l['venda_id']; ?></a></td>
                                                    <td><?php echo html_escape($l['empreendimento']); ?></td>
                                                    <td><?php echo html_escape($l['unidade']); ?></td>
                                                    <td><span class="label label-default"><?php echo html_escape($l['etiqueta']); ?></span></td>
                                                    <td class="text-right"><?php echo app_format_money($l['valor'], $moeda); ?></td>
                                                    <td>
                                                        <span class="label label-warning"><?php echo html_escape($l['bloqueio']); ?></span>
                                                    </td>
                                                    <td class="text-right">
                                                        <a href="<?php echo admin_url('dps_vendas/view/' . (int) $l['venda_id']); ?>"
                                                           class="btn btn-default btn-xs">Abrir ficha</a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <?php
        /*
         * FUTURAS — comissões de vendas ainda em CPCV.
         *
         * Bloco só de leitura, de propósito: o valor e a data já estão fixados
         * pela regra do empreendimento, mas o circuito de pagamento (concluir,
         * comprovativo, validação, recibo) ainda não abriu. Pôr aqui botões de
         * "marcar como pago" era convidar ao erro.
         */
        ?>
        <?php if (!empty($futuras)) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">

                            <div class="row mbot15">
                                <div class="col-md-8">
                                    <h4 class="no-margin">Futuras <small class="text-muted">— vendas em CPCV</small></h4>
                                    <p class="text-muted no-margin">
                                        Já contratadas e com data marcada. Entram em "Por pagar" quando a venda
                                        passar a Concluído e o circuito do recibo estiver cumprido.
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    <h4 class="no-margin text-info">
                                        <?php echo app_format_money($previsao['totais']['futuro'], $moeda); ?>
                                    </h4>
                                </div>
                            </div>

                            <?php foreach ($futuras as $grupo) { ?>
                                <div class="mbot15">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <strong><?php echo html_escape($grupo['nome']); ?></strong>
                                        </div>
                                        <div class="col-md-4 text-right text-muted">
                                            Subtotal: <?php echo app_format_money($grupo['total'], $moeda); ?>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Venda</th>
                                                    <th>Empreendimento</th>
                                                    <th>Unidade</th>
                                                    <th>Parcela</th>
                                                    <th class="text-right">Valor</th>
                                                    <th>Mês previsto</th>
                                                    <th>Estado da venda</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($grupo['linhas'] as $l) { ?>
                                                    <tr>
                                                        <td>
                                                            <a href="<?php echo admin_url('dps_vendas/view/' . (int) $l['venda_id']); ?>">
                                                                #<?php echo (int) $l['venda_id']; ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo html_escape($l['empreendimento']); ?></td>
                                                        <td><?php echo html_escape($l['unidade']); ?></td>
                                                        <td>
                                                            <span class="label label-default"><?php echo html_escape($l['etiqueta']); ?></span>
                                                            <small class="text-muted"><?php echo rtrim(rtrim(number_format((float) $l['taxa'], 2, ',', ''), '0'), ','); ?>%</small>
                                                        </td>
                                                        <td class="text-right"><?php echo app_format_money($l['valor'], $moeda); ?></td>
                                                        <td style="white-space:nowrap;">
                                                            <?php if ($l['mes'] !== '') { ?>
                                                                <strong><?php echo dps_vendas_mes_legivel($l['mes']); ?></strong>
                                                                <?php if (!empty($l['mes_da_regra'])) { ?>
                                                                    <br><small class="text-muted" title="A venda não tem mês próprio; vem da regra do empreendimento">
                                                                        pela regra do empreendimento
                                                                    </small>
                                                                <?php } ?>
                                                            <?php } else { ?>
                                                                <span class="text-muted">sem data —</span>
                                                                <a href="<?php echo admin_url('dps_vendas/regras'); ?>"><small>definir na regra</small></a>
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <span class="label <?php echo dps_vendas_cor_estado($l['venda']['estado']); ?>">
                                                                <?php echo dps_vendas_nome_estado($l['venda']['estado']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

    </div>
</div>
<?php init_tail(); ?>
