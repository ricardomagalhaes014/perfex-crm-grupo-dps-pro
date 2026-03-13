<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Class Realestate
 */
class Realestate extends AdminController
{
	/**
	 * __construct
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('realestate_model');
		$this->load->model('broker_model');
		hooks()->do_action('realestate_init');
		$this->site_url = admin_url().'realestate/';
	}

	/**
	 * settings
	 * @return [type] 
	 */
	public function settings()
	{
		
		if(!is_admin() && !has_permission('real_permission', '', 'view')){
			access_denied('re_settings');
		}
		$data['title']                 = _l('reale_settings');
		$data['tab'] = $this->input->get('tab');
		$data['roles']         = $this->realestate_model->get_role('', '(role_type = "plan_detail")');
		
		$this->load->view('settings/manage_setting', $data);
	}

	/**
	 * prefix setting
	 * @return [type] 
	 */
	public function prefix_setting()
	{
		$data = $this->input->post();

		if ($data) {
			
			$success = $this->realestate_model->update_prefix_setting($data);

			if ($success == true) {

				$message = _l('updated_successfully');
				set_alert('success', $message);
			}

			redirect(admin_url('realestate/settings?tab=prefix_setting'));
		}
	}

	/**
	 * role changed
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function role_changed($id)
	{
		echo json_encode($this->roles_model->get($id)->permissions);
	}

	/**
	 * role table
	 * @return [type] 
	 */
	public function role_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'settings/roles/role_table'));
	}

	/**
	 * role
	 * @param  string $id 
	 * @return [type]     
	 */
	public function role($id = '')
	{
		if (!has_permission('real_permission', '', 'view')) {
			access_denied('roles');
		}
		if ($this->input->post()) {
			if ($id == '') {
				if (!has_permission('real_permission', '', 'create')) {
					access_denied('roles');
				}
				$id = $this->roles_model->add($this->input->post());
				if ($id) {
					set_alert('success', _l('added_successfully', _l('role')));
					redirect(admin_url('realestate/role/' . $id));
				}
			} else {
				if (!has_permission('real_permission', '', 'edit')) {
					access_denied('roles');
				}
				$success = $this->roles_model->update($this->input->post(), $id);
				if ($success) {
					set_alert('success', _l('updated_successfully', _l('role')));
				}
				redirect(admin_url('realestate/role/' . $id));
			}
		}
		if ($id == '') {
			$title = _l('real_new_plan_detail');
		} else {
			$data['role_staff'] = $this->roles_model->get_role_staff($id);
			$role               = $this->roles_model->get($id);
			$data['role']       = $role;
			$title              = _l('edit', _l('real_plan_detail')) . ' ' . $role->name;
		}
		$data['title'] = $title;
		$this->load->view('settings/roles/role', $data);

	}

	/**
	 * delete role
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_role($id)
	{
		if (!has_permission('real_permission', '', 'delete')) {
			access_denied('roles');
		}
		if (!$id) {
			redirect(admin_url('roles'));
		}
		$response = $this->roles_model->delete($id);
		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('is_referenced', _l('role_lowercase')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted', _l('role')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('role_lowercase')));
		}
		redirect(admin_url('realestate/settings?tab=role'));
	}

	/**
	 * plan table
	 * @return [type] 
	 */
	public function plan_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'settings/plans/plan_table'));
	}

	/**
	 * plan
	 * @return [type] 
	 */
	public function plan()
	{
		$message = '';
		$success = false;
		if ($this->input->post()) {
			if ($this->input->post('id')) {
				if(!has_permission('real_plan', '', 'edit')  &&  !is_admin()) {
					access_denied('real_plan');
				}

				$id = $this->input->post('id');
				$data = $this->input->post();
				if(isset($data['id'])){
					unset($data['id']);
				}
				$data['long_description'] = $this->input->post('long_description', false);
				$success = $this->realestate_model->update_plan($data, $id);
				if ($success == true) {
					$message = _l('updated_successfully', _l('real_plan'));
				}
			} else {
				if(!has_permission('real_plan', '', 'create')  &&  !is_admin()) {
					access_denied('real_plan');
				}

				$data = $this->input->post();

				$staff_user_id = get_staff_user_id();
				$admin_id = $staff_user_id;
				$construction_company_id = 0;
				$agent_id = 0;
				$created_id = $staff_user_id;

				$data['admin_id'] = $admin_id;
				$data['created_id'] = $created_id;
				$data['long_description'] = $this->input->post('long_description', false);
				$success = $this->realestate_model->add_plan($data);
				if ($success == true) {
					$message = _l('added_successfully', _l('real_plan'));
				}
			}
		}
		echo json_encode([
			'success' => $success,
			'message' => $message,
		]);
	}

	/**
	 * delete plan
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_plan($id)
	{
		if (!$id) {
			redirect(admin_url('realestate/settings?tab=plan'));
		}

		if(!has_permission('real_plan', '', 'delete')  &&  !is_admin()) {
			access_denied('real_plan');
		}

		$response = $this->realestate_model->delete_plan($id);
		if ($response) {
			set_alert('success', _l('deleted'));
			redirect(admin_url('realestate/settings?tab=plan'));
		} else {
			set_alert('warning', _l('problem_deleting'));
			redirect(admin_url('realestate/settings?tab=plan'));
		}

	}

	/**
	 * companies
	 * @return [type] 
	 */
	public function companies()
	{

		if(!has_permission('real_estate_agent', '', 'view') && !has_permission('real_estate_agent', '', 'view_own')){
			access_denied('real_real_estate_agents');
		}

		$data['title']                 = _l('real_real_estate_agents');

		$this->load->view('companies/companies/manage', $data);
	}

	/**
	 * company table
	 * @return [type] 
	 */
	public function company_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/companies/company_table'));
	}

	/**
	 * add edit company
	 * @param string $id 
	 */
	public function add_edit_company($id = 0, $related_type = 'company')
	{
		if ($this->input->post() && !$this->input->is_ajax_request()) {
			$data = $this->input->post();
			$data['password'] = $this->input->post('password', false);
			if ($id == 0) {

				$broker_id = 0;
				$related_id = get_staff_user_id();
				$check_staff_type = rel_check_staff_type();

				$data['is_company_admin'] = $check_staff_type['is_company_admin'];
				$data['company_id'] = $check_staff_type['company_id'];
				$data['broker_id'] = $broker_id;
				$data['related_id'] = $related_id;

				$result_data = $this->realestate_model->add_construction_company($data);

				if (isset($result_data['id'])) {
					if($data['related_type'] == 'company'){
						handle_staff_profile_image_upload($result_data['staff_id']);
					}else{
						handle_broker_profile_image_upload($result_data['staff_id']);
					}
					rel_handle_company_attachments_pdf($result_data['id'], 'file');
					handle_company_profile_image_upload($result_data['id']);

					set_alert('success', _l('added_successfully', _l('real_construction_company')));
				}
				if($data['related_type'] == 'company'){
					redirect(admin_url('realestate/companies'));
				}else{
					redirect(admin_url('realestate/business_brokers'));
				}
			} else {

				$success = $this->realestate_model->update_construction_company($data, $id);
				$result = rel_handle_company_attachments_pdf($id, 'file');
				if($result){
					$success = true;
				}
				$result = handle_company_profile_image_upload($id);
				if($result){
					$success = true;
				}

				if ($success == true) {
					set_alert('success', _l('updated_successfully', _l('real_construction_company')));
				}

				if($data['related_type'] == 'company'){
					redirect(admin_url('realestate/companies'));
				}else{
					redirect(admin_url('realestate/business_brokers'));
				}
			}
		}

		$group         = !$this->input->get('group') ? 'add_edit_company' : $this->input->get('group');
		$data['group'] = $group;

		if ($group != 'staffs' && $contact_id = $this->input->get('contactid')) {
			redirect(admin_url('realestate/add_edit_company/' . $id . '?group=staffs&contactid=' . $contact_id));
		}

		$arr_approval_managers = [];

		if ($id == 0) {
			$data['related_type'] = $related_type;
			if($related_type == 'company'){
				$title = _l('add_new', _l('real_construction_company'));
				$data['company_code'] = $this->realestate_model->create_code('company');
			}else{
				$title = _l('add_new', _l('real_business_broker'));
				$data['company_code'] = $this->realestate_model->create_code('business_broker');
			}
		} else {
			$construction_company                = $this->realestate_model->get_construction_company($id);
			$data['customer_tabs'] = get_customer_profile_tabs();

			if (!$construction_company) {
				show_404();
			}

			$data['group'] = $this->input->get('group');
			$data['title']                 = _l('setting');
			$data['tab'][] = ['name' => 'add_edit_company', 'icon' => '<i class="fa fa-user-circle menu-icon"></i>'];

			if(has_permission('real_estate_agent_staff', '', 'view') || has_permission('real_business_broker', '', 'view_own') ||has_permission('real_business_broker', '', 'view')){
				$data['tab'][] = ['name' => 'staffs','icon' => '<i class="fa fa-users menu-icon"></i>'];
			}
			
			$data['tab'][] = ['name' => 'company_listings','icon' => '<i class="fa-solid fa-file-contract menu-icon"></i>'];
			$data['tab'][] = ['name' => 'review','icon' => '<i class="fa-solid fa-id-badge menu-icon"></i>'];

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
			if($data['group'] == 'review'){
				$map_listing_where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';
			}else{
				// company_listings
				$map_listing_where[] = 'AND status != "pending"';
			}

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
			
			// check add hash
			if($construction_company){
				if(is_null($construction_company->hash)){
					$construction_companies['hash'] = app_generate_hash();
					$this->db->where('id', $id);
					$this->db->update(db_prefix() . 'real_companies', $construction_companies);
				}
			}
		}
		$arr_approval_managers = $this->staff_model->get('', 'company_id=0 ');
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
		$data['departments']   = $this->departments_model->get();
		$data['site_url'] = $this->site_url;
		$data['title']     = $title;
		$data['is_staff']     = true;

		$this->load->view('companies/companies/company', $data);
	}

	/**
	 * delete company
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_company($id)
	{
		if (!is_admin() && !has_permission('rel_company', '', 'delete') ) {
			access_denied('rel_company');
		}

		$success = $this->realestate_model->delete_company($id);
		if ($success) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('realestate/companies'));
	}

	/**
	 * delete broker
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_broker($id)
	{
		if (!is_admin() && !has_permission('rel_company', '', 'delete') ) {
			access_denied('rel_company');
		}

		$success = $this->realestate_model->delete_company($id);
		if ($success) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('realestate/business_brokers'));
	}

	/**
	 * company staff table
	 * @param  [type] $company_id 
	 * @return [type]             
	 */
	public function company_staff_table($company_id)
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/companies/company_staff_table'), [
			'company_id' => $company_id,
		]);
	}

	/**
	 * staff modal
	 * @return [type] 
	 */
	public function staff_modal()
	{
		if(!has_permission('real_estate_agent_staff', '', 'view') &&  !has_permission('real_estate_agent_staff', '', 'create')  &&  !has_permission('real_estate_agent_staff', '', 'edit')){
			access_denied('re_per_construction_company_staffs');
		}

		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$this->load->model('staff_model');
		$data=[];
		$data['staffs'] = $this->staff_model->get();
		$data['company_id'] = $this->input->post('company_id');

		$type = $this->input->post();
		$construction_company = $this->realestate_model->get_construction_company($data['company_id']);
		$plan_id = $construction_company->plan_id;

		if($type['slug'] == 'add'){
		//add
		//only allow company create new staff, Freelance don't
			$data['header'] = _l('asm_add_staff');
			$data['roles']         = $this->realestate_model->get_role('', '(role_type = "plan_detail" )');

			$company_role_id = '';
			if($construction_company){
				$get_plan = $this->realestate_model->get_plan($construction_company->plan_id);
				if($get_plan){
					$company_role_id = $get_plan->role_id;
				}
			}
			$data['is_add'] = true;
			$data['company_role_id'] = $company_role_id;
			$data['related_type'] = 'construction';
			$data['staff_identifi_code'] = $this->realestate_model->create_code('staff');

		}else{
		//update
			$this->load->model('staff_model');

			$staff_id = $this->input->post('staff_id');
			$data['member'] = $this->staff_model->get($staff_id);
			$data['staff_departments'] = $this->departments_model->get_staff_departments($staff_id);
			$data['header'] = _l('asm_update_staff');
			if(isset($data['member']->freelance_id) && $data['member']->freelance_id > 0){
				$data['roles']         = $this->realestate_model->get_role('', 'role_type = "plan_detail" OR role_type = "approval_manager

					"');
				$data['related_type'] = 'business_broker';

			}else{
				$data['roles']         = $this->realestate_model->get_role('', '(role_type = "plan_detail" )');
				$data['related_type'] = 'construction';
			}
		}

		$role_id = $this->input->post('role_id');
		$this->load->model('currencies_model');

		$data['base_currency'] = $this->currencies_model->get_base_currency();
		$data['roles_value']         = $this->roles_model->get();
		$data['departments']   = $this->departments_model->get();
		$data['plan_id']   = $plan_id;
		$data['title'] = _l('asm_staff');

		$this->load->view('companies/companies/modals/add_edit_staff_modal', $data);
	}

	/**
	 * add edit construction staff
	 * @param string $id 
	 */
	public function add_edit_construction_staff($id = '')
	{
		if ( !has_permission('real_estate_agent_staff', '', 'create') && !has_permission('real_estate_agent_staff', '', 'edit')) {
			access_denied('staff');
		}
		hooks()->do_action('staff_member_edit_view_profile', $id);

		$this->load->model('departments_model');
		if ($this->input->post()) {
			$company_id = $this->input->post('company_id');

			$data = $this->input->post();
			// Don't do XSS clean here.
			$data['email_signature'] = $this->input->post('email_signature', false);
			$data['email_signature'] = html_entity_decode($data['email_signature']);

			if ($data['email_signature'] == strip_tags($data['email_signature'])) {
				// not contains HTML, add break lines
				$data['email_signature'] = nl2br_save_html($data['email_signature']);
			}

			$data['password'] = $this->input->post('password', false);

			if ($id == '') {
				if (!has_permission('real_estate_agent_staff', '', 'create')) {
					access_denied('staff');
				}

				$data['staff_identifi'] = $this->realestate_model->create_code('staff');
				// construction
				$data['staff_type'] = 'company';

				$id = $this->staff_model->add($data);
				update_option('staff_code_number', (int)get_option('staff_code_number')+1);
				if ($id) {
					handle_staff_profile_image_upload($id);
					set_alert('success', _l('added_successfully', _l('staff_member')));
					redirect(admin_url('realestate/add_edit_company/'.$company_id.'?group=staffs'));
				}
			} else {
				if (!has_permission('real_estate_agent_staff', '', 'edit') ) {
					access_denied('staff');
				}
				handle_staff_profile_image_upload($id);
				$response = $this->staff_model->update($data, $id);
				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('updated_successfully', _l('staff_member')));
				}
				redirect(admin_url('realestate/add_edit_company/'.$company_id.'?group=staffs'));
			}
		}
	}

	/**
	 * change construction company status
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_construction_company_status($id, $status)
	{
		if (has_permission('rel_company', '', 'edit')) {
			if ($this->input->is_ajax_request()) {
				$this->realestate_model->change_construction_company_status($id, $status);
			}
		}
	}

	/**
	 * change staff status
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_staff_status($id, $status)
	{
		if (has_permission('real_estate_agent_staff', '', 'edit')) {
			if ($this->input->is_ajax_request()) {
				$this->staff_model->change_staff_status($id, $status);
			}
		}
	}

	/**
	 * change staff public
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_staff_public($id, $status)
	{
		if (has_permission('real_estate_agent_staff', '', 'edit')) {
			if ($this->input->is_ajax_request()) {
				$this->realestate_model->change_staff_public($id, $status);
			}
		}
	}

	/**
	 * delete staff
	 * @return [type] 
	 */
	public function delete_staff()
	{
		if (!is_admin() && is_admin($this->input->post('id'))) {
			die('Busted, you can\'t delete administrators');
		}

		$company_id = $this->input->post('company_id');
		if (has_permission('real_estate_agent_staff', '', 'delete') || has_permission('staff', '', 'delete')) {
			$this->load->model('staff_model');

			$success = $this->staff_model->delete($this->input->post('id'), $this->input->post('transfer_data_to'));
			if ($success) {
				set_alert('success', _l('deleted', _l('staff_member')));
			}
		}
		if($company_id == 0){
			redirect(admin_url('realestate/company_staffs'));
		}else{
			redirect(admin_url('realestate/add_edit_company/'.$company_id.'?group=staffs'));
		}
	}


	/**
	 * property owners
	 * @return [type] 
	 */
	public function property_owners()
	{
		if(!has_permission('real_property_owner', '', 'view') && !has_permission('real_property_owner', '', 'view_own')){
			access_denied('real_property_owners');
		}

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
				$broker_id = 0;
				$related_id = get_staff_user_id();
				$check_staff_type = rel_check_staff_type();

				$data['is_company_admin'] = $check_staff_type['is_company_admin'];
				$data['company_id'] = $check_staff_type['company_id'];
				$data['broker_id'] = $broker_id;
				$data['related_type'] = $check_staff_type['staff_type'];
				$data['related_id'] = $related_id;
				
				$result_data['id'] = $this->realestate_model->add_owner($data);

				if (isset($result_data['id'])) {
					handle_owner_profile_image_upload($result_data['id']);
					set_alert('success', _l('added_successfully', _l('real_property_owner')));
				}
				redirect(admin_url('realestate/property_owners'));
			} else {
				$success = $this->realestate_model->update_owner($data, $id);
				handle_owner_profile_image_upload($id);

				if ($success == true) {
					set_alert('success', _l('updated_successfully', _l('real_property_owner')));
				}
				redirect(admin_url('realestate/add_edit_owner/'.$id));
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
		if (!is_admin() && !has_permission('rel_property_owner', '', 'delete') ) {
			access_denied('real_property_owner');
		}

		$success = $this->realestate_model->delete_owner($id);
		if ($success) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		redirect(admin_url('realestate/property_owners'));
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
		if (has_permission('real_property_owner', '', 'edit')) {
			if ($this->input->is_ajax_request()) {
				$this->realestate_model->change_property_owner_status($id, $status);
			}
		}
	}


	/**
	 * get plan
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_plan($id) {

		$plan = $this->realestate_model->get_plan($id);
		if (isset($plan)) {
			$long_description = $plan->long_description;
		} else {
			$long_description = '';
		}

		echo json_encode([
			'long_description' => $long_description,
		]);

	}

	/**
	 * setting google map API Code
	 * @return [type] 
	 */
	public function setting_google_map_API_Code(){
		$data = $this->input->post();

		if (!has_permission('rel_setting_general', '', 'create') && !is_admin()) {
			$success = false;
			$message = _l('Not permission edit');

			echo json_encode([
				'message' => $message,
				'success' => $success,
			]);
			die;
		}

		if($data != 'null'){
			$result = update_option('real_Gogle_Map_API_Code', $data['real_Gogle_Map_API_Code']);
			if($result){
				$success = true;
				$message = _l('updated_successfully');
			}else{
				$success = false;
				$message = _l('updated_false');
			}
			echo json_encode([
				'message' => $message,
				'success' => $success,
			]);
			die;
		}
	}

	/**
	 * get role by plan id
	 * @param  string $plan_id 
	 * @return [type]          
	 */
	public function get_role_by_plan_id($plan_id = '')
	{
		$role_id = 0;
		if(is_numeric($plan_id)){
			$plan = $this->realestate_model->get_plan($plan_id);
			if($plan){
				$role_id = $plan->role_id;
			}
		}
		echo json_encode([
			'role_id' => $role_id,
		]);
	}


	/**
	 * freelance_agents
	 * @return [type] 
	 */
	public function business_brokers()
	{

		if(!has_permission('real_business_broker', '', 'view') && !has_permission('real_business_broker', '', 'view_own')){
			access_denied('real_business_brokers');
		}

		$data['title']                 = _l('real_business_brokers');

		$this->load->view('companies/business_brokers/manage', $data);
	}

	/**
	 * company table
	 * @return [type] 
	 */
	public function business_broker_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/business_brokers/business_broker_table'));
	}

	/**
	 * broker staff table
	 * @param  [type] $company_id 
	 * @return [type]             
	 */
	public function broker_staff_table($company_id)
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/business_brokers/broker_staff_table'), [
			'company_id' => $company_id,
		]);
	}

	/**
	 * broker staff modal
	 * @return [type] 
	 */
	public function broker_staff_modal()
	{
		if(  !has_permission('real_estate_agent_staff', '', 'create')  &&  !has_permission('real_estate_agent_staff', '', 'edit') ){
			access_denied('re_per_construction_company_staffs');
		}

		if (!$this->input->is_ajax_request()) {
			show_404();
		}

		$data=[];
		$data['staffs'] = $this->broker_model->get_broker_staff();
		$data['company_id'] = $this->input->post('company_id');

		$type = $this->input->post();
		$construction_company = $this->realestate_model->get_construction_company($data['company_id']);

		if($type['slug'] == 'add'){
		//add
		//only allow company create new staff, Freelance don't
			$data['header'] = _l('real_add_staff');
			$data['is_add'] = true;
			$data['related_type'] = 'business_broker';
			$data['staff_identifi_code'] = $this->realestate_model->create_code('broker_staff');
		}else{
		//update

			$staff_id = $this->input->post('staff_id');
			$data['member'] = $this->broker_model->get_broker_staff($staff_id);
			$data['header'] = _l('real_update_staff');
			$data['related_type'] = 'business_broker';
		}

		$this->load->model('currencies_model');

		$data['base_currency'] = $this->currencies_model->get_base_currency();
		$data['title'] = _l('asm_staff');

		$this->load->view('brokers_portals/profiles/add_edit_staff_modal', $data);
	}

	/**
	 * remove profile image
	 * @param  string $id 
	 * @return [type]     
	 */
	public function remove_profile_image($id = '', $company_id = '')
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

		redirect(admin_url('realestate/add_edit_company/'.$company_id.'?group=broker_staffs'));
	}

	/**
	 * add edit broker staff
	 * @param string $id 
	 */
	public function add_edit_broker_staff($id = '')
	{
		if (!has_permission('real_estate_agent_staff', '', 'create') && !has_permission('real_estate_agent_staff', '', 'edit')) {
			access_denied('staff');
		}
		hooks()->do_action('broker_staff_member_edit_view_profile', $id);

		if ($this->input->post()) {
			$company_id = $this->input->post('company_id');
			$data = $this->input->post();
			
			$data['password'] = $this->input->post('password', false);

			if ($id == '') {
				if (!has_permission('real_estate_agent_staff', '', 'create')) {
					access_denied('staff');
				}

				$data['staff_identifi'] = $this->realestate_model->create_code('broker_staff');
				// construction
				$data['staff_type'] = 'company';

				$id = $this->broker_model->add($data);
				update_option('real_broker_staff_number', (int)get_option('real_broker_staff_number')+1);

				if ($id) {
					handle_broker_profile_image_upload($id);
					set_alert('success', _l('added_successfully', _l('staff_member')));
					redirect(admin_url('realestate/add_edit_company/'.$company_id.'?group=broker_staffs'));
				}
			} else {
				if (!has_permission('real_estate_agent_staff', '', 'edit')) {
					access_denied('staff');
				}
				handle_broker_profile_image_upload($id);
				$response = $this->broker_model->update($data, $id);
				if (is_array($response)) {
					if (isset($response['cant_remove_main_admin'])) {
						set_alert('warning', _l('staff_cant_remove_main_admin'));
					} elseif (isset($response['cant_remove_yourself_from_admin'])) {
						set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
					}
				} elseif ($response == true) {
					set_alert('success', _l('updated_successfully', _l('staff_member')));
				}
				redirect(admin_url('realestate/add_edit_company/'.$company_id.'?group=broker_staffs'));
			}
		}
	}

	/**
	 * property listings
	 * @return [type] 
	 */
	public function properties()
	{
		if(!has_permission('real_property', '', 'view') && !has_permission('real_property', '', 'view_own') && !has_permission('rel_my_property_listing','','view_own') ) {
			access_denied('real_listings');
		}

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

		$map_listing_where = [];

		$staff_in_company = rel_check_staff_in_company();
		$get_staff_user_id = get_staff_user_id();
		$map_listing_where[] = 'AND (status != "pending")';
		if(is_admin()){
			// is admin: view all
		}elseif($staff_in_company){
			// staff in company
			if(has_permission('real_property', '', 'view_own')){

				$map_listing_where[] = 'AND ( ('.db_prefix().'items.related_type = "company" AND '.db_prefix().'items.related_id = '.$get_staff_user_id .') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.') )';

			}elseif(has_permission('real_property', '', 'view')){

				$map_listing_where[] = 'AND ('.db_prefix().'items.company_id = '.$staff_in_company.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.' OR '.db_prefix().'real_request_brokerages.company_id = '.$staff_in_company.' ) )';
			}else{
				$map_listing_where[] = 'AND 1=2';
			}

		}else{
			// staff not in construction company
			if(has_permission('real_property', '', 'view')){

				$map_listing_where[] = 'AND ('.db_prefix().'items.is_company_admin = 1 OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.') )';

			}elseif(has_permission('real_property', '', 'view_own')){

				$map_listing_where[] = 'AND (('.db_prefix().'items.related_type = "staff" AND '.db_prefix().'items.related_id = '.$get_staff_user_id.') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.') )';
			}else{
				$map_listing_where[] = 'AND 1=2';
			}
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


		$staff_in_construction_company = rel_check_staff_in_company();
		$data['isMap'] = $this->session->has_userdata('listings_map_view') &&
		$this->session->userdata('listings_map_view') == 'true';
		$data['site_url'] = $this->site_url;
		

		$this->load->view('companies/property_listings/manage', $data);
	}

	/**
	 * property listing table
	 * @return [type] 
	 */
	public function property_listing_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/property_listings/property_listing_table'), ['rel_type' => 'company',
			'site_url' => $this->site_url,
		]);
	}

	/**
	 * add edit property listing
	 * @param string $id 
	 */
	public function add_edit_property_listing($id = '')
	{
		if(!has_permission('real_property', '', 'view') && !has_permission('real_property', '', 'view_own')){
			access_denied('real_properties');
		}
		$staff_user_id = get_staff_user_id();
		if ($this->input->post()) {
			$data = $this->input->post();
			$data['long_description'] = $this->input->post('long_description', false);
			
			if ($id == '') {
				if (!has_permission('real_property', '', 'create') && !has_permission('real_property', '', 'create_10_on_month') && !is_admin()) {
					access_denied('real_property');
				}

				$broker_id = 0;
				$related_id = get_staff_user_id();
				$check_staff_type = rel_check_staff_type();

				$data['formdata'][] = [
					'name' => 'is_company_admin',
					'value' => $check_staff_type['is_company_admin'],
				];
				$data['formdata'][] = [
					'name' => 'company_id',
					'value' => $check_staff_type['company_id'],
				];
				$data['formdata'][] = [
					'name' => 'broker_id',
					'value' => $broker_id,
				];
				$data['formdata'][] = [
					'name' => 'related_type',
					'value' => $check_staff_type['staff_type'],
				];
				$data['formdata'][] = [
					'name' => 'related_id',
					'value' => $related_id,
				];

				$insert_id = $this->realestate_model->add_property_listing($data);
				$url = admin_url('realestate/properties');

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
				$url = admin_url('realestate/properties');

				echo json_encode([
					'url' => $url,
					'add_or_update' => 'add',

				]);
				die;

			} else {
				if (!has_permission('real_property', '', 'edit') && !is_admin()) {
					access_denied('real_property');
				}
				$success = $this->realestate_model->update_property_listing($data, $id);
				/*update file*/
				set_alert('success', _l('updated_successfully', _l('real_listing')));

				$url = admin_url('realestate/properties');

				echo json_encode([
					'url' => $url,
					'commodityid' => $id,
					'add_or_update' => 'update',
				]);
				die;
			}

		}

		if ($id == '') {

			if (!has_permission('real_property', '', 'create')){
				access_denied('real_property');
			}
			
			$title = _l('real_add_new_listing');
			$data['room_templates'] = $this->realestate_model->create_property_listing_room_row_template();
			$data['payment_plan_templates'] = [];
		} else {
			if (!has_permission('real_property', '', 'edit')){
				access_denied('real_property');
			}

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
		$url = admin_url('realestate/properties');
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
		if (!has_permission('real_property', '', 'delete') && !is_admin()) {
			access_denied('real_property');
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
	 * add property listing attachment1
	 * @param [type] $id 
	 */
	public function add_property_listing_attachment1($id)
	{
		real_handle_property_listing_attachments1($id);
		$url = admin_url('realestate/properties');
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
		if (!has_permission('real_property', '', 'delete') && !is_admin()) {
			access_denied('real_property');
		}

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
			redirect(admin_url('real_estate/property_listings'));
		}

		if(!has_permission('real_property', '', 'delete')  &&  !is_admin()) {
			access_denied('real_property');
		}

		$response = $this->realestate_model->delete_property_listing($id, $rel_type);
		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('is_referenced', _l('real_property')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}
		
		redirect(admin_url('realestate/properties'));
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
	 * add property listing attachment2
	 * @param [type] $id 
	 */
	public function add_property_listing_attachment2($id)
	{
		real_handle_property_listing_attachments2($id);
		$url = admin_url('realestate/properties');
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
		$url = admin_url('realestate/properties');
		echo json_encode([
			'url' => $url,
			'id' => $id,
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

		if(!has_permission('real_property', '', 'view') && !has_permission('real_property', '', 'view_own')){
			access_denied('real_properties');
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
				$construction_company_id = $staff_type['company_id'];

				break;

				case 'property_agent':
				$construction_company_id = $staff_type['company_id'];
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
        $data['activities']                   = $this->realestate_model->get_realestate_activity($id);


		$data['contact_infor'] = real_get_contact_infor($data['property_listing']->related_id, $data['property_listing']->related_type);
		$data['title']                 = $property_listing->description;
		$public_profile_url = '';
		
		$data['public_profile_url'] = $public_profile_url;
		$data['request_brokers'] = $this->realestate_model->get_request_broker(false, "item_id = ".$property_listing->id, $property_listing->id);
		$data['site_url'] = $this->site_url;
		

		$this->load->view('companies/property_listings/property_listing_detail', $data);
	}

	/**
	 * reload map
	 * @return [type] 
	 */
	public function reload_map()
	{
		$map_property_listing = $this->realestate_model->render_query_data_for_map('company');

		echo json_encode([
			'map_property_listing' => $map_property_listing,
		]);
	}

	/**
	 * change favorite
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_favorite($id, $status)
	{
		if ($this->input->is_ajax_request()) {
			$this->realestate_model->add_favorite_listing($id, $status);
		}
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

		redirect(admin_url('realestate/add_edit_company/'.$id));
	}

	/**
	 * remove owner profile image
	 * @param  string $id 
	 * @return [type]     
	 */
	public function remove_owner_profile_image($id = '')
	{
		hooks()->do_action('before_remove_owner_profile_image');

		if (file_exists(OWNER_PROFILE_UPLOAD . $id)) {
			delete_dir(OWNER_PROFILE_UPLOAD . $id);
		}
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_property_owners', [
			'profile_image' => null,
		]);

		redirect(admin_url('realestate/add_edit_owner/'.$id));
	}

	/**
	 * request broker table
	 * @return [type] 
	 */
	public function request_broker_table($property_id)
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/property_listings/request_brokerages/request_broker_table'), ['item_id' => $property_id, 'site_url' => $this->site_url]);
	}

	/**
	 * request broker
	 * @return [type] 
	 */
	public function request_broker()
	{
		$message = '';
		$success = false;
		if ($this->input->post()) {
			if ($this->input->post('id')) {
				if(!has_permission('real_request_broker', '', 'edit')  &&  !is_admin()) {
					access_denied('real_request_broker');
				}

				$id = $this->input->post('id');
				$data = $this->input->post();
				if(isset($data['id'])){
					unset($data['id']);
				}
				$success = $this->realestate_model->update_request_broker($data, $id);
				if ($success == true) {
					$message = _l('updated_successfully', _l('real_request_broker'));
				}
			} else {
				if(!has_permission('real_request_broker', '', 'create')  &&  !is_admin()) {
					access_denied('real_request_broker');
				}

				$data = $this->input->post();

				$staff_user_id = get_staff_user_id();
				$broker_id = 0;
				$check_staff_type = rel_check_staff_type();
				$staff_in_company = rel_check_staff_in_company();

				$is_company_admin = $check_staff_type['is_company_admin'];
				$related_type = $check_staff_type['staff_type'];
				$related_id = $staff_user_id;
				$related_company_id = $check_staff_type['company_id'];
				$commission = $data['commission'];
				$item_id = $data['item_id'];
				$broker_type = $data['broker_type'];

				if($broker_type == 'staffs'){
					$broker_id = 0;
					if(is_admin() || !$staff_in_company){
						// is admin: assign to staff admin
						$company_id = 0;

					}elseif($staff_in_company){
						$company_id = $staff_in_company;
						// staff in company
					}
					if(isset($data['staff_id'])){
						foreach ($data['staff_id'] as $key => $staff_id) {
							$this->realestate_model->add_request_broker([
								'is_company_admin' => $is_company_admin,
								'related_type' => $related_type,
								'related_id' => $related_id,
								'related_company_id' => $related_company_id,
								'company_id' => $company_id,
								'broker_id' => $broker_id,
								'staff_id' => $staff_id,
								'commission' => $commission,
								'item_id' => $item_id,
							]);
						}
					}

				}elseif($broker_type == 'agents'){
					$broker_id = 0;

					if(isset($data['company_id'])){
						foreach ($data['company_id'] as $key => $company_id) {
							$this->realestate_model->add_request_broker([
								'is_company_admin' => $is_company_admin,
								'related_type' => $related_type,
								'related_id' => $related_id,
								'related_company_id' => $related_company_id,
								'company_id' => $company_id,
								'broker_id' => $broker_id,
								'staff_id' => 0,
								'commission' => $commission,
								'item_id' => $item_id,
							]);
						}
					}

				}elseif($broker_type == 'business_brokers'){

					if(isset($data['broker_id'])){
						foreach ($data['broker_id'] as $key => $broker_id) {
							$this->realestate_model->add_request_broker([
								'is_company_admin' => $is_company_admin,
								'related_type' => $related_type,
								'related_id' => $related_id,
								'related_company_id' => $related_company_id,
								'company_id' => 0,
								'broker_id' => $broker_id,
								'staff_id' => 0,
								'commission' => $commission,
								'item_id' => $item_id,
							]);
						}
					}
				}
				$success = true;
				if ($success == true) {
					$message = _l('added_successfully', _l('real_request_broker'));
				}
			}
		}
		echo json_encode([
			'success' => $success,
			'message' => $message,
		]);
	}

	/**
	 * delete request broker
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_request_broker($id, $property_id)
	{
		if (!$id) {
			redirect(admin_url('realestate/add_edit_property_listing/'.$property_id));
		}

		if(!has_permission('real_request_broker', '', 'delete')  &&  !is_admin()) {
			access_denied('request_broker');
		}

		$response = $this->realestate_model->delete_request_broker($id);
		if ($response) {
			set_alert('success', _l('deleted'));
			redirect(admin_url('realestate/add_edit_property_listing/'.$property_id));
		} else {
			set_alert('warning', _l('problem_deleting'));
			redirect(admin_url('realestate/add_edit_property_listing/'.$property_id));
		}
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
		}elseif($data['file']->rel_type == 'supporting_document'){
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
	 * company listing table
	 * @param  [type] $company_id 
	 * @return [type]             
	 */
	public function company_listing_table($company_id, $related_type)
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/companies/company_listing_table'), [
			'related_type' => $related_type,
			'company_id' => $company_id,
			'site_url' => $this->site_url,
		]);
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

			$broker_id = 0;
			$related_id = get_staff_user_id();
			$check_staff_type = rel_check_staff_type();

			$data['is_company_admin'] = $check_staff_type['is_company_admin'];
			$data['company_id'] = $check_staff_type['company_id'];
			$data['broker_id'] = $broker_id;
			$data['related_type'] = $check_staff_type['staff_type'];
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

			$broker_id = 0;
			$related_id = get_staff_user_id();
			$check_staff_type = rel_check_staff_type();

			$data['is_company_admin'] = $check_staff_type['is_company_admin'];
			$data['company_id'] = $check_staff_type['company_id'];
			$data['broker_id'] = $broker_id;
			$data['related_type'] = $check_staff_type['staff_type'];
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

			$broker_id = 0;
			$related_id = get_staff_user_id();
			$check_staff_type = rel_check_staff_type();

			$data['is_company_admin'] = $check_staff_type['is_company_admin'];
			$data['company_id'] = $check_staff_type['company_id'];
			$data['broker_id'] = $broker_id;
			$data['related_type'] = $check_staff_type['staff_type'];
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

			$broker_id = 0;
			$related_id = get_staff_user_id();
			$check_staff_type = rel_check_staff_type();

			$data['is_company_admin'] = $check_staff_type['is_company_admin'];
			$data['company_id'] = $check_staff_type['company_id'];
			$data['broker_id'] = $broker_id;
			$data['related_type'] = $check_staff_type['staff_type'];
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
	 * property listing status mark as
	 * @param  [type] $status 
	 * @param  [type] $id     
	 * @param  [type] $type   
	 * @return [type]         
	 */
	public function property_listing_status_mark_as($status, $id, $type)
	{
		$success = $this->realestate_model->property_listing_status_mark_as($status, $id, $type);
		$message = '';

		if ($success) {
			$message = _l('real_change_property_status_successfully');
		}
		echo json_encode([
			'success'  => $success,
			'message'  => $message
		]);
	}

	/**
	 * company staffs
	 * @return [type] 
	 */
	public function company_staffs()
	{
		if(!has_permission('view', '', 'view') && !has_permission('view', '', 'view_own')){
			access_denied('real_company_staffs');
		}

		$data['title']                 = _l('real_company_staffs');
		$this->load->model('staff_model');
		$data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
		$data['site_url'] = $this->site_url;
		$data['related_type'] = 'staff';

		$this->load->view('companies/company_staffs/manage', $data);
	}

	/**
	 * change staff is approval manager
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_staff_is_approval_manager($id, $status)
	{
		if(has_permission('real_estate_agent_staff', '', 'edit') || has_permission('staff', '', 'edit')){

			if ($this->input->is_ajax_request()) {
				$this->realestate_model->change_staff_is_approval_manager($id, $status);
			}
		}
	}

	/**
	 * change staff require approvals
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_staff_require_approvals($id, $status)
	{
		if(has_permission('real_estate_agent_staff', '', 'edit') || has_permission('staff', '', 'edit')){
			if ($this->input->is_ajax_request()) {
				$this->realestate_model->change_staff_require_approvals($id, $status);
			}
		}
	}

	/**
	 * approvals
	 * @return [type] 
	 */
	public function approvals()
	{
		$is_approval_mamanger = false;
		$this->load->model('staff_model');
		$staff = $this->staff_model->get(get_staff_user_id());
		if($staff && $staff->is_approval_manager == 1){
			$is_approval_mamanger = true;
		}

		if(!has_permission('real_property_approval', '', 'view') && $is_approval_mamanger) {
			access_denied('real_approvals');
		}

		$data = [];
		$data['title'] = _l('real_approvals');
		$data['is_approval_mamanger'] = $is_approval_mamanger;
		$this->load->view('companies/property_approvals/manage', $data);
	}

	/**
	 * property listing table
	 * @return [type] 
	 */
	public function pendding_property_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/property_approvals/pendding_property_table'), ['rel_type' => 'company',
			'site_url' => $this->site_url,
		]);
	}

	/**
	 * change status in bulk
	 * @return [type] 
	 */
	public function change_status_in_bulk()
	{
		
		if(!has_permission('real_property_approval', '', 'create') && !is_admin()){
			ajax_access_denied();
		}

		$success = false;
		if ($this->input->post()) {

			$ids                   = $this->input->post('ids');
			if(count($ids) > 0){
				$this->db->where('id IN('.implode(',', $ids).')');
				$this->db->update(db_prefix().'items', ['status' => 'new', 'date_update' => date('Y-m-d'), 'date_approval' => date('Y-m-d H:i:s'), 'approver_id' => get_staff_user_id()]);
				if($this->db->affected_rows() > 0){
					$success = true;
				}
			}

			echo json_encode([
				'success' => $success,
			]);
		}
	}

	/**
	 * property requests
	 * @param  string $id 
	 * @return [type]     
	 */
	public function requests($id = '')
	{
		if (!has_permission('real_buy_request', '', 'view') && !has_permission('real_buy_request', '', 'view_own') ) {
			access_denied('real_requests');
		}
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
		if (!has_permission('real_buy_request', '', 'create')  && !has_permission('real_buy_request', '', 'edit') && !has_permission('real_rent_request', '', 'create')  && !has_permission('real_rent_request', '', 'edit')) {
			access_denied('real_property_request');
		}
		$request_type = 'buy';
		if($this->input->get('request_type')){
			$request_type = $this->input->get('request_type');
		}

		if ($this->input->post()) {
			$data = $this->input->post();
			$id = $this->input->post('id');

			if ($id == '') {
				if (!has_permission('real_buy_request', '', 'create') && !has_permission('real_rent_request', '', 'create') && !is_admin()) {
					access_denied('real_property_request');
				}

				$broker_id = 0;
				$related_id = get_staff_user_id();
				$check_staff_type = rel_check_staff_type();

				$data['is_company_admin'] = $check_staff_type['is_company_admin'];
				$data['company_id'] = $check_staff_type['company_id'];
				$data['broker_id'] = $broker_id;
				$data['related_type'] = $check_staff_type['staff_type'];
				$data['related_id'] = $related_id;

				$insert_id = $this->realestate_model->add_property_request($data);
				if ($insert_id) {
					set_alert('success', _l('added_successfully'));
				}
				if($data['request_type'] == 'buy'){
					redirect(admin_url('realestate/requests'));
				}else{
					redirect(admin_url('realestate/rent_requests'));
				}

			} else {
				if (!has_permission('real_buy_request', '', 'edit') && !has_permission('real_rent_request', '', 'edit') && !is_admin()) {
					access_denied('real_property_request');
				}
				$success = $this->realestate_model->update_property_request($data, $id);
				$property_request = $this->realestate_model->get_property_request($id);
				$request_type = $property_request->request_type;

				set_alert('success', _l('updated_successfully'));
				if($request_type == 'buy'){
					redirect(admin_url('realestate/requests'));
				}else{
					redirect(admin_url('realestate/rent_requests'));
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

		
		$where = [];
		$transaction_type = 'Sale';
		if($request_type == 'buy'){
			$transaction_type = 'Sale';
		}else{
			$transaction_type = 'Rent';
		}

		$staff_in_company = rel_check_staff_in_company();
		$get_staff_user_id = get_staff_user_id();

		$where[] = 'AND ('.db_prefix().'items.transaction_type = "'.$transaction_type.'") AND (status = "new" OR status = "active")';

		if(is_broker_logged_in()){
			$business_broker_id = get_business_broker_id();
			$where[] = 'AND ('.db_prefix().'items.broker_id = '.$business_broker_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$business_broker_id.') )';
		}else{
			if(is_admin()){
			// is admin: view all
			}elseif($staff_in_company){
				$where[] = 'AND ('.db_prefix().'items.company_id = '.$staff_in_company.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.' OR '.db_prefix().'real_request_brokerages.company_id = '.$staff_in_company.' ) )';
			}else{
			// staff not in construction company
				$where[] = 'AND ('.db_prefix().'items.is_company_admin = 1 OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.') )';
			}
		}

		$smapWhere = '';
		$where = implode(' ', $where);
		if ($smapWhere == '') {
			$where = trim($where);
			if (startsWith($where, 'AND') || startsWith($where, 'OR')) {
				if (startsWith($where, 'OR')) {
					$where = substr($where, 2);
				} else {
					$where = substr($where, 3);
				}
			}
		}

		$data['items']     = $this->realestate_model->get_property_listing(false, $where);
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

		if(!has_permission('real_buy_request', '', 'delete') && !has_permission('real_rent_request', '', 'delete')  ) {
			access_denied('real_property_request');
		}
		$request = $this->realestate_model->get_property_request($id);
		$response = $this->realestate_model->delete_property_request($id);
		if ($response == true) {
			set_alert('success', _l('deleted'));
		} else {
			set_alert('warning', _l('problem_deleting'));
		}

		if($request->request_type == 'buy'){
			redirect(admin_url('realestate/requests'));
		}else{
			redirect(admin_url('realestate/rent_requests'));
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
		if (!has_permission('real_buy_request', '', 'view') && !has_permission('real_rent_request', '', 'view')) {
			echo _l('access_denied');
			die;
		}

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
		if (!has_permission('real_buy_request', '', 'create') && !has_permission('real_buy_request', '', 'edit') && !has_permission('real_rent_request', '', 'create') && !has_permission('real_rent_request', '', 'edit')) {
			access_denied('Convert to Contract');
		}
		if (!$id) {
			die('No Request found');
		}

		$contractid = $this->realestate_model->convert_to_contract($id, false);
		if ($contractid) {
			set_alert('success', _l('real_request_convert_to_contract_successfully'));
			if (has_permission('contracts', '', 'view') || has_permission('contracts', '', 'view_own')) {
				redirect(admin_url('contracts/contract/' . $contractid));
			}else{
				redirect(admin_url('realestate/requests'));
			}
		} else {
			redirect(admin_url('realestate/requests'));
		}
	}

	/* Convert estimate to invoice */
	public function convert_to_invoice($id)
	{
		if (!has_permission('real_buy_request', '', 'create') && !has_permission('real_buy_request', '', 'edit') && !has_permission('real_rent_request', '', 'create') && !has_permission('real_rent_request', '', 'edit')) {
			access_denied('Convert to Invoice');
		}
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
			if (has_permission('invoices', '', 'view') || has_permission('invoices', '', 'view_own')) {
				redirect(admin_url('invoices/list_invoices/' . $invoiceid));
			}else{
				redirect(admin_url('realestate/requests'));
			}
		} else {

			redirect(admin_url('realestate/requests'));
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
	 * add_note
	 * @param [type] $rel_id 
	 */
	public function add_note($rel_id)
	{
		if ($this->input->post()) {
			$this->misc_model->add_note($this->input->post(), 'property_request', $rel_id);
			echo $rel_id;
		}
	}

	/**
	 * get_notes
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_notes($id)
	{
		$data['notes'] = $this->misc_model->get_notes($id, 'property_request');
		$this->load->view('admin/includes/sales_notes_template', $data);
	}


	/**
	 * property request pdf
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function property_request_pdf($id)
	{
		if (!$id) {
			redirect(admin_url('realestate/requests'));
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
			redirect(admin_url('realestate/requests/' . $id));
		}else{
			redirect(admin_url('realestate/rent_requests/' . $id));
		}
	}

	/**
	 * property requests
	 * @param  string $id 
	 * @return [type]     
	 */
	public function rent_requests($id = '')
	{
		if (!has_permission('real_buy_request', '', 'view') && !has_permission('real_buy_request', '', 'view_own') ) {
			access_denied('real_requests');
		}
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
	 * tenants
	 * @param  string $id 
	 * @return [type]     
	 */
	public function tenants($id = '')
	{
		if (!has_permission('real_tenant', '', 'view') && !has_permission('real_tenant', '', 'view_own') ) {
			access_denied('real_tenants');
		}
		$this->load->model('invoices_model');
		$data['title'] = _l('real_requests');
		$data['clients'] = $this->clients_model->get();
		$data['propertyrequestid']            = $id;
		$data['site_url'] = $this->site_url;

		$this->load->view('companies/tenants/manage', $data);
	}

	/**
	 * tenant table
	 * @return [type] 
	 */
	public function tenant_table()
	{
		$this->app->get_table_data(module_views_path('realestate', 'companies/tenants/tenant_table'), [
			'site_url' => $this->site_url,
			'request_type' => 'rent',
		]);
	}

	/**
	 * auto create realestate setting
	 * @return [type] 
	 */
	public function auto_create_realestate_setting()
	{
		$data = $this->input->post();
		if($data != 'null'){
			$value = $this->realestate_model->update_auto_create_realestate_setting($data);
			if($value){
				$success = true;
				$message = _l('updated_successfully');
			}else{
				$success = false;
				$message = _l('updated_false');
			}
			echo json_encode([
				'message' => $message,
				'success' => $success,
			]);
			die;
		}
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
		$property_request = $this->realestate_model->get_property_request($request_id);
		$data['site_url'] = $this->site_url;
		$data['request_id'] = $request_id;
		$data['property_request'] = $property_request;
		$get_property_listing = $this->realestate_model->get_property_listing($property_request->item_id);
		$data['property'][] = (array)$get_property_listing;
		$data['rental_type'] = $get_property_listing->rental_type;
		$data['address_histores'] = $this->realestate_model->get_address_history(false, 'contact_id = '.$contact_id);
		$data['income_sources'] = $this->realestate_model->get_income_source(false, 'contact_id = '.$contact_id);
		$data['persons'] = $this->realestate_model->get_person(false, 'contact_id = '.$contact_id);
		$data['base_currency_id'] = get_base_currency_id();

		$this->load->view('companies/tenants/renter_profile', $data);
	}

	/**
	 * delete property activity
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_property_activity($id)
	{
		if (is_admin()) {
			$this->db->where('id', $id);
			$this->db->delete(db_prefix() . 'real_activity');
		}
	}

	/**
	 * assign property to broker
	 * @return [type] 
	 */
	public function assign_property_to_broker()
	{
		if ($this->input->is_ajax_request()) {
			$data = $this->input->get();
			$broker_type = $data['broker_type'];
			$property_id = $data['property_id'];

			$staff_options = '';
			$agent_options = '';
			$business_broker_options = '';
			$status = false;

			$staff_in_company = rel_check_staff_in_company();
			if($broker_type == 'staffs'){
				$this->load->model('staff_model');
				// get staff
				if(is_admin() || !$staff_in_company){
					// is admin: assign to staff admin
					$company_staffs = $this->staff_model->get(false, ['company_id' => 0]);
					
				}elseif($staff_in_company){
					// staff in company
					$company_staffs = $this->staff_model->get(false, ['company_id' => $staff_in_company]);
				}

				foreach ($company_staffs as $staff) {
					$staff_options .= '<option value="' . $staff['staffid'] . '">' . $staff['firstname'].' ' .$staff['lastname']  . '</option>';
				}
				$status = true;

			}elseif($broker_type == 'agents'){
				// get agents
				$agents = $this->realestate_model->get_construction_company(false, 'related_type = "company"');

				foreach ($agents as $agent) {
					$agent_options .= '<option value="' . $agent['id'] . '">' . $agent['name']  . '</option>';
				}
				$status = true;

			}elseif($broker_type == 'business_brokers'){
				// get business_brokers
				$business_brokers = $this->realestate_model->get_construction_company(false, 'related_type = "business_broker"');

				foreach ($business_brokers as $business_broker) {
					$business_broker_options .= '<option value="' . $business_broker['id'] . '">' . $business_broker['name']  . '</option>';
				}
				$status = true;
			}


			echo json_encode([
				'status' => $status,
				'staff_options' => $staff_options,
				'agent_options' => $agent_options,
				'business_broker_options' => $business_broker_options,
			]);
			die;
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
	 * staff email exists
	 * @return [type] 
	 */
	public function staff_email_exists()
	{
		if ($this->input->is_ajax_request()) {
			if ($this->input->post()) {

                // First we need to check if the email is the same
				$member_id = $this->input->post('memberid');
				$related_type = $this->input->post('related_type');

				if($related_type == 'company'){
					if ($member_id != '') {
						$this->db->where('staffid', $member_id);
						$_current_email = $this->db->get(db_prefix() . 'staff')->row();
						if ($_current_email->email == $this->input->post('email')) {
							echo json_encode(true);
							die();
						}
					}
					$this->db->where('email', $this->input->post('email'));
					$total_rows = $this->db->count_all_results(db_prefix() . 'staff');
					if ($total_rows > 0) {
						echo json_encode(false);
					} else {
						echo json_encode(true);
					}
					die();
				}else{
					// related_type: bussiness broker
					if ($member_id != '') {
						$this->db->where('id', $member_id);
						$_current_email = $this->db->get(db_prefix() . 'real_broker_staffs')->row();
						if ($_current_email->email == $this->input->post('email')) {
							echo json_encode(true);
							die();
						}
					}
					$this->db->where('email', $this->input->post('email'));
					$total_rows = $this->db->count_all_results(db_prefix() . 'real_broker_staffs');
					if ($total_rows > 0) {
						echo json_encode(false);
					} else {
						echo json_encode(true);
					}
					die();
				}

			}
		}
	}

	/**
	 * dashboard
	 * @return [type] 
	 */
	public function dashboard()
	{
		if (!has_permission('real_dashboard', '', 'view')  && !is_admin()) {
			access_denied('dashboard');
		}

		$data['title'] = _l('reale_dashboard');
		$data['base_currency_id'] = get_base_currency_id();
		$check_staff_type = rel_check_staff_type();
		$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];

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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];

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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];

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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];

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
	 * real permission table
	 * @return [type] 
	 */
	public function real_permission_table() {
		if ($this->input->is_ajax_request()) {

			$select = [
				'staffid',
				'CONCAT(firstname," ",lastname) as full_name',
				'firstname', //for role name
				'email',
				'phonenumber',
			];
			$where = [];
			$where[] = 'AND ' . db_prefix() . 'staff.admin != 1';

			$arr_staff_id = realestate_get_staff_id_permissions();

			if (count($arr_staff_id) > 0) {
				$where[] = 'AND ' . db_prefix() . 'staff.staffid IN (' . implode(', ', $arr_staff_id) . ')';
			} else {
				$where[] = 'AND ' . db_prefix() . 'staff.staffid IN ("")';
			}

			$staff_in_company = rel_check_staff_in_company();
			$get_staff_user_id = get_staff_user_id();
			if(is_admin()){
			// is admin: view all
			}elseif($staff_in_company){
			// staff in company
				if(has_permission('real_permission', '', 'view')){
					$where[] = 'AND '.db_prefix().'staff.company_id = '.$staff_in_company;
				}else{
					$where[] = 'AND 1=2';
				}

			}else{
			// staff not in construction company
				if(has_permission('real_permission', '', 'view')){
				// get all
					$where[] = 'AND '.db_prefix().'staff.company_id = 0';
				}else{
					$where[] = 'AND 1=2';
				}
			}

			$aColumns = $select;
			$sIndexColumn = 'staffid';
			$sTable = db_prefix() . 'staff';
			$join = ['LEFT JOIN ' . db_prefix() . 'roles ON ' . db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role'];

			$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'roles.name as role_name', db_prefix() . 'staff.role']);

			$output = $result['output'];
			$rResult = $result['rResult'];

			$not_hide = '';

			foreach ($rResult as $aRow) {
				$row = [];

				$row[] = '<a href="' . admin_url('staff/member/' . $aRow['staffid']) . '">' . $aRow['full_name'] . '</a>';

				$row[] = $aRow['role_name'];
				$row[] = $aRow['email'];
				$row[] = $aRow['phonenumber'];

				$options = '';

				if (has_permission('real_permission', '', 'edit')) {
					$options = icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
						'title' => _l('hr_edit'),
						'onclick' => 'realestate_permissions_update(' . $aRow['staffid'] . ', ' . $aRow['role'] . ', ' . $not_hide . '); return false;',
					]);
				}

				if (has_permission('real_permission', '', 'delete')) {
					$options .= icon_btn('realestate/delete_realestate_permission/' . $aRow['staffid'], 'fa fa-remove', 'btn-danger _delete', ['title' => _l('delete')]);
				}

				$row[] = $options;

				$output['aaData'][] = $row;
			}

			echo json_encode($output);
			die();
		}
	}

	/**
	 * permission modal
	 * @return [type] 
	 */
	public function permission_modal() {
		if (!$this->input->is_ajax_request()) {
			show_404();
		}
		$this->load->model('staff_model');

		if ($this->input->post('slug') === 'update') {
			$staff_id = $this->input->post('staff_id');
			$role_id = $this->input->post('role_id');

			$data = ['funcData' => ['staff_id' => isset($staff_id) ? $staff_id : null]];

			if (isset($staff_id)) {
				$data['member'] = $this->staff_model->get($staff_id);
			}

			$data['staffs'] = realestate_get_staff_id_dont_permissions();
			$add_new = $this->input->post('add_new');

			if ($add_new == ' hide') {
				$data['add_new'] = ' hide';
				$data['display_staff'] = '';
			} else {
				$data['add_new'] = '';
				$data['display_staff'] = ' hide';
			}
			// get plan by company
			$company_role_id = '';
			$is_company = false;
			$staff_in_company = rel_check_staff_in_company();
			$get_staff_user_id = get_staff_user_id();
			$list_realestate_permisstion = list_realestate_permisstion();

			if(is_admin()){
			// is admin: view all
			}elseif($staff_in_company){
			// staff in company
				$is_company = true;
				$construction_company = $this->realestate_model->get_construction_company($staff_in_company);
				if($construction_company){
					$get_plan = $this->realestate_model->get_plan($construction_company->plan_id);
					if($get_plan){
						$company_role_id = $get_plan->role_id;
					}
				}
			}

			$data['company_role_id'] = $company_role_id;
			$data['is_company'] = $is_company;
			if(is_numeric($company_role_id)){
				$list_realestate_permisstion = [];

				$data['roles_value'] = $this->realestate_model->get_role('', '(roleid = '.$company_role_id.')');
				$get_role = $this->roles_model->get($company_role_id);
				if($get_role && isset($get_role->permissions)){
					foreach ($get_role->permissions as $key => $value) {
						$list_realestate_permisstion[] = $key;
					}
				}
			}else{
				$data['roles_value'] = $this->roles_model->get();
			}

			$data['list_realestate_permisstion'] = $list_realestate_permisstion;

			$this->load->view('settings/permissions/permission_modal', $data);
		}
	}

	/**
	 * realestate update permissions
	 * @param  string $id 
	 * @return [type]     
	 */
	public function realestate_update_permissions($id = '') {
		if (!is_admin() && !has_permission('real_permission', '', 'create') && !has_permission('real_permission', '', 'edit')) {
			access_denied('realestate');
		}
		$data = $this->input->post();

		if (!isset($id) || $id == '') {
			$id = $data['staff_id'];
		}

		if (isset($id) && $id != '') {

			$data = hooks()->apply_filters('before_update_staff_member', $data, $id);

			if (is_admin()) {
				if (isset($data['administrator'])) {
					$data['admin'] = 1;
					unset($data['administrator']);
				} else {
					if ($id != get_staff_user_id()) {
						if ($id == 1) {
							return [
								'cant_remove_main_admin' => true,
							];
						}
					} else {
						return [
							'cant_remove_yourself_from_admin' => true,
						];
					}
					$data['admin'] = 0;
				}
			}

			$this->db->where('staffid', $id);
			$this->db->update(db_prefix() . 'staff', [
				'role' => $data['role'],
			]);

			$response = $this->staff_model->update_permissions((isset($data['admin']) && $data['admin'] == 1 ? [] : $data['permissions']), $id);
		} else {
			$this->load->model('roles_model');

			$role_id = $data['role'];
			unset($data['role']);
			unset($data['staff_id']);

			$data['update_staff_permissions'] = true;

			$response = $this->roles_model->update($data, $role_id);
		}

		if (is_array($response)) {
			if (isset($response['cant_remove_main_admin'])) {
				set_alert('warning', _l('staff_cant_remove_main_admin'));
			} elseif (isset($response['cant_remove_yourself_from_admin'])) {
				set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
			}
		} elseif ($response == true) {
			set_alert('success', _l('updated_successfully', _l('staff_member')));
		}
		redirect(admin_url('realestate/settings?tab=permissions'));

	}

	/**
	 * delete realestate permission
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_realestate_permission($id) {
		if (!is_admin()) {
			access_denied('hr_profile');
		}

		$response = $this->realestate_model->delete_realestate_permission($id);

		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('hr_is_referenced', _l('department_lowercase')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted', _l('hr_department')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('department_lowercase')));
		}
		redirect(admin_url('realestate/settings?tab=permissions'));

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
	 * reports
	 * @return [type] 
	 */
	public function reports()
    {
        if (!has_permission('real_report', '', 'view')) {
			access_denied('real_reports');
		}

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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];
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
			$check_staff_type = rel_check_staff_type();
			$company_id = $check_staff_type['company_id'];

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
	

	/*end file*/
}