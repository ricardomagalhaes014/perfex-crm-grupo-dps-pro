<?php
// Verificar módulos activos e hooks relacionados com o perfil
$modules_dir = __DIR__ . '/modules/';
$results = ['modules' => [], 'hooks_files' => []];

if (is_dir($modules_dir)) {
    $modules = scandir($modules_dir);
    foreach ($modules as $mod) {
        if ($mod === '.' || $mod === '..') continue;
        $mod_path = $modules_dir . $mod;
        if (is_dir($mod_path)) {
            // Verificar se tem ficheiros relacionados com profile/staff
            $profile_files = glob($mod_path . '/**/*profile*') ?: [];
            $staff_files = glob($mod_path . '/**/*staff*') ?: [];
            $init_file = $mod_path . '/' . $mod . '.php';
            
            $has_profile_hook = false;
            if (file_exists($init_file)) {
                $content = file_get_contents($init_file);
                $has_profile_hook = strpos($content, 'edit_logged_in_staff_profile') !== false 
                    || strpos($content, 'profile') !== false;
            }
            
            if (!empty($profile_files) || !empty($staff_files) || $has_profile_hook) {
                $results['modules'][$mod] = [
                    'profile_files' => $profile_files,
                    'staff_files' => $staff_files,
                    'has_profile_hook' => $has_profile_hook,
                ];
            }
        }
    }
}

// Verificar hooks.php
$hooks = __DIR__ . '/application/config/hooks.php';
if (file_exists($hooks)) {
    $results['hooks_content'] = file_get_contents($hooks);
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
