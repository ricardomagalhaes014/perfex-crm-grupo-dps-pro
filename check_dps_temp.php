<?php
$conn = new mysqli('localhost', 'u172337921_crmgrupopds', '3AF5_ZCiqQ7:=At', 'u172337921_crmgrupopds');
if ($conn->connect_error) {
    echo "ERRO BD: " . $conn->connect_error . "\n";
    exit(1);
}
$conn->set_charset('utf8mb4');

// Verificar se a tabela existe
$result = $conn->query("SHOW TABLES LIKE 'tbldps_imoveis'");
if ($result->num_rows > 0) {
    echo "TABELA tbldps_imoveis: EXISTE\n";
} else {
    echo "TABELA tbldps_imoveis: NAO EXISTE\n";
    
    // Criar a tabela
    $sql = "CREATE TABLE IF NOT EXISTS `tbldps_imoveis` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `titulo` VARCHAR(255) NOT NULL,
        `tipo` ENUM('Apartamento','Moradia','Loja','Terreno','Escritório','Armazém','Outro') NOT NULL DEFAULT 'Apartamento',
        `tipologia` VARCHAR(10) NULL DEFAULT NULL,
        `distrito` VARCHAR(100) NULL DEFAULT NULL,
        `cidade` VARCHAR(100) NULL DEFAULT NULL,
        `morada` VARCHAR(255) NULL DEFAULT NULL,
        `preco` DECIMAL(15,2) NULL DEFAULT NULL,
        `area_total` DECIMAL(10,2) NULL DEFAULT NULL,
        `nr_quartos` INT(3) NULL DEFAULT 0,
        `area_quartos` DECIMAL(10,2) NULL DEFAULT NULL,
        `nr_suites` INT(3) NULL DEFAULT 0,
        `area_suites` DECIMAL(10,2) NULL DEFAULT NULL,
        `nr_salas` INT(3) NULL DEFAULT 0,
        `area_salas` DECIMAL(10,2) NULL DEFAULT NULL,
        `nr_casas_banho` INT(3) NULL DEFAULT 0,
        `area_casas_banho` DECIMAL(10,2) NULL DEFAULT NULL,
        `area_cozinha` DECIMAL(10,2) NULL DEFAULT NULL,
        `garagem` TINYINT(1) NULL DEFAULT 0,
        `lugar_garagem` TINYINT(1) NULL DEFAULT 0,
        `ano_construcao` INT(4) NULL DEFAULT NULL,
        `equipamento` TEXT NULL DEFAULT NULL,
        `texto_livre` TEXT NULL DEFAULT NULL,
        `foto_principal` VARCHAR(255) NULL DEFAULT NULL,
        `fotos` TEXT NULL DEFAULT NULL,
        `doc_cmi` VARCHAR(255) NULL DEFAULT NULL,
        `doc_cc_proprietarios` VARCHAR(255) NULL DEFAULT NULL,
        `doc_caderneta_predial` VARCHAR(255) NULL DEFAULT NULL,
        `nome_proprietarios` VARCHAR(255) NULL DEFAULT NULL,
        `contacto_proprietario` VARCHAR(50) NULL DEFAULT NULL,
        `mail_proprietario` VARCHAR(191) NULL DEFAULT NULL,
        `agente_id` INT(11) NULL DEFAULT NULL,
        `status` ENUM('pendente','aprovado','rejeitado','arquivado') NOT NULL DEFAULT 'pendente',
        `published_website` TINYINT(1) NOT NULL DEFAULT 0,
        `approver_id` INT(11) NULL DEFAULT NULL,
        `date_approval` DATETIME NULL DEFAULT NULL,
        `notas_aprovacao` TEXT NULL DEFAULT NULL,
        `datecreated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `datemodified` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        `created_by` INT(11) NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "TABELA CRIADA COM SUCESSO\n";
    } else {
        echo "ERRO AO CRIAR TABELA: " . $conn->error . "\n";
    }
}

// Verificar módulo activo
$result2 = $conn->query("SELECT * FROM tblmodules WHERE module_name = 'dps_imoveis'");
if ($result2 && $result2->num_rows > 0) {
    $mod = $result2->fetch_assoc();
    echo "MÓDULO dps_imoveis: " . ($mod['active'] ? 'ACTIVO' : 'INACTIVO') . "\n";
} else {
    echo "MÓDULO dps_imoveis: NÃO ENCONTRADO NA BD\n";
}

$conn->close();
