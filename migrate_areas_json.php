<?php
/**
 * Script de migração: adiciona colunas areas_*_json à tabela tbldps_imoveis
 * Executar UMA VEZ em: https://crm.grupo-dps.com/migrate_areas_json.php
 * Apagar após execução.
 */

// Carregar configuração do Perfex CRM
define('BASEPATH', dirname(__FILE__) . '/application/');
$db_config_file = dirname(__FILE__) . '/application/config/database.php';

if (!file_exists($db_config_file)) {
    die(json_encode(['error' => 'Ficheiro de configuração não encontrado']));
}

// Ler configuração da BD directamente
$db = [];
include $db_config_file;

$host     = $db['default']['hostname'];
$user     = $db['default']['username'];
$pass     = $db['default']['password'];
$dbname   = $db['default']['database'];
$prefix   = $db['default']['dbprefix'];

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Erro de ligação: ' . $conn->connect_error]));
}
$conn->set_charset('utf8mb4');

$table = $prefix . 'dps_imoveis';
$results = [];

// Colunas a adicionar
$colunas = [
    'areas_quartos_json'    => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de áreas individuais dos quartos'",
    'areas_suites_json'     => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de áreas individuais das suítes'",
    'areas_salas_json'      => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de áreas individuais das salas'",
    'areas_cozinhas_json'   => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de áreas individuais das cozinhas'",
    'areas_casasbanho_json' => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de áreas individuais das casas de banho'",
];

foreach ($colunas as $col => $definition) {
    // Verificar se já existe
    $check = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
    if ($check && $check->num_rows > 0) {
        $results[] = "✓ Coluna `{$col}` já existe — ignorada.";
        continue;
    }
    $sql = "ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$definition}";
    if ($conn->query($sql)) {
        $results[] = "✓ Coluna `{$col}` adicionada com sucesso.";
    } else {
        $results[] = "✗ Erro ao adicionar `{$col}`: " . $conn->error;
    }
}

$conn->close();

header('Content-Type: text/plain; charset=utf-8');
echo "=== Migração DPS Imóveis - Áreas JSON ===\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\nMigração concluída. Apague este ficheiro do servidor.\n";
