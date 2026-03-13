<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mailchimp_audience_model extends App_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('mailchimp');
    }


    public function add($data)
    {
        if(isExistMailChimp()){
            $mailchimpApi = new MailchimpAPIWrapper;
            $data['mailchimp_id'] = '';
            $result = $mailchimpApi->create_or_update_mailchimp_audience($data);
            $result = json_decode($result);
            if($result->status == 'success'){
              if(!empty($result->mailchimp_id)){
                $data['mailchimp_id'] = $result->mailchimp_id;  
                $this->audience_merge_field($result->mailchimp_id);
              }  
            }
        }
        $data['created_at'] =  date('Y-m-d H:i:s');
        $data['updated_at'] =  date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'mailchimp_audience', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Mailchimp Audience Added [ID: ' . $insert_id . ', Name:' . $data['name'] . ']');
            return $insert_id;
        }
        return false;
    }

    public function update($data)
    {
        $id = $data['id'];
        unset($data['id']);
        if(isExistMailChimp()){
            $audience = get_audience_details($id);
            if($audience){
                $data['mailchimp_id'] = $audience->mailchimp_id;
            }
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->create_or_update_mailchimp_audience($data);
            $result = json_decode($result);
            if($result->status == 'success'){
              if(!empty($result->mailchimp_id)){
                $data['mailchimp_id'] = $result->mailchimp_id; 
                $this->audience_merge_field($result->mailchimp_id); 
              }  
            }
        }
        $data['updated_at'] =  date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'mailchimp_audience', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Mailchimp Audience Updated [ID: ' . $id . ', Name:' . $data['name'] . ']');

            return true;
        }
        return true;
    }

    public function delete($id)
    {
        $audience = get_audience_details($id);
        $default_list_has_id = get_default_mailchimp_audience_has_id();
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'mailchimp_audience');
        if ($this->db->affected_rows() > 0) {
            if($audience){
                if(isExistMailChimp()){
                    $mailchimpApi = new MailchimpAPIWrapper;
                    $result = $mailchimpApi->delete_mailchimp_audience($audience->mailchimp_id);
                    //Delete store link to this audience
                    $this->db->where('mailchimp_audience_id', $audience->mailchimp_id);
                    $this->db->delete(db_prefix() . 'mailchimp_stores');
                    //Delete contact assigned audience
                    $this->db->where(['mailchimp_audience_id' => $audience->mailchimp_id]);
                    $this->db->delete(db_prefix() . 'mailchimp_contact_audience');
                    //Delete order link from estimate table
                    $this->db->where(['mailchimp_audience_id' => $audience->mailchimp_id]);
                    $this->db->set('mailchimp_estimate_id', '');
                    $this->db->update(db_prefix() . 'estimates');
                }
            }
            log_activity('Mailchimp Audience Deleted [' . $id . ']');
            return true;
        }

        return true;
    }
    public function make_default($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'mailchimp_audience', ['is_default' => 1,'updated_at'=>date('Y-m-d H:i:s')]);
        if ($this->db->affected_rows() > 0) {
            $this->db->where('id !=', $id);
            $this->db->update(db_prefix() . 'mailchimp_audience', ['is_default' => 0,'updated_at'=>date('Y-m-d H:i:s')]);
            return true;
        }
        return false;
    }

    public function get_audience_data($id)
    {
        $this->db->where('id', $id);
        $result = $this->db->select('*')->from(db_prefix() . 'mailchimp_audience')->get()->result_array();
        return $result;
    }
    public function get_audience_list()
    {
        $result = $this->db->select('*')->from(db_prefix() . 'mailchimp_audience')->order_by('name','ASC')->get()->result_array();
        return $result;
    }
    public function audience_merge_field($audience_id=''){
        $mailchimpApi = new MailchimpAPIWrapper;
        $result = $mailchimpApi->get_mailchimp_audience_list();
        $result = json_decode($result);
        if($result->status == 'success'){
            foreach($result->list as $k=>$mailchimp){
                if($mailchimp->id == $audience_id){
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
            $response['message'] = 'Audience merge fields successfully';
        }else{
            $response['status'] = 'failure';
             $response['message'] = _l('Something went wrong!');
        }
        return json_encode($response);
    }
    public function get_contact_details($id){
        $this->db->where('id', $id);
        $contact = $this->db->get(db_prefix() . 'contacts')->row();
        return $contact;
    }
}