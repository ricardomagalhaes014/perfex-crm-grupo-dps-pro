<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Quickbooks extends AdminController {

	public function __construct() {
		parent::__construct();
		$this->load->model('clients_model');
		$this->load->model('payments_model');
		$this->load->model('invoices_model');
		$this->load->model('invoice_items_model');
		$this->app_css->add('quickbooks-css', module_dir_url('quickbooks', 'assets/css/quickbooks.css') . '?v=' . $this->app_css->core_version(), 'admin', ['app-css']);
		if (get_option('qb_access_token') && time() > get_option('qb_expiration_date')) {
			set_alert('warning', _l('quickbooks') . ' needs reconnecting');
			redirect(admin_url());
		}
	}

	public function index() {
		if (has_permission('quickbooks', '', 'view')) {
			$data['accounts'] = $this->quickbooks_model->get_qb_accounts('*');
			$this->load->view('quickbooks', $data, FALSE);
		}
	}

/*==========================================================
//  Chart of accounts
//==========================================================*/

	public function synchallaccounts() {

		$bg_process = false;
		if ($this->input->get('bg_process', true) !== null) {
			$bg_process = $this->input->get('bg_process', true);
		}

		if ($this->input->is_ajax_request() || $bg_process) {
			$result = $this->quickbooks_model->qb_synch_accounts();

			if ($result) {
				//Run Expense Catgories sync:
				//$this->quickbooks_model->qb_payment_method();
				//$this->quickbooks_model->qb_expense_category();

				echo json_encode(array(
					'success' => true,
					'message' => _l('qb_chart_of_acc_sync_success'),
				));
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_chart_of_acc_sync_fail'),
				));
			}
		}

		return true;
	}

/*==========================================================
//  CUSTOMER
//==========================================================*/

	public function synchallclients() {
		$bg_process = false;
		if ($this->input->get('bg_process', true) !== null) {
			$bg_process = $this->input->get('bg_process', true);
		}

		if ($this->input->is_ajax_request() || $bg_process) {
			$clients = $this->clients_model->get();

			if (count($clients) > 0) {
				foreach ($clients as $client) {
					if (total_rows(db_prefix() . 'contacts', ['active' => 1, 'userid' => $client['userid']]) > 0) {
						$success = $this->quickbooks_model->qb_synch_client($client);
						if (!$success) {
							echo json_encode(array(
								'success' => false,
								'message' => _l('qb_clients_sync_fail'),
							));

							return;
						}
					}
				}

				echo json_encode(array(
					'success' => true,
					'message' => _l('qb_clients_sync_success'),
				));
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_clients_sync_not_found'),
				));
			}
			die;
		}
		return true;
	}

	public function get_qb_clients($select = "") {
		if (!empty($select)) {
			$this->db->select($select);
		}

		$result = $this->db->where('qb_id >', 0)->get(db_prefix() . 'clients')->result();

		echo json_encode($result);
	}

/*==========================================================
//  ITEMS
//==========================================================*/

	public function synchallitems() {

		$bg_process = false;
		if ($this->input->get('bg_process', true) !== null) {
			$bg_process = $this->input->get('bg_process', true);
		}

		if ($this->input->is_ajax_request() || $bg_process) {
			$items = $this->quickbooks_model->get_items('');

			if (count($items) > 0) {
				$items = json_decode(json_encode($items));
				foreach ($items as $key => $item) {
					$success = $this->quickbooks_model->qb_item($item->itemid);
					$unsuccessful = '';
					if (!$success) {
						$unsuccessful .= $item->description . ',';
					}
				}
				if (empty($unsuccessful)) {

					echo json_encode(array(
						'success' => true,
						'message' => _l('qb_items_sync_success'),
					));
				} else {
					echo json_encode(array(
						'success' => false,
						'message' => _l('qb_items_sync_fail', $unsuccessful),
					));
				}
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_items_sync_not_found'),
				));
			}
			die;
		}

		return true;
	}

	public function get_qb_items($select = "") {
		if (!empty($select)) {
			$this->db->select($select);
		}
		$result = $this->db->where('qb_id >', 0)->get(db_prefix() . 'qb_items')->result();

		echo json_encode($result);
	}

	public function get_item_income_acc_select_field($id = null) {
		if ($this->input->is_ajax_request()) {
			if (!is_numeric($id)) {
				$selected = null;
			} else {
				$selected = $this->quickbooks_model->get_items($id)->qb_incomeAccountRef;
			}

			$data = array();
			$data['selected'] = $selected;
			$data['qb_income_accounts'] = array();

			$acc_select = $this->quickbooks_model->get_qb_accounts("*", "Revenue");
			if (!empty($acc_select)) {
				foreach ($acc_select->chart->Revenue as $key => $income_vals) {
					foreach ($income_vals as $key => $value) {
						$data['qb_income_accounts'][$value->Id] = $value->Name;
					}
				}
			}

			$this->load->view('includes/item_income_acc_ref_field', $data);
		}
	}

/*==========================================================
//  INVOICES
//==========================================================*/

	public function synchallinvoices() {

		$bg_process = false;
		if ($this->input->get('bg_process', true) !== null) {
			$bg_process = $this->input->get('bg_process', true);
		}

		if ($this->input->is_ajax_request() || $bg_process) {
			$invoices = $this->invoices_model->get('', array('status <' => 5/*'tblinvoices.id' => 8*/));
			if (count($invoices) > 0) {
				$invoices = json_decode(json_encode($invoices));
				foreach ($invoices as $key => $invoice) {
					$success = $this->quickbooks_model->qb_invoice($invoice->id);
					$unsuccessful = '';
					if (!$success) {
						$unsuccessful .= format_invoice_number($invoice->id) . ',';
					}
				}
				if (empty($unsuccessful)) {

					echo json_encode(array(
						'success' => true,
						'message' => _l('qb_invoices_sync_success'),
					));
				} else {
					echo json_encode(array(
						'success' => false,
						'message' => _l('qb_invoices_sync_fail', $unsuccessful),
					));
				}
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_invoices_sync_not_found'),
				));
			}
			die;
		}

		return true;
	}

	public function get_qb_invoice($select = "") {
		if (!empty($select)) {
			$this->db->select($select);
		}
		$result = $this->db->where('qb_id >', 0)->get(db_prefix() . 'invoices')->result();

		echo json_encode($result);
	}

/*==========================================================
//  PAYMENTS
//==========================================================*/

	public function synchallpayments() {

		$bg_process = false;
		if ($this->input->get('bg_process', true) !== null) {
			$bg_process = $this->input->get('bg_process', true);
		}

		if ($this->input->is_ajax_request() || $bg_process) {
			$invoices = $this->invoices_model->get('', array('status <' => 4, 'status >' => 1));
			if (count($invoices) > 0) {
				$invoices = json_decode(json_encode($invoices));
				foreach ($invoices as $key => $invoice) {
					$payments = $this->payments_model->get_invoice_payments($invoice->id);
					if (count($payments) > 0) {
						$payments = json_decode(json_encode($payments));
						foreach ($payments as $key => $payment) {
							$success = $this->quickbooks_model->qb_payment($payment->paymentid);
							$unsuccessful = '';
							if (!$success) {
								$unsuccessful .= $payment->paymentid . ',';
							}
						}
					}
				}
				if (empty($unsuccessful)) {

					echo json_encode(array(
						'success' => true,
						'message' => _l('qb_payments_sync_success'),
					));
				} else {
					echo json_encode(array(
						'success' => false,
						'message' => _l('qb_payments_sync_fail', $unsuccessful),
					));
				}
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_payments_sync_not_found'),
				));
			}
			die;
		}

		return true;
	}

	public function get_qb_payments($select = "") {
		if (!empty($select)) {
			$this->db->select($select);
		}

		$result = $this->db->where(['qb_payment_id !=' => 0])->get(db_prefix() . 'invoicepaymentrecords')->result();

		echo json_encode($result);
	}
/*==========================================================
//  EXPENSES
//==========================================================*/

	public function synchallexpenses() {

		$bg_process = false;
		if ($this->input->get('bg_process', true) !== null) {
			$bg_process = $this->input->get('bg_process', true);
		}

		if ($this->input->is_ajax_request() || $bg_process) {
			if ($this->quickbooks_model->qb_expense()) {
				echo json_encode(array(
					'success' => true,
					'message' => _l('qb_expenses_sync_success'),
				));
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_expenses_sync_fail'),
				));
			}
		}

		return true;
	}

/*==========================================================
//  RESET ALL SYNC
//==========================================================*/
	public function reset_all_sync() {
		//Chart of acc
		$this->db->query("TRUNCATE " . db_prefix() . "qb_chart_accounts");
		$this->db->query("ALTER TABLE " . db_prefix() . "qb_chart_accounts  AUTO_INCREMENT = 1");

		//Taxes & Tax Agencies
		$this->db->query("TRUNCATE " . db_prefix() . "qb_agencies");
		$this->db->query("ALTER TABLE " . db_prefix() . "qb_agencies  AUTO_INCREMENT = 1");

		$this->db->query("TRUNCATE " . db_prefix() . "qb_taxes");
		$this->db->query("ALTER TABLE " . db_prefix() . "qb_taxes  AUTO_INCREMENT = 1");
		$this->db->update(db_prefix() . 'taxes', ["agency_id" => Null], ['id >' => 0]);

		//items
		$this->db->query("TRUNCATE " . db_prefix() . "qb_items");
		$this->db->query("ALTER TABLE " . db_prefix() . "qb_items  AUTO_INCREMENT = 1");
		$this->db->update(db_prefix() . 'items', ["qb_incomeAccountRef" => Null], ['id >' => 0]);

		//expenses
		$this->db->update(db_prefix() . 'expenses', ["qb_paymentAccountRef" => Null, "qb_expense_id" => 0], ['id >' => 0]);
		$this->db->update(db_prefix() . 'expenses_categories', ["qb_id" => 0], ['id >' => 0]);

		//Clients
		$this->db->update(db_prefix() . 'clients', ["qb_id" => 0], ['userid >' => 0]);
		//invoices
		$this->db->update(db_prefix() . 'invoices', ["qb_id" => 0], ['id >' => 0]);
		//Payments
		$this->db->update(db_prefix() . 'invoicepaymentrecords', ["qb_payment_id" => 0], ['id >' => 0]);
		$this->db->update(db_prefix() . 'payment_modes', ["qb_id" => 0], ['id >' => 0]);

		//Settings
		update_option('qb_default_income_account', '');
		update_option('qb_default_payment_account', '');
		update_option('qb_default_asset_account', '');

		echo json_encode(array('success' => true, 'message' => 'Reset successful.'));
		return true;
	}

	public function test($code = false) {
		$CI = &get_instance();
		if ($CI->db->field_exists("direction", db_prefix() . 'qb_taxes')) {
			$CI->db->query("ALTER TABLE " . db_prefix() . 'qb_taxes' . " ADD direction varchar(10) DEFAULT 'UP'");
		}

		/*$taxes = $this->quickbooks_model->qb_sync_taxes_down($code);
		echo '<pre>' . json_encode($taxes);*/
	}

}

/* End of file Quickbooks.php */
/* Location: ../modules/quickbooks/controllers/Quickbooks.php */
