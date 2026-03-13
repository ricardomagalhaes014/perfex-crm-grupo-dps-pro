<?php

namespace modules\mailbox\core;

require_once __DIR__.'/../third_party/node.php';
require_once __DIR__.'/../vendor/autoload.php';
use WpOrg\Requests\Requests as Mailbox_Requests;

class Apiinit
{
    public static function the_da_vinci_code($module_name)
    {
        // O módulo sempre será considerado como ativo
        return true;
    }

    public static function ease_of_mind($module_name)
    {
        // O módulo sempre será considerado como ativo
        return;
    }

    public static function activate($module)
    {
        // O módulo sempre será ativado
        return;
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
        // Removemos completamente a lógica de validação do código de compra
        return;
    }
}
