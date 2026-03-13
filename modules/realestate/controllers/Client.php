<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Realestate Client Controller
 */
class Client extends ClientsController
{

	/**
	 * construct
	 */
	public function __construct()
	{

		parent::__construct();
		$this->load->model('realestate_model');
		$this->site_url = site_url().'realestate/client/';

	}

	/**
	 * layout
	 * @param  boolean $notInThemeViewFiles 
	 * @return [type]                       
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
		$this->template['head'] = $this->load->view('themes/' . active_clients_theme() . '/head', $this->data, true);

		$GLOBALS['customers_head'] = $this->template['head'];

		/**
		 * Load the template view
		 * @var string
		 */
		$module                       = CI::$APP->router->fetch_module();
		$this->data['current_module'] = $module;
		$viewPath                     = !is_null($module) || $notInThemeViewFiles ?
		$this->view :
		$this->createThemeViewPath($this->view);

		$this->template['view']    = $this->load->view($viewPath, $this->data, true);
		$GLOBALS['customers_view'] = $this->template['view'];

		/**
		 * Theme footer
		 * @var string
		 */
		$this->template['footer'] = $this->use_footer == true
		? $this->load->view('themes/' . active_clients_theme() . '/footer', $this->data, true)
		: '';
		$GLOBALS['customers_footer'] = $this->template['footer'];

		/**
		 * @deprecated 2.3.0
		 * Theme scripts.php file is no longer used since vresion 2.3.0, add app_customers_footer() in themes/[theme]/index.php
		 * @var string
		 */
		
		$this->template['scripts'] = '';
		if (file_exists(VIEWPATH . 'clients/scripts.php')) {
			if (ENVIRONMENT != 'production') {
				trigger_error(sprintf('%1$s', 'Clients area theme file scripts.php file is no longer used since version 2.3.0, add app_customers_footer() in themes/[theme]/index.php. You can check the original theme index.php for example.'));
			}

		}
		$this->template['scripts'] = $this->load->view('clients/scripts', $this->data, true);

		/**
		 * Load the theme compiled template
		 */
		$this->load->view('clients/index', $this->template);
	}

	/**
	 * properties
	 * @return [type] 
	 */
	public function properties()
	{
		$data = [];

		$data['switch_map'] = true;

		if ($this->session->userdata('listings_map_view') == 'true') {
			$data['switch_map'] = false;
			$data['bodyclass']     = 'kan-ban-body';
		}
		$data['isMap'] = $this->session->has_userdata('listings_map_view') &&
		$this->session->userdata('listings_map_view') == 'true';

		$data['total_page'] = $this->realestate_model->get_total_page('can_be_property_listing', 'can_be_property_listing', 'items');

		$map_listing_where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';

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

		$data['map_property_listing'] = $this->realestate_model->map_get_property_listing($map_listing_where, false, '', '', 0, true, false, 'client');

		$this->data($data);
		$this->view('clients/property_listings/manage');
		$this->layout();

	}

	/**
	 * client sproperty sgrid sview
	 * @return [type] 
	 */
	public function client_property_grid_view()
	{
		$property_grid_view = $this->realestate_model->property_grid_view('client');

		echo json_encode(['html' => $property_grid_view['html'], 'total_page' => $property_grid_view['total_page']]);
	}

	/**
	 * company property grid view
	 * @return [type] 
	 */
	public function company_property_grid_view($company_id)
	{
		$property_grid_view = $this->realestate_model->company_property_grid_view($company_id);
		echo json_encode(['html' => $property_grid_view['html'], 'total_page' => $property_grid_view['total_page']]);
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

		$data['product_attachments'] = $this->realestate_model->rel_get_attachments_file($id, 'commodity_item_file');
		$data['product_attachment_pdfs'] = $this->realestate_model->rel_get_attachments_file($id, 'real_listing_pdf');
		$data['property_assets'] = $this->realestate_model->get_property_asset($id);
		$data['product_videos'] = $this->realestate_model->rel_get_attachments_file($id, 'property_video');

		$data['contact_infor'] = real_get_contact_infor($data['property_listing']->related_id, $data['property_listing']->related_type);
		$data['title']                 = $property_listing->commodity_code.' '.$property_listing->description;
		$public_profile_url = '';
		$data['public_profile_url'] = $public_profile_url;
		$data['request_brokers'] = $this->realestate_model->get_request_broker(false, "item_id = ".$property_listing->id, $property_listing->id);

		$data['site_url'] = $this->site_url;

		$this->data($data);
		$this->view('clients/property_listings/property_listing_detail');
		$this->layout();

	}

	/**
	 * reload map
	 * @return [type] 
	 */
	public function reload_map()
	{
		$map_property_listing = $this->realestate_model->render_query_data_for_map('client');

		echo json_encode([
			'map_property_listing' => $map_property_listing,
		]);
	}

	/**
	 * renter profile
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function renter_profile()
	{
		if(!is_client_logged_in()){ 
			redirect_after_login_to_current_url();
			redirect(site_url('authentication/login'));
		}
		
		$contact_id = get_contact_user_id();
		if($this->input->post()){
			$data = $this->input->post();
			if(isset($data['birthday'])){
				$data['birthday'] = to_sql_date($data['birthday']);
			}
			if(isset($data['not_employed'])){
				$data['not_employed'] = 1;
			}else{
				$data['not_employed'] = 0;
			}
			if(isset($data['DataTables_Table_2_length'])){
				unset($data['DataTables_Table_2_length']);
			}
			if(isset($data['DataTables_Table_1_length'])){
				unset($data['DataTables_Table_1_length']);
			}
			
			$affected_row = 0;
			$success = $this->clients_model->update_contact($data, $contact_id, true);
			if($success){
				$affected_row++;
			}
			$attachment_status = handle_supporting_document_file($contact_id);
			if($attachment_status){
				$affected_row++;
			}
			$attachment_status = handle_proof_income_file($contact_id);
			if($attachment_status){
				$affected_row++;
			}
			$attachment_status = handle_identity_document_file($contact_id);
			if($attachment_status){
				$affected_row++;
			}

			if ($affected_row > 0) {
				set_alert('success', _l('real_renter_profile_updated'));
			}

			redirect(site_url('realestate/client/renter_profile'));
		}

		$data = [];
		$data['title'] = _l('real_renter_profile');
		$id = get_contact_user_id();
		$this->load->model('clients_model');
		$data['renter_profile'] = $this->clients_model->get_contact($contact_id);
		$data['supporting_documents'] = $this->realestate_model->rel_get_attachments_file($contact_id, 'supporting_document');
		$data['proof_incomes'] = $this->realestate_model->rel_get_attachments_file($contact_id, 'proof_income');
		$data['identity_documents'] = $this->realestate_model->rel_get_attachments_file($contact_id, 'identity_document');

		$this->data($data);
		$this->view('clients/renter_profiles/renter_profile');
		$this->layout();
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

		if($data['file']->rel_type == 'supporting_document'){
			$upload_path = SUPPORTING_DOCUMENT_UPLOAD;
			$upload_folder = 'supporting_documents';
		}elseif($data['file']->rel_type == 'proof_income'){
			$upload_path = PROOF_INCOME_UPLOAD;
			$upload_folder = 'proof_incomes';
		}elseif($data['file']->rel_type == 'identity_document'){
			$upload_path = IDENTIFY_DOCUMENT_UPLOAD;
			$upload_folder = 'identity_documents';
		}
		$data['upload_path'] = $upload_path;
		$data['upload_folder'] = $upload_folder;
		$this->load->view('clients/utilities/preview_file', $data);
	}

	/**
	 * delete property listing attachment
	 * @param  [type] $attachment_id 
	 * @param  [type] $rel_type      
	 * @return [type]                
	 */
	public function delete_realestate_attachment($attachment_id, $folder_name = false)
	{
		if (!is_client_logged_in()) {
			access_denied('delete_attachment');
		}

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
	 * address history table
	 * @return [type] 
	 */
	public function address_history_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'clients/renter_profiles/tables/address_history_table'),['contact_id' => get_contact_user_id()]);
	}

	/**
	 * address history
	 * @return [type] 
	 */
	public function address_history()
	{
		$message = '';
		$success = false;
		if ($this->input->post()) {
			if ($this->input->post('id')) {

				$id = $this->input->post('id');
				$data = $this->input->post();
				if(isset($data['id'])){
					unset($data['id']);
				}
				
				$success = $this->realestate_model->update_address_history($data, $id);
				if ($success == true) {
					$success = true;
					$message = _l('updated_successfully', _l('real_address_history'));
				}
			} else {

				$data = $this->input->post();
				$data['contact_id'] = get_contact_user_id();
				$success = $this->realestate_model->add_address_history($data);
				if ($success == true) {
					$success = true;
					$message = _l('added_successfully', _l('real_address_history'));
				}
			}
		}
		echo json_encode([
			'success' => $success,
			'message' => $message,
		]);
	}

	/**
	 * delete address history
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_address_history($id)
	{
		if ($this->input->is_ajax_request()) {
			$success = $this->realestate_model->delete_address_history($id);
			$message = _l('deleted', _l('real_address_history'));

			echo json_encode([
				'success' => $success,
				'message' => $message,
			]);

		}
	}

	/**
	 * income source table
	 * @return [type] 
	 */
	public function income_source_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'clients/renter_profiles/tables/income_source_table'),['contact_id' => get_contact_user_id()]);
	}

	/**
	 * income source
	 * @return [type] 
	 */
	public function income_source()
	{
		$message = '';
		$success = false;
		if ($this->input->post()) {
			if ($this->input->post('id')) {

				$id = $this->input->post('id');
				$data = $this->input->post();
				if(isset($data['id'])){
					unset($data['id']);
				}
				
				$success = $this->realestate_model->update_income_source($data, $id);
				if ($success == true) {
					$success = true;
					$message = _l('updated_successfully', _l('real_income_source'));
				}
			} else {

				$data = $this->input->post();
				$data['contact_id'] = get_contact_user_id();
				$success = $this->realestate_model->add_income_source($data);
				if ($success == true) {
					$success = true;
					$message = _l('added_successfully', _l('real_income_source'));
				}
			}
		}
		echo json_encode([
			'success' => $success,
			'message' => $message,
		]);
	}

	/**
	 * delete income source
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_income_source($id)
	{
		if ($this->input->is_ajax_request()) {
			$success = $this->realestate_model->delete_income_source($id);
			$message = _l('deleted', _l('real_income_source'));

			echo json_encode([
				'success' => $success,
				'message' => $message,
			]);

		}
	}

	/**
	 * person table
	 * @return [type] 
	 */
	public function person_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'clients/renter_profiles/tables/person_table'),['contact_id' => get_contact_user_id()]);
	}

	/**
	 * person
	 * @return [type] 
	 */
	public function person()
	{
		$message = '';
		$success = false;
		if ($this->input->post()) {
			if ($this->input->post('id')) {

				$id = $this->input->post('id');
				$data = $this->input->post();
				if(isset($data['id'])){
					unset($data['id']);
				}
				
				$success = $this->realestate_model->update_person($data, $id);
				if ($success == true) {
					$success = true;
					$message = _l('updated_successfully', _l('real_person'));
				}
			} else {

				$data = $this->input->post();
				$data['contact_id'] = get_contact_user_id();
				$success = $this->realestate_model->add_person($data);
				if ($success == true) {
					$success = true;
					$message = _l('added_successfully', _l('real_person'));
				}
			}
		}
		echo json_encode([
			'success' => $success,
			'message' => $message,
		]);
	}

	/**
	 * delete person
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_person($id)
	{
		if ($this->input->is_ajax_request()) {
			$success = $this->realestate_model->delete_person($id);
			$message = _l('deleted', _l('real_person'));

			echo json_encode([
				'success' => $success,
				'message' => $message,
			]);

		}
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
	 * property request
	 * @param  string $id           
	 * @param  string $request_type 
	 * @return [type]               
	 */
	public function property_request($id = '', $request_type = 'buy')
	{
		$request_type = 'buy';
		if($this->input->get('request_type')){
			$request_type = $this->input->get('request_type');
		}

		if ($this->input->post()) {
			$data = $this->input->post();
			$id = $this->input->post('id');

			if ($id == '') {
				if (!is_client_logged_in()) {
					access_denied('real_property_request');
				}

				$_broker_staff_id = $this->input->post('broker_id');
				if(isset($data['broker_type'])){
					unset($data['broker_type']);
				}
				if(isset($data['broker_id'])){
					unset($data['broker_id']);
				}
				if($this->input->post('broker_type') == 'staff' || $this->input->post('broker_type') == 'company'){
					$broker_id = 0;
					$check_staff_type = rel_check_staff_type($_broker_staff_id);

					$data['is_company_admin'] = $check_staff_type['is_company_admin'];
					$data['company_id'] = $check_staff_type['company_id'];
					$data['broker_id'] = $broker_id;
					$data['related_type'] = $check_staff_type['staff_type'];
					$data['related_id'] = $_broker_staff_id;

					$data['broker_related_type'] = $this->input->post('broker_type');
					$data['broker_related_id'] = $_broker_staff_id;
				}elseif($this->input->post('broker_type') == 'business_broker'){
					$broker_staff = get_broker_staff($_broker_staff_id);
					if($broker_staff){
						$broker_id = $broker_staff->company_id;
					}

					$data['is_company_admin'] = 0;
					$data['company_id'] = 0;
					$data['broker_id'] = $broker_id;
					$data['related_type'] = 'business_broker';
					$data['related_id'] = $_broker_staff_id;

					$data['broker_related_type'] = $this->input->post('broker_type');
					$data['broker_related_id'] = $_broker_staff_id;
				}
				
				$insert_id = $this->realestate_model->add_property_request($data);
				if ($insert_id) {
					set_alert('success', _l('added_successfully'));
				}
				if($data['request_type'] == 'buy'){
					redirect(site_url('realestate/client/buy'));
				}else{
					redirect(site_url('realestate/client/rents'));
				}

			} else {
				if (!is_client_logged_in()) {
					access_denied('real_property_request');
				}
				$success = $this->realestate_model->update_property_request($data, $id);
				$property_request = $this->realestate_model->get_property_request($id);
				$request_type = $property_request->request_type;

				set_alert('success', _l('updated_successfully'));
				if($request_type == 'buy'){
					redirect(site_url('realestate/client/buy'));
				}else{
					redirect(site_url('realestate/client/rents'));
				}
			}
		}
		
		$data=[];
		if($this->input->get('property')){
			$property_id = $this->input->get('property');
			$data['property'][] = (array)$this->realestate_model->get_property_listing($property_id);
			$data['property_id'] = $property_id;
			$data['_broker_id'] = $this->input->get('broker_id');
			$data['_broker_type'] = $this->input->get('broker_type');
		}
		$data['customer_id'] = get_client_user_id();
		
		if ($id != ''){
			$property_request = $this->realestate_model->get_property_request($id);
			$data['property_request'] = $property_request;
			$request_type = $property_request->request_type;
			$get_property_listing = $this->realestate_model->get_property_listing($property_request->item_id);
			$data['property'][] = (array)$get_property_listing;
			$data['rental_type'] = $get_property_listing->rental_type;
		}

		$transaction_type = 'Sale';
		if($request_type == 'buy'){
			$transaction_type = 'Sale';
			$data['title'] = _l('real_buy_application');
		}else{
			$transaction_type = 'Rent';
			$data['title'] = _l('real_rental_application');
		}
		$data['items']     = $this->realestate_model->get_property_listing(false, "status != 'pending' AND status != 'pending_sale' AND status != 'sold' AND status != 'rented'");
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
		$this->data($data);
		$this->view('clients/property_request_buy/request');
		$this->layout();
	}

	/**
	 * buy
	 * @param  boolean $status 
	 * @return [type]          
	 */
	public function buy()
	{
		$data['requests'] = $this->realestate_model->get_property_request(false, "clientid = ".get_client_user_id()." AND request_type = 'buy'");

		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$data['base_currency_id'] = $base_currency_id;
		$data['site_url'] = $this->site_url;

		$data['title']    = _l('real_buy_application');
		$this->data($data);
		$this->view('clients/property_request_buy/manage');
		$this->layout();
	}

	/**
	 * rent
	 * @param  boolean $status 
	 * @return [type]          
	 */
	public function rents()
	{
		$data['requests'] = $this->realestate_model->get_property_request(false, "clientid = ".get_client_user_id()." AND request_type = 'rent'");

		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$data['base_currency_id'] = $base_currency_id;
		$data['site_url'] = $this->site_url;

		$data['title']    = _l('real_rental_application');
		$this->data($data);
		$this->view('clients/property_request_rents/manage');
		$this->layout();
	}

	/**
	 * request detail
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function request_detail($id)
	{
		$data['property_request'] = $this->realestate_model->get_property_request($id);
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$data['base_currency_id'] = $base_currency_id;
		$data['site_url'] = $this->site_url;

		$data['title']    = _l('real_buy_application');
		$this->data($data);
		$this->view('clients/property_request_buy/request_detail');
		$this->layout();
	}

	/**
	 * property request pdf
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function property_request_pdf($id)
	{
		if (!$id) {
			redirect(site_url('realestate/client/buy'));
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
	 * agent
	 * @return [type] 
	 */
	public function agent($id)
	{
		if(!is_numeric($id)){
			redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
		}
		$construction_company                = $this->realestate_model->get_construction_company($id);
		if($construction_company->privacy == 'private'){
			set_alert('warning', _l('real_Access_denied_because_agent_profile_is_private'));
			redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
		}

		$data['construction_company'] = $construction_company;

		$map_listing_where = [];
		$map_listing_where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';
		if($construction_company->related_type == 'company'){
			$map_listing_where[] = 'AND ('.db_prefix().'items.company_id = '.$id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$id.' ) )';
		}else{
				// broker
			$map_listing_where[] = 'AND ('.db_prefix().'items.broker_id = '.$id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$id.') )';
		}

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

		$data['map_property_listing'] = $this->realestate_model->map_get_property_listing($map_listing_where, false, '', '', 0, true, false, 'company');
		$data['sale_performance'] = $this->realestate_model->get_sale_performance($id);
		$data['total_page'] = $this->realestate_model->get_total_page('can_be_property_listing', 'can_be_property_listing', 'items');
		$data['title'] = $construction_company->name;
		$data['site_url'] = $this->site_url;

		$this->data($data);
		$this->view('clients/property_agents/public_profile');
		$this->layout();
	}

	/**
	 * company
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function company($id)
	{
		if(!is_numeric($id)){
			redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
		}

		$this->load->model('staff_model');
		$construction_company                = new \stdClass;
		$construction_company->id = 0;
		$construction_company->name = get_option('invoice_company_name');
		$construction_company->code = '';
		$construction_company->related_type = 'company';
		$construction_company->company_staffs = $this->staff_model->get(false, ['company_id' => 0]);
		$construction_company->public_company_staffs = $this->staff_model->get(false, ['company_id' => 0, 'mark_public' => 1]);
		$construction_company->about_information = '';  
		$construction_company->phonenumber = get_option('invoice_company_phonenumber');
		$construction_company->facebook_url = '';
		$construction_company->instagram_url = '';
		$construction_company->whatsapp_url = '';
		$construction_company->website = '';
		$construction_company->email = '';

		$map_listing_where = [];
		$map_listing_where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';

		$map_listing_where[] = 'AND ('.db_prefix().'items.is_company_admin = 1)';

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

		$data['construction_company'] = $construction_company;
		$data['map_property_listing'] = $this->realestate_model->map_get_property_listing($map_listing_where, false, '', '', 0, true, false, 'company');
		$data['sale_performance'] = $this->realestate_model->get_sale_performance($id);
		$data['total_page'] = $this->realestate_model->get_total_page('can_be_property_listing', 'can_be_property_listing', 'items');
		$data['title'] = $construction_company->name;
		$data['site_url'] = $this->site_url;
		$data['company_admin'] = true;

		$this->data($data);
		$this->view('clients/property_agents/public_profile');
		$this->layout();
	}

	/**
	 * company property grid view
	 * @return [type] 
	 */
	public function staff_property_grid_view($staff_id)
	{
		$property_grid_view = $this->realestate_model->staff_property_grid_view($staff_id);
		echo json_encode(['html' => $property_grid_view['html'], 'total_page' => $property_grid_view['total_page']]);
	}

	/**
	 * staff
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function staff($id)
	{
		if(!is_numeric($id)){
			redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
		}

		$this->load->model('staff_model');
		$staff                = $this->staff_model->get($id);

		if($staff->mark_public == 0){
			set_alert('warning', _l('real_Access_denied_because_staff_profile_is_private'));
			redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
		}

		$data['staff'] = $staff;

		$map_listing_where = [];
		$staff_in_company = rel_check_staff_in_company($id);
		$map_listing_where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';
		if($staff_in_company){
			// staff in company
			$map_listing_where[] = 'AND ( ('.db_prefix().'items.related_type = "company" AND '.db_prefix().'items.related_id = '.$id .') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$id.') )';
		}else{
			// staff not in construction company
			$map_listing_where[] = 'AND (('.db_prefix().'items.related_type = "staff" AND '.db_prefix().'items.related_id = '.$id.') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$id.') )';
		}

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

		$data['map_property_listing'] = $this->realestate_model->map_get_property_listing($map_listing_where, false, '', '', 0, true, false, 'client');

		$data['sale_performance'] = $this->realestate_model->get_staff_sale_performance($id);
		$data['total_page'] = $this->realestate_model->get_total_page('can_be_property_listing', 'can_be_property_listing', 'items');
		$data['title'] = $staff->firstname.' '.$staff->lastname;
		$data['site_url'] = $this->site_url;

		$this->data($data);
		$this->view('clients/staffs/public_profile');
		$this->layout();
	}

	/**
	 * delete_property_request
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_property_request($id) {

		$request = $this->realestate_model->get_property_request($id);
		$client_id = get_client_user_id();

		if(!is_primary_contact() || ($request->clientid != $client_id)) {
			access_denied('real_property_request');
		}

		$response = $this->realestate_model->delete_property_request($id);
		if ($response == true) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}

		if($request->request_type == 'buy'){
			redirect(site_url('realestate/client/buy'));
		}else{
			redirect(site_url('realestate/client/rents'));
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

}