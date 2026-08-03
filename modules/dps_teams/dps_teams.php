<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: DPS Teams
Description: Gestão de equipas por área (Dentária, Imobiliário, Media) com hierarquia Super Admin > Gestor > Comercial
Version: 1.0.0
Author: Grupo DPS
*/

if (!defined('DPS_TEAMS_MODULE')) {
    define('DPS_TEAMS_MODULE', basename(__DIR__));
}

// Registar activação
register_activation_hook(DPS_TEAMS_MODULE, 'dps_teams_activation_hook');
function dps_teams_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

// Registar desinstalação
register_uninstall_hook(DPS_TEAMS_MODULE, 'dps_teams_uninstall_hook');
function dps_teams_uninstall_hook()
{
    require_once(__DIR__ . '/uninstall.php');
}

// Registar ficheiros de idioma
register_language_files(DPS_TEAMS_MODULE, [DPS_TEAMS_MODULE]);

// ─── Menu lateral do painel admin ────────────────────────────────────────────
// Usa o hook admin_init tal como os outros módulos do Perfex
hooks()->add_action('admin_init', 'dps_teams_init_menu_items');
function dps_teams_init_menu_items()
{
    $CI = &get_instance();

    // Só o Super Admin (Ricardo) vê o item "Equipas DPS" no menu lateral
    if (!is_admin()) {
        return;
    }

    // Adicionar item de topo no sidebar (sem filhos, link directo)
    $CI->app_menu->add_sidebar_menu_item('dps_teams', [
        'slug'     => 'dps_teams',
        'name'     => 'Equipas DPS',
        'href'     => admin_url('dps_teams'),
        'icon'     => 'fa fa-users',
        'position' => 15,
    ]);
}

// ─── Hook: registar nota como actividade na lead ──────────────────────────────
// Nota: a lógica principal de notas como actividade está directamente no
// controller Leads.php (add_note) e Misc.php (edit_note).
// Este hook serve como fallback para outros módulos que possam criar notas.
hooks()->add_action('note_created', 'dps_teams_note_as_lead_activity', 10, 2);
function dps_teams_note_as_lead_activity($note_id, $note_data)
{
    // Só processar notas de leads
    if (!isset($note_data['rel_type']) || $note_data['rel_type'] !== 'lead') {
        return;
    }

    $CI      = &get_instance();
    $lead_id = (int)$note_data['rel_id'];

    // Evitar duplicação: o controller já regista a actividade directamente
    // Este hook só actua se o registo vier de outro contexto (ex: API, automações)
    // Verificar se já foi registado nos últimos 5 segundos
    $recent = $CI->db
        ->where('leadid', $lead_id)
        ->where('staffid', get_staff_user_id())
        ->where('date >=', date('Y-m-d H:i:s', time() - 5))
        ->like('description', 'Nota', 'after')
        ->count_all_results(db_prefix() . 'lead_activity_log');

    if ($recent > 0) {
        return; // Já registado pelo controller
    }

    $desc = strip_tags(html_entity_decode($note_data['description'], ENT_QUOTES, 'UTF-8'));
    $desc = mb_substr(trim($desc), 0, 300);
    if (empty($desc)) {
        $desc = 'Nota adicionada';
    }

    $CI->load->model('leads_model');
    $CI->leads_model->log_lead_activity($lead_id, '📝 Nota: ' . $desc);
}

/**
 * A REGRA, num sítio só: que leads é que esta pessoa pode ver.
 *
 *   Admin           -> todas
 *   Gestor de equipa-> as dos seus comerciais + as suas
 *   Comercial       -> só as suas
 *
 * Existe porque a regra estava escrita duas vezes e as duas cópias
 * divergiram: a tabela de leads mostrava só as próprias, mas o contador de
 * cima (get_leads_summary, do núcleo do Perfex) contava também as leads
 * marcadas como públicas. O Miguel Silva via 281 no botão "Novos" e uma lista
 * vazia por baixo — contava-se o que não se podia abrir (03/08/2026).
 *
 * Quem precisar da regra chama isto. Duas cópias voltam a divergir; uma não.
 *
 * @param  string $tabela  prefixo da tabela nas consultas (ex.: 'tblleads')
 * @return string          condição SQL, ou '' quando não há restrição
 */
function dps_teams_where_leads_visiveis($tabela = null)
{
    if (is_admin()) {
        return '';
    }

    $tabela   = $tabela ?: db_prefix() . 'leads';
    $staff_id = (int) get_staff_user_id();

    $CI = &get_instance();
    $CI->load->model('dps_teams/Dps_teams_model', 'dps_teams_model');
    $membro = $CI->dps_teams_model->get_member($staff_id);

    if ($membro && $membro['role'] === 'manager') {
        $ids   = array_column(
            $CI->dps_teams_model->get_team_commercials((int) $membro['team_id']),
            'staff_id'
        );
        $ids[] = $staff_id;
        $lista = implode(',', array_map('intval', array_unique($ids)));

        return '(' . $tabela . '.assigned IN (' . $lista . ') OR '
             . $tabela . '.addedfrom IN (' . $lista . '))';
    }

    return '(' . $tabela . '.assigned = ' . $staff_id . ' OR '
         . $tabela . '.addedfrom = ' . $staff_id . ')';
}
