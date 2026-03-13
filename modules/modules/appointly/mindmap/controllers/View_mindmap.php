<?php

//defined('BASEPATH') or exit('No direct script access allowed');

$check =  __dir__ ;


class View_mindmap extends App_Controller {

	public function __construct()
    {
        parent::__construct();
    }

    public function mind($id, $hash)
    {
    	    $CI = &get_instance();
		      $CI->db->where('id', $id);
          $CI->db->where('hash', $hash);
          $data['mindmap'] = $CI->db->get(db_prefix() . 'mindmap')->row();


        $this->app_scripts->add('jquery-form-js','https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.2.2/jquery.form.min.js');
        $this->app_scripts->add('mind-elixir-js','modules/mindmap/assets/js/mind-elixir.js');
         

		$this->load->view('mindmap_pdf', $data);
		
	}
}