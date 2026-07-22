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
                                <h4 class="no-margin">Processos de Crédito</h4>
                                <p class="text-muted no-margin">
                                    Abertos automaticamente quando um comercial regista que o cliente
                                    está interessado em proposta.
                                </p>
                            </div>
                            <div class="col-md-6 text-right">
                                <?php if (is_admin() || staff_can('definicoes', 'dps_credito')) { ?>
                                    <a href="<?php echo admin_url('dps_credito/definicoes'); ?>" class="btn btn-default">
                                        <i class="fa fa-cog"></i> Definições
                                    </a>
                                <?php } ?>
                            </div>
                        </div>

                        <hr>

                        <form method="get" action="<?php echo admin_url('dps_credito'); ?>">
                            <div class="row mbot15">
                                <div class="col-md-3">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control selectpicker">
                                        <option value="">Todos</option>
                                        <?php foreach (dps_credito_estados_processo() as $e) { ?>
                                            <option value="<?php echo $e; ?>" <?php echo $filtros['estado'] === $e ? 'selected' : ''; ?>>
                                                <?php echo dps_credito_nome_estado($e); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-default">Filtrar</button>
                                    <a href="<?php echo admin_url('dps_credito'); ?>" class="btn btn-link">Limpar</a>
                                </div>
                            </div>
                        </form>

                        <table class="table dt-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th>Lead</th>
                                    <th>Situação</th>
                                    <th>Banco</th>
                                    <th>Montante</th>
                                    <th>Comercial</th>
                                    <th>Estado</th>
                                    <th>Criado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($processos as $p) { ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo admin_url('dps_credito/view/' . $p['id']); ?>">
                                                <?php echo $p['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo html_escape($p['cliente']); ?></td>
                                        <td>
                                            <?php if (!empty($p['lead_id'])) { ?>
                                                <a href="<?php echo admin_url('leads/index/' . $p['lead_id']); ?>" target="_blank">
                                                    #<?php echo $p['lead_id']; ?>
                                                </a>
                                            <?php } else { ?>
                                                <span class="text-muted">—</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo dps_credito_nome_situacao($p['situacao']); ?></td>
                                        <td><?php echo html_escape($p['banco'] ?: '—'); ?></td>
                                        <td>
                                            <?php echo $p['montante'] !== null
                                                ? app_format_money($p['montante'], get_base_currency())
                                                : '—'; ?>
                                        </td>
                                        <td><?php echo html_escape($p['staff_nome']); ?></td>
                                        <td>
                                            <span class="label <?php echo dps_credito_cor_estado($p['estado']); ?>">
                                                <?php echo dps_credito_nome_estado($p['estado']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo _dt($p['date_created']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <?php if (empty($processos)) { ?>
                            <p class="text-muted text-center">
                                Ainda não há processos de crédito.
                            </p>
                        <?php } ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
