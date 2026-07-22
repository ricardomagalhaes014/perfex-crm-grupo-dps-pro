<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Comissões — DPS Crédito</h4>
                        <p class="text-muted">
                            Processos de crédito concluídos com <strong>sucesso</strong>.
                            A comissão é <?php echo rtrim(rtrim(number_format(dps_credito_taxa_comissao(), 2, ',', ''), '0'), ','); ?>%
                            sobre o valor do crédito recebido.
                        </p>
                        <hr>

                        <?php if (empty($por_comercial)) { ?>
                            <p class="text-muted text-center">Ainda não há comissões de crédito.</p>
                        <?php } ?>

                        <?php foreach ($por_comercial as $grupo) { ?>
                            <div class="mbot25">
                                <h5>
                                    <?php echo html_escape($grupo['nome']); ?>
                                    <span class="pull-right">Total: <strong><?php echo app_format_money($grupo['total'], get_base_currency()); ?></strong></span>
                                </h5>
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th><th>Cliente</th><th>Lead</th>
                                            <th>Valor do crédito</th><th>Taxa</th><th>Comissão</th><th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grupo['linhas'] as $c) { ?>
                                            <tr>
                                                <td><a href="<?php echo admin_url('dps_credito/view/' . $c['id']); ?>">#<?php echo $c['id']; ?></a></td>
                                                <td><?php echo html_escape($c['cliente']); ?></td>
                                                <td>
                                                    <?php if (!empty($c['lead_id'])) { ?>
                                                        <a href="<?php echo admin_url('leads/index/' . $c['lead_id']); ?>" target="_blank">#<?php echo $c['lead_id']; ?></a>
                                                    <?php } else { echo '—'; } ?>
                                                </td>
                                                <td><?php echo app_format_money($c['valor_credito'] ?? 0, get_base_currency()); ?></td>
                                                <td><?php echo rtrim(rtrim(number_format((float) ($c['taxa'] ?? 0), 2, ',', ''), '0'), ','); ?>%</td>
                                                <td><strong><?php echo app_format_money($c['comissao_total'] ?? 0, get_base_currency()); ?></strong></td>
                                                <td><?php echo $c['dateupdated'] ? _dt($c['dateupdated']) : _dt($c['date_created']); ?></td>
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
