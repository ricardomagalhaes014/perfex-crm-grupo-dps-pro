<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        add_option('staff_members_create_inline_mindmap_group', 1);
    }
}