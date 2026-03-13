<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Broker_model extends App_Model
{

	/**
	 * get broker staff
	 * @param  string $id    
	 * @param  array  $where 
	 * @return [type]        
	 */
	public function get_broker_staff($id = '', $where = [])
	{
		$select_str = '*,CONCAT(firstname,\' \',lastname) as full_name';

		// Used to prevent multiple queries on logged in staff to check the total unread notifications in core/AdminController.php
		if (is_broker_logged_in() && $id != '' && $id == get_broker_id()) {
			$select_str .= ',(SELECT COUNT(*) FROM ' . db_prefix() . 'real_notifications WHERE touserid=' . get_broker_id() . ' and isread=0) as total_unread_notifications, (SELECT COUNT(*) FROM ' . db_prefix() . 'todos WHERE finished=0 AND staffid=' . get_broker_id() . ') as total_unfinished_todos';
		}

		$this->db->select($select_str);
		$this->db->where($where);

		if (is_numeric($id)) {
			$this->db->where('id', $id);
			$staff = $this->db->get(db_prefix() . 'real_broker_staffs')->row();

			return $staff;
		}
		$this->db->order_by('firstname', 'desc');

		return $this->db->get(db_prefix() . 'real_broker_staffs')->result_array();
	}

	/**
	 * Add new staff member
	 * @param array $data staff $_POST data
	 */
	public function add($data)
	{
		if (isset($data['fakeusernameremembered'])) {
			unset($data['fakeusernameremembered']);
		}
		if (isset($data['fakepasswordremembered'])) {
			unset($data['fakepasswordremembered']);
		}

		// First check for all cases if the email exists.
		$data = hooks()->apply_filters('before_create_broker_staff_member', $data);

		$this->db->where('email', $data['email']);
		$email = $this->db->get(db_prefix() . 'real_broker_staffs')->row();

		if ($email) {
			die('Email already exists');
		}

		$send_welcome_email = true;
		$original_password  = $data['password'];
		if (!isset($data['send_welcome_email'])) {
			$send_welcome_email = false;
		} else {
			unset($data['send_welcome_email']);
		}

		$data['password']    = app_hash_password($data['password']);

		$this->db->insert(db_prefix() . 'real_broker_staffs', $data);
		$staffid = $this->db->insert_id();
		if ($staffid) {
			$slug = $data['firstname'] . ' ' . $data['lastname'];

			if ($slug == ' ') {
				$slug = 'unknown-' . $staffid;
			}

			if ($send_welcome_email == true) {
				send_mail_template('staff_created', $data['email'], $staffid, $original_password);
			}

			$this->db->where('id', $staffid);
			$this->db->update(db_prefix() . 'real_broker_staffs', [
				'media_path_slug' => slug_it($slug),
			]);

			if (isset($custom_fields)) {
				handle_custom_fields_post($staffid, $custom_fields);
			}

			log_activity('New Broker Staff Member Added [ID: ' . $staffid . ', ' . $data['firstname'] . ' ' . $data['lastname'] . ']');

			hooks()->do_action('broker_staff_member_created', $staffid);

			return $staffid;
		}

		return false;
	}

	/**
	 * Update staff member info
	 * @param  array $data staff data
	 * @param  mixed $id   staff id
	 * @return boolean
	 */
	public function update($data, $id)
	{
		if (isset($data['fakeusernameremembered'])) {
			unset($data['fakeusernameremembered']);
		}
		if (isset($data['fakepasswordremembered'])) {
			unset($data['fakepasswordremembered']);
		}

		$data = hooks()->apply_filters('before_update_broker_staff_member', $data, $id);


		$affectedRows = 0;

		if (empty($data['password'])) {
			unset($data['password']);
		} else {
			$data['password']             = app_hash_password($data['password']);
			$data['last_password_change'] = date('Y-m-d H:i:s');
		}

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'real_broker_staffs', $data);

		if ($this->db->affected_rows() > 0) {
			$affectedRows++;
		}

		if ($affectedRows > 0) {
			hooks()->do_action('broker_staff_member_updated', $id);
			log_activity('Broker Staff Member Updated [ID: ' . $id . ', ' . $data['firstname'] . ' ' . $data['lastname'] . ']');

			return true;
		}

		return false;
	}

	/**
	 * Change staff passwordn
	 * @param  mixed $data   password data
	 * @param  mixed $userid staff id
	 * @return mixed
	 */
	public function change_password($data, $userid)
	{
		$data = hooks()->apply_filters('before_broker_change_password', $data, $userid);

		$member = $this->get_broker_staff($userid);
		// CHeck if member is active
		if ($member->active == 0) {
			return [
				[
					'memberinactive' => true,
				],
			];
		}

		// Check new old password
		if (!app_hasher()->CheckPassword($data['oldpassword'], $member->password)) {
			return [
				[
					'passwordnotmatch' => true,
				],
			];
		}

		$data['newpasswordr'] = app_hash_password($data['newpasswordr']);

		$this->db->where('id', $userid);
		$this->db->update(db_prefix() . 'real_broker_staffs', [
			'password'             => $data['newpasswordr'],
			'last_password_change' => date('Y-m-d H:i:s'),
		]);
		if ($this->db->affected_rows() > 0) {
			log_activity('Broker Staff Password Changed [' . $userid . ']');

			return true;
		}

		return false;
	}

	
	/**
	 * get contracts about to expire
	 * @param  [type]  $staffId 
	 * @param  integer $days    
	 * @return [type]           
	 */
	public function get_contracts_about_to_expire($staffId = null, $days = 7)
	{
		$diff1 = date('Y-m-d', strtotime('-' . $days . ' days'));
		$diff2 = date('Y-m-d', strtotime('+' . $days . ' days'));

		$this->db->where('broker_id', $staffId);
		$this->db->select('id,subject,client,datestart,dateend');

		$this->db->where('dateend IS NOT NULL');
		$this->db->where('trash', 0);
		$this->db->where('dateend >=', $diff1);
		$this->db->where('dateend <=', $diff2);

		return $this->db->get(db_prefix() . 'contracts')->result_array();
	}

	/**
	 * Get contract types data for chart
	 * @return array
	 */
	public function get_contracts_types_chart_data()
	{
		return $this->get_chart_data();
	}

	/**
	* Get contract types values for chart
	* @return array
	*/
	public function get_contracts_types_values_chart_data()
	{
		return $this->get_values_chart_data();
	}

	/**
	 * Get contract types data for chart
	 * @return array
	 */
	public function get_chart_data()
	{
		$labels = [];
		$totals = [];
		$types  = $this->get_contract_type();
		foreach ($types as $type) {
			$total_rows_where = [
				'contract_type' => $type['id'],
				'trash'         => 0,
			];

			$total_rows_where['broker_id'] = get_business_broker_id();
			
			$total_rows = total_rows(db_prefix().'contracts', $total_rows_where);
			if ($total_rows == 0 && is_client_logged_in()) {
				continue;
			}
			array_push($labels, $type['name']);
			array_push($totals, $total_rows);
		}
		$chart = [
			'labels'   => $labels,
			'datasets' => [
				[
					'label'           => _l('contract_summary_by_type'),
					'backgroundColor' => 'rgba(3,169,244,0.2)',
					'borderColor'     => '#03a9f4',
					'borderWidth'     => 1,
					'data'            => $totals,
				],
			],
		];

		return $chart;
	}

	/**
	 * Get contract types values for chart
	 * @return array
	 */
	public function get_values_chart_data()
	{
		$labels = [];
		$totals = [];
		$types  = $this->get_contract_type();
		foreach ($types as $type) {
			array_push($labels, $type['name']);

			$where = [
				'where' => [
					'contract_type' => $type['id'],
					'trash'         => 0,
				],
				'field' => 'contract_value',
			];

			$where['where']['broker_id'] = get_business_broker_id();

			$total = sum_from_table(db_prefix().'contracts', $where);
			if ($total == null) {
				$total = 0;
			}
			array_push($totals, $total);
		}
		$chart = [
			'labels'   => $labels,
			'datasets' => [
				[
					'label'           => _l('contract_summary_by_type_value'),
					'backgroundColor' => 'rgba(37,155,35,0.2)',
					'borderColor'     => '#84c529',
					'tension'         => false,
					'borderWidth'     => 1,
					'data'            => $totals,
				],
			],
		];

		return $chart;
	}

	/**
	 * @param  integer ID (optional)
	 * @return mixed
	 * Get contract type object based on passed id if not passed id return array of all types
	 */
	public function get_contract_types($id = '')
	{
		return $this->get_contract_type($id);
	}

	/**
     * @param  integer ID (optional)
     * @return mixed
     * Get contract type object based on passed id if not passed id return array of all types
     */
	public function get_contract_type($id = '')
	{
		if (is_numeric($id)) {
			$this->db->where('id', $id);

			return $this->db->get(db_prefix().'contracts_types')->row();
		}
		$this->db->where('broker_id', get_business_broker_id());
		$types = $this->db->get(db_prefix().'contracts_types')->result_array();

		return $types;
	}

    /**
     * Select unique contracts years
     * @return array
     */
    public function get_contracts_years()
    {
    	return $this->db->query('SELECT DISTINCT(YEAR(datestart)) as year FROM ' . db_prefix() . 'contracts WHERE broker_id = '.get_business_broker_id())->result_array();
    }

    /**
     * Get the invoices years
     *
     * @return array
     */
    public function get_invoices_years()
    {
    	return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'invoices ORDER BY year DESC')->result_array();
    }

    public function get_sale_agents()
    {
    	return $this->db->query('SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, \' \', lastname) as full_name FROM ' . db_prefix() . 'invoices JOIN ' . db_prefix() . 'staff ON ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'invoices.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * process payment
     * @param  [type] $data      
     * @param  string $invoiceid 
     * @return [type]            
     */
	public function process_payment($data, $invoiceid = '')
	{
			$this->load->model('payments_model');

	    // Offline payment mode from the admin side
		if (is_numeric($data['paymentmode'])) {
			if (is_broker_logged_in()) {
				$id = $this->payments_model->add($data);

				return $id;
			}

			return false;

	    // Is online payment mode request by client or staff
		} elseif (!is_numeric($data['paymentmode']) && !empty($data['paymentmode'])) {
	        // This request will come from admin area only
	        // If admin clicked the button that dont want to pay the invoice from the getaways only want
			if (is_broker_logged_in()) {
				if (isset($data['do_not_redirect'])) {
					$id = $this->payments_model->add($data);

					return $id;
				}
			}

			if (!is_numeric($invoiceid)) {
				if (!isset($data['invoiceid'])) {
					die('No invoice specified');
				}
				$invoiceid = $data['invoiceid'];
			}

			if (isset($data['do_not_send_email_template'])) {
				unset($data['do_not_send_email_template']);
				$this->session->set_userdata([
					'do_not_send_email_template' => true,
				]);
			}

			$invoice = $this->invoices_model->get($invoiceid);
	        // Check if request coming from admin area and the user added note so we can insert the note also when the payment is recorded
			if (isset($data['note']) && $data['note'] != '') {
				$this->session->set_userdata([
					'payment_admin_note' => $data['note'],
				]);
			}

			if (get_option('allow_payment_amount_to_be_modified') == 0) {
				$data['amount'] = get_invoice_total_left_to_pay($invoiceid, $invoice->total);
			}

			$data['invoiceid'] = $invoiceid;
			$data['invoice']   = $invoice;
			$data              = hooks()->apply_filters('before_process_gateway_func', $data);

			$this->load->model('payment_modes_model');
			$gateway = $this->payment_modes_model->get($data['paymentmode']);
			$data['gateway_fee'] = $gateway->instance->getFee($data['amount']);

			$this->load->model('payment_attempts_model');

			$data['payment_attempt'] = $this->payment_attempts_model->add([
				'reference' => app_generate_hash(),
				'amount' => $data['amount'],
				'fee' => $data['gateway_fee'],
				'invoice_id' => $data['invoiceid'],
				'payment_gateway' => $gateway->instance->getId()
			]);

			$data['amount']     += $data['gateway_fee'];
			$gateway->instance->process_payment($data);
		}

		return false;
	}

}