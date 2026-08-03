<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Quem é que falha 39 ligações à base de dados de 5 em 5 minutos.
 *
 * O registo de erros do CodeIgniter diz o quê e onde rebentou —
 * mysqli_driver.php:203, que é o real_connect — mas não diz QUEM pediu a
 * ligação. Sem isso não há como saber que módulo é, e há mais de vinte a
 * correr no cron.
 *
 * Esta peça acrescenta o rasto da chamada. Não altera comportamento
 * nenhum: apanha a excepção, anota de onde veio, e entrega-a a quem já a
 * tratava antes.
 *
 * Instalado a 01/08/2026 para uma única pergunta. Apagar quando respondida.
 */

if (!function_exists('dps_diagnostico_tempos')) {
    /**
     * Cronómetro por pedido — para saber ONDE o CRM está lento.
     *
     * Sem isto andava-se a adivinhar: a base de dados responde em menos de
     * 10 ms e o opcache tem 99% de acertos, mas a página de entrada leva 1,2 s.
     * O tempo está algures no PHP executado, e só medindo pedido a pedido se
     * descobre qual é a página (e não "o CRM" em geral).
     *
     * Custo: uma linha escrita no fim do pedido. Nada é calculado durante a
     * página. Desligar quando a pergunta estiver respondida.
     */
    function dps_diagnostico_tempos()
    {
        $inicio = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));

        register_shutdown_function(function () use ($inicio) {
            $ficheiro = APPPATH . 'logs/dps-tempos.log';

            // Um registo de diagnóstico não pode ser ele próprio um problema.
            if (@filesize($ficheiro) > 500000) {
                return;
            }

            $ms  = (microtime(true) - $inicio) * 1000;
            $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');

            // Ficheiros estáticos servidos pelo PHP não interessam a ninguém.
            if (preg_match('/\.(css|js|png|jpe?g|gif|svg|woff2?|ico|map)(\?|$)/i', $uri)) {
                return;
            }

            $consultas = '?';
            if (class_exists('CI_Controller', false) && function_exists('get_instance')) {
                $CI = @get_instance();
                if ($CI && isset($CI->db) && is_array(@$CI->db->queries)) {
                    $consultas = count($CI->db->queries);
                }
            }

            @file_put_contents(
                $ficheiro,
                sprintf(
                    "[%s] %6.0f ms | %5.1f MB | %s consultas | %s\n",
                    date('H:i:s'),
                    $ms,
                    memory_get_peak_usage(true) / 1048576,
                    $consultas,
                    substr($uri, 0, 90)
                ),
                FILE_APPEND
            );
        });
    }
}

if (!function_exists('dps_diagnostico_ligacoes_register')) {
    function dps_diagnostico_ligacoes_register()
    {
        dps_diagnostico_tempos();

        /*
         * Quem bate à porta do cron.
         *
         * O rasto mostrou 40 pedidos GET /cron/index no mesmo segundo, de 5
         * em 5 minutos. Não há nada dentro do CRM a chamá-lo, por isso vêm de
         * fora — e o endereço está aberto, responde sem chave nenhuma. Falta
         * saber de onde: o cron do alojamento, um monitor externo, ou coisa
         * pior. O endereço de origem e o agente respondem à pergunta.
         */
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if (stripos($uri, 'cron') !== false && @filesize(APPPATH . 'logs/dps-cron.log') < 200000) {
            @file_put_contents(
                APPPATH . 'logs/dps-cron.log',
                sprintf(
                    "[%s] %s %s | de %s | agente: %s\n",
                    date('Y-m-d H:i:s'),
                    $_SERVER['REQUEST_METHOD'] ?? '?',
                    $uri,
                    $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'),
                    substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? '(sem agente)'), 0, 90)
                ),
                FILE_APPEND
            );
        }

        $ficheiro = APPPATH . 'logs/dps-ligacoes.log';

        /*
         * Trava de segurança do registo de rastos: se isto disparar aos
         * milhares, pára de escrever em vez de encher o disco da conta.
         *
         * Fica DEPOIS do registo do cron de propósito. À primeira estava
         * antes, o ficheiro de rastos passou os 300 KB, e a trava de um
         * calou o outro — o registo do cron nunca chegou a escrever nada.
         */
        if (@filesize($ficheiro) > 300000) {
            return;
        }

        // Descobre quem trata as excepções neste momento (o CodeIgniter) para
        // lhe devolver o controlo depois de anotar.
        $anterior = set_exception_handler(null);
        set_exception_handler(function ($e) use ($anterior, $ficheiro) {
            @file_put_contents(
                $ficheiro,
                sprintf(
                    "[%s] %s: %s\n  pedido: %s %s\n  origem: %s:%s\n%s\n\n",
                    date('Y-m-d H:i:s'),
                    get_class($e),
                    $e->getMessage(),
                    $_SERVER['REQUEST_METHOD'] ?? 'cli',
                    $_SERVER['REQUEST_URI'] ?? ($_SERVER['argv'][1] ?? '(linha de comandos)'),
                    basename((string) $e->getFile()),
                    $e->getLine(),
                    $e->getTraceAsString()
                ),
                FILE_APPEND
            );

            // O comportamento normal continua exactamente igual.
            if (is_callable($anterior)) {
                $anterior($e);
            } else {
                throw $e;
            }
        });
    }
}
