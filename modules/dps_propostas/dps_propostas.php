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
/**
 * Motivos de perda. Obrigatório escolher um ao marcar uma proposta como
 * recusada — pedido do dono (07/08/2026).
 *
 * A razão de ser fechada em lista e não em texto livre: só assim se consegue
 * depois contar quantas propostas se perderam por preço e quantas por prazo.
 * Texto livre dá "preço", "caro", "valor alto" e "$" — e não se soma nada.
 *
 * A chave é o que fica gravado; muda-se o rótulo sem estragar o histórico.
 */
function dps_propostas_motivos_perda()
{
    return [
        'preco'                  => 'Preço',
        'falta_capital'          => 'Falta de capital',
        'localizacao'            => 'Localização',
        'tipologia_indisponivel' => 'Tipologia indisponível',
        'deixou_responder'       => 'Deixou de responder',
        'comprou_concorrente'    => 'Comprou com concorrente',
        'prazo_conclusao'        => 'Prazo de conclusão',
        'condicoes_pagamento'    => 'Condições de pagamento',
        'desconfianca_projeto'   => 'Desconfiança no projeto',
    ];
}

/** O rótulo de um motivo gravado, ou o próprio valor se for de outra época. */
function dps_propostas_motivo_label($chave)
{
    $m = dps_propostas_motivos_perda();

    /*
     * Este não entra na lista de cima de propósito: não é um motivo que
     * alguém escolha, é o que o sistema escreve quando cancela sozinho uma
     * proposta cuja fracção saiu do mercado. Ter rótulo é o que o faz
     * aparecer legível nos relatórios.
     */
    $m['unidade_indisponivel'] = 'Unidade já não disponível';

    return $m[(string) $chave] ?? (string) $chave;
}

/* ---------------------------------------------------------------------
 * CANCELAMENTO AUTOMÁTICO DE PROPOSTAS
 *
 * Uma proposta por responder de uma fracção entretanto reservada, vendida ou
 * marcada DPS não tem para onde ir. Cancela-se, avisa-se o cliente por email e
 * dá-se ao comercial a lista de quem tem de voltar a contactar.
 *
 * Dois gatilhos, porque há dois caminhos para uma fracção sair do mercado:
 *   1. o CRM — uma venda que avança de estado;
 *   2. o simulador — o administrador a marcar à mão no Modo de Edição, que o
 *      CRM não chega a saber. É para esse que serve a passagem do cron.
 * ------------------------------------------------------------------ */

hooks()->add_action('dps_venda_estado_alterado', 'dps_propostas_cancelar_ao_mudar_venda');
hooks()->add_action('after_cron_run', 'dps_propostas_cron_cancelar_indisponiveis');

/**
 * A venda mudou de estado — se a fracção saiu do mercado, as propostas por
 * responder dessa fracção caem com ela. Imediato, sem esperar pelo cron: quem
 * acabou de reservar quer ver a lista limpa já.
 */
function dps_propostas_cancelar_ao_mudar_venda($dados)
{
    $venda_id = (int) ($dados['venda_id'] ?? 0);

    if ($venda_id <= 0) {
        return;
    }

    $CI = &get_instance();
    $v  = $CI->db->select('empreendimento, unidade')
                 ->where('id', $venda_id)
                 ->get(db_prefix() . 'dps_vendas')
                 ->row();

    if (! $v || trim((string) $v->unidade) === '') {
        return;
    }

    /*
     * Sem empreendimento nem unidade filtrados na consulta ficaria a varrer a
     * tabela toda a cada mudança de estado de qualquer venda.
     */
    $r = dps_propostas_cancelar_indisponiveis($v->empreendimento, null, true);

    if ($r['canceladas'] > 0) {
        log_activity('Propostas: ' . $r['canceladas'] . ' cancelada(s) por a fracção ter saído do mercado ('
            . $v->empreendimento . ' ' . $v->unidade . '); ' . $r['emails'] . ' cliente(s) avisado(s).');
    }
}

/**
 * A rede de segurança: apanha o que o CRM não viu.
 *
 * Corre uma vez por dia. O interruptor existe porque no dia em que isto
 * arrancou havia 218 propostas antigas nesta situação, de 150 clientes — um
 * envio desses tem de ser uma decisão, não um efeito secundário de instalar
 * uma funcionalidade.
 */
function dps_propostas_cron_cancelar_indisponiveis()
{
    if ((int) get_option('dps_propostas_cancelar_auto') !== 1) {
        return;
    }

    $hoje    = date('Y-m-d');
    $ultimo  = (string) get_option('dps_propostas_cancelar_ultimo');

    if ($ultimo === $hoje) {
        return;
    }

    update_option('dps_propostas_cancelar_ultimo', $hoje);

    $r = dps_propostas_cancelar_indisponiveis(null, null, true);

    if ($r['canceladas'] > 0) {
        log_activity('Propostas (cron): ' . $r['canceladas'] . ' cancelada(s) por a fracção já não estar '
            . 'disponível; ' . $r['emails'] . ' cliente(s) avisado(s); '
            . count($r['comerciais']) . ' comercial(is) avisado(s).');
    }
}

hooks()->add_action('admin_init', 'dps_propostas_coluna_motivo');

/**
 * A coluna do motivo, acrescentada em instalações que já existiam.
 */
function dps_propostas_coluna_motivo()
{
    static $feito = false;
    if ($feito) {
        return;
    }
    $feito = true;

    $CI = &get_instance();
    $t  = db_prefix() . 'dps_propostas';

    if (!$CI->db->table_exists($t)) {
        return;
    }
    if (!$CI->db->field_exists('motivo_perda', $t)) {
        $CI->db->query("ALTER TABLE `{$t}` ADD `motivo_perda` VARCHAR(40) NULL DEFAULT NULL AFTER `outcome`");
    }
}

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
    <script>
    /*
     * Motivo da perda — UMA implementação, para todos os ecrãs.
     *
     * Havia dois botões "Recusada" (a ficha da lead e as Propostas Enviadas) e
     * a caixa só tinha sido posta num deles: no outro o servidor recusava o
     * pedido por falta de motivo e o comercial não percebia porquê. Vive aqui,
     * no rodapé, para não voltar a haver duas cópias a divergir.
     */
    window.DPS_MOTIVOS_PERDA = <?= json_encode(dps_propostas_motivos_perda(), JSON_UNESCAPED_UNICODE); ?>;

    window.dpsPedirMotivoPerda = function (aoEscolher) {
        var ov = document.createElement('div');
        ov.style.cssText = 'position:fixed;inset:0;background:rgba(8,21,40,.65);z-index:2147483000;'
            + 'display:flex;align-items:center;justify-content:center;padding:20px;';

        var opcoes = '';
        Object.keys(window.DPS_MOTIVOS_PERDA).forEach(function (k) {
            opcoes += '<option value="' + k + '">'
                + $('<span>').text(window.DPS_MOTIVOS_PERDA[k]).html() + '</option>';
        });

        var cx = document.createElement('div');
        cx.style.cssText = 'background:#fff;border-radius:12px;padding:22px 24px;max-width:400px;width:100%;'
            + 'box-shadow:0 20px 60px rgba(0,0,0,.3);font-family:inherit;';
        cx.innerHTML =
              '<div style="font-weight:700;font-size:1.05rem;margin-bottom:4px;">Proposta recusada</div>'
            + '<div style="color:#5a6675;font-size:.86rem;margin-bottom:16px;">'
            +   'A lead passa para "Para outras oportunidades". Porque é que se perdeu?</div>'
            + '<select class="form-control" id="dps-motivo-perda" style="margin-bottom:16px;">'
            +   '<option value="">— escolha o motivo —</option>' + opcoes + '</select>'
            + '<div style="display:flex;gap:8px;">'
            +   '<button type="button" class="btn btn-danger" id="dps-motivo-ok" style="flex:1;">Marcar como recusada</button>'
            +   '<button type="button" class="btn btn-default" id="dps-motivo-no">Cancelar</button>'
            + '</div>';

        ov.appendChild(cx);
        document.body.appendChild(ov);

        function fechar() { if (ov.parentNode) { ov.parentNode.removeChild(ov); } }
        cx.querySelector('#dps-motivo-no').onclick = fechar;
        ov.addEventListener('click', function (ev) { if (ev.target === ov) { fechar(); } });
        cx.querySelector('#dps-motivo-ok').onclick = function () {
            var m = cx.querySelector('#dps-motivo-perda').value;
            if (!m) {
                if (typeof alert_float === 'function') { alert_float('warning', 'Escolha o motivo — é obrigatório.'); }
                return;
            }
            fechar();
            aoEscolher(m);
        };
    };
    </script>
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

/**
 * Varredura das propostas que ficaram penduradas em PENDING.
 *
 * O WhatsApp confirma uma mensagem em segundos — no teste de 12/08/2026 foi
 * um segundo entre o envio e o SERVER_ACK. Uma proposta que passe meia hora
 * sem recibo nenhum não está lenta: não saiu. Antes disto ficava para sempre
 * marcada como enviada, e o comercial só descobria pelo silêncio do cliente.
 *
 * Meia hora é folgado de propósito: o recibo pode atrasar se a Evolution
 * estiver a reconectar, e é preferível marcar tarde do que marcar de errado.
 */
hooks()->add_action('perfex_cron', 'dps_propostas_marcar_penduradas');
function dps_propostas_marcar_penduradas()
{
    $CI = &get_instance();
    $t  = db_prefix() . 'dps_propostas';

    if (! $CI->db->table_exists($t)) {
        return;
    }

    $campos = $CI->db->list_fields($t);
    if (! in_array('wa_status', $campos, true)) {
        return;   // ainda sem os recibos instalados
    }

    $CI->db->where('wa_status', 'PENDING')
        ->where('wa_ok', 1)
        ->where('wa_msg_id IS NOT NULL', null, false)
        ->where('created_at <', date('Y-m-d H:i:s', strtotime('-30 minutes')))
        // Só as de depois de os recibos existirem: as antigas nunca chegaram a
        // ter hipótese de ser confirmadas e marcá-las agora seria inventar.
        ->where('created_at >', '2026-08-12 14:00:00')
        ->update($t, [
            'wa_status'    => 'SEM_RECIBO',
            'wa_ok'        => 0,
            'wa_status_at' => date('Y-m-d H:i:s'),
        ]);

    $n = $CI->db->affected_rows();

    if ($n > 0) {
        log_activity('dps_propostas: ' . $n . ' proposta(s) sem recibo do WhatsApp ao fim de 30 min — marcadas como não enviadas.');
    }
}
