<?php
/**
 * Script de migração: adiciona colunas areas_*_json à tabela tbldps_imoveis
 * Executar UMA VEZ em: https://crm.grupo-dps.com/migrate_areas_json.php
 * Apagar após execução.
 */

$host   = 'localhost';
$user   = 'u172337921_crmgrupopds';
$pass   = '3AF5_ZCiqQ7:=At';
$dbname = 'u172337921_crmgrupopds';
$prefix = 'tbl';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("ERRO de ligação: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$table = $prefix . 'dps_imoveis';

// Verificar se a tabela existe
$check_table = $conn->query("SHOW TABLES LIKE '{$table}'");
if (!$check_table || $check_table->num_rows === 0) {
    die("ERRO: Tabela '{$table}' não existe.");
}

$colunas = [
    'areas_quartos_json'    => "TEXT NULL DEFAULT NULL",
    'areas_suites_json'     => "TEXT NULL DEFAULT NULL",
    'areas_salas_json'      => "TEXT NULL DEFAULT NULL",
    'areas_cozinhas_json'   => "TEXT NULL DEFAULT NULL",
    'areas_casasbanho_json' => "TEXT NULL DEFAULT NULL",
];

$results = [];
foreach ($colunas as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    if ($check && $check->num_rows > 0) {
        $results[] = "OK (ja existe): `{$col}`";
        continue;
    }
    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}";
    if ($conn->query($sql)) {
        $results[] = "ADICIONADA: `{$col}`";
    } else {
        $results[] = "ERRO: `{$col}` - " . $conn->error;
    }
}

$conn->close();

header('Content-Type: text/plain; charset=utf-8');
echo "=== Migracao DPS Imoveis - Areas JSON ===\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\nConcluido. Apague este ficheiro.\n";
