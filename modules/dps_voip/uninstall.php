<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();
// Não apagar dados por segurança - apenas remover opções
delete_option('dps_voip_twilio_account_sid');
delete_option('dps_voip_twilio_auth_token');
delete_option('dps_voip_twilio_app_sid');
delete_option('dps_voip_record_calls');
delete_option('dps_voip_default_timeout');
