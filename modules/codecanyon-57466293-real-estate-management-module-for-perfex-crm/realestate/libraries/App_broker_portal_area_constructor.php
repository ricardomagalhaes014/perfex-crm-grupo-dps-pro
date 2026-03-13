<?php

defined('BASEPATH') or exit('No direct script access allowed');

class App_broker_portal_area_constructor
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->load->library('form_validation');
        $this->ci->form_validation->set_error_delimiters('<p class="text-danger alert-validation">', '</p>');

        $this->ci->form_validation->set_message('required', _l('form_validation_required'));
        $this->ci->form_validation->set_message('valid_email', _l('form_validation_valid_email'));
        $this->ci->form_validation->set_message('matches', _l('form_validation_matches'));
        $this->ci->form_validation->set_message('is_unique', _l('form_validation_is_unique'));

        $this->ci->load->model('realestate/realestate_model');
        $this->ci->load->model('realestate/authentication_broker_model');
        $this->ci->load->model('realestate/broker_model');

        $vars = [];

         if (is_broker_logged_in()) {
            $currentUser = $this->ci->broker_model->get_broker_staff(get_broker_id());
            $GLOBALS['current_broker'] = $currentUser;

            if (!$currentUser || $currentUser->active == 0) {
                $this->ci->Authentication_broker_model->logout();
                redirect(site_url('realestate/broker'));
            }

            $vars['current_broker']                         = $currentUser;
        }
        include_once(module_dir_path(REALESTATE_MODULE_NAME, 'views/brokers_portals/functions.php'));
        init_broker_portal_area_assets();

        $vars['menu']            = $this->ci->app_menu->get_theme_items();
        $vars['isRTL']           = (is_rtl_broker(true) ? 'true' : 'false');

        $this->ci->load->vars($vars);
    }
}
