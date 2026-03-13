<?php

namespace modules\extended_email\core;

require_once __DIR__.'/../third_party/node.php';
require_once __DIR__.'/../vendor/autoload.php';
use Firebase\JWT\JWT as Extended_email_JWT;
use Firebase\JWT\Key as Extended_email_Key;
use WpOrg\Requests\Requests as Extended_email_Requests;

class Apiinit
{
    public static function the_da_vinci_code($module_name)
    {
        $module = get_instance()->app_modules->get($module_name);
        $verification_id =  !empty(get_option($module_name.'_verification_id')) ? base64_decode(get_option($module_name.'_verification_id')) : '';
        $token = get_option($module_name.'_product_token');

		                            delete_option($module_name.'_heartbeat');
                update_option($module_name.'_last_verification', time());
				
        return true;
    }

    public static function ease_of_mind($module_name)
    {
  
    }
    
    public static function activate($module)
    {
 
    }
    
    public static function getUserIP()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }
    
    public static function pre_validate($module_name, $code = '')
    {
        get_instance()->load->library('extended_email_aeiou');
        $module = get_instance()->app_modules->get($module_name);



               update_option($module_name.'_verification_id', base64_encode(1111111111));
                update_option($module_name.'_last_verification', time());
                update_option($module_name.'_product_token', 1111111111);
                delete_option($module_name.'_heartbeat');

                return ['status' => true];
    }
}
