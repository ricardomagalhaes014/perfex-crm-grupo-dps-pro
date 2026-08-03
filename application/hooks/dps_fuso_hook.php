<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Põe a base de dados na mesma hora que o CRM.
 *
 * O PROBLEMA (medido a 03/08/2026, às 20:01 de Lisboa):
 *
 *     gravado pelo PHP  ->  2026-08-03 20:01:37
 *     gravado pela BD   ->  2026-08-03 19:01:37
 *
 * O servidor corre em UTC. O `index.php` do Perfex chama
 * date_default_timezone_set('Europe/Lisbon'), por isso tudo o que o PHP grava
 * fica com a hora certa — mas a base de dados nunca foi informada e continua
 * uma hora atrás. Resultado: duas horas diferentes para a mesma coisa,
 * conforme o caminho por onde a data foi escrita.
 *
 * Onde isso se nota:
 *   - as colunas que a própria base de dados preenche (DEFAULT CURRENT_TIMESTAMP)
 *     ficam uma hora adiantadas em relação à realidade;
 *   - NOW() e CURDATE() ficam atrasados, e é com eles que se decide se um
 *     lembrete já venceu ou se uma tarefa é "de hoje" — um lembrete das 9h
 *     só disparava às 10h.
 *
 * A CORREÇÃO: dizer à ligação qual é o fuso, a cada pedido.
 *
 * O desvio é calculado a partir do fuso que o PHP está mesmo a usar, e não
 * fixado em '+01:00': Lisboa muda de hora duas vezes por ano e um valor à mão
 * ficaria errado a partir do último domingo de Outubro.
 *
 * PORQUE É SEGURO: só as colunas TIMESTAMP são convertidas pelo fuso da
 * ligação, e nesta base de dados há 22 — todas em módulos de terceiros (wiki,
 * dashboard, appointly). As 320 colunas DATETIME, onde vivem leads, tarefas,
 * vendas e propostas, guardam o valor tal e qual e não mexem.
 */

if (!function_exists('dps_fuso_alinhar_bd')) {
    function dps_fuso_alinhar_bd()
    {
        $CI = &get_instance();

        // Em pedidos que não usam base de dados não há nada a fazer.
        if (!isset($CI->db) || !is_object($CI->db)) {
            return;
        }

        try {
            $fuso = date_default_timezone_get() ?: 'UTC';

            // '+01:00' no verão, '+00:00' no inverno — lido do próprio PHP.
            $desvio = (new DateTime('now', new DateTimeZone($fuso)))->format('P');

            $CI->db->query('SET time_zone = ' . $CI->db->escape($desvio));
        } catch (Throwable $e) {
            /*
             * Se falhar, fica tudo como estava — uma hora trocada é um
             * incómodo, mas rebentar o CRM inteiro por causa disso seria
             * bem pior. Deixa-se rasto para não ficar mudo.
             */
            log_message('error', 'dps_fuso: não consegui alinhar o fuso da base de dados: ' . $e->getMessage());
        }
    }
}
