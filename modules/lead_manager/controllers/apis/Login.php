<?php



defined('BASEPATH') or exit('No direct script access allowed');



// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */

//require __DIR__.'/REST_Controller.php';

require __DIR__ . '/LmAPI_Controller.php';

class Login extends LmAPI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('lead_manager_api_model');
        $this->load->helper('lead_manager');

    }



    public function index()
    {
        header("Access-Control-Allow-Origin: *");
        // API Configuration
        $this->_apiConfig([
            'methods' => ['POST'],
        ]);

        $email    = $this->input->post('email');

        $password = $this->input->post('password', false);
        $remember = $this->input->post('remember');

        $result = $this->lead_manager_api_model->login($email, $password,$remember, true);

        if ($result['status']) {

            $data = $result['data'];

            $this->api_return([

                'status' => TRUE,

                'message' => 'staff data is here',

                'data' =>  $data

            ], LmAPI_Controller::HTTP_OK);
        } else {

            $this->api_return([

                'status' => FALSE,

                'message' => $result['message'],

                'data' => (object)array()

            ], LmAPI_Controller::HTTP_UNAUTHORIZED);
        }
    }
}
