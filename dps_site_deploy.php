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
}
