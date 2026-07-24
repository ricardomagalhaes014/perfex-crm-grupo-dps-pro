<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reorganiza o menu lateral para todos os utilizadores.
 *
 * Ordem pedida:
 *   1 Leads · 2 Simulador · 3 Propostas Enviadas · 4 Automações (WhatsApp,
 *   Sofia Calls, Automação) · 5 Tarefas · 6 Lembrete · 7 Funil de Vendas ·
 *   8 DPS Crédito · 9 Simulador de Comissões · 10 Webmail · 11 DPS Imóveis ·
 *   12 Clientes · 13 Outros (Biblioteca de Vídeos, Wiki Book, Reuniões
 *   Online, Interações, Chatbot Interno, VOIP Central, Projectos, Suporte).
 *
 * Tudo o resto: escondido para não-admins; para admins agrupado num botão
 * "Admin" no fim.
 *
 * Simulador / Propostas Enviadas / Funil de Vendas / Painel / IMOBILIARIO
 * são links personalizados (módulo custom_links) criados no próprio CRM —
 * chegam a este filtro como itens normais, e são identificados pelo NOME.
 * Alguns duplicam módulos reais (ex.: um link personalizado "Wiki Book" a
 * apontar para o módulo Wiki Book) — nesses casos fica só um.
 */

if (!function_exists('dps_sidebar_reorg_register')) {
    function dps_sidebar_reorg_register()
    {
        hooks()->add_action('admin_init', 'dps_sidebar_reorg_register_filter', 999);
    }
}

if (!function_exists('dps_sidebar_reorg_register_filter')) {
    function dps_sidebar_reorg_register_filter()
    {
        // Prioridade 9999: o módulo menu_setup (Menu Builder) corre nos
        // 998/999 e RECONSTRÓI o menu inteiro segundo a ordem antiga que
        // está guardada na opção 'aside_menu_active' da base de dados.
        // Sem isto, tudo o que este filtro fizer é desfeito logo a seguir —
        // foi exactamente o que aconteceu nas primeiras tentativas.
        // Registado acima de 999, este filtro corre em último e é a
        // palavra final sobre ordem, agrupamento e visibilidade.
        hooks()->add_filter('sidebar_menu_items', 'dps_sidebar_reorg_apply', 9999);
    }
}

if (!function_exists('dps_sidebar_norm')) {
    function dps_sidebar_norm($nome)
    {
        $s = mb_strtolower(trim((string) $nome), 'UTF-8');
        // Remover acentos comuns para comparação robusta
        $s = strtr($s, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'õ' => 'o', 'ô' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        return preg_replace('/\s+/', ' ', $s);
    }
}

if (!function_exists('dps_sidebar_reorg_apply')) {
    function dps_sidebar_reorg_apply($items)
    {
        /* -----------------------------------------------------------------
         * 1. Agrupar "Automações": WhatsApp + Sofia Calls + Automação
         * ---------------------------------------------------------------- */
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
        // "Automação" (SMS/Email) vive como filho de "utilities"
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
        // Se já existia um botão "Automações" (item só-do-servidor, com o
        // envio de SMS configurado lá dentro), absorvemos o que ele tem —
        // filhos e, se for um link directo, o próprio item — em vez de o
        // deixar ser descartado pela deduplicação de nomes.
        $pos_abs = 4;
        foreach ($items as $slug => $item) {
            if ($slug === 'dps_automacoes' || dps_sidebar_norm($item['name'] ?? '') !== 'automacoes') {
                continue;
            }
            if (!empty($item['children'])) {
                foreach ($item['children'] as $sub) {
                    $sub['parent_slug']    = 'dps_automacoes';
                    $sub['position']       = $pos_abs++;
                    $automacoes_children[] = $sub;
                }
            } elseif (!empty($item['href']) && $item['href'] !== '#') {
                $item['parent_slug']   = 'dps_automacoes';
                $item['position']      = $pos_abs++;
                $automacoes_children[] = $item;
            }
            unset($items[$slug]);
        }
        if (!empty($automacoes_children)) {
            $items['dps_automacoes'] = [
                'slug'     => 'dps_automacoes',
                'name'     => 'Automações',
                'icon'     => 'fa fa-cogs',
                'href'     => '#',
                'collapse' => true,
                'children' => $automacoes_children,
                'badge'    => [],
            ];
        }

        /* -----------------------------------------------------------------
         * 2. Agrupar "Outros"
         * ---------------------------------------------------------------- */
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
        // "IMOBILIARIO" (link personalizado — slug desconhecido, apanhado
        // pelo nome) também entra em "Outros", no fim da lista.
        foreach ($items as $slug => $item) {
            if (dps_sidebar_norm($item['name'] ?? '') === 'imobiliario') {
                $child                = $item;
                $child['parent_slug'] = 'dps_outros';
                $child['position']    = $pos++;
                $outros_children[]    = $child;
                unset($items[$slug]);
                break;
            }
        }
        if (!empty($outros_children)) {
            $items['dps_outros'] = [
                'slug'     => 'dps_outros',
                'name'     => 'Outros',
                'icon'     => 'fa fa-ellipsis-h',
                'href'     => '#',
                'collapse' => true,
                'children' => $outros_children,
                'badge'    => [],
            ];
        }

        /* -----------------------------------------------------------------
         * 3. "Vendas & Comissões" -> "Simulador de Comissões";
         *    "Vendas" (mapa de vendas, recebe as reservas) sobe a item
         *    próprio, logo abaixo de Propostas Enviadas;
         *    "Regras de Comissão" vai para Admin
         * ---------------------------------------------------------------- */
        $regras_comissao_extraida = null;
        if (isset($items['dps_vendas'])) {
            $items['dps_vendas']['name'] = 'Simulador de Comissões';
            if (!empty($items['dps_vendas']['children'])) {
                $nova_lista = [];
                foreach ($items['dps_vendas']['children'] as $c) {
                    if ($c['slug'] === 'dps_vendas_lista') {
                        // Mapa de vendas: promovido a item de topo
                        $items['dps_vendas_mapa'] = [
                            'slug'     => 'dps_vendas_mapa',
                            'name'     => 'Vendas',
                            'icon'     => 'fa fa-handshake-o',
                            'href'     => $c['href'],
                            'children' => [],
                            'badge'    => [],
                        ];
                        continue;
                    }
                    if ($c['slug'] === 'dps_vendas_regras') {
                        $regras_comissao_extraida = $c;
                        continue;
                    }
                    $nova_lista[] = $c;
                }
                $items['dps_vendas']['children'] = array_values($nova_lista);
            }
        }

        /* -----------------------------------------------------------------
         * 4. Ordem final por NOME normalizado (apanha tanto módulos como
         *    links personalizados, independentemente do slug)
         * ---------------------------------------------------------------- */
        $ordem_por_nome = [
            'leads'                  => 1,
            'simulador'              => 2,
            'propostas enviadas'     => 3,
            'vendas'                 => 4,
            'automacoes'             => 5,
            'tarefas'                => 6,
            'lembrete'               => 7,
            'funil de vendas'        => 8,
            'funil de leads'         => 8,
            'dps credito'            => 9,
            'simulador de comissoes' => 10,
            'webmail'                => 11,
            'dps imoveis'            => 12,
            'clientes'               => 13,
            'outros'                 => 14,
        ];

        // Nomes em inglês/alternativos que o Perfex pode usar consoante o idioma
        $alias = [
            'tasks'     => 'tarefas',
            'customers' => 'clientes',
            'reminder'  => 'lembrete',
        ];

        $visiveis   = [];   // slug => posição
        $vistos     = [];   // nome normalizado => slug (deduplicação)

        // Em caso de nomes duplicados, estes slugs têm SEMPRE prioridade —
        // são os itens "certos" criados por este filtro ou pelos módulos.
        // Ex.: sem isto, um link personalizado antigo chamado "Vendas"
        // (a apontar para outro sítio) ganhava ao mapa de vendas real.
        $slugs_prioritarios = ['dps_vendas_mapa', 'dps_automacoes', 'dps_outros', 'dps_vendas', 'dps_credito', 'dps_webmail', 'dps_imoveis', 'leads', 'tasks', 'reminder', 'customers'];

        $ordem_iteracao = array_merge(
            array_values(array_intersect($slugs_prioritarios, array_keys($items))),
            array_values(array_diff(array_keys($items), $slugs_prioritarios))
        );

        foreach ($ordem_iteracao as $slug) {
            $item = $items[$slug];
            $nome = dps_sidebar_norm($item['name'] ?? '');
            if (isset($alias[$nome])) {
                $nome = $alias[$nome];
            }

            if (!isset($ordem_por_nome[$nome])) {
                continue;
            }

            // Duplicado (ex.: link personalizado + módulo com o mesmo nome):
            // fica só o prioritário/primeiro; o repetido é removido.
            if (isset($vistos[$nome])) {
                unset($items[$slug]);
                continue;
            }

            $vistos[$nome]                = $slug;
            $items[$slug]['position']     = $ordem_por_nome[$nome];
            $visiveis[$slug]              = true;
        }

        /* -----------------------------------------------------------------
         * 5. Tudo o resto: só admins veem, dentro de "Admin" no fim.
         *    (dashboard fica sempre visível)
         * ---------------------------------------------------------------- */

        // Nomes já em uso no menu visível — inclui os filhos de Automações
        // e Outros. Um link personalizado homónimo (ex.: "Wiki Book" a
        // duplicar o módulo Wiki Book que já está em Outros) é removido em
        // vez de ir parar ao Admin.
        $nomes_em_uso = $vistos;
        foreach (['dps_automacoes', 'dps_outros'] as $grupo) {
            foreach ($items[$grupo]['children'] ?? [] as $filho) {
                $nomes_em_uso[dps_sidebar_norm($filho['name'] ?? '')] = true;
            }
        }

        if (is_admin()) {
            $admin_children = [];
            $pos = 1;

            if ($regras_comissao_extraida) {
                $regras_comissao_extraida['parent_slug'] = 'dps_admin_menu';
                $regras_comissao_extraida['position']    = $pos++;
                $admin_children[]                        = $regras_comissao_extraida;
            }

            foreach ($items as $slug => $item) {
                if (isset($visiveis[$slug]) || $slug === 'dashboard' || $slug === 'dps_admin_menu') {
                    continue;
                }
                unset($items[$slug]);

                // Homónimo de algo já visível: remover em vez de duplicar no Admin
                $nome = dps_sidebar_norm($item['name'] ?? '');
                if (isset($alias[$nome])) {
                    $nome = $alias[$nome];
                }
                if (isset($nomes_em_uso[$nome])) {
                    continue;
                }

                $child                = $item;
                $child['parent_slug'] = 'dps_admin_menu';
                $child['position']    = $pos++;
                $admin_children[]     = $child;
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
                if (isset($visiveis[$slug]) || $slug === 'dashboard') {
                    continue;
                }
                unset($items[$slug]);
            }
        }

        return $items;
    }
}
