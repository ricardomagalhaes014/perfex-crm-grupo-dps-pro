<?php



defined('BASEPATH') or exit('No direct script access allowed');



// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */

require __DIR__ . '/REST_Controller.php';



class Common extends REST_Controller
{

    public function __construct()

    {

        parent::__construct();
    }



    public function get_country($id = '')
    {
        $result = [];
        if ($id == '') {
            $data =  get_all_countries();
        } else {
            $data  = get_country($id);
        }
        if ($data) {
            $result['records'] = $data;
           $this->response([
                'status' => TRUE,
                'message' => 'Country details is here',
                'data' => $result
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Data not found',
                'data' => $result
            ], REST_Controller::HTTP_NOT_FOUND); // Not found (404) being the HTTP response code
        }
    }

    public function get_timezones()
    {
        $data =   get_timezones_list();
        if ($data) {
            $result['records'] = $data;
            $this->response([
                'status' => TRUE,
                'message' => 'Timezone list is here',
                'data' => $result
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Data not found',
                'data' => $result
            ], REST_Controller::HTTP_NOT_FOUND); // Not found (404) being the HTTP response code
        }
    }

    public function get_staff($id = '')
    {
        if (is_numeric($id)) {
            $result['records'] = get_staff($id);
            if(isset($result['records']) && !empty($result['records'])){
                $this->api_return([
                    'status' => TRUE,
                    'message' => 'Staff is here',
                    'data' => $result
                ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
            }
            $this->api_return([
                'status' => FALSE,
                'message' => 'Data not found',
                'data' => $result
            ], REST_Controller::HTTP_NOT_FOUND); // Not found (404) being the HTTP response code
            
        }
        $result['records'] = $this->staff_model->get();
        $this->api_return([
            'status' => TRUE,
            'message' => 'Staff list is here',
            'data' => $result
        ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code

    }

    public function get_staff_list(){
        $this->load->model('staff_model');
        $staff_data      = $this->staff_model->get('', ['is_not_staff' => 0, 'active' => 1]);
        $data=[];
        foreach ( $staff_data  as $staff ) {
            $data[] = ['staffid'=>$staff['staffid'],'fullname'=>$staff['firstname'].' '.$staff['lastname']];
        }
        if ($data) {
            $result['records'] = $data;
            $this->response([
                'status' => TRUE,
                'message' => 'Staff member list is here',
                'data' => $data
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Data not found',
                'data' => ['status'=>false],
            ], REST_Controller::HTTP_NOT_FOUND); // Not found (404) being the HTTP response code
        }
    }

    public function get_source_list(){
        $this->load->model('leads_model');
        $data  =  $this->leads_model->get_source();
        if ($data) {
            $result['records'] = $data;
            $this->response([
                'status' => TRUE,
                'message' => 'Source list is here',
                'data' => $data
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Data not found',
                'data' => ['status'=>false],
            ], REST_Controller::HTTP_NOT_FOUND); // Not found (404) being the HTTP response code
        }
    }

    public function get_status_list(){
        $this->load->model('leads_model');
        $data  =  $this->leads_model->get_status();
     
        if ($data) {
            $result['records'] = $data;
            $this->response([
                'status' => TRUE,
                'message' => 'Status list is here',
                'data' => $data
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Data not found',
                'data' => ['status'=>false],
            ], REST_Controller::HTTP_NOT_FOUND); // Not found (404) being the HTTP response code
        }
    }

    public function get_custom_fields($field_to=''){
        $fields =  get_custom_fields($field_to);
        $this->response([
            'status'=>FALSE,
            'message'=>'Custom field data',
            'data'=> $fields  ,
        ], REST_Controller::HTTP_OK);  
    }

    public function get_options_data($name=''){
        if ($this->db->field_exists('autoload', db_prefix() . 'options')) {
            $options = $this->db->select('name, value')
            ->where('autoload', 1)
            ->get(db_prefix() . 'options')->result_array();
        } else {
            $options = $this->db->select('name, value')
            ->get(db_prefix() . 'options')->result_array();
        }
        foreach ($options as $option) {
            $optionsdata[$option['name']] = $option['value'];
        }
        if(!empty($name)){
            $data = [$name=>$optionsdata[$name]];
        }else{
            $data=$optionsdata;
        }
        $this->response([
            'status' => TRUE,
            'message' =>'option data is here',
            'data'=>$data
        ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
    }

    public function get_relation_data(){
        $this->_lm_allow_methods(['POST']);
        if ($this->input->post()) {
            $type = $this->input->post('type');
            $data = get_relation_data($type);
            if ($this->input->post('rel_id')) {
                $rel_id = $this->input->post('rel_id');
            } else {
                $rel_id = '';
            }
            
            $relOptions = init_relation_options($data, $type, $rel_id);
            $this->response([
                'status' => TRUE,
                'message' =>'option data is here',
                'data'=>$relOptions
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
            
        }
    }

    
}
