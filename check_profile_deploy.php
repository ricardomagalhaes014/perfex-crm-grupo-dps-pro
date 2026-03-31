<?php
$file = __DIR__ . '/application/views/admin/staff/profile.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $has_landing = strpos($content, 'landing_profile_form') !== false;
    $has_slug = strpos($content, 'landing_slug') !== false;
    $modified = date('Y-m-d H:i:s', filemtime($file));
    echo json_encode([
        'has_landing_form' => $has_landing,
        'has_landing_slug' => $has_slug,
        'modified' => $modified,
        'size' => strlen($content)
    ]);
} else {
    echo json_encode(['error' => 'file not found']);
}
