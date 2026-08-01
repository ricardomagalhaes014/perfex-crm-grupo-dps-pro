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

if (!function_exists('dps_diagnostico_ligacoes_register')) {
    function dps_diagnostico_ligacoes_register()
    {
        $ficheiro = APPPATH . 'logs/dps-ligacoes.log';

        /*
         * Trava de segurança: se por algum motivo isto disparar aos
         * milhares, pára de escrever em vez de encher o disco da conta.
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
