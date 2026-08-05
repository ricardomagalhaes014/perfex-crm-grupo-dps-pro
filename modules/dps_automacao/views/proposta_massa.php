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
                        Pode carregar propostas e pré-visualizar, mas nada será enviado
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
                        <h4 class="no-margin">1. Proposta (PDF)</h4>
                        <p class="text-muted">
                            Gere a proposta no simulador (Gerar Proposta descarrega o PDF)
                            e carregue-a aqui. Depois escolha-a na lista abaixo.
                        </p>
                        <hr>

                        <?php echo form_open_multipart(admin_url('dps_automacao/proposta_carregar'), ['id' => 'form-carregar-proposta']); ?>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="pdf">Ficheiro PDF (máx. 25 MB)</label>
                                    <input type="file" name="pdf" id="pdf" accept="application/pdf,.pdf" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-upload"></i> Carregar proposta
                                </button>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">2. Enviar em massa</h4>
                        <p class="text-muted">
                            Escolha a proposta, os estados de leads, o canal e a mensagem
                            curta que acompanha o PDF. Pré-visualize sempre antes de enviar.
                        </p>
                        <hr>

                        <?php echo form_open(admin_url('dps_automacao/proposta_massa_lote'), ['id' => 'form-proposta-massa']); ?>

                        <div class="form-group">
                            <label>Proposta a enviar</label>
                            <?php if (empty($propostas)) { ?>
                                <p class="form-control-static text-muted">
                                    Ainda não carregou nenhuma proposta — use o passo 1.
                                </p>
                            <?php } else { ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th style="width:40px;"></th>
                                                <th>Ficheiro</th>
                                                <?php if (is_admin()) { ?>
                                                    <th>Comercial</th>
                                                <?php } ?>
                                                <th>Tamanho</th>
                                                <th>Carregada em</th>
                                                <th style="width:90px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($propostas as $i => $proposta) { ?>
                                                <tr>
                                                    <td>
                                                        <input type="radio" name="proposta_id"
                                                               value="<?php echo (int) $proposta['id']; ?>"
                                                               <?php echo $i === 0 ? 'checked' : ''; ?>>
                                                    </td>
                                                    <td><?php echo html_escape($proposta['original_name']); ?></td>
                                                    <?php if (is_admin()) { ?>
                                                        <td><?php echo html_escape(trim((string) $proposta['staff_nome']) ?: '—'); ?></td>
                                                    <?php } ?>
                                                    <td>
                                                        <?php
                                                        // Tamanho legível — a coluna guarda bytes.
                                                        $kb = (int) $proposta['tamanho'] / 1024;
                                                        echo $kb >= 1024
                                                            ? number_format($kb / 1024, 1, ',', '') . ' MB'
                                                            : number_format($kb, 0, ',', '') . ' KB';
                                                        ?>
                                                    </td>
                                                    <td><?php echo html_escape(_dt($proposta['dateadded'])); ?></td>
                                                    <td>
                                                        <button type="button" class="btn btn-default btn-xs btn-apagar-proposta"
                                                                data-id="<?php echo (int) $proposta['id']; ?>">
                                                            <i class="fa fa-trash"></i> Apagar
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } ?>
                        </div>

                        <?php
                        /*
                         * EMPREENDIMENTO = o do documento já enviado à lead.
                         *
                         * Não é a etiqueta da lead — essa diz de que campanha
                         * ela veio, não o que já lhe mandámos. Vem de
                         * dps_propostas, que guarda lead + empreendimento a
                         * cada envio.
                         *
                         * A contagem é de PROPOSTAS — quantos documentos
                         * saíram, não a quantas pessoas — e ACOMPANHA o
                         * comercial escolhido: a matriz inteira vai no
                         * atributo data-, e o número muda sem ir outra vez ao
                         * servidor. Regra do dono (05/08/2026).
                         */
                        $emp_totais = $empreendimentos['totais'] ?? [];
                        $emp_matriz = $empreendimentos['por_comercial'] ?? [];
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="empreendimento">Empreendimento</label>
                                    <select name="empreendimento" id="empreendimento" class="form-control selectpicker"
                                            data-live-search="true"
                                            data-matriz="<?php echo html_escape(json_encode($emp_matriz, JSON_UNESCAPED_UNICODE)); ?>"
                                            data-totais="<?php echo html_escape(json_encode($emp_totais, JSON_UNESCAPED_UNICODE)); ?>">
                                        <option value="">Todos os empreendimentos</option>
                                        <?php foreach ($emp_totais as $nome => $n) { ?>
                                            <option value="<?php echo html_escape($nome); ?>"
                                                    data-nome="<?php echo html_escape($nome); ?>">
                                                <?php echo html_escape($nome); ?> (<?php echo (int) $n; ?> propostas)
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted" id="emp-nota">
                                        Propostas já enviadas de cada empreendimento. Escolha um comercial para ver só as dele.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estados">Estados das leads <small class="text-muted">(opcional)</small></label>
                                    <select name="estados[]" id="estados" class="form-control selectpicker" multiple
                                            data-live-search="true" data-actions-box="true">
                                        <?php foreach ($estados as $estado) { ?>
                                            <option value="<?php echo (int) $estado['id']; ?>">
                                                <?php echo html_escape($estado['name']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <small class="text-muted">
                                        Deixe vazio quando escolher um empreendimento: se a proposta foi
                                        enviada, a lead já está nesse estado.
                                    </small>
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
                                            No WhatsApp, cada lead recebe o PDF pela instância do comercial a que está atribuída.
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
                                <label for="canal_whatsapp"><i class="fa fa-whatsapp"></i> WhatsApp (PDF como documento)</label>
                            </div>
                            <div class="radio radio-primary">
                                <input type="radio" name="canal" id="canal_email" value="email">
                                <label for="canal_email"><i class="fa fa-envelope-o"></i> Email (PDF em anexo, assunto "Proposta DPS")</label>
                            </div>
                        </div>

                        <?php
                        /*
                         * Reenvio. Por omissão, quem já recebeu ESTA proposta fica de
                         * fora — repetir sem querer irrita o cliente. Mas o mesmo
                         * ficheiro pode ser reenviado de propósito quando o que mudou
                         * está no conteúdo (preços, unidades ainda disponíveis), e é
                         * por isso que a decisão é do utilizador, envio a envio.
                         */
                        ?>
                        <div class="form-group">
                            <div class="checkbox checkbox-warning">
                                <input type="checkbox" name="repetir" id="repetir" value="1">
                                <label for="repetir">
                                    Enviar também a quem <strong>já recebeu este mesmo ficheiro</strong>
                                </label>
                            </div>
                            <small class="text-muted">
                                Em branco (o normal), quem já a recebeu fica de fora.
                                Ligue quando o conteúdo mudou — preços, unidades disponíveis —
                                e quiser que recebam a versão nova. Dentro do mesmo envio
                                ninguém recebe duas vezes, mesmo com isto ligado.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="mensagem">Mensagem que acompanha o PDF</label>
                            <textarea name="mensagem" id="mensagem" rows="6" class="form-control"><?php
                                echo html_escape("Olá {nome},\n\nSegue em anexo uma proposta que preparámos a pensar em si. Diga-nos o que acha — estamos ao dispor.\n\nCom os melhores cumprimentos,\n{comercial}");
                            ?></textarea>
                            <small class="text-muted">
                                Variáveis disponíveis: <code>{nome}</code> (nome da lead) e
                                <code>{comercial}</code> (nome do comercial atribuído).
                            </small>
                        </div>

                        <div id="resultado-preview" class="alert alert-info" style="display:none;"></div>
                        <div id="progresso-envio" class="alert alert-warning" style="display:none;"></div>
                        <div id="resumo-envio" class="alert alert-success" style="display:none;"></div>
                        <div id="erro-envio" class="alert alert-danger" style="display:none;"></div>

                        <p class="text-muted">
                            <i class="fa fa-info-circle"></i>
                            Leads que já receberam <strong>este mesmo ficheiro</strong> são saltadas
                            automaticamente — repetir o envio nunca duplica o PDF na mesma lead.
                            É por ficheiro, e não tem nada a ver com o filtro do empreendimento:
                            esse escolhe <em>a quem</em> se envia, este evita mandar duas vezes o mesmo.
                        </p>

                        <hr>

                        <button type="button" class="btn btn-default" id="btn-preview">
                            <i class="fa fa-search"></i> Pré-visualizar
                        </button>
                        <button type="button" class="btn btn-info" id="btn-teste">
                            <i class="fa fa-paper-plane-o"></i> Enviar teste para mim
                        </button>
                        <button type="button" class="btn btn-danger" id="btn-enviar"
                                title="Conta as leads e pede confirmação antes de enviar">
                            <i class="fa fa-send"></i> Enviar proposta
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
    var urlTeste   = '<?php echo admin_url('dps_automacao/proposta_massa_teste'); ?>';
    var urlEnviar  = '<?php echo admin_url('dps_automacao/proposta_massa_lote'); ?>';
    var urlApagar  = '<?php echo admin_url('dps_automacao/proposta_apagar'); ?>/';

    var $form      = $('#form-proposta-massa');
    var $preview   = $('#resultado-preview');
    var $progresso = $('#progresso-envio');
    var $resumo    = $('#resumo-envio');
    var $erro      = $('#erro-envio');
    var aEnviar    = false;

    // Com csrf_regenerate ativo no Perfex, cada resposta traz um token novo —
    // sem esta atualização, o POST seguinte da sequência falharia. Atualiza
    // TODOS os tokens do documento (o form de upload do passo 1 incluído):
    // um upload feito depois de um preview/teste falharia com 403 se ficasse
    // com o token da carga inicial da página.
    function atualizarCsrf(r) {
        if (r && r.csrf) {
            $('input[name="' + r.csrf.name + '"]').val(r.csrf.hash);
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

    // Campos da campanha SEM os hidden — serializados UMA vez no clique de
    // "Enviar" e reutilizados em todos os lotes: mudar canal/proposta/estados
    // a meio da sequência não pode alterar a campanha em curso. O token CSRF
    // (hidden) é acrescentado fresco em cada lote, porque se regenera.
    function dadosCampanha() {
        return $form.find('input, select, textarea').not('input[type="hidden"]').serializeArray();
    }

    // Durante o envio, todos os campos ficam bloqueados (não só os botões).
    // Os hidden ficam de fora: campos disabled não serializam, e o CSRF
    // fresco tem de seguir em cada lote.
    function bloquearCampos(bloquear) {
        $form.find('input, select, textarea, .btn-apagar-proposta').not('input[type="hidden"]').prop('disabled', bloquear);
        if ($.fn.selectpicker) {
            $form.find('.selectpicker').selectpicker('refresh');
        }
    }

    // Mudar o alvo ou o canal invalida a contagem mostrada — limpa-se o que
    // está no ecrã, mas o botão de enviar continua utilizável: ele recalcula
    // sempre antes de disparar.
    /*
     * As contagens do selector de empreendimento seguem o comercial escolhido.
     * A matriz inteira já veio na página, por isso é só reescrever os rótulos —
     * sem pedido ao servidor e sem esperar.
     */
    function actualizarContagensEmpreendimento() {
        var $sel = $('#empreendimento');
        if (!$sel.length) { return; }

        var matriz = $sel.data('matriz') || {};
        var totais = $sel.data('totais') || {};
        var quem   = parseInt($('#comercial_id').val() || '0', 10);
        var fonte  = (quem > 0 && matriz[quem]) ? matriz[quem] : totais;

        $sel.find('option').each(function () {
            var nome = $(this).data('nome');
            if (!nome) { return; }
            var n = fonte[nome] || 0;
            $(this).text(nome + ' (' + n + ' proposta' + (n === 1 ? '' : 's') + ')');
        });

        $('#emp-nota').text(quem > 0
            ? 'Propostas enviadas por este comercial, por empreendimento.'
            : 'Propostas já enviadas de cada empreendimento. Escolha um comercial para ver só as dele.');

        if ($.fn.selectpicker) { $sel.selectpicker('refresh'); }
    }

    $('#comercial_id').on('change', actualizarContagensEmpreendimento);
    actualizarContagensEmpreendimento();

    $('input[name="canal"], input[name="proposta_id"], #estados, #comercial_id, #empreendimento, #repetir').on('change', function () {
        limparAvisos();
    });

    // Apagar proposta: POST com o CSRF do form principal (nunca GET para
    // ações destrutivas — os forms não podem ser aninhados, daí este submit).
    $('.btn-apagar-proposta').on('click', function () {
        if (!confirm('Apagar esta proposta? Os envios já feitos ficam no Registo de Envios.')) {
            return;
        }
        var $f = $('<form>', { method: 'post', action: urlApagar + $(this).data('id') });
        $form.find('input[type="hidden"]').clone().appendTo($f);
        $f.appendTo('body').submit();
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

            /*
             * DUAS COISAS DIFERENTES, e a redacção antiga misturava-as.
             *
             * O filtro do EMPREENDIMENTO escolhe quem já recebeu proposta
             * daquele empreendimento — é o alvo. A desduplicação salta quem já
             * recebeu ESTE MESMO PDF — é outra coisa, e é por ficheiro.
             *
             * Lidas juntas, pareciam contradizer-se: "enviar a quem já recebeu"
             * e logo a seguir "quem já recebeu é saltado". Agora cada uma diz
             * ao que vem. Corrigido a 05/08/2026.
             */
            var emp = $('#empreendimento').val() || '';

            var html = '<strong>' + r.com_contacto + '</strong> lead(s) receberão a proposta';
            html += ' (' + r.total + ' no total';
            html += emp ? ' com proposta de ' + $('<span>').text(emp).html() + ' já enviada' : ' nos estados escolhidos';
            if (r.excluidas > 0) {
                html += ', <strong>' + r.excluidas + ' excluída(s)</strong> por não terem o contacto necessário ao canal';
            }
            html += ').<ul>';
            $.each(r.estados, function (i, e) {
                html += '<li>' + $('<span>').text(e.estado_nome || ('Estado #' + e.estado_id)).html()
                    + ': ' + e.com_contacto + ' de ' + e.total + '</li>';
            });
            html += '</ul>';

            if ($('#repetir').is(':checked')) {
                html += '<small>Inclui quem já recebeu <strong>este mesmo ficheiro</strong>.</small>';
            } else {
                html += '<small>Quem já recebeu <strong>este mesmo ficheiro</strong> é saltado'
                      + (emp ? ' — nada a ver com o filtro do empreendimento, que é quem recebeu proposta de '
                             + $('<span>').text(emp).html() : '')
                      + '. Para reenviar o mesmo PDF, marque a caixa em baixo.</small>';
            }

            $preview.html(html).show();
            // O botão de enviar não depende disto: ele próprio conta as leads
            // e pede confirmação. (Deixá-lo desativado até se carregar em
            // "Pré-visualizar" parecia uma avaria.)
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

    function enviarLote(dados, lastId, totais) {
        // Dados congelados da campanha + CSRF fresco + cursor do lote.
        var pedido = dados.concat($form.find('input[type="hidden"]').serializeArray());
        pedido.push({ name: 'last_id', value: lastId });
        // Escolha do utilizador: reenviar a quem já recebeu esta proposta.
        pedido.push({ name: 'repetir', value: $('#repetir').is(':checked') ? 1 : 0 });

        $.post(urlEnviar, $.param(pedido), function (r) {
            atualizarCsrf(r);
            if (r.erro) {
                aEnviar = false;
                $progresso.hide();
                mostrarErro(r.erro);
                if (r.aviso) { mostrarErro(r.aviso); }
                bloquearCampos(false);
                $('#btn-enviar, #btn-preview, #btn-teste').prop('disabled', false);
                return;
            }

            totais.enviados += r.enviados;
            totais.falhados += r.falhados;

            $progresso.html('A enviar a proposta… <strong>' + totais.enviados + '</strong> enviadas, '
                + '<strong>' + totais.falhados + '</strong> falhadas até agora.').show();

            if (!r.fim) {
                enviarLote(dados, r.last_id, totais);
            } else {
                aEnviar = false;
                $progresso.hide();
                $resumo.html('Concluído: <strong>' + totais.enviados + '</strong> propostas enviadas, '
                    + '<strong>' + totais.falhados + '</strong> falhadas. '
                    + 'Consulte o <a href="<?php echo admin_url('dps_automacao/envios'); ?>">Registo de Envios</a>.').show();
                bloquearCampos(false);
                $('#btn-preview, #btn-teste, #btn-enviar').prop('disabled', false);
            }
        }, 'json').fail(function () {
            aEnviar = false;
            $progresso.hide();
            mostrarErro('O envio foi interrompido a meio. Consulte o Registo de Envios antes de repetir — as leads já processadas não voltarão a receber a proposta.');
            bloquearCampos(false);
            $('#btn-preview, #btn-teste').prop('disabled', false);
        });
    }

    $('#btn-enviar').on('click', function () {
        if (aEnviar) {
            return;
        }
        if (!$('input[name="proposta_id"]:checked').length) {
            mostrarErro('Escolha (ou carregue) a proposta em PDF antes de enviar.');
            return;
        }
        // Contar SEMPRE antes de enviar: assim a confirmação mostra o número
        // real de leads e ninguém dispara às cegas — sem obrigar a carregar
        // primeiro em "Pré-visualizar".
        var $btn = $(this).prop('disabled', true);
        limparAvisos();

        // dadosDoForm() (não dadosCampanha) — esta última exclui os campos
        // hidden, e é lá que vai o token CSRF: sem ele o pedido é recusado e
        // aparecia "Não foi possível contar as leads".
        $.post(urlPreview, dadosDoForm(), function (r) {
            atualizarCsrf(r);
            $btn.prop('disabled', false);

            if (r.erro) { mostrarErro(r.erro); return; }

            if (!r.com_contacto) {
                mostrarErro('Não há nenhuma lead com email/WhatsApp nos estados escolhidos.');
                return;
            }

            if (!confirm('Vai enviar esta proposta a ' + r.com_contacto + ' lead(s). '
                       + 'As que já receberam este mesmo ficheiro são saltadas. Esta ação não pode ser anulada. Confirma?')) {
                return;
            }

            // Serializar ANTES de bloquear (campos disabled não serializam) e
            // congelar: os lotes seguintes usam sempre estes dados.
            var dados = dadosCampanha();

            aEnviar = true;
            bloquearCampos(true);
            $('#btn-enviar, #btn-preview, #btn-teste').prop('disabled', true);
            enviarLote(dados, 0, { enviados: 0, falhados: 0 });
        }, 'json').fail(function () {
            $btn.prop('disabled', false);
            mostrarErro('Não foi possível contar as leads. Tente de novo.');
        });
    });
});
</script>
