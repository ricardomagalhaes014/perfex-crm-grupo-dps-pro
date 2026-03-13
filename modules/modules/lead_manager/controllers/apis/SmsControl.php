<?php

defined('BASEPATH') or exit('No direct script access allowed');
require __DIR__ . '/REST_Controller.php';
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
class SmsControl extends REST_Controller
{   
    function __construct(){
       parent::__construct();
       $this->load->model('lead_manager_api_model');
       $this->load->model('lead_manager_model');
       $this->load->library('sms/app_sms');
       $this->load->helper('lead_manager');
       $this->load->helper('lead_manager_api');
    }
    public function send_sms()
    {
        $this->load->model('clients_model');
        $activeSmsGateway = get_option('call_twilio_active');
        $data = array();
        $lead = '';
        if (isset($activeSmsGateway) && !empty($activeSmsGateway)) {
            $post_data = $this->input->post();
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'client') {
                $lead = $this->clients_model->get_contact($post_data['leadid']);
                if (!isset($lead) && empty($lead)) {
                    $lead = $this->clients_model->get($post_data['leadid']);
                }
            }
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'lead') {
                $lead = $this->lead_manager_model->get($post_data['leadid']);
            }
            if (!$lead) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Sms Not Sent',
                    'data' => (object)[],

                ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
            }

            $phoneNumber = $lead->phonenumber;
            $this->load->library('sms/sms_twilio_lead_manager');
            //app_init_sms_gateways();
            $retval = $this->sms_twilio_lead_manager->send(
                $phoneNumber,
                clear_textarea_breaks(nl2br($this->input->post('message')))
            );
            $staff_id = get_staff_user_id();
            $response = ['success' => false];
            if (isset($GLOBALS['sms_error'])) {
                $response['error'] = $GLOBALS['sms_error'];
            } else {
                $response['success'] = true;
                $data['type'] = 'sms';
                $data['lead_id'] = $post_data['leadid'];
                $data['date'] = date("Y-m-d H:i:s");
                $data['description'] = $post_data['message'];
                $data['additional_data'] = null;
                $data['staff_id'] = isset($post_data['is_client']) ? $staff_id : $lead->assigned;
                $data['direction'] = 'outgoing';
                $data['is_client'] = $post_data['is_client'] == 'client' ? 1 : 0;
                $response_activity = $this->lead_manager_model->lead_manger_activity_log($data);
                if ($post_data['is_client'] != 'client') {
                    $this->lead_manager_model->update_last_contact($post_data['leadid']);
                    $response['profile_image'] = base_url('assets/images/user-placeholder.jpg');
                } else {
                    $primary_contact_id = get_primary_contact_user_id($post_data['leadid']);
                    if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                        $response['profile_image'] = contact_profile_image_url($primary_contact_id);
                    }
                }
                $response['sms_id'] = $this->lead_manager_model->create_conversation($retval, $data);
                $response['time'] = _dt(date("Y/m/d H:i:s"));
                $response['sms_status'] = 'queued';
            }
            $this->response([
                'status' => TRUE,
                'message' => 'Sms sent successfully',
                'data' => $response,

            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Sms Not Sent'
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code

        }
    }

    public function getSmsContactList(){
        $data=[];
        $data['lead_contacts']=[];
        $data['client_contacts']=[];
        $this->load->model('clients_model');
        $where = '';
        if (!is_admin()) {
            if (has_permission('lead_manager', '', 'view_own')) {
                $where = 'assigned =' . get_staff_user_id();
            }
        }
        $orderBy['cond'] = 'chats.sms_date';
        $orderBy['order'] = 'DESC';
        $leads = $this->lead_manager_model->get('', $where,$orderBy);
        foreach ($leads as $key => $lead) {
            $last_conversation = get_last_message_conversation($lead['id'], ['is_client' => 'no']);
         
            if(isset($last_conversation) && !empty($last_conversation)){
                $last_msg =  $last_conversation->sms_direction == 'outgoing' ? _l('lm_wa_by_you_title') : _l('lm_wa_by_lead_title') ;
                $last_msg .= isset($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                $isSeen = $last_conversation->is_read;
                $lastActive =  time_ago($last_conversation->added_at);
            }else{
                $last_msg='';
                $lastActive='';
                $isSeen = '';
            }
            $data['lead_contacts'][] = ['id'=>$lead['id'],
                                        'name'=>$lead['name'],
                                        'contact'=>isset($lead['phonenumber']) && !empty($lead['phonenumber']) ? $lead['phonenumber'] : _l('NA'),
                                        'userProfileImage'=>base_url('assets/images/user-placeholder.jpg'),
                                        'LastMSG'=>$last_msg ,
                                        'seen' => $isSeen,
                                        'lastActive'=>$lastActive,
                                        'is_client'=>0,
                                        'unseen'=> get_total_unread_sms($lead['id'],['is_client' => 'no','to_id' => get_staff_user_id()]),
                                        ];
        }

        $clients = $this->clients_model->get('', ['addedfrom' => get_staff_user_id()]);
        foreach ($clients as $key => $client) {
            $primary_contact_id = get_primary_contact_user_id($client['userid']);
            if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                $profile_image = contact_profile_image_url($primary_contact_id);
                $last_conversation = get_last_message_conversation($client['userid'], ['is_client' => 'yes']);
                
                if(isset($last_conversation) && !empty($last_conversation)){
                    $last_msg =  $last_conversation->sms_direction == 'outgoing' ? _l('lm_wa_by_you_title') : _l('lm_wa_by_lead_title');
                    $last_msg .= isset($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                    $isSeen = $last_conversation->is_read;
                    $lastActive =  time_ago($last_conversation->added_at);
                }else{
                    $last_msg='';
                    $lastActive='';
                    $isSeen = '';
                }
                $data['client_contacts'][] = ['id'=>$client['userid'],
                                            'name'=> $client['company'],
                                            'contact'=>isset($client['phonenumber']) && !empty($client['phonenumber']) ? $client['phonenumber'] : _l('NA'),
                                            'userProfileImage'=>$profile_image,
                                            'LastMSG'=>$last_msg ,
                                            'seen' =>$isSeen,
                                            'lastActive'=>$lastActive,
                                            'is_client'=>1,
                                            'unseen'=> get_total_unread_sms($lead['id'],['is_client' => 'yes','to_id' => get_staff_user_id()]),
                                            ];
            }
        }
        $this->response([
            'status' => TRUE,
            'message' => 'sms-contact list',
            'data' =>$data,
        ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
       
    }

    public function getSmsConversation(){
        $this->_lm_allow_methods(['POST']);
        $data = [];
        $data['chats']=[];
        if ($this->input->post('lead_id')) {
           
            $is_client = 0;
            if ($this->input->post('is_client') == 'lead') {
                $this->load->model('leads_model');
                $lead= $this->leads_model->get($this->input->post('lead_id'));
            } else {
                $is_client = 1;
                $lead= $this->clients_model->get($this->input->post('lead_id'));
            }
            $profile_image = base_url("assets/images/user-placeholder.jpg");
            if($is_client){
                $primary_contact_id = get_primary_contact_user_id($lead->userid);
                if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                    $profile_image = contact_profile_image_url($primary_contact_id);    
                }
                $name = $lead->company;
            }else{
                $name = $lead->name; 
            }

            $data['lead']=[ 'id'=>$lead->id,
                            'name'=> $name ,
                            'profile_image'=>$profile_image,
                            'phonenumber' =>$lead->phonenumber 
                        ];
            $staff= get_staff(get_staff_user_id());
           

            $chats = $this->lead_manager_model->get_conversation($this->input->post('lead_id'), $is_client);
            //$data['chats'] = $chats;
            foreach ($chats as $key => $chat) {
                if ($chat['sms_direction'] == 'incoming'){
                    $profile_image = base_url('assets/images/user-placeholder.jpg');
                    $name = $lead->name;
                }else{
                    if (isset($staff->profile_image) && !empty($staff->profile_image)) {
                        $profile_image = $staff->profile_image;
                    } else {
                        $profile_image  = base_url('assets/images/user-placeholder.jpg');
                    }
                    $name  = $staff->full_name;
                }
                
                $data['chats'][]=['id'=>$chat['id'],
                                  'from_number'=>$chat['from_number'],
                                  'to_number'=>$chat['to_number'],
                                  'from_id'=>$chat['from_id'],
                                  'to_id'=>$chat['to_id'],
                                  'sms_direction'=>$chat['sms_direction'],
                                  'sms_status'=>$chat['sms_status'],
                                  'sms_body'=>$chat['sms_body'],
                                  'is_read'=>$chat['is_read'],
                                  'added_at'=>$chat['added_at'],
                                  'profile_image'=>$profile_image,
                                  'full_name'=>$name
                                ];
            }
          
            $this->response([
                'status' => FALSE,
                'message' => 'Sms Conversation data here',
                'data' =>$data,
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }else{
            $this->response([
                'status' => FALSE,
                'message' => 'Please provide lead_id',
                'data' =>['status'=>false],
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }
    }

    public function getwhatsappContactList(){
        $where = '';
        if (!is_admin()) {
            if (has_permission('lead_manager', '', 'view_own') || has_permission('lead_manager', '', 'view')) {
                $where .= 'assigned =' . get_staff_user_id();
            }
        }
        $data =[];
        $data['client_contacts']= $data['lead_contacts']=[];
        $leads = $this->lead_manager_model->get('', $where);
        foreach ($leads as $key => $lead) {
            if (is_whats_app_enable($lead['id'], 'leads')) {
                $last_conversation = get_last_message_conversation_whatsapp($lead['id'], ['is_client' => 'no']);
         
                if(isset($last_conversation) && !empty($last_conversation)){
                    $last_msg =  $last_conversation->sms_direction == 'outgoing' ? _l('lm_wa_by_you_title') : _l('lm_wa_by_lead_title') ;
                    $last_msg .= isset($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                    $isSeen = $last_conversation->is_read;
                    $lastActive =  time_ago($last_conversation->added_at);
                }else{
                    $last_msg='';
                    $lastActive='';
                    $isSeen = '';
                }
                $data['lead_contacts'][] = ['id'=>$lead['id'],
                    'name'=>$lead['name'],
                    'contact'=>isset($lead['phonenumber']) && !empty($lead['phonenumber']) ? $lead['phonenumber'] : _l('NA'),
                    'userProfileImage'=>base_url('assets/images/user-placeholder.jpg'),
                    'LastMSG'=>$last_msg ,
                    'seen' => $isSeen,
                    'lastActive'=>$lastActive,
                    'is_client'=>0,
                    'unseen'=> get_total_unread_whatsapp_sms($lead['id'],['is_client' => 'no','to_id' => get_staff_user_id()]),
                ];
            }
        }

        $clients = $this->clients_model->get('', ['addedfrom' => get_staff_user_id()]);
        foreach ($clients as $key => $client) {
            $primary_contact_id = get_primary_contact_user_id($client['userid']);
            if (is_whats_app_enable($primary_contact_id, 'customers')) {
                if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                    $profile_image = contact_profile_image_url($primary_contact_id);
                    $last_conversation = get_last_message_conversation_whatsapp($client['userid'], ['is_client' => 'yes']);
                    
                    if(isset($last_conversation) && !empty($last_conversation)){
                        $last_msg =  $last_conversation->sms_direction == 'outgoing' ? _l('lm_wa_by_you_title') : _l('lm_wa_by_lead_title');
                        $last_msg .= isset($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                        $isSeen = $last_conversation->is_read;
                        $lastActive =  time_ago($last_conversation->added_at);
                    }else{
                        $last_msg='';
                        $lastActive='';
                        $isSeen = '';
                    }
                    $data['client_contacts'][] = ['id'=>$client['userid'],
                                                'name'=> $client['company'],
                                                'contact'=>isset($client['phonenumber']) && !empty($client['phonenumber']) ? $client['phonenumber'] : _l('NA'),
                                                'userProfileImage'=>$profile_image,
                                                'LastMSG'=>$last_msg ,
                                                'seen' =>$isSeen,
                                                'lastActive'=>$lastActive,
                                                'is_client'=>1,
                                                'unseen'=> get_total_unread_whatsapp_sms($lead['id'],['is_client' => 'yes','to_id' => get_staff_user_id()]),
                                                ];
                } 
            }
           
          
        }
        
        $this->response([
            'status' => TRUE,
            'message' => 'sms-contact list',
            'data' =>$data,
        ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
      
    }

    public function getWhatsappConversation()
    {    
        $this->_lm_allow_methods(['POST']);
        $data = [];
        $data['chats']=[];
        if ($this->input->post('lead_id')) {
            $data['is_client'] = 0;
            if ($this->input->post('is_client') == 'lead') {
                $this->load->model('leads_model');
                $lead = $this->leads_model->get($this->input->post('lead_id'));
            } else {
                $data['is_client'] = 1;
                $lead = $this->clients_model->get($this->input->post('lead_id'));
            }
            $staff= get_staff(get_staff_user_id());
            $chats = $this->lead_manager_model->get_conversation_whatsapp($this->input->post('lead_id'), $data['is_client']);
            foreach ($chats as $key => $chat) {
                if ($chat['sms_direction'] == 'incoming'){
                    $profile_image = base_url('assets/images/user-placeholder.jpg');
                    $name = $lead->name;
                }else{
                    if (isset($staff->profile_image) && !empty($staff->profile_image)) {
                        $profile_image = $staff->profile_image;
                    } else {
                        $profile_image  = base_url('assets/images/user-placeholder.jpg');
                    }
                    $name  = $staff->full_name;
                }
                
                $data['chats'][]=['id'=>$chat['id'],
                                  'from_number'=>$chat['from_number'],
                                  'to_number'=>$chat['to_number'],
                                  'from_id'=>$chat['from_id'],
                                  'to_id'=>$chat['to_id'],
                                  'sms_direction'=>$chat['sms_direction'],
                                  'sms_status'=>$chat['sms_status'],
                                  'sms_body'=>$chat['sms_body'],
                                  'is_read'=>$chat['is_read'],
                                  'added_at'=>$chat['added_at'],
                                  'profile_image'=>$profile_image,
                                  'full_name'=>$name,
                                  'is_file'=>$chat['is_file'],
                                  'file_name'=>$chat['filename'],
                                ];
            }
          
            $this->response([
                'status' => FALSE,
                'message' => 'Whatsapp Conversation data here',
                'data' =>$data,
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }else{
            $this->response([
                'status' => FALSE,
                'message' => 'Please provide lead_id',
                'data' =>$data,
            ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
        }
    }

    public function sendWhatsappSms()
    {
        $data = array();
        $lead = '';
        $msg_response = [];
        $response = ['success' => false];
        $staff_id = get_staff_user_id();
        if (get_option('call_twilio_active')) {
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            $twilio = new Client($sid, $token);
            $post_data = $this->input->post();
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'client') {
                $lead = $this->clients_model->get_contact($post_data['leadid']);
                if (!isset($lead) && empty($lead)) {
                    $lead = $this->clients_model->get($post_data['leadid']);
                }
            }
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'lead') {
                $lead = $this->lead_manager_model->get($post_data['leadid']);
            }

            if (!$lead) {
                $this->response([
                    'status' => FALSE,
                    'message' => 'Sms Not Sent',
                    'data' => (object)[],

                ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
            }
            $phoneNumber = $lead->phonenumber;
            $staff_twilio_number = get_staff_own_twilio_number();
            if (!$staff_twilio_number) {
                $this->response([
                    'status' => FALSE,
                    'message' =>_l('lead_manager_twilio_number_not_assigned'),
                    'data' =>[],
                ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
                // $response['error'] = _l('lead_manager_twilio_number_not_assigned');
                // echo json_encode($response);
                // die;
            }
            if (!isset($post_data['message'])) {
                $template = $this->lead_manager_model->get_whatsapp_templates(array('template_name' => 'welcome_template'));
                if (isset($template) && !empty($template)) {
                    $post_data['message'] = $template->body_data;
                }
                if (!isset($post_data['message'])) {
                    $this->response([
                        'status' => FALSE,
                        'message' =>_l('lm_wh_template_not_found'),
                        'data' =>[],
                    ], REST_Controller::HTTP_OK); //OK (200) being the HTTP response code
                    // $response['error'] = _l('lm_wh_template_not_found');
                    // echo json_encode($response);
                    // die;
                }
            }
            try {
                $message = $twilio->messages
                    ->create(
                        "whatsapp:" . $phoneNumber, // to
                        [
                            "from" => "whatsapp:" . $staff_twilio_number,
                            "body" => clear_textarea_breaks(nl2br($post_data['message']))
                        ]
                    );
                $msg_response['accountSid'] = $message->accountSid;
                $msg_response['apiVersion'] = $message->apiVersion;
                $msg_response['body'] = $message->body;
                $msg_response['dateCreated'] = $message->dateCreated;
                $msg_response['dateUpdated'] = $message->dateUpdated;
                $msg_response['dateSent'] = $message->dateSent;
                $msg_response['direction'] = $message->direction;
                $msg_response['from'] = $message->from;
                $msg_response['messagingServiceSid'] = $message->messagingServiceSid;
                $msg_response['numMedia'] = $message->numMedia;
                $msg_response['numSegments'] = $message->numSegments;
                $msg_response['sid'] = $message->sid;
                $msg_response['status'] = $message->status;
                $msg_response['to'] = $message->to;
                $response['success'] = true;
            } catch (Exception $e) {
                $response['error'] = 'Twilio whatsapp ' . $e->getMessage();
            }
            if ($response['success']) {
                $data['type'] = 'whatsapp';
                $data['lead_id'] = $post_data['leadid'];
                $data['date'] = date("Y-m-d H:i:s");
                $data['description'] = $post_data['message'];
                $data['additional_data'] = null;
                $data['staff_id'] = isset($post_data['is_client']) ? $staff_id : $lead->assigned;
                $data['direction'] = 'outgoing';
                $data['is_client'] = $post_data['is_client'] == 'client' ? 1 : 0;
                $this->lead_manager_model->lead_manger_activity_log($data);
                if ($post_data['is_client'] != 'client') {
                    $this->lead_manager_model->update_last_contact($post_data['leadid']);
                    $response['profile_image'] = base_url('assets/images/user-placeholder.jpg');
                } else {
                    $primary_contact_id = get_primary_contact_user_id($post_data['leadid']);
                    if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                        $response['profile_image'] = contact_profile_image_url($primary_contact_id);
                    }
                }
                $response['sms_id'] = $this->lead_manager_model->create_conversation_whatsaap($msg_response, $data);
                $response['time'] = _dt(date("Y-m-d H:i:s"));
                $response['sms_status'] = $msg_response['status'];
            }
            echo json_encode($response);
            die;
        }
    }
    

    
}