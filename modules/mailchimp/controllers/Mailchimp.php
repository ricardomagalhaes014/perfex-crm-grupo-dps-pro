<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(APP_MODULES_PATH.'/mailchimp/third_party/mailchimp-marketing/vendor/autoload.php');
class Mailchimp extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->ci = &get_instance();
        $this->load->helper('mailchimp');
        $this->load->model('mailchimp_audience_model');
        $this->load->model('mailchimp_store_model');
        $this->load->library('MailchimpAPIWrapper');
        $this->load->model('clients_model');
    }
    public function index()
    {
        $data['title']= _l('mailchimp_configuration');
        $configuration = $this->db->get(db_prefix() . 'mailchimp')->row();
        if(isset($configuration)){
            $data['api_key'] = $configuration->api_key;
            $data['limit_for_sync_jm_to_mc'] = $configuration->limit_for_sync_jm_to_mc;
            $data['is_active'] = $configuration->is_active;
        }else{
            $data['is_active'] = 0;
        }
        
        $this->load->view('mailchimp_configuration', $data);

    }
    
    public function save_configuration(){
        $api_key = $this->input->post('api_key', true);
        if(!empty($api_key)){
           $server_prefix = explode("-", $api_key)[1]; 
        }else{
            $server_prefix = '';
        }
        $data = array(
            'api_key'=> $api_key,
            'server_prefix' => $server_prefix,
            'limit_for_sync_jm_to_mc' => $this->input->post('limit_for_sync_jm_to_mc',true),
            'is_active' =>1
        );
        $configuration = $this->db->get(db_prefix() . 'mailchimp')->row();
        if(isset($configuration)){
            $this->db->update( db_prefix().'mailchimp',$data);
        }else{
            $this->db->insert( db_prefix().'mailchimp',$data);
        }
        set_alert('success', _l('mailchimp_configuration_saved_and_session_started', _l('mailchimp')));
        redirect(admin_url('mailchimp'));
    }

    /*****************Audience CRUD Start here 28-07-2022***********************/
    public function audience()
    {
        $data['title']= _l('mailchimp_audience');
        $this->db->order_by('id','DESC');
        $data['list'] = $this->db->get(db_prefix().'mailchimp_audience')->result_array();
        $this->load->view('mailchimp_audience', $data);

    }

    public function add_audience()
    {
        $post_data = $this->input->post();
        $id = $this->input->post('id');

        if($id){
            $result = $this->mailchimp_audience_model->update($post_data);
            $response['message'] = _l('Audience_updated_successfully');
        }else{
            $result = $this->mailchimp_audience_model->add($post_data);
            $response['message'] = _l('Audience_added_successfully');
        }
        if($result){
            $response['status'] = 'success';
            
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Something_went_wrong!');
        }
        echo json_encode($response);
    }

    public function delete_audience()
    {
        $id = $this->input->post('id');
        $delete = $this->mailchimp_audience_model->delete($id);
        if($delete){

            $response['status'] = 'success';
            $response['message'] = _l('Audience_deleted_successfully');
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Something_went_wrong!');
        }
        echo json_encode($response);
    }

    public function get_audience_data(){
        $id = $this->input->post('id');
        if($id){
            $list = $this->mailchimp_audience_model->get_audience_data($id);
            if($list){
                $response['title'] = _l('edit_audience');
                $response['audience'] = $list[0];
                $response['status'] = 'success';
            }
        }else{
            $response['status'] = 'failure';
             $response['message'] = _l('Something_went_wrong!');
        }
        echo json_encode($response);
    }

    public function audience_link_to_mailchimp($id){
        $audience = get_audience_details($id);
        if($audience){
            $param = array(
                'id' => $audience->id,
                'name' => $audience->name,
                'company' => $audience->company,
                'address' => $audience->address,
                'city' => $audience->city,
                'state' => $audience->state,
                'zip' => $audience->zip,
                'phone' => $audience->phone,
                'country' => $audience->country,
                'from_name' => $audience->from_name,
                'from_email' => $audience->from_email,
                'mailchimp_id' => $audience->mailchimp_id,
            );
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->create_or_update_mailchimp_audience($param);
            $result = json_decode($result);
            
            if($result->status == 'success'){
                if(!empty($result->mailchimp_id)){
                    $param['mailchimp_id'] = $result->mailchimp_id;  
                    update_mailchimp_audience_data($param);  
                }
                set_alert('success', $result->message);   
            }else{
                set_alert('error', _l('Invalid audience id'));
            }    
       }else{
           set_alert('error', _l('Invalid audience id'));
       }
       redirect(admin_url('mailchimp/audience'));
    }

    public function make_default_audience()
    {
        $id = $this->input->post('id');
        $result = $this->mailchimp_audience_model->make_default($id);
        if($result){

            $response['status'] = 'success';
            $response['message'] = _l('Default_Audience_set_successfully');
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Something_went_wrong!');
        }
        echo json_encode($response);
    }

    //Audience member custom field add to mailchim 08-08-2022
    public function audience_merge_field($audience_id=''){
        $mailchimpApi = new MailchimpAPIWrapper;
        $result = $mailchimpApi->get_mailchimp_audience_list();
        $result = json_decode($result);
        if($result->status == 'success'){
            foreach($result->list as $k=>$mailchimp){
                if($mailchimp->id == 'ef270a49be'){
                    $field1 = array(
                        "name" => "Customer Id",
                        "type" => "text",
                        'tag' => "CUSTOMERID"
                    );
                    
                    $field2 = array(
                        "name" => "Company",
                        "type" => "text",
                        'tag' => "COMPANY"
                    );
                    $result2 = $mailchimpApi->create_merge_field($field2,$mailchimp->id);
                    $field3 = array(
                        "name" => "Position",
                        "type" => "text",
                        'tag' => "POSITION"
                    );
                    $result3 = $mailchimpApi->create_merge_field($field3,$mailchimp->id); 
                }
            }
            $response['status'] = 'success';
            $response['message'] = 'Audience_merge_fields_successfully';
        }else{
            $response['status'] = 'failure';
             $response['message'] = _l('Something_went_wrong!');
        }
        return json_encode($response);
    }

    //Audiens sync from Mailchimp to JM
    public function audience_sync(){
        $mailchimpApi = new MailchimpAPIWrapper;
        $result = $mailchimpApi->get_mailchimp_audience_list();
        $result = json_decode($result);
        $new_audience = 0;
        $updated_audience = 0;
        if($result->status == 'success'){
            $list = array();
            foreach($result->list as $k=>$mailchimp){
                $param = array(
                    'name' => $mailchimp->name,
                    'company' => $mailchimp->contact->company,
                    'address' => $mailchimp->contact->address1,
                    'city' => $mailchimp->contact->city,
                    'state' => $mailchimp->contact->state,
                    'zip' => $mailchimp->contact->zip,
                    'phone' => $mailchimp->contact->phone,
                    'country' => $mailchimp->contact->country,
                    'from_name' => $mailchimp->campaign_defaults->from_name,
                    'from_email' => $mailchimp->campaign_defaults->from_email,
                    'from_subject' => $mailchimp->campaign_defaults->subject,
                    'mailchimp_id' => $mailchimp->id,
                );
                $this->mailchimp_audience_model->audience_merge_field($mailchimp->id); 
                $audience = isExistAudience($mailchimp->name,$mailchimp->id);
                if($audience){
                    $param['id'] = $audience->id;
                    $update = update_mailchimp_audience_data($param);
                    if($update){
                        $updated_audience++;
                    }
                }else{
                    $insert = add_mailchimp_audience_data($param);   
                    if($insert){
                        $new_audience ++;
                    }
                }
            }
                $response['new_audience'] = $new_audience;
                $response['updated_audience'] = $updated_audience;
                $response['status'] = 'success';
                $response['message'] = 'Total '.($new_audience + $updated_audience). '  audience data synced successfully.';
        }else{
            $response['status'] = 'failure';
             $response['message'] = _l('Something went wrong!');
        }
        echo json_encode($response);
    }

    //Member sync from Mailchimp to JM
    public function all_member_sync_mc_to_jm(){
        
        $assigned_audience_list = array();
        if(empty($assigned_audience_list)){
            $assigned_audience_list[0] = array('mailchimp_audience_id'=> get_default_mailchimp_audience_has_id());
        }
        $mailchimpApi = new MailchimpAPIWrapper;
        
        $limit = 500;
        if(empty($limit)){
            $limit = 10;
        }
        $new_contact = 0;
        $updated_contact = 0;
        $all_list_sync_info = array();
        foreach($assigned_audience_list as $key=>$audience_list){
            $new_contact = 0;
            $updated_contact = 0;
            $offset = 0;
            $total = 0;
            for($i=0; $i <1000 ; $i++) { 
                $result = $mailchimpApi->get_mailchimp_member_list($offset, $limit,$audience_list['mailchimp_audience_id']);
                $result = json_decode($result);
                if($result->status == 'success' && $result->count>0 && !empty($result->list)){
                    $offset = $offset + $limit;
                    $total= $total+count($result->list);
                    //Sync data to jm
                    foreach($result->list as $k=>$mailchimp){
                        $param = array(
                            'firstname' => $mailchimp->merge_fields->FNAME,
                            'lastname' => $mailchimp->merge_fields->LNAME,
                            'email' => $mailchimp->email_address,
                            'phonenumber' => !empty($mailchimp->merge_fields->PHONE)?$mailchimp->merge_fields->PHONE:'',
                            'title' => $mailchimp->merge_fields->POSITION,
                        );
                        $existingContact = isContactEmailExist($mailchimp->email_address);
                        if(!empty($existingContact)){
                            $this->db->where(['id'=> $existingContact->id]); 
                            $update = $this->db->update(db_prefix() . 'contacts',  $param);
                            if($update){
                                $updated_contact++;
                                $update_con_aud = update_contact_audience($existingContact->id,$mailchimp->list_id,$mailchimp->status);
                            }
                        }else{
                            $new_param = array(
                                'firstname' => $mailchimp->merge_fields->FNAME,
                                'lastname' => $mailchimp->merge_fields->LNAME,
                                'email' => $mailchimp->email_address,
                                'phonenumber' => !empty($mailchimp->merge_fields->PHONE)?$mailchimp->merge_fields->PHONE:'',
                                'company' => $mailchimp->merge_fields->COMPANY,
                                'password' => '',
                            );
                            $this->ci->db->where(['company' => $mailchimp->merge_fields->COMPANY]);
                            $exitsClient = $this->ci->db->get(db_prefix() . 'clients')->row();
                            if(!empty( $exitsClient) && !empty($mailchimp->merge_fields->COMPANY)){
                                unset($new_param['company']);
                                $insert_new_contact = $this->clients_model->add_contact($new_param,$exitsClient->userid,true);  
                                if($insert_new_contact){
                                    $new_contact ++;
                                    $update_con_aud = update_contact_audience($insert_new_contact,$mailchimp->list_id,$mailchimp->status);
                                } 

                            }else{
                                $new_client = $this->clients_model->add($new_param,true);  
                                if($new_client){
                                    $new_contact ++;
                                } 
                            }
                            

                        }
                    }
                }else{
                    break;
                }
            }
            $all_list_sync_info[$key] = array('audience_id'=>$audience_list['mailchimp_audience_id'],'new_contact'=>$new_contact,'updated_contact' => $updated_contact);
        }
        $response['new_contact'] = $new_contact;
        $response['updated_contact'] = $updated_contact;
        $response['all_list_sync_info'] = $all_list_sync_info;
        $response['status'] = 'success';
        
        $message = '';
        if(is_array($all_list_sync_info)){
            foreach ($all_list_sync_info as $info) {
                $audience_name = get_audience_name_by_mailchimp_id($info['audience_id']);
                $message .= 'Total '.($info['new_contact'] + $info['updated_contact']). '  contact data synced from '.$audience_name.'<br>';
            }
        }
        $response['message'] = $message;
        echo json_encode($response);
    }

    //Member sync from JM to MC(Link to mailchimp) 26-08-2022
    public function all_member_sync_jm_to_mc(){
        $limit = get_limit_for_sync_jm_to_mc();
        if(empty($limit)){
            $limit = 10;
        }
        
        $contact_list = get_contact_list('',$limit);
        
        $link_contact = 0;
        $fails_contact = 0;
        $log_array = array();
        if($contact_list && !empty($contact_list)){
            foreach($contact_list as $key=>$contact){
                $mailchimp_param = array(
                    "firstname" => $contact['firstname'],
                    "lastname" => $contact['lastname'],
                    "email" => $contact['email'],
                    "phone" => $contact['phonenumber'],
                    "position" => $contact['title'],
                    "customer_id" => $contact['userid'],
                    "contact_id" => $contact['id'],
                );
                $audience_ids = get_contact_assigned_audience_ids($contact['id']);
                if(empty($contact->mailchimp_audience_id)){
                   $audience_ids = array(get_default_mailchimp_audience_has_id());

                }
                $mailchimpApi = new MailchimpAPIWrapper;
                $sync_status = 'no';
                foreach( $audience_ids as $key=>$value){
                    $mailchimp_param['mailchimp_audience_id'] = $value;
                    $contact_audience_details = get_contact_assigned_audience_details($contact['id'],$value);
                    if(!empty($contact_audience_details)){
                        $mailchimp_param['status'] = $contact_audience_details->mailchimp_subscribed_status;
                    }else{
                        $mailchimp_param['status'] = 'subscribed';
                    }
                    $result = $mailchimpApi->create_update_mailchimp_member($mailchimp_param );
                    $result = json_decode($result);
                    if($result->status == 'success'){
                        $update_con_aud = update_contact_audience($contact['id'],$result->list_id,$result->mailchimp_status);
                        $sync_status = 'yes';
                    }else{
                        $sync_status = 'no';
                    }
                    //Log for developer to check success or failure
                    $log = array(
                        'contact_id' =>  $contact['id'],
                        'message' => $result->message,
                    );
                    array_push($log_array, $log);
                }
                if($sync_status == 'yes'){
                    $link_contact++;
                }else{
                   $fails_contact++; 
                }
            }
            $response['status'] = 'success';
            $response['message'] = 'Total '.$link_contact.' contact sync to mailchimp.Failed '.$fails_contact.' contact.';
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('0 contact selected for Sync!');  
        }
        
        $response['log_array'] = $log_array;
        echo json_encode($response);
    }

    public function get_contact_audience_selected_options($id){
        $contact      = $this->mailchimp_audience_model->get_contact_details($id);
        if(!empty($contact)){
            $contact_audience_list  = get_contact_assigned_all_audience_list($contact->id);
            $html = '';
            if(!empty($contact_audience_list)){
                foreach($contact_audience_list as $key=>$list){
                    $mailchimpApi = new MailchimpAPIWrapper;
                    $result = $mailchimpApi->get_member_info(md5($contact->email),$list['mailchimp_audience_id']);
                    $result=json_decode($result);
                    if(!empty($result) && $result->status == 'success' && !empty($result->list)){
                        $latest_status = $result->list->status;
                        $checked =  $latest_status == 'subscribed'?'checked':'';
                    }else{
                       $checked = ''; 
                    }
                    
                    $html .= '<div class="row">
                        <div class="col-md-6 mtop35 border-right">
                            <span>'.$list['name'].'</span>
                        </div>
                        <div class="col-md-6 mtop35">
                            <div class="onoffswitch">
                                <input type="checkbox" id="'.$list['mailchimp_audience_id'].'" class="onoffswitch-checkbox contact_subscribe_btn" '.$checked.' value="'.$list['mailchimp_audience_id'].'" name="subscribed_status[]">
                                <label class="onoffswitch-label" for="'.$list['mailchimp_audience_id'].'"></label>
                            </div>
                        </div>
                    </div>';
                }
            }
            $response['status'] = 'success';
            $response['message'] = 'Data found';
            $response['content'] =  $html;
        }else{
            $response['status'] = 'failure';
            $response['message'] = 'data not found';
        }
        echo json_encode($response);
    }

    public function get_subscribed_checkbox_by_select_option(){
        $contact_id = $this->input->post('contact_id');
        $audience_ids = $this->input->post('audience_ids');
        $subscribed_status = $this->input->post('subscribed_status');
        $unsubscribed_status = $this->input->post('unsubscribed_status');
        $html = '';
        $contact_audience_ids  = get_contact_assigned_audience_ids($contact_id);
        $audience_list  = $this->mailchimp_audience_model->get_audience_list();
        if(!empty($audience_list)){
            if(empty($audience_ids)){
                $audience_ids = array();
            }
            if(empty($contact_audience_ids)){
                $contact_audience_ids = array();
            }
            foreach($audience_list as $key=>$list){
                if(in_array($list['mailchimp_id'],$audience_ids) || in_array($list['mailchimp_id'],$contact_audience_ids)){
                    if(is_array($subscribed_status) && in_array($list['mailchimp_id'],$subscribed_status)){
                        $checked = 'checked';
                        $title = "Unsubscribe";
                    }elseif(is_array($unsubscribed_status) && in_array($list['mailchimp_id'],$unsubscribed_status)){
                        $checked = '';
                        $title = "Subscribe";
                    }else{
                        $checked = 'checked';
                        $title = "Unsubscribe";
                    }
                    if(!empty($contact_id)){
                        $contact      = $this->mailchimp_audience_model->get_contact_details($contact_id);
                        $mailchimpApi = new MailchimpAPIWrapper;
                        $result = $mailchimpApi->get_member_info(md5($contact->email),$list['mailchimp_id']);
                        $result=json_decode($result);
                        $activity = $mailchimpApi->get_member_activity_feed(md5($contact->email),$list['mailchimp_id']);
                        $activity=json_decode($activity);
                    }else{
                       $result = ''; 
                       $activity = '';
                    }
                    $latest_html = '';
                    $latest_unsubscribe_html = '';
                    $disable = '';
                    if(!empty($activity) && $activity->status == 'success' && in_array($list['mailchimp_id'],$contact_audience_ids)){
                        if(!empty( $activity->list)){
                            foreach($activity->list as $a_key=>$a_value){
                                if(isset($a_value->activity_type) && $a_value->activity_type == 'unsub'){
                                    $unsubscribed_date = $a_value->created_at_timestamp;
                                    $latest_unsubscribe_html = '<div class="col-md-5 mtop10 contact-mailchimp-info" style="margin-left:10px">
                                        <span>Unsubscribed on '.date('Y-m-d H:i A',strtotime($unsubscribed_date)).'</span>
                                    </div>';
                                    break;
                                }
                            }
                        }
                          
                    }
                    if(!empty($result) && $result->status == 'success' && in_array($list['mailchimp_id'],$contact_audience_ids)){
                        if(!empty($result->list)){
                            $latest_status = $result->list->status;
                            $subscribed_date = $result->list->timestamp_opt;
                            $last_changed = $result->list->last_changed;
                            $checked = $latest_status == 'subscribed'?'checked':'';
                            $title = $latest_status  == 'subscribed'?'Unsubscribe':'Subscribe';
                            $disable = $checked == ''?'mc-subscribed-disable':'';
                            $latest_html = '<div class="col-md-5 mtop10 contact-mailchimp-info" style="margin-left:10px">
                                <span>Subscribed on '.date('Y-m-d H:i A',strtotime($subscribed_date)).'</span>
                            </div>';
                            if(!empty($latest_unsubscribe_html)){
                                $latest_html  = $latest_unsubscribe_html;
                            }
                        }
                          
                    }
                    $html .= '<div class="row">
                            <div class="col-md-5 mtop10 border-right">
                                <span>'.$list['name'].'</span>
                            </div>
                            <div class="col-md-1 mtop10">
                                <div class="onoffswitch '. $disable.'" data-toggle="tooltip" title="'.$title.'">
                                    <input type="checkbox" id="'.$list['mailchimp_id'].'" class="onoffswitch-checkbox" '.$checked.' value="'.$list['mailchimp_id'].'" name="mailchimp_subscribed_status[]">
                                    <label class="onoffswitch-label" for="'.$list['mailchimp_id'].'"></label>
                                </div>
                            </div>
                            '.$latest_html.'
                        </div>';

                }
            }
        }
        $response['status'] = 'success';
        $response['message'] = 'Data found';
        $response['content'] =  $html;
        echo json_encode($response);
    }

    public function member_subscribe_unsubscribe(){

        $id = $this->input->post('contact_id');
        $subscribed_ids = $this->input->post('status');
        $mailchimp_aud_id = $this->input->post('mailchimp_aud_id');

        $contact      = $this->mailchimp_audience_model->get_contact_details($id);
        $contact_audience_list  = get_contact_assigned_audience_list($contact->id);
        $audience_list_details  = get_audience_details_by_mailchimp_id($mailchimp_aud_id);

        if($contact){
            $mailchimpApi = new MailchimpAPIWrapper;

            if (!empty($audience_list_details)) {

                if(is_array($subscribed_ids) && in_array($mailchimp_aud_id, $subscribed_ids)){
                    $mailchimp_status = 'subscribed';
                }else{
                    $mailchimp_status = 'unsubscribed';
                }

                
                $mailchimp_param = array(
                    "firstname" => $contact->firstname,
                    "lastname" => $contact->lastname,
                    "email" => $contact->email,
                    "phone" => $contact->phonenumber,
                    "position" => isset($contact->title) ? $contact->title : '',
                    "customer_id" => $contact->userid,
                    "contact_id" => $contact->id,
                    "mailchimp_contact_id" => $contact->id
                );
                $mailchimp_param['mailchimp_audience_id'] = $mailchimp_aud_id;
                $mailchimp_param['status'] = $mailchimp_status;

                $result = $mailchimpApi->create_update_mailchimp_member($mailchimp_param );

                $result = json_decode($result);
                if($result->status == 'success'){
                    $update_con_aud = update_contact_audience($id,$mailchimp_aud_id,$mailchimp_status);
                    $response['status'] = 'success';
                    $response['message'] = _l('Subscribe status updated successfully');
                }else{
                    $response['status'] = 'failure';
                    $response['message'] = _l('Something went wrong!');
                }
                
            }else{
              $response['status'] = 'failure';
              $response['message'] = _l('Contact have not link to mailchimp!');  
            }
           
            echo json_encode($response);
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Invalid contact id');
        }
    }

    /*****************Mailchimp Store CRUD start here 17-08-2022***********************/
    public function stores()
    {
        $data['title']= _l('mailchimp_stores');
        $this->db->order_by('id','DESC');
        $data['list'] = $this->db->get(db_prefix().'mailchimp_stores')->result_array();
        $this->db->order_by('id','DESC');
        $data['audience_list'] = $this->db->get(db_prefix().'mailchimp_audience')->result_array();
        $this->load->view('mailchimp_stores', $data);

    }

    public function add_stores()
    {
        $post_data = $this->input->post();
        $id = $this->input->post('id');

        if($id){
            $result = $this->mailchimp_store_model->update($post_data);
            $response['message'] = _l('Store_updated_successfully');
        }else{
            $result = $this->mailchimp_store_model->add($post_data);
            $response['message'] = _l('Store_added_successfully');
        }
        if($result){
            $response['status'] = 'success';
            
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Something_went_wrong!');
        }
        echo json_encode($response);
    }

    public function get_store_data(){
        $id = $this->input->post('id');
        if($id){
            $list = $this->mailchimp_store_model->get_store_data($id);
            if($list){
                $response['title'] = _l('edit_store');
                $response['store'] = $list[0];
                $response['status'] = 'success';
            }
        }else{
            $response['status'] = 'failure';
             $response['message'] = _l('Something went wrong!');
        }
        echo json_encode($response);
    }

    public function store_link_to_mailchimp($id){
        $store = get_store_details($id);
        if($store){
            $param = array(
                'id' => $store->id,
                'name' => $store->name,
                'mailchimp_audience_id' => $store->mailchimp_audience_id,
            );
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->create_mailchimp_store($param);
            $result = json_decode($result);
            
            if($result->status == 'success'){
              if(!empty($result->mailchimp_id)){
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'mailchimp_stores', [
                    'mailchimp_store_id' => $result->mailchimp_id,
                    'updated_at'=>date('Y-m-d H:i:s')
                ]);
                set_alert('success', 'Store_linked_to_mailchimp_successfully');  
              }else{
                set_alert('error', _l('Something_went_wrong'));
              }
               
            }else{
                set_alert('error', _l('Invalid store id'));
            }    
       }else{
           set_alert('error', _l('Invalid store id'));
       }
       redirect(admin_url('mailchimp/stores'));
    }

    //Stores sync from Mailchimp to JM
    public function store_sync(){
        $mailchimpApi = new MailchimpAPIWrapper;
        $result = $mailchimpApi->get_mailchimp_store_list();
        $result = json_decode($result);
        $new_store = 0;
        $updated_store = 0;
        if($result->status == 'success'){
            $list = array();
            foreach($result->list as $k=>$mailchimp){
                $param = array(
                    'name' => $mailchimp->name,
                    'mailchimp_store_id' => $mailchimp->id,
                    'mailchimp_audience_id' => $mailchimp->list_id,
                    'updated_at' => date('Y-m-d-H-i-s'),
                );
                $this->db->where('mailchimp_store_id', $mailchimp->id);
                $existingStore = $this->db->get(db_prefix() . 'mailchimp_stores')->row();
                if($existingStore ){
                    $this->db->where('id', $existingStore->id);
                    $this->db->update(db_prefix() . 'mailchimp_stores', $param);
                    if ($this->db->affected_rows() > 0) {
                         $updated_store ++;
                    }  
                }else{
                    $this->db->insert(db_prefix() . 'mailchimp_stores', $param);
                    $insert_id = $this->db->insert_id();
                    if($insert_id){
                        $new_store ++;
                    }
                }
            }
            $response['new_store'] = $new_store;
            $response['updated_store'] = $updated_store;
            $response['status'] = 'success';
            $response['message'] = 'Total '.($new_store + $updated_store). '  store data synced successfully.';
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Something went wrong!');
        }
        echo json_encode($response);
    }

    public function delete_store()
    {
        $id = $this->input->post('id');
        $delete = $this->mailchimp_store_model->delete($id);
        if($delete){
            $response['status'] = 'success';
            $response['message'] = _l('Store_deleted_successfully');
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Something went wrong!');
        }
        echo json_encode($response);
    }

    public function make_default_store()
    {
        $id = $this->input->post('id');
        $result = $this->mailchimp_store_model->make_default($id);
        if($result){

            $response['status'] = 'success';
            $response['message'] = _l('Default Store set successfully');
        }else{
            $response['status'] = 'failure';
            $response['message'] = _l('Something went wrong!');
        }
        echo json_encode($response);
    }

    //Store functionality end here 18-08-2022
    public function turnoff($id, $active = 0){
        $postData = [
            'is_active' => $active
        ];
        $this->db->update( db_prefix().'mailchimp',$postData);
    }

    public function get_contact_info($contact_id)
    {
        $contact      = $this->mailchimp_audience_model->get_contact_details($contact_id);
        $current_data = array();
        if(empty($contact_id) || empty($contact)){
            $response['status'] = 'failure';
            $response['mailchimp'] = $current_data;
            echo json_encode($response);
        }
        $contact_audience_list  = get_contact_assigned_audience_list($contact->id);
        foreach($contact_audience_list as $audience){
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->get_member_info(md5($contact->email),$audience['mailchimp_audience_id']);
            $result=json_decode($result);
            if(!empty($result) && $result->status == 'success'){
                $current_data[]=$result->list;
            }
        }
        $response['status'] = 'success';
        $response['mailchimp'] = $current_data;
        echo json_encode($response);
    }

    public function get_member_activity_feed($contact_id){
        $contact      = $this->mailchimp_audience_model->get_contact_details($contact_id);
        $current_data = array();
        if(empty($contact_id) || empty($contact)){
            $response['status'] = 'failure';
            $response['list'] = $current_data;
            echo json_encode($response);
        }
        $contact_audience_list  = get_contact_assigned_audience_list($contact->id);
        foreach($contact_audience_list as $audience){
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->get_member_activity_feed(md5($contact->email),$audience['mailchimp_audience_id']);
            $result=json_decode($result);
            if(!empty($result) && $result->status == 'success'){
                $current_data[]=$result->list;
            }
        }
        $response['status'] = 'success';
        $response['list'] = $current_data;
        echo json_encode($response);
    }

    /*************************New Integration Contact 17-october-2022**********************************/
    //Mailchimp Contact Page(Sidebar menu)
    public function contacts()
    {
        $data['title']= _l('Mailchimp Contact');
        if ($this->input->is_ajax_request()) {
            $this->get_table_data('all_contacts');
        }

        if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') {
            $this->load->model('gdpr_model');
            $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
        }
        $this->load->view('mailchimp_all_contact', $data);

    }

    public function get_table_data($table, $params = []){
        $params = hooks()->apply_filters('table_params', $params, $table);

        foreach ($params as $key => $val) {
            $$key = $val;
        }

        $customFieldsColumns = [];

        $path = FCPATH.'/modules/'.MAILCHIMP_MODULE_NAME.'/views/tables/' . $table . EXT;
        if (!file_exists($path)) {
            $path = $table;
            if (!endsWith($path, EXT)) {
                $path .= EXT;
            }
        } else {
            $myPrefixedPath = VIEWPATH . 'admin/tables/my_' . $table . EXT;
            if (file_exists($myPrefixedPath)) {
                $path = $myPrefixedPath;
            }
        }
        include_once($path);
        echo json_encode($output);
        die;
    }

    public function link_contact_to_mailchimp($id)
    {
        $contact = $this->mailchimp_audience_model->get_contact_details($id);
        if($contact){
            $mailchimp_param = array(
                "firstname" => $contact->firstname,
                "lastname" => $contact->lastname,
                "email" => $contact->email,
                "phone" => $contact->phonenumber,
                "position" => $contact->title,
                "customer_id" => $contact->userid,
                "contact_id" => $contact->id,
                'status' => 'subscribed'
            );
            $audience_ids = get_contact_assigned_audience_ids($contact->id);
            if(empty($audience_ids)){
               $audience_ids = array(get_default_mailchimp_audience_has_id());

            }
            $mailchimpApi = new MailchimpAPIWrapper;
            if(is_array($audience_ids)){
                foreach( $audience_ids as $key=>$value){
                    $mailchimp_param['mailchimp_audience_id'] = $value;
                    $contact_audience_details = get_contact_assigned_audience_details($contact->id,$value);
                    if(!empty($contact_audience_details)){
                        $mailchimp_param['status'] = $contact_audience_details->mailchimp_subscribed_status;
                    }else{
                        $mailchimp_param['status'] = 'subscribed';
                    }
                    $result = $mailchimpApi->create_update_mailchimp_member($mailchimp_param );
                    $result = json_decode($result);
                    if($result->status == 'success'){
                        if(!empty($result->mailchimp_id)){
                            $update_con_aud = update_contact_audience($contact->id,$result->list_id,$result->mailchimp_status);
                        }
                        set_alert('success', _l('contact_added_success_msg', _l('contact')));
                    }else{
                        set_alert('danger',$result->message, _l('contact'));
                    }
                }
            }else{
                set_alert('danger','Please set default audience!', _l('contact'));
            }
        }else{
            set_alert('danger','Invalid contact!', _l('contact'));
        }
        redirect(admin_url('mailchimp/contacts'));
    }

    public function link_estimate_to_mailchimp($id)
    {
        $this->db->where('id', $id);
        $estimate = $this->db->get(db_prefix() . 'estimates')->row();
        $output= 0;
        if(!get_default_mailchimp_store_has_id()){
            set_alert('danger', 'Plese set default store first');
            redirect(admin_url('estimates'));  
        }
        if(isExistMailChimp()){
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->create_mailchimp_order($id);
            $result = json_decode($result);
            
            if($result->status == 'success'){
              if(!empty($result->mailchimp_id)){
                $this->db->reset_query();
                $store_id = get_audience_id_of_store($result->store_id);
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'estimates', [
                    'mailchimp_estimate_id' => $result->mailchimp_id,
                    'mailchimp_audience_id' => $store_id,
                ]); 
              }
              $output = 1; 
              $message = _l('Added_successfully_to_mailchimp', _l('estimate')); 
            }else{
               $message = $result->message; 
            }
        }else{
           $message = _l('Problem adding to mailchimp');
        }
        if ($output == 1) {
            set_alert('success', $message);
        } else {
            set_alert('danger', $message);
        }
        redirect(admin_url('estimates'));
    }

    /*********************************Activity Logs for error checking***************/
    public function getActivityLog(){
         $mailchimpWrapper = new MailchimpAPIWrapper();
         $result = $mailchimpWrapper->getActivityLog();
         print_r($result);
    }
    
    //Test delete mailchimp id from Clients,contact,estimate
    public function deleteDemoData(){
        $this->db->query("UPDATE ".db_prefix()."contacts SET mailchimp_contact_id=''"); 
        $this->db->query("UPDATE ".db_prefix()."clients SET mailchimp_customer_id=''");
        $this->db->query("UPDATE ".db_prefix()."estimates SET mailchimp_estimate_id=''");
        echo"Remove mailchimp id from clients,contact and estimate table" ;
    }
    
}



