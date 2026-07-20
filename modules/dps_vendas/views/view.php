<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">

                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    Venda #<?php echo $venda['id']; ?>
                                    <span class="label <?php echo dps_vendas_cor_estado($venda['estado']); ?>">
                                        <?php echo dps_vendas_nome_estado($venda['estado']); ?>
                                    </span>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?php echo admin_url('dps_vendas/form/' . $venda['id']); ?>" class="btn btn-default btn-sm">
                                    <i class="fa fa-pencil"></i> Editar
                                </a>
                            </div>
                        </div>

                        <hr>

                        <?php if (empty($venda['estado'])) { ?>
                            <div class="alert alert-info">
                                Esta venda vem do simulador e é anterior ao circuito de estados.
                                Ao mudar o estado, entra no circuito a partir de <strong>Pendente</strong>.
                            </div>
                        <?php } ?>

                        <table class="table table-borderless">
                            <tr>
                                <td width="35%"><strong>Empreendimento</strong></td>
                                <td><?php echo html_escape($venda['empreendimento']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Unidade / Fracção</strong></td>
                                <td><?php echo html_escape($venda['unidade']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Valor</strong></td>
                                <td><?php echo app_format_money($venda['valor'], get_base_currency()); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Comercial</strong></td>
                                <td><?php echo html_escape($venda['comercial_nome']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Data da venda</strong></td>
                                <td><?php echo $venda['data_venda'] ? _d($venda['data_venda']) : '—'; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Origem</strong></td>
                                <td><?php echo ucfirst($venda['origem'] ?? 'manual'); ?></td>
                            </tr>
                            <?php if (!empty($venda['lead_id'])) { ?>
                                <tr>
                                    <td><strong>Lead</strong></td>
                                    <td>
                                        <a href="<?php echo admin_url('leads/index/' . $venda['lead_id']); ?>" target="_blank">
                                            #<?php echo $venda['lead_id']; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>

                        <h5>Cliente</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td width="35%"><strong>Nome</strong></td>
                                <td><?php echo html_escape($venda['cliente']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Morada</strong></td>
                                <td><?php echo html_escape($venda['cliente_morada'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Telefone</strong></td>
                                <td><?php echo html_escape($venda['cliente_telefone'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email</strong></td>
                                <td><?php echo html_escape($venda['cliente_email'] ?: '—'); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Regime civil</strong></td>
                                <td><?php echo html_escape($venda['regime_civil'] ?: '—'); ?></td>
                            </tr>
                        </table>

                        <h5>Documentos</h5>
                        <?php if (empty($docs)) { ?>
                            <p class="text-muted">Sem documentos anexados.</p>
                        <?php } else { ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Ficheiro</th>
                                        <th>Data</th>
                                        <th class="text-right">Acções</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($docs as $doc) { ?>
                                        <tr>
                                            <td><?php echo dps_vendas_nome_doc($doc['tipo']); ?></td>
                                            <td><?php echo html_escape($doc['original_name']); ?></td>
                                            <td><?php echo _dt($doc['dateadded']); ?></td>
                                            <td class="text-right">
                                                <?php if ($pode_download || (int) $venda['staff_id'] === (int) get_staff_user_id()) { ?>
                                                    <a href="<?php echo admin_url('dps_vendas/download_doc/' . $doc['id']); ?>"
                                                       class="btn btn-default btn-xs">
                                                        <i class="fa fa-download"></i> Descarregar
                                                    </a>
                                                <?php } else { ?>
                                                    <span class="text-muted">sem permissão</span>
                                                <?php } ?>
                                                <a href="<?php echo admin_url('dps_vendas/delete_doc/' . $doc['id']); ?>"
                                                   class="btn btn-danger btn-xs _delete">
                                                    <i class="fa fa-remove"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>

                    </div>
                </div>
            </div>

            <div class="col-md-4">

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Comissão</h5>
                        <hr>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Taxa aplicada</strong></td>
                                <td><?php echo $calculo['taxa']; ?>%</td>
                            </tr>
                            <tr>
                                <td><strong>Origem da taxa</strong></td>
                                <td>
                                    <?php echo $calculo['fonte'] === 'regra' ? 'Regra do empreendimento' : 'Definida na venda'; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Valor calculado</strong></td>
                                <td><?php echo app_format_money($calculo['valor'], get_base_currency()); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Estado</strong></td>
                                <td>
                                    <?php
                                    $estados_comissao = [
                                        'na'        => 'Ainda não devida',
                                        'a_receber' => 'A receber',
                                        'recebida'  => 'Recebida',
                                    ];
                                    echo $estados_comissao[$venda['comissao_estado']] ?? $venda['comissao_estado'];
                                    ?>
                                </td>
                            </tr>
                        </table>
                        <?php if ($calculo['taxa'] <= 0) { ?>
                            <div class="alert alert-warning">
                                <strong>Sem taxa definida.</strong>
                                Não há regra de comissão para "<?php echo html_escape($venda['empreendimento']); ?>"
                                e a venda também não tem taxa própria. Esta venda não poderá passar a
                                <strong>Recebido</strong> enquanto isso não for resolvido.
                                <?php if (is_admin() || staff_can('gerir_regras', 'dps_vendas')) { ?>
                                    <br><a href="<?php echo admin_url('dps_vendas/regras'); ?>">Definir regra</a>
                                <?php } ?>
                            </div>
                        <?php } elseif ($venda['comissao_estado'] === 'na') { ?>
                            <p class="text-muted">
                                <small>A comissão é fixada quando a venda passa a <strong>Recebido</strong>.</small>
                            </p>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Mudar estado</h5>
                        <hr>
                        <?php echo form_open(admin_url('dps_vendas/change_status/' . $venda['id'])); ?>
                        <div class="form-group">
                            <select name="estado" class="form-control" required>
                                <option value="">Escolher...</option>
                                <?php foreach ($fluxo as $estado) { ?>
                                    <?php if ($estado !== $venda['estado']) { ?>
                                        <option value="<?php echo $estado; ?>">
                                            <?php echo dps_vendas_nome_estado($estado); ?>
                                        </option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <textarea name="nota" class="form-control" rows="2" placeholder="Nota (opcional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-info btn-block">Actualizar estado</button>
                        <?php echo form_close(); ?>
                        <p class="text-muted mtop10">
                            <small>
                                Só se avança uma etapa de cada vez. Recuar é permitido e fica registado.
                            </small>
                        </p>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin">Histórico</h5>
                        <hr>
                        <?php if (empty($historico)) { ?>
                            <p class="text-muted">Sem movimentos.</p>
                        <?php } else { ?>
                            <ul class="list-unstyled">
                                <?php foreach ($historico as $h) { ?>
                                    <li class="mbot10">
                                        <strong><?php echo dps_vendas_nome_estado($h['estado_para']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo $h['estado_de'] ? 'de ' . dps_vendas_nome_estado($h['estado_de']) . ' · ' : ''; ?>
                                            <?php echo html_escape($h['staff_nome']); ?> ·
                                            <?php echo _dt($h['dateadded']); ?>
                                        </small>
                                        <?php if (!empty($h['nota'])) { ?>
                                            <br><em><?php echo html_escape($h['nota']); ?></em>
                                        <?php } ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
<?php init_tail(); ?>
