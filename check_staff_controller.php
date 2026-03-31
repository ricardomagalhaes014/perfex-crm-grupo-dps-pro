<?php
$file = __DIR__ . '/application/controllers/admin/Staff.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    echo json_encode([
        'has_save_landing' => strpos($content, 'save_landing_profile') !== false,
        'has_landing_slug' => strpos($content, 'landing_slug') !== false,
        'modified' => date('Y-m-d H:i:s', filemtime($file)),
        'size' => strlen($content),
        'lines' => substr_count($content, "\n"),
    ]);
} else {
    echo json_encode(['error' => 'file not found']);
}
