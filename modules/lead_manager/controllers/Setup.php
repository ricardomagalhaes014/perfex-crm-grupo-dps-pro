<?php defined('BASEPATH') or exit('No direct script access allowed');

class Setup extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('lead_manager_model');
    }

    public function whatsapp_template()
    {
        if($this->input->post()){

        }
        $data['title'] = _l('lm_whatsapp_template_page_title');
        $data['languages'] = $this->app->get_available_languages();
        $data['staff'] = get_staff();
        $this->load->view('admin/setup/whatsapp_templates', $data);
    }
    
    public function whatsapp_templates_table()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('lead_manager', 'admin/setup/whatsapp_templates_table'));
    }
}