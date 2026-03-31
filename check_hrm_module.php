<?php
// Verificar o módulo HRM
$hrm_init = __DIR__ . '/modules/hrm/hrm.php';
$hrm_profile = __DIR__ . '/modules/hrm/views/profile.php';
$hr_profile = __DIR__ . '/modules/modules/hr_profile/hr_profile.php';

$results = [];

if (file_exists($hrm_init)) {
    $content = file_get_contents($hrm_init);
    // Procurar hooks relacionados com edit_profile
    preg_match_all('/hooks\(\)->add_action\([^)]+\)/', $content, $matches);
    $results['hrm_hooks'] = $matches[0];
    $results['hrm_init_has_profile'] = strpos($content, 'edit_profile') !== false || strpos($content, 'profile') !== false;
    $results['hrm_init_size'] = strlen($content);
}

if (file_exists($hrm_profile)) {
    $content = file_get_contents($hrm_profile);
    $results['hrm_profile_size'] = strlen($content);
    $results['hrm_profile_modified'] = date('Y-m-d H:i:s', filemtime($hrm_profile));
    $results['hrm_profile_has_landing'] = strpos($content, 'landing') !== false;
    // Primeiras 50 linhas
    $lines = explode("\n", $content);
    $results['hrm_profile_first_20'] = implode("\n", array_slice($lines, 0, 20));
}

if (file_exists($hr_profile)) {
    $content = file_get_contents($hr_profile);
    $results['hr_profile_size'] = strlen($content);
    $results['hr_profile_has_edit_profile'] = strpos($content, 'edit_profile') !== false;
    $results['hr_profile_has_profile_hook'] = strpos($content, 'edit_logged_in_staff_profile') !== false;
    // Mostrar hooks
    preg_match_all('/hooks\(\)->add_action\([^)]+\)/', $content, $matches);
    $results['hr_profile_hooks'] = $matches[0];
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
