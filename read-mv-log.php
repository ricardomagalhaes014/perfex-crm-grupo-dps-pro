<?php
define('BASEPATH', __DIR__ . '/');
$token = isset($_GET['token']) ? $_GET['token'] : '';
if ($token !== 'dps-log-2026') { http_response_code(403); die('Forbidden'); }
$log_file = __DIR__ . '/mv-lead-debug.log';
if (!file_exists($log_file)) { die('Log file not found: ' . $log_file); }
$lines = isset($_GET['lines']) ? intval($_GET['lines']) : 50;
$content = file($log_file);
$last = array_slice($content, -$lines);
header('Content-Type: text/plain');
echo implode('', $last);
