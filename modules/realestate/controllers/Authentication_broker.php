<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Authentication_broker extends App_Controller
{   
	public $template = [];

	public $data = [];

	public $use_footer = true;

	public $use_submenu = true;

	public $use_navigation = true;

	/**
	 * construct
	 */
	public function __construct()
	{
		parent::__construct();
		
		if (is_staff_logged_in() && $this->app->is_db_upgrade_required($this->current_db_version)) {
			redirect(admin_url());
		}

		$this->load->library('app_broker_portal_area_constructor');

		if (method_exists($this, 'validateContact')) {
			$this->validateContact();
		}
	}

	/**
	 * { index }
	 */
	public function index()
	{
		$this->login();
	}

	// Added for backward compatibilies
	public function admin()
	{
		redirect(admin_url('authentication'));
	}

	/**
	 * { login }
	 */
	public function login()
	{
		if (is_broker_logged_in()) {
			redirect(site_url('realestate/broker'));
		}

		$this->form_validation->set_rules('password', _l('clients_login_password'), 'required');
		$this->form_validation->set_rules('email', _l('clients_login_email'), 'trim|required|valid_email');

		if (get_option('use_recaptcha_customers_area') == 1
			&& get_option('recaptcha_secret_key') != ''
			&& get_option('recaptcha_site_key') != '') {
			$this->form_validation->set_rules('g-recaptcha-response', 'Captcha', 'callback_recaptcha');
	}
	if ($this->form_validation->run() !== false) {
		$this->load->model('Authentication_broker_model');

		$success = $this->Authentication_broker_model->login(
			$this->input->post('email'),
			$this->input->post('password', false),
			$this->input->post('remember'),
			false
		);

		if (is_array($success) && isset($success['memberinactive'])) {
			set_alert('danger', _l('inactive_account'));
			redirect(site_url('realestate/authentication_broker/login'));
		} elseif ($success == false) {
			set_alert('danger', _l('client_invalid_username_or_password'));
			redirect(site_url('realestate/authentication_broker/login'));
		}

		redirect(site_url('realestate/broker'));
	}
	if (get_option('allow_registration') == 1) {
		$data['title'] = _l('clients_login_heading_register');
	} else {
		$data['title'] = _l('clients_login_heading_no_register');
	}
	$data['bodyclass'] = 'customers_login';

	$this->data($data);
	$this->view('brokers_portals/login');
	$this->layout();
}

	/**
	 * { register }
	 */
	public function register()
	{
		$this->load->model('realestate_model');
		
		$this->form_validation->set_rules('name', _l('real_broker_name'), 'required');
		$this->form_validation->set_rules('firstname', _l('firstname'), 'required');
		$this->form_validation->set_rules('lastname', _l('lastname'), 'required');
		$this->form_validation->set_rules('email', _l('email'), 'trim|required|is_unique[' . db_prefix() . 'real_broker_staffs.email]|valid_email');
		$this->form_validation->set_rules('password', _l('broker_register_password'), 'required');
		$this->form_validation->set_rules('passwordr', _l('broker_register_password_repeat'), 'required|matches[password]');

		if ($this->input->post()) {
			// check email exit
			$this->db->where('email', $this->input->post('email'));
			$broker_staff = $this->db->get(db_prefix().'real_broker_staffs')->row();
			if($broker_staff){

				set_alert('warning', _l('email_exists'));
				redirect('realestate/authentication_broker/register');
			}

			if ($this->form_validation->run() !== false) {
				$data = $this->input->post();

				define('CADIDATE_REGISTERING', true);
				$data['related_type'] = 'business_broker';
				$data['password'] = $data['passwordr'];
				if(isset($data['passwordr'])){
					unset($data['passwordr']);
				}
				$clientid = $this->realestate_model->add_construction_company($data);

				if ($clientid) {

					$this->load->model('authentication_broker_model');
					$logged_in = $this->authentication_broker_model->login(
						$this->input->post('email'),
						$this->input->post('password', false),
						false,
						false
					);

					$redUrl = site_url('realestate/authentication_broker/login');

					if ($logged_in) {
						
						set_alert('success', _l('clients_successfully_registered'));
					} else {
						set_alert('warning', _l('clients_account_created_but_not_logged_in'));
						$redUrl = site_url('realestate/authentication_broker/login');
					}

					redirect($redUrl);
				}
			}
		}

		$data['title']     = _l('clients_register_heading');
		$data['bodyclass'] = 'register';
		$this->data($data);
		$this->view('brokers_portals/register');
		$this->layout();
	}

	/**
	 * { forgot password }
	 */
	public function forgot_password()
	{
		if (is_broker_logged_in()) {
			redirect(site_url());
		}

		$this->form_validation->set_rules(
			'email',
			_l('customer_forgot_password_email'),
			'trim|required|valid_email|callback_contact_email_exists'
		);
		if ($this->input->post()) {
			if ($this->form_validation->run() !== false) {
				$this->load->model('Authentication_broker_model');
				$success = $this->Authentication_broker_model->forgot_password($this->input->post('email'));
				if (is_array($success) && isset($success['memberinactive'])) {
					set_alert('danger', _l('inactive_account'));
				} elseif ($success == true) {
					set_alert('success', _l('check_email_for_resetting_password'));
				} else {
					set_alert('danger', _l('error_setting_new_password_key'));
				}
				redirect(site_url('realestate/authentication_broker/forgot_password'));
			}
		}
		$data['title'] = _l('customer_forgot_password');
		$this->data($data);
		$this->view('brokers_portals/forgot_password');

		$this->layout();
	}

	/**
	 * { reset password }
	 *
	 * @param      <type>  $staff         The staff
	 * @param      <type>  $userid        The userid
	 * @param      <type>  $new_pass_key  The new pass key
	 */
	public function reset_password($staff, $userid, $new_pass_key)
	{
		$this->load->model('Authentication_broker_model');
		if (!$this->Authentication_broker_model->can_reset_password($staff, $userid, $new_pass_key)) {
			set_alert('danger', _l('password_reset_key_expired'));
			redirect(site_url('realestate/authentication_broker/login'));
		}

		$this->form_validation->set_rules('password', _l('customer_reset_password'), 'required');
		$this->form_validation->set_rules('passwordr', _l('customer_reset_password_repeat'), 'required|matches[password]');
		if ($this->input->post()) {
			if ($this->form_validation->run() !== false) {
				hooks()->do_action('before_broker_reset_password', [
					'staff'  => $staff,
					'userid' => $userid,
				]);
				$success = $this->Authentication_broker_model->reset_password(
					false,
					$userid,
					$new_pass_key,
					$this->input->post('passwordr', false)
				);
				if (is_array($success) && $success['expired'] == true) {
					set_alert('danger', _l('password_reset_key_expired'));
				} elseif ($success == true) {
					hooks()->do_action('after_broker_reset_password', [
						'staff'  => $staff,
						'userid' => $userid,
					]);
					set_alert('success', _l('password_reset_message'));
				} else {
					set_alert('danger', _l('password_reset_message_fail'));
				}
				redirect(site_url('realestate/authentication_broker/login'));
			}
		}
		$data['title'] = _l('admin_auth_reset_password_heading');
		$this->data($data);
		$this->view('brokers_portals/reset_password');
		$this->layout();
	}

	/**
	 * { logout }
	 */
	public function logout()
	{
		$this->load->model('Authentication_broker_model');
		$this->Authentication_broker_model->logout(false);
		redirect(site_url('realestate/authentication_broker/login'));
	}

	/**
	 * Determines if contact email exists.
	 *
	 * @param      string   $email  The email
	 *
	 * @return     boolean  True if contact email exists, False otherwise.
	 */
	public function contact_email_exists($email = '')
	{
		$this->db->where('email', $email);
		$total_rows = $this->db->count_all_results(db_prefix() . 'real_broker_staffs');

		if ($total_rows == 0) {
			$this->form_validation->set_message('contact_email_exists', _l('auth_reset_pass_email_not_found'));

			return false;
		}

		return true;
	}

	/**
	 * { recaptcha }
	 *
	 * @param      string  $str    The string
	 *
	 * @return     <type>  ( description_of_the_return_value )
	 */
	public function recaptcha($str = '')
	{
		return do_recaptcha_validation($str);
	}

	/**
	 * { layout }
	 *
	 * @param      boolean  $notInThemeViewFiles  Not in theme view files
	 */
	public function layout($notInThemeViewFiles = false)
	{
		/**
		 * Navigation and submenu
		 * @deprecated 2.3.2
		 * @var boolean
		 */

		$this->data['use_navigation'] = $this->use_navigation == true;
		$this->data['use_submenu']    = $this->use_submenu == true;

		/**
		 * @since  2.3.2 new variables
		 * @var array
		 */
		$this->data['navigationEnabled'] = $this->use_navigation == true;
		$this->data['subMenuEnabled']    = $this->use_submenu == true;

		/**
		 * Theme head file
		 * @var string
		 */
		$this->template['head'] = $this->load->view('brokers_portals/head', $this->data, true);

		$GLOBALS['customers_head'] = $this->template['head'];

		/**
		 * Load the template view
		 * @var string
		 */
		$module                       = CI::$APP->router->fetch_module();
		$this->data['current_module'] = $module;

		$viewPath = !is_null($module) || $notInThemeViewFiles ? $this->view : 'recruitment_portal/' . $this->view;

		$this->template['view']    = $this->load->view($viewPath, $this->data, true);
		$GLOBALS['customers_view'] = $this->template['view'];

		/**
		 * Theme footer
		 * @var string
		 */
		$this->template['footer'] = $this->use_footer == true
		? $this->load->view('brokers_portals/footer', $this->data, true)
		: '';
		$GLOBALS['customers_footer'] = $this->template['footer'];

		/**
		 * @deprecated 2.3.0
		 * Theme scripts.php file is no longer used since vresion 2.3.0, add app_customers_footer() in themes/[theme]/index.php
		 * @var string
		 */
		$this->template['scripts'] = '';
		if (file_exists(VIEWPATH . 'brokers_portals/scripts.php')) {
			if (ENVIRONMENT != 'production') {
				trigger_error(sprintf('%1$s', 'Clients area theme file scripts.php file is no longer used since version 2.3.0, add app_customers_footer() in themes/[theme]/index.php. You can check the original theme index.php for example.'));
			}

			$this->template['scripts'] = $this->load->view('brokers_portals/scripts', $this->data, true);
		}

		/**
		 * Load the theme compiled template
		 */
		$this->load->view('brokers_portals/index', $this->template);
	}

	/**
	 * Sets view data
	 * @param  array $data
	 * @return core/ClientsController
	 */
	public function data($data)
	{
		if (!is_array($data)) {
			return false;
		}

		$this->data = array_merge($this->data, $data);

		return $this;
	}

	/**
	 * Set view to load
	 * @param  string $view view file
	 * @return core/ClientsController
	 */
	public function view($view)
	{
		$this->view = $view;

		return $this;
	}

	/**
	 * Sets view title
	 * @param  string $title
	 * @return core/ClientsController
	 */
	public function title($title)
	{
		$this->data['title'] = $title;

		return $this;
	}

	/**
	 * Disables theme navigation
	 * @return core/ClientsController
	 */
	public function disableNavigation()
	{
		$this->use_navigation = false;

		return $this;
	}

	/**
	 * Disables theme navigation
	 * @return core/ClientsController
	 */
	public function disableSubMenu()
	{
		$this->use_submenu = false;

		return $this;
	}

	/**
	* Disables theme footer
	* @return core/ClientsController
	*/
	public function disableFooter()
	{
		$this->use_footer = false;

		return $this;
	}
}
