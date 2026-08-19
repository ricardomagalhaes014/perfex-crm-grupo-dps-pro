<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="dps-credito-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Crédito — questionário</h4>
            </div>
            <div class="modal-body" id="dps-credito-modal-body">
                <p class="text-center text-muted">A carregar...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="dps-credito-guardar">Guardar e continuar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function ($) {
    'use strict';

    var adminUrl = '<?php echo admin_url(); ?>';

    // Estados de lead que contam como "fechar", vindos das Definições.
    var fechoStatuses = <?php echo json_encode(array_map('strval', dps_credito_estados_fecho())); ?>;

    // Acção a executar quando a resposta for guardada com sucesso (aplicar o
    // fecho que o utilizador tinha começado). Null = apenas fechar o modal.
    var accaoPendente = null;

    function precisaQuestionario(leadId) {
        // Devolve uma promessa com o booleano "precisa".
        return $.get(adminUrl + 'dps_credito/estado_lead/' + leadId).then(function (r) {
            try {
                var d = typeof r === 'string' ? JSON.parse(r) : r;
                return !!d.precisa;
            } catch (e) {
                // Em caso de dúvida, não travamos o trabalho do comercial.
                return false;
            }
        }, function () {
            return false;
        });
    }

    function abrirQuestionario(leadId, aoConcluir) {
        accaoPendente = aoConcluir || null;

        $('#dps-credito-modal-body').html('<p class="text-center text-muted">A carregar...</p>');
        $('#dps-credito-modal').modal('show');

        $.get(adminUrl + 'dps_credito/form_lead/' + leadId, function (html) {
            $('#dps-credito-modal-body').html(html);
            aplicarRegras();
        });
    }

    /**
     * Mostra/esconde os campos do questionário conforme as respostas.
     * O banco só é obrigatório quando o cliente já tem financiamento.
     */
    function aplicarRegras() {
        var abordado = $('input[name="abordado"]:checked').val();
        var situacao = $('#dps-credito-situacao').val();
        var interessado = $('input[name="interessado_proposta"]:checked').val();

        // Assim que há escolha, tira o destaque de campo em falta.
        if (abordado) {
            $('#dps-credito-abordado-sim').closest('.form-group').removeClass('has-error');
        }

        /*
         * O bloco de perguntas deixou de abrir com o "sim".
         *
         * O que segue para o parceiro é a ficha da lead, por email — e as
         * perguntas (situação, banco, montante) eram respondidas de cor pelo
         * comercial e voltavam a ser feitas ao cliente na mesma. Fica visível
         * apenas quando já há respostas gravadas de antes, para não se
         * esconder informação que alguém deu.
         */
        var temRespostasAntigas = $('#dps-credito-detalhes').data('preenchido') === 1;
        $('#dps-credito-detalhes').toggle(abordado === 'sim' && temRespostasAntigas);
        $('#dps-credito-aviso-parceiro').toggle(abordado === 'sim');

        var jaFinanciado = situacao === 'financiamento_existente';
        $('#dps-credito-banco-obrigatorio').toggle(jaFinanciado);
        $('#dps-credito-banco-ajuda').text(
            jaFinanciado ? 'Banco onde o cliente está financiado.' : 'Banco pretendido (opcional).'
        );

        $('#dps-credito-aviso-proposta').toggle(interessado === 'sim');
        $('#dps-credito-docs-grupo').toggle(interessado === 'sim');
    }

    $(document).on('change',
        '#dps-credito-modal input[name="abordado"], #dps-credito-situacao, #dps-credito-modal input[name="interessado_proposta"]',
        aplicarRegras);

    // Abrir o questionário a partir da coluna/painel (sem acção pendente).
    $(document).on('click', '.dps-credito-abrir', function () {
        abrirQuestionario($(this).data('lead'), null);
    });

    // Guardar a resposta
    $(document).on('click', '#dps-credito-guardar', function () {
        var $form = $('#dps-credito-form');
        if (!$form.length) {
            return;
        }

        // Obrigatório: para alterar o estado, o crédito tem SEMPRE de estar
        // marcado como Sim ou Não. Sem isso não avançamos e dizemos porquê.
        var abordado = $form.find('input[name="abordado"]:checked').val();
        if (!abordado) {
            $('#dps-credito-abordado-sim').closest('.form-group').addClass('has-error');
            alert_float('warning', 'O campo "Crédito abordado?" não está selecionado. Escolha Sim ou Não para alterar o estado.');
            return;
        }

        var $botao = $(this);
        $botao.prop('disabled', true).text('A guardar...');

        // FormData para levar os ficheiros; token CSRF acrescentado (o Perfex
        // devolve 419 sem ele).
        var fd = new FormData($form[0]);
        if (typeof csrfData !== 'undefined') {
            fd.append(csrfData.token_name, csrfData.hash);
        }

        $.ajax({
            url: adminUrl + 'dps_credito/guardar_resposta/' + $form.data('lead'),
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (resposta) {
                $botao.prop('disabled', false).text('Guardar e continuar');

                if (!resposta.success) {
                    alert_float('danger', resposta.message);
                    return;
                }

                $('#dps-credito-modal').modal('hide');
                alert_float('success', resposta.message);

                var accao = accaoPendente;
                accaoPendente = null;

                if (typeof accao === 'function') {
                    setTimeout(accao, 300);
                } else {
                    setTimeout(function () { window.location.reload(); }, 1000);
                }
            },
            error: function () {
                $botao.prop('disabled', false).text('Guardar e continuar');
                alert_float('danger', 'Não foi possível guardar o questionário.');
            }
        });
    });

    /* ------------------------------------------------------------------
     * Gravar nota exige o crédito respondido
     * ------------------------------------------------------------------
     * Cada interação registada é o momento natural para saber se o crédito
     * foi abordado. Intercetamos o "Guardar" da nota (popup da tabela de
     * leads): se a lead ainda estiver INDEFINIDA, abre-se o questionário e
     * a nota é gravada logo a seguir, sem o comercial perder o que escreveu.
     */
    /* ------------------------------------------------------------------
     * Responder Sim/Não na própria tabela de leads (sem abrir a lead)
     * ------------------------------------------------------------------
     * "Não" grava directamente. "Sim" tem de abrir o questionário, porque
     * a resposta afirmativa exige situação/banco/montante/proposta — gravar
     * só "sim" deixaria o processo incompleto.
     */
    $(document).on('click', '.dps-credito-nao', function () {
        var $b = $(this), leadId = $b.data('lead');
        if (!leadId || $b.prop('disabled')) { return; }
        $b.prop('disabled', true).text('...');

        var dados = { abordado: 'nao' };
        if (typeof csrfData !== 'undefined') { dados[csrfData.token_name] = csrfData.hash; }

        $.post(adminUrl + 'dps_credito/responder_rapido/' + leadId, dados, null, 'json')
            .done(function (r) {
                if (r && r.success) {
                    // Actualizar a célula no sítio: etiqueta passa a "Não
                    // abordado" e o botão Não fica realçado como activo.
                    var $cel = $b.closest('.dps-credito-inline');
                    $cel.find('.label').remove();
                    $cel.prepend('<span class="label label-default">Não abordado</span> ');
                    $cel.find('.dps-credito-sim').removeClass('btn-success').addClass('btn-default');
                    $b.prop('disabled', false).text('Não')
                      .removeClass('btn-default').addClass('btn-success');
                    if (typeof alert_float === 'function') { alert_float('success', 'Crédito: Não abordado.'); }
                } else {
                    $b.prop('disabled', false).text('Não');
                    alert_float('danger', (r && r.message) || 'Não foi possível gravar.');
                }
            })
            .fail(function () {
                $b.prop('disabled', false).text('Não');
                alert_float('danger', 'Não foi possível gravar.');
            });
    });

    $(document).on('click', '.dps-credito-sim', function () {
        abrirQuestionario($(this).data('lead'), null);
    });

    var _dpsNotaLiberta = false;

    // Fase de CAPTURA: o handler que grava a nota está registado em document
    // (bubble) e foi registado antes deste. Só apanhando o clique na captura
    // é que conseguimos decidir ANTES de ele gravar.
    document.addEventListener('click', function (ev) {
        var btn = ev.target && ev.target.closest ? ev.target.closest('#dps-note-save') : null;
        if (!btn) { return; }

        if (_dpsNotaLiberta) { _dpsNotaLiberta = false; return; }   // já validado

        var leadId = window._dpsNoteLeadId || null;
        if (!leadId) { return; }

        ev.preventDefault();
        ev.stopPropagation();

        var reenviar = function () {
            _dpsNotaLiberta = true;
            btn.click();
        };

        precisaQuestionario(leadId).then(function (precisa) {
            if (!precisa) { reenviar(); return; }

            // Não perder o que já estava escrito.
            var notaEscrita = $('#dps-note-text').val();
            $('#dps-note-popup').modal('hide');

            abrirQuestionario(leadId, function () {
                $('#dps-note-popup').modal('show');
                setTimeout(function () {
                    $('#dps-note-text').val(notaEscrita);
                    reenviar();
                }, 500);
            });
        }).catch(function () { reenviar(); });   // em dúvida, não travar o trabalho
    }, true);

    // Comercial: anexar documentos em falta e voltar a submeter (do painel da lead)
    $(document).on('click', '#dps-credito-resubmeter-btn', function () {
        var $form = $('#dps-credito-resubmeter');
        var $btn = $(this);
        $btn.prop('disabled', true).text('A submeter...');

        var fd = new FormData($form[0]);
        if (typeof csrfData !== 'undefined') {
            fd.append(csrfData.token_name, csrfData.hash);
        }

        $.ajax({
            url: adminUrl + 'dps_credito/resubmeter/' + $form.data('credito'),
            method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json',
            success: function (r) {
                alert_float('success', (r && r.message) || 'Submetido.');
                setTimeout(function () { window.location.reload(); }, 1000);
            },
            error: function () {
                $btn.prop('disabled', false).text('Anexar e voltar a submeter');
                alert_float('danger', 'Não foi possível submeter.');
            }
        });
    });

    /* ------------------------------------------------------------------
     * Interceção das duas formas de fechar uma lead no Perfex.
     * Substituímos as funções GLOBAIS do Perfex — ponto limpo, sem mexer
     * em ordem de eventos nem no token CSRF.
     * ------------------------------------------------------------------ */

    // (1) Formulário da ficha da lead → lead_profile_form_handler(form)
    if (typeof window.lead_profile_form_handler === 'function') {
        var _origLeadHandler = window.lead_profile_form_handler;

        window.lead_profile_form_handler = function (form) {
            var self = this;
            var args = arguments;
            var $form = $(form);
            var status = String($form.find('[name="status"]').val() || '');
            var leadId = $('#lead-modal').find('input[name="leadid"]').val();

            // Não é um fecho, ou já validámos nesta submissão → segue normal.
            if (fechoStatuses.indexOf(status) === -1 || !leadId || $form.data('dpsOk')) {
                $form.removeData('dpsOk');
                return _origLeadHandler.apply(self, args);
            }

            precisaQuestionario(leadId).then(function (precisa) {
                if (!precisa) {
                    $form.data('dpsOk', true);
                    _origLeadHandler.apply(self, args);
                    return;
                }
                // Trava o fecho, abre o questionário; ao guardar, re-submete.
                abrirQuestionario(leadId, function () {
                    $form.data('dpsOk', true);
                    _origLeadHandler.apply(self, args);
                });
            });

            // Não chama o original agora — a decisão é assíncrona.
        };
    }

    // (2) Arrastar no kanban → leads_kanban_update(ui, object)
    if (typeof window.leads_kanban_update === 'function') {
        var _origKanban = window.leads_kanban_update;

        window.leads_kanban_update = function (ui, object) {
            var self = this;
            var args = arguments;
            var status = String($(ui.item.parent()[0]).attr('data-lead-status-id') || '');
            var leadId = $(ui.item).attr('data-lead-id');

            if (fechoStatuses.indexOf(status) === -1 || !leadId) {
                return _origKanban.apply(self, args);
            }

            precisaQuestionario(leadId).then(function (precisa) {
                if (!precisa) {
                    _origKanban.apply(self, args);
                    return;
                }
                // Repõe o quadro (o cartão volta ao sítio, pois nada gravou) e
                // abre o questionário; ao guardar, aplica a mudança de estado.
                if (typeof leads_kanban === 'function') {
                    leads_kanban();
                }
                abrirQuestionario(leadId, function () {
                    var dados = { status: status, leadid: leadId };
                    if (typeof csrfData !== 'undefined') {
                        dados[csrfData.token_name] = csrfData.hash;
                    }
                    $.post(adminUrl + 'leads/update_lead_status', dados).always(function () {
                        if (typeof leads_kanban === 'function') {
                            leads_kanban();
                        }
                    });
                });
            });
        };
    }
})(jQuery);
</script>
