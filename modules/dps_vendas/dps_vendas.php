<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Vendas & Comissões
Description: Processo de venda (documentos, workflow de estados) sobre a tabela de vendas do simulador, com regras de comissão por empreendimento.
Version: 1.1.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_VENDAS_MODULE_NAME', 'dps_vendas');
define('DPS_VENDAS_VERSION', '1.1.0');
define('DPS_VENDAS_UPLOAD_PATH', 'uploads/dps_vendas/');

register_activation_hook(DPS_VENDAS_MODULE_NAME, 'dps_vendas_activate');

// O helper é usado nas vistas (cores de estado, nomes legíveis), por isso tem
// de estar disponível antes de qualquer uma delas ser renderizada.
get_instance()->load->helper(DPS_VENDAS_MODULE_NAME . '/dps_vendas');

hooks()->add_action('admin_init', 'dps_vendas_ensure_schema');
hooks()->add_action('admin_init', 'dps_vendas_menu');
hooks()->add_action('admin_init', 'dps_vendas_permissions');

function dps_vendas_activate()
{
    require_once __DIR__ . '/install.php';
}

/**
 * Migração automática de esquema, sem obrigar a desactivar/reactivar o módulo.
 *
 * Corre no máximo uma vez por versão: quando a opção guardada já iguala a
 * versão actual, sai imediatamente (custo nulo nos pedidos seguintes). Serve
 * para acrescentar as colunas do circuito CPCV/pagamento e alargar o `tipo`
 * dos documentos depois de um deploy só de ficheiros.
 */
function dps_vendas_ensure_schema()
{
    if (get_option('dps_vendas_schema_version') === DPS_VENDAS_VERSION) {
        return;
    }

    $CI     = &get_instance();
    $vendas = db_prefix() . 'simulador_vendas';
    $docs   = db_prefix() . 'vendas_docs';

    if ($CI->db->table_exists($vendas)) {
        $existing = array_map(function ($f) {
            return $f->name;
        }, $CI->db->field_data($vendas));

        $novas = [
            'cpcv_assinado'    => 'TINYINT(1) NOT NULL DEFAULT 0',
            'cpcv_assinado_em' => 'DATETIME NULL DEFAULT NULL',
            'pago'             => 'TINYINT(1) NOT NULL DEFAULT 0',
            'pago_em'          => 'DATETIME NULL DEFAULT NULL',
        ];

        foreach ($novas as $coluna => $definicao) {
            if (!in_array($coluna, $existing, true)) {
                $CI->db->query("ALTER TABLE `{$vendas}` ADD `{$coluna}` {$definicao}");
            }
        }
    }

    if ($CI->db->table_exists($docs)) {
        $CI->db->query("ALTER TABLE `{$docs}` MODIFY `tipo` VARCHAR(40) NOT NULL DEFAULT 'outro'");
    }

    update_option('dps_vendas_schema_version', DPS_VENDAS_VERSION);
}

/**
 * Menu lateral com dois submenus: as vendas e as comissões a receber.
 * As Regras de Comissão ficam num terceiro item, só para quem pode geri-las.
 */
function dps_vendas_menu()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('dps_vendas', [
        'slug'     => 'dps_vendas',
        'name'     => 'Vendas & Comissões',
        'icon'     => 'fa fa-handshake-o',
        'position' => 26,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_vendas', [
        'slug'     => 'dps_vendas_lista',
        'name'     => 'Vendas',
        'href'     => admin_url('dps_vendas'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_vendas', [
        'slug'     => 'dps_vendas_comissoes',
        'name'     => 'Comissões a Receber',
        'href'     => admin_url('dps_vendas/comissoes'),
        'position' => 2,
    ]);

    if (is_admin() || staff_can('gerir_regras', 'dps_vendas')) {
        $CI->app_menu->add_sidebar_children_item('dps_vendas', [
            'slug'     => 'dps_vendas_regras',
            'name'     => 'Regras de Comissão',
            'href'     => admin_url('dps_vendas/regras'),
            'position' => 3,
        ]);
    }
}

/**
 * Capacidades do módulo.
 *
 * Por omissão um comercial vê e cria as suas vendas. Marcar como "Recebido"
 * (que liberta a comissão) e descarregar documentos de identificação são
 * acções sensíveis e ficam atrás de capacidades próprias — na prática, só
 * admin, a menos que sejam atribuídas explicitamente.
 */
function dps_vendas_permissions()
{
    $capabilities = [
        'view'          => 'Ver todas as vendas (não apenas as suas)',
        'create'        => 'Criar vendas',
        'edit'          => 'Editar vendas',
        'delete'        => 'Eliminar vendas',
        'marcar_recebido' => 'Marcar venda como Recebida (liberta a comissão)',
        'download_docs' => 'Descarregar documentos de identificação',
        'gerir_regras'  => 'Gerir regras de comissão',
    ];

    register_staff_capabilities('dps_vendas', $capabilities, 'DPS Vendas & Comissões');
}
