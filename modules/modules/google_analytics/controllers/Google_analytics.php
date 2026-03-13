<?php

defined('BASEPATH') or exit('No direct script access allowed');

class google_analytics extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('Google Analytics');
        }
        
        $this->load->helper('/google_analytics');
    }

    public function index()
    {
        $data['title'] = _l('google_analytics');
        $this->load->view('google_analytics', $data);
    }

    public function reset()
    {
        update_option('google_analytics', 'enable');
        redirect(admin_url('google_analytics'));
    }

    public function save()
    {
        hooks()->do_action('before_save_google_analytics');
        
        foreach(['admin_area','clients_area','clients_and_admin'] as $css_area) {
            // Also created the variables
            $$css_area = $this->input->post($css_area, FALSE);
            $$css_area = trim($$css_area);
            $$css_area = nl2br($$css_area);
        }
        
        update_option('google_analytics_admin_area', $admin_area);
        update_option('google_analytics_clients_area', $clients_area);
        update_option('google_analytics_clients_and_admin_area', $clients_and_admin);
    }
    
    public function enable()
    {
        hooks()->do_action('before_save_google_analytics');

        update_option('google_analytics', 'enable');
    }
    
    public function disable()
    {
        hooks()->do_action('before_save_google_analytics');

        update_option('google_analytics', 'disable');
    }
}
