<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
// Formata 5.0000 como "5" e 1.2500 como "1,25" — as taxas escrevem-se curtas.
$pct = function ($n) {
    return rtrim(rtrim(number_format((float) $n, 4, ',', ''), '0'), ',');
};
?>
<div id="wrapper">
    <div class="content">

        <div class="row mbot15">
            <div class="col-md-8">
                <h4 class="no-margin"><i class="fa fa-briefcase"></i> Comissões a receber</h4>
            </div>
            <div class="col-md-4 text-right">
                <a href="<?php echo admin_url('dps_painel'); ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Voltar ao painel
                </a>
            </div>
        </div>

        <div class="row">

            <div class="col-md-7">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">O que a DPS recebe, por empreendimento</h4>
                        <p class="text-muted">
                            Percentagem <strong>sobre o valor da venda</strong> que o promotor paga à DPS.
                            É a contrapartida das Regras de Comissão (o que pagamos ao comercial) e serve para
                            estimar o recebido enquanto o valor real não é lançado venda a venda.
                            <strong>Este quadro só é visível aqui</strong> — os comerciais nunca lhe chegam.
                        </p>
                        <hr>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Empreendimento</th>
                                    <th>Recebemos</th>
                                    <th>No CPCV</th>
                                    <th>Na Escritura</th>
                                    <th>Prazos <small class="text-muted">(das Regras de Comissão)</small></th>
                                    <th>Notas</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recebimentos as $r) { ?>
                                    <?php $por_definir = (float) $r['taxa_recebida'] <= 0; ?>
                                    <tr <?php echo $por_definir ? 'class="warning"' : ''; ?>>
                                        <td><?php echo html_escape($r['empreendimento']); ?></td>
                                        <td>
                                            <?php if ($por_definir) { ?>
                                                <span class="label label-warning">Por definir</span>
                                            <?php } else { ?>
                                                <strong><?php echo $pct($r['taxa_recebida']); ?>%</strong>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($por_definir) { ?>
                                                <span class="text-muted">—</span>
                                            <?php } else { ?>
                                                <?php echo $pct($r['cpcv_efectivo']); ?>%
                                                <br><small class="text-muted"><?php echo $pct($r['taxa_recebida'] * $r['cpcv_efectivo'] / 100); ?>% da venda</small>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if ($por_definir || (float) $r['escritura_efectivo'] <= 0) { ?>
                                                <span class="text-muted">—</span>
                                            <?php } else { ?>
                                                <?php echo $pct($r['escritura_efectivo']); ?>%
                                                <br><small class="text-muted"><?php echo $pct($r['taxa_recebida'] * $r['escritura_efectivo'] / 100); ?>% da venda</small>
                                            <?php } ?>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <?php
                                            /*
                                             * Os prazos editam-se no formulário ao lado, mas GRAVAM-SE nas
                                             * Regras de Comissão — um sítio só, para não haver duas datas
                                             * diferentes para o mesmo empreendimento.
                                             */
                                            $fm = function ($m) {
                                                return $m ? substr($m, 5, 2) . '/' . substr($m, 0, 4) : null;
                                            };
                                            ?>
                                            <?php if ($fm($r['mes_cpcv'])) { ?>
                                                <small>CPCV: <strong><?php echo $fm($r['mes_cpcv']); ?></strong></small>
                                            <?php } else { ?>
                                                <small class="text-muted">CPCV: na conclusão</small>
                                            <?php } ?>
                                            <br>
                                            <?php if ($fm($r['mes_escritura'])) { ?>
                                                <small>Escritura: <strong><?php echo $fm($r['mes_escritura']); ?></strong></small>
                                            <?php } else { ?>
                                                <small class="text-muted">Escritura: —</small>
                                            <?php } ?>
                                        </td>
                                        <td><small class="text-muted"><?php echo html_escape($r['notas']); ?></small></td>
                                        <td class="text-right" style="white-space:nowrap;">
                                            <button type="button" class="btn btn-default btn-xs editar-receb"
                                                    data-id="<?php echo (int) $r['id']; ?>"
                                                    data-empreendimento="<?php echo html_escape($r['empreendimento']); ?>"
                                                    data-taxa="<?php echo $pct($r['taxa_recebida']); ?>"
                                                    data-cpcv="<?php echo isset($r['cpcv_pct']) ? $pct($r['cpcv_pct']) : ''; ?>"
                                                    data-escritura="<?php echo isset($r['escritura_pct']) ? $pct($r['escritura_pct']) : ''; ?>"
                                                    data-notas="<?php echo html_escape($r['notas']); ?>"
                                                    data-mescpcv="<?php echo html_escape($r['mes_cpcv'] ?? ''); ?>"
                                                    data-mesescritura="<?php echo html_escape($r['mes_escritura'] ?? ''); ?>">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <?php // Apagar é destrutivo: form POST (leva o token do Perfex), não um link GET. ?>
                                            <?php echo form_open(admin_url('dps_painel/recebimento_delete'), ['style' => 'display:inline;']); ?>
                                                <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-xs"
                                                        onclick="return confirm('Eliminar esta linha?');">
                                                    <i class="fa fa-remove"></i>
                                                </button>
                                            <?php echo form_close(); ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <?php if (empty($recebimentos)) { ?>
                            <p class="text-muted text-center">Ainda não há empreendimentos definidos.</p>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="no-margin" id="titulo-form-receb">Nova comissão a receber</h5>
                        <hr>

                        <?php echo form_open(admin_url('dps_painel/recebimento'), ['id' => 'form-receb']); ?>
                        <input type="hidden" name="id" id="receb-id" value="">

                        <div class="form-group">
                            <label class="control-label">Empreendimento <span class="text-danger">*</span></label>
                            <input type="text" name="empreendimento" id="receb-empreendimento" class="form-control"
                                   list="lista-emps-receb" required>
                            <datalist id="lista-emps-receb">
                                <?php foreach ($empreendimentos as $emp) { ?>
                                    <option value="<?php echo html_escape($emp); ?>"></option>
                                <?php } ?>
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Recebemos (%) <span class="text-danger">*</span></label>
                            <input type="text" name="taxa_recebida" id="receb-taxa" class="form-control"
                                   placeholder="ex.: 5" required>
                            <small class="text-muted">Percentagem sobre o valor da venda.</small>
                        </div>

                        <?php
                        /*
                         * Repartição do que recebemos. Espelha as Regras de Comissão:
                         * as percentagens são DA VERBA RECEBIDA (66/34, não 66%+34% da
                         * venda). Os MESES não se editam aqui — vêm das Regras de
                         * Comissão para não haver dois sítios a dizer prazos diferentes.
                         */
                        ?>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="control-label">No CPCV (%)</label>
                                    <input type="text" name="cpcv_pct" id="receb-cpcv" class="form-control"
                                           placeholder="ex.: 66">
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="control-label">Na escritura (%)</label>
                                    <input type="text" name="escritura_pct" id="receb-escritura" class="form-control"
                                           placeholder="ex.: 34">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <small class="text-muted">
                                Percentagens <strong>da verba que recebemos</strong>, não da venda.
                                Têm de somar 100%. Em branco = recebemos tudo no CPCV.
                            </small>
                        </div>

                        <?php
                        /*
                         * PRAZOS — em que mês é que o promotor paga cada tranche.
                         *
                         * Guardam-se nas Regras de Comissão, que é onde vivem; editam-se
                         * aqui por comodidade. Um mês vazio é resposta válida e quer dizer
                         * "na conclusão / imediato" — é o caso do Gaia Douro e do Boavista.
                         */
                        ?>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="control-label">Mês do CPCV</label>
                                    <input type="month" name="mes_cpcv" id="receb-mes-cpcv" class="form-control">
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label class="control-label">Mês da escritura</label>
                                    <input type="month" name="mes_escritura" id="receb-mes-escritura" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <small class="text-muted">
                                Em que mês o promotor paga cada tranche. <strong>Em branco = na
                                conclusão</strong>, ou seja, conta como vencido desde já.
                                É o mesmo campo das
                                <a href="<?php echo admin_url('dps_vendas/regras'); ?>" target="_blank">Regras de Comissão</a>:
                                mude aqui ou lá, é o mesmo sítio.
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="control-label">Notas</label>
                            <textarea name="notas" id="receb-notas" class="form-control" rows="2"></textarea>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-info btn-block">Guardar</button>
                        <button type="button" class="btn btn-link btn-block" id="cancelar-receb" style="display:none;">
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
    // Editar carrega a linha no mesmo formulário (igual às Regras de Comissão):
    // são poucos campos e não justifica um modal nem uma vista extra.
    document.querySelectorAll('.editar-receb').forEach(function (botao) {
        botao.addEventListener('click', function () {
            var d = this.dataset;
            document.getElementById('receb-id').value = d.id;
            document.getElementById('receb-empreendimento').value = d.empreendimento;
            document.getElementById('receb-taxa').value = d.taxa || '';
            document.getElementById('receb-cpcv').value = d.cpcv || '';
            document.getElementById('receb-escritura').value = d.escritura || '';
            document.getElementById('receb-notas').value = d.notas || '';
            document.getElementById('receb-mes-cpcv').value = d.mescpcv || '';
            document.getElementById('receb-mes-escritura').value = d.mesescritura || '';
            document.getElementById('titulo-form-receb').textContent = 'Editar comissão a receber';
            document.getElementById('cancelar-receb').style.display = '';
            window.scrollTo(0, 0);
        });
    });

    // Preencher uma das percentagens sugere a outra (têm de somar 100).
    var cp = document.getElementById('receb-cpcv');
    var es = document.getElementById('receb-escritura');
    function completar(origem, destino) {
        origem.addEventListener('input', function () {
            var v = parseFloat((origem.value || '').replace(',', '.'));
            if (!isNaN(v) && v >= 0 && v <= 100 && destino.value.trim() === '') {
                destino.value = String(Math.round((100 - v) * 100) / 100);
            }
        });
    }
    completar(cp, es);
    completar(es, cp);

    document.getElementById('cancelar-receb').addEventListener('click', function () {
        document.getElementById('form-receb').reset();
        document.getElementById('receb-id').value = '';
        document.getElementById('receb-mes-cpcv').value = '';
        document.getElementById('receb-mes-escritura').value = '';
        document.getElementById('titulo-form-receb').textContent = 'Nova comissão a receber';
        this.style.display = 'none';
    });
})();
</script>
