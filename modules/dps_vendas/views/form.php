<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">

                        <h4 class="no-margin"><?php echo $venda ? 'Editar Venda #' . $venda['id'] : 'Nova Venda'; ?></h4>
                        <hr>

                        <?php echo form_open_multipart(admin_url('dps_vendas/form' . ($venda ? '/' . $venda['id'] : '')), ['id' => 'dps-venda-form']); ?>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="control-label">Empreendimento <span class="text-danger">*</span></label>
                                <input type="text" name="empreendimento" class="form-control" list="lista-empreendimentos"
                                       value="<?php echo html_escape($venda['empreendimento'] ?? ''); ?>" required>
                                <datalist id="lista-empreendimentos">
                                    <?php foreach ($empreendimentos as $emp) { ?>
                                        <option value="<?php echo html_escape($emp); ?>"></option>
                                    <?php } ?>
                                </datalist>
                                <small class="text-muted">Escolha da lista ou escreva um novo.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Unidade / Fracção <span class="text-danger">*</span></label>
                                <input type="text" name="unidade" class="form-control"
                                       value="<?php echo html_escape($venda['unidade'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Valor da venda (€) <span class="text-danger">*</span></label>
                                <input type="text" name="valor" class="form-control"
                                       value="<?php echo $venda['valor'] ?? ''; ?>" required>
                            </div>
                        </div>

                        <div class="row mtop15">
                            <div class="col-md-4">
                                <label class="control-label">Taxa de comissão (%)</label>
                                <input type="text" name="taxa" class="form-control"
                                       value="<?php echo $venda['taxa'] ?? ''; ?>"
                                       placeholder="Deixe vazio para usar a regra">
                                <small class="text-muted">
                                    Se deixar vazio, aplica-se a regra do empreendimento.
                                </small>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Data da venda</label>
                                <div class="input-group date">
                                    <input type="text" name="data_venda" class="form-control datepicker"
                                           value="<?php echo !empty($venda['data_venda']) ? _d($venda['data_venda']) : ''; ?>">
                                    <div class="input-group-addon"><i class="fa fa-calendar"></i></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Lead associada (opcional)</label>
                                <input type="number" name="lead_id" class="form-control"
                                       value="<?php echo $venda['lead_id'] ?? ''; ?>"
                                       placeholder="ID da lead">
                            </div>
                        </div>

                        <hr>
                        <h5>Dados do cliente</h5>

                        <?php
                        /*
                         * Particular ou Empresa é a PRIMEIRA pergunta, e não uma opção
                         * escondida lá dentro do bloco do contrato.
                         *
                         * Decide o resto do formulário: uma empresa identifica-se pela
                         * certidão permanente (CRC) e por quem assina por ela; uma pessoa
                         * pelo Cartão de Cidadão, naturalidade e estado civil. Perguntar isto
                         * no fim obrigava a voltar atrás e a corrigir o que já se tinha
                         * escrito. Pedido do dono (03/08/2026).
                         */
                        $tipo_comprador = trim((string) ($venda['cliente_tipo'] ?? ''));
                        $e_empresa_form = strcasecmp($tipo_comprador, 'empresa') === 0;
                        ?>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="control-label">Tipo de comprador</label>
                                <select name="cliente_tipo" id="dps-tipo-comprador" class="form-control">
                                    <option value="Particular" <?php echo $e_empresa_form ? '' : 'selected'; ?>>Particular</option>
                                    <option value="Empresa" <?php echo $e_empresa_form ? 'selected' : ''; ?>>Empresa</option>
                                </select>
                            </div>
                            <div class="col-md-4 dps-so-empresa" style="<?php echo $e_empresa_form ? '' : 'display:none;'; ?>">
                                <label class="control-label">
                                    Código de acesso à certidão permanente (CRC)
                                </label>
                                <input type="text" name="cliente_crc" class="form-control"
                                       placeholder="0000-0000-0000"
                                       value="<?php echo html_escape($venda['cliente_crc'] ?? ''); ?>">
                                <span class="help-block" style="margin-bottom:0;">
                                    Sendo empresa, é isto que identifica o comprador no contrato —
                                    a certidão traz a firma, a sede e o NIPC.
                                </span>
                            </div>
                        </div>

                        <div class="row mtop15">
                            <div class="col-md-6">
                                <label class="control-label">
                                    <span id="dps-etiqueta-nome">Nome</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="cliente" class="form-control"
                                       value="<?php echo html_escape($venda['cliente'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="control-label">Morada</label>
                                <input type="text" name="cliente_morada" class="form-control"
                                       value="<?php echo html_escape($venda['cliente_morada'] ?? ''); ?>">
                            </div>
                        </div>
                            <div class="col-md-4">
                                <label class="control-label">Código postal</label>
                                <input type="text" name="cliente_codigo_postal" class="form-control" placeholder="0000-000"
                                       value="<?php echo $venda ? html_escape($venda['cliente_codigo_postal'] ?? '') : ''; ?>">
                            </div>

                        <div class="row mtop15">
                            <div class="col-md-4">
                                <label class="control-label">Telefone</label>
                                <input type="text" name="cliente_telefone" class="form-control"
                                       value="<?php echo html_escape($venda['cliente_telefone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Email</label>
                                <input type="email" name="cliente_email" class="form-control"
                                       value="<?php echo html_escape($venda['cliente_email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Regime civil</label>
                                <select name="regime_civil" class="form-control selectpicker">
                                    <option value="">—</option>
                                    <?php foreach (dps_vendas_regimes_civis() as $regime) { ?>
                                        <option value="<?php echo html_escape($regime); ?>"
                                            <?php echo ($venda['regime_civil'] ?? '') === $regime ? 'selected' : ''; ?>>
                                            <?php echo html_escape($regime); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <?php
                        /*
                         * DADOS DO CPCV — abertos no Aura, à mão de semear nos outros.
                         *
                         * O contrato-promessa do Meixomil identifica o comprador por estes
                         * campos, e por isso no Aura o bloco abre logo. Nos outros
                         * empreendimentos estava ESCONDIDO — e escondido a sério: nem havia
                         * como lá chegar. Quem precisava de guardar o número do Cartão de
                         * Cidadão numa venda da Boavista não tinha onde (pedido do dono,
                         * 03/08/2026). As colunas sempre aceitaram estes dados; era só o
                         * formulário que os tapava.
                         *
                         * Agora fica atrás de um botão. Abre sozinho em três casos: é Aura,
                         * já tem alguma coisa preenchida (senão os dados ficavam invisíveis
                         * dentro de um bloco fechado), ou o utilizador carregou no botão.
                         *
                         * A decisão corre no browser porque o empreendimento é um campo de
                         * texto livre com datalist — não há recarregamento onde o servidor
                         * pudesse decidir.
                         */
                        $eh_aura = stripos((string) ($venda['empreendimento'] ?? ''), 'aura') !== false;

                        $tem_dados = false;
                        foreach (['cliente_nif', 'cliente_cc', 'cliente_cc_validade', 'cliente_naturalidade',
                                  'cliente_nacionalidade', 'cliente_freguesia', 'cliente_concelho',
                                  'cliente_crc', 'cliente_representante'] as $campo_cpcv) {
                            if (trim((string) ($venda[$campo_cpcv] ?? '')) !== '') {
                                $tem_dados = true;
                                break;
                            }
                        }

                        $abrir = $eh_aura || $tem_dados;
                        ?>
                        <div id="abrir-cpcv" style="<?php echo $abrir ? 'display:none;' : ''; ?>">
                            <hr>
                            <button type="button" class="btn btn-default" onclick="dpsAbrirCpcv()">
                                <i class="fa fa-id-card-o"></i>
                                Preencher dados do contrato
                            </button>
                            <span class="text-muted" style="margin-left:10px;font-size:13px;">
                                Cartão de Cidadão, NIF, naturalidade, estado civil — o que o contrato pede.
                            </span>
                        </div>

                        <div id="bloco-cpcv" style="<?php echo $abrir ? '' : 'display:none;'; ?>">
                            <hr>
                            <h5>
                                Dados para o contrato-promessa (CPCV)
                                <small class="text-muted">— obrigatórios no Aura, opcionais nos outros</small>
                            </h5>
                            <p class="text-muted">
                                Com estes campos preenchidos, a ficha da venda passa a ter o botão para
                                descarregar o CPCV já preenchido em Word.
                            </p>

                            <div class="row">
                                <div class="col-md-4 dps-so-particular">
                                    <label class="control-label">NIF</label>
                                    <input type="text" name="cliente_nif" class="form-control" placeholder="000000000"
                                           value="<?php echo html_escape($venda['cliente_nif'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="control-label">N.º do Cartão de Cidadão</label>
                                    <input type="text" name="cliente_cc" class="form-control" placeholder="00000000 0AA0"
                                           value="<?php echo html_escape($venda['cliente_cc'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="control-label">Validade do Cartão de Cidadão</label>
                                    <input type="date" name="cliente_cc_validade" class="form-control"
                                           value="<?php echo html_escape($venda['cliente_cc_validade'] ?? ''); ?>">
                                </div>
                            </div>

                            <?php
                            /*
                             * Particular ou empresa. É esta escolha que decide como o
                             * comprador é identificado no CPCV e na declaração de cedência:
                             * a empresa leva NIPC, certidão, sede e quem assina por ela;
                             * o particular leva estado civil, naturalidade e residência.
                             *
                             * Antes decidia-se pela presença do CRC, o que obrigava a
                             * preencher a certidão só para o texto sair na forma certa.
                             */
                            // $tipo_comprador e $e_empresa_form já vêm de cima,
                            // onde a pergunta é feita.
                            ?>
                            <div class="row mtop15 dps-so-empresa" style="<?php echo $e_empresa_form ? '' : 'display:none;'; ?>">
                                <div class="col-md-4">
                                    <label class="control-label">Representante legal da empresa</label>
                                    <input type="text" name="cliente_representante" class="form-control"
                                           placeholder="Quem assina em nome da empresa"
                                           value="<?php echo html_escape($venda['cliente_representante'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="control-label">NIF do representante</label>
                                    <input type="text" name="cliente_representante_nif" class="form-control"
                                           placeholder="000000000"
                                           value="<?php echo html_escape($venda['cliente_representante_nif'] ?? ''); ?>">
                                    <span class="help-block" style="margin-bottom:0;">
                                        O Cartão de Cidadão pedido acima é o <strong>do representante</strong>,
                                        não o da empresa.
                                    </span>
                                </div>
                            </div>

                            <script>
                            /*
                             * Sem jQuery de propósito. Este bloco fica a meio da página e
                             * o Perfex só carrega o jQuery no rodapé — usar $ aqui dava
                             * "$ is not defined", o script morria, e os campos da empresa
                             * ficavam à vista mesmo com "Particular" escolhido.
                             */
                            document.addEventListener('DOMContentLoaded', function () {
                                var sel = document.getElementById('dps-tipo-comprador');
                                if (!sel) { return; }

                                function mostrarSe(seletor, condicao) {
                                    var els = document.querySelectorAll(seletor);
                                    for (var i = 0; i < els.length; i++) {
                                        els[i].style.display = condicao ? '' : 'none';
                                    }
                                }

                                var etiqueta = document.getElementById('dps-etiqueta-nome');

                                function aplicar() {
                                    var empresa = sel.value === 'Empresa';

                                    // Quem escreve no campo tem de saber o que lá pôr: a firma
                                    // ou a pessoa. É o mesmo campo, mas não é a mesma coisa.
                                    if (etiqueta) {
                                        etiqueta.textContent = empresa ? 'Nome da empresa' : 'Nome';
                                    }

                                    // Certidão e representante: só uma sociedade os tem.
                                    mostrarSe('.dps-so-empresa', empresa);

                                    // Naturalidade e nacionalidade: só uma pessoa as tem.
                                    // Freguesia e concelho ficam nos dois casos — servem a
                                    // residência do particular e a sede da empresa.
                                    mostrarSe('.dps-so-particular', !empresa);
                                }

                                sel.addEventListener('change', aplicar);
                                aplicar();
                            });
                            </script>

                            <div class="row mtop15">
                                <div class="col-md-3 dps-so-particular">
                                    <label class="control-label">Naturalidade</label>
                                    <input type="text" name="cliente_naturalidade" class="form-control"
                                           placeholder="concelho de nascimento"
                                           value="<?php echo html_escape($venda['cliente_naturalidade'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3 dps-so-particular">
                                    <label class="control-label">Nacionalidade</label>
                                    <input type="text" name="cliente_nacionalidade" class="form-control"
                                           value="<?php echo html_escape($venda['cliente_nacionalidade'] ?? 'Portuguesa'); ?>">
                                </div>
                                <div class="col-md-3 dps-so-particular">
                                    <label class="control-label">Freguesia</label>
                                    <input type="text" name="cliente_freguesia" class="form-control"
                                           value="<?php echo html_escape($venda['cliente_freguesia'] ?? ''); ?>">
                                </div>
                                <div class="col-md-3 dps-so-particular">
                                    <label class="control-label">Concelho</label>
                                    <input type="text" name="cliente_concelho" class="form-control"
                                           value="<?php echo html_escape($venda['cliente_concelho'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Documentos</h5>

                        <?php if (!$venda) { ?>
                            <p class="text-muted">
                                O Cartão de Cidadão (frente e verso) é obrigatório para registar a venda.
                                Aceita-se PDF, JPG ou PNG até 8 MB por ficheiro.
                            </p>
                        <?php } else { ?>
                            <?php
                            /*
                             * Mostrar o que JÁ está anexado e o que falta. Sem isto, quem
                             * abre uma reserva para completar não sabe se o Cartão de
                             * Cidadão está lá ou não — tinha de sair, ir ao detalhe, e
                             * voltar.
                             */
                            $por_tipo = [];
                            foreach ($docs as $d) {
                                $por_tipo[$d['tipo']][] = $d;
                            }
                            ?>
                            <table class="table table-condensed" style="margin-bottom:12px;">
                                <tbody>
                                <?php foreach (['cc_frente' => 'Cartão de Cidadão (frente)',
                                                'cc_verso'  => 'Cartão de Cidadão (verso)'] as $tipo => $etiqueta) { ?>
                                    <tr>
                                        <td width="40%"><strong><?php echo $etiqueta; ?></strong></td>
                                        <td>
                                            <?php if (!empty($por_tipo[$tipo])) { ?>
                                                <?php foreach ($por_tipo[$tipo] as $d) { ?>
                                                    <span class="label label-success">Anexado</span>
                                                    <?php echo html_escape($d['original_name']); ?>
                                                    <a href="<?php echo admin_url('dps_vendas/download_doc/' . (int) $d['id']); ?>"
                                                       class="btn btn-default btn-xs"><i class="fa fa-download"></i></a>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <span class="label label-danger">Em falta</span>
                                                <small class="text-muted">— anexe abaixo</small>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php
                                $outros = array_filter($docs, function ($d) {
                                    return !in_array($d['tipo'], ['cc_frente', 'cc_verso'], true);
                                });
                                ?>
                                <?php if (!empty($outros)) { ?>
                                    <tr>
                                        <td><strong>Outros documentos</strong></td>
                                        <td>
                                            <?php foreach ($outros as $d) { ?>
                                                <div>
                                                    <?php echo dps_vendas_nome_doc($d['tipo']); ?>:
                                                    <?php echo html_escape($d['original_name']); ?>
                                                    <a href="<?php echo admin_url('dps_vendas/download_doc/' . (int) $d['id']); ?>"
                                                       class="btn btn-default btn-xs"><i class="fa fa-download"></i></a>
                                                </div>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                            <p class="text-muted">
                                Anexe abaixo o que estiver em falta. Anexar de novo um documento
                                que já existe <strong>substitui</strong> o anterior.
                            </p>
                        <?php } ?>

                        <div class="row">
                            <div class="col-md-4">
                                <label class="control-label">
                                    CC frente <?php echo !$venda ? '<span class="text-danger">*</span>' : ''; ?>
                                </label>
                                <input type="file" name="cc_frente" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">
                                    CC verso <?php echo !$venda ? '<span class="text-danger">*</span>' : ''; ?>
                                </label>
                                <input type="file" name="cc_verso" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-4">
                                <label class="control-label">Outros documentos</label>
                                <input type="file" name="outros[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <hr>

                        <div class="text-right">
                            <a href="<?php echo admin_url('dps_vendas'); ?>" class="btn btn-default">Cancelar</a>
                            <button type="submit" class="btn btn-info">
                                <?php echo $venda ? 'Guardar alterações' : 'Criar venda'; ?>
                            </button>
                        </div>

                        <?php echo form_close(); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';
    /*
     * Mostra o bloco do CPCV quando o empreendimento é o Aura.
     * Corre no 'input' e não só no 'change' porque o campo tem datalist: quem
     * escreve "aura" à mão nunca dispara o change até sair do campo.
     */
    var campo  = document.querySelector('[name="empreendimento"]');
    var bloco  = document.getElementById('bloco-cpcv');
    var abrir  = document.getElementById('abrir-cpcv');
    if (!campo || !bloco) { return; }

    // Uma vez aberto à mão, fica aberto: mudar o empreendimento não pode
    // fazer desaparecer campos que a pessoa está a preencher.
    var aberto_a_mao = false;

    window.dpsAbrirCpcv = function () {
        aberto_a_mao = true;
        actualizar();
        var primeiro = bloco.querySelector('input, select');
        if (primeiro) { primeiro.focus(); }
    };

    function temDados() {
        var campos = bloco.querySelectorAll('input, select, textarea');
        for (var i = 0; i < campos.length; i++) {
            if ((campos[i].value || '').trim() !== '') { return true; }
        }
        return false;
    }

    function actualizar() {
        var mostrar = aberto_a_mao || /aura/i.test(campo.value || '') || temDados();
        bloco.style.display = mostrar ? '' : 'none';
        if (abrir) { abrir.style.display = mostrar ? 'none' : ''; }
    }

    campo.addEventListener('input', actualizar);
    campo.addEventListener('change', actualizar);
    actualizar();
})();
</script>
<?php init_tail(); ?>
