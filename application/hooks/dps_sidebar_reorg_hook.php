<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reorganiza o menu lateral para todos os utilizadores, sem editar cada
 * módulo individualmente — filtra a lista final de itens já registada.
 *
 * Ordem pedida:
 *   Leads, Simulador*, Propostas Enviadas*, Automações (WhatsApp, Sofia
 *   Calls, Automação), Tarefas, Lembrete, Funil de Vendas*, DPS Crédito,
 *   Simulador de Comissões, Webmail, DPS Imóveis, Clientes,
 *   Outros (Biblioteca de Vídeos, Wiki Book, Reuniões Online, Interações,
 *   Chatbot Interno, VOIP Central, Projectos, Suporte).
 *   Tudo o resto: escondido para não-admins; agrupado num botão "Admin"
 *   visível só para admins.
 *
 * * Simulador, Propostas Enviadas e Funil de Vendas são itens que só
 *   existem no servidor (código nunca commitado) — não sabemos o slug
 *   exacto, por isso protegemo-los por NOME: ficam sempre visíveis, fora
 *   do grupo Admin, mas não conseguimos controlar a sua posição exacta
 *   até localizarmos e trazermos esse código para o repositório.
 */

if (!function_exists('dps_sidebar_reorg_register')) {
    function dps_sidebar_reorg_register()
    {
        // Regista-se no mesmo ponto (admin_init) onde todos os módulos
        // registam os seus próprios itens de menu, com prioridade alta para
        // correr depois de tudo já ter sido adicionado.
        hooks()->add_action('admin_init', 'dps_sidebar_reorg_register_filter', 999);
    }
}

if (!function_exists('dps_sidebar_reorg_register_filter')) {
    function dps_sidebar_reorg_register_filter()
    {
        hooks()->add_filter('sidebar_menu_items', 'dps_sidebar_reorg_apply');
    }
}

if (!function_exists('dps_sidebar_reorg_apply')) {
    function dps_sidebar_reorg_apply($items)
    {
        // Posições dos itens de topo que reconhecemos, na ordem pedida.
        $ordem = [
            'leads'          => 1,
            'dps_automacoes' => 4,
            'tasks'          => 5,
            'reminder'       => 6,
            'dps_credito'    => 8,
            'dps_vendas'     => 9,
            'dps_webmail'    => 10,
            'dps_imoveis'    => 11,
            'customers'      => 12,
            'dps_outros'     => 13,
        ];

        // 1. "Automações" — junta WhatsApp, Sofia Calls e o item "Automação"
        //    (que estava escondido dentro de Utilities) como filhos
        $automacoes_children = [];
        foreach (['dps_whatsapp' => 1, 'dps_sofia_calls' => 2] as $slug => $pos) {
            if (isset($items[$slug])) {
                $child                 = $items[$slug];
                $child['parent_slug']  = 'dps_automacoes';
                $child['position']     = $pos;
                $automacoes_children[] = $child;
                unset($items[$slug]);
            }
        }
        // "Automação" (automation_manager) vive como filho de "utilities" —
        // vai buscá-lo lá antes de "utilities" cair no balde do Admin.
        if (!empty($items['utilities']['children'])) {
            foreach ($items['utilities']['children'] as $i => $sub) {
                if (($sub['slug'] ?? null) === 'automation_manager') {
                    $sub['parent_slug']    = 'dps_automacoes';
                    $sub['position']       = 3;
                    $automacoes_children[] = $sub;
                    unset($items['utilities']['children'][$i]);
                    break;
                }
            }
        }
        if (!empty($automacoes_children)) {
            $items['dps_automacoes'] = [
                'slug'     => 'dps_automacoes',
                'name'     => 'Automações',
                'icon'     => 'fa fa-cogs',
                'href'     => '#',
                'collapse' => true,
                'position' => $ordem['dps_automacoes'],
                'children' => $automacoes_children,
                'badge'    => [],
            ];
        }

        // 2. "Outros" — junta os módulos secundários como filhos
        $outros_slugs = [
            'video_library',
            'wiki-module-menu-wiki-master',
            'dps-meetings',
            'dps-interacoes',
            'dps-chatbot',
            'dps_voip',
            'projects',
            'support',
        ];
        $outros_children = [];
        $pos = 1;
        foreach ($outros_slugs as $slug) {
            if (isset($items[$slug])) {
                $child                = $items[$slug];
                $child['parent_slug'] = 'dps_outros';
                $child['position']    = $pos++;
                $outros_children[]    = $child;
                unset($items[$slug]);
            }
        }
        if (!empty($outros_children)) {
            $items['dps_outros'] = [
                'slug'     => 'dps_outros',
                'name'     => 'Outros',
                'icon'     => 'fa fa-ellipsis-h',
                'href'     => '#',
                'collapse' => true,
                'position' => $ordem['dps_outros'],
                'children' => $outros_children,
                'badge'    => [],
            ];
        }

        // 3. "Vendas & Comissões" -> renomeado para "Simulador de Comissões".
        //    Remove "Vendas" (apagado) e "Regras de Comissão" (vai para Admin).
        $regras_comissao_extraida = null;
        if (isset($items['dps_vendas'])) {
            $items['dps_vendas']['name']     = 'Simulador de Comissões';
            $items['dps_vendas']['position'] = $ordem['dps_vendas'];
            if (!empty($items['dps_vendas']['children'])) {
                $nova_lista = [];
                foreach ($items['dps_vendas']['children'] as $c) {
                    if ($c['slug'] === 'dps_vendas_lista') {
                        continue; // "Vendas" — apagado
                    }
                    if ($c['slug'] === 'dps_vendas_regras') {
                        $regras_comissao_extraida = $c; // "Regras de Comissão" — vai para Admin
                        continue;
                    }
                    $nova_lista[] = $c;
                }
                $items['dps_vendas']['children'] = array_values($nova_lista);
            }
        }

        // 4. Posições explícitas dos restantes itens de topo reconhecidos
        foreach (['leads', 'tasks', 'reminder', 'dps_credito', 'dps_webmail', 'dps_imoveis', 'customers'] as $slug) {
            if (isset($items[$slug])) {
                $items[$slug]['position'] = $ordem[$slug];
            }
        }

        // 5. Tudo o resto: escondido para não-admins; agrupado num botão
        //    "Admin" (visível só a admins). O dashboard fica sempre visível.
        $mantidos_visiveis = array_merge(array_keys($ordem), ['dashboard']);

        // Itens só-do-servidor identificados pelo NOME (não sabemos o slug,
        // porque o código deles não está no git): Simulador, Propostas
        // Enviadas, Funil de Vendas/Leads, Painel. Ficam sempre visíveis,
        // fora do grupo Admin — não controlamos a posição exacta deles.
        $nomes_protegidos = ['propostas enviadas', 'simulador', 'painel', 'funil de leads', 'funil de vendas'];
        $eh_protegido_por_nome = function ($item) use ($nomes_protegidos) {
            return in_array(mb_strtolower(trim((string) ($item['name'] ?? '')), 'UTF-8'), $nomes_protegidos, true);
        };

        if (is_admin()) {
            $admin_children = [];
            $pos = 1;

            if ($regras_comissao_extraida) {
                $regras_comissao_extraida['parent_slug'] = 'dps_admin_menu';
                $regras_comissao_extraida['position']    = $pos++;
                $admin_children[]                        = $regras_comissao_extraida;
            }

            foreach ($items as $slug => $item) {
                if (in_array($slug, $mantidos_visiveis, true) || $slug === 'dps_admin_menu' || $eh_protegido_por_nome($item)) {
                    continue;
                }
                $child                 = $item;
                $child['parent_slug']  = 'dps_admin_menu';
                $child['position']     = $pos++;
                $admin_children[]      = $child;
                unset($items[$slug]);
            }
            if (!empty($admin_children)) {
                $items['dps_admin_menu'] = [
                    'slug'     => 'dps_admin_menu',
                    'name'     => 'Admin',
                    'icon'     => 'fa fa-lock',
                    'href'     => '#',
                    'collapse' => true,
                    'position' => 999,
                    'children' => $admin_children,
                    'badge'    => [],
                ];
            }
        } else {
            foreach ($items as $slug => $item) {
                if (in_array($slug, $mantidos_visiveis, true) || $eh_protegido_por_nome($item)) {
                    continue;
                }
                unset($items[$slug]);
            }
        }

        return $items;
    }
}
