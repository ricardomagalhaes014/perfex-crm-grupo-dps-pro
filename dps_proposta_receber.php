<?php
/**
 * Recebe o pedido de "Enviar Proposta ao Cliente" vindo do simulador.
 *
 * ESTADO ACTUAL: modo de diagnóstico. Devolve exactamente o que recebeu
 * (método, cabeçalhos relevantes, GET, POST, corpo bruto) sem tocar em
 * base de dados nem enviar nada. Serve para descobrir os nomes de campo
 * e o token reais que o simulador já envia, para depois estes serem
 * usados na versão definitiva (que vai processar e enviar por WhatsApp).
 *
 * Não é sensível expor isto temporariamente: não lê nem escreve dados,
 * só ecoa o pedido recebido.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://dpsimobiliario.pt');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$corpo_bruto = file_get_contents('php://input');

echo json_encode([
    'diagnostico'   => true,
    'metodo'        => $_SERVER['REQUEST_METHOD'] ?? null,
    'content_type'  => $_SERVER['CONTENT_TYPE'] ?? null,
    'get'           => $_GET,
    'post'          => $_POST,
    'ficheiros'     => array_keys($_FILES ?? []),
    'corpo_bruto'   => $corpo_bruto,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
