<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Cor da etiqueta de cada estado do circuito de venda.
 * Vai aquecendo à medida que a venda avança, para se ler a lista de relance.
 */
function dps_vendas_cor_estado($estado)
{
    $cores = [
        'pedido'    => 'label-danger',
        'reservado' => 'label-warning',
        'submetido' => 'label-primary',
        'vendido'   => 'label-info',
        'concluido' => 'label-success',
        'cancelado' => 'label-danger',
    ];

    return $cores[$estado] ?? 'label-default';
}

function dps_vendas_nome_estado($estado)
{
    if (empty($estado)) {
        return 'Reservado';
    }

    $nomes = [
        'pedido'    => 'Pedido (por confirmar)',
        'reservado' => 'Reservado',
        'submetido' => 'Submetido',
        'vendido'   => 'CPCV',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
    ];

    return $nomes[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}

function dps_vendas_nome_doc($tipo)
{
    $nomes = [
        'cc_frente'    => 'Cartão de Cidadão (frente)',
        'cc_verso'     => 'Cartão de Cidadão (verso)',
        'cpcv'         => 'CPCV',
        'comprovativo' => 'Comprovativo de pagamento',
        'outro'        => 'Outro documento',
    ];

    return $nomes[$tipo] ?? $tipo;
}

/**
 * Regimes civis usados nas escrituras. Lista fechada para não haver
 * dez maneiras de escrever a mesma coisa no histórico.
 */
function dps_vendas_regimes_civis()
{
    return [
        'Solteiro(a)',
        'Casado(a) - Comunhão de adquiridos',
        'Casado(a) - Comunhão geral de bens',
        'Casado(a) - Separação de bens',
        'União de facto',
        'Divorciado(a)',
        'Viúvo(a)',
    ];
}
