<?php
/**
 * Script de migração: adiciona colunas areas_*_json à tabela tbldps_imoveis
 * Executar UMA VEZ em: https://crm.grupo-dps.com/migrate_areas_json.php
 * Apagar após execução.
 */

// Ler configuração da BD do Perfex CRM
$db_config_file = __DIR__ . '/application/config/database.php';

if (!file_exists($db_config_file)) {
    die("ERRO: Ficheiro de configuração não encontrado em: " . $db_config_file);
}

// Extrair credenciais via regex (evita conflito com constantes do CI)
$config_content = file_get_contents($db_config_file);

preg_match("/'hostname'\s*=>\s*'([^']+)'/", $config_content, $m_host);
preg_match("/'username'\s*=>\s*'([^']+)'/", $config_content, $m_user);
preg_match("/'password'\s*=>\s*'([^']+)'/", $config_content, $m_pass);
preg_match("/'database'\s*=>\s*'([^']+)'/", $config_content, $m_db);
preg_match("/'dbprefix'\s*=>\s*'([^']+)'/", $config_content, $m_prefix);

$host   = isset($m_host[1])   ? $m_host[1]   : 'localhost';
$user   = isset($m_user[1])   ? $m_user[1]   : '';
$pass   = isset($m_pass[1])   ? $m_pass[1]   : '';
$dbname = isset($m_db[1])     ? $m_db[1]     : '';
$prefix = isset($m_prefix[1]) ? $m_prefix[1] : 'tbl';

if (empty($user) || empty($dbname)) {
    die("ERRO: Não foi possível ler as credenciais da BD. user='$user' db='$dbname'");
}

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("ERRO de ligação: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$table = $prefix . 'dps_imoveis';
$results = [];

// Verificar se a tabela existe
$check_table = $conn->query("SHOW TABLES LIKE '{$table}'");
if (!$check_table || $check_table->num_rows === 0) {
    die("ERRO: Tabela '{$table}' não existe na base de dados '{$dbname}'.");
}

// Colunas a adicionar
$colunas = [
    'areas_quartos_json'    => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de areas individuais dos quartos'",
    'areas_suites_json'     => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de areas individuais das suites'",
    'areas_salas_json'      => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de areas individuais das salas'",
    'areas_cozinhas_json'   => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de areas individuais das cozinhas'",
    'areas_casasbanho_json' => "TEXT NULL DEFAULT NULL COMMENT 'JSON array de areas individuais das casas de banho'",
];

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
        $results[] = "ERRO ao adicionar `{$col}`: " . $conn->error;
    }
}

$conn->close();

header('Content-Type: text/plain; charset=utf-8');
echo "=== Migracao DPS Imoveis - Areas JSON ===\n";
echo "Tabela: {$table}\n";
echo "Base de dados: {$dbname}\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\nMigracao concluida. Apague este ficheiro do servidor.\n";
