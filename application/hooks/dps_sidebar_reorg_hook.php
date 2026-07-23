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
        // Estes itens já aparecem num bloco fixo só-do-servidor (a secção
        // "IMOBILIARIO" e afins) que fica onde está, por pedido. Para não
        // duplicar, escondemo-los aqui do lado dinâmico — só se vêem através
        // desse bloco fixo.
        foreach ([
            'perfex-dashboard-module-menu-master',
            'wiki-module-menu-wiki-master',
            'customers',
            'importsync',
            'agenda',
            'video_library',
            'dps_teams',
            'dps_imoveis',
            'projects',
            'tasks',
            'support',
        ] as $slug_duplicado) {
            unset($items[$slug_duplicado]);
        }

        // Posições dos itens de topo que reconhecemos (ordem pedida).
        // Os 4 itens só-servidor ficam fora desta lista e não são mexidos.
        // "leads" fica de fora da lista de escondidos: aparece sempre em
        // primeiro lugar, controlado por aqui.
        $ordem = [
            'leads'          => 1,
            'dps_automacoes' => 2,
            'reminder'       => 4,
            'dps_credito'    => 5,
            'dps_vendas'     => 6,
            'dps_webmail'    => 7,
            'dps_outros'     => 10,
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

        // 2. "Outros" — junta os módulos secundários como filhos.
        //    video_library/wiki-module-menu-wiki-master/projects/support já
        //    foram removidos acima (duplicavam o bloco fixo), por isso não
        //    aparecem aqui.
        $outros_slugs = [
            'dps-meetings',
            'dps-interacoes',
            'dps-chatbot',
            'dps_voip',
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
        foreach (['leads', 'reminder', 'dps_credito', 'dps_webmail'] as $slug) {
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

            if ($regras_comissao_extraida) {
                $regras_comissao_extraida['parent_slug'] = 'dps_admin_menu';
                $regras_comissao_extraida['position']    = $pos++;
                $admin_children[]                        = $regras_comissao_extraida;
            }

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
