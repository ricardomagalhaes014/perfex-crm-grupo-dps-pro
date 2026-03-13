<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function get_clientID() {
	return get_option('qb_test_mode_enabled') == '1' ? get_option('qb_client_id_demo') : get_option('qb_client_id');
}
function get_clientSecret() {
	return get_option('qb_test_mode_enabled') == '1' ? get_option('qb_client_secret_demo') : get_option('qb_client_secret');
}

if (!function_exists('get_field_value')) {
/**
 * Helps to get a specific value in atable field
 * @param  [type] $table
 * @param  array  $where
 * @param  [type] $check
 * @return string
 */

	function get_field_value($table, $where = array(), $check) {
		$CI = &get_instance();
		$CI->db->where($where);
		$data = $CI->db->get($table)->row();
		if (!empty($data)) {
			return $data->$check;
		} else {
			return null;
		}
	}
}

if (!function_exists('print_account_chart')) {

/**
 * Chart of Accounts body
 * @param  [type] $_account
 * @return [string]
 */
	function print_account_chart($_account) {
		$CI = &get_instance();

		$chart_output = "";
		$i_g = 1;
		foreach ($_account as $g_key => $group) {
			$group_no = sprintf("%02s", $i_g); ++$i_g;
			$chart_output .= '<tr class="tr-group tr-root-group"><td>' . $group_no . '</td><td>' . $g_key . '</td><td>Group</td><td align="right">-</td><td align="right">-</td></tr>';
			$i_sg = 1;
			foreach ($group as $sg_key => $sub_group) {
				$sub_group_no = sprintf("%02s", $i_sg); ++$i_sg;
				$chart_output .= '<tr class="tr-group tr-sub-group"><td>' . print_space(1) . $group_no . '-' . $sub_group_no . '</td><td>' . print_space(1) . $sg_key . '</td><td>Group</td><td align="right">-</td><td align="right">-</td></tr>';
				$i_a = 1;
				foreach ($sub_group as $l_key => $ledger) {
					$ledger_no = sprintf("%04s", $i_a); ++$i_a;
					$chart_output .= '<tr class="tr-ledger"><td>' . print_space(2) . $group_no . '-' . $sub_group_no . '-' . $ledger_no . '</td><td>' . print_space(2) . $ledger->Name . '</td><td>Ledger</td><td align="right">' . (double) $ledger->OpeningBalance . '</td><td align="right">' . (double) $ledger->CurrentBalance . '</td></tr>';
				}
			}
		}

		if (get_option('qb_access_token') && time() < get_option('qb_expiration_date')):
			return $chart_output;
		else:return null;
		endif;
	}
}

if (!function_exists('print_space')) {
/**
 * Add spaces to chart of acc
 * @param  [type] $count
 * @return string
 */
	function print_space($count) {
		$html = '';
		for ($i = 1; $i <= $count; $i++) {
			$html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		return $html;
	}
}

/**
 * Get Quick Books TAX RATE ID
 * @param  [type] $tax_id
 * @return int
 */
function get_qb_id_from_tax_id($tax_id) {
	$CI = &get_instance();
	$CI->load->model('quickbooks_model');
	$result = $CI->quickbooks_model->getTax(['taxes.id' => $tax_id]);
	if ($result) {
		return $result->qb_id;
	} else {
		return '';
	}

}

/**
 * Function that return invoice item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_invoice_item_tax_qb_id($itemid) {
	$CI = &get_instance();
	$CI->db->where('itemid', $itemid);
	$CI->db->where('rel_type', 'invoice');
	$taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
	$i = 0;
	$taxes_qb = array();
	foreach ($taxes as $tax) {
		$taxes_r = $CI->db->where(array('name' => $tax['taxname'], 'taxrate' => $tax['taxrate']))->get(db_prefix() . 'taxes')->row();
		if (!empty($taxes_r)) {
			$taxes_qb[] = get_qb_id_from_tax_id($taxes_r->id);
		}
	}

	return $taxes_qb;
}

/**
 * Get Quick Books CUSOMER ID
 * @param  [type] $client_id
 * @return int
 */
function get_qb_id_from_client_id($client_id) {
	$CI = &get_instance();
	$CI->load->model('clients_model');
	$result = $CI->clients_model->get($client_id);
	return $result->qb_id;
}

function format_qb_filter_description($string) {
	return preg_replace("/[[:blank:]]+/", " ", preg_replace("/\r|\n/", " ", $string));
}