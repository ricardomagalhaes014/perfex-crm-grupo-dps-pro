<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: DPS Móvel
Description: O CRM no telemóvel: as minhas leads, tarefas, agenda e propostas, em ecrãs feitos para o polegar. Instala-se no ecrã inicial como uma aplicação.
Version: 1.0.0
Requires at least: 2.3.*
Author: Grupo DPS
*/

define('DPS_MOVEL_MODULE_NAME', 'dps_movel');

/**
 * PORQUÊ UMA APP WEB E NÃO UMA APP NATIVA
 *
 * Uma app nativa (Flutter, pelo flutex_admin_api) dá a melhor experiência, mas
 * exige um programador de Flutter, publicação na App Store e na Google Play, e
 * uma versão nova por cada alteração — com a aprovação das lojas pelo meio.
 * Para uma equipa de vinte pessoas que precisa de mudar um botão na quinta-feira,
 * isso é caro de manter.
 *
 * Isto instala-se no ecrã inicial, abre sem barra de endereço, usa a sessão do
 * CRM e as notificações que o dps_webpush já trata. Uma alteração fica no ar
 * no momento em que a publico. Decisão do dono (03/08/2026).
 *
 * NÃO funciona sem rede, de propósito. Guardar leads no telemóvel para as
 * mostrar offline seria mostrar dados que podem já estar errados — o estado de
 * uma lead muda de minuto a minuto e dois comerciais a trabalhar sobre a mesma
 * cópia velha é pior do que uma mensagem a dizer "sem ligação".
 */

hooks()->add_action('admin_init', 'dps_movel_menu');

function dps_movel_menu()
{
    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('dps_movel', [
        'name'     => 'Telemóvel',
        'href'     => admin_url('dps_movel'),
        'icon'     => 'fa fa-mobile',
        'position' => 92,
        'badge'    => [],
    ]);
}

/**
 * Cor de um estado de lead, para os autocolantes da lista.
 */
function dps_movel_cor_estado($id)
{
    static $cores = null;

    if ($cores === null) {
        $cores = [];
        $CI    = &get_instance();
        foreach ($CI->db->get(db_prefix() . 'leads_status')->result_array() as $s) {
            $cores[(int) $s['id']] = $s['color'] ?: '#7c8798';
        }
    }

    return $cores[(int) $id] ?? '#7c8798';
}

/**
 * Número pronto para o WhatsApp: só dígitos, com indicativo de Portugal
 * quando o número foi guardado sem ele (que é a maioria).
 */
function dps_movel_numero_wa($telefone)
{
    $n = preg_replace('/\D+/', '', (string) $telefone);

    if ($n === '') {
        return '';
    }
    if (strlen($n) === 9 && $n[0] === '9') {
        $n = '351' . $n;
    }

    return $n;
}
