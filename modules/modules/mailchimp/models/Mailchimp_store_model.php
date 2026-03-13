<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mailchimp_store_model extends App_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('mailchimp');
    }


    public function add($param)
    {
        $data['name'] =  $param['store_name'];
        $data['mailchimp_audience_id'] =  $param['store_audience'];
        $data['created_at'] =  date('Y-m-d H:i:s');
        $data['updated_at'] =  date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'mailchimp_stores', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if(isExistMailChimp()){
                $mailchimpApi = new MailchimpAPIWrapper;
                $data['mailchimp_id'] = '';
                $result = $mailchimpApi->create_mailchimp_store($data);
                $result = json_decode($result);
                if($result->status == 'success'){
                  if(!empty($result->mailchimp_id)){
                    $this->db->where('id', $insert_id);
                    $this->db->update(db_prefix() . 'mailchimp_stores', 
                        ['mailchimp_store_id'=> $result->mailchimp_id]
                    );
                    if ($this->db->affected_rows() > 0) {
                        return $insert_id;
                    }  
                  }  
                }
            }
            return $insert_id;
        }
        return false;
    }

    public function update($param)
    {
        $id = $param['id'];
        $data['name'] =  $param['store_name'];
        $data['mailchimp_store_id'] =  $param['mailchimp_store_id'];
        if(isExistMailChimp()){
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->update_mailchimp_store($data);
            $result = json_decode($result);
            if($result->status == 'success'){
              if(!empty($result->mailchimp_id)){
                $data['mailchimp_store_id'] = $result->mailchimp_id;  
              }  
            }
        }
        $data['updated_at'] =  date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'mailchimp_stores', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return true;
    }

    public function delete($id)
    {
        $store = get_store_details($id);
        $default_store_hash_id = get_default_mailchimp_store_has_id();
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'mailchimp_stores');
        if ($this->db->affected_rows() > 0) {
            if($store){
                if(isExistMailChimp()){
                    $mailchimpApi = new MailchimpAPIWrapper;
                    $result = $mailchimpApi->delete_mailchimp_store($store->mailchimp_store_id);
                }
                $this->db->where(['mailchimp_audience_id' => $store->mailchimp_audience_id]);
                $this->db->set('mailchimp_estimate_id', '');
                $this->db->update(db_prefix() . 'estimates');
            }
            return true;
        }

        return true;
    }
    public function make_default($id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'mailchimp_stores', ['is_default' => 1,'updated_at'=>date('Y-m-d H:i:s')]);
        if ($this->db->affected_rows() > 0) {
            $this->db->where('id !=', $id);
            $this->db->update(db_prefix() . 'mailchimp_stores', ['is_default' => 0,'updated_at'=>date('Y-m-d H:i:s')]);
            return true;
        }
        return false;
    }
    public function get_store_data($id)
    {
        $this->db->where('id', $id);
        $result = $this->db->select('*')->from(db_prefix() . 'mailchimp_stores')->get()->result_array();
        return $result;
    }
    public function get_audience_list()
    {
        $this->db->order_by('id','desc');
        $result = $this->db->select('*')->from(db_prefix() . 'mailchimp_stores')->get()->result_array();
        return $result;
    }
}