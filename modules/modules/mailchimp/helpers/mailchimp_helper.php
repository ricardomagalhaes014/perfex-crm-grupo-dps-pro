<?php
defined('BASEPATH') or exit('No direct script access allowed');
function get_mailchimp_api_key()
{
    $CI = &get_instance();
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp->api_key;
    }
    return false;
}
function get_mailchimp_server_prefix()
{
    $CI = &get_instance();
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp->server_prefix;
    }
    return false;
}
function get_audience_details($id)
{
    $CI = &get_instance();
    $CI->db->where('id',$id);
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp_audience')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp;
    }
    return false;
}
function get_audience_details_by_mailchimp_id($id)
{
    $CI = &get_instance();
    $CI->db->where('mailchimp_id', $id);
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp_audience')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp;
    }
    return false;
}

function update_mailchimp_audience_data($data){
    $id = $data['id'];
    unset($data['id']);
    $data['updated_at'] =  date('Y-m-d H:i:s');
    $CI = &get_instance();
    $CI->db->where('id', $id);
    $CI->db->update(db_prefix() . 'mailchimp_audience', $data);
    if ($CI->db->affected_rows() > 0) {
        return true;
    }
    return false;
}
function add_mailchimp_audience_data($data){
    $data['created_at'] =  date('Y-m-d H:i:s');
    $data['updated_at'] =  date('Y-m-d H:i:s');
    $CI = &get_instance();
    $CI->db->insert(db_prefix() . 'mailchimp_audience', $data);
    $insert_id = $CI->db->insert_id();
    if ($insert_id) {
        return $insert_id;
    }
    return false;
}

function isExistAudience($name,$mailchimp_id){
    $CI = &get_instance();
    $CI->db->where('name',trim($name));
    $audience = $CI->db->get(db_prefix() . 'mailchimp_audience')->row();
    if(!empty($audience)){
       return $audience;
    }
    if(!empty($mailchimp_id)){
       $CI->db->where('mailchimp_id',trim($mailchimp_id));
        $audience = $CI->db->get(db_prefix() . 'mailchimp_audience')->row();
        if(!empty($audience)){
           return $audience;
        }else{
            return false;
        } 
    }
    return false; 
}  

function get_store_details($id)
{
    $CI = &get_instance();
    $CI->db->where('id',$id);
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp_stores')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp;
    }
    return false;
}
function get_audience_name_by_mailchimp_id($id)
{
    $CI = &get_instance();
    $CI->db->where('mailchimp_id',$id);
    $result = $CI->db->get(db_prefix() . 'mailchimp_audience')->row();
    if($result && !empty($result)){
        return $result->name;
    }
    return false;
}
function get_default_mailchimp_audience_has_id()
{
    $CI = &get_instance();
    $CI->db->where('is_default',1);
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp_audience')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp->mailchimp_id;
    }
    return false;
}
function get_default_mailchimp_store_has_id()
{
    $CI = &get_instance();
    $CI->db->where('is_default',1);
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp_stores')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp->mailchimp_store_id;
    }
    return false;
}
function isContactEmailExist($email,$user_id=''){
    $CI = &get_instance();
    $CI->db->where('email',trim($email));
    if(!empty($user_id)){
       $CI->db->where('userid',trim($user_id)); 
    }
    $result = $CI->db->get(db_prefix() . 'contacts')->row();
    if(!empty($result)){
       return $result;
    }
    return false; 
}
function get_country_id_by_country_code($country_code){
    $CI = &get_instance();
    $CI->db->where('iso2',$country_code);
    $result = $CI->db->get(db_prefix() . 'countries')->row();
    if(!empty($result)){
       return $result->country_id;
    }
    return false; 
}
function get_contact_list($link='',$limit='',$customer_id=''){
    $CI = &get_instance();
    $CI->db->select(db_prefix().'contacts.*,'.db_prefix().'clients.company'); 
    $CI->db->from(db_prefix() . 'contacts');
    if(!empty($customer_id)){
       $CI->db->where(db_prefix() .'contacts.userid =',$customer_id); 
    }
    $CI->db->join(db_prefix() . 'clients', db_prefix() .'clients.userid = '.db_prefix() .'contacts.userid','left');
    if(!empty($limit)){
        $CI->db->limit($limit); 
    }else{
    }
    $CI->db->order_by('firstname','ASC'); 
    $result = $CI->db->get()->result_array();
    if($result){
        return $result;
    }
    return false;
}

function get_limit_for_sync_jm_to_mc()
{
    $CI = &get_instance();
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp->limit_for_sync_jm_to_mc;
    }
    return false;
}

function get_customer_tags_names($customer_id){
    $groups =  get_customer_group_names($customer_id);
    $CI = &get_instance();
    $result = $CI->db->select('*')->from(db_prefix() . 'customers_groups')->get()->result_array();
    $group_names = array();
    if($result){
        foreach($result as $key => $group){
            $new_arr = array(
                'name' => $group['name'],
            );
            if(is_array($groups) && in_array($group['name'], $groups)){
              $new_arr['status'] = 'active' ; 
            }else{
               $new_arr['status'] = 'inactive' ;  
            }
            $group_names[$key] = $new_arr;
        }
        return $group_names;
    }
    return false;
}

function update_contact_audience($contact_id, $mailchimp_audience_id,$status){
    $data['contact_id'] =  $contact_id;
    $data['mailchimp_audience_id'] =  $mailchimp_audience_id;
    $data['mailchimp_subscribed_status'] =  $status;
    $data['updated_at'] =  date('Y-m-d H:i:s');

    $CI = &get_instance();
    $CI->db->where(['contact_id'=> $contact_id,'mailchimp_audience_id' => $mailchimp_audience_id]);
    $exist = $CI->db->get(db_prefix() . 'mailchimp_contact_audience')->row();
    if(empty($exist)){
        $data['created_at'] =  date('Y-m-d H:i:s');
        $CI->db->insert(db_prefix() . 'mailchimp_contact_audience', $data);
        $insert_id = $CI->db->insert_id();
        if ($insert_id) {
            return true;
        }
    }else{
        $CI->db->where('id', $exist->id);
        $CI->db->update(db_prefix() . 'mailchimp_contact_audience', $data);
        if ($CI->db->affected_rows() > 0) {
            return true;
        }
    }
    return false; 
}
function get_contact_assigned_audience_ids($contact_id){
    $CI = &get_instance();
    $CI->db->where(['contact_id'=> $contact_id]);
    $result = $CI->db->select('*')->from(db_prefix() . 'mailchimp_contact_audience')->get()->result_array();
    if(!empty($result)){
        $list = array();
        foreach($result as $key => $value){
            $list[$key] = $value['mailchimp_audience_id'];
        }
        return $list;
    }
    return false;
}
function get_contact_assigned_audience_list($contact_id){
    $CI = &get_instance();
    $CI->db->select(db_prefix() . 'mailchimp_contact_audience.*, '.db_prefix() .'mailchimp_audience.name')->from(db_prefix() . 'mailchimp_contact_audience');
    $CI->db->where(['contact_id'=> $contact_id]);
    $CI->db->join(db_prefix() . 'mailchimp_audience', db_prefix() .'mailchimp_audience.mailchimp_id = '.db_prefix() .'mailchimp_contact_audience.mailchimp_audience_id','left');
    $CI->db->order_by(db_prefix() . 'mailchimp_audience.name','ASC');
    $result = $CI->db->get()->result_array();
    if(!empty($result)){
        return $result;
    }
    return false;
}
function get_contact_assigned_all_audience_list($contact_id){
    $CI = &get_instance();
    $CI->db->select(db_prefix() . 'mailchimp_audience.*, '.db_prefix() .'mailchimp_audience.mailchimp_id as mailchimp_audience_id, (SELECT COUNT(*) FROM ' . db_prefix() . 'mailchimp_contact_audience WHERE contact_id = '.$contact_id.' AND mailchimp_audience_id = '.db_prefix(). 'mailchimp_audience.mailchimp_id) as subscribed')->from(db_prefix() . 'mailchimp_audience');

    $CI->db->order_by(db_prefix() . 'mailchimp_audience.name','ASC');
    $result = $CI->db->get()->result_array();
    
    if(!empty($result)){
        return $result;
    }
    return false;
}
function get_contact_assigned_audience_details($contact_id,$audience_id){
    $CI = &get_instance();
    $CI->db->select(db_prefix() . 'mailchimp_contact_audience.*, '.db_prefix() .'mailchimp_audience.name')->from(db_prefix() . 'mailchimp_contact_audience');
    $CI->db->where([db_prefix() .'mailchimp_contact_audience.contact_id' => $contact_id,db_prefix() .'mailchimp_contact_audience.mailchimp_audience_id'=>$audience_id]);
    $CI->db->join(db_prefix() . 'mailchimp_audience', db_prefix() .'mailchimp_audience.mailchimp_id = '.db_prefix() .'mailchimp_contact_audience.mailchimp_audience_id','left');
    $CI->db->order_by(db_prefix() . 'mailchimp_audience.name','ASC');
    $result = $CI->db->get()->row();
    if(!empty($result)){
        return $result;
    }
    return false;
}
function get_assigned_audience_list(){
    $CI = &get_instance();
    $CI->db->select(db_prefix() . 'mailchimp_contact_audience.mailchimp_audience_id')->from(db_prefix() . 'mailchimp_contact_audience');
    $CI->db->group_by(db_prefix() .'mailchimp_contact_audience.mailchimp_audience_id');
    $result = $CI->db->get()->result_array();
    if(!empty($result)){
        return $result;
    }
    return false;
}
function get_unassigned_contact_audience_list($contact_id){
    $CI = &get_instance();
    $audience_list = $CI->db->select('*')->from(db_prefix() . 'mailchimp_audience')->order_by('name','ASC')->get()->result_array();
    $assigned_ids = get_contact_assigned_audience_ids($contact_id);
    if(!empty($audience_list)){
        foreach($audience_list as $key=>$audience){
            if(is_array($assigned_ids)){
                if(in_array($audience['mailchimp_id'],$assigned_ids)){
                    unset($audience_list[$key]);
                }
            }
        }
        return $audience_list;
    }else{
        return false;
    }
}
function isExistMailChimp(){
    $CI = &get_instance();
    if($CI->app_modules->is_active('mailchimp')) {
        return true;
    }else{
        return false;
    }
}
function get_customer_group_names($id){
    $CI = &get_instance();
    $CI->db->where('customer_id',$id);
    $result = $CI->db->select('*')->from(db_prefix() . 'customer_groups')->get()->result_array();
    
    $group_names = array();
    if($result){
        foreach($result as $key => $group){
            $CI->db->where('id',$group['groupid']);
            $q_result = $CI->db->select('*')->from(db_prefix() . 'customers_groups')->get()->row();
            if($q_result){
                array_push($group_names,$q_result->name);
            }
        }
        return $group_names;
    }
    return false;
}
function get_audience_id_of_store($id)
{
    $CI = &get_instance();
    $CI->db->where('mailchimp_store_id',$id);
    $mailchimp = $CI->db->get(db_prefix() . 'mailchimp_stores')->row();
    if($mailchimp && !empty($mailchimp)){
        return $mailchimp->mailchimp_audience_id;
    }
    return false;
}