<?php
require_once(APP_MODULES_PATH.'/mailchimp/third_party/mailchimp-marketing/vendor/autoload.php');
class MailchimpAPIWrapper {

    public function __construct() {
        $this->ci = &get_instance();
        $this->ci->load->helper('mailchimp');
        $this->api_key = get_mailchimp_api_key();
        $this->list_id = get_default_mailchimp_audience_has_id();
        $this->store_id = get_default_mailchimp_store_has_id();
        $this->server_prefix = get_mailchimp_server_prefix();
    }

    public function get_mailchimp_audience_list(){

        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        $fields ='lists.id,lists.name,lists.contact,lists.campaign_defaults';
        try {
            $result = $client->lists->getAllLists($fields);
            if(isset($result->lists) && !empty($result->lists)){
                $response['status'] = 'success';
                $response['list'] = $result->lists;
                $response['message'] = 'Data found';
            }else{
                $response['status'] = 'failure';
                 $response['list'] = $result;
                $response['message'] = 'Data not found';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'get audience list';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function create_or_update_mailchimp_audience($data){

        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        $param = array(
            "name" => $data['name'],
            "permission_reminder" => "permission_reminder",
            "email_type_option" => false,
            "contact" => [
              "company" => $data['company'],
              "address1" => $data['address'],
              "city" => $data['city'],
              "state" => $data['state'],
              "zip" => $data['zip'],
              "country" => $data['country'],
            ],
            "campaign_defaults" => [
              "from_name" => $data['from_name'],
              "from_email" => $data['from_email'],
              "subject" => isset($data['subject'])?$data['subject']:"Job mgt mailchimp",
              "language" => "EN_US",
            ],
        );
        if(isset($data['mailchimp_id']) && !empty($data['mailchimp_id'])){
            $mailchimp_id = $data['mailchimp_id'];
        }else{
            $mailchimp_id = '';
        }
        try{
          if(empty($mailchimp_id)){
            $result = $client->lists->createList($param);
            $message = _l('audience_add_success_msg');
          }else{
            $result = $client->lists->updateList($mailchimp_id,$param);
            $message = _l('audience_update_success_msg');
          }
          if(isset($result->id) && !empty($result->id)){
            $response['status'] = 'success';
            $response['mailchimp_id'] = $result->id;
            $response['message'] = $message;
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Something went wrong!';
         }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        if($mailchimp_id && !empty($mailchimp_id)){
            $response['action'] = 'update audience';
        }else{
           $response['action'] = 'create audience'; 
        }
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_mailchimp_audience_info($mailchimp_id){

        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        $fields ='id,name,contact,campaign_defaults';
        try{
          $result = $client->lists->getList($mailchimp_id,$fields);
          if(isset($result->id) && !empty($result->id)){
            $response['status'] = 'success';
            $response['list'] = $result;
            $response['message'] = 'Data found';
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Data not found';
         }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'get audience info';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function delete_mailchimp_audience($mailchimp_id){

        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);

        try{
          $result = $client->lists->deleteList($mailchimp_id);
          $response['status'] = 'success';
          $response['message'] = 'Audience deleted successfully';
          $response['id'] = $mailchimp_id;
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'delete audience ';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }

    //Mailchimp Members api integration start here 01-08-2022
    public function create_update_mailchimp_member($data)
    {
        //Get customer details
        $this->ci->db->where('userid',$data['customer_id']);
        $client = $this->ci->db->get(db_prefix() . 'clients')->row();

        $address = array(
            "addr1" => !empty($client->address)?$client->address:'',
            "addr2" => !empty($client->address2)?$client->address2:'',
            "city" =>  !empty($client->city)?$client->city:'',
            "state" => !empty($client->state)?$client->state:'',
            "zip" =>   !empty($client->zip)?$client->zip:'',
            "country" => $client?get_country_name($client->country):'',
            "country_code" => $client?get_country_short_name($client->country):'',
        );
        $merge_fields = array(
            "FNAME" => !empty($data['firstname'])?$data['firstname']:'',
            "LNAME" =>!empty($data['lastname'])?$data['lastname']:'',
            "PHONE" => !empty($data['phone'])?$data['phone']:'',
            "ADDRESS" => $address,
            "POSITION" => !empty($data['position'])?$data['position']:'',
            "COMPANY" => get_company_name($data['customer_id']),
            
        );
        if(empty($address['addr1']) || empty($address['city']) || empty($address['state']) || empty($address['zip']) || empty($address['country']) || empty($address['country_code'])){
            unset($merge_fields['ADDRESS']);
        }
        
        $param =array(
            "email_address" => $data['email'],
            "skip_merge_validation" => true,
            
        );
        if(isset($data['status'])){
            $param['status'] = $data['status'];
        }else{
            $param['status'] = 'unsubscribed';
        }
        $param['merge_fields'] =  $merge_fields;
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        $has_contact_id = md5($data['email']);
        
        if(isset($data['mailchimp_audience_id']) && !empty($data['mailchimp_audience_id'])){
            $list_id = $data['mailchimp_audience_id'];
        }else{
            $list_id = $this->list_id;
        }
        try{
          $result = $client->lists->setListMember($list_id, $has_contact_id,$param);  
          $message = _l('contact_add_success_msg');
          if(isset($result->contact_id) && !empty($result->contact_id)){
            //add or update tags
            $tags = get_customer_tags_names($data['customer_id']);
            if(!empty($tags)){
               $this->assign_mailchimp_member_tags($list_id, $has_contact_id,$tags);
            }
            $response['status'] = 'success';
            $response['mailchimp_id'] = $result->contact_id;
            $response['mailchimp_status'] = $result->status;
            $response['list_id'] = $list_id;
            $response['message'] = $message;
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Something went wrong!';
          }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $second_message = '';
            if(is_object($mailchimp_error)){
                if(isset($mailchimp_error->errors)){
                    $second_message  .= $mailchimp_error->errors[0]->field." ";    
                    $second_message  = $mailchimp_error->errors[0]->message;
                }
            }
            $response['message'] = $mailchimp_error->detail." ".$second_message;
            
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }

        $response['action'] = 'create update member';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function update_mailchimp_member($data)
    {
        //Get customer details
        $this->ci->db->where('userid',$data['customer_id']);
        $client = $this->ci->db->get(db_prefix() . 'clients')->row();
        $address = array(
            "addr1" => !empty($client->address)?$client->address:'',
            "addr2" => !empty($client->address2)?$client->address2:'',
            "city" =>  !empty($client->city)?$client->city:'',
            "state" => !empty($client->state)?$client->state:'',
            "zip" =>   !empty($client->zip)?$client->zip:'',
            "country" => $client?get_country_name($client->country):'',
            "country_code" => $client?get_country_short_name($client->country):'',
        );
        $merge_fields = array(
            "FNAME" => $data['firstname'],
            "LNAME" => $data['lastname'],
            "PHONE" => $data['phone'],
            "ADDRESS" => $address,
            "POSITION" => $data['position'],
            "COMPANY" => get_company_name($data['customer_id']),
            
        );
        
        if(empty($address['addr1']) || empty($address['city']) || empty($address['state']) || empty($address['zip']) || empty($address['country']) || empty($address['country_code'])){
            unset($merge_fields['ADDRESS']);
        }
        $param =array(
            "email_address" => $data['email'],
            "skip_merge_validation" => true,
           
        );
        if(isset($data['status'])){
            $param['status'] = $data['status'];
        }else{
            $param['status'] = 'unsubscribed';
        }
        $param['merge_fields'] =  $merge_fields;
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        if(isset($data['mailchimp_audience_id']) && !empty($data['mailchimp_audience_id'])){
            $list_id = $data['mailchimp_audience_id'];
        }else{
            $list_id = $this->list_id;
        }
        try{
            $result = $client->lists->setListMember($list_id,$data['mailchimp_contact_id'],$param);  
          
            $message = _l('contact_updated_success_msg');
            if(isset($result->contact_id) && !empty($result->contact_id)){
                //add or update tags
                $tags = get_customer_tags_names($data['customer_id']);
                if(!empty($tags)){
                   $this->assign_mailchimp_member_tags($list_id, $result->contact_id,$tags);
                }
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->contact_id;
                $response['list_id'] = $list_id;
                $response['mailchimp_status'] = $result->status;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $second_message = '';
            if(is_object($mailchimp_error)){
                if(isset($mailchimp_error->errors)){
                    $second_message  .= $mailchimp_error->errors[0]->field." ";    
                    $second_message  = $mailchimp_error->errors[0]->message;
                }
            }
            $response['message'] = $mailchimp_error->detail." ".$second_message;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'update member';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }

    public function update_mailchimp_member_status($email,$status,$list_id)
    {
        $param =array(
            "email_address" => $email,
            "status" => $status,
        );
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{ 
            $result = $client->lists->updateListMember($list_id,md5($email),$param);  
            $message = _l('contact_updated_success_msg');
            if(isset($result->contact_id) && !empty($result->contact_id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->contact_id;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'update member status';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_member_info($contact_id,$list_id=''){
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        
        $fields='id,contact_id,status,email_address,ip_opt,timestamp_opt,last_changed,consents_to_one_to_one_messaging,timestamp_signup,merge_fields';
        try{
            if(empty($list_id)){
               $list_id =  $this->list_id;
            } 
          $result = $client->lists->getListMember($list_id,$contact_id,$fields);
          if(isset($result->id) && !empty($result->id)){
            $response['status'] = 'success';
            $response['list'] = $result;
            $response['message'] = 'Data found';
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Data not found';
         }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'member info';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_mailchimp_member_list($offset='',$limit='',$list_id=''){
        if(empty($offset)){
            $offset=0;
        }
        if(empty($limit)){
            $limit=1000;
        }
        if(empty($list_id)){
            $list_id=$this->list_id;
        }
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        $fields ='members.id,members.email_address,members.contact_id,members.status,members.merge_fields,members.list_id';
        try {
            $result = $client->lists->getListMembersInfo($list_id,$fields,'',$limit,$offset);
            if(isset($result->members) && !empty($result->members)){
                $response['status'] = 'success';
                $response['list'] = $result->members;
                $response['count'] = count($result->members);
                $response['message'] = 'Data found';
            }else{
                $response['status'] = 'failure';
                $response['list'] = '';
                $response['message'] = 'Data not found';
                $response['count'] = 0;
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
            $response['count'] = 0;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
            $response['count'] = 0;
        }
        $response['action'] = 'get mailchimp member list';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function delete_mailchimp_member($mailchimp_contact_id,$list_id){

        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);

        try{
          $result = $client->lists->deleteListMember($list_id, $mailchimp_contact_id);
          $response['status'] = 'success';
          $response['id'] = $mailchimp_contact_id;
          $response['message'] = 'Member deleted successfully';
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'delete mailchimp member';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    //Store Code start here 04-08-2022
    public function create_mailchimp_store($data)
    {
        $currency=get_base_currency();
        $param =array(
            "id" => md5(uniqid()),
            "list_id" => $data['mailchimp_audience_id'],
            "name" => $data['name'],
            "currency_code" => $currency->name,
            "created_at" => date('Y-m-d-H-i-s'),
            "updated_at" => date('Y-m-d-H-i-s'),
        );
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
          $result = $client->ecommerce->addStore($param);
          
          $message = _l('store_add_success_msg');
          if(isset($result->id) && !empty($result->id)){
            $response['status'] = 'success';
            $response['mailchimp_id'] = $result->id;
            $response['message'] = $message;
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Something went wrong!';
          }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $this->mailchimp_activity_log('Store add',$response['message']);
        $response['action'] = 'create store';
        return json_encode($response);
    }
    public function update_mailchimp_store($data)
    {
         $currency=get_base_currency();
        $param =array(
            
            "name" => $data['name'],
            "currency_code" => $currency->name,
            "updated_at" => date('Y-m-d-H-i-s'),
        );
        
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
            $result = $client->ecommerce->updateStore($data['mailchimp_store_id'], $param);
          
            $message = _l('store_update_success_msg');
            if(isset($result->id) && !empty($result->id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->id;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'update store';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_mailchimp_store_list(){

        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        $fields ='stores.id,stores.list_id,stores.name,stores.currency_code';
        try {
            $result = $client->ecommerce->stores($fields);
            
            if(isset($result->stores) && !empty($result->stores)){
                $response['status'] = 'success';
                $response['list'] = $result->stores;
                $response['message'] = '';
            }else{
                $response['status'] = 'failure';
                 $response['list'] = '';
                $response['message'] = 'Data not found';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        return json_encode($response);
    }
    public function delete_mailchimp_store($store_id){
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);

        try{
          $result = $client->ecommerce->deleteStore($store_id);
          $response['status'] = 'success';
          $response['id'] = $store_id;
          $response['message'] = 'Store deleted successfully';
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'delete store';
        return json_encode($response);
    }
    //Mailchimp Customer code start here 04-08-2022
    public function create_or_update_mailchimp_customer($data)
    {
        $customer_hash_id = md5($data['email']);
        $address = array(
            "address1"=> !empty($data['street'])?$data['street']:'',
            
            "city"=> !empty($data['city'])?$data['city']:'',
            "province"=> !empty($data['state'])?$data['state']:'',
            "postal_code"=> $data['zip'],
            "country"=> !empty($data['country'])?$data['country']:'',
            "country_code"=> !empty($data['country_code'])?$data['country_code']:'',
        );
        $param =array(
            "id" => $customer_hash_id,
            "first_name" => !empty($data['first_name'])?$data['first_name']:'',
            "first_last" => !empty($data['last_name'])?$data['last_name']:'',
            "company" => !empty($data['company'])?$data['company']:'',
            "email_address" => $data['email'],
            "opt_in_status" => true,
            
            "address"  => $address,
        );
        if(empty($address['address']) || empty($address['city']) || empty($address['province']) || empty($address['postal_code']) || empty($address['country']) || empty($address['country_code'])){
            unset($param['address']);
        }
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
          $result = $client->ecommerce->setStoreCustomer($this->store_id, $customer_hash_id,$param);
          
          $message = _l('customer_add_update_success_msg');
          if(isset($result->id) && !empty($result->id)){
            $response['status'] = 'success';
            $response['mailchimp_id'] = $customer_hash_id;
            $response['message'] = $message;
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Something went wrong!';
          }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            
            $response['status'] = 'failure';
            $second_message = '';
            if(is_object($mailchimp_error)){
                if(isset($mailchimp_error->errors)){
                    $second_message  .= $mailchimp_error->errors[0]->field." ";
                    $second_message  .= $mailchimp_error->errors[0]->message;
                }
            }
            $response['message'] = $mailchimp_error->detail." ".$second_message;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'create or update mailchimp customer';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_mailchimp_customer_list(){
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{ 
          $result = $client->ecommerce->getAllStoreCustomers($this->store_id);
          
          if(isset($result->customers) && !empty($result->customers)){
            $response['status'] = 'success';
            $response['list'] = $result;
            $response['message'] = 'Data found';
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Data not found';
         }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'Store Customer List';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    
    public function get_mailchimp_customer_info($customer_hash_id){
        
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{ 
          $result = $client->ecommerce->getStoreCustomer($this->store_id, $customer_hash_id);
          if(isset($result->id) && !empty($result->id)){
            $response['status'] = 'success';
            $response['id'] = $result->id;
            $response['list'] = $result;
            $response['message'] = 'Data found';
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Data not found';
         }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'member info';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    //Product Api Start here
    public function create_mailchimp_product($data)
    {
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
            $result = $client->ecommerce->addStoreProduct($this->store_id, [
                "id" => md5($data['product_id']),
                "title" => $data['description'],
                "published_at_foreign" => date('Y-m-d-H-i-s'),
                "variants" => [
                    [
                        "id" => $data['product_id'], 
                        "title" =>  $data['description'],
                        'price'=> $data['unit_price']
                    ]
                ],
            ]);
            
            $message = _l('product_add_success_msg');
            if(isset($result->id) && !empty($result->id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->id;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'add mailchimp product';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function update_mailchimp_product($data)
    {
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
            $product_hash_id = md5($data['product_id']);
            $result = $client->ecommerce->updateStoreProduct($this->store_id,$product_hash_id, [
                "title" => $data['description'],
                "published_at_foreign" => date('Y-m-d-H-i-s'),
                "variants" => [
                    [
                        "id" => $data['product_id'], 
                        "title" =>  $data['description'],
                        'price'=> $data['unit_price']
                    ]
                ],
            ]);
            
            $message = _l('product_update_success_msg');
            if(isset($result->id) && !empty($result->id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->id;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'update mailchimp product';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_mailchimp_product_info($product_hash_id)
    {
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
            $result = $client->ecommerce->getStoreProduct($this->store_id, $product_hash_id);
            
            $message = _l('product_info');
            if(isset($result->id) && !empty($result->id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->id;
                $response['list'] = $result;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'mailchimp product info';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    //Orders Api start here 
    public function create_mailchimp_order($order_id)
    {
        //Order Details
        $this->ci->db->where('id',$order_id);
        $order =  $this->ci->db->get(db_prefix() . 'estimates')->row();
        //Client Details
        $client = $this->ci->clients_model->get($order->clientid);
        //Contact Details
        $contact_id = !is_client_logged_in()
                    ? get_primary_contact_user_id($order->clientid)
                    : get_contact_user_id();
        $this->ci->db->where('id',$contact_id);
        $contact =  $this->ci->db->get(db_prefix() . 'contacts')->row();
        //Order Number
        $order_data['order_number'] = format_estimate_number($order->id);
        //Customer details
        if(!empty($client->source)){
            $source = $this->ci->clients_model->get_sources($client->source);
            if($source && !empty($source)){
                $source = $source->name;
            }else{
                $source = '';
            }
        }else{
            $source = '';
        }
        if($client->default_currency && $client->default_currency !=0){
            $currency=get_currency($client->default_currency);
        }else{
            $currency=get_base_currency();   
        }
        $customer['customer_id'] = $client->userid;
        $customer['first_name'] = $contact?$contact->firstname:'';
        $customer['last_name'] = $contact?$contact->lastname:'';
        $customer['company'] = $client->company;
        $customer['email'] =  $contact?$contact->email:'';  
        $customer['street'] = $client->address; 
        $customer['city'] = $client->city;
        $customer['country'] = get_country_short_name($client->state);
        $customer['country_code'] = get_country_short_name($client->country);
        $customer['zip'] = $client->zip;
        $customer['state'] = $client->state;
        $customer['phone'] = $client->phonenumber;
        $customer['website'] = $client->website;
        $customer['source'] = $source;
        $customer['currency'] = $currency->name;
        $customer_link = $this->create_or_update_mailchimp_customer($customer);

        //Product Details
        $lines_array = array();
        $order->items = get_items_by_type('estimate', $order_id);
        if(isset($order->items) && !empty($order->items)){
            foreach ($order->items as $key => $value){
                //Product Param for create or update product   
                $product_id = $order_data['order_number'].'-'.$value['item_order'];
                $product['product_id'] = $product_id;              
                $product['description'] = $value['description'];
                $product['quantity'] = $value['qty'];
                $product['unit_price'] = $value['rate'];

                //Lines Param for create order 
                $lines[$key]['id'] = md5($key+1);
                $lines[$key]['product_id'] = md5($product_id);
                $lines[$key]['product_variant_id'] = $product_id;
                $lines[$key]['quantity'] = intval($value['qty']);
                $lines[$key]['price'] = $value['rate'];
                $exists_product = $this->get_mailchimp_product_info(md5($product_id));
                $exists_product = json_decode($exists_product);
                if(!empty($exists_product) && $exists_product->status == 'success'){
                    $product_link = $this->update_mailchimp_product($product);
                }else{
                    $product_link = $this->create_mailchimp_product($product);
                }
            }
        }else{
            return false;
            exit();
        }
         //Order Currency
        if($order->currency && $order->currency !=0){
            $order_currency=get_currency($order->currency);
        }else{
            $order_currency=get_base_currency();   
        }
        $client1 = new MailchimpMarketing\ApiClient();
        $client1->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        $param =array(
            "customer" => ["id" => md5($customer['email'])],
            "currency_code" => $order_currency->name,
            "order_total" => $order->subtotal,
            "processed_at_foreign" => $order->date,
            "updated_at_foreign" => date('Y-m-d-H-i-s'),
            "lines" =>  $lines,
        );
        $order_number = format_estimate_number($order->id);
        try{
          $exist_order = $this->get_mailchimp_order_info($order_number);
          $exist_order = json_decode($exist_order);
          if($exist_order->status  == 'success'){
            $result = $client1->ecommerce->deleteOrder($this->store_id,$order_number);
            $param['id'] = $order_number;
            $result = $client1->ecommerce->addStoreOrder($this->store_id,$param);  
            $message = 'update mailchimp order';
          }else{
            $param['id'] = $order_number;
            $result = $client1->ecommerce->addStoreOrder($this->store_id,$param);  
            $message = 'add mailchimp order';
          } 
          if(isset($result->id) && !empty($result->id)){
            $response['status'] = 'success';
            $response['mailchimp_id'] = $result->id;
            $response['store_id'] = $this->store_id;
            $response['message'] = $message;
          }else{
            $response['status'] = 'failure';
            $response['message'] = 'Something went wrong!';
          }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'mailchimp order';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    
    public function delete_mailchimp_order($mailchimp_order_id){
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);

        try{
          $result = $client->ecommerce->deleteOrder($this->store_id,$mailchimp_order_id);
          $response['status'] = 'success';
          $response['id'] = $store_id;
          $response['message'] = 'Mailchimp order deleted successfully';
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'delete order from MC';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_mailchimp_order_info($order_has_id)
    {
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{

            $result = $client->ecommerce->getOrder($this->store_id, $order_has_id);

            $message = "Order Info";
            if(isset($result->id) && !empty($result->id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->id;
                $response['list'] = $result;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'mailchimp order info';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
    public function get_member_list(){
        
    }
    public function create_merge_field($param,$list_id){
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
            $result = $client->lists->addListMergeField($list_id, [
                "name" => $param['name'],
                "type" => $param['type'],
                'tag' =>  $param['tag'],
            ]);
            $message = _l('merge_fields_add_success_msg');
            if(isset($result->id) && !empty($result->id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->id;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'Create Merge field';
        return json_encode($response);
    }
    //Mailchimp Member Tags
    public function assign_mailchimp_member_tags($list_id,$contact_id,$groups)
    {
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{
            $result = $client->lists->updateListMemberTags($list_id, $contact_id, [
                "tags" => $groups,
            ]);
            $message = _l('Tags_add_updated_success_msg');
            if(isset($result->id) && !empty($result->id)){
                $response['status'] = 'success';
                $response['mailchimp_id'] = $result->id;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $this->mailchimp_activity_log('Memeber Tags create update',$response['message']);
        $response['action'] = 'create update member tags';
        return json_encode($response);
    }
    public function mailchimp_activity_log($type='',$result='')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if(is_array($result) || is_object($result)) 
        {
           $result = json_encode($result);
        }
        $this->ci->db->insert(db_prefix().'mailchimp_activity_logs', [
            'type' => $type,
            'description' => $result, 
            'date' => date('Y-m-d H:i:s'),
            'staff' => $full_name,
        ]);
    }
    public function getActivityLog()
    {
        $this->ci->db->order_by('id','desc');
        if(isset($_GET['limit']) && is_numeric($_GET['limit'])){
            $this->ci->db->limit($_GET['limit']);
        }else{
            $this->ci->db->limit(30);
        }
        $result  = $this->ci->db->get(db_prefix().'mailchimp_activity_logs')->result_array();
        return $result;
    }
    public function get_member_activity_feed($hash_id,$list_id)
    {
        $client = new MailchimpMarketing\ApiClient();
        $client->setConfig([
            'apiKey' => $this->api_key,
            'server' => $this->server_prefix
        ]);
        try{

            $result = $client->lists->getListMemberActivityFeed($list_id,$hash_id);
            $message = "Member Activity";
            if(isset($result->activity) && !empty($result->activity)){
                $response['status'] = 'success';
                $response['list'] = $result->activity;
                $response['message'] = $message;
            }else{
                $response['status'] = 'failure';
                $response['message'] = 'Something went wrong!';
            }
        }catch (GuzzleHttp\Exception\ClientException $e) {
            $mailchimp_error = json_decode($e->getResponse()->getBody()->getContents());
            $response['status'] = 'failure';
            $response['message'] = $mailchimp_error;
        }catch (MailchimpMarketing\ApiException $e) {
            $message = $e->getMessage();
            $response['status'] = 'failure';
            $response['message'] = $message;
        }
        $response['action'] = 'Member Activity';
        $this->mailchimp_activity_log($response['action'],$response['message']);
        return json_encode($response);
    }
}