<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\Facades\Account;
use QuickBooksOnline\API\Facades\Customer;
use QuickBooksOnline\API\Facades\Invoice;
use QuickBooksOnline\API\Facades\Item;
use QuickBooksOnline\API\Facades\Payment;
use QuickBooksOnline\API\Facades\Purchase;
use QuickBooksOnline\API\Facades\TaxAgency;
use QuickBooksOnline\API\Facades\TaxRate;
use QuickBooksOnline\API\Facades\TaxService;

class Quickbooks_model extends App_Model {

	/**
	 * @var QuickBooksOnline\API\DataService\DataService
	 */
	private $dataService = null;

	public function __construct() {
		parent::__construct();
		$this->dataService = $this->quickbooks_module->getDataService();
		$this->load->model('taxes_model');
		$this->load->model('invoices_model');
		$this->load->model('clients_model');
		$this->load->model('payments_model');
		$this->load->model('payment_modes_model');
		$this->load->model('expenses_model');
	}

/*==========================================================
//  Chart of accounts
//==========================================================*/

	/*
		 * Synch Chart of Accounts
	*/
	public function qb_synch_accounts() {
		try {
			$allAccounts = $this->dataService->query("SELECT * FROM Account  WHERE Id > '0'");
			if (count($allAccounts) > 0) {
				$chat = array();
				$list_table_fields = array();
				$chat_table = db_prefix() . 'qb_chart_accounts';
				$columns = $this->db->query("SHOW COLUMNS FROM " . $chat_table)->result_array();
				foreach ($columns as $column) {
					$list_table_fields[] = $column['Field'];
				}

				foreach ($allAccounts as $oneAccount) {

					foreach ($oneAccount as $key => $value) {
						if (!in_array($key, $list_table_fields)) {
							unset($oneAccount->$key);
						}
					}

					$chat[] = $oneAccount;
					if (get_field_value($chat_table, array('Id' => (int) $oneAccount->Id), 'chat_id')) {
						//update
						$this->db->where('Id', (int) $oneAccount->Id)->update($chat_table, $oneAccount);
					} else {
						$this->db->insert($chat_table, $oneAccount);
					}

					$chat[] = $oneAccount;
				}
				return true;
			} else {
				return false;
			}
		} catch (Exception $ex) {
			log_message('error', $ex->getMessage());
		}

		return false;
	}

	/*Get Quick Books Account*/
	public function get_qb_accounts($select = "", $Classification = "", $AccountType = "") {
		if (!empty($select)) {
			$this->db->select($select);
		}
		$query = $this->db->order_by("Name", "asc")->get(db_prefix() . 'qb_chart_accounts');

		$accs = array();
		$acc_classifications_arr = array();
		$currency = "";

		if ($results = $query->result()) {
			if (count($results) > 0) {
				foreach ($results as $result) {
					$_Classification = $result->Classification;
					$_Classification_lower = strtolower($_Classification);

					if ($_Classification) {
						$accs['chart'][$_Classification_lower][] = $result;
						$sets = $accs['chart'][$_Classification_lower];
						unset($accs['chart'][$_Classification_lower]);
						foreach ($sets as $sete) {
							$_AccountType = $sete->AccountType;
							$accs['chart'][$_Classification][$_AccountType][] = $sete;
						}
					}

					$currency = $result->CurrencyRef;

					$acc_classifications_arr[] = $_Classification;
				}

				if (!empty($Classification)) {
					foreach ($acc_classifications_arr as $cls) {
						if ($cls != $Classification) {
							unset($accs['chart'][$cls]);
						} else {
							if (!empty($AccountType)) {
								foreach ($accs['chart'][$cls] as $key => $acc_typ) {
									if ($key != $AccountType) {
										unset($accs['chart'][$cls][$key]);
									}
								}
							}
						}

					}
				}

				$accs['currency'] = $currency;
			} else {
				$accs = array();
			}
		}

		return json_decode(json_encode($accs));

	}

/*==========================================================
//  Tax & Tax Agency
//==========================================================*/

	public function getTaxes($where = array()) {
		$this->db->select(db_prefix() . "taxes.id, name, taxrate, qb_id, agency_id");
		$this->db->from(db_prefix() . 'taxes');
		$this->db->join(db_prefix() . 'qb_taxes', db_prefix() . 'qb_taxes.tax_id=' . db_prefix() . 'taxes.id');
		$this->db->where($where);
		$this->db->order_by('taxrate', 'ASC');

		return $this->db->get()->result_array();
	}

	public function getTax($where = array()) {
		$this->db->select(db_prefix() . "taxes.id, name, taxrate, qb_id, agency_id");
		$this->db->from(db_prefix() . 'taxes');
		$this->db->join(db_prefix() . 'qb_taxes', db_prefix() . 'qb_taxes.tax_id=' . db_prefix() . 'taxes.id');
		$this->db->where($where);
		return $this->db->get()->row();
	}

	/*
		 * Get tax Agency by id
	*/
	public function get_tax_agency($id = '') {
		if (is_numeric($id)) {
			$this->db->where('id', $id);

			return $this->db->get(db_prefix() . 'qb_agencies')->row();
		}
		$this->db->order_by('agency_name', 'ASC');

		return $this->db->get(db_prefix() . 'qb_agencies')->result_array();
	}

	/*
		 * Create Tax Agency in Quickbooks
	*/
	public function qb_create_tax_agency($data) {
		//Create QB Record Create
		try {

			$taxAgency = TaxAgency::create([
				'DisplayName' => $data['agency_name'],
			]);

			$resultingObj = $this->dataService->Add($taxAgency);
			if (!empty($resultingObj)) {
				$this->db->where('id', $data['id'])->update(db_prefix() . 'qb_agencies', ['qb_id' => $resultingObj->Id]);
				if ($this->db->affected_rows() > 0) {
					log_activity('Quickbooks Tax Agency added [ID: ' . $resultingObj->Id . ', Local Tax Agency ID' . $data['id'] . ']');
				}
				return $resultingObj->Id;
			}
		} catch (Exception $ex) {
			log_message('error', $ex->getMessage());
		}

		return false;
	}

	/**
	 * Add new tax Agency
	 * @param array $data tax data
	 */
	public function add_tax_agency($data) {
		unset($data['tax_agencyid']);
		$data['agency_name'] = trim($data['agency_name']);
		$this->db->insert(db_prefix() . 'qb_agencies', $data);
		$insert_id = $this->db->insert_id();
		if ($insert_id) {
			log_activity('New Tax Agency Added [ID: ' . $insert_id . ', ' . $data['agency_name'] . ']');

			//Create QB Record Create
			$data['id'] = $insert_id;
			$this->qb_create_tax_agency($data);

			return true;
		}

		return false;
	}

	/*
		 * Edit tax
	*/
	public function edit_tax_agency($data) {
		$tax_agencyid = $data['tax_agencyid'];
		$original_tax = get_tax_by_id($tax_agencyid);
		unset($data['tax_agencyid']);
		$data['agency_name'] = trim($data['agency_name']);
		$this->db->where('id', $tax_agencyid);
		$this->db->update(db_prefix() . 'qb_agencies', $data);
		if ($this->db->affected_rows() > 0) {
			log_activity('Tax Agency Updated [ID: ' . $tax_agencyid . ', ' . $data['agency_name'] . ']');

			//Create QB Record Update
			$data['id'] = $tax_agencyid;
			$this->qb_create_tax_agency($data);

			return true;
		}

		return false;
	}

	/*
		 * Delete tax from database
	*/
	public function delete_tax_agency($id) {
		if (is_reference_in_table('id', db_prefix() . 'taxes', $id)) {
			return [
				'referenced' => true,
			];
		}
		//Delete QB Record

		$this->db->where('id', $id);
		$this->db->delete(db_prefix() . 'qb_agencies');
		if ($this->db->affected_rows() > 0) {
			log_activity('Tax Agency Deleted [ID: ' . $id . ']');

			return true;
		}

		return false;
	}

	/*
		 * Synch All TAX AGENCIES
	*/
	public function qb_synch_tax_agencies() {

		try {
			//Synch Up
			$agencies = $this->get_tax_agency();
			foreach ($agencies as $key => $agency) {
				//query quickbooks
				$qb_tax_agency = $this->dataService->query("SELECT * FROM TaxAgency WHERE Id = '" . $agency['qb_id'] . "'");

				if (empty($qb_tax_agency)) {
					//Create agency
					$this->qb_create_tax_agency($agency);
				} else {
					//Update local qb_id
					$qb_tax_agency = reset($qb_tax_agency);
					$this->db->where('id', $agency['id'])->update(db_prefix() . 'qb_agencies', ['qb_id' => $qb_tax_agency->Id]);
					if ($this->db->affected_rows() > 0) {
						log_activity('Quickbooks Tax Agency Updated [ID: ' . $qb_tax_agency->Id . ', Local Tax Agency ID' . $agency['id'] . ']');
					}
				}
			}

			//Synch Down
			$qb_tax_agencies = $this->dataService->query("SELECT * FROM TaxAgency WHERE Id > '0'");

			foreach ($qb_tax_agencies as $key => $qb_tax_agency) {
				//check name locally
				if (total_rows(db_prefix() . 'qb_agencies', ['agency_name' => $qb_tax_agency->DisplayName, 'qb_id' => $qb_tax_agency->Id]) == 0) {
					//create
					$this->db->insert(db_prefix() . 'qb_agencies', ['agency_name' => $qb_tax_agency->DisplayName, 'qb_id' => $qb_tax_agency->Id]);
					$insert_id = $this->db->insert_id();
					if ($insert_id) {
						log_activity('New Tax Agency Added [ID: ' . $insert_id . ', ' . $qb_tax_agency->DisplayName . ']');
					}

				}

			}
			return true;
		} catch (Exception $ex) {
			return false;
		}
	}

	//Taxes Sync Up
	public function qb_synch_taxes($tax = array()) {
		try {
			if (!empty($tax)) {
				//check if synched
				$syncCheck = $this->getTax(['taxes.id' => $tax['id']]);
				if (empty($syncCheck)) {
					$TaxRateDetails = array();
					$agencyObj = $this->get_tax_agency($tax['agency_id']);

					$currentTaxServiceDetail = TaxRate::create([
						"TaxRateName" => $tax['name'],
						"RateValue" => $tax['taxrate'],
						"TaxAgencyId" => $agencyObj->qb_id,
						"TaxApplicableOn" => "Sales",
					]);
					$TaxRateDetails[] = $currentTaxServiceDetail;
					$TaxService = TaxService::create([
						"TaxCode" => $tax['name'],
						"TaxRateDetails" => $TaxRateDetails,
					]);
					$resultingObj = $this->dataService->Add($TaxService);

					if (!empty($resultingObj)) {
						$data = array();
						$data['qb_id'] = $resultingObj->TaxService->TaxCodeId;
						if (total_rows(db_prefix() . 'qb_taxes', ['tax_id' => $tax['id']]) > 0) {
							$this->db->where('tax_id', $tax['id'])->update(db_prefix() . 'qb_taxes', $data);
							if ($this->db->affected_rows() > 0) {
								log_activity('Quickbooks Tax Rate added [ID: ' . $data['qb_id'] . ', Local Tax Agency ID' . $tax['id'] . ']');
							}
						} else {
							$data['tax_id'] = $tax['id'];
							$this->db->insert(db_prefix() . 'qb_taxes', $data);
							if ($this->db->insert_id()) {
								log_activity('Tax Rate synchronized with Quickbooks & updated [ID: ' . $data['tax_id'] . ']');
							}
						}

						return true;
					}
				}
			}
			return true;
		} catch (Exception $ex) {
			return false;
		}
	}

	//Taxes Sync Down
	public function qb_sync_taxes_down($code = false) {
		try {

			$qb_codes = $this->dataService->query("SELECT * FROM TaxCode  WHERE Active = true");
			$qb_codes = json_decode(json_encode($qb_codes));
			foreach ($qb_codes as $key => $tax_code) {
				if (!empty($tax_code->SalesTaxRateList)) {
					$arr1 = json_decode(json_encode($tax_code->SalesTaxRateList->TaxRateDetail));
					if (!is_array($arr1)) {
						$rateId = $tax_code->SalesTaxRateList->TaxRateDetail->TaxRateRef;
						$taxRate = $this->dataService->query("SELECT * FROM TaxRate WHERE Id = '" . $rateId . "'");
						$taxRate = reset($taxRate);

						$qb_rates_1['SalesTax'][] = [
							'TaxCodeName' => $tax_code->Name,
							'TaxCodeId' => $tax_code->Id,
							'TaxRateName' => $taxRate->Name,
							'TaxRateId' => $rateId,
							'TaxRateValue' => $taxRate->RateValue,
							'AgencyRef' => $taxRate->AgencyRef,
						];
					}

				}
				if (!empty($tax_code->PurchaseTaxRateList)) {
					$arr2 = json_decode(json_encode($tax_code->PurchaseTaxRateList->TaxRateDetail));
					if (!is_array($arr2)) {
						$rateId = $tax_code->PurchaseTaxRateList->TaxRateDetail->TaxRateRef;
						$taxRate = $this->dataService->query("SELECT * FROM TaxRate WHERE Id = '" . $rateId . "'");
						$taxRate = reset($taxRate);

						$qb_rates_2['PurchaseTax'][] = [
							'TaxCodeName' => $tax_code->Name,
							'TaxCodeId' => $tax_code->Id,
							'TaxRateName' => $taxRate->Name,
							'TaxRateId' => $rateId,
							'TaxRateValue' => $taxRate->RateValue,
							'AgencyRef' => $taxRate->AgencyRef,
						];
					}

				}
			}

			$qb_rates = array_merge($qb_rates_1['SalesTax'], $qb_rates_2['PurchaseTax']);

			$qb_rates = json_decode(json_encode($qb_rates));

			foreach ($qb_rates as $key => $qb_rate) {

				//check name locally
				if (total_rows(db_prefix() . 'taxes', ['name' => $qb_rate->TaxRateName]) == 0) {
					$agency = $this->db->select('*')->where(['qb_id' => $qb_rate->AgencyRef])->get(db_prefix() . 'qb_agencies')->row();
					if (!empty($agency)) {
						if (empty($qb_rate->TaxRateValue)) {
							$qb_rate->TaxRateValue = 0;
						}
						//create
						$this->db->insert(db_prefix() . 'taxes', ['name' => $qb_rate->TaxRateName, 'taxrate' => $qb_rate->TaxRateValue, 'agency_id' => $agency->id]);
						$insert_id = $this->db->insert_id();
						if ($insert_id) {
							$this->db->insert(db_prefix() . 'qb_taxes', ['tax_id' => $insert_id, 'qb_id' => $qb_rate->TaxCodeId]);
							log_activity('New Tax Added from Quickbooks [ID: ' . $insert_id . ', ' . $qb_rate->TaxRateName . ']');
						}
					}

				} else {
					$agency = $this->db->select('*')->where('qb_id', $qb_rate->AgencyRef)->get(db_prefix() . 'qb_agencies')->row();
					if (!empty($agency)) {
						if (empty($qb_rate->TaxRateValue)) {
							$qb_rate->TaxRateValue = 0;
						}

						//get value
						$tax_id = get_field_value(db_prefix() . 'taxes', ['name' => $qb_rate->TaxRateName], 'id');

						$this->db->update(db_prefix() . 'taxes', ['name' => $qb_rate->TaxRateName, 'taxrate' => $qb_rate->TaxRateValue, 'agency_id' => $agency->id], ['id' => $tax_id]);

						if ($this->db->affected_rows() > 0) {
							log_activity('Quickbooks Tax Updated [QB TaxCode ID' . $qb_rate->TaxCodeId . ']');

							if (total_rows(db_prefix() . 'qb_taxes', ['tax_id' => $tax_id]) > 0) {
								$this->db->where('tax_id', $tax_id)->update(db_prefix() . 'qb_taxes', ['qb_id' => $qb_rate->TaxCodeId]);
								if ($this->db->affected_rows() > 0) {
									log_activity('Quickbooks Tax Rate added [Tax Code ID: ' . $qb_rate->TaxCodeId . ', Local Tax Agency ID' . $tax_id . ']');
								}
							} else {
								$this->db->insert(db_prefix() . 'qb_taxes', ['tax_id' => $tax_id, 'qb_id' => $qb_rate->TaxCodeId]);
								if ($this->db->insert_id()) {
									log_activity('Tax Rate synchronized with Quickbooks & updated [ID: ' . $tax_id . ']');
								}
							}
						}
					}

				}

			}
			return true;
		} catch (Exception $ex) {
			return false;
		}
	}

/*==========================================================
//  Items
//==========================================================*/

	/*
		 * Create new invoice item on the go
	*/
	public function inv_item($db_item = array()) {
		if (!empty($db_item)) {
			$item_qb_map = array(
				"Name" => $db_item['description'],
				"Description" => $db_item['long_description'],
				"Active" => true,
				"Taxable" => false,
				"UnitPrice" => $db_item['rate'],
				"Type" => "Service",
				"PurchaseCost" => 0,
				"TrackQtyOnHand" => false,
				"IncomeAccountRef" => array(
					"value" => get_option('qb_default_income_account'),
				),
			);
			try {
				$item = Item::create($item_qb_map);
				$resultingObj = $this->dataService->Add($item);
				if (!empty($resultingObj)) {
					return $resultingObj->Id;
				}
			} catch (Exception $ex) {
				log_message('error', $ex->getMessage());
			}
		}

		return '';

	}

	/*
		 * Item : Product / Service
	*/
	public function qb_item($id = "", $check = 'add_edit') {
		if (!empty($check) && !empty($id)) {
			$item_qb_map = array();
			$qb_id = null;
			$db_item = $this->get_items($id);
			if (!empty($db_item)) {
				$item_qb_map = array(
					"Name" => $db_item->description,
					"Description" => $db_item->long_description,
					"Taxable" => true,
					"UnitPrice" => $db_item->rate,
					"Type" => "Service",
					"PurchaseCost" => 0,
					"TrackQtyOnHand" => false,
					"IncomeAccountRef" => array(
						"value" => !empty($db_item->qb_incomeAccountRef) ? $db_item->qb_incomeAccountRef : get_option('qb_default_income_account'),
					),
				);
				if (!empty($db_item->taxid)) {
					$item_qb_map = array_merge($item_qb_map, array("SalesTaxCodeRef" => get_qb_id_from_tax_id($db_item->taxid)));
				}

			}

			$qb_id = get_field_value(db_prefix() . "qb_items", array("item_id" => $id), "qb_id");

			if ($check == "add_edit") {
				//Query Quickbooks
				$qb_item_by_name = $this->dataService->query("SELECT * FROM Item WHERE name = '" . $db_item->description . "'");
				if (empty($qb_item_by_name)) {
					//Add New
					try {
						$item = Item::create($item_qb_map);
						$resultingObj = $this->dataService->Add($item);
						if (!empty($resultingObj)) {
							$data = array();
							$data['item_id'] = $id;
							$data['qb_id'] = $resultingObj->Id;
							if (total_rows(db_prefix() . 'qb_items', array('item_id' => $id)) == 0) {
								$this->db->insert(db_prefix() . 'qb_items', $data);
								if ($this->db->insert_id()) {

									log_activity('Quickbooks Item added [ID: ' . $data['qb_id'] . ', Local Item ID' . $id . ']');
								}
								$this->db->update(db_prefix() . 'items', array('qb_incomeAccountRef' => $resultingObj->IncomeAccountRef->value), array('id' => $id));
							} else {
								$this->db->update(db_prefix() . 'qb_items', array('qb_id' => $data['qb_id']), array('item_id' => $id));
								if ($this->db->affected_rows() > 0) {
									log_activity('Quickbooks Item Updated [ID: ' . $data['qb_id'] . ', Local Item ID' . $id . ']');
								}
								$this->db->update(db_prefix() . 'items', array('qb_incomeAccountRef' => $resultingObj->IncomeAccountRef->value), array('id' => $id));
							}
							return $data['qb_id'];
						}
					} catch (Exception $ex) {
						log_message('error', $ex->getMessage());
					}

				} else {
					//Update
					try {

						$qb_item_by_name = reset($qb_item_by_name);
						$resultingObj = Item::update($qb_item_by_name, $item_qb_map);
						//$resultingObj = $this->dataService->Update($item);
						if (!empty($resultingObj)) {
							$data = array();
							$data['item_id'] = $id;
							$data['qb_id'] = $resultingObj->Id;
							if (total_rows(db_prefix() . 'qb_items', array('item_id' => $id)) == 0) {
								$this->db->insert(db_prefix() . 'qb_items', $data);
								if ($this->db->insert_id()) {
									log_activity('Quickbooks Item Updated and Added [ID: ' . $data['qb_id'] . ', Local Item ID' . $id . ']');
								}
								$this->db->update(db_prefix() . 'items', array('qb_incomeAccountRef' => $qb_item_by_name->IncomeAccountRef), array('id' => $id));
							} else {
								$this->db->update(db_prefix() . 'qb_items', array('qb_id' => $data['qb_id']), array('item_id' => $id));
								if ($this->db->affected_rows() > 0) {
									log_activity('Quickbooks Item Updated [ID: ' . $data['qb_id'] . ', Local Item ID' . $id . ']');
								}
								$this->db->update(db_prefix() . 'items', array('qb_incomeAccountRef' => $qb_item_by_name->IncomeAccountRef), array('id' => $id));
							}
							return $data['qb_id'];
						}

					} catch (Exception $ex) {
						log_message('error', $ex->getMessage());
					}

				}
			} else if ($check == 'delete') {
				try {
					if (is_numeric($qb_id)) {
						$item = $this->dataService->FindbyId('Item', $qb_id);
						$theResourceObj = Item::update($item, array("Active" => false));
						$result = $this->dataService->Update($theResourceObj);
						if (count($result) > 0) {
							$this->db->where('item_id', $id);
							$this->db->where('qb_id', $qb_id);
							$this->db->delete(db_prefix() . 'qb_items');
							log_activity('Quickbooks Item updated as Inactive [ID: ' . $qb_id . ', Local Item ID' . $id . ']');
						}
					}
					return true;
				} catch (Exception $ex) {
					log_message('error', $ex->getMessage());
				}

			}
		}
		return false;
	}

	/*
		 * Get Items
	*/
	public function get_items($id = '', $where = array()) {
		$columns = $this->db->list_fields(db_prefix() . 'items');
		$rateCurrencyColumns = '';
		foreach ($columns as $column) {
			if (strpos($column, 'rate_currency_') !== false) {
				$rateCurrencyColumns .= $column . ',';
			}
		}
		$this->db->select($rateCurrencyColumns . '' . db_prefix() . 'items.id as itemid,rate,
			t1.taxrate as taxrate,t1.id as taxid,t1.name as taxname,
			t2.taxrate as taxrate_2,t2.id as taxid_2,t2.name as taxname_2, qt1.qb_id as qb_tax_id, qt2.qb_id as qb_tax_id2, t1.agency_id as qb_agency_id, t2.agency_id as qb_agency_id2,
			description,long_description,group_id,' . db_prefix() . 'items_groups.name as group_name,unit, qb_incomeAccountRef');
		$this->db->from(db_prefix() . 'items');
		$this->db->join('' . db_prefix() . 'taxes t1', 't1.id = ' . db_prefix() . 'items.tax', 'left');
		$this->db->join('' . db_prefix() . 'taxes t2', 't2.id = ' . db_prefix() . 'items.tax2', 'left');
		$this->db->join('' . db_prefix() . 'qb_taxes qt1', 'qt1.tax_id = ' . db_prefix() . 'items.tax', 'left');
		$this->db->join('' . db_prefix() . 'qb_taxes qt2', 'qt2.tax_id = ' . db_prefix() . 'items.tax2', 'left');
		$this->db->join(db_prefix() . 'items_groups', '' . db_prefix() . 'items_groups.id = ' . db_prefix() . 'items.group_id', 'left');

		$this->db->where($where);

		if (is_numeric($id)) {
			$this->db->where(db_prefix() . 'items.id', $id);

			return $this->db->get()->row();
		}
		$this->db->order_by('description', 'asc');

		return $this->db->get()->result_array();
	}

/*==========================================================
//  Clients
//==========================================================*/

	/*
		 * Synch Clients
	*/
	public function qb_synch_client($client) {
		try {
			if (!empty($client)) {

				$contactid = get_primary_contact_user_id($client['userid']);
				$contact_fullnames = get_contact_full_name($contactid);
				$contact = $this->clients_model->get_contact($contactid);
				$client_arr = array(
					'BillAddr' => array(
						'Line1' => $client['billing_street'],
						'City' => $client['billing_city'],
						'Country' => get_country_name($client['billing_country']),
						'CountrySubDivisionCode' => $client['billing_state'],
						'PostalCode' => $client['billing_zip'],
						'Lat' => $client['latitude'],
						'Long' => $client['longitude'],
					),
					'ShipAddr' => array(
						'Line1' => $client['shipping_street'],
						'City' => $client['shipping_city'],
						'Country' => get_country_name($client['shipping_country']),
						'CountrySubDivisionCode' => $client['shipping_state'],
						'PostalCode' => $client['shipping_zip'],
						'Lat' => $client['latitude'],
						'Long' => $client['longitude'],
					),
					'Title' => $contact->title,
					'GivenName' => $contact->firstname,
					'MiddleName' => '',
					'FamilyName' => $contact->lastname,
					'FullyQualifiedName' => $contact_fullnames,
					'CompanyName' => $client['company'],
					'DisplayName' => $contact_fullnames,
					'Active' => $client['active'] == '1' ? 'true' : 'false',
					'PrimaryPhone' => array(
						'FreeFormNumber' => empty($client['phonenumber']) ? $contact->phonenumber : $client['phonenumber'],
					),
					'PrimaryEmailAddr' => array(
						'Address' => $contact->email,
					),
					'WebAddr' => $client['website'],
				);

				$quickbooks_client = $this->dataService->query("SELECT * FROM Customer  WHERE DisplayName = '" . $contact_fullnames . "'");

				//That client does not exist in quickbooks
				if (empty($quickbooks_client)) {
					//Create
					$customerObj = Customer::create($client_arr);

					$resultingCustomerObj = $this->dataService->Add($customerObj);

					if (count($resultingCustomerObj) > 0) {
						$data = array();
						$data['qb_id'] = $resultingCustomerObj->Id;
						$this->db->where('userid', $client['userid'])->update(db_prefix() . 'clients', $data);
						if ($this->db->affected_rows() > 0) {
							log_activity('Quickbooks Customer added [ID: ' . $data['qb_id'] . ', Local Tax Agency ID' . $client['userid'] . ']');
						}
						return $data['qb_id'];
					}

				} else {
					//Update
					$quickbooks_client = reset($quickbooks_client);
					$theCustomer = $quickbooks_client;
					$client_arr = array_merge($client_arr, ['sparse' => 'false']);
					$updateCustomer = Customer::update($theCustomer, $client_arr);
					$resultingCustomerUpdatedObj = $this->dataService->Update($updateCustomer);
					if (!empty($resultingCustomerUpdatedObj)) {
						$data = array();
						$data['qb_id'] = $resultingCustomerUpdatedObj->Id;
						$this->db->where('userid', $client['userid'])->update(db_prefix() . 'clients', $data);
						if ($this->db->affected_rows() > 0) {
							log_activity('Quickbooks Customer updated [ID: ' . $data['qb_id'] . ', Local Tax Agency ID' . $client['userid'] . ']');
						}
						return $data['qb_id'];
					}
				}
			}
			return true;
		} catch (Exception $ex) {
			log_message('error', $ex->getMessage());
			return false;
		}
	}

/*==========================================================
//  Invoice
//==========================================================*/

	/*
		 * QB INVOICE CRUD
	*/
	public function qb_invoice($id = "", $check = "add_edit") {
		/* This sample performs the folowing functions:
			     1.   Add a customer
			     2.   Add an item
			     3    Create invoice using the information above
			     4.   Email invoice to customer
			     5.   Receive payments for the invoice created above
		*/
		if (!empty($check) && !empty($id)) {
			$invoice_qb_map = array();
			$qb_id = null;
			$db_invoice = $this->invoices_model->get($id, array('status <' => 5));
			if (!empty($db_invoice)) {
				try {
					//handle client
					$client = $db_invoice->client;
					$client_qb_id = $this->quickbooks_model->qb_synch_client(json_decode(json_encode($client), true));

					//handle item
					$raw_items = $db_invoice->items;
					$qb_item_line = array();
					foreach ($raw_items as $key => $item) {
						//Query quickbooks Item
						$_item_name = $item['description'];
						$_qb_item = $this->dataService->query("SELECT * FROM Item WHERE name = '" . $_item_name . "'");

						//if it doesnt exist in our local records
						if (empty($_qb_item)) {
							//synch item
							$item_qbid = $this->quickbooks_model->inv_item($item);
						} else {
							$_qb_item = reset($_qb_item);
							$item_qbid = $_qb_item->Id;
						}

						$taxes = get_invoice_item_tax_qb_id($item['id']);
						$_qb_item_line = [
							"Amount" => $item['rate'] * $item['qty'],
							"Description" => $item['long_description'],
							"DetailType" => "SalesItemLineDetail",
							"SalesItemLineDetail" => [
								"Qty" => $item['qty'],
								"UnitPrice" => $item['rate'],
								"ItemRef" => [
									"value" => $item_qbid,
								],
							],

						];

						if (get_option('qb_sales_tax_model') == 'us_locales') {
							if (count($taxes) > 0) {
								$_qb_item_line["SalesItemLineDetail"]["TaxCodeRef"] = [
									"value" => 'TAX',
								];
							} else {
								$_qb_item_line["SalesItemLineDetail"]["TaxCodeRef"] = [
									"value" => 'NON',
								];
							}

						} else {
							if (count($taxes) > 0) {
								$_qb_item_line["SalesItemLineDetail"]["TaxCodeRef"] = [
									"value" => $taxes[0],
								];
							} else {
								$excemptTax = $this->getTax(['taxrate' => 0]);
								if (!empty($excemptTax)) {
									$_qb_item_line["SalesItemLineDetail"]["TaxCodeRef"] = [
										"value" => $excemptTax->qb_id,
									];
								}
							}
						}

						$qb_item_line[] = $_qb_item_line;
					}

					//handle discounts
					$discount[] = [
						"Amount" => $db_invoice->discount_total, //not percent based
						"DetailType" => "DiscountLineDetail",
						"DiscountLineDetail" => [
							"PercentBased" => ($db_invoice->discount_total > 0 && $db_invoice->discount_percent > 0) ? true : false,
							"DiscountPercent" => $db_invoice->discount_percent,
						],
					];

					$qb_item_line = array_merge($qb_item_line, $discount);

					$inv_obj = [
						"CustomerRef" => ["value" => $client_qb_id],
						"CustomerMemo" => ["value" => $db_invoice->clientnote],
						"DocNumber" => format_invoice_number($db_invoice->id),
						"DueDate" => $db_invoice->duedate,
						"TxnDate" => $db_invoice->date,
						"ApplyTaxAfterDiscount" => ($db_invoice->discount_type == "before_tax") ? true : false,
						"Line" => $qb_item_line,
					];

					if ($check == 'add_edit') {
						//Query Invoices:
						$qb_invoice = $this->dataService->query("SELECT * FROM Invoice WHERE DocNumber = '" . format_invoice_number($db_invoice->id) . "'");
						if (empty($qb_invoice)) {
							//create
							$invoiceObj = Invoice::create($inv_obj);
							$resultingObj = $this->dataService->Add($invoiceObj);

							if (!empty($resultingObj)) {
								$data = array();
								$data['qb_id'] = $resultingObj->Id;
								$this->db->where('id', $db_invoice->id)->update(db_prefix() . 'invoices', $data);
								if ($this->db->affected_rows() > 0) {
									log_activity('Quickbooks Invoice added [ID: ' . $data['qb_id'] . ', Invoice ID' . $db_invoice->id . ']');
								}
								return $data['qb_id'];
							}
						} else {
							//update
							$qb_invoice = reset($qb_invoice);
							//$inv_obj = array_merge($inv_obj, ["Id" => $qb_invoice->Id, "SyncToken" => "0"]);
							$resultingObj = Invoice::update($qb_invoice, $inv_obj);
							$resultingObj = $this->dataService->Update($resultingObj);
							//$error = $this->dataService->getLastError();

							//echo '<pre>' . json_encode($resultingObj);exit;
							if (!empty($resultingObj)) {
								$data = array();
								$data['qb_id'] = $resultingObj->Id;
								$this->db->where('id', $db_invoice->id)->update(db_prefix() . 'invoices', $data);
								if ($this->db->affected_rows() > 0) {
									log_activity('Quickbooks Invoice updated [ID: ' . $data['qb_id'] . ', Invoice ID' . $db_invoice->id . ']');
								}
								return $data['qb_id'];
							}
						}

					} else if ($check == 'delete') {
						$invoice = $this->dataService->FindbyId('invoice', $db_invoice->qb_id);
						$resultingObj = $this->dataService->Delete($invoice);
						if (!empty($resultingObj)) {
							log_activity('Quickbooks Invoice deleted [ID: ' . $db_invoice->qb_id . ', Invoice ID' . $db_invoice->id . ']');
						}
					}

				} catch (Exception $ex) {
					log_message('error', $ex->getMessage());
				}

			}
		}
		return false;
	}

/*==========================================================
//  Payment
//==========================================================*/

	/*
		 * QB PAYMENT CRUD
	*/
	public function qb_payment($id = "", $check = "add_edit", $posted_data = array()) {
		if (!empty($check) && !empty($id)) {
			$qb_id = null;
			$db_payment = $this->payments_model->get($id);
			if (!empty($db_payment)) {
				$db_invoice = $this->invoices_model->get($db_payment->invoiceid);

				if ($db_invoice->qb_id > 0) {
					try {
						//handle client
						$client = $db_invoice->client;
						$client_qb_id = $this->quickbooks_model->qb_synch_client(json_decode(json_encode($client), true));

						//handle invoice

						$pay_obj = [
							"CustomerRef" => ["value" => $client_qb_id],
							"TotalAmt" => empty($posted_data) ? $db_payment->amount : $posted_data['amount'],
							"TxnDate" => empty($posted_data) ? $db_payment->daterecorded : $posted_data['date'],
							"DocNumber" => format_invoice_number($db_invoice->id) . '-' . $db_payment->paymentid,
							"DepositToAccountRef" => get_option('qb_default_payment_account'),
							"PaymentRefNum" => empty($posted_data) ? $db_payment->transactionid : $posted_data['transactionid'],
							"PrivateNote" => empty($posted_data) ? $db_payment->note : $posted_data['note'],
							"Line" => [
								"Amount" => empty($posted_data) ? $db_payment->amount : $posted_data['amount'],
								"LinkedTxn" => [
									"TxnId" => $db_invoice->qb_id,
									"TxnType" => "Invoice",
								],
							],
						];

						//payment METHOD
						$pm_qb_id = $this->get_paymentmodes($db_payment->paymentmode);
						if (!empty($pm_qb_id)) {
							$pay_obj = array_merge($pay_obj, array("PaymentMethodRef" => ["value" => $pm_qb_id]));
						}

						if ($check == 'add_edit') {
							//Query Invoices:
							$qb_payment = $this->dataService->query("SELECT * FROM Payment WHERE Id = '" . $db_payment->qb_payment_id . "'");

							if (empty($qb_payment) && empty($posted_data)) {
								//create
								$paymentObj = Payment::create($pay_obj);
								$resultingObj = $this->dataService->Add($paymentObj);

								if (!empty($resultingObj)) {
									$data = array();
									$data['qb_payment_id'] = $resultingObj->Id;
									$this->db->update(db_prefix() . 'invoicepaymentrecords', $data, ['id' => $db_payment->paymentid]);
									if ($this->db->affected_rows() > 0) {
										log_activity('Quickbooks Payment added [ID: ' . $data['qb_payment_id'] . ', Payment ID' . $db_payment->paymentid . ']');
									}
									return $data['qb_payment_id'];
								}
							} else if (!empty($posted_data)) {
								//update
								$qb_payment = reset($qb_payment);
								$paymentObj = Payment::update($qb_payment, $pay_obj);
								$resultingObj = $this->dataService->Update($paymentObj);
								if (!empty($resultingObj)) {
									$data = array();
									$data['qb_payment_id'] = $resultingObj->Id;
									$this->db->update(db_prefix() . 'invoicepaymentrecords', $data, ['id' => $db_payment->paymentid]);
									if ($this->db->affected_rows() > 0) {
										log_activity('Quickbooks Payment updated [ID: ' . $data['qb_payment_id'] . ', Payment ID' . $db_payment->paymentid . ']');
									}
									return $data['qb_payment_id'];
								}
							}
							return true;

						} else if ($check == 'delete') {
							$payment = $this->dataService->FindbyId('payment', $db_payment->qb_payment_id);
							$resultingObj = $this->dataService->Delete($payment);
							if (!empty($resultingObj)) {
								log_activity('Quickbooks Payment deleted [ID: ' . $db_payment->qb_payment_id . ', Payment ID' . $db_payment->paymentid . ']');
							}
						}

					} catch (Exception $ex) {
						log_message('error', $ex->getMessage());
						return false;
					}

				}
			}
		}
		return false;
	}

	/*
		 * QB PAYMENT METHODS SYNC UP & DOWN
	*/
	public function qb_payment_method() {
		try {
			//Sync Up
			$local_payment_modes = $this->payment_modes_model->get();
			if (count($local_payment_modes) > 0) {
				$local_payment_modes = json_decode(json_encode($local_payment_modes));
				foreach ($local_payment_modes as $key => $l_payment_mode) {
					// Curl Params
					$s_url = '/v3/company/' . get_option('qb_realmid') . '/paymentmethod';
					$query_params = 'minorversion=51';
					$post_params = array("Name" => $l_payment_mode->name);

					if (isset($l_payment_mode->qb_id) && is_numeric($l_payment_mode->id)) {
						//check existance
						$pmss = $this->dataService->query("SELECT * FROM PaymentMethod WHERE Name = '" . $l_payment_mode->name . "'");
						if (count($pmss) > 0) {
							//update local qb_id
							$this->db->update(db_prefix() . 'payment_modes', ['qb_id' => $pmss[0]->Id], ['name' => $l_payment_mode->name]);
						} else {
							//create
							$posted = $this->quickbooks_module->trigger($s_url, $query_params, $post_params, true)['body'];
							$posted = json_decode($posted);
							if (isset($posted->PaymentMethod)) {
								$this->db->update(db_prefix() . 'payment_modes', ['qb_id' => $posted->PaymentMethod->Id], ['name' => $l_payment_mode->name]);
							}

						}

					} else {
						//online payment
						$opms = $this->dataService->query("SELECT * FROM PaymentMethod WHERE Name = '" . $l_payment_mode->name . "'");
						if (count($opms) == 0) {
							//create
							$this->quickbooks_module->trigger($s_url, $query_params, $post_params);
						}
					}
				}
			}

			//Down sync
			$pms = $this->dataService->query("SELECT * FROM PaymentMethod WHERE Id > '0'");
			$_local_pms = $this->db->select('name')->get(db_prefix() . 'payment_modes')->result();
			$local_pms = array();
			$arr = array();

			foreach ($_local_pms as $key => $_local_pm) {
				$local_pms[] = $_local_pm->name;
			}
			if (count($pms) > 0) {

				foreach ($pms as $keyz => $pm) {
					if (in_array($pm->Name, $local_pms)) {
						$this->db->update(db_prefix() . 'payment_modes', ['qb_id' => $pm->Id], ['name' => $pm->Name]);
					} else {
						$this->db->insert(db_prefix() . 'payment_modes', ['name' => $pm->Name, 'qb_id' => $pm->Id]);
					}
				}

			}

			return true;
		} catch (Exception $ex) {
			log_message('error', $ex->getMessage());
			return false;
		}

	}

	/*
		 * Get Payment Modes
	*/
	public function get_paymentmodes($id) {

		$name = null;
		$local_payment_modes = $this->payment_modes_model->get($id);
		if (!empty($local_payment_modes)) {
			$local_payment_modes = json_decode(json_encode($local_payment_modes));
			$name = $local_payment_modes->name;
		}

		$pms = $this->dataService->query("SELECT * FROM PaymentMethod WHERE Name = '" . $name . "'");

		if (count($pms) > 0 && $pms[0]->Id > 0) {
			return $pms[0]->Id;
		} else {
			return '';
		}

	}

/*==========================================================
//  Expense
//==========================================================*/

	/*
		 * QB EXPENSE CATEGORY SYNC DOWN
	*/
	public function qb_expense_category() {
		//Synch down
		$qb_categories = $this->get_qb_accounts("*", "Expense", "Expense");
		if (!empty($qb_categories)) {
			foreach ($qb_categories->chart->Expense->Expense as $key => $qb_category) {
				$local_category = $this->db->where(array('name' => $qb_category->Name))->get(db_prefix() . 'expenses_categories')->row();
				if (!empty($local_category)) {
					//update
					$this->db->update(db_prefix() . 'expenses_categories', ['description' => $qb_category->Description, 'qb_id' => $qb_category->Id], ['id' => $local_category->id]);
				} else {
					//create
					$this->db->insert(db_prefix() . 'expenses_categories', ['name' => $qb_category->Name, 'description' => $qb_category->Description, 'qb_id' => $qb_category->Id]);
				}

			}
		}

		//Synch up
		$local_categories = $this->db->where(array('qb_id' => 0))->get(db_prefix() . 'expenses_categories')->result();
		foreach ($local_categories as $key => $local_category) {
			$exp_acc = $this->dataService->Query("SELECT * FROM Account WHERE  Name = '" . $local_category->name . "' AND AccountType = 'Expense' AND AccountSubType = 'OfficeGeneralAdministrativeExpenses'");
			if (empty($exp_acc)) {
				//create
				$expenseAccountRequestObj = Account::create([
					"AccountType" => "Expense",
					"AccountSubType" => "OfficeGeneralAdministrativeExpenses",
					"Name" => $local_category->name,
				]);
				$requestObj = $this->dataService->Add($expenseAccountRequestObj);
				if (!empty($requestObj)) {
					$this->db->update(db_prefix() . 'expenses_categories', ['qb_id' => $requestObj->Id], ['id' => $local_category->id]);
				}
			}
		}

	}

	/*
		 * Get Expense Category
	*/
	public function get_expense_category($id) {

		$pmodes = $this->db->where('id', $id)->get(db_prefix() . 'expenses_categories')->row();

		if ($pmodes->qb_id > 0) {
			return $pmodes->qb_id;
		} else {
			return '';
		}

	}

	/*
		 * QB EXPENSE CRUD
	*/
	public function qb_expense() {
		try {
			$qb_id = null;
			$db_expenses = json_decode(json_encode($this->expenses_model->get()));
			if (count($db_expenses) > 0) {

				//loop through
				foreach ($db_expenses as $key => $db_expense) {

					//handle client
					$client = $this->clients_model->get($db_expense->clientid);
					$client_qb_id = $this->quickbooks_model->qb_synch_client(json_decode(json_encode($client), true));

					//handle taxes
					$tax1 = get_qb_id_from_tax_id($db_expense->tax);
					$tax2 = get_qb_id_from_tax_id($db_expense->tax2);
					$taxes = array($tax1, $tax2);

					$_qb_tax = array();

					if (get_option('qb_sales_tax_model') == 'us_locales') {
						if (count($taxes) > 0) {
							$_qb_tax = [
								"value" => 'TAX',
							];
						} else {
							$_qb_tax = [
								"value" => 'NON',
							];
						}

					} else {
						if (count($taxes) > 0) {
							$_qb_tax = [
								"value" => $taxes[0],
							];
						}
					}

					$exp_obj = [
						"PaymentType" => "Cash",
						"AccountRef" => ["value" => get_option('qb_default_payment_account')],
						"PaymentMethodRef" => ["value" => $this->get_paymentmodes($db_expense->paymentmode)],
						"DocNumber" => 'EXP-' . $db_expense->id,
						"TxnDate" => $db_expense->date,
						"PrivateNote" => $db_expense->note,
						"Line" => [
							"Description" => $db_expense->expense_name,
							"Amount" => $db_expense->amount,
							"DetailType" => "AccountBasedExpenseLineDetail",
							"AccountBasedExpenseLineDetail" => [
								"AccountRef" => ["value" => $this->get_expense_category($db_expense->category)], //category
								//"BillableStatus" => $db_expense->billable ? "Billable" : "NotBillable",
								//"TaxCodeRef" => $_qb_tax,
							],
						],
					];

					if ($client_qb_id && 1 == 0) {
						$exp_obj["Line"]["AccountBasedExpenseLineDetail"]["CustomerRef"] = $client_qb_id;
					}
					$qb_expense = $this->dataService->query("SELECT * FROM Purchase WHERE Id = '" . $db_expense->qb_expense_id . "'");
					if (empty($qb_expense)) {
						//create
						$expenseObj = Purchase::create($exp_obj);
						$resultingObj = $this->dataService->Add($expenseObj);

						if (!empty($resultingObj)) {
							$data = array();
							$data['qb_expense_id'] = $resultingObj->Id;
							$data['qb_paymentAccountRef'] = get_option('qb_default_payment_account');
							$this->db->where('id', $db_expense->id)->update(db_prefix() . 'expenses', $data);
							if ($this->db->affected_rows() > 0) {
								log_activity('Quickbooks Expense added [ID: ' . $data['qb_expense_id'] . ', Payment ID' . $db_expense->id . ']');
							}
						}
					} else {
						//update
						$qb_expense = reset($qb_expense);
						$expenseObj = Purchase::update($qb_expense, $exp_obj);
						$resultingObj = $this->dataService->Update($expenseObj);
						if (!empty($resultingObj)) {
							$data = array();
							$data['qb_expense_id'] = $resultingObj->Id;
							$data['qb_paymentAccountRef'] = get_option('qb_default_payment_account');
							$this->db->where('id', $db_expense->id)->update(db_prefix() . 'expenses', $data);
							if ($this->db->affected_rows() > 0) {
								log_activity('Quickbooks Expense updated [ID: ' . $data['qb_expense_id'] . ', Payment ID' . $db_expense->id . ']');
							}
						}
					}

				}

				return true;
			}
		} catch (Exception $ex) {
			log_message('error', $ex->getMessage());
		}

		return false;
	}

/*==========================================================
//  Disconnect Quickbooks
//==========================================================*/

	/*
		 * Disconnect from Quickbooks
	*/
	public function disconnect() {
		$clientSecret = get_clientSecret();
		$clientID = get_clientID();
		$refreshtoken = get_option('qb_refresh_token');

		if (!empty($clientSecret) && !empty($clientID) && get_option('qb_active') != '0' && time() < (get_option('qb_expiration_date') - 10 * 60)) {
			$oauth2LoginHelper = new OAuth2LoginHelper($clientID, $clientSecret);
			$revokeResult = $oauth2LoginHelper->revokeToken($refreshtoken);
			return $revokeResult;
		}
		return true;
	}

/*==========================================================
//  Cron Jobs
//==========================================================*/

	/**
	 * Runs after every hour or 60 minutes.
	 * @param string $value
	 */
	public function hourly_scheduled_synch($manually) {
		$get_token = $this->quickbooks_module->get_access_token($manually);
		if ($get_token) {
			$this->qb_synch_accounts();
			$this->qb_payment_method();
			$this->qb_expense_category();
		}
	}

	/*
		 * Runs ones a day at a specified time
	*/
	public function daily_scheduled_synch($manually) {
		if (get_option('qb_manual_cron_sync_enabled') == '0') {
			$manually = false;
		}

		$qb_auto_operations_hour = get_option('qb_auto_operations_hour');
		if ($qb_auto_operations_hour == '') {
			$qb_auto_operations_hour = 9;
		}

		$qb_auto_operations_hour = intval($qb_auto_operations_hour);
		$hour_now = date('G');
		if ($hour_now != $qb_auto_operations_hour && $manually === false) {
			return;
		}

		//Customers
		if (get_option('qb_auto_cron_sync_clients_enabled')) {
			$clients = $this->clients_model->get();
			if (count($clients) > 0) {
				foreach ($clients as $client) {
					if (total_rows(db_prefix() . 'contacts', ['active' => 1, 'userid' => $client['userid']]) > 0) {
						$this->qb_synch_client($client);
					}
				}
			}
		}

		if (get_option('qb_sales_tax_model') == 'non_us_locales' && get_option('qb_auto_cron_sync_taxes_enabled')) {

			//Tax Agencies
			$this->qb_synch_tax_agencies();

			//Synch Down
			$this->qb_sync_taxes_down();

			//Synch Up
			//Taxes
			$taxes = $this->taxes_model->get();
			if (count($taxes) > 0) {
				foreach ($taxes as $tax) {
					if (is_numeric($tax['agency_id']) && $tax['agency_id'] > 0) {
						$this->qb_synch_taxes($tax);
					}
				}
			}
		}

		//Items
		if (get_option('qb_auto_cron_sync_items_enabled')) {
			$items = $this->get_items('', array('qb_incomeAccountRef !=' => null));
			if (count($items) > 0) {
				$items = json_decode(json_encode($items));
				foreach ($items as $key => $item) {
					$this->qb_item($item->itemid);
				}
			}
		}

		//Invoices
		if (get_option('qb_auto_cron_sync_invoices_enabled')) {
			$invoices = $this->invoices_model->get('', array('status <' => 5));
			if (count($invoices) > 0) {
				$invoices = json_decode(json_encode($invoices));
				foreach ($invoices as $key => $invoice) {
					$this->qb_invoice($invoice->id);
				}
			}
		}

		//Payments
		if (get_option('qb_auto_cron_sync_payments_enabled')) {
			$invoices = $this->invoices_model->get('', array('status <' => 4, 'status >' => 1));
			if (count($invoices) > 0) {
				$invoices = json_decode(json_encode($invoices));
				foreach ($invoices as $key => $invoice) {
					$payments = $this->payments_model->get_invoice_payments($invoice->id);
					if (count($payments) > 0) {
						$payments = json_decode(json_encode($payments));
						foreach ($payments as $key => $payment) {
							$this->qb_payment($payment->paymentid);
						}
					}
				}
			}
		}

		//Expenses
		if (get_option('qb_auto_cron_sync_expenses_enabled')) {
			$this->qb_expense();
		}
	}

}

/* End of file Quickbooks_model.php */
/* Location: ../modules/quickbooks/model/Quickbooks_model.php */