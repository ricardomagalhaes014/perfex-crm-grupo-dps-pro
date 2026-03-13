<?php
// --------- runner web p/ disparar a sincronização ---------
// PROTEÇÃO: use uma chave simples na URL (substitua por algo forte).
const RUN_KEY = 'dLEQMcmfLHUOYXyM8CaR3i4jO4zZ3ybQ';

// Exige ?key=... para executar
if (!isset($_GET['key']) || $_GET['key'] !== RUN_KEY) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok'=>false,'error'=>'forbidden']);
    exit;
}

// Caminho para o sync real
$syncFile = __DIR__ . '/sync_perfex.php';
if (!file_exists($syncFile)) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'sync_perfex.php não encontrado']);
    exit;
}

// Inclui o arquivo e chama a função principal, se existir.
// Ajuste o nome da função caso tenha outro.
require_once $syncFile;

if (function_exists('dps_sync_perfex')) {
    $result = dps_sync_perfex(); // retorne array/logs do sync
} else {
    // fallback: se o seu arquivo executa direto, só inclui-lo já roda
    $result = ['ok'=>true, 'note'=>'sync_perfex.php incluído; verifique logs'];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result ?: ['ok'=>true]);