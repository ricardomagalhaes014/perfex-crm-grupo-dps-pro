<?php

defined('BASEPATH') or exit('No direct script access allowed');

update_option('qb_qb_active', '0');
//Disconnect Quickbooks
$CI = &get_instance();
$CI->load->model('quickbooks_model');
$result = $CI->quickbooks_model->disconnect();

if ($result) {
//Clear access params
	update_option('qb_access_token', '');
	update_option('qb_refresh_token', '');
	update_option('qb_expiration_date', '');
	update_option('qb_is_expired', '');
	update_option('qb_realmid', '');
	update_option('qb_refresh_token_expires_in', '');
}
