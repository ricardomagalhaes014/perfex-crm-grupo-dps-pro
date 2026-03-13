<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Mailchimp | Integração com o Mailchimp
Description: Integração com o Mailchimp. Sincronize todos os contatos com o Mailchimp e do Mailchimp para o sistema.
Version: 1.0.0
Requires at least: 2.3.*
Author: Unknown | Grupo Liquida I.A no Telegram
Author URI: https://t.me/crmperfex
*/
define('MAILCHIMP_MODULE_NAME', 'mailchimp');
$companyname = !empty(get_option('companyname'))?get_option('companyname'):'Our System';
define('SYNC_COMPANY_NAME', $companyname);
$CI = &get_instance();
/**
* Register activation module hook
*/
register_activation_hook(MAILCHIMP_MODULE_NAME, 'mailchimp_module_activation_hook');
function mailchimp_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
    require_once(__DIR__ . '/libraries/MailchimpAPIWrapper.php');
}
hooks()->add_action('admin_init', 'mailchimp_module_init_menu_items');

$capabilities = [];

$capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')'
];

register_staff_capabilities('mailchimp', $capabilities, _l('mailchimp'));
/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(MAILCHIMP_MODULE_NAME, [MAILCHIMP_MODULE_NAME]);

function mailchimp_module_init_menu_items()
{
	if(has_permission('mailchimp','','view')){
		$CI = &get_instance();

		$CI->app_menu->add_setup_menu_item('mailchimp', [
			'name'     => '<img class="side_menu_mailchimp_setup_icon" src="'.site_url().'modules/mailchimp/assets/logo_new_white.png">',
			'collapse' => true, 
			'position' => 98, 
		]);
		
		$CI->app_menu->add_setup_children_item('mailchimp', [
			'slug'     => 'mailchimp-config', 
			'name'     => _l('mailchimp_configuration'),
			'href'     => admin_url('mailchimp'),
			'position' => 15,
		]);
		$CI->app_menu->add_setup_children_item('mailchimp', [
			'slug'     => 'mailchimp-audience', 
			'name'     => _l('mailchimp_audience'),
			'href'     => admin_url('mailchimp/audience'),
			'position' => 20,
		]);
		$CI->app_menu->add_setup_children_item('mailchimp', [
			'slug'     => 'mailchimp-stores',
			'name'     => _l('mailchimp_stores'),
			'href'     => admin_url('mailchimp/stores'),
			'position' => 25,
		]);

		//Sidebar menu
        $CI->load->helper('mailchimp/mailchimp');
        if(isExistMailChimp()){
    		$CI->app_menu->add_sidebar_menu_item('Mailchimp', [
    			'slug'     => 'mailchimp_sidebar',
    			'name'     => _l('Mailchimp'),
    			'position' => 250,
    			'icon'     => 'fa fa-envelope'
    		]);
    		$CI->app_menu->add_sidebar_children_item('Mailchimp', [
    			'slug'     => 'mailchimp_contact', 
    			'name'     => _l('menu_contacts'),
    			'href'     => admin_url('mailchimp/contacts'), 
    			'position' => 1,
    		]);
        }

    }
}
//Css Load
hooks()->add_action('app_admin_assets_added', 'mailchim_css_add');
function mailchim_css_add(){
	$CI = &get_instance();
    $CI->app_css->add('custom-css', base_url('modules/'.MAILCHIMP_MODULE_NAME.'/assets/css/custom.css'), 'admin', ['app-css']);
}
hooks()->add_action('contact_updated', 'contact_add_update_to_mailchimp',10,1);
hooks()->add_action('contact_created', 'contact_add_update_to_mailchimp',10,1);
function contact_add_update_to_mailchimp($id){
	$CI = &get_instance();
	$CI->load->helper('mailchimp/mailchimp');
	$CI->load->library('mailchimp/MailchimpAPIWrapper');
	if(isExistMailChimp()){
		$CI->db->where('id', $id);
		$contact = $CI->db->get(db_prefix() . 'contacts')->row();
        if($contact){
            $mailchimp_param = array(
                "firstname" => $contact->firstname,
                "lastname" => $contact->lastname,
                "email" => $contact->email,
                "phone" => $contact->phonenumber,
                "position" => $contact->title,
                "customer_id" => $contact->userid,
                "contact_id" => $contact->id,
            );
            $audience_ids = get_contact_assigned_audience_ids($contact->id);
            if(empty($audience_ids)){
               $audience_ids = array(get_default_mailchimp_audience_has_id());
            }
            $mailchimpApi = new MailchimpAPIWrapper;
            if(is_array($audience_ids)){
                foreach( $audience_ids as $key=>$value){
                    $mailchimp_param['mailchimp_audience_id'] = $value;
                    $contact_audience_details = get_contact_assigned_audience_details($contact->id,$value);
                    if(!empty($contact_audience_details)){
                        $mailchimp_param['status'] = $contact_audience_details->mailchimp_subscribed_status;
                    }else{
                        $mailchimp_param['status'] = 'subscribed';
                    }
                    $result = $mailchimpApi->create_update_mailchimp_member($mailchimp_param );
                    $result = json_decode($result);
                    if($result->status == 'success'){
                        if(!empty($result->mailchimp_id)){
                            $update_con_aud = update_contact_audience($contact->id,$result->list_id,$result->mailchimp_status);
                        }
                    }
                }
            }
        }
    }
}
hooks()->add_action('contact_deleted', 'contact_deleted_from_mailchimp',10,2);
function contact_deleted_from_mailchimp($id,$result){
	$CI = &get_instance();
	$CI->load->helper('mailchimp/mailchimp');
	$CI->load->library('mailchimp/MailchimpAPIWrapper');
	if(isExistMailChimp()){
        $audience_ids = get_contact_assigned_audience_ids($id);
        $mailchimpApi = new MailchimpAPIWrapper;
        if(is_array($audience_ids)){
            foreach( $audience_ids as $key=>$audience_id){
                $response = $mailchimpApi->delete_mailchimp_member(md5(strtolower($result->email)),$audience_id);
                $CI->db->where(['contact_id'=> $result->id,'mailchimp_audience_id' => $audience_id]);
                $CI->db->delete(db_prefix() . 'mailchimp_contact_audience');
            }
        }
    }
}
//Estimate hook action define here
hooks()->add_action('after_estimate_added', 'estimate_add_update_to_mailchimp',10,1);
hooks()->add_action('after_estimate_updated', 'estimate_add_update_to_mailchimp',10,1);
hooks()->add_action('before_estimate_deleted', 'estimate_deleted_from_mailchimp',10,1);
function estimate_add_update_to_mailchimp($id){
	$CI = &get_instance();
	$CI->load->helper('mailchimp/mailchimp');
	$CI->load->library('mailchimp/MailchimpAPIWrapper');
	if(isExistMailChimp()){
        $mailchimpApi = new MailchimpAPIWrapper;
        $result = $mailchimpApi->create_mailchimp_order($id);
        $result = json_decode($result);
        if($result->status == 'success'){
          if(!empty($result->mailchimp_id)){
            $store_id = get_audience_id_of_store($result->store_id);
            $CI->db->where('id', $id);
            $CI->db->update(db_prefix() . 'estimates', [
                'mailchimp_estimate_id' => $result->mailchimp_id,
                'mailchimp_audience_id' => $store_id,
            ]); 
          }  
        }
    }
}
function estimate_deleted_from_mailchimp($id){
	$CI = &get_instance();
	$CI->load->helper('mailchimp/mailchimp');
	$CI->load->library('mailchimp/MailchimpAPIWrapper');
    $CI->db->where('id', $id);
    $estimate = $CI->db->get(db_prefix() . 'estimates')->row();
    if(!empty($estimate)){
    	if(isExistMailChimp() && !empty($estimate->mailchimp_estimate_id)){
            $mailchimpApi = new MailchimpAPIWrapper;
            $result = $mailchimpApi->delete_mailchimp_order($estimate->mailchimp_estimate_id);
        }
    }
}
hooks()->add_action('estimates_table_columns', 'link_estimates_table_columns');
hooks()->add_action('estimates_table_row_data', 'link_estimates_table_row_data',10,2);
hooks()->add_action('estimates_table_sql_columns', 'link_estimates_table_sql_columns');

function link_estimates_table_columns($table_data){
    $CI = &get_instance();
    $CI->load->helper('mailchimp/mailchimp');
    if(isExistMailChimp()){
        $table_data[] =  _l('mailchimp_linked');
    }
    return $table_data;
}
function link_estimates_table_row_data($row,$aRow){
    $CI = &get_instance();
    $CI->load->helper('mailchimp/mailchimp');
    if(isExistMailChimp()){
        if(empty($aRow['mailchimp_estimate_id'])){
           $row[]='<a href="'.admin_url().'mailchimp/link_estimate_to_mailchimp/'.$aRow["id"].'" class="btn btn-info link-to-mailchimp-btn">' . _l('add_to') . ' <img src="'.site_url().'modules/mailchimp/assets/logo.svg" class="mailchimp_logo_bg_color" /></a>'; 
        }else{
            $row[] = '<a href="#" class="linked-mailchimp-btn mailchimp-linked-txt-color"><img src="'.site_url().'modules/mailchimp/assets/logo.svg" />' . _l(' mailchimp_linked') . '</a>';
        }
    }
    return $row;
}
function link_estimates_table_sql_columns($aColumns){
    $CI = &get_instance();
    $CI->load->helper('mailchimp/mailchimp');
    if(isExistMailChimp()){
        $aColumns[] = db_prefix() . 'estimates.mailchimp_estimate_id as mailchimp_estimate_id';
    }
    return $aColumns;
}


 
