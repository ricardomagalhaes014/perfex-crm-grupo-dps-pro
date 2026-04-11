<?php
// Script de deploy temporário - APAGAR APÓS USO
// Escreve o meta-webhook.php no servidor
$token = $_GET['t'] ?? '';
if ($token !== 'dps2026deploy') {
    http_response_code(403);
    die('Forbidden');
}

$content = base64_decode($_POST['content'] ?? '');
if (empty($content)) {
    die('No content');
}

$target = __DIR__ . '/meta-webhook.php';
$result = file_put_contents($target, $content);

if ($result !== false) {
    // Auto-apagar este script após uso
    @unlink(__FILE__);
    echo json_encode(['success' => true, 'bytes' => $result, 'path' => $target]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao escrever ficheiro']);
}
