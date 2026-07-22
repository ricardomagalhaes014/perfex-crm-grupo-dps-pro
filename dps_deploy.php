<?php
// DPS Auto-Deploy - busca ficheiros do GitHub e aplica no servidor
// Uso: https://crm.grupo-dps.com/dps_deploy.php?token=dps2026deploy[&module=X][&file=X][&branch=X]
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

$base   = __DIR__;
$branch = isset($_GET['branch']) ? preg_replace('/[^a-zA-Z0-9\-_\/.]/', '', $_GET['branch']) : 'main';
$raw    = 'https://raw.githubusercontent.com/ricardomagalhaes014/perfex-crm-grupo-dps-pro/' . $branch;
$results = [];

// Grupos de módulos pré-definidos
$module_groups = [
    'dps_sofia_calls' => [
        'modules/dps_sofia_calls/controllers/Dps_sofia_calls.php',
        'modules/dps_sofia_calls/models/Dps_sofia_calls_model.php',
        'modules/dps_sofia_calls/views/sofia_calls/index.php',
        'modules/dps_sofia_calls/config/csrf_exclude_uris.php',
        'modules/dps_sofia_calls/migrations/100_version_100.php',
    ],
    'dps_credito' => [
        'modules/dps_credito/dps_credito.php',
        'modules/dps_credito/install.php',
        'modules/dps_credito/controllers/Dps_credito.php',
        'modules/dps_credito/models/Dps_credito_model.php',
        'modules/dps_credito/views/view.php',
        'modules/dps_credito/migrations/100_version_100.php',
    ],
    'migrations' => [
        'modules/dps_imoveis/migrations/100_version_100.php',
        'modules/dps_imoveis/migrations/110_version_110.php',
        'modules/dps_interacoes/migrations/100_version_100.php',
        'modules/dps_interacoes/migrations/110_version_110.php',
        'modules/dps_chatbot/migrations/100_version_100.php',
        'modules/dps_meetings/migrations/100_version_100.php',
        'modules/dps_sofia_calls/migrations/100_version_100.php',
        'modules/dps_teams/migrations/100_version_100.php',
        'modules/dps_voip/migrations/100_version_100.php',
        'modules/dps_webmail/migrations/100_version_100.php',
        'modules/dps_whatsapp/migrations/100_version_100.php',
        'modules/dps_credito/migrations/100_version_100.php',
        'modules/dps_vendas/migrations/100_version_100.php',
        'modules/dps_vendas/migrations/110_version_110.php',
    ],
];

$module_param = isset($_GET['module']) ? trim($_GET['module']) : '';
$file_param   = isset($_GET['file'])   ? trim($_GET['file'])   : '';

if ($file_param) {
    $files = [$file_param];
} elseif ($module_param && isset($module_groups[$module_param])) {
    $files = $module_groups[$module_param];
} elseif ($module_param === 'all') {
    $files = array_merge(...array_values($module_groups));
} else {
    $files = [
        'modules/dps_sofia_calls/controllers/Dps_sofia_calls.php',
        'modules/dps_sofia_calls/models/Dps_sofia_calls_model.php',
        'modules/dps_sofia_calls/views/sofia_calls/index.php',
        'modules/dps_sofia_calls/config/csrf_exclude_uris.php',
        'application/controllers/admin/Leads.php',
    ];
}

foreach ($files as $rel) {
    $url     = $raw . '/' . $rel;
    $content = @file_get_contents($url);
    if ($content === false) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $content = curl_exec($ch);
        $http    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!$content || $http !== 200) {
            $results[$rel] = 'ERRO ao buscar do GitHub (HTTP ' . $http . ')';
            continue;
        }
    }
    $dest = $base . '/' . $rel;
    $dir  = dirname($dest);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $ok = file_put_contents($dest, $content);
    $results[$rel] = $ok !== false ? 'OK (' . strlen($content) . ' bytes)' : 'ERRO ao escrever';
}

if (function_exists('opcache_reset')) opcache_reset();

header('Content-Type: application/json');
echo json_encode([
    'status'  => 'deployed',
    'branch'  => $branch,
    'source'  => $raw,
    'files'   => $results,
    'time'    => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);
