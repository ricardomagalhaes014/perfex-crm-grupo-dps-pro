<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Module Name: DPS Calendar Sync
 * Module URI: https://grupo-dps.com/
 * Description: Permite sincronizar o calendário de cada utilizador com o Google Calendar. Este módulo adiciona um menu no Painel de Administração onde cada utilizador pode ligar a sua conta Google via OAuth2 e criar eventos que são sincronizados com a sua agenda Google. O administrador pode configurar as credenciais de API através das definições do módulo.
 * Version: 1.0.0
 * Requires at least: 2.3.*
 * Author: DPS Group
 * Author URI: https://grupo-dps.com/
 */

/**
 * Regista os hooks básicos necessários para o módulo.
 */
register_activation_hook(__FILE__, 'dps_calendar_sync_activate');
register_uninstall_hook(__FILE__, 'dps_calendar_sync_uninstall');

// Carregar traduções e permissões no admin_init
hooks()->add_action('admin_init', 'dps_calendar_sync_init');
// Adicionar item de menu no admin
hooks()->add_action('admin_init', 'dps_calendar_sync_add_admin_menu');

/**
 * Função executada no momento da activação do módulo.
 * É responsável por criar as tabelas e opções necessárias.
 */
function dps_calendar_sync_activate()
{
    require_once(__DIR__ . '/install.php');
}

/**
 * Função executada no momento da desinstalação do módulo.
 * Elimina as tabelas e opções criadas pelo módulo.
 */
function dps_calendar_sync_uninstall()
{
    require_once(__DIR__ . '/uninstall.php');
}

/**
 * Inicializa o módulo e carrega traduções.
 */
function dps_calendar_sync_init()
{
    $CI = &get_instance();
    // Carregar linguagens
    $CI->load->language('dps_calendar_sync', active_language());
    // Registar capacidades/papéis para este módulo
    dps_calendar_sync_add_permissions();
}

/**
 * Regista as capacidades de staff para o módulo.
 */
function dps_calendar_sync_add_permissions()
{
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'sync'   => _l('dps_calendar_sync_permission_sync'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('dps_calendar_sync', $capabilities, _l('dps_calendar_sync'));
}

/**
 * Adiciona o item de menu no painel de administração.
 */
function dps_calendar_sync_add_admin_menu()
{
    // Apenas utilizadores com permissão podem ver o menu
    if (!staff_can('view', 'dps_calendar_sync')) {
        return;
    }
    $CI = &get_instance();

    // Menu principal
    $CI->app_menu->add_sidebar_menu_item('dps-calendar-sync', [
        'slug'     => 'dps-calendar-sync',
        'name'     => _l('dps_calendar_sync'),
        'icon'     => 'fa fa-calendar',
        'href'     => admin_url('dps_calendar_sync'),
        'position' => 45,
    ]);

    // Submenu: Eventos
    $CI->app_menu->add_sidebar_children_item('dps-calendar-sync', [
        'slug'     => 'dps-calendar-sync-events',
        'name'     => _l('dps_calendar_sync_events'),
        'href'     => admin_url('dps_calendar_sync'),
        'position' => 1,
    ]);

    // Submenu: Configurações
    if (staff_can('view', 'settings')) {
        $CI->app_menu->add_sidebar_children_item('dps-calendar-sync', [
            'slug'     => 'dps-calendar-sync-settings',
            'name'     => _l('dps_calendar_sync_settings'),
            'href'     => admin_url('dps_calendar_sync/settings'),
            'position' => 2,
        ]);
    }
}
