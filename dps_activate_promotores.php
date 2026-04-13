<?php
/**
 * DPS - Activar módulo Promotores directamente na BD
 * Aceder via: https://crm.grupo-dps.com/dps_activate_promotores.php
 * APAGAR após usar.
 */
header('Content-Type: text/html; charset=utf-8');

define('DB_HOST', 'localhost');
define('DB_USER', 'u172337921_crmgrupopds');
define('DB_PASS', '3AF5_ZCiqQ7:=At');
define('DB_NAME', 'u172337921_crmgrupopds');

$base = __DIR__;

echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} .ok{color:green;font-weight:bold;} .err{color:red;font-weight:bold;} .warn{color:orange;font-weight:bold;}</style>";
echo "<h2>DPS - Activar módulo Promotores</h2>";

// ─── 1. Verificar ficheiros do módulo ────────────────────────────────────────
echo "<h3>1. Verificar ficheiros</h3>";
$mod_dir = $base . '/modules/promotores/';
$main_file = $mod_dir . 'promotores.php';

if (!is_dir($mod_dir)) {
    echo "<p class='err'>❌ Pasta modules/promotores/ NÃO existe no servidor!</p>";
    echo "<p>Conteúdo de modules/: <pre>";
    $dirs = scandir($base . '/modules/');
    foreach ($dirs as $d) {
        if ($d !== '.' && $d !== '..') echo $d . "\n";
    }
    echo "</pre></p>";
} else {
    echo "<p class='ok'>✅ Pasta modules/promotores/ existe</p>";
    if (file_exists($main_file)) {
        echo "<p class='ok'>✅ promotores.php existe (" . filesize($main_file) . " bytes)</p>";
        $content = file_get_contents($main_file);
        if (strpos($content, 'Module Name:') !== false) {
            echo "<p class='ok'>✅ Cabeçalho 'Module Name:' encontrado</p>";
        } else {
            echo "<p class='err'>❌ Cabeçalho 'Module Name:' em falta!</p>";
        }
    } else {
        echo "<p class='err'>❌ promotores.php NÃO existe!</p>";
        echo "<p>Ficheiros em modules/promotores/: <pre>";
        $files = scandir($mod_dir);
        foreach ($files as $f) echo $f . "\n";
        echo "</pre></p>";
    }
}

// ─── 2. BD ───────────────────────────────────────────────────────────────────
echo "<h3>2. Base de dados</h3>";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo "<p class='err'>❌ Erro de ligação BD: " . $conn->connect_error . "</p>";
    exit;
}
$conn->set_charset('utf8mb4');
echo "<p class='ok'>✅ Ligação BD OK</p>";

// Verificar tblmodules
$r = $conn->query("SHOW TABLES LIKE 'tblmodules'");
if (!$r || $r->num_rows === 0) {
    echo "<p class='err'>❌ Tabela tblmodules não existe!</p>";
    exit;
}
echo "<p class='ok'>✅ Tabela tblmodules existe</p>";

// Ver módulos actuais
echo "<h4>Módulos na BD:</h4><table border='1' cellpadding='4' style='border-collapse:collapse'>";
echo "<tr><th>ID</th><th>module_name</th><th>version</th><th>active</th></tr>";
$r = $conn->query("SELECT id, module_name, installed_version, active FROM tblmodules ORDER BY module_name");
$promotores_exists = false;
while ($row = $r->fetch_assoc()) {
    $color = $row['active'] ? 'green' : '#999';
    if ($row['module_name'] === 'promotores') $promotores_exists = true;
    echo "<tr><td>{$row['id']}</td><td style='color:{$color}'>{$row['module_name']}</td><td>{$row['installed_version']}</td><td>{$row['active']}</td></tr>";
}
echo "</table>";

// ─── 3. Registar/activar módulo ──────────────────────────────────────────────
echo "<h3>3. Registar e activar módulo</h3>";

if ($promotores_exists) {
    $conn->query("UPDATE tblmodules SET active = 1, installed_version = '1.1.0' WHERE module_name = 'promotores'");
    echo "<p class='ok'>✅ Módulo actualizado para active=1</p>";
} else {
    $conn->query("INSERT INTO tblmodules (module_name, installed_version, active) VALUES ('promotores', '1.1.0', 1)");
    $id = $conn->insert_id;
    echo "<p class='ok'>✅ Módulo inserido na BD (id={$id})</p>";
}

// ─── 4. Criar tabela tblpromotores ───────────────────────────────────────────
echo "<h3>4. Tabela tblpromotores</h3>";
$r = $conn->query("SHOW TABLES LIKE 'tblpromotores'");
if ($r && $r->num_rows > 0) {
    $count = $conn->query("SELECT COUNT(*) as c FROM tblpromotores")->fetch_assoc()['c'];
    echo "<p class='warn'>⚠️ Tabela já existe ({$count} registos)</p>";
} else {
    $sql = "CREATE TABLE `tblpromotores` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(191) NOT NULL,
        `company` VARCHAR(191) NULL,
        `phase` VARCHAR(100) NOT NULL DEFAULT 'Não contactado',
        `phone` VARCHAR(100) NULL,
        `email` VARCHAR(191) NULL,
        `website` VARCHAR(191) NULL,
        `address` TEXT NULL,
        `city` VARCHAR(100) NULL,
        `state` VARCHAR(100) NULL,
        `zip` VARCHAR(30) NULL,
        `notes` LONGTEXT NULL,
        `assigned` INT NULL,
        `dateadded` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `lastcontact` DATETIME NULL,
        `addedfrom` INT NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `phase` (`phase`(20)),
        KEY `state` (`state`(50))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "<p class='ok'>✅ Tabela tblpromotores criada</p>";
    } else {
        echo "<p class='err'>❌ Erro ao criar tabela: " . $conn->error . "</p>";
    }
}

// ─── 5. Verificar resultado final ────────────────────────────────────────────
echo "<h3>5. Resultado final</h3>";
$r = $conn->query("SELECT id, module_name, installed_version, active FROM tblmodules WHERE module_name = 'promotores'");
$row = $r->fetch_assoc();
if ($row && $row['active'] == 1) {
    echo "<p class='ok'>✅ Módulo 'promotores' está ACTIVO na BD (id={$row['id']})</p>";
    echo "<p>Agora vá a <strong>Admin → Módulos</strong> — o módulo deve aparecer como activo.</p>";
    echo "<p>Se não aparecer, limpe a cache do Perfex: <a href='/admin/misc/clear_system_cache'>Admin → Misc → Clear Cache</a></p>";
} else {
    echo "<p class='err'>❌ Módulo não está activo!</p>";
}

$conn->close();
echo "<hr><p><b>Apague este ficheiro após usar.</b></p>";
