<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Sofia IA
Description: Assistente interna (Sofia) que responde às perguntas dos comerciais a partir do conhecimento carregado no CRM. Quando não sabe, avisa a administração para responder — e a resposta passa a fazer parte do conhecimento.
Version: 1.1.0
Requires at least: 2.3.*
Author: Grupo DPS
Author URI: https://grupo-dps.com
*/

define('DPS_SOFIA_IA_MODULE_NAME', 'dps_sofia_ia');
define('DPS_SOFIA_IA_VERSION', '1.1.0');
define('DPS_SOFIA_IA_UPLOAD_PATH', 'uploads/dps_sofia_ia/');

/*
 * Marcador que a Sofia devolve quando o conhecimento carregado não chega para
 * responder. É lido pelo modelo (não pelo comercial): a resposta mostrada é
 * limpa deste texto antes de ir para o ecrã. Ver Dps_sofia_ia_model::perguntar.
 */
define('DPS_SOFIA_IA_MARCA_SEM_RESPOSTA', '[SEM_RESPOSTA]');

register_activation_hook(DPS_SOFIA_IA_MODULE_NAME, 'dps_sofia_ia_activate');
register_uninstall_hook(DPS_SOFIA_IA_MODULE_NAME, 'dps_sofia_ia_uninstall');

// O helper é usado nas vistas e no hook do rodapé, por isso carrega-se já aqui.
get_instance()->load->helper(DPS_SOFIA_IA_MODULE_NAME . '/dps_sofia_ia');

hooks()->add_action('admin_init', 'dps_sofia_ia_ensure_schema');
hooks()->add_action('admin_init', 'dps_sofia_ia_permissions');
hooks()->add_action('admin_init', 'dps_sofia_ia_menu');
hooks()->add_action('app_admin_footer', 'dps_sofia_ia_widget');

/*
 * A POSIÇÃO NA BARRA LATERAL NÃO SE DECIDE AQUI.
 *
 * O ficheiro application/hooks/dps_sidebar_reorg_hook.php corre no filtro
 * sidebar_menu_items com prioridade 9999 e reescreve a barra inteira: ordena
 * por NOME e — o que importa mesmo — esconde dos não-administradores tudo o que
 * não conste da lista dele. Um filtro registado aqui, em qualquer prioridade
 * abaixo dessa, seria simplesmente desfeito, e a Sofia acabaria visível só para
 * quem não precisa dela.
 *
 * Por isso "A Sofia responde" está inscrita naquele ficheiro, entre as Leads e
 * o Simulador. Se o nome do menu mudar aqui, tem de mudar lá também.
 */

function dps_sofia_ia_activate()
{
    require_once __DIR__ . '/install.php';
}

function dps_sofia_ia_uninstall()
{
    require_once __DIR__ . '/uninstall.php';
}

/**
 * Migração automática de esquema, sem obrigar a desactivar/reactivar o módulo.
 *
 * O install.php é idempotente (CREATE TABLE IF NOT EXISTS + add_option), por
 * isso pode correr as vezes que forem precisas. A opção guarda a versão já
 * aplicada para que os pedidos seguintes saiam daqui a custo zero — o mesmo
 * padrão usado no dps_vendas.
 */
function dps_sofia_ia_ensure_schema()
{
    if (get_option('dps_sofia_ia_schema_version') === DPS_SOFIA_IA_VERSION) {
        return;
    }

    require_once __DIR__ . '/install.php';

    update_option('dps_sofia_ia_schema_version', DPS_SOFIA_IA_VERSION);
}

/*
 * REPARAÇÃO PONTUAL — 09/08/2026. Pode ser apagada quando estiver feita.
 *
 * Ao aplicar-se a Função a todos os membros (a caixa "actualizar permissões"),
 * o Perfex corre Staff_model::update_permissions(), que APAGA todas as
 * permissões da pessoa antes de inserir as da Função. Tudo o que cada comercial
 * tinha recebido individualmente e não constava da Função desapareceu — os
 * Lembretes foram o primeiro sintoma.
 *
 * Isto devolve o acesso aos Lembretes a quem ficou sem nenhuma permissão desse
 * módulo. Dá `view_own` e não `view`: o segundo deixaria cada comercial ver os
 * lembretes de toda a gente, e não há razão para alargar o que estava.
 *
 * Não toca em quem já tenha alguma permissão de `reminder` — pode ter sido
 * restringido de propósito.
 *
 * Vive neste módulo por ser um dos que está activo e a correr; não tem relação
 * com a Sofia.
 */
hooks()->add_action('admin_init', 'dps_reparar_permissoes_reminder');
function dps_reparar_permissoes_reminder()
{
    if (get_option('dps_reparacao_reminder_2026_08_09') === '1') {
        return;
    }

    $CI = &get_instance();

    // Se o módulo dos lembretes não existir aqui, não há nada a reparar.
    if (!$CI->db->table_exists('staff_permissions')) {
        update_option('dps_reparacao_reminder_2026_08_09', '1');

        return;
    }

    $capacidades = ['view_own', 'create', 'edit', 'delete'];

    $equipa = $CI->db->select('staffid')
        ->where('active', 1)
        ->where('admin', 0)
        ->get(db_prefix() . 'staff')->result_array();

    $repostos = 0;

    foreach ($equipa as $membro) {
        $staff_id = (int) $membro['staffid'];

        $tem = (int) $CI->db->where('staff_id', $staff_id)
            ->where('feature', 'reminder')
            ->count_all_results('staff_permissions');

        if ($tem > 0) {
            continue;
        }

        foreach ($capacidades as $capacidade) {
            $CI->db->insert('staff_permissions', [
                'staff_id'   => $staff_id,
                'feature'    => 'reminder',
                'capability' => $capacidade,
            ]);
        }

        $repostos++;
    }

    update_option('dps_reparacao_reminder_2026_08_09', '1');

    log_activity('Reparação: permissões de Lembretes repostas a ' . $repostos . ' membros.');
}

function dps_sofia_ia_permissions()
{
    register_staff_capabilities(DPS_SOFIA_IA_MODULE_NAME, [
        'capabilities' => [
            // "view" = pode perguntar à Sofia. "edit" = pode gerir o
            // conhecimento e responder às perguntas em aberto.
            'view' => 'Perguntar à Sofia',
            'edit' => 'Gerir conhecimento e responder',
        ],
    ], 'Sofia IA');
}

function dps_sofia_ia_menu()
{
    if (!dps_sofia_ia_pode_perguntar()) {
        return;
    }

    $CI = &get_instance();

    /*
     * 46 fica logo a seguir às Leads (45) do núcleo. Na prática o número é
     * ignorado — quem manda é o dps_sidebar_reorg_hook (ver nota no topo) —
     * mas serve de rede se esse ficheiro alguma vez sair.
     */
    $CI->app_menu->add_sidebar_menu_item('dps_sofia_ia', [
        'slug'     => 'dps_sofia_ia',
        'name'     => 'A Sofia responde',
        'href'     => admin_url('dps_sofia_ia'),
        'icon'     => 'fa fa-comments-o',
        'position' => 46,
    ]);

    /*
     * Para o comercial o menu não tem filhos: um submenu com uma única entrada
     * a apontar para a mesma página do item pai é um clique a mais para chegar
     * ao mesmo sítio. Os filhos só fazem sentido para quem também gere o
     * conhecimento — aí há mesmo mais do que um destino.
     */
    if (!dps_sofia_ia_pode_gerir()) {
        return;
    }

    $CI->app_menu->add_sidebar_children_item('dps_sofia_ia', [
        'slug'     => 'dps_sofia_ia_chat',
        'name'     => 'Perguntar',
        'href'     => admin_url('dps_sofia_ia'),
        'position' => 5,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_sofia_ia', [
        'slug'     => 'dps_sofia_ia_conhecimento',
        'name'     => 'Base de conhecimento',
        'href'     => admin_url('dps_sofia_ia/conhecimento'),
        'position' => 10,
    ]);

    /*
     * O número por responder aparece no menu porque é isso que faz o circuito
     * fechar: sem um sinal visível, as perguntas que a Sofia não soube ficavam
     * numa tabela que ninguém abre.
     */
    $por_responder = dps_sofia_ia_contar_pendentes();

    $CI->app_menu->add_sidebar_children_item('dps_sofia_ia', [
        'slug'     => 'dps_sofia_ia_pendentes',
        'name'     => 'Por responder' . ($por_responder > 0 ? ' (' . $por_responder . ')' : ''),
        'href'     => admin_url('dps_sofia_ia/pendentes'),
        'position' => 15,
    ]);

    $CI->app_menu->add_sidebar_children_item('dps_sofia_ia', [
        'slug'     => 'dps_sofia_ia_definicoes',
        'name'     => 'Definições',
        'href'     => admin_url('dps_sofia_ia/definicoes'),
        'position' => 20,
    ]);
}

/**
 * Botão flutuante em todas as páginas do CRM.
 *
 * O comercial está sempre a meio de outra coisa — numa lead, numa venda — e é
 * aí que a dúvida aparece. Obrigá-lo a navegar até uma página própria para
 * perguntar é o suficiente para não perguntar de todo.
 */
function dps_sofia_ia_widget()
{
    if (!dps_sofia_ia_pode_perguntar()) {
        return;
    }

    // Na própria página do chat o botão seria um duplicado do ecrã por baixo.
    $CI = &get_instance();
    if (strpos(uri_string(), 'dps_sofia_ia') !== false) {
        return;
    }

    $CI->load->view('dps_sofia_ia/partials/widget');
}
