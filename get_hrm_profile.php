<?php
// Ver o conteúdo completo do profile.php do módulo HRM
$file = __DIR__ . '/modules/hrm/views/profile.php';
if (file_exists($file)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo file_get_contents($file);
} else {
    echo 'File not found: ' . $file;
}
