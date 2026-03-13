<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_110 extends App_module_migration
{
    public function up()
    {
        add_option('predix_use_streams_for_chat', '1');
    }
}