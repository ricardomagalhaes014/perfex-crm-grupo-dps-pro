<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Social_settings extends AdminController
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$data = array();
		$data["title"] = _l("Social Media Settings");
		$this->load->view('Settings', $data, FALSE);
	}

	public function settings_save()
	{

		$setting_arr  = $this->input->post();

		foreach ($setting_arr as $key => $value)
		{
			update_option($key, $value);
		}

		$data['status'] = 1;
		$data['data'] = "";
		$data['message'] = "Settings Updated.";

		echo json_encode($data);
	}
}