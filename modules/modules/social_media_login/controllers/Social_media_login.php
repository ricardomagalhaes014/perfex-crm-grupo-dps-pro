<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Social_media_login extends ClientsController
{
	public function __construct()
	{
		parent::__construct();
		require (__DIR__ . '/../vendor/autoload.php');

		if(get_option('social_media_login_module_status') != "Active")
		{
			redirect(site_url(''));
		}

	}

	public function google_login()
	{
		if(get_option('google_btn_status') != "Active"){
			redirect(site_url(''));
		}
		
		\modules\social_media_login\core\Apiinit::parse_module_url('social_media_login');
		\modules\social_media_login\core\Apiinit::check_url('social_media_login');
		
		$config = [
			// Location where to redirect users once they authenticate with a provider
			'callback' => site_url("social_media_login/google_login"),
			'enabled' => false,
			'keys' => 
				[
					'id' => get_option('google_id'),
					'secret' => get_option('google_key')
				]
			];

		// Instantiate Google's adapter directly
		$adapter = new \Hybridauth\Provider\Google($config);

		try {

			// Attempt to authenticate the user with Google
			$adapter->authenticate();

			// // Returns a boolean of whether the user is connected with Google
			$isConnected = $adapter->isConnected();

			// // Retrieve the user's profile
			$userProfile = $adapter->getUserProfile();

			$no_error['status'] = true;
		}
		catch(\Exception $e)
		{
			$no_error['status'] = false;

			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}

		if($no_error['status'] == true)
		{
			// Load Model
			$this->load->model('social_media_login_model');
			$this->load->model('clients_model');

			$check = $this->social_media_login_model->check_user_registered($userProfile->email);
			if($check['status'] == true)
			{
				// Set Session User Login With Social
				$this->session->set_userdata($check['data']);
				redirect(site_url(''));
			}else{
				$firstName 	= (isset($userProfile->firstName) && $userProfile->firstName != '') ? $userProfile->firstName : '';
				$lastName  	= (isset($userProfile->lastName) && $userProfile->lastName != '') ? $userProfile->lastName : '';
				$email 	   	= (isset($userProfile->email) && $userProfile->email != '') ? $userProfile->email : '';

				$clientid = $this->clients_model->add([
				      'billing_street'      => "",
				      'billing_city'        => "",
				      'billing_state'       => "",
				      'billing_zip'         => "",
				      'billing_country'     => 0,
				      'firstname'           => $firstName,
				      'lastname'            => $lastName,
				      'email'               => $email,
				      'contact_phonenumber' => "",
				      'website'             => "",
				      'title'               => "",
				      'password'            => "",
				      'company'             => $firstName." ".$lastName,
				      'vat'                 => isset($data['vat']) ? $data['vat'] : '',
				      'phonenumber'         => "",
				      'country'             => "",
				      'city'                => "",
				      'address'             => "",
				      'zip'                 => "",
				      'state'               => "",
				      'custom_fields'       => isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [],
				      'is_primary'			=> 1,
				], true);

				if ($clientid) {
				    $check = $this->social_media_login_model->check_user_registered($email);

				    if($check['status'] == true)
					{
						// Set Session User Login With Social
						$this->session->set_userdata($check['data']);
					}
					redirect(site_url());
				}
			}
		}
	}

	public function google_logout()
	{
		if(get_option('google_btn_status') != "Active"){
			redirect(site_url(''));
		}

		$config = [
			// Location where to redirect users once they authenticate with a provider
			'callback' => site_url("authentication/login"),
			'enabled' => true,
			'keys' => 
				[
					'id' => get_option('google_id'),
					'secret' => get_option('google_key')
				]
			];

		// Instantiate Google's adapter directly
		$adapter = new \Hybridauth\Provider\Google($config);
		$hybridauth = new Hybridauth\Hybridauth( $config );

		$this->load->model('authentication_model');
		try 
		{
			// Disconnect the adapter (log out)
			$adapter->disconnect();
			$hybridauth->disconnectAllAdapters(); //<< this line
			$this->authentication_model->logout();
		}
		catch(\Exception $e){
			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}
	}

	public function facebook_login()
	{
		if(get_option('facebook_btn_status') != "Active"){
			redirect(site_url(''));
		}
		
		\modules\social_media_login\core\Apiinit::parse_module_url('social_media_login');
		\modules\social_media_login\core\Apiinit::check_url('social_media_login');
		
		$config = [
			// Location where to redirect users once they authenticate with a provider
			'callback' => site_url("social_media_login/facebook_login"),
			'enabled' => false,
			'keys' => 
				[
					'id' =>  get_option('facebook_id'),
					'secret' => get_option('facebook_key')
				],
				"scope"   => ['email', 'user_age_range', 'user_birthday', 'user_gender', 'public_profile']
			];

		//Note : One Review Submit in Facebook Developer Console.

		// Instantiate Google's adapter directly
		$adapter = new \Hybridauth\Provider\Facebook($config);

		try {

			// Attempt to authenticate the user with Google
			$adapter->authenticate();

			$adapter->getAccessToken();

			// Returns a boolean of whether the user is connected with Google
			$isConnected = $adapter->isConnected();
		 
			// Retrieve the user's profile
			$userProfile = $adapter->getUserProfile();
			$no_error['status'] = true;
		}
		catch(\Exception $e){
			$no_error['status'] = false;
			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}

		if($no_error['status'] == true)
		{
			// Load Model
			$this->load->model('social_media_login_model');
			$this->load->model('clients_model');

			$check = $this->social_media_login_model->check_user_registered($userProfile->email);
			if($check['status'] == true)
			{
				// Set Session User Login With Social
				$this->session->set_userdata($check['data']);
				redirect(site_url(''));
			}else{
				$firstName 	= (isset($userProfile->firstName) && $userProfile->firstName != '') ? $userProfile->firstName : '';
				$lastName  	= (isset($userProfile->lastName) && $userProfile->lastName != '') ? $userProfile->lastName : '';
				$email 	   	= (isset($userProfile->email) && $userProfile->email != '') ? $userProfile->email : '';
				
				$clientid = $this->clients_model->add([
				      'billing_street'      => "",
				      'billing_city'        => "",
				      'billing_state'       => "",
				      'billing_zip'         => "",
				      'billing_country'     => 0,
				      'firstname'           => $firstName,
				      'lastname'            => $lastName,
				      'email'               => $email,
				      'contact_phonenumber' => "",
				      'website'             => "",
				      'title'               => "",
				      'password'            => "",
				      'company'             => $firstName." ".$lastName,
				      'vat'                 => isset($data['vat']) ? $data['vat'] : '',
				      'phonenumber'         => "",
				      'country'             => "",
				      'city'                => "",
				      'address'             => "",
				      'zip'                 => "",
				      'state'               => "",
				      'custom_fields'       => isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [],
				      'is_primary'			=> 1,
				], true);

				if ($clientid) {
				    $check = $this->social_media_login_model->check_user_registered($email);

				    if($check['status'] == true)
					{
						// Set Session User Login With Social
						$this->session->set_userdata($check['data']);
					}
					redirect(site_url());
				}
			}
		}
	}

	public function facebook_logout()
	{
		if(get_option('facebook_btn_status') != "Active"){
			redirect(site_url(''));
		}

		$config = [
			// Location where to redirect users once they authenticate with a provider
			'callback' => site_url(""),
			'enabled' => false,
			'keys' => 
				[
					'id' => get_option('facebook_id'),
					'secret' => get_option('facebook_key')
				],
				"scope"   => ['email', 'user_link', 'user_gender']
			];

		$adapter = new \Hybridauth\Provider\Facebook($config);
		$hybridauth = new Hybridauth\Hybridauth( $config );

		$this->load->model('authentication_model');
		try {
			// Disconnect the adapter (log out)
			$adapter->disconnect();
			$hybridauth->disconnectAllAdapters(); //<< this line
			
			$this->authentication_model->logout();
		}
		catch(\Exception $e){
			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}
	}

	public function linkedin_login()
	{
		if(get_option('linkedin_btn_status') != "Active"){
			redirect(site_url(''));
		}
		
		\modules\social_media_login\core\Apiinit::parse_module_url('social_media_login');
		\modules\social_media_login\core\Apiinit::check_url('social_media_login');

		$config = [
			// Location where to redirect users once they authenticate with a provider
			'callback' => site_url("social_media_login/linkedin_login"),
			'enabled' => false,
			'keys' => 
				[
					'id' => get_option('linkedin_id'),
					'secret' => get_option('linkedin_key')
				],
				"scope" => "r_liteprofile r_emailaddress"
			];

		//Note : One Review Submit in Facebook Developer Console.

		// Instantiate Google's adapter directly
		$adapter = new \Hybridauth\Provider\LinkedIn($config);

		try {

			// Attempt to authenticate the user with Google
			$adapter->authenticate();

			$adapter->getAccessToken();

			// Returns a boolean of whether the user is connected with Google
			$isConnected = $adapter->isConnected();
		 
			// Retrieve the user's profile
			$userProfile = $adapter->getUserProfile();
			$no_error['status'] = true;
		}
		catch(\Exception $e){
			$no_error['status'] = false;
			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}

		if($no_error['status'] == true)
		{
			// Load Model
			$this->load->model('social_media_login_model');
			$this->load->model('clients_model');

			$check = $this->social_media_login_model->check_user_registered($userProfile->email);
			if($check['status'] == true)
			{
				// Set Session User Login With Social
				$this->session->set_userdata($check['data']);

				redirect(site_url(''));
			}else{	
				$firstName 	= (isset($userProfile->firstName) && $userProfile->firstName != '') ? $userProfile->firstName : '';
				$lastName  	= (isset($userProfile->lastName) && $userProfile->lastName != '') ? $userProfile->lastName : '';
				$email 	   	= (isset($userProfile->email) && $userProfile->email != '') ? $userProfile->email : '';

				$clientid = $this->clients_model->add([
				      'billing_street'      => "",
				      'billing_city'        => "",
				      'billing_state'       => "",
				      'billing_zip'         => "",
				      'billing_country'     => 0,
				      'firstname'           => $firstName,
				      'lastname'            => $lastName,
				      'email'               => $email,
				      'contact_phonenumber' => "",
				      'website'             => "",
				      'title'               => "",
				      'password'            => "",
				      'company'             => $firstName." ".$lastName,
				      'vat'                 => isset($data['vat']) ? $data['vat'] : '',
				      'phonenumber'         => "",
				      'country'             => "",
				      'city'                => "",
				      'address'             => "",
				      'zip'                 => "",
				      'state'               => "",
				      'custom_fields'       => isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [],
				      'is_primary'			=> 1,
				], true);

				if ($clientid) {
				    $check = $this->social_media_login_model->check_user_registered($email);

				    if($check['status'] == true)
					{
						// Set Session User Login With Social
						$this->session->set_userdata($check['data']);
					}
					redirect(site_url());
				}
			}
		}
	}

	public function linkedin_logout()
	{
		if(get_option('linkedin_btn_status') != "Active"){
			redirect(site_url(''));
		}

		$config = [
		// Location where to redirect users once they authenticate with a provider
			'callback' => site_url(""),
			'enabled' => false,
			'keys' => 
			[
				'id' => get_option('linkedin_id'),
				'secret' => get_option('facebook_key'),
			],
			"scope" => "r_liteprofile r_emailaddress"
		];

		$adapter = new \Hybridauth\Provider\LinkedIn($config);
		$hybridauth = new Hybridauth\Hybridauth( $config );

		$this->load->model('authentication_model');
		try {
			// Disconnect the adapter (log out)
			$adapter->disconnect();
			$hybridauth->disconnectAllAdapters(); //<< this line
			
			$this->authentication_model->logout();
		}
		catch(\Exception $e){
			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}
	}

	public function twitter_login()
	{
		if(get_option('twitter_btn_status') != "Active"){
			redirect(site_url(''));
		}
		
		\modules\social_media_login\core\Apiinit::parse_module_url('social_media_login');
		\modules\social_media_login\core\Apiinit::check_url('social_media_login');

		$config = [
			// Location where to redirect users once they authenticate with a provider
			'callback' => site_url("social_media_login/twitter_login"),
			'enabled' => false,
			'keys' => 
				[
					'id' => get_option('twitter_id'),
					'secret' => get_option('twitter_key')
				]
			];

		// Instantiate Google's adapter directly
		$adapter = new \Hybridauth\Provider\Twitter($config);

		try {

			// Attempt to authenticate the user with Google
			$adapter->authenticate();

			// // Returns a boolean of whether the user is connected with Google
			$isConnected = $adapter->isConnected();

			// // Retrieve the user's profile
			$userProfile = $adapter->getUserProfile();
			$no_error['status'] = true;
		}
		catch(\Exception $e)
		{
			$no_error['status'] = false;

			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}

		if($no_error['status'] == true)
		{
			// Load Model
			$this->load->model('social_media_login_model');
			$this->load->model('clients_model');

			$check = $this->social_media_login_model->check_user_registered($userProfile->email);
			if($check['status'] == true)
			{
				// Set Session User Login With Social
				$this->session->set_userdata($check['data']);
				redirect(site_url(''));
			}else{
				$user_name = explode(" ", $userProfile->firstName);
				$email 	   	= (isset($userProfile->email) && $userProfile->email != '') ? $userProfile->email : '';

				$clientid = $this->clients_model->add([
				      'billing_street'      => "",
				      'billing_city'        => "",
				      'billing_state'       => "",
				      'billing_zip'         => "",
				      'billing_country'     => 0,
				      'firstname'           => $user_name[0],
				      'lastname'            => (isset($user_name[1]) && $user_name[1] != '') ? $user_name[1] : '',
				      'email'               => $email,
				      'contact_phonenumber' => "",
				      'website'             => "",
				      'title'               => "",
				      'password'            => "",
				      'company'             => $userProfile->firstName,
				      'vat'                 => isset($data['vat']) ? $data['vat'] : '',
				      'phonenumber'         => "",
				      'country'             => "",
				      'city'                => "",
				      'address'             => "",
				      'zip'                 => "",
				      'state'               => "",
				      'custom_fields'       => isset($data['custom_fields']) && is_array($data['custom_fields']) ? $data['custom_fields'] : [],
				      'is_primary'			=> 1,
				], true);

				if ($clientid) {
				    $check = $this->social_media_login_model->check_user_registered($email);

				    if($check['status'] == true)
					{
						// Set Session User Login With Social
						$this->session->set_userdata($check['data']);
					}
					redirect(site_url());
				}
			}
		}
	}

	public function twitter_logout()
	{
		if(get_option('linkedin_btn_status') != "Active"){
			redirect(site_url(''));
		}

		$config = [
		// Location where to redirect users once they authenticate with a provider
			'callback' => site_url(""),
			'enabled' => false,
			'keys' => 
			[
				'id' => get_option('twitter_id'),
				'secret' => get_option('twitter_key'),
			],
			"scope" => "r_liteprofile r_emailaddress"
		];

		$adapter = new \Hybridauth\Provider\Twitter($config);
		$hybridauth = new Hybridauth\Hybridauth( $config );

		$this->load->model('authentication_model');
		try {
			// Disconnect the adapter (log out)
			$adapter->disconnect();
			$hybridauth->disconnectAllAdapters(); //<< this line
			
			$this->authentication_model->logout();
		}
		catch(\Exception $e){
			echo 'Oops, we ran into an issue! ' . $e->getMessage();
		}
	}
}