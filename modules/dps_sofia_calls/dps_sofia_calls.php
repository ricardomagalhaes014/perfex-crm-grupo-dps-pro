<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Sofia Calls
Description: Automação de chamadas telefónicas com a Sofia (ElevenLabs AI) para leads do CRM. Permite criar campanhas de chamadas automáticas por estado de lead, com foco/contexto personalizável.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/
define('DPS_SOFIA_CALLS_MODULE_NAME', 'dps_sofia_calls');
define('DPS_SOFIA_CALLS_VERSION', '1.0.0');

/**
 * Quantos dias tem de esperar um comercial entre campanhas.
 *
 * A Sofia liga a clientes reais em nome da empresa e gasta saldo a cada
 * chamada. O travão não é o menu — é este intervalo, mais a aprovação.
 */
define('DPS_SOFIA_INTERVALO_DIAS', 7);

// Registar menu lateral
hooks()->add_action('admin_init', 'dps_sofia_calls_menu');
function dps_sofia_calls_menu()
{
    /*
     * O módulo voltou a estar aberto aos comerciais (regra do dono,
     * 22/08/2026), mas o que eles podem fazer aqui dentro é outra coisa:
     * criam a campanha e pedem-na, não a põem a correr. Quem a põe a correr é
     * a direcção, depois de ouvir a chamada de teste.
     *
     * Esconder o menu nunca foi a defesa — quem souber o endereço entra à
     * mesma. As verificações que contam estão no controlador, acção a acção.
     */
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps_sofia_calls', [
        'name'     => 'Sofia Calls',
        'href'     => admin_url('dps_sofia_calls'),
        'icon'     => 'fa fa-phone',
        'position' => 49,
        'badge'    => [],
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_sofia_calls', [
        'slug'     => 'dps_sofia_calls_relatorio',
        'name'     => 'Resultados',
        'href'     => admin_url('dps_sofia_calls/relatorio'),
        'position' => 3,
    ]);

    if (! is_admin()) {
        return;
    }

    // A chave da ElevenLabs escreve-se aqui, nao no codigo.
    $CI->app_menu->add_sidebar_children_item('dps_sofia_calls', [
        'slug'     => 'dps_sofia_calls_definicoes',
        'name'     => 'Definições',
        'href'     => admin_url('dps_sofia_calls/definicoes'),
        'position' => 5,
    ]);
}

/**
 * As colunas da aprovação, acrescentadas em instalações que já existiam.
 *
 * O install.php só corre quando o módulo se instala de raiz, e este já está
 * instalado há meses. Sem isto, a primeira campanha de um comercial rebentava
 * numa coluna que não existe.
 */
hooks()->add_action('admin_init', 'dps_sofia_calls_colunas_aprovacao');
function dps_sofia_calls_colunas_aprovacao()
{
    $CI     = &get_instance();
    $tabela = db_prefix() . 'dps_sofia_campaigns';

    if (! $CI->db->table_exists($tabela)) {
        return;
    }

    $colunas = [
        'aprovacao'     => "ENUM('rascunho','teste_pedido','teste_feito','aprovada','recusada') NOT NULL DEFAULT 'rascunho'",
        'teste_numero'  => 'VARCHAR(50) NULL',
        'teste_em'      => 'DATETIME NULL',
        'teste_call_id' => 'VARCHAR(100) NULL',
        'decidida_por'  => 'INT(11) NULL',
        'decidida_em'   => 'DATETIME NULL',
        'decisao_nota'  => 'VARCHAR(255) NULL',
        'arrancou_em'   => 'DATETIME NULL',
    ];

    foreach ($colunas as $nome => $tipo) {
        if (! $CI->db->field_exists($nome, $tabela)) {
            $CI->db->query('ALTER TABLE `' . $tabela . '` ADD COLUMN `' . $nome . '` ' . $tipo);
        }
    }

    /*
     * As campanhas que já lá estavam foram todas criadas pela direcção, antes
     * de isto existir. Ficam aprovadas — obrigá-las a pedir aprovação agora
     * seria inventar-lhes um problema que nunca tiveram.
     */
    $CI->db->where('aprovacao', 'rascunho')
           ->where('created_at <', '2026-08-22')
           ->update($tabela, ['aprovacao' => 'aprovada']);
}

// Cron: processar chamadas pendentes
// Usa o hook 'after_cron_run' que é disparado pelo Perfex core após cada execução do cron.
hooks()->add_action('after_cron_run', 'dps_sofia_calls_process_cron');
function dps_sofia_calls_process_cron()
{
    $CI = &get_instance();
    $CI->load->model('dps_sofia_calls/Dps_sofia_calls_model');
    $CI->Dps_sofia_calls_model->process_pending_calls();
}
