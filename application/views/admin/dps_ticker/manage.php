<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><i class="fa fa-bullhorn"></i> Nova notícia do rodapé</h4>
                        <hr>
                        <?php echo form_open(admin_url('dps_ticker')); ?>
                        <div class="form-group">
                            <label>Frase / notícia</label>
                            <textarea name="mensagem" class="form-control" rows="3" required
                                      placeholder="Ex.: Reunião geral sexta-feira às 10h."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Adicionar ao rodapé</button>
                        <?php echo form_close(); ?>
                        <p class="text-muted mtop15">
                            As frases activas passam no rodapé de todas as páginas do CRM,
                            15 segundos cada, em ciclo contínuo.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Notícias</h4>
                        <hr>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Frase</th>
                                    <th width="90">Estado</th>
                                    <th width="140">Adicionada</th>
                                    <th width="130"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mensagens)) { ?>
                                    <tr><td colspan="4" class="text-muted">Ainda não há notícias.</td></tr>
                                <?php } ?>
                                <?php foreach ($mensagens as $m) { ?>
                                    <tr>
                                        <td><?php echo html_escape($m['mensagem']); ?></td>
                                        <td>
                                            <?php if ($m['ativo']) { ?>
                                                <span class="label label-success">Activa</span>
                                            <?php } else { ?>
                                                <span class="label label-default">Parada</span>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo _dt($m['dateadded']); ?></td>
                                        <td class="text-right">
                                            <a href="<?php echo admin_url('dps_ticker/toggle/' . $m['id']); ?>"
                                               class="btn btn-default btn-xs">
                                                <?php echo $m['ativo'] ? 'Parar' : 'Activar'; ?>
                                            </a>
                                            <a href="<?php echo admin_url('dps_ticker/delete/' . $m['id']); ?>"
                                               class="btn btn-danger btn-xs _delete">Apagar</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
