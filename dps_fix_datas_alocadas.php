<?php
/**
 * ARRANJO ÚNICO — leads em "Novos" com comercial e data antiga passam a
 * "criadas agora" (não "há 2–3 semanas"). Também preenche dateassigned
 * quando está vazio. Apaga-se depois de fixar.
 *
 * Uso: ?t=dps2026fixdatas&modo=relatorio | &modo=fixar
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
$p = $ler('APP_DB_PREFIX') ?: 'tbl';

$onde = "l.assigned IS NOT NULL AND l.assigned != 0
         AND DATE(l.dateadded) < CURDATE()
         AND LOWER(TRIM(s.name)) IN ('novos','novo','nova','novas')";
$join = "FROM {$p}leads l JOIN {$p}leads_status s ON s.id = l.status";

$modo = $_GET['modo'] ?? 'relatorio';

if ($modo === 'fixar') {
    $bd->query("UPDATE {$p}leads l JOIN {$p}leads_status s ON s.id = l.status
                SET l.dateadded = NOW(), l.lastcontact = NULL,
                    l.dateassigned = COALESCE(l.dateassigned, NOW())
                WHERE {$onde}");
    $feitas = $bd->affected_rows;
    @unlink(__FILE__);
    exit(json_encode(['ok' => true, 'modo' => 'fixar', 'leads_atualizadas' => $feitas, 'quando' => date('c')]));
}

$tot = (int) $bd->query("SELECT COUNT(*) c {$join} WHERE {$onde}")->fetch_assoc()['c'];
$porIdade = [];
$r = $bd->query("SELECT DATEDIFF(CURDATE(), DATE(l.dateadded)) dias, COUNT(*) c {$join} WHERE {$onde} GROUP BY dias ORDER BY dias");
while ($l = $r->fetch_assoc()) { $porIdade[$l['dias'] . ' dias'] = (int) $l['c']; }
$porFonte = [];
$r = $bd->query("SELECT COALESCE(f.name,'?') fonte, COUNT(*) c {$join} LEFT JOIN {$p}leads_sources f ON f.id = l.source WHERE {$onde} GROUP BY f.name ORDER BY c DESC");
while ($l = $r->fetch_assoc()) { $porFonte[$l['fonte']] = (int) $l['c']; }

echo json_encode(['ok' => true, 'modo' => 'relatorio', 'total_para_fixar' => $tot, 'por_idade' => $porIdade, 'por_fonte' => $porFonte], JSON_UNESCAPED_UNICODE);
