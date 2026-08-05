<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Funil de Leads | DPS
Description: Vista de funil/fluxo das leads, com os estados agrupados por fase e contagens em tempo real. Cada estado abre a lista de leads correspondente.
Version: 1.0.0
Requires at least: 2.3.2
Author: Grupo DPS
*/

define('DPS_FUNIL_MODULE_NAME', 'dps_funil');

$CI = &get_instance();

/**
 * Regista o item na barra lateral (e, por consequência, no ecrã de apps).
 */
hooks()->add_action('admin_init', 'dps_funil_register_menu');

// Janela de entrada com as leads por estado (aparece a seguir à das interações).
hooks()->add_action('app_admin_footer', 'dps_funil_popup_estados');

/**
 * Ao entrar no CRM, mostra ao comercial quantas leads tem em cada estado.
 *
 * Aparece só no painel e só uma vez por sessão. Espera que a janela das
 * interações (dps-ic-popup-overlay) seja fechada, para não se sobreporem —
 * se essa não existir, aparece na mesma passado um instante.
 */
function dps_funil_popup_estados()
{
    $CI  = &get_instance();
    $uri = $CI->uri->uri_string();

    if (!preg_match('#^admin(/dashboard|/?$)#', $uri)) {
        return;
    }
    if (!function_exists('is_staff_member') || !is_staff_member()) {
        return;
    }

    $p        = db_prefix();
    $staff_id = (int) get_staff_user_id();

    // Admin vê o total; comercial vê só as suas.
    $onde = (function_exists('is_admin') && is_admin()) ? '' : ' AND l.assigned = ' . $staff_id;

    $linhas = $CI->db->query("
        SELECT s.id, s.name, s.color, COUNT(l.id) AS total
        FROM {$p}leads_status s
        LEFT JOIN {$p}leads l ON l.status = s.id {$onde}
        GROUP BY s.id
        ORDER BY s.statusorder, s.id
    ")->result_array();

    $total = 0;
    foreach ($linhas as $l) {
        $total += (int) $l['total'];
    }

    // Nada para mostrar? Não incomodar.
    if ($total === 0) {
        return;
    }
    ?>
    <div id="dps-fn-overlay" style="display:none;position:fixed;inset:0;z-index:99990;background:rgba(8,21,40,.72);
         align-items:center;justify-content:center;padding:20px;overflow:auto;">
      <div style="background:#fff;border-radius:14px;max-width:640px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.35);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#0d1b2e,#16304f);padding:20px 24px;color:#fff;">
          <div style="font-size:.72rem;letter-spacing:.18em;text-transform:uppercase;color:#C5A55A;font-weight:700;">As suas leads</div>
          <div style="font-size:1.35rem;font-weight:700;margin-top:4px;">
            <?php echo number_format($total, 0, ',', '.'); ?> leads por trabalhar
          </div>
        </div>

        <div style="padding:18px 24px;max-height:46vh;overflow:auto;">
          <table style="width:100%;border-collapse:collapse;font-size:.92rem;">
            <tbody>
            <?php foreach ($linhas as $l) {
                if ((int) $l['total'] === 0) { continue; }
                $cor = $l['color'] ?: '#8a97a6';
            ?>
              <tr style="border-bottom:1px solid #eef1f5;">
                <td style="padding:8px 4px;">
                  <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo html_escape($cor); ?>;margin-right:8px;"></span>
                  <?php echo html_escape($l['name']); ?>
                </td>
                <td style="padding:8px 4px;text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">
                  <a href="<?php echo admin_url('dps_funil/estado/' . (int) $l['id']); ?>" style="color:#0d1b2e;">
                    <?php echo number_format((int) $l['total'], 0, ',', '.'); ?>
                  </a>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>

        <div style="padding:16px 24px;background:#fbf7ee;border-top:1px solid #efe6d2;text-align:center;">
          <div style="font-family:Georgia,serif;font-size:1.05rem;color:#8a6414;font-style:italic;">
            «Se não contactas, alguém o faz e vende por ti.»
          </div>
        </div>

        <div style="padding:14px 24px 20px;display:flex;gap:10px;justify-content:center;">
          <a href="<?php echo admin_url('dps_funil'); ?>" class="btn btn-default btn-sm">Ver funil</a>
          <button type="button" class="btn btn-info btn-sm" onclick="dpsFnFechar()">Começar a trabalhar</button>
        </div>
      </div>
    </div>

    <script>
    (function () {
      var CHAVE = 'dps_fn_visto';
      function jaViu() { try { return sessionStorage.getItem(CHAVE) === '1'; } catch (e) { return false; } }
      window.dpsFnFechar = function () {
        var o = document.getElementById('dps-fn-overlay');
        if (o) { o.style.display = 'none'; }
        try { sessionStorage.setItem(CHAVE, '1'); } catch (e) {}
      };
      function mostrar() {
        if (jaViu()) { return; }
        var o = document.getElementById('dps-fn-overlay');
        if (o) { o.style.display = 'flex'; }
      }
      // Esperar que a janela das interações feche; se não existir, mostrar já.
      function arrancar() {
        if (jaViu()) { return; }
        var ic = document.getElementById('dps-ic-popup-overlay');
        if (!ic) { setTimeout(mostrar, 900); return; }
        var n = 0;
        var iv = setInterval(function () {
          var visivel = ic.style.display && ic.style.display !== 'none';
          if (!visivel || ++n > 600) {           // fechou (ou 5 min de espera)
            clearInterval(iv);
            setTimeout(mostrar, 400);
          }
        }, 500);
      }
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(arrancar, 1200); });
      } else {
        setTimeout(arrancar, 1200);
      }
    })();
    </script>
    <?php
}

function dps_funil_register_menu()
{
    $CI = &get_instance();

    if (! (function_exists('is_staff_member') && is_staff_member())) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('dps-funil', [
        'name'     => 'Funil de Leads',
        'href'     => admin_url('dps_funil'),
        'icon'     => 'fa fa-filter menu-icon',
        'position' => 16,
    ]);
}
