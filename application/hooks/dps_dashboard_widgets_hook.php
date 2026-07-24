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
        array_splice($widgets, 1, 0, [[
            'path'      => 'admin/dashboard/widgets/dps_graficos',
            'container' => 'top-12',
        ]]);

        return $widgets;
    }
}
