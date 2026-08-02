<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <?php if (is_admin()) { ?>
            <!-- ============ ADMIN: gestão dos guiões ============ -->
            <div class="row">
                <div class="col-md-5">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin">
                                <?php echo $guiao_editar ? 'Editar guião' : 'Novo guião'; ?>
                            </h4>
                            <hr>

                            <?php
                            $url_guardar = $guiao_editar
                                ? admin_url('dps_automacao/guiao_guardar/' . (int) $guiao_editar['id'])
                                : admin_url('dps_automacao/guiao_guardar');
                            echo form_open($url_guardar);
                            ?>

                            <div class="form-group">
                                <label for="nome">Nome <span class="text-danger">*</span></label>
                                <input type="text" name="nome" id="nome" class="form-control" required
                                       value="<?php echo $guiao_editar ? html_escape($guiao_editar['nome']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label for="descricao">Descrição (o que a Sofia fará — visível ao comercial)</label>
                                <textarea name="descricao" id="descricao" rows="3" class="form-control"><?php echo $guiao_editar ? html_escape($guiao_editar['descricao']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="instrucoes">Instruções de execução (texto/guião para a Sofia)</label>
                                <textarea name="instrucoes" id="instrucoes" rows="6" class="form-control"><?php echo $guiao_editar ? html_escape($guiao_editar['instrucoes']) : ''; ?></textarea>
                            </div>

                            <div class="form-group">
                                <div class="checkbox checkbox-primary">
                                    <input type="checkbox" name="ativo" id="ativo" value="1"
                                        <?php echo (!$guiao_editar || (int) $guiao_editar['ativo'] === 1) ? 'checked' : ''; ?>>
                                    <label for="ativo">Ativo (visível aos comerciais)</label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-info">
                                <?php echo $guiao_editar ? 'Guardar alterações' : 'Criar guião'; ?>
                            </button>
                            <?php if ($guiao_editar) { ?>
                                <a href="<?php echo admin_url('dps_automacao/guioes'); ?>" class="btn btn-default">Cancelar</a>
                            <?php } ?>

                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin">Guiões da Sofia</h4>
                            <hr>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Estado</th>
                                            <th class="text-right">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($guioes)) { ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">Ainda não há guiões criados.</td>
                                            </tr>
                                        <?php } ?>
                                        <?php foreach ($guioes as $guiao) { ?>
                                            <tr>
                                                <td><?php echo html_escape($guiao['nome']); ?></td>
                                                <td>
                                                    <small><?php echo html_escape((string) $guiao['descricao']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ((int) $guiao['ativo'] === 1) { ?>
                                                        <span class="label label-success">Ativo</span>
                                                    <?php } else { ?>
                                                        <span class="label label-default">Inativo</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-right">
                                                    <a href="<?php echo admin_url('dps_automacao/guioes?editar=' . (int) $guiao['id']); ?>"
                                                       class="btn btn-default btn-xs">
                                                        <i class="fa fa-pencil"></i>
                                                    </a>
                                                    <?php
                                                    // Apagar é POST com CSRF — nunca um link GET. A mensagem de
                                                    // confirmação é estática para não injetar o nome no JS inline.
                                                    echo form_open(
                                                        admin_url('dps_automacao/guiao_apagar/' . (int) $guiao['id']),
                                                        ['style' => 'display:inline', 'onsubmit' => "return confirm('Apagar este guião? Se houver comerciais a usá-lo, será apenas desativado.');"]
                                                    );
                                                    ?>
                                                    <button type="submit" class="btn btn-danger btn-xs">
                                                        <i class="fa fa-remove"></i>
                                                    </button>
                                                    <?php echo form_close(); ?>
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

        <?php } else { ?>
            <!-- ============ COMERCIAL: escolher um guião ============ -->
            <div class="row">
                <div class="col-md-10 col-md-offset-1">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="no-margin">Guiões da Sofia</h4>
                            <p class="text-muted">
                                Escolha o guião que a Sofia deve usar nas suas leads.
                                Pode mudar quando quiser — a nova escolha substitui a anterior.
                            </p>
                            <?php if ($escolha && !empty($escolha['guiao_nome'])) { ?>
                                <div class="alert alert-info">
                                    Guião atual: <strong><?php echo html_escape($escolha['guiao_nome']); ?></strong>
                                    (escolhido em <?php echo html_escape(_dt($escolha['dateadded'])); ?>)
                                </div>
                            <?php } ?>
                            <hr>

                            <?php if (empty($guioes)) { ?>
                                <p class="text-muted">Ainda não há guiões disponíveis. Fale com a administração.</p>
                            <?php } ?>

                            <div class="row">
                                <?php foreach ($guioes as $guiao) {
                                    $e_o_escolhido = $escolha && (int) $escolha['guiao_id'] === (int) $guiao['id'];
                                ?>
                                    <div class="col-md-4">
                                        <div class="panel_s" style="<?php echo $e_o_escolhido ? 'border:2px solid #84c529;' : ''; ?>">
                                            <div class="panel-body">
                                                <h5>
                                                    <?php echo html_escape($guiao['nome']); ?>
                                                    <?php if ($e_o_escolhido) { ?>
                                                        <span class="label label-success pull-right">Em uso</span>
                                                    <?php } ?>
                                                </h5>
                                                <p class="text-muted">
                                                    <?php echo html_escape((string) $guiao['descricao']); ?>
                                                </p>
                                                <?php if (!$e_o_escolhido) { ?>
                                                    <?php echo form_open(admin_url('dps_automacao/guiao_escolher')); ?>
                                                    <input type="hidden" name="guiao_id" value="<?php echo (int) $guiao['id']; ?>">
                                                    <button type="submit" class="btn btn-info btn-block">
                                                        Usar este guião nas minhas leads
                                                    </button>
                                                    <?php echo form_close(); ?>
                                                <?php } else { ?>
                                                    <button type="button" class="btn btn-default btn-block" disabled>
                                                        É o guião em uso
                                                    </button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

    </div>
</div>
<?php init_tail(); ?>
