<?php defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_customer_statuses')) {
    /**
     * Retorna a lista de estados permitidos.
     * Altere aqui se precisar de novos rótulos.
     */
    function get_customer_statuses(): array
    {
        return [
            'Novo',
            'Em contacto',
            'Em reunião',
            'Proposta enviada',
            'Negociação',
            'Ganho',
            'Perdido',
        ];
    }
}