<?php

class Meetlink_manager_lib
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('meetlink_manager/meetings_model');

    }
}
