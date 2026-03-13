<?php

defined('BASEPATH') or exit('No direct script access allowed');

class poly_utilities_user_helper
{
    private static $CI;
    
    public static function init_ci()
    {
        if (!self::$CI) {
            self::$CI = &get_instance();
        }
        return self::$CI;
    }

    public static function get_user_role($staff_id)
    {
        self::init_ci();
        $role_by_staffid = self::$CI->db->select("role")->where("staffid", $staff_id)->get(db_prefix() . "staff")->row();
        return $role_by_staffid ?? null;
    }

    public static function get_users_access_modules(){
        $data = get_option(POLY_UTILITIES_USERS_ACCESS_MODULES, true);
        if (is_null($data) || !is_string($data) || empty($data)) {
            $data = '[]';
        }
        return $data;
    }
}
