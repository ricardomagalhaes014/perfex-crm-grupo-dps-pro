<?php
$file = __DIR__ . '/modules/si_custom_theme/views/si_custom_theme_staff_view.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    header('Content-Type: text/plain');
    echo "SIZE: " . strlen($content) . "\n";
    echo "HAS_LANDING: " . (strpos($content, 'landing') !== false ? 'YES' : 'NO') . "\n";
    echo "HAS_EDIT_PROFILE: " . (strpos($content, 'edit_profile') !== false ? 'YES' : 'NO') . "\n";
    echo "FIRST 500:\n" . substr($content, 0, 500);
} else {
    echo "NOT FOUND";
}
