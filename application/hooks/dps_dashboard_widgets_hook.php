<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Regista o widget "Os meus números" (gráficos pessoais) no dashboard.
 * A view faz as consultas filtradas pelo utilizador com sessão iniciada.
 */

if (!function_exists('dps_dashboard_widgets_register')) {
    function dps_dashboard_widgets_register()
    {
        hooks()->add_filter('get_dashboard_widgets', 'dps_dashboard_widgets_add');
    }
}

if (!function_exists('dps_dashboard_widgets_add')) {
    function dps_dashboard_widgets_add($widgets)
    {
        /*
         * VENDAS é o PRIMEIRO widget do painel — antes até das quatro caixas
         * de estatísticas do topo. É o quadro que se quer ver ao abrir o CRM:
         * quem vendeu quanto, e de quê. Pedido do dono (04/08/2026).
         */
        array_splice($widgets, 0, 0, [[
            'path'      => 'admin/dashboard/widgets/dps_vendas_todos',
            'container' => 'top-12',
        ]]);

        /*
         * Os números de cada um vêm a seguir às estatísticas do topo —
         * respondem a "como vou eu", pergunta que se faz depois de ver como
         * vai a casa.
         *
         * "A equipa" fica logo atrás, e só para administradores. Quem não é
         * admin nem chega a carregar o segundo — a própria vista também se
         * protege, mas não se manda ao servidor trabalho que se sabe que vai
         * ser deitado fora.
         */
        $novos = [[
            'path'      => 'admin/dashboard/widgets/dps_graficos',
            'container' => 'top-12',
        ]];

        if (is_admin()) {
            $novos[] = [
                'path'      => 'admin/dashboard/widgets/dps_graficos_equipa',
                'container' => 'top-12',
            ];
        }

        array_splice($widgets, 2, 0, $novos);

        return $widgets;
    }
}
