<?php
// Procurar qual ficheiro tem o layout col-md-7 do profile
$base = __DIR__;
$results = [];

// Procurar em todos os módulos
$dirs_to_search = [
    $base . '/modules',
    $base . '/application/views',
];

foreach ($dirs_to_search as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') continue;
        $content = file_get_contents($file->getPathname());
        // Procurar o layout específico col-md-7 com edit_profile ou staff_profile_table
        if (strpos($content, 'col-md-7') !== false && 
            (strpos($content, 'staff_profile_table') !== false || strpos($content, 'edit_profile') !== false || strpos($content, 'change_password_profile') !== false)) {
            $results[] = [
                'path' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                'has_landing' => strpos($content, 'landing') !== false,
                'has_col_md_7' => true,
            ];
        }
    }
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT);
