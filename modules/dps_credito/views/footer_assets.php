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
                <button type="button" class="btn btn-info" id="dps-credito-guardar">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function ($) {
    'use strict';

    var adminUrl = '<?php echo admin_url(); ?>';

    /**
     * Mostra ou esconde os campos consoante as respostas. O banco só é
     * obrigatório quando o cliente já tem financiamento — num pedido novo pode
     * ainda não haver banco escolhido.
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
    }

    $(document).on('change', '#dps-credito-modal input[name="abordado"], #dps-credito-situacao, #dps-credito-modal input[name="interessado_proposta"]', aplicarRegras);

    // Abrir o questionário — a partir da listagem de leads ou da ficha
    $(document).on('click', '.dps-credito-abrir', function () {
        var leadId = $(this).data('lead');

        $('#dps-credito-modal-body').html('<p class="text-center text-muted">A carregar...</p>');
        $('#dps-credito-modal').modal('show');

        $.get(adminUrl + 'dps_credito/form_lead/' + leadId, function (html) {
            $('#dps-credito-modal-body').html(html);
            aplicarRegras();
        });
    });

    $(document).on('click', '#dps-credito-guardar', function () {
        var $form = $('#dps-credito-form');
        if (!$form.length) {
            return;
        }

        var $botao = $(this);
        $botao.prop('disabled', true).text('A guardar...');

        // O Perfex exige o token CSRF em todos os POST (senão devolve 419
        // "Page Expired"). O serialize() do formulário não o inclui, por isso
        // acrescentamo-lo a partir da variável global csrfData do Perfex.
        var dadosPost = $form.serialize();
        if (typeof csrfData !== 'undefined') {
            dadosPost += '&' + encodeURIComponent(csrfData.token_name)
                + '=' + encodeURIComponent(csrfData.hash);
        }

        $.post(
            adminUrl + 'dps_credito/guardar_resposta/' + $form.data('lead'),
            dadosPost,
            function (resposta) {
                $botao.prop('disabled', false).text('Guardar');

                if (!resposta.success) {
                    alert_float('danger', resposta.message);
                    return;
                }

                $('#dps-credito-modal').modal('hide');
                alert_float('success', resposta.message);

                // Recarregar para a coluna reflectir a resposta nova
                setTimeout(function () {
                    window.location.reload();
                }, 1200);
            },
            'json'
        ).fail(function () {
            $botao.prop('disabled', false).text('Guardar');
            alert_float('danger', 'Não foi possível guardar o questionário.');
        });
    });

    /**
     * O arrastar do kanban ignora o corpo da resposta, por isso um bloqueio do
     * lado do servidor passaria despercebido: o cartão ficava na coluna nova e
     * a base de dados não mudava. Apanhamos aqui a bandeira e desfazemos o
     * engano à vista do utilizador.
     */
    $(document).ajaxComplete(function (evento, xhr, opcoes) {
        if (!opcoes.url || opcoes.url.indexOf('leads/update_lead_status') === -1) {
            return;
        }

        var dados;
        try {
            dados = JSON.parse(xhr.responseText);
        } catch (e) {
            return;
        }

        if (dados && dados.dps_credito_bloqueado) {
            alert_float('warning', dados.message);

            $('#dps-credito-modal-body').html('<p class="text-center text-muted">A carregar...</p>');
            $('#dps-credito-modal').modal('show');

            $.get(adminUrl + 'dps_credito/form_lead/' + dados.lead_id, function (html) {
                $('#dps-credito-modal-body').html(html);
                aplicarRegras();
            });
        }
    });
})(jQuery);
</script>
