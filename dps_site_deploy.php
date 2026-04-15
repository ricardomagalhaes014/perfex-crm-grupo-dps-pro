<?php
// Script de deploy do bundle do site dpsimobiliario.pt
// Verifica se tem acesso ao filesystem do site e faz upload
$token = $_GET['t'] ?? '';
if ($token !== 'dps2026deploy') {
    http_response_code(403);
    die('Forbidden');
}

$action = $_GET['a'] ?? 'check';

if ($action === 'check') {
    // Verificar caminhos possíveis para o site
    $paths = [
        '/home/u172337921/domains/dpsimobiliario.pt/public_html/assets/',
        '/home/u172337921/public_html/assets/',
        '/var/www/dpsimobiliario.pt/assets/',
        '/home/u172337921/dpsimobiliario.pt/public_html/assets/',
    ];
    $results = [];
    foreach ($paths as $path) {
        $results[$path] = [
            'exists' => file_exists($path),
            'writable' => is_writable($path),
        ];
    }
    // Também verificar o próprio directório
    $results['__DIR__'] = __DIR__;
    $results['__FILE__'] = __FILE__;
    echo json_encode($results, JSON_PRETTY_PRINT);
} elseif ($action === 'deploy') {
    // Receber o bundle e escrever no servidor
    $content = base64_decode($_POST['content'] ?? '');
    $filename = $_POST['filename'] ?? 'index-DPS-v4.js';
    
    if (empty($content)) {
        die(json_encode(['success' => false, 'error' => 'No content']));
    }
    
    // Tentar diferentes caminhos
    $paths = [
        '/home/u172337921/domains/dpsimobiliario.pt/public_html/assets/' . $filename,
        '/home/u172337921/public_html/assets/' . $filename,
    ];
    
    foreach ($paths as $target) {
        $dir = dirname($target);
        if (is_dir($dir) && is_writable($dir)) {
            $result = file_put_contents($target, $content);
            if ($result !== false) {
                echo json_encode(['success' => true, 'bytes' => $result, 'path' => $target]);
                exit;
            }
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'No writable path found', 'tried' => $paths]);
} elseif ($action === 'update_index') {
    // Actualizar o index.html para apontar para o novo bundle
    $filename = $_POST['filename'] ?? 'index-DPS-v4.js';
    $paths = [
        '/home/u172337921/domains/dpsimobiliario.pt/public_html/index.html',
        '/home/u172337921/public_html/index.html',
    ];
    
    foreach ($paths as $target) {
        if (file_exists($target)) {
            $content = file_get_contents($target);
            // Substituir o bundle actual pelo novo
            $new_content = preg_replace('/index-DPS-[^"]+\.js/', $filename, $content);
            $result = file_put_contents($target, $new_content);
            if ($result !== false) {
                echo json_encode(['success' => true, 'bytes' => $result, 'path' => $target]);
                exit;
            }
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'index.html not found']);
} elseif ($action === 'write_file') {
    $content = base64_decode($_POST['content'] ?? '');
    $path = $_POST['path'] ?? '';
    if (empty($content) || empty($path)) die(json_encode(['success' => false, 'error' => 'Missing params']));
    if (strpos($path, '/home/u172337921/') !== 0) die(json_encode(['success' => false, 'error' => 'Invalid path']));
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $result = file_put_contents($path, $content);
    echo json_encode(['success' => $result !== false, 'bytes' => $result, 'path' => $path]);
} elseif ($action === 'read_file') {
    $path = $_POST['path'] ?? '';
    if (empty($path)) die(json_encode(['success' => false, 'error' => 'Missing path']));
    if (strpos($path, '/home/u172337921/') !== 0) die(json_encode(['success' => false, 'error' => 'Invalid path']));
    if (!file_exists($path)) die(json_encode(['success' => false, 'error' => 'File not found: ' . $path]));
    $content = file_get_contents($path);
    echo json_encode(['success' => true, 'content' => base64_encode($content), 'size' => strlen($content)]);
} elseif ($action === 'list_dir') {
    $path = $_POST['path'] ?? '/home/u172337921/domains/';
    if (strpos($path, '/home/u172337921/') !== 0) die(json_encode(['success' => false, 'error' => 'Invalid path']));
    $files = is_dir($path) ? scandir($path) : [];
    echo json_encode(['success' => true, 'files' => $files, 'path' => $path]);
} elseif ($action === 'exec_sql') {
    $host = $_POST['host'] ?? '127.0.0.1';
    $db = $_POST['db'] ?? '';
    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';
    $sql = $_POST['sql'] ?? '';
    if (empty($sql) || empty($db)) die(json_encode(['success' => false, 'error' => 'Missing params']));
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) die(json_encode(['success' => false, 'error' => $conn->connect_error]));
    $conn->set_charset('utf8mb4');
    $r = $conn->query($sql);
    if ($r === false) die(json_encode(['success' => false, 'error' => $conn->error]));
    if ($r === true) { echo json_encode(['success' => true, 'affected' => $conn->affected_rows]); exit; }
    $rows = [];
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode(['success' => true, 'rows' => $rows, 'count' => count($rows)]);
} elseif ($action === 'check') {
    // Also list domains
    $domains_dir = '/home/u172337921/domains/';
    $domains = is_dir($domains_dir) ? scandir($domains_dir) : [];
    $paths = [
        '/home/u172337921/domains/dpsimobiliario.pt/public_html/assets/',
        '/home/u172337921/public_html/assets/',
    ];
    $results = ['domains' => $domains, '__DIR__' => __DIR__];
    foreach ($paths as $p) {
        $results[$p] = ['exists' => file_exists($p), 'writable' => is_writable($p)];
    }
    echo json_encode($results, JSON_PRETTY_PRINT);
}
