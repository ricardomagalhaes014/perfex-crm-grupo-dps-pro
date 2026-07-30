<?php
/**
 * Module Name: VoIPstudio DPS
 * Description: Integração VoIPstudio - click-to-call nas fichas de leads/clientes e registo de chamadas no CRM
 * Version: 1.0.0
 * Requires at least: 2.3.*
 * Author: DPS Imobiliário
 */
defined('BASEPATH') or exit('No direct script access allowed');

define('VOIPSTUDIO_DPS_MODULE_NAME', 'voipstudio_dps');

register_activation_hook(VOIPSTUDIO_DPS_MODULE_NAME, 'voipstudio_dps_activation_hook');
function voipstudio_dps_activation_hook()
{
    require __DIR__ . '/install.php';
}

/* Menu lateral (Admin) */
hooks()->add_action('admin_init', 'voipstudio_dps_init_menu');
function voipstudio_dps_init_menu()
{
    $CI = &get_instance();
    // Sem portão (padrão Sofia Calls): o admin_init já só corre para staff
    // autenticado. O relatório restringe cada um às suas chamadas e as
    // Definições continuam atrás de is_admin().
    if (true) {
        $CI->app_menu->add_sidebar_menu_item('voipstudio-dps', [
            'name'     => 'VoIPstudio',
            'href'     => admin_url('voipstudio_dps/report'),
            'icon'     => 'fa fa-phone',
            'position' => 36,
        ]);
        $CI->app_menu->add_sidebar_children_item('voipstudio-dps', [
            'slug'     => 'voipstudio-dps-report',
            'name'     => 'Relatório de Chamadas',
            'href'     => admin_url('voipstudio_dps/report'),
            'position' => 1,
        ]);
        if (is_admin()) {
            $CI->app_menu->add_sidebar_children_item('voipstudio-dps', [
                'slug'     => 'voipstudio-dps-settings',
                'name'     => 'Definições',
                'href'     => admin_url('voipstudio_dps'),
                'position' => 2,
            ]);
        }
    }
}

/* Permissões */
hooks()->add_filter('staff_permissions', 'voipstudio_dps_permissions', 10, 2);
function voipstudio_dps_permissions($permissions, $data)
{
    $permissions['voipstudio_dps'] = [
        'name'         => 'VoIPstudio',
        'capabilities' => ['view' => 'Ver / Ligar'],
    ];
    return $permissions;
}

/* Botões de chamada + JS global no admin */
hooks()->add_action('app_admin_footer', 'voipstudio_dps_footer_js');
function voipstudio_dps_footer_js()
{
    $CI       = &get_instance();
    $call_url = admin_url('voipstudio_dps/call');
    ?>
<script>
(function () {
    'use strict';
    var CALL_URL = '<?php echo $call_url; ?>';
    var csrf = { name: '<?php echo $CI->security->get_csrf_token_name(); ?>',
                 hash: '<?php echo $CI->security->get_csrf_hash(); ?>' };

    function vsCall(number, ctxType, ctxId) {
        if (!number) return;
        if (!confirm('Ligar para ' + number + ' via VoIPstudio?')) return;
        var data = { number: number, rel_type: ctxType || '', rel_id: ctxId || '' };
        data[csrf.name] = csrf.hash;
        $.post(CALL_URL, data)
            .done(function (r) {
                try { r = typeof r === 'string' ? JSON.parse(r) : r; } catch (e) {}
                if (r && r.success) {
                    alert_float('success', 'A ligar... O seu telefone/softphone VoIPstudio vai tocar primeiro.');
                } else {
                    alert_float('danger', 'Falha: ' + (r && r.message ? r.message : 'erro desconhecido'));
                }
            })
            .fail(function (x) { alert_float('danger', 'Erro na chamada (' + x.status + ')'); });
    }
    window.voipstudioCall = vsCall;

    function detectContext() {
        var m = window.location.href.match(/leads\/index\/(\d+)/) || $('#lead_id').val() ? null : null;
        return null;
    }

    /* Adiciona botão 📞 junto a links tel: e a campos de telefone conhecidos */
    function decorate() {
        // 1) todos os links tel: (excluindo os nossos botões)
        $('a[href^="tel:"]').not('.vs-btn').each(function () {
            var $a = $(this);
            if ($a.attr('data-vs-done')) return;
            $a.attr('data-vs-done', '1');
            var num = ($a.attr('href') || '').replace('tel:', '').trim();
            if (!num) return;
            var ctxType = '', ctxId = '';
            // contexto lead (modal de lead aberto)
            var $leadModal = $a.closest('#lead-modal');
            if ($leadModal.length) { ctxType = 'lead'; ctxId = $('input[name="leadid"]').val() || ''; }
            // contexto cliente
            var mm = window.location.href.match(/clients\/client\/(\d+)/);
            if (!ctxType && mm) { ctxType = 'customer'; ctxId = mm[1]; }
            var btnStyle = 'margin-left:6px;display:inline-block;padding:1px 8px;border-radius:12px;font-weight:bold;font-size:11px;text-decoration:none;';
            var $normal = $('<a class="vs-btn" title="Chamada normal" style="' + btnStyle + 'border:1px solid #337ab7;color:#337ab7;"><i class="fa fa-phone"></i> Ligar</a>');
            $normal.attr('href', 'tel:' + num);
            var $voip = $('<a href="#" class="vs-btn" title="Ligar via VoIPstudio" style="' + btnStyle + 'border:1px solid #28a745;color:#28a745;"><i class="fa fa-phone"></i> VoIP</a>');
            $voip.on('click', function (e) { e.preventDefault(); vsCall(num, ctxType, ctxId); });
            $a.after($voip).after($normal);
        });
    }
    decorate();
    setInterval(decorate, 1500); // apanha modais/tabelas carregadas por AJAX
})();
</script>
    <?php
}

/* Sincronização de chamadas (CDR) no cron do Perfex */
hooks()->add_action('after_cron_run', 'voipstudio_dps_cron_sync');
function voipstudio_dps_cron_sync()
{
    $last = get_option('voipstudio_dps_last_sync');
    if ($last && (time() - (int) $last) < 300) {
        return; // no máx. de 5 em 5 minutos
    }
    update_option('voipstudio_dps_last_sync', time());
    $CI = &get_instance();
    $CI->load->model('voipstudio_dps/voipstudio_dps_model');
    try {
        $CI->voipstudio_dps_model->sync_cdrs();
    } catch (Exception $e) {
        log_activity('VoIPstudio DPS: erro no sync CDR - ' . $e->getMessage());
    }
}

/* Tab "Chamadas" no perfil da lead */
hooks()->add_filter('lead_profile_tabs', 'voipstudio_dps_lead_tab');
function voipstudio_dps_lead_tab($tabs)
{
    $tabs['voipstudio_calls'] = [
        'name'     => 'Chamadas',
        'icon'     => 'fa fa-phone',
        'view'     => 'voipstudio_dps/lead_calls_tab',
        'position' => 50,
    ];
    return $tabs;
}
