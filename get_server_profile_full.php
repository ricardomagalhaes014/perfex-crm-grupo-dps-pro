<?php
// Obter o conteúdo completo do profile.php real do servidor
$file = __DIR__ . '/application/views/admin/staff/profile.php';
if (file_exists($file)) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-File-Size: ' . filesize($file));
    header('X-File-Modified: ' . date('Y-m-d H:i:s', filemtime($file)));
    echo file_get_contents($file);
} else {
    echo 'NOT FOUND: ' . $file;
}
