<?php

/**
 * Bootstrap do Perfex/CodeIgniter
 * Compatível com PHP 8.1+
 */

/* -------------------------------------------------------------
 | TIMEZONE
 * ------------------------------------------------------------- */
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Lisbon');
}

/* -------------------------------------------------------------
 | AMBIENTE (production | testing | development)
 | - Usa APP_ENV do ambiente se existir; caso contrário, production.
 * ------------------------------------------------------------- */
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');

/* -------------------------------------------------------------
 | ERROS E LOGS conforme o ambiente
 * ------------------------------------------------------------- */
switch (ENVIRONMENT) {
    case 'development':
        // Mostrar tudo na tela e logar tudo
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        break;

    case 'testing':
    case 'production':
    default:
        // Em produção, não mostrar erros na tela
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        // Reportar tudo, exceto notices/strict/deprecated de usuário
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }
        break;
}

/* -------------------------------------------------------------
 | PATHS
 * ------------------------------------------------------------- */
$system_path      = 'system';
$application_path = 'application';
$view_folder      = ''; // deixe vazio para usar application/views

// Consolida caminho relativo em absoluto
if (defined('STDIN')) {
    chdir(dirname(__FILE__));
}

if (($_temp = realpath($system_path)) !== false) {
    $system_path = $_temp . DIRECTORY_SEPARATOR;
} else {
    $system_path = rtrim($system_path, '/\\') . DIRECTORY_SEPARATOR;
}
if (!is_dir($system_path)) {
    header('HTTP/1.1 503 Service Unavailable', true, 503);
    exit('O caminho da pasta "system" está incorreto.');
}

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('BASEPATH', $system_path);

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('SYSDIR', basename(BASEPATH));

// application/
if (is_dir($application_path)) {
    $_temp = realpath($application_path);
    $application_path = ($_temp !== false) ? $_temp : rtrim($application_path, '/\\');
} elseif (is_dir(BASEPATH . $application_path . DIRECTORY_SEPARATOR)) {
    $application_path = BASEPATH . rtrim($application_path, '/\\');
} else {
    header('HTTP/1.1 503 Service Unavailable', true, 503);
    exit('O caminho da pasta "application" está incorreto.');
}
define('APPPATH', $application_path . DIRECTORY_SEPARATOR);

// views/
if ($view_folder === '' && is_dir(APPPATH . 'views' . DIRECTORY_SEPARATOR)) {
    $view_folder = APPPATH . 'views';
} elseif ($view_folder !== '') {
    $_temp = realpath($view_folder);
    $view_folder = ($_temp !== false) ? $_temp : rtrim($view_folder, '/\\');
} elseif (is_dir(APPPATH . $view_folder . DIRECTORY_SEPARATOR)) {
    $view_folder = APPPATH . rtrim($view_folder, '/\\');
} else {
    header('HTTP/1.1 503 Service Unavailable', true, 503);
    exit('O caminho da pasta "views" está incorreto.');
}
define('VIEWPATH', $view_folder . DIRECTORY_SEPARATOR);

/* -------------------------------------------------------------
 | AJUSTES DE EXECUÇÃO
 * ------------------------------------------------------------- */
@ini_set('max_execution_time', '120'); // 2 minutos
@ini_set('memory_limit', '512M');      // 512 MB

/* -------------------------------------------------------------
 | INICIAR APLICAÇÃO
 * ------------------------------------------------------------- */
require_once BASEPATH . 'core/CodeIgniter.php';