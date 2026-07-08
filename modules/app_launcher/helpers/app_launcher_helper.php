<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Injeta o ficheiro CSS da grelha de apps no <head> do backend.
 */
function app_launcher_head_css()
{
    $url = module_dir_url(APP_LAUNCHER_MODULE_NAME, 'assets/css/launcher.css');
    echo '<link href="' . $url . '?v=1.0.0" rel="stylesheet" type="text/css">' . PHP_EOL;
}

/**
 * Resolve o href de um item do menu.
 * Itens "pai" (com filhos) normalmente têm href "#": nesse caso usamos
 * o href do primeiro filho para o quadrado abrir algo útil.
 *
 * @param  array $item
 * @return string
 */
function app_launcher_item_href($item)
{
    $href     = isset($item['href']) ? $item['href'] : '#';
    $children = isset($item['children']) && is_array($item['children']) ? $item['children'] : [];

    if (($href === '' || $href === '#') && count($children) > 0) {
        foreach ($children as $child) {
            if (! empty($child['href']) && $child['href'] !== '#') {
                return $child['href'];
            }
        }
    }

    return $href === '' ? '#' : $href;
}

/**
 * Devolve uma "cor" (1 a 8) estável para um slug, para colorir os quadrados
 * de forma consistente sem depender de configuração.
 *
 * @param  string $slug
 * @return int
 */
function app_launcher_color_index($slug)
{
    // Cores fixas para as secções mais usadas (para ficarem sempre iguais).
    $map = [
        'dashboard'      => 1,
        'leads'          => 1,
        'customers'      => 3,
        'clients'        => 3,
        'contracts'      => 5,
        'estimates'      => 5,
        'proposals'      => 5,
        'invoices'       => 6,
        'payments'       => 6,
        'projects'       => 2,
        'tasks'          => 4,
        'calendar'       => 1,
        'utilities'      => 7,
        'reports'        => 2,
        'support'        => 8,
        'knowledge_base' => 7,
    ];

    if (isset($map[$slug])) {
        return $map[$slug];
    }

    // Fallback determinístico a partir do nome do slug.
    return (abs(crc32((string) $slug)) % 8) + 1;
}

/**
 * Desenha a grelha de apps (quadrados) usando o mesmo menu da barra lateral.
 */
function app_launcher_render_grid()
{
    $CI = &get_instance();

    // Mesma fonte de dados da barra lateral: nada de duplicar configuração.
    $items = $CI->app_menu->get_sidebar_menu_items();

    if (empty($items) || ! is_array($items)) {
        return;
    }

    $tiles = [];
    foreach ($items as $item) {
        // Salta itens colapsados/vazios (mesma regra da aside.php).
        $children = isset($item['children']) && is_array($item['children']) ? $item['children'] : [];
        if ((isset($item['collapse']) && $item['collapse']) && count($children) === 0) {
            continue;
        }

        if (empty($item['slug'])) {
            continue;
        }

        $tiles[] = [
            'slug'  => $item['slug'],
            'name'  => _l($item['name'], '', false),
            'href'  => app_launcher_item_href($item),
            'icon'  => ! empty($item['icon']) ? $item['icon'] : 'fa fa-th-large',
            'color' => app_launcher_color_index($item['slug']),
            'badge' => (isset($item['badge']['value']) && $item['badge']['value'] !== '') ? $item['badge']['value'] : null,
        ];
    }

    if (empty($tiles)) {
        return;
    }

    $CI->load->view('app_launcher/launcher', ['tiles' => $tiles]);
}
