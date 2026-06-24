<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Interacções por Comercial
Description: Relatório de interacções por comercial com filtros de período e status de lead. Pop-up no painel.
Version: 1.1.0
Requires at least: 2.3.*
Author: DPS Imobiliário
*/
define('DPS_INTERACOES_MODULE_NAME', 'dps_interacoes');
register_activation_hook(DPS_INTERACOES_MODULE_NAME, 'dps_interacoes_activate');
register_language_files(DPS_INTERACOES_MODULE_NAME, ['dps_interacoes']);
hooks()->add_action('admin_init', 'dps_interacoes_init_menu_items');
hooks()->add_action('admin_init', 'dps_interacoes_register_permissions');
hooks()->add_action('app_admin_footer', 'dps_interacoes_popup_footer');

function dps_interacoes_activate()
{
    // Sem tabelas adicionais necessárias
}

function dps_interacoes_init_menu_items()
{
    $CI = &get_instance();
    if (!is_admin() && !staff_can('view', DPS_INTERACOES_MODULE_NAME)) {
        return;
    }
    $CI->app_menu->add_sidebar_menu_item('dps-interacoes', [
        'name'     => 'Interacções Comercial',
        'icon'     => 'fa fa-bar-chart',
        'position' => 36,
        'href'     => admin_url('dps_interacoes'),
    ]);
}

function dps_interacoes_register_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view' => _l('permission_view') ?: 'View',
        ],
    ];
    register_staff_capabilities(DPS_INTERACOES_MODULE_NAME, $capabilities, 'Interacções Comercial');
}

function dps_interacoes_popup_footer()
{
    // Mostrar apenas no painel (dashboard) do CRM
    $CI = &get_instance();
    $uri = $CI->uri->uri_string();

    // Mostrar apenas no painel principal
    if (!preg_match('#^admin(/dashboard|/?$)#', $uri)) {
        return;
    }

    // Objectivos
    $obj_semana = 200;
    $obj_mes    = 800;
    $objectivo  = $obj_semana; // últimos 7 dias

    $date_from = date('Y-m-d 00:00:00', strtotime('-6 days'));
    $date_to   = date('Y-m-d 23:59:59');
    $p         = db_prefix();

    // Obter staff activo
    $staff_result = $CI->db->query("SELECT staffid, CONCAT(firstname, ' ', lastname) AS nome FROM {$p}staff WHERE active = 1 ORDER BY firstname ASC");
    $staff_list   = $staff_result ? $staff_result->result_array() : [];

    $comerciais = [];
    foreach ($staff_list as $s) {
        $sid       = (int)$s['staffid'];
        $count_sql = "
            SELECT COUNT(al.id) AS total
            FROM {$p}lead_activity_log al
            INNER JOIN {$p}leads l ON l.id = al.leadid
            WHERE l.assigned = {$sid}
            AND al.description LIKE '? Nota%'
            AND al.date >= '{$date_from}'
            AND al.date <= '{$date_to}'
        ";
        $count_res = $CI->db->query($count_sql);
        $total     = 0;
        if ($count_res) {
            $row   = $count_res->row_array();
            $total = (int)($row['total'] ?? 0);
        }
        $pct          = ($objectivo > 0) ? round(($total / $objectivo) * 100, 1) : 0;
        $comerciais[] = ['nome' => $s['nome'], 'total' => $total, 'pct' => $pct];
    }

    // Ordenar por total desc
    usort($comerciais, function($a, $b) { return $b['total'] - $a['total']; });

    $label_periodo = 'Últimos 7 dias';
    $label_datas   = date('d/m/Y', strtotime($date_from)) . ' a ' . date('d/m/Y', strtotime($date_to));

    ob_start();
    ?>
    <!-- DPS Interacções Pop-up -->
    <div id="dps-ic-popup-overlay" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.55);z-index:99998;pointer-events:none;"></div>
    <div id="dps-ic-popup" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:99999;background:#fff;border-radius:12px;box-shadow:0 8px 40px rgba(0,0,0,0.22);width:90%;max-width:820px;max-height:88vh;overflow:hidden;flex-direction:column;">
        <!-- Header -->
        <div style="background:#1a1a2e;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;border-radius:12px 12px 0 0;">
            <div>
                <span style="color:#f97316;font-size:18px;font-weight:700;">📊 Interacções por Comercial</span>
                <span style="color:#aaa;font-size:13px;margin-left:12px;"><?php echo $label_periodo; ?> &nbsp;|&nbsp; <?php echo $label_datas; ?></span>
            </div>
            <button onclick="dpsIcClosePopup()" style="background:none;border:none;color:#fff;font-size:22px;cursor:pointer;line-height:1;padding:0 4px;">&times;</button>
        </div>
        <!-- Objectivo -->
        <div style="background:#fff8f0;padding:10px 24px;border-bottom:1px solid #ffe0c0;font-size:13px;color:#c2540a;">
            🎯 Objectivo: <strong><?php echo $objectivo; ?> interacções</strong> &nbsp;·&nbsp; Base: 200/semana · 800/mês
        </div>
        <!-- Tabela -->
        <div style="overflow-y:auto;flex:1;padding:0 24px 20px 24px;">
            <table style="width:100%;border-collapse:collapse;margin-top:16px;">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="padding:10px 12px;text-align:left;font-size:13px;font-weight:700;color:#333;border-bottom:2px solid #e8e8e8;">#</th>
                        <th style="padding:10px 12px;text-align:left;font-size:13px;font-weight:700;color:#333;border-bottom:2px solid #e8e8e8;">Comercial</th>
                        <th style="padding:10px 12px;text-align:center;font-size:13px;font-weight:700;color:#333;border-bottom:2px solid #e8e8e8;">Interacções</th>
                        <th style="padding:10px 12px;text-align:left;font-size:13px;font-weight:700;color:#333;border-bottom:2px solid #e8e8e8;min-width:200px;">Objectivo / Concretizado</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($comerciais as $c):
                    $pct      = $c['pct'];
                    $pct_disp = min($pct, 100);
                    if ($pct >= 100)    { $color = '#22c55e'; $tcolor = '#16a34a'; }
                    elseif ($pct >= 50) { $color = '#f97316'; $tcolor = '#ea6c0a'; }
                    else                { $color = '#ef4444'; $tcolor = '#dc2626'; }
                ?>
                    <tr style="border-bottom:1px solid #f0f0f0;">
                        <td style="padding:10px 12px;font-size:13px;color:#888;"><?php echo $i++; ?></td>
                        <td style="padding:10px 12px;font-size:13px;font-weight:600;color:#333;"><?php echo htmlspecialchars($c['nome']); ?></td>
                        <td style="padding:10px 12px;text-align:center;">
                            <span style="display:inline-block;background:<?php echo $c['total']>0?'#f97316':'#e0e0e0'; ?>;color:<?php echo $c['total']>0?'#fff':'#999'; ?>;border-radius:20px;padding:3px 12px;font-size:13px;font-weight:700;min-width:36px;text-align:center;">
                                <?php echo $c['total']; ?>
                            </span>
                        </td>
                        <td style="padding:10px 12px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="flex:1;background:#f0f0f0;border-radius:20px;height:10px;overflow:hidden;min-width:80px;">
                                    <div style="width:<?php echo $pct_disp; ?>%;height:10px;border-radius:20px;background:<?php echo $color; ?>;transition:width .3s;"></div>
                                </div>
                                <span style="font-size:12px;font-weight:700;color:<?php echo $tcolor; ?>;white-space:nowrap;min-width:48px;"><?php echo number_format($pct, 1, ',', '.'); ?>%</span>
                                <span style="font-size:11px;color:#aaa;white-space:nowrap;">/ <?php echo $objectivo; ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Footer -->
        <div style="padding:12px 24px;border-top:1px solid #eee;display:flex;justify-content:space-between;align-items:center;background:#fafafa;border-radius:0 0 12px 12px;">
            <a href="<?php echo admin_url('dps_interacoes'); ?>" style="font-size:13px;color:#1976d2;text-decoration:none;font-weight:600;">Ver página completa →</a>
            <button onclick="dpsIcClosePopup()" style="background:#f97316;color:#fff;border:none;border-radius:6px;padding:8px 20px;font-size:13px;font-weight:600;cursor:pointer;">Fechar</button>
        </div>
    </div>
    <script>
    (function() {
        function dpsIcShowPopup() {
            var overlay = document.getElementById('dps-ic-popup-overlay');
            var popup   = document.getElementById('dps-ic-popup');
            if (overlay) { overlay.style.display = 'block'; overlay.style.pointerEvents = 'auto'; }
            if (popup)   { popup.style.display = 'flex'; }
        }
        window.dpsIcClosePopup = function() {
            var overlay = document.getElementById('dps-ic-popup-overlay');
            var popup   = document.getElementById('dps-ic-popup');
            if (overlay) { overlay.style.display = 'none'; overlay.style.pointerEvents = 'none'; }
            if (popup)   { popup.style.display = 'none'; }
        };
        // Fechar ao clicar no overlay
        var overlay = document.getElementById('dps-ic-popup-overlay');
        if (overlay) {
            overlay.addEventListener('click', window.dpsIcClosePopup);
        }
        // Mostrar pop-up ao carregar a página
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', dpsIcShowPopup);
        } else {
            dpsIcShowPopup();
        }
    })();
    </script>
    <?php
    echo ob_get_clean();
}
