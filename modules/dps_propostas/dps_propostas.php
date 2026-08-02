<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Propostas & Envios | DPS
Description: Na ficha da lead, botões para enviar informação (dossier + unidades disponíveis) e propostas por WhatsApp, com aba de registo de propostas.
Version: 1.0.0
Requires at least: 2.3.2
Author: Grupo DPS
*/

define('DPS_PROPOSTAS_MODULE_NAME', 'dps_propostas');

$CI = &get_instance();

register_activation_hook(DPS_PROPOSTAS_MODULE_NAME, 'dps_propostas_activation');

function dps_propostas_activation()
{
    require __DIR__ . '/install.php';
}

$CI->load->helper(DPS_PROPOSTAS_MODULE_NAME . '/dps_propostas');

/**
 * Injeta a aba "Propostas" (com os botões) na ficha da lead.
 */
hooks()->add_action('after_lead_tabs_content', 'dps_propostas_render_lead_tab');

/**
 * Item de menu "Propostas Enviadas" (lista global, com filtro por comercial).
 */
hooks()->add_action('admin_init', 'dps_propostas_register_menu');

function dps_propostas_register_menu()
{
    $CI = &get_instance();
    if (! (function_exists('is_staff_member') && is_staff_member())) {
        return;
    }
    $CI->app_menu->add_sidebar_menu_item('dps-visao-geral', [
        'name'     => 'Visão Geral',
        'href'     => admin_url('dps_propostas/visao'),
        'icon'     => 'fa fa-line-chart menu-icon',
        'position' => 15,
    ]);
    $CI->app_menu->add_sidebar_menu_item('dps-propostas-enviadas', [
        'name'     => 'Propostas Enviadas',
        'href'     => admin_url('dps_propostas/todas'),
        'icon'     => 'fa fa-file-pdf-o menu-icon',
        'position' => 17,
    ]);
}

/**
 * Na lista de leads: renomeia a coluna "Empresa" para "Proposta".
 * (A célula passa a ter um botão — ver application/views/admin/tables/leads.php.)
 */
hooks()->add_filter('leads_table_columns', 'dps_propostas_leads_columns');

function dps_propostas_leads_columns($cols)
{
    /*
     * Antes renomeava-se a coluna "Empresa" para "Proposta" — resquício de
     * quando este módulo reaproveitava essa coluna. A tabela de leads passou
     * a ter uma coluna "Proposta" própria, por isso a renomeação deixava
     * DUAS colunas com o mesmo nome (a segunda a mostrar a empresa, quase
     * sempre vazia) e escondia o cabeçalho "Empresa". Deixa-se intacta.
     */
    return $cols;
}

/**
 * Modal global + função dps_open_proposta(leadId) para abrir o painel de
 * proposta/informação a partir da lista de leads (sem abrir a lead).
 */
hooks()->add_action('app_admin_footer', 'dps_propostas_footer_modal');

function dps_propostas_footer_modal()
{
    if (! (function_exists('is_staff_member') && is_staff_member())) {
        return;
    }
    ?>
    <div class="modal fade" id="dps_prop_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document" style="width:92%;max-width:1150px;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-file-pdf-o"></i> Proposta / Informação</h4>
                </div>
                <div class="modal-body" id="dps_prop_modal_body" style="min-height:200px;">A carregar...</div>
            </div>
        </div>
    </div>
    <script>
    function dps_open_proposta(leadId) {
        var body = document.getElementById('dps_prop_modal_body');
        if (body) { body.innerHTML = '<p class="text-muted">A carregar...</p>'; }
        $('#dps_prop_modal').modal('show');
        $.get(admin_url + 'dps_propostas/painel/' + leadId, function (html) {
            $('#dps_prop_modal_body').html(html);
        }).fail(function () {
            $('#dps_prop_modal_body').html('<p class="text-danger">Erro ao carregar o painel.</p>');
        });
    }
    </script>
    <?php
}
