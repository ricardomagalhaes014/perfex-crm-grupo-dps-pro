<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Quem criou uma tarefa deixa de ser avisado só por a ter criado.
 *
 * O Perfex avisa quatro grupos quando alguém mexe numa tarefa: quem está
 * atribuído, quem a segue, quem já comentou — e QUEM A CRIOU. Esse último é
 * razoável quando as tarefas se criam uma a uma; deixa de ser quando as cria
 * um robô em nome de uma pessoa.
 *
 * A Sofia cria as tarefas das chamadas em nome do staff #1. A 31/07/2026
 * eram 1062 num dia. Cada mudança de estado ou comentário de qualquer
 * comercial mandava um email e uma notificação ao #1 — centenas por dia sobre
 * trabalho que não é dele. Uma caixa de correio assim deixa de ser lida, e
 * com ela deixam de ser lidos os avisos que interessam.
 *
 * O que muda: para as pessoas listadas em DPS_SEM_AVISO_DE_CRIADOR, ser autor
 * da tarefa deixa de contar. Continuam a ser avisadas de tudo em que estejam
 * mesmo envolvidas — atribuídas, a seguir, ou já tendo comentado.
 *
 * Para voltar a receber, tirar o número da lista. Para aplicar a outra
 * pessoa, acrescentar o dela.
 */

/** Staff que não quer avisos das tarefas que apenas criou. */
define('DPS_SEM_AVISO_DE_CRIADOR', [1]);   // #1 Ricardo Magalhães

function dps_task_notificacoes_register()
{
    hooks()->add_filter('should_staff_receive_task_notification', 'dps_task_notificacoes_filtrar', 10, 2);
}

/**
 * @param bool  $receber  o que o Perfex decidiu
 * @param array $dados    ['staff_id' => int, 'task_id' => int]
 *
 * @return bool
 */
function dps_task_notificacoes_filtrar($receber, $dados = [])
{
    if (!$receber) {
        return $receber;                       // já era não; não se inverte
    }

    $staff_id = (int) ($dados['staff_id'] ?? 0);
    $task_id  = (int) ($dados['task_id'] ?? 0);

    if (!$staff_id || !$task_id || !in_array($staff_id, DPS_SEM_AVISO_DE_CRIADOR, true)) {
        return $receber;
    }

    $CI = &get_instance();
    $CI->load->model('tasks_model');

    /*
     * Envolvimento a sério: atribuído, a seguir, ou já comentou. Qualquer um
     * destes mantém o aviso — o que se corta é APENAS o "sou o autor".
     */
    if ($CI->tasks_model->is_task_assignee($staff_id, $task_id)
        || $CI->tasks_model->is_task_follower($staff_id, $task_id)
        || $CI->tasks_model->staff_has_commented_on_task($staff_id, $task_id)) {
        return true;
    }

    return false;
}
