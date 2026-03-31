<?php
/**
 * Script de migração: Adicionar colunas de Landing Page à tblstaff
 * Executar uma vez: https://crm.grupo-dps.com/migrate_landing_columns.php
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

$columns = [
    'landing_slug'     => "VARCHAR(100) NULL DEFAULT NULL COMMENT 'Slug personalizado para landing page'",
    'landing_whatsapp' => "VARCHAR(30) NULL DEFAULT NULL COMMENT 'Numero WhatsApp para landing page'",
    'landing_foto'     => "VARCHAR(255) NULL DEFAULT NULL COMMENT 'Foto personalizada para landing page'",
];

foreach ($columns as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM tblstaff LIKE '$col'");
    if ($check->num_rows === 0) {
        $sql = "ALTER TABLE tblstaff ADD COLUMN $col $def";
        if ($conn->query($sql)) {
            echo "✅ Coluna '$col' adicionada com sucesso.<br>";
        } else {
            echo "❌ Erro ao adicionar '$col': " . $conn->error . "<br>";
        }
    } else {
        echo "ℹ️ Coluna '$col' já existe.<br>";
    }
}

$conn->close();
echo "<br><strong>Migração concluída.</strong>";
