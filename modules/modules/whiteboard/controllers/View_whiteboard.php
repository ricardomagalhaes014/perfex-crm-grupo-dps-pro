<?php

//defined('BASEPATH') or exit('No direct script access allowed');

$check =  __dir__ ;


class View_whiteboard extends App_Controller {

	public function __construct()
    {
        parent::__construct();
    }

    public function white($id, $hash)
    {
    	$CI = &get_instance();
		$CI->db->where('id', $id);
        $CI->db->where('hash', $hash);
        $data['whiteboard'] = $CI->db->get(db_prefix() . 'whiteboard')->row();
        $this->app_scripts->add('jquery-form-js','https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.2/jquery.form.min.js');
        $this->app_scripts->add('jquery-min-js','https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js');
		$this->load->view('view_whiteboard', $data);
		
	}
}