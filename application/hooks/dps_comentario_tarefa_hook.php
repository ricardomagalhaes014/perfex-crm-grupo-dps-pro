<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * O comentário de tarefa que gravava mas deixava a janela presa.
 *
 * Sintoma relatado a 01/08/2026: carrega-se em gravar, o botão fica em "Por
 * favor aguarde" para sempre, a janela não fecha — mas o comentário está lá
 * quando se recarrega a página.
 *
 * A causa está no JavaScript de origem do Perfex:
 *
 *     $.post(admin_url+"tasks/add_task_comment", t).done(function (e) {
 *         _task_append_html((e = JSON.parse(e)).taskHtml);   // <-- rebenta aqui
 *         tinymce.remove("#task_comment");
 *     })
 *
 * Não há .fail() nem try/catch. O servidor grava o comentário e só DEPOIS
 * monta a resposta, que inclui o HTML da tarefa inteira. Se essa segunda
 * metade sair mal — resposta vazia, resposta truncada, erro de PHP, pedido
 * que demora demais — o JSON.parse lança uma exceção, a linha seguinte nunca
 * corre, e o botão fica eternamente no estado de carregamento que o
 * data-loading-text lhe pôs. O comentário, esse, já está gravado.
 *
 * Este ficheiro faz duas coisas:
 *
 * 1. REPARA o sintoma. Substitui a função por uma versão que trata a
 *    resposta ilegível, tem prazo máximo, e vai buscar a tarefa outra vez em
 *    vez de deixar o utilizador à espera de nada. O que quer que corra mal
 *    do lado do servidor, a janela responde.
 *
 * 2. REGISTA a causa. Enquanto DPS_DIAGNOSTICO_COMENTARIO estiver a true,
 *    cada gravação de comentário escreve uma linha em
 *    application/logs/dps-comentario.log com o tempo que demorou, o tamanho
 *    da resposta e o erro fatal, se houver. É o que falta para saber porque
 *    é que a resposta sai mal — até aqui a avaria não deixava rasto nenhum.
 *
 * Quando a causa estiver identificada e corrigida, pôr a constante a false.
 * A reparação do ponto 1 fica — vale por si, independentemente da causa.
 */

/** Registo temporário, para apanhar a causa. Pôr a false quando resolvido. */
define('DPS_DIAGNOSTICO_COMENTARIO', true);

if (!function_exists('dps_comentario_tarefa_register')) {
    function dps_comentario_tarefa_register()
    {
        hooks()->add_action('app_admin_footer', 'dps_comentario_tarefa_js');

        if (DPS_DIAGNOSTICO_COMENTARIO) {
            dps_comentario_tarefa_gravar_diagnostico();
        }
    }
}

/**
 * Anota como correu este pedido de gravação de comentário.
 *
 * Corre em register_shutdown_function para apanhar também os pedidos que
 * morrem a meio: é precisamente esses que interessam, e são os únicos que de
 * outra forma não deixariam vestígio.
 */
if (!function_exists('dps_comentario_tarefa_gravar_diagnostico')) {
    function dps_comentario_tarefa_gravar_diagnostico()
    {
        if (strpos((string) ($_SERVER['REQUEST_URI'] ?? ''), 'add_task_comment') === false) {
            return;
        }

        $inicio  = microtime(true);
        $tarefa  = (string) ($_POST['taskid'] ?? '?');

        register_shutdown_function(function () use ($inicio, $tarefa) {
            $fatal = error_get_last();

            /*
             * connection_status(): 0 normal, 1 o browser desistiu,
             * 2 tempo de execução esgotado. Distingue "o servidor demorou"
             * de "o utilizador fechou" — que são avarias diferentes.
             */
            $estados = [0 => 'normal', 1 => 'browser-desistiu', 2 => 'tempo-esgotado'];

            $corpo = @ob_get_contents();
            $corpo = is_string($corpo) ? $corpo : '';

            $linha = sprintf(
                "[%s] tarefa=%-6s staff=%-4s %6.1fs http=%s bytes=%-7s ligacao=%s memoria=%sMB %s%s\n",
                date('Y-m-d H:i:s'),
                $tarefa,
                function_exists('get_staff_user_id') ? (string) @get_staff_user_id() : '?',
                microtime(true) - $inicio,
                (string) @http_response_code(),
                $corpo === '' ? 'nao-visivel' : strlen($corpo),
                $estados[connection_status()] ?? connection_status(),
                round(memory_get_peak_usage(true) / 1048576),
                $fatal ? ('FATAL: ' . $fatal['message'] . ' @ '
                          . basename((string) $fatal['file']) . ':' . $fatal['line']) : 'sem-erro',
                $corpo !== '' && substr(ltrim($corpo), 0, 1) !== '{'
                    ? ' | resposta NAO comeca por { : ' . substr(preg_replace('/\s+/', ' ', strip_tags($corpo)), 0, 160)
                    : ''
            );

            @file_put_contents(APPPATH . 'logs/dps-comentario.log', $linha, FILE_APPEND);
        });
    }
}

/**
 * Substitui add_task_comment por uma versão que não deixa a janela pendurada.
 *
 * Definida no arranque do documento, depois de todos os ficheiros de origem
 * estarem lidos, para garantir que é esta que fica — e não a do Perfex.
 */
if (!function_exists('dps_comentario_tarefa_js')) {
    function dps_comentario_tarefa_js()
    {
        ?>
<script>
$(function () {
    if (typeof _task_append_html !== 'function') { return; }   // não é uma página de tarefas

    window.add_task_comment = function (tarefa) {
        var $botao = $('#addTaskCommentBtn');

        function libertarBotao() {
            try { $botao.button('reset'); } catch (e) {}
            $botao.prop('disabled', false);
        }

        /*
         * O comentário é gravado no servidor ANTES de a resposta ser montada.
         * Por isso, quando a resposta sai mal, a atitude certa não é dizer que
         * falhou — é ir buscar a tarefa outra vez e mostrar o que lá está.
         */
        function recarregarTarefa(aviso) {
            $.get(admin_url + 'tasks/get_task_data/' + tarefa)
                .done(function (html) {
                    _task_append_html(html);
                    tinymce.remove('#task_comment');
                })
                .always(function () {
                    libertarBotao();
                    if (aviso) { alert_float('warning', aviso); }
                });
        }

        if (typeof taskCommentAttachmentDropzone !== 'undefined'
            && taskCommentAttachmentDropzone.files.length > 0) {
            return taskCommentAttachmentDropzone.processQueue(tarefa);
        }

        var dados = { taskid: tarefa };
        if (tinymce.activeEditor) {
            dados.content = tinyMCE.activeEditor.getContent();
        } else {
            dados.content   = $('#task_comment').val();
            dados.no_editor = true;
        }

        $.ajax({
            url:     admin_url + 'tasks/add_task_comment',
            type:    'POST',
            data:    dados,
            timeout: 45000               // 45s. O limite do PHP são 480s: sem isto, o botão girava 8 minutos.
        })
        .done(function (resposta) {
            var r = null;
            try { r = JSON.parse(resposta); } catch (e) { r = null; }

            if (r && r.taskHtml) {
                _task_append_html(r.taskHtml);
                tinymce.remove('#task_comment');
                libertarBotao();
                return;
            }
            recarregarTarefa('O comentário foi gravado, mas a confirmação veio ilegível. A janela foi atualizada.');
        })
        .fail(function (xhr, estado) {
            recarregarTarefa(estado === 'timeout'
                ? 'O servidor demorou mais de 45 segundos. O comentário pode ter ficado gravado — confirme abaixo.'
                : 'Não foi possível gravar o comentário (erro ' + xhr.status + ').');
        });
    };
});
</script>
        <?php
    }
}
