<?php 

namespace modules\social_media_login\core;

defined('BASEPATH') or exit('No direct script access allowed');
if (!class_exists('\Requests')) {
    require_once __DIR__ .'/../third_party/Requests.php';
}
if (!class_exists('\Firebase\JWT\SignatureInvalidException')) {
    require_once __DIR__ .'/../third_party/php-jwt/SignatureInvalidException.php';
}
if (!class_exists('\Firebase\JWT\JWT')) {
    require_once __DIR__ .'/../third_party/php-jwt/JWT.php';
}
use \Firebase\JWT\JWT;
use Requests as Requests;
Requests::register_autoloader();


class Apiinit
{
    public static function check_url($module_name){
        return true;
    }

    public static function parse_module_url($module_name){}
}