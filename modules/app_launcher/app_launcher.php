<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: App Launcher | Ecrã de Apps (estilo iPhone)
Description: Mostra o menu do CRM como uma grelha de quadrados/ícones (estilo ecrã inicial do iPhone) na página inicial. Mantém a barra lateral intacta.
Version: 1.0.0
Requires at least: 2.3.2
Author: Grupo DPS
*/

define('APP_LAUNCHER_MODULE_NAME', 'app_launcher');

$CI = &get_instance();

/**
 * Carrega o helper com as funções de render e de estilos.
 */
$CI->load->helper(APP_LAUNCHER_MODULE_NAME . '/app_launcher');

/**
 * Injeta o CSS da grelha no <head> do backend.
 */
hooks()->add_action('app_admin_head', 'app_launcher_head_css');

/**
 * Desenha a grelha de apps no topo da página inicial (dashboard),
 * antes dos widgets. A barra lateral continua disponível por baixo.
 */
hooks()->add_action('before_start_render_dashboard_content', 'app_launcher_render_grid');
