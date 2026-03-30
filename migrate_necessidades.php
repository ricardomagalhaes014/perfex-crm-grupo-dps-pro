<?php
// Script de migração — cria a tabela dps_necessidades
// Executar UMA VEZ em: https://crm.grupo-dps.com/migrate_necessidades.php
// Apagar após execução!

$host = 'localhost';
$user = 'u172337921_crm';
$pass = 'Dps2026#';
$db   = 'u172337921_crm';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Erro de ligação: ' . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS `tblDps_necessidades` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `staff_id`         INT(11) NOT NULL,
  `nome_cliente`     VARCHAR(255) NOT NULL DEFAULT '',
  `contacto_cliente` VARCHAR(100) DEFAULT '',
  `email_cliente`    VARCHAR(255) DEFAULT '',
  `tipo`             VARCHAR(50) DEFAULT '',
  `tipologia`        VARCHAR(20) DEFAULT '',
  `distrito`         VARCHAR(100) DEFAULT '',
  `cidade`           VARCHAR(100) DEFAULT '',
  `preco_min`        DECIMAL(12,2) DEFAULT NULL,
  `preco_max`        DECIMAL(12,2) DEFAULT NULL,
  `area_min`         DECIMAL(8,2) DEFAULT NULL,
  `nr_quartos_min`   TINYINT(2) DEFAULT NULL,
  `garagem`          TINYINT(1) DEFAULT NULL,
  `urgencia`         ENUM('baixa','normal','alta','urgente') DEFAULT 'normal',
  `observacoes`      TEXT DEFAULT NULL,
  `data_criacao`     DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`),
  KEY `idx_data_criacao` (`data_criacao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo '<p style="color:green;font-family:monospace;">✅ Tabela tblDps_necessidades criada com sucesso!</p>';
} else {
    echo '<p style="color:red;font-family:monospace;">❌ Erro: ' . $conn->error . '</p>';
}

$conn->close();
echo '<p style="font-family:monospace;">Apague este ficheiro do servidor após execução.</p>';
