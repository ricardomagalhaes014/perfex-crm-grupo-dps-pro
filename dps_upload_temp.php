<?php
// Script temporário de upload - APAGAR APÓS USO
$token = $_GET['t'] ?? '';
if ($token !== 'dps2026upload') {
    http_response_code(403);
    die('Forbidden');
}
$filename = $_POST['filename'] ?? '';
$content = base64_decode($_POST['content'] ?? '');
if (empty($filename) || empty($content)) {
    die(json_encode(['success' => false, 'error' => 'Missing params']));
}
// Apenas permite ficheiros PHP no directório raiz
$filename = basename($filename);
if (!preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $filename)) {
    die(json_encode(['success' => false, 'error' => 'Invalid filename']));
}
$target = __DIR__ . '/' . $filename;
$result = file_put_contents($target, $content);
if ($result !== false) {
    echo json_encode(['success' => true, 'bytes' => $result, 'path' => $target]);
} else {
    echo json_encode(['success' => false, 'error' => 'Write failed', 'path' => $target]);
}
