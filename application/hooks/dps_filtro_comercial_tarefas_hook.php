<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Filtro por comercial na lista de tarefas (/admin/tasks).
 *
 * O Perfex 3.4 já tem um filtro por responsável, mas está escondido dentro do
 * construtor de filtros em Vue: são três cliques e um painel para responder à
 * pergunta mais frequente do dia — "quais são as minhas?". Isto põe a resposta
 * a um clique, num selector à vista por cima da tabela.
 *
 * COMO A TABELA RECEBE O FILTRO
 *
 * O main.js do Perfex, ao arrancar a tabela das tarefas, faz:
 *
 *     v = $("._hidden_inputs._filters._tasks_filters input");
 *     $.each(v, function(){ b[$(this).attr("name")] = '[name="'+...+'"]'; });
 *
 * ou seja, QUALQUER input dentro desse contentor passa a ser enviado ao
 * servidor em cada pedido da tabela. É o mecanismo do próprio Perfex — não é
 * preciso inventar nenhum.
 *
 * O contentor tem de existir ANTES de o main.js correr. Por isso o campo é
 * escrito no gancho before_js_scripts_render (linha 4 do scripts.php) e não no
 * rodapé, que só é servido na linha 46 — depois dos scripts. Escrito no
 * rodapé, o campo existiria no HTML mas a tabela já teria arrancado sem ele.
 *
 * Nota: o ficheiro views/admin/tasks/tasks_filter_by.php traz um contentor
 * destes, mas é código morto no 3.4 — nada o carrega. Daí escrevermos o nosso.
 *
 * O lado do servidor está em views/admin/tables/tasks.php, ao lado do
 * my_tasks, que funciona exactamente da mesma maneira.
 */

if (!function_exists('dps_filtro_comercial_tarefas_register')) {
    function dps_filtro_comercial_tarefas_register()
    {
        hooks()->add_action('before_js_scripts_render', 'dps_filtro_comercial_campo');
        hooks()->add_action('app_admin_footer', 'dps_filtro_comercial_selector');
    }
}

/**
 * Só na lista de tarefas. Noutras páginas o selector não teria onde encaixar,
 * e o campo escondido acabaria a ser enviado por tabelas que não o esperam —
 * incluindo a do painel inicial, que tem um contentor destes só dela.
 */
if (!function_exists('dps_filtro_comercial_e_pagina_das_tarefas')) {
    function dps_filtro_comercial_e_pagina_das_tarefas()
    {
        $CI = &get_instance();

        return $CI->uri->segment(1) === 'admin'
            && $CI->uri->segment(2) === 'tasks'
            && in_array((string) $CI->uri->segment(3), ['', 'index'], true);
    }
}

if (!function_exists('dps_filtro_comercial_campo')) {
    function dps_filtro_comercial_campo()
    {
        if (!dps_filtro_comercial_e_pagina_das_tarefas() || !staff_can('view', 'tasks')) {
            return;
        }
        ?>
<div class="_hidden_inputs _filters _tasks_filters" style="display:none;">
    <input type="hidden" name="dps_comercial" value="">
</div>
        <?php
    }
}

if (!function_exists('dps_filtro_comercial_selector')) {
    function dps_filtro_comercial_selector()
    {
        /*
         * Quem não pode ver as tarefas de todos já só vê as suas — o CRM
         * aplica-lhe o filtro de visibilidade em todo o lado. Mostrar-lhe um
         * selector de comerciais seria oferecer uma escolha que não existe.
         */
        if (!dps_filtro_comercial_e_pagina_das_tarefas() || !staff_can('view', 'tasks')) {
            return;
        }

        $CI  = &get_instance();
        $eu  = (int) get_staff_user_id();
        $CI->load->model('staff_model');

        $comerciais = [];
        foreach ($CI->staff_model->get('', ['active' => 1]) as $s) {
            $id = (int) $s['staffid'];
            if ($id === $eu) {
                continue;                       // vai à cabeça, com nome próprio
            }
            $comerciais[$id] = trim($s['firstname'] . ' ' . $s['lastname']);
        }
        asort($comerciais, SORT_NATURAL | SORT_FLAG_CASE);
        ?>
<script>
$(function () {
    var tabela = $('.table-tasks');
    if (!tabela.length) { return; }

    var opcoes = ''
        + '<option value="">Todos os comerciais</option>'
        + '<option value="<?= $eu; ?>">As minhas tarefas</option>'
        + '<option disabled>──────────</option>'
        <?php foreach ($comerciais as $id => $nome) { ?>
        + '<option value="<?= $id; ?>"><?= addslashes(html_escape($nome)); ?></option>'
        <?php } ?>;

    var barra = $(''
        + '<div class="tw-mb-3" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">'
        +   '<label for="dps-filtro-comercial" class="control-label" style="margin:0;white-space:nowrap;">'
        +     '<i class="fa fa-user tw-mr-1"></i> Comercial'
        +   '</label>'
        +   '<select id="dps-filtro-comercial" class="form-control" style="width:auto;min-width:240px;">'
        +     opcoes
        +   '</select>'
        +   '<span id="dps-filtro-aviso" class="text-muted" style="font-size:13px;"></span>'
        + '</div>');

    tabela.closest('.panel-body').prepend(barra);

    $('#dps-filtro-comercial').on('change', function () {
        var escolhido = $(this).val();

        // O campo escondido é o que o main.js do Perfex envia ao servidor.
        $('input[name="dps_comercial"]').val(escolhido);

        $('#dps-filtro-aviso').text(
            escolhido === '' ? '' : 'A mostrar só as tarefas de ' + $(this).find('option:selected').text() + '.'
        );

        tabela.DataTable().ajax.reload();
    });
});
</script>
        <?php
    }
}
