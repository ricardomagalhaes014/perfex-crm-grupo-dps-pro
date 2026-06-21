<?php
defined('BASEPATH') or exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
    'dsn'          => '',
    'hostname'     => defined('APP_DB_HOSTNAME') ? APP_DB_HOSTNAME : 'localhost',
    'username'     => defined('APP_DB_USERNAME') ? APP_DB_USERNAME : '',
    'password'     => defined('APP_DB_PASSWORD') ? APP_DB_PASSWORD : '',
    'database'     => defined('APP_DB_NAME') ? APP_DB_NAME : '',
    'dbdriver'     => 'mysqli',
    'dbprefix'     => defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl',
    'pconnect'     => FALSE,
    'db_debug'     => (ENVIRONMENT !== 'production'),
    'cache_on'     => FALSE,
    'cachedir'     => '',
    'char_set'     => defined('APP_DB_CHARSET') ? APP_DB_CHARSET : 'utf8mb4',
    'dbcollat'     => defined('APP_DB_COLLATION') ? APP_DB_COLLATION : 'utf8mb4_unicode_ci',
    'swap_pre'     => '',
    'encrypt'      => FALSE,
    'compress'     => FALSE,
    'stricton'     => FALSE,
    'failover'     => array(),
    'save_queries' => TRUE,
);
