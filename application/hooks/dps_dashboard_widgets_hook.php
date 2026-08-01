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
        // Logo a seguir ao primeiro widget (as 4 estatísticas do topo),
        // para os gráficos ficarem imediatamente por baixo dos ícones.
        $novos = [[
            'path'      => 'admin/dashboard/widgets/dps_graficos',
            'container' => 'top-12',
        ]];

        /*
         * "A equipa" fica LOGO A SEGUIR aos números próprios, e só para
         * administradores. São perguntas diferentes: um responde a "como vou
         * eu", o outro a "como vai a equipa". Quem não é admin nem chega a
         * carregar o segundo — a própria vista também se protege, mas não se
         * manda ao servidor trabalho que se sabe que vai ser deitado fora.
         */
        if (is_admin()) {
            $novos[] = [
                'path'      => 'admin/dashboard/widgets/dps_graficos_equipa',
                'container' => 'top-12',
            ];
        }

        array_splice($widgets, 1, 0, $novos);

        return $widgets;
    }
}
