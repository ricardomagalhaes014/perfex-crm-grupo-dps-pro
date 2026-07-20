<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Regras de Comissão</h4>
                        <p class="text-muted">
                            A taxa definida aqui é aplicada automaticamente às novas vendas do empreendimento.
                            Cada venda guarda a taxa que lhe foi aplicada, por isso alterar uma regra
                            <strong>não</strong> altera vendas já registadas.
                        </p>
                        <hr>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Empreendimento</th>
                                    <th>Taxa</th>
                                    <th>CPCV</th>
                                    <th>Escritura</th>
                                    <th>Activa</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($regras as $r) { ?>
                                    <?php $por_definir = (float) $r['taxa'] <= 0; ?>
                                    <tr <?php echo $por_definir ? 'class="warning"' : ''; ?>>
                                        <td>
                                            <?php echo html_escape($r['empreendimento']); ?>
                                            <?php if (!empty($r['notas'])) { ?>
                                                <br><small class="text-muted"><?php echo html_escape($r['notas']); ?></small>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($por_definir) { ?>
                                                <span class="label label-warning">Por definir</span>
                                            <?php } else { ?>
                                                <?php echo rtrim(rtrim(number_format($r['taxa'], 4, ',', ''), '0'), ','); ?>%
                                            <?php } ?>
                                        </td>
                                        <td><?php echo $r['cpcv_taxa'] !== null ? rtrim(rtrim(number_format($r['cpcv_taxa'], 4, ',', ''), '0'), ',') . '%' : '—'; ?></td>
                                        <td><?php echo $r['escritura_taxa'] !== null ? rtrim(rtrim(number_format($r['escritura_taxa'], 4, ',', ''), '0'), ',') . '%' : '—'; ?></td>
                                        <td>
                                            <?php if ($r['ativo']) { ?>
                                                <span class="label label-success">Sim</span>
                                            <?php } else { ?>
                                                <span class="label label-default">Não</span>
                                            <?php } ?>
                                        </td>
                                        <td class="text-right">
                                            <button type="button" class="btn btn-default btn-xs editar-regra"
                                                    data-id="<?php echo $r['id']; ?>"
                                                    data-empreendimento="<?php echo html_escape($r['empreendimento']); ?>"
                                                    data-taxa="<?php echo $r['taxa']; ?>"
                                                    data-cpcv="<?php echo $r['cpcv_taxa']; ?>"
                                                    data-escritura="<?php echo $r['escritura_taxa']; ?>"
                                                    data-ativo="<?php echo $r['ativo']; ?>"
                                                    data-notas="<?php echo html_escape($r['notas']); ?>">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <a href="<?php echo admin_url('dps_vendas/delete_regra/' . $r['id']); ?>"
                                               class="btn btn-danger btn-xs _delete">
                                                <i class="fa fa-remove"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <?php if (empty($regras)) { ?>
                            <p class="text-muted text-center">Ainda não há regras definidas.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin" id="titulo-form-regra">Nova regra</h5>
                        <hr>

                        <?php echo form_open(admin_url('dps_vendas/regras'), ['id' => 'form-regra']); ?>
                        <input type="hidden" name="id" id="regra-id" value="">

                        <div class="form-group">
                            <label class="control-label">Empreendimento <span class="text-danger">*</span></label>
                            <input type="text" name="empreendimento" id="regra-empreendimento" class="form-control"
                                   list="lista-empreendimentos" required>
                            <datalist id="lista-empreendimentos">
                                <?php foreach ($empreendimentos as $emp) { ?>
                                    <option value="<?php echo html_escape($emp); ?>"></option>
                                <?php } ?>
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Taxa total (%) <span class="text-danger">*</span></label>
                            <input type="text" name="taxa" id="regra-taxa" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Taxa no CPCV (%)</label>
                                    <input type="text" name="cpcv_taxa" id="regra-cpcv" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Taxa na escritura (%)</label>
                                    <input type="text" name="escritura_taxa" id="regra-escritura" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Notas</label>
                            <textarea name="notas" id="regra-notas" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="ativo" id="regra-ativo" checked>
                            <label for="regra-ativo">Regra activa</label>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-info btn-block">Guardar regra</button>
                        <button type="button" class="btn btn-link btn-block" id="cancelar-edicao" style="display:none;">
                            Cancelar edição
                        </button>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function () {
    // Editar carrega a linha no mesmo formulário, em vez de abrir um modal —
    // são poucos campos e evita uma vista extra.
    document.querySelectorAll('.editar-regra').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var d = this.dataset;
            document.getElementById('regra-id').value = d.id;
            document.getElementById('regra-empreendimento').value = d.empreendimento;
            document.getElementById('regra-taxa').value = d.taxa;
            document.getElementById('regra-cpcv').value = d.cpcv || '';
            document.getElementById('regra-escritura').value = d.escritura || '';
            document.getElementById('regra-notas').value = d.notas || '';
            document.getElementById('regra-ativo').checked = d.ativo === '1';
            document.getElementById('titulo-form-regra').textContent = 'Editar regra';
            document.getElementById('cancelar-edicao').style.display = '';
            window.scrollTo(0, 0);
        });
    });

    document.getElementById('cancelar-edicao').addEventListener('click', function () {
        document.getElementById('form-regra').reset();
        document.getElementById('regra-id').value = '';
        document.getElementById('titulo-form-regra').textContent = 'Nova regra';
        this.style.display = 'none';
    });
})();
</script>
