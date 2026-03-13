<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Tax_agency extends AdminController {

	public function __construct() {
		parent::__construct();
		if (!is_admin()) {
			access_denied('tax_agency');
		}
		$this->load->model('taxes_model');
		$this->load->model('quickbooks_model');
	}

	/*
		 * Get Agency Select Field
	*/
	public function get_agency_select_field($id = null) {
		if ($this->input->is_ajax_request()) {
			$selected = null;
			if (!is_numeric($id)) {
				$selected = null;
			} else {
				$selected_tax = $this->quickbooks_model->getTax(['taxes.id' => $id]);
				if (!empty($selected_tax)) {
					$selected = $selected_tax->agency_id;
				}
			}

			$data = array();
			$data['selected'] = $selected;
			$data['agencies'] = $this->quickbooks_model->get_tax_agency();

			$this->load->view('includes/agency_select_field', $data);
		}
	}

	/*
		 * Synch All Taxes
	*/
	public function synchalltaxes() {
		$bg_process = false;
		if ($this->input->get('bg_process', true) != null) {
			$bg_process = $this->input->get('bg_process', true);
		}

		if ($this->input->is_ajax_request() || $bg_process) {

			//Synch Down
			if (!$this->quickbooks_model->qb_sync_taxes_down()) {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_tax_down_sync_fail'),
				));

				return;
			}

			//Synch All Tax Agencies
			if (!$this->quickbooks_model->qb_synch_tax_agencies()) {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_tax_agency_sync_fail'),
				));

				return;
			}

			//Synch Up
			//Get Taxes
			$taxes = $this->taxes_model->get();
			if (count($taxes) > 0) {
				foreach ($taxes as $tax) {
					if (is_numeric($tax['agency_id']) && $tax['agency_id'] > 0) {
						$success = $this->quickbooks_model->qb_synch_taxes($tax);
						if (!$success) {
							echo json_encode(array(
								'success' => false,
								'message' => $tax['name'] . ' ' . _l('qb_taxes_sync_fail'),
							));

							return;
						}
					}
				}

				echo json_encode(array(
					'success' => true,
					'message' => _l('qb_taxes_sync_success'),
				));
			} else {
				echo json_encode(array(
					'success' => false,
					'message' => _l('qb_taxes_sync_not_found'),
				));
			}
			die;
		}

		return true;
	}

	/*
		 * List all taxes
	*/
	public function index() {
		if ($this->input->is_ajax_request()) {
			$this->app->get_table_data(module_views_path('quickbooks', 'tables/tax_agency'));
		}
		$data['title'] = _l('qb_tax_agency');
		$this->load->view('tax_agency/manage', $data);
	}

	/*
		 * Add or edit tax agency / ajax
	*/
	public function manage() {
		if ($this->input->post()) {
			$data = $this->input->post();
			if ($data['tax_agencyid'] == '') {
				$success = $this->quickbooks_model->add_tax_agency($data);
				$message = '';
				if ($success == true) {
					$message = _l('added_successfully', _l('qb_tax_agency'));
				}
				echo json_encode([
					'success' => $success,
					'message' => $message,
				]);
			} else {
				$success = $this->quickbooks_model->edit_tax_agency($data);
				$message = '';
				if ($success == true) {
					$message = _l('updated_successfully', _l('qb_tax_agency'));
				}
				echo json_encode([
					'success' => $success,
					'message' => $message,
				]);
			}
		}
	}

	/*
		 * Delete tax from database
	*/
	public function delete($id) {
		if (!$id) {
			redirect(admin_url('taxes'));
		}
		$response = $this->quickbooks_model->delete_tax_agency($id);
		if (is_array($response) && isset($response['referenced'])) {
			set_alert('warning', _l('is_referenced', _l('qb_tax_agency')));
		} elseif ($response == true) {
			set_alert('success', _l('deleted', _l('qb_tax_agency')));
		} else {
			set_alert('warning', _l('problem_deleting', _l('qb_tax_agency')));
		}
		redirect(admin_url('quickbooks/tax_agency'));
	}

	/*
		 * Check if agency exists
	*/
	public function tax_agency_name_exists() {
		if ($this->input->post()) {
			$taxA_id = $this->input->post('tax_agencyid');
			if ($taxA_id != '') {
				$this->db->where('id', $taxA_id);
				$_current_taxA = $this->db->get(db_prefix() . 'qb_agencies')->row();
				if ($_current_taxA->agency_name == $this->input->post('agency_name')) {
					echo json_encode(true);
					die();
				}
			}
			$this->db->where('agency_name', $this->input->post('agency_name'));
			$total_rows = $this->db->count_all_results(db_prefix() . 'qb_agencies');
			if ($total_rows > 0) {
				echo json_encode(false);
			} else {
				echo json_encode(true);
			}
			die();
		}
	}

	/*
		 * Get QB taxes
	*/

	public function get_qb_taxes() {

		$result = $this->quickbooks_model->getTaxes(['qb_id >' => 0]);

		echo json_encode($result);
	}
}
