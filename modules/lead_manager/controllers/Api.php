<?php

defined('BASEPATH') or exit('No direct script access allowed');



$check =  __dir__;

$str = preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);

$str . '/third_party/vendor/autoload.php';



class Api extends AdminController

{

    public function __construct()

    {

        parent::__construct();

        $this->load->model('lead_manager_api_model');



        $this->load->library('app_modules');

        if(!$this->app_modules->is_active('lead_manager')){

            access_denied("Lead Manager Api");

        }

        \modules\lead_manager\core\Apiinit::check_url('api');

        $this->load->helper('lead_manager_api');

    }

    public function index()

    {  

        \modules\lead_manager\core\Apiinit::check_url('api');

        $data['user_api'] = $this->lead_manager_api_model->get_user_data();

        $data['title'] = _l('lm_api_management');

        

        $staffData = getStaff();

        $staffs=[];

       

        foreach ($staffData as $key => $staff) {

           $staffs[]=['id'=>$staff['staffid'],'name'=>$staff['firstname'].' '.$staff['lastname']];

        }

        $data['staffs'] = $staffs;

        $this->load->view('admin/api/token_management', $data);

    }



    public function user_token(){

        \modules\lead_manager\core\Apiinit::check_url('api');

        if (!is_admin()) {

            access_denied('Access Denied');

        }

        if ($this->input->post()) {

            \modules\lead_manager\core\Apiinit::check_url('api');

            if (!$this->input->post('id')) {

                $id = $this->lead_manager_api_model->add_user_token($this->input->post());

                if ($id) {

                    set_alert('success', _l('added_successfully', _l('lm_user_api')));

                }

                 redirect(admin_url('lead_manager/api'));

            } else {

                $data = $this->input->post();

                $id   = $data['id'];

                unset($data['id']);

                $success = $this->lead_manager_api_model->update_user_token($data, $id);

                if ($success) {

                    set_alert('success', _l('updated_successfully', _l('user_api')));

                }

                redirect(admin_url('lead_manager/api'));

            }

            die;

        }

    }

    public function delete_user_token($id=''){

        

        \modules\lead_manager\core\Apiinit::check_url('api');

        if (!is_admin()) {

            access_denied('User');

        }

        if (!$id) {

            redirect(admin_url('lead_manager/api'));

        }

        $response = $this->lead_manager_api_model->delete_user_token($id);

        if ($response == true) {

            set_alert('success', _l('deleted', _l('user_api')));

        }

        redirect(admin_url('lead_manager/api'));

    }

}