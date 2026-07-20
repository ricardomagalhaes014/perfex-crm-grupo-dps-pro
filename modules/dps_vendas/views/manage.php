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
                                <a href="<?php echo admin_url('dps_vendas/comissoes'); ?>" class="btn btn-default">
                                    Comissões a Receber
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
                                                <?php echo ucfirst(str_replace('_', ' ', $estado)); ?>
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
                                    <th>Comissão</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendas as $v) { ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo admin_url('dps_vendas/view/' . $v['id']); ?>">
                                                <?php echo $v['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo html_escape($v['empreendimento']); ?></td>
                                        <td><?php echo html_escape($v['unidade']); ?></td>
                                        <td><?php echo html_escape($v['cliente']); ?></td>
                                        <td><?php echo app_format_money($v['valor'], get_base_currency()); ?></td>
                                        <td><?php echo html_escape($v['comercial_nome']); ?></td>
                                        <td>
                                            <?php if (empty($v['estado'])) { ?>
                                                <span class="label label-default">Histórico</span>
                                            <?php } else { ?>
                                                <span class="label <?php echo dps_vendas_cor_estado($v['estado']); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $v['estado'])); ?>
                                                </span>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($v['comissao_estado'] !== 'na') { ?>
                                                <?php echo app_format_money($v['comissao_total'], get_base_currency()); ?>
                                                <small class="text-muted">
                                                    (<?php echo $v['comissao_estado'] === 'recebida' ? 'recebida' : 'a receber'; ?>)
                                                </small>
                                            <?php } else { ?>
                                                <span class="text-muted">—</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo $v['data_venda'] ? _d($v['data_venda']) : '—'; ?></td>
                                    </tr>
                                <?php } ?>
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
