<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Imóveis
Description: Gestão de imóveis para DPS Imobiliário - registo, aprovação e publicação no site dpsimobiliario.pt
Version: 1.1.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_IMOVEIS_MODULE_NAME', 'dps_imoveis');
define('DPS_IMOVEIS_VERSION', '1.1.0');
define('DPS_IMOVEIS_UPLOAD_PATH', 'modules/dps_imoveis/uploads/');

// Criar pasta de uploads se não existir
if (!file_exists(FCPATH . DPS_IMOVEIS_UPLOAD_PATH)) {
    mkdir(FCPATH . DPS_IMOVEIS_UPLOAD_PATH, 0755, true);
    mkdir(FCPATH . DPS_IMOVEIS_UPLOAD_PATH . 'fotos/', 0755, true);
    mkdir(FCPATH . DPS_IMOVEIS_UPLOAD_PATH . 'documentos/', 0755, true);
}

// Hooks
hooks()->add_action('admin_init', 'dps_imoveis_menu');
hooks()->add_action('admin_init', 'dps_imoveis_permissions');

/**
 * Menu lateral — visível para TODOS os membros de staff autenticados.
 * Não é necessária nenhuma permissão especial para aceder ao módulo.
 */
function dps_imoveis_menu()
{
    $CI = &get_instance();
    // Mostrar o menu a qualquer staff autenticado (is_staff_logged_in() já é
    // garantido pelo AdminController; aqui apenas evitamos mostrar a clientes)
    $CI->app_menu->add_sidebar_menu_item('dps_imoveis', [
        'slug'     => 'dps_imoveis',
        'name'     => 'DPS Imóveis',
        'icon'     => 'fa fa-home',
        'href'     => admin_url('dps_imoveis'),
        'position' => 25,
    ]);
}

/**
 * Registar apenas a capability de aprovação.
 * O admin atribui esta capability manualmente aos Directores /
 * Responsáveis de Área que devem poder aprovar/rejeitar imóveis.
 * As acções de ver, inserir e editar não requerem permissão especial.
 */
function dps_imoveis_permissions()
{
    $capabilities = [
        'approve' => 'Aprovar / Rejeitar Imóveis',
    ];
    register_staff_capabilities('dps_imoveis', $capabilities, 'DPS Imóveis');
}
