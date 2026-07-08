<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Funil de Leads | DPS
Description: Vista de funil/fluxo das leads, com os estados agrupados por fase e contagens em tempo real. Cada estado abre a lista de leads correspondente.
Version: 1.0.0
Requires at least: 2.3.2
Author: Grupo DPS
*/

define('DPS_FUNIL_MODULE_NAME', 'dps_funil');

$CI = &get_instance();

/**
 * Regista o item na barra lateral (e, por consequência, no ecrã de apps).
 */
hooks()->add_action('admin_init', 'dps_funil_register_menu');

function dps_funil_register_menu()
{
    $CI = &get_instance();

    if (! (function_exists('is_staff_member') && is_staff_member())) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('dps-funil', [
        'name'     => 'Funil de Leads',
        'href'     => admin_url('dps_funil'),
        'icon'     => 'fa fa-filter menu-icon',
        'position' => 16,
    ]);
}
