<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reorganiza o menu lateral para todos os utilizadores, sem editar cada
 * módulo individualmente — filtra a lista final de itens já registada.
 *
 * Nota: 4 itens que só existem no servidor (Simulador, Painel, Funil de
 * Leads/Vendas, Propostas Enviadas) não são tocados aqui de propósito —
 * continuam a aparecer onde já estavam, fora desta ordem, até serem
 * localizados e trazidos para o repositório.
 */

if (!function_exists('dps_sidebar_reorg_register')) {
    function dps_sidebar_reorg_register()
    {
        hooks()->add_filter('sidebar_menu_items', 'dps_sidebar_reorg_apply');
    }
}

if (!function_exists('dps_sidebar_reorg_apply')) {
    function dps_sidebar_reorg_apply($items)
    {
        // Posições dos itens de topo que reconhecemos (ordem pedida).
        // Os 4 itens só-servidor ficam fora desta lista e não são mexidos.
        $ordem = [
            'leads'            => 1,
            'dps_automacoes'   => 2,
            'tasks'            => 3,
            'reminder'         => 4,
            'dps_credito'      => 5,
            'dps_vendas'       => 6,
            'dps_webmail'      => 7,
            'dps_imoveis'      => 8,
            'customers'        => 9,
            'dps_outros'       => 10,
        ];

        // 1. "Automações" — junta WhatsApp e Sofia Calls como filhos
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

        // 3. "Vendas & Comissões" -> renomeado para "Simulador de Comissões",
        //    removendo o filho "Vendas" (apagado a pedido).
        if (isset($items['dps_vendas'])) {
            $items['dps_vendas']['name']     = 'Simulador de Comissões';
            $items['dps_vendas']['position'] = $ordem['dps_vendas'];
            if (!empty($items['dps_vendas']['children'])) {
                $items['dps_vendas']['children'] = array_values(array_filter(
                    $items['dps_vendas']['children'],
                    fn ($c) => $c['slug'] !== 'dps_vendas_lista'
                ));
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

        if (is_admin()) {
            $admin_children = [];
            $pos = 1;
            foreach ($items as $slug => $item) {
                if (in_array($slug, $mantidos_visiveis, true) || $slug === 'dps_admin_menu') {
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
                if (in_array($slug, $mantidos_visiveis, true)) {
                    continue;
                }
                unset($items[$slug]);
            }
        }

        return $items;
    }
}
