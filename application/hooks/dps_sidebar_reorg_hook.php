<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Reorganiza o menu lateral para todos os utilizadores.
 *
 * Ordem pedida:
 *   Leads · A Sofia Responde · Simulador · Propostas Enviadas · Vendas ·
 *   Automações (WhatsApp, Sofia Calls, Em Massa Reunião Online, Automação) ·
 *   Tarefas · Lembrete · Funil de Vendas · Suporte · DPS Crédito ·
 *   Simulador de Comissões · Webmail · DPS Imóveis · Clientes ·
 *   Agenda · Arquivo · Reuniões online ·
 *   Outros (Biblioteca de Vídeos, Wiki Book, Google Calendar, VoIPstudio,
 *   Interacções, Chatbot Interno, Projectos, Suporte, IMOBILIARIO) ·
 *   Painel do Negócio.
 *
 * A Agenda, o Arquivo e as Reuniões online estiveram dentro do "Outros" até
 * 22/08/2026; saíram para item de topo por serem de uso diário.
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
        foreach (['dps_whatsapp' => 1, 'dps_sofia_calls' => 2, 'dps_reunioes_massa' => 3] as $slug => $pos) {
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
            /*
             * "Google Calendar" TEM de estar aqui.
             *
             * O que não entra em grupo nenhum cai na regra 5 — vai para
             * "Admin" e passa a ser visível só a administradores. Para o
             * Google Calendar isso era fatal: é a página onde CADA COMERCIAL
             * liga a conta dele, e nenhum a veria.
             *
             * O 'dps-meetings' ficou de um módulo que já foi removido; sai.
             */
            'dps_google',
            /*
             * A Agenda, o Arquivo e as Reuniões online SAÍRAM daqui em
             * 22/08/2026, por ordem do dono: passam a itens de topo, entre
             * "Clientes" e "Outros". Estão em $ordem_por_nome com as posições
             * 17, 18 e 19 — e têm de lá estar, senão a regra 5 enterra-as no
             * submenu "Admin" e os comerciais deixam de as ver.
             *
             * O VoIP fica (02/08/2026): é coisa que se usa de vez em quando e
             * não precisa de espaço de topo.
             */
            'voipstudio-dps',
            'dps-interacoes',
            'dps-chatbot',
            'dps_voip',
            'projects',
            'support',
        ];
        /*
         * Estes três ficam SEMPRE de fora do grupo, venham de que slug vierem.
         *
         * A lista acima trabalha por slug, e por slug já se falhou aqui: a
         * Agenda andou a ser procurada por 'calendar' e 'appointly', que não
         * são o slug dela. Comparar pelo nome apanha-a venha ela do módulo ou
         * de um link personalizado, que é como entrou o IMOBILIARIO.
         */
        $fora_do_grupo = ['agenda', 'arquivo', 'reunioes online'];

        $outros_children = [];
        $pos = 1;
        foreach ($outros_slugs as $slug) {
            if (isset($items[$slug]) && ! in_array(dps_sidebar_norm($items[$slug]['name'] ?? ''), $fora_do_grupo, true)) {
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
            // A Sofia (assistente interna) entra entre as Leads e o Simulador.
            // TEM de constar desta lista: o passo 5 esconde dos não-admins tudo
            // o que aqui não estiver, e a Sofia existe precisamente para os
            // comerciais — ficaria visível só para quem não precisa dela.
            'a sofia responde'       => 2,
            'simulador'              => 3,
            'propostas enviadas'     => 4,
            'vendas'                 => 5,
            'automacoes'             => 6,
            'tarefas'                => 7,
            'lembrete'               => 9,
            'funil de vendas'        => 10,
            'funil de leads'         => 10,
            /*
             * SUPORTE, entre o Funil e o DPS Crédito. Pedido do dono
             * (12/08/2026).
             *
             * Tem de constar desta lista como tudo o resto: o passo 5 esconde
             * dos não-administradores tudo o que aqui não esteja, e o Suporte
             * existe precisamente para os comerciais pedirem ajuda. Foi por
             * isto que o item não apareceu quando foi criado no módulo — o
             * módulo registava-o e este filtro deitava-o fora a seguir.
             */
            'suporte'                => 11,
            'dps credito'            => 12,
            'simulador de comissoes' => 13,
            'webmail'                => 14,
            'dps imoveis'            => 15,
            'clientes'               => 16,
            /*
             * AGENDA, ARQUIVO e REUNIÕES ONLINE, entre os Clientes e o
             * "Outros". Pedido do dono (22/08/2026) — estavam metidos dentro
             * do "Outros" e é preciso abrir o grupo para lá chegar.
             *
             * Têm de constar desta lista como tudo o resto: o passo 5 esconde
             * dos não-administradores tudo o que aqui não esteja. A Agenda já
             * tinha passado por isso uma vez — os comerciais marcavam
             * lembretes e depois não tinham por onde lá voltar.
             */
            'agenda'                 => 17,
            'arquivo'                => 18,
            'reunioes online'        => 19,
            'outros'                 => 20,
            // Privado do Ricardo. Tem de estar AQUI: o que não consta desta
            // lista cai na regra do passo 5 e é enterrado dentro do submenu
            // "Admin", que foi como o Painel do Negócio desapareceu do sítio
            // em 29/07/2026. O módulo já só cria o item para o staff 1, por
            // isso ninguém mais o vê.
            'painel do negocio'      => 21,
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
        $slugs_prioritarios = ['dps-suporte', 'dps_vendas_mapa', 'dps_automacoes', 'dps_outros', 'dps_vendas', 'dps_credito', 'dps_webmail', 'dps_imoveis', 'dps_sofia_ia', 'leads', 'tasks', 'reminder', 'customers'];

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
