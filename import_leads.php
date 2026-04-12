<?php
/**
 * Script de importação de leads da Meta para o Perfex CRM
 * Importa as leads do CSV de hoje directamente na base de dados
 * 
 * ATENÇÃO: Remover este ficheiro após uso por segurança!
 */

// Chave de segurança para evitar acesso não autorizado
$secret = isset($_GET['key']) ? $_GET['key'] : '';
if ($secret !== 'dps-import-2026') {
    die('Acesso negado');
}

// Ler credenciais directamente do ficheiro de configuração do Perfex CRM
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';

$config_files = [
    __DIR__ . '/application/config/app.php',
    __DIR__ . '/application/config/database.php',
];
foreach ($config_files as $cf) {
    if (file_exists($cf)) {
        $content = file_get_contents($cf);
        if (preg_match("/['\"]hostname['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $m)) $db_host = $m[1];
        if (preg_match("/['\"]database['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $m)) $db_name = $m[1];
        if (preg_match("/['\"]username['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $m)) $db_user = $m[1];
        if (preg_match("/['\"]password['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $m)) $db_pass = $m[1];
        if (!empty($db_name)) break;
    }
}
if (empty($db_name)) {
    die('ERRO: Não foi possível ler as credenciais da BD do ficheiro de configuração.');
}

// Leads do CSV para importar
$leads = [
    ['name' => 'Manuel Vaz', 'email' => 'vaz.variz@sapo.pt', 'phone' => '+351938357645', 'created' => '2026-04-11 13:18:00'],
    ['name' => 'Vítor Fernandes', 'email' => 'vitorf12m@gmail.com', 'phone' => '+351912608591', 'created' => '2026-04-11 10:38:00'],
    ['name' => 'Dyene Oliveira', 'email' => 'dyene22martins@gmail.com', 'phone' => '+351912260598', 'created' => '2026-04-11 09:40:00'],
    ['name' => 'Domingos Jorge Rocha Guimaraes', 'email' => 'djrgcc@gmail.com', 'phone' => '+351966938257', 'created' => '2026-04-11 09:10:00'],
    ['name' => 'Paula Matos', 'email' => 'ppiresmatos@hotmail.com', 'phone' => '+351915805792', 'created' => '2026-04-11 08:10:00'],
    ['name' => 'Sandra Maria Gabriel', 'email' => 'sandragg09@gmail.com', 'phone' => '+351912295226', 'created' => '2026-04-11 08:07:00'],
    ['name' => 'Olavo Chaves Brandão', 'email' => 'olavo@uai.com.br', 'phone' => '+5531986463810', 'created' => '2026-04-11 07:42:00'],
    ['name' => 'Laura Pedreira', 'email' => 'laura.lopes87@my.com.com', 'phone' => '+351916055168', 'created' => '2026-04-11 06:17:00'],
    ['name' => 'Carlos Andrade', 'email' => 'carlosandradenovo@gmail.com', 'phone' => '+5516994625813', 'created' => '2026-04-11 06:07:00'],
    ['name' => 'Vânia Marlene Santos', 'email' => 'vaniasantos2001@hotmail.com', 'phone' => '+35100000000', 'created' => '2026-04-11 03:23:00'],
    ['name' => 'Ana Paula Batista', 'email' => 'apaula11@live.com.pt', 'phone' => '+351932593416', 'created' => '2026-04-11 03:04:00'],
    ['name' => 'Angelo Monteiro', 'email' => 'imoanalise@gmail.com', 'phone' => '+351912210937', 'created' => '2026-04-11 01:52:00'],
    ['name' => 'Lopes Elsa', 'email' => 'elsamplopes@gmail.com', 'phone' => '939326686', 'created' => '2026-04-11 01:13:00'],
    ['name' => 'Fatima Nogueira', 'email' => 'fatymcnogueira9@icloud.com', 'phone' => '917134699', 'created' => '2026-04-11 01:01:00'],
    ['name' => 'Carla Silva', 'email' => 'carla07marques@gmail.com', 'phone' => '+351918694937', 'created' => '2026-04-11 00:12:00'],
    ['name' => 'Pedro Furet', 'email' => 'furet.pedro@hotmail.com', 'phone' => '+351910852434', 'created' => '2026-04-10 21:13:00'],
];

// Conectar à base de dados
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erro de ligação à BD: ' . $e->getMessage());
}

// Obter IDs necessários
// Status "Novos"
$stmt = $pdo->query("SELECT id FROM tblleadsstatus WHERE name = 'Novos' LIMIT 1");
$status_row = $stmt->fetch(PDO::FETCH_ASSOC);
$status_id = $status_row ? $status_row['id'] : 1;

// Source "Facebook" ou "Meta"
$stmt = $pdo->query("SELECT id FROM tblleadssources WHERE name LIKE '%Facebook%' OR name LIKE '%Meta%' LIMIT 1");
$source_row = $stmt->fetch(PDO::FETCH_ASSOC);
$source_id = $source_row ? $source_row['id'] : null;

echo "<h2>Importação de Leads Meta → Perfex CRM</h2>";
echo "<p>Status ID: $status_id | Source ID: " . ($source_id ?? 'N/A') . "</p>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>#</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Resultado</th></tr>";

$success = 0;
$errors = 0;
$skipped = 0;

foreach ($leads as $i => $lead) {
    // Verificar se já existe (por email ou telefone)
    $phone_clean = preg_replace('/[^0-9+]/', '', $lead['phone']);
    
    $stmt = $pdo->prepare("SELECT id FROM tblleads WHERE email = ? OR phonenumber = ? LIMIT 1");
    $stmt->execute([$lead['email'], $phone_clean]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "<tr><td>" . ($i+1) . "</td><td>" . htmlspecialchars($lead['name']) . "</td><td>" . htmlspecialchars($lead['email']) . "</td><td>$phone_clean</td><td style='color:orange'>⚠ Já existe (ID: {$existing['id']})</td></tr>";
        $skipped++;
        continue;
    }
    
    // Separar nome e apelido
    $name_parts = explode(' ', $lead['name'], 2);
    $firstname = $name_parts[0];
    $lastname = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Inserir lead
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tblleads 
            (status, source, assigned, firstname, lastname, email, phonenumber, description, dateadded, lastcontact, addedfrom, is_public, date_converted) 
            VALUES 
            (?, ?, 1, ?, ?, ?, ?, ?, ?, NULL, 1, 0, NULL)
        ");
        
        $description = "Lead importada do Meta Lead Ads (Facebook/Instagram)\nFormulário: FORMULARIO QUALIFICADO PARA PORTUGAL RICARDO\nData original: {$lead['created']}";
        
        $stmt->execute([
            $status_id,
            $source_id,
            $firstname,
            $lastname,
            $lead['email'],
            $phone_clean,
            $description,
            $lead['created']
        ]);
        
        $lead_id = $pdo->lastInsertId();
        echo "<tr><td>" . ($i+1) . "</td><td>" . htmlspecialchars($lead['name']) . "</td><td>" . htmlspecialchars($lead['email']) . "</td><td>$phone_clean</td><td style='color:green'>✓ Criada (ID: $lead_id)</td></tr>";
        $success++;
        
    } catch (PDOException $e) {
        echo "<tr><td>" . ($i+1) . "</td><td>" . htmlspecialchars($lead['name']) . "</td><td>" . htmlspecialchars($lead['email']) . "</td><td>$phone_clean</td><td style='color:red'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        $errors++;
    }
}

echo "</table>";
echo "<h3>Resultado: $success criadas | $skipped já existiam | $errors erros</h3>";

// Remover este ficheiro após execução por segurança
// unlink(__FILE__);
echo "<p style='color:red'><strong>IMPORTANTE: Remova este ficheiro do servidor após verificar os resultados!</strong></p>";
?>
