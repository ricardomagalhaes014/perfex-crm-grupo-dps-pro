<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * load error page
 * @param  string $title  
 * @param  string $content
 * @return view         
 */
function infor_page($title = '',$content = '',$previous_link=''){
	$data['title'] = $title;
	$data['content'] = $content;  
	$data['previous_link'] = $previous_link;  
	$CI = & get_instance();                  
	$CI->data($data);
	$CI->view('client/info_page');
	$CI->layout();
}

/**
 * get all email contacts
 * @return $data_email
 */
function get_all_email_contacts(){
	$CI = & get_instance();                  
	$data = $CI->db->get(db_prefix() . 'contacts')->result_array();
	$data_email = [];
	foreach ($data as $key => $value) {
		$data_email[] = $value['email'];
	}
	return $data_email;
}
/**
 * cron job sync woo
 * @param  string $type    
 * @param  int $store   
 * @param  int $minutes 
 * @return  bolean         
 */
function cron_job_sync_woo($store = ''){

	$CI = & get_instance();      

	$CI->load->model('omni_sales/omni_sales_model');
	$CI->load->library('omni_sales/asynclibrary');
	$hour = date("H:i:s", time());
	$hour_cron = get_option('time_cron_woo');
    //records
	$records_time1 = get_option('records_time1');
	$records_time2 = get_option('records_time2');
	$records_time3 = get_option('records_time3');
	$records_time4 = get_option('records_time4');
	$records_time5 = get_option('records_time5');
	$records_time6 = get_option('records_time6');
	$records_time7 = get_option('records_time7');
	$records_time8 = get_option('records_time8');
	

	$config_store = $CI->omni_sales_model->get_setting_auto_sync_store($store);

	$sync_omni_sales_inventorys = $config_store[0]['sync_omni_sales_inventorys'];
	$sync_omni_sales_products = $config_store[0]['sync_omni_sales_products'];
	$sync_omni_sales_orders = $config_store[0]['sync_omni_sales_orders'];
	$sync_omni_sales_description = $config_store[0]['sync_omni_sales_description'];
	$sync_omni_sales_images = $config_store[0]['sync_omni_sales_images'];
	$price_crm_woo = $config_store[0]['price_crm_woo'];
	$product_info_enable_disable = $config_store[0]['product_info_enable_disable'];
	$product_info_image_enable_disable = $config_store[0]['product_info_image_enable_disable'];

	$minute_sync_inventory_info_time2 = $config_store[0]['time2'];
	$minute_sync_price_time3 = $config_store[0]['time3'];
	$minute_sync_decriptions_time4 = $config_store[0]['time4'];
	$minute_sync_images_time5 = $config_store[0]['time5'];
	$minute_sync_orders_time6 = $config_store[0]['time6'];
	$minute_sync_product_info_time7 = $config_store[0]['time7'];
	$minute_sync_product_info_images_time8 = $config_store[0]['time8'];
	if($store != ''){

		if($sync_omni_sales_orders == "1"){
			if(strtotime($hour) >= date('H:i:s', strtotime('+'.$minute_sync_orders_time6.' minutes', strtotime($records_time6)))){
				try {
					$result = $CI->omni_sales_model->process_orders_woo($store);
				} catch (Exception $e) {
					
				} finally {
					update_option('records_time6', date("H:i:s"));
				}
				update_option('records_time6', date("H:i:s"));
			}
		}
		if($sync_omni_sales_inventorys == "1"){
			if(strtotime($hour) >= date('H:i:s', strtotime('+'.$minute_sync_inventory_info_time2.' minutes', strtotime($records_time2)))){
				try {
					$result = $CI->omni_sales_model->process_inventory_synchronization_detail($store);
				} catch (Exception $e) {
					
				} finally {
					update_option('records_time2', date("H:i:s"));
				}
				update_option('records_time2', date("H:i:s"));
			}
		}

		if($sync_omni_sales_description == "1"){
			if(strtotime($hour) >= date('H:i:s', strtotime('+'.$minute_sync_decriptions_time4.' minutes', strtotime($records_time4)))){
				if(strtotime($hour) >= date('H:i:s', strtotime('+'.$minute_sync_orders_time6.' minutes', strtotime($records_time6)))){
					try {
						$result = $CI->omni_sales_model->process_decriptions_synchronization_detail($store);
					} catch (Exception $e) {
					} finally {
						update_option('records_time4', date("H:i:s"));
					}
					update_option('records_time4', date("H:i:s"));
				}

			}
		}
		if($sync_omni_sales_images == "1"){
			if(strtotime($hour) >= date('H:i:s', strtotime('+'.$minute_sync_images_time5.' minutes', strtotime($records_time5)))){
				try {
					$result = $CI->omni_sales_model->process_images_synchronization_detail($store);
				} catch (Exception $e) {
				} finally {
					update_option('records_time5', date("H:i:s"));
				}
				update_option('records_time5', date("H:i:s"));
			}
		}
		if($price_crm_woo == "1")
			if(strtotime($hour) >= date('H:i:s', strtotime('+'.$minute_sync_price_time3.' minutes', strtotime($records_time3)))){
				try {
					$result = $CI->omni_sales_model->process_price_synchronization($store);
				} catch (Exception $e) {
				} finally {
					update_option('records_time3', date("H:i:s"));
				}
				update_option('records_time3', date("H:i:s"));
			}
		}

		if($product_info_image_enable_disable == "1"){
			if(strtotime($hour) >= date('H:i:s', strtotime('+'.$minute_sync_product_info_images_time8.' minutes', strtotime($records_time8)))){
				$url = site_url()."omni_sales/omni_sales_client/sync_products_from_store/".$store;
				$success = $CI->asynclibrary->do_in_background($url, array());
				update_option('records_time8', date("H:i:s"));
			}
		}


		
		return true;
	}

/**
 * get all store 
 * @return  stores
 */
function get_all_store(){
	$CI = & get_instance();      
	$CI->load->model('omni_sales/omni_sales_model');
	return $CI->omni_sales_model->get_woocommere_store();
}

function get_name_store($id){
	$CI = & get_instance();      
	$CI->db->where('id', $id);
	return $CI->db->get(db_prefix().'omni_master_channel_woocommere')->row()->name_channel;
}
hooks()->add_action('after_email_templates', 'add_purchase_receipt_email_templates');

if (!function_exists('add_purchase_receipt_email_templates')) {
    /**
     * Init inventory email templates and assign languages
     * @return void
     */
    function add_purchase_receipt_email_templates()
    {
    	$CI = &get_instance();

    	$data['purchase_receipt_templates'] = $CI->emails_model->get(['type' => 'omni_sales', 'language' => 'english']);

    	$CI->load->view('omni_sales/purchase_receipt_email_template', $data);
    }
}


/**
 * omni sales reformat currency j
 * @param  [type] $value 
 * @return [type]        
 */
function omni_sales_reformat_currency_j($value)
{

	$f_dot = str_replace(',','', $value);
	return ((float)$f_dot + 0);
}

/**
 * omni sales get payment name
 * @param  integer $id 
 * @return [type]     
 */
function omni_sales_get_payment_name($id)
{
	$CI = & get_instance(); 

	$payment_name ='';
	$CI->db->where('id',$id);               
	$data = $CI->db->get(db_prefix() . 'payment_modes')->row();

	if($data){
		$payment_name .= $data->name;
	}
	return $payment_name;
}

/**
 * omni sales get customer name
 * @param  [type] $id 
 * @return [type]     
 */
function omni_sales_get_customer_name($id, $name)
{
	$customer_name ='';

	$CI = & get_instance(); 

	if(isset($id) && $id != ''){
		$CI->db->where(db_prefix() . 'clients.userid', $id);
		$client = $CI->db->get(db_prefix() . 'clients')->row();

		if($client){
			$customer_name .= $client->company;
		}
	}else{
		$customer_name .= $name;
	}

	return $customer_name;
}

/**
 * omni get user group name
 * @return  
 */
function omni_get_user_group_name($user_id){
	$CI = & get_instance(); 
	$data = $CI->db->query('select name from '.db_prefix().'customer_groups a left join '.db_prefix().'customers_groups b on a.groupid = b.id where customer_id = '.$user_id)->result_array();
	$result = '';
	foreach ($data as $item) {
		$result .= $item['name'].', ';
	}
	if($result != ''){
		$result = rtrim($result, ', ');
	}
	return $result;
}
/**
 * omni channel exists
 * @param  string $channel 
 * @return boolean          
 */
function omni_channel_exists($channel){
	$CI = & get_instance(); 
	$CI->db->where('channel', $channel);
	$sales_channel = $CI->db->get(db_prefix().'sales_channel')->row();
	if($sales_channel){	
		return true;
	}
	return false;
}

/**
 * get status modules wh
 * @param  string $module_name 
 * @return boolean             
 */
function omni_get_status_modules($module_name){
	$CI             = &get_instance();

	$sql = 'select * from '.db_prefix().'modules where module_name = "'.$module_name.'" AND active =1 ';
	$module = $CI->db->query($sql)->row();
	if($module){
		return true;
	}else{
		return false;
	}
}

/**
 * omni ppc get image file name
 * @param  integer $id 
 * @return object     
 */
function omni_ppc_get_image_file_name($id){
	$CI             = &get_instance();
	$CI->db->where('rel_id',$id);
	$CI->db->where('rel_type','commodity_item_file');
	$CI->db->select('file_name');
	$CI->db->order_by('dateadded', 'desc');
	return $CI->db->get(db_prefix().'files')->row();
}

/**
   * get image items
   * @param  integer $item_id 
   * @return string          
   */
function omni_get_image_items($item_id){
	$file_path_rs  = site_url('modules/omni_sales/assets/images/no_image.jpg');
	$data_file = omni_ppc_get_image_file_name($item_id);
	if($data_file){
		$file_path_rs = omni_check_image_items($item_id, $data_file->file_name);
	}
	return $file_path_rs;
}

/**
 * omni check image items
 * @param  integer $item_id   
 * @param  string $file_name 
 * @return string            
 */
function omni_check_image_items($item_id, $file_name){
	$file_path = '';
	if(omni_get_status_modules('warehouse') == true && omni_get_status_modules('purchase') == false && omni_get_status_modules('manufacturing') == false){ 
		$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';

		if($file_name!=''){
			$file_path  = 'modules/warehouse/uploads/item_img/'.$item_id.'/'.$file_name;
			if(!file_exists(FCPATH.$file_path)){
				$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';
			}
		}
	}else if(omni_get_status_modules('purchase') == true && omni_get_status_modules('warehouse') == false && omni_get_status_modules('manufacturing') == false){
		$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';

		if($file_name!=''){
			$file_path  = 'modules/purchase/uploads/item_img/'.$item_id.'/'.$file_name;
			if(!file_exists(FCPATH.$file_path) ){
				$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';
			}
		}
	}else if(omni_get_status_modules('manufacturing') == true && omni_get_status_modules('warehouse') == false && omni_get_status_modules('purchase') == false){
		$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';

		if($file_name!=''){
			$file_path  = 'modules/manufacturing/uploads/products/'.$item_id.'/'.$file_name;
			if(!file_exists(FCPATH.$file_path) ){
				$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';
			}
		}
	}
	else if(omni_get_status_modules('purchase') == true && omni_get_status_modules('warehouse') == true && omni_get_status_modules('manufacturing') == true){
		if($file_name!=''){
			$file_path  = 'modules/warehouse/uploads/item_img/'.$item_id.'/'.$file_name;
			if(!file_exists(FCPATH.$file_path) ){
				$file_path  = 'modules/purchase/uploads/item_img/'.$item_id.'/'.$file_name;
				if(!file_exists(FCPATH.$file_path) ){
					$file_path  = 'modules/manufacturing/uploads/products/'.$item_id.'/'.$file_name;
					if(!file_exists(FCPATH.$file_path) ){
						$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';
					}
				}
			}
		}
	}else{
		$file_path  = 'modules/omni_sales/assets/images/no_image.jpg';
	}
	return site_url($file_path);
}


/**
 * email staff
 *
 * @param        $staffid  The staffid
 *
 */
function omni_email_staff($staffid){
	$CI = & get_instance();
	$CI->db->where('staffid', $staffid);
	return $CI->db->get(db_prefix().'staff')->row()->email;
}
/**
 * get status by index
 * @param  integer $index 
 * @return string        
 */
function get_status_by_index($index, $return_obj = false){
	$status = '';
	$slug = '';
	switch ($index) {
		case 0:
		$status = _l('omni_draft');
		$slug = 'draft';
		break;  
		case 1:
		$status = _l('processing');
		$slug = 'processing';
		break;      
		case 2:
		$status = _l('pending_payment');
		$slug = 'pending_payment';
		break;
		case 3:
		$status = _l('confirm');
		$slug = 'confirm';
		break;
		case 4:
		$status = _l('shipping');
		$slug = 'shipping';
		break;
		case 5:
		$status = _l('finish');
		$slug = 'finish';
		break;
		case 6:
		$status = _l('refund');
		$slug = 'refund';
		break;
		case 7:
		$status = _l('omni_return');
		$slug = 'return';
		break; 
		case 8:
		$status = _l('cancelled');
		$slug = 'cancelled';
		break;  
		case 9:
		$status = _l('omni_on_hold');
		$slug = 'on-hold';
		break;  
		case 10:
		$status = _l('omni_failed');
		$slug = 'failed';
		break; 
	}
	if($return_obj){
		$obj = new stdClass();
		$obj->status = $status;
		$obj->slug = $slug;
		return $obj;
	}
	return $status;
}

/**
 * get index by status
 * @param  string $status 
 * @return integer        
 */
function get_index_by_status($status){
	$index = 0;
	switch ($status) {
		case 'draft':
		$index = 0;
		break;  
		case 'processing':
		$index = 1;
		break;  
		case 'pending':
		$index = 2;
		break;     
		case 'pending_payment':
		$index = 2;
		break;
		case 'confirm':
		$index = 3;
		break;
		case 'shipping':
		$index = 4;
		break;
		case 'finish':
		$index = 5;
		break;
		case 'completed':
		$index = 5;
		break;
		case 'refund':
		$index = 6;
		break;
		case 'refunded':
		$index = 6;
		break;
		case 'return':
		$index = 7;
		break; 
		case 'cancelled':
		$index = 8;
		break; 
		case 'on-hold':
		$index = 9;
		break;
		case 'failed':
		$index = 10;
		break;
	}
	return $index;
}

/**
 * get status by index woo
 * @param  integer $index 
 * @return string        
 */
function get_status_by_index_woo($index){
	$status = '';
	switch ($index) {
		case 1:
		$status = 'processing';
		break;
		case 2:
		$status = 'pending';//pending_payment
		break;
		case 5:
		$status = "completed";//finish
		break;
		case 6:
		$status = 'refunded';//refund
		break;
		case 8:
		$status = 'cancelled';
		break;
		case 9:
		$status = 'on-hold';
		break;
		case 10:
		$status = 'failed';
		break;
	}
	return $status;
}

/**
 * get all woo_customer_id
 * @return $woo_customer_ids
 */
function get_all_woo_customer_id(){
	$CI = & get_instance();                  
	$data = $CI->db->get(db_prefix() . 'clients')->result_array();
	$woo_customer_ids = [];
	foreach ($data as $key => $value) {
		$woo_customer_ids[] = $value['woo_customer_id'];
	}
	return $woo_customer_ids;
}
/**
 * get taxes
 * @param  integer $id
 * @return array or row
 */
function omni_get_taxes($id =''){
    $CI           = & get_instance();

    if (is_numeric($id)) {
        $CI->db->where('id',$id);

        return $CI->db->get(db_prefix().'taxes')->row();
    }
    $CI->db->order_by('taxrate', 'ASC');
    return $CI->db->get(db_prefix().'taxes')->result_array();

}

/**
 * omni status list
 * @return array 
 */
function omni_status_list(){
	return [
		['id' => 0, 'label' => _l('omni_draft'), 'key' => 'draft'],
		['id' => 1, 'label' => _l('processing'), 'key' => 'processing'],
		['id' => 2, 'label' => _l('pending_payment'), 'key' => 'pending_payment'],
		['id' => 3, 'label' => _l('confirm'), 'key' => 'confirm'],
		['id' => 4, 'label' => _l('shipping'), 'key' => 'shipping'],
		['id' => 5, 'label' => _l('finish'), 'key' => 'finish'],
		['id' => 6, 'label' => _l('refund'), 'key' => 'refund'],
		['id' => 8, 'label' => _l('omni_canceled'), 'key' => 'canceled'],
		['id' => 9, 'label' => _l('omni_on_hold'), 'key' => 'on_hold'],
		['id' => 10, 'label' => _l('omni_failed'), 'key' =>'failed']
	];
}

/**
 * count portal order
 * @param  integer $status 
 * @return integer          
 */
function count_portal_order($userid, $status = 0, $channel_id = '', $where = ''){
	if(is_numeric($userid)){
		$CI           = & get_instance();
		$CI->db->select('id');
		if(is_numeric($channel_id)){
			$CI->db->where('channel_id', $channel_id);   
		}
		if($where != ''){
			$CI->db->where($where);   
		}
		$CI->db->where('userid', $userid);
		$CI->db->where('status', $status);
		return $CI->db->get(db_prefix().'cart')->num_rows();
	}
	return 0;
}