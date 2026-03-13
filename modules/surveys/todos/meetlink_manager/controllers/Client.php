<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Client extends ClientsController
{
    public function __construct()
    {
        parent::__construct();
    }

   
    public function index()
    {
        if (0 != get_option('meetlink_manager_menu_disabled')) {
            set_alert('warning', _l('access_denied'));
            redirect(site_url());
        }
        $this->load->model('meetings_model');
        $data['title']              = _l('meetlink_manager');
        $data['meetings']           = $this->meetings_model->get();
        $this->data($data);
        $this->view('clients/list');
        $this->layout();
    }

   


   
}