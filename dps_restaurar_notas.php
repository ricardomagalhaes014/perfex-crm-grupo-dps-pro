<?php
/**
 * RESTAURO DE NOTAS DE LEADS a partir de um backup da base de dados.
 *
 * Como usar:
 *   1. hPanel → Backups → Bases de dados → descarregar um backup ANTERIOR
 *      ao apagão (ex.: de ontem).
 *   2. Enviar o ficheiro (.sql ou .sql.gz) para o docroot do CRM pelo
 *      Gestor de Ficheiros, com o nome  notas_backup.sql  ou  notas_backup.sql.gz
 *   3. Abrir  /dps_restaurar_notas.php?t=dps2026notas          → relatório (dry-run)
 *      Abrir  /dps_restaurar_notas.php?t=dps2026notas&aplicar=1 → repõe
 *
 * Só INSERE notas de leads que já não existam (mesmo id) — não altera nem
 * apaga nada. No fim, apagar este ficheiro e o backup do docroot.
 */
declare(strict_types=1);
ini_set('memory_limit', '512M');
set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['t'] ?? '') !== 'dps2026notas') { http_response_code(403); exit('forbidden'); }

$config = __DIR__ . '/application/config/app-config.php';
$c = (string) @file_get_contents($config);
$ler = static function (string $k) use ($c): string {
    return preg_match("/define\(\s*'{$k}'\s*,\s*'([^']*)'\s*\)/", $c, $m) ? $m[1] : '';
};
$bd = @new mysqli($ler('APP_DB_HOSTNAME') ?: 'localhost', $ler('APP_DB_USERNAME'), $ler('APP_DB_PASSWORD'), $ler('APP_DB_NAME'));
if ($bd->connect_error) { exit("ERRO: base de dados indisponível\n"); }
$bd->set_charset('utf8mb4');
$p = $ler('APP_DB_PREFIX') ?: 'tbl';

$fich = null;
foreach ([__DIR__ . '/notas_backup.sql', __DIR__ . '/notas_backup.sql.gz'] as $f) {
    if (is_readable($f)) { $fich = $f; break; }
}
if ($fich === null) {
    exit("Falta o backup: envia o ficheiro para o docroot com o nome notas_backup.sql (ou .sql.gz).\n");
}

echo "Backup: {$fich} (" . filesize($fich) . " bytes)\n";
$sql = substr($fich, -3) === '.gz'
    ? (string) gzdecode((string) file_get_contents($fich))
    : (string) file_get_contents($fich);
echo "Descomprimido: " . strlen($sql) . " bytes\n";

// apanhar os INSERTs da tabela de notas
if (!preg_match_all('/INSERT INTO `?' . $p . 'notes`?[^;]*?VALUES\s*(.+?);\s*\n/si', $sql, $mm)) {
    exit("Não encontrei INSERTs de {$p}notes no backup.\n");
}
unset($sql);

// parser simples de tuplos SQL: (v1,v2,...),(...)
function tuplos(string $vals): array
{
    $out = []; $n = strlen($vals); $i = 0;
    while ($i < $n) {
        while ($i < $n && $vals[$i] !== '(') { $i++; }
        if ($i >= $n) { break; }
        $i++; $campo = ''; $linha = []; $emStr = false;
        while ($i < $n) {
            $ch = $vals[$i];
            if ($emStr) {
                if ($ch === '\\\\') { $campo .= $vals[$i + 1] ?? ''; $i += 2; continue; }
                if ($ch === "'") { if (($vals[$i + 1] ?? '') === "'") { $campo .= "'"; $i += 2; continue; } $emStr = false; $i++; continue; }
                $campo .= $ch; $i++; continue;
            }
            if ($ch === "'") { $emStr = true; $i++; continue; }
            if ($ch === ',') { $linha[] = $campo; $campo = ''; $i++; continue; }
            if ($ch === ')') { $linha[] = $campo; $i++; break; }
            $campo .= $ch; $i++;
        }
        $out[] = $linha;
    }
    return $out;
}

// colunas reais da tabela actual
$cols = [];
$r = $bd->query("SHOW COLUMNS FROM {$p}notes");
while ($l = $r->fetch_assoc()) { $cols[] = $l['Field']; }
echo "Colunas de {$p}notes: " . implode(',', $cols) . "\n";
$idx_id  = array_search('id', $cols, true);
$idx_rt  = array_search('rel_type', $cols, true);

$existentes = [];
$r = $bd->query("SELECT id FROM {$p}notes");
while ($l = $r->fetch_row()) { $existentes[(int) $l[0]] = true; }
echo "Notas existentes agora: " . count($existentes) . "\n";

$aplicar = isset($_GET['aplicar']);
$repostas = 0; $vistas = 0;
foreach ($mm[1] as $bloco) {
    foreach (tuplos($bloco) as $t) {
        if (count($t) !== count($cols)) { continue; }
        $vistas++;
        if (strtolower(trim((string) $t[$idx_rt])) !== 'lead') { continue; }
        $id = (int) $t[$idx_id];
        if (isset($existentes[$id])) { continue; }
        $repostas++;
        if ($aplicar) {
            $vals = array_map(function ($v) use ($bd) {
                return strtoupper(trim((string) $v)) === 'NULL' ? 'NULL' : "'" . $bd->real_escape_string((string) $v) . "'";
            }, $t);
            $bd->query("INSERT INTO {$p}notes (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ")");
        }
    }
}
echo "Notas no backup: {$vistas}\n";
echo ($aplicar ? "REPOSTAS: {$repostas}\n" : "A REPOR (dry-run, nada alterado): {$repostas}\nAcrescenta &aplicar=1 para repor.\n");
