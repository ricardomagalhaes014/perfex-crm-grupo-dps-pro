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

// Adicionar menu ao painel admin
hooks()->add_action('admin_init', 'dps_teams_init_menu_items');
function dps_teams_init_menu_items()
{
    // Só o super admin (Ricardo) vê o menu de gestão de equipas
    if (!is_admin()) {
        return;
    }
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('dps-teams', [
        'name'     => 'Equipas DPS',
        'href'     => admin_url('dps_teams'),
        'icon'     => 'fa fa-users',
        'position' => 15,
    ]);
}

// Hook: filtrar leads por equipa/comercial ao listar
hooks()->add_filter('leads_table_where', 'dps_teams_filter_leads_by_team');
function dps_teams_filter_leads_by_team($where)
{
    // Super admin vê tudo
    if (is_admin()) {
        return $where;
    }

    $CI        = &get_instance();
    $staff_id  = get_staff_user_id();

    // Carregar o modelo se necessário
    if (!isset($CI->dps_teams_model)) {
        $CI->load->model('dps_teams/Dps_teams_model', 'dps_teams_model');
    }

    $member = $CI->dps_teams_model->get_member($staff_id);

    if (!$member) {
        // Sem equipa definida: só vê as suas leads
        if (is_array($where)) {
            $where[] = '(assigned = ' . (int)$staff_id . ' OR addedfrom = ' . (int)$staff_id . ')';
        } else {
            $where = '(assigned = ' . (int)$staff_id . ' OR addedfrom = ' . (int)$staff_id . ')';
        }
        return $where;
    }

    $role = $member['role']; // 'manager' ou 'commercial'

    if ($role === 'manager') {
        // Gestor vê todas as leads da sua equipa (os seus comerciais + as suas próprias)
        $team_id      = (int)$member['team_id'];
        $commercials  = $CI->dps_teams_model->get_team_commercials($team_id);
        $ids          = array_column($commercials, 'staff_id');
        $ids[]        = $staff_id;
        $ids_str      = implode(',', array_map('intval', $ids));
        $cond         = '(assigned IN (' . $ids_str . ') OR addedfrom IN (' . $ids_str . '))';
        if (is_array($where)) {
            $where[] = $cond;
        } else {
            $where = $cond;
        }
    } else {
        // Comercial só vê as suas próprias leads
        $cond = '(assigned = ' . (int)$staff_id . ' OR addedfrom = ' . (int)$staff_id . ')';
        if (is_array($where)) {
            $where[] = $cond;
        } else {
            $where = $cond;
        }
    }

    return $where;
}

// Hook: registar nota como actividade na lead
hooks()->add_action('note_created', 'dps_teams_note_as_lead_activity', 10, 2);
function dps_teams_note_as_lead_activity($note_id, $note_data)
{
    if (!isset($note_data['rel_type']) || $note_data['rel_type'] !== 'lead') {
        return;
    }
    $CI      = &get_instance();
    $lead_id = (int)$note_data['rel_id'];
    $desc    = strip_tags($note_data['description']);
    $desc    = html_entity_decode($desc, ENT_QUOTES, 'UTF-8');
    $desc    = mb_substr(trim($desc), 0, 300);
    if (empty($desc)) {
        $desc = 'Nota adicionada';
    }
    $CI->load->model('leads_model');
    $CI->leads_model->log_lead_activity($lead_id, 'Nota: ' . $desc);
}
