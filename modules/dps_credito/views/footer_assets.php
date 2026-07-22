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

        $('#dps-credito-detalhes').toggle(abordado === 'sim');

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
