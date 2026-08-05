<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">

                <?php if (!$automacao_ativa) { ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>A automação está DESLIGADA.</strong>
                        Pode preparar e pré-visualizar campanhas, mas nada será enviado
                        até um administrador ligar o interruptor geral nas
                        <?php if (is_admin()) { ?>
                            <a href="<?php echo admin_url('dps_automacao/definicoes'); ?>">Definições</a>.
                        <?php } else { ?>
                            Definições.
                        <?php } ?>
                    </div>
                <?php } ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Envio em Massa</h4>
                        <p class="text-muted">
                            Escolha os estados de leads, o canal e escreva a mensagem.
                            Pré-visualize sempre antes de enviar.
                        </p>
                        <hr>

                        <?php echo form_open(admin_url('dps_automacao/envio_massa_enviar'), ['id' => 'form-envio-massa']); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estados">Estados das leads</label>
                                    <select name="estados[]" id="estados" class="form-control selectpicker" multiple
                                            data-live-search="true" data-actions-box="true">
                                        <?php foreach ($estados as $estado) { ?>
                                            <option value="<?php echo (int) $estado['id']; ?>">
                                                <?php echo html_escape($estado['name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <?php if (is_admin()) { ?>
                                    <div class="form-group">
                                        <label for="comercial_id">Comercial</label>
                                        <select name="comercial_id" id="comercial_id" class="form-control selectpicker"
                                                data-live-search="true">
                                            <option value="0">Todas as leads (todos os comerciais)</option>
                                            <?php foreach ($comerciais as $comercial) { ?>
                                                <option value="<?php echo (int) $comercial['staffid']; ?>">
                                                    <?php echo html_escape($comercial['firstname'] . ' ' . $comercial['lastname']); ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <small class="text-muted">
                                            No WhatsApp, cada lead sai pela instância do comercial a que está atribuída.
                                        </small>
                                    </div>
                                <?php } else { ?>
                                    <div class="form-group">
                                        <label>Comercial</label>
                                        <p class="form-control-static">
                                            Apenas as suas leads — o envio limita-se ao que lhe está atribuído.
                                        </p>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Canal</label>
                            <div class="radio radio-primary">
                                <input type="radio" name="canal" id="canal_whatsapp" value="whatsapp" checked>
                                <label for="canal_whatsapp"><i class="fa fa-whatsapp"></i> WhatsApp</label>
                            </div>
                            <div class="radio radio-primary">
                                <input type="radio" name="canal" id="canal_email" value="email">
                                <label for="canal_email"><i class="fa fa-envelope-o"></i> Email</label>
                            </div>
                            <?php if ($sms_disponivel) { ?>
                                <div class="radio radio-primary">
                                    <input type="radio" name="canal" id="canal_sms" value="sms">
                                    <label for="canal_sms"><i class="fa fa-mobile"></i> SMS</label>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="form-group" id="grupo-assunto" style="display:none;">
                            <label for="assunto">Assunto do email</label>
                            <input type="text" name="assunto" id="assunto" class="form-control"
                                   placeholder="Mensagem de <?php echo html_escape(get_option('companyname')); ?>">
                        </div>

                        <div class="form-group">
                            <?php
                            /*
                             * ANEXO OPCIONAL.
                             *
                             * Reaproveita os PDFs já carregados na Proposta em
                             * Massa em vez de uma segunda máquina de upload —
                             * um sítio só para carregar ficheiros, dois para os
                             * usar. Sem anexo, o envio é o de sempre.
                             *
                             * No WhatsApp o PDF vai como documento com a
                             * mensagem por legenda; no email vai anexado.
                             * Pedido do dono (05/08/2026).
                             */
                            ?>
                            <div class="form-group">
                                <label for="proposta_id">Anexar PDF <small class="text-muted">(opcional)</small></label>
                                <select name="proposta_id" id="proposta_id" class="form-control selectpicker"
                                        data-live-search="true">
                                    <option value="0">Sem anexo</option>
                                    <?php foreach (($propostas ?? []) as $pdf) { ?>
                                        <option value="<?php echo (int) $pdf['id']; ?>">
                                            <?php echo html_escape($pdf['original_name']); ?>
                                            (<?php echo number_format(((int) $pdf['tamanho']) / 1048576, 1, ',', '.'); ?> MB)
                                        </option>
                                    <?php } ?>
                                </select>
                                <small class="text-muted">
                                    Os ficheiros são os que já estão carregados em
                                    <a href="<?php echo admin_url('dps_automacao/proposta_massa'); ?>">Proposta em Massa</a>.
                                    No WhatsApp segue como documento, no email como anexo.
                                </small>
                            </div>

                            <label for="mensagem">Mensagem</label>
                            <textarea name="mensagem" id="mensagem" rows="6" class="form-control"
                                      placeholder="Olá {nome}, ..."></textarea>
                            <small class="text-muted">
                                Variáveis disponíveis: <code>{nome}</code> (nome da lead) e
                                <code>{comercial}</code> (nome do comercial atribuído).
                            </small>
                        </div>

                        <div id="resultado-preview" class="alert alert-info" style="display:none;"></div>
                        <div id="progresso-envio" class="alert alert-warning" style="display:none;"></div>
                        <div id="resumo-envio" class="alert alert-success" style="display:none;"></div>
                        <div id="erro-envio" class="alert alert-danger" style="display:none;"></div>

                        <hr>

                        <button type="button" class="btn btn-default" id="btn-preview">
                            <i class="fa fa-search"></i> Pré-visualizar
                        </button>
                        <button type="button" class="btn btn-info" id="btn-teste">
                            <i class="fa fa-paper-plane-o"></i> Enviar teste para mim
                        </button>
                        <button type="button" class="btn btn-danger" id="btn-enviar" disabled
                                title="Pré-visualize primeiro">
                            <i class="fa fa-send"></i> Enviar
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
$(function () {
    'use strict';

    var urlPreview = '<?php echo admin_url('dps_automacao/envio_massa_preview'); ?>';
    var urlTeste   = '<?php echo admin_url('dps_automacao/envio_massa_teste'); ?>';
    var urlEnviar  = '<?php echo admin_url('dps_automacao/envio_massa_enviar'); ?>';

    var $form      = $('#form-envio-massa');
    var $preview   = $('#resultado-preview');
    var $progresso = $('#progresso-envio');
    var $resumo    = $('#resumo-envio');
    var $erro      = $('#erro-envio');
    var aEnviar    = false;

    // Com csrf_regenerate ativo no Perfex, cada resposta traz um token novo —
    // sem esta atualização, o POST seguinte da sequência falharia.
    function atualizarCsrf(r) {
        if (r && r.csrf) {
            $form.find('input[name="' + r.csrf.name + '"]').val(r.csrf.hash);
        }
    }

    function limparAvisos() {
        $preview.hide();
        $progresso.hide();
        $resumo.hide();
        $erro.hide();
    }

    function mostrarErro(msg) {
        $erro.text(msg).show();
    }

    function dadosDoForm(extra) {
        var dados = $form.serializeArray();
        if (extra) {
            $.each(extra, function (nome, valor) {
                dados.push({ name: nome, value: valor });
            });
        }
        return $.param(dados);
    }

    $('input[name="canal"]').on('change', function () {
        $('#grupo-assunto').toggle($(this).val() === 'email');
        // Mudar de canal invalida a contagem: obrigar a novo preview.
        $('#btn-enviar').prop('disabled', true);
        limparAvisos();
    });

    $('#estados, #comercial_id').on('change', function () {
        $('#btn-enviar').prop('disabled', true);
        limparAvisos();
    });

    $('#btn-preview').on('click', function () {
        limparAvisos();
        var $btn = $(this).prop('disabled', true);

        $.post(urlPreview, dadosDoForm(), function (r) {
            atualizarCsrf(r);
            if (r.erro) {
                mostrarErro(r.erro);
                return;
            }

            var html = '<strong>' + r.com_contacto + '</strong> lead(s) receberão a mensagem';
            html += ' (' + r.total + ' no total nos estados escolhidos';
            if (r.excluidas > 0) {
                html += ', <strong>' + r.excluidas + ' excluída(s)</strong> por não terem o contacto necessário ao canal';
            }
            html += ').<ul>';
            $.each(r.estados, function (i, e) {
                html += '<li>' + $('<span>').text(e.estado_nome || ('Estado #' + e.estado_id)).html()
                    + ': ' + e.com_contacto + ' de ' + e.total + '</li>';
            });
            html += '</ul>';

            $preview.html(html).show();
            $('#btn-enviar').prop('disabled', r.com_contacto == 0);
        }, 'json').fail(function () {
            mostrarErro('Não foi possível calcular a pré-visualização.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#btn-teste').on('click', function () {
        limparAvisos();
        var $btn = $(this).prop('disabled', true);

        $.post(urlTeste, dadosDoForm(), function (r) {
            atualizarCsrf(r);
            if (r.erro) {
                mostrarErro(r.erro);
            } else {
                $resumo.text(r.sucesso).show();
            }
        }, 'json').fail(function () {
            mostrarErro('Não foi possível enviar o teste.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function enviarLote(lastId, totais) {
        $.post(urlEnviar, dadosDoForm({ last_id: lastId }), function (r) {
            atualizarCsrf(r);
            if (r.erro) {
                aEnviar = false;
                $progresso.hide();
                mostrarErro(r.erro);
                $('#btn-enviar, #btn-preview, #btn-teste').prop('disabled', false);
                return;
            }

            totais.enviados += r.enviados;
            totais.falhados += r.falhados;

            $progresso.html('A enviar… <strong>' + totais.enviados + '</strong> enviadas, '
                + '<strong>' + totais.falhados + '</strong> falhadas até agora.').show();

            if (!r.fim) {
                enviarLote(r.last_id, totais);
            } else {
                aEnviar = false;
                $progresso.hide();
                $resumo.html('Concluído: <strong>' + totais.enviados + '</strong> enviadas, '
                    + '<strong>' + totais.falhados + '</strong> falhadas. '
                    + 'Consulte o <a href="<?php echo admin_url('dps_automacao/envios'); ?>">Registo de Envios</a>.').show();
                $('#btn-preview, #btn-teste').prop('disabled', false);
                // Novo envio exige novo preview — evita o duplo clique tardio.
                $('#btn-enviar').prop('disabled', true);
            }
        }, 'json').fail(function () {
            aEnviar = false;
            $progresso.hide();
            mostrarErro('O envio foi interrompido a meio. Consulte o Registo de Envios antes de repetir, para não duplicar mensagens.');
            $('#btn-preview, #btn-teste').prop('disabled', false);
        });
    }

    $('#btn-enviar').on('click', function () {
        if (aEnviar) {
            return;
        }
        if (!confirm('Confirma o envio para todas as leads da pré-visualização? Esta ação não pode ser anulada.')) {
            return;
        }

        aEnviar = true;
        limparAvisos();
        $('#btn-enviar, #btn-preview, #btn-teste').prop('disabled', true);
        enviarLote(0, { enviados: 0, falhados: 0 });
    });
});
</script>
