<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * A lista de clientes mostra o que interessa, e a ficha mostra o que ele comprou.
 *
 * LISTA — a tabela vinha com dez colunas, das quais quatro não dizem nada a
 * quem vende imobiliário: o contacto principal (que repete o nome), o
 * activo/inactivo, os grupos (que não são usados) e um "Status" que alguém
 * acrescentou e ficou vazio. Ficam o nome, o email, o telefone e a data.
 *
 * São ESCONDIDAS, não removidas. Arrancá-las obrigaria a mexer em dois
 * ficheiros do núcleo que têm de ficar sincronizados — o cabeçalho em
 * clients/manage.php e o SQL em tables/clients.php — e os índices das colunas
 * são usados pela ordenação guardada, pelo number-index e pelos filtros. Um
 * engano ali deixa a lista de clientes partida para toda a gente. Escondidas,
 * o resultado no ecrã é o mesmo, nada quebra, e quem quiser volta a mostrá-las
 * pelo próprio botão de colunas do Perfex.
 *
 * FICHA — as compras não estavam em lado nenhum: o cliente é criado a partir
 * de uma venda concluída mas a ficha não dizia o que ele tinha comprado.
 * Passa a haver um separador "Compras" com o empreendimento, a fracção, o
 * valor e a data — que é a pergunta que se faz ao abrir a ficha.
 */

if (!function_exists('dps_clientes_register')) {
    function dps_clientes_register()
    {
        hooks()->add_action('app_admin_footer', 'dps_clientes_esconder_colunas');
        hooks()->add_filter('client_filtered_visible_tabs', 'dps_clientes_separador_compras');
    }
}

if (!function_exists('dps_clientes_esconder_colunas')) {
    function dps_clientes_esconder_colunas()
    {
        $CI = &get_instance();

        // Só na lista de clientes. A ficha de um cliente é outra página.
        if ($CI->uri->segment(1) !== 'admin' || $CI->uri->segment(2) !== 'clients'
            || !in_array((string) $CI->uri->segment(3), ['', 'index'], true)) {
            return;
        }
        ?>
<script>
(function () {
    /*
     * Pelos ids do cabeçalho, não por posição: a posição muda assim que
     * alguém acrescentar um campo personalizado à tabela, e a coluna
     * escondida passaria a ser outra.
     */
    var esconder = ['#th-primary-contact', '#th-active', '#th-groups'];

    /*
     * A tabela é montada pelo main.js do Perfex, e não há garantia de que já
     * exista quando este bloco corre — à primeira tentativa não existia, e o
     * script saía em silêncio sem esconder nada. Em vez de assumir a ordem,
     * espera-se por ela. Cinco segundos chegam de sobra; passado isso desiste
     * em vez de ficar a rodar para sempre.
     */
    var tentativas = 0;

    function aplicar() {
        if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) { return false; }

        var tabela = $('#clients');
        if (!tabela.length || !$.fn.DataTable.isDataTable(tabela)) { return false; }

        var api = tabela.DataTable();

        /*
         * Os índices são recolhidos TODOS primeiro, e só depois se escondem —
         * de trás para a frente.
         *
         * Esconder uma coluna faz o DataTables retirá-la do DOM, e as
         * seguintes deslocam-se. À primeira versão escondi uma a uma lendo a
         * posição no momento: escondida a primeira, as leituras seguintes já
         * vinham trocadas e desapareceram o email e o telefone em vez do que
         * era para desaparecer.
         */
        var indices = [];
        for (var i = 0; i < esconder.length; i++) {
            var th = tabela.find(esconder[i]);
            if (th.length) { indices.push(th.index()); }
        }
        indices.sort(function (a, b) { return b - a; });

        for (var j = 0; j < indices.length; j++) {
            try { api.column(indices[j]).visible(false, false); } catch (e) {}
        }
        api.columns.adjust();

        return true;
    }

    var relogio = setInterval(function () {
        if (aplicar() || ++tentativas > 50) { clearInterval(relogio); }
    }, 100);
})();
</script>
        <?php
    }
}

/**
 * Acrescenta o separador "Compras" à ficha do cliente.
 *
 * Entra pelo filtro que devolve os separadores visíveis — é o único ponto em
 * que a lista já está montada e ainda dá para lhe acrescentar alguma coisa.
 */
if (!function_exists('dps_clientes_separador_compras')) {
    function dps_clientes_separador_compras($tabs)
    {
        if (!is_array($tabs)) {
            return $tabs;
        }

        $tabs['dps_reunioes'] = [
            'name'     => 'Reuniões',
            'icon'     => 'fa fa-video-camera',
            'view'     => 'dps_reunioes/tab_cliente',
            'position' => 8,
            'badge'    => [],
            'slug'     => 'dps_reunioes',
        ];

        $tabs['dps_compras'] = [
            'name'     => 'Compras',
            'icon'     => 'fa fa-key',
            'view'     => 'dps_vendas/clientes_compras',
            'position' => 7,          // logo a seguir ao perfil
            'badge'    => [],
            'slug'     => 'dps_compras',
        ];

        return $tabs;
    }
}
