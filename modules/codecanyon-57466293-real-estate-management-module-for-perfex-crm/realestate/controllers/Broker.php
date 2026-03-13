<?php
defined('BASEPATH') or exit('No direct script access allowed');
use app\services\ValidatesContact;

/**
 * Broker Controller
 */
class Broker extends App_Controller
{   


	public $template = [];

	public $data = [];

	public $use_footer = true;

	public $use_submenu = true;

	public $use_navigation = true;

	/**
	 * construct
	 */
	public function __construct() {

		hooks()->do_action('after_clients_area_init', $this);

		parent::__construct();

		$this->load->library('app_broker_portal_area_constructor');

		$this->load->model('realestate_model');
		$this->load->model('broker_model');
		$this->load->model('contracts_model');
		$this->load->model('invoices_model');
		$this->site_url = site_url().'realestate/broker/';

		if (is_broker_logged_in()) {
			$this->load->model('Authentication_broker_model');

			$currentUser = $this->broker_model->get_broker_staff(get_broker_id());
			// Deleted or inactive but have session
			if (!$currentUser || $currentUser->active == 0) {
				$this->Authentication_broker_model->logout();
				redirect(site_url('realestate/broker'));
			}
			$GLOBALS['current_broker'] = $currentUser;
		}else{ 
			redirect_after_login_to_current_url();
			redirect(site_url('realestate/authentication_broker/login'));
		}
	}


	/**
	 * version 1.1.2
	 * seperation realestate portal
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

		$viewPath = !is_null($module) || $notInThemeViewFiles ? $this->view : 'brokers_portals/' . $this->view;

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


	/**
	 * index
	 * @return view
	 */
	public function index()
	{   
		if(!is_broker_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('realestate/authentication_broker/login'));
		}

		$data['title']            = _l('realestate_portal');
		
		$data['title'] = _l('clients_profile_heading');
		$broker_id = get_broker_id();
		$data['broker'] = $this->broker_model->get_broker_staff($broker_id);
		$data['csv'] = [];
		$this->load->model('currencies_model');
		$data['base_currency'] = $this->currencies_model->get_base_currency();

		$this->data($data);
		$this->view('brokers_portals/profiles/staff_profile');
		$this->layout();
		
	}

	/**
	 * profile
	 * @return [type] 
	 */
	public function profile()
	{
		if(!is_broker_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('realestate/authentication_broker/login'));
		}

		if ($this->input->post('profile')) {
			$this->form_validation->set_rules('firstname', _l('client_firstname'), 'required');
			$this->form_validation->set_rules('lastname', _l('client_lastname'), 'required');

			if ($this->form_validation->run() !== false) {

				$data = $this->input->post();
				if(isset($data['profile'])){
					unset($data['profile']);
				}
				$success = $this->broker_model->update($data, get_broker_id());

				handle_broker_profile_image_upload(get_broker_id());
				if ($success == true) {
					set_alert('success', _l('clients_profile_updated'));
				}

				redirect(site_url('realestate/broker/profile'));
			}
		} elseif ($this->input->post('change_password')) {
			$this->form_validation->set_rules('oldpassword', _l('clients_edit_profile_old_password'), 'required');
			$this->form_validation->set_rules('newpassword', _l('clients_edit_profile_new_password'), 'required');
			$this->form_validation->set_rules('newpasswordr', _l('clients_edit_profile_new_password_repeat'), 'required|matches[newpassword]');
			if ($this->form_validation->run() !== false) {
				
				$success = $this->broker_model->change_password($this->input->post(null, false), get_broker_id());

				if (is_array($success) && isset($success['old_password_not_match'])) {
					set_alert('danger', _l('client_old_password_incorrect'));
				} elseif ($success == true) {
					set_alert('success', _l('client_password_changed'));
				}

				redirect(site_url('realestate/broker/profile'));
			}
		}

		$broker_id = get_broker_id();
		$data['title'] = _l('clients_profile_heading');
		$data['broker'] = $this->broker_model->get_broker_staff($broker_id);
		$data['csv'] = [];
		$this->load->model('currencies_model');
		$data['base_currency'] = $this->currencies_model->get_base_currency();

		$this->data($data);
		$this->view('brokers_portals/profiles/staff_profile');
		$this->layout();
	}

	/* Remove staff profile image / ajax */
	public function remove_profile_image($id = '')
	{
		if($id == ''){
			$broker_id = get_broker_id();
		}
		
		hooks()->do_action('before_remove_broker_profile_image');
		$member = $this->broker_model->get_broker_staff($broker_id);

		if (file_exists(BROKER_PROFILE_UPLOAD . $broker_id)) {
			delete_dir(BROKER_PROFILE_UPLOAD . $broker_id);
		}
		$this->db->where('id', $broker_id);
		$this->db->update(db_prefix() . 'real_broker_staffs', [
			'profile_image' => null,
		]);

		redirect(site_url('realestate/broker/profile'));
	}

	/* Set notifications to read */
	public function set_notifications_read()
	{
		if ($this->input->is_ajax_request()) {
			echo json_encode([
				'success' => $this->realestate_model->set_notifications_read(),
			]);
		}
	}

	/**
	 * set notification read inline
	 * @param [type] $id 
	 */
	public function set_notification_read_inline($id)
	{
		$this->realestate_model->set_notification_read_inline($id);
	}

	/**
	 * set desktop notification read
	 * @param [type] $id 
	 */
	public function set_desktop_notification_read($id)
	{
		$this->realestate_model->set_desktop_notification_read($id);
	}

	/**
	 * mark all notifications as read inline
	 * @return [type] 
	 */
	public function mark_all_notifications_as_read_inline()
	{
		$this->realestate_model->mark_all_notifications_as_read_inline();
	}

	/**
	 * notifications check
	 * @return [type] 
	 */
	public function notifications_check()
	{
		$notificationsIds = [];
		if (get_option('desktop_notifications') == '1') {
			$notifications = $this->realestate_model->get_user_notifications();

			$notificationsPluck = array_filter($notifications, function ($n) {
				return $n['isread'] == 0;
			});

			$notificationsIds = array_pluck($notificationsPluck, 'id');
		}

		echo json_encode([
			'html'             => $this->load->view('brokers_portals/notifications', ['notifications_check' => true], true),
			'notificationsIds' => $notificationsIds,
		]);
	}

	/**
	 * profile
	 * @param  string $id 
	 * @return [type]     
	 */
	public function notifications_detail($id = '')
	{
		if ($id == '') {
			$id = get_candidate_id();
		}

		$data['staff_p']     = $this->realestate_model->get_candidate_v1($id);

		if (!$data['staff_p']) {
			blank_page('Candidate Not Found', 'danger');
		}

		$data['title']             = _l('staff_profile_string') . ' - ' . $data['staff_p']->candidate_name . ' ' . $data['staff_p']->last_name;
		// notifications
		$total_notifications = total_rows(db_prefix() . 'real_notifications', [
			'touserid' => get_candidate_id(),
		]);
		$data['total_pages'] = ceil($total_notifications / $this->realestate_model->get_candidate_notifications_limit());

		$this->data($data);
		$this->view('brokers_portals/notifications_detail');
		$this->layout();   

	}

	/**
	 * notifications
	 * @return [type] 
	 */
	public function notifications()
	{
		if ($this->input->post()) {
			$page   = $this->input->post('page');
			$offset = ($page * $this->realestate_model->get_candidate_notifications_limit());
			$this->db->limit($this->realestate_model->get_candidate_notifications_limit(), $offset);
			$this->db->where('touserid', get_candidate_id());
			$this->db->order_by('date', 'desc');
			$notifications = $this->db->get(db_prefix() . 'real_notifications')->result_array();
			$i             = 0;
			foreach ($notifications as $notification) {
				if (($notification['fromcompany'] == null && $notification['fromuserid'] != 0) || ($notification['fromcompany'] == null && $notification['fromclientid'] != 0)) {
					if ($notification['fromuserid'] != 0) {
						$notifications[$i]['profile_image'] = '<a href="#">' . staff_profile_image($notification['fromuserid'], [
							'staff-profile-image-small',
							'img-circle',
							'pull-left',
						]) . '</a>';
					} else {
						$notifications[$i]['profile_image'] = '<a href="#">
						<img class="client-profile-image-small img-circle pull-left" src="' . contact_profile_image_url($notification['fromclientid']) . '"></a>';
					}
				} else {
					$notifications[$i]['profile_image'] = '';
					$notifications[$i]['full_name']     = '';
				}
				$additional_data = '';
				if (!empty($notification['additional_data'])) {
					$additional_data = unserialize($notification['additional_data']);
					$x               = 0;
					foreach ($additional_data as $data) {
						if (strpos($data, '<lang>') !== false) {
							$lang = get_string_between($data, '<lang>', '</lang>');
							$temp = _l($lang);
							if (strpos($temp, 'project_status_') !== false) {
								$status = get_project_status_by_id(strafter($temp, 'project_status_'));
								$temp   = $status['name'];
							}
							$additional_data[$x] = $temp;
						}
						$x++;
					}
				}
				$notifications[$i]['description'] = _l($notification['description'], $additional_data);
				$notifications[$i]['date']        = time_ago($notification['date']);
				$notifications[$i]['full_date']   = $notification['date'];
				$i++;
			} //$notifications as $notification
			echo json_encode($notifications);
			die;
		}
	}

	/**
	 * change language
	 * @param  string $lang 
	 * @return [type]       
	 */
	public function change_language($lang = '')
	{
		hooks()->do_action('before_broker_change_language', $lang);

		$this->db->where('id', get_broker_id());
		$this->db->update(db_prefix() . 'real_broker_staffs', ['default_language' => $lang]);
		
		redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
	}

	/**
	 * add edit company
	 * @param string $id 
	 */
	public function add_edit_company($id = 0, $related_type = 'business_broker')
	{
		$id = get_business_broker_id();
		if ($this->input->post() && !$this->input->is_ajax_request()) {
			$data = $this->input->post();
			$data['password'] = $this->input->post('password', false);
			if ($id == 0) {
				
				$result_data['id'] = $this->realestate_model->add_construction_company($data);

				if (isset($result_data['id'])) {
					handle_staff_profile_image_upload($result_data['staff_id']);
					rel_handle_company_attachments_pdf($result_data['id'], 'file');
					set_alert('success', _l('added_successfully', _l('re_construction_company')));
				}
				if($data['related_type'] == 'company'){
					redirect(site_url('realestate/broker/companies'));
				}else{
					redirect(site_url('realestate/broker/add_edit_company'));
				}
			} else {

				$success = $this->realestate_model->update_construction_company($data, $id);
				rel_handle_company_attachments_pdf($id, 'file');

				if ($success == true) {
					set_alert('success', _l('updated_successfully', _l('re_construction_company')));
				}

				if($data['related_type'] == 'company'){
					redirect(site_url('realestate/broker/companies'));
				}else{
					redirect(site_url('realestate/broker/add_edit_company'));
				}
			}
		}

		$group         = !$this->input->get('group') ? 'add_edit_company' : $this->input->get('group');
		$data['group'] = $group;

		if ($group != 'staffs' && $contact_id = $this->input->get('contactid')) {
			redirect(site_url('realestate/broker/add_edit_company/' . $id . '?group=staffs&contactid=' . $contact_id));
		}

		$arr_approval_managers = [];

		if ($id == 0) {
			$title = _l('add_new', _l('re_construction_company'));
			$data['related_type'] = $related_type;
		} else {
			$construction_company                = $this->realestate_model->get_construction_company($id);
			$data['customer_tabs'] = get_customer_profile_tabs();

			if (!$construction_company) {
				show_404();
			}
			$data['group'] = $this->input->get('group');
			$data['title']                 = _l('setting');
			$data['tab'][] = ['name' => 'add_edit_company', 'icon' => '<i class="fa fa-user-circle menu-icon"></i>'];

			if($data['group'] == ''){
				$data['group'] = 'add_edit_company';
			}
			$data['tabs']['view'] = 'companies/companies/groups/'.$data['group'];

			$data['staff'] = $this->staff_model->get('', ['active' => 1]);

			$data['construction_company'] = $construction_company;
			$title          = $construction_company->name;
			$data['related_type'] = $construction_company->related_type;

				// Get all active staff members (used to add reminder)
			$data['members'] = $data['staff'];
			$data['pdf_attachment'] = $this->realestate_model->rel_get_attachments_file($id, 'rel_com_freelance');

			$map_listing_where = [];
			$map_listing_where[] = 'AND ('.db_prefix().'items.company_id = '.$id.')';
			$smapWhere = '';
			$map_listing_where = implode(' ', $map_listing_where);
			if ($smapWhere == '') {
				$map_listing_where = trim($map_listing_where);
				if (startsWith($map_listing_where, 'AND') || startsWith($map_listing_where, 'OR')) {
					if (startsWith($map_listing_where, 'OR')) {
						$map_listing_where = substr($map_listing_where, 2);
					} else {
						$map_listing_where = substr($map_listing_where, 3);
					}
				}
			}

			$data['map_property_listing'] = $this->realestate_model->map_get_property_listing($map_listing_where, false, '', '', 0, true, false, 'broker');

			// check add hash
			if($construction_company){
				if(is_null($construction_company->hash)){
					$construction_companies['hash'] = app_generate_hash();
					$this->db->where('id', $id);
					$this->db->update(db_prefix() . 'real_companies', $construction_companies);
				}
			}

		}
		$arr_approval_managers = $this->staff_model->get('', 'company_id=0');
		$data['arr_approval_managers'] = $arr_approval_managers;

		$this->load->model('currencies_model');
		$data['currencies'] = $this->currencies_model->get();

		if ($id != 0) {
			$customer_currency = $data['construction_company']->default_currency;

			foreach ($data['currencies'] as $currency) {
				if ($customer_currency != 0) {
					if ($currency['id'] == $customer_currency) {
						$customer_currency = $currency;

						break;
					}
				} else {
					if ($currency['isdefault'] == 1) {
						$customer_currency = $currency;

						break;
					}
				}
			}

			if (is_array($customer_currency)) {
				$customer_currency = (object) $customer_currency;
			}

			$data['customer_currency'] = $customer_currency;
		}

		$this->load->model('staff_model');
		
		$data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
		$data['bodyclass'] = 'customer-profile dynamic-create-groups';
		$data['staffs'] = $this->staff_model->get();
		$data['base_currency'] = $this->currencies_model->get_base_currency();
		if($data['related_type'] == 'company'){
			$data['roles']         = $this->realestate_model->get_role('', '(role_type = "plan_detail")');
			$data['plans']   = $this->realestate_model->get_plan(false, false);
		}else{
			$data['roles']         = $this->realestate_model->get_role('', 'role_type = "plan_detail"');
			$data['plans']   = $this->realestate_model->get_plan(false, false);
		}
		$data['departments']   = [];
		$data['title']     = $title;
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/companies/company', $data);
	}

	/**
	 * remove company profile image
	 * @param  string $id 
	 * @return [type]     
	 */
	public function remove_company_profile_image($id = '')
	{
		hooks()->do_action('before_remove_company_profile_image');

		if (file_exists(COMPANY_PROFILE_UPLOAD . $id)) {
			delete_dir(COMPANY_PROFILE_UPLOAD . $id);
		}
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_companies', [
			'profile_image' => null,
		]);

		redirect(site_url('realestate/broker/add_edit_company/'.$id));
	}

	/**
	 * preview file
	 * @param  [type] $id     
	 * @param  [type] $rel_id 
	 * @return [type]         
	 */
	public function preview_file($id, $rel_id)
	{
		$data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
		$data['current_user_is_admin'] = is_admin();
		$data['file'] = $this->misc_model->get_file($id, $rel_id);

		if (!$data['file']) {
			header('HTTP/1.0 404 Not Found');
			die;
		}

		$upload_path = SUPPORTING_DOCUMENT_UPLOAD;
		$upload_folder = 'supporting_documents';

		if($data['file']->rel_type == 'rel_com_freelance'){
			$upload_path = COMPANY_PDF_UPLOAD;
			$upload_folder = 'company_pdfs';
		}

		$data['upload_path'] = $upload_path;
		$data['upload_folder'] = $upload_folder;
		$this->load->view('clients/utilities/preview_file', $data);
	}

	/**
	 * delete realestate attachment
	 * @param  [type]  $attachment_id 
	 * @param  boolean $folder_name   
	 * @return [type]                 
	 */
	public function delete_realestate_attachment($attachment_id, $folder_name = false)
	{
		$_folder_name = COMPANY_PDF_UPLOAD;

		if($folder_name == 'COMPANY_PDF_UPLOAD'){
			$_folder_name = COMPANY_PDF_UPLOAD;
		}

		echo json_encode([
			'success' => $this->realestate_model->delete_real_attachment_file($attachment_id, $_folder_name),
		]);
	}

	/**
	 * property listings
	 * @return [type] 
	 */
	public function properties()
	{

		$data['switch_map'] = true;

		if ($this->session->userdata('listings_map_view') == 'true') {
			$data['switch_map'] = false;
			$data['bodyclass']     = 'kan-ban-body';
		}

		if(!is_null($this->input->get('my_listing'))){
			$data['my_listing'] = 1;
		}else{
			$data['my_listing'] = 0;
		}

		if(is_array($this->input->post()) && count($this->input->post()) > 0){
			$data['posts'] = $this->input->post();
			$data['dasboard_search_template_id'] = $this->input->post('search_template_id');
			$data['dasboard_search_template'] = $this->realestate_model->get_search_template_form_manage($this->input->post('search_template_id'));
			$data['dasboard_search_template_fields'] = rel_search_template_fields();
			$data['show_criteria'] = $this->input->post('show_criteria');
		}

		$data['title']                 = _l('real_properties');

		$admin_id='';
		$construction_company_id='';
		$agent_id='';
		$created_id='';

		$where = [];
		$search_template_where = [];
		$search_template_where[] = 'AND rel_type = "search_template"';

		$business_broker_id = get_business_broker_id();
		$map_listing_where = [];
		$map_listing_where[] = 'AND ('.db_prefix().'items.broker_id = '.$business_broker_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$business_broker_id.') )';
		$smapWhere = '';
		$map_listing_where = implode(' ', $map_listing_where);
		if ($smapWhere == '') {
			$map_listing_where = trim($map_listing_where);
			if (startsWith($map_listing_where, 'AND') || startsWith($map_listing_where, 'OR')) {
				if (startsWith($map_listing_where, 'OR')) {
					$map_listing_where = substr($map_listing_where, 2);
				} else {
					$map_listing_where = substr($map_listing_where, 3);
				}
			}
		}

		$data['map_property_listing'] = $this->realestate_model->map_get_property_listing($map_listing_where, false, '', '', 0, true, false, 'broker');


		$staff_in_construction_company = rel_check_staff_in_company();
		$data['isMap'] = $this->session->has_userdata('listings_map_view') &&
		$this->session->userdata('listings_map_view') == 'true';
		$data['site_url'] = $this->site_url;


		$this->load->view('companies/property_listings/manage', $data);
	}

	/**
	 * switch map
	 * @param  integer $set 
	 * @return [type]       
	 */
	public function switch_map($set = 0)
	{
		if ($set == 1) {
			$set = 'true';
		} else {
			$set = 'false';
		}
		$this->session->set_userdata([
			'listings_map_view' => $set,
		]);
		redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
	}

	/**
	 * property listing table
	 * @return [type] 
	 */
	public function property_listing_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/property_listings/property_listing_table'), ['rel_type' => 'broker',
			'site_url' => $this->site_url,
		]);
	}

	/**
	 * add edit property listing
	 * @param string $id 
	 */
	public function add_edit_property_listing($id = '')
	{
		if ($this->input->post()) {
			$data = $this->input->post();
			$data['long_description'] = $this->input->post('long_description', false);
			
			if ($id == '') {

				$broker_id = get_business_broker_id();
				$related_id = get_broker_id();

				$data['formdata'][] = [
					'name' => 'is_company_admin',
					'value' => 0,
				];
				$data['formdata'][] = [
					'name' => 'company_id',
					'value' => 0,
				];
				$data['formdata'][] = [
					'name' => 'broker_id',
					'value' => $broker_id,
				];
				$data['formdata'][] = [
					'name' => 'related_type',
					'value' => 'business_broker',
				];
				$data['formdata'][] = [
					'name' => 'related_id',
					'value' => $related_id,
				];

				$insert_id = $this->realestate_model->add_property_listing($data);
				$url = site_url('realestate/broker/properties');

				if ($insert_id) {
					set_alert('success', _l('added_successfully', _l('real_listing')));
					/*upload multifile*/
					echo json_encode([
						'url' => $url,
						'commodityid' => $insert_id,
						'add_or_update' => 'add',
					]);
					die;
				}

				set_alert('warning', _l('mrp_added_failed'));
				$url = site_url('realestate/broker/properties');

				echo json_encode([
					'url' => $url,
					'add_or_update' => 'add',

				]);
				die;

			} else {
				
				$success = $this->realestate_model->update_property_listing($data, $id);
				/*update file*/
				set_alert('success', _l('updated_successfully', _l('real_listing')));
				$url = site_url('realestate/broker/properties');

				echo json_encode([
					'url' => $url,
					'commodityid' => $id,
					'add_or_update' => 'update',
				]);
				die;
			}

		}

		if ($id == '') {
			$title = _l('real_add_new_listing');
			$data['room_templates'] = $this->realestate_model->create_property_listing_room_row_template();
		} else {
			
			$property_listing                = $this->realestate_model->get_property_listing($id);
			$data['property_listing']                = $property_listing;

			if (!$property_listing) {
				show_404();
			}

			$property_listing_room = $this->realestate_model->create_property_listing_room_row_template();
			if (isset($property_listing->listing_rooms) && count($property_listing->listing_rooms) > 0) {
				$index_receipt = 0;
				foreach ($property_listing->listing_rooms as $listing_room) {
					$index_receipt++;

					$property_listing_room .= $this->realestate_model->create_property_listing_room_row_template([], [], 'items[' . $index_receipt . ']', $listing_room['room_type'], $listing_room['rooms_level'], $listing_room['room_demension_width'], $listing_room['room_demension_lenght'], $listing_room['room_benefits'],  $listing_room['id'], true);
				}
			}

			$data['room_templates'] = $property_listing_room;
			$title          = _l('rel_edit_listing').' '.$property_listing->description;
			$data['product_attachments'] = $this->realestate_model->rel_get_attachments_file($id, 'commodity_item_file');
			$data['product_videos'] = $this->realestate_model->rel_get_attachments_file($id, 'property_video');
			$data['product_attachment_pdfs'] = $this->realestate_model->rel_get_attachments_file($id, 'real_listing_pdf');
			$data['brokers'] = $this->realestate_model->get_construction_company(false, 'related_type = "business_broker"');
			$data['property_agents'] = $this->realestate_model->get_construction_company(false, 'related_type = "company"');
		}

		$this->load->model('staff_model');
		$this->load->model('invoice_items_model');
		$data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
		$data['groups'] = $this->realestate_model->get_group_form_manage();
		$data['schools'] = $this->realestate_model->get_school_form_manage();
		$data['landmarks'] = $this->realestate_model->get_landmark_form_manage();
		$data['hopspitals'] = $this->realestate_model->get_hopspital_form_manage();
		$data['property_owners'] = $this->realestate_model->get_owner();
		$data['site_url'] = $this->site_url;
		$data['title']     = $title;
		$data['drawing_search_history'] = [];
		$data['_property_code'] = $this->realestate_model->create_code('property');

		$this->load->view('companies/property_listings/add_edit_property_listing', $data);
	}

	/**
	 * get_country_id
	 * @param  [type] $country_iso2 
	 * @return [type]               
	 */
	public function get_country_id($country_iso2) {
		$country_id = 0;
		$this->db->where('iso2', $country_iso2);
		$country = $this->db->get(db_prefix() . 'countries')->row();
		if($country){

			$country_id = $country->country_id;
		}

		echo json_encode([
			'country_id' => $country_id,
		]);
	}

	/**
	 * get room row template
	 * @return [type] 
	 */
	public function get_room_row_template()
	{
		$name = $this->input->post('name');
		$room_type = $this->input->post('room_type');
		$rooms_level = $this->input->post('rooms_level');
		$room_demension_width = $this->input->post('room_demension_width');
		$room_demension_lenght = $this->input->post('room_demension_lenght');
		$room_benefits = $this->input->post('room_benefits');
		$item_key = $this->input->post('item_key');

		echo $this->realestate_model->create_property_listing_room_row_template([], [], $name, $room_type, $rooms_level, $room_demension_width, $room_demension_lenght, $room_benefits, $item_key);

	}

	/**
	 * add product attachment
	 * @param [type] $id          
	 * @param [type] $rel_type    
	 * @param string $add_variant 
	 */
	public function add_property_listing_attachment($id)
	{
		real_handle_property_listing_attachments($id);
		$url = site_url('realestate/broker/properties');
		echo json_encode([
			'url' => $url,
			'id' => $id,
		]);
	}

	/**
	 * delete property listing attachment
	 * @param  [type] $attachment_id 
	 * @param  [type] $rel_type      
	 * @return [type]                
	 */
	public function delete_property_listing_attachment($attachment_id, $folder_name = false)
	{

		$_folder_name = PROPERTY_UPLOAD;

		if($folder_name == 'PROPERTY_VIDEO_UPLOAD'){
			$_folder_name = PROPERTY_VIDEO_UPLOAD;
		}elseif($folder_name == 'SUPPORTING_DOCUMENT_UPLOAD'){
			$_folder_name = SUPPORTING_DOCUMENT_UPLOAD;
		}elseif($folder_name == 'PROOF_INCOME_UPLOAD'){
			$_folder_name = PROOF_INCOME_UPLOAD;
		}elseif($folder_name == 'IDENTIFY_DOCUMENT_UPLOAD'){
			$_folder_name = IDENTIFY_DOCUMENT_UPLOAD;
		}

		echo json_encode([
			'success' => $this->realestate_model->delete_real_attachment_file($attachment_id, $_folder_name),
		]);
	}

	/**
	 * add property listing attachment1
	 * @param [type] $id 
	 */
	public function add_property_listing_attachment1($id)
	{
		real_handle_property_listing_attachments1($id);
		$url = site_url('realestate/broker/properties');
		echo json_encode([
			'url' => $url,
			'id' => $id,
		]);
	}

	/**
	 * listing pdf file
	 * @param  [type] $id     
	 * @param  [type] $rel_id 
	 * @return [type]         
	 */
	public function listing_pdf_file($id, $rel_id)
	{
		$data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
		$data['current_user_is_admin'] = is_admin();
		$data['file'] = $this->misc_model->get_file($id, $rel_id);
		if (!$data['file']) {
			header('HTTP/1.0 404 Not Found');
			die;
		}
		$this->load->view('companies/property_listings/preview_pdf_file', $data);
	}

	/**
	 * delete listing attachment pdf file
	 * @param  [type] $attachment_id 
	 * @return [type]                
	 */
	public function delete_listing_attachment_pdf_file($attachment_id)
	{

		$file = $this->misc_model->get_file($attachment_id);
		echo json_encode([
			'success' => $this->realestate_model->delete_listing_attachment_pdf_file($attachment_id),
		]);
	}

	/**
	 * delete property listing
	 * @param  [type] $id       
	 * @param  [type] $rel_type 
	 * @return [type]           
	 */
	public function delete_property_listing($id)
	{
		if (!$id) {
			redirect(site_url('realestate/broker/property_listings'));
		}

		$response = $this->realestate_model->delete_property_listing($id, $rel_type);
		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('is_referenced', _l('real_property')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		
		redirect(site_url('realestate/broker/properties'));
	}

	/**
	 * add property listing attachment2
	 * @param [type] $id 
	 */
	public function add_property_listing_attachment2($id)
	{
		real_handle_property_listing_attachments2($id);
		$url = site_url('realestate/broker/properties');
		echo json_encode([
			'url' => $url,
			'id' => $id,
		]);
	}

	/**
	 * add property listing attachment3
	 * @param [type] $id 
	 */
	public function add_property_listing_attachment3($id)
	{
		real_handle_property_listing_attachments3($id);
		$url = site_url('realestate/broker/properties');
		echo json_encode([
			'url' => $url,
			'id' => $id,
		]);
	}

	/**
	 * reload map
	 * @return [type] 
	 */
	public function reload_map()
	{
		$map_property_listing = $this->realestate_model->render_query_data_for_map('broker');

		echo json_encode([
			'map_property_listing' => $map_property_listing,
		]);
	}

	/**
	 * property listing detail
	 * @param  string $id 
	 * @return [type]     
	 */
	public function property_listing_detail($id='')
	{
		if (!is_numeric($id)) {
			show_404();
		}

		$property_listing                = $this->realestate_model->get_property_listing($id);
		$data['property_listing']                = $property_listing;

		if (!$property_listing) {
			show_404();
		}

		// check add hash
		if($property_listing){
			if(is_null($property_listing->hash)){
				$this->db->where('id', $id);
				$this->db->update(db_prefix() . 'items', ['hash' => app_generate_hash()]);
			}
		}

		// add listing Recent

		$staff_user_id = get_staff_user_id();

		$admin_id = 0;
		$construction_company_id = 0;
		$agent_id = 0;
		$created_id = $staff_user_id;

		$staff_type = rel_check_staff_type();

		if(is_admin()){
			$admin_id = $staff_user_id;
		}else{
			switch ($staff_type['staff_type']) {
				case 'staff':

				break;

				case 'company':
				$construction_company_id = $staff_type['construction_company_id'];

				break;

				case 'property_agent':
				$construction_company_id = $staff_type['construction_company_id'];
				$agent_id = $staff_type['agent_id'];
				break;

				case 'freelance_staff':

				break;

				default:

				break;
			}
		}


		$data['product_attachments'] = $this->realestate_model->rel_get_attachments_file($id, 'commodity_item_file');
		$data['product_attachment_pdfs'] = $this->realestate_model->rel_get_attachments_file($id, 'real_listing_pdf');
		$data['property_assets'] = $this->realestate_model->get_property_asset($id);
		$data['product_videos'] = $this->realestate_model->rel_get_attachments_file($id, 'property_video');

		$data['title']                 = $property_listing->description;
		$public_profile_url = '';
		
		$data['public_profile_url'] = $public_profile_url;
		$data['request_brokers'] = $this->realestate_model->get_request_broker(false, "item_id = ".$property_listing->id, $property_listing->id);
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/property_listings/property_listing_detail', $data);
	}

	/**
	 * property owners
	 * @return [type] 
	 */
	public function property_owners()
	{
		$data['title']                 = _l('real_property_owners');
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/property_owners/manage', $data);
	}

	/**
	 * property owner table
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function property_owner_table($company_id = 0)
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/property_owners/owner_table'), [
			'company_id' => $company_id,
			'site_url' => $this->site_url,
		]);
	}

	/**
	 * add edit owner
	 * @param string $id 
	 */
	public function add_edit_owner($id = '', $company_id = '')
	{
		if ($this->input->post() && !$this->input->is_ajax_request()) {
			$data = $this->input->post();

			if ($id == '') {
				$broker_id = get_business_broker_id();
				$related_id = get_broker_id();

				$data['is_company_admin'] = 0;
				$data['company_id'] = 0;
				$data['broker_id'] = $broker_id;
				$data['related_type'] = 'business_broker';
				$data['related_id'] = $related_id;
				
				$result_data['id'] = $this->realestate_model->add_owner($data);

				if (isset($result_data['id'])) {
					handle_owner_profile_image_upload($result_data['id']);
					set_alert('success', _l('added_successfully', _l('real_property_owner')));
				}
				redirect(site_url('realestate/broker/property_owners'));
			} else {
				$success = $this->realestate_model->update_owner($data, $id);
				handle_owner_profile_image_upload($id);

				if ($success == true) {
					set_alert('success', _l('updated_successfully', _l('real_property_owner')));
				}
				redirect(site_url('realestate/broker/add_edit_owner/'.$id));
			}
		}

		$group         = !$this->input->get('group') ? 'add_edit_owner' : $this->input->get('group');
		$data['group'] = $group;

		if ($id == '' || $id == 0) {
			
			$title = _l('add_new', _l('real_property_owner'));
			$data['is_add'] = true;
			$data['owner_code'] = $this->realestate_model->create_code('owner');

		} else {
			$owner                = $this->realestate_model->get_owner($id);
			$data['customer_tabs'] = get_customer_profile_tabs();

			if (!$owner) {
				show_404();
			}
			$data['group'] = $this->input->get('group');
			$data['tab'][] = ['name' => 'add_edit_owner', 'icon' => '<i class="fa fa-user-circle menu-icon"></i>'];
			if($data['group'] == ''){
				$data['group'] = 'add_edit_owner';
			}
			$data['tabs']['view'] = 'companies/property_owners/groups/'.$data['group'];

			$data['owner'] = $owner;
			$title          = $owner->name;

			if($owner){
				if(is_null($owner->hash)){
					$owner_data['hash'] = app_generate_hash();
					$this->db->where('id', $id);
					$this->db->update(db_prefix() . 'real_property_owners', $owner_data);
				}
			}
		}
		$data['title']     = $title;
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/property_owners/owner', $data);
	}

	/**
	 * delete owner
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_owner($id)
	{
		$success = $this->realestate_model->delete_owner($id);
		if ($success) {
			set_alert('success', _l('rel_deleted_successfully'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(site_url('realestate/broker/property_owners'));
	}

	/**
	 * agent exists
	 * @return [type] 
	 */
	public function owner_exists()
	{
		if ($this->input->is_ajax_request()) {
			if ($this->input->post()) {
				// First we need to check if the email is the same
				$member_id = $this->input->post('memberid');
				if ($member_id != '') {
					$this->db->where('id', $member_id);
					$_current_email = $this->db->get(db_prefix() . 'real_property_owners')->row();
					if ($_current_email->email == $this->input->post('email')) {
						echo json_encode(true);
						die();
					}
				}
				$this->db->where('email', $this->input->post('email'));
				$total_rows = $this->db->count_all_results(db_prefix() . 'real_property_owners');
				if ($total_rows > 0) {
					echo json_encode(false);
				} else {
					echo json_encode(true);
				}
				die();
			}
		}
	}

	/**
	 * change owner status
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_owner_status($id, $status)
	{
		if ($this->input->is_ajax_request()) {
			$this->realestate_model->change_property_owner_status($id, $status);
		}
	}

	/**
	 * real estate add group name
	 * @return [type] 
	 */
	public function real_estate_add_group_name()
	{
		if ($this->input->is_ajax_request()) {
			$data = $this->input->get();
			$status = false;
			$option = '';

			if(isset($data['csrf_token_name'])){
				unset($data['csrf_token_name']);
			}

			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$data['is_company_admin'] = 0;
			$data['company_id'] = 0;
			$data['broker_id'] = $broker_id;
			$data['related_type'] = 'business_broker';
			$data['related_id'] = $related_id;

			$status_id = $this->realestate_model->add_group_form_manage($data);
			if($status_id){
				$status = true;
				$option = '<option value="'.$status_id.'" selected>'.$data['name'].'</option>';
			}

			echo json_encode([
				'status' => $status,
				'option' => $option,
			]);
			die;
		}
	}

	/**
	 * real estate add school name
	 * @return [type] 
	 */
	public function real_estate_add_school_name()
	{
		if ($this->input->is_ajax_request()) {
			$data = $this->input->get();
			$status = false;
			$option = '';

			if(isset($data['csrf_token_name'])){
				unset($data['csrf_token_name']);
			}

			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$data['is_company_admin'] = 0;
			$data['company_id'] = 0;
			$data['broker_id'] = $broker_id;
			$data['related_type'] = 'business_broker';
			$data['related_id'] = $related_id;

			$data['active'] = 1;
			$status_id = $this->realestate_model->add_school_form_manage($data);
			if($status_id){
				$status = true;
				$option = '<option value="'.$status_id.'" selected>'.$data['name'].'</option>';
			}

			echo json_encode([
				'status' => $status,
				'option' => $option,
			]);
			die;
		}
	}

	/**
	 * real estate add landmark name
	 * @return [type] 
	 */
	public function real_estate_add_landmark_name()
	{
		if ($this->input->is_ajax_request()) {
			$data = $this->input->get();
			$status = false;
			$option = '';

			if(isset($data['csrf_token_name'])){
				unset($data['csrf_token_name']);
			}

			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$data['is_company_admin'] = 0;
			$data['company_id'] = 0;
			$data['broker_id'] = $broker_id;
			$data['related_type'] = 'business_broker';
			$data['related_id'] = $related_id;
			$data['active'] = 1;
			$status_id = $this->realestate_model->add_landmark_form_manage($data);
			if($status_id){
				$status = true;
				$option = '<option value="'.$status_id.'" selected>'.$data['name'].'</option>';
			}

			echo json_encode([
				'status' => $status,
				'option' => $option,
			]);
			die;
		}
	}

	/**
	 * real estate add hopspital name
	 * @return [type] 
	 */
	public function real_estate_add_hopspital_name()
	{
		if ($this->input->is_ajax_request()) {
			$data = $this->input->get();
			$status = false;
			$option = '';

			if(isset($data['csrf_token_name'])){
				unset($data['csrf_token_name']);
			}

			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$data['is_company_admin'] = 0;
			$data['company_id'] = 0;
			$data['broker_id'] = $broker_id;
			$data['related_type'] = 'business_broker';
			$data['related_id'] = $related_id;
			$data['active'] = 1;
			$status_id = $this->realestate_model->add_hopspital_form_manage($data);
			if($status_id){
				$status = true;
				$option = '<option value="'.$status_id.'" selected>'.$data['name'].'</option>';
			}

			echo json_encode([
				'status' => $status,
				'option' => $option,
			]);
			die;
		}
	}

	/**
	 * get property owner information
	 * @param  string $owner_id 
	 * @return [type]           
	 */
	public function get_property_owner_information($owner_id = '')
	{
		$owner_name = '';
		$owner_phone = '';
		$owner_email = '';
		$property_owner = $this->realestate_model->get_owner($owner_id);
		if($property_owner){
			$owner_name = $property_owner->name;
			$owner_phone = $property_owner->phonenumber;
			$owner_email = $property_owner->email;
		}

		echo json_encode([
			'owner_name' => $owner_name,
			'owner_phone' => $owner_phone,
			'owner_email' => $owner_email,
		]);
	}

	/**
	 * property requests
	 * @param  string $id 
	 * @return [type]     
	 */
	public function requests($id = '')
	{

		$data['title'] = _l('real_requests');
		$data['clients'] = $this->clients_model->get();
		$data['propertyrequestid']            = $id;
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/property_requests/property_request', $data);
	}

	/**
	 * property request table
	 * @return [type] 
	 */
	public function property_request_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/property_requests/property_request_table'), [
			'site_url' => $this->site_url,
			'request_type' => 'buy',
		]);
	}

	/**
	 * add edit property request
	 * @param string $id 
	 */
	public function add_edit_property_request($id = '', $request_type = 'buy')
	{

		$request_type = 'buy';
		if($this->input->get('request_type')){
			$request_type = $this->input->get('request_type');
		}

		if ($this->input->post()) {
			$data = $this->input->post();
			$id = $this->input->post('id');

			if ($id == '') {

				$broker_id = get_business_broker_id();
				$related_id = get_broker_id();

				$data['is_company_admin'] = 0;
				$data['company_id'] = 0;
				$data['broker_id'] = $broker_id;
				$data['related_type'] = 'business_broker';
				$data['related_id'] = $related_id;

				$insert_id = $this->realestate_model->add_property_request($data);
				if ($insert_id) {
					set_alert('success', _l('added_successfully'));
				}
				if($data['request_type'] == 'buy'){
					redirect(site_url('realestate/broker/requests'));
				}else{
					redirect(site_url('realestate/broker/rent_requests'));
				}

			} else {
				
				$success = $this->realestate_model->update_property_request($data, $id);
				$property_request = $this->realestate_model->get_property_request($id);
				$request_type = $property_request->request_type;

				set_alert('success', _l('updated_successfully'));
				if($request_type == 'buy'){
					redirect(site_url('realestate/broker/requests'));
				}else{
					redirect(site_url('realestate/broker/rent_requests'));
				}
			}
		}
		
		$data=[];
		$data['title'] = _l('real_new_property_request');
		
		if ($id != ''){
			$property_request = $this->realestate_model->get_property_request($id);
			$data['property_request'] = $property_request;
			$data['title'] = _l('real_update_property_request');
			$request_type = $property_request->request_type;
			$get_property_listing = $this->realestate_model->get_property_listing($property_request->item_id);
			$data['property'][] = (array)$get_property_listing;
			$data['rental_type'] = $get_property_listing->rental_type;
		}

		$transaction_type = 'Sale';
		if($request_type == 'buy'){
			$transaction_type = 'Sale';
		}else{
			$transaction_type = 'Rent';
		}
		$data['items']     = $this->realestate_model->get_property_listing(false, "transaction_type = '".$transaction_type."' AND status != 'pending' AND status != 'pending_sale' AND status != 'sold' AND status != 'rented' AND broker_id = ".get_business_broker_id());
		$data['staffs'] = $this->staff_model->get();
		$data['clients'] = $this->clients_model->get();
		$data['code'] = 'REQ'.date('YmdHis');
		$data['request_type'] = $request_type;
		$data['site_url'] = $this->site_url;

		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$data['base_currency_id'] = $get_base_currency->id;
		}else{
			$data['base_currency_id'] = 0;
		}

		$this->load->view('companies/property_requests/add_edit_property_request', $data);
	}

	/**
	 * calculate estimated request duration
	 * @return [type] 
	 */
	public function calculate_estimated_request_duration()
	{
		if ($this->input->is_ajax_request()) {
			$data = $this->input->post();
			$start_date = $data['start_date'];
			$term_month = $data['term_month'];
			$property = $this->realestate_model->get_property_listing($data['item_id']);
			$end_date = _d(date("Y-m-d", strtotime(to_sql_date($data['start_date']) . ' +'.$data['term_month'].' month')));

			if($property && isset($property->transaction_type) && $property->transaction_type == 'Rent'){
				switch ($property->rental_type) {
					case 'day':
					$end_date = _d(date("Y-m-d", strtotime(to_sql_date($data['start_date']) . ' +'.$data['term_month'].' days')));

					break;
					case 'week':
					$end_date = _d(date("Y-m-d", strtotime(to_sql_date($data['start_date']) . ' +'.$data['term_month'].'weeks')));
					break;
					case 'month':
					$end_date = _d(date("Y-m-d", strtotime(to_sql_date($data['start_date']) . ' +'.$data['term_month'].' month')));

					break;
					case 'year':
					$end_date = _d(date("Y-m-d", strtotime(to_sql_date($data['start_date']) . ' +'.$data['term_month'].'years')));
					break;

					default:
					$end_date = _d(date("Y-m-d", strtotime(to_sql_date($data['start_date']) . ' +'.$data['term_month'].' month')));

					break;
				}
			}

			echo json_encode([
				'term_month' => $term_month,
				'start_date' => $start_date,
				'end_date' => $end_date,
			]);die;

		}
	}

	/**
	 * delete property request
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_property_request($id) {

		$request = $this->realestate_model->get_property_request($id);
		$response = $this->realestate_model->delete_property_request($id);
		if ($response == true) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}

		if($request->request_type == 'buy'){
			redirect(site_url('realestate/broker/requests'));
		}else{
			redirect(site_url('realestate/broker/rent_requests'));
		}
	}

	/**
	 * get property request data ajax
	 * @param  [type]  $id        
	 * @param  boolean $to_return 
	 * @return [type]             
	 */
	public function get_property_request_data_ajax($id, $to_return = false)
	{

		if (!$id) {
			die('No Property Request found');
		}

		$property_request = $this->realestate_model->get_property_request($id);

		$property_request->date       = _d($property_request->date);
		$property_request->expirydate = _d($property_request->duedate);
		if ($property_request->invoice_id !== null && $property_request->invoice_id !== 0) {
			$this->load->model('invoices_model');
			$property_request->invoice = $this->invoices_model->get($property_request->invoice_id);
		}

		$template_name = 'property_request_send_to_client';

		$data = real_prepare_mail_preview_data( $template_name, $property_request->clientid, [0 => 'realestate']);

		$data['property_request']          = $property_request;
		$data['members']           = $this->staff_model->get('', ['active' => 1]);
		$data['totalNotes']        = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'property_request']);

		$data['send_later'] = false;
		$data['site_url'] = $this->site_url;
		if ($this->session->has_userdata('send_later')) {
			$data['send_later'] = true;
			$this->session->unset_userdata('send_later');
		}

		if ($to_return == false) {
			$this->load->view('companies/property_requests/property_request_preview_template', $data);
		} else {
			return $this->load->view('companies/property_requests/property_request_preview_template', $data, true);
		}
	}

	/**
	 * convert to contract
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function convert_to_contract($id)
	{
		if (!$id) {
			die('No Request found');
		}

		$contractid = $this->realestate_model->convert_to_contract($id, false);
		if ($contractid) {
			set_alert('success', _l('real_request_convert_to_contract_successfully'));
			redirect(site_url('realestate/broker/contract/' . $contractid));
		} else {

			redirect(site_url('realestate/broker/requests'));
		}
	}

	/* Convert estimate to invoice */
	public function convert_to_invoice($id)
	{
		if (!$id) {
			die('No Request found');
		}
		$draft_invoice = false;
		if ($this->input->get('save_as_draft')) {
			$draft_invoice = true;
		}
		$invoiceid = $this->realestate_model->convert_to_invoice($id, false, $draft_invoice);
		if ($invoiceid) {
			set_alert('success', _l('real_request_convert_to_invoice_successfully'));
			redirect(site_url('realestate/broker/list_invoices#' . $invoiceid));
		} else {

			redirect(site_url('realestate/broker/requests'));
		}
	}

	/**
	 * property request status mark as
	 * @param  [type] $status 
	 * @param  [type] $id     
	 * @param  [type] $type   
	 * @return [type]         
	 */
	public function property_request_status_mark_as($status, $id, $type)
	{
		$success = $this->realestate_model->property_request_status_mark_as($status, $id, $type);
		$message = '';

		if ($success) {
			$message = _l('real_change_request_status_successfully');
		}
		echo json_encode([
			'success'  => $success,
			'message'  => $message
		]);die;
	}

	/* Get item by id / ajax */
	public function get_property_by_id($id)
	{
		if ($this->input->is_ajax_request()) {
			$base_currency_id = get_base_currency_id();
			$properties = [];
			$property_col = '';
			$transaction_type = '';
			$rate = '';
			$rent_price = '';
			$rental_value = '';
			$rental_type = '';
			$property                     = $this->realestate_model->get_property_listing($id);
			$rent_label = '';
			if($property){
				$transaction_type = $property->transaction_type;
				$rate = $property->rate;
				$rent_price = $property->rent_price;
				$rental_value = $property->rental_value;
				$rental_type = _l('invoice_recurring_'.$property->rental_type.'s');
				$properties[] = (array)$property;
				$rent_label = $rental_type;
				
				if($property->transaction_type == 'Sale'){
					$rental_value = 1;
				}
			}

			echo json_encode([
				'transaction_type' => $transaction_type,
				'rate' => $rate,
				'rent_price' => $rent_price,
				'rental_value' => $rental_value,
				'rental_type' => $rental_type,
				'rent_label' => $rent_label,
				'property_html' => $this->load->view('companies/property_listings/utilities/room_item', ['properties' => $properties, 'property_col' => $property_col], true),
			]);
		}
	}

	/**
	 * client change data
	 * @param  [type] $customer_id     
	 * @param  string $current_invoice 
	 * @return [type]                  
	 */
	public function client_change_data($customer_id, $current_invoice = '')
	{
		if ($this->input->is_ajax_request()) {
			$this->load->model('projects_model');
			$data                     = [];
			$data['billing_shipping'] = $this->clients_model->get_customer_billing_and_shipping_details($customer_id);
			$data['client_currency']  = $this->clients_model->get_customer_default_currency($customer_id);
			$this->load->model('currencies_model');

			echo json_encode($data);
		}
	}

	/**
	 * property request pdf
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function property_request_pdf($id)
	{
		if (!$id) {
			redirect(site_url('realestate/broker/requests'));
		}
		$this->load->model('clients_model');
		$this->load->model('currencies_model');

		$property_request_number = '';
		$property_request = $this->realestate_model->get_property_request($id);

		$base_currency = $this->currencies_model->get_base_currency();
		$currency = $base_currency;
		if(is_numeric($property_request->currency) && $property_request->currency != 0){
			$currency = $property_request->currency;
		}

		$property_request->client = $this->clients_model->get($property_request->clientid);
		$property_request->currency = $currency;

		if($property_request){
			$property_request_number .= $property_request->code;
		}
		try {
			$pdf = $this->realestate_model->property_request_pdf($property_request);

		} catch (Exception $e) {
			echo new_html_entity_decode($e->getMessage());
			die;
		}

		$type = 'D';
		ob_end_clean();

		if ($this->input->get('output_type')) {
			$type = $this->input->get('output_type');
		}

		if ($this->input->get('print')) {
			$type = 'I';
		}

		$pdf->Output(mb_strtoupper(slug_it($property_request_number)).'.pdf', $type);
	}

	/**
	 * send_to_email
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function send_to_email($id)
	{
		try {
			$success = $this->realestate_model->send_property_request_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
		} catch (Exception $e) {
			$message = $e->getMessage();
			echo $message;
			if (strpos($message, 'Unable to get the size of the image') !== false) {
				show_pdf_unable_to_get_image_size_error();
			}
			die;
		}

		// In case client use another language
		load_admin_language();
		if ($success) {
			set_alert('success', _l('estimate_sent_to_client_success'));
		} else {
			set_alert('danger', _l('estimate_sent_to_client_fail'));
		}
		
		$request = $this->realestate_model->get_property_request($id);
		if($request->request_type == 'buy'){
			redirect(site_url('realestate/broker/requests#' . $id));
		}else{
			redirect(site_url('realestate/broker/rent_requests#' . $id));
		}
	}

	/**
	 * property requests
	 * @param  string $id 
	 * @return [type]     
	 */
	public function rent_requests($id = '')
	{
		
		$data['title'] = _l('real_requests');
		$data['clients'] = $this->clients_model->get();
		$data['propertyrequestid']            = $id;
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/property_request_rents/property_request_rent', $data);
	}

	/**
	 * property request table
	 * @return [type] 
	 */
	public function property_request_rent_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/property_requests/property_request_table'), [
			'site_url' => $this->site_url,
			'request_type' => 'rent',
		]);
	}

	/**
	 * get currency
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_currency($id)
	{
		echo json_encode(get_currency($id));
	}

	/**
	 * get taxes dropdown template
	 * @return [type] 
	 */
	public function get_taxes_dropdown_template()
	{
		$name    = $this->input->post('name');
		$taxname = $this->input->post('taxname');
		echo $this->misc_model->get_taxes_dropdown_template($name, $taxname);
	}

	/**
	 * get relation data
	 * @return [type] 
	 */
	public function get_relation_data()
	{
		if ($this->input->post()) {
			$type = $this->input->post('type');
			$data = get_relation_data($type, '', $this->input->post('extra'));

			if ($this->input->post('rel_id')) {
				$rel_id = $this->input->post('rel_id');
			} else {
				$rel_id = '';
			}

			$relOptions = broker_init_relation_options($data, $type, $rel_id);
			echo json_encode($relOptions);
			die;
		}
	}

	/* List all contracts start */
	public function contracts()
	{
		close_setup_menu();

		$data['expiring']               = $this->broker_model->get_contracts_about_to_expire(get_business_broker_id());
		$data['count_active']           = broker_count_active_contracts();
		$data['count_expired']          = broker_count_expired_contracts();
		$data['count_recently_created'] = broker_count_recently_created_contracts();
		$data['count_trash']            = broker_count_trash_contracts();
		$data['chart_types']            = json_encode($this->broker_model->get_contracts_types_chart_data());
		$data['chart_types_values']     = json_encode($this->broker_model->get_contracts_types_values_chart_data());
		$data['contract_types']         = $this->broker_model->get_contract_types();
		$data['years']                  = $this->broker_model->get_contracts_years();
		$this->load->model('currencies_model');
		$data['base_currency'] = $this->currencies_model->get_base_currency();
		$data['title']         = _l('contracts');
		$this->load->view('brokers_portals/contracts/manage', $data);
	}

	/**
	 * contract table
	 * @param  string $clientid 
	 * @return [type]           
	 */
	public function contract_table($clientid = '')
	{
		$this->app->get_table_data(module_views_path('realestate', 'brokers_portals/contracts/contract_table'), [
			'clientid' => $clientid,
		]);

	}

	/* Edit contract or add new contract */
	public function contract($id = '')
	{
		if ($this->input->post()) {
			if ($id == '') {
				$data     = $this->input->post();
				$broker_id = get_business_broker_id();
				$related_id = get_broker_id();

				$data['is_company_admin'] = 0;
				$data['company_id'] = 0;
				$data['broker_id'] = $broker_id;
				$data['related_type'] = 'business_broker';
				$data['related_id'] = $related_id;

				$id = $this->contracts_model->add($data);
				if ($id) {
					set_alert('success', _l('added_successfully', _l('contract')));
					redirect(site_url('realestate/broker/contract/' . $id));
				}
			} else {
				
				$contract = $this->contracts_model->get($id);
				$data     = $this->input->post();

				if ($contract->signed == 1) {
					unset($data['contract_value'],$data['clientid'], $data['datestart'], $data['dateend']);
				}

				$success = $this->contracts_model->update($data, $id);
				if ($success) {
					set_alert('success', _l('updated_successfully', _l('contract')));
				}
				redirect(site_url('realestate/broker/contract/' . $id));
			}
		}
		if ($id == '') {
			$title = _l('add_new', _l('contract_lowercase'));
		} else {

			$data['contract']                 = $this->contracts_model->get($id, [], true);
			$data['contract_renewal_history'] = $this->contracts_model->get_contract_renewal_history($id);
			$data['totalNotes']               = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'contract']);
			if (!$data['contract']) {
				redirect(site_url('contract_not_found'));
			}

			$data['contract_merge_fields'] = $this->app_merge_fields->get_flat('contract', ['other', 'client'], '{email_signature}');

			$title = $data['contract']->subject;

			$data = array_merge($data, prepare_mail_preview_data('contract_send_to_customer', $data['contract']->client));
		}
		if ($this->input->get('customer_id')) {
			$data['customer_id'] = $this->input->get('customer_id');
		}

		$this->load->model('currencies_model');
		$data['base_currency'] = $this->currencies_model->get_base_currency();
		$data['types']         = $this->broker_model->get_contract_types();
		$data['title']         = $title;
		$data['bodyclass']     = 'contract';
		$data['site_url'] = $this->site_url;
		$this->load->view('brokers_portals/contracts/contract', $data);
	}

	/**
	 * get template
	 * @return [type] 
	 */
	public function get_template()
	{
		$name = $this->input->get('name');
		echo $this->load->view('brokers_portals/contracts/templates/' . $name, [], true);
	}

	/**
	 * mark as signed
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mark_as_signed($id)
	{

		$this->contracts_model->mark_as_signed($id);

		redirect(site_url('realestate/broker/contract/' . $id));
	}

	/**
	 * unmark as signed
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function unmark_as_signed($id)
	{
		

		$this->contracts_model->unmark_as_signed($id);

		redirect(site_url('realestate/broker/contract/' . $id));
	}

	/**
	 * contract pdf
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function contract_pdf($id)
	{

		if (!$id) {
			redirect(site_url('realestate/broker/contracts'));
		}

		$contract = $this->contracts_model->get($id);

		try {
			$pdf = contract_pdf($contract);
		} catch (Exception $e) {
			echo $e->getMessage();
			die;
		}

		$type = 'D';

		if ($this->input->get('output_type')) {
			$type = $this->input->get('output_type');
		}

		if ($this->input->get('print')) {
			$type = 'I';
		}

		$pdf->Output(slug_it($contract->subject) . '.pdf', $type);
	}

	/**
	 * send contract to email
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function send_contract_to_email($id)
	{

		$success = $this->contracts_model->send_contract_to_client($id, $this->input->post('attach_pdf'), $this->input->post('cc'));
		if ($success) {
			set_alert('success', _l('contract_sent_to_client_success'));
		} else {
			set_alert('danger', _l('contract_sent_to_client_fail'));
		}
		redirect(site_url('realestate/broker/contract/' . $id));
	}

	/**
	 * clear signature
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function clear_signature($id)
	{
		$this->contracts_model->clear_signature($id);
		redirect(site_url('realestate/broker/contract/' . $id));
	}

	/**
	 * save contract data
	 * @return [type] 
	 */
	public function save_contract_data()
	{

		$success = false;
		$message = '';

		$this->db->where('id', $this->input->post('contract_id'));
		$this->db->update(db_prefix() . 'contracts', [
			'content' => html_purify($this->input->post('content', false)),
		]);

		$success = $this->db->affected_rows() > 0;
		$message = _l('updated_successfully', _l('contract'));

		echo json_encode([
			'success' => $success,
			'message' => $message,
		]);
	}

	/**
	 * add comment
	 */
	public function add_comment()
	{
		if ($this->input->post()) {
			$data = $this->input->post();
			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$data['is_company_admin'] = 0;
			$data['company_id'] = 0;
			$data['broker_id'] = $broker_id;
			$data['related_type'] = 'business_broker';
			$data['related_id'] = $related_id;

			echo json_encode([
				'success' => $this->contracts_model->add_comment($data),
			]);
		}
	}

	/**
	 * edit comment
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function edit_comment($id)
	{
		if ($this->input->post()) {
			$data = $this->input->post();
			echo json_encode([
				'success' => $this->contracts_model->edit_comment($data, $id),
				'message' => _l('comment_updated_successfully'),
			]);
		}
	}

	/**
	 * get contract comments
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_contract_comments($id)
	{
		$data['comments'] = $this->contracts_model->get_comments($id);
		$this->load->view('brokers_portals/contracts/comments_template', $data);
	}

	/**
	 * remove contract comment
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function remove_contract_comment($id)
	{
		$this->db->where('id', $id);
		$comment = $this->db->get(db_prefix() . 'contract_comments')->row();
		if ($comment) {
			if ($comment->broker_id != get_business_broker_id()) {
				echo json_encode([
					'success' => false,
				]);
				die;
			}
			echo json_encode([
				'success' => $this->contracts_model->remove_comment($id),
			]);
		} else {
			echo json_encode([
				'success' => false,
			]);
		}
	}

	/**
	 * renew contract
	 * @return [type] 
	 */
	public function renew_contract()
	{

		if ($this->input->post()) {
			$data    = $this->input->post();
			$success = $this->contracts_model->renew($data);
			if ($success) {
				set_alert('success', _l('contract_renewed_successfully'));
			} else {
				set_alert('warning', _l('contract_renewed_fail'));
			}
			redirect(site_url('realestate/broker/contract/' . $data['contractid'] . '?tab=renewals'));
		}
	}

	/**
	 * delete renewal contract
	 * @param  [type] $renewal_id 
	 * @param  [type] $contractid 
	 * @return [type]             
	 */
	public function delete_renewal_contract($renewal_id, $contractid)
	{
		$success = $this->contracts_model->delete_renewal($renewal_id, $contractid);
		if ($success) {
			set_alert('success', _l('contract_renewal_deleted'));
		} else {
			set_alert('warning', _l('contract_renewal_delete_fail'));
		}
		redirect(site_url('realestate/broker/contract/' . $contractid . '?tab=renewals'));
	}

	/**
	 * copy
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function copy($id)
	{

		if (!$id) {
			redirect(site_url('realestate/broker/contracts'));
		}
		$newId = $this->contracts_model->copy($id);
		if ($newId) {
			set_alert('success', _l('contract_copied_successfully'));
		} else {
			set_alert('warning', _l('contract_copied_fail'));
		}
		redirect(site_url('realestate/broker/contract/' . $newId));
	}

	/* Delete contract from database */
	public function contract_delete($id)
	{

		if (!$id) {
			redirect(site_url('realestate/broker/contracts'));
		}
		$response = $this->contracts_model->delete($id);
		if ($response == true) {
			set_alert('success', _l('deleted', _l('contract')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('contract_lowercase')));
		}
		if (strpos($_SERVER['HTTP_REFERER'], 'clients/') !== false) {
			redirect($_SERVER['HTTP_REFERER']);
		} else {
			redirect(site_url('realestate/broker/contracts'));
		}
	}

	/* Manage contract types Since Version 1.0.3 */
	public function contract_type($id = '')
	{
		
		if ($this->input->post()) {
			if (!$this->input->post('id')) {
				$data = $this->input->post();
				$broker_id = get_business_broker_id();
				$related_id = get_broker_id();

				$data['is_company_admin'] = 0;
				$data['company_id'] = 0;
				$data['broker_id'] = $broker_id;
				$data['related_type'] = 'business_broker';
				$data['related_id'] = $related_id;

				$id = $this->contracts_model->add_contract_type($data);
				if ($id) {
					$success = true;
					$message = _l('added_successfully', _l('contract_type'));
				}
				echo json_encode([
					'success' => $success,
					'message' => $message,
					'id'      => $id,
					'name'    => $this->input->post('name'),
				]);
			} else {
				$data = $this->input->post();
				$id   = $data['id'];
				unset($data['id']);
				$success = $this->contracts_model->update_contract_type($data, $id);
				$message = '';
				if ($success) {
					$message = _l('updated_successfully', _l('contract_type'));
				}
				echo json_encode([
					'success' => $success,
					'message' => $message,
				]);
			}
		}
	}

	/**
	 * contract types
	 * @return [type] 
	 */
	public function contract_types()
	{
		if ($this->input->is_ajax_request()) {
			$this->app->get_table_data(module_views_path('realestate', 'brokers_portals/settings/contract_types/contract_type_table'));
		}

		$data['title'] = _l('contract_types');
		$this->load->view('brokers_portals/contracts/manage_types', $data);
	}

	/* Delete announcement from database */
	public function delete_contract_type($id)
	{
		if (!$id) {
			redirect(site_url('realestate/broker/contract_types'));
		}

		$response = $this->contracts_model->delete_contract_type($id);
		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('is_referenced', _l('contract_type_lowercase')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted', _l('contract_type')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('contract_type_lowercase')));
		}
		redirect(site_url('realestate/broker/contract_types'));
	}

	/**
	 * add contract attachment
	 * @param [type] $id 
	 */
	public function add_contract_attachment($id)
	{
		handle_contract_attachment($id);
	}

	/**
	 * add external attachment
	 */
	public function add_external_attachment()
	{
		if ($this->input->post()) {
			$this->misc_model->add_attachment_to_database(
				$this->input->post('contract_id'),
				'contract',
				$this->input->post('files'),
				$this->input->post('external')
			);
		}
	}

	/**
	 * delete contract attachment
	 * @param  [type] $attachment_id 
	 * @return [type]                
	 */
	public function delete_contract_attachment($attachment_id)
	{
		$file = $this->misc_model->get_file($attachment_id);
		
		echo json_encode([
			'success' => $this->contracts_model->delete_contract_attachment($attachment_id),
		]);
	}

	/* List all contracts end */

	/* List all invoices start */
	/* List all invoices datatables */
	public function list_invoices($id = '')
	{
		close_setup_menu();

		$this->load->model('invoices_model');
		$this->load->model('payment_modes_model');
		$data['payment_modes']        = $this->payment_modes_model->get('', [], true);
		$data['invoiceid']            = $id;
		$data['title']                = _l('invoices');
		$data['invoices_years']       = $this->broker_model->get_invoices_years();
		$data['invoices_sale_agents'] = $this->broker_model->get_sale_agents();
		$data['invoices_statuses']    = $this->invoices_model->get_statuses();
		$data['bodyclass']            = 'invoices-total-manual';
		$this->load->view('brokers_portals/invoices/manage', $data);
	}

	/**
	 * invoice table
	 * @param  string $clientid 
	 * @return [type]           
	 */
	public function invoice_table($clientid = '')
	{

		$this->load->model('payment_modes_model');
		$data['payment_modes'] = $this->payment_modes_model->get('', [], true);

		$this->app->get_table_data(module_views_path('realestate', 'brokers_portals/invoices/tables/'.($this->input->get('recurring') ? 'recurring_invoices' : 'invoices')), [
			'clientid' => $clientid,
			'data'     => $data,
		]);

	}

	/**
	 * invoice client change data
	 * @param  [type] $customer_id     
	 * @param  string $current_invoice 
	 * @return [type]                  
	 */
	public function invoice_client_change_data($customer_id, $current_invoice = '')
	{
		if ($this->input->is_ajax_request()) {
			$this->load->model('invoices_model');
			$this->load->model('projects_model');
			$data                     = [];
			$data['billing_shipping'] = $this->clients_model->get_customer_billing_and_shipping_details($customer_id);
			$data['client_currency']  = $this->clients_model->get_customer_default_currency($customer_id);

			if ($current_invoice != '') {
				$this->db->select('status');
				$this->db->where('id', $current_invoice);
				$current_invoice_status = $this->db->get(db_prefix() . 'invoices')->row()->status;
			}

			$_data['invoices_to_merge'] = [];

			$data['merge_info'] = $this->load->view('brokers_portals/invoices/merge_invoice', $_data, true);

			$this->load->model('currencies_model');

			$__data['expenses_to_bill'] = !isset($current_invoice_status) || (isset($current_invoice_status) && $current_invoice_status != Invoices_model::STATUS_CANCELLED) ? $this->invoices_model->get_expenses_to_bill($customer_id) : [];

			$data['expenses_bill_info'] = $this->load->view('brokers_portals/invoices/bill_expenses', $__data, true);
			echo json_encode($data);
		}
	}

	/**
	 * validate invoice number
	 * @return [type] 
	 */
	public function validate_invoice_number()
	{
		$isedit          = $this->input->post('isedit');
		$number          = $this->input->post('number');
		$date            = $this->input->post('date');
		$original_number = $this->input->post('original_number');
		$number          = trim($number);
		$number          = ltrim($number, '0');

		if ($isedit == 'true') {
			if ($number == $original_number) {
				echo json_encode(true);
				die;
			}
		}

		if (total_rows('invoices', [
			'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
			'number' => $number,
			'status !=' => Invoices_model::STATUS_DRAFT,
		]) > 0) {
			echo 'false';
		} else {
			echo 'true';
		}
	}

	/**
	 * mark as cancelled
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mark_as_cancelled($id)
	{

		$success = $this->invoices_model->mark_as_cancelled($id);

		if ($success) {
			set_alert('success', _l('invoice_marked_as_cancelled_successfully'));
		}

		redirect(site_url('realestate/broker/list_invoices#' . $id));
	}

	/**
	 * unmark as cancelled
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function unmark_as_cancelled($id)
	{
		$success = $this->invoices_model->unmark_as_cancelled($id);
		if ($success) {
			set_alert('success', _l('invoice_unmarked_as_cancelled'));
		}
		redirect(site_url('realestate/broker/list_invoices#' . $id));
	}

	/**
	 * get merge data
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_merge_data($id)
	{
			$this->load->model('invoices_model');

		$invoice = $this->invoices_model->get($id);
		$cf      = get_custom_fields('items');

		$i = 0;

		foreach ($invoice->items as $item) {
			$invoice->items[$i]['taxname']          = get_invoice_item_taxes($item['id']);
			$invoice->items[$i]['long_description'] = clear_textarea_breaks($item['long_description']);
			$this->db->where('item_id', $item['id']);
			$rel              = $this->db->get(db_prefix() . 'related_items')->result_array();
			$item_related_val = '';
			$rel_type         = '';
			foreach ($rel as $item_related) {
				$rel_type = $item_related['rel_type'];
				$item_related_val .= $item_related['rel_id'] . ',';
			}
			if ($item_related_val != '') {
				$item_related_val = substr($item_related_val, 0, -1);
			}
			$invoice->items[$i]['item_related_formatted_for_input'] = $item_related_val;
			$invoice->items[$i]['rel_type']                         = $rel_type;

			$invoice->items[$i]['custom_fields'] = [];

			foreach ($cf as $custom_field) {
				$custom_field['value']                 = get_custom_field_value($item['id'], $custom_field['id'], 'items');
				$invoice->items[$i]['custom_fields'][] = $custom_field;
			}
			$i++;
		}
		echo json_encode($invoice);
	}

	/* Add new invoice or update existing */
	public function invoice($id = '')
	{
		if ($this->input->post()) {
			$this->load->model('invoices_model');

			$invoice_data = $this->input->post();
			if ($id == '') {
				if (hooks()->apply_filters('validate_invoice_number', true)) {
					$number = ltrim($invoice_data['number'], '0');
					if (total_rows('invoices', [
						'YEAR(date)' => date('Y', strtotime(to_sql_date($invoice_data['date']))),
						'number'     => $number,
						'status !='  => Invoices_model::STATUS_DRAFT,
					])) {
						set_alert('warning', _l('invoice_number_exists'));

						redirect(site_url('realestate/broker/invoice'));
					}
				}

				$broker_id = get_business_broker_id();
				$related_id = get_broker_id();

				$invoice_data['is_company_admin'] = 0;
				$invoice_data['company_id'] = 0;
				$invoice_data['broker_id'] = $broker_id;
				$invoice_data['related_type'] = 'business_broker';
				$invoice_data['related_id'] = $related_id;

				$id = $this->invoices_model->add($invoice_data);
				if ($id) {
					set_alert('success', _l('added_successfully', _l('invoice')));
					$redUrl = site_url('realestate/broker/list_invoices#' . $id);

					if (isset($invoice_data['save_and_record_payment'])) {
						$this->session->set_userdata('record_payment', true);
					} elseif (isset($invoice_data['save_and_send_later'])) {
						$this->session->set_userdata('send_later', true);
					}

					redirect($redUrl);
				}
			} else {
				
				// If number not set, is draft
				if (hooks()->apply_filters('validate_invoice_number', true) && isset($invoice_data['number'])) {
					$number = trim(ltrim($invoice_data['number'], '0'));
					if (total_rows('invoices', [
						'YEAR(date)' => date('Y', strtotime(to_sql_date($invoice_data['date']))),
						'number'     => $number,
						'status !='  => Invoices_model::STATUS_DRAFT,
						'id !='      => $id,
					])) {
						set_alert('warning', _l('invoice_number_exists'));

						redirect(site_url('realestate/broker/invoice/' . $id));
					}
				}
				$success = $this->invoices_model->update($invoice_data, $id);
				if ($success) {
					set_alert('success', _l('updated_successfully', _l('invoice')));
				}

				redirect(site_url('realestate/broker/list_invoices#' . $id));
			}
		}
		if ($id == '') {
			$title                  = _l('create_new_invoice');
			$data['billable_tasks'] = [];
		} else {
			$this->load->model('invoices_model');

			$invoice = $this->invoices_model->get($id);

			if (!$invoice || !user_can_view_invoice($id)) {
				blank_page(_l('invoice_not_found'));
			}

			$data['invoices_to_merge'] = [];
			$data['expenses_to_bill']  = [];

			$data['invoice']        = $invoice;
			$data['edit']           = true;
			$data['billable_tasks'] = [];

			$title = _l('edit', _l('invoice_lowercase')) . ' - ' . format_invoice_number($invoice->id);
		}

		if ($this->input->get('customer_id')) {
			$data['customer_id'] = $this->input->get('customer_id');
		}

		$this->load->model('payment_modes_model');
		$data['payment_modes'] = $this->payment_modes_model->get('', [
			'expenses_only !=' => 1,
		]);

		$this->load->model('taxes_model');
		$data['taxes'] = $this->taxes_model->get();
		$this->load->model('invoice_items_model');

		$data['ajaxItems'] = false;
		if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
			$data['items'] = $this->invoice_items_model->get_grouped();
		} else {
			$data['items']     = [];
			$data['ajaxItems'] = true;
		}
		$data['items_groups'] = $this->invoice_items_model->get_groups();

		$this->load->model('currencies_model');
		$data['currencies'] = $this->currencies_model->get();

		$data['base_currency'] = $this->currencies_model->get_base_currency();

		$data['staff']     = $this->staff_model->get('', ['active' => 1]);
		$data['title']     = $title;
		$data['bodyclass'] = 'invoice';
		$data['site_url'] = $this->site_url;
		$this->load->view('brokers_portals/invoices/invoice', $data);
	}

	/* Get all invoice data used when user click on invoiec number in a datatable left side*/
	public function get_invoice_data_ajax($id)
	{
		if (!$id) {
			die(_l('invoice_not_found'));
		}
		$this->load->model('invoices_model');

		$invoice = $this->invoices_model->get($id);

		if (!$invoice) {
			echo _l('invoice_not_found');
			die;
		}

		$template_name = 'invoice_send_to_customer';

		if ($invoice->sent == 1) {
			$template_name = 'invoice_send_to_customer_already_sent';
		}

		$data = prepare_mail_preview_data($template_name, $invoice->clientid);

		// Check for recorded payments
		$this->load->model('payments_model');
		$data['invoices_to_merge']          = [];
		$data['members']                    = $this->staff_model->get('', ['active' => 1]);
		$data['payments']                   = $this->payments_model->get_invoice_payments($id);
		$data['activity']                   = $this->invoices_model->get_invoice_activity($id);
		$data['totalNotes']                 = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'invoice']);
		$data['invoice_recurring_invoices'] = $this->invoices_model->get_invoice_recurring_invoices($id);

		$data['applied_credits'] = [];
		// This data is used only when credit can be applied to invoice
		if (credits_can_be_applied_to_invoice($invoice->status)) {
			$data['credits_available'] = $this->credit_notes_model->total_remaining_credits_by_customer($invoice->clientid);

			if ($data['credits_available'] > 0) {
				$data['open_credits'] = $this->credit_notes_model->get_open_credits($invoice->clientid);
			}

			$customer_currency = $this->clients_model->get_customer_default_currency($invoice->clientid);
			$this->load->model('currencies_model');

			if ($customer_currency != 0) {
				$data['customer_currency'] = $this->currencies_model->get($customer_currency);
			} else {
				$data['customer_currency'] = $this->currencies_model->get_base_currency();
			}
		}

		$data['invoice'] = $invoice;

		$data['record_payment'] = false;
		$data['send_later']     = false;

		if ($this->session->has_userdata('record_payment')) {
			$data['record_payment'] = true;
			$this->session->unset_userdata('record_payment');
		} elseif ($this->session->has_userdata('send_later')) {
			$data['send_later'] = true;
			$this->session->unset_userdata('send_later');
		}

		$this->load->view('brokers_portals/invoices/invoice_preview_template', $data);
	}

	/**
	 * get invoices total
	 * @return [type] 
	 */
	public function get_invoices_total()
	{
		if ($this->input->post()) {
			load_invoices_total_template();
		}
	}

	/**
	 * record invoice payment ajax
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function record_invoice_payment_ajax($id)
	{
		$this->load->model('payment_modes_model');
		$this->load->model('payments_model');

		$data['payment_modes'] = $this->payment_modes_model->get('', [
			'expenses_only !=' => 1,
		]);
		$data['invoice']  = $this->invoices_model->get($id);
		$data['payments'] = $this->payments_model->get_invoice_payments($id);
		$this->load->view('brokers_portals/invoices/record_payment_template', $data);
	}

	/**
	 * record_payment This is where invoice payment record $_POST data is send
	 * @return [type] [
	 */
	public function record_payment()
	{
		if ($this->input->post()) {
			$this->load->model('payments_model');
			$id = $this->broker_model->process_payment($this->input->post(), '');
			if ($id) {
				set_alert('success', _l('invoice_payment_recorded'));
				redirect(site_url('realestate/broker/payment/' . $id));
			} else {
				set_alert('danger', _l('invoice_payment_record_failed'));
			}
			redirect(site_url('realestate/broker/list_invoices#' . $this->input->post('invoiceid')));
		}
	}

	/* Send invoice to email */
	public function send_invoice_to_email($id)
	{
		try {
			$statementData = [];
			if ($this->input->post('attach_statement')) {
				$statementData['attach'] = true;
				$statementData['from']   = to_sql_date($this->input->post('statement_from'));
				$statementData['to']     = to_sql_date($this->input->post('statement_to'));
			}

			$success = $this->invoices_model->send_invoice_to_client(
				$id,
				'',
				$this->input->post('attach_pdf'),
				$this->input->post('cc'),
				false,
				$statementData
			);
		} catch (Exception $e) {
			$message = $e->getMessage();
			echo $message;
			if (strpos($message, 'Unable to get the size of the image') !== false) {
				show_pdf_unable_to_get_image_size_error();
			}
			die;
		}

		// In case client use another language
		load_admin_language();
		if ($success) {
			set_alert('success', _l('invoice_sent_to_client_success'));
		} else {
			set_alert('danger', _l('invoice_sent_to_client_fail'));
		}
		redirect(site_url('realestate/broker/list_invoices#' . $id));
	}

	/* Delete invoice payment*/
	public function delete_payment($id, $invoiceid)
	{

		$this->load->model('payments_model');
		if (!$id) {
			redirect(admin_url('payments'));
		}
		$response = $this->payments_model->delete($id);
		if ($response == true) {
			set_alert('success', _l('deleted', _l('payment')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('payment_lowercase')));
		}
		redirect(site_url('realestate/broker/list_invoices#' . $invoiceid));
	}

	/* Delete invoice */
	public function delete_invoice($id)
	{

		if (!$id) {
			redirect(site_url('realestate/broker/list_invoices'));
		}
		$this->load->model('invoices_model');

		$success = $this->invoices_model->delete($id);

		if ($success) {
			set_alert('success', _l('deleted', _l('invoice')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('invoice_lowercase')));
		}
		if (strpos($_SERVER['HTTP_REFERER'], 'list_invoices') !== false) {
			redirect(site_url('realestate/broker/list_invoices'));
		} else {
			redirect($_SERVER['HTTP_REFERER']);
		}
	}

	/**
	 * delete invoice attachment
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_invoice_attachment($id)
	{
		$file = $this->misc_model->get_file($id);
		if (is_broker_logged_in()) {
			$this->load->model('invoices_model');

			echo $this->invoices_model->delete_attachment($id);
		} else {
			header('HTTP/1.0 400 Bad error');
			echo _l('access_denied');
			die;
		}
	}

	/**
	 * toggle file visibility
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function toggle_file_visibility($id)
	{
		$this->db->where('id', $id);
		$row = $this->db->get(db_prefix() . 'files')->row();
		if ($row->visible_to_customer == 1) {
			$v = 0;
		} else {
			$v = 1;
		}

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'files', [
			'visible_to_customer' => $v,
		]);
		echo $v;
	}

	/* Will send overdue notice to client */
	public function send_overdue_notice($id)
	{

		$this->load->model('invoices_model');

		$send = $this->invoices_model->send_invoice_overdue_notice($id);
		if ($send) {
			set_alert('success', _l('invoice_overdue_reminder_sent'));
		} else {
			set_alert('warning', _l('invoice_reminder_send_problem'));
		}
		redirect(site_url('realestate/broker/list_invoices#' . $id));
	}

	/* Generates invoice PDF and senting to email of $send_to_email = true is passed */
	public function invoice_pdf($id)
	{
		if (!$id) {
			redirect(site_url('realestate/broker/list_invoices'));
		}
		$this->load->model('invoices_model');

		$invoice        = $this->invoices_model->get($id);
		$invoice        = hooks()->apply_filters('before_admin_view_invoice_pdf', $invoice);
		$invoice_number = format_invoice_number($invoice->id);

		try {
			$pdf = invoice_pdf($invoice);
		} catch (Exception $e) {
			$message = $e->getMessage();
			echo $message;
			if (strpos($message, 'Unable to get the size of the image') !== false) {
				show_pdf_unable_to_get_image_size_error();
			}
			die;
		}

		$type = 'D';

		if ($this->input->get('output_type')) {
			$type = $this->input->get('output_type');
		}

		if ($this->input->get('print')) {
			$type = 'I';
		}

		$pdf->Output(mb_strtoupper(slug_it($invoice_number)) . '.pdf', $type);
	}

	/**
	 * mark as sent
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function mark_as_sent($id)
	{
		if (!$id) {
			redirect(site_url('realestate/broker/list_invoices'));
		}
		if (!user_can_view_invoice($id)) {
			access_denied('Invoice Mark As Sent');
		}

		$success = $this->invoices_model->set_invoice_sent($id, true);

		if ($success) {
			set_alert('success', _l('invoice_marked_as_sent'));
		} else {
			set_alert('warning', _l('invoice_marked_as_sent_failed'));
		}

		redirect(site_url('realestate/broker/list_invoices#' . $id));
	}

	/**
	 * get due date
	 * @return [type] 
	 */
	public function get_due_date()
	{
		if ($this->input->post()) {
			$date    = $this->input->post('date');
			$duedate = '';
			if (get_option('invoice_due_after') != 0) {
				$date    = to_sql_date($date);
				$d       = date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime($date)));
				$duedate = _d($d);
				echo $duedate;
			}
		}
	}

	/**
	 * upload_sales_file
	 * @return [type] 
	 */
	public function upload_sales_file()
	{
		handle_sales_attachments($this->input->post('rel_id'), $this->input->post('type'));
	}

	/* Update payment data */
	public function payment($id = '')
	{
		if (!$id) {
			redirect(site_url('realestate/broker/payments'));
		}
		$this->load->model('payments_model');
		if ($this->input->post()) {
			$success = $this->payments_model->update($this->input->post(), $id);
			if ($success) {
				set_alert('success', _l('updated_successfully', _l('payment')));
			}
			redirect(site_url('realestate/broker/payment/' . $id));
		}

		$payment = $this->payments_model->get($id);

		if (!$payment) {
			show_404();
		}

		$this->load->model('invoices_model');
		$payment->invoice = $this->invoices_model->get($payment->invoiceid);
		$template_name    = 'invoice_payment_recorded_to_customer';

		$data = prepare_mail_preview_data($template_name, $payment->invoice->clientid);

		$data['payment'] = $payment;
		$this->load->model('payment_modes_model');
		$data['payment_modes'] = $this->payment_modes_model->get('', [], true, true);

		$i = 0;
		foreach ($data['payment_modes'] as $mode) {
			if ($mode['active'] == 0 && $data['payment']->paymentmode != $mode['id']) {
				unset($data['payment_modes'][$i]);
			}
			$i++;
		}

		$data['title'] = _l('payment_receipt') . ' - ' . format_invoice_number($data['payment']->invoiceid);
		$this->load->view('brokers_portals/payments/payment', $data);
	}

	/**
	 * Generate payment pdf
	 * @since  Version 1.0.1
	 * @param  mixed $id Payment id
	 */
	public function payment_pdf($id)
	{
		$this->load->model('payments_model');

		$payment = $this->payments_model->get($id);
		$this->load->model('invoices_model');
		$payment->invoice_data = $this->invoices_model->get($payment->invoiceid);

		try {
			$paymentpdf = payment_pdf($payment);
		} catch (Exception $e) {
			$message = $e->getMessage();
			echo $message;
			if (strpos($message, 'Unable to get the size of the image') !== false) {
				show_pdf_unable_to_get_image_size_error();
			}
			die;
		}

		$type = 'D';

		if ($this->input->get('output_type')) {
			$type = $this->input->get('output_type');
		}

		if ($this->input->get('print')) {
			$type = 'I';
		}

		$paymentpdf->Output(mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid)) . '.pdf', $type);
	}

	/**
     * Send payment manually to customer contacts
     * @since  2.3.2
     * @param  mixed $id payment id
     * @return mixed
     */
    public function send_payment_to_email($id)
    {
		$this->load->model('payments_model');
        $payment = $this->payments_model->get($id);

        $this->load->model('invoices_model');
        $payment->invoice_data = $this->invoices_model->get($payment->invoiceid);
        set_mailing_constant();

        $paymentpdf = payment_pdf($payment);
        $filename   = mb_strtoupper(slug_it(_l('payment') . '-' . $payment->paymentid), 'UTF-8') . '.pdf';

        $attach = $paymentpdf->Output($filename, 'S');

        $sent    = false;
        $sent_to = $this->input->post('sent_to');

        if (is_array($sent_to) && count($sent_to) > 0) {
            foreach ($sent_to as $contact_id) {
                if ($contact_id != '') {
                    $contact = $this->clients_model->get_contact($contact_id);

                    $template = mail_template('invoice_payment_recorded_to_customer', (array) $contact, $payment->invoice_data, false, $payment->paymentid);

                    $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $filename,
                            'type'       => 'application/pdf',
                        ]);


                    if (get_option('attach_invoice_to_payment_receipt_email') == 1) {
                        $invoice_number = format_invoice_number($payment->invoiceid);
                        set_mailing_constant();
                        $pdfInvoice           = invoice_pdf($payment->invoice_data);
                        $pdfInvoiceAttachment = $pdfInvoice->Output($invoice_number . '.pdf', 'S');

                        $template->add_attachment([
                            'attachment' => $pdfInvoiceAttachment,
                            'filename'   => str_replace('/', '-', $invoice_number) . '.pdf',
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        $sent = true;
                    }
                }
            }
        }

        // In case client use another language
        load_admin_language();
        set_alert($sent ? 'success' : 'danger', _l($sent ? 'payment_sent_successfully' : 'payment_sent_failed'));

        redirect(site_url('realestate/broker/payment/' . $id));
    }

    /**
	 * renter profile
	 * @param  [type] $clientid 
	 * @return [type]           
	 */
	public function renter_profile($clientid, $request_id)
	{
		$contact_id = get_primary_contact_user_id($clientid);
		$data = [];
		$data['title'] = _l('real_renter_profile');
		$id = get_contact_user_id();
		$this->load->model('clients_model');
		$data['renter_profile'] = $this->clients_model->get_contact($contact_id);
		$data['supporting_documents'] = $this->realestate_model->rel_get_attachments_file($contact_id, 'supporting_document');
		$data['proof_incomes'] = $this->realestate_model->rel_get_attachments_file($contact_id, 'proof_income');
		$data['identity_documents'] = $this->realestate_model->rel_get_attachments_file($contact_id, 'identity_document');
		$data['site_url'] = $this->site_url;
		$data['request_id'] = $request_id;

		$this->load->view('companies/tenants/renter_profile', $data);
	}

	/**
	 * delete property activity
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_property_activity($id)
	{
		if (is_broker_logged_in()) {
			$this->db->where('id', $id);
			$this->db->delete(db_prefix() . 'real_activity');
		}
	}

	/**
	 * company email exists
	 * @return [type] 
	 */
	public function company_email_exists()
	{
		if ($this->input->is_ajax_request()) {
			if ($this->input->post()) {
                // First we need to check if the email is the same
				$company_id = $this->input->post('company_id');
				if ($company_id != '') {
					$this->db->where('id', $company_id);
					$_current_email = $this->db->get(db_prefix() . 'real_companies')->row();
					if ($_current_email->email == $this->input->post('email')) {
						echo json_encode(true);
						die();
					}
				}
				$this->db->where('email', $this->input->post('email'));
				$total_rows = $this->db->count_all_results(db_prefix() . 'real_companies');
				if ($total_rows > 0) {
					echo json_encode(false);
				} else {
					echo json_encode(true);
				}
				die();
			}
		}
	}

	/**
	 * dashboard
	 * @return [type] 
	 */
	public function dashboard()
	{

		$data['title'] = _l('reale_dashboard');
		$data['base_currency_id'] = get_base_currency_id();
		$company_id = get_business_broker_id();
		$data['dashboard_sale_performance'] = $this->realestate_model->get_dashboard_sale_performance($company_id);
		$data['get_remaining_property'] = get_remaining_property();
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/dashboards/dashboard', $data);
	}

	/**
	 * report buy sale request by status
	 * @return [type] 
	 */
	public function report_buy_sale_request_by_status()
	{
		if ($this->input->is_ajax_request()) { 
			$data = $this->input->get();
			$company_id = get_business_broker_id();

			$months_report = $data['months_report'];
			$report_from = $data['report_from'];
			$report_to = $data['report_to'];

			if($months_report == ''){
				$from_date = date('Y-m-d', strtotime('1997-01-01'));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'this_month'){
				$from_date = date('Y-m-01');
				$to_date   = date('Y-m-t');
			}

			if($months_report == '1'){ 
				$from_date = date('Y-m-01', strtotime('first day of last month'));
				$to_date   = date('Y-m-t', strtotime('last day of last month'));
			}

			if($months_report == 'this_year'){
				$from_date = date('Y-m-d', strtotime(date('Y-01-01')));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'last_year'){
				$from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
				$to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  
			}

			if($months_report == '3'){
				$months_report = 3;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '6'){
				$months_report = 6;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '12'){
				$months_report = 12;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == 'custom'){
				$from_date = to_sql_date($report_from);
				$to_date   = to_sql_date($report_to);
			}
	
			$request_by_status =  $this->realestate_model->get_rent_request_by_status($from_date, $to_date, 'buy', $company_id);
			echo json_encode([
				'data_result' =>  $request_by_status,

			]); 
		}
	}

	/**
	 * report rent sale request by status
	 * @return [type] 
	 */
	public function report_rent_sale_request_by_status()
	{
		if ($this->input->is_ajax_request()) { 
			$data = $this->input->get();

			$months_report = $data['months_report'];
			$report_from = $data['report_from'];
			$report_to = $data['report_to'];
			$company_id = get_business_broker_id();

			if($months_report == ''){
				$from_date = date('Y-m-d', strtotime('1997-01-01'));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'this_month'){
				$from_date = date('Y-m-01');
				$to_date   = date('Y-m-t');
			}

			if($months_report == '1'){ 
				$from_date = date('Y-m-01', strtotime('first day of last month'));
				$to_date   = date('Y-m-t', strtotime('last day of last month'));
			}

			if($months_report == 'this_year'){
				$from_date = date('Y-m-d', strtotime(date('Y-01-01')));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'last_year'){
				$from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
				$to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  
			}

			if($months_report == '3'){
				$months_report = 3;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '6'){
				$months_report = 6;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '12'){
				$months_report = 12;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == 'custom'){
				$from_date = to_sql_date($report_from);
				$to_date   = to_sql_date($report_to);
			}
	
			$request_by_status =  $this->realestate_model->get_rent_request_by_status($from_date, $to_date, 'rent', $company_id);
			echo json_encode([
				'data_result' =>  $request_by_status,

			]); 
		}
	}

	/**
	 * report rent sale request by property_type
	 * @return [type] 
	 */
	public function report_request_by_property_type()
	{
		if ($this->input->is_ajax_request()) { 
			$data = $this->input->get();

			$months_report = $data['months_report'];
			$report_from = $data['report_from'];
			$report_to = $data['report_to'];
			$request_type = $data['request_type'];
			$company_id = get_business_broker_id();

			if($months_report == ''){
				$from_date = date('Y-m-d', strtotime('1997-01-01'));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'this_month'){
				$from_date = date('Y-m-01');
				$to_date   = date('Y-m-t');
			}

			if($months_report == '1'){ 
				$from_date = date('Y-m-01', strtotime('first day of last month'));
				$to_date   = date('Y-m-t', strtotime('last day of last month'));
			}

			if($months_report == 'this_year'){
				$from_date = date('Y-m-d', strtotime(date('Y-01-01')));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'last_year'){
				$from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
				$to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  
			}

			if($months_report == '3'){
				$months_report = 3;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '6'){
				$months_report = 6;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '12'){
				$months_report = 12;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == 'custom'){
				$from_date = to_sql_date($report_from);
				$to_date   = to_sql_date($report_to);
			}
	
			$request_by_status =  $this->realestate_model->get_request_by_property_type($from_date, $to_date, $request_type, $company_id);
			echo json_encode([
				'data_result' =>  $request_by_status,

			]); 
		}
	}

	/**
	 * report by listing status
	 * @return [type] 
	 */
	public function report_by_listing_status()
	{
		if ($this->input->is_ajax_request()) { 
			$data = $this->input->get();

			$months_report = $data['months_report'];
			$report_from = $data['report_from'];
			$report_to = $data['report_to'];
			$company_id = get_business_broker_id();

			if($months_report == ''){
				$from_date = date('Y-m-d', strtotime('1997-01-01'));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'this_month'){
				$from_date = date('Y-m-01');
				$to_date   = date('Y-m-t');
			}

			if($months_report == '1'){ 
				$from_date = date('Y-m-01', strtotime('first day of last month'));
				$to_date   = date('Y-m-t', strtotime('last day of last month'));
			}

			if($months_report == 'this_year'){
				$from_date = date('Y-m-d', strtotime(date('Y-01-01')));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'last_year'){
				$from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
				$to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  
			}

			if($months_report == '3'){
				$months_report = 3;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '6'){
				$months_report = 6;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '12'){
				$months_report = 12;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == 'custom'){
				$from_date = to_sql_date($report_from);
				$to_date   = to_sql_date($report_to);
			}
	
			$property_by_status =  $this->realestate_model->get_report_by_listing_status($from_date, $to_date, $company_id);
			echo json_encode([
				'data_result' =>  $property_by_status,

			]); 
		}
	}

	/**
	 * reports
	 * @return [type] 
	 */
	public function reports()
    {
        $data['title'] = _l('real_reports');
		$data['site_url'] = $this->site_url;

        $this->load->view('companies/reports/manage', $data);
    }

    /**
     * rent paid report
     * @return [type] 
     */
    public function rent_paid_report() {
		if ($this->input->post()) {
			$company_id = get_business_broker_id();
			$rent_paid_html = $this->realestate_model->get_rent_paid_report_data($company_id);
		}

		echo json_encode([
			'value' => $rent_paid_html,
		]);
		die();
	}

	/**
	 * report vacant rental property report
	 * @return [type] 
	 */
	public function report_vacant_rental_property_report() {
		if ($this->input->post()) {
			$company_id = get_business_broker_id();
			$rental_property_html = $this->realestate_model->vacant_rental_property_report_data($company_id);
		}

		echo json_encode([
			'value' => $rental_property_html,
		]);
		die();
	}

	/**
	 * report unit property rental report
	 * @return [type] 
	 */
	public function report_unit_property_rental_report() {
		if ($this->input->post()) {
			$company_id = get_business_broker_id();
			$rental_report_html = $this->realestate_model->unit_property_rental_report_data($company_id);
		}

		echo json_encode([
			'value' => $rental_report_html,
		]);
		die();
	}

	/**
	 * report delinquent tenants report
	 * @return [type] 
	 */
	public function report_delinquent_tenants_report() {
		if ($this->input->post()) {
			$company_id = get_business_broker_id();
			$delinquent_tenant_html = $this->realestate_model->delinquent_tenants_report_data($company_id);
		}

		echo json_encode([
			'value' => $delinquent_tenant_html,
		]);
		die();
	}

	/**
	 * report leases ending report
	 * @return [type] 
	 */
	public function report_leases_ending_report() {
		if ($this->input->post()) {
			$company_id = get_business_broker_id();
			$leases_ending_html = $this->realestate_model->leases_ending_repor_data($company_id);
		}

		echo json_encode([
			'value' => $leases_ending_html,
		]);
		die();
	}

	/**
	 * report vacant sale property report
	 * @return [type] 
	 */
	public function report_vacant_sale_property_report() {
		if ($this->input->post()) {
			$company_id = get_business_broker_id();
			$vacant_sale_property_report_data_html = $this->realestate_model->vacant_sale_property_report_data($company_id);
		}

		echo json_encode([
			'value' => $vacant_sale_property_report_data_html,
		]);
		die();
	}

	/**
	 * report unit property sold report
	 * @return [type] 
	 */
	public function report_unit_property_sold_report() {
		if ($this->input->post()) {
			$company_id = get_business_broker_id();
			$property_sold_html = $this->realestate_model->unit_property_sold_report_data($company_id);
		}

		echo json_encode([
			'value' => $property_sold_html,
		]);
		die();
	}

	/**
	 * report by top city listing
	 * @return [type] 
	 */
	public function report_by_top_city_listing()
	{
		if ($this->input->is_ajax_request()) {
			$data = $this->input->get();

			$months_report = $data['months_report'];
			$report_from = $data['report_from'];
			$report_to = $data['report_to'];
			$company_id = get_business_broker_id();

			if($months_report == ''){
				$from_date = date('Y-m-d', strtotime('1997-01-01'));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'this_month'){
				$from_date = date('Y-m-01');
				$to_date   = date('Y-m-t');
			}

			if($months_report == '1'){ 
				$from_date = date('Y-m-01', strtotime('first day of last month'));
				$to_date   = date('Y-m-t', strtotime('last day of last month'));
			}

			if($months_report == 'this_year'){
				$from_date = date('Y-m-d', strtotime(date('Y-01-01')));
				$to_date = date('Y-m-d', strtotime(date('Y-12-31')));
			}

			if($months_report == 'last_year'){
				$from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
				$to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  
			}

			if($months_report == '3'){
				$months_report = 3;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '6'){
				$months_report = 6;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == '12'){
				$months_report = 12;
				$months_report--;
				$from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
				$to_date   = date('Y-m-t');
			}

			if($months_report == 'custom'){
				$from_date = to_sql_date($report_from);
				$to_date   = to_sql_date($report_to);
			}
			
			$list_result = $this->realestate_model->report_by_top_city_listing($from_date, $to_date, $company_id);
			if(count($list_result['categories']) > 0){
				$show_chart = true;
			}

			echo json_encode([
				'data_result' => $list_result['chart'],
				'categories' => $list_result['categories'],
			]);
			die;
		}
	}

/* List all invoices end */

}
