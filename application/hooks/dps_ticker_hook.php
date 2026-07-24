<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Rodapé de notícias (ticker) em todas as páginas do admin.
 * - Frases geridas em /admin/dps_ticker (item "Rodapé" dentro do menu Admin)
 * - Cada frase desliza da direita para a esquerda durante 15 segundos,
 *   em ciclo contínuo, visível a todos os utilizadores.
 */

if (!function_exists('dps_ticker_register')) {
    function dps_ticker_register()
    {
        hooks()->add_action('admin_init', 'dps_ticker_menu');
        hooks()->add_action('app_admin_footer', 'dps_ticker_render');
    }
}

if (!function_exists('dps_ticker_menu')) {
    function dps_ticker_menu()
    {
        if (!is_admin()) {
            return;
        }
        $CI = &get_instance();
        // Nome fora da lista de ordenação do menu → o filtro de
        // reorganização arruma-o automaticamente dentro do botão "Admin".
        $CI->app_menu->add_sidebar_menu_item('dps_ticker', [
            'name'     => 'Rodapé',
            'href'     => admin_url('dps_ticker'),
            'icon'     => 'fa fa-bullhorn',
            'position' => 90,
            'badge'    => [],
        ]);
    }
}

if (!function_exists('dps_ticker_render')) {
    function dps_ticker_render()
    {
        $CI = &get_instance();

        $t = db_prefix() . 'dps_ticker';
        if (!$CI->db->table_exists($t)) {
            return;
        }

        $mensagens = $CI->db
            ->where('ativo', 1)
            ->order_by('id', 'ASC')
            ->get($t)
            ->result_array();

        if (empty($mensagens)) {
            return;
        }

        $frases = array_values(array_map(fn ($m) => $m['mensagem'], $mensagens));
        ?>
<style>
#dps-ticker {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 34px;
    background: #0D1F3C;
    color: #F5F2ED;
    z-index: 99990;
    overflow: hidden;
    border-top: 2px solid #C5A55A;
    font-size: 14px;
}
#dps-ticker-msg {
    position: absolute;
    top: 0;
    line-height: 32px;
    white-space: nowrap;
    will-change: transform;
    padding: 0 8px;
}
#dps-ticker-msg.dps-anim {
    animation: dpsTickerSlide 15s linear;
}
@keyframes dpsTickerSlide {
    from { transform: translateX(100vw); }
    to   { transform: translateX(-100%); }
}
body { padding-bottom: 36px; }
</style>
<div id="dps-ticker"><span id="dps-ticker-msg"></span></div>
<script>
(function () {
    var frases = <?php echo json_encode($frases, JSON_UNESCAPED_UNICODE); ?>;
    var el = document.getElementById('dps-ticker-msg');
    var i = 0;

    function mostra() {
        el.textContent = '📢 ' + frases[i % frases.length];
        i++;
        // Reinicia a animação
        el.classList.remove('dps-anim');
        void el.offsetWidth;
        el.classList.add('dps-anim');
    }

    el.addEventListener('animationend', mostra);
    mostra();
})();
</script>
        <?php
    }
}
