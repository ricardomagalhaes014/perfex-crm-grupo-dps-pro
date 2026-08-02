<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps2026deploy') { http_response_code(403); die('Forbidden'); }
$log = __DIR__ . '/aura-lead-debug.log';
if (!file_exists($log)) { echo json_encode(['error' => 'Log nao encontrado', 'path' => $log]); exit; }
$lines = file($log);
$last = array_slice($lines, -50);
header('Content-Type: text/plain');
echo implode('', $last);
