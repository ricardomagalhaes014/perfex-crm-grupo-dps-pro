<?php
// Ver o conteúdo real do profile.php no servidor
$file = __DIR__ . '/application/views/admin/staff/profile.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    // Procurar por two_factor_auth_via_whatsapp
    $has_whatsapp_2fa = strpos($content, 'two_factor_auth_via_whatsapp') !== false;
    $has_landing = strpos($content, 'landing_profile_form') !== false;
    $lines = explode("\n", $content);
    $total_lines = count($lines);
    
    echo json_encode([
        'has_whatsapp_2fa' => $has_whatsapp_2fa,
        'has_landing' => $has_landing,
        'total_lines' => $total_lines,
        'size' => strlen($content),
        'modified' => date('Y-m-d H:i:s', filemtime($file)),
        // Mostrar as últimas 30 linhas para ver o que está no fim
        'last_30_lines' => implode("\n", array_slice($lines, -30)),
        // Mostrar linhas 140-160 para ver onde está a secção Landing
        'lines_140_160' => implode("\n", array_slice($lines, 139, 21)),
    ]);
} else {
    echo json_encode(['error' => 'file not found']);
}
