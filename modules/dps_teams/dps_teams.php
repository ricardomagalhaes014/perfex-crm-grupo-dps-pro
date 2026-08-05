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

/*
 * A TAREFA SEGUE O DONO DA LEAD.
 *
 * Não pode haver a lead de um lado e a tarefa dessa lead do outro: dois
 * comerciais a trabalhar a mesma pessoa sem saberem um do outro, e nenhum
 * deles a ver o trabalho já feito. Manda a lead — quem a tem, leva a tarefa.
 * Regra do dono (05/08/2026).
 *
 * Dois momentos, porque o desalinhamento pode nascer de qualquer um dos lados:
 *   - tarefa criada numa lead   -> nasce já com o dono da lead;
 *   - lead muda de dono         -> as tarefas abertas dela vão atrás.
 */
hooks()->add_action('after_add_task', 'dps_teams_tarefa_segue_lead');
hooks()->add_action('after_lead_updated', 'dps_teams_tarefas_seguem_a_lead');

if (!function_exists('dps_teams_dono_da_lead')) {
    function dps_teams_dono_da_lead($lead_id)
    {
        $CI = &get_instance();

        $lead = $CI->db->select('assigned')->where('id', (int) $lead_id)
            ->get(db_prefix() . 'leads')->row();

        return $lead ? (int) $lead->assigned : 0;
    }
}

if (!function_exists('dps_teams_atribuir_tarefa')) {
    /**
     * Põe a tarefa nas mãos de quem tem a lead, e só nas dele.
     *
     * Substitui os atribuídos em vez de acrescentar: uma tarefa com dois donos
     * é uma tarefa sem dono nenhum, e foi por aí que isto se desalinhou.
     *
     * @return bool se mudou alguma coisa
     */
    function dps_teams_atribuir_tarefa($task_id, $staff_id)
    {
        $CI = &get_instance();

        $task_id  = (int) $task_id;
        $staff_id = (int) $staff_id;

        if ($task_id <= 0 || $staff_id <= 0) {
            return false;
        }

        $actuais = $CI->db->select('staffid')->where('taskid', $task_id)
            ->get(db_prefix() . 'task_assigned')->result_array();

        $ids = array_map('intval', array_column($actuais, 'staffid'));

        if ($ids === [$staff_id]) {
            return false;   // já está como deve ser
        }

        $CI->db->where('taskid', $task_id)->delete(db_prefix() . 'task_assigned');
        $CI->db->insert(db_prefix() . 'task_assigned', [
            'taskid'     => $task_id,
            'staffid'    => $staff_id,
            'assigned_from' => $staff_id,
        ]);

        return true;
    }
}

if (!function_exists('dps_teams_tarefa_segue_lead')) {
    function dps_teams_tarefa_segue_lead($task_id)
    {
        $CI = &get_instance();

        $t = $CI->db->select('rel_type, rel_id')->where('id', (int) $task_id)
            ->get(db_prefix() . 'tasks')->row();

        if (!$t || $t->rel_type !== 'lead' || (int) $t->rel_id <= 0) {
            return;
        }

        $dono = dps_teams_dono_da_lead($t->rel_id);

        if ($dono > 0) {
            dps_teams_atribuir_tarefa($task_id, $dono);
        }
    }
}

if (!function_exists('dps_teams_tarefas_seguem_a_lead')) {
    function dps_teams_tarefas_seguem_a_lead($lead_id)
    {
        $CI = &get_instance();

        $dono = dps_teams_dono_da_lead($lead_id);

        if ($dono <= 0) {
            return;
        }

        /*
         * Só as tarefas por fechar. Uma tarefa concluída é história de quem a
         * fez — reescrever-lhe o dono apagava o registo de quem trabalhou.
         */
        $tarefas = $CI->db->select('id')
            ->where('rel_type', 'lead')
            ->where('rel_id', (int) $lead_id)
            ->where('status !=', 5)
            ->get(db_prefix() . 'tasks')->result_array();

        foreach ($tarefas as $t) {
            dps_teams_atribuir_tarefa($t['id'], $dono);
        }
    }
}
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
