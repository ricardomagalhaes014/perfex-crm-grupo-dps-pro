<?php
/**
 * ARRANJO ÚNICO — leads ALOCADAS HOJE passam a "criadas hoje".
 *
 * Renova a dateadded (e limpa o lastcontact) de todas as leads cuja
 * atribuição (dateassigned) aconteceu hoje e que têm comercial. Executa,
 * responde com o total, e apaga-se a si próprio.
 *
 * Uso: GET ?t=dps2026fixdatas[&dry=1]  (dry=1 só conta, não altera)
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

if (($_GET['t'] ?? '') !== 'dps2026fixdatas') {
    http_response_code(403);
    exit(json_encode(['error' => 'forbidden']));
}

$config = __DIR__ . '/application/config/app-config.php';
if (!is_readable($config)) {
    http_response_code(500);
    exit(json_encode(['error' => 'config indisponivel']));
}
$conteudo = (string) file_get_contents($config);
$ler = static function (string $c) use ($conteudo): string {
    return preg_match("/define\(\s*'" . $c . "'\s*,\s*'([^']*)'\s*\)/", $conteudo, $m) ? $m[1] : '';
};
$bd = @new mysqli($ler('APP_DB_HOSTNAME') ?: 'localhost', $ler('APP_DB_USERNAME'), $ler('APP_DB_PASSWORD'), $ler('APP_DB_NAME'));
if ($bd->connect_error) {
    http_response_code(500);
    exit(json_encode(['error' => 'bd indisponivel']));
}
$bd->set_charset('utf8mb4');
$prefixo = $ler('APP_DB_PREFIX') ?: 'tbl';

$onde = "assigned IS NOT NULL AND assigned != 0
         AND dateassigned IS NOT NULL AND DATE(dateassigned) = CURDATE()
         AND DATE(dateadded) < CURDATE()";

$res   = $bd->query("SELECT COUNT(*) c FROM {$prefixo}leads WHERE {$onde}");
$antes = (int) ($res ? $res->fetch_assoc()['c'] : 0);

if (isset($_GET['dry'])) {
    exit(json_encode(['dry' => true, 'leads_a_atualizar' => $antes]));
}

$bd->query("UPDATE {$prefixo}leads SET dateadded = NOW(), lastcontact = NULL WHERE {$onde}");
$feitas = $bd->affected_rows;

@unlink(__FILE__); // arranjo único: apaga-se depois de correr

echo json_encode(['ok' => true, 'leads_atualizadas' => $feitas, 'encontradas' => $antes, 'quando' => date('c')]);
