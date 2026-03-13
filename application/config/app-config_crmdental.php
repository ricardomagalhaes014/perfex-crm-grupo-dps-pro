<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
* --------------------------------------------------------------------------
* Base Site URL
* --------------------------------------------------------------------------
*/
define('APP_BASE_URL', 'https://crm.dentalprosmart.pt/');

/*
* --------------------------------------------------------------------------
* Encryption Key
* --------------------------------------------------------------------------
*/
define('APP_ENC_KEY', 'e92b20a7d7626acc3f270882da7fdf4d');

/**
 * Database Credentials
 */
define('APP_DB_HOSTNAME', 'localhost');
define('APP_DB_USERNAME', 'u677382737_crmdpsenergy');
define('APP_DB_PASSWORD', '3AF5_ZCiqQ7:=At');
define('APP_DB_NAME', 'u677382737_crmdpsenergy');

/**
 * Database Charset and Collation
 */
define('APP_DB_CHARSET', 'utf8');
define('APP_DB_COLLATION', 'utf8_general_ci');

/**
 * Session Handler
 */
define('SESS_DRIVER', 'database');
define('SESS_SAVE_PATH', 'sessions');
define('APP_SESSION_COOKIE_SAME_SITE', 'Lax');

/**
 * CSRF Protection Disabled
 */
define('APP_CSRF_PROTECTION', false);

/**
 * Disable Google API Integration
 */
define('APP_DISABLE_GOOGLE_API', true); 
define('APP_GOOGLE_API_KEY', '');
define('APP_GOOGLE_CLIENT_ID', '');
define('APP_GOOGLE_CALENDAR_ID', '');
define('APP_GOOGLE_PICKER_ENABLED', false);

/**
 * Disable Google OAuth2 Authentication
 */
define('APP_DISABLE_GOOGLE_AUTH', true);
define('APP_GOOGLE_AUTH_CLIENT_ID', '');
define('APP_GOOGLE_AUTH_CLIENT_SECRET', '');
define('APP_GOOGLE_AUTH_REDIRECT_URI', '');

/**
 * Logging and Debugging
 */
define('APP_LOG_THRESHOLD', 1);
define('APP_DEBUG_MODE', false);

/**
 * Disable Google reCAPTCHA
 */
define('APP_RECAPTCHA_ENABLED', false);
define('APP_RECAPTCHA_SITE_KEY', '');
define('APP_RECAPTCHA_SECRET_KEY', '');

/**
 * Disable Google Maps API
 */
define('APP_GOOGLE_MAPS_ENABLED', false);
define('APP_GOOGLE_MAPS_API_KEY', '');

?>