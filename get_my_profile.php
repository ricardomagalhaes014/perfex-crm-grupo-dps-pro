<?php
$file = __DIR__ . '/application/views/admin/staff/my_profile.php';
if (file_exists($file)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo file_get_contents($file);
} else {
    echo 'NOT FOUND';
}
