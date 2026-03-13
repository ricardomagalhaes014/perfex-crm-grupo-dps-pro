<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Social_media_login_model extends App_Model 
{

	/**
	 * [check_user_registered : check user registered or not]
	 * @param  [string] $email [emali pass]
	 * 
	 */
	public function check_user_registered($email)
	{
	    $this->db->select('email, userid, id');
	    $this->db->where('email', $email);
	    $contact = $this->db->get(db_prefix() . 'contacts');

	    if($contact->num_rows() > 0)
	    {
	        $data =  $contact->row_array();

	        $user_data = [
	            'client_user_id'   => $data['userid'],
	            'contact_user_id'  => $data['id'],
	            'client_logged_in' => true,
	        ];

	        $result['status'] = true;
	        $result['data'] = $user_data;
	    }else{
	        $result['status'] = false; 
	    }

	    return $result;
	}

}