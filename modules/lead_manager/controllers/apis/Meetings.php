<?php

//require __DIR__.'/REST_Controller.php';

require __DIR__ . '/REST_Controller.php';

use Firebase\JWT\JWT;
use GuzzleHttp\Client;

class Meetings extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('lead_manager_api_model');
        $this->load->model('lead_manager_model');
        $this->load->helper('lead_manager');
    }

    public function get_data($id = '')
    {
        $result = [];
        $page = (isset($_REQUEST['page']) && !empty($_REQUEST['page'])) ? $_REQUEST['page'] : 1;
        $limit = (isset($_REQUEST['limit']) && !empty($_REQUEST['limit'])) ? $_REQUEST['limit'] : 10;
        $offset = ($page - 1) * $limit;
        $where = [];
        $data = $this->lead_manager_api_model->getZoomMeetingDetails($id, $where, $limit, $offset);
        // Check if the data contains
        if ($data) {
            // $data = $this->Lead_manager_api_model->get_api_custom_data($data,"leads", $id);

            // Set the response and exit
            $this->response([
                'status' => TRUE,
                'message' => 'Meeting  details is here',
                'data' => $data
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    function getZoomAccessToken()
    {
        $key = get_option('zoom_secret_key');
        $payload = array(
            "iss" => get_option('zoom_api_key'),
            'exp' => time() + 3600,
        );
        return JWT::encode($payload, $key);
    }

    function updateZoomMeetingStatus($meeting_id, $status) {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        $response = $client->request('PUT', '/v2/meetings/'.$meeting_id.'/status', [
            "headers" => [
                "Authorization" => "Bearer " . $this->getZoomAccessToken()
            ],
            'json' => [
                "action" => $status
            ],
        ]);
        return $response->getStatusCode();
    }

    public function create()
    {
        $this->_lm_allow_methods(['POST']);
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['leadid'];
        $staffid = $data['staff_id'];
        if (isset($data['is_client']) && $data['is_client']) {
            $data['lead'] = $this->clients_model->get_contact($id);
        } else {
            $leadWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' . $staffid . ' OR addedfrom=' . $staffid . ' OR is_public=1)');
            $data['lead'] = $this->lead_manager_model->get($id, $leadWhere);
        }
        if (isset($data['lead']->email) && !empty($data['lead']->email)) {
            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://api.zoom.us',
            ]);
            if (isset($data['is_client']) && $data['is_client']) {
                $staff = get_staff($data['staff_id']);
                $data['staff_email'] = $staff->email;
                $data['staff_id'] = $data['staff_id'];
                $data['staff_name'] = $staff->full_name;
            } else {
                $data['is_client'] = 0;
                $staff = get_staff($data['staff_id']);
                $data['staff_email'] = $staff->email;
                $data['staff_id'] = $data['staff_id'];
                $data['staff_name'] = $staff->full_name;
            }
            $data['lead_id'] = $data['leadid'];
            $settings = array();
            $json = array();
            if (isset($data['meeting_option'])) {
                $data['meeting_option'] = $data['meeting_option'];
                if (!is_bool(array_search("allow_participants_to_join_anytime", $data['meeting_option']))) {
                    $settings["join_before_host"] = TRUE;
                }
                if (!is_bool(array_search("mute_participants_upon_entry", $data['meeting_option']))) {
                    $settings["mute_upon_entry"] = TRUE;
                }
                if (!is_bool(array_search("automatically_record_meeting_on_the_local_computer", $data['meeting_option']))) {
                    $settings["audio"] = "both";
                    $settings["auto_recording"] = "local";
                }
                $json = [
                    "topic" => $data['meeting_agenda'],
                    "type" => 2,
                    "start_time" => $data['meeting_start_date'],
                    "duration" => $data['meeting_duration'], // 30 mins
                    "password" => "123456",
                    "timezone" => $data['zoom_timezone'],
                    "settings" => $settings
                ];
            } else {
                $settings["auto_recording"] = "none";
                $data['meeting_option'] = array();
                $json = [
                    "topic" => $data['meeting_agenda'],
                    "type" => 2,
                    "start_time" => $data['meeting_start_date'],
                    "duration" => $data['meeting_duration'], // 30 mins
                    "password" => "123456",
                    "timezone" => $data['zoom_timezone'],
                ];
            }

            $response = $client->request('POST', '/v2/users/me/meetings', [
                "headers" => [
                    "Authorization" => "Bearer " . $this->getZoomAccessToken()
                ],
                'json' => $json,
            ]);

            $meeting_res_data = json_decode($response->getBody());
            $response = $this->lead_manager_model->save_zoom_meeting($data, $meeting_res_data);
            if ($response) {
                $this->response([
                    'status' => TRUE,
                    'message' => 'Zoom meeting created successfully'
                ], REST_Controller::HTTP_OK); // NOT_FOUND (404) being the HTTP response code
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Zoom Meeting not created'
                ], REST_Controller::HTTP_OK); // NOT_FOUND (404) being the HTTP response code
            };
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Plz add email id to lead!'
            ], REST_Controller::HTTP_OK); // NOT_FOUND (404) being the HTTP response code

        }
    }

    public function update()
    {
        $this->_lm_allow_methods(['POST']);
        if ($this->input->post()) {
            $meeting = $this->lead_manager_model->zoomMeetingDetails($this->input->post('id'));
            $status = $this->input->post('status') == 0 ? 'end' : ($this->input->post('status') == 1 ? 'waiting' : '');
            $post_data = ['id' => $this->input->post('id'), 'status' => $status];
            if (isset($meeting) && !empty($meeting->meeting_id)) {
                if ($status) {
                    $apiresponse = $this->updateZoomMeetingStatus($meeting->meeting_id, $status);
                    if ($apiresponse == 204) {
                        $this->lead_manager_model->update_meeting_status($post_data);
                        $this->response([
                            'status' => TRUE,
                            'message' => 'Zoom meeting updated successfully'
                        ], REST_Controller::HTTP_OK); // NOT_FOUND (404) being the HTTP response code
                    } else {
                        $this->response([
                            'status' => FALSE,
                            'message' => 'Something went wrong!'
                        ], REST_Controller::HTTP_OK); // NOT_FOUND (404) being the HTTP response code
                    }
                } else {
                    $this->response([
                        'status' => FALSE,
                        'message' => 'Send proper status!'
                    ], REST_Controller::HTTP_OK); // NOT_FOUND (404) being the HTTP response code
                }
            } else {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Meeting ID Not found'
                ], REST_Controller::HTTP_OK); // NOT_FOUND (404) being the HTTP response code
            }
        }
    }
    public function save_meeting_remark(){
        $data = $this->input->post();
        $res=$this->lead_manager_model->save_meeting_remark($data);
        
        if ($res){
            $this->response([
                'status'=>TRUE,
                'message'=>'Meetig remark saved successfully.',
                'data'=>['status'=>true]
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }else{
            $this->response([
                'status' => FALSE,
                'message' => 'Meetig remark not saved.'
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }
    }

    public function showMeetingRemark()
    {
        $id = $this->input->post('id');
        $rel_type = $this->input->post('rel_type');
        $zoom_meeting_remarks = $this->lead_manager_model->zoom_meeting_remarksDetails($id,$rel_type);
       
        if (!empty($zoom_meeting_remarks)){
            $this->response([
                'status'=>TRUE,
                'message'=>'Remark data is here',
                'data'=>$zoom_meeting_remarks,
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }else{
            $this->response([
                'status' => FALSE,
                'message' => 'No remark is added yet',
                'data'=>(object)[],     
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }
    }

    public function delete_zoom_meeting($id){
        if (!$id) {
            redirect(admin_url('lead_manager/zoom_meeting'));
        }
       
        $response=false;
        $meeting = $this->lead_manager_model->zoomMeetingDetails($id);
        $apiresponse = $this->deleteZoomMeeting($meeting->meeting_id);
    
        if($apiresponse == 204){
            $response = $this->lead_manager_model->delete_zoom_meeting($id);
        }
        
        if ($response === true) {
            $this->response([
                'status'=>TRUE,
                'message'=>'Zoom meeting deleted',
                'data'=>['status'=>true]
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
           
        } else {
            $this->response([
                'status'=>FALSE,
                'message'=>_l('problem_deleting'),
                'data'=>['status'=>false]
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code

        }
    
    }

    function deleteZoomMeeting($meeting_id) {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        try {
            $response = $client->request("DELETE", "/v2/meetings/$meeting_id", [
                "headers" => [
                    "Authorization" => "Bearer " . $this->getZoomAccessToken()
                ]
            ]);
            return $response->getStatusCode();
        }
        catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $msgContent = json_decode($response->getBody()->getContents(), true);
            $this->response([
                'status'=>FALSE,
                'message'=> $msgContent['message'], 
                'data'=>['status'=>false]
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        
    }
    public function getZoomMeetingDetails(){
        $id = $this->input->post('id');
        $data = $this->lead_manager_model->zoomMeetingDetails($id);
        if( $data ){
            $this->response([
                'status'=>TRUE,
                'message'=>'Meeting detail is here', 
                'data'=>$data,
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
          
        }else{
            $this->response([
                'status'=>FALSE,
                'message'=>'Meeting detail not found', 
                'data'=>(object)[],
            ], REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
       
    }
}
