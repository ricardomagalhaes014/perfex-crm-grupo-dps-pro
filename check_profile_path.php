<?php
// Verificar todos os ficheiros profile.php no servidor
$base = __DIR__;
$results = [];

// Verificar o ficheiro principal
$main = $base . '/application/views/admin/staff/profile.php';
if (file_exists($main)) {
    $content = file_get_contents($main);
    $results['main_profile'] = [
        'path' => $main,
        'size' => strlen($content),
        'modified' => date('Y-m-d H:i:s', filemtime($main)),
        'has_landing' => strpos($content, 'landing_profile_form') !== false,
        'lines' => substr_count($content, "\n"),
    ];
}

// Verificar se há módulos/plugins que sobrepõem o profile.php
$search_dirs = [
    $base . '/application/modules',
    $base . '/application/third_party',
    $base . '/modules',
];
foreach ($search_dirs as $dir) {
    if (is_dir($dir)) {
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iter as $file) {
            if ($file->getFilename() === 'profile.php' && strpos($file->getPathname(), 'staff') !== false) {
                $content = file_get_contents($file->getPathname());
                $results['override_' . md5($file->getPathname())] = [
                    'path' => $file->getPathname(),
                    'size' => strlen($content),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                    'has_landing' => strpos($content, 'landing_profile_form') !== false,
                ];
            }
        }
    }
}

// Verificar se há hooks que modificam a view
$hooks_file = $base . '/application/config/hooks.php';
if (file_exists($hooks_file)) {
    $hooks = file_get_contents($hooks_file);
    $results['hooks_has_profile'] = strpos($hooks, 'profile') !== false;
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
