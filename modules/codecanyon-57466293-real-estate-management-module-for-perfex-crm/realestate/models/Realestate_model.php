<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Realestate model
 */
class Realestate_model extends App_Model
{
	public $broker_notifications_limit, $tables_pagination_limit;

	/**
	 * construct
	 */
	public function __construct()
	{
		parent::__construct();
		$this->broker_notifications_limit = 15;
		$this->tables_pagination_limit = 25;

	}

	/**
	 * get construction company
	 * @param  string $id    
	 * @param  array  $where 
	 * @return [type]        
	 */
	public function get_construction_company($id = '', $where = [])
	{

		if (is_numeric($id)) {

			$this->db->where(db_prefix().'real_companies.id', $id);
			$company = $this->db->get(db_prefix() . 'real_companies')->row();
			$this->load->model('realestate/broker_model');
			$this->load->model('staff_model');
			$broker_staffs = $this->broker_model->get_broker_staff(false, ['company_id' => $id]);
			$company_staffs = $this->staff_model->get(false, ['company_id' => $id]);
			$public_company_staffs = $this->staff_model->get(false, ['company_id' => $id,'mark_public' => 1]);
			if($broker_staffs){
				$company->broker_staffs = $broker_staffs;
			}
			if($company_staffs){
				$company->company_staffs = $company_staffs;
			}

			if($public_company_staffs){
				$company->public_company_staffs = $public_company_staffs;
			}
			
			return $company;
		}

		$staff_in_company = rel_check_staff_in_company();

		if(is_admin()){
			// is admin: view all
		}elseif($staff_in_company){
			if(has_permission('real_business_broker', '', 'view') || has_permission('real_estate_agent', '', 'view')){
				$this->db->where(db_prefix().'real_companies.company_id', $staff_in_company);

			}elseif(has_permission('real_business_broker', '', 'view_own') || has_permission('real_estate_agent', '', 'view_own')){
				$this->db->where(db_prefix().'real_companies.staff_id', get_staff_user_id());
			}else{
				$this->db->where('1=2');
			}
		}else{
			// staff not in construction company
			if(has_permission('real_business_broker', '', 'view') || has_permission('real_estate_agent', '', 'view')){
				// get all
				$this->db->where(db_prefix().'real_companies.is_company_admin', 1);
			}elseif(has_permission('real_business_broker', '', 'view_own') || has_permission('real_estate_agent', '', 'view_own')){
				$this->db->where(db_prefix().'real_companies.staff_id', get_staff_user_id());
			}else{
				$this->db->where('1=2');
			}
		}

		if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
			$this->db->where($where);
		}
		$this->db->where('active', 1);
		$this->db->order_by('name', 'asc');
		return $this->db->get(db_prefix() . 'real_companies')->result_array();
	}

	/**
	 * add construction company
	 * @param [type] $data 
	 */
	public function add_construction_company($data) {
		$construction_company_data = [];

		$construction_company_data['name'] = isset($data['name']) ? $data['name'] : '';
		$construction_company_data['active'] = 1;
		$construction_company_data['email'] = isset($data['company_email']) ? $data['company_email'] : '';
		$construction_company_data['phonenumber'] = isset($data['company_phonenumber']) ? $data['company_phonenumber'] : '';
		$construction_company_data['country'] = isset($data['country']) ? $data['country'] : '';
		$construction_company_data['city'] = isset($data['city']) ? $data['city'] : '';
		$construction_company_data['zip'] = isset($data['zip']) ? $data['zip'] : '';
		$construction_company_data['state'] = isset($data['state']) ? $data['state'] : '';
		$construction_company_data['address'] = isset($data['address']) ? $data[
			'address'] : '';
			$construction_company_data['website'] = isset($data['website']) ? $data['website'] : '';
			$construction_company_data['vat'] = isset($data['vat']) ? $data['vat'] : '';
			$construction_company_data['billing_street'] = isset($data['billing_street']) ? $data['billing_street'] : '';
			$construction_company_data['billing_city'] = isset($data['billing_city']) ? $data['billing_city'] : '';
			$construction_company_data['billing_state'] = isset($data['billing_state']) ? $data['billing_state'] : '';
			$construction_company_data['billing_zip'] = isset($data['billing_zip']) ? $data['billing_zip'] : '';
			$construction_company_data['billing_country'] = isset($data['billing_country']) ? $data['billing_country'] : '';
			$construction_company_data['shipping_street'] = isset($data['shipping_street']) ? $data['shipping_street'] : '';
			$construction_company_data['shipping_city'] = isset($data['shipping_city']) ? $data['shipping_city'] : '';
			$construction_company_data['shipping_state'] = isset($data['shipping_state']) ? $data['shipping_state'] : '';
			$construction_company_data['shipping_zip'] = isset($data['shipping_zip']) ? $data['shipping_zip'] : '';
			$construction_company_data['shipping_country'] = isset($data['shipping_country']) ? $data['shipping_country'] : '';
			$construction_company_data['created_date'] = date('Y-m-d H:i:s');
			$construction_company_data['staff_id'] = get_staff_user_id();
			$construction_company_data['related_type'] = isset($data['related_type']) ? $data['related_type'] : '';
			$construction_company_data['facebook_url'] = isset($data['facebook_url']) ? $data['facebook_url'] : '';
			$construction_company_data['instagram_url'] = isset($data['instagram_url']) ? $data['instagram_url'] : '';
			$construction_company_data['whatsapp_url'] = isset($data['whatsapp_url']) ? $data['whatsapp_url'] : '';
			$construction_company_data['plan_id'] = isset($data['plan_id']) ? $data['plan_id'] : '';
			$construction_company_data['announcement_message'] = isset($data['announcement_message']) ? $data['announcement_message'] : '';
			$construction_company_data['privacy'] = isset($data['privacy']) ? $data['privacy'] : 'private';
			$construction_company_data['verification_status'] = isset($data['verification_status']) ? $data['verification_status'] : 'regular';
			$construction_company_data['about_information'] = isset($data['about_information']) ? $data['about_information'] : '';
			$construction_company_data['hash']           = app_generate_hash();
			$construction_company_data['is_company_admin']           = isset($data['is_company_admin']) ? $data['is_company_admin'] : 0;
			$construction_company_data['company_id']           = isset($data['company_id']) ? $data['company_id'] : 0;
			$construction_company_data['broker_id']           = isset($data['broker_id']) ? $data['broker_id'] : 0;
			$construction_company_data['related_id']           = isset($data['related_id']) ? $data['related_id'] : 0;


			if(isset($data['related_type'])){
				if($data['related_type'] == 'business_broker'){
					// business_broker
					$construction_company_data['code'] = $this->create_code('business_broker');
					update_option('real_business_broker_number', get_option('real_business_broker_number')+1);
				}else{
					// company
					$construction_company_data['code'] = $this->create_code('company');
					update_option('real_company_number', get_option('real_company_number')+1);

				}
			}else{
			// 'staff'
				$construction_company_data['code'] = $this->create_code('company');
				update_option('real_company_number', get_option('real_company_number')+1);

			}


			if (isset($data['name'])){
				unset($data['name']);
			}
			if (isset($data['company_email'])){
				unset($data['company_email']);
			}
			if (isset($data['company_phonenumber'])){
				unset($data['company_phonenumber']);
			}
			if (isset($data['country'])){
				unset($data['country']);
			}
			if (isset($data['city'])){
				unset($data['city']);
			}
			if (isset($data['zip'])){
				unset($data['zip']);
			}
			if (isset($data['state'])){
				unset($data['state']);
			}
			if (isset($data['address'])){
				unset($data['address']);
			}
			if (isset($data['website'])){
				unset($data['website']);
			}
			if (isset($data['vat'])){
				unset($data['vat']);
			}
			if (isset($data['billing_street'])){
				unset($data['billing_street']);
			}
			if (isset($data['billing_city'])){
				unset($data['billing_city']);
			}
			if (isset($data['billing_state'])){
				unset($data['billing_state']);
			}
			if (isset($data['billing_zip'])){
				unset($data['billing_zip']);
			}
			if (isset($data['billing_country'])){
				unset($data['billing_country']);
			}
			if (isset($data['shipping_street'])){
				unset($data['shipping_street']);
			}
			if (isset($data['shipping_city'])){
				unset($data['shipping_city']);
			}
			if (isset($data['shipping_state'])){
				unset($data['shipping_state']);
			}
			if (isset($data['shipping_zip'])){
				unset($data['shipping_zip']);
			}
			if (isset($data['shipping_country'])){
				unset($data['shipping_country']);
			}
			if (isset($data['related_type'])){
				$related_type = $data['related_type'];
				unset($data['related_type']);
			}

			if(isset($data['facebook_url'])){
				unset($data['facebook_url']);
			}
			if(isset($data['instagram_url'])){
				unset($data['instagram_url']);
			}
			if(isset($data['whatsapp_url'])){
				unset($data['whatsapp_url']);
			}
			if(isset($data['plan_id'])){
				unset($data['plan_id']);
			}
			if(isset($data['announcement_message'])){
				unset($data['announcement_message']);
			}
			if(isset($data['privacy'])){
				unset($data['privacy']);
			}
			if(isset($data['verification_status'])){
				unset($data['verification_status']);
			}
			if(isset($data['about_information'])){
				unset($data['about_information']);
			}
			if(isset($data['is_company_admin'])){
				unset($data['is_company_admin']);
			}
			if(isset($data['company_id'])){
				unset($data['company_id']);
			}
			if(isset($data['broker_id'])){
				unset($data['broker_id']);
			}
			if(isset($data['related_id'])){
				unset($data['related_id']);
			}
			

			$is_approval_manager = 1;
			if (!isset($data['is_approval_manager'])) {
				$is_approval_manager = 0;
			} else {
				unset($data['is_approval_manager']);
			}

			$this->db->insert(db_prefix() . 'real_companies', $construction_company_data);
			$insert_id = $this->db->insert_id();

			if ($insert_id) {

				if(isset($related_type)){
					if($related_type == 'business_broker'){
					// business_broker
						$data['staff_type'] = 'business_broker';
						$data['company_id'] = $insert_id;

					}else{
					// construction
						$data['staff_type'] = 'company';
						$data['company_id'] = $insert_id;
					}
				}else{
					$data['staff_type'] = 'staff';
				}

				if($data['staff_type'] == 'company' || $data['staff_type'] == 'staff'){
					if(isset($data['code'])){
						unset($data['code']);
					}
					$this->load->model('staff_model');
					$data['staff_identifi'] = $this->create_code('staff');
					$staff_id = $this->staff_model->add($data);
					update_option('staff_code_number', (int)get_option('staff_code_number')+1);

				}else{
					if(isset($data['code'])){
						unset($data['code']);
					}
					if(isset($data['staff_type'])){
						unset($data['staff_type']);
					}
				// new staff for business_broker
					$this->load->model('broker_model');
					$data['code'] = $this->create_code('broker_staff');
					$staff_id = $this->broker_model->add($data);
					update_option('real_broker_staff_number', (int)get_option('real_broker_staff_number')+1);
				}

				$result_data = [];
				$result_data['id'] = $insert_id;
				$result_data['staff_id'] = $staff_id;
				return $result_data;
			}
			return false;
		}

	/**
	 * update company
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_construction_company($data, $id)
	{
		if (isset($data['password']) || $data['password'] == null) {
			unset($data['password']);
		}
		$affected_rows = 0;

		if(isset($data['company_email'])){
			$data['email'] = $data['company_email'];
			unset($data['company_email']);
		}
		if(isset($data['company_phonenumber'])){
			$data['phonenumber'] = $data['company_phonenumber'];
			unset($data['company_phonenumber']);
		}

		if(!is_admin()){
			if(isset($data['plan_id'])){
				unset($data['plan_id']);
			}
		}

		// check add hash
		$construction_company = $this->get_construction_company($id);
		if($construction_company){
			if(is_null($construction_company->hash)){
				$data['hash'] = app_generate_hash();
			}
		}

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_companies', $data);
		if ($this->db->affected_rows() > 0) {
			$affected_rows++;
		}

		if ($affected_rows > 0) {
			return true;
		}
		return false;
	}

	/**
	 * delete company
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_company($id)
	{
		$affected_rows = 0;

		//delete staff
		$this->db->where('company_id', $id);
		$this->db->delete(db_prefix() . 'staff');
		if ($this->db->affected_rows() > 0) {
			$affected_rows++;
		}

		//delete broker staff
		$this->db->where('company_id', $id);
		$this->db->delete(db_prefix() . 'real_broker_staffs');
		if ($this->db->affected_rows() > 0) {
			$affected_rows++;
		}

		//delete sub company
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_companies');
		if ($this->db->affected_rows() > 0) {
			$affected_rows++;
		}

		if ($affected_rows > 0) {
			return true;
		}
		return false;
	}


	/**
	 * change construction company status
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_construction_company_status($id, $status)
	{
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_companies', [
			'active' => $status,
		]);
		if($this->db->affected_rows() > 0){
			return true;
		}
		return false;
	}

	/**
	 * add owner
	 * @param [type] $data 
	 */
	public function add_owner($data) {
		$owner_data = [];

		$owner_data['name'] = $data['name'];
		$owner_data['active'] = 1;
		$owner_data['email'] = $data['company_email'];
		$owner_data['phonenumber'] = $data['company_phonenumber'];
		$owner_data['country'] = $data['country'];
		$owner_data['city'] = $data['city'];
		$owner_data['zip'] = $data['zip'];
		$owner_data['state'] = $data['state'];
		$owner_data['address'] = $data['address'];
		$owner_data['website'] = $data['website'];
		$owner_data['vat'] = $data['vat'];
		$owner_data['billing_street'] = $data['billing_street'];
		$owner_data['billing_city'] = $data['billing_city'];
		$owner_data['billing_state'] = $data['billing_state'];
		$owner_data['billing_zip'] = $data['billing_zip'];
		$owner_data['billing_country'] = $data['billing_country'];
		$owner_data['shipping_street'] = $data['shipping_street'];
		$owner_data['shipping_city'] = $data['shipping_city'];
		$owner_data['shipping_state'] = $data['shipping_state'];
		$owner_data['shipping_zip'] = $data['shipping_zip'];
		$owner_data['shipping_country'] = $data['shipping_country'];
		$owner_data['facebook_url'] = $data['facebook_url'];
		$owner_data['instagram_url'] = $data['instagram_url'];
		$owner_data['whatsapp_url'] = $data['whatsapp_url'];
		$owner_data['is_company_admin'] = $data['is_company_admin'];
		$owner_data['company_id'] = $data['company_id'];
		$owner_data['broker_id'] = $data['broker_id'];
		$owner_data['related_type'] = $data['related_type'];
		$owner_data['related_id'] = $data['related_id'];
		$owner_data['created_date'] = date('Y-m-d H:i:s');
		$owner_data['hash']           = app_generate_hash();

		if (isset($data['name'])){
			unset($data['name']);
		}
		if (isset($data['company_email'])){
			unset($data['company_email']);
		}
		if (isset($data['company_phonenumber'])){
			unset($data['company_phonenumber']);
		}
		if (isset($data['country'])){
			unset($data['country']);
		}
		if (isset($data['city'])){
			unset($data['city']);
		}
		if (isset($data['zip'])){
			unset($data['zip']);
		}
		if (isset($data['state'])){
			unset($data['state']);
		}
		if (isset($data['address'])){
			unset($data['address']);
		}
		if (isset($data['website'])){
			unset($data['website']);
		}
		if (isset($data['vat'])){
			unset($data['vat']);
		}
		if (isset($data['billing_street'])){
			unset($data['billing_street']);
		}
		if (isset($data['billing_city'])){
			unset($data['billing_city']);
		}
		if (isset($data['billing_state'])){
			unset($data['billing_state']);
		}
		if (isset($data['billing_zip'])){
			unset($data['billing_zip']);
		}
		if (isset($data['billing_country'])){
			unset($data['billing_country']);
		}
		if (isset($data['shipping_street'])){
			unset($data['shipping_street']);
		}
		if (isset($data['shipping_city'])){
			unset($data['shipping_city']);
		}
		if (isset($data['shipping_state'])){
			unset($data['shipping_state']);
		}
		if (isset($data['shipping_zip'])){
			unset($data['shipping_zip']);
		}
		if (isset($data['shipping_country'])){
			unset($data['shipping_country']);
		}
		
		if(isset($data['facebook_url'])){
			unset($data['facebook_url']);
		}
		if(isset($data['instagram_url'])){
			unset($data['instagram_url']);
		}
		if(isset($data['whatsapp_url'])){
			unset($data['whatsapp_url']);
		}

		$owner_data['code'] = $this->create_code('owner');

		$this->db->insert(db_prefix() . 'real_property_owners', $owner_data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			update_option('real_property_owner_number', (int)get_option('real_property_owner_number')+1);
			return $insert_id;
		}
		return false;
	}

	/**
	 * update owner
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_owner($data, $id)
	{
		$affected_rows = 0;

		$owner_data = [];

		$owner_data['name'] = $data['name'];
		$owner_data['email'] = $data['company_email'];
		$owner_data['phonenumber'] = $data['company_phonenumber'];
		$owner_data['country'] = $data['country'];
		$owner_data['city'] = $data['city'];
		$owner_data['zip'] = $data['zip'];
		$owner_data['state'] = $data['state'];
		$owner_data['address'] = $data['address'];
		$owner_data['website'] = $data['website'];
		$owner_data['vat'] = $data['vat'];
		$owner_data['billing_street'] = $data['billing_street'];
		$owner_data['billing_city'] = $data['billing_city'];
		$owner_data['billing_state'] = $data['billing_state'];
		$owner_data['billing_zip'] = $data['billing_zip'];
		$owner_data['billing_country'] = $data['billing_country'];
		$owner_data['shipping_street'] = $data['shipping_street'];
		$owner_data['shipping_city'] = $data['shipping_city'];
		$owner_data['shipping_state'] = $data['shipping_state'];
		$owner_data['shipping_zip'] = $data['shipping_zip'];
		$owner_data['shipping_country'] = $data['shipping_country'];
		$owner_data['facebook_url'] = $data['facebook_url'];
		$owner_data['instagram_url'] = $data['instagram_url'];
		$owner_data['whatsapp_url'] = $data['whatsapp_url'];

		$owner = $this->get_owner($id);
		if($owner){
			if(is_null($owner->hash)){
				$owner_data['hash'] = app_generate_hash();
			}
		}

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_property_owners', $owner_data);
		if ($this->db->affected_rows() > 0) {
			$affected_rows++;
		}

		if ($affected_rows > 0) {
			return true;
		}
		return false;
	}

	/**
	 * delete owner
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_owner($id)
	{
		$affected_rows = 0;

		//delete owner
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_property_owners');
		if ($this->db->affected_rows() > 0) {
			$affected_rows++;
		}

		if ($affected_rows > 0) {
			return true;
		}
		return false;
	}

	/**
	 * get agent
	 * @param  string $id    
	 * @param  array  $where 
	 * @return [type]        
	 */
	public function get_owner($id = '', $where = [])
	{
		
		if (is_numeric($id)) {
			$this->db->where(db_prefix().'real_property_owners.id', $id);
			$owner = $this->db->get(db_prefix() . 'real_property_owners')->row();
			return $owner;
		}

		if(is_broker_logged_in()){
			$business_broker_id = get_business_broker_id();
			$this->db->where(db_prefix().'real_property_owners.broker_id', $business_broker_id);
		}else{

			$staff_in_company = rel_check_staff_in_company();
			$get_staff_user_id = get_staff_user_id();
			if(is_admin()){
			// is admin: view all
			}elseif($staff_in_company){
			// staff in company
				if(has_permission('real_property_owner', '', 'view_own')){
					$this->db->where(db_prefix().'real_property_owners.related_type = "staff"');
					$this->db->where(db_prefix().'real_property_owners.related_id', $get_staff_user_id);
				}elseif(has_permission('real_property_owner', '', 'view')){
					$this->db->where(db_prefix().'real_property_owners.company_id', $staff_in_company);
				}else{
					$this->db->where('1=2');
				}

			}else{
			// staff not in construction company
				if(has_permission('real_property_owner', '', 'view')){
				// get all
				}elseif(has_permission('real_property_owner', '', 'view_own')){
					$this->db->where(db_prefix().'real_property_owners.related_type = "staff"');
					$this->db->where(db_prefix().'real_property_owners.related_id', $get_staff_user_id);
				}else{
					$this->db->where('1=2');
				}
			}
		}
		if ((is_array($where) && count($where) > 0) || (is_string($where) && $where != '')) {
			$this->db->where($where);
		}

		$this->db->where('active', 1);
		$this->db->order_by('name', 'asc');
		return $this->db->get(db_prefix() . 'real_property_owners')->result_array();
	}
	
	/**
	 * change property owner status
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_property_owner_status($id, $status)
	{
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_property_owners', [
			'active' => $status,
		]);
		if($this->db->affected_rows() > 0){
			return true;
		}
		return false;
	}


	/**
	 * send agent employee welcome mail
	 * @param  [type] $data                 
	 * @param  [type] $password_before_hash 
	 * @return [type]                       
	 */
	public function send_agent_employee_welcome_mail($data, $password_before_hash)
	{
		$this->load->model('emails_model');
		$html = '';
		$html .= _l('rel_dear').' '.$data['firstname'] .' '.$data['lastname'].'. '._l('rel_welcome_employee').'. <br>'._l('rel_click_here_to_login') .': <a href="'.site_url('real_estate/authentication_candidate/login').'">link</a> <br> '._l('rel_email').': '.$data['email'].'<br>'._l('your_password').': '.$password_before_hash;

		$this->emails_model->send_simple_email($data['email'], _l('re_welcome'), $html);

		return true;
	}

	/**
	 * change staff public
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_staff_public($id, $status)
	{
		$this->db->where('staffid', $id);
		$this->db->update(db_prefix() . 'staff', [
			'mark_public' => $status,
		]);
		if($this->db->affected_rows() > 0){
			return true;
		}
		return false;
	}

	/**
	 * get role
	 * @param  string $id    
	 * @param  string $where 
	 * @return [type]        
	 */
	public function get_role($id = '', $where = '')
	{
		if (is_numeric($id)) {

			$role = $this->app_object_cache->get('role-' . $id);

			if ($role) {
				return $role;
			}

			$this->db->where('roleid', $id);

			$role              = $this->db->get(db_prefix() . 'roles')->row();
			$role->permissions = !empty($role->permissions) ? unserialize($role->permissions) : [];

			$this->app_object_cache->add('role-' . $id, $role);

			return $role;
		}

		if(new_strlen($where) > 0){
			$this->db->where($where);
		}
		return $this->db->get(db_prefix() . 'roles')->result_array();
	}

	/**
	 * get plan
	 * @param  boolean $id     
	 * @param  boolean $active 
	 * @return [type]          
	 */
	public function get_plan($id = false, $active = false, $where = '') {

		if (is_numeric($id)) {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_plans')->row();
		}
		if ($id == false) {
			if($active){
				$this->db->where('status', 'enabled');
			}
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			return $this->db->get(db_prefix() . 'real_plans')->result_array();
		}
	}

	/**
	 * add plan
	 * @param [type] $data 
	 */
	public function add_plan($data)
	{

		$data['date_created'] = date('Y-m-d H:i:s');
		$data['created_id'] = get_staff_user_id();

		if($data['read_only'] == 1){
			$data['monthly_listing_number'] = 0;
		}

		$this->db->insert(db_prefix().'real_plans',$data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			return $insert_id;
		}
		return false;
	}

	/**
	 * update plan
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_plan($data, $id)
	{
		$affected_rows=0;
		$data['date_updated'] = date('Y-m-d H:i:s');
		if($data['read_only'] == 1){
			$data['monthly_listing_number'] = 0;
		}

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_plans', $data);
		if ($this->db->affected_rows() > 0) {
			return true;
		}

		return false;  
	}

	/**
	 * delete plan
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_plan($id)
	{
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_plans');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}

	/**
	 * rel get attachments file
	 * @param  [type] $rel_id   
	 * @param  [type] $rel_type 
	 * @return [type]           
	 */
	public function rel_get_attachments_file($rel_id, $rel_type)
	{
		$this->db->order_by('dateadded', 'desc');
		$this->db->where('rel_id', $rel_id);
		$this->db->where('rel_type', $rel_type);

		return $this->db->get(db_prefix() . 'files')->result_array();
	}

	/**
	 * get property listing
	 * @return [type] 
	 */
	public function map_get_property_listing($where='', $has_attachment_filter = false, $latitude_filter = '', $longitude_filter = '', $radius_filter = 0, $visible = true, $hidden_pending = false, $rel_type = 'client')
	{
		$base_currency_id = get_base_currency_id();
		$marker_data = [];
		$arr_images = $this->item_attachments();
		$listing_pdfs = $this->listing_attachment();
		$primary_image = '';

		if(new_strlen($where) > 0){
			$this->db->where($where);
		}
		$this->db->where('can_be_property_listing', 'can_be_property_listing');
		$items = $this->db->get(db_prefix().'items')->result_array();
		$visible_temp = $visible;

		foreach ($items as $key => $value) {

			if($has_attachment_filter == 'true'){
				if(!(isset($listing_pdfs[$value['id']]))){
					continue;
				}
			}

			if(strlen($latitude_filter) > 0){
				if(!check_lat_long_within_circle($latitude_filter, $longitude_filter, $value['latitude'], $value['longitude'], $radius_filter)){
					continue;
				}
			}

			$temp_images = [];

			if (isset($arr_images[$value['id']])){
				foreach ($arr_images[$value['id']] as $key => $p_img) {
					if (file_exists(PROPERTY_UPLOAD . $p_img['rel_id'] . '/thumb_' . $p_img['file_name'])) {
						$temp_images[] = site_url('modules/realestate/uploads/property_listings/' . $p_img['rel_id'] . '/thumb_' . $p_img['file_name']);
					}elseif (file_exists(PROPERTY_UPLOAD . $p_img['rel_id'] . '/' . $p_img['file_name'])) {
						$temp_images[] = site_url('modules/realestate/uploads/property_listings/' . $p_img['rel_id'] . '/' . $p_img['file_name']);
					}
				}
			}

			$image = main_photo($value['id'], $value['primary_image']);
			$primary_image = $value['primary_image'];
			$sqFt_total = $value['lot_size_acres'];
			$property_style = $value['property_style'];

			if($hidden_pending){
				if($value['status'] == 'pending'){
					$visible = false;
				}else{
					$visible = $visible_temp;
				}
			}

			if($value['transaction_type'] == 'Sale' || $value['transaction_type'] == 'sold'){
				$listing_price = app_format_money($value['rate'], $base_currency_id);
			}else{
				$listing_price = app_format_money($value['rent_price'], $base_currency_id).' / '.$value['rental_value'].'('.$value['rental_type'].')';
			}

			if($rel_type == 'company'){
				$property_url = admin_url('realestate/property_listing_detail/'.$value['id']);
			}elseif($rel_type == 'broker'){
				$property_url = site_url('realestate/broker/property_listing_detail/'.$value['id']);
			}else{
				// client
				$property_url = site_url('realestate/client/property_listing_detail/'.$value['id']);
			}

			array_push($marker_data, [
				'id' => $value['id'],
				'lat' => $value['latitude'],
				'lng' => $value['longitude'],
				'name' => _l('real_mls').': <a href="'.admin_url('realestate/property_listing_detail/'.$value['id']).'" target="_blank">'.$value['description'].'</a>',
				'name_none_href' => _l('real_mls').': '.$value['description'],
				'address' => $value['street_number'].' '.$value['street_dir_pre'].' '.$value['street_name'].' '.$value['city'].' '.$value['state'].' '.get_country_name($value['country']),
				'listing_price' => $listing_price,
				'listing_status' => _l('real_'.$value['status']),
				'transaction_type' => $value['transaction_type'],
				'image' => $image,
				'images' => $temp_images,
				'visible' => $visible,
				'status' => $value['status'],
				'primary_image' => $primary_image,
				'sqFt_total' => merter_to_hectare($sqFt_total),
				'property_style' => $property_style,
				'property_url' => $property_url,
			]);
		}

		return $marker_data;
	}

	/**
	 * item attachments
	 * @return [type] 
	 */
	public function item_attachments($rel_id = false) {
		$arr_images = [];

		$this->db->order_by('dateadded', 'desc');
		if($rel_id){
			$this->db->where('rel_id', $rel_id);
		}
		$this->db->where('rel_type', 'commodity_item_file');
		$item_atts = $this->db->get(db_prefix() . 'files')->result_array();
		foreach ($item_atts as $key => $value) {
			$arr_images[$value['rel_id']][] = $value;
		}

		return $arr_images;
	}

	/**
	 * listing pdf
	 * @return [type] 
	 */
	public function listing_attachment() {
		$arr_pdf = [];

		$this->db->order_by('dateadded', 'desc');
		$this->db->where('rel_type', 'rel_listing_pdf');
		$item_atts = $this->db->get(db_prefix() . 'files')->result_array();
		foreach ($item_atts as $key => $value) {
			$arr_pdf[$value['rel_id']][] = $value;
		}

		return $arr_pdf;
	}

	/**
	 * create code
	 * @param  [type] $rel_type 
	 * @return [type]           
	 */
	public function create_code($rel_type) {
		$str_result ='';

		$prefix_str ='';
		switch ($rel_type) {
			case 'property':
			$prefix_str .= get_option('real_property_prefix');
			$next_number = get_option('real_property_number');
			$str_result .= $prefix_str.str_pad($next_number,6,'0',STR_PAD_LEFT);
			break;

			case 'company':
			$prefix_str .= get_option('real_company_prefix');
			$next_number = get_option('real_company_number');
			$str_result .= $prefix_str.str_pad($next_number,6,'0',STR_PAD_LEFT);
			break;

			case 'business_broker':
			$prefix_str .= get_option('real_business_broker_prefix');
			$next_number = get_option('real_business_broker_number');
			$str_result .= $prefix_str.str_pad($next_number,6,'0',STR_PAD_LEFT);
			break;

			case 'owner':
			$prefix_str .= get_option('real_property_owner_prefix');
			$next_number = get_option('real_property_owner_number');
			$str_result .= $prefix_str.str_pad($next_number,6,'0',STR_PAD_LEFT);
			break;

			case 'staff':
			$prefix_str .= get_option('staff_code_prefix');
			$next_number = get_option('staff_code_number');
			$str_result .= $prefix_str.str_pad($next_number,6,'0',STR_PAD_LEFT);
			break;
			case 'broker_staff':
			$prefix_str .= get_option('real_broker_staff_prefix');
			$next_number = get_option('real_broker_staff_number');
			$str_result .= $prefix_str.str_pad($next_number,6,'0',STR_PAD_LEFT);
			break;
			
			default:
				# code...
			break;
		}

		return $str_result;
	}

	/**
	 * update prefix setting
	 * @param  [type] $data 
	 * @return [type]       
	 */
	public function update_prefix_setting($data)
	{
		$affected_rows=0;
		foreach ($data as $key => $value) {

			$this->db->where('name',$key);
			$this->db->update(db_prefix() . 'options', [
				'value' => $value,
			]);

			if ($this->db->affected_rows() > 0) {
				$affected_rows++;
			}
		}

		if($affected_rows > 0){
			return true;
		}else{
			return false;
		}
	}

		/**
	 * generate unique code
	 * @param  [type] $rel_type 
	 * @return [type]           
	 */
		public function generate_unique_code($rel_type, $length)
		{
			$item = false;
			do {
				$length = $length;
				$chars = '0123456789';
				$count = new_strlen($chars);
				$unique_code = '';
				for ($i = 0; $i < $length; $i++) {
					$index = rand(0, $count - 1);
					$unique_code .= mb_substr($chars, $index, 1);
				}

				if($rel_type == 'company' || $rel_type == 'business_broker'){
					$this->db->where('code', $unique_code);
					$item = $this->db->get(db_prefix() . 'real_companies')->row();
				}elseif($rel_type == 'staff'){
					$this->db->where('staff_identifi', $unique_code);
					$item = $this->db->get(db_prefix() . 'staff')->row();
				}elseif($rel_type == 'listing'){
					$this->db->where('description', $unique_code);
					$item = $this->db->get(db_prefix() . 'items')->row();
				}
			} while ($item);

			return $unique_code;
		}

	/**
	 * get user notifications
	 * @param  boolean $read 
	 * @return [type]        
	 */
	public function get_user_notifications($read = false)
	{
		$read     = $read == false ? 0 : 1;
		$total    = $this->broker_notifications_limit;
		$broker_id = get_broker_id();

		$sql = 'SELECT COUNT(*) as total FROM ' . db_prefix() . 'real_notifications WHERE isread=' . $read . ' AND touserid=' . $broker_id;
		$sql .= ' UNION ALL ';
		$sql .= 'SELECT COUNT(*) as total FROM ' . db_prefix() . 'real_notifications WHERE isread_inline=' . $read . ' AND touserid=' . $broker_id;

		$res = $this->db->query($sql)->result();

		$total_unread        = $res[0]->total;
		$total_unread_inline = $res[1]->total;

		if ($total_unread > $total) {
			$total = ($total_unread - $total) + $total;
		} elseif ($total_unread_inline > $total) {
			$total = ($total_unread_inline - $total) + $total;
		}

		// In case user is not marking the notifications are read this process may be long because the script will always fetch the total from the not read notifications.
		// In this case we are limiting to 30
		$total = $total > 30 ? 30 : $total;

		$this->db->where('touserid', $broker_id);
		$this->db->limit($total);
		$this->db->order_by('date', 'desc');

		return $this->db->get(db_prefix() . 'real_notifications')->result_array();
	}

	/**
	 * Set notification read when user open notification dropdown
	 * @return boolean
	 */
	public function set_notifications_read()
	{
		$this->db->where('touserid', get_broker_id());
		$this->db->update(db_prefix() . 'real_notifications', [
			'isread' => 1,
		]);
		if ($this->db->affected_rows() > 0) {
			return true;
		}

		return false;
	}

	/**
	 * set notification read inline
	 * @param [type] $id 
	 */
	public function set_notification_read_inline($id)
	{
		$this->db->where('touserid', get_broker_id());
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_notifications', [
			'isread_inline' => 1,
		]);
	}

	/**
	 * set desktop notification read
	 * @param [type] $id 
	 */
	public function set_desktop_notification_read($id)
	{
		$this->db->where('touserid', get_broker_id());
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_notifications', [
			'isread'        => 1,
			'isread_inline' => 1,
		]);
	}

	/**
	 * mark all notifications as read inline
	 * @return [type] 
	 */
	public function mark_all_notifications_as_read_inline()
	{
		$this->db->where('touserid', get_broker_id());
		$this->db->update(db_prefix() . 'real_notifications', [
			'isread_inline' => 1,
			'isread'        => 1,
		]);
	}

	/**
	 * get candidate notifications limit
	 * @return [type] 
	 */
	public function get_broker_notifications_limit()
	{
		return hooks()->apply_filters('broker_notifications_limit', $this->broker_notifications_limit);
	}

	/**
	 * create property listing room row template
	 * @param  array   $room_type_data        
	 * @param  array   $rooms_level_data      
	 * @param  string  $name                  
	 * @param  string  $room_type             
	 * @param  string  $rooms_level           
	 * @param  string  $room_demension_width  
	 * @param  string  $room_demension_lenght 
	 * @param  string  $room_benefits         
	 * @param  string  $room_key              
	 * @param  boolean $is_edit               
	 * @return [type]                         
	 */
	public function create_property_listing_room_row_template($room_type_data = [], $rooms_level_data = [], $name = '', $room_type = '', $rooms_level = '', $room_demension_width = '', $room_demension_lenght = '',  $room_benefits = '', $room_key = '', $is_edit = false) {

		if(is_array($room_benefits)){
		}else{
			$room_benefits = isset($room_benefits) && new_strlen($room_benefits) > 0 ? new_explode(',', $room_benefits) : '';
		}

		$row = '';

		$name_room_type = 'room_type';
		$name_rooms_level = 'rooms_level';
		$name_room_demension_width = 'room_demension_width';
		$name_room_demension_lenght = 'room_demension_lenght';
		$name_room_benefits = 'room_benefits[]';

		$array_attr = [];
		$array_attr_payment = ['data-payment' => 'invoice'];
		$name_sub_total = 'sub_total';
		$name_serial_number = 'serial_number';

		$array_qty_attr = [ 'min' => '0.0', 'step' => 'any'];
		$array_rate_attr = [ 'min' => '0.0', 'step' => 'any'];
		$str_rate_attr = 'min="0.0" step="any"';

		if(count($room_type_data) == 0){
			$room_type_data = rel_room_type();
			$rooms_level_data = rel_room_levels();
			$room_benefits_data = rel_room_benefits();
		}

		if ($name == '') {
			$row .= '<tr class="main">
			<td></td>';
			$manual             = true;
		} else {
			$row .= '<tr class="sortable item">
			<td class="dragger"><input type="hidden" class="order" name="' . $name . '[order]"><input type="hidden" class="ids" name="' . $name . '[id]" value="' . $room_key . '"></td>';
			$name_room_type = $name . '[room_type]';
			$name_rooms_level = $name . '[rooms_level]';
			$name_room_demension_width = $name . '[room_demension_width]';
			$name_room_demension_lenght = $name . '[room_demension_lenght]';
			$name_room_benefits = $name . '[room_benefits][]';
		}

		$row .= '<td class="room_type_select">' .
		render_select($name_room_type, $room_type_data,array('name','label'),'',$room_type,[], ["data-none-selected-text" => _l('rel_room_type')], 'no-margin').
		'</td>';
		$row .= '<td class="rooms_level_select">' .
		render_select($name_rooms_level, $rooms_level_data,array('name','label'),'',$rooms_level,[], ["data-none-selected-text" => _l('rel_rooms_level')], 'no-margin').
		'</td>';
		
		$row .= '<td class="room_benefits_select">' .
		render_select($name_room_benefits, $room_benefits_data,array('name','label'),'',$room_benefits,['multiple' => true], ["data-none-selected-text" => _l('rel_room_benefits')], 'no-margin', '', false).
		'</td>';
		$row .= '<td class="room_demension_width">' . 
		render_input($name_room_demension_width, '', $room_demension_width, 'number', $array_qty_attr, [], 'no-margin') . 
		'</td>';
		$row .= '<td class="room_demension_lenght">' . 
		render_input($name_room_demension_lenght, '', $room_demension_lenght, 'number', $array_qty_attr, [], 'no-margin') . 
		'</td>';

		if ($name == '') {
			$row .= '<td><button type="button" onclick="rel_add_room_to_table(\'undefined\',\'undefined\'); return false;" class="btn pull-right btn-primary"><i class="fa fa-check"></i></button></td>';
		} else {
			$row .= '<td><a href="#" class="btn btn-danger pull-right" onclick="rel_delete_room(this,' . $room_key . ',\'.invoice-item\'); return false;" data-toggle="tooltip" data-original-title="'._l('delete').'"><i class="fa fa-trash"></i></a></td>';
		}
		$row .= '</tr>';
		return $row;
	}

	/**
	 * add property listing
	 * @param [type] $formdata 
	 */
	public function add_property_listing($formdata)
	{
		$data=[];

		$arr_custom_fields=[];

		$arr_room= [];
		$temp_room_id = '';
		$temp_room_type = '';
		$temp_rooms_level = '';
		$temp_room_demension_width = '';
		$temp_room_demension_lenght = '';
		$temp_room_benefits = '';
		$search_listing_id = [];

		$temp_payment_plan_id = '';
		$temp_payment_plan = '';
		$temp_amount = '';

		if(isset($formdata['search_listing_id']) && $formdata['search_listing_id'] != ''){
			$search_listing_id = explode(',', $formdata['search_listing_id']);
			unset($formdata['search_listing_id']);
		}

		foreach ($formdata['formdata'] as $key => $value) {
			if(preg_match('/^custom_fields/', $value['name'])){
				$index =  new_str_replace('custom_fields[items][', '', $value['name']);
				$index =  new_str_replace(']', '', $index);

				$arr_custom_fields[$index] = $value['value'];

			}elseif(preg_match('/^newitems/', $value['name'])){

				if(preg_match('/id]/', $value['name'])){
					$temp_room_id = $value['value'];
				}elseif(preg_match('/room_type]/', $value['name'])){
					$temp_room_type = $value['value'];
				}elseif(preg_match('/rooms_level]/', $value['name'])){
					$temp_rooms_level = $value['value'];
				}elseif(preg_match('/room_benefits]/', $value['name'])){
					if(strlen($temp_room_benefits) > 0){
						$temp_room_benefits .= ','.$value['value'];
					}else{
						$temp_room_benefits = $value['value'];
					}
				}elseif(preg_match('/room_demension_width]/', $value['name'])){
					$temp_room_demension_width = $value['value'];
				}elseif(preg_match('/room_demension_lenght]/', $value['name'])){
					$temp_room_demension_lenght = $value['value'];

					array_push($arr_room, [
						'id' => (int)$temp_room_id,
						'room_type' => $temp_room_type,
						'rooms_level' => $temp_rooms_level,
						'room_demension_width' => $temp_room_demension_width,
						'room_demension_lenght' => $temp_room_demension_lenght,
						'room_benefits' => $temp_room_benefits,
					]);

					$temp_room_id = '';
					$temp_room_type = '';
					$temp_rooms_level = '';
					$temp_room_demension_width = '';
					$temp_room_demension_lenght = '';
					$temp_room_benefits = '';
				}

			}elseif( $value['name'] != 'csrf_token_name' && $value['name'] != 'id'){

				if(isset($data[$value['name']])){
					$data[$value['name']] .= ','.$value['value'];
				}else{
					$data[$value['name']] = $value['value'];
				}
			}

		}

		if(isset($formdata['long_description'])){
			$data['long_description'] = $formdata['long_description'];
		}
		
		if(isset($data['room_type'])){
			unset($data['room_type']);
		}
		if(isset($data['rooms_level'])){
			unset($data['rooms_level']);
		}
		if(isset($data['room_demension_width'])){
			unset($data['room_demension_width']);
		}
		if(isset($data['room_demension_lenght'])){
			unset($data['room_demension_lenght']);
		}
		if(isset($data['room_primary_floor'])){
			unset($data['room_primary_floor']);
		}
		if(isset($data['room_benefits'])){
			unset($data['room_benefits']);
		}

		if(isset($data['newitems'])){
			$newitems = $data['newitems'];
			unset($data['newitems']);
		}
		if(isset($data['newpaymentitems'])){
			$newpaymentitems = $data['newpaymentitems'];
			unset($data['newpaymentitems']);
		}
		if(isset($data['payment_plan'])){
			unset($data['payment_plan']);
		}
		if(isset($data['amount'])){
			unset($data['amount']);
		}

		if(isset($data['proj_completion_date'])){
			$data['proj_completion_date'] = to_sql_date($data['proj_completion_date']);
		}else{
			$data['proj_completion_date'] = null;
		}


		if ($this->db->field_exists('can_be_sold' ,db_prefix() . 'items')) { 
			$data['can_be_sold'] = null;
		}
		if ($this->db->field_exists('can_be_purchased' ,db_prefix() . 'items')) { 
			$data['can_be_purchased'] = null;
		}
		if ($this->db->field_exists('can_be_manufacturing' ,db_prefix() . 'items')) { 
			$data['can_be_manufacturing'] = null;
		}
		if ($this->db->field_exists('can_be_inventory' ,db_prefix() . 'items')) { 
			$data['can_be_inventory'] = null;
		}

		$data['hash']           = app_generate_hash();
		$data['status']           = 'new';
		$data['date_approval']           = date('Y-m-d H:i:s');
		$data['date_update'] = date('Y-m-d');
		$data['can_be_property_listing'] = 'can_be_property_listing';
		$data['date_created'] = date('Y-m-d H:i:s');
		$data['commodity_code'] = $this->create_code('property');

		$this->db->insert(db_prefix() . 'items', $data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			update_option('real_property_number', get_option('real_property_number')+1);

			// Send notify
			// TODO
			if(isset($data['listing_privacy']) && $data['listing_privacy'] == 'visible_everywhere' && 1==2){
				$data_searches = $this->get_search_history('', 'notify = 1');
				$point=[];
				$point['x'] = $data['latitude'];
				$point['y'] = $data['longitude'];
				$this->load->model('emails_model');

				foreach ($data_searches as $data_search) {
					$polygon = json_decode($data_search['drawing_data']);
					$send_notify = false;
					if(isset($polygon[0]->data)){

						if(pointInPolygon($point, $polygon[0]->data)){
							$send_notify = true;

						}
					}elseif( new_strlen($data['latitude']) > 0 && new_strlen($data['longitude']) > 0 ) {

						if(check_lat_long_within_circle($data_search['latitude'], $data_search['longitude'], $data['latitude'], $data['longitude'], $data_search['radius'])){
							$send_notify = true;
						}

					}

					// send notifications
					if($send_notify){
						$link = 'real_estate/property_listings?search_id='.$data_search['id'];
						$link1 = 'real_estate/property_listing_detail/'.$insert_id;
						if(is_numeric($data_search['created_id'])){					
								// Notify
							$this->notifications($data_search['created_id'], $link1, _l('new_listings_have_been_added_to_the_search_area').': '.$data_search['name']);

								// Send email
							$get_staff_infor = get_staff_infor($data_search['created_id']);
							if(strlen($get_staff_infor->email) > 0 ){

								$html = '';
								$html .= _l('rel_dear').' '.$get_staff_infor->firstname .' '.$get_staff_infor->lastname.'. '._l('new_listings_have_been_added_to_the_search_area').'. <br>'._l('rel_click_here_to_view_listing') .': <a href="'.admin_url($link1).'">link</a> <br>';
								$this->emails_model->send_simple_email($get_staff_infor->email, _l('new_listings_have_been_added_to_the_search_area').': '.$data_search['name'], $html);
							}

						}
					}

				}

				$this->update_item_search_history($created_id, $search_listing_id, $insert_id);
			}
			//check status listing
			$this->get_listing_status_add_new($insert_id);

			if(isset($arr_room) && count($arr_room) > 0){
				foreach ($arr_room as $key => $value) {
					$arr_room[$key]['item_id'] = $insert_id;
				}
				$affected_rows = $this->db->insert_batch(db_prefix().'real_property_listing_rooms', $arr_room);
			}

			hooks()->do_action('after_realestate_property_listing_created', $insert_id);
			log_activity('New Property Listing Added [ID:' . $insert_id);

			return $insert_id;
		}
		return false;
	}

	/**
	 * update property listing
	 * @param  [type]  $formdata                
	 * @param  integer $admin_id                
	 * @param  integer $company_id 
	 * @param  integer $agent_id                
	 * @param  integer $created_id              
	 * @return [type]                           
	 */
	public function update_property_listing($formdata, $id, $admin_id = 0, $company_id = 0, $agent_id = 0, $created_id = 0)
	{	

		$affected_rows = 0;
		$base_currency_id = get_base_currency_id();

		$data=[];

		// reset data
		$data['sale_includes'] = '';
		$data['number_of_tenants'] = '';
		$data['fireplace_description'] = '';
		$data['appliances_included'] = '';
		$data['utilities'] = '';
		$data['sewer'] = '';
		$data['water'] = '';
		$data['heating_and_fuel'] = '';
		$data['air_conditioning'] = '';
		$data['electrical_Service'] = '';
		$data['security_features'] = '';
		$data['accessibility_features'] = '';
		$data['floor_covering'] = '';
		$data['ceiling_type'] = '';
		$data['window_Features'] = '';
		$data['realtor_information'] = '';
		$data['realtor_information_confidential'] = '';
		$data['disclosures'] = '';
		$data['possession'] = '';
		$data['school'] = '';
		$data['landmarks'] = '';

		$arr_custom_fields=[];

		$arr_room= [];
		$temp_room_id = '';
		$temp_room_type = '';
		$temp_rooms_level = '';
		$temp_room_demension_width = '';
		$temp_room_demension_lenght = '';
		$temp_room_benefits = '';

		$temp_payment_plan_id = '';
		$temp_payment_plan = '';
		$temp_amount = '';

		$arr_old_room= [];
		$temp_old_room_id = '';
		$temp_old_room_type = '';
		$temp_old_rooms_level = '';
		$temp_old_room_demension_width = '';
		$temp_old_room_demension_lenght = '';
		$temp_old_room_primary_floor = '';
		$temp_old_room_benefits = '';

		$arr_old_payment_plan= [];
		$temp_old_payment_plan_id = '';
		$temp_old_payment_plan = '';
		$temp_old_amount = '';

		$old_room_ids = [];
		$old_payment_plan_ids = [];
		$search_listing_id = [];
		if(isset($formdata['search_listing_id']) && $formdata['search_listing_id'] != ''){
			$search_listing_id = explode(',', $formdata['search_listing_id']);
			unset($formdata['search_listing_id']);
		}

		foreach ($formdata['formdata'] as $key => $value) {
			if(preg_match('/^custom_fields/', $value['name'])){
				$index =  new_str_replace('custom_fields[items][', '', $value['name']);
				$index =  new_str_replace(']', '', $index);

				$arr_custom_fields[$index] = $value['value'];

			}elseif(preg_match('/^newitems/', $value['name'])){

				if(preg_match('/id]/', $value['name'])){
					$temp_room_id = $value['value'];
				}elseif(preg_match('/room_type]/', $value['name'])){
					$temp_room_type = $value['value'];
				}elseif(preg_match('/rooms_level]/', $value['name'])){
					$temp_rooms_level = $value['value'];
				}elseif(preg_match('/room_benefits]/', $value['name'])){
					if(strlen($temp_room_benefits) > 0){
						$temp_room_benefits .= ','.$value['value'];
					}else{
						$temp_room_benefits = $value['value'];
					}
				}elseif(preg_match('/room_demension_width]/', $value['name'])){
					$temp_room_demension_width = $value['value'];
				}elseif(preg_match('/room_demension_lenght]/', $value['name'])){
					$temp_room_demension_lenght = $value['value'];

					array_push($arr_room, [
						'id' => (int)$temp_room_id,
						'room_type' => $temp_room_type,
						'rooms_level' => $temp_rooms_level,
						'room_demension_width' => $temp_room_demension_width,
						'room_demension_lenght' => $temp_room_demension_lenght,
						'room_benefits' => $temp_room_benefits,
					]);

					$temp_room_id = '';
					$temp_room_type = '';
					$temp_rooms_level = '';
					$temp_room_demension_width = '';
					$temp_room_demension_lenght = '';
					$temp_room_benefits = '';
				}

			}elseif(preg_match('/^items/', $value['name'])){
				if(preg_match('/id]/', $value['name'])){
					$temp_old_room_id = $value['value'];
					$old_room_ids[] = $value['value'];
				}elseif(preg_match('/room_type]/', $value['name'])){
					$temp_old_room_type = $value['value'];
				}elseif(preg_match('/rooms_level]/', $value['name'])){
					$temp_old_rooms_level = $value['value'];
				}elseif(preg_match('/room_demension_width]/', $value['name'])){
					$temp_old_room_demension_width = $value['value'];
				}elseif(preg_match('/room_benefits]/', $value['name'])){
					if(strlen($temp_old_room_benefits) > 0){
						$temp_old_room_benefits .= ','.$value['value'];
					}else{
						$temp_old_room_benefits = $value['value'];
					}
				}elseif(preg_match('/room_demension_lenght]/', $value['name'])){
					$temp_old_room_demension_lenght = $value['value'];

					array_push($arr_old_room, [
						'id' => (int)$temp_old_room_id,
						'room_type' => $temp_old_room_type,
						'rooms_level' => $temp_old_rooms_level,
						'room_demension_width' => $temp_old_room_demension_width,
						'room_demension_lenght' => $temp_old_room_demension_lenght,
						'room_benefits' => $temp_old_room_benefits,
					]);

					$temp_old_room_id = '';
					$temp_old_room_type = '';
					$temp_old_rooms_level = '';
					$temp_old_room_demension_width = '';
					$temp_old_room_demension_lenght = '';
					$temp_old_room_primary_floor = '';
					$temp_old_room_benefits = '';
				}

			}elseif( $value['name'] != 'csrf_token_name' && $value['name'] != 'id'){

				if(isset($data[$value['name']]) && strlen($data[$value['name']]) > 0){
					$data[$value['name']] .= ','.$value['value'];
				}else{
					$data[$value['name']] = $value['value'];
				}
			}

		}

		if(isset($formdata['long_description'])){
			$data['long_description'] = $formdata['long_description'];
		}

		if(isset($data['room_type'])){
			unset($data['room_type']);
		}
		if(isset($data['rooms_level'])){
			unset($data['rooms_level']);
		}
		if(isset($data['room_demension_width'])){
			unset($data['room_demension_width']);
		}
		if(isset($data['room_demension_lenght'])){
			unset($data['room_demension_lenght']);
		}
		
		if(isset($data['room_benefits'])){
			unset($data['room_benefits']);
		}

		if(isset($data['newitems'])){
			$newitems = $data['newitems'];
			unset($data['newitems']);
		}
		if(isset($data['items'])){
			$items = $data['items'];
			unset($data['items']);
		}
		if(isset($data['newpaymentitems'])){
			$newpaymentitems = $data['newpaymentitems'];
			unset($data['newpaymentitems']);
		}
		if(isset($data['payment_items'])){
			$payment_items = $data['payment_items'];
			unset($data['payment_items']);
		}

		if(isset($data['payment_plan'])){
			unset($data['payment_plan']);
		}
		if(isset($data['amount'])){
			unset($data['amount']);
		}
		if(isset($data['DataTables_Table_0_length'])){
			unset($data['DataTables_Table_0_length']);
		}

		if(isset($data['proj_completion_date'])){
			$data['proj_completion_date'] = to_sql_date($data['proj_completion_date']);
		}else{
			$data['proj_completion_date'] = null;
		}
		// Get old data
		$this->db->select('latitude, longitude, status, transaction_type, rate, rent_price');
		$this->db->where('id', $id);
		$old_data = $this->db->get(db_prefix() . 'items')->row();

		if($old_data->status == 'pending'){
			$data['status'] = 'pending';
		}

		if($old_data){
			if($old_data->status != $data['status']){
				$data['date_update'] = date('Y-m-d');
			}
		}

		// Update
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'items', $data);
		if($this->db->affected_rows() > 0) {
			if($old_data->transaction_type != $data['transaction_type']){
				$this->log_realestate_activity($id, 'property', 'property_activity_property_transaction_type_changed', serialize([
					$old_data->transaction_type,
					$data['transaction_type'],
				]));
			}

			if($data['transaction_type'] == 'Sale'){
				if((float)$old_data->rate != (float)$data['rate']){
					$this->log_realestate_activity($id, 'property', 'property_activity_property_price_changed', serialize([
						app_format_money($old_data->rate, $base_currency_id),
						app_format_money($data['rate'], $base_currency_id),
					]));
				}

			}
			if($data['transaction_type'] == 'Rent'){
				if((float)$old_data->rent_price != (float)$data['rent_price']){
					$this->log_realestate_activity($id, 'property', 'property_activity_property_price_changed', serialize([
						app_format_money($old_data->rent_price, $base_currency_id),
						app_format_money($data['rent_price'], $base_currency_id),
					]));
				}
			}

			if($old_data && (($old_data->status != $data['status'] && $data['status'] == 'sold') || ($old_data->latitude != $data['latitude']) || ($old_data->longitude != $data['longitude']) ) ){

				if($data['listing_privacy'] == 'visible_everywhere' && 1==2){
					$data_searches = $this->get_search_history('', 'notify = 1');
					$point=[];
					$point['x'] = $data['latitude'];
					$point['y'] = $data['longitude'];
					$this->load->model('emails_model');

					foreach ($data_searches as $data_search) {
						$polygon = json_decode($data_search['drawing_data']);
						$send_notify = false;
						if(isset($polygon[0]->data)){

							if(pointInPolygon($point, $polygon[0]->data)){
								$send_notify = true;

							}
						}elseif( new_strlen($data['latitude']) > 0 && new_strlen($data['longitude']) > 0 ) {

							if(check_lat_long_within_circle($data_search['latitude'], $data_search['longitude'], $data['latitude'], $data['longitude'], $data_search['radius'])){
								$send_notify = true;
							}

						}

						if($send_notify){
							$link = 'real_estate/property_listings?search_id='.$data_search['id'];
							$link1 = 'real_estate/property_listing_detail/'.$id;
							$link_label = '';
							if(is_numeric($data_search['created_id'])){					
								// Notify
								if($old_data->status != $data['status'] && $data['status'] == 'sold'){
									$this->notifications($data_search['created_id'], $link1, _l('new_listings_have_been_sold_with_in_that_search_area').': '.$data_search['name']);

									$link_label = _l('new_listings_have_been_sold_with_in_that_search_area');

								}else{
									$this->notifications($data_search['created_id'], $link1, _l('new_listings_have_been_added_to_the_search_area').': '.$data_search['name']);
									$link_label = _l('new_listings_have_been_added_to_the_search_area');
								}


									// send email
								$get_staff_infor = get_staff_infor($data_search['created_id']);
								if(strlen($get_staff_infor->email) > 0){

									$html = '';
									$html .= _l('rel_dear').' '.$get_staff_infor->firstname .' '.$get_staff_infor->lastname.'. '.$link_label.'. <br>'._l('rel_click_here_to_view_listing') .': <a href="'.admin_url($link1).'">link</a> <br>';
									$this->emails_model->send_simple_email($get_staff_infor->email, $link_label.': '.$data_search['name'], $html);
								}

							}
						}

					}

					$this->update_item_search_history($created_id, $search_listing_id, $id);
				}
			}

			$affected_rows++;
		}

		if ($id) {

			if(count($old_room_ids) > 0){
				$this->db->where('id NOT IN ('.implode(",", $old_room_ids).')');
			}
			$this->db->where('item_id', $id);
			$this->db->delete(db_prefix().'real_property_listing_rooms');
			if($this->db->affected_rows() > 0) {
				$affected_rows++;
			}

			if(isset($arr_room) && count($arr_room) > 0){
				foreach ($arr_room as $key => $value) {
					$arr_room[$key]['item_id'] = $id;
				}
				$affected_row = $this->db->insert_batch(db_prefix().'real_property_listing_rooms', $arr_room);
				if($affected_row > 0) {
					$affected_rows++;
				}
			}

			if(isset($arr_old_room) && count($arr_old_room) > 0){
				$affected_row = $this->db->update_batch(db_prefix().'real_property_listing_rooms', $arr_old_room, 'id');
				if($affected_row > 0) {
					$affected_rows++;
				}
			}
		}

		if($affected_rows > 0){
			return true;
		}
		return false;
	}

	/**
	 * get property listing
	 * @param  string $id    
	 * @param  array  $where 
	 * @return [type]        
	 */
	public function get_property_listing($id = '', $where = [])
	{
		if (is_numeric($id)) {
			$this->db->where(db_prefix().'items.id', $id);
			$item = $this->db->get(db_prefix() . 'items')->row();

			$this->db->where('item_id', $id);
			$listing_rooms = $this->db->get(db_prefix().'real_property_listing_rooms')->result_array();
			$item->listing_rooms = $listing_rooms;
			// $item->contact_infor = real_get_contact_infor($item->created_id);

			return $item;
		}else{
			$this->db->where($where);
			$this->db->where('can_be_property_listing', 'can_be_property_listing');
			$items = $this->db->get(db_prefix().'items')->result_array();
			return $items;
		}
	}

	/**
	 * property listing
	 * @param  [type] $status 
	 * @param  [type] $id     
	 * @param  [type] $type   
	 * @return [type]         
	 */
	public function property_listing_status_mark_as($status, $id, $type)
	{
		$get_property_listing = $this->get_property_listing($id);
		$base_currency_id = get_base_currency_id();

		$status_f = false;
		if($type == 'property_listing'){
			$this->db->where('id', $id);
			if($status == 'sold' || $status == 'rented' || $status == 'temp_off_market' || $status == 'expired' ){
				$dom =round( (strtotime(date('Y-m-d H:i:s')) - strtotime($get_property_listing->date_created))/ (60*60*24));
				$this->db->update(db_prefix() . 'items', ['status' => $status, 'date_sold' => date('Y-m-d H:i:s'), 'date_update' => date('Y-m-d'), 'dom' => $dom]);
			}else{
				$this->db->update(db_prefix() . 'items', ['status' => $status, 'date_update' => date('Y-m-d')]);
			}
			if ($this->db->affected_rows() > 0) {
				$status_f = true;
				$this->log_realestate_activity($id, 'property', 'property_activity_marked_as_'.$status);
				if($status == 'sold'){
					$this->log_realestate_activity($id, 'property', 'property_activity_property_sold_to', serialize([
						app_format_money($get_property_listing->rate, $base_currency_id),
					]));
				}
				if($status == 'rented'){
					$this->log_realestate_activity($id, 'property', 'property_activity_property_rented_to', serialize([
						app_format_money($get_property_listing->rent_price, $base_currency_id).' per '.$get_property_listing->rental_type
					]));
				}
				
			}
		}
		return $status_f;
	}

	/**
	 * delete rel attachment file
	 * @param  [type] $attachment_id 
	 * @param  [type] $folder_name   
	 * @return [type]                
	 */
	public function delete_real_attachment_file($attachment_id, $folder_name)
	{
		$deleted    = false;
		$attachment = $this->misc_model->get_file($attachment_id);
		if ($attachment) {
			if (empty($attachment->external)) {
				if (file_exists($folder_name .$attachment->rel_id.'/'.$attachment->file_name)) {
					unlink($folder_name .$attachment->rel_id.'/'.$attachment->file_name);
				}
				if (file_exists($folder_name .$attachment->rel_id.'/small_'.$attachment->file_name)) {
					unlink($folder_name .$attachment->rel_id.'/small_'.$attachment->file_name);
				}
				if (file_exists($folder_name .$attachment->rel_id.'/thumb_'.$attachment->file_name)) {
					unlink($folder_name .$attachment->rel_id.'/thumb_'.$attachment->file_name);
				}
			}
			
			$this->db->where('id', $attachment->id);
			$this->db->delete(db_prefix() . 'files');
			if ($this->db->affected_rows() > 0) {
				$deleted = true;
				log_activity('Property listing Attachment Deleted [ID: ' . $attachment->rel_id . '] folder name: '.$folder_name);
			}

			if (is_dir($folder_name .$attachment->rel_id)) {
				// Check if no attachments left, so we can delete the folder also
				$other_attachments = list_files($folder_name .$attachment->rel_id);
				if (count($other_attachments) == 0) {
					// okey only index.html so we can delete the folder also
					delete_dir($folder_name .$attachment->rel_id);
				}
			}
		}
		return $deleted;
	}

	/**
	 * delete property listing
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_property_listing($id)
	{
		/*delete file attachment*/
		$array_file= $this->rel_get_attachments_file($id, 'commodity_item_file');
		if(count($array_file) > 0 ){
			foreach ($array_file as $key => $file_value) {
				$this->delete_real_attachment_file($file_value['id'], PROPERTY_UPLOAD);
			}
		}

		$this->db->where('item_id', $id);
		$this->db->delete(db_prefix() . 'real_property_listing_rooms');

		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'items');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}

	/**
	 * delete listing attachment pdf file
	 * @param  [type] $attachment_id 
	 * @return [type]                
	 */
	public function delete_listing_attachment_pdf_file($attachment_id)
	{
		$deleted    = false;
		$attachment = $this->misc_model->get_file($attachment_id);
		if ($attachment) {
			if (empty($attachment->external)) {
				unlink(PROPERTY_PDF_UPLOAD .$attachment->rel_id.'/'.$attachment->file_name);
			}
			$this->db->where('id', $attachment->id);
			$this->db->delete(db_prefix() . 'files');
			if ($this->db->affected_rows() > 0) {
				$deleted = true;
				log_activity('RealEstate Listing PDF Attachment Deleted [ID: ' . $attachment->rel_id . ']');
			}

			if (is_dir(PROPERTY_PDF_UPLOAD .$attachment->rel_id)) {
				// Check if no attachments left, so we can delete the folder also
				$other_attachments = list_files(PROPERTY_PDF_UPLOAD .$attachment->rel_id);
				if (count($other_attachments) == 0) {
					// okey only index.html so we can delete the folder also
					delete_dir(PROPERTY_PDF_UPLOAD .$attachment->rel_id);
				}
			}
		}

		return $deleted;
	}

	/**
	 * Gets the total page.
	 */
	public function get_total_page($type_name = true, $type = true, $table = '', $tables_pagination_limit = 0){
		if($tables_pagination_limit == 0){
			$tables_pagination_limit = get_option('tables_pagination_limit');
		}
		if($tables_pagination_limit == -1){
			return 1;
		}

		$this->db->where($type_name, $type);
		$data = $this->db->get(db_prefix().$table)->result_array();
		// return $total_page = count($data) > 25 ? round((count($data)/25), 0) + 1 : 1;
		
		$remainder = 0;
		if(count($data)%$tables_pagination_limit != 0){
			$remainder = 1;
		}
		return $total_page = count($data) > $tables_pagination_limit ? (int)(count($data)/$tables_pagination_limit) + $remainder : 1;
	}

	/**
	 * property grid view
	 * @return [type] 
	 */
	public function property_grid_view($rel_type = 'client'){

		$where = [];

		$where[] = 'AND '.db_prefix().'items.active = 1 AND '.db_prefix().'items.can_be_property_listing = "can_be_property_listing"';

		$commodity_ft = $this->input->post('commodity_ft');
		$can_be_value_filter = $this->input->post('can_be_value_filter');

		$group_filter = $this->input->post('group_filter');
		$itemPerPage = null !== $this->input->post('itemPerPage') ? $this->input->post('itemPerPage') : 25;

		$join = [
			'LEFT JOIN ' . db_prefix() . 'taxes t1 ON t1.id = ' . db_prefix() . 'items.tax',
			'LEFT JOIN ' . db_prefix() . 'taxes t2 ON t2.id = ' . db_prefix() . 'items.tax2',
			'LEFT JOIN ' . db_prefix() . 'items_groups ON ' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id',
		];

		if($rel_type == 'client'){
		// client
			$where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';
		}

		if($this->input->post()){
			$post_data = $this->input->post();

			$search_input = $this->input->post('search_input');
			$transaction_type_search = $this->input->post('transaction_type');
			$listing_type = $this->input->post('listing_type');
			$property_style = $this->input->post('property_style');
			$min_price_search = $this->input->post('min_price');
			$max_price_search = $this->input->post('max_price');
			$status_search = $this->input->post('status');
			$beds_search = $this->input->post('min_bed');
			$max_bed_search = $this->input->post('max_bed');
			$filter_bath = $this->input->post('filter_bath');
			$filter_garage = $this->input->post('filter_garage');
			$filter_land_min = $this->input->post('filter_land_min');
			$filter_land_max = $this->input->post('filter_land_max');
			$min_total_of_floors = $this->input->post('min_total_of_floors');
			$max_total_of_floors = $this->input->post('max_total_of_floors');
			$appliances_included = $this->input->post('appliances_included');
			$utilities = $this->input->post('utilities');
			$sewer = $this->input->post('sewer');
			$water = $this->input->post('water');
			$air_conditioning = $this->input->post('air_conditioning');
			$electrical_service = $this->input->post('electrical_service');
			$security_features = $this->input->post('security_features');
			$accessibility_features = $this->input->post('accessibility_features');


			$listing_price_from = $min_price_search;
			$listing_price_to = $max_price_search;
			$rent_price_from = $min_price_search;
			$rent_price_to = $max_price_search;

			if($search_input){
				$where[] = 'AND (UPPER('.db_prefix().'items.description) LIKE "%'.strtoupper($search_input).'%"
				OR '.db_prefix().'items.commodity_code LIKE "%'.strtoupper($search_input).'%"
				OR UPPER('.db_prefix().'items.status) LIKE "%'.strtoupper($search_input).'%"
				OR UPPER('.db_prefix().'items.city) LIKE "%'.strtoupper($search_input).'%"
				OR UPPER('.db_prefix().'items.state) LIKE "%'.strtoupper($search_input).'%"
				)
				';
			}

			if($transaction_type_search){
				$arr_transaction_type = explode(',', $transaction_type_search);
				if(in_array('Sale', $arr_transaction_type) || in_array('sold', $arr_transaction_type)){
					$listing_price_from = $min_price_search;
					$listing_price_to = $max_price_search;
				}
				if(in_array('Rent', $arr_transaction_type) || in_array('rented', $arr_transaction_type) ){
					$rent_price_from = $min_price_search;
					$rent_price_to = $max_price_search;
				}
			}

			$price_query = [];
			if(is_numeric($listing_price_from) && $listing_price_from > 0 && is_numeric($listing_price_to) && $listing_price_to > 0 ){
				$price_query[] = '('.db_prefix().'items.rate >= '.$listing_price_from.' AND '.db_prefix().'items.rate <= '.$listing_price_to.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
			}elseif(is_numeric($listing_price_from) && $listing_price_from > 0){
				$price_query[] = '('.db_prefix().'items.rate >= '.$listing_price_from.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
			}elseif(is_numeric($listing_price_to) && $listing_price_to > 0){
				$price_query[] = '('.db_prefix().'items.rate <= '.$listing_price_to.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
			}

			if(is_numeric($rent_price_from) && $rent_price_from > 0 && is_numeric($rent_price_to) && $rent_price_to > 0 ){
				$price_query[] = '('.db_prefix().'items.rent_price >= '.$rent_price_from.' AND '.db_prefix().'items.rent_price <= '.$rent_price_to.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
			}elseif(is_numeric($rent_price_from) && $rent_price_from > 0){
				$price_query[] = '('.db_prefix().'items.rent_price >= '.$rent_price_from.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
			}elseif(is_numeric($rent_price_to) && $rent_price_to > 0){
				$price_query[] = '('.db_prefix().'items.rent_price <= '.$rent_price_to.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
			}

			if(count($price_query) > 0){
				$where[] = 'AND ('.implode(' OR ', $price_query).')';
			}

			$bed_query = [];
			if(is_numeric($beds_search) && $beds_search > 0 && is_numeric($max_bed_search) && $max_bed_search > 0 ){
				$bed_query[] = '('.db_prefix().'items.beds >= '.$beds_search.' AND '.db_prefix().'items.beds <= '.$max_bed_search.')';		
			}elseif(is_numeric($beds_search) && $beds_search > 0){
				$bed_query[] = '('.db_prefix().'items.beds >= '.$beds_search.')';		
			}elseif(is_numeric($max_bed_search) && $max_bed_search > 0){
				$bed_query[] = '('.db_prefix().'items.beds <= '.$max_bed_search.')';		
			}

			if(count($bed_query) > 0){
				$where[] = 'AND ('.implode(' OR ', $bed_query).')';
			}

			$land_size_query = [];
			if(is_numeric($filter_land_min) && $filter_land_min > 0 && is_numeric($filter_land_max) && $filter_land_max > 0 ){
				$land_size_query[] = '('.db_prefix().'items.lot_size_acres >= '.$filter_land_min.' AND '.db_prefix().'items.lot_size_acres <= '.$filter_land_max.')';		
			}elseif(is_numeric($filter_land_min) && $filter_land_min > 0){
				$land_size_query[] = '('.db_prefix().'items.lot_size_acres >= '.$filter_land_min.')';		
			}elseif(is_numeric($filter_land_max) && $filter_land_max > 0){
				$land_size_query[] = '('.db_prefix().'items.lot_size_acres <= '.$filter_land_max.')';		
			}

			if(count($land_size_query) > 0){
				$where[] = 'AND ('.implode(' OR ', $land_size_query).')';
			}

			if ($filter_garage) {
				$where[] = 'AND '.db_prefix().'items.garage >= '.$filter_garage;
			}

			if ($filter_bath) {
				$where[] = 'AND '.db_prefix().'items.full_baths >= '.$filter_bath;
			}

			// property filter by mutilple value
			$property_listing_where = '';
			$fields = [
				'transaction_type',
				'listing_type',
				'property_style',
				'appliances_included',
				'utilities',
				'sewer',
				'water',
				'air_conditioning',
				'electrical_service',
				'security_features',
				'accessibility_features',
			];

			foreach ($fields as $key => $field_name) {

				if(($this->input->post($field_name))){
					if(strlen($property_listing_where) == 0){
						$property_listing_where .= ' AND (';
					}
					if($property_listing_where != ' AND ('){
						$property_listing_where .= ' AND';
					}
					$arr_search_value  = explode(',', $this->input->post($field_name));

					foreach ($arr_search_value as $key => $search_value) {
						if ($search_value != '') {

							if($key == 0 && count($arr_search_value) > 1){
								$property_listing_where .= ' ( find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.') ';

							}elseif(count($arr_search_value) == 1){
								$property_listing_where .= ' ( find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.')) ';

							}elseif($key == count($arr_search_value) - 1){
								$property_listing_where .= ' OR find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.')) ';

							}else{
								$property_listing_where .= ' OR find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$field_name.') ';
							}
						}
					}
				}
			}

			if ($property_listing_where != '' && $property_listing_where != ' AND (' && $property_listing_where != ' AND ()') {
				$property_listing_where .= ')';

				$where[] = $property_listing_where;
			}
			if($status_search){
				$where[] = 'AND ('.db_prefix().'items.status = "'.$status_search.'")';
			}

		}

		if($this->input->post('page_number')){
			$result = real_data_grid_init($this->input->post('page_number'), $where, $itemPerPage);
			$total_page = $this->get_total_page('can_be_property_listing', 'can_be_property_listing', 'items', $itemPerPage);
		}else{
			$result = [];
			$total_page = 0;
		}

		$html = '';

		if(count($result) > 0){
			$data['properties'] = $result;
			$html = $this->load->view('companies/property_listings/utilities/room_item', $data, true);
		}else{
			$html = 'No entries found';
		}

		return ['html' => $html, 'total_page' => $total_page];
	}

	/**
	 * get property asset
	 * @param  [type]  $id            
	 * @param  boolean $include_video 
	 * @return [type]                 
	 */
	public function get_property_asset($id, $include_video = false)
	{
		$assets = [];

		if($include_video){
			$property_videos = $this->rel_get_attachments_file($id, 'property_video');
		}
		$property_images = $this->realestate_model->rel_get_attachments_file($id, 'commodity_item_file');
		$main_image = main_photo($id);
		$assets[] = [
			'type' => 'main_image',
			'site_url' => $main_image,
		];

		if($include_video){
			foreach ($property_videos as $key => $value) {
				$value['type'] = 'video';
				$assets[] = $value;
			}
		}

		foreach ($property_images as $key => $value) {
			$value['type'] = 'image';
			$assets[] = $value;
		}

		return $assets;
	}

	/**
	 * render query data for map
	 * @param  string $rel_type 
	 * @return [type]           
	 */
	public function render_query_data_for_map($rel_type = 'client')
	{
		$where = [];
		$post_data = [];
		$formdata = $this->input->post();
		$listing_attachment = $this->realestate_model->listing_attachment();

		if(isset($formdata['formdata'])){
			foreach ($formdata['formdata'] as $key => $value) {
				if(preg_match('/]/', $value['name'])){
					$name = str_replace('[]', '', $value['name']);
					$post_data[$name][] = $value['value'];
				}elseif( $value['name'] != 'csrf_token_name' && $value['name'] != 'id'){
					$post_data[$value['name']] = $value['value'];
				}
			}

			$staff_in_company = rel_check_staff_in_company();

			if($rel_type == 'company'){

				if(is_admin()){
			// is admin: view all
				}elseif($staff_in_company){
					// staff in company
					if(has_permission('real_property', '', 'view_own')){

						$where[] = 'AND ( ('.db_prefix().'items.related_type = "company" AND '.db_prefix().'items.related_id = '.$get_staff_user_id .') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.') )';

					}elseif(has_permission('real_property', '', 'view')){

						$where[] = 'AND ('.db_prefix().'items.company_id = '.$staff_in_company.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.' OR '.db_prefix().'real_request_brokerages.company_id = '.$staff_in_company.' ) )';
					}else{
						$where[] = 'AND 1=2';
					}
				}else{
					// staff not in construction company
					if(has_permission('real_property', '', 'view')){

						$where[] = 'AND ('.db_prefix().'items.is_company_admin = 1 OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.') )';

					}elseif(has_permission('real_property', '', 'view_own')){

						$where[] = 'AND (('.db_prefix().'items.related_type = "staff" AND '.db_prefix().'items.related_id = '.$get_staff_user_id.') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$get_staff_user_id.') )';
					}else{
						$where[] = 'AND 1=2';
					}
				}
			}elseif($rel_type == 'broker'){
				$business_broker_id = get_business_broker_id();

				$where[] = 'AND ('.db_prefix().'items.broker_id = '.$business_broker_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$business_broker_id.') )';
			}else{
				// client
				$where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';
			}

			$min_price_search = isset($post_data['min_price']) ? $post_data['min_price'] : '';
			$max_price_search = isset($post_data['max_price']) ? $post_data['max_price'] : '';

			$listing_price_from = isset($post_data['min_price']) ? $post_data['min_price'] : '';
			$listing_price_to = isset($post_data['max_price']) ? $post_data['max_price'] : '';
			$rent_price_from = isset($post_data['min_price']) ? $post_data['min_price'] : '';
			$rent_price_to = isset($post_data['max_price']) ? $post_data['max_price'] : '';

			if(isset($post_data['filter_input']) && $post_data['filter_input'] != ''){
				$where[] = 'AND (UPPER('.db_prefix().'items.description) LIKE "%'.strtoupper($post_data['filter_input']).'%"
				OR '.db_prefix().'items.commodity_code LIKE "%'.strtoupper($post_data['filter_input']).'%"
				OR UPPER('.db_prefix().'items.status) LIKE "%'.strtoupper($post_data['filter_input']).'%"
				OR UPPER('.db_prefix().'items.city) LIKE "%'.strtoupper($post_data['filter_input']).'%"
				OR UPPER('.db_prefix().'items.state) LIKE "%'.strtoupper($post_data['filter_input']).'%"
				)
				';
			}

			if(isset($post_data['filter_transaction_type_value']) && $post_data['filter_transaction_type_value'] != ''){
				$arr_transaction_type = explode(',', $post_data['filter_transaction_type_value']);
				if(in_array('Sale', $arr_transaction_type) || in_array('sold', $arr_transaction_type)){
					$listing_price_from = $min_price_search;
					$listing_price_to = $max_price_search;
					$rent_price_from = '';
					$rent_price_to = '';
				}elseif(in_array('Rent', $arr_transaction_type) || in_array('rented', $arr_transaction_type) ){
					$rent_price_from = $min_price_search;
					$rent_price_to = $max_price_search;
					$listing_price_from = '';
					$listing_price_to = '';
				}
			}
			
			$price_query = [];
			if(is_numeric($listing_price_from) && $listing_price_from > 0 && is_numeric($listing_price_to) && $listing_price_to > 0 ){
				$price_query[] = '('.db_prefix().'items.rate >= '.$listing_price_from.' AND '.db_prefix().'items.rate <= '.$listing_price_to.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
			}elseif(is_numeric($listing_price_from) && $listing_price_from > 0){
				$price_query[] = '('.db_prefix().'items.rate >= '.$listing_price_from.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
			}elseif(is_numeric($listing_price_to) && $listing_price_to > 0){
				$price_query[] = '('.db_prefix().'items.rate <= '.$listing_price_to.' AND ('.db_prefix().'items.transaction_type = "Sale" OR '.db_prefix().'items.transaction_type = "sold" ) )';		
			}

			if(is_numeric($rent_price_from) && $rent_price_from > 0 && is_numeric($rent_price_to) && $rent_price_to > 0 ){
				$price_query[] = '('.db_prefix().'items.rent_price >= '.$rent_price_from.' AND '.db_prefix().'items.rent_price <= '.$rent_price_to.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
			}elseif(is_numeric($rent_price_from) && $rent_price_from > 0){
				$price_query[] = '('.db_prefix().'items.rent_price >= '.$rent_price_from.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
			}elseif(is_numeric($rent_price_to) && $rent_price_to > 0){
				$price_query[] = '('.db_prefix().'items.rent_price <= '.$rent_price_to.' AND ('.db_prefix().'items.transaction_type = "Rent" OR '.db_prefix().'items.transaction_type = "rented" ) )';		
			}

			if(count($price_query) > 0){
				$where[] = 'AND ('.implode(' OR ', $price_query).')';
			}

			$bed_query = [];
			if(isset($post_data['min_bed']) && is_numeric($post_data['min_bed']) && $post_data['min_bed'] > 0 && isset($post_data['max_bed']) && is_numeric($post_data['max_bed']) && $post_data['max_bed'] > 0 ){
				$bed_query[] = '('.db_prefix().'items.beds >= '.$post_data['min_bed'].' AND '.db_prefix().'items.beds <= '.$post_data['max_bed'].')';		
			}elseif(isset($post_data['min_bed']) && is_numeric($post_data['min_bed']) && $post_data['min_bed'] > 0){
				$bed_query[] = '('.db_prefix().'items.beds >= '.$post_data['min_bed'].')';		
			}elseif(isset($post_data['max_bed']) && is_numeric($post_data['max_bed']) && $post_data['max_bed'] > 0){
				$bed_query[] = '('.db_prefix().'items.beds <= '.$post_data['max_bed'].')';		
			}

			if(count($bed_query) > 0){
				$where[] = 'AND ('.implode(' OR ', $bed_query).')';
			}

			$land_size_query = [];
			if(isset($post_data['filter_land_min']) && is_numeric($post_data['filter_land_min']) && $post_data['filter_land_min'] > 0 && isset($post_data['filter_land_max']) && is_numeric($post_data['filter_land_max']) && $post_data['filter_land_max'] > 0 ){
				$land_size_query[] = '('.db_prefix().'items.lot_size_acres >= '.$post_data['filter_land_min'].' AND '.db_prefix().'items.lot_size_acres <= '.$post_data['filter_land_max'].')';		
			}elseif(isset($post_data['filter_land_min']) && is_numeric($post_data['filter_land_min']) && $post_data['filter_land_min'] > 0){
				$land_size_query[] = '('.db_prefix().'items.lot_size_acres >= '.$post_data['filter_land_min'].')';		
			}elseif(isset($post_data['filter_land_max']) && is_numeric($post_data['filter_land_max']) && $post_data['filter_land_max'] > 0){
				$land_size_query[] = '('.db_prefix().'items.lot_size_acres <= '.$post_data['filter_land_max'].')';		
			}

			if(count($land_size_query) > 0){
				$where[] = 'AND ('.implode(' OR ', $land_size_query).')';
			}

			
			if (isset($post_data['filter_garage']) && $post_data['filter_garage'] != '') {
				$where[] = 'AND '.db_prefix().'items.garage >= '.$post_data['filter_garage'];
			}

			if (isset($post_data['filter_bath']) && $post_data['filter_bath'] != '') {
				$where[] = 'AND '.db_prefix().'items.full_baths >= '.$post_data['filter_bath'];
			}

			if (isset($post_data['status']) && $post_data['status'] != '') {
				$where[] = 'AND '.db_prefix().'items.status = "'.$post_data['status'].'"';
			}

			// property filter by mutilple value
			$property_listing_where = '';
			$fields = [
				'transaction_type' => 'filter_transaction_type_value',
				'listing_type' => 'filter_listing_type_value',
				'property_style' => 'filter_property_style_value',
				'appliances_included' => 'filter_appliances_included_value',
				'utilities' => 'filter_utilities_value',
				'sewer' => 'filter_sewer_value',
				'water' => 'filter_water_value',
				'air_conditioning' => 'filter_air_conditioning_value',
				'electrical_service' => 'filter_electrical_service_value',
				'security_features' => 'filter_security_features_value',
				'accessibility_features' => 'filter_accessibility_features_value',
			];

			foreach ($fields as $column => $field_name) {

				if(isset($post_data[$field_name]) && $post_data[$field_name] != ''){
					if(strlen($property_listing_where) == 0){
						$property_listing_where .= ' AND (';
					}
					if($property_listing_where != ' AND ('){
						$property_listing_where .= ' AND';
					}
					$arr_search_value  = explode(',', $post_data[$field_name]);

					foreach ($arr_search_value as $key => $search_value) {
						if ($search_value != '') {

							if($key == 0 && count($arr_search_value) > 1){
								$property_listing_where .= ' ( find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$column.') ';

							}elseif(count($arr_search_value) == 1){
								$property_listing_where .= ' ( find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$column.')) ';

							}elseif($key == count($arr_search_value) - 1){
								$property_listing_where .= ' OR find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$column.')) ';

							}else{
								$property_listing_where .= ' OR find_in_set("' . $search_value . '", ' . db_prefix() . 'items.'.$column.') ';
							}
						}
					}
				}
			}

			if ($property_listing_where != '' && $property_listing_where != ' AND (' && $property_listing_where != ' AND ()') {
				$property_listing_where .= ')';

				$where[] = $property_listing_where;
			}

		}

		if(isset($formdata['my_favourite_filter']) && $formdata['my_favourite_filter'] != null && $formdata['my_favourite_filter'] == 'favourite'){
			$favarite_listings = $this->realestate_model->get_favarite_listing();

			$arr_favourite = array_keys($favarite_listings);
			if(count($arr_favourite) > 0){
				$where[] = 'AND '.db_prefix().'items.id IN ('.implode(',', $arr_favourite).')';
			}else{
				$where[] = 'AND 1=2';
			}
		}

		if(isset($formdata['has_attachment']) && $formdata['has_attachment'] != null && $formdata['has_attachment'] == 'true'){
			if(count($listing_attachment) > 0){
				$list_item_id = [];
				foreach ($listing_attachment as $key => $value) {
					if(!in_array($key, $list_item_id)){
						$list_item_id[] = $key;
					}
				}
				$where[] = 'AND '.db_prefix().'items.id IN ('.implode(',', $list_item_id).')';
			}else{
				$where[] = 'AND 1=2';
			}
		}

		if(isset($formdata['my_listing']) && $formdata['my_listing'] == 1){
			$where[] = 'AND ('.db_prefix().'items.created_id = '.get_staff_user_id().')';
		}

		if(isset($formdata['item_id'])){
			if($formdata['item_id'] != null && $formdata['item_id'] != ''){
				$item_id_arr = explode(',',$formdata['item_id']);
				$add_query = [];
				foreach ($item_id_arr as $item_id) {
					$add_query[] = db_prefix().'items.id = '.$item_id;
				}

				if(count($add_query) > 0){
					$new_query = implode(' OR ', $add_query);
					$where[] = 'AND ('.$new_query.')';
				}
			}
		}

		
		$sWhere = '';
		$where = implode(' ', $where);
		if ($sWhere == '') {
			$where = trim($where);
			if (startsWith($where, 'AND') || startsWith($where, 'OR')) {
				if (startsWith($where, 'OR')) {
					$where = substr($where, 2);
				} else {
					$where = substr($where, 3);
				}
			}
		}

		$map_property_listing = $this->map_get_property_listing($where, false, isset($post_data['latitude_filter']) ? $post_data['latitude_filter'] : '', isset($post_data['longitude_filter']) ? $post_data['longitude_filter'] : '' , isset($formdata['radius_filter']) ? $formdata['radius_filter'] : 10, true, false, $rel_type );
		return $map_property_listing;
	}

	/**
	 * add favorite listing
	 * @param [type] $id     
	 * @param [type] $status 
	 */
	public function add_favorite_listing($id, $status)
	{
		if($status == 1){
			// add
			$this->db->insert(db_prefix() . 'real_saved_properties', [
				'item_id' => $id,
				'created_id' => get_staff_user_id(),
				'date_created' => date('Y-m-d H:i:s'),
			]);
		}else{
			//delete
			$this->db->where('item_id', $id);
			$this->db->where('created_id', get_staff_user_id());
			$this->db->delete(db_prefix().'real_saved_properties');
		}
		return true;
	}

	/**
	 * get request broker
	 * @param  boolean $id    
	 * @param  string  $where 
	 * @return [type]         
	 */
	public function get_request_broker($id = false, $where = '', $property_id = false) {

		if (is_numeric($id)) {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_request_brokerages')->row();
		}
		if ($id == false) {
			$request_brokerages = [];

			if(is_numeric($property_id) && $property_id != 0){
				$property = $this->get_property_listing($property_id);
				if($property && $property->broker_id > 0){
					$this->load->model('realestate/broker_model');
					$broker_staff = $this->broker_model->get_broker_staff(false, ['company_id'=>$property->broker_id]);

					$broker_id = [];
					$broker_id[$property->broker_id]['broker_id'] = $property->broker_id;
					$broker_id[$property->broker_id]['company_id'] = 0;
					$broker_id[$property->broker_id]['agent_employees'] = $broker_staff;
				}
			}
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			$real_request_brokerages = $this->db->get(db_prefix() . 'real_request_brokerages')->result_array();
			foreach ($real_request_brokerages as $key => $value) {
				if($value['company_id'] != 0){
					if(isset($request_brokerages[$value['company_id']])){
						$request_brokerages[$value['company_id']]['agent_employees'][] = $value;
					}else{
						$request_brokerages[$value['company_id']]['broker_id'] = $value['broker_id'];
						$request_brokerages[$value['company_id']]['company_id'] = $value['company_id'];
						$request_brokerages[$value['company_id']]['agent_employees'][] = $value;
					}
				}
				if($value['broker_id'] != 0){
					if(isset($request_brokerages[$value['company_id']])){
						$request_brokerages[$value['broker_id']]['agent_employees'][] = $value;
					}else{
						$request_brokerages[$value['broker_id']]['broker_id'] = $value['broker_id'];
						$request_brokerages[$value['broker_id']]['company_id'] = $value['company_id'];
						$request_brokerages[$value['broker_id']]['agent_employees'][] = $value;
					}
				}

				if($value['company_id'] == 0 && $value['broker_id'] == 0){
					if(isset($request_brokerages['company'])){
						$request_brokerages['company']['agent_employees'][] = $value;
					}else{
						$request_brokerages['company']['broker_id'] = $value['broker_id'];
						$request_brokerages['company']['company_id'] = $value['company_id'];
						$request_brokerages['company']['agent_employees'][] = $value;
					}
				}
			}

			if(isset($broker_id)){
				$request_brokerages = array_merge($broker_id, $request_brokerages);
			}
			return $request_brokerages;
		}
	}

	/**
	 * add request broker
	 * @param [type] $data 
	 */
	public function add_request_broker($data)
	{

		$data['date_created'] = date('Y-m-d H:i:s');
		$data['created_id'] = get_staff_user_id();

		$this->db->insert(db_prefix().'real_request_brokerages',$data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			return $insert_id;
		}
		return false;
	}

	/**
	 * update request broker
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_request_broker($data, $id)
	{
		$affected_rows=0;
		$data['date_updated'] = date('Y-m-d H:i:s');

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_request_brokerages', $data);
		if ($this->db->affected_rows() > 0) {
			return true;
		}

		return false;  
	}

	/**
	 * delete request broker
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_request_broker($id)
	{
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_request_brokerages');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}

	/**
	 * get address history
	 * @param  boolean $id    
	 * @param  string  $where 
	 * @return [type]         
	 */
	public function get_address_history($id = false, $where = '') {

		if (is_numeric($id)) {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_contact_address_histories')->row();
		}
		if ($id == false) {
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			return $this->db->get(db_prefix() . 'real_contact_address_histories')->result_array();
		}
	}

	/**
	 * add address history
	 * @param [type] $data 
	 */
	public function add_address_history($data)
	{

		if($data['move_in']){
			$data['move_in'] = to_sql_date($data['move_in']);
		}
		$data['move_out'] = to_sql_date($data['move_out']);

		$this->db->insert(db_prefix().'real_contact_address_histories',$data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			return $insert_id;
		}
		return false;
	}

	/**
	 * update address history
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_address_history($data, $id)
	{
		$affected_rows=0;
		if($data['move_in']){
			$data['move_in'] = to_sql_date($data['move_in']);
		}
		$data['move_out'] = to_sql_date($data['move_out']);

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_contact_address_histories', $data);
		if ($this->db->affected_rows() > 0) {
			return true;
		}

		return false;  
	}

	/**
	 * delete address history
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_address_history($id)
	{
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_contact_address_histories');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}

	/**
	 * get income source
	 * @param  boolean $id    
	 * @param  string  $where 
	 * @return [type]         
	 */
	public function get_income_source($id = false, $where = '') {

		if (is_numeric($id)) {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_contact_incomes')->row();
		}
		if ($id == false) {
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			return $this->db->get(db_prefix() . 'real_contact_incomes')->result_array();
		}
	}

	/**
	 * add_income_source
	 * @param [type] $data 
	 */
	public function add_income_source($data)
	{

		$this->db->insert(db_prefix().'real_contact_incomes',$data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			return $insert_id;
		}
		return false;
	}

	/**
	 * update income source
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_income_source($data, $id)
	{
		$affected_rows=0;

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_contact_incomes', $data);
		if ($this->db->affected_rows() > 0) {
			return true;
		}

		return false;  
	}

	/**
	 * delete income source
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_income_source($id)
	{
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_contact_incomes');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}

	/**
	 * get person
	 * @param  boolean $id    
	 * @param  string  $where 
	 * @return [type]         
	 */
	public function get_person($id = false, $where = '') {

		if (is_numeric($id)) {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_contact_occupants')->row();
		}
		if ($id == false) {
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			return $this->db->get(db_prefix() . 'real_contact_occupants')->result_array();
		}
	}

	/**
	 * add person
	 * @param [type] $data 
	 */
	public function add_person($data)
	{
		$this->db->insert(db_prefix().'real_contact_occupants',$data);
		$insert_id = $this->db->insert_id();

		if ($insert_id) {
			return $insert_id;
		}
		return false;
	}

	/**
	 * update person
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_person($data, $id)
	{
		$affected_rows=0;

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_contact_occupants', $data);
		if ($this->db->affected_rows() > 0) {
			return true;
		}

		return false;  
	}

	/**
	 * delete person
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_person($id)
	{
		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_contact_occupants');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}

	/**
	 * add group form manage
	 * @param array $data 
	 */
	public function add_group_form_manage($data)
	{
		$this->db->insert(db_prefix().'items_groups', $data);
		$insert_id = $this->db->insert_id();
		if($insert_id){
			return $insert_id;
		}
		return false;
	}
	/**
	 * get group form manage
	 * @param  string $id 
	 * @return [type]     
	 */
	public function get_group_form_manage($id = '', $where = '')
	{
		if ($id != '') {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'items_groups')->row();
		}
		else{
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			$this->db->order_by('id', 'desc');
			return $this->db->get(db_prefix().'items_groups')->result_array();
		}
	}

	/**
	 * add school form manage
	 * @param [type] $data 
	 */
	public function add_school_form_manage($data)
	{
		$data['date_created'] = date('Y-m-d H:i:s');
		$data['date_updated'] = date('Y-m-d H:i:s');
		$this->db->insert(db_prefix().'real_schools', $data);
		$insert_id = $this->db->insert_id();
		if($insert_id){
			return $insert_id;
		}
		return false;
	}

	/**
	 * get school form manage
	 * @param  string $id    
	 * @param  string $where 
	 * @return [type]        
	 */
	public function get_school_form_manage($id = '', $where = '')
	{
		if ($id != '') {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_schools')->row();
		}
		else{
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			$this->db->where('active', 1);
			$this->db->order_by('id', 'desc');
			return $this->db->get(db_prefix().'real_schools')->result_array();
		}
	}

	/**
	 * add landmark form manage
	 * @param [type] $data 
	 */
	public function add_landmark_form_manage($data)
	{
		$data['date_created'] = date('Y-m-d H:i:s');
		$data['date_updated'] = date('Y-m-d H:i:s');
		$this->db->insert(db_prefix().'real_landmarks', $data);
		$insert_id = $this->db->insert_id();
		if($insert_id){
			return $insert_id;
		}
		return false;
	}

	/**
	 * get landmark form manage
	 * @param  string $id    
	 * @param  string $where 
	 * @return [type]        
	 */
	public function get_landmark_form_manage($id = '', $where = '')
	{
		if ($id != '') {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_landmarks')->row();
		}
		else{
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			$this->db->where('active', 1);
			$this->db->order_by('id', 'desc');
			return $this->db->get(db_prefix().'real_landmarks')->result_array();
		}
	}

	/**
	 * add hopspital form manage
	 * @param [type] $data 
	 */
	public function add_hopspital_form_manage($data)
	{
		$data['date_created'] = date('Y-m-d H:i:s');
		$data['date_updated'] = date('Y-m-d H:i:s');
		$this->db->insert(db_prefix().'real_hopspitals', $data);
		$insert_id = $this->db->insert_id();
		if($insert_id){
			return $insert_id;
		}
		return false;
	}

	/**
	 * get hopspital form manage
	 * @param  string $id    
	 * @param  string $where 
	 * @return [type]        
	 */
	public function get_hopspital_form_manage($id = '', $where = '')
	{
		if ($id != '') {
			$this->db->where('id', $id);
			return $this->db->get(db_prefix() . 'real_hopspitals')->row();
		}
		else{
			if(strlen($where) > 0){
				$this->db->where($where);
			}
			$this->db->where('active', 1);
			$this->db->order_by('id', 'desc');
			return $this->db->get(db_prefix().'real_hopspitals')->result_array();
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
		$this->db->where('staffid', $id);
		$this->db->update(db_prefix() . 'staff', [
			'require_approvals' => $status,
		]);
		if($this->db->affected_rows() > 0){
			return true;
		}
		return false;
	}

	/**
	 * change staff is approval manager
	 * @param  [type] $id     
	 * @param  [type] $status 
	 * @return [type]         
	 */
	public function change_staff_is_approval_manager($id, $status)
	{
		$this->db->where('staffid', $id);
		$this->db->update(db_prefix() . 'staff', [
			'is_approval_manager' => $status,
		]);
		if($this->db->affected_rows() > 0){
			return true;
		}
		return false;
	}

	/**
	 * get listing status add new
	 * @param  [type] $listing_id 
	 * @return [type]             
	 */
	public function get_listing_status_add_new($listing_id)
	{
		if(is_broker_logged_in()){
			// don't need approval
		}else{
			$arr_staff = [];
			$staff_in_company = rel_check_staff_in_company();
			$get_staff_user_id = get_staff_user_id();
			$this->load->model('staff_model');

			if(is_admin()){
			// don't need approval

			}elseif($staff_in_company){
			// staff in company
				$arr_approval_managers = $this->staff_model->get('', '(company_id='.$staff_in_company.') AND is_approval_manager = 1');
				$arr_staff_require_approvals = $this->staff_model->get('', '(staffid='.$get_staff_user_id.') AND require_approvals = 1');

				if(count($arr_approval_managers) > 0 && count($arr_staff_require_approvals) > 0){
					$this->db->where('id', $listing_id);
					$this->db->update(db_prefix().'items', ['status' => 'pending', 'date_approval' => '']);

					// send email approval to approval_managers
					// get list user to send mail
					foreach ($arr_approval_managers as $arr_approval_manager) {
						$arr_staff[$arr_approval_manager['staffid']] = [
							'staffid' => $arr_approval_manager['staffid'],
							'email' => $arr_approval_manager['email'],
						];
					}
				}
			}else{
			// staff not in construction company
				$arr_approval_managers = $this->staff_model->get('', '(company_id=0) AND is_approval_manager = 1');
				$arr_staff_require_approvals = $this->staff_model->get('', '(staffid='.$get_staff_user_id.') AND require_approvals = 1');

				if(count($arr_approval_managers) > 0 && count($arr_staff_require_approvals) > 0){
					$this->db->where('id', $listing_id);
					$this->db->update(db_prefix().'items', ['status' => 'pending', 'date_approval' => '']);

					// send email approval to approval_managers
					// get list user to send mail
					foreach ($arr_approval_managers as $arr_approval_manager) {
						$arr_staff[$arr_approval_manager['staffid']] = [
							'staffid' => $arr_approval_manager['staffid'],
							'email' => $arr_approval_manager['email'],
						];
					}
				}
			}

			if(count($arr_staff) > 0){
				$this->load->model('emails_model');
				$current_staff_name = get_staff_full_name(get_staff_user_id());

				// send notification

				// send mail
				$link = 'realestate/approvals';
				$string_notification = '<a href="'.admin_url($link).'">'. _l('real_new_listing_has_been_published_that_needs_approval').'</a>'.'<br/>';

				foreach ($arr_staff as $staff) {
					$notified = add_notification([
						'description' => $string_notification,
						'touserid' => $staff['staffid'],
						'link' => $link,
						'additional_data' => serialize([
							$string_notification,
						]),
					]);
					if ($notified) {
						pusher_trigger_notification([$staff['staffid']]);
					}

					/*send mail*/
					$this->emails_model->send_simple_email($staff['email'], _l('real_new_listing_has_been_published_that_needs_approval'), $string_notification._l('real_from_staff', $current_staff_name).'. '. _l('real_please_visit_the_new_listing_approval_page'));
				}
			}
		}
		return true;
	}

	/**
	 * add property request
	 * @param [type]  $data 
	 * @param boolean $id   
	 */
	public function add_property_request($data, $id = false) {
		$order_details = [];
		
		if(isset($data['include_shipping'])){
			unset($data['include_shipping']);
		}
		if(isset($data['tax_select'])){
			unset($data['tax_select']);
		}
		if(isset($data['contract_is_recurring'])){
			$data['contract_is_recurring'] = 1;
		}else{
			$data['contract_is_recurring'] = 0;
			$data['contract_recurring_value'] = 0;
			$data['contract_recurring_type'] = '';
		}

		$data['datecreated'] = to_sql_date($data['datecreated'], true);
		$data['date'] = to_sql_date($data['date']);
		$data['duedate'] = to_sql_date($data['duedate']);
		if($data['inspection_date'] != '' && $data['inspection_date'] != null){
			$data['inspection_date'] = to_sql_date($data['inspection_date']);
		}else{
			$data['inspection_date'] = null;
		}

		$data['status'] = 1;
		$data['hash'] = app_generate_hash();

		$this->db->insert(db_prefix() . 'real_requests', $data);
		$insert_id = $this->db->insert_id();

		if (isset($insert_id)) {
			$this->log_property_request_activity($insert_id, 'property_request_activity_created');
		}

		if (isset($insert_id)) {
			return $insert_id;
		}
		return false;
	}

	/**
	 * update property request
	 * @param  [type]  $data 
	 * @param  boolean $id   
	 * @return [type]        
	 */
	public function update_property_request($data, $id = false) {
		$results=0;

		if(isset($data['isedit'])){
			unset($data['isedit']);
		}

		if(isset($data['include_shipping'])){
			unset($data['include_shipping']);
		}
		if(isset($data['contract_is_recurring'])){
			$data['contract_is_recurring'] = 1;
		}else{
			$data['contract_is_recurring'] = 0;
			$data['contract_recurring_value'] = 0;
			$data['contract_recurring_type'] = '';
		}

		$data['datecreated'] = to_sql_date($data['datecreated'], true);
		$data['date'] = to_sql_date($data['date'], true);
		$data['duedate'] = to_sql_date($data['duedate'], true);

		$order_id = $data['id'];
		unset($data['id']);

		$this->db->where('id', $order_id);
		$this->db->update(db_prefix() . 'real_requests', $data);
		if ($this->db->affected_rows() > 0) {
			$results++;
		}
		
		hooks()->do_action('real_after_request_updated', $order_id);

		return $results > 0 ? true : false;

	}

	/**
	 * delete property request
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function delete_property_request($id)
	{

		hooks()->do_action('real_before_request_deleted', $id);
		$affected_rows = 0;

		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'real_requests');
		if ($this->db->affected_rows() > 0) {
			$affected_rows++;
		}

		if ($affected_rows > 0) {
			return true;
		}
		return false;
	}

	/**
	 * get property request
	 * @param  boolean $id    
	 * @param  string  $where 
	 * @return [type]         
	 */
	public function get_property_request($id = false, $where = '')
	{
		 
		$this->db->select('*,' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'real_requests.id as id, ' . db_prefix() . 'currencies.name as currency_name');
		$this->db->from(db_prefix() . 'real_requests');
		$this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'real_requests.currency', 'left');
		if($where != ''){
			$this->db->where($where);
		}
		if (is_numeric($id)){

			$this->db->where(db_prefix() . 'real_requests.id', $id);
			$request = $this->db->get()->row();

			$this->load->model('clients_model');
			$request->client = $this->clients_model->get($request->clientid);

			return $request;
		}
		return $this->db->get()->result_array();
	}

	/**
	 * convert to contract
	 * @param  [type]  $id     
	 * @param  boolean $client 
	 * @return [type]          
	 */
	public function convert_to_contract($id, $client = false)
	{
		$this->load->model('contracts_model');
		$contract_description = '';
		$property_request = $this->get_property_request($id);
		$contract = [];
		$contract['client'] = $property_request->clientid;
		$contract['project_id'] = 0;
		$contract['subject'] = get_property_name($property_request->item_id, false, false, true);
		$contract['contract_value'] = $property_request->contract_total;
		$contract['contract_type'] = '';
		$contract['datestart'] = _d($property_request->date);
		$contract['dateend'] = _d($property_request->duedate);
		$contract['description'] = 'Converted from '. $property_request->code;
		$contract['content'] = $contract_description;
		$contract['property_request_id'] = $id;

		if(is_broker_logged_in()){
			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$contract['is_company_admin'] = 0;
			$contract['company_id'] = 0;
			$contract['broker_id'] = $broker_id;
			$contract['related_type'] = 'business_broker';
			$contract['related_id'] = $related_id;
		}else{
			$broker_id = 0;
			$related_id = get_staff_user_id();
			$check_staff_type = rel_check_staff_type();

			$contract['is_company_admin'] = $check_staff_type['is_company_admin'];
			$contract['company_id'] = $check_staff_type['company_id'];
			$contract['broker_id'] = $broker_id;
			$contract['related_type'] = $check_staff_type['staff_type'];
			$contract['related_id'] = $related_id;

			// Assign customers to staff to allow staff to create invoices - contracts for customers if needed
			$customer_admins = [];
			array_push($customer_admins, $related_id);

			$this->load->model('clients_model');
			$current_admins     = $this->clients_model->get_admins($property_request->clientid);
			foreach ($current_admins as $key => $value) {
				array_push($customer_admins, (int)$value['staff_id']);
			}
			$this->clients_model->assign_admins(['customer_admins' => $customer_admins], $property_request->clientid);
		}

		$contract_id = $this->contracts_model->add($contract);
		if($contract_id){
			$this->db->where('id', $id);
			$this->db->update(db_prefix().'real_requests', ['contract_id' => $contract_id, 'contrated_date' => date('Y-m-d H-i-s')]);

			hooks()->do_action('request_converted_to_contract', ['contract_id' => $contract_id, 'property_request_id' => $id]);
			$this->property_listing_status_mark_as('pending_sale', $property_request->item_id, 'property_listing');
			return $contract_id;

		}

		return false;
	}

	/**
	 * Convert  to invoice
	 * @param mixed $id estimate id
	 * @return mixed     New invoice ID
	 */
	public function convert_to_invoice($id, $client = false, $draft_invoice = false)
	{
		// Recurring invoice date is okey lets convert it to new invoice
		$_property_request = $this->get_property_request($id);

		$new_invoice_data = [];
		if ($draft_invoice == true) {
			$new_invoice_data['save_as_draft'] = true;
		}
		$new_invoice_data['clientid']   = $_property_request->clientid;
		$new_invoice_data['project_id'] = $_property_request->project_id;
		$new_invoice_data['number']     = get_option('next_invoice_number');
		$new_invoice_data['date']       = _d($_property_request->date);
		$new_invoice_data['duedate']    = _d($_property_request->duedate);
		if (get_option('invoice_due_after') != 0) {
			$new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
		}
		$new_invoice_data['show_quantity_as'] = $_property_request->show_quantity_as;
		$new_invoice_data['currency']         = $_property_request->currency;
		$new_invoice_data['subtotal']         = $_property_request->subtotal;
		$new_invoice_data['total']            = $_property_request->total;
		$new_invoice_data['adjustment']       = $_property_request->adjustment;
		$new_invoice_data['discount_percent'] = $_property_request->discount_percent;
		$new_invoice_data['discount_total']   = $_property_request->discount_total;
		$new_invoice_data['discount_type']    = $_property_request->discount_type;
		// Since version 1.0.6
		$new_invoice_data['billing_street']   = clear_textarea_breaks($_property_request->billing_street);
		$new_invoice_data['billing_city']     = $_property_request->billing_city;
		$new_invoice_data['billing_state']    = $_property_request->billing_state;
		$new_invoice_data['billing_zip']      = $_property_request->billing_zip;
		$new_invoice_data['billing_country']  = $_property_request->billing_country;
		$new_invoice_data['shipping_street']  = clear_textarea_breaks($_property_request->shipping_street);
		$new_invoice_data['shipping_city']    = $_property_request->shipping_city;
		$new_invoice_data['shipping_state']   = $_property_request->shipping_state;
		$new_invoice_data['shipping_zip']     = $_property_request->shipping_zip;
		$new_invoice_data['shipping_country'] = $_property_request->shipping_country;

		if ($_property_request->include_shipping == 1) {
			$new_invoice_data['include_shipping'] = 1;
		}

		$new_invoice_data['show_shipping_on_invoice'] = $_property_request->show_shipping_on_contract_estimate;
		$new_invoice_data['terms']                    = get_option('predefined_terms_invoice');
		$new_invoice_data['clientnote']               = get_option('predefined_clientnote_invoice');
		// Set to unpaid status automatically
		$new_invoice_data['status']    = 1;
		$new_invoice_data['adminnote'] = '';
		$new_invoice_data['property_request_id'] = $_property_request->id;

		if(is_broker_logged_in()){
			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$new_invoice_data['is_company_admin'] = 0;
			$new_invoice_data['company_id'] = 0;
			$new_invoice_data['broker_id'] = $broker_id;
			$new_invoice_data['related_type'] = 'business_broker';
			$new_invoice_data['related_id'] = $related_id;
		}else{
			$broker_id = 0;
			$related_id = get_staff_user_id();
			$check_staff_type = rel_check_staff_type();

			$new_invoice_data['is_company_admin'] = $check_staff_type['is_company_admin'];
			$new_invoice_data['company_id'] = $check_staff_type['company_id'];
			$new_invoice_data['broker_id'] = $broker_id;
			$new_invoice_data['related_type'] = $check_staff_type['staff_type'];
			$new_invoice_data['related_id'] = $related_id;

			// Assign customers to staff to allow staff to create invoices - contracts for customers if needed
			$customer_admins = [];
			array_push($customer_admins, $related_id);

			$this->load->model('clients_model');
			$current_admins     = $this->clients_model->get_admins($_property_request->clientid);
			foreach ($current_admins as $key => $value) {
				array_push($customer_admins, (int)$value['staff_id']);
			}
			$this->clients_model->assign_admins(['customer_admins' => $customer_admins], $_property_request->clientid);
			
		}

		$time_period = $this->get_property_listing($_property_request->item_id);
		if($_property_request->request_type == 'rent'){

			if($time_period){
				$_request_term_month = $_property_request->term_month;
				if((int)$_request_term_month > 0){

					$cycles = 0;
					$repeat_type_custom = 'month';
					switch ($time_period->rental_type) {
						
						case 'day':
						$repeat_type_custom = 'day';
						$repeat_every_custom = (int)$_request_term_month;
						break;

						case 'week':
						$repeat_type_custom = 'week';
						$repeat_every_custom = (int)$_request_term_month;
						break;

						case 'month':
						$repeat_type_custom = 'month';
						$repeat_every_custom = (int)$_request_term_month;
						break;

						case 'year':
						$repeat_type_custom = 'year';
						$repeat_every_custom = (int)$_request_term_month;
						break;

						
						default:
						$repeat_type_custom = 'month';
						$repeat_every_custom = (int)$_request_term_month;
						break;
					}

					$cycles = $repeat_every_custom - 1;

					if($cycles > 0){
						$new_invoice_data['repeat_every_custom'] = 1;
						$new_invoice_data['repeat_type_custom'] = $repeat_type_custom;
						$new_invoice_data['cycles'] = $cycles;
						$new_invoice_data['recurring'] = 'custom';
					}
				}

			}

			// update next available date for property
			if( is_null($time_period->proj_completion_date) || $time_period->proj_completion_date == '' || (strtotime($time_period->proj_completion_date) < strtotime($_property_request->duedate)) ){
				$this->db->where('id', $_property_request->item_id);
				$this->db->update(db_prefix() . 'items', ['proj_completion_date' => $_property_request->duedate]);
			}
		}

		$this->load->model('payment_modes_model');
		$modes = $this->payment_modes_model->get('', [
			'expenses_only !=' => 1,
		]);
		$temp_modes = [];
		foreach ($modes as $mode) {
			if ($mode['selected_by_default'] == 0) {
				continue;
			}
			$temp_modes[] = $mode['id'];
		}
		$new_invoice_data['allowed_payment_modes'] = $temp_modes;
		$new_invoice_data['newitems']              = [];

		$item_key                                       = 1;
		$description = get_property_name($_property_request->item_id);
		$long_description = '';

		$new_invoice_data['newitems'][$item_key]['description']      = $description;
		$new_invoice_data['newitems'][$item_key]['long_description'] = $long_description;
		$new_invoice_data['newitems'][$item_key]['qty']              = 1;
		$new_invoice_data['newitems'][$item_key]['unit']             = '';
		$new_invoice_data['newitems'][$item_key]['taxname']          = [];
		$new_invoice_data['newitems'][$item_key]['rate']  = $_property_request->property_price;
		$new_invoice_data['newitems'][$item_key]['order'] = 1;


		$this->load->model('invoices_model');
		$id = $this->invoices_model->add($new_invoice_data);
		if ($id) {
			// For all cases update addefrom and sale agent from the invoice
			// May happen staff is not logged in and these values to be 0
			if(is_staff_logged_in()){
				$this->db->where('id', $id);
				$this->db->update(db_prefix() . 'invoices', [
					'addedfrom'  => $related_id,
					'sale_agent' => $related_id,
				]);
			}

			// Update estimate with the new invoice data and set to status accepted
			$this->db->where('id', $_property_request->id);
			$this->db->update(db_prefix() . 'real_requests', [
				'invoiced_date' => date('Y-m-d H:i:s'),
				'invoice_id'     => $id,
			]);

			if ($client == false) {
				$this->log_property_request_activity($_property_request->id, 'property_request_activity_converted', false, serialize([
					'<a href="' . admin_url('invoices/list_invoices/' . $id) . '">' . format_invoice_number($id) . '</a>',
				]));
			}

			hooks()->do_action('property_request_converted_to_invoice', ['invoice_id' => $id, 'request_id' => $_property_request->id]);
		}

		return $id;
	}

	/**
	 * log contract estimate estimate activity
	 * @param  [type]  $id              
	 * @param  string  $description     
	 * @param  boolean $client          
	 * @param  string  $additional_data 
	 * @return [type]                   
	 */
	public function log_property_request_activity($id, $description = '', $client = false, $additional_data = '')
	{
		$staffid   = get_staff_user_id();
		$full_name = get_staff_full_name(get_staff_user_id());
		if (DEFINED('CRON')) {
			$staffid   = '[CRON]';
			$full_name = '[CRON]';
		} elseif ($client == true) {
			$staffid   = null;
			$full_name = '';
		}

		$this->db->insert(db_prefix() . 'sales_activity', [
			'description'     => $description,
			'date'            => date('Y-m-d H:i:s'),
			'rel_id'          => $id,
			'rel_type'        => 'property_request',
			'staffid'         => $staffid,
			'full_name'       => $full_name,
			'additional_data' => $additional_data,
		]);
		$insert_id = $this->db->insert_id();
		if($insert_id){
			return true;
		}
		return false;
	}

	/**
	 * create property log for request
	 * @param  [type] $payment_id 
	 * @return [type]             
	 */
	public function create_property_log_for_request($payment_id)
	{
		$result = false;

		$this->db->where('id', $payment_id);    
		$invoicepaymentrecords = $this->db->get(db_prefix().'invoicepaymentrecords')->row();
		if($invoicepaymentrecords){
			$this->load->model('invoices_model');
			$invoice = $this->invoices_model->get($invoicepaymentrecords->invoiceid);
			$invoice_id = $invoicepaymentrecords->invoiceid;

			if(is_numeric($invoice->is_recurring_from)){
				$invoice_id = $invoice->is_recurring_from;
			}
			
			$this->db->where('invoice_id', $invoice_id);
			$property_request = $this->db->get(db_prefix().'real_requests')->row();
			
			if($property_request){
				//check if payment for invoice == order amount
				$sql_where = "SELECT sum(amount) as total_payment FROM `".db_prefix()."invoicepaymentrecords`
				WHERE invoiceid = '".$invoicepaymentrecords->invoiceid."'
				GROUP BY invoiceid;";
				$invoice_payment = $this->db->query($sql_where)->row();

				if((float)$invoice_payment->total_payment >= $property_request->total){
					$get_property_request = $this->get_property_request($property_request->id);
					if($get_property_request){
						if($get_property_request->request_type == 'buy'){
							$status = 'sold';
						}else{
							$status = 'rented';
						}

						$this->property_listing_status_mark_as($status, $get_property_request->item_id, 'property_listing');
						return true;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * real get grouped
	 * @param  string  $can_be     
	 * @param  boolean $search_all 
	 * @return [type]              
	 */
	public function real_get_grouped($can_be = '', $search_all = false)
	{
		$items = [];
		$this->db->order_by('name', 'asc');
		$groups = $this->db->get(db_prefix() . 'items_groups')->result_array();

		array_unshift($groups, [
			'id'   => 0,
			'name' => '',
		]);

		foreach ($groups as $group) {
			$this->db->select('*,' . db_prefix() . 'items_groups.name as group_name,' . db_prefix() . 'items.id as id');
			if(new_strlen($can_be) > 0){
				$this->db->where($can_be, $can_be);
			}
			
			$this->db->where('group_id', $group['id']);
			$this->db->where(db_prefix().'items.active', 1);
			$this->db->where(db_prefix().'items.can_be_property_listing = "can_be_property_listing"');
			$this->db->join(db_prefix() . 'items_groups', '' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id', 'left');
			$this->db->order_by('description', 'asc');

			$_items = $this->db->get(db_prefix() . 'items')->result_array();

			if (count($_items) > 0) {
				$items[$group['id']] = [];
				foreach ($_items as $i) {
					array_push($items[$group['id']], $i);
				}
			}
		}

		return $items;
	}

	/**
	 * property request status mark as
	 * @param  [type] $status 
	 * @param  [type] $id     
	 * @param  string $type   
	 * @return [type]         
	 */
	public function property_request_status_mark_as($status, $id, $type = 'property_request')
	{
		$status_f = false;
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_requests', ['status' => $status]);
		if ($this->db->affected_rows() > 0) {
			if($status == 4){
			// send mail to client
				$success = $this->send_property_request_to_client($id, '', true, '' , true);
			}
			return true;
		}
		return false;
	}

	/**
	 * property request pdf
	 * @param  [type] $property_request 
	 * @return [type]                   
	 */
	public function property_request_pdf($property_request) {
		return app_pdf('property_request', module_dir_path(REALESTATE_MODULE_NAME, 'libraries/pdf/Property_request_pdf.php'), $property_request);
	}

	/**
	 * send property request to client
	 * @param  [type]  $id            
	 * @param  string  $template_name 
	 * @param  boolean $attachpdf     
	 * @param  string  $cc            
	 * @param  boolean $manually      
	 * @return [type]                 
	 */
	public function send_property_request_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
	{
		$this->load->model('currencies_model');
		$property_request = $this->get_property_request($id);

		$template_name = 'property_request_send_to_client';

		$property_request_number = $property_request->code;

		$emails_sent = [];
		$send_to     = [];

		// Manually is used when sending the estimate via add/edit area button Save & Send
		if (!DEFINED('CRON') && $manually === false) {
			$send_to = $this->input->post('sent_to');
		} elseif (isset($GLOBALS['scheduled_email_contacts'])) {
			$send_to = $GLOBALS['scheduled_email_contacts'];
		} else {
			$this->load->model('clients_model');
			$contacts = $this->clients_model->get_contacts(
				$property_request->clientid,
				['active' => 1, 'estimate_emails' => 1]
			);

			foreach ($contacts as $contact) {
				array_push($send_to, $contact['id']);
			}
		}

		$status_auto_updated = false;
		$status_now          = $property_request->status;

		if (is_array($send_to) && count($send_to) > 0) {
			$i = 0;

			// Auto update status to sent in case when user sends the request is with status draft
			if ($status_now == 1) {
				$this->db->where('id', $property_request->id);
				$this->db->update(db_prefix() . 'real_requests', [
					'status' => 3,
				]);
				$status_auto_updated = true;
			}

			if ($attachpdf) {
				$_pdf_property_request = $this->get_property_request($property_request->id);

				$base_currency = $this->currencies_model->get_base_currency();
				$currency = $base_currency;
				if(is_numeric($_pdf_property_request->currency) && $_pdf_property_request->currency != 0){
					$currency = $_pdf_property_request->currency;
				}

				$_pdf_property_request->client = $this->clients_model->get($_pdf_property_request->clientid);
				$_pdf_property_request->currency = $currency;

				set_mailing_constant();
				$pdf = $this->property_request_pdf($_pdf_property_request);

				$attach = $pdf->Output($property_request_number . '.pdf', 'S');
			}

			$this->load->model('clients_model');

			foreach ($send_to as $contact_id) {
				if ($contact_id != '') {
					// Send cc only for the first contact
					if (!empty($cc) && $i > 0) {
						$cc = '';
					}

					$contact = $this->clients_model->get_contact($contact_id);

					if (!$contact) {
						continue;
					}

					$template = mail_template($template_name, 'realestate', $property_request, $contact, $cc);

					if ($attachpdf) {
						$hook = hooks()->apply_filters('send_property_request_to_customer_file_name', [
							'file_name' => str_replace('/', '-', $property_request_number . '.pdf'),
							'property_request'  => $_pdf_property_request,
						]);

						$template->add_attachment([
							'attachment' => $attach,
							'filename'   => $hook['file_name'],
							'type'       => 'application/pdf',
						]);
					}

					if ($template->send()) {

						array_push($emails_sent, $contact->email);
					}
				}
				$i++;
			}
		} else {
			return false;
		}

		if (count($emails_sent) > 0) {
			$this->set_property_request_sent($id, $emails_sent);
			hooks()->do_action('property_request_sent', $id);

			return true;
		}

		if ($status_auto_updated) {
			// Estimate not send to customer but the status was previously updated to sent now we need to revert back to draft
			$this->db->where('id', $property_request->id);
			$this->db->update(db_prefix() . 'real_requests', [
				'status' => 1,
			]);
		}

		return false;
	}

	/**
	 * set property request sent
	 * @param [type] $id          
	 * @param array  $emails_sent 
	 */
	public function set_property_request_sent($id, $emails_sent = [])
	{
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_requests', [
			'sent'     => 1,
			'datesend' => date('Y-m-d H:i:s'),
		]);

		$this->log_property_request_activity($id, 'property_request_activity_sent_to_client', false, serialize([
			'<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
		]));

		// Update estimate status to sent
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_requests', [
			'status' => 3,
		]);

	}

	/**
	 * get unpaid invoices
	 * @param  [type] $request_id 
	 * @return [type]             
	 */
	public function get_unpaid_invoices($request_id)
	{
		$this->db->select('*, ' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'invoices.id as id, ' . db_prefix() . 'currencies.name as currency_name');
		$this->db->join(db_prefix() . 'clients', db_prefix() . 'invoices.clientid=' . db_prefix() . 'clients.userid', 'left');
		$this->db->join(db_prefix() . 'currencies', '' . db_prefix() . 'currencies.id = ' . db_prefix() . 'invoices.currency', 'left');
		$this->db->where_not_in('status', [5, 2]);
		$this->db->where('total >', 0);
		$this->db->where('property_request_id', $request_id);
		$this->db->order_by('number,YEAR(date)', 'desc');
		$invoices = $this->db->get(db_prefix() . 'invoices')->result_array();
		return $invoices;
	}

	/**
	 * update auto create realestate setting
	 * @param  [type] $data 
	 * @return [type]       
	 */
	public function update_auto_create_realestate_setting($data)
	{
		$val = $data['input_name_status'] == 'true' ? 1 : 0;

		$this->db->where('name',$data['input_name']);
		$this->db->update(db_prefix() . 'options', [
			'value' => $val,
		]);
		if ($this->db->affected_rows() > 0) {
			return true;
		}else{
			return false;
		}
	}

	/**
	 * get realestate activity
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_realestate_activity($id, $rel_type = 'property')
	{
		$this->db->where('rel_id', $id);
		$this->db->where('rel_type', $rel_type);
		$this->db->order_by('date', 'asc');

		return $this->db->get(db_prefix() . 'real_activity')->result_array();
	}

	/**
	 * log realestate activity
	 * @param  [type]  $id              
	 * @param  string  $description     
	 * @param  boolean $client          
	 * @param  string  $additional_data 
	 * @return [type]                   
	 */
	public function log_realestate_activity($id, $rel_type = 'property', $description = '', $additional_data = '')
	{
		if (is_staff_logged_in()) {
			$broker_id = 0;
			$related_id = get_staff_user_id();
			$check_staff_type = rel_check_staff_type();

			$is_company_admin = $check_staff_type['is_company_admin'];
			$company_id = $check_staff_type['company_id'];
			$broker_id = $broker_id;
			$related_type = $check_staff_type['staff_type'];
			$related_id = $related_id;

		}elseif(is_broker_logged_in()){
			$broker_id = get_business_broker_id();
			$related_id = get_broker_id();

			$is_company_admin = 0;
			$company_id = 0;
			$broker_id = $broker_id;
			$related_type = 'business_broker';
			$related_id = $related_id;
		}else{
			$is_company_admin = 0;
			$company_id = 0;
			$broker_id = 0;
			$related_type = 0;
			$related_id = 0;
		}

		$this->db->insert(db_prefix() . 'real_activity', [
			'description'     => $description,
			'date'            => date('Y-m-d H:i:s'),
			'rel_id'          => $id,
			'rel_type'        => $rel_type,
			'additional_data' => $additional_data,
			'is_company_admin' => $is_company_admin,
			'company_id' => $company_id,
			'broker_id' => $broker_id,
			'related_type' => $related_type,
			'related_id' => $related_id,

		]);
	}

	/**
	 * get sale performance
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function get_sale_performance($company_id = 0)
	{
		$median_sold_price = 0;
		$properties_sold = 0;
		$properties_for_sale = 0;
		$median_leased_price = 0;
		$properties_leased = 0;
		$properties_for_rent = 0;

		if($company_id == 0){
			// get company admin
			$median_sql = '
			SELECT AVG(company_requests.contract_total) as contract_total, company_requests.request_type, COUNT(company_requests.item_id) as properties FROM (
				SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE is_company_admin = 1 AND invoice_id > 0
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			GROUP BY company_requests.request_type
			';

			$property_sql = '
			SELECT COUNT('.db_prefix().'items.id) as properties, transaction_type
			FROM  '.db_prefix().'items
				WHERE '.db_prefix().'items.is_company_admin = 1 AND (`status` = "new"  OR `status` = "active")
			GROUP BY transaction_type
			';
		}else{

			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){
				$median_sql = '
				SELECT AVG(company_requests.contract_total) as contract_total, company_requests.request_type, COUNT(company_requests.item_id) as properties FROM (
					SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE company_id = '.$company_id.' AND invoice_id > 0
					) AS company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				GROUP BY company_requests.request_type
				';

				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as properties, transaction_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' )) AND (`status` = "new"  OR `status` = "active")
				GROUP BY transaction_type
				';

			}else{
				$median_sql = '
				SELECT AVG(company_requests.contract_total) as contract_total, company_requests.request_type, COUNT(company_requests.item_id) as properties FROM (
					SELECT item_id, contract_total, broker_id, request_type FROM '.db_prefix().'real_requests WHERE broker_id = '.$company_id.' AND invoice_id > 0
					) AS company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				GROUP BY company_requests.request_type
				';

				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as properties, transaction_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.')) AND (`status` = "new"  OR `status` = "active")
				GROUP BY transaction_type
				';
			}
		}
		$median = $this->db->query($median_sql)->result_array();
		$arr_property = $this->db->query($property_sql)->result_array();
		foreach ($median as $key => $value) {
			if(isset($value['request_type']) && $value['request_type'] == 'rent'){
				$median_leased_price = $value['contract_total'];
				$properties_leased 	 = $value['properties'];
			}
			if(isset($value['request_type']) && $value['request_type'] == 'buy'){
				$median_sold_price = $value['contract_total'];
				$properties_sold	= $value['properties'];
			}
		}
		foreach ($arr_property as $key => $value) {
			if(isset($value['transaction_type']) && $value['transaction_type'] == 'Rent'){
				$properties_for_rent = $value['properties'];
			}
			if(isset($value['transaction_type']) && $value['transaction_type'] == 'Sale'){
				$properties_for_sale = $value['properties'];
			}
		}
		

		$data = [];
		$data['median_sold_price'] = $median_sold_price;
		$data['properties_sold'] = $properties_sold;
		$data['properties_for_sale'] = $properties_for_sale;
		$data['median_leased_price'] = $median_leased_price;
		$data['properties_leased'] = $properties_leased;
		$data['properties_for_rent'] = $properties_for_rent;

		return $data;
	}

	/**
	 * company property grid view
	 * @return [type] 
	 */
	public function company_property_grid_view($company_id){

		$where = [];

		if($company_id == 0){
			// get company admin
			$where[] = 'AND ('.db_prefix().'items.is_company_admin = 1 )';
		}else{
			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){
				$where[] = 'AND ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' ) )';
			}else{
				// broker
				$where[] = 'AND ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.') )';
			}
		}
		$where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';
		$where[] = 'AND '.db_prefix().'items.active = 1 AND '.db_prefix().'items.can_be_property_listing = "can_be_property_listing"';
		$commodity_ft = $this->input->post('commodity_ft');
		$can_be_value_filter = $this->input->post('can_be_value_filter');

		$group_filter = $this->input->post('group_filter');
		$itemPerPage = null !== $this->input->post('itemPerPage') ? $this->input->post('itemPerPage') : 25;

		$join = [
			'LEFT JOIN ' . db_prefix() . 'taxes t1 ON t1.id = ' . db_prefix() . 'items.tax',
			'LEFT JOIN ' . db_prefix() . 'taxes t2 ON t2.id = ' . db_prefix() . 'items.tax2',
			'LEFT JOIN ' . db_prefix() . 'items_groups ON ' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id',
		];


		if (isset($commodity_ft)) {
			$where_commodity_ft = '';
			foreach ($commodity_ft as $commodity_id) {
				if ($commodity_id != '') {
					if ($where_commodity_ft == '') {
						$where_commodity_ft .= 'AND (tblitems.id = "' . $commodity_id . '"';
					} else {
						$where_commodity_ft .= ' or tblitems.id = "' . $commodity_id . '"';
					}
				}
			}
			if ($where_commodity_ft != '') {
				$where_commodity_ft .= ')';
				array_push($where, $where_commodity_ft);
			}
		}

		if (isset($group_filter)) {
			$where_group_filter = '';
			foreach ($group_filter as $group_id) {
				if ($group_id != '') {
					if ($where_group_filter == '') {
						$where_group_filter .= 'AND ('.db_prefix().'items.group_id = "' . $group_id . '"';
					} else {
						$where_group_filter .= ' or '.db_prefix().'items.group_id = "' . $group_id . '"';
					}
				}
			}
			if ($where_group_filter != '') {
				$where_group_filter .= ')';
				array_push($where, $where_group_filter);
			}
		}

		$result = real_data_grid_init($this->input->post('page_number'), $where, $itemPerPage);
		$total_page = $this->get_total_page('can_be_property_listing', 'can_be_property_listing', 'items', $itemPerPage);

		$html = '';

		if(count($result) > 0){
			$data['properties'] = $result;
			$html = $this->load->view('companies/property_listings/utilities/listing_item', $data, true);
		}else{
			$html = 'No entries found';
		}

		return ['html' => $html, 'total_page' => $total_page];
	}

	/**
	 * staff property grid view
	 * @param  [type] $id 
	 * @return [type]     
	 */
	
	public function staff_property_grid_view($id){

		$where = [];

		$staff_in_company = rel_check_staff_in_company($id);
		if($staff_in_company){
			// staff in company
			$where[] = 'AND ( ('.db_prefix().'items.related_type = "company" AND '.db_prefix().'items.related_id = '.$id .') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$id.') )';
		}else{
			// staff not in construction company
			$where[] = 'AND (('.db_prefix().'items.related_type = "staff" AND '.db_prefix().'items.related_id = '.$id.') OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$id.') )';
		}

		$where[] = 'AND ('.db_prefix().'items.status IN ("new","active","closed_sale","pending_sale","sold","rented") )';
		$where[] = 'AND '.db_prefix().'items.active = 1 AND '.db_prefix().'items.can_be_property_listing = "can_be_property_listing"';
		$commodity_ft = $this->input->post('commodity_ft');
		$can_be_value_filter = $this->input->post('can_be_value_filter');

		$group_filter = $this->input->post('group_filter');
		$itemPerPage = null !== $this->input->post('itemPerPage') ? $this->input->post('itemPerPage') : 25;

		$join = [
			'LEFT JOIN ' . db_prefix() . 'taxes t1 ON t1.id = ' . db_prefix() . 'items.tax',
			'LEFT JOIN ' . db_prefix() . 'taxes t2 ON t2.id = ' . db_prefix() . 'items.tax2',
			'LEFT JOIN ' . db_prefix() . 'items_groups ON ' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id',
		];

		$result = real_data_grid_init($this->input->post('page_number'), $where, $itemPerPage);
		$total_page = $this->get_total_page('can_be_property_listing', 'can_be_property_listing', 'items', $itemPerPage);

		$html = '';

		if(count($result) > 0){
			$data['properties'] = $result;
			$html = $this->load->view('companies/property_listings/utilities/listing_item', $data, true);
		}else{
			$html = '<div class="col-md-12">No entries found</div>';
		}

		return ['html' => $html, 'total_page' => $total_page];
	}

	/**
	 * get_staff_sale_performance
	 * @param  integer $staff_id 
	 * @return [type]            
	 */
	public function get_staff_sale_performance($staff_id = 0)
	{
		$median_sold_price = 0;
		$properties_sold = 0;
		$properties_for_sale = 0;
		$median_leased_price = 0;
		$properties_leased = 0;
		$properties_for_rent = 0;

		$median_sql = '
		SELECT AVG(company_requests.contract_total) as contract_total, company_requests.request_type, COUNT(company_requests.item_id) as properties FROM (
			SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE related_id = '.$staff_id.' AND ('.db_prefix().'real_requests.related_type = "staff" OR '.db_prefix().'real_requests.related_type = "company" ) AND invoice_id > 0
			) AS company_requests
		LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
		GROUP BY company_requests.request_type
		';

		$property_sql = '
		SELECT COUNT('.db_prefix().'items.id) as properties, transaction_type
		FROM  '.db_prefix().'items
		WHERE (('.db_prefix().'items.related_id = '.$staff_id.' AND '.db_prefix().'items.related_type = "company") OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.staff_id = '.$staff_id.') ) AND (`status` = "new"  OR `status` = "active")
		GROUP BY transaction_type
		';
		
		$median = $this->db->query($median_sql)->result_array();
		$arr_property = $this->db->query($property_sql)->result_array();
		foreach ($median as $key => $value) {
			if(isset($value['request_type']) && $value['request_type'] == 'rent'){
				$median_leased_price = $value['contract_total'];
				$properties_leased 	 = $value['properties'];
			}
			if(isset($value['request_type']) && $value['request_type'] == 'buy'){
				$median_sold_price = $value['contract_total'];
				$properties_sold	= $value['properties'];
			}
		}
		foreach ($arr_property as $key => $value) {
			if(isset($value['transaction_type']) && $value['transaction_type'] == 'Rent'){
				$properties_for_rent = $value['properties'];
			}
			if(isset($value['transaction_type']) && $value['transaction_type'] == 'Sale'){
				$properties_for_sale = $value['properties'];
			}
		}
		

		$data = [];
		$data['median_sold_price'] = $median_sold_price;
		$data['properties_sold'] = $properties_sold;
		$data['properties_for_sale'] = $properties_for_sale;
		$data['median_leased_price'] = $median_leased_price;
		$data['properties_leased'] = $properties_leased;
		$data['properties_for_rent'] = $properties_for_rent;

		return $data;
	}

	/**
	 * get dashboard sale performance
	 * @param  integer $company id 
	 * @return [type]              
	 */
	public function get_dashboard_sale_performance($company_id = 0)
	{
		$income_sold = 0;
		$properties_for_sale = 0;
		$properties_sold = 0;
		$properties_selling = 0;

		$income_leased = 0;
		$properties_for_lease = 0;
		$properties_leased = 0;
		$properties_rent_available = 0;

		if($company_id == 0){
			// get company admin
			$median_sql = '
			SELECT SUM(company_requests.contract_total) as contract_total, company_requests.request_type, COUNT(company_requests.item_id) as properties FROM (
				SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE is_company_admin = 1 AND invoice_id > 0
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			GROUP BY company_requests.request_type
			';

			$property_sql = '
			SELECT COUNT('.db_prefix().'items.id) as properties, transaction_type
			FROM  '.db_prefix().'items
				WHERE '.db_prefix().'items.is_company_admin = 1 AND (`status` = "new"  OR `status` = "active")
			GROUP BY transaction_type
			';
		}else{

			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){
				$median_sql = '
				SELECT SUM(company_requests.contract_total) as contract_total, company_requests.request_type, COUNT(company_requests.item_id) as properties FROM (
					SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE company_id = '.$company_id.' AND invoice_id > 0
					) AS company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				GROUP BY company_requests.request_type
				';

				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as properties, transaction_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' )) AND (`status` = "new"  OR `status` = "active")
				GROUP BY transaction_type
				';

			}else{
				$median_sql = '
				SELECT SUM(company_requests.contract_total) as contract_total, company_requests.request_type, COUNT(company_requests.item_id) as properties FROM (
					SELECT item_id, contract_total, broker_id, request_type FROM '.db_prefix().'real_requests WHERE broker_id = '.$company_id.' AND invoice_id > 0
					) AS company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				GROUP BY company_requests.request_type
				';

				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as properties, transaction_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.')) AND (`status` = "new"  OR `status` = "active")
				GROUP BY transaction_type
				';
			}
		}
		$median = $this->db->query($median_sql)->result_array();
		$arr_property = $this->db->query($property_sql)->result_array();
		foreach ($median as $key => $value) {
			if(isset($value['request_type']) && $value['request_type'] == 'rent'){
				$income_leased = $value['contract_total'];
				$properties_leased 	 = $value['properties'];
			}
			if(isset($value['request_type']) && $value['request_type'] == 'buy'){
				$income_sold = $value['contract_total'];
				$properties_sold	= $value['properties'];
			}
		}
		foreach ($arr_property as $key => $value) {
			if(isset($value['transaction_type']) && $value['transaction_type'] == 'Rent'){
				$properties_rent_available = $value['properties'];
			}
			if(isset($value['transaction_type']) && $value['transaction_type'] == 'Sale'){
				$properties_selling = $value['properties'];
			}
		}
		

		$data = [];
		$data['income_sold'] = $income_sold;
		$data['properties_for_sale'] = $properties_sold + $properties_selling;
		$data['properties_sold'] = $properties_sold;
		$data['properties_selling'] = $properties_selling;
		$data['income_leased'] = $income_leased;
		$data['properties_for_lease'] = $properties_leased + $properties_rent_available;
		$data['properties_leased'] = $properties_leased;
		$data['properties_rent_available'] = $properties_rent_available;

		return $data;
	}

	/**
	 * get rent request by status
	 * @param  [type] $from_date 
	 * @param  [type] $to_date   
	 * @return [type]            
	 */
	public function get_rent_request_by_status($from_date, $to_date, $related_type, $company_id)
	{
		$request_by_status = [];
		$draft = 0;
		$submitted = 0;
		$sent = 0;
		$waiting_for_approval = 0;
		$approved = 0;
		$declined = 0;
		$complete = 0;
		$expired = 0;
		$cancelled = 0;

		if($company_id == 0){
			$request_by_status_sql = '
			SELECT count(id) as total, status FROM  '.db_prefix().'real_requests WHERE is_company_admin = 1 AND request_type = "'.$related_type.'"
			GROUP BY '.db_prefix().'real_requests.status
			';
		}else{
			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){
				$request_by_status_sql = '
				SELECT count(id) as total, status FROM  '.db_prefix().'real_requests WHERE company_id = '.$company_id.' AND request_type = "'.$related_type.'"
				GROUP BY '.db_prefix().'real_requests.status
				';
			}else{
				$request_by_status_sql = '
				SELECT count(id) as total, status FROM  '.db_prefix().'real_requests WHERE broker_id = '.$company_id.' AND request_type = "'.$related_type.'"
				GROUP BY '.db_prefix().'real_requests.status
				';
			}
		}

		$requests = $this->db->query($request_by_status_sql)->result_array();
		foreach ($requests as $key => $request) {
			switch ($request['status']) {
				case '1':
					$draft = $request['total'];
					break;
				case '9':
					$submitted = $request['total'];
					break;
				case '3':
					$sent = $request['total'];
					break;
				case '4':
					$waiting_for_approval = $request['total'];
					break;
				case '2':
					$approved = $request['total'];
					break;
				case '5':
					$declined = $request['total'];
					break;
				case '6':
					$complete = $request['total'];
					break;
				case '7':
					$expired = $request['total'];
					break;
				case '8':
					$cancelled = $request['total'];
					break;
				
				default:
					// code...
					break;
			}
		}

		$request_by_status = [
			[
				'name' => _l('real_draft'),
				'y' => (int)$draft,
			],
			[
				'name' => _l('real_submitted'),
				'y' => (int)$submitted,
			],
			[
				'name' => _l('real_sent'),
				'y' => (int)$sent,
			],
			[
				'name' => _l('real_waiting_for_approval'),
				'y' => (int)$waiting_for_approval,
			],
			[
				'name' => _l('real_approved'),
				'y' => (int)$approved,
			],
			[
				'name' => _l('real_declined'),
				'y' => (int)$declined,
			],
			[
				'name' => _l('real_complete'),
				'y' => (int)$complete,
			],
			[
				'name' => _l('real_expired'),
				'y' => (int)$expired,
			],
			[
				'name' => _l('real_cancelled'),
				'y' => (int)$cancelled,
			],
			
		];
		return $request_by_status; 
	}

	/**
	 * get rent request by property type
	 * @param  [type] $from_date    
	 * @param  [type] $to_date      
	 * @param  [type] $related_type 
	 * @return [type]               
	 */
	public function get_request_by_property_type($from_date, $to_date, $related_type, $company_id)
	{
		$request_by_status = [];

		$Housing = 0;
		$Business = 0;
		$Agriculture = 0;
		$Government = 0;

		if($company_id == 0){

		// get company admin
			$request_by_property_type_sql = '
			SELECT count(id) as total, '.db_prefix().'items.listing_type FROM (
				SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE is_company_admin = 1 AND request_type = "'.$related_type.'"
				) AS company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				GROUP BY '.db_prefix().'items.listing_type
				';

			}else{

				$company = $this->get_construction_company($company_id);
				if($company->related_type == 'company'){

					$request_by_property_type_sql = '
					SELECT count(id) as total, '.db_prefix().'items.listing_type FROM (
						SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE company_id = '.$company_id.' AND request_type = "'.$related_type.'"
						) AS company_requests
						LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
						GROUP BY '.db_prefix().'items.listing_type
						';
					}else{
						$request_by_property_type_sql = '
						SELECT count(id) as total, '.db_prefix().'items.listing_type FROM (
							SELECT item_id, contract_total, company_id, request_type FROM '.db_prefix().'real_requests WHERE broker_id = '.$company_id.' AND request_type = "'.$related_type.'"
							) AS company_requests
							LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
							GROUP BY '.db_prefix().'items.listing_type
							';
						}
					}

					$requests = $this->db->query($request_by_property_type_sql)->result_array();
					foreach ($requests as $key => $request) {
						switch ($request['listing_type']) {
							case 'Housing':
							$Housing = $request['total'];
							break;
							case 'Business':
							$Business = $request['total'];
							break;
							case 'Agriculture':
							$Agriculture = $request['total'];
							break;
							case 'Government':
							$Government = $request['total'];
							break;
							
							default:

							break;
						}
					}

					$request_by_status = [
						[
							'name' => _l('rel_Housing'),
							'y' => (int)$Housing,
						],
						[
							'name' => _l('rel_Business'),
							'y' => (int)$Business,
						],
						[
							'name' => _l('rel_Agriculture'),
							'y' => (int)$Agriculture,
						],
						[
							'name' => _l('rel_Government'),
							'y' => (int)$Government,
						],
					];
					return $request_by_status; 
				}

	/**
	 * report by listing status
	 * @param  [type] $from_date  
	 * @param  [type] $to_date    
	 * @param  [type] $company_id 
	 * @return [type]             
	 */
	public function get_report_by_listing_status($from_date, $to_date, $company_id)
	{
		$pending = 0;
		$new = 0;
		$active = 0;
		$cancelled = 0;
		$closed_sale = 0;
		$pending_sale = 0;
		$sold = 0;
		$rented = 0;
		$temp_off_market = 0;
		$withdrawn = 0;
		$expired = 0;
		$backup = 0;

		$list_result = array();

		if($company_id == 0){
			// get company admin
			$property_sql = '
			SELECT COUNT('.db_prefix().'items.id) as total, status
			FROM  '.db_prefix().'items
			WHERE '.db_prefix().'items.is_company_admin = 1
			GROUP BY status
			';
		}else{

			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){
				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as total, status
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' ))
				GROUP BY status
				';

			}else{
				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as total, status
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.'))
				GROUP BY status
				';
			}
		}
		$arr_property = $this->db->query($property_sql)->result_array();

		foreach ($arr_property as $key => $property) {
			switch ($property['status']) {
				case 'pending':
				$pending = $property['total'];
				break;
				case 'new':
				$new = $property['total'];
				break;
				case 'active':
				$active = $property['total'];
				break;
				case 'cancelled':
				$cancelled = $property['total'];
				break;
				case 'closed_sale':
				$closed_sale = $property['total'];
				break;
				case 'pending_sale':
				$pending_sale = $property['total'];
				break;
				case 'sold':
				$sold = $property['total'];
				break;
				case 'rented':
				$rented = $property['total'];
				break;
				case 'temp_off_market':
				$temp_off_market = $property['total'];
				break;
				case 'withdrawn':
				$withdrawn = $property['total'];
				break;
				case 'expired':
				$expired = $property['total'];
				break;
				case 'backup':
				$backup = $property['total'];
				break;

				default:

				break;
			}
		}

		$property_by_status = [
			[
				'name' => _l('rel_pending'),
				'y' => (int)$pending,
			],
			[
				'name' => _l('rel_new'),
				'y' => (int)$new,
			],
			[
				'name' => _l('rel_active'),
				'y' => (int)$active,
			],
			[
				'name' => _l('rel_cancelled'),
				'y' => (int)$cancelled,
			],
			[
				'name' => _l('rel_closed_sale'),
				'y' => (int)$closed_sale,
			],
			[
				'name' => _l('rel_pending_sale'),
				'y' => (int)$pending_sale,
			],
			[
				'name' => _l('rel_sold'),
				'y' => (int)$sold,
			],
			[
				'name' => _l('rel_rented'),
				'y' => (int)$rented,
			],
			[
				'name' => _l('rel_temp_off_market'),
				'y' => (int)$temp_off_market,
			],
			[
				'name' => _l('rel_withdrawn'),
				'y' => (int)$withdrawn,
			],
			[
				'name' => _l('rel_expired'),
				'y' => (int)$expired,
			],
			[
				'name' => _l('rel_backup'),
				'y' => (int)$backup,
			],

		];

		return $property_by_status;
	}

	/**
	 * get rent paid report data
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function get_rent_paid_report_data($company_id = 0)
	{
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$months_report = $this->input->post('months_report');
		if ($months_report != '') {
			$custom_date_select = '';
			if (is_numeric($months_report)) {
                // Last month
				if ($months_report == '1') {
					$beginMonth = date('Y-m-01', strtotime('first day of last month'));
					$endMonth   = date('Y-m-t', strtotime('last day of last month'));
				} else {
					$months_report = (int) $months_report;
					$months_report--;
					$beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
					$endMonth   = date('Y-m-t');
				}

				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
			} elseif ($months_report == 'this_month') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
			} elseif ($months_report == 'this_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date('Y-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
			} elseif ($months_report == 'last_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
			} elseif ($months_report == 'custom') {
				$from_date = to_sql_date($this->input->post('report_from'));
				$to_date   = to_sql_date($this->input->post('report_to'));
				if ($from_date == $to_date) {
					$custom_date_select = db_prefix() . 'real_requests.datecreated ="' . $from_date . '"';
				} else {
					$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
				}
			}
			$custom_date_select = ' AND '.$custom_date_select;
		}
		if(!isset($custom_date_select)){
			$custom_date_select = ' AND 1=1';
		}

		$rent_paid_sql = '';
		if($company_id == 0){
			// get company admin
			$rent_paid_sql ='
			SELECT '.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, company_requests.date, company_requests.duedate,  company_requests.total as contract_total, company_requests.clientid, company_requests.request_type, company_requests.item_id FROM (
				SELECT '.db_prefix().'real_requests.item_id, '.db_prefix().'real_requests.total, '.db_prefix().'real_requests.company_id, request_type, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate FROM '.db_prefix().'real_requests
				LEFT JOIN '.db_prefix().'invoices on '.db_prefix().'invoices.id= '.db_prefix().'real_requests.invoice_id
				 WHERE '.db_prefix().'real_requests.is_company_admin = 1 AND invoice_id > 0 AND '.db_prefix().'invoices.`status` = 2 AND '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.'
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'clients on company_requests.clientid = '.db_prefix().'clients.userid
			ORDER BY '.db_prefix().'items.description asc, company_requests.duedate desc
			';

		}else{

			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){

			$rent_paid_sql ='
			SELECT '.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, company_requests.date, company_requests.duedate,  company_requests.total as contract_total, company_requests.clientid, company_requests.request_type, company_requests.item_id FROM (
				SELECT '.db_prefix().'real_requests.item_id, '.db_prefix().'real_requests.total, '.db_prefix().'real_requests.company_id, request_type, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate FROM '.db_prefix().'real_requests
				LEFT JOIN '.db_prefix().'invoices on '.db_prefix().'invoices.id= '.db_prefix().'real_requests.invoice_id
				 WHERE '.db_prefix().'real_requests.company_id = '.$company_id.' AND invoice_id > 0 AND '.db_prefix().'invoices.`status` = 2 AND '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.'
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'clients on company_requests.clientid = '.db_prefix().'clients.userid
			ORDER BY '.db_prefix().'items.description asc, company_requests.duedate desc
			';

			}else{
				$rent_paid_sql ='
			SELECT '.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, company_requests.date, company_requests.duedate,  company_requests.total as contract_total, company_requests.clientid, company_requests.request_type, company_requests.item_id FROM (
				SELECT '.db_prefix().'real_requests.item_id, '.db_prefix().'real_requests.total, '.db_prefix().'real_requests.company_id, request_type, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate FROM '.db_prefix().'real_requests
				LEFT JOIN '.db_prefix().'invoices on '.db_prefix().'invoices.id= '.db_prefix().'real_requests.invoice_id
				 WHERE '.db_prefix().'real_requests.broker_id = '.$company_id.' AND invoice_id > 0 AND '.db_prefix().'invoices.`status` = 2 AND '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.'
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'clients on company_requests.clientid = '.db_prefix().'clients.userid
			ORDER BY '.db_prefix().'items.description asc, company_requests.duedate desc
			';

			}
		}

		$data = $this->db->query($rent_paid_sql)->result_array();

		$rent_paid_html = $this->load->view('companies/reports/templates/rent_paid', ['rent_paids' => $data, 'base_currency_id' => $base_currency_id], true);
		return $rent_paid_html;
	}

	/**
	 * delinquent tenants report data
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function delinquent_tenants_report_data($company_id = 0)
	{
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$months_report = $this->input->post('months_report');
		if ($months_report != '') {
			$custom_date_select = '';
			if (is_numeric($months_report)) {
                // Last month
				if ($months_report == '1') {
					$beginMonth = date('Y-m-01', strtotime('first day of last month'));
					$endMonth   = date('Y-m-t', strtotime('last day of last month'));
				} else {
					$months_report = (int) $months_report;
					$months_report--;
					$beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
					$endMonth   = date('Y-m-t');
				}

				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
			} elseif ($months_report == 'this_month') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
			} elseif ($months_report == 'this_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date('Y-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
			} elseif ($months_report == 'last_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
			} elseif ($months_report == 'custom') {
				$from_date = to_sql_date($this->input->post('report_from'));
				$to_date   = to_sql_date($this->input->post('report_to'));
				if ($from_date == $to_date) {
					$custom_date_select = db_prefix() . 'real_requests.datecreated ="' . $from_date . '"';
				} else {
					$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
				}
			}
			$custom_date_select = ' AND '.$custom_date_select;
		}
		if(!isset($custom_date_select)){
			$custom_date_select = ' AND 1=1';
		}

		$rent_paid_sql = '';
		if($company_id == 0){
			// get company admin
				
			$rent_paid_sql ='
			SELECT '.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, company_requests.date, company_requests.duedate,  company_requests.total as contract_total, company_requests.clientid, company_requests.request_type, company_requests.item_id, company_requests.invoice_id FROM (
				SELECT '.db_prefix().'real_requests.item_id, '.db_prefix().'real_requests.total, '.db_prefix().'real_requests.company_id, request_type, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate , '.db_prefix().'real_requests.invoice_id FROM '.db_prefix().'real_requests
				LEFT JOIN '.db_prefix().'invoices on '.db_prefix().'invoices.id= '.db_prefix().'real_requests.invoice_id
				WHERE '.db_prefix().'real_requests.is_company_admin = 1 AND invoice_id > 0 AND '.db_prefix().'invoices.`status` = 1 AND '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.' 
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'clients on company_requests.clientid = '.db_prefix().'clients.userid
			ORDER BY company_requests.clientid desc
			';

		}else{

			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){

			$rent_paid_sql ='
			SELECT '.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, company_requests.date, company_requests.duedate,  company_requests.total as contract_total, company_requests.clientid, company_requests.request_type, company_requests.item_id , company_requests.invoice_id FROM (
				SELECT '.db_prefix().'real_requests.item_id, '.db_prefix().'real_requests.total, '.db_prefix().'real_requests.company_id, request_type, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate, '.db_prefix().'real_requests.invoice_id FROM '.db_prefix().'real_requests
				LEFT JOIN '.db_prefix().'invoices on '.db_prefix().'invoices.id= '.db_prefix().'real_requests.invoice_id
				 WHERE '.db_prefix().'real_requests.company_id = '.$company_id.' AND invoice_id > 0 AND '.db_prefix().'invoices.`status` = 1 AND '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.'
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'clients on company_requests.clientid = '.db_prefix().'clients.userid
			ORDER BY company_requests.clientid desc
			';

			}else{
				$rent_paid_sql ='
			SELECT '.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, company_requests.date, company_requests.duedate,  company_requests.total as contract_total, company_requests.clientid, company_requests.request_type, company_requests.item_id, company_requests.invoice_id FROM (
				SELECT '.db_prefix().'real_requests.item_id, '.db_prefix().'real_requests.total, '.db_prefix().'real_requests.company_id, request_type, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'real_requests.invoice_id FROM '.db_prefix().'real_requests
				LEFT JOIN '.db_prefix().'invoices on '.db_prefix().'invoices.id= '.db_prefix().'real_requests.invoice_id
				 WHERE '.db_prefix().'real_requests.broker_id = '.$company_id.' AND invoice_id > 0 AND '.db_prefix().'invoices.`status` = 1 AND '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.'
			) AS company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'clients on company_requests.clientid = '.db_prefix().'clients.userid
			ORDER BY company_requests.clientid desc
			';

			}
		}

		$data = $this->db->query($rent_paid_sql)->result_array();
		$delinquent_tenant_html = $this->load->view('companies/reports/templates/delinquent_tenants', ['delinquent_tenants' => $data, 'base_currency_id' => $base_currency_id], true);
		return $delinquent_tenant_html;
	}

	/**
	 * vacant rental property report data
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function vacant_rental_property_report_data($company_id = 0)
	{
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$months_report = $this->input->post('months_report');
		if ($months_report != '') {
			$custom_date_select = '';
			if (is_numeric($months_report)) {
                // Last month
				if ($months_report == '1') {
					$beginMonth = date('Y-m-01', strtotime('first day of last month'));
					$endMonth   = date('Y-m-t', strtotime('last day of last month'));
				} else {
					$months_report = (int) $months_report;
					$months_report--;
					$beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
					$endMonth   = date('Y-m-t');
				}

				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
			} elseif ($months_report == 'this_month') {
				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
			} elseif ($months_report == 'this_year') {
				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' .
				date('Y-m-d', strtotime(date('Y-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
			} elseif ($months_report == 'last_year') {
				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
			} elseif ($months_report == 'custom') {
				$from_date = to_sql_date($this->input->post('report_from'));
				$to_date   = to_sql_date($this->input->post('report_to'));
				if ($from_date == $to_date) {
					$custom_date_select = db_prefix() . 'items.proj_completion_date ="' . $from_date . '"';
				} else {
					$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
				}
			}
			$custom_date_select = ' AND '.$custom_date_select;
		}
		if(!isset($custom_date_select)){
			$custom_date_select = ' AND 1=1';
		}

		$property_sql = '';
		if($company_id == 0){
			$property_sql = '
			SELECT '.db_prefix().'items.id ,  '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'items.proj_completion_date, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.rent_price, '.db_prefix().'items.rental_type
			FROM  '.db_prefix().'items
			WHERE  '.db_prefix().'items.is_company_admin = 1 AND (`status` = "new"  OR `status` = "active") AND transaction_type = "Rent" '.$custom_date_select.'
			ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
			';

		}else{
			$company = $this->get_construction_company($company_id);

			if($company->related_type == 'company'){
				$property_sql = '
				SELECT '.db_prefix().'items.id ,  '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'items.proj_completion_date, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.rent_price, '.db_prefix().'items.rental_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' )) AND (`status` = "new"  OR `status` = "active") AND transaction_type = "Rent" '.$custom_date_select.'
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';

			}else{
				$property_sql = '
				SELECT '.db_prefix().'items.id ,  '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'items.proj_completion_date, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.rent_price, '.db_prefix().'items.rental_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.')) AND (`status` = "new"  OR `status` = "active") AND transaction_type = "Rent" '.$custom_date_select.'
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';
			}
		}
		$data = $this->db->query($property_sql)->result_array();
		$vacant_rental_property_html = $this->load->view('companies/reports/templates/vacant_rental', ['vacant_rentals' => $data, 'base_currency_id' => $base_currency_id], true);
		return $vacant_rental_property_html;
	}

	/**
	 * vacant sale property report data
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function vacant_sale_property_report_data($company_id = 0)
	{
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$months_report = $this->input->post('months_report');
		if ($months_report != '') {
			$custom_date_select = '';
			if (is_numeric($months_report)) {
                // Last month
				if ($months_report == '1') {
					$beginMonth = date('Y-m-01', strtotime('first day of last month'));
					$endMonth   = date('Y-m-t', strtotime('last day of last month'));
				} else {
					$months_report = (int) $months_report;
					$months_report--;
					$beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
					$endMonth   = date('Y-m-t');
				}

				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
			} elseif ($months_report == 'this_month') {
				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
			} elseif ($months_report == 'this_year') {
				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' .
				date('Y-m-d', strtotime(date('Y-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
			} elseif ($months_report == 'last_year') {
				$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
			} elseif ($months_report == 'custom') {
				$from_date = to_sql_date($this->input->post('report_from'));
				$to_date   = to_sql_date($this->input->post('report_to'));
				if ($from_date == $to_date) {
					$custom_date_select = db_prefix() . 'items.proj_completion_date ="' . $from_date . '"';
				} else {
					$custom_date_select = '(' . db_prefix() . 'items.proj_completion_date BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
				}
			}
			$custom_date_select = ' AND '.$custom_date_select;
		}
		if(!isset($custom_date_select)){
			$custom_date_select = ' AND 1=1';
		}

		$property_sql = '';

		if($company_id == 0){
			$property_sql = '
			SELECT '.db_prefix().'items.id ,  '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'items.proj_completion_date, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.rate, '.db_prefix().'items.rental_type
			FROM  '.db_prefix().'items
			WHERE '.db_prefix().'items.is_company_admin = 1 AND (`status` = "new"  OR `status` = "active") AND transaction_type = "Sale" '.$custom_date_select.'
			ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
			';

		}else{
			$company = $this->get_construction_company($company_id);

			if($company->related_type == 'company'){
				$property_sql = '
				SELECT '.db_prefix().'items.id ,  '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'items.proj_completion_date, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.rate, '.db_prefix().'items.rental_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' )) AND (`status` = "new"  OR `status` = "active") AND transaction_type = "Sale" '.$custom_date_select.'
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';

			}else{
				$property_sql = '
				SELECT '.db_prefix().'items.id ,  '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'items.proj_completion_date, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.rate, '.db_prefix().'items.rental_type
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.')) AND (`status` = "new"  OR `status` = "active") AND transaction_type = "Sale" '.$custom_date_select.'
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';
			}
		}
		$data = $this->db->query($property_sql)->result_array();
		$vacant_sale_property_html = $this->load->view('companies/reports/templates/vacant_sale_property', ['vacant_sales' => $data, 'base_currency_id' => $base_currency_id], true);
		return $vacant_sale_property_html;
	}

	/**
	 * unit property rental report data
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function unit_property_rental_report_data($company_id = 0)
	{
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$months_report = $this->input->post('months_report');
		if ($months_report != '') {
			$custom_date_select = '';
			if (is_numeric($months_report)) {
                // Last month
				if ($months_report == '1') {
					$beginMonth = date('Y-m-01', strtotime('first day of last month'));
					$endMonth   = date('Y-m-t', strtotime('last day of last month'));
				} else {
					$months_report = (int) $months_report;
					$months_report--;
					$beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
					$endMonth   = date('Y-m-t');
				}

				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
			} elseif ($months_report == 'this_month') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
			} elseif ($months_report == 'this_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date('Y-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
			} elseif ($months_report == 'last_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
			} elseif ($months_report == 'custom') {
				$from_date = to_sql_date($this->input->post('report_from'));
				$to_date   = to_sql_date($this->input->post('report_to'));
				if ($from_date == $to_date) {
					$custom_date_select = db_prefix() . 'real_requests.datecreated ="' . $from_date . '"';
				} else {
					$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
				}
			}
			$custom_date_select = ' AND '.$custom_date_select;
		}
		if(!isset($custom_date_select)){
			$custom_date_select = ' AND 1=1';
		}

		$property_sql = '';
		if($company_id == 0){
			$property_sql = '
			SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id FROM (
			SELECT item_id, MAX(id) as request_id, request_type FROM '.db_prefix().'real_requests WHERE '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.' 
			GROUP BY item_id
			) as company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'real_requests ON company_requests.request_id= '.db_prefix().'real_requests.id
			LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
			
			WHERE '.db_prefix().'items.is_company_admin = 1 AND ('.db_prefix().'items.status = "rented") AND '.db_prefix().'items.transaction_type = "Rent"
			ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
			';
		}else{
			$company = $this->get_construction_company($company_id);

			if($company->related_type == 'company'){
				$property_sql = '
				SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id FROM (
				SELECT item_id, MAX(id) as request_id, request_type FROM '.db_prefix().'real_requests WHERE '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.' 
				GROUP BY item_id
				) as company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				LEFT JOIN '.db_prefix().'real_requests ON company_requests.request_id= '.db_prefix().'real_requests.id
				LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
				
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' )) AND ('.db_prefix().'items.status = "rented") AND '.db_prefix().'items.transaction_type = "Rent"
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';
			}else{
				$property_sql = '
				SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id FROM (
				SELECT item_id, MAX(id) as request_id, request_type FROM '.db_prefix().'real_requests WHERE '.db_prefix().'real_requests.request_type = "rent" '.$custom_date_select.' 
				GROUP BY item_id
				) as company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				LEFT JOIN '.db_prefix().'real_requests ON company_requests.request_id= '.db_prefix().'real_requests.id
				LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
				
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.')) AND ('.db_prefix().'items.status = "rented") AND '.db_prefix().'items.transaction_type = "Rent"
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';

			}
		}
		$data = $this->db->query($property_sql)->result_array();
		$unit_property_rental_html = $this->load->view('companies/reports/templates/unit_property_rental', ['property_rentals' => $data, 'base_currency_id' => $base_currency_id], true);
		return $unit_property_rental_html;
	}

	/**
	 * unit property sold report data
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function unit_property_sold_report_data($company_id = 0)
	{
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$months_report = $this->input->post('months_report');
		if ($months_report != '') {
			$custom_date_select = '';
			if (is_numeric($months_report)) {
                // Last month
				if ($months_report == '1') {
					$beginMonth = date('Y-m-01', strtotime('first day of last month'));
					$endMonth   = date('Y-m-t', strtotime('last day of last month'));
				} else {
					$months_report = (int) $months_report;
					$months_report--;
					$beginMonth = date('Y-m-01', strtotime("-$months_report MONTH"));
					$endMonth   = date('Y-m-t');
				}

				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $beginMonth . '" AND "' . $endMonth . '")';
			} elseif ($months_report == 'this_month') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . date('Y-m-01') . '" AND "' . date('Y-m-t') . '")';
			} elseif ($months_report == 'this_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date('Y-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date('Y-12-31'))) . '")';
			} elseif ($months_report == 'last_year') {
				$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01'))) .
				'" AND "' .
				date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31'))) . '")';
			} elseif ($months_report == 'custom') {
				$from_date = to_sql_date($this->input->post('report_from'));
				$to_date   = to_sql_date($this->input->post('report_to'));
				if ($from_date == $to_date) {
					$custom_date_select = db_prefix() . 'real_requests.datecreated ="' . $from_date . '"';
				} else {
					$custom_date_select = '(' . db_prefix() . 'real_requests.datecreated BETWEEN "' . $from_date . '" AND "' . $to_date . '")';
				}
			}
			$custom_date_select = ' AND '.$custom_date_select;
		}
		if(!isset($custom_date_select)){
			$custom_date_select = ' AND 1=1';
		}

		$property_sql = '';
		if($company_id == 0){
			$property_sql = '
			SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id, '.db_prefix().'items.date_sold FROM (
			SELECT item_id, MAX(id) as request_id, request_type FROM '.db_prefix().'real_requests WHERE '.db_prefix().'real_requests.request_type = "buy"  '.$custom_date_select.'
			GROUP BY item_id
			) as company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'real_requests ON company_requests.request_id= '.db_prefix().'real_requests.id
			LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
			
			WHERE '.db_prefix().'items.is_company_admin = 1 AND ('.db_prefix().'items.status = "sold") AND '.db_prefix().'items.transaction_type = "Sale"
			ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
			';
		}else{
			$company = $this->get_construction_company($company_id);

			if($company->related_type == 'company'){
				$property_sql = '
				SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id, '.db_prefix().'items.date_sold FROM (
				SELECT item_id, MAX(id) as request_id, request_type FROM '.db_prefix().'real_requests WHERE '.db_prefix().'real_requests.request_type = "buy"  '.$custom_date_select.'
				GROUP BY item_id
				) as company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				LEFT JOIN '.db_prefix().'real_requests ON company_requests.request_id= '.db_prefix().'real_requests.id
				LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
				
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' )) AND ('.db_prefix().'items.status = "sold") AND '.db_prefix().'items.transaction_type = "Sale"
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';
			}else{
				$property_sql = '
				SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id, '.db_prefix().'items.date_sold FROM (
				SELECT item_id, MAX(id) as request_id, request_type FROM '.db_prefix().'real_requests WHERE '.db_prefix().'real_requests.request_type = "buy"  '.$custom_date_select.'
				GROUP BY item_id
				) as company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				LEFT JOIN '.db_prefix().'real_requests ON company_requests.request_id= '.db_prefix().'real_requests.id
				LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
				
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.')) AND ('.db_prefix().'items.status = "sold") AND '.db_prefix().'items.transaction_type = "Sale"
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';

			}
		}

		$data = $this->db->query($property_sql)->result_array();

		$sold_paid_html = $this->load->view('companies/reports/templates/unit_property_sold', ['unit_property_solds' => $data, 'base_currency_id' => $base_currency_id], true);
		return $sold_paid_html;
	}

	/**
	 * leases ending repor data
	 * @param  integer $company_id 
	 * @return [type]              
	 */
	public function leases_ending_repor_data($company_id = 0)
	{
		$get_base_currency =  get_base_currency();
		if($get_base_currency){
			$base_currency_id = $get_base_currency->id;
		}else{
			$base_currency_id = 0;
		}
		$current_date = date('Y-m-d');
		if($company_id == 0){
			$property_sql = '
			SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id, '.db_prefix().'items.date_sold, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.street_number, '.db_prefix().'items.street_dir_pre, '.db_prefix().'items.street_name, '.db_prefix().'items.city, '.db_prefix().'items.state, '.db_prefix().'items.country FROM (
				SELECT item_id, request_type,id 
				FROM '.db_prefix().'real_requests 
				WHERE '.db_prefix().'real_requests.is_company_admin = 1 AND '.db_prefix().'real_requests.request_type = "rent"
				AND DATE(duedate) < "'.$current_date.'" AND status != 1
				) as company_requests
			LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
			LEFT JOIN '.db_prefix().'real_requests ON company_requests.id= '.db_prefix().'real_requests.id
			LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
			ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
			';
		}else{
			$company = $this->get_construction_company($company_id);

			if($company->related_type == 'company'){
				$property_sql = '
				SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id, '.db_prefix().'items.date_sold, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.street_number, '.db_prefix().'items.street_dir_pre, '.db_prefix().'items.street_name, '.db_prefix().'items.city, '.db_prefix().'items.state, '.db_prefix().'items.country FROM (
					SELECT item_id, request_type,id 
					FROM '.db_prefix().'real_requests 
					WHERE '.db_prefix().'real_requests.company_id = '.$company_id.' AND '.db_prefix().'real_requests.request_type = "rent"
					AND DATE(duedate) < "'.$current_date.'" AND status != 1
					) as company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				LEFT JOIN '.db_prefix().'real_requests ON company_requests.id= '.db_prefix().'real_requests.id
				LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';
			}else{
				$property_sql = '
				SELECT 	'.db_prefix().'clients.company, '.db_prefix().'items.description, '.db_prefix().'items.listing_type, '.db_prefix().'items.commodity_code, '.db_prefix().'items.use_code, '.db_prefix().'real_requests.date, '.db_prefix().'real_requests.duedate,  '.db_prefix().'items.proj_completion_date, '.db_prefix().'real_requests.total as contract_total, '.db_prefix().'real_requests.clientid, '.db_prefix().'real_requests.request_type, '.db_prefix().'real_requests.item_id, '.db_prefix().'items.date_sold, '.db_prefix().'items.beds, '.db_prefix().'items.full_baths, '.db_prefix().'items.lot_size_acres, '.db_prefix().'items.street_number, '.db_prefix().'items.street_dir_pre, '.db_prefix().'items.street_name, '.db_prefix().'items.city, '.db_prefix().'items.state, '.db_prefix().'items.country FROM (
					SELECT item_id, request_type,id 
					FROM '.db_prefix().'real_requests 
					WHERE '.db_prefix().'real_requests.broker_id = '.$company_id.' AND '.db_prefix().'real_requests.request_type = "rent"
					AND DATE(duedate) < "'.$current_date.'" AND status != 1
					) as company_requests
				LEFT JOIN '.db_prefix().'items on company_requests.item_id = '.db_prefix().'items.id
				LEFT JOIN '.db_prefix().'real_requests ON company_requests.id= '.db_prefix().'real_requests.id
				LEFT JOIN '.db_prefix().'clients on '.db_prefix().'real_requests.clientid = '.db_prefix().'clients.userid
				ORDER BY '.db_prefix().'items.description asc, '.db_prefix().'items.listing_type desc
				';
			}
		}

		$data = $this->db->query($property_sql)->result_array();
		$leases_ending_html = $this->load->view('companies/reports/templates/leases_ending', ['leases_endings' => $data, 'base_currency_id' => $base_currency_id], true);
		return $leases_ending_html;
	}

	/**
	 * report by top city listing
	 * @param  string $from_date 
	 * @param  string $to_date   
	 * @return [type]            
	 */
	public function report_by_top_city_listing($from_date = '', $to_date = '', $company_id = 0)
	{
		$chart = [];
		$categories = [];
		$color_data = ['#a4d17a', '#225b8', '#be608b', '#96b00c', '#088baf',
		'#63b598', '#ce7d78', '#ea9e70' ,'#a48a9e', '#c6e1e8', '#648177' ,'#0d5ac1','#00FF7F', '#0cffe95c','#80da22','#f37b15','#da1818','#176cea','#5be4f0', '#57c4d8', '#d2737d' ,'#c0a43c' ,'#f2510e' ,'#651be6' ,'#79806e' ,'#61da5e' ,'#cd2f00' ];

		if($company_id == 0){
			// get company admin
			$property_sql = '
			SELECT COUNT('.db_prefix().'items.id) as total_item, city
			FROM  '.db_prefix().'items
			WHERE '.db_prefix().'items.is_company_admin = 1
			GROUP BY city
			LIMIT 20
			';
		}else{

			$company = $this->get_construction_company($company_id);
			if($company->related_type == 'company'){
				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as total_item, city
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.company_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.company_id = '.$company_id.' ))
				GROUP BY city
				LIMIT 20

				';

			}else{
				$property_sql = '
				SELECT COUNT('.db_prefix().'items.id) as total_item, city
				FROM  '.db_prefix().'items
				WHERE ('.db_prefix().'items.broker_id = '.$company_id.' OR '.db_prefix().'items.id IN ( SELECT item_id FROM '.db_prefix().'real_request_brokerages WHERE '.db_prefix().'real_request_brokerages.broker_id = '.$company_id.'))
				GROUP BY city
				LIMIT 20

				';
			}
		}
		$arr_property = $this->db->query($property_sql)->result_array();

		$color_index=0;

		foreach ($arr_property as $key => $value) {
			if(new_strlen($value['city']) > 0){
				$categories[] = $value['city'];
			}else{
				$categories[] = 'other';
			}

			$chart[] = (int)$value['total_item'];
			$color_index++;
		}

		return ['chart' => $chart, 'categories' => $categories];
	}

	/*end file*/
}