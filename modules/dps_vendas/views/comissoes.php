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
                                <h4 class="no-margin">Comissões a Receber</h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('dps_vendas/export_comissoes'); ?>" class="btn btn-default">
                                    <i class="fa fa-file-excel-o"></i> Exportar CSV
                                </a>
                            </div>
                        </div>

                        <hr>

                        <?php if (empty($por_comercial)) { ?>
                            <p class="text-muted text-center">
                                Ainda não há comissões. Uma venda só gera comissão quando passa a <strong>Recebido</strong>.
                            </p>
                        <?php } ?>

                        <?php foreach ($por_comercial as $grupo) { ?>
                            <div class="mbot25">
                                <h5>
                                    <?php echo html_escape($grupo['nome']); ?>
                                    <span class="pull-right">
                                        Total: <strong><?php echo app_format_money($grupo['total'], get_base_currency()); ?></strong>
                                    </span>
                                </h5>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Venda</th>
                                            <th>Empreendimento</th>
                                            <th>Unidade</th>
                                            <th>Cliente</th>
                                            <th>Valor</th>
                                            <th>Taxa</th>
                                            <th>Comissão</th>
                                            <th>Estado</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupo['linhas'] as $c) { ?>
                                            <tr>
                                                <td>
                                                    <a href="<?php echo admin_url('dps_vendas/view/' . $c['id']); ?>">
                                                        #<?php echo $c['id']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo html_escape($c['empreendimento']); ?></td>
                                                <td><?php echo html_escape($c['unidade']); ?></td>
                                                <td><?php echo html_escape($c['cliente']); ?></td>
                                                <td><?php echo app_format_money($c['valor'], get_base_currency()); ?></td>
                                                <td><?php echo $c['taxa']; ?>%</td>
                                                <td><strong><?php echo app_format_money($c['comissao_total'], get_base_currency()); ?></strong></td>
                                                <td>
                                                    <?php if ($c['comissao_estado'] === 'recebida') { ?>
                                                        <span class="label label-success">Recebida</span>
                                                    <?php } else { ?>
                                                        <span class="label label-warning">A receber</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-right">
                                                    <?php if ($c['comissao_estado'] === 'a_receber' && (is_admin() || staff_can('marcar_recebido', 'dps_vendas'))) { ?>
                                                        <a href="<?php echo admin_url('dps_vendas/marcar_comissao_recebida/' . $c['id']); ?>"
                                                           class="btn btn-success btn-xs">
                                                            Marcar paga
                                                        </a>
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
    </div>
</div>
<?php init_tail(); ?>
