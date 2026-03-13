<?php
defined('BASEPATH') or exit('No direct script access allowed');
require 'modules/omni_sales/third_party/WooCommerce/HttpClient/OAuth.php';
require 'modules/omni_sales/third_party/WooCommerce/HttpClient/BasicAuth.php';
require 'modules/omni_sales/third_party/WooCommerce/HttpClient/HttpClientException.php';
require 'modules/omni_sales/third_party/WooCommerce/HttpClient/HttpClient.php';
require 'modules/omni_sales/third_party/WooCommerce/HttpClient/Options.php';
require 'modules/omni_sales/third_party/WooCommerce/HttpClient/Request.php';
require 'modules/omni_sales/third_party/WooCommerce/HttpClient/Response.php';
require 'modules/omni_sales/third_party/WooCommerce/Client.php';
use Automattic\WooCommerce\Client;
/**
 * Omni sales model
 */
class Omni_sales_model extends App_Model
{
	/**
	 * change active channel 
	 * @param   array  $data  
	 * @return  bool         
	 */
	public $amount = 100;
	public $per_page_tags = 100;
	public $per_page_product_categories = 100;

	public function change_active_channel($data){
		$this->db->where('channel',$data['channel']);
		$this->db->update(db_prefix().'sales_channel',$data);
		return true;
	}   
	/**
	 * get sales channel by channel   
	 * @param  string $channel    
	 * @return  object              
	 */
	public function get_sales_channel_by_channel($channel =''){
		if($channel != ''){
			$this->db->where('channel',$channel);
			return $this->db->get(db_prefix().'sales_channel')->row();
		}
		else{
			return $this->db->get(db_prefix().'sales_channel')->result_array();
		}
	}
	/**
	 *  get_group_product  
	 * @param  int $id   
	 * @return  object or array object        
	 */
	public function get_group_product($id = ''){
		if($id != ''){
			$this->db->where('id',$id);
			return $this->db->get(db_prefix().'items_groups')->row();
		}
		else{
			return $this->db->get(db_prefix().'items_groups')->result_array();
		}
	}
	/**
	 *  get_product_by_group   
	 * @param  string $group_id    
	 * @return  array object               
	 */
	public function get_product_by_group($group_id = '', $channel = ''){
		if($group_id != '' && $group_id != null && $group_id != 0){
			$this->db->select('id');      
			$this->db->select('description'); 
			$this->db->select('commodity_code');  
			$this->db->where('group_id', $group_id);
			if($channel == 'pos'){
				$this->db->where('((parent_id is null OR parent_id = 0) AND (CHAR_LENGTH(parent_attributes) = 28 OR parent_attributes is null OR parent_attributes = "[]")) OR parent_id > 0');
			}
			elseif($channel == 'portal'){
				$this->db->where('(parent_id is null or parent_id = 0)');
			}
			return $this->db->get(db_prefix().'items')->result_array();
		}
		else{
			$this->db->select('id');      
			$this->db->select('description');
			$this->db->select('commodity_code'); 
			if($channel == 'pos'){
				$this->db->where('((parent_id is null OR parent_id = 0) AND (CHAR_LENGTH(parent_attributes) = 28 OR parent_attributes is null OR parent_attributes = "[]")) OR parent_id > 0');
			}
			elseif($channel == 'portal'){
				$this->db->where('parent_id is null OR parent_id = 0');
			}      
			return $this->db->get(db_prefix().'items')->result_array();
		}
	} 
	/**
	 *  get product   
	 * @param  int $id    
	 * @return  object or array object       
	 */
	public function get_product($id = ''){
		if($id != ''){
			$this->db->select(db_prefix() . 'ware_unit_type.unit_name'.','.db_prefix() . 'items.*');
			$this->db->join(db_prefix() . 'ware_unit_type', db_prefix() . 'ware_unit_type.unit_type_id=' . db_prefix() . 'items.unit_id', 'left');
			$this->db->where('id',$id);
			return $this->db->get(db_prefix().'items')->row();
		}
		else{     
			return $this->db->get(db_prefix().'items')->result_array();
		}
	}
	/**
	 *  add_product   
	 * @param  array  $data 
	 * @return  int $insert_id
	 */
	public function add_product($data){
		$insert_id = 0;
		$department_list_id = '';
		$customer_group_list_id = '';
		$customer_list_id = '';
		
		if(isset($data['department_id'])){
			$department_list_id = implode(',', $data['department_id']);
		}
		if(isset($data['customer_group'])){
			$customer_group_list_id = implode(',', $data['customer_group']);
		}
		if(isset($data['customer'])){
			$customer_list_id = implode(',', $data['customer']);
		}
		if($data['group_product_id'] && empty($data['product_id'])){
			$items = $this->get_all_product_group($data['group_product_id']);
			foreach ($items as $key => $value) {
				if($data['prices'] == ''){
					$get_data = $this->omni_sales_model->get_product($value['id']);
					if($get_data){
						$prices = $get_data->rate;
					} 
				}else{
					$prices = str_replace(',', '', $data['prices']);
				}
				$data_add['sales_channel_id'] = $data['sales_channel_id'];
				$data_add['group_product_id'] = $data['group_product_id'];
				$data_add['product_id'] = $value['id'];
				$data_add['prices'] = $prices;
				$data_add['department'] = $department_list_id;
				$data_add['customer_group'] = $customer_group_list_id;
				$data_add['customer'] = $customer_list_id;
				$data_saved = $this->get_product_channel($value['id'],$data['sales_channel_id']);
				if($data_saved){
					$this->db->where('id', $data_saved->id);
					$this->db->update(db_prefix() .'sales_channel_detailt', $data_add);
				}
				else{
					$this->db->insert(db_prefix() .'sales_channel_detailt', $data_add);
				}
			}
		}
		foreach ($data['product_id'] as $key => $value) {
			$prices = 0;
			if($data['prices'] == ''){
				$get_data = $this->omni_sales_model->get_product($value);
				if($get_data){
					$prices = $get_data->rate;
				} 
			}
			else{
				$prices = str_replace(',', '', $data['prices']);
			}

			$data_add['sales_channel_id'] = $data['sales_channel_id'];
			$data_add['group_product_id'] = $data['group_product_id'];
			$data_add['product_id'] = $value;
			$data_add['prices'] = $prices;
			$data_add['department'] = $department_list_id;
			$data_add['customer_group'] = $customer_group_list_id;
			$data_add['customer'] = $customer_list_id;
			$data_saved = $this->get_product_channel($value,$data['sales_channel_id']);

			if($data_saved){
				$this->db->where('id', $data_saved->id);
				$this->db->update(db_prefix() .'sales_channel_detailt', $data_add);
			}
			else{

				$this->db->insert(db_prefix() .'sales_channel_detailt', $data_add);
			}
			$insert_id = 1;     
		}   
		return $insert_id;
	}

	/**
	 *  delete_product  
	 * @param   int $id   
	 * @return  bool       
	 */
	public function delete_product($id){
		$this->db->where('id',$id);
		$this->db->delete(db_prefix().'sales_channel_detailt');
		if ($this->db->affected_rows() > 0) {           
			return true;
		}
		return false;
	}
	/**
	 *  add channel woocommerce   
	 * @param  array  $data 
	 * @return  int $insert_id     
	 */
	public function add_channel_woocommerce($data){
		$this->db->insert('omni_master_channel_woocommere', $data);
		$insert_id = $this->db->insert_id();
		return $insert_id;
	}
	/**
	 *  update channel woocommerce  
	 * @param   array  $data   
	 * @param   int  $id     
	 * @return  bool          
	 */
	public function update_channel_woocommerce($data, $id){
		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'omni_master_channel_woocommere', $data);
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}
	/**
	 *  delete channel woocommerce   
	 * @param   int  $id    
	 * @return  bool         
	 */
	public function delete_channel_woocommerce($id){
		$this->db->where('id',$id);
		$this->db->delete(db_prefix().'omni_master_channel_woocommere');
		if ($this->db->affected_rows() > 0) {           
			return true;
		}
		return false;
	}
	/**
	 *  add product channel wcm   
	 * @param  array  $data 
	 * @return  int insert_id      
	 */
	public function add_product_channel_wcm($data){
		$insert_id = 0;
		if(isset($data[0])){
			$data['woocommere_store_id'] =  implode($data[0]);
			$data['group_product_id'] =  implode($data[1]);
			$data['prices'] =  implode($data[3]);
			$data['product_id'] =  $data[2];
			unset($data[0]);
			unset($data[1]);
			unset($data[2]);
			unset($data[3]);
		}
		if($data['group_product_id'] && empty($data['product_id'])){
			$items = $this->get_all_product_group($data['group_product_id']);
			foreach ($items as $key => $value) {
				if($data['prices'] == ''){
					$get_data = $this->omni_sales_model->get_product($value['id']);
					if($get_data){
						$prices = $get_data->rate;
					} 
				}else{
					$prices = str_replace(',', '', $data['prices']);
				}
				$data_add['woocommere_store_id'] = $data['woocommere_store_id'];
				$data_add['group_product_id'] = $data['group_product_id'];
				$data_add['product_id'] = $value['id'];
				$data_add['prices'] = $prices;
				$data_saved = $this->get_product_channel($value['id'],$data['woocommere_store_id']);
				if($data_saved){
					$this->db->where('id', $data_saved->id);
					$this->db->update('woocommere_store_detailt', $data_add);
				}
				else{
					$this->db->insert('woocommere_store_detailt', $data_add);
				}
				$insert_id = 1;     
			}
			return $insert_id;
		}

		foreach ($data['product_id'] as $key => $value) {
			$prices = 0;
			if($data['prices'] == ''){
				$get_data = $this->get_product($value);
				if($get_data){
					$prices = $get_data->rate;
				} 
			}
			else{
				$prices = str_replace(',', '', $data['prices']);
			}
			$data_add['woocommere_store_id'] = $data['woocommere_store_id'];
			$data_add['group_product_id'] = $data['group_product_id'];
			$data_add['product_id'] = $value;
			$data_add['prices'] = $prices;
			$data_saved = $this->get_woocommere_store_detailt($value,$data['woocommere_store_id']);
			if($data_saved){
				$this->db->where('id', $data_saved->id);
				$this->db->update('woocommere_store_detailt', $data_add);
				$this->process_price_synchronization_update_product($data_add['woocommere_store_id'], $data_add['prices'], $data_add['product_id']);
			}
			else{
				$this->db->insert('woocommere_store_detailt', $data_add);
			}
			$insert_id = 1;     
		}   
		return $insert_id;
	}
	/**
	 *  get_woocommere_store_detailt 
	 * @param   int  $product_id           
	 * @param   int  $woocommere_store_id  
	 * @return  object                        
	 */
	public function get_woocommere_store_detailt($product_id, $woocommere_store_id, $return_array = false){
		$this->db->where('product_id', $product_id);
		$this->db->where('woocommere_store_id', $woocommere_store_id);
		if($return_array == false){
			return $this->db->get('woocommere_store_detailt')->row();
		}else{
			return $this->db->get('woocommere_store_detailt')->result_array();
		}
	}
	/**
	 *  get woocommere store   
	 * @param   int  $id    
	 * @return  object         
	 */
	public function get_woocommere_store($id = ''){
		if($id != ''){
			$this->db->where('id', $id);
			return $this->db->get(db_prefix().'omni_master_channel_woocommere')->row();
		}else{
			return $this->db->get(db_prefix().'omni_master_channel_woocommere')->result_array();
		}
	}


	/**
	 *  get list product by group
	 * @param  int  $id_chanel 
	 * @param  int  $id_group  
	 * @param  string  $key       
	 * @param  integer $limit     
	 * @param  integer $ofset     
	 * @return  array $result              
	 */
	public function get_list_product_by_group($id_chanel, $id_group = '0', $id_warehouse = '', $key = '',$limit = 0, $ofset = 1){
		// Product by warehouse
		$warehouse = '';
		if($id_warehouse != '0'){
			if($id_chanel != ''){
				if($id_chanel != 2){
					$warehouse = ' and product_id in (SELECT a.id FROM '.db_prefix().'items a left join '.db_prefix().'inventory_manage b on a.id = b.commodity_id where (without_checking_warehouse = 1 AND b.warehouse_id = '.$id_warehouse.') OR a.id in (SELECT commodity_id FROM '.db_prefix().'inventory_manage where warehouse_id = '.$id_warehouse.' group by commodity_id having sum(inventory_number) > 0))';
				}
			}
		}
		else{
			if($id_chanel != ''){
				if($id_chanel != 2){
					$warehouse = ' and product_id in (SELECT id FROM '.db_prefix().'items where without_checking_warehouse = 1 OR id in (SELECT commodity_id FROM '.db_prefix().'inventory_manage group by commodity_id having sum(inventory_number) > 0))';
				}
			}
		}
		// Search product
		$search = '';
		if($key!=''){
			$search = ' and (description like \'%'.$key.'%\' or rate like \'%'.$key.'%\' or sku_code like \'%'.$key.'%\' or commodity_barcode like \'%'.$key.'%\') ';
		}

		// Product by group
		$group = '';
		if($id_group != '0'){
			$group = ' and group_id = '.$id_group.'';
		}

		// Product by department
		$department = '';
		if(get_option('omni_show_products_by_department') == 1){
			$this->load->model('departments_model');
			$staff_departments = $this->departments_model->get_staff_departments(get_staff_user_id());
			$dep_query = '';
			foreach ($staff_departments as $key => $department) {
				$dep_query .= 'find_in_set('.$department['departmentid'].', department) or ';
			}

			if($dep_query != ''){
				$dep_query = rtrim($dep_query, ' or ');
				$department = ' and ('.$dep_query.')';
			}
		}

		// Product of channel
		$channel = '';
		if($id_chanel != ''){
			$query_channel = '';
			if($id_chanel == 1){
				//POS
				$query_channel = ' and product_id in (SELECT id FROM '.db_prefix().'items where ((parent_id is null OR parent_id = 0) AND (CHAR_LENGTH(parent_attributes) = 28 OR parent_attributes is null OR parent_attributes = "[]")) OR parent_id > 0)';
			}
			elseif($id_chanel == 2){
				//Portal
				$query_channel = ' and product_id in (SELECT id FROM '.db_prefix().'items where parent_id = 0 or parent_id is null)';
			}
			$channel = ' id in (select product_id from '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$id_chanel.''.$warehouse.''.$department.''.$query_channel.')';
		}

		$where = $channel.''.$group.''.$search;
		if($where != ''){
			$where = 'where'.$where;
		}

		$count_product = 'select count(id) as count from '.db_prefix().'items '.$where;
		$select_list_product = 'select id, parent_id, description, long_description, rate, sku_code, tax, group_id, commodity_barcode, parent_attributes, without_checking_warehouse from '.db_prefix().'items '.$where.' limit '.$limit.','.$ofset;
		return [
			'list_product' => $this->db->query($select_list_product)->result_array(),
			'count' => (int)$this->db->query($count_product)->row()->count
		];
	}

	/**
	 * get image file name
	 * @param   int $id 
	 * @return  object   
	 */
	public function get_image_file_name($id){
		$this->db->order_by('dateadded', 'desc');
		$this->db->where('rel_id', $id);
		$this->db->where('rel_type', 'commodity_item_file');
		$this->db->select('file_name, rel_id');		
		return $this->db->get(db_prefix() . 'files')->result_array();
	}

	/**
	 *  add contact 
	 * @param array $data
	 * @return  int $insert_id   
	 */
	public function add_contact($data)
	{     
		$this->db->insert(db_prefix() . 'contacts', $data);
		$insert_id = $this->db->insert_id();
		return $insert_id;
	}
		/**
		 * get contact
		 * @param  int $id 
		 * @return object or object array     
		 */
		public function get_contact($id = ''){
			if($id != ''){
				$this->db->where('id',$id);
				return $this->db->get(db_prefix().'contacts')->row();
			}
			else{     
				return $this->db->get(db_prefix().'contacts')->result_array();
			}
		}
	/**
	 * incrementalHash
	 * @return string hash 
	 */
	public function incrementalHash(){
		$charset = "01FGHIJ23OPQ456TUVWXYZ789ABCDEKLMNRS";
		$base = strlen($charset);
		$result = '';

		$now = explode(' ', microtime())[1];
		while ($now >= $base){
			$i = $now % $base;
			$result = $charset[$i] . $result;
			$now /= $base;
		}
		return substr($result, -5).strtotime(date('Y-m-d H:i:s'));
	}
	/**
	 * check out
	 * @param  array $data 
	 * @return string order_number       
	 */
	public function check_out($data){
		$this->load->model('clients_model');
		$data_client = $this->clients_model->get($data['userid']);
		if($data_client){
			$user_id = $data['userid'];
			$order_number = $this->incrementalHash();
			$channel_id = 2;
			$data_cart['userid'] = $user_id;
			$data_cart['voucher'] = $data['voucher'];
			$data_cart['order_number'] = $order_number;
			$data_cart['channel_id'] = $channel_id;
			$data_cart['channel'] = 'portal';
			$data_cart['company'] =  $data_client->company;
			$data_cart['phonenumber'] =  $data_client->phonenumber;
			$data_cart['city'] =  $data_client->city;
			$data_cart['state'] =  $data_client->state;
			$data_cart['country'] =  $data_client->country;
			$data_cart['zip'] =  $data_client->zip;
			$data_cart['billing_street'] =  $data_client->billing_street;
			$data_cart['billing_city'] =  $data_client->billing_city;
			$data_cart['billing_state'] =  $data_client->billing_state;
			$data_cart['billing_country'] =  $data_client->billing_country;
			$data_cart['billing_zip'] =  $data_client->billing_zip;
			$data_cart['shipping_street'] =  $data_client->shipping_street;
			$data_cart['shipping_city'] =  $data_client->shipping_city;
			$data_cart['shipping_state'] =  $data_client->shipping_state;
			$data_cart['shipping_country'] =  $data_client->shipping_country;
			$data_cart['shipping_zip'] =  $data_client->shipping_zip;
			$data_cart['total'] =  preg_replace('%,%','',$data['total']);
			$data_cart['sub_total'] =  $data['sub_total'];
			$data_cart['discount'] =  $data['discount'];
			$data_cart['discount_type'] =  2;
			$data_cart['notes'] =  $data['notes'];
			$data_cart['tax'] =  $data['tax'];
			$data_cart['allowed_payment_modes'] =  $data['payment_methods'];


			$this->db->insert(db_prefix() . 'cart', $data_cart);
			$insert_id = $this->db->insert_id();
			if($insert_id){


				$date = date('Y-m-d');
				$productid_list = explode(',',$data['list_id_product']);
				$quantity_list = explode(',',$data['list_qty_product']);
				$total_tax = 0;
				foreach ($productid_list as $key => $value) {
					$data_detailt['product_id'] = $value;   
					$item_quantity = $quantity_list[$key];
					$data_detailt['quantity'] = $item_quantity;
					$data_detailt['classify'] = '';
					$data_detailt['cart_id']  = $insert_id;
					$product_name = '';
					$prices = '';
					$long_description = '';
					$sku = '';
					$data_products = $this->get_product($value);
					if($data_products){
						$product_name = $data_products->description;
						$long_description = $data_products->long_description;
						$sku = $data_products->sku_code;
					}
					$prices = $this->get_price_channel($value,2);
					$data_detailt['product_name'] = $product_name;

					$prices  = 0;
					$data_prices = $this->get_price_channel($value,2);
					if($data_prices){
						$prices  = $data_prices->prices;
					}
					$data_detailt['prices'] = $prices;

					$tax_array = [];
					$get_tax_data = $this->get_tax_list_product($value);
					foreach ($get_tax_data as $tax) {
						$total_tax_value = ($tax['taxrate'] * ($prices * $item_quantity) / 100);
						$total_tax += $total_tax_value;
						$tax_array[] = [
							'id' => $tax['id'],
							'name' => $tax['name'],
							'rate' => $tax['taxrate'],
							'value' => $total_tax_value
						];
					}
					$data_detailt['tax'] = json_encode($tax_array);

					$data_detailt['sku'] = $sku;
					$data_detailt['long_description'] = $long_description;
					$this->db->insert(db_prefix() . 'cart_detailt', $data_detailt);
				} 

				$data_update['tax'] = $total_tax;
				$this->db->where('id',$insert_id);
				$this->db->update(db_prefix() . 'cart', $data_update);
				
				$staff_approve = get_option('staff_sync_orders');
				if($staff_approve){
					$this->notifications($staff_approve,'omni_sales/view_order_detailt/'.$insert_id,_l('new_orders_are_waiting_for_your_confirmation'));
				}     

				//add hook after invoice add from order
				hooks()->do_action('omni_sales_after_invoice_added', $insert_id);
				
				$this->add_inv_when_order($insert_id,0);  
				$data_inv = $this->get_cart($insert_id);
				$this->remove_cart_data_cookie();

				$this->add_log_trade_discount($user_id, $order_number,$channel_id, $data_cart['sub_total'], $data_cart['discount'], $data_cart['tax'], $data_cart['total'], $data['voucher']);
				if($data_inv){
					hooks()->do_action('after_cart_added',$data_inv,$data);
					return $data_inv->number_invoice;   
				}
				else{
					return 0;
				}     
			}
			return '';
		}     
	}
	/**
	 * remove cart data cookie   
	 * @return bool
	 */
	public function remove_cart_data_cookie(){
		if (isset($_COOKIE['cart_id_list'])&&isset($_COOKIE['cart_qty_list'])) {
			unset($_COOKIE['cart_id_list']); 
			unset($_COOKIE['cart_qty_list']); 
			setcookie('cart_id_list', null, -1, '/'); 
			setcookie('cart_qty_list', null, -1, '/'); 
			return true;
		} else {
			return false;
		}
	}
	/**
	 * get cart
	 * @param  int $id 
	 * @return object or array    
	 */
	public function get_cart($id = ''){
		if($id != ''){
			$this->db->where('id',$id);
			return $this->db->get(db_prefix().'cart')->row();
		}
		else{     
			return $this->db->get(db_prefix().'cart')->result_array();
		}
	}
	/**
	 * get cart detailt
	 * @param  int $id 
	 * @return  object or array      
	 */
	public function get_cart_detailt($id = ''){
		if($id != ''){
			$this->db->where('id',$id);
			return $this->db->get(db_prefix().'cart_detailt')->row();
		}
		else{     
			return $this->db->get(db_prefix().'cart_detailt')->result_array();
		}
	}
	/**
	 * get cart detailt by master
	 * @param  int $id 
	 * @return array     
	 */
	public function get_cart_detailt_by_master($id = ''){
		if($id != ''){
			$this->db->where('cart_id',$id);
			return $this->db->get(db_prefix().'cart_detailt')->result_array();
		}
		else{     
			return $this->db->get(db_prefix().'cart_detailt')->result_array();
		}
	}
	/**
	 * products list store
	 * @param  int $store_id 
	 * @return array           
	 */
	public function products_list_store($store_id){
		$this->db->where('woocommere_store_id', $store_id);
		return $this->db->get(db_prefix().'woocommere_store_detailt')->result_array();
	}
	/**
	 * sync order woo system
	 * @param  int $store_id 
	 * @return string           
	 */
	public function sync_order_woo_system($store_id){
		$woocommerce = $this->init_connect_woocommerce($store_id);
		$per_page = 100;
		$order = [];
		for($page = 1; $page <= $this->per_page_tags; $page++ ){
			$offset = ($page - 1) * $per_page;
			$orders = $woocommerce->get('orders', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

			$order = array_merge($order, $orders);
			
			if(count($orders) < $this->per_page_tags){
				break;
			}
		}

		return $order;
	}
/**
 * process orders woo
 * @param  int $store_id 
 * @return bool           
 */
public function process_orders_woo($store_id){
	$this->load->model('clients_model');
	$this->load->model('emails_model');
	$password = $this->generate_string();
	$store  = $this->get_woocommere_store($store_id);
	$store_name = $store->name_channel;
	$woocommerce = $this->init_connect_woocommerce($store_id);

	$data = $this->sync_order_woo_system($store_id);
	$this->db->select('iso2');
	$iso2 = $this->db->get(db_prefix().'countries')->result_array();
	$iso1 = [];
	foreach ($iso2 as $key => $value) {
		$iso1[] = $value['iso2'];
	}
	$order_number = [];
	$email_client = get_all_email_contacts();
	$woo_customer_ids = get_all_woo_customer_id($store_id);

	$orders = [];

	if(!empty($data)){
		//for
		foreach ($data as $key => $value) {
			if($value->status == "completed" || $value->status == "cancelled" || $value->status == "pending" || $value->status == "refunded" || $value->status == "failed"){
				$status_update_order_sync = get_index_by_status($value->status);
				$admin_action = 0;
        		// If order is canceled -> order canceler by admin
				if($status_update_order_sync == 8){
					$admin_action = 1;
				}
				$data_update['status'] = $status_update_order_sync;
				$data_update['admin_action'] = $admin_action;
				$this->db->where('order_number', $value->number);
				$this->db->update(db_prefix().'cart', $data_update);

			}else if($value->status == "processing" || $value->status == "on-hold"){
				$data_cart['status'] = get_index_by_status($value->status);

				if(!in_array($value->customer_id, $woo_customer_ids)){
					$woo_customer_ids[] = $value->customer_id;

					if(in_array($value->billing->country, $iso1)){
						$this->db->where('iso2',$value->billing->country);
						$info_create['country'] = $this->db->get('tblcountries')->row()->country_id;
					}

					$first_name = $value->billing->first_name;
					$last_name = $value->billing->last_name;
					$address_1 = $value->billing->address_1;
					$address_2 = $value->billing->address_2;
					$street = $address_1 .','. $address_2;
					$city = $value->billing->city;
					$state = $value->billing->state;
					$postcode = $value->billing->postcode;
					$info_create['company'] = $first_name . ' ' . $last_name;
					$info_create['address'] = $street;
					$info_create['city'] = $city;
					$info_create['state'] = $state;
					$info_create['billing_street'] = $street;
					$info_create['billing_city'] = $city;
					$info_create['billing_state'] = $state;
					$info_create['billing_zip'] = $postcode;
					$info_create['billing_country'] = is_numeric($info_create['country']) ? $info_create['country'] : 0;
					$info_create['shipping_street'] = $street;
					$info_create['shipping_city'] = $city;
					$info_create['shipping_state'] = $info_create['country'];
					$info_create['country'] = $info_create['country'];
					$info_create['shipping_zip'] = $postcode;
					$info_create['shipping_country'] = $info_create['country'];
					$info_create['firstname'] = $first_name;
					$info_create['lastname'] = $last_name;
					$info_create['zip'] = $postcode;
					$info_create['email'] = $value->billing->email;
					$info_create['contact_phonenumber'] = $value->billing->phone;
					$info_create['password'] = $password;
					$info_create['woo_customer_id'] = $value->customer_id;
					$info_create['woo_channel_id'] = $store_id;

					$link = '<a href="'.site_url("authentication/login")  .'">'.site_url('authentication/login')  .'</a>';
					$client = $this->clients_model->add($info_create, true);
					$message = 'Congratulations! The order has been successfully placed - The system has automatically created an account for you.';
					$message = '';
					$message .= '<br>';
					$message .= 'Link : '.$link.'<br>';
					$message .= 'Account : '.$value->billing->email.'<br>';
					$message .= 'Password : '.$password.'<br>';
				}

				$payment_mode_id = $this->get_payment_mode_id_by_name($value->payment_method_title);

				array_push($orders, 
					array(
						'order_number' => $value->number, 
						'billing' => $value->billing, 
						'status' => $value->status, 
						'shipping' => $value->shipping, 
						'line_items' => $value->line_items,
						'shipping_lines' => $value->shipping_lines,
						'email' => $value->billing->email,
						'notes' => $value->customer_note,
						'tax' => $value->total_tax - $value->shipping_tax,
						'shipping_total' => $value->shipping_total,
						'shipping_tax' => $value->shipping_tax,
						'payment_method' => $value->payment_method,
						'payment_mode' => $payment_mode_id,
						'payment_method_title' => $value->payment_method_title,
						'customer_id' => $value->customer_id
					)
				);
				foreach ($orders as $key => $value_) {
					$carts = $this->get_cart(); 
					foreach ($carts as $key => $value) {
						$order_number[] = $value['order_number'];
					}

					if(!in_array($value_['order_number'], $order_number)){

						$productid_list = [];
						$taxes_list = [];
						$prices = [];
						$quantity_list = [];

						$total = 0;
						$total_tax = 0;
						$subtotal = 0;
						foreach ($value_['line_items'] as $items) {
							$prices[] = $items->price;
							$quantity_list[] = $items->quantity;
							$subtotal += $items->subtotal;
							$total_tax += $items->subtotal_tax;
							$this->db->where('sku_code', $items->sku);
							$item = $this->db->get(db_prefix().'items')->row();
							if($item){
								$productid_list[] = $item->id;
							}

							$t_array = [];
							if(isset($items->taxes)){
								foreach ($items->taxes as $t) {
									if($t->total != ''){
										$tax = $woocommerce->get('taxes/'.$t->id);

										$_tax = $this->get_tax_by_name($tax->name, $tax->rate);

										$t_array[] = [
											'id' => $_tax['id'],
											'name' => $_tax['name'],
											'rate' => $_tax['taxrate'],
											'value' => $t->total
										];
									}
								}
							}

							$taxes_list[] = json_encode($t_array);
						}

						$shipping_t_array = [];
						
						if(isset($value_['shipping_lines'])){
							foreach($value_['shipping_lines'] as $shipping) {
								if(isset($shipping->taxes)){
									foreach ($shipping->taxes as $t) {
										if($t->total != ''){
											$tax = $woocommerce->get('taxes/'.$t->id);

											$_tax = $this->get_tax_by_name($tax->name, $tax->rate);

											$shipping_t_array[] = [
												'id' => $_tax['id'],
												'name' => $_tax['name'],
												'rate' => $_tax['taxrate'],
												'value' => $t->total
											];
										}
									}
								}
							}
						}

						if($value_['shipping_total'] != "0.00"){
							$total_tax += $value_['shipping_total'];
						}

						if($value_['shipping_tax'] != "0.00"){
							$total_tax += $value_['shipping_tax'];
						}
						
						$discounts_woo = 0;
						$total = $subtotal + $total_tax - $discounts_woo;
						$this->db->where('woo_customer_id', $value_['customer_id']);
						$this->db->where('woo_channel_id', $store_id);
						$data_client = $this->db->get(db_prefix().'clients')->row();
						
						if(!$data_client){
							$data_client = $this->get_customer_public();
						}

						if($data_client){
							$data_cart['userid'] = $data_client->userid;
							$data_cart['voucher'] = '';
							$data_cart['order_number'] = $value_['order_number'];
							$data_cart['channel_id'] = 3;
							$data_cart['channel'] = 'WooCommerce('.$store_name.')  ';
							$data_cart['company'] = $data_client->company;
							$data_cart['phonenumber'] = $data_client->phonenumber;
							$data_cart['city'] = $data_client->city;
							$data_cart['state'] = $data_client->state;
							$data_cart['country'] = $data_client->country;
							$data_cart['zip'] = $data_client->zip;
							$data_cart['billing_street'] = $data_client->billing_street;
							$data_cart['billing_city'] = $data_client->billing_city;
							$data_cart['billing_state'] = $data_client->billing_state;
							$data_cart['billing_country'] = $data_client->billing_country;
							$data_cart['billing_zip'] = $data_client->billing_zip;
							$data_cart['shipping_street'] = $data_client->shipping_street;
							$data_cart['shipping_city'] = $data_client->shipping_city;
							$data_cart['shipping_state'] = $data_client->shipping_state;
							$data_cart['shipping_country'] = $data_client->shipping_country;
							$data_cart['shipping_zip'] = $data_client->shipping_zip;
							$data_cart['shipping_zip'] = $data_client->shipping_zip;
							$data_cart['notes'] = $value_['notes'];
							$data_cart['admin_action'] = 0; 
							$data_cart['total'] = $total; 
							$data_cart['sub_total'] = $subtotal; 
							$data_cart['tax'] = $value_['tax'];
						 	//add shipping and payment method
							$data_cart['shipping'] = $value_['shipping_total'];
							$data_cart['shipping_tax'] = $value_['shipping_tax'];
							$data_cart['shipping_tax_json'] = json_encode($shipping_t_array);
							$data_cart['allowed_payment_modes'] = $value_['payment_mode'];
							$data_cart['payment_method_title'] = $value_['payment_method_title'];
							$data_cart['datecreator'] = date('Y-m-d H:i:s');

							$this->db->insert(db_prefix() . 'cart', $data_cart);
							$insert_id = $this->db->insert_id();
							$staff_approve = get_option('staff_sync_orders');
							if($staff_approve){
								$this->notifications($staff_approve,'omni_sales/view_order_detailt/'.$insert_id,_l('new_orders_are_waiting_for_your_confirmation'));
							} 
						}
						$temp = '';
						if($insert_id){
							foreach ($productid_list as $key => $p_value) {
								$data_detailt['product_id'] = $p_value; 
								$data_detailt['quantity'] = $quantity_list[$key];
								$data_detailt['classify'] = '';
								$data_detailt['cart_id'] = $insert_id;
								$product_name = '';
								$long_description = '';
								$sku = '';
								$this->db->where('id', $p_value);
								$data_products = $this->db->get(db_prefix().'items')->row();
								if($data_products){
									$product_name = $data_products->description;
									$long_description = $data_products->long_description;
									$sku = $data_products->sku_code;
								}
								$data_detailt['product_name'] = $product_name;
								$data_detailt['prices'] = $prices[$key];
								$data_detailt['sku'] = $sku;
								$data_detailt['long_description'] = $long_description;
								$data_detailt['tax'] = $taxes_list[$key];
								
								$this->db->insert(db_prefix() . 'cart_detailt', $data_detailt);
								$temp = $data_detailt;
							}
							$this->remove_cart_data_cookie();

						}
						$cart_after_insert = $this->get_cart($insert_id);
						$setting_inv = get_option('invoice_sync_configuration');
						if(isset($setting_inv)){
							if($setting_inv == 1){
								$id_invoice_rs = $this->add_inv_when_order_v2($insert_id, $cart_after_insert->status);
								if($id_invoice_rs){
									$this->db->where('invoice_id', $id_invoice_rs);
									$id_exp = $this->db->get(db_prefix().'goods_delivery')->row();
									if($id_exp != null){
										$data_update['stock_export_number'] = $id_exp->id;
										$this->db->where('id', $insert_id);
										$this->db->update(db_prefix().'cart', $data_update); 

									}

								}

							}
						}
						$log_orders = [
							'name' => $cart_after_insert->order_number,
							'order_id' => $insert_id,
							'regular_price' => $cart_after_insert->total,
							'sale_price' => $cart_after_insert->sub_total,
							'chanel' => $cart_after_insert->channel,
							'company' => $cart_after_insert->company,
							"type" => "orders",
						];
						$this->db->insert(db_prefix().'omni_log_sync_woo', $log_orders);
					}
				} 

			}
		}
		return true;
	} 

}
	/**
	 * get cart by order number
	 * @param  string $order_number 
	 * @return object or array               
	*/
	public function get_cart_by_order_number($order_number=''){
		if($order_number != ''){
			$this->db->where('order_number',$order_number);
			return $this->db->get(db_prefix().'cart')->row();
		}
		else{     
			return $this->db->get(db_prefix().'cart')->result_array();
		}
	}
	/**
	 * get cart detailt by cart id
	 * @param  int $cart_id 
	 * @return array          
	 */
	public function get_cart_detailt_by_cart_id($cart_id = ''){
		if($cart_id != ''){
			$this->db->where('cart_id',$cart_id);
			return $this->db->get(db_prefix().'cart_detailt')->result_array();
		}
		else{     
			return $this->db->get(db_prefix().'cart_detailt')->result_array();
		}
	}
	/**
	 * [change_status_order
	 * @param  array  $data         
	 * @param  string  $order_number 
	 * @param  integer $admin_action 
	 * @return bool                
	 */
	public function change_status_order($data, $order_number,$admin_action = 0){
		$this->db->where('order_number',$order_number);
		$data_update['reason'] = _l($data['cancelReason']);
		$data_update['status'] = $data['status'];
		$data_update['admin_action'] = $admin_action;
		$this->db->update(db_prefix().'cart',$data_update);
		if ($this->db->affected_rows() > 0) {
			$channel_id = $this->omni_sales_model->get_cart_by_order_number($order_number);
			if($channel_id->channel_id == 3){
				$regex = "/\(([^)]*)\)/";
				preg_match_all($regex,$channel_id->channel,$matches);
				$this->db->where('name_channel', $matches[1][0]);
				$rs = $this->db->get(db_prefix().'omni_master_channel_woocommere')->row();
				$woocommerce = $this->init_connect_woocommerce($rs->id);
				$status = get_status_by_index_woo($data['status']);
				if($status != ''){
					$data = [
						'update' => [
							[
								'id' => $order_number,
								'status' => $status
							]
						],

					];
					$woocommerce->post('orders/batch', $data);
				}
				return true;
			}
			return true;
		}
		return false;
	}
	/**
	 * get cart of client by status
	 * @param  int  $userid 
	 * @param  int $status 
	 * @return array          
	 */
	public function get_cart_of_client_by_status($userid = '', $status = 0, $channel_id = '', $where = ''){
		if($where != ''){
			$this->db->where($where);
		}
		if($userid != ''){
			if($channel_id != ''){
				$this->db->where('channel_id', $channel_id);   
			}
			$this->db->where('userid',$userid);
			$this->db->where('status',$status);
			$this->db->order_by('datecreator', 'DESC');
			return $this->db->get(db_prefix().'cart')->result_array();
		}
		elseif($userid == '' && $status !=''){  
			if($channel_id != ''){
				$this->db->where('channel_id', $channel_id);   
			}
			$this->db->where('status',$status);   
			$this->db->order_by('datecreator', 'DESC');
			return $this->db->get(db_prefix().'cart')->result_array();
		}
		else{
			return $this->db->get(db_prefix().'cart')->result_array();
		}
	}
	/**
	 * generate_string
	 * @param  integer $strength 
	 * @return string            
	 */
	public function generate_string($strength = 16) {
		$input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$input_length = strlen($input);
		$random_string = '';
		for($i = 0; $i < $strength; $i++) {
			$random_character = $input[mt_rand(0, $input_length - 1)];
			$random_string .= $random_character;
		}
		return $random_string;
	}
	/**
	 * add inv and out of stock
	 * @param int $orderid 
	 * @return bolean
	 */
	public function add_inv_and_out_of_stock($orderid, $status = '') {
		$this->load->model('invoices_model');
		$this->load->model('credit_notes_model');
		$this->load->model('warehouse/warehouse_model');
		$cart = $this->get_cart($orderid);

		$cart_detailt = $this->get_cart_detailt_by_master($orderid);
		$newitems = [];
		$count = 0;
		foreach ($cart_detailt as $key => $value) {
			$unit = 0;
			$unit_name = '';
			$this->db->where('id', $value['product_id']);
			$data_product = $this->db->get(db_prefix().'items')->row();
			$tax = $this->get_tax($data_product->tax);
			if($tax == ''){
				$taxname = '';
			}else{
				$taxname = $tax->name.'|'.$tax->taxrate;
			}

			$unit_name = "";
			if($data_product){        
				$unit = $data_product->unit_id;
				if($unit != 0 || $unit != null){
					$this->db->where('unit_type_id', $unit);
					$unit_parent = $this->db->get(db_prefix().'ware_unit_type')->row();
					if($unit_parent){
						$unit_name = $unit_parent->unit_name;
					}
				}  
			}
			$count = $key;
			array_push($newitems, array('order' => $key, 'description' => $value['product_name'], 'long_description' => $value['long_description'], 'qty' => $value['quantity'], 'unit' => $unit_name, 'rate'=> $value['prices'], 'taxname' => array($taxname)));
		}   
		
		$total = $this->get_total_order($orderid)['total'];
		$sub_total = $this->get_total_order($orderid)['sub_total'];
		$discount_total = $this->get_total_order($orderid)['discount'];
		$__number = get_option('next_invoice_number');
		$_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
		$this->db->where('isdefault', 1);
		$curreny = $this->db->get(db_prefix().'currencies')->row()->id;

		if($cart){
			$data['clientid'] = $cart->userid;
			$data['billing_street'] = $cart->billing_street;
			$data['billing_city'] = $cart->billing_city;
			$data['billing_state'] = $cart->billing_state;
			$data['billing_zip'] = $cart->billing_zip;
			$data['billing_country'] = $cart->billing_country;
			$data['include_shipping'] = 1;
			$data['show_shipping_on_invoice'] = 1;
			$data['shipping_street'] = $cart->shipping_street;
			$data['shipping_city'] = $cart->shipping_city;
			$data['shipping_state'] = $cart->shipping_state;
			$data['shipping_zip'] = $cart->shipping_zip;
			$date_format   = get_option('dateformat');
			$date_format   = explode('|', $date_format);
			$date_format   = $date_format[0];       
			$data['date'] = date($date_format);
			$data['duedate'] = date($date_format);
			//terms_invoice
			$data['terms'] = get_option('predefined_terms_invoice');
			if(isset($cart->shipping)){
				array_push($newitems, array('order' => $count+1, 'description' => _l('shipping'), 'long_description' => "", 'qty' => 1, 'unit' => "", 'rate'=> $cart->shipping, 'taxname' => array()));
			}
			$data['currency'] = $curreny;
			$data['newitems'] = $newitems;
			$data['number'] = $_invoice_number;
			$data['total'] = $cart->total;
			$data['subtotal'] = $cart->sub_total;      
			$data['total_tax'] = $cart->tax;
			$data['discount_total'] = $cart->discount_total;
			if($cart->discount_type == 1){
				$data['discount_percent' ]= $cart->discount;
			}elseif($cart->discount_type == 2){
				$data['discount_percent'] =  ($cart->discount_total/$data['subtotal'])*100;
			}
			$prefix = get_option('invoice_prefix');

			
			$id = $this->invoices_model->add($data);

			if($cart->discount != '' && $cart->discount_type != '' && $cart->voucher != ''){
				$credit_notes = $this->credit_note_from_invoice_omni($id);
			}            
			if($id){

				$this->warehouse_model->auto_create_goods_delivery_with_invoice($id);
				if($status!=''){
					$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number, $status);
				}
				else{
					$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number);
				}           
				return true;
			}
		}   
		return true;

	}
	/**
	 *  get total order
	 * @param  int  $id      
	 * @param  boolean $voucher 
	 * @return array           
	 */
	public function get_total_order($id ='',$voucher = false){        
		$data_detailt = $this->get_cart_detailt_by_master($id);
		$total = 0;
		foreach ($data_detailt as $key => $value) {
			$total += $value['quantity'] * $value['prices'];
		}
		return ['total' => $total,'sub_total' => $total,'discount' => '0'];
	}
		/**
		 * add_goods_delivery
		 * @param array  $data 
		 * @param bool $id  
		 * @return bool   
		 */
		public function add_goods_delivery($data, $id = false) {

			$data['approval'] = 1;


			if (isset($data['cart_detailt'])) {
				$cart_detailt = $data['cart_detailt'];
				unset($data['cart_detailt']);
			}
			$hot_purchase = [];

			$data['goods_delivery_code'] = $this->create_goods_delivery_code();

			if(!$this->check_format_date($data['date_c'])){
				$data['date_c'] = to_sql_date($data['date_c']);
			}else{
				$data['date_c'] = $data['date_c'];
			}


			if(!$this->check_format_date($data['date_add'])){
				$data['date_add'] = to_sql_date($data['date_add']);
			}else{
				$data['date_add'] = $data['date_add'];
			}

			$data['total_money']  = reformat_currency_j($data['total_money']);
			$data['total_discount'] = reformat_currency_j($data['total_discount']);
			$data['after_discount'] = reformat_currency_j($data['after_discount']);   

			$data['addedfrom'] = get_staff_user_id();

			$this->db->insert(db_prefix() . 'goods_delivery', $data);
			$insert_id = $this->db->insert_id();

			if (isset($insert_id)) {


				foreach ($cart_detailt as $key => $value) {
					$total_inventory = $this->get_total_inventory_commodity($value['product_id']);

					$quantity = $value['quantity'];
					if($quantity < $total_inventory){
						$this->db->where('commodity_id', $value['product_id']);
						$this->db->order_by('id', 'ASC');
						$result = $this->db->get('tblinventory_manage')->result_array();
						$temp_quantities = $value['quantity'];

						$expiry_date = '';
						$lot_number = '';
						foreach ($result as $result_value) {
							if (($result_value['inventory_number'] != 0) && ($temp_quantities != 0)) {

								if ($temp_quantities >= $result_value['inventory_number']) {
									$temp_quantities = (float) $temp_quantities - (float) $result_value['inventory_number'];

									if(($result_value['lot_number'] != null) && ($result_value['lot_number'] != '') ){
										if(strlen($lot_number) != 0){
											$lot_number .=','.$result_value['lot_number'].','.$result_value['inventory_number'];
										}else{
											$lot_number .= $result_value['lot_number'].','.$result_value['inventory_number'];
										}
									}

									if(($result_value['expiry_date'] != null) && ($result_value['expiry_date'] != '') ){
										if(strlen($expiry_date) != 0){
											$expiry_date .=','.$result_value['expiry_date'].','.$result_value['inventory_number'];
										}else{
											$expiry_date .= $result_value['expiry_date'].','.$result_value['inventory_number'];
										}
									}

									$this->db->where('id', $result_value['id']);
									$this->db->update(db_prefix() . 'inventory_manage', [
										'inventory_number' => 0,
									]);

								} else {

									if(($result_value['lot_number'] != null) && ($result_value['lot_number'] != '') ){
										if(strlen($lot_number) != 0){
											$lot_number .=','.$result_value['lot_number'].','.$temp_quantities;
										}else{
											$lot_number .= $result_value['lot_number'].','.$temp_quantities;
										}
									}

									if(($result_value['expiry_date'] != null) && ($result_value['expiry_date'] != '') ){
										if(strlen($expiry_date) != 0){
											$expiry_date .=','.$result_value['expiry_date'].','.$temp_quantities;
										}else{
											$expiry_date .= $result_value['expiry_date'].','.$temp_quantities;
										}
									}


									$this->db->where('id', $result_value['id']);
									$this->db->update(db_prefix() . 'inventory_manage', [
										'inventory_number' => (float) $result_value['inventory_number'] - (float) $temp_quantities,
									]);

									$temp_quantities = 0;

								}

							}

						}

						$this->db->where('id', $data['id']);
						$this->db->update(db_prefix() . 'goods_delivery_detail', [
							'expiry_date' => $expiry_date,
							'lot_number' => $lot_number,
						]);

						$data['expiry_date'] = $expiry_date;
						$data['lot_number'] = $lot_number;

					}else{
						return false;
					}

					$this->db->where('commodity_code', $value['product_id']);
					$warehouse = $this->db->get(db_prefix().'goods_receipt_detail')->row();
				}

				$results = 0;
				foreach ($hot_purchase as $purchase_key => $purchase_value) {
					$this->db->insert(db_prefix() . 'goods_delivery_detail', $purchase_value);
					$insert_detail = $this->db->insert_id();
					$results++;
				} 

			}
			$data_log = [];
			$data_log['rel_id'] = $insert_id;
			$data_log['rel_type'] = 'stock_export';
			$data_log['staffid'] = get_staff_user_id();
			$data_log['date'] = date('Y-m-d H:i:s');
			$data_log['note'] = "stock_export";
			$this->add_activity_log($data_log);     
			return $insert_id;
		}

	/**
	 * check format date Y-m-d
	 *
	 * @param      String   $date   The date
	 *
	 * @return     boolean 
	 */
	public function check_format_date($date){
		if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * create goods delivery code
	 * @return string 
	 */
	public function create_goods_delivery_code() {
		$id = $this->db->query('SELECT id FROM ' . db_prefix() . 'goods_delivery order by id desc limit 1')->row();
		if ($id == null) {
			$goods_code = 'XK01';
		} else {
			$goods_code = 'XK0' . (get_object_vars($id)['id'] + 1);
		}
		return $goods_code;
	}

	
	/**
	 * add activity log
	 * @param array $data
	 * @return boolean
	 */
	public function add_activity_log($data) {
		$this->db->insert(db_prefix() . 'wh_activity_log', $data);
		return true;
	}
	/**
	 * get all image file name
	 * @param  int $id 
	 * @return array     
	 */
	public function get_all_image_file_name($id){
		$this->db->order_by('dateadded', 'desc');
		$this->db->where('rel_id',$id);
		$this->db->where('rel_type','commodity_item_file');
		$this->db->select('file_name');
		return $this->db->get(db_prefix().'files')->result_array();
	}
	/**
	 * get list product by group and key
	 * @param  int $id_chanel 
	 * @param  int $id_group  
	 * @param  int $limit     
	 * @param  int $ofset     
	 * @param  string  $key       
	 * @return array             
	 */
	public function get_list_product_by_group_and_key($id_chanel, $id_group = '', $limit = 0, $ofset = 1,$key=''){
		if($id_group!=''){
			$count_product = 'select count(id) as count from '.db_prefix().'items where id in (select product_id from '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$id_chanel.') and group_id = '.$id_group.' and description like \'%'.$key.'%\'';
			$select_list_product = 'select  id, description, long_description, rate from '.db_prefix().'items where id in (select product_id from '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$id_chanel.') and group_id = '.$id_group.' limit '.$limit.','.$ofset.' and description like \'%'.$key.'%\'';
			$result = [
				'list_product' => $this->db->query($select_list_product)->result_array(),
				'count' => (int)$this->db->query($count_product)->row()->count
			];
			return $result;
		}
		else{
			$count_product = 'select count(id) as count from '.db_prefix().'items where id in (select product_id from '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$id_chanel.') and description like \'%'.$key.'%\'';

			$select_list_product = 'select  id, description, long_description, rate from '.db_prefix().'items where id in (select product_id from '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$id_chanel.') limit '.$limit.','.$ofset.' and description like \'%'.$key.'%\'';
			$result = [
				'list_product' => $this->db->query($select_list_product)->result_array(),
				'count' => (int)$this->db->query($count_product)->row()->count
			];
			return $result;
		}
	}
	/**
	 * get list product by group
	 * @param  int  $id_chanel  
	 * @param  int  $id_group   
	 * @param  int  $id_product 
	 * @param  int $limit      
	 * @param  int $ofset      
	 * @return array              
	 */
	public function get_list_product_by_group_s($id_chanel, $id_group = '', $id_product = '', $limit = 0, $ofset = 1){
		if($id_group!=''){
			$count_product = 'select count(id) as count from '.db_prefix().'items where id in (select product_id from '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$id_chanel.') and group_id = '.$id_group.' and id != '.$id_product;
			$select_list_product = 'select  id, description, long_description, rate from '.db_prefix().'items where id in (select product_id from '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$id_chanel.') and group_id = '.$id_group.' and id != '.$id_product.' limit '.$limit.','.$ofset;
			$result = [
				'list_product' => $this->db->query($select_list_product)->result_array(),
				'count' => (int)$this->db->query($count_product)->row()->count
			];
			return $result;
		}
	}
	/**
	 * get_group_product
	 * @param  int $id_group 
	 * @return array           
	 */
	public function get_group_product_s($id_group){
		return $this->db->query('select * from '.db_prefix().'items_groups where id != '.$id_group)->result_array();
	}
	/**
	 * quantity detail product
	 * @param  int $product_id 
	 * @return object             
	 */
	public function quantity_detail_product($product_id){
		return $this->db->query('SELECT sum(quantities) as quantity FROM '.db_prefix().'goods_receipt_detail where commodity_code = '.$product_id.'')->row();
	}

	/**
	 * get total inventory commodity
	 * @param  boolean $id
	 * @return object
	 */
	public function get_total_inventory_commodity($commodity_id = false) {
		if ($commodity_id != false) {
			$sql = 'SELECT sum(inventory_number) as inventory_number FROM ' . db_prefix() . 'inventory_manage
			where ' . db_prefix() . 'inventory_manage.commodity_id = ' . $commodity_id . ' order by ' . db_prefix() . 'inventory_manage.warehouse_id';
			return $this->db->query($sql)->row();
		}

	}

	/**
	 * get goods delivery detail
	 * @param  integer $id
	 * @return array
	 */
	public function get_goods_delivery_detail($id) {
		if (is_numeric($id)) {
			$this->db->where('goods_delivery_id', $id);

			return $this->db->get(db_prefix() . 'goods_delivery_detail')->result_array();
		}
		if ($id == false) {
			return $this->db->query('select * from tblgoods_delivery_detail')->result_array();
		}
	}
	/**
	 * get unit
	 * @param  int $id 
	 * @return object or array     
	 */
	public function get_unit($id=''){   
		if($id != ''){
			$this->db->where('unit_type_id',$id);
			return $this->db->get(db_prefix().'ware_unit_type')->row();
		}
		else{     
			return $this->db->get(db_prefix().'ware_unit_type')->result_array();
		}
	}
	/**
	 *  get product channel
	 * @param  int $product_id       
	 * @param  int $sales_channel_id 
	 * @return object                   
	 */
	public function get_product_channel($product_id = '', $sales_channel_id = ''){
		$this->db->where('product_id',$product_id);
		$this->db->where('sales_channel_id',$sales_channel_id);
		return $this->db->get(db_prefix().'sales_channel_detailt')->row();    
	}
	/**
	 * get_price_channel
	 * @param  $product_id       
	 * @param  $sales_channel_id 
	 * @return  object                 
	 */
	public function get_price_channel($product_id,$sales_channel_id){
		$this->db->where('product_id',$product_id);
		$this->db->where('sales_channel_id',$sales_channel_id);
		$this->db->select('prices');
		$data = $this->db->get(db_prefix().'sales_channel_detailt')->row(); 
		if(!$data){
			$this->db->where('id',$product_id);
			$this->db->select('rate');
			$data = $this->db->get(db_prefix().'items')->row(); 
			if($data){
				$data->prices = $data->rate;
			}
		} 
		return $data;
	}
		/**
		 * get price store
		 * @param  int $product_id          
		 * @param  int $woocommere_store_id 
		 * @return object                      
		 */
		public function get_price_store($product_id,$woocommere_store_id){
			$this->db->where('product_id', $product_id);
			$this->db->where('woocommere_store_id', $woocommere_store_id);
			$this->db->select('prices');    
			return $this->db->get('woocommere_store_detailt')->row();
		}
/**
 * add discount form
 * @param array $data 
 * @return int $insert_id
 */
public function add_discount_form($data){    
	if($data){
		if(isset($data['minimum_order_value'])){
			if($data['minimum_order_value']!=''){
				$data['minimum_order_value'] =  preg_replace('%,%','',$data['minimum_order_value']);
			}
			else{
				$data['minimum_order_value'] = 0;          
			}
		}else{
			$data['minimum_order_value'] = 0;                  
		}
		unset($data['select-option-items']);
		unset($data['select-option-client']);
		$data['start_time'] = to_sql_date($data['start_time']);
		$data['end_time'] = to_sql_date($data['end_time']);

		if(isset($data['group_clients'])){
			$data['group_clients'] = implode(',', $data['group_clients']);
		}
		else{
			$data['group_clients'] = '';        
		}

		if(isset($data['clients'])){
			$data['clients'] = implode(',', $data['clients']);
		}
		else{
			$data['clients'] = '';        
		}

		if(isset($data['group_items'])){
			$data['group_items'] = implode(',', $data['group_items']);
		}
		else{
			$data['group_items'] = '';        
		}

		if(isset($data['items'])){
			$data['items'] = implode(',', $data['items']);
		}
		else{
			$data['items'] = '';
		}

		if(!$this->check_format_date($data['start_time'])){
			$data['start_time'] = to_sql_date($data['start_time']);
		}  
		if(!$this->check_format_date($data['end_time'])){
			$data['end_time'] = to_sql_date($data['end_time']);
		}
		$this->db->insert(db_prefix() . 'omni_trade_discount', $data);
		$insert_id = $this->db->insert_id();
		return $insert_id;
	}
}
/**
 * get commodity code name id
 * @param  int $id 
 * @return object     
 */
public function get_commodity_code_name_id($id) {
	return $this->db->query('select id as id, CONCAT(commodity_code,"-",description) as label from ' . db_prefix() . 'items where id = '.$id)->row();
}
	/**
	 * delete_trade_discount
	 * @param  int $id 
	 * @return bool     
	 */
	public function delete_trade_discount($id){
		$this->db->where('id',$id);
		$this->db->delete(db_prefix().'omni_trade_discount');
		if ($this->db->affected_rows() > 0) {           
			return true;
		}
		return false;
	}
	/**
	 * update discount form
	 * @param  array $data 
	 * @param  int $id   
	 * @return bool       
	 */
	public function update_discount_form($data, $id){
		if(isset($data['minimum_order_value'])){
			if($data['minimum_order_value']!=''){
				$data['minimum_order_value'] =  preg_replace('%,%','',$data['minimum_order_value']);
			}
			else{
				$data['minimum_order_value'] = 0;          
			}
		}else{
			$data['minimum_order_value'] = 0;                  
		}
		unset($data['select-option-items']);
		unset($data['select-option-client']);
		$data['start_time'] = to_sql_date($data['start_time']);
		$data['end_time'] = to_sql_date($data['end_time']);

		if(isset($data['group_clients'])){
			$data['group_clients'] = implode(',', $data['group_clients']);
		}
		else{
			$data['group_clients'] = '';        
		}

		if(isset($data['clients'])){
			$data['clients'] = implode(',', $data['clients']);
		}
		else{
			$data['clients'] = '';        
		}

		if(isset($data['group_items'])){
			$data['group_items'] = implode(',', $data['group_items']);
		}
		else{
			$data['group_items'] = '';        
		}

		if(isset($data['items'])){
			$data['items'] = implode(',', $data['items']);
		}
		else{
			$data['items'] = '';
		}

		if(!$this->check_format_date($data['start_time'])){
			$data['start_time'] = to_sql_date($data['start_time']);
		}  
		if(!$this->check_format_date($data['end_time'])){
			$data['end_time'] = to_sql_date($data['end_time']);
		}

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'omni_trade_discount', $data);
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}
/**
 * get discount
 * @param  int $id 
 * @return object     
 */
public function get_discount($id = ''){
	if($id != ''){
		$this->db->where('id',$id);
		return $this->db->get(db_prefix().'omni_trade_discount')->row();
	}
	else{     
		return $this->db->get(db_prefix().'omni_trade_discount')->result_array();
	}
}
	/**
	 * [apply_trade_discount
	 * @param  int $client  
	 * @param  int $list_id 
	 * @return array or bool          
	 */
	public function apply_trade_discount($client, $list_id){
		$this->load->model('clients_model');
		$this->load->model('warehouse/warehouse_model');

		$clients = $this->clients_model->get_customer_groups($client);
		$list_id = explode(',', $list_id);
		
		$date = date('Y-m-d');

		$query = 'select * from '.db_prefix().'omni_trade_discount where end_time > CURDATE() and voucher = ""';
		$list_discount =  $this->db->query($query)->result_array();
		$result = [];
		foreach ($list_discount as $key => $discount) {
			$discount['group_items'] = explode(',', $discount['group_items']);
			$discount['clients'] = explode(',', $discount['clients']);
			$discount['group_clients'] = explode(',', $discount['group_clients']);
			$discount['items'] = explode(',', $discount['items']);
			$formal = $discount['formal'];
			$voucher = $discount['voucher'];
			$name = $discount['name_trade_discount'];
			$discounts = $discount['discount'];
			if(in_array($client, $discount['clients'])){
				array_push($result, array('voucher'=> $voucher, 'name'=> $name,  'formal' => $formal, 'discount' => $discounts));
				return $result;
			}

			if(!empty($clients)){
				foreach ($clients as $value) {
					if(in_array($value, $discount['group_clients'])){
						array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
						return $result;
					}
				}
			}
			if(!empty($list_id)){
				foreach ($list_id as $item) {
					$gr_items = $this->warehouse_model->get_commodity_group_type($item);
					if(in_array($gr_items, $discount['group_items'])){
						array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
						return $result;
					}
					if(in_array($item, $discount['items'])){
						array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
						return $result;
					}
				}
			}

			if(empty($discount['group_items']) && empty($discount['items']) && empty($discount['group_clients']) && empty($discount['clients'])){
				array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
				return $result;
			}
		}

		if(empty($result)){
			return false;
		}
		
	}
	/**
	 * check discount
	 * @param  int $id_product 
	 * @param  date $date       
	 * @return object             
	 */
	public function check_discount($id_product, $date, $channel = 0, $store = ''){
		if($store == ''){
			return $this->db->query('select * from '.db_prefix().'omni_trade_discount where find_in_set('.$id_product.',items) and start_time <= \''.$date.'\' and end_time >= \''.$date.'\' and voucher = \'\' and group_clients = \'\' and group_items = \'\' and clients = \'\'
				and channel = '.$channel.'  ')->row();
		}else{
			return $this->db->query('select * from '.db_prefix().'omni_trade_discount where find_in_set('.$id_product.',items) and start_time <= \''.$date.'\' and end_time >= \''.$date.'\' and voucher = \'\' and group_clients = \'\' and group_items = \'\' and clients = \'\' and channel = '.$channel.' and store = '.$store.'')->row();
		}
	}
	/**
	 * check out
	 * @param  array $data 
	 * @return string order_number       
	 */
	public function check_out_pos($data){
		$this->load->model('clients_model');
		$this->load->model('warehouse/warehouse_model');
		$chanel_id = 1;
		$data_client = $this->clients_model->get($data['userid']);  
		if($data_client){
			$user_id = $data['userid'];
			$order_number = $this->incrementalHash();
			$data_cart['userid'] = $user_id;
			$data_cart['seller'] = $data['seller'];
			$data_cart['voucher'] = $data['voucher'];
			$data_cart['order_number'] = $order_number;
			$data_cart['channel_id'] = $chanel_id;
			$data_cart['channel'] = 'pos';
			$data_cart['company'] =  $data_client->company;
			$data_cart['phonenumber'] =  $data_client->phonenumber;
			$data_cart['city'] =  $data_client->city;
			$data_cart['state'] =  $data_client->state;
			$data_cart['country'] =  $data_client->country;
			$data_cart['zip'] =  $data_client->zip;
			$data_cart['billing_street'] =  $data_client->billing_street;
			$data_cart['billing_city'] =  $data_client->billing_city;
			$data_cart['billing_state'] =  $data_client->billing_state;
			$data_cart['billing_country'] =  $data_client->billing_country;
			$data_cart['billing_zip'] =  $data_client->billing_zip;
			$data_cart['shipping_street'] =  $data_client->shipping_street;
			$data_cart['shipping_city'] =  $data_client->shipping_city;
			$data_cart['shipping_state'] =  $data_client->shipping_state;
			$data_cart['shipping_country'] =  $data_client->shipping_country;
			$data_cart['shipping_zip'] =  $data_client->shipping_zip;
			$data_cart['total'] =  $data['total'];
			$data_cart['sub_total'] =  $data['sub_total'];
			$data_cart['discount'] =  0;
			$data_cart['discount_type'] =  2;        
			$data_cart['notes'] =  $data['notes'];
			$data_cart['create_invoice'] =  $data['create_invoice'];
			$data_cart['stock_export'] =  $data['stock_export'];
			$customer_pay = 0;
			if(isset($data['customers_pay'])){
				foreach ($data['customers_pay'] as $key => $value_pay) {
					$customer_pay += $value_pay;
				}
			}
			$data_cart['customers_pay'] =  $customer_pay;
			$data_cart['amount_returned'] =  $data['amount_returned'];
			$data_cart['tax'] =  0;
			$data_cart['staff_note'] =  $data['staff_note'];
			$data_cart['payment_note'] =  $data['payment_note'];
			$data_cart['allowed_payment_modes'] =  isset($data['payment_methods']) ? implode(',', $data['payment_methods']) : '';

			$this->db->insert(db_prefix() . 'cart', $data_cart);
			$insert_id = $this->db->insert_id();
			if($insert_id){
				$date = date('Y-m-d');
				$productid_list = explode(',',$data['list_id_product']);
				$quantity_list = explode(',',$data['list_qty_product']);
				$count_total_percent = 0;
				$total_tax = 0;
				foreach ($productid_list as $key => $value) {
					$data_detailt['product_id'] = $value; 
					$item_quantity = $quantity_list[$key];      
					$data_detailt['quantity'] = $item_quantity;
					$data_detailt['classify'] = '';
					$data_detailt['cart_id']  = $insert_id;
					$product_name = '';
					$prices = '';
					$long_description = '';
					$sku = '';
					$data_products = $this->get_product($value);
					if($data_products){
						$product_name = $data_products->description;
						$long_description = $data_products->long_description;
						$sku = $data_products->sku_code;
					}
					$data_detailt['product_name'] = $product_name;
					$prices  = 0;
					$data_prices = $this->get_price_channel($value,$chanel_id);
					if($data_prices){
						$prices  = $data_prices->prices;
					}
					$data_detailt['prices'] = $prices;
					$data_detailt['percent_discount'] = 0;
					$data_detailt['prices_discount'] = 0;
					$tax_array = [];
					$get_tax_data = $this->get_tax_list_product($value);
					if($get_tax_data){
						foreach ($get_tax_data as $tax) {
							$total_tax_value = ($tax['taxrate'] * ($prices * $item_quantity) / 100);
							$total_tax += $total_tax_value;
							$tax_array[] = [
								'id' => $tax['id'],
								'name' => $tax['name'],
								'rate' => $tax['taxrate'],
								'value' => $total_tax_value
							];
						}						
					}
					$data_detailt['tax'] = json_encode($tax_array);
					$data_detailt['sku'] = $sku;
					$data_detailt['long_description'] = $long_description;
					$this->db->insert(db_prefix() . 'cart_detailt', $data_detailt);
				}
				$data_update['discount'] = $data['discount_total'];
				$data_update['tax'] = $total_tax;
				$this->db->where('id',$insert_id);
				$this->db->update(db_prefix() . 'cart', $data_update);
				$id = '';
				$number_invoice_ = '';
				$data_payment["date"]= _d(date('Y-m-d'));
				$data_payment["do_not_redirect"]='off';
				$data_payment["transactionid"]=$data_cart['order_number'];
				$data_payment["note"]='';
				if($data['create_invoice'] == 'on'){
					$id = $this->add_inv_when_order($insert_id,4);
					$this->db->where('id', $id);
					$number_invoice_ = $this->db->get(db_prefix().'invoices')->row()->number;
					$data_payment["invoiceid"]=$id;

					//add hook after invoice add from order
					hooks()->do_action('omni_sales_after_invoice_added', $insert_id);

					//Add log payment
					if($data['debit_order'] == 'off'){
						if(isset($data['payment_methods']) && isset($data['customers_pay'])){
							$this->load->model('payment_modes_model');
							foreach ($data['payment_methods'] as $key => $payment) {
								$amount = $data['customers_pay'][$key];
								if(count($data['payment_methods']) == 1){
									$amount = $data['total'];
								}	
								$data_payment["amount"] = $amount;
								$data_payment["paymentmode"] = $payment;
								$this->payments_model->add($data_payment); 
								$payment_name = '';
								$data_payments = $this->payment_modes_model->get($payment);
								if($data_payments){
									$payment_name = $data_payments->name;
								}

								$this->add_order_payment($insert_id, $payment, $payment_name, $amount);
							}
							$data_payment["paid"] = '';
						}
					}
					//End add log payment

					if($data_cart['stock_export'] == 'on'){
						$id_exp = '';
						if(isset($data['warehouse_id'])){
							$id_exp = $this->create_goods_delivery($id, $data['warehouse_id']);
						}
						else{
							$id_exp = $this->omnisales_auto_create_goods_delivery_with_invoice($id);
						}            
						$data_update['status'] = 4;
						$data_update['admin_action'] = 1;
						$data_update['stock_export_number'] = $id_exp;
						$this->db->where('id', $insert_id);
						$this->db->update(db_prefix().'cart', $data_update); 

						//add hook after delivery note add from order
						hooks()->do_action('omni_sales_after_delivery_note_added', $insert_id);               
					}     
				}
				$html_bill = '';
				$data_html_bill = $this->send_mail_order($insert_id, $data['userid']);   
				if(isset($data_html_bill) && $data_html_bill != '' && $data_html_bill){
					$html_bill = $data_html_bill;   
				}    
				$this->add_log_trade_discount($user_id, $order_number,$chanel_id, $data_cart['sub_total'], $data['discount_total'], $data_cart['tax'], $data_cart['total'], $data['voucher']);
				// Add log transaction
				if($data['debit_order'] == 'off'){
					if(isset($data['payment_methods'])){
						foreach ($data['payment_methods'] as $key => $payment) {
							if($payment == 2){
								$data_shift = $this->get_shift_staff($data['seller'], 1);
								if($data_shift){
									$shift_history_data = $this->get_shift_history($data_shift->id, true);
									if($shift_history_data){
										$current_amount = $shift_history_data->current_amount - $data['amount_returned'];
										$this->add_shift_transactions($data_shift->id, 'customer_pay', $order_number, $data_shift->granted_amount, $current_amount, $customer_pay, $data['amount_returned'], $data_cart['total'], $data['seller'], $user_id);  
									}
								}
							}
						}
					}
				}
				// End add log transaction

				$data_cart = $this->get_cart($insert_id);
				if($data_cart){
					hooks()->do_action('after_cart_added',$data_cart,$data);
				}
				return ['id_invoice' => $id,'number_invoice' => $number_invoice_, 'stock_export_number' => $data_cart->stock_export_number, 'payment' => isset($data_payment) ? $data_payment : '', 'html_bill' => $html_bill, 'insert_id' => $insert_id, 'warehouse_id' => $data['warehouse_id']];
			}
			return '';
		}     
	}

		/**
	 * get voucher
	 * @param   string $voucher 
	 * @return     $discount or 0      
	 */
		public function get_voucher($voucher){
			$query = 'SELECT * FROM '.db_prefix().'omni_trade_discount where end_time > CURDATE() and voucher != ""';
			$list_voucher = $this->db->query($query)->result_array();
			$array_code = [];
			$discount = [];
			foreach ($list_voucher as $key => $voucher_code) {
				array_push($array_code, $voucher_code['voucher']);
			}
			if(empty($list_voucher)){
				return 0;
			}else{
				if(in_array($voucher['voucher'], $array_code)){
					$this->db->where('voucher', $voucher['voucher']);
					$discount[] = $this->db->get(db_prefix().'omni_trade_discount')->row()->discount;
					$this->db->where('voucher', $voucher['voucher']);
					$discount[] = $this->db->get(db_prefix().'omni_trade_discount')->row()->formal;
					$this->db->where('voucher', $voucher['voucher']);
					$discount[] = $this->db->get(db_prefix().'omni_trade_discount')->row()->name_trade_discount;
					return $discount;
				}else{
					return 0;
				}
			}
		}

	/**
	 * credit note from invoice omni
	 * @param  int $invoice_id 
	 * @return  $id or false
	 */
	public function credit_note_from_invoice_omni($invoice_id)
	{
		$this->load->model('invoices_model');
		$this->load->model('credit_notes_model');
		$_invoice = $this->invoices_model->get($invoice_id);

		$new_credit_note_data             = [];
		$new_credit_note_data['clientid'] = $_invoice->clientid;
		$new_credit_note_data['number']   = get_option('next_credit_note_number');
		$new_credit_note_data['date']     = _d(date('Y-m-d'));

		$new_credit_note_data['show_quantity_as'] = $_invoice->show_quantity_as;
		$new_credit_note_data['currency']         = $_invoice->currency;
		$new_credit_note_data['subtotal']         = $_invoice->subtotal;
		$new_credit_note_data['total']            = $_invoice->total;
		$new_credit_note_data['adminnote']        = $_invoice->adminnote;
		$new_credit_note_data['adjustment']       = $_invoice->adjustment;
		$new_credit_note_data['discount_percent'] = $_invoice->discount_percent;
		$new_credit_note_data['discount_total']   = $_invoice->discount_total;
		$new_credit_note_data['discount_type']    = $_invoice->discount_type;


		$new_credit_note_data['billing_street']   = clear_textarea_breaks($_invoice->billing_street);
		$new_credit_note_data['billing_city']     = $_invoice->billing_city;
		$new_credit_note_data['billing_state']    = $_invoice->billing_state;
		$new_credit_note_data['billing_zip']      = $_invoice->billing_zip;
		$new_credit_note_data['billing_country']  = $_invoice->billing_country;
		$new_credit_note_data['shipping_street']  = clear_textarea_breaks($_invoice->shipping_street);
		$new_credit_note_data['shipping_city']    = $_invoice->shipping_city;
		$new_credit_note_data['shipping_state']   = $_invoice->shipping_state;
		$new_credit_note_data['shipping_zip']     = $_invoice->shipping_zip;
		$new_credit_note_data['shipping_country'] = $_invoice->shipping_country;
		$new_credit_note_data['reference_no']     = format_invoice_number($_invoice->id);
		if ($_invoice->include_shipping == 1) {
			$new_credit_note_data['include_shipping'] = $_invoice->include_shipping;
		}
		$new_credit_note_data['show_shipping_on_credit_note'] = $_invoice->show_shipping_on_invoice;
		$new_credit_note_data['clientnote']                   = get_option('predefined_clientnote_credit_note');
		$new_credit_note_data['terms']                        = get_option('predefined_terms_credit_note');
		$new_credit_note_data['adminnote']                    = '';
		$new_credit_note_data['newitems']                     = [];

		$custom_fields_items = get_custom_fields('items');
		$key                 = 1;
		foreach ($_invoice->items as $item) {
			$new_credit_note_data['newitems'][$key]['description']      = $item['description'];
			$new_credit_note_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
			$new_credit_note_data['newitems'][$key]['qty']              = $item['qty'];
			$new_credit_note_data['newitems'][$key]['unit']             = $item['unit'];
			$new_credit_note_data['newitems'][$key]['taxname']          = [];
			$taxes                                                      = get_invoice_item_taxes($item['id']);
			foreach ($taxes as $tax) {
				array_push($new_credit_note_data['newitems'][$key]['taxname'], $tax['taxname']);
			}
			$new_credit_note_data['newitems'][$key]['rate']  = $item['rate'];
			$new_credit_note_data['newitems'][$key]['order'] = $item['item_order'];
			foreach ($custom_fields_items as $cf) {
				$new_credit_note_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

				if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
					define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
				}
			}
			$key++;
		}
		$id = $this->credit_notes_model->add($new_credit_note_data);
		if ($id) {
			if ($_invoice->status != 2) {
				if ($this->credit_notes_model->apply_credits($id, ['invoice_id' => $invoice_id, 'amount' => $new_credit_note_data['discount_total']])) {
					update_invoice_status($invoice_id, true);
				}
			}

			log_activity('Created Credit Note From Invoice [Invoice: ' . format_invoice_number($_invoice->id) . ', Credit Note: ' . format_credit_note_number($id) . ']');

			hooks()->do_action('created_credit_note_from_invoice', ['invoice_id' => $invoice_id, 'credit_note_id' => $id]);

			return $id;
		}

		return false;
	}

		/**
		 * update status order comfirm 
		 * @param  int $order_id 
		 * @return bolean
		 */
		public function update_status_order_comfirm($order_id, $prefix = '' , $_invoice_number = '', $number = '', $status = 2){
			$code_invoice = $prefix . $_invoice_number;
			$this->db->where('id', $order_id);
			$dara = $this->db->update(db_prefix().'cart', ['status' => $status, 'admin_action' => 1, 'invoice' => $code_invoice, 'number_invoice' => $number]);
			if ($this->db->affected_rows() > 0) {
				return true;
			}
			return false;
		}
		/**
		 * get id invoice 
		 * @param  $number
		 * @return   id invoice    
		 */
		public function get_id_invoice($number){
			$this->db->where('number', $number);
			return $this->db->get(db_prefix().'invoices')->row()->id;
		}
		/**
	 * add inv and out of stock pos
	 * @param int $orderid 
	 * @return bolean
	 */
		public function add_inv_and_out_of_stock_pos($orderid, $status = '') {
			$this->load->model('invoices_model');
			$this->load->model('credit_notes_model');
			$cart = $this->get_cart($orderid);

			$cart_detailt = $this->get_cart_detailt_by_master($orderid);
			$newitems = [];   
			foreach ($cart_detailt as $key => $value) {
				array_push($newitems, array('order' => $key, 'description' => $value['product_name'], 'long_description' => $value['long_description'], 'qty' => $value['quantity'], 'unit' => '', 'rate'=> $value['prices']));
			}

			$total = $this->get_total_order($orderid)['total'];
			$sub_total = $this->get_total_order($orderid)['sub_total'];
			$discount_total = $this->get_total_order($orderid)['discount'];
			$__number = get_option('next_invoice_number');
			$_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
			$this->db->where('isdefault', 1);
			$curreny = $this->db->get(db_prefix().'currencies')->row()->id;

			if($cart){
				$data['clientid'] = $cart->userid;
				$data['billing_street'] = $cart->billing_street;
				$data['billing_city'] = $cart->billing_city;
				$data['billing_state'] = $cart->billing_state;
				$data['billing_zip'] = $cart->billing_zip;
				$data['billing_country'] = $cart->billing_country;
				$data['include_shipping'] = 1;
				$data['show_shipping_on_invoice'] = 1;
				$data['shipping_street'] = $cart->shipping_street;
				$data['shipping_city'] = $cart->shipping_city;
				$data['shipping_state'] = $cart->shipping_state;
				$data['shipping_zip'] = $cart->shipping_zip;
				$date_format   = get_option('dateformat');
				$date_format   = explode('|', $date_format);
				$date_format   = $date_format[0];       
				$data['date'] = date($date_format);
				$data['duedate'] = date($date_format);
				//terms_invoice
				$data['terms'] = get_option('predefined_terms_invoice');

				$data['currency'] = $curreny;
				$data['newitems'] = $newitems;
				$data['number'] = $_invoice_number;
				$data['total'] = $cart->total;
				$data['subtotal'] = $cart->sub_total;
				$data['discount_total'] = $cart->discount_total;
				if($cart->discount_type == 1){
					$data['discount_percent'] = $cart->discount;
				}elseif($cart->discount_type == 2){
					$data['discount_percent'] =  ($cart->discount_total/$data['subtotal'])*100;
				}

				$id = $this->invoices_model->add($data);
				if($cart->discount != '' && $cart->discount_type != '' && $cart->voucher != ''){
					$credit_notes = $this->credit_note_from_invoice_omni($id);
				}            
				if($id){
					$this->warehouse_model->auto_create_goods_delivery_with_invoice($id);
					$prefix = get_option('invoice_prefix');
					if($status!=''){
						$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number, $status);
					}
					else{
						$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number);
					}           
					return true;
				}
			}   
			return true;
		}

	 /**
		 * get id invoice 
		 * @param  $id
		 * @return   number invoice    
		 */
	 public function get_number_invoice($id){
	 	$this->db->where('id', $id);
	 	return $this->db->get(db_prefix().'invoices')->row()->number;
	 }

		/**
	 * add invoice when order
	 * @param int $orderid 
	 * @return bolean
	 */
		public function add_inv_when_order($orderid, $status = '') {
			$this->load->model('invoices_model');
			$this->load->model('credit_notes_model');
			$cart = $this->get_cart($orderid);

			$cart_detailt = $this->get_cart_detailt_by_master($orderid);
			$newitems = [];   
			$count = 0; 
			foreach ($cart_detailt as $key => $value) {
				$unit = 0;
				$unit_name = '';
				$data_product = $this->get_product($value['product_id']);
				if($data_product){
					$tax = $this->get_tax($data_product->tax);
					if($tax == ''){
						$taxname = '';
					}else{
						$taxname = $tax->name.'|'.$tax->taxrate;
					}
					if($data_product){        
						$unit = $data_product->unit_id;
						if($unit != 0 || $unit != null){
							$this->db->where('unit_type_id', $unit);
							$unit_parent = $this->db->get(db_prefix().'ware_unit_type')->row();
							if($unit_parent){
								$unit_name = $unit_parent->unit_name;								
							}
						}else{
							$unit_name = "";
						}
					}
					$count = $key;
					array_push($newitems, array('order' => $key, 'description' => $value['product_name'], 'long_description' => $value['long_description'], 'qty' => $value['quantity'], 'unit' => $unit_name, 'rate'=> $value['prices'], 'taxname' => array($taxname)));
				}
			}
			$total_order = $this->get_total_order($orderid);
			$total = $total_order['total'];
			$sub_total = $total_order['sub_total'];
			$discount_total = $total_order['discount'];
			$__number = get_option('next_invoice_number');
			$_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
			$this->db->where('isdefault', 1);
			$curreny = $this->db->get(db_prefix().'currencies')->row()->id;

			if($cart){
				$data['clientid'] = $cart->userid;
				$data['billing_street'] = $cart->billing_street;
				$data['billing_city'] = $cart->billing_city;
				$data['billing_state'] = $cart->billing_state;
				$data['billing_zip'] = $cart->billing_zip;
				$data['billing_country'] = $cart->billing_country;
				$data['include_shipping'] = 1;
				$data['show_shipping_on_invoice'] = 1;
				$data['shipping_street'] = $cart->shipping_street;
				$data['shipping_city'] = $cart->shipping_city;
				$data['shipping_state'] = $cart->shipping_state;
				$data['shipping_zip'] = $cart->shipping_zip;
				$date_format   = get_option('dateformat');
				$date_format   = explode('|', $date_format);
				$date_format   = $date_format[0];       
				$data['date'] = date($date_format);
				$data['duedate'] = date($date_format);
				//terms_invoice
				$data['terms'] = get_option('predefined_terms_invoice');

				$payment_model_list = [];
				if($cart->allowed_payment_modes != ''){
					$payment_model_list = explode(',', $cart->allowed_payment_modes);
				}
				$data["allowed_payment_modes"] = $payment_model_list;
				if(isset($cart->shipping)){
					if($cart->shipping > 0){
						array_push($newitems, array('order' => $count+1, 'description' => _l('shipping'), 'long_description' => "", 'qty' => 1, 'unit' => "", 'rate'=> $cart->shipping, 'taxname' => array()));
					}
				}

				$data['currency'] = $curreny;
				$data['newitems'] = $newitems;
				$data['number'] = $_invoice_number;
				$data['total'] = $cart->total;
				$data['subtotal'] = $cart->sub_total;
				if($cart->discount_type == 1){
					$data['discount_percent'] = $cart->discount;
					$data['discount_total'] =  ($cart->discount * $data['subtotal'])/100;
				}elseif($cart->discount_type == 2){
					$data['discount_total'] = $cart->discount;
					$data['discount_percent'] =  ($cart->discount/$data['subtotal'])*100;
				}else{
					$data['discount_total'] = '';
					$data['discount_percent'] = '';
				}
				$id = $this->invoices_model->add($data);
				if($cart->discount != '' && $cart->discount_type != '' && $cart->voucher != ''){
					$credit_notes = $this->credit_note_from_invoice_omni($id);
				} 
				$prefix = get_option('invoice_prefix');
				$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number, $status);
				return $id;
			}   
			return true;
		}
	 /**
		 * get invoice 
		 * @param  $number
		 * @return   invoice    
		 */
	 public function get_invoice($number){
	 	$this->db->where('number', $number);
	 	return $this->db->get(db_prefix().'invoices')->row();
	 }
		/**
	 * create export stock
	 * @param int $orderid 
	 * @param int $status 
	 * @return bolean
	 */
		public function create_export_stock($orderid, $status = '') {
			$this->load->model('warehouse/warehouse_model');

			$cart = $this->get_cart($orderid);  
			$cart_detailt = $this->get_cart_detailt_by_master($orderid);

			$id = $this->get_id_invoice($cart->number_invoice);
			$this->load->model('invoices_model');
			$this->load->model('credit_notes_model');

			$total = $this->get_total_order($orderid)['total'];
			$sub_total = $this->get_total_order($orderid)['sub_total'];
			$discount_total = $this->get_total_order($orderid)['discount'];
			$__number = get_option('next_invoice_number');
			$_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
			$this->db->where('isdefault', 1);
			$curreny = $this->db->get(db_prefix().'currencies')->row()->id;


			$data_delivery["id"] ='';
			$data_delivery["date_c"] = date('Y-m-d');
			$data_delivery["date_add"] = date('Y-m-d');
			$data_delivery["customer_code"] = $cart->userid;
			$data_delivery["invoice_id"] = $id;
			$data_delivery["to_"] = $cart->company;
			$data_delivery["address"] = $cart->billing_street;
			$data_delivery["staff_id"] = get_staff_user_id();
			$data_delivery["description"] ='';
			$data_delivery["total_money"] = $total;
			$data_delivery["total_discount"] = $discount_total;
			$data_delivery["after_discount"] = $discount_total;
			$data_delivery["cart_detailt"] = $cart_detailt;
			if(isset($type)){
				$this->db->insert(db_prefix().'goods_delivery_invoices_pr_orders', [
					'rel_id'  => $rel_id,
					'rel_type'  => $rel_type,
					'type'    => $type,
				]);

			}
			$id_exp = $this->omnisales_auto_create_goods_delivery_with_invoice($id);
			
			//add hook after delivery note add from order
            hooks()->do_action('omni_sales_after_delivery_note_added', $orderid);

			$data_update['status'] = $status;
			$data_update['admin_action'] = 1;
			$data_update['stock_export_number'] = $id_exp;

			$this->db->where('id', $orderid);
			$this->db->update(db_prefix().'cart', $data_update);
			if ($this->db->affected_rows() > 0) {
				return true;
			}
			return false;
		}
		/**
		 * notifications
		 * @param  integer $id_staff    
		 * @param  string $link        
		 * @param  string $description 
		 */
		public function notifications($id_staff, $link, $description){
			$notifiedUsers = [];
			$id_userlogin = get_staff_user_id();

			$notified = add_notification([
				'fromuserid'      => $id_userlogin,
				'description'     => $description,
				'link'            => $link,
				'touserid'        => $id_staff,
				'additional_data' => serialize([
					$description,
				]),
			]);
			if ($notified) {
				array_push($notifiedUsers, $id_staff);
			}
			pusher_trigger_notification($notifiedUsers);
		}
		/**
		 * get data by week
		 * @param  date $start_date 
		 * @param  date $end_date   
		 * @param  integer $channel_id 
		 * @return array             
		 */
		public function get_data_by_week($start_date, $end_date, $channel_id){
			$query = 'SELECT day(datecreator) as dayonly,count(*) as count FROM '.db_prefix().'cart where datecreator between \''.$start_date.'\' and \''.$end_date.'\' and channel_id = '.$channel_id.' group by dayonly';
			return $this->db->query($query)->result_array();
		}
	/**
		 * auto_create_goods_delivery_with_invoice
		 * @param  integer $invoice_id 
		 *              
		 */
	public function auto_create_goods_delivery_with_invoice_s($invoice_id)
	{
		$check_arr = $this->get_invoices_goods_delivery('invoice');
		if(in_array($invoice_id, $check_arr)){
			return true;
		}
		$this->load->model('warehouse/warehouse_model');
		$this->db->where('id', $invoice_id);
		$invoice_value = $this->db->get(db_prefix().'invoices')->row();

		if($invoice_value){

			/*get value for goods delivery*/

			$data['goods_delivery_code'] = $this->warehouse_model->create_goods_delivery_code();

			if(!$this->warehouse_model->check_format_date($invoice_value->date)){
				$data['date_c'] = to_sql_date($invoice_value->date);
			}else{
				$data['date_c'] = $invoice_value->date;
			}


			if(!$this->warehouse_model->check_format_date($invoice_value->date)){
				$data['date_add'] = to_sql_date($invoice_value->date);

			}else{
				$data['date_add'] = $invoice_value->date;
			}

			$data['customer_code']  = $invoice_value->clientid;
			$data['invoice_id']   = $invoice_id;
			$data['addedfrom']  = $invoice_value->addedfrom;
			$data['description']  = $invoice_value->adminnote;
			$data['address']  = $this->warehouse_model->get_shipping_address_from_invoice($invoice_id);

			$data['total_money']  = (float)$invoice_value->subtotal + (float)$invoice_value->total_tax;
			$data['total_discount'] = $invoice_value->discount_total;
			$data['after_discount'] = $invoice_value->total;

			/*get data for goods delivery detail*/
			/*get item in invoices*/
			$this->db->where('rel_id', $invoice_id);
			$this->db->where('rel_type', 'invoice');
			$arr_itemable = $this->db->get(db_prefix().'itemable')->result_array();

			$arr_item_insert=[];
			$index=0;

			if(count($arr_itemable) > 0){
				foreach ($arr_itemable as $key => $value) {
					$commodity_code = $this->warehouse_model->get_itemid_from_name($value['description']);
					if($commodity_code != 0){
						/*get item from name*/
						$arr_item_insert[$index]['commodity_code'] = $commodity_code;
						$arr_item_insert[$index]['quantities'] = $value['qty'] + 0;
						$arr_item_insert[$index]['unit_price'] = $value['rate'] + 0;
						$arr_item_insert[$index]['tax_id'] = '';

						$arr_item_insert[$index]['total_money'] = (float)$value['qty']*(float)$value['rate'];
						$arr_item_insert[$index]['total_after_discount'] = (float)$value['qty']*(float)$value['rate'];

						/*update after : goods_delivery_id, warehouse_id*/

						/*get tax item*/
						$this->db->where('itemid', $value['id']);
						$this->db->where('rel_id', $invoice_id);
						$this->db->where('rel_type', "invoice");

						$item_tax = $this->db->get(db_prefix().'item_tax')->result_array();

						if(count($item_tax) > 0){
							foreach ($item_tax as $tax_value) {
								$tax_id = $this->warehouse_model->get_tax_id_from_taxname_taxrate($tax_value['taxname'], $tax_value['taxrate']);

								if($tax_id != 0){
									if(strlen($arr_item_insert[$index]['tax_id']) != ''){
										$arr_item_insert[$index]['tax_id'] .= '|'.$tax_id;
									}else{
										$arr_item_insert[$index]['tax_id'] .= $tax_id;

									}
								}


								$arr_item_insert[$index]['total_money'] += (float)$value['qty']*(float)$value['rate']*(float)$tax_value['taxrate']/100;

								$arr_item_insert[$index]['total_after_discount'] += (float)$value['qty']*(float)$value['rate']*(float)$tax_value['taxrate']/100;

							}
						}

						$index++;
					}


				}
			}

			$data_insert=[];

			$data_insert['goods_delivery'] = $data;
			$data_insert['goods_delivery_detail'] = $arr_item_insert;

			$status = $this->add_goods_delivery_from_invoice_s($data_insert, '', $invoice_id);

			if($status){
				return true;
			}else{
				return false;
			}

		}

		return false;

	}
		/**
		 * add goods delivery from invoice
		 * @param  $data_insert 
		 */
		public function add_goods_delivery_from_invoice_s($data_insert, $warehouse_id = '', $invoice_id = '')
		{

			$this->load->model('warehouse/warehouse_model');
			$results=0;
			$flag_export_warehouse = 1;

			$check_appr = $this->warehouse_model->get_approve_setting('2');

			$data_insert['goods_delivery']['approval'] = 0;
			if ($check_appr && $check_appr != false) {
				$data_insert['goods_delivery']['approval'] = 0;
			} else {
				$data_insert['goods_delivery']['approval'] = 1;
			}

			$this->db->insert(db_prefix() . 'goods_delivery', $data_insert['goods_delivery']);
			$insert_id = $this->db->insert_id();

			$this->db->insert(db_prefix().'goods_delivery_invoices_pr_orders', [
				'rel_id'  => $insert_id,
				'rel_type'  => $invoice_id,
				'type'    => 'invoice',
			]);


			if (isset($insert_id)) {

				foreach ($data_insert['goods_delivery_detail'] as $delivery_detail_key => $delivery_detail_value) {
					/*check export warehouse*/

					$inventory = $this->warehouse_model->get_inventory_by_commodity($delivery_detail_value['commodity_code']);
					if($inventory){
						$inventory_number =  $inventory->inventory_number;

						if((float)$inventory_number < (float)$delivery_detail_value['quantities'] ){
							$flag_export_warehouse = 0;
						}

					}else{
						$flag_export_warehouse = 0;
					}

					$delivery_detail_value['goods_delivery_id'] = $insert_id;
					$delivery_detail_value['warehouse_id'] = $warehouse_id;
					$this->db->insert(db_prefix() . 'goods_delivery_detail', $delivery_detail_value);
					$insert_detail = $this->db->insert_id();

					$results++;

				}

				$data_log = [];
				$data_log['rel_id'] = $insert_id;
				$data_log['rel_type'] = 'stock_export';
				$data_log['staffid'] = get_staff_user_id();
				$data_log['date'] = date('Y-m-d H:i:s');
				$data_log['note'] = "stock_export";

				$this->warehouse_model->add_activity_log($data_log);

			}


			if($flag_export_warehouse == 1){
				$data_update['approval'] = 1;
				$this->db->where('id', $insert_id);
				$this->db->update(db_prefix() . 'goods_delivery', $data_update);

				$goods_delivery_detail = $this->warehouse_model->get_goods_delivery_detail($insert_id);
				foreach ($goods_delivery_detail as $goods_delivery_detail_value) {
					$this->add_inventory_from_invoices($goods_delivery_detail_value);
				}
			}
			return $insert_id;

		}

	/**
	 *  delete_product  
	 * @param   int $id   
	 * @return  bool       
	 */
	public function delete_product_store($store, $id){
		$this->db->where('id',$id);
		$this->db->where('woocommere_store_id',$store);
		$this->db->delete(db_prefix().'woocommere_store_detailt');
		if ($this->db->affected_rows() > 0) {           
			return true;
		}
		return false;
	}
	/**
	 * get all product group
	 * @param  $group_items 
	 * @return list items             
	 */
	public function get_all_product_group($group_items){
		$this->db->where('group_id',$group_items);
		return $this->db->get(db_prefix().'items')->result_array();
	}
	/**
	 *  check tax product
	 * @param  $list_product 
	 * @return  array           
	 */
	public function check_tax_product($list_product){
		$array = [];
		if(!empty($list_product)){
			$list_product = explode(',', $list_product);
			foreach ($list_product as $key => $value) { 
				$product = $this->get_product($value);
				if($product){
					if($product->tax != '' && !is_null($product->tax)){
						$this->db->where('id', $product->tax);              
						$tax = $this->db->get(db_prefix().'taxes')->row();
						if($tax){
							array_push($array, $tax->taxrate);
						}
						else{
							array_push($array, 0);                  
						}
					}else{
						array_push($array, 0);
					}
				}
			}
		}
		return $array;
	}
	/**
	 * get tax
	 * @param $tax_id 
	 * @return            
	 */
	public function get_tax($tax_id){
		if($tax_id == 0){
			return '';
		}
		$this->db->where('id', $tax_id);
		return $this->db->get(db_prefix().'taxes')->row();
	}
	/**
	 * get data
	 * @param  $query
	 * @param  boolean $array 
	 * @return data         
	 */
	public function get_data($query, $array = false){
		if($array == false){
			return $this->db->query($query)->row();
		}
		else{
			return $this->db->query($query)->result_array();        
		}
	}

	/**
	 * create_new_tax_sync
	 * @param  $store_id      
	 * @param  $taxclass_name 
	 * @param  $tax_rate      
	 * @return                
	 */
	public function create_new_tax_sync($store_id, $taxclass_name, $tax_rate){
		$woocommerce = $this->init_connect_woocommerce($store_id);

		$data = [
			'name' => $taxclass_name
		];
		$list_tax_class = $woocommerce->get('taxes/classes');
		$slug = [];
		foreach ($list_tax_class as $key => $value) {
			$slug[] = $value->slug;
		}
		$replaces = $this->clean($taxclass_name);
		if(in_array($replaces, $slug)){
			return $replaces;
		}

		$list_tax = $woocommerce->get('taxes/classes');
		$tax_class_new = $woocommerce->post('taxes/classes', $data);
		$slug_class =  $tax_class_new->slug;

		$data_rates = [
			"country"=> "",
			"state" => "",
			"postcode" => "",
			"city" => "",
			"compound" => false,
			"shipping" => false,
			'rate' => $tax_rate,
			'name' => $taxclass_name,    
			'class' => $slug_class,
		];
		$woocommerce->post('taxes', $data_rates);
		return $slug_class;
	}

	 /**
		 * create goods delivery
		 * @param  integer $invoice_id 
		 *              
		 */
	 public function create_goods_delivery($invoice_id, $warehouse_id = '')
	 {
	 	$this->load->model('warehouse/warehouse_model');
	 	$this->db->where('id', $invoice_id);
	 	$invoice_value = $this->db->get(db_prefix().'invoices')->row();

	 	if($invoice_value){
	 		$data['goods_delivery_code'] = $this->warehouse_model->create_goods_delivery_code();

	 		if(!$this->warehouse_model->check_format_date($invoice_value->date)){
	 			$data['date_c'] = to_sql_date($invoice_value->date);
	 		}else{
	 			$data['date_c'] = $invoice_value->date;
	 		}


	 		if(!$this->warehouse_model->check_format_date($invoice_value->date)){
	 			$data['date_add'] = to_sql_date($invoice_value->date);

	 		}else{
	 			$data['date_add'] = $invoice_value->date;
	 		}

	 		$data['customer_code']  = $invoice_value->clientid;
	 		$data['invoice_id']   = $invoice_id;
	 		$data['addedfrom']  = $invoice_value->addedfrom;
	 		$data['description']  = $invoice_value->adminnote;

	 		$data['total_money']  = (float)$invoice_value->subtotal + (float)$invoice_value->total_tax;
	 		$data['total_discount'] = $invoice_value->discount_total;
	 		$data['after_discount'] = $invoice_value->total;

	 		/*get data for goods delivery detail*/
	 		/*get item in invoices*/
	 		$this->db->where('rel_id', $invoice_id);
	 		$this->db->where('rel_type', 'invoice');
	 		$arr_itemable = $this->db->get(db_prefix().'itemable')->result_array();

	 		$arr_item_insert=[];
	 		$index=0;

	 		if(count($arr_itemable) > 0){
	 			foreach ($arr_itemable as $key => $value) {
	 				$commodity_code = $this->warehouse_model->get_itemid_from_name($value['description']);
	 				if($commodity_code != 0){
	 					/*get item from name*/
	 					$arr_item_insert[$index]['commodity_code'] = $commodity_code;
	 					$arr_item_insert[$index]['quantities'] = $value['qty'] + 0;
	 					$arr_item_insert[$index]['unit_price'] = $value['rate'] + 0;
	 					$arr_item_insert[$index]['tax_id'] = '';

	 					$arr_item_insert[$index]['total_money'] = (float)$value['qty']*(float)$value['rate'];
	 					$arr_item_insert[$index]['total_after_discount'] = (float)$value['qty']*(float)$value['rate'];

	 					/*update after : goods_delivery_id, warehouse_id*/

	 					/*get tax item*/
	 					$this->db->where('itemid', $value['id']);
	 					$this->db->where('rel_id', $invoice_id);
	 					$this->db->where('rel_type', "invoice");

	 					$item_tax = $this->db->get(db_prefix().'item_tax')->result_array();

	 					if(count($item_tax) > 0){
	 						foreach ($item_tax as $tax_value) {
	 							$tax_id = $this->warehouse_model->get_tax_id_from_taxname_taxrate($tax_value['taxname'], $tax_value['taxrate']);

	 							if($tax_id != 0){
	 								if(strlen($arr_item_insert[$index]['tax_id']) != ''){
	 									$arr_item_insert[$index]['tax_id'] .= '|'.$tax_id;
	 								}else{
	 									$arr_item_insert[$index]['tax_id'] .= $tax_id;

	 								}
	 							}


	 							$arr_item_insert[$index]['total_money'] += (float)$value['qty']*(float)$value['rate']*(float)$tax_value['taxrate']/100;

	 							$arr_item_insert[$index]['total_after_discount'] += (float)$value['qty']*(float)$value['rate']*(float)$tax_value['taxrate']/100;

	 						}
	 					}
	 					$index++;
	 				}
	 			}
	 		}

	 		$data_insert=[];

	 		$data_insert['goods_delivery'] = $data;
	 		$data_insert['goods_delivery_detail'] = $arr_item_insert;

	 		$status = $this->add_goods_delivery_from_invoice_s($data_insert, $warehouse_id, $invoice_id);
	 		if($status){
	 			return $status;
	 		}else{
	 			return false;
	 		}
	 	}
	 	return false;
	 }
		 /**
	 * get tax product
	 * @return  decimal $tax           
	 */
		 public function get_tax_product($id_product){
		 	if($id_product!=''){
		 		$product = $this->get_product($id_product);
		 		if($product){

		 			if($product->tax != '' && $product->tax){
		 				$this->db->where('id', $product->tax);              
		 				$tax = $this->db->get(db_prefix().'taxes')->row();
		 				if($tax){
		 					return $tax->taxrate;
		 				}
		 				else{
		 					return 0;                  
		 				}
		 			}

		 		}
		 		return 0;                  
		 	}
		 }
	 /**
	 * [apply trade_discount pos
	 * @param  int $client  
	 * @param  int $list_id 
	 * @return array or bool          
	 */
	 public function apply_trade_discount_pos($client, $list_id){
	 	$this->load->model('clients_model');
	 	$this->load->model('warehouse/warehouse_model');

	 	$clients = $this->clients_model->get_customer_groups($client);
	 	$list_id = explode(',', $list_id);

	 	$date = date('Y-m-d');

	 	$query = 'select * from '.db_prefix().'omni_trade_discount where end_time > CURDATE() and voucher = ""';
	 	$list_discount =  $this->db->query($query)->result_array();
	 	$result = [];
	 	foreach ($list_discount as $key => $discount) {

	 		$discount['group_items'] = explode(',', $discount['group_items']);
	 		$discount['clients'] = explode(',', $discount['clients']);
	 		$discount['group_clients'] = explode(',', $discount['group_clients']);
	 		$discount['items'] = explode(',', $discount['items']);
	 		$formal = $discount['formal'];
	 		$voucher = $discount['voucher'];
	 		$name = $discount['name_trade_discount'];
	 		$discounts = $discount['discount'];
	 		if(in_array($client, $discount['clients'])){
	 			array_push($result, array('voucher'=> $voucher, 'name'=> $name,  'formal' => $formal, 'discount' => $discounts));
	 			return $result;
	 		}


	 		if(!empty($clients)){
	 			foreach ($clients as $value) {
	 				if(in_array($value, $discount['group_clients'])){
	 					array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
	 					return $result;
	 				}
	 			}
	 		}

	 		if(!empty($list_id)){
	 			foreach ($list_id as $item) {
	 				$gr_items = $this->warehouse_model->get_commodity_group_type($item);
	 				if(in_array($gr_items, $discount['group_items'])){
	 					array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
	 					return $result;
	 				}
	 				if(in_array($item, $discount['items'])){
	 					array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
	 					return $result;
	 				}
	 			}
	 		}

	 		if(empty($discount['group_items']) && empty($discount['items']) && empty($discount['group_clients']) && empty($discount['clients'])){
	 			array_push($result, array('voucher'=> $voucher, 'name'=> $name, 'formal' => $formal, 'discount' => $discounts));
	 			return $result;
	 		}
	 	}

	 	if(empty($result)){
	 		return false;
	 	}    
	 }
	/**
	 * Gets the discount list.
	 *
	 * @param      string  $channel_id  The channel identifier
	 * @param      string  $client      The client
	 *
	 * @return     The discount list.
	 */
	public function get_discount_list($channel_id, $client = '', $voucher=''){
		if($voucher == ''){
			if($client!=''){
				$data_group = $this->db->query('select * from '.db_prefix().'customer_groups where customer_id = '.$client)->result_array();
				$list_group = '';
				foreach ($data_group as $key => $group) {
					$list_group .= 'find_in_set('.$group['groupid'].',group_clients) or '; 
				}
				$open = '';
				$close = '';
				if($list_group != ''){
					$open = '(';
					$close = ')';
				}

				$query = 'select * from '.db_prefix().'omni_trade_discount where ('.$open.''.$list_group.'find_in_set('.$client.',clients)'.$close.' or (group_clients="" and clients="")) and end_time >= CURDATE() and channel = '.$channel_id.' and voucher = "" order by id desc limit 0,1';
				$data_dis = $this->db->query($query)->result_array();

				$data_dis = hooks()->apply_filters('apply_mbs_program_discount', $data_dis, $client);
				return $data_dis;
			}
			else{
				$query = 'select * from '.db_prefix().'omni_trade_discount where group_clients = "" and clients = "" and  end_time > CURDATE() and channel = '.$channel_id.' and voucher = ""  order by id desc limit 0,1';
				return $this->db->query($query)->result_array();      
			}
		}
		else{
			if($client!=''){
				$data_group = $this->db->query('select * from '.db_prefix().'customer_groups where customer_id = '.$client)->result_array();
				$list_group = '';
				foreach ($data_group as $key => $group) {
					$list_group .= 'find_in_set('.$group['groupid'].',group_clients) or '; 
				}
				$open = '';
				$close = '';
				if($list_group != ''){
					$open = '(';
					$close = ')';
				}
				$query = 'select * from '.db_prefix().'omni_trade_discount where ('.$open.''.$list_group.'find_in_set('.$client.',clients)'.$close.' or (group_clients="" and clients="")) and end_time >= CURDATE() and channel = '.$channel_id.' and voucher = "'.$voucher.'" order by id desc limit 0,1';
				$data_rs = $this->db->query($query)->row()->limit(0,1);

				$data_rs = hooks()->apply_filters('apply_other_voucher', $data_rs, $client,$voucher);

				return $data_rs;
			}
		}
	}

	/**
	 * sync from the store to the system
	 * @param  int $store_id          
	 */
	public function sync_from_the_store_to_the_system($store_id, $omni_warehouse){
		$this->load->model('warehouse/warehouse_model');
		$this->load->model('misc_model');
		$woocommerce = $this->init_connect_woocommerce($store_id);
		$per_page = 100;
		$products_store = [];
		$data_new = [];
		$data_new_s = [];
		$product_update = [];
		$count = 0;
		$profif_ratio = get_option('warehouse_selling_price_rule_profif_ratio');

		$arrContextOptions=array(
							    "ssl"=>array(
							        "verify_peer"=>false,
							        "verify_peer_name"=>false,
							    ),
							);

		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$products_store = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);
			$commodity_code = [];
			$description = [];
			$commodity_barcode = [];
			$sku_code = [];
			$sku_name = [];
			$long_description = [];
			$commodity_type = [];
			$unit_id = [];
			$group_id = [];
			$purchase_price = [];
			$rate = [];
			$tax = [];
			$origin = [];
			$style_id = [];
			$model_id = [];
			$size_id = [];
			$color = [];
			$guarantee = [];
			$long_descriptions = [];
			$id = [];
			$images = [];
			$type = [];
			$stock_quantity = [];
			//attributes product from store in woocommerce
			$attributes = [];
			$parent_attributes = [];
			foreach ($products_store as $key => $products) {
				array_push($id, $products->id);
				array_push($commodity_code, $products->sku);
				array_push($description, $products->name);
				array_push($commodity_barcode, $this->warehouse_model->generate_commodity_barcode());
				array_push($sku_code, $products->sku);
				array_push($sku_name, $products->name);
				array_push($long_description, $products->short_description);
				array_push($commodity_type, '');
				array_push($unit_id, 1);
				array_push($group_id, '');
				array_push($rate, $products->price);
				array_push($tax, '');
				array_push($origin, '');
				array_push($style_id, '');
				array_push($model_id, '');
				array_push($size_id, '');
				array_push($color, '');
				array_push($guarantee, '');
				array_push($long_descriptions, $products->description);      
				array_push($images, $products->images);
				array_push($type, $products->type);
				array_push($attributes, $products->variations);
				array_push($stock_quantity, $products->stock_quantity);
				$products_attributes_value = [];
				if(count($products->attributes) > 0){
					foreach ($products->attributes as $products_attribute) {
						$products_attributes_value[] = ['name' => $products_attribute->name, 'options' => $products_attribute->options];
					}
				}
				if(count($products_attributes_value) > 0){
					array_push($parent_attributes, json_encode($products_attributes_value));
				}else{
					array_push($parent_attributes, null);
				}
			}

			$products_all = $this->get_product();
			$array_sku_code = [];
			foreach ($products_all as $product) {
				if(!is_null($product['sku_code'])){
					array_push($array_sku_code, $product['sku_code']);
				}
			}
			foreach ($sku_code as $key => $value) {
				if(!in_array($value, $array_sku_code)){
					$purchase_price = $this->caculator_purchase_price($rate[$key]);
					$data = [
						"commodity_code" => $commodity_code[$key],
						"description" => $description[$key],
						"commodity_barcode" => $commodity_barcode[$key],
						"sku_code" => $value,
						"sku_name" => $sku_name[$key],
						"long_description" => $long_description[$key],
						"commodity_type" => "0",
						"unit_id" => $unit_id[$key],
						"group_id" => $group_id[$key],
						"rate" => $rate[$key],
						"tax" => $tax[$key],
						"profif_ratio" => $profif_ratio,
						"origin" => $origin[$key],
						"style_id" => $style_id[$key],
						"model_id" => $model_id[$key],
						"size_id" => $size_id[$key],
						"color" => $color[$key],
						"guarantee" => $guarantee[$key],
						"long_descriptions" => $long_descriptions[$key],
						"parent_attributes" => $parent_attributes[$key],
						"sub_group"=> "",
						"without_checking_warehouse"=> "0",
						"purchase_price" => $purchase_price
					];

					//insert item
					$idw = $this->warehouse_model->add_commodity_one_item($data);
					$ids = $idw['insert_id']; 

					// create image product sync from store to crm
					if(!empty($images)){
						foreach ($images[$key] as $image) {
							$url_to_image = $image->src;
							$my_save_dir = 'modules/warehouse/uploads/item_img/'.$ids.'/';
							$filename = basename($url_to_image);

							$filename = explode('?',$filename)[0];

							$complete_save_loc = $my_save_dir.$filename;

							_maybe_create_upload_path($my_save_dir);

							

							if(file_put_contents($complete_save_loc,file_get_contents($url_to_image, false, stream_context_create($arrContextOptions)))){

								$filetype = array(
									'jpg' => 'image/jpeg',
									'png' => 'image/png',
									'gif' => 'image/gif',
								);
								$attachment   = [];
								if(isset($filetype[pathinfo($image->src, PATHINFO_EXTENSION)])){
									$attachment[] = [
										'file_name' => $filename,
										'filetype'  => $filetype[pathinfo($image->src, PATHINFO_EXTENSION)],
									];
								}else{

									$f_type = explode("?",pathinfo($image->src, PATHINFO_EXTENSION));
									if(isset($filetype[$f_type[0]])){
										$attachment[] = [
											'file_name' => $filename,
											'filetype'  => $filetype[$f_type[0]],
										];
									}
								}
								if(count($attachment)>0){
									$this->misc_model->add_attachment_to_database($ids, 'commodity_item_file', $attachment);
								}
							}
						}
					}

					$inventory_parent_total = 0;
						//add log sync from store to crm
					$data_add_new = [];

					if($ids){
						array_push($data_add_new, ['woocommere_store_id'=>$store_id]);
						array_push($data_add_new, ['group_product_id'=>'']);
						array_push($data_add_new, array($ids));
						array_push($data_add_new, ['prices'=> $rate[$key]]);
						//create inventory 
						$data_inventory['warehouse_id'] = $omni_warehouse;
						$data_inventory['commodity_id'] = $ids;
						$quantities = $stock_quantity[$key] == null ? 0 : $stock_quantity[$key];
						$data_inventory['quantities'] = $quantities;
						$this->omnisales_add_inventory_manage($data_inventory, 1, false);
						if($type[$key] == 'variable'){
							if(count($attributes[$key]) > 0){
								foreach ($attributes[$key] as $k_attr => $attribute) {
										//get variation from store
									$vt_variation = $woocommerce->get('products/'.$id[$key].'/variations/'.$attribute);
									$hour = time();
									$vt_variation_sku = $vt_variation->sku == $sku_code[$key] ? $vt_variation->sku . "-" .$hour : $vt_variation->sku;

									if($vt_variation->sku == $sku_code[$key]){
										$woocommerce->post('products/'.$id[$key].'/variations/'.$vt_variation->id, 
											[
												'sku' => $vt_variation_sku
											]);
									}

									//init data variation
									$data_variation["commodity_code"] = $vt_variation_sku;
									$data_variation["description"] = $description[$key];
									$data_variation["commodity_barcode"] = $this->warehouse_model->generate_commodity_barcode();
									$data_variation["sku_code"] = $vt_variation_sku;
									$data_variation["sku_name"] = $sku_name[$key];
									$data_variation["commodity_type"] = '';
									$data_variation["unit_id"] = null;
									$data_variation["group_id"] = '';
									$data_variation["rate"] = $vt_variation->price;
									$data_variation["tax"] = '';
									$data_variation["profif_ratio"] = $profif_ratio;
									$data_variation["origin"] = '';
									$data_variation["style_id"] = '';
									$data_variation["model_id"] = '';
									$data_variation["size_id"] = '';
									$data_variation["color"] = '';
									$data_variation["guarantee"] = '';
									$data_variation["long_descriptions"] = $vt_variation->description;
									$data_variation["parent_id"] = $ids;
									$data_variation["images"] = $vt_variation->image;
									if(count($vt_variation->attributes) > 0){
										foreach ($vt_variation->attributes as $vt_variation_attribute) {
											$vt_variation_attribute_value[] = ['name' => $vt_variation_attribute->name, 'option' => $vt_variation_attribute->option];
										}
									}

									$data_variation["attributes"] = json_encode($vt_variation_attribute_value);
									//init value inventory for variation product
									$data_variation["omni_warehouse"] = $omni_warehouse;
									$data_variation["quantities"] = $vt_variation->stock_quantity == null ? 0 : $vt_variation->stock_quantity; 
									$inventory_parent_total += (int) $data_variation["quantities"];
									//add product variable
									$this->add_product_variable($data_variation);
								}
							}
						}

						if($inventory_parent_total > 0){
							//Goods receipt
							$data_update['inventory_number'] = (int) $inventory_parent_total;
							//update
							$this->db->where('warehouse_id', $omni_warehouse);
							$this->db->where('commodity_id', $ids);
							$this->db->update(db_prefix() . 'inventory_manage', $data_update);
							$this->update_inventory_product_variation_parent($store_id, $value, $inventory_parent_total);

						}
					}
					$log_product = [
						'name' => $description[$key],
						'regular_price' => $rate[$key],
						'short_description' => $long_description[$key],
						'sku' => $sku_code[$key],
						'chanel' => 'WooCommerce('.$this->get_name_store($store_id).')',
						'type' => "products_store_info_images",
					];        
					$this->db->insert(db_prefix().'omni_log_sync_woo', $log_product);
					//insert product in channel crm
					$this->add_product_channel_wcm($data_add_new);

					$items = [];
					$items['id'] = $id[$key];
					$items['sku'] = $this->warehouse_model->get_commodity($ids)->sku_code;
					if(!isset($update)){
						$update = [];
					}
					array_push($update, $items);
					$count++;
					if( $count % 100 == 0){
						array_push($product_update, $update);
						$update = [];
					}

				}else{
					$this->db->where('sku_code', $value);
					$item_ = $this->db->get(db_prefix() . 'items')->row();
					$purchase_price_update = $this->caculator_purchase_price($rate[$key]);
					$this->db->where('product_id', $item_->id);
					$this->db->update(db_prefix() . 'woocommere_store_detailt', 
						[
							"prices" => $rate[$key],              
						]);

					$this->db->where('sku_code', $value);
					$this->db->update(db_prefix() . 'items', 
						[
							"description" => $description[$key],              
							"long_descriptions" => $long_descriptions[$key],
							"long_description" => $long_description[$key],
							"commodity_code" => $commodity_code[$key],  
							"commodity_barcode" => $commodity_barcode[$key],
							"sku_code" => $value,
							"sku_name" => $sku_name[$key],
							"commodity_type" => "0",
							"unit_id" => $unit_id[$key],
							"group_id" => $group_id[$key],
							"rate" => $rate[$key],
							"tax" => $tax[$key],
							"profif_ratio" => $profif_ratio,
							"origin" => $origin[$key],
							"style_id" => $style_id[$key],
							"model_id" => $model_id[$key],
							"size_id" => $size_id[$key],
							"color" => $color[$key],
							"guarantee" => $guarantee[$key],
							"parent_attributes" => $parent_attributes[$key],
							"sub_group"=> "",
							"without_checking_warehouse"=> "0",
							"purchase_price" => $purchase_price_update
						]);

						//get all id image of item
					$images_items = $this->get_image_id($item_->id);

						//delete all image of ite,
					if(count($images_items) > 0){
						foreach ($images_items as $images_item) {
							$this->warehouse_model->delete_commodity_file($images_item['id']);
						}
					}

						// create image product sync from store to crm
					if(!empty($images)){
						foreach ($images[$key] as $image) {
							$url_to_image = $image->src;
							$my_save_dir = 'modules/warehouse/uploads/item_img/'.$item_->id.'/';
							$filename = basename($url_to_image);

							$filename = explode('?',$filename)[0];

							$complete_save_loc = $my_save_dir.$filename;

							_maybe_create_upload_path($my_save_dir);
							if(file_put_contents($complete_save_loc,file_get_contents($url_to_image, false, stream_context_create($arrContextOptions)))){

								$filetype = array(
									'jpg' => 'image/jpeg',
									'png' => 'image/png',
									'gif' => 'image/gif',
								);

								$attachment   = [];
								if(isset($filetype[pathinfo($image->src, PATHINFO_EXTENSION)])){
									$attachment[] = [
										'file_name' => $filename,
										'filetype'  => $filetype[pathinfo($image->src, PATHINFO_EXTENSION)],
									];
								}else{

									$f_type = explode("?",pathinfo($image->src, PATHINFO_EXTENSION));
									if(isset($filetype[$f_type[0]])){
										$attachment[] = [
											'file_name' => $filename,
											'filetype'  => $filetype[$f_type[0]],
										];
									}
								}

								$this->misc_model->add_attachment_to_database($item_->id, 'commodity_item_file', $attachment);
							}
						}
					}
					$inventory_parent_total = 0;
					if($type[$key] == 'variable'){
						if(count($attributes[$key]) > 0){
							foreach ($attributes[$key] as $k_attr => $attribute) {
									//get variation from store
								$vt_variation = $woocommerce->get('products/'.$id[$key].'/variations/'.$attribute);


									//init data variation
								$data_variation["commodity_code"] = $commodity_code[$key];
								$data_variation["description"] = $description[$key];
								$data_variation["sku_code"] = $vt_variation->sku;
								$data_variation["sku_name"] = $sku_name[$key];
								$data_variation["commodity_type"] = '';
								$data_variation["unit_id"] = null;
								$data_variation["group_id"] = '';
								$data_variation["rate"] = $vt_variation->price;
								$data_variation["tax"] = '';
								$data_variation["profif_ratio"] = $profif_ratio;
								$data_variation["origin"] = '';
								$data_variation["style_id"] = '';
								$data_variation["model_id"] = '';
								$data_variation["size_id"] = '';
								$data_variation["color"] = '';
								$data_variation["guarantee"] = '';
								$data_variation["long_descriptions"] = $vt_variation->description;
								$data_variation["parent_id"] = $item_->id;
								$data_variation["images"] = $vt_variation->image;

								if(count($vt_variation->attributes) > 0){
									foreach ($vt_variation->attributes as $vt_variation_attribute) {
										$vt_variation_attribute_value[] = ['name' => $vt_variation_attribute->name, 'option' => $vt_variation_attribute->option];
									}
								}

								$data_variation["attributes"] = isset($vt_variation_attribute_value) ? json_encode($vt_variation_attribute_value) : "";
									//init value inventory for variation product
								$data_variation["omni_warehouse"] = $omni_warehouse;
								$data_variation["quantities"] = $vt_variation->stock_quantity == null ? 0 : $vt_variation->stock_quantity; 
								$inventory_parent_total += (int) $data_variation["quantities"];

									//update product variable
								$this->update_product_variable($data_variation);
							}
						}
					}

					if($inventory_parent_total > 0){
						//Goods receipt
						$this->db->where('commodity_id', $item_->id);
						$results_old = $this->db->get('tblinventory_manage')->result_array();

						if(count($results_old) > 0){
							if(count($results_old) == 1){

								$this->db->where('warehouse_id', $results_old[0]['warehouse_id']);
								$this->db->where('commodity_id', $item_->id);
								$inventory_manage = $this->db->get('tblinventory_manage')->row();
								$data_update['inventory_number'] = (string) $inventory_parent_total;
								//update stock to store woo
								$this->update_inventory_product_variation_parent($store_id, $item_->sku_code, $inventory_parent_total);
								$this->db->where('id', $inventory_manage->id);
								return $this->db->update(db_prefix() . 'inventory_manage', $data_update);

							}else{
								$current_inventory_quantities = 0;
								//data old inventory when there are many stocks (" sum inventory_number of all stocks ")
								foreach ($results_old as $key_results_old => $value_results_old) {
									$current_inventory_quantities += (int) $value_results_old['inventory_number']; 
								}

								if($current_inventory_quantities > $inventory_parent_total){
									$number_compare = (int) $current_inventory_quantities -  (int) $inventory_parent_total;
									foreach ($results_old as $key_results_old => $value_results_old) {
										if($number_compare > $value_results_old['inventory_number']){
											$this->db->where('warehouse_id', $value_results_old['warehouse_id']);
											$this->db->where('commodity_id', $item_->id);
											$this->db->update(db_prefix() . 'inventory_manage', ['inventory_number' => 0]);
											$number_compare = $number_compare - $value_results_old['inventory_number'];
										}else{
											$number_result = $value_results_old['inventory_number'] - $number_compare;
											$this->db->where('warehouse_id', $value_results_old['warehouse_id']);
											$this->db->where('commodity_id', $item_->id);
											$this->db->update(db_prefix() . 'inventory_manage', ['inventory_number' => $number_result]);
											break;
										}
									}
								}else if($current_inventory_quantities < $inventory_parent_total){
									$number_compare = (int) $inventory_parent_total -  (int) $current_inventory_quantities;

									foreach ($results_old as $key_results_old => $value_results_old) {
										$number_result = (int) $number_compare + (int) $value_results_old['inventory_number'];
										$this->db->where('warehouse_id', $value_results_old['warehouse_id']);
										$this->db->where('commodity_id', $item_->id);
										$this->db->update(db_prefix() . 'inventory_manage', ['inventory_number' => $number_result]);
										break;
									}
								}
								$this->db->where('commodity_id', $item_->id);
								$results_new_update = $this->db->get('tblinventory_manage')->result_array();
								$total_results_update = 0;
								foreach ($results_new_update as $key_results_new_update => $value_results_new_update) {
									$total_results_update += (int) $value_results_new_update['inventory_number']; 
								}
								//update stock to store woo
								$this->update_inventory_product_variation_parent($store_id, $item_->sku_code, $total_results_update);
							}
						}

					}

					//------------------start update inventory-------------------------------------
					$this->db->where('commodity_id', $item_->id);
					$check_inventory_exits = $this->db->get(db_prefix().'inventory_manage')->result_array();
					//value inventory 
					$value_inventory_ = $stock_quantity[$key] == null || $stock_quantity[$key] == "" || $stock_quantity[$key] == 0 ? 0 : $stock_quantity[$key];
					if(count($check_inventory_exits) > 0){
						//init inventory total
						$inventory_total = 0;
						foreach ($check_inventory_exits as $key_check_inventory_exit => $value_check_inventory_exit) {
							//sum inventory total
							$inventory_total += $value_check_inventory_exit['inventory_number'];
						}
						$check_inventory_exits_one = $this->db->get(db_prefix().'inventory_manage')->result_array();

						foreach ($check_inventory_exits_one as $key_check_inventory_exit_one => $value_check_inventory_exit_one) {
							$data_inventory['warehouse_id'] = $value_check_inventory_exit_one['warehouse_id'];
							$data_inventory['commodity_id'] = $item_->id;
							$data_inventory['inventory_total'] = $inventory_total;
							$data_inventory['quantities'] = $value_inventory_;
							$rs = $this->omnisales_add_inventory_manage($data_inventory, 2, false);
							if($rs){
								break;
							}
						}
					}else{
							//create new if haven't warehouse
						$data_inventory['warehouse_id'] = $omni_warehouse;
						$data_inventory['commodity_id'] = $item_->id;
						$data_inventory['quantities'] = $value_inventory_;
						$data_inventory['inventory_total'] = 0;
						$this->omnisales_add_inventory_manage($data_inventory, 2, true);
					}
					//------------------end update inventory-------------------------------------
				}
			}
			if(count($products_store) < $per_page){
				break;
			}
		}

		if(isset($update) && count($update) > 0){
			array_push($product_update, $update);
		}
		if(count($product_update) > 0){

			foreach ($product_update as $key => $value) {
				$data_cus = [
					'update' => $value
				];
				$woocommerce->post('products/batch', $data_cus);
				$this->exit_type_variation($store_id, 2);
			}
			return true;
		}

	}
		/**
		 * test connect 
		 * @param   $data 
		 * @return        
		 */
		public function test_connect($data)
		{
			$consumer_key = $data['consumer_key'];
			$consumer_secret = $data['consumer_secret'];
			$url = $data['url'];
			$woocommerce = new Client(
				$url, 
				$consumer_key, 
				$consumer_secret,
				[ 
					'wp_api' => true,
					'version' => 'wc/v3',
					'query_string_auth' => true,
					'timeout' => (20*60*1000),
				]
			);

			try {
				if($woocommerce->get('')){
					return true;
				}
			} catch (Exception $e) {
				return false;
			}
			

		}


		/**
	 * sync from the store to the system
	 * @param  int $store_id          
	 */
		public function sync_products_from_info_woo($store_id){
			$this->load->model('warehouse/warehouse_model');
			$this->load->model('misc_model');
			$woocommerce = $this->init_connect_woocommerce($store_id);
			$per_page = 100;
			$products_store = [];
			$data_new = [];
			$data_new_s = [];
			$product_update = [];
			$profif_ratio = get_option('warehouse_selling_price_rule_profif_ratio');
			for($page = 1; $page <= 100; $page++ ){
				$offset = ($page - 1) * $per_page;
				$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

				$products_store = array_merge($products_store, $list_products);

				if(count($list_products) < $per_page){
					break;
				}
			}
			$commodity_code = [];
			$description = [];
			$commodity_barcode = [];
			$sku_code = [];
			$sku_name = [];
			$long_description = [];
			$commodity_type = [];
			$unit_id = [];
			$group_id = [];
			$purchase_price = [];
			$rate = [];
			$tax = [];
			$origin = [];
			$style_id = [];
			$model_id = [];
			$size_id = [];
			$color = [];
			$guarantee = [];
			$long_descriptions = [];
			$id = [];
			$images = [];
			$arry_sku_new = [];
			$count = 0;
			foreach ($products_store as $key => $products) {
				array_push($id, $products->id);
				array_push($commodity_code, $products->sku);
				array_push($description, $products->name);
				array_push($commodity_barcode, $this->warehouse_model->generate_commodity_barcode());
				array_push($sku_code, $products->sku);
				array_push($sku_name, '');
				array_push($long_description, $products->short_description);
				array_push($commodity_type, '');
				array_push($unit_id, 1);
				array_push($group_id, '');
				array_push($rate, $products->price);
				array_push($tax, '');
				array_push($origin, '');
				array_push($style_id, '');
				array_push($model_id, '');
				array_push($size_id, '');
				array_push($color, '');
				array_push($guarantee, '');
				array_push($long_descriptions, $products->description);      
				array_push($images, $products->images);
			}

			$products_all = $this->get_product();
			$array_sku_code = [];
			foreach ($products_all as $product) {
				array_push($array_sku_code, $product['sku_code']);
			}

			foreach ($sku_code as $key => $value) {

				if(!in_array($value, $array_sku_code)){
					$data = [
						"commodity_code" => $commodity_code[$key],
						"description" => $description[$key],
						"commodity_barcode" => $commodity_barcode[$key],
						"sku_code" => $value,
						"sku_name" => $sku_name[$key],
						"long_description" => $long_description[$key],
						"commodity_type" => $commodity_type[$key],
						"unit_id" => $unit_id[$key],
						"purchase_price" => 0,
						"group_id" => $group_id[$key],
						"rate" => $rate[$key],
						"tax" => $tax[$key],
						"profif_ratio" => $profif_ratio,
						"origin" => $origin[$key],
						"style_id" => $style_id[$key],
						"model_id" => $model_id[$key],
						"size_id" => $size_id[$key],
						"color" => $color[$key],
						"guarantee" => $guarantee[$key],
						"long_descriptions" => $long_descriptions[$key]
					];
					$ids = $this->warehouse_model->add_commodity_one_item($data);

					$data_add_new = [];
					if($ids){
						array_push($data_add_new, ['woocommere_store_id'=>$store_id]);
						array_push($data_add_new, ['group_product_id'=>'']);
						array_push($data_add_new, array($ids));
						array_push($data_add_new, ['prices'=>'']);
					}


					$data = [
						"commodity_code" => $commodity_code[$key],
						"description" => $description[$key],
						"commodity_barcode" => $commodity_barcode[$key],
						"sku_code" => $value,
						"sku_name" => $sku_name[$key],
						"long_description" => $long_description[$key],
						"commodity_type" => $commodity_type[$key],
						"unit_id" => $unit_id[$key],
						"group_id" => $group_id[$key],
						"rate" => $rate[$key],
						"tax" => $tax[$key],
						"origin" => $origin[$key],
						"style_id" => $style_id[$key],
						"model_id" => $model_id[$key],
						"size_id" => $size_id[$key],
						"color" => $color[$key],
						"guarantee" => $guarantee[$key],
						"long_descriptions" => $long_descriptions[$key]
					];

					$log_product = [
						'name' => $description[$key],
						'regular_price' => $rate[$key],
						'short_description' => $long_description[$key],
						'sku' => $value,
						'chanel' => 'WooCommerce('.$this->get_name_store($store_id).')',
						'type' => "products_store_info",
					];        
					$this->db->insert(db_prefix().'omni_log_sync_woo', $log_product);
					$this->add_product_channel_wcm($data_add_new);
					$items = [];
					$items['id'] = $id[$key];
					$items['sku'] = $value;

					array_push($product_update, $items);
					$count++;
				}else{
					$this->db->where('sku_code', $value);
					$item_ = $this->db->get(db_prefix() . 'items')->row();

					$this->db->where('product_id', $item_->id);
					$this->db->update(db_prefix() . 'woocommere_store_detailt', 
						[
							"prices" => $rate[$key],              
						]);

					$this->db->where('sku_code', $value);
					$this->db->update(db_prefix() . 'items', 
						[
							"description" => $description[$key],              
							"long_descriptions" => $long_descriptions[$key],
							"long_description" => $long_description[$key]              
						]);
				}
			}

			if(count($product_update) > 0){
				$data_update = [];
				foreach ($product_update as $key => $value) {
					$data_update[] = $value;
				}
				$data_cus = [
					'update' => $data_update
				];
				$woocommerce->post('products/batch', $data_cus);
				$this->exit_type_variation($store_id, 2);
			}

			return true;
		}

		public function get_name_store($store_id){
			$this->db->where('id', $store_id);
			if($this->db->get(db_prefix().'omni_master_channel_woocommere')->row()->name_channel){
				$this->db->where('id', $store_id);
				return $this->db->get(db_prefix().'omni_master_channel_woocommere')->row()->name_channel;
			}else{
				return "";
			}
		}


		/**
 * process price synchronization
 * @param  int $store_id 
 * @param  array $arr_detail 
 * @return bool           
 */
		public function process_price_synchronization($store_id, $arr_detail = null){
			$products_store = $this->get_product_parent_id();

			$items = [];

			if(isset($arr_detail)){
				foreach ($arr_detail  as $key => $product) {
					$this->db->where('id',$product);
					array_push($items, $this->db->get(db_prefix().'items')->row());
				}
			}else{
				if(!empty($products_store)){
					foreach ($products_store  as $key => $product) {
						if(!is_null($this->get_product($product['id']))){
							$this->db->where('id',$product['id']);
							array_push($items, $this->db->get(db_prefix().'items')->row());
						}
					}
				}
			}
			$woocommerce = $this->init_connect_woocommerce($store_id);

			$per_page = 100;
			$products_store = [];
			for($page = 1; $page <= 100; $page++ ){
				$offset = ($page - 1) * $per_page;
				$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

				$products_store = array_merge($products_store, $list_products);

				if(count($list_products) < $per_page){
					break;
				}
			}
			$data_create = [];
			$data_create_master = [];
			foreach ($products_store as $key => $value) {

				if($value->sku != ''){
					foreach ($items as $item) {
						if($item->sku_code == $value->sku){

							$stock_quantity = $this->get_total_inventory_commodity($item->id);
							$stock_quantity = $stock_quantity->inventory_number;
							$images = [];
							if($this->get_all_image_file_name($item->id)){
								$file_name = $this->omni_sales_model->get_all_image_file_name($item->id);
							}
							if(isset($file_name)){
								foreach ($file_name as $k => $name) {
									array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$item->id.'/'.$name['file_name'])));
								}
							}
							if(is_null($stock_quantity)){
								$stock_quantity = 0;
							}
							$date = date('Y-m-d');
							$discount =  $this->check_discount($item->id, $date, 3, $store_id);
							$price_discount = 0;
							$date_on_sale_from = null;
							$date_on_sale_to = null;
							if(!is_null($discount)){
								if($discount->formal == 1){
									$price_discount = $item->rate - (($item->rate * $discount->discount)/100);
								}else{
									$price_discount = $item->rate - $discount->discount;
								}
								$date_on_sale_from = $discount->start_time;
								$date_on_sale_to = $discount->end_time;
							}else{
								$price_discount = "";
							}
							$regular_price = $this->get_price_store($item->id, $store_id);

							$regular_price_prices = '';
							if(!isset($regular_price->prices)){
								$regular_price_prices = 0;
							}else{
								$regular_price_prices = $regular_price->prices;
							}
							$data = [
								'id' => $value->id,
								'name' => $item->description,
								'regular_price' => $regular_price_prices,
								'price' => $regular_price_prices,
								'sale_price' => strval($price_discount),
								'date_on_sale_from' => $date_on_sale_from,
								'date_on_sale_to' => $date_on_sale_to,
							];
							if(is_null($value->stock_quantity)){
								$value->stock_quantity = 0;
							}
							$log_price = [
								'name' => $item->description,
								'regular_price' => $item->rate,
								'sale_price' => strval($price_discount),
								'date_on_sale_from' => $date_on_sale_from,
								'date_on_sale_to' => $date_on_sale_to,
								'short_description' => $item->description,
								'chanel' => 'WooCommerce('.$this->get_name_store($store_id).')',
								"sku" => $item->sku_code,
								"type" => "price",
							];
							array_push($data_create,$data);
							if(count($data_create) == $this->amount){
								array_push($data_create_master,$data_create);
								$data_create = [];
							}
							$this->db->insert(db_prefix().'omni_log_sync_woo', $log_price);
						}
					}   
				}
			}
			if(count($data_create) < 10){
				array_push($data_create_master,$data_create);
			}

			if($data_create_master > 0){
				foreach ($data_create_master as  $data__) {
					$data_cus = [
						'update' => $data__
					];
					$woocommerce->post('products/batch', $data_cus);
					$this->exit_type_variation($store_id, 2);
				}
			}

			return true;
		}
 /**
 * process price synchronization
 * @param  int $store_id 
 * @return bool           
 */
 public function process_price_synchronization_update_product($store_id, $price, $product_id){
 	$product = $this->get_product($product_id);

 	$woocommerce = $this->init_connect_woocommerce($store_id);

 	$per_page = 100;
 	$products_store = [];
 	for($page = 1; $page <= 100; $page++ ){
 		$offset = ($page - 1) * $per_page;
 		$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

 		$products_store = array_merge($products_store, $list_products);

 		if(count($list_products) < $per_page){
 			break;
 		}
 	}
 	$arr_product_store = [];

 	foreach ($products_store as $key => $value) {

 		if($value->sku != ''){
 			if($product->sku_code == $value->sku){
 				$data = [
 					'regular_price' => $price,
 				];

 				$log_price = [
 					'name' => $product->description,
 					'regular_price' => $price,
 					'chanel' => 'WooCommerce('.$this->get_name_store($store_id).')',
 					"type" => "price",
 				];
 				$this->db->insert(db_prefix().'omni_log_sync_woo', $log_price);
 				$rs = $woocommerce->post('products/'.$value->id, $data);
 			}
 		}
 	}
 	return true;
 }
 public function add_log_trade_discount($user_id, $order_number,$channel_id , $total_order, $discount_price, $tax, $total_after, $voucher){
 	$this->load->model('currencies_model');
 	$base_currency = $this->currencies_model->get_base_currency();
 	$currency_name = '';
 	if(isset($base_currency)){
 		$currency_name = $base_currency->name;
 	}
 	$data_trade_discount = $this->get_discount_list($channel_id, $user_id);
 	$name_trade_discount = '';
 	foreach ($data_trade_discount as $value) {
 		$name_trade_discount .= '['.$value['name_trade_discount'];
 		if((int)$value['formal'] == 1){
 			$name_trade_discount .= ' (-'.$value['discount'].'%)';
 		}
 		if((int)$value['formal'] == 2){
 			$name_trade_discount .= ' (-'.app_format_money($value['discount'],$currency_name).')';
 		} 
 		$name_trade_discount .=']<br>';
 	}

 	if($voucher != ''){
 		$data_voucher = $this->get_discount_list($channel_id, $user_id, $voucher);
 		$name_trade_discount .= '['.$data_voucher->name_trade_discount;
 		if((int)$data_voucher->formal == 1){
 			$name_trade_discount .= ' (-'.$data_voucher->discount.'%)';
 		}
 		if((int)$data_voucher->formal == 2){
 			$name_trade_discount .= ' (-'.app_format_money($data_voucher->discount,$currency_name).')';
 		} 
 		$name_trade_discount .=']';
 	}
 	$data['name_discount'] = $name_trade_discount;
 	$data['client'] = $user_id;
 	$data['order_number'] = $order_number;
 	$data['voucher_coupon'] = $voucher;
 	$data['total_order'] = app_format_money($total_order,$currency_name);
 	$data['discount'] = app_format_money($discount_price,$currency_name);
 	$data['tax'] = app_format_money($tax,$currency_name);
 	$data['total_after'] = app_format_money($total_after,$currency_name);
 	$data['date_apply'] = date('Y-m-d H:i:s');
 	$this->db->insert(db_prefix().'omni_log_discount', $data);
 }

 public function add_setting_auto_sync_store($data){
 	if(isset($data['sync_omni_sales_products'])){
 		$data['sync_omni_sales_products'] = 1;
 	}else{$data['sync_omni_sales_products'] = 0;}

 	if(isset($data['sync_omni_sales_inventorys'])){
 		$data['sync_omni_sales_inventorys'] = 1;
 	}else{$data['sync_omni_sales_inventorys'] = 0;}

 	if(isset($data['price_crm_woo'])){
 		$data['price_crm_woo'] = 1;
 	}else{$data['price_crm_woo'] = 0;}

 	if(isset($data['sync_omni_sales_description'])){
 		$data['sync_omni_sales_description'] = 1;
 	}else{$data['sync_omni_sales_description'] = 0;}

 	if(isset($data['sync_omni_sales_images'])){
 		$data['sync_omni_sales_images'] = 1;
 	}else{$data['sync_omni_sales_images'] = 0;}

 	if(isset($data['sync_omni_sales_orders'])){
 		$data['sync_omni_sales_orders'] = 1;
 	}else{$data['sync_omni_sales_orders'] = 0;}

 	if(isset($data['product_info_enable_disable'])){
 		$data['product_info_enable_disable'] = 1;
 	}else{$data['product_info_enable_disable'] = 0;}

 	if(isset($data['product_info_image_enable_disable'])){
 		$data['product_info_image_enable_disable'] = 1;
 	}else{$data['product_info_image_enable_disable'] = 0;}

 	$this->db->insert('omni_setting_woo_store', $data);
 	$insert_id = $this->db->insert_id();
 	return $insert_id;
 }

 public function get_setting_auto_sync_store_exit($id = ''){
 	$omni_setting_woo_store = $this->db->get('omni_setting_woo_store')->result_array();
 	$arr = [];
 	foreach ($omni_setting_woo_store as $key => $value) {
 		$arr[] = $value['store'];
 	}
 	return $arr;
 }

 public function update_setting_auto_sync_store($data, $id){
 	if(isset($data['sync_omni_sales_products'])){
 		$data['sync_omni_sales_products'] = 1;
 	}else{$data['sync_omni_sales_products'] = 0;}

 	if(isset($data['sync_omni_sales_inventorys'])){
 		$data['sync_omni_sales_inventorys'] = 1;
 	}else{$data['sync_omni_sales_inventorys'] = 0;}

 	if(isset($data['price_crm_woo'])){
 		$data['price_crm_woo'] = 1;
 	}else{$data['price_crm_woo'] = 0;}

 	if(isset($data['sync_omni_sales_description'])){
 		$data['sync_omni_sales_description'] = 1;
 	}else{$data['sync_omni_sales_description'] = 0;}

 	if(isset($data['sync_omni_sales_images'])){
 		$data['sync_omni_sales_images'] = 1;
 	}else{$data['sync_omni_sales_images'] = 0;}

 	if(isset($data['sync_omni_sales_orders'])){
 		$data['sync_omni_sales_orders'] = 1;
 	}else{$data['sync_omni_sales_orders'] = 0;}

 	if(isset($data['product_info_enable_disable'])){
 		$data['product_info_enable_disable'] = 1;
 	}else{$data['product_info_enable_disable'] = 0;}

 	if(isset($data['product_info_image_enable_disable'])){
 		$data['product_info_image_enable_disable'] = 1;
 	}else{$data['product_info_image_enable_disable'] = 0;}


 	$this->db->where('id', $id);
 	$this->db->update(db_prefix() . 'omni_setting_woo_store', $data);
 	if ($this->db->affected_rows() > 0) {
 		return true;
 	}
 	return false;
 }
 public function delete_sync_auto_store($id){
 	$this->db->where('id',$id);
 	$this->db->delete(db_prefix().'omni_setting_woo_store');
 	if ($this->db->affected_rows() > 0) {           
 		return true;
 	}
 	return false;
 }
		/**
		 * get setting auto sync store
		 * @param  string $store 
		 * @return object or array       
		 */
		public function get_setting_auto_sync_store($store = ''){
			if($store == ''){
				$this->db->where('id',$store);
				return $omni_setting_woo_store = $this->db->get('omni_setting_woo_store')->row();
			}
			return $omni_setting_woo_store = $this->db->get('omni_setting_woo_store')->result_array();
		}


					/**
		 * check format date ymd
		 * @param  date $date 
		 * @return boolean       
		 */
					public function check_format_date_ymd($date) {
						if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
							return true;
						} else {
							return false;
						}
					}

			/**
		 * format date
		 * @param  date $date     
		 * @return date           
		 */
			public function format_date($date){
				if(!$this->check_format_date_ymd($date)){
					$date = to_sql_date($date);
				}
				return $date;
			}            

/**
 * format date time
 * @param  date $date     
 * @return date           
 */
public function format_date_time($date){
	if(!$this->check_format_date($date)){
		$date = to_sql_date($date, true);
	}
	return $date;
}

		/**
	 * add goods delivery
	 * @param array  $data
	 * @param boolean $id
	 * return boolean
	 */
		public function add_goods_delivery_with_warehouse($data) {
			$data['goods_delivery_code'] = $this->create_goods_delivery_code();
			$data['date_c'] = date('Y-m-d'); 
			$data['date_add'] = date('Y-m-d'); 

			$data['total_money']  = reformat_currency_j($data['total_money']);
			$data['total_discount'] = reformat_currency_j($data['total_discount']);
			$data['after_discount'] = reformat_currency_j($data['after_discount']);

			$data['addedfrom'] = get_staff_user_id();

			$this->db->insert(db_prefix() . 'goods_delivery', $data);
			$insert_id = $this->db->insert_id();

			if(isset($hot_purchase)){
				$goods_delivery_detail = json_decode($hot_purchase);

				$es_detail = [];
				$row = [];
				$rq_val = [];
				$header = [];

				$header[] = 'commodity_code';
				$header[] = 'warehouse_id';
				$header[] = 'available_quantity';
				$header[] = 'unit_id';
				$header[] = 'quantities';
				$header[] = 'unit_price';
				$header[] = 'tax_id';
				$header[] = 'total_money';
				$header[] = 'discount';
				$header[] = 'discount_money';
				$header[] = 'total_after_discount';
				$header[] = 'guarantee_period';
				$header[] = 'note';



				foreach ($goods_delivery_detail as $key => $value) {

					if($value[0] != ''){
						if($value[3] != ''){
							$value[3] = $value[3];

						}else{
							$value[3] = $this->get_unitid_from_commodity_id($value[0]);
						}
						if($value[11] != ''){
							if(!$this->check_format_date($value[11])){
								$value[11] = to_sql_date($value[11]);
							}else{
								$value[11] = $value[11];
							}
						}else{
							$get_warranty = $this->get_warranty_from_commodity_id($value[0]);

							if(!$this->check_format_date($get_warranty)){
								$value[11] = to_sql_date($get_warranty);
							}else{
								$value[11] = $get_warranty;
							}
						}         

						$es_detail[] = array_combine($header, $value);
					}
				}
			}


			if (isset($insert_id)) {
				foreach($es_detail as $key => $rqd){
					$es_detail[$key]['goods_delivery_id'] = $insert_id;
				}
				$this->db->insert_batch(db_prefix().'goods_delivery_detail',$es_detail);

				$data_log = [];
				$data_log['rel_id'] = $insert_id;
				$data_log['rel_type'] = 'stock_export';
				$data_log['staffid'] = get_staff_user_id();
				$data_log['date'] = date('Y-m-d H:i:s');
				$data_log['note'] = "stock_export";

				$this->add_activity_log($data_log);
				$this->update_inventory_setting(['next_inventory_delivery_mumber' =>  get_warehouse_option('next_inventory_delivery_mumber')+1]);
			}
		}


		public function update_stock(){
			$data_update['approval'] = $status;
			$this->db->where('id', $rel_id);
			$this->db->update(db_prefix() . 'goods_delivery', $data_update);

			$goods_delivery_detail = $this->get_goods_delivery_detail($rel_id);
			foreach ($goods_delivery_detail as $goods_delivery_detail_value) {
				$this->add_inventory_manage($goods_delivery_detail_value, 2);
			}
		}

	/**
	 * add inventory manage
	 * @param array $data
	 * @param string $status
	 */
	public function add_inventory_manage($data, $status) {
		$this->db->where('warehouse_id', $data['warehouse_id']);
		$this->db->where('commodity_id', $data['commodity_code']);
		$this->db->order_by('id', 'ASC');
		$result = $this->db->get('tblinventory_manage')->result_array();

		$temp_quantities = $data['quantities'];

		$expiry_date = '';
		$lot_number = '';
		foreach ($result as $result_value) {
			if (($result_value['inventory_number'] != 0) && ($temp_quantities != 0)) {

				if ($temp_quantities >= $result_value['inventory_number']) {
					$temp_quantities = (float) $temp_quantities - (float) $result_value['inventory_number'];

						//log lot number
					if(($result_value['lot_number'] != null) && ($result_value['lot_number'] != '') ){
						if(strlen($lot_number) != 0){
							$lot_number .=','.$result_value['lot_number'].','.$result_value['inventory_number'];
						}else{
							$lot_number .= $result_value['lot_number'].','.$result_value['inventory_number'];
						}
					}

						//log expiry date
					if(($result_value['expiry_date'] != null) && ($result_value['expiry_date'] != '') ){
						if(strlen($expiry_date) != 0){
							$expiry_date .=','.$result_value['expiry_date'].','.$result_value['inventory_number'];
						}else{
							$expiry_date .= $result_value['expiry_date'].','.$result_value['inventory_number'];
						}
					}

						//update inventory
					$this->db->where('id', $result_value['id']);
					$this->db->update(db_prefix() . 'inventory_manage', [
						'inventory_number' => 0,
					]);

				} else {

						//log lot number
					if(($result_value['lot_number'] != null) && ($result_value['lot_number'] != '') ){
						if(strlen($lot_number) != 0){
							$lot_number .=','.$result_value['lot_number'].','.$temp_quantities;
						}else{
							$lot_number .= $result_value['lot_number'].','.$temp_quantities;
						}
					}

						//log expiry date
					if(($result_value['expiry_date'] != null) && ($result_value['expiry_date'] != '') ){
						if(strlen($expiry_date) != 0){
							$expiry_date .=','.$result_value['expiry_date'].','.$temp_quantities;
						}else{
							$expiry_date .= $result_value['expiry_date'].','.$temp_quantities;
						}
					}


						//update inventory
					$this->db->where('id', $result_value['id']);
					$this->db->update(db_prefix() . 'inventory_manage', [
						'inventory_number' => (float) $result_value['inventory_number'] - (float) $temp_quantities,
					]);

					$temp_quantities = 0;

				}

			}

		}

			//update good delivery detail
		$this->db->where('id', $data['id']);
		$this->db->update(db_prefix() . 'goods_delivery_detail', [
			'expiry_date' => $expiry_date,
			'lot_number' => $lot_number,
		]);

			//goods transaction detail log
		$data['expiry_date'] = $expiry_date;
		$data['lot_number'] = $lot_number;
		$this->add_goods_transaction_detail($data, 2);
		return true;
	}
	/**
	 * get contact by email
	 * @param string $email 
	 * @return  object      
	 */
	public function get_contact_by_email($email){
		$this->db->where('email', $email);
		return $this->db->get(db_prefix().'contacts')->row();
	}
	/**
	 * create payment
	 */
	public function create_payment(){
		$data_payment["invoiceid"]=$id;
		$data_payment["amount"]=$data_cart['total'];
		$data_payment["date"]= _d(date('Y-m-d'));
		$data_payment["paymentmode"]=1;
		$data_payment["do_not_redirect"]='off';
		$data_payment["transactionid"]=$data_cart['order_number'];
		$data_payment["note"]='';
		$this->payments_model->add($data_payment); 
	}
	/**
	 * send mail order
	 * @param  integer $order_id 
	 * @param  integer $user_id  
	 * @return void           
	 */
	public function send_mail_order($order_id, $user_id){    
		$this->load->model('currencies_model');
		$base_currency = $this->currencies_model->get_base_currency();
		$currency_name = '';
		if(isset($base_currency)){
			$currency_name = $base_currency->name;
		}

		$data_client = $this->clients_model->get($user_id);

		$contact_id =  get_primary_contact_user_id($user_id);
		$data_contact = $this->clients_model->get_contact($contact_id);
		$full_name_customer = $data_client->company;
		$email_customer = '';
		if($data_contact){
			$data_cart = $this->get_cart($order_id);
			$data_cart_detail =$this->get_cart_detailt_by_master($order_id);


			$html = '';
			$html .= '<style type="text/css">';
			$html .= 'span.cls_002{font-family:monospace,Arial,serif;font-size:8.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'div.cls_002{font-family:monospace,Arial,serif;font-size:8.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'span.cls_003{font-family:monospace,Arial,serif;font-size:18.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'div.cls_003{font-family:monospace,Arial,serif;font-size:18.1px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'span.cls_004{font-family:monospace,Arial,serif;font-size:15.6px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'div.cls_004{font-family:monospace,Arial,serif;font-size:15.6px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'span.cls_005{font-family:monospace,Arial,serif;font-size:9.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'div.cls_005{font-family:monospace,Arial,serif;font-size:9.0px;color:rgb(0,0,0);font-weight:normal;font-style:normal;text-decoration: none}';
			$html .= 'span.cls_006{font-family:monospace,Arial,serif;font-size:15.6px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none}';
			$html .= 'div.cls_006{font-family:monospace,Arial,serif;font-size:15.6px;color:rgb(0,0,0);font-weight:bold;font-style:normal;text-decoration: none}';
			$html .= '</style>';
			$html .= '<div>';
			$html .= '<div class="cls_002"><span class="cls_002">'._d(date('Y-m-d')).'</span></div>';
			$html .= '<div class="cls_002"><span class="cls_002">#'.$order_id.'</span></div><br>';

			$header = '';
			$data_header_option = get_option('bill_header_pos');
			if($data_header_option){
				$header = $data_header_option;      
			}
			$html .= $header;
			$html .= '<br>';


			$html .= '<div class="cls_004"><span class="cls_004">'._l('date').': '._d($data_cart->datecreator).'</span></div>';
			$html .= '<div class="cls_004"><span class="cls_004">No: '.$data_cart->order_number.'</span></div>';
			$html .= '<div class="cls_004"><span class="cls_004">'._l('sales_associate').': '.get_staff_full_name($data_cart->seller).'</span></div>';
			$html .= '<div class="cls_004"><span class="cls_004">'._l('customer').': '.$full_name_customer.'</span></div><br>';
			$count = 1;
			$tax_total_array = [];
			foreach ($data_cart_detail as $key => $item) {
				$list_tax = json_decode($item['tax']);
				$tax_name = '';
				foreach ($list_tax as $tax_item) {
					$tax_name .= $tax_item->name.' ('.$tax_item->rate.'%)<br>'; 
					$array_tax_index = $tax_item->rate.'_'.$tax_item->id;
					if(isset($tax_total_array[$array_tax_index])){
						$old_value_tax = $tax_total_array[$array_tax_index]['value'];
						$tax_total_array[$array_tax_index] = ['value' => ($old_value_tax + $tax_item->value), 'name' => $tax_item->name.' ('.$tax_item->rate.'%)'];
					}
					else{
						$tax_total_array[$array_tax_index] = ['value' => $tax_item->value, 'name' => $tax_item->name.' ('.$tax_item->rate.'%)'];
					}
				}
				$line_total = round($item['quantity'] * $item['prices'], 2);
				$html .= '
				<table class="cls_004">
				<tr>
				<td colspan="4"><strong><em>'.$count.'. '.$item['product_name'].' - '.$item['sku'].'</em></strong></td>
				</tr>
				<tr>
				<td class="cls_004">'.$item['quantity'].' x '.app_format_money($item['prices'],$currency_name).'</td>
				<td class="cls_004">'.$tax_name.'</td>
				<td></td>
				<td class="cls_004">'.app_format_money($line_total,$currency_name).'</td>
				</tr>
				</table><br>';
				$count ++;
			}
			$html .= '<br><hr><table class="width-100">
			<tr>
			<td class="cls_004"><span class="cls_004">'._l('sub_total').': </span></td>
			<td class="cls_004">   '.app_format_money($data_cart->sub_total,$currency_name).'</td>
			</tr>';
			if($data_cart->discount > 0){
				$html .= '<tr>';
				$html .= '<td class="cls_004"><span class="cls_004">'._l('discount').': </span></td>';
				$html .= '<td class="cls_004">   '.app_format_money($data_cart->discount,$currency_name).'</td>';
				$html .= '</tr>';
			}

			foreach ($tax_total_array as $tax_item_row) {
				$html .= '<tr>';
				$html .= '<td class="cls_004"><span class="cls_004">'.$tax_item_row['name'].': </span>';
				$html .= '</td>';
				$html .= '<td class="cls_004">';
				$html .= '   '.app_format_money($tax_item_row['value'],$currency_name);
				$html .= '</td>';
				$html .= '</tr>';
			}

			$html .= '
			<tr>
			<td class="cls_004"><span class="cls_004">'._l('total').': </span></td>
			<td class="cls_004">   '.app_format_money($data_cart->total,$currency_name).'</td>
			</tr>
			</table>';

			$list_payment = [];
			$name_payment = '';
			if($data_cart->allowed_payment_modes!=''){
				$list_payment = explode(',', $data_cart->allowed_payment_modes);
				$this->load->model('payment_modes_model');
				foreach ($list_payment  as $key => $item) {
					$data_payment = $this->payment_modes_model->get($item);
					$name = $data_payment->name;
					if($name !=''){
						$name_payment .= $name.', ';              
					}
				}
				if($name_payment != ''){
					$name_payment = rtrim($name_payment, ', ');
				}
			}
			
			$html .= '<div class="cls_004"><span class="cls_004">'._l('paid_by').': '.$name_payment.'</span></div>';
			$html .= '<div class="cls_004"><span class="cls_004">'._l('amount').': '.app_format_money($data_cart->total,$currency_name).'</span></div>';
			$html .= '<div class="cls_004"><br></div>';

			$footer = '';
			$data_footer_option = get_option('bill_footer_pos');
			if($data_footer_option){
				$footer = $data_footer_option;      
			}
			$html .= $footer;

			$html .= '<div class="cls_002"><span class="cls_002"> </span>';
			$html .= '</div>';
			$html .= '</div>';
			if($data_contact->email != ''){
				$email_customer = $data_contact->email;
				$data_send_mail['notification_content'] = $html;
				$data_send_mail['email'] = $email_customer;
				$data_send_mail['staff_name'] = $full_name_customer;
				$template = mail_template('purchase_receipt', 'omni_sales', array_to_object($data_send_mail));
				$template->send();
			}
			return $html;
		}
	}
	 /**
		 * add inventory from invoices
		 * @param array $data 
		 */
	 public function add_inventory_from_invoices($data)
	 {   
	 	$available_quantity_n = 0;

	 	$available_quantity = $this->warehouse_model->get_inventory_by_commodity($data['commodity_code']);
	 	if($available_quantity){
	 		$available_quantity_n = $available_quantity->inventory_number;
	 	}
	 	if(isset($data['warehouse_id'])){
	 		if($data['warehouse_id'] != ''){
	 			$this->db->where('warehouse_id', $data['warehouse_id']);
	 		}
	 	}
	 	$this->db->where('commodity_id', $data['commodity_code']);
	 	$this->db->order_by('id', 'ASC');

	 	$result = $this->db->get('tblinventory_manage')->result_array();
	 	$temp_quantities = $data['quantities'];

	 	$expiry_date = '';
	 	$lot_number = '';
	 	foreach ($result as $result_value) {
	 		if (($result_value['inventory_number'] != 0) && ($temp_quantities != 0)) {

	 			if ($temp_quantities >= $result_value['inventory_number']) {
	 				$temp_quantities = (float) $temp_quantities - (float) $result_value['inventory_number'];

						//log lot number
	 				if(($result_value['lot_number'] != null) && ($result_value['lot_number'] != '') ){
	 					if(strlen($lot_number) != 0){
	 						$lot_number .=','.$result_value['lot_number'].','.$result_value['inventory_number'];
	 					}else{
	 						$lot_number .= $result_value['lot_number'].','.$result_value['inventory_number'];
	 					}
	 				}

						//log expiry date
	 				if(($result_value['expiry_date'] != null) && ($result_value['expiry_date'] != '') ){
	 					if(strlen($expiry_date) != 0){
	 						$expiry_date .=','.$result_value['expiry_date'].','.$result_value['inventory_number'];
	 					}else{
	 						$expiry_date .= $result_value['expiry_date'].','.$result_value['inventory_number'];
	 					}
	 				}

						//update inventory
	 				$this->db->where('id', $result_value['id']);
	 				$this->db->update(db_prefix() . 'inventory_manage', [
	 					'inventory_number' => 0,
	 				]);

						//add warehouse id get from inventory manage
	 				if(strlen($data['warehouse_id']) != 0){
	 					$data['warehouse_id'] .= ','.$result_value['warehouse_id'];
	 				}else{
	 					$data['warehouse_id'] .= $result_value['warehouse_id'];

	 				}

	 			} else {

						//log lot number
	 				if(($result_value['lot_number'] != null) && ($result_value['lot_number'] != '') ){
	 					if(strlen($lot_number) != 0){
	 						$lot_number .=','.$result_value['lot_number'].','.$temp_quantities;
	 					}else{
	 						$lot_number .= $result_value['lot_number'].','.$temp_quantities;
	 					}
	 				}

						//log expiry date
	 				if(($result_value['expiry_date'] != null) && ($result_value['expiry_date'] != '') ){
	 					if(strlen($expiry_date) != 0){
	 						$expiry_date .=','.$result_value['expiry_date'].','.$temp_quantities;
	 					}else{
	 						$expiry_date .= $result_value['expiry_date'].','.$temp_quantities;
	 					}
	 				}

					//update inventory
	 				$this->db->where('id', $result_value['id']);
	 				$this->db->update(db_prefix() . 'inventory_manage', [
	 					'inventory_number' => (float) $result_value['inventory_number'] - (float) $temp_quantities,
	 				]);

						//add warehouse id get from inventory manage
	 				if(strlen($data['warehouse_id']) != 0){
	 					$data['warehouse_id'] .= ','.$result_value['warehouse_id'];
	 				}else{
	 					$data['warehouse_id'] .= $result_value['warehouse_id'];

	 				}

	 				$temp_quantities = 0;

	 			}

	 		}

	 	}
	 }

	/**
   * get image items
   * @param  integer $item_id 
   * @return string          
   */
	public function get_image_items($item_id){
		$file_path_rs  = site_url('modules/omni_sales/assets/images/no_image.jpg');
		
		$list_filename = $this->get_all_image_file_name($item_id);
		foreach ($list_filename as $key => $value) {
		    $is_image_exist = false;
		    if (file_exists('modules/warehouse/uploads/item_img/' . $item_id . '/' . $value["file_name"])) {
		      $is_image_exist = true;         
		    } 
		    elseif(file_exists('modules/purchase/uploads/item_img/'. $item_id . '/' . $value["file_name"])) {
		      $is_image_exist = true;         
		    }
		    elseif(file_exists('modules/manufacturing/uploads/products/'. $item_id . '/' . $value["file_name"])) {
		      $is_image_exist = true;         
		    }

		    if($is_image_exist == true){
		      $file_path_rs = omni_check_image_items($item_id, $value['file_name']);
		      break;
		    } 
		}
		
		return $file_path_rs;
	}

	
	/**
	 * proccess sku item delete
	 * @param  integer $item 
	 * @return string       
	 */
	public function proccess_sku_item_delete($item){
		$this->db->where('product_id', $item);
		$this->db->delete(db_prefix().'woocommere_store_detailt');		
		echo "Delete successfully!";
	}
	/**
	 * get quantity inventory
	 * @param  integer $warehouse_id
	 * @param  integer $commodity_id
	 * @return object
	*/
	public function get_quantity_inventory($commodity_id, $warehouse_id = '') {
		$sql = '';
		if($warehouse_id == '0'){
			$sql = 'SELECT commodity_id, sum(inventory_number) as inventory_number from ' . db_prefix() . 'inventory_manage where commodity_id = ' . $commodity_id .' group by commodity_id';
		}
		else{
			$sql = 'SELECT warehouse_id, commodity_id, sum(inventory_number) as inventory_number from ' . db_prefix() . 'inventory_manage where warehouse_id = ' . $warehouse_id . ' AND commodity_id = ' . $commodity_id .' group by warehouse_id, commodity_id';
		}
		if($sql != ''){
			$result = $this->db->query($sql)->row();
			return $result;
		}
	}

	/**
	 *  get product   
	 * @param  int $id    
	 * @return  object or array object       
	 */
	public function get_product_cus($id = ''){
		if($id != ''){
			$this->db->select(db_prefix() . 'woocommere_store_detailt.prices'.','.db_prefix() . 'ware_unit_type.unit_name'.','.db_prefix() . 'items.*');
			$this->db->join(db_prefix() . 'ware_unit_type', db_prefix() . 'ware_unit_type.unit_type_id=' . db_prefix() . 'items.unit_id');
			$this->db->join(db_prefix() . 'woocommere_store_detailt', db_prefix() . 'woocommere_store_detailt.product_id=' . db_prefix() . 'items.id');
			$this->db->where(db_prefix().'items.id',$id);
			return $this->db->get(db_prefix().'items')->row();
		}
		else{     
			return $this->db->get(db_prefix().'items')->result_array();
		}
	}
	/**
	 * clean 
	 * @param  $string 
	 * @return         
	 */
	public function clean($string) {
	 $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	 return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
	}

	public function get_tags_product($id){
		$this->db->from(db_prefix() . 'taggables');
		$this->db->join(db_prefix() . 'tags', db_prefix() . 'tags.id = ' . db_prefix() . 'taggables.tag_id', 'left');

		$this->db->where(db_prefix() . 'taggables.rel_id', $id);
		$this->db->where(db_prefix() . 'taggables.rel_type', 'item_tags');
		$this->db->order_by('tag_order', 'ASC');

		return $item_tags = $this->db->get()->result_array();
	}
	 /**
	 * create payment cart 
	 * @param  string $invoice_id      
	 * @param  string $total_cart      
	 * @param  string $payment_mode    
	 * @param  string $transaction_id  
	 * @param  string $note            
	 * @param  string $do_not_redirect 
	 * @return boolean
	 */
	 public function create_payment_cart($invoice_id, $total_cart, $payment_mode, $transaction_id, $note, $do_not_redirect = 'off'){
	 	$this->load->model('payments_model');
	 	$data_payment["invoiceid"] = $invoice_id;
	 	$data_payment["amount"] = $total_cart;
	 	$data_payment["date"] = _d(date('Y-m-d'));
	 	$data_payment["paymentmode"] = $payment_mode;
	 	$data_payment["do_not_redirect"] = $do_not_redirect;
	 	$data_payment["transactionid"]=$transaction_id;
	 	$data_payment["note"] = $note;
	 	$this->payments_model->add($data_payment); 
	 	return true;
	 }
	/**
	 * has product cat
	 * @param  integer  $channel_id 
	 * @param  integer  $group_id   
	 * @return boolean             
	 */
	public function has_product_cat($channel_id, $group_id){
		$data = $this->db->query('SELECT count(1) as count FROM '.db_prefix().'sales_channel_detailt where sales_channel_id = '.$channel_id.' and product_id in (SELECT id FROM '.db_prefix().'items where group_id = '.$group_id.')')->row();
		if($data){
			if((int)$data->count > 0){
				return true;
			}
		}
		return false;
	}
	/**
	 * sync_from_the_system_to_the_store_single 
	 * @param  $store_id
	 * @param  $arr     
	 * @return          
	 */
	public function sync_from_the_system_to_the_store_single($store_id, $arr = null){
		$channel =  $this->get_woocommere_store($store_id);
		$store_name = $channel->name_channel;
		
		$woocommerce = $this->init_connect_woocommerce($store_id);


		$per_page = 100;
		$products_store = [];
		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

			$products_store = array_merge($products_store, $list_products);
			
			if(count($list_products) < $per_page){
				break;
			}
		}
		$taxes_classes = $woocommerce->get('taxes/classes');
		$arr_taxes = [];
		foreach ($taxes_classes as  $taxes) {
			array_push($arr_taxes, $taxes->name);
		}
		$arr_product_store = [];
		$arr_product_id_store = [];
		foreach ($products_store as $key => $value) {
			if($value->sku != ''){
				array_push($arr_product_store, $value->sku);
				array_push($arr_product_id_store, $value->id);
			}
		}
		$product_detail = [];

		if(isset($arr)){
			$products_list =  $this->products_list_store_detail($store_id, $arr);
			foreach ($products_list as $key => $product) {
				$product_detail[] =  $this->get_product($product[0]['product_id']);
			}
		}else{
			$products_list =  $this->products_list_store($store_id);
			foreach ($products_list as $key => $product) {
				$product_detail[] =  $this->get_product($product['product_id']);
			}
		}

		$data_cus_update_=[];
		$data_cus_update_master=[];
		
		$data_create = [];
		$data_create_master = [];

		$list_tag = [];
		for($page = 1; $page <= $this->per_page_tags; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_tags = $woocommerce->get('products/tags', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

			$list_tag = array_merge($list_tag, $list_tags);
			
			if(count($list_tags) < $this->per_page_tags){
				break;
			}
		}
		$tag_woo_slug = [];
		$tag_woo_id = [];


		foreach ($list_tag as $tag_w) {
			$tag_woo_slug[] = $tag_w->slug;
			$tag_woo_name[] = $tag_w->name;
			$tag_woo_id[] = $tag_w->id;
		}

		foreach ($product_detail as $key => $value) {
			if(!is_null($value)){

				if(!in_array($value->sku_code, $arr_product_store)){
					if($this->omni_sales_model->get_all_image_file_name($value->id)){
						$file_name = $this->omni_sales_model->get_all_image_file_name($value->id);
					}

					$images = [];
					$images_final = [];
					if(isset($file_name)){
						foreach ($file_name as $k => $name) {
							array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$value->id.'/'.$name['file_name'])));
						}
					}
					$date = date('Y-m-d');
					$discount =  $this->check_discount($value->id, $date, 3);

					$price_discount = 0;
					$date_on_sale_from = null;
					$date_on_sale_to = null;
					if(!is_null($discount)){
						if($discount->formal == 1){
							$price_discount = $value->rate - (($value->rate * $discount->discount)/100);
						}else{
							$price_discount = $value->rate - $discount->discount;
						}
						$date_on_sale_from = $discount->start_time;
						$date_on_sale_to = $discount->end_time;
					}else{
						$price_discount = "";
					}
					$tax_status = 'taxable';
					$tax_class = '';
					$taxname = '';
					if($value->tax != '' && !is_null($value->tax)){
						$tax = $this->get_tax($value->tax);
						if($tax != ''){
							$tax_status = 'taxable';
							$tax->name = $this->vn_to_str($tax->name);
							$tax->name = strtolower($this->clean($tax->name));
							if(!in_array($tax->name, $arr_taxes)){
								$slug_class = $this->create_new_tax_sync($store_id, $tax->name, $tax->taxrate);
								$data_rates = [
									"country"=> "",
									"state" => "",
									"postcode" => "",
									"city" => "",
									"compound" => false,
									"shipping" => false,
									'rate' => $tax->taxrate,
									'name' => $tax->name,    
									'class' => $slug_class,
								];
								$woocommerce->post('taxes', $data_rates);
							}else{
								$name_tax_finnal = explode(" ", $tax->name);
								$slug_class = strtolower(implode("-", $name_tax_finnal));
							}
							if($tax == ''){
								$taxname = 'zero-rate';
							}else{
								if(isset($slug_class)){
									$taxname = $slug_class;
								}else{
									$taxname = 'standard';
								}
							}
							$tax_class = $taxname;
						}
					}

					$stock_quantity = $this->get_total_inventory_commodity($value->id); 
					$regular_price = $this->get_price_store($value->id, $store_id);
					$get_tags_product = $this->get_tags_product($value->id);

					$tags_id = [];
					$tags_name = [];
					$tags_final = [];

					if(count($get_tags_product) > 0){
						foreach ($get_tags_product as $get_tags_) {
							$tags_id[] =  $get_tags_['rel_id'];
							$tags_name[] =  $get_tags_['name'];
						}
					}

					if(count($tags_name) > 0){
						$data_tag_ = [];
						foreach ($tags_name as $key_count => $tags_) {
							$tags_ = strtolower($tags_);
							$tags_ = trim($tags_);
							$tags_ = $this->vn_to_str($tags_);
							$name_tag = $this->clean($tags_);

							if(!in_array($name_tag, $tag_woo_slug)){
								$data_tag_[] = [
									'name' => $name_tag 
								];

							}else{
								foreach ($tag_woo_slug as $keyss => $valuess_) {
									if($valuess_ == $name_tag){
										$tags_final[] = ['id' => $tag_woo_id[$keyss] ]; 
									}
								}
							}

						}
						foreach ($data_tag_ as $data_1) {
							if(!in_array($data_1["name"], $tag_woo_name)){
								$avbcs = $woocommerce->post('products/tags', $data_1);
								$tag_woo_slug[] = $avbcs->slug;
								$tag_woo_id[] = $avbcs->id;
								$tags_final[] = ['id' => $avbcs->id ];
							}
						}
					}


					$data = [
						'name' => $value->description,
						'type' => 'simple',
						'regular_price' => $value->rate,
						'sale_price' => strval($price_discount),
						'date_on_sale_from' => $date_on_sale_from,
						'date_on_sale_to' => $date_on_sale_to,
						'short_description' => $value->long_description,
						'stock_quantity' => $stock_quantity->inventory_number,
						'manage_stock' => true,
						'tax_status' => $tax_status,
						'tax_class' => $tax_class,
						'sku' => $value->sku_code,
						'tags' => $tags_final,

					];

					$data1 = [
						'name' => $value->description,
						'type' => 'simple',
						'regular_price' => $value->rate,
						'sale_price' => strval($price_discount),
						'date_on_sale_from' => $date_on_sale_from,
						'date_on_sale_to' => $date_on_sale_to,
						'short_description' => $value->long_description,
						'stock_quantity' => $stock_quantity->inventory_number,
						'manage_stock' => true,
						'tax_status' => $tax_status,
						'tax_class' => $tax_class,
						'tags' => $tags_final,
					];
					$log_product = [
						'name' => $value->description,
						'regular_price' => $value->rate,
						'sale_price' => strval($price_discount),
						'date_on_sale_from' => $date_on_sale_from,
						'date_on_sale_to' => $date_on_sale_to,
						'short_description' => $value->long_description,
						'stock_quantity' => $stock_quantity->inventory_number,
						'chanel' => 'WooCommerce('.$store_name.')',
						'sku' => $value->sku_code,
						'type' => "products",
					];  
					array_push($data_create,$data);
					if(count($data_create) == $this->amount){
						array_push($data_create_master,$data_create);
						$data_create = [];
					}
					$this->db->insert(db_prefix().'omni_log_sync_woo', $log_product);
				}else{

					$get_tags_product = $this->get_tags_product($value->id);

					$tags_id = [];
					$tags_name = [];
					$tags_final = [];

					if(count($get_tags_product) > 0){
						foreach ($get_tags_product as $get_tags_) {
							$tags_id[] =  $get_tags_['rel_id'];
							$tags_name[] =  $get_tags_['name'];
						}
					}if(count($tags_name) > 0){
						$data_tag_ = [];
						foreach ($tags_name as $key_count => $tags_) {
							$tags_ = strtolower($tags_);
							$tags_ = trim($tags_);
							$tags_ = $this->vn_to_str($tags_);
							$name_tag = $this->clean($tags_);

							if(!in_array($name_tag, $tag_woo_slug)){
								$data_tag_[] = [
									'name' => $name_tag 
								];

							}else{
								foreach ($tag_woo_slug as $keyss => $valuess_) {
									if($valuess_ == $name_tag){
										$tags_final[] = ['id' => $tag_woo_id[$keyss] ]; 
									}
								}
							}

						}
						foreach ($data_tag_ as $data_1) {
							if(!in_array($data_1["name"], $tag_woo_name)){
								$avbcs = $woocommerce->post('products/tags', $data_1);
								$tag_woo_slug[] = $avbcs->slug;
								$tag_woo_id[] = $avbcs->id;
								$tags_final[] = ['id' => $avbcs->id ];
							}
						}
					}
					$index_key = array_search($value->sku_code,$arr_product_store,true);
					if(count($arr_product_id_store) > 0){
						$regular_price = $this->get_price_store($value->id, $store_id);
						$regular_price_prices = '';
						if(!isset($regular_price->prices)){
							$regular_price_prices = 0;
						}else{
							$regular_price_prices = $regular_price->prices;
						}
						$stock_quantity = $this->get_total_inventory_commodity($value->id);
						if($this->omni_sales_model->get_all_image_file_name($value->id)){
							$file_name = $this->omni_sales_model->get_all_image_file_name($value->id);
						}

						$images = [];
						$images_final = [];
						if(isset($file_name)){
							foreach ($file_name as $k => $name) {
								array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$value->id.'/'.$name['file_name'])));
							}
						}
						$tax_status = 'taxable';
						$tax_class = '';
						$taxname = '';
						if($value->tax != '' && !is_null($value->tax)){
							$tax = $this->get_tax($value->tax);
							if($tax != ''){
								$tax_status = 'taxable';
								$tax->name = $this->vn_to_str($tax->name);
								$tax->name = strtolower($this->clean($tax->name));
								if(!in_array($tax->name, $arr_taxes)){
									$slug_class = $this->create_new_tax_sync($store_id, $tax->name, $tax->taxrate);
									$data_rates = [
										"country"=> "",
										"state" => "",
										"postcode" => "",
										"city" => "",
										"compound" => false,
										"shipping" => false,
										'rate' => $tax->taxrate,
										'name' => $tax->name,    
										'class' => $slug_class,
									];
									$woocommerce->post('taxes', $data_rates);
								}else{
									$name_tax_finnal = explode(" ", $tax->name);
									$slug_class = strtolower(implode("-", $name_tax_finnal));
								}
								if($tax == ''){
									$taxname = 'zero-rate';
								}else{
									if(isset($slug_class)){
										$taxname = $slug_class;
									}else{
										$taxname = 'standard';
									}
								}
								$tax_class = $taxname;
							}
						}
						$data_cus_update_2 = [
							'id' => $arr_product_id_store[$index_key],
							'tags' => $tags_final,
							'name' => $value->description,
							'regular_price' => $value->rate,
							'tax_status' => $tax_status,
							'tax_class' => $tax_class,
							'short_description' => $value->long_description,
						];
						array_push($data_cus_update_, $data_cus_update_2);

						if(count($data_cus_update_) == $this->amount){

							array_push($data_cus_update_master, $data_cus_update_);
							$data_cus_update_ = [];

						}
					}
				}
			}
		}
		if(count($arr_product_id_store) > 0){
			if(count($data_cus_update_) < $this->amount){
				array_push($data_cus_update_master,$data_cus_update_);
			}

			if($data_cus_update_){
				foreach ($data_cus_update_master as  $data__s) {
					$data_cus_ = [
						'update' => $data__s
					];
					$woocommerce->post('products/batch', $data_cus_);
					$this->exit_type_variation($store_id, 2);
				}
			}
		}

		if(count($data_create) < 10){
			array_push($data_create_master,$data_create);
		}

		if(count($data_create_master) > 0){
			foreach ($data_create_master as  $data__) {
				$data_cus = [
					'create' => $data__
				];
				$woocommerce->post('products/batch', $data_cus);
				$this->exit_type_variation($store_id, 1);
			}
		}
		return true;
	}
	public function vn_to_str($str){

		$unicode = array(

			'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',

			'd'=>'đ',

			'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',

			'i'=>'í|ì|ỉ|ĩ|ị',

			'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',

			'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',

			'y'=>'ý|ỳ|ỷ|ỹ|ỵ',

			'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',

			'D'=>'Đ',

			'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',

			'I'=>'Í|Ì|Ỉ|Ĩ|Ị',

			'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',

			'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',

			'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',

		);

		foreach($unicode as $nonUnicode=>$uni){

			$str = preg_replace("/($uni)/i", $nonUnicode, $str);

		}
		$str = str_replace('  ',' ',$str);
		$str = str_replace(' ','-',$str);

		return $str;

	}

	/**
	 * products list store
	 * @param  int $store_id 
	 * @return array           
	 */
	public function products_list_store_detail($store_id, $arr = []){
		$rs = [];

		if(count($arr) > 0){
			foreach ($arr as $key => $value_id) {
				$this->db->where('woocommere_store_id = '.$store_id.' and product_id = '.$value_id.'');
				array_push($rs, $this->db->get(db_prefix().'woocommere_store_detailt')->result_array());
			}
		}
		return $rs;
	}

	/**
 * process inventory synchronization
 * @param  int $store_id 
 * @return bool           
 */
	public function process_inventory_synchronization_detail($store_id, $arr_detail = null){
		$store =  $this->get_woocommere_store($store_id);
		$store_name = $store->name_channel;
		$products_store = $this->get_product_parent_id();

		$items = [];
		if(isset($arr_detail)){
			foreach ($arr_detail  as $key => $product) {
				$this->db->where('id',$product);
				array_push($items, $this->db->get(db_prefix().'items')->row());
			}
		}else{
			if(!empty($products_store)){
				if(count($products_store) > 0){
					foreach ($products_store  as $key => $product) {
						$this->db->where('id',$product['id']);
						array_push($items, $this->db->get(db_prefix().'items')->row());
					}
				}
			}
		}
		$woocommerce = $this->init_connect_woocommerce($store_id);

		$per_page = 100;
		$products_store = [];
		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

			$products_store = array_merge($products_store, $list_products);

			if(count($list_products) < $per_page){
				break;
			}
		}
		$data_create = [];
		$data_create_master = [];
		foreach ($products_store as $key => $value) {

			if($value->sku != ''){
				foreach ($items as $item) {
					if($item->sku_code == $value->sku){

						$stock_quantity = $this->get_total_inventory_commodity($item->id);
						$stock_quantity = $stock_quantity->inventory_number;
						$images = [];
						if($this->get_all_image_file_name($item->id)){
							$file_name = $this->omni_sales_model->get_all_image_file_name($item->id);
						}
						if(isset($file_name)){
							foreach ($file_name as $k => $name) {
								array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$item->id.'/'.$name['file_name'])));
							}
						}
						if(is_null($stock_quantity)){
							$stock_quantity = 0;
						}
						$date = date('Y-m-d');
						$discount =  $this->check_discount($item->id, $date, 3, $store_id);
						$price_discount = 0;
						$date_on_sale_from = null;
						$date_on_sale_to = null;
						if(!is_null($discount)){
							if($discount->formal == 1){
								$price_discount = $item->rate - (($item->rate * $discount->discount)/100);
							}else{
								$price_discount = $item->rate - $discount->discount;
							}
							$date_on_sale_from = $discount->start_time;
							$date_on_sale_to = $discount->end_time;
						}else{
							$price_discount = "";
						}
						$regular_price = $this->get_price_store($item->id, $store_id);
						$regular_price_prices = '';
						if(!isset($regular_price->prices)){
							$regular_price_prices = 0;
						}else{
							$regular_price_prices = $regular_price->prices;
						}
						$data = [
							'id' => $value->id,
							"stock_quantity" => $stock_quantity,
							"manage_stock" => true,
						];
						if(is_null($value->stock_quantity)){
							$value->stock_quantity = 0;
						}
						$log_inventory = [
							'name' => $item->description,
							'regular_price' => $item->rate,
							'sale_price' => strval($price_discount),
							'date_on_sale_from' => $date_on_sale_from,
							'date_on_sale_to' => $date_on_sale_to,
							'short_description' => $item->description,
							"stock_quantity" => $stock_quantity,
							"stock_quantity_history" => $value->stock_quantity,
							"chanel" => 'WooCommerce('.$store_name.')',
							"sku" => $item->sku_code,
							"type" => "inventory",
						];
						array_push($data_create,$data);
						if(count($data_create) == $this->amount){
							array_push($data_create_master,$data_create);
							$data_create = [];
						}
						$this->db->insert(db_prefix().'omni_log_sync_woo', $log_inventory);
					}
				}   
			}
		}
		if(count($data_create) < $this->amount){
			array_push($data_create_master,$data_create);
		}
		if($data_create_master > 0){
			foreach ($data_create_master as  $data__) {
				$data_cus = [
					'update' => $data__
				];
				$woocommerce->post('products/batch', $data_cus);
			}
			$this->exit_type_variation($store_id, 2);
		}

		return true;
	}
	/**
	 * process images synchronization
	 * @param $store_id
	 */

	public function process_images_synchronization_detail($store_id, $arr_detail = null){
		$items = [];
		$products_store = $this->get_product_parent_id();

		if(isset($arr_detail)){
			foreach ($arr_detail  as $key => $product) {
				$this->db->where('id',$product);
				array_push($items, $this->db->get(db_prefix().'items')->row());
			}
		}else{
			if(!empty($products_store)){
				if(count($products_store) > 0){
					foreach ($products_store  as $key => $product) {
						$this->db->where('id',$product['id']);
						array_push($items, $this->db->get(db_prefix().'items')->row());
					}
				}
			}
		}

		$woocommerce = $this->init_connect_woocommerce($store_id);

		$per_page = 100;
		$products_store = [];
		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);
			$products_store = array_merge($products_store, $list_products);

			if(count($list_products) < $per_page){
				break;
			}
		}
		$arr_product_store = [];
		$data_create = [];
		$data_create_master = [];
		$data_cus_update_=[];
		$data_cus_update_master=[];
		foreach ($products_store as $key => $value) {

			if($value->sku != ''){
				foreach ($items as $item) {
					if($item->sku_code == $value->sku){

						$images = [];
						if($this->get_all_image_file_name($item->id)){
							$file_name = $this->omni_sales_model->get_all_image_file_name($item->id);
						}
						foreach ($file_name as $k => $name) {
							array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$item->id.'/'.$name['file_name'])));
						}

						$data_cus_update_2 = [
							'id' => $value->id,
							'images' => $images
						];

						array_push($data_cus_update_, $data_cus_update_2);
						if(count($data_cus_update_) == $this->amount){
							array_push($data_cus_update_master, $data_cus_update_);
							$data_cus_update_ = [];
						}

						$images_arr = [
							'id' => $value->id,
							'images' => $images
						];
					}
				}   
			}
		}
		if(count($data_cus_update_) < $this->amount){
			array_push($data_cus_update_master,$data_cus_update_);
		}

		if(count($data_cus_update_) > 0){
			foreach ($data_cus_update_master as  $data__s) {
				$data_cus_ = [
					'update' => $data__s
				];
				$woocommerce->post('products/batch', $data_cus_);
			}
			$this->exit_type_variation($store_id, 2);
		}

		return true;
	}

	/**
		 * process decriptions synchronization
		 * @param $store_id
		 * @return           
		 */
	public function process_decriptions_synchronization_detail($store_id, $arr_detail = null){
		$items = [];
		$products_store = $this->get_product_parent_id();

		if(isset($arr_detail)){
			foreach ($arr_detail  as $key => $product) {
				$this->db->where('id',$product);
				array_push($items, $this->db->get(db_prefix().'items')->row());
			}
		}else{
			if(!empty($products_store)){
				if(count($products_store) > 0){
					foreach ($products_store  as $key => $product) {
						$this->db->where('id',$product['id']);
						array_push($items, $this->db->get(db_prefix().'items')->row());
					}
				}
			}
		}

		$woocommerce = $this->init_connect_woocommerce($store_id);

		$per_page = 100;
		$products_store = [];
		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

			$products_store = array_merge($products_store, $list_products);

			if(count($list_products) < $per_page){
				break;
			}
		}
		$arr_product_store = [];
		$data_create = [];
		$data_create_master = [];
		foreach ($products_store as $key => $value) {

			if($value->sku != ''){
				foreach ($items as $item) {
					if($item->sku_code == $value->sku){
						$data = [
							'id' => $value->id,
							'description' => $item->long_descriptions,
						];

						$log_product = [
							'name' => $item->description,
							'short_description' => $item->long_description,
							'description' => $item->long_descriptions,
							'chanel' => 'WooCommerce('.$this->get_name_store($store_id).')',
							'sku' => $item->sku_code,
							'type' => "description",
						];

						$this->db->insert(db_prefix().'omni_log_sync_woo', $log_product);

						if(is_null($value->stock_quantity)){
							$value->stock_quantity = 0;
						}
						array_push($data_create,$data);
						if(count($data_create) == $this->amount){
							array_push($data_create_master,$data_create);
							$data_create = [];
						}
					}
				}   
			}
		}
		if(count($data_create) < 10){
			array_push($data_create_master,$data_create);
		}

		if(count($data_create_master) > 0){
			foreach ($data_create_master as  $data__) {
				$data_cus = [
					'update' => $data__
				];
				$woocommerce->post('products/batch', $data_cus);
			}
			$this->exit_type_variation($store_id, 2);
		}
		return true;
	}

	/**
	 *  delete_product  
	 * @param   int $id   
	 * @return  bool       
	 */
	public function delete_product_store_all($store, $id){
		$this->db->where('product_id',$id);
		$this->db->where('woocommere_store_id',$store);
		$this->db->delete(db_prefix().'woocommere_store_detailt');
		if ($this->db->affected_rows() > 0) {           
			return true;
		}
		return false;
	}

	/**
	 *  get_woocommere_store_detailt 
	 * @param   int  $product_id           
	 * @param   int  $woocommere_store_id  
	 * @return  object                        
	 */
	public function get_ids_woocommere_store_detailt($woocommere_store_id){
		$this->db->where('woocommere_store_id', $woocommere_store_id);
		$products = $this->db->get('woocommere_store_detailt')->result_array();
		$ids = [];
		foreach ($products as $product) {
			array_push($ids, $product['product_id']);
		}
		return $ids;
	}

	/**
	 * sync all 
	 * @param  $store_id
	 * @param  $arr     
	 * @return          
	 */
	public function sync_all($store_id, $arr = null){

		$woocommerce = $this->init_connect_woocommerce($store_id);

		//get all products have variation include ids and sku_codes 
		$products_variation = $this->get_item_have_variation();

		$per_page = 100;
		$products_store = [];
		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

			$products_store = array_merge($products_store, $list_products);
			
			if(count($list_products) < $per_page){
				break;
			}
		}
		$taxes_classes = $woocommerce->get('taxes/classes');
		$arr_taxes = [];
		foreach ($taxes_classes as  $taxes) {
			array_push($arr_taxes, $taxes->name);
		}
		$arr_product_store = [];
		$arr_product_id_store = [];
		foreach ($products_store as $key => $value) {
			if($value->sku != ''){
				array_push($arr_product_store, $value->sku);
				array_push($arr_product_id_store, $value->id);
			}
		}
		$product_detail = [];

		if(isset($arr)){
			$products_list =  $this->products_list_store_detail($store_id, $arr);
			foreach ($products_list as $key => $product) {
				$this->db->select('*, (select '.db_prefix().'items_groups.name from '.db_prefix().'items_groups where '.db_prefix().'items_groups.id = '.db_prefix().'items.group_id) as group_name');
				$this->db->where('id',$product[0]['product_id']);
				$product_detail[] = $this->db->get(db_prefix().'items')->row();
			}
		}else{
			$products_list =  $this->products_list_store($store_id);
			foreach ($products_list as $key => $product) {
				$this->db->select('*, (select '.db_prefix().'items_groups.name from '.db_prefix().'items_groups where '.db_prefix().'items_groups.id = '.db_prefix().'items.group_id) as group_name');
				$this->db->where('id',$product['product_id']);
				$product_detail[] = $this->db->get(db_prefix().'items')->row();
			}
		}

		$data_cus_update_=[];
		$data_cus_update_master=[];
		
		$data_create = [];
		$data_create_master = [];

		$list_tag = [];

		for($page = 1; $page <= $this->per_page_tags; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_tags = $woocommerce->get('products/tags', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);
			$list_tag = array_merge($list_tag, $list_tags);
			if(count($list_tags) < $this->per_page_tags){
				break;
			}
		}

		$tag_woo_slug = [];
		$tag_woo_id = [];


		foreach ($list_tag as $tag_w) {
			$tag_woo_slug[] = $tag_w->slug;
			$tag_woo_name[] = $tag_w->name;
			$tag_woo_id[] = $tag_w->id;
		}

		$_product_categories_list = [];

		for($page = 1; $page <= $this->per_page_product_categories; $page++ ){
			$offset = ($page - 1) * $per_page;
			$categories = $woocommerce->get('products/categories', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);
			$_product_categories_list = array_merge($_product_categories_list, $categories);
			if(count($categories) < $this->per_page_product_categories){
				break;
			}
		}

		$product_categories_list = [];
		foreach ($_product_categories_list as $category) {
			$product_categories_list[html_entity_decode($category->name)] = $category->id;
		}

		foreach ($product_detail as $key => $value) {
			if(!is_null($value)){

				if(!in_array($value->sku_code, $arr_product_store)){
					if($this->omni_sales_model->get_all_image_file_name($value->id)){
						$file_name = $this->omni_sales_model->get_all_image_file_name($value->id);
					}

					$images = [];
					$images_final = [];
					if(isset($file_name)){
						foreach ($file_name as $k => $name) {
							array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$value->id.'/'.$name['file_name'])));
						}
					}

					$date = date('Y-m-d');
					$discount =  $this->check_discount($value->id, $date, 3);

					$price_discount = 0;
					$date_on_sale_from = null;
					$date_on_sale_to = null;
					if(!is_null($discount)){
						if($discount->formal == 1){
							$price_discount = $value->rate - (($value->rate * $discount->discount)/100);
						}else{
							$price_discount = $value->rate - $discount->discount;
						}
						$date_on_sale_from = $discount->start_time;
						$date_on_sale_to = $discount->end_time;
					}else{
						$price_discount = "";
					}
					$tax_status = 'taxable';
					$tax_class = '';
					$taxname = '';
					if($value->tax != '' && !is_null($value->tax)){
						$tax = $this->get_tax($value->tax);
						if($tax != ''){
							$tax_status = 'taxable';
							$tax->name = $this->vn_to_str($tax->name);
							$tax->name = strtolower($this->clean($tax->name));
							if(!in_array($tax->name, $arr_taxes)){
								$slug_class = $this->create_new_tax_sync($store_id, $tax->name, $tax->taxrate);
								$data_rates = [
									"country"=> "",
									"state" => "",
									"postcode" => "",
									"city" => "",
									"compound" => false,
									"shipping" => false,
									'rate' => $tax->taxrate,
									'name' => $tax->name,    
									'class' => $slug_class,
								];
								$woocommerce->post('taxes', $data_rates);
							}else{
								$name_tax_finnal = explode(" ", $tax->name);
								$slug_class = strtolower(implode("-", $name_tax_finnal));
							}
							if($tax == ''){
								$taxname = 'zero-rate';
							}else{
								if(isset($slug_class)){
									$taxname = $slug_class;
								}else{
									$taxname = 'standard';
								}
							}
							$tax_class = $taxname;
						}
					}

					$stock_quantity = $this->get_total_inventory_commodity($value->id); 
					$regular_price = $this->get_price_store($value->id, $store_id);
					$get_tags_product = $this->get_tags_product($value->id);
					
					$tags_id = [];
					$tags_name = [];
					$tags_final = [];

					if(count($get_tags_product) > 0){
						foreach ($get_tags_product as $get_tags_) {
							$tags_id[] =  $get_tags_['rel_id'];
							$tags_name[] =  $get_tags_['name'];
						}
					}

					if(count($tags_name) > 0){
						$data_tag_ = [];
						foreach ($tags_name as $key_count => $tags_) {
							$tags_ = strtolower($tags_);
							$tags_ = trim($tags_);
							$tags_ = $this->vn_to_str($tags_);
							$name_tag = $this->clean($tags_);

							if(!in_array($name_tag, $tag_woo_slug)){
								$data_tag_[] = [
									'name' => $name_tag 
								];

							}else{
								foreach ($tag_woo_slug as $keyss => $valuess_) {
									if($valuess_ == $name_tag){
										$tags_final[] = ['id' => $tag_woo_id[$keyss] ]; 
									}
								}
							}

						}
						foreach ($data_tag_ as $data_1) {
							if(!in_array($data_1["name"], $tag_woo_name)){
								$avbcs = $woocommerce->post('products/tags', $data_1);
								$tag_woo_slug[] = $avbcs->slug;
								$tag_woo_id[] = $avbcs->id;
								$tags_final[] = ['id' => $avbcs->id ];
							}
						}
					}

					$regular_price = $this->get_price_store($value->id, $store_id);
					$regular_price_prices = '';
					if(!isset($regular_price->prices)){
						$regular_price_prices = 0;
					}else{
						$regular_price_prices = $regular_price->prices;
					}

					$type = 'simple';
					$attributes = [];

					if(in_array($value->sku_code, $products_variation['sku_code_s'])){
						$type = 'variable';
					//get id by index of array $products_variation['sku_code_s']
						$index = array_search($value->sku_code , $products_variation['sku_code_s'], true);

						$this->db->where('id', $products_variation['ids'][$index]);

						$item_attributes = $this->db->get(db_prefix().'items')->row();

						if($item_attributes->parent_attributes != null || $item_attributes->parent_attributes != ''){
							$parent_attributes = json_decode($item_attributes->parent_attributes);
						}else{
							$parent_attributes = [];
						}

						$products_attributes = $woocommerce->get('products/attributes');
						//get name attr 
						$slug_attributes = [];
						$name_attributes = [];
						$id_attributes = [];

						if(count($products_attributes) > 0){
							foreach ($products_attributes as $key_products_attributes => $value_products_attributes) {
								array_push($slug_attributes, $value_products_attributes->slug);
								array_push($name_attributes, $value_products_attributes->name);
								array_push($id_attributes, $value_products_attributes->id);
							}
						}


						if(count($parent_attributes) > 0){
							foreach ($parent_attributes as $key_parent_attributes => $value_parent_attributes) {
								if($value_parent_attributes->name == ''){
									continue;
								}
								$create_attributes = $this->vn_to_str($value_parent_attributes->name);
								$create_attributes = strtolower($this->clean($value_parent_attributes->name));
								$create_attributes = "pa_" . $create_attributes;
								//check in_array exit in slug 
								if(!in_array($create_attributes, $slug_attributes)){

									$create_data_attr = [
										'name' => $value_parent_attributes->name,
										'slug' => $create_attributes,
										'has_archives' => true
									];

									$data_terms = [];

									foreach ($value_parent_attributes->options as $key_options => $value_options) {
										$data_terms[] = ['name' => $value_options];
									}

									//add attr to woo api
									$attr_id = $woocommerce->post('products/attributes', $create_data_attr);

									$create_attr_terms_data = [
										'create' => $data_terms
									];

									//add attr term to woo api
									$woocommerce->post('products/attributes/'.$attr_id->id.'/terms/batch', $create_attr_terms_data);

									$attributes[] = [
										"id" => $attr_id->id,
										"name" => $value_parent_attributes->name,
										"visible" => true,
										"variation" => true,
										"options" => $value_parent_attributes->options
									]; 

								}else{
									$index_exit_attr = array_search($create_attributes, $slug_attributes, true);

									$attributes[] = [
										"id" => $id_attributes[$index_exit_attr],
										"name" => $value_parent_attributes->name,
										"visible" => true,
										"variation" => true,
										"options" => $value_parent_attributes->options
									]; 
								}

							}

						}
					}

					$categories = [];
					if($value->group_name != ''){
						if(isset($product_categories_list[$value->group_name])){
							$categories[] = ['id' => $product_categories_list[$value->group_name]];
						}else{
							$category = $woocommerce->post('products/categories', ['name' => $value->group_name]);

							$product_categories_list[$value->group_name] = $category->id;
							$categories[] = ['id' => $category->id];
						}
					}

					$data = [
						'name' => $value->description,
						'type' => $type,
						'regular_price' => $regular_price_prices,
						'sale_price' => strval($price_discount),
						'date_on_sale_from' => $date_on_sale_from,
						'date_on_sale_to' => $date_on_sale_to,
						'short_description' => $value->long_description,
						'stock_quantity' => $stock_quantity->inventory_number,
						'manage_stock' => true,
						'tax_status' => $tax_status,
						'tax_class' => $tax_class,
						'sku' => $value->sku_code,
						'tags' => $tags_final,
						'images' => $images,
						'categories' => $categories,
						'description' => $value->long_descriptions,
						'attributes' => $attributes,
					];  

					$data1 = [
						'name' => $value->description,
						'type' => $type,
						'regular_price' => $regular_price_prices,
						'sale_price' => strval($price_discount),
						'date_on_sale_from' => $date_on_sale_from,
						'date_on_sale_to' => $date_on_sale_to,
						'short_description' => $value->long_description,
						'stock_quantity' => $stock_quantity->inventory_number,
						'manage_stock' => true,
						'tax_status' => $tax_status,
						'tax_class' => $tax_class,
						'sku' => $value->sku_code,
						'tags' => $tags_final,
						'images' => $images,
						'description' => $value->long_descriptions,
						'attributes' => $attributes,
					];
					$log_product = [
						'name' => $value->description,
						'regular_price' => $regular_price_prices,
						'sale_price' => strval($price_discount),
						'date_on_sale_from' => $date_on_sale_from,
						'date_on_sale_to' => $date_on_sale_to,
						'short_description' => $value->long_description,
						'stock_quantity' => $stock_quantity->inventory_number,
						'chanel' => 'WooCommerce('.$this->get_name_store($store_id).')',
						'sku' => $value->sku_code,
						'type' => "products-all",
					];  

					array_push($data_create,$data);
					if(count($data_create) == $this->amount){
						array_push($data_create_master,$data_create);
						$data_create = [];
					}
					$this->db->insert(db_prefix().'omni_log_sync_woo', $log_product);


				}else{

					$get_tags_product = $this->get_tags_product($value->id);

					$tags_id = [];
					$tags_name = [];
					$tags_final = [];

					if(count($get_tags_product) > 0){
						foreach ($get_tags_product as $get_tags_) {
							$tags_id[] =  $get_tags_['rel_id'];
							$tags_name[] =  $get_tags_['name'];
						}
					}if(count($tags_name) > 0){
						$data_tag_ = [];
						foreach ($tags_name as $key_count => $tags_) {
							$tags_ = strtolower($tags_);
							$tags_ = trim($tags_);
							$tags_ = $this->vn_to_str($tags_);
							$name_tag = $this->clean($tags_);

							if(!in_array($name_tag, $tag_woo_slug)){
								$data_tag_[] = [
									'name' => $name_tag 
								];

							}else{
								foreach ($tag_woo_slug as $keyss => $valuess_) {
									if($valuess_ == $name_tag){
										$tags_final[] = ['id' => $tag_woo_id[$keyss] ]; 
									}
								}
							}

						}
						foreach ($data_tag_ as $data_1) {
							if(!in_array($data_1["name"], $tag_woo_name)){
								$avbcs = $woocommerce->post('products/tags', $data_1);
								$tag_woo_slug[] = $avbcs->slug;
								$tag_woo_id[] = $avbcs->id;
								$tags_final[] = ['id' => $avbcs->id ];
							}
						}
					}
					$index_key = array_search($value->sku_code,$arr_product_store,true);

					if(count($arr_product_id_store) > 0){
						$regular_price = $this->get_price_store($value->id, $store_id);
						$regular_price_prices = '';
						if(!isset($regular_price->prices)){
							$regular_price_prices = 0;
						}else{
							$regular_price_prices = $regular_price->prices;
						}
						$stock_quantity = $this->get_total_inventory_commodity($value->id);
						if($this->omni_sales_model->get_all_image_file_name($value->id)){
							$file_name = $this->omni_sales_model->get_all_image_file_name($value->id);
						}

						$images = [];
						$images_final = [];
						if(isset($file_name)){
							foreach ($file_name as $k => $name) {
								array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$value->id.'/'.$name['file_name'])));
							}
						}
						$tax_status = 'taxable';
						$tax_class = '';
						$taxname = '';
						if($value->tax != '' && !is_null($value->tax)){
							$tax = $this->get_tax($value->tax);
							if($tax != ''){
								$tax_status = 'taxable';
								$tax->name = $this->vn_to_str($tax->name);
								$tax->name = strtolower($this->clean($tax->name));
								if(!in_array($tax->name, $arr_taxes)){
									$slug_class = $this->create_new_tax_sync($store_id, $tax->name, $tax->taxrate);
									$data_rates = [
										"country"=> "",
										"state" => "",
										"postcode" => "",
										"city" => "",
										"compound" => false,
										"shipping" => false,
										'rate' => $tax->taxrate,
										'name' => $tax->name,    
										'class' => $slug_class,
									];
									$woocommerce->post('taxes', $data_rates);
								}else{
									$name_tax_finnal = explode(" ", $tax->name);
									$slug_class = strtolower(implode("-", $name_tax_finnal));
								}
								if($tax == ''){
									$taxname = 'zero-rate';
								}else{
									if(isset($slug_class)){
										$taxname = $slug_class;
									}else{
										$taxname = 'standard';
									}
								}
								$tax_class = $taxname;
							}
						}


						$type = 'simple';
						$attributes = [];

						if(in_array($value->sku_code, $products_variation['sku_code_s'])){
							$type = 'variable';
					//get id by index of array $products_variation['sku_code_s']
							$index = array_search($value->sku_code , $products_variation['sku_code_s'], true);

							$this->db->where('id', $products_variation['ids'][$index]);

							$item_attributes = $this->db->get(db_prefix().'items')->row();

							if($item_attributes->parent_attributes != null || $item_attributes->parent_attributes != ''){
								$parent_attributes = json_decode($item_attributes->parent_attributes);
							}else{
								$parent_attributes = [];
							}

							$products_attributes = $woocommerce->get('products/attributes');
						//get name attr 
							$slug_attributes = [];
							$name_attributes = [];
							$id_attributes = [];

							if(count($products_attributes) > 0){
								foreach ($products_attributes as $key_products_attributes => $value_products_attributes) {
									array_push($slug_attributes, $value_products_attributes->slug);
									array_push($name_attributes, $value_products_attributes->name);
									array_push($id_attributes, $value_products_attributes->id);
								}
							}


							if(count($parent_attributes) > 0){
								$update_attr = $woocommerce->get('products/'.$arr_product_id_store[$index_key]);
								foreach ($parent_attributes as $key_parent_attributes => $value_parent_attributes) {
									if(isset($update_attr->attributes[$key_parent_attributes])){
										if($value_parent_attributes->name == $update_attr->attributes[$key_parent_attributes]->name){
											$attributes[] = [
												"id" => $update_attr->attributes[$key_parent_attributes]->id,
												"name" => $value_parent_attributes->name,
												"visible" => true,
												"variation" => true,
												"options" => $value_parent_attributes->options
											]; 
										}
									}
								}
							}
						}

						$categories = [];
						if($value->group_name != ''){
							if(isset($product_categories_list[$value->group_name])){
								$categories[] = ['id' => $product_categories_list[$value->group_name]];
							}else{
								$category = $woocommerce->post('products/categories', ['name' => $value->group_name]);

								$product_categories_list[$value->group_name] = $category->id;
								$categories[] = ['id' => $category->id];
							}
						}

						$data_cus_update_2 = [
							'id' => $arr_product_id_store[$index_key],
							'tags' => $tags_final,
							'type' => $type,
							'name' => $value->description,
							'regular_price' => $regular_price_prices,
							'tax_status' => $tax_status,
							'tax_class' => $tax_class,
							'short_description' => $value->long_description,
							'description' => $value->long_descriptions,
							'stock_quantity' => $stock_quantity->inventory_number,
							'manage_stock' => true,
							'images' => $images,
							'categories' => $categories,
							'attributes' => $attributes
						];
						array_push($data_cus_update_, $data_cus_update_2);
						if(count($data_cus_update_) == $this->amount){
							array_push($data_cus_update_master, $data_cus_update_);
							$data_cus_update_ = [];
						}
					}
				}
			}
		}

		if(count($arr_product_id_store) > 0){
			if(count($data_cus_update_) < $this->amount){
				array_push($data_cus_update_master,$data_cus_update_);
			}

			if($data_cus_update_){
				foreach ($data_cus_update_master as  $data__s) {
					$data_cus_ = [
						'update' => $data__s
					];
					$woocommerce->post('products/batch', $data_cus_);
					$this->exit_type_variation($store_id, 2);
				}
			}
		}
		if(count($data_create) < 10){
			array_push($data_create_master,$data_create);
		}

		if(count($data_create_master) > 0 && count($data_create_master[0]) > 0){
			foreach ($data_create_master as  $data__) {
				$data_cus = [
					'create' => $data__
				];
				$create_batch = $woocommerce->post('products/batch', $data_cus);
			}
			$this->exit_type_variation($store_id, 1);
		}
		return true;
	}
	/**
	 *  delete order  
	 * @param   int $id   
	 * @return  bool       
	 */
	public function delete_order($id){
		$this->db->where('id',$id);
		$this->db->delete(db_prefix().'cart');
		if ($this->db->affected_rows() > 0) {
			$this->db->where('cart_id',$id);
			$this->db->delete(db_prefix().'cart_detailt');           
			return true;
		}
		return false;
	}

	/**
	 * get invoices goods delivery
	 * @return mixed 
	 */
	public function get_invoices_goods_delivery($type)
	{
		$this->db->where('type', $type);
		$goods_delivery_invoices_pr_orders = $this->db->get(db_prefix().'goods_delivery_invoices_pr_orders')->result_array();

		$array_id = [];
		foreach ($goods_delivery_invoices_pr_orders as $value) {
			array_push($array_id, $value['rel_type']);
		}

		return $array_id;

	}

	/**
		 * get invoices
		 * @param  boolean $id 
		 * @return array      
		 */
	public function  get_invoices($id = false)
	{

		if (is_numeric($id)) {
			$this->db->where('id', $id);

			return $this->db->get(db_prefix() . 'invoices')->row();
		}
		if ($id == false) {
			$arr_invoice = $this->get_invoices_goods_delivery('invoice');
			return $this->db->query('select * from tblinvoices where id NOT IN ("'.implode(", ", $arr_invoice).'") order by id desc')->result_array();
		}

	}



	/*
	*update for client kenya ------------------------------------------------------------------------------------------------------------------
	*/


/**
 * get max version omni customer report
 * @param  boolean $next_version 
 * @return [type]                
 */
public function get_max_version_omni_customer_report($next_version = false)
{
	$select_str = 'MAX(version) as version';
	$this->db->select($select_str);
	$pumsales_version = $this->db->get(db_prefix(). 'omni_customer_report')->row();

	if($next_version == false){
		if(isset($pumsales_version) && $pumsales_version->version != null){
			return $pumsales_version->version+1;
		}else{
			return 0;
		}
	}else{
		if(isset($pumsales_version) && $pumsales_version->version != null){
			return $pumsales_version->version;
		}else{
			return 0;
		}
	}

}


	/**
	 * get customer report
	 * @param  boolean $id 
	 * @return [type]      
	 */
	public function get_customer_report($id = false) {

		if (is_numeric($id)) {
			$this->db->where('id', $id);

			return $this->db->get(db_prefix() . 'omni_customer_report')->row();
		}
		if ($id == false) {
			return $this->db->query('select * from tblomni_customer_report')->result_array();
		}

	}


	/**
	 * update customer report
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_customer_report($data, $id) {

		$data_update=[];

		$data_update['pay_mode_id']    =$data['pay_mode_id'];
		$data_update['pay_mode']    = omni_sales_get_payment_name($data['pay_mode_id']);
		$data_update['ref_slip_no'] =$data['ref_slip_no'];
		$data_update['customer_id'] =$data['customer_id'];
		$data_update['payment_id'] =$data['payment_id'];

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'omni_customer_report', $data_update);

		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return true;
	}

	/**
	 * get distinct authorized customer report
	 * @return [type] 
	 */
	public function get_distinct_authorized_customer_report() {
		$sql_where = "SELECT distinct authorized_by FROM ".db_prefix()."omni_customer_report";
		return $this->db->query($sql_where)->result_array();

	}

	/**
	 * create report from transaction bulk action
	 * @param  [type] $ids 
	 * @return [type]      
	 */
	public function create_report_from_transaction_bulk_action($data)
	{

		$ids        = $data['customer_report_id'];
		$from_date  = $data['customer_report_from_date'];
		$to_date    = $data['customer_report_to_date'];
			//insert data to table omin_sales_create_customer_report
		$sql_where = " id  IN (" . $ids. ") ";

		$this->db->select(" sum(total_sale) as total_sale, sum(quantity) as total_quantity");
		$this->db->where($sql_where);
		$total_sales_data = $this->db->get(db_prefix() . 'omni_customer_report')->row();

		$create_customer_report_data=[];
		$create_customer_report_data['date_time_transaction'] = date('Y-m-d H:i:s');
		$create_customer_report_data['m_date_report'] = $from_date .' - '.$to_date;
		$create_customer_report_data['list_customer_report_id'] = $data['ids'];

		if($total_sales_data){
			$create_customer_report_data['m_total_amount'] = $total_sales_data->total_sale;
			$create_customer_report_data['m_total_quantity'] = $total_sales_data->total_quantity;
		}

		$this->db->insert(db_prefix().'omni_create_customer_report', $create_customer_report_data);
		$insert_id = $this->db->insert_id();

			//insert data to table omni_create_customer_report_detail
		$sql_where_1 = " id  IN (" . $ids. ") ";
		$this->db->select("authorized_by, product, sum(total_sale) as total_sale, pay_mode, shift_type");
		$this->db->where($sql_where_1);
		$this->db->group_by(array( "authorized_by", "product", "pay_mode", "shift_type"));
		$this->db->order_by('authorized_by', 'ASC');
		$this->db->order_by('shift_type', 'ASC');
		$report_data = $this->db->get(db_prefix() . 'omni_customer_report')->result_array();

		$data_insert_customer_report_detail=[];

		$data_temp_detail                   =[];
		$data_temp_detail['total_by_cash']  =0;
		$data_temp_detail['total_by_mpesa'] =0;
		$data_temp_detail['total_by_card']  =0;
		$data_temp_detail['total_by_invoice']   =0;
		$data_temp_detail['total_diesel']       =0;
		$data_temp_detail['total_pertrol']      =0;
		$data_temp_detail['total_other_product']=0;
		$data_temp_detail['total_sale']=0;

		$check_authorized_shift_type=[];

		foreach ($report_data as $report_data_key => $report_data_value) {
			$data_temp_detail['total_sale'] += (float)$report_data_value['total_sale'];

			switch ($report_data_value['pay_mode']) {
				case 'Cash':
				$data_temp_detail['total_by_cash'] += (float)$report_data_value['total_sale'];
				break;

				case 'Mobile':
				$data_temp_detail['total_by_mpesa'] += (float)$report_data_value['total_sale'];
				break;

				case 'Card':
				$data_temp_detail['total_by_card'] += (float)$report_data_value['total_sale'];
				break;

				case 'Invoice ':
				$data_temp_detail['total_by_invoice'] += (float)$report_data_value['total_sale'];
				break;

				default:
							# code...
				break;
			}

			switch ($report_data_value['product']) {
				case 'DX':
				$data_temp_detail['total_diesel'] += (float)$report_data_value['total_sale'];
				break;

				case 'ULX':
				$data_temp_detail['total_pertrol'] += (float)$report_data_value['total_sale'];
				break;

				default:
				$data_temp_detail['total_other_product'] += (float)$report_data_value['total_sale'];
				break;
			}


					//check create
			if(count($check_authorized_shift_type) == 0){
						//first value
				$check_authorized_shift_type['authorized_by']=$report_data_value['authorized_by'];
				$check_authorized_shift_type['shift_type']=$report_data_value['shift_type'];

			}


			if(count($report_data) != $report_data_key+1){
				if( ($check_authorized_shift_type['authorized_by'] != $report_data[$report_data_key+1]['authorized_by']) || ($check_authorized_shift_type['shift_type'] != $report_data[$report_data_key+1]['shift_type'])){

					array_push($data_insert_customer_report_detail, [
						'create_customer_report_id' => $insert_id,
						'date_add' => date('Y-m-d H:i:s'),
						'attendant_name' => $check_authorized_shift_type['authorized_by'],
						'shift_type' => $check_authorized_shift_type['shift_type'],
						'date_report' => $from_date .' - '.$to_date,
						'total_diesel' => $data_temp_detail['total_diesel'],
						'total_pertrol' => $data_temp_detail['total_pertrol'],
						'total_other_product' => $data_temp_detail['total_other_product'],
						'total_by_cash' => $data_temp_detail['total_by_cash'],
						'total_by_mpesa' => $data_temp_detail['total_by_mpesa'],
						'total_by_card' => $data_temp_detail['total_by_card'],
						'total_by_invoice' => $data_temp_detail['total_by_invoice'],
						'total_sales' => $data_temp_detail['total_sale'],
					]);

							//reset 
					$data_temp_detail                   =[];
					$data_temp_detail['total_by_cash']  =0;
					$data_temp_detail['total_by_mpesa'] =0;
					$data_temp_detail['total_by_card']  =0;
					$data_temp_detail['total_by_invoice']   =0;
					$data_temp_detail['total_diesel']       =0;
					$data_temp_detail['total_pertrol']      =0;
					$data_temp_detail['total_other_product']=0;
					$data_temp_detail['total_sale']=0;

					$check_authorized_shift_type=[];

				}
			}else{

				array_push($data_insert_customer_report_detail, [
					'create_customer_report_id' => $insert_id,
					'date_add' => date('Y-m-d H:i:s'),
					'attendant_name' => $check_authorized_shift_type['authorized_by'],
					'shift_type' => $check_authorized_shift_type['shift_type'],
					'date_report' => $from_date .' - '.$to_date,
					'total_diesel' => $data_temp_detail['total_diesel'],
					'total_pertrol' => $data_temp_detail['total_pertrol'],
					'total_other_product' => $data_temp_detail['total_other_product'],
					'total_by_cash' => $data_temp_detail['total_by_cash'],
					'total_by_mpesa' => $data_temp_detail['total_by_mpesa'],
					'total_by_card' => $data_temp_detail['total_by_card'],
					'total_by_invoice' => $data_temp_detail['total_by_invoice'],
					'total_sales' => $data_temp_detail['total_sale'],
				]);

			}

		}
		$this->db->insert_batch_on_duplicate(db_prefix().'omni_create_customer_report_detail', $data_insert_customer_report_detail);
		$insert_id_report_detail = $this->db->insert_id();
		return ['insert_id_report_detail' => $insert_id_report_detail, 'insert_id' => $insert_id];

	}


	/**
	 * get create customer report
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_create_customer_report($id)
	{
		$this->db->where('id', $id);
		return $this->db->get(db_prefix().'omni_create_customer_report')->row();
	}

	/**
	 * get list customer report by id
	 * @param  [type] $id 
	 * @return [type]     
	 */
	public function get_list_customer_report_by_id($id)
	{
		$list_customer_report_id = $this->get_create_customer_report($id);
		if($list_customer_report_id){
			$where = db_prefix()."omni_customer_report.id  IN ( " . $list_customer_report_id->list_customer_report_id. " ) ";

			$this->db->where($where);
			$this->db->order_by('authorized_by', 'ASC');
			$this->db->order_by('shift_type', 'ASC');
			$array_customer_report = $this->db->get(db_prefix().'omni_customer_report')->result_array();

			return $array_customer_report;
		}

		return [];
	}


	/**
	 * create_invoice_from_customer_report_bulk_action
	 * @param  [type] $ids 
	 * @return [type]      
	 */
	public function create_invoice_from_customer_report_bulk_action($ids)
	{


		$sql_where = " id  IN ( '" . implode( "', '" , $ids ) . "' ) ";


		$this->db->select("customer_id, authorized_by, product, sum(total_sale) as total_sale,sum(quantity) as quantity, pay_mode_id, date as _date");
		$this->db->where($sql_where);
		$this->db->group_by(array( "customer_id", "_date", "authorized_by", "product", "pay_mode_id"));
		$this->db->order_by('customer_id', 'ASC');
		$this->db->order_by('authorized_by', 'ASC');

		$invoice_data = $this->db->get(db_prefix() . 'omni_customer_report')->result_array();

		$data=[];
		$index = 1;

		$data_insert  =[];
		$check_staff_start_date_end_date=[];
		$arr_item   =[];
		$arr_item_update  =[];
		$total    = 0; 
		$subtotal   = 0;

		$customer_id  = 0;

		/*start*/
		$data_temp_detail                   =[];
		$data_temp_detail['total_by_cash']  =0;
		$data_temp_detail['total_by_mpesa'] =0;
		$data_temp_detail['total_by_card']  =0;
		$data_temp_detail['total_by_invoice']   =0;
		$data_temp_detail['total_diesel']       =0;
		$data_temp_detail['total_pertrol']      =0;
		$data_temp_detail['total_other_product']=0;
		$data_temp_detail['total_sale']=0;

		$check_authorized_shift_type=[];
		/*end*/

		foreach ($invoice_data as $key => $invoice_item) {

			$commodity_name='';

			switch ($invoice_item['product']) {
				case 'ULX':
				$commodity_name .= 'Petrol';
				break;

				case 'DX':
				$commodity_name .= 'Diesel ';
				break;
				
				default:
				$commodity_name .= $invoice_item['product'];
				break;
			}


			array_push($arr_item, [
				"order" => $index,
				"description" => $commodity_name ,
				"long_description" => 'Date: '.$invoice_item['_date'],
				"unit" => 'VOLUME',
				"rate" => round((float)$invoice_item['total_sale']/(float)$invoice_item['quantity'], 2),
				"qty" => $invoice_item['quantity'],
				"taxname" => '',
			]);

				//caculation subtotal
			$subtotal += (float)$invoice_item['total_sale'];


			//check create invoice
			if(count($check_staff_start_date_end_date) == 0){

					//first value
				$check_staff_start_date_end_date['customer_id']=$invoice_item['customer_id'];
				$check_staff_start_date_end_date['pay_mode_id']=$invoice_item['pay_mode_id'];
				$check_staff_start_date_end_date['_date']=$invoice_item['_date'];
				$check_staff_start_date_end_date['authorized_by']=$invoice_item['authorized_by'];

				if(isset($invoice_item['customer_id'])){

					$customer_id = $invoice_item['customer_id'];

				}else{
					$customer_id  = 0;
				}

					//add data to array
			}

			/*start*/
			switch ($this->omni_sales_get_payment_name($invoice_item['pay_mode_id'])) {
				case 'Cash':
				$data_temp_detail['total_by_cash'] += (float)$invoice_item['total_sale'];
				break;

				case 'Mobile':
				$data_temp_detail['total_by_mpesa'] += (float)$invoice_item['total_sale'];
				break;

				case 'Card':
				$data_temp_detail['total_by_card'] += (float)$invoice_item['total_sale'];
				break;

				case 'Invoice ':
				$data_temp_detail['total_by_invoice'] += (float)$invoice_item['total_sale'];
				break;

				default:
							# code...
				break;
			}

			switch ($invoice_item['product']) {
				case 'DX':
				$data_temp_detail['total_diesel'] += (float)$invoice_item['total_sale'];
				break;

				case 'ULX':
				$data_temp_detail['total_pertrol'] += (float)$invoice_item['total_sale'];
				break;

				default:
				$data_temp_detail['total_other_product'] += (float)$invoice_item['total_sale'];
				break;
			}


					//check create
			if(count($check_authorized_shift_type) == 0){
						//first value
				$check_authorized_shift_type['authorized_by']=$invoice_item['authorized_by'];
				$check_authorized_shift_type['shift_type']='';

			}

			/*end*/

			if(count($invoice_data) != $key+1){
				if( ($check_staff_start_date_end_date['customer_id'] != $invoice_data[$key+1]['customer_id']) || ($check_staff_start_date_end_date['authorized_by'] != $invoice_data[$key+1]['authorized_by'])  ){

					/*start*/
					$adminnote ='';
					$adminnote .= _l('authorized_by').': '.  $check_authorized_shift_type['authorized_by'] .'('._l($check_authorized_shift_type['shift_type']).') - '._l('diesel').': '.app_format_money((float) $data_temp_detail['total_diesel'],'').' - '. _l('pertrol').': '.app_format_money((float) $data_temp_detail['total_pertrol'],'') . ' - '. _l('other').': '.app_format_money((float) $data_temp_detail['total_other_product'],'').' - '. _l('cash').': '.app_format_money((float) $data_temp_detail['total_by_cash'],'') .' - ' ._l('mpesa').': '.app_format_money((float) $data_temp_detail['total_by_mpesa'],'') .' - '. _l('card').': '.app_format_money((float) $data_temp_detail['total_by_card'],'') . ' - '. _l('invoice').': '.app_format_money((float) $data_temp_detail['total_by_invoice'],'');
					/*end*/

					//create invoice
					$this->transaction_create_invoice($check_staff_start_date_end_date['authorized_by'], $arr_item, $subtotal, $customer_id, $check_staff_start_date_end_date['pay_mode_id'], $adminnote);

					//reset start
					$data_temp_detail                   =[];
					$data_temp_detail['total_by_cash']  =0;
					$data_temp_detail['total_by_mpesa'] =0;
					$data_temp_detail['total_by_card']  =0;
					$data_temp_detail['total_by_invoice']   =0;
					$data_temp_detail['total_diesel']       =0;
					$data_temp_detail['total_pertrol']      =0;
					$data_temp_detail['total_other_product']=0;
					$data_temp_detail['total_sale']=0;

					$check_authorized_shift_type=[];
					//reset end

					//reset params after create invoice
					$check_staff_start_date_end_date=[];
					$arr_item   =[];
					$total    = 0; 
					$subtotal   = 0;
					$index    =1;

					$customer_id    =0;

				}
			}else{
				/*start*/
				$adminnote ='';
				$adminnote .= _l('authorized_by').': '.  $check_authorized_shift_type['authorized_by'] .'('._l($check_authorized_shift_type['shift_type']).') - '._l('diesel').': '.app_format_money((float) $data_temp_detail['total_diesel'],'').' - '. _l('pertrol').': '.app_format_money((float) $data_temp_detail['total_pertrol'],'') . ' - '. _l('other').': '.app_format_money((float) $data_temp_detail['total_other_product'],'').' - '. _l('cash').': '.app_format_money((float) $data_temp_detail['total_by_cash'],'') .' - ' ._l('mpesa').': '.app_format_money((float) $data_temp_detail['total_by_mpesa'],'') .' - '. _l('card').': '.app_format_money((float) $data_temp_detail['total_by_card'],'') . ' - '. _l('invoice').': '.app_format_money((float) $data_temp_detail['total_by_invoice'],'');
				/*end*/


				//last item
				//create invoice
				$this->transaction_create_invoice($check_staff_start_date_end_date['authorized_by'], $arr_item, $subtotal, $customer_id, $check_staff_start_date_end_date['pay_mode_id'], $adminnote);
			}

			$index++;

		}
		return true;

	}

	public function transaction_create_invoice($staffname, $arr_items, $subtotal, $customer_id, $payments_model_id, $adminnote)
	{

		$data=[];
		$this->load->model('clients_model');
		$this->load->model('currencies_model');
		$this->load->model('invoices_model');
		$this->load->model('staff_model');
		$this->load->model('payments_model');

			//get sale agent from nam
		$sale_agent = $this->omni_sales_get_staffid_by_name($staffname);

		//get base_currency
		$base_currency = $this->currencies_model->get_base_currency();

		 //get customer_id start
		if(isset($customer_id) && $customer_id != 0){
			$customer_id = $customer_id;
		}else{
			//get customer from athorized by
			$customer_id = $this->omni_sales_get_customer_id_by_name($staffname);

		}
		 //get customer_id end
		$data['clientnote'] = $adminnote. ' - '. _l('total_sale').': '.app_format_money((float) $subtotal,'');
		$data['cancel_merged_invoices'] ='on';
		$data['clientid'] = $customer_id;
		$data['project_id'] = '';

		$data['billing_street'] ='';
		$data['billing_city'] = '';
		$data['billing_state'] = '';
		$data['billing_zip'] = '';
		$data['billing_country'] = '';
		$data['include_shipping'] = 'on';
		$data['show_shipping_on_invoice'] = 'on';
		$data['shipping_street'] = '';
		$data['shipping_city'] = '';
		$data['shipping_state'] = '';
		$data['shipping_zip'] = '';
		$data['shipping_country'] = '';

		$data['number'] = get_option('next_invoice_number');
		$data['date'] = _d(date('Y-m-d'));
		$data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
		$data['allowed_payment_modes'] = array( 0 => $payments_model_id );
		$data['tags'] = '';
		$data['currency'] = $base_currency->id;
		$data['sale_agent'] = $sale_agent;
		$data['recurring'] = '0';
		$data['repeat_every_custom'] = '1';
		$data['repeat_type_custom'] = 'day';
		$data['adminnote'] =  '';
		$data['item_select'] = '';
		$data['task_select'] = '';
		$data['show_quantity_as'] = '';
		$data['description'] = '';
		$data['long_description'] = '';
		$data['quantity'] = '1';
		$data['unit'] = '';
		$data['rate'] = '';
		$data['taxname'] = 'TAXT 10|10.00';

				//
		$data['adjustment'] = '0' ;
		$data['task_id'] = '' ;
		$data['expense_id'] = '' ;
		$data['terms'] = '';

		$data['discount_percent'] = '0'; 
		$data['discount_type'] = '0';
		$data['discount_total'] = '0.00'; 

		$data['total'] = $subtotal; 
		$data['subtotal'] = $subtotal; 

		$data['newitems'] = $arr_items;
			//insert to data base 
		$insert_id = $this->invoices_model->add($data);
		if ($insert_id) {
			return true;
		}
		return false;

	}

		/**
		 * get default payment modes
		 * @return [type] 
		 */
		public function get_default_payment_modes()
		{ 
			$this->load->model('payment_modes_model');
			$payment_modes = $this->payment_modes_model->get('', [
				'expenses_only !=' => 1,
			]);

			$payment_modes_id = '';
			foreach ($payment_modes as $key => $value) {
				if( ($payment_modes_id == '') && ($value['selected_by_default'] == 1) ){
					$payment_modes_id = $value['id'];
				}
			}
			return $payment_modes_id;
		}

		/**
		 * check payment mode exist
		 * @param  [type] $name 
		 * @return [type]       
		 */
		public function check_payment_mode_exist($name)
		{

			$this->db->where('name', $name);
			$payment_modes_value = $this->db->get(db_prefix().'payment_modes')->row();

			if($payment_modes_value){
				return $payment_modes_value->id;
			}else{
					//create tax if not exist
				$data['name']                   = trim($name);
				$data['show_on_pdf']            = 0;
				$data['invoices_only']          = 0;
				$data['expenses_only']          = 0;
				$data['selected_by_default']    = 0;
				$data['active']                 = 1;

				$this->db->insert(db_prefix().'payment_modes', $data);
				$insert_id = $this->db->insert_id();
				if ($insert_id) {
					log_activity('New payment_modes Added [ID: ' . $insert_id . ', ' . $data['name'] . ']');
					return $insert_id;
				}
			}

		}

		/**
		 * omni sales get staffid by name
		 * @param  string $value 
		 * @return [type]        
		 */
		public function omni_sales_get_staffid_by_name($staff_name)
		{
			$sql_where = "select CONCAT(firstname, ' ', lastname) as full_name, staffid from ".db_prefix()."staff where CONCAT(firstname, ' ', lastname) LIKE '%".$staff_name."%'";
			$staff_value = $this->db->query($sql_where)->row();
			if($staff_value){
				return $staff_value->staffid;
			}else{
				return 0;
			}
		}


		/**
		 * omni sales get customer id by name
		 * @param  [type] $staff_name 
		 * @return [type]             
		 */
		public function omni_sales_get_customer_id_by_name($staff_name)
		{
			$sql_where = "select userid from ".db_prefix()."clients where company LIKE '%".$staff_name."%'";
			$customer_value = $this->db->query($sql_where)->row();
			if($customer_value){
				return $customer_value->userid;
			}else{
				return 0;
			}
		}

		/**
		 * omni_sales_get_payment_name
		 * @param  [type] $id 
		 * @return [type]     
		 */
		public function omni_sales_get_payment_name($id){
			$payment_name ='';
			$this->db->where('id', $id);
			$payment = $this->db->get(db_prefix().'payment_modes')->row();
			if($payment){
				$payment_name .= $payment->name;
			}
			return $payment_name;

		}


		/**
		 * get distin version import transaction
		 * @return [type] 
		 */
		public function get_distinct_version_import_transaction() {

			$sql_where = "SELECT distinct version FROM ".db_prefix()."pump_sales order by version desc";

			return $this->db->query($sql_where)->result_array();

		}

		/**
	 * get staff
	 * @param  string $id
	 * @param  array  $where
	 * @return array or object
	 */
		public function get_staff($id = '', $where = []) {
			$select_str = '*,CONCAT(firstname," ",lastname) as full_name';

		// Used to prevent multiple queries on logged in staff to check the total unread notifications in core/AdminController.php
			if (is_staff_logged_in() && $id != '' && $id == get_staff_user_id()) {
				$select_str .= ',(SELECT COUNT(*) FROM ' . db_prefix() . 'notifications WHERE touserid=' . get_staff_user_id() . ' and isread=0) as total_unread_notifications, (SELECT COUNT(*) FROM ' . db_prefix() . 'todos WHERE finished=0 AND staffid=' . get_staff_user_id() . ') as total_unfinished_todos';
			}

			$this->db->select($select_str);
			$this->db->where($where);

			if (is_numeric($id)) {
				$this->db->where('staffid', $id);
				$staff = $this->db->get(db_prefix() . 'staff')->row();

				if ($staff) {
					$staff->permissions = $this->get_staff_permissions($id);
				}

				return $staff;
			}
			$this->db->order_by('firstname', 'desc');

			return $this->db->get(db_prefix() . 'staff')->result_array();
		}


	/**
		 * get mpesatransc
		 * @param  boolean $id 
		 * @return [type]      
		 */
	public function get_mpesatransc($id = false) {

		if (is_numeric($id)) {
			$this->db->where('id', $id);

			return $this->db->get(db_prefix() . 'mpesatransc')->row();
		}
		if ($id == false) {
			return $this->db->query('select * from tblmpesatransc')->result_array();
		}

	}

	/**
	 * update mpesatransc
	 * @param  [type] $data 
	 * @param  [type] $id   
	 * @return [type]       
	 */
	public function update_mpesatransc($data, $id) {

		$data_update=[];
		$data_update['customer_id']    =$data['customer_id'];
		$data_update['staffname']    =$data['staffname'];
		$data_update['trans_amount']    =omni_sales_reformat_currency_j($data['trans_amount']);

		$this->db->where('id', $id);
		$this->db->update(db_prefix() . 'mpesatransc', $data);

		if ($this->db->affected_rows() > 0) {
			return true;
		}

		return true;
	}


	/**
	 * get_mpesatransc_grouped
	 * @param  [type] $data 
	 * @return [type]       
	 */
	public function get_mpesatransc_grouped($data)
	{
		$trans_type ='';
		$trans_time ='';
		$first_name ='';
		$middle_name ='';
		$last_name ='';
		$bill_ref_number ='';
		$short_code ='';
		$sale_id ='';
		$pumpId ='';
		$employee_name ='';
		$mpesaType ='';

		$sql_where ='';
		if(isset($data['phone']) && $data['phone'] != ''){
			if ($sql_where == '') {
				$sql_where .= " phone = '".$data['phone']."'"; 
			}else{
				$sql_where .= " AND phone = '".$data['phone']."'"; 
			}
		}

		if(isset($data['date']) && $data['date'] != ''){
			if ($sql_where == '') {
				$sql_where .= " date_format(trans_date, '%Y-%m-%d') = '".$data['date']."'"; 
			}else{
				$sql_where .= " AND date_format(trans_date, '%Y-%m-%d') = '".$data['date']."'"; 
			}
		}

		if(isset($data['staffname']) && $data['staffname'] != ''){
			if ($sql_where == '') {
				$sql_where .= " staffname = '".$data['staffname']."'"; 
			}else{
				$sql_where .= " AND staffname = '".$data['staffname']."'"; 
			}
		}elseif($data['staffname'] == null){
			if ($sql_where == '') {
				$sql_where .= " staffname is null"; 
			}else{
				$sql_where .= " AND staffname is null"; 
			}
		}


		$this->db->where($sql_where);
		$mpesatransc_data = $this->db->get(db_prefix() . 'mpesatransc')->result_array();
		$mpesatransc_data_lenght = count($mpesatransc_data);

		if($mpesatransc_data_lenght == 0){
					// = 0
			$trans_type ='';
			$trans_time ='';
			$first_name ='';
			$middle_name ='';
			$last_name ='';
			$bill_ref_number ='';
			$short_code ='';
			$sale_id ='';
			$pumpId ='';
			$employee_name ='';
			$mpesaType ='';
			$staffname ='';

		}elseif($mpesatransc_data_lenght == 1){
					// = 1
					// 

			$trans_type =$mpesatransc_data[0]['trans_type'];
			$trans_time =$mpesatransc_data[0]['trans_time'];
			$first_name =$mpesatransc_data[0]['first_name'];
			$middle_name =$mpesatransc_data[0]['middle_name'];
			$last_name =$mpesatransc_data[0]['last_name'];
			$bill_ref_number =$mpesatransc_data[0]['bill_ref_number'];
			$short_code =$mpesatransc_data[0]['short_code'];
			$sale_id =$mpesatransc_data[0]['sale_id'];
			$pumpId =$mpesatransc_data[0]['pumpId'];
			$employee_name =$mpesatransc_data[0]['employee_name'];
			$mpesaType =$mpesatransc_data[0]['mpesaType'];
			$staffname =$mpesatransc_data[0]['staffname'];

		}else{
			if(isset($data['flag_date']) && $data['flag_date'] != ''){


						// >= 2
				$first_value = reset($mpesatransc_data);
				$last_value = array_pop($mpesatransc_data);


				$trans_type =$first_value['trans_type'];
				$trans_time =$first_value['trans_time']. ' - '.$last_value['trans_time'];
				$first_name ='';
				$middle_name ='';
				$last_name ='';
				$bill_ref_number =$first_value['bill_ref_number'];
				$short_code =$first_value['short_code'];
				$sale_id =$first_value['sale_id'];
				$pumpId =$first_value['pumpId'];
				$employee_name =$first_value['employee_name'];
				$mpesaType =$first_value['mpesaType'];
			}else{

						// >= 2
				$first_value = reset($mpesatransc_data);
				$last_value = array_pop($mpesatransc_data);


				$trans_type =$first_value['trans_type'];
				$trans_time =$first_value['trans_time']. ' - '.$last_value['trans_time'];
				$first_name =$first_value['first_name'];
				$middle_name =$first_value['middle_name'];
				$last_name =$first_value['last_name'];
				$bill_ref_number =$first_value['bill_ref_number'];
				$short_code =$first_value['short_code'];
				$sale_id =$first_value['sale_id'];
				$pumpId =$first_value['pumpId'];
				$employee_name =$first_value['employee_name'];
				$mpesaType =$first_value['mpesaType'];
			}
		}

		$data=[];

		$data['trans_type'] = $trans_type;
		$data['trans_time'] = $trans_time;
		$data['first_name'] = $first_name;
		$data['middle_name'] = $middle_name;
		$data['last_name'] = $last_name;
		$data['bill_ref_number'] = $bill_ref_number;
		$data['short_code'] = $short_code;
		$data['sale_id'] = $sale_id;
		$data['pumpId'] = $pumpId;
		$data['employee_name'] = $employee_name;
		$data['mpesaType'] = $mpesaType;

		return $data;
	}
	/**
	 * [init_connect_woocommerce description]
	 * @param  [type] $store_id [description]
	 * @return [type]           [description]
	 */
	public function init_connect_woocommerce($store_id){
		$channel =  $this->get_woocommere_store($store_id);
		$consumer_key = $channel->consumer_key;
		$consumer_secret = $channel->consumer_secret;
		$url = $channel->url;
		$woocommerce = new Client(
			$url, 
			$consumer_key, 
			$consumer_secret,
			[
				'wp_api' => true,
				'version' => 'wc/v3',
				'query_string_auth' => true,
				'timeout' => (40*60*1000)
			]
		);
		return $woocommerce;
	}
	/**
	 * [add_product_variable description]
	 * @param [type] $data [description]
	 */
	public function add_product_variable($data){
		$this->load->model('warehouse/warehouse_model');
		$this->load->model('misc_model');
		$purchase_price = $this->caculator_purchase_price($data['rate']);
		$data_variable = [
			"commodity_code" => $data['commodity_code'],
			"description" => $data['description'],
			"commodity_barcode" => $data['commodity_barcode'],
			"sku_code" => $data['sku_code'],
			"sku_name" => $data['sku_name'],
			"commodity_type" => $data['commodity_type'],
			"unit_id" => $data['unit_id'],
			"group_id" => $data['group_id'],
			"rate" => $data['rate'],
			"tax" => $data['tax'],
			"profif_ratio" => $data['profif_ratio'],
			"origin" => $data['origin'],
			"style_id" => $data['style_id'],
			"model_id" => $data['model_id'],
			"size_id" => $data['size_id'],
			"color" => $data['color'],
			"guarantee" => $data['guarantee'],
			"long_descriptions" => $data['long_descriptions'],
			"parent_id" => $data['parent_id'],
			"attributes" => $data['attributes'],
			"without_checking_warehouse"=> "0",
			"purchase_price" => $purchase_price
		];
		//insert item
		
		$idw = $this->warehouse_model->add_commodity_one_item($data_variable);
		$ids = $idw['insert_id'];

		// create image product sync from store to crm
		if(!empty($data['images'])){
			$url_to_image = $data['images']->src;
			$my_save_dir = 'modules/warehouse/uploads/item_img/'.$ids.'/';
			$filename = basename($url_to_image);

			$filename = explode('?',$filename)[0];

			$complete_save_loc = $my_save_dir.$filename;

			_maybe_create_upload_path($my_save_dir);
			if(file_put_contents($complete_save_loc,file_get_contents($url_to_image))){
				$filetype = array(
					'jpg' => 'image/jpeg',
					'png' => 'image/png',
					'gif' => 'image/gif',
				);

				$attachment   = [];
				if(isset($filetype[pathinfo($data['images']->src, PATHINFO_EXTENSION)])){
					$attachment[] = [
						'file_name' => $filename,
						'filetype'  => $filetype[pathinfo($data['images']->src, PATHINFO_EXTENSION)],
					];
				}else{

					$f_type = explode("?",pathinfo($data['images']->src, PATHINFO_EXTENSION));
					if(isset($filetype[$f_type[0]])){
						$attachment[] = [
							'file_name' => $filename,
							'filetype'  => $filetype[$f_type[0]],
						];
					}
				}
				$this->misc_model->add_attachment_to_database($ids, 'commodity_item_file', $attachment);
			}
		}  
		//create inventory 
		$data_inventory['warehouse_id'] = $data['omni_warehouse'];
		$data_inventory['commodity_id'] = $ids;
		$data_inventory['quantities'] = $data['quantities'];
		$this->omnisales_add_inventory_manage($data_inventory, 1, false);
	}
	/**
	 * [caculator_purchase_price description]
	 * @param  [type] $sale_price [description]
	 * @return [type]             [description]
	 */
	public function caculator_purchase_price($sale_price)
	{
		$data = $this->input->post();
		$purchase_price = 0;

		/*type : 0 purchase price, 1: sale price*/
		$profit_type = get_warehouse_option('profit_rate_by_purchase_price_sale');
		$the_fractional_part = get_warehouse_option('warehouse_the_fractional_part');
		$integer_part = get_warehouse_option('warehouse_integer_part');

		$profit_rate = get_warehouse_option('warehouse_selling_price_rule_profif_ratio');

		switch ($profit_type) {
			case '0':
					# Calculate the selling price based on the purchase price rate of profit
					# sale price = purchase price * ( 1 + profit rate)
			if( ($profit_rate =='') || ($profit_rate == '0')|| ($profit_rate == 'null') ){
				$purchase_price = (float)$sale_price;

			}else{
				$purchase_price = (float)$sale_price/(1+((float)$profit_rate/100));

			}
			break;

			case '1':
					# Calculate the selling price based on the selling price rate of profit
					# sale price = purchase price / ( 1 - profit rate)
			if( ($profit_rate =='') || ($profit_rate == '0')|| ($profit_rate == 'null') ){
				$purchase_price = (float)$sale_price;
			}else{

				$purchase_price = (float)$purchase_price*(1-((float)$profit_rate/100));

			}
			break;

		}

			//round purchase_price
		$purchase_price = round($purchase_price, (int)$the_fractional_part);

		if($integer_part != '0'){
			$integer_part = 0 - (int)($integer_part);
			$purchase_price = round($purchase_price, $integer_part);
		}

		return $purchase_price;
	}

		/**
	 * omnisales get warehouse 
	 * @param  boolean $id
	 * @return array or object
	 */
		public function omnisales_get_warehouse($id = false) {

			if (is_numeric($id)) {
				$this->db->where('warehouse_id', $id);
				return $this->db->get(db_prefix() . 'warehouse')->row();
			}
			if ($id == false) {
				return $this->db->query('select * from tblwarehouse')->result_array();
			}

		}


		/**
	 * omnisales add inventory manage
	 * @param array $data
	 * @param string $status
	 */
		public function omnisales_add_inventory_manage($data, $status = 1, $update_new = flase) {
			// status = 1 create inventory
			// status = 2 update inventory
			if($status == 1 && $update_new == false){

				//insert
				$data_insert['warehouse_id'] = $data['warehouse_id'];
				$data_insert['commodity_id'] = $data['commodity_id'];
				$data_insert['inventory_number'] = $data['quantities'];
				$data_insert['lot_number'] = "";
				
				$this->db->insert(db_prefix() . 'inventory_manage', $data_insert);
				$insert_id = $this->db->insert_id();
				return;
			}

			if($status == 2 && $update_new == false){

				// Inventory number < Quantities Woo 
				if($data['quantities'] > $data['inventory_total']){
					$this->db->where('warehouse_id', $data['warehouse_id']);
					$this->db->where('commodity_id', $data['commodity_id']);
					$inventory_manage = $this->db->get(db_prefix() . 'inventory_manage')->row();

					if($inventory_manage){
						$inventory_number_change = (int) $data['quantities'] - (int) $data['inventory_total'];

						$update_id = $inventory_manage->id;
						//Goods receipt
						$data_update['inventory_number'] = (int) $inventory_number_change + (int) $inventory_manage->inventory_number;

						//update
						$this->db->where('id', $update_id);
						$this->db->update(db_prefix() . 'inventory_manage', $data_update);
						return true;
					}
				} 
				// Inventory number > Quantities Woo

				if($data['quantities'] < $data['inventory_total']){
					$this->db->where('commodity_id', $data['commodity_id']);

					$results = $this->db->get('tblinventory_manage')->result_array();

					$inventory_number_change = (int) $data['inventory_total'] - (int) $data['quantities'];

					if($inventory_number_change == 0){
						return true;
					} 

					if(count($results) > 0){
						foreach ($results as $key_results => $result) {

							// Inventory number < Quantities change
							if($result['inventory_number'] < $inventory_number_change && $inventory_number_change > 0){
								$inventory_number_change = (int) $inventory_number_change - (int) $result['inventory_number'];
								$data_update['inventory_number'] = 0;
								$this->db->where('id', $result['id']);
								$this->db->update(db_prefix() . 'inventory_manage', $data_update);
							// Inventory number > Quantities change
							}else if($result['inventory_number'] > $inventory_number_change && $inventory_number_change > 0){
								$data_update['inventory_number'] = (int) $result['inventory_number'] - (int) $inventory_number_change;
								$this->db->where('id', $result['id']);
								$this->db->update(db_prefix() . 'inventory_manage', $data_update);
							}

						}

					}
				}
				return true;
			}

			if($status == 2 && $update_new == true){
				//insert if haven't warehouse
				$data_insert['warehouse_id'] = $data['warehouse_id'];
				$data_insert['commodity_id'] = $data['commodity_id'];
				$data_insert['inventory_number'] = $data['quantities'];
				$this->db->insert(db_prefix() . 'inventory_manage', $data_insert);
				$insert_id = $this->db->insert_id();
				return;
			}
			return true;
		}
	/**
	 * [update_product_variable description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	public function update_product_variable($data){
		$this->db->where('sku_code', $data['sku_code']);
		$item_ = $this->db->get(db_prefix() . 'items')->row();
		$this->load->model('warehouse/warehouse_model');
		$this->load->model('misc_model');
		$purchase_price = $this->caculator_purchase_price($data['rate']);
		$data_variable = [
			"commodity_code" => $data['commodity_code'],
			"description" => $data['description'],
			"sku_code" => $data['sku_code'],
			"sku_name" => $data['sku_name'],
			"commodity_type" => $data['commodity_type'],
			"unit_id" => $data['unit_id'],
			"group_id" => $data['group_id'],
			"rate" => $data['rate'],
			"tax" => $data['tax'],
			"profif_ratio" => $data['profif_ratio'],
			"origin" => $data['origin'],
			"style_id" => $data['style_id'],
			"model_id" => $data['model_id'],
			"size_id" => $data['size_id'],
			"color" => $data['color'],
			"guarantee" => $data['guarantee'],
			"long_descriptions" => $data['long_descriptions'],
			"parent_id" => $data['parent_id'],
			"attributes" => $data['attributes'],
			"without_checking_warehouse"=> "0",
			"purchase_price" => $purchase_price
		];
		//update item
		$this->db->where('sku_code', $data['sku_code']);
		$this->db->update(db_prefix() . 'items', $data_variable);
		
		//get all id image of item
		$images_items = $this->get_image_id($item_->id);

		//delete all image of ite,
		if(count($images_items) > 0){
			foreach ($images_items as $images_item) {
				$this->warehouse_model->delete_commodity_file($images_item);
			}
		}

		// create image product sync from store to crm
		if(!empty($data['images'])){
			foreach ($data['images'] as $image) {
				$url_to_image = $data['images']->src;
				$my_save_dir = 'modules/warehouse/uploads/item_img/'.$item_->id.'/';
				$filename = basename($url_to_image);

				$filename = explode('?',$filename)[0];

				$complete_save_loc = $my_save_dir.$filename;

				_maybe_create_upload_path($my_save_dir);
				if(file_put_contents($complete_save_loc,file_get_contents($url_to_image))){
					$filetype = array(
						'jpg' => 'image/jpeg',
						'png' => 'image/png',
						'gif' => 'image/gif',
					);

					$attachment   = [];
					if(isset($filetype[pathinfo($data['images']->src, PATHINFO_EXTENSION)])){
						$attachment[] = [
							'file_name' => $filename,
							'filetype'  => $filetype[pathinfo($data['images']->src, PATHINFO_EXTENSION)],
						];
					}else{

						$f_type = explode("?",pathinfo($data['images']->src, PATHINFO_EXTENSION));
						if(isset($filetype[$f_type[0]])){
							$attachment[] = [
								'file_name' => $filename,
								'filetype'  => $filetype[$f_type[0]],
							];
						}
					}
					$this->misc_model->add_attachment_to_database($item_->id, 'commodity_item_file', $attachment);
				}
			}
		}  

		//------------------start update inventory-------------------------------------
		$this->db->where('commodity_id', $item_->id);
		$check_inventory_exits = $this->db->get(db_prefix().'inventory_manage')->result_array();
			//value inventory 

		if(count($check_inventory_exits) > 0){
				//init inventory total
			$inventory_total = 0;
			foreach ($check_inventory_exits as $key_check_inventory_exit => $value_check_inventory_exit) {
					//sum inventory total
				$inventory_total += $value_check_inventory_exit['inventory_number'];
			}
			$check_inventory_exits_one = $this->db->get(db_prefix().'inventory_manage')->result_array();

			foreach ($check_inventory_exits_one as $key_check_inventory_exit_one => $value_check_inventory_exit_one) {
				$data_inventory['warehouse_id'] = $value_check_inventory_exit_one['warehouse_id'];
				$data_inventory['commodity_id'] = $item_->id;
				$data_inventory['inventory_total'] = $inventory_total;
				$data_inventory['quantities'] = $data['quantities'];
				$rs = $this->omnisales_add_inventory_manage($data_inventory, 2, false);
				if($rs){
					break;
				}
			}
		}else{
					//create new if haven't warehouse
			$data_inventory['warehouse_id'] = $data['omni_warehouse'];
			$data_inventory['commodity_id'] = $item_->id;
			$data_inventory['quantities'] = $data['quantities'];
			$data_inventory['inventory_total'] = 0;

			$this->omnisales_add_inventory_manage($data_inventory, 2, true);
		}
		//------------------end update inventory-------------------------------------
	}
	/**
	 * [get_image_id description]
	 * @param  [type] $item_id [description]
	 * @return [type]          [description]
	 */
	public function get_image_id($item_id){
		$this->db->where('rel_id',$item_id);
		$this->db->where('rel_type','commodity_item_file');
		$this->db->select('id');
		return $this->db->get(db_prefix().'files')->result_array();
	}
	/**
	 * get item have variation
	 * @return array
	 */
	public function get_item_have_variation(){
		$data = $this->db->query('select DISTINCT (parent_id) as ids,(select sku_code FROM ' . db_prefix() . 'items where id = ids) as sku_code_s from ' . db_prefix() . 'items where parent_id != "" or parent_id != 0 or parent_id IS NOT NULL')->result_array();
		$result['ids'] = [];
		$result['sku_code_s'] = [];
		if(count($data) > 0){
			foreach ($data as $key => $value) {
				$result['ids'][] =  $value['ids'];
				$result['sku_code_s'][] =  $value['sku_code_s'];
			}
		}
		return $result;
	}

	/**
	 *  get product not parent id  
	 * @return array        
	 */
	public function get_product_parent_id(){
		$this->db->where('parent_id IS NULL or parent_id = "" or parent_id = 0');
		return $this->db->get(db_prefix().'items')->result_array();
	}
	/**
	 * exit type variation
	 * @param  $store_id 
	 * @return          
	 */
	public function exit_type_variation($store_id, $status = 1){
		//status = 1 create, 2 update
		$woocommerce = $this->init_connect_woocommerce($store_id);

		//get all products have variation include ids and sku_codes 
		$products_variation = $this->get_item_have_variation();
		//get all product form woo
		$per_page = 100;
		$products_store = [];
		$products_store_variable = [];
		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);
			if(count($list_products) > 0){
				foreach ($list_products as $key => $value) {
					if($value->type == "variable"){
						array_push($products_store_variable, $value);
					}
				}
			}
			$products_store = array_merge($products_store, $list_products);
			
			if(count($list_products) < $per_page){
				break;
			}
		}
		$products_attributes = $woocommerce->get('products/attributes');
		//get name attr 
		$slug_attributes = [];
		$name_attributes = [];
		$id_attributes = [];

		if(count($products_attributes) > 0){
			foreach ($products_attributes as $key_products_attributes => $value_products_attributes) {
				array_push($slug_attributes, $value_products_attributes->slug);
				array_push($name_attributes, $value_products_attributes->name);
				array_push($id_attributes, $value_products_attributes->id);
			}
		}
		//get sku and id have type "variable"
		$arr_product_store = [];
		$arr_product_id_store = [];

		//# variable
		$arr_product_store_ = [];
		$arr_product_id_store_ = [];
		foreach ($products_store as $key => $value) {
			if($value->type == 'variable'){
				array_push($arr_product_store, $value->sku);
				array_push($arr_product_id_store, $value->id);
			}else{
				array_push($arr_product_store_, $value->sku);
				array_push($arr_product_id_store_, $value->id);
			}

		}
		if(count($arr_product_store) > 0){
			foreach ($arr_product_store as $key_arr_product_store => $value_arr_product_store) {
				$inventory_update = 0;
				if(in_array($value_arr_product_store, $products_variation['sku_code_s'])){
						//get id by index of array $products_variation['sku_code_s']
					$index = array_search($value_arr_product_store , $products_variation['sku_code_s'], true);

					$this->db->where('parent_id', $products_variation['ids'][$index]);

					$variations = $this->db->get(db_prefix().'items')->result_array();

					$data = [];

					foreach ($variations as $key_variations => $value_variations) {
						$attributes = [];

							//quantity stock
						$stock_quantity = $this->get_total_inventory_commodity($value_variations['id']); 

							//get image product
						if($this->get_all_image_file_name($value_variations['id'])){
							$file_name = $this->get_all_image_file_name($value_variations['id']);
						}

						$images = [];

						if(isset($file_name)){
							foreach ($file_name as $k => $name) {
								array_push($images, array('src' => site_url('modules/warehouse/uploads/item_img/'.$value_variations['id'].'/'.$name['file_name'])));
							}
						}

							//get attr variable product
						if($value_variations['attributes']  != null || $value_variations['attributes']  != ''){
							$variations_attributes = json_decode($value_variations['attributes']);
						}else{
							$variations_attributes = [];
						}

						if(count($variations_attributes) > 0){
							foreach ($variations_attributes as $key_variations_attributes => $value_variations_attributes) {
								$cus_attributes = $this->vn_to_str($value_variations_attributes->name);
								$cus_attributes = strtolower($this->clean($value_variations_attributes->name));
								$cus_attributes = "pa_" . $cus_attributes;
										//check in_array exit in slug 
								if(in_array($cus_attributes, $slug_attributes)){
									$index_exit_attr_variable = array_search($cus_attributes, $slug_attributes, true);
									$attributes[] = [
										"id" => $id_attributes[$index_exit_attr_variable],
										"name" => $name_attributes[$index_exit_attr_variable],
										"option" => $value_variations_attributes->option
									]; 
								}
							}
						}

						$inventory_update +=  (int) $stock_quantity->inventory_number;
						if($status == 1){
							$data[] = [
								'regular_price' => $value_variations['rate'],
								'price' => $value_variations['rate'],
								'stock_quantity' => $stock_quantity->inventory_number,
								'manage_stock' => true,
								'sku' => $value_variations['sku_code'],
								'image' => $images,
								'description' => ($value_variations['long_descriptions'] == null ? "" : $value_variations['long_descriptions']) ,
								'attributes' => $attributes,
								"backorders" => "yes",
								"backorders_allowed" => true,
								"backordered" => false
							];  
						}else{
							$variation_product_value = $woocommerce->get('products/'.$arr_product_id_store[$key_arr_product_store].'/variations');

							if(count($variation_product_value) > 0){
								foreach ($variation_product_value as $key_variation_product_value => $value_variation_product_value) {
									if($value_variation_product_value->sku == $value_variations['sku_code']){
										$data[] = [
											'id' => $value_variation_product_value->id,
											'regular_price' => $value_variations['rate'],
											'price' => $value_variations['rate'],
											'stock_quantity' => $stock_quantity->inventory_number,
											'manage_stock' => true,
											'sku' => $value_variations['sku_code'],
											'image' => $images,
											'description' => ($value_variations['long_descriptions'] == null ? "" : $value_variations['long_descriptions']) ,
											'attributes' => $attributes,
											"backorders" => "yes",
											"backorders_allowed" => true,
											"backordered" => false
										]; 
									}
								}
							}
						}

					}

					if($status == 1){
						$data_variations = [
							'create' => $data
						];
					}else{
						$data_variations = [
							'update' => $data
						];
					}

					$data_update = [
						'stock_quantity' => $inventory_update
					];

						//check and insert product variable 
					if(count($products_store_variable) > 0){
						foreach ($products_store_variable as $key => $value_products_variation) {
							if($value_products_variation->sku == $products_variation['sku_code_s'][$index]){
								$woocommerce->post('products/'.$value_products_variation->id.'/variations/batch', $data_variations);
									//update inventory product parent variable
								$woocommerce->post('products/'.$value_products_variation->id, $data_update);
							}
						}
					}

				}
			}
		}

	}
	/**
	 * [update_inventory_product_variation_parent description]
	 * @param  [type] $store_id [description]
	 * @param  [type] $sku_code [description]
	 * @return [type]           [description]
	 */
	public function update_inventory_product_variation_parent($store_id, $sku_code, $stock_quantity){
		$woocommerce = $this->init_connect_woocommerce($store_id);

		//get all products have variation include ids and sku_codes 
		$products_variation = $this->get_item_have_variation();
		//get all product form woo
		$per_page = 100;
		$products_store = [];
		$products_store_variable = [];
		for($page = 1; $page <= 100; $page++ ){
			$offset = ($page - 1) * $per_page;
			$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);
			if(count($list_products) > 0){
				foreach ($list_products as $key => $value) {
					if($value->type == "variable"){
						array_push($products_store_variable, $value);
					}
				}
			}
			$products_store = array_merge($products_store, $list_products);
			
			if(count($list_products) < $per_page){
				break;
			}
		}

		$sku_code_s = [];
		$ids = [];

		//all sku and id porduct variation
		if(count($products_store_variable) > 0){
			foreach ($products_store_variable as $key_variations => $value_variations) {
				array_push($sku_code_s, $value_variations->sku);
				array_push($ids, $value_variations->id);
			}
		}

		if(count($sku_code_s) > 0){
			if(in_array($sku_code, $sku_code_s)){
				$index = array_search($sku_code, $sku_code_s, true);
				$data = [
					'stock_quantity' => $stock_quantity
				];
				$woocommerce->post('products/'.$ids[$index], $data);
				return true;
			}
		}
		return true;
	}

	/**
	 * add invoice when order v2
	 * @param int $orderid 
	 * @return bolean
	 */
	public function add_inv_when_order_v2($orderid, $status = '') {

		$this->load->model('invoices_model');
		$this->load->model('credit_notes_model');
		$cart = $this->get_cart($orderid);

		$cart_detailt = $this->get_cart_detailt_by_master($orderid);
		$newitems = [];   
		$count = 0;
		foreach ($cart_detailt as $key => $value) {
			$unit = 0;
			$unit_name = '';
			$this->db->where('id', $value['product_id']);
			$data_product = $this->db->get(db_prefix().'items')->row();

			$taxname = [];
			if($value['tax']){
				$list_tax = json_decode($value['tax']);
				foreach ($list_tax as $tax_item) {
					$taxname[] = $tax_item->name.'|'.$tax_item->rate;
				}
			}

			$unit_name = "";
			if($data_product){ 
				$unit = $data_product->unit_id;
				if($unit != 0 || $unit != null){
					$this->db->where('unit_type_id', $unit);
					$unit_parent = $this->db->get(db_prefix().'ware_unit_type')->row();
					if($unit_parent){
						$unit_name = $unit_parent->unit_name;
					}
				}
			}

			$count = $key;
			array_push($newitems, array('order' => $key, 'description' => $value['product_name'], 'long_description' => $value['long_description'], 'qty' => $value['quantity'], 'unit' => $unit_name, 'rate'=> $value['prices'], 'taxname' => $taxname));
		}
		$total = $this->get_total_order($orderid)['total'];
		$sub_total = $this->get_total_order($orderid)['sub_total'];
		$discount_total = $this->get_total_order($orderid)['discount'];
		$__number = get_option('next_invoice_number');
		$_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
		$this->db->where('isdefault', 1);
		$curreny = $this->db->get(db_prefix().'currencies')->row()->id;
		if($cart){
			$data['clientid'] = $cart->userid;
			$data['billing_street'] = $cart->billing_street;
			$data['billing_city'] = $cart->billing_city;
			$data['billing_state'] = $cart->billing_state;
			$data['billing_zip'] = $cart->billing_zip;
			$data['billing_country'] = $cart->billing_country;
			$data['include_shipping'] = 1;
			$data['show_shipping_on_invoice'] = 1;
			$data['shipping_street'] = $cart->shipping_street;
			$data['shipping_city'] = $cart->shipping_city;
			$data['shipping_state'] = $cart->shipping_state;
			$data['shipping_zip'] = $cart->shipping_zip;
			$date_format   = get_option('dateformat');
			$date_format   = explode('|', $date_format);
			$date_format   = $date_format[0];       
			$data['date'] = date($date_format);
			$data['duedate'] = date($date_format);
				//terms_invoice
			$data['terms'] = get_option('predefined_terms_invoice');

			$payment_model_list = [];
			if($cart->allowed_payment_modes != ''){
				$payment_model_list = explode(',', $cart->allowed_payment_modes);
			}
			$data["allowed_payment_modes"] = $payment_model_list;
			if(isset($cart->shipping)){
				$data['subtotal'] = $cart->sub_total + $cart->shipping;
				$taxname = [];
				if(isset($cart->shipping_tax)){
					if($cart->shipping_tax > 0 && $cart->shipping > 0){
						$p = round(($cart->shipping_tax/$cart->shipping) * 100, 2);
						$taxname = [];
						$list_tax = json_decode($cart->shipping_tax_json);
						foreach ($list_tax as $tax_shipping) {
							$taxname[] = $tax_shipping->name.'|'.$tax_shipping->rate;
						}
					}
				}
				array_push($newitems, array('order' => $count+1, 'description' => _l('shipping'), 'long_description' => "", 'qty' => 1, 'unit' => "", 'rate'=> $cart->shipping, 'taxname' => $taxname));
			}else{
				$data['subtotal'] = $cart->sub_total;
			}
			$data['currency'] = $curreny;
			$data['newitems'] = $newitems;
			$data['number'] = $_invoice_number;

			$data['total'] = $cart->total ;
			if($cart->discount_type == 1){
				$data['discount_percent'] = $cart->discount;
				$data['discount_total'] =  ($cart->discount * $data['subtotal'])/100;
			}elseif($cart->discount_type == 2){
				$data['discount_total'] = $cart->discount;
				$data['discount_percent'] =  ($cart->discount/$data['subtotal'])*100;
			}else{
				$data['discount_total'] = '';
				$data['discount_percent'] = '';
			}
			$id = $this->invoices_model->add($data);

			if($cart->discount != '' && $cart->discount_type != '' && $cart->voucher != ''){
				$credit_notes = $this->credit_note_from_invoice_omni($id);
			} 
			$prefix = get_option('invoice_prefix');
			$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number, $status);
			return $id;
		}   
		return true;
	}


		 /**
	 * sync product name
	 * @param  int $store_id 
	 * @return bool           
	 */
		 public function sync_product_name($store_id, $arr_detail = null){
		 	$store =  $this->get_product_parent_id($store_id);
		 	$store_name = $store->name_channel;
		 	$products_store = $this->get_product();

		 	$items = [];
		 	if(isset($arr_detail)){
		 		foreach ($arr_detail  as $key => $product) {
		 			$this->db->where('id',$product);
		 			array_push($items, $this->db->get(db_prefix().'items')->row());
		 		}
		 	}else{
		 		if(!empty($products_store)){
		 			foreach ($products_store  as $key => $product) {
		 				if(!is_null($this->get_product($product['id']))){
		 					$this->db->where('id',$product['id']);
		 					array_push($items, $this->db->get(db_prefix().'items')->row());;
		 				}
		 			}
		 		}
		 	}

		 	$woocommerce = $this->init_connect_woocommerce($store_id);

		 	$per_page = 100;
		 	$products_store = [];
		 	for($page = 1; $page <= 100; $page++ ){
		 		$offset = ($page - 1) * $per_page;
		 		$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

		 		$products_store = array_merge($products_store, $list_products);

		 		if(count($list_products) < $per_page){
		 			break;
		 		}
		 	}
		 	$data_create = [];
		 	$data_create_master = [];
		 	foreach ($products_store as $key => $value) {

		 		if($value->sku != ''){
		 			foreach ($items as $item) {
		 				if($item->sku_code == $value->sku){
		 					$data = [
		 						'id' => $value->id,
		 						'name' => $item->description,
		 					];
		 					if(is_null($value->stock_quantity)){
		 						$value->stock_quantity = 0;
		 					}
		 					array_push($data_create,$data);
		 					if(count($data_create) == $this->amount){
		 						array_push($data_create_master,$data_create);
		 						$data_create = [];
		 					}
		 				}
		 			}   
		 		}
		 	}
		 	if(count($data_create) < 10){
		 		array_push($data_create_master,$data_create);
		 	}
		 	if($data_create_master > 0){
		 		foreach ($data_create_master as  $data__) {
		 			$data_cus = [
		 				'update' => $data__
		 			];
		 			$woocommerce->post('products/batch', $data_cus);
		 		}
		 		$this->exit_type_variation($store_id, 2);
		 	}
		 	return true;
		 }

	 /**
		 * sync short description
		 * @param $store_id
		 * @return           
		 */
	 public function sync_short_description($store_id, $arr_detail = null){
	 	$products_store = $this->get_product_parent_id();
	 	$items = [];
	 	if(isset($arr_detail)){
	 		foreach ($arr_detail  as $key => $product) {
	 			if(!is_null($this->get_product($product))){
	 				$this->db->where('id',$product);
	 				array_push($items, $this->db->get(db_prefix().'items')->row());
	 			}
	 		}
	 	}else{
	 		if(!empty($products_store)){
	 			foreach ($products_store  as $key => $product) {
	 				if(!is_null($this->get_product($product['id']))){
	 					$this->db->where('id',$product['id']);
	 					array_push($items, $this->db->get(db_prefix().'items')->row());
	 				}
	 			}
	 		}
	 	}

	 	$woocommerce = $this->init_connect_woocommerce($store_id);

	 	$per_page = 100;
	 	$products_store = [];
	 	for($page = 1; $page <= 100; $page++ ){
	 		$offset = ($page - 1) * $per_page;
	 		$list_products = $woocommerce->get('products', ['per_page' => $per_page, 'offset' => $offset, 'page' => $page]);

	 		$products_store = array_merge($products_store, $list_products);

	 		if(count($list_products) < $per_page){
	 			break;
	 		}
	 	}
	 	$arr_product_store = [];
	 	$data_create = [];
	 	$data_create_master = [];
	 	foreach ($products_store as $key => $value) {

	 		if($value->sku != ''){
	 			foreach ($items as $item) {
	 				if($item->sku_code == $value->sku){
	 					$data = [
	 						'id' => $value->id,
	 						'short_description' => $item->long_description,
	 					];

	 					$log_product = [
	 						'name' => $item->description,
	 						'short_description' => $item->long_description,
	 						'description' => $item->long_descriptions,
	 						'chanel' => 'WooCommerce('.$this->get_name_store($store_id).')',
	 						'sku' => $item->sku_code,
	 						'type' => "description",
	 					];

	 					$this->db->insert(db_prefix().'omni_log_sync_woo', $log_product);

	 					if(is_null($value->stock_quantity)){
	 						$value->stock_quantity = 0;
	 					}
	 					array_push($data_create,$data);
	 					if(count($data_create) == $this->amount){
	 						array_push($data_create_master,$data_create);
	 						$data_create = [];
	 					}
	 				}
	 			}   
	 		}
	 	}
	 	if(count($data_create) < 10){
	 		array_push($data_create_master,$data_create);
	 	}

	 	if(count($data_create_master) > 0){
	 		foreach ($data_create_master as  $data__) {
	 			$data_cus = [
	 				'update' => $data__
	 			];
	 			$woocommerce->post('products/batch', $data_cus);
	 		}
	 		$this->exit_type_variation($store_id, 2);
	 	}
	 	return true;
	 }


	/**
		 * auto_create_goods_delivery_with_invoice
		 * @param  integer $invoice_id 
		 *              
		 */
	public function omnisales_auto_create_goods_delivery_with_invoice($invoice_id, $invoice_update='')
	{
		$this->load->model('warehouse/warehouse_model');

		$this->db->where('id', $invoice_id);
		$invoice_value = $this->db->get(db_prefix().'invoices')->row();

		if($invoice_value){

			/*get value for goods delivery*/

			$data['goods_delivery_code'] = $this->warehouse_model->create_goods_delivery_code();

			if(!$this->warehouse_model->check_format_date($invoice_value->date)){
				$data['date_c'] = to_sql_date($invoice_value->date);
			}else{
				$data['date_c'] = $invoice_value->date;
			}


			if(!$this->warehouse_model->check_format_date($invoice_value->date)){
				$data['date_add'] = to_sql_date($invoice_value->date);

			}else{
				$data['date_add'] = $invoice_value->date;
			}

			$data['customer_code']  = $invoice_value->clientid;
			$data['invoice_id']   = $invoice_id;
			$data['addedfrom']  = $invoice_value->addedfrom;
			$data['description']  = $invoice_value->adminnote;
			$data['address']  = $this->warehouse_model->get_shipping_address_from_invoice($invoice_id);

			$data['total_money']  = (float)$invoice_value->subtotal + (float)$invoice_value->total_tax;
			$data['total_discount'] = $invoice_value->discount_total;
			$data['after_discount'] = $invoice_value->total;

			/*get data for goods delivery detail*/
			/*get item in invoices*/
			$this->db->where('rel_id', $invoice_id);
			$this->db->where('rel_type', 'invoice');
			$arr_itemable = $this->db->get(db_prefix().'itemable')->result_array();

			$arr_item_insert=[];
			$index=0;

			if(count($arr_itemable) > 0){
				foreach ($arr_itemable as $key => $value) {
					$commodity_code = $this->warehouse_model->get_itemid_from_name($value['description']);
					//get_unit_id
					$unit_id = $this->warehouse_model->get_unitid_from_commodity_name($value['description']);
					//get warranty
					$warranty = $this->warehouse_model->get_warranty_from_commodity_name($value['description']);

					if($commodity_code != 0){
						/*get item from name*/
						$arr_item_insert[$index]['commodity_code'] = $commodity_code;
						$arr_item_insert[$index]['quantities'] = $value['qty'] + 0;
						$arr_item_insert[$index]['unit_price'] = $value['rate'] + 0;
						$arr_item_insert[$index]['tax_id'] = '';
						$arr_item_insert[$index]['unit_id'] = $unit_id;
						$arr_item_insert[$index]['guarantee_period'] = $warranty;

						$arr_item_insert[$index]['total_money'] = (float)$value['qty']*(float)$value['rate'];
						$arr_item_insert[$index]['total_after_discount'] = (float)$value['qty']*(float)$value['rate'];

						/*update after : goods_delivery_id, warehouse_id*/

						/*get tax item*/
						$this->db->where('itemid', $value['id']);
						$this->db->where('rel_id', $invoice_id);
						$this->db->where('rel_type', "invoice");

						$item_tax = $this->db->get(db_prefix().'item_tax')->result_array();

						if(count($item_tax) > 0){
							foreach ($item_tax as $tax_value) {
								$tax_id = $this->warehouse_model->get_tax_id_from_taxname_taxrate($tax_value['taxname'], $tax_value['taxrate']);

								if($tax_id != 0){
									if(strlen($arr_item_insert[$index]['tax_id']) != ''){
										$arr_item_insert[$index]['tax_id'] .= '|'.$tax_id;
									}else{
										$arr_item_insert[$index]['tax_id'] .= $tax_id;

									}
								}


								$arr_item_insert[$index]['total_money'] += (float)$value['qty']*(float)$value['rate']*(float)$tax_value['taxrate']/100;

								$arr_item_insert[$index]['total_after_discount'] += (float)$value['qty']*(float)$value['rate']*(float)$tax_value['taxrate']/100;

							}
						}

						$index++;
					}


				}
			}

			$data_insert=[];

			$data_insert['goods_delivery'] = $data;
			$data_insert['goods_delivery_detail'] = $arr_item_insert;

			if($invoice_update != ''){
				//case invoice update
				$status = $this->warehouse_model->add_goods_delivery_from_invoice_update($invoice_id, $data_insert);

			}else{
				//case invoice add
				$status = $this->omnisalse_add_goods_delivery_from_invoice($data_insert, $invoice_id);

			}

			if($status){
				return $status;
			}else{
				return $status;
			}

		}

		return false;

	}


		/**
		 * add goods delivery from invoice
		 * @param array $data_insert 
		 */
		public function omnisalse_add_goods_delivery_from_invoice($data_insert, $invoice_id ='')
		{
			$this->load->model('warehouse/warehouse_model');

			$results=0;
			$flag_export_warehouse = 1;


			$this->db->insert(db_prefix() . 'goods_delivery', $data_insert['goods_delivery']);
			$insert_id = $this->db->insert_id();


			if (isset($insert_id)) {

				foreach ($data_insert['goods_delivery_detail'] as $delivery_detail_key => $delivery_detail_value) {
					/*check export warehouse*/

				//checking Do not save the quantity of inventory with item
					if($this->warehouse_model->check_item_without_checking_warehouse($delivery_detail_value['commodity_code']) == true){

						$inventory = $this->warehouse_model->get_inventory_by_commodity($delivery_detail_value['commodity_code']);

						if($inventory){
							$inventory_number =  $inventory->inventory_number;

							if((float)$inventory_number < (float)$delivery_detail_value['quantities'] ){
								$flag_export_warehouse = 0;
							}

						}else{
							$flag_export_warehouse = 0;
						}

					}


					$delivery_detail_value['goods_delivery_id'] = $insert_id;
					$this->db->insert(db_prefix() . 'goods_delivery_detail', $delivery_detail_value);
					$insert_detail = $this->db->insert_id();

					$results++;

				}

				$data_log = [];
				$data_log['rel_id'] = $insert_id;
				$data_log['rel_type'] = 'stock_export';
				$data_log['staffid'] = get_staff_user_id();
				$data_log['date'] = date('Y-m-d H:i:s');
				$data_log['note'] = "stock_export";

				$this->add_activity_log($data_log);

				/*update next number setting*/
				$this->warehouse_model->update_inventory_setting(['next_inventory_delivery_mumber' =>  get_warehouse_option('next_inventory_delivery_mumber')+1]);
				


			}


		//check inventory warehouse => export warehouse
			if($flag_export_warehouse == 1){
			//update approval
				$data_update['approval'] = 1;
				$this->db->where('id', $insert_id);
				$this->db->update(db_prefix() . 'goods_delivery', $data_update);

			//update log for table goods_delivery_invoices_pr_orders
				$this->db->insert(db_prefix() . 'goods_delivery_invoices_pr_orders', [
					"rel_id" => $insert_id,
					"rel_type" => $invoice_id,
					"type" => 'invoice',
				]);

			//update history stock, inventoty manage after staff approved
				$goods_delivery_detail = $this->warehouse_model->get_goods_delivery_detail($insert_id);

				foreach ($goods_delivery_detail as $goods_delivery_detail_value) {
				// add goods transaction detail (log) after update invetory number
				// 
				// check Without checking warehouse

					if($this->warehouse_model->check_item_without_checking_warehouse($goods_delivery_detail_value['commodity_code']) == true){
						$this->warehouse_model->add_inventory_from_invoices($goods_delivery_detail_value);
					}

				}
			}


			return $insert_id;


		}

	 /**
	 * get product by id  
	 * @param  int $id    
	 * @return  object or array object       
	 */
	 public function get_product_by_id($id = ''){
	 	if($id != ''){
	 		$this->db->where('id',$id);
	 		return $this->db->get(db_prefix().'items')->row();
	 	}
	 	else{     
	 		return $this->db->get(db_prefix().'items')->result_array();
	 	}
	 }

	/**
	 * delete mass product sales channel
	 * @param  array $data 
	 * @return boolean       
	 */
	public function delete_mass_product_sales_channel($data){
		$list_id = explode(',', $data['check_id']);
		$affected_rows = 0;
		foreach ($list_id as $key => $id) {
			$res = $this->delete_product($id);
			if($res == true){
				$affected_rows++;
			}
		}
		if($affected_rows != 0){
			return true;
		}
		return false;
	}

	/**
	 * get tax info by product
	 * @return  object $tax           
	 */
	public function get_tax_info_by_product($id_product){
		if($id_product!=''){
			$product = $this->get_product($id_product);
			if($product)
			{
				if($product->tax != '' && $product->tax)
				{
					$this->db->where('id', $product->tax);              
					return $this->db->get(db_prefix().'taxes')->row();
				}
			}
		}
	}
/**
 * get shift
 * @param  string $id 
 * @return object or array object     
 */
public function get_shift($id = ''){
	if(is_numeric($id)){
		$this->db->where('id', $id);
		return $this->db->get(db_prefix().'omni_shift')->row();			
	}
	else{
		return $this->db->get(db_prefix().'omni_shift')->result_array();			
	}
}
/**
 * get shift staff
 * @param  string $staff_id 
 * @param  string $status   
 * @return object or array object           
 */
public function get_shift_staff($staff_id = '', $status = ''){
	if($status != ''){
		return $this->db->query('select * from '.db_prefix().'omni_shift where staff_id = '.$staff_id.' and status = '.$status.' order by id desc limit 0,1')->row();		
	}
	return $this->db->query('select * from '.db_prefix().'omni_shift where staff_id = '.$staff_id)->result_array();		
}
	/**
	 * get shift history
	 * @param  string  $shift_id 
	 * @param  boolean $last
	 * @return object or array object           
	 */
	public function get_shift_history($shift_id = '', $last = false){
		if($last == true){
			return $this->db->query('select * from '.db_prefix().'omni_shift_history where shift_id = '.$shift_id.' order by id desc limit 0,1')->row();		
		}
		else{
			$this->db->where('shift_id', $shift_id);
			return $this->db->get(db_prefix().'omni_shift_history')->result_array();		
		}
	}

	/**
	 * add shift
	 * @param array $data 
	 * @return boolean 
	 */
	public function add_shift($data){
		$data['granted_amount'] = str_replace(',','',$data['granted_amount']);
		$this->db->insert(db_prefix().'omni_shift', $data);		
		return $this->db->insert_id();
	}
	/**
	 * update shift
	 * @param array $data 
	 * @return boolean 
	 */
	public function update_shift($data){
		$data['granted_amount'] = str_replace(',','',$data['granted_amount']);
		$this->db->where('id', $data['id']);
		$this->db->update(db_prefix().'omni_shift', $data);		
		if ($this->db->affected_rows() > 0) {           
			return true;
		}
		return false;
	}
/**
 * add shift transactions 
 * @param integer  $shift_id        
 * @param string  $type            
 * @param string  $action          
 * @param float $granted_amount  
 * @param float $current_amount  
 * @param float $customer_amount 
 * @param float $balance_amount  
 * @param float $order_value     
 * @param integer  $staff_id        
 * @param integer  $customer_id     
 */
public function add_shift_transactions($shift_id, $type, $action = '', $granted_amount = 0, $current_amount = 0, $customer_amount = 0, $balance_amount = 0, $order_value = 0, $staff_id = null, $customer_id = null)
{
	$data['shift_id'] = $shift_id;
	$data['action'] = $action;
	$data['granted_amount'] = $granted_amount;
	$data['current_amount'] = $current_amount;
	$data['customer_amount'] = $customer_amount;
	$data['balance_amount'] = $balance_amount;
	$data['staff_id'] = $staff_id;
	$data['order_value'] = $order_value;
	$data['type'] = $type;
	$data['customer_id'] = $customer_id;
	$this->db->insert(db_prefix().'omni_shift_history', $data);	
	return $this->db->insert_id();
}
/**
 * change shift status
 * @param  integer $shift_id 
 * @param  integer $status   
 * @return boolean           
 */
public function change_shift_status($shift_id, $status){
	$granted_amount = 0;
	$incurred_amount = 0;
	$closing_amount = 0;
	$order_value = 0;
	if($status == 2){
		if($shift_id != ''){
			$shift_history_data = $this->get_shift_history($shift_id);
			if($shift_history_data){
				foreach ($shift_history_data as $key => $value) {
					$granted_amount = $value['granted_amount'];
					$incurred_amount += $value['balance_amount'];
					$order_value += $value['order_value'];
					$closing_amount = $value['current_amount'];
				}
			}
		}
	}
	$this->db->where('id', $shift_id);
	$this->db->update(db_prefix().'omni_shift', [
		'status' => $status,
		'granted_amount' => $granted_amount,
		'incurred_amount' => $incurred_amount,
		'closing_amount' => $closing_amount,
		'order_value' => $order_value
	]);		
	if ($this->db->affected_rows() > 0) {           
		return true;
	}
	return false;
}
/**
 * delete shift
 * @param  integer $id 
 * @return boolean     
 */
public function delete_shift($id){
	$this->db->where('id',$id);
	$this->db->delete(db_prefix().'omni_shift');
	if ($this->db->affected_rows() > 0) {   
		$this->db->where('shift_id',$id);
		$this->db->delete(db_prefix().'omni_shift_history');
		return true;
	}
	return false;
}
   /**
   * create new order
   * @param  array $data 
   * @return string order_number       
   */
   public function create_new_order($data){
   	$this->load->model('clients_model');
   	$data_client = $this->clients_model->get($data['customer']);
   	if($data_client){
   		$user_id = $data['customer'];
   		$order_number = $this->incrementalHash();
   		$channel_id = 4;
   		$data_cart['userid'] = $user_id;
   		$data_cart['voucher'] = '';
   		$data_cart['order_number'] = $order_number;
   		$data_cart['channel_id'] = $channel_id;
   		$data_cart['channel'] = 'manual';
   		$data_cart['company'] =  $data_client->company;
   		$data_cart['phonenumber'] =  $data_client->phonenumber;
   		$data_cart['city'] =  $data_client->city;
   		$data_cart['state'] =  $data_client->state;
   		$data_cart['country'] =  $data_client->country;
   		$data_cart['zip'] =  $data_client->zip;
   		$data_cart['billing_street'] =  $data_client->billing_street;
   		$data_cart['billing_city'] =  $data_client->billing_city;
   		$data_cart['billing_state'] =  $data_client->billing_state;
   		$data_cart['billing_country'] =  $data_client->billing_country;
   		$data_cart['billing_zip'] =  $data_client->billing_zip;
   		$data_cart['shipping_street'] =  $data_client->shipping_street;
   		$data_cart['shipping_city'] =  $data_client->shipping_city;
   		$data_cart['shipping_state'] =  $data_client->shipping_state;
   		$data_cart['shipping_country'] =  $data_client->shipping_country;
   		$data_cart['shipping_zip'] =  $data_client->shipping_zip;
   		$data_cart['total'] =  preg_replace('%,%','',$data['total']);
   		$data_cart['sub_total'] =  $data['subtotal'];
   		$data_cart['tax'] =  0;
   		$data_cart['allowed_payment_modes'] =  $data['payment_methods'];

   		$data_cart['discount_type_str'] = $data['discount_type'];
   		$data_cart['discount_total'] = $data['discount_total'];
   		$data_cart['discount_percent'] = $data['discount_percent'];
   		$data_cart['seller'] = $data['sale_agent'];
   		$data_cart['notes'] =  $data['client_note'];
   		$data_cart['staff_note'] = $data['note'];
   		$data_cart['terms'] = $data['terms'];
   		$data_cart['currency'] = $data['currency'];
   		$data_cart['adjustment'] = $data['adjustment'];
   		if($data_cart['discount_percent'] != 0){
   			$data_cart['discount_type'] = 1;
   		}else{
   			$data_cart['discount_type'] = 2;
   		}

   		$this->db->insert(db_prefix() . 'cart', $data_cart);
   		$insert_id = $this->db->insert_id();
   		if($insert_id){
   			$date = date('Y-m-d');
   			$newitems = $data['newitems'];
   			$tax_amount = 0;
   			$total_tax = 0;
   			foreach ($newitems as $key => $value) {
   				$data_detailt['product_id'] = $value['product_id'];  
   				$item_quantity = $value['qty'];
   				$data_detailt['quantity'] = $item_quantity;  
   				$data_detailt['classify'] = '';
   				$data_detailt['cart_id']  = $insert_id;
   				$long_description = $value['long_description'];
   				$sku = '';
   				$data_products = $this->get_product($value['product_id']);
   				if($data_products){
   					$sku = $data_products->sku_code;
   				}
   				$taxrate = 0;
   				if(isset($value['taxrate']) && $value['taxrate'] != '' && $value['taxrate'] != 0){
   					$cal_tax = $value['taxrate'] * $value['rate'] / 100;
   					$tax_amount += $cal_tax;
   				}
   				$data_detailt['product_name'] = $value['description'];
   				$data_detailt['prices'] = $value['rate'];

            //Save tax to json
   				$tax_array = [];
   				$get_tax_data = $this->get_tax_list_product($value['product_id']);
   				if($get_tax_data){
   					foreach ($get_tax_data as $tax) {
   						$total_tax_value = ($tax['taxrate'] * ($value['rate'] * $item_quantity) / 100);
   						$total_tax += $total_tax_value;
   						$tax_array[] = [
   							'id' => $tax['id'],
   							'name' => $tax['name'],
   							'rate' => $tax['taxrate'],
   							'value' => $total_tax_value
   						];
   					}
   				}
   				$data_detailt['tax'] = json_encode($tax_array);

   				$data_detailt['sku'] = $sku;
   				$data_detailt['long_description'] = $value['long_description'];
   				$this->db->insert(db_prefix() . 'cart_detailt', $data_detailt);
   			} 
   			$this->db->where('id', $insert_id);
   			$this->db->update(db_prefix().'cart', ['tax' => $total_tax]);
   			return true;
   		}
   		return false;
   	}     
   }
    /**
     * update order
     * @param  array $data 
     * @return string order_number       
    */
    public function update_order($data){
    	$this->load->model('clients_model');
    	$data_client = $this->clients_model->get($data['customer']);
    	if($data_client){
    		$user_id = $data['customer'];
    		$order_number = $this->incrementalHash();
    		$channel_id = 4;
    		$data_cart['userid'] = $user_id;
    		$data_cart['voucher'] = '';
    		$data_cart['order_number'] = $order_number;
    		$data_cart['channel_id'] = $channel_id;
    		$data_cart['channel'] = 'manual';
    		$data_cart['company'] =  $data_client->company;
    		$data_cart['phonenumber'] =  $data_client->phonenumber;
    		$data_cart['city'] =  $data_client->city;
    		$data_cart['state'] =  $data_client->state;
    		$data_cart['country'] =  $data_client->country;
    		$data_cart['zip'] =  $data_client->zip;
    		$data_cart['billing_street'] =  $data_client->billing_street;
    		$data_cart['billing_city'] =  $data_client->billing_city;
    		$data_cart['billing_state'] =  $data_client->billing_state;
    		$data_cart['billing_country'] =  $data_client->billing_country;
    		$data_cart['billing_zip'] =  $data_client->billing_zip;
    		$data_cart['shipping_street'] =  $data_client->shipping_street;
    		$data_cart['shipping_city'] =  $data_client->shipping_city;
    		$data_cart['shipping_state'] =  $data_client->shipping_state;
    		$data_cart['shipping_country'] =  $data_client->shipping_country;
    		$data_cart['shipping_zip'] =  $data_client->shipping_zip;
    		$data_cart['total'] =  preg_replace('%,%','',$data['total']);
    		$data_cart['sub_total'] =  $data['subtotal'];
    		$data_cart['discount'] =  0;
    		$data_cart['tax'] =  0;
    		$data_cart['allowed_payment_modes'] =  $data['payment_methods'];

    		$data_cart['discount_type_str'] = $data['discount_type'];
    		$data_cart['discount_total'] = $data['discount_total'];
    		$data_cart['discount_percent'] = $data['discount_percent'];
    		$data_cart['seller'] = $data['sale_agent'];
    		$data_cart['notes'] =  $data['client_note'];
    		$data_cart['staff_note'] = $data['note'];
    		$data_cart['terms'] = $data['terms'];
    		$data_cart['currency'] = $data['currency'];
    		$data_cart['adjustment'] = $data['adjustment'];
    		if($data_cart['discount_percent'] != 0){
    			$data_cart['discount_type'] = 1;
    		}else{
    			$data_cart['discount_type'] = 2;
    		}

    		$order_id = $data['id'];
    		$this->db->where('id', $order_id);
    		$this->db->update(db_prefix() . 'cart', $data_cart);
    		if ($this->db->affected_rows() > 0) {
    			$date = date('Y-m-d');
    			$newitems = $data['newitems'];
    			$tax_amount = 0;
    			$list_item_id = [];
    			$total_tax = 0;
    			foreach ($newitems as $key => $value) {
    				$data_detailt['product_id'] = $value['product_id'];    
    				$item_quantity =  $value['qty'];
    				$data_detailt['quantity'] = $item_quantity;  
    				$data_detailt['classify'] = '';
    				$data_detailt['cart_id']  = $order_id;
    				$long_description = $value['long_description'];
    				$sku = '';
    				$data_products = $this->get_product($value['product_id']);
    				if($data_products){
    					$sku = $data_products->sku_code;
    				}
    				$taxrate = 0;
    				if(isset($value['taxrate']) && $value['taxrate'] != '' && $value['taxrate'] != 0){
    					$cal_tax = $value['taxrate'] * $value['rate'] / 100;
    					$tax_amount += $cal_tax;
    				}
    				$data_detailt['product_name'] = $value['description'];
    				$data_detailt['prices'] = $value['rate'];
    				$data_detailt['sku'] = $sku;
    				$data_detailt['long_description'] = $value['long_description'];


//Save tax to json
    				$tax_array = [];
    				$get_tax_data = $this->get_tax_list_product($value['product_id']);
    				if($get_tax_data){
    					foreach ($get_tax_data as $tax) {
    						$total_tax_value = ($tax['taxrate'] * ($value['rate'] * $item_quantity) / 100);
    						$total_tax += $total_tax_value;
    						$tax_array[] = [
    							'id' => $tax['id'],
    							'name' => $tax['name'],
    							'rate' => $tax['taxrate'],
    							'value' => $total_tax_value
    						];
    					}
    				}
    				$data_detailt['tax'] = json_encode($tax_array);

    				if(is_numeric($value['id'])){
    					$list_item_id[] = $value['id'];
    					$this->db->where('id', $value['id']);
    					$this->db->update(db_prefix() . 'cart_detailt', $data_detailt);
    				}
    				else{
    					$this->db->insert(db_prefix() . 'cart_detailt', $data_detailt);
    					$insert_item_id = $this->db->insert_id();
    					$list_item_id[] = $insert_item_id;
    				}
    			}

    			$order_detailt_data = $this->db->query('select id from '.db_prefix().'cart_detailt where cart_id = '.$order_id)->result_array();
    			$list_id_db = [];
    			foreach ($order_detailt_data as $row) {
    				$list_id_db[] = $row['id'];
    			}
    			$item_id_delete = array_diff($list_id_db, $list_item_id);
    			foreach ($item_id_delete as $id_delete) {
    				$this->db->where('id', $id_delete);
    				$this->db->delete(db_prefix() . 'cart_detailt');
    			}
    			$this->db->where('id', $order_id);
    			$this->db->update(db_prefix().'cart', ['tax' => $total_tax]);
    			return true;
    		}
    		return false;
    	}    
    }
/**
* get tax product
* @return  decimal $tax           
*/
public function get_tax_list_product($id_product){
	if($id_product!=''){
		$product = $this->get_product($id_product);
		if($product){
			if($product->tax != '' && $product->tax){
				$this->db->where('id', $product->tax);              
				return $this->db->get(db_prefix().'taxes')->result_array();
			}
		}
	}
}
/**
 * add product pos
 * @param array $data 
 */
public function add_product_pos($data){
	// Add product
	if(isset($data['id'])){
		unset($data['id']);
	}
	$sales_prices = 0;
	$purchase_prices = 0;
	if($data["rate"] != ''){
		$sales_prices = str_replace(',','',$data["rate"]);
	}
	if($data["purchase_price"] != ''){
		$purchase_prices = str_replace(',','',$data["purchase_price"]);
	}
	$data_add_product["description"] = $data["description"];
	$data_add_product["commodity_code"] = $data["commodity_code"];
	$data_add_product["sku_code"] = $data["sku_code"];
	$data_add_product["unit_id"] = $data["unit_id"];
	$data_add_product["rate"] = $sales_prices;
	$data_add_product["purchase_price"] = $purchase_prices;
	$data_add_product["tax"] = $data["tax"];
	$data_add_product["commodity_barcode"] = $data["commodity_barcode"];
	$data_add_product["group_id"] = $data["group_id"];
	$data_add_product["warehouse_id"] = ((isset($data["warehouse_id"])) ? $data["warehouse_id"] : '');
	$data_add_product["without_checking_warehouse"] = 0;
	$this->db->insert(db_prefix() . 'items', $data_add_product);
	$insert_id = $this->db->insert_id();
	if($insert_id){
		// Ware house
		$purchase_prices = ($purchase_prices != 0) ? $purchase_prices : $sales_prices;
		$total_good_amount = $purchase_prices * $data["quantity"];
		$data_warehouse['supplier_code'] = '';
		$data_warehouse['supplier_name'] = '';
		$data_warehouse['deliver_name'] = '';
		$data_warehouse['buyer_id'] = '';
		$data_warehouse['description'] = $data["description"];
		$data_warehouse['pr_order_id'] = '';
		$data_warehouse['date_c'] = date('Y-m-d');
		$data_warehouse['date_add'] = date('Y-m-d');
		$data_warehouse['goods_receipt_code'] = '';
		$data_warehouse['warehouse_id'] = '';
		$tax_rate = 0;
		$data_tax = $this->get_tax($data["tax"]);
		if($data_tax){
			$tax_rate = $data_tax->taxrate;
		}
		$total_tax_money = $total_good_amount * $tax_rate / 100;
		$data_warehouse['total_tax_money'] = $total_tax_money;
		$data_warehouse['total_goods_money'] = $total_good_amount;
		$data_warehouse['value_of_inventory'] = $total_good_amount;
		$data_warehouse['total_money'] = $total_good_amount + $total_tax_money;
		$data_warehouse['addedfrom'] = get_staff_user_id();
		$data_warehouse['approval'] = 1;
		$data_warehouse['project'] = '';
		$data_warehouse['type'] = '';
		$data_warehouse['department'] = '';
		$data_warehouse['requester'] = '';
		$data_warehouse['expiry_date'] = date('Y-m-d');
		$data_warehouse['invoice_no'] = '';
		$this->db->insert(db_prefix() . 'goods_receipt', $data_warehouse);
		$ware_house_insert_id = $this->db->insert_id();
		if($ware_house_insert_id){
			$data_warehouse_detail["commodity_code"] = $insert_id;
			$data_warehouse_detail["warehouse_id"] = ((isset($data["warehouse_id"])) ? $data["warehouse_id"] : '');
			$data_warehouse_detail["unit_id"] = $data["unit_id"];
			$data_warehouse_detail["quantities"] = $data["quantity"];
			$data_warehouse_detail["unit_price"] = $purchase_prices;
			$data_warehouse_detail["tax"] = $data["tax"];
			$data_warehouse_detail["goods_money"] = $total_good_amount;
			$data_warehouse_detail["tax_money"] = $total_tax_money;
			$data_warehouse_detail["discount"] = '';
			$data_warehouse_detail["discount_money"] = '';
			$data_warehouse_detail["lot_number"] = '';
			// $data_warehouse_detail["date_manufacture"] = '';
			// $data_warehouse_detail["expiry_date"] = '';
			$data_warehouse_detail["note"] = '';
			$data_warehouse_detail["goods_receipt_id"] = $ware_house_insert_id;
			$this->db->insert(db_prefix() . 'goods_receipt_detail', $data_warehouse_detail);
		}
		$data_inventory['warehouse_id'] = ((isset($data["warehouse_id"])) ? $data["warehouse_id"] : '');
		$data_inventory['commodity_id'] = $insert_id;
		$data_inventory['inventory_number'] = $data["quantity"];
		$data_inventory['date_manufacture'] = '';
		$data_inventory['expiry_date'] = '';
		$data_inventory['lot_number'] = '0';
		$this->db->insert(db_prefix() . 'inventory_manage', $data_inventory);
		// End warehouse
		$this->load->model('departments_model');
		$departmentid = '';
		$staff_departments = $this->departments_model->get_staff_departments(get_staff_user_id());
		$dep_query = '';
		foreach ($staff_departments as $key => $department) {
			$departmentid = $department['departmentid'];
			break;
		}
		$data_add_product_channel["sales_channel_id"] = '1';
		$data_add_product_channel["group_product_id"] = $data["group_id"];
		$data_add_product_channel["product_id"] = $insert_id;
		$data_add_product_channel["prices"] = $sales_prices;
		$data_add_product_channel["department"] = $departmentid;
		$this->db->insert(db_prefix().'sales_channel_detailt', $data_add_product_channel);
	}
	return $insert_id;
}
/**
 * add order payment
 * @param integer $cart_id      
 * @param integer $payment_id   
 * @param integer $payment_name 
 * @param integer $customer_pay 
 * @param string $datecreator  
 */
public function add_order_payment($cart_id, $payment_id, $payment_name, $customer_pay, $datecreator = '') 
{
	$data['cart_id'] = $cart_id;
	$data['payment_id'] = $payment_id;
	$data['payment_name'] = $payment_name;
	$data['customer_pay'] = $customer_pay;
	$data['datecreator'] = ($datecreator == '' ? date('Y-m-d H:i:s') : $this->format_date_time($datecreator));
	$this->db->insert(db_prefix().'omni_cart_payment', $data);
	return $this->db->insert_id();
}

/**
* get order multi payment
* @param  integer $cart_id 
*/
public function get_order_multi_payment($cart_id) 
{
	$this->db->where('cart_id', $cart_id);
	return $this->db->get(db_prefix().'omni_cart_payment')->result_array();
}

/**
 * get list child products
 * @param  $product_id 
 * @return              
 */

public function get_list_child_products($product_id){
	$this->db->where('parent_id', $product_id);
	return $this->db->get(db_prefix().'items')->result_array();
}
/**
 * get variation product
 * @param  integer $product_id 
 * @return object             
 */
public function get_variation_product($product_id)
{
	$this->db->select('parent_attributes');
	$this->db->where('id', $product_id);
	return $this->db->get(db_prefix().'items')->row();
}
/**
 * has variation
 * @param  json  $parent_attributes 
 * @return boolean                    
 */
public function has_variation($parent_attributes){
	$has_classify = false;
	$classify_list = json_decode($parent_attributes);
	if(is_array($classify_list)){
		if(count($classify_list) > 0){
			foreach ($classify_list as $key => $classify) {
				if($has_classify == false){
					if($classify->name == ""){
						$has_classify = false;
						break;
					}
					else{
						$has_classify = true;		
						break;
					}
				}
			} 
		} 
	} 
	return $has_classify;
}

	/**
	 * get payment mode id by name
	 * @param  string $name
	 * @return object     
	 */
	public function get_payment_mode_id_by_name($name)
	{
		if($name == ''){
			return 0;
		}
		$this->db->where('name', $name);
		$payment_mode = $this->db->get(db_prefix().'payment_modes')->row();
		if($payment_mode){
			return $payment_mode->id;
		}

		$this->db->insert(db_prefix().'payment_modes', ['name' => $name, 'active' => 1]);
		$insert_id = $this->db->insert_id();
		return $insert_id;
	}

	/**
	 * get tax by name
	 * @param  string $name
	 * @return object     
	 */
	public function get_tax_by_name($name, $rate)
	{
		if($name == ''){
			return 0;
		}

		$taxs = $this->db->get(db_prefix().'taxes')->result_array();
		if($taxs){
			foreach ($taxs as $tax) {
				if(strtolower($tax['name']) == strtolower($name)){
					if($tax['taxrate'] != $rate){
						$this->db->where('id', $tax['id']);
						$this->db->update(db_prefix().'taxes', ['taxrate' => $rate]);
					}

					return $tax;
				}
			}
		}

		$this->db->insert(db_prefix().'taxes', ['name' => $name, 'taxrate' => $rate]);
		$insert_id = $this->db->insert_id();
		return ['id' => $insert_id, 'name' => $name, 'taxrate' => $rate];
	}
/**
 * get list product pre order
 * @param  string $userid 
 * @return array         
 */
public function get_list_product_pre_order($userid = '', $add_query = ''){
	$where = '';
	if($userid != ''){
		$this->load->model('client_groups_model');
		$where .= 'find_in_set('.$userid.', customer)';
		$client_groups = $this->client_groups_model->get_customer_groups($userid);
		if($client_groups){
			$groups_query = '';
			foreach ($client_groups as $key => $group) {
				$groups_query .= 'find_in_set('.$group['groupid'].', customer_group) OR ';
			}
			if($groups_query != ''){
				$groups_query = rtrim($groups_query, ' OR ');
				$where .= ' OR ('.$groups_query.')';
			}
		}
		if($where != ''){
			$where = ' AND ('.$where.')';
		}
	}
	$data = $this->db->query('select * from '.db_prefix().'sales_channel_detailt left join '.db_prefix().'items on '.db_prefix().'items.id = '.db_prefix().'sales_channel_detailt.product_id where sales_channel_id = 6'.$where.($add_query != '' ? ' '.$add_query : ''))->result_array();
	if($data){
		return $data;
	}
	else{
		return $this->db->query('select * from '.db_prefix().'sales_channel_detailt left join '.db_prefix().'items on '.db_prefix().'items.id = '.db_prefix().'sales_channel_detailt.product_id where  sales_channel_id = 6 AND (customer = "" OR customer is null) AND (customer_group = "" OR customer_group is null)'.($add_query != '' ? ' '.$add_query : ''))->result_array();
	}
}


  /**
   * create new pre order
   * @param  array $data 
   * @return string order_number       
   */
  public function create_new_pre_order($data){
  	$this->load->model('clients_model');
  	$data_client = $this->clients_model->get($data['customer']);
  	$buyer_name = '';
  	$create_at = date('Y-m-d H:i:s');
	$date = date('Y-m-d');

  	if($data_client){
  		$user_id = $data['customer'];
  		$order_number = $this->incrementalHash();
  		$channel_id = 6;
  		$buyer_name = $data_client->company;

  		$data_cart['userid'] = $user_id;
  		$data_cart['enable'] = 0;
  		$data_cart['voucher'] = '';
  		$data_cart['order_number'] = $order_number;
  		$data_cart['channel_id'] = $channel_id;
  		$data_cart['channel'] = 'pre_order';
  		$data_cart['company'] =  $buyer_name;
  		$data_cart['phonenumber'] =  $data_client->phonenumber;
  		$data_cart['city'] =  $data_client->city;
  		$data_cart['state'] =  $data_client->state;
  		$data_cart['country'] =  $data_client->country;
  		$data_cart['zip'] =  $data_client->zip;
  		$data_cart['billing_street'] =  $data_client->billing_street;
  		$data_cart['billing_city'] =  $data_client->billing_city;
  		$data_cart['billing_state'] =  $data_client->billing_state;
  		$data_cart['billing_country'] =  $data_client->billing_country;
  		$data_cart['billing_zip'] =  $data_client->billing_zip;
  		$data_cart['shipping_street'] =  $data_client->shipping_street;
  		$data_cart['shipping_city'] =  $data_client->shipping_city;
  		$data_cart['shipping_state'] =  $data_client->shipping_state;
  		$data_cart['shipping_country'] =  $data_client->shipping_country;
  		$data_cart['shipping_zip'] =  $data_client->shipping_zip;
  		$data_cart['tax'] =  0;
  		$payment_method = '';
  		if(isset($data['allowed_payment_modes'])){
  			$payment_method = implode(',', $data['allowed_payment_modes']);
  		}
  		$data_cart['allowed_payment_modes'] =  $payment_method;

  		$seller = '';
  		$omni_default_seller = get_option('omni_default_seller');
  		if($omni_default_seller && $omni_default_seller != ''){
  			$seller = $omni_default_seller;
  		}
  		$data_cart['seller'] = $seller;

  		$duedate = '';
  		if(isset($data['duedate'])){
  			$duedate = $this->format_date($data['duedate']);
  		}


  		$data_cart['duedate'] = $duedate;
  		$data_cart['datecreator'] = $create_at;

  		$data_cart['notes'] =  $data['clientnote'];
  		$data_cart['currency'] = isset($data['currency']) ? $data['currency'] : '';


  		$this->db->insert(db_prefix() . 'cart', $data_cart);
  		$insert_id = $this->db->insert_id();
  		if($insert_id){
  			$sub_total = 0;
  			$total_tax = 0;
  			$discount_price = 0;
  			foreach ($data['newitems'] as $key => $value) {
  				$data_detailt['product_id'] = $value['product_id'];   
  				$item_quantity = $value['qty'];
  				$data_detailt['quantity'] = $item_quantity;
  				$data_detailt['classify'] = '';
  				$data_detailt['cart_id']  = $insert_id;
  				$product_name = '';
  				$prices = '';
  				$long_description = '';
  				$sku = '';
  				$data_products = $this->get_product($value['product_id']);
  				if($data_products){
  					$product_name = $data_products->description;
  					$long_description = $data_products->long_description;
  					$sku = $data_products->sku_code;
  				}
  				$data_detailt['product_name'] = $product_name;

  				$prices  = 0;
  				$data_prices = $this->get_price_channel($value['product_id'],6);
  				if($data_prices){
  					$prices  = $data_prices->prices;
  				}
  				$data_detailt['prices'] = $prices;


  				$data_detailt['percent_discount'] = 0;
  				$data_detailt['prices_discount'] = 0;
  				$taxrate = 0;
  				$total_tax_value = 0;
  				$tax_array = [];
  				$get_tax_data = $this->get_tax_list_product($value['product_id']);
  				if($get_tax_data){
  					foreach ($get_tax_data as $tax) {
  						$total_tax_value = ($tax['taxrate'] * ($prices * $item_quantity) / 100);
  						$total_tax += $total_tax_value;
  						$taxrate += $tax['taxrate'];

  						$tax_array[] = [
  							'id' => $tax['id'],
  							'name' => $tax['name'],
  							'rate' => $tax['taxrate'],
  							'value' => $total_tax_value
  						];
  					}						
  				}
  				$data_detailt['tax'] = json_encode($tax_array);


  				$data_detailt['sku'] = $sku;

  				$data_detailt['long_description'] = $long_description;

				$discount_percent = 0;
				$discountss = $this->check_discount($value['product_id'], $date, 2);

				if($discountss){
					if($taxrate > 0){
						$price = $prices * (1 + ($taxrate / 100));
						$discount_percent = $discountss->discount;
						$discount_price += (($discount_percent * $price) / 100) * $item_quantity;
					}
				}

  				$sub_total += $item_quantity * $prices;
  	
  				$this->db->insert(db_prefix() . 'cart_detailt', $data_detailt);
  			} 

  			$total = $sub_total + $total_tax - $discount_price;
  			$data_update['total'] =  $total;
  			$data_update['sub_total'] =  $sub_total;
  			$data_update['tax'] = $total_tax;
  			$data_update['discount'] =  $discount_price;
  			$data_update['discount_type'] =  2;
  			$this->db->where('id',$insert_id);
  			$this->db->update(db_prefix() . 'cart', $data_update);

  			if(isset($data['allowed_payment_modes'])){
  				$this->load->model('payment_modes_model');
  				$this->load->model('payments_model');
  				foreach ($data['allowed_payment_modes'] as $key => $payment) {
  					$payment_name = '';
  					$data_payments = $this->payment_modes_model->get($payment);
  					if($data_payments){
  						$payment_name = $data_payments->name;
  					}
  					$this->add_order_payment($insert_id, $payment, $payment_name, $total);
  				}
  			}

  			// Send mail and notify to seller
  			if($seller != ''){
  				$email = omni_email_staff($seller);
  				if($email != ''){
  					$url = admin_url('omni_sales/view_order_detailt/'.$insert_id);
  					$link = '<a href="'.$url.'" target="_blank">'.$url.'</a>';
  					$this->notifications($seller, 'omni_sales/view_order_detailt/'.$insert_id, _l('omni_you_have_a_new_pre_order').' '.$buyer_name);
  					$data_send_mail->email = $email;
  					$data_send_mail->seller_name = get_staff_full_name($seller);
  					$data_send_mail->buyer_name = $buyer_name;
  					$data_send_mail->create_at = _dt($create_at);
  					$data_send_mail->link = $link;
  					$template = mail_template('pre_orders_notify', 'omni_sales', $data_send_mail);
  					$template->send();
  				}
  			}
			// END 

  			return true;
  		}
  		return false;
  	}     
  }

  	/**
  	 * Gets the pre orders list by client.
  	 *
  	 * @param        $client  The client
  	 *
  	 * @return       The pre orders list by client.
  	 */
  	public function get_pre_orders_list_by_client($client){
  		$this->db->where('channel_id', 6);
  		$this->db->where('enable', 0);
  		$this->db->where('userid', $client);
  		$this->db->order_by('id', 'DESC');
  		return $this->db->get(db_prefix().'cart')->result_array();
  	}

	/**
	 * update cart
	 * @param  int $id 
	 * @return boolean   
	 */
	public function update_cart($id = '', $data = []){
		if($id != ''){
			$this->db->where('id',$id);
			$this->db->update(db_prefix().'cart', $data);
			if ($this->db->affected_rows() > 0) {
				return true;
			}
			return false;
		}
		return false;
	}

	/**
	 * Gets the pre order.
	 *
	 * @param        $id     The identifier
	 *
	 * @return       The pre order.
	 */
	public function get_pre_order($id){
		$this->db->where('id', $id);
		$cart = $this->db->get(db_prefix().'cart')->row();
		$cart->items = $this->get_cart_detailt_by_master($id);
		return $cart; 
	}

	public function mo_create_purchase_request($id)
	{
		$this->load->model('taxes_model');
		$this->load->model('departments_model');
		$this->load->model('purchase/purchase_model');

		$mo = $this->get_manufacturing_order($id);
		if(isset($mo['manufacturing_order_detail'])){
			$mo_detail=[];

			$arr_product_id=[];
			foreach ($mo['manufacturing_order_detail'] as $key => $mo_value) {
				$arr_product_id[] = $mo_value['product_id'];
			}

			$this->db->where('id IN ('.implode(",",$arr_product_id) .')');
			$products = $this->db->get(db_prefix() . 'items')->result_array();

			$arr_products=[];
			foreach ($products as $product) {
				$arr_products[$product['id']] = $product;
			}

			$pu_subtotal=0;
			$pu_total_tax=0;
			$pu_total=0;

			foreach ($mo['manufacturing_order_detail'] as $key => $mo_value) {

				if($mo_value['qty_reserved'] != $mo_value['qty_to_consume']){

					$pu_qty = $mo_value['qty_to_consume'] - $mo_value['qty_reserved'];

					$unit_price = isset($arr_products[$mo_value['product_id']]) ? $arr_products[$mo_value['product_id']]['purchase_price'] : 0;
					$list_taxrate = isset($arr_products[$mo_value['product_id']]) ? $arr_products[$mo_value['product_id']]['supplier_taxes_id'] : '';

					$taxrate= 0 ;
					$tax_id='';
					if(strlen($list_taxrate) > 0){
						$array_taxrate = explode(',', $list_taxrate);
						$tax = $this->taxes_model->get($array_taxrate[0]);
						if($tax){
							$taxrate = $tax->taxrate;
							$tax_id = $tax->id;
						}
					}

					$tax_value = (float)$unit_price*$pu_qty*$taxrate;
					$into_money = (float)$unit_price*$pu_qty;
					$total = (float)$unit_price*$pu_qty+$tax_value;

					$pu_total_tax += $tax_value;
					$pu_subtotal += $into_money;
					$pu_total += $total;

					array_push($mo_detail, [
						'item_code' => $mo_value['product_id'],
						'unit_id' => $mo_value['unit_id'],
						'unit_price' => $unit_price,
						'quantity' => $pu_qty,
						'into_money' => $into_money,
						'tax' => $tax_id,
						'tax_value' => $tax_value, 
						'total' => $total,
						'inventory_quantity' => 0,
					]);
				}

			}

			$prefix = get_purchase_option('pur_request_prefix');

			$staff_departments = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
			if(count($staff_departments) > 0){
				$data['department'] = $staff_departments[0];
			}else{
				$staff_departments = $this->departments_model->get();
				if(count($staff_departments) > 0){
					$data['department'] = $staff_departments[0];
				}else{
					$data['department'] = 0;
				}
			}

			$dpm_name = department_pur_request_name($data['department']);

			$purchase_data=[];
			$purchase_data['number'] = get_purchase_option('next_pr_number');
			$purchase_data['pur_rq_code'] =  $prefix.'-'.str_pad($purchase_data['number'],5,'0',STR_PAD_LEFT).'-'.date('M-Y').'-'.$dpm_name;
			$purchase_data['pur_rq_name'] =  'PUR create from Manufacturing '.$mo['manufacturing_order']->manufacturing_order_code;
			$purchase_data['project'] =  '';
			$purchase_data['type'] =  '';
			$purchase_data['sale_invoice'] =  '';
			$purchase_data['requester'] =  get_staff_user_id();
			$purchase_data['from_items'] =  '1';
			$purchase_data['rq_description'] =  _l('this_puchase_request_create_from_MO_module').$mo['manufacturing_order']->manufacturing_order_code;
			$purchase_data['subtotal'] =  $pu_subtotal;
			$purchase_data['total_mn'] =  $pu_total;
			$purchase_data['department'] =  $data['department'];

			$request_detail_temp=[];
			foreach ($mo_detail as $mo_detail_value) {
				array_push($request_detail_temp, array_values($mo_detail_value));
			}

			$purchase_data['request_detail'] = json_encode($request_detail_temp);

          	//add purchase request
			$pur_request_id = $this->purchase_model->add_pur_request($purchase_data);

			return $pur_request_id;
		}

	}

	/**
	 *  add product pre order   
	 * @param  array  $data 
	 * @return  int $insert_id
	 */
	public function add_product_pre_order($data){
		$list_product_id = [];
		if((isset($data['group_product_id']) && $data['group_product_id'] != '') && !isset($data['product_id'])){
	 		$items = $this->get_all_product_group($data['group_product_id']);
	 		foreach ($items as $key => $value) {
	 			$list_product_id[] = $value['id'];
	 		}
	 	}
	 	else{
	 		if(isset($data['product_id']) && count($data['product_id']) > 0){
	 			if(isset($data['product_id'])){
	 				$list_product_id = $data['product_id'];
	 			}
	 		}
	 	}

		if(count($list_product_id) > 0){
			$customer_group_list_id = '';
			$customer_list_id = '';
			if(isset($data['customer_group'])){
				$customer_group_list_id = implode(',', $data['customer_group']);
			}
			if(isset($data['customer'])){
				$customer_list_id = implode(',', $data['customer']);
			}
			$data_add_master['channel_id'] = 6;
			$data_add_master['customer_group'] = $customer_group_list_id;
			$data_add_master['customer'] = $customer_list_id;
			$data_add_master['group_product_id'] = (isset($data['group_product_id']) ? $data['group_product_id'] : '');
			$this->db->insert('omni_pre_order_product_setting', $data_add_master);
			$insert_id = $this->db->insert_id();

			foreach ($list_product_id as $key => $id) {
				$prices = 0;
				$get_data = $this->omni_sales_model->get_product($id);
				if($get_data){
					$prices = $get_data->rate;
				} 

				$data_add['sales_channel_id'] = $data['sales_channel_id'];
				$data_add['group_product_id'] = (isset($data['group_product_id']) ? $data['group_product_id'] : '');
				$data_add['product_id'] = $id;
				$data_add['prices'] = $prices;
				$data_add['customer_group'] = $customer_group_list_id;
				$data_add['customer'] = $customer_list_id;
				$data_add['pre_order_product_st_id'] = $insert_id;

				$this->db->insert('sales_channel_detailt', $data_add);
			}
			return true;
		}
		else{
			return false;
		}
	}

	 /**
	 * update product pre order   
	 * @param  array  $data 
	 * @return  int $insert_id
	 */
	 public function update_product_pre_order($data){
	 	$list_product_id = [];
	 	if((isset($data['group_product_id']) && $data['group_product_id'] != '') && !isset($data['product_id'])){
	 		$items = $this->get_all_product_group($data['group_product_id']);
	 		foreach ($items as $key => $value) {
	 			$list_product_id[] = $value['id'];
	 		}
	 	}
	 	else{
	 		if(isset($data['product_id']) && count($data['product_id']) > 0){
	 			if(isset($data['product_id'])){
	 				$list_product_id = $data['product_id'];
	 			}
	 		}
	 	}

	 	if(count($list_product_id) > 0){
	 		$customer_group_list_id = '';
	 		$customer_list_id = '';
	 		if(isset($data['customer_group'])){
	 			$customer_group_list_id = implode(',', $data['customer_group']);
	 		}
	 		if(isset($data['customer'])){
	 			$customer_list_id = implode(',', $data['customer']);
	 		}
	 		$data_add_master['channel_id'] = 6;
	 		$data_add_master['customer_group'] = $customer_group_list_id;
	 		$data_add_master['customer'] = $customer_list_id;
	 		$data_add_master['group_product_id'] = (isset($data['group_product_id']) ? $data['group_product_id'] : '');
	 		$this->db->where('id', $data['id']);
	 		$this->db->update('omni_pre_order_product_setting', $data_add_master);
	 		// Delete old detail
	 		$this->db->where('pre_order_product_st_id', $data['id']);
	 		$this->db->delete(db_prefix() .'sales_channel_detailt');
	 		// 
	 		foreach ($list_product_id as $key => $id) {
	 			$prices = 0;
	 			$get_data = $this->omni_sales_model->get_product($id);
	 			if($get_data){
	 				$prices = $get_data->rate;
	 			} 
	 			$data_add['sales_channel_id'] = $data['sales_channel_id'];
	 			$data_add['group_product_id'] = (isset($data['group_product_id']) ? $data['group_product_id'] : '');
	 			$data_add['product_id'] = $id;
	 			$data_add['prices'] = $prices;
	 			$data_add['customer_group'] = $customer_group_list_id;
	 			$data_add['customer'] = $customer_list_id;
	 			$data_add['pre_order_product_st_id'] = $data['id'];
	 			$this->db->insert(db_prefix() .'sales_channel_detailt', $data_add);
	 		}
	 		return true;
	 	}
	 	else{
	 		return false;
	 	}
	 }
	 
/**
 * count product pre order setting
 * @param  integer $id 
 * @return integer     
 */
public function count_product_pre_order_setting($id){
	$count = 0;
	$data = $this->db->query('select count(1) as count from '.db_prefix().'sales_channel_detailt where pre_order_product_st_id ='.$id)->row();
	if($data){
		$count = $data->count;
	}
	return $count;
}

/**
 * list product pre order setting
 * @param  integer $id 
 * @return integer     
 */
public function list_product_pre_order_setting($id){
	return $this->db->query('select * from '.db_prefix().'sales_channel_detailt where pre_order_product_st_id ='.$id)->result_array();
}


	/**
	 * delete pre order product 
	 * @param   int $id   
	 * @return  bool       
	 */
	public function delete_pre_order_product($id){
		$this->db->where('id',$id);
		$this->db->delete(db_prefix().'omni_pre_order_product_setting');
		if ($this->db->affected_rows() > 0) {  
			$this->db->where('pre_order_product_st_id',$id);
			$this->db->delete(db_prefix().'sales_channel_detailt');         
			return true;
		}
		return false;
	}

/**
 * create purchase request
 * @param  integer $id 
 */
public function create_purchase_request($id){
	$data_cart = $this->omni_sales_model->get_cart($id);
	if($data_cart){
		$data_detail = $this->omni_sales_model->get_cart_detailt_by_master($id);
		if($data_detail){
			$data_dt = [];
			foreach ($data_detail as $key => $detail) {
				$quantity = $detail['quantity'];
				$inventory_number = 0;
				$data_inventory = $this->omni_sales_model->get_quantity_inventory($detail['product_id'], '0');
				if($data_inventory){
					$inventory_number = $data_inventory->inventory_number;
				}

				$product = $this->get_product_by_id($detail['product_id']);
				if($product && $product->without_checking_warehouse == 1){
					$inventory_number = 1000;
				}

				$different = $inventory_number - $quantity;
				if($different < 0){
					if($product){
					// Create data detail
						$quantity = abs($different);
						$unit_price = $product->rate;
						$tax_id = $product->tax;

						$taxrate = 0;
						$this->db->where('id', $tax_id);              
						$tax = $this->db->get(db_prefix().'taxes')->row();
						if($tax){
							$taxrate = (float)$tax->taxrate;
						}

						$into_money = (float)$unit_price * $quantity;
						$tax_value = (float)($unit_price * $quantity * $taxrate) / 100;
						$total = (float)$unit_price * $quantity + $tax_value;

						array_push($data_dt, [
							'item_code' => $product->id,
							'unit_id' => $product->unit_id,
							'unit_price' => $unit_price,
							'quantity' => $quantity,
							'into_money' => $into_money,
							'tax' => $tax_id,
							'tax_value' => $tax_value, 
							'total' => $total,
							'inventory_quantity' => 0
						]);

		  			//End create data detail
					}
				}
			}

			$this->load->model('taxes_model');
			$this->load->model('departments_model');
			$this->load->model('purchase/purchase_model');


			$staff_departments = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
			if(count($staff_departments) > 0)
			{
				$data['department'] = $staff_departments[0];
			}
			else
			{
				$staff_departments = $this->departments_model->get();
				if(count($staff_departments) > 0){
					$data['department'] = $staff_departments[0]['departmentid'];
				}else{
					$data['department'] = 0;
				}
			}

			$dpm_name = department_pur_request_name($data['department']);

			$prefix = get_purchase_option('pur_request_prefix');
			$purchase_data = [];
			$purchase_data['number'] = get_purchase_option('next_pr_number');
			$purchase_data['pur_rq_code'] =  $prefix.'-'.str_pad($purchase_data['number'],5,'0',STR_PAD_LEFT).'-'.date('M-Y').'-'.$dpm_name;
			$purchase_data['pur_rq_name'] =  'PUR create from order #'.$data_cart->order_number;
			$purchase_data['project'] =  '';
			$purchase_data['type'] =  '';
			$purchase_data['sale_invoice'] =  '';
			$purchase_data['requester'] =  get_staff_user_id();
			$purchase_data['from_items'] =  '1';
			$purchase_data['rq_description'] =  _l('omni_this_puchase_request_create_from_omni_sale_module');
			$purchase_data['subtotal'] =  $data_cart->sub_total;
			$purchase_data['total_mn'] =  $data_cart->total;
			$purchase_data['department'] =  $data['department'];

			$request_detail_temp=[];
			foreach ($data_dt as $mo_detail_value) {
				array_push($request_detail_temp, array_values($mo_detail_value));
			}
			$purchase_data['request_detail'] = json_encode($request_detail_temp);
          	//add purchase request
			$pur_request_id = $this->purchase_model->add_pur_request($purchase_data);
			return $pur_request_id;
		}
	}
}


/**
	 * create invoice detail order
	 * @param int $orderid 
	 * @return bolean
	 */
public function create_invoice_detail_order($orderid, $status = '') {
	$this->load->model('invoices_model');
	$this->load->model('credit_notes_model');
	$this->load->model('warehouse/warehouse_model');
	$cart = $this->get_cart($orderid);

	$cart_detailt = $this->get_cart_detailt_by_master($orderid);
	$newitems = [];
	$count = 0;
	foreach ($cart_detailt as $key => $value) {
		$unit = 0;
		$unit_name = '';
		$this->db->where('id', $value['product_id']);
		$data_product = $this->db->get(db_prefix().'items')->row();
		$tax = $this->get_tax($data_product->tax);
		if($tax == ''){
			$taxname = '';
		}else{
			$taxname = $tax->name.'|'.$tax->taxrate;
		}

		$unit_name = "";
		if($data_product){        
			$unit = $data_product->unit_id;
			if($unit != 0 || $unit != null){
				$this->db->where('unit_type_id', $unit);
				$unit_parent = $this->db->get(db_prefix().'ware_unit_type')->row();
				if($unit_parent){
					$unit_name = $unit_parent->unit_name;
				}
			}  
		}
		$count = $key;
		array_push($newitems, array('order' => $key, 'description' => $value['product_name'], 'long_description' => $value['long_description'], 'qty' => $value['quantity'], 'unit' => $unit_name, 'rate'=> $value['prices'], 'taxname' => array($taxname)));
	}   

	$data_total = $this->get_total_order($orderid);
	$total = $data_total['total'];
	$sub_total = $data_total['sub_total'];
	$discount_total = $data_total['discount'];
	$__number = get_option('next_invoice_number');
	$_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
	$this->db->where('isdefault', 1);
	$curreny = $this->db->get(db_prefix().'currencies')->row()->id;

	if($cart){
		$data['clientid'] = $cart->userid;
		$data['billing_street'] = $cart->billing_street;
		$data['billing_city'] = $cart->billing_city;
		$data['billing_state'] = $cart->billing_state;
		$data['billing_zip'] = $cart->billing_zip;
		$data['billing_country'] = $cart->billing_country;
		$data['include_shipping'] = 1;
		$data['show_shipping_on_invoice'] = 1;
		$data['shipping_street'] = $cart->shipping_street;
		$data['shipping_city'] = $cart->shipping_city;
		$data['shipping_state'] = $cart->shipping_state;
		$data['shipping_zip'] = $cart->shipping_zip;
		$date_format   = get_option('dateformat');
		$date_format   = explode('|', $date_format);
		$date_format   = $date_format[0];       
		$data['date'] = date($date_format);
		$data['duedate'] = date($date_format);
			//terms_invoice
		$data['terms'] = get_option('predefined_terms_invoice');
		if(isset($cart->shipping)){
			array_push($newitems, array('order' => $count+1, 'description' => _l('shipping'), 'long_description' => "", 'qty' => 1, 'unit' => "", 'rate'=> $cart->shipping, 'taxname' => array()));
		}
		$data['currency'] = $curreny;
		$data['newitems'] = $newitems;
		$data['number'] = $_invoice_number;
		$data['total'] = $cart->total;
		$data['subtotal'] = $cart->sub_total;      
		$data['total_tax'] = $cart->tax;
		$data['discount_total'] = $cart->discount_total;
		if($cart->discount_type == 1){
			$data['discount_percent' ]= $cart->discount;
		}elseif($cart->discount_type == 2){
			$data['discount_percent'] =  ((float)$cart->discount_total/(float)$data['subtotal'])*100;
		}
		$prefix = get_option('invoice_prefix');


		$id = $this->invoices_model->add($data);

		if($cart->discount != '' && $cart->discount_type != '' && $cart->voucher != ''){
			$credit_notes = $this->credit_note_from_invoice_omni($id);
		}            
		if($id){
			//add hook after invoice add from order
            hooks()->do_action('omni_sales_after_invoice_added', $orderid);

			if($status!=''){
				$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number, $status);
			}
			else{
				$this->update_status_order_comfirm($orderid, $prefix, $_invoice_number, $__number);
			}           
			return true;
		}
	}   
	return true;
}


	/**
	 * clear diary sync data
	 * @return boolean
	 */
	public function clear_diary_sync_data(){
		$this->db->where('id > 0');
		$this->db->delete(db_prefix().'omni_log_sync_woo');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}
	
	/**
   * cron clear diary sync
   * @return boolean
   */
	public function cron_clear_diary_sync(){
		$number_of_days_to_save_diary_sync = get_option('number_of_days_to_save_diary_sync');
		if($number_of_days_to_save_diary_sync <= 0){
			return false;
		}

		$date = date('Y-m-d', strtotime("-$number_of_days_to_save_diary_sync DAY"));

		$this->db->where('(date_format(date_sync, \'%Y-%m-%d\') < "' . $date . '")');
		$this->db->delete(db_prefix().'omni_log_sync_woo');
		if ($this->db->affected_rows() > 0) {
			return true;
		}
		return false;
	}

	/**
	 * Update prices for store products
	 * @param  $store_id
	 * @param  $arr     
	 * @return boolean       
	 */
	public function woo_price_update($store_id, $arr = null){
		if($arr != '' && $arr != null){
			$this->db->where(db_prefix().'woocommere_store_detailt.product_id IN (' . implode(', ', $arr) . ')');
		}
		$this->db->select('*, '.db_prefix().'woocommere_store_detailt.id as id');
		$this->db->where('woocommere_store_id', $store_id);
		$this->db->join(db_prefix() . 'items', db_prefix() . 'items.id = '.db_prefix() . 'woocommere_store_detailt.product_id','left');
		$products_store = $this->db->get(db_prefix().'woocommere_store_detailt')->result_array();
		foreach($products_store as $product){
			$this->db->where('id', $product['id']);
			$this->db->update(db_prefix().'woocommere_store_detailt', ['prices' => $product['rate']]);
		}

		return true;
	}

	/**
	 * Update prices for portal products
	 * @param  $arr     
	 * @return boolean       
	 */
	public function portal_price_update($channel, $arr = null){
	
		$this->db->select('*, '.db_prefix().'sales_channel_detailt.id as id');
		$this->db->where('sales_channel_id', $channel);

		if($arr != '' && $arr != null){
			$this->db->where(db_prefix().'sales_channel_detailt.id IN (' . implode(', ', $arr) . ')');
		}
		$this->db->join(db_prefix() . 'items', db_prefix() . 'items.id = '.db_prefix() . 'sales_channel_detailt.product_id','left');
		$products_store = $this->db->get(db_prefix().'sales_channel_detailt')->result_array();
		foreach($products_store as $product){
			$this->db->where('id', $product['id']);
			$this->db->update(db_prefix().'sales_channel_detailt', ['prices' => $product['rate']]);
		}


		return true;
	}
	

	/**
	 * Gets the discount list.
	 * @param      string  $channel_id  The channel identifier
	 * @param      string  $client      The client
	 *
	 * @return     The discount list.
	 */
	public function get_discount_item_portal($item_id, $client = '', $date = ''){
		$group_product_query = '';
		$this->db->select('group_id');
		$this->db->where('id',$item_id);
		$data_group_product =  $this->db->get(db_prefix().'items')->row();
		if($data_group_product){
			$group_product =  $data_group_product->group_id;
			if(is_numeric($group_product) && $group_product > 0){
				$group_product_query = ' and (find_in_set('.$group_product.',group_items) or (group_items = "" or group_items is null))';
			}
		}

		if($client!=''){
			$data_group = $this->db->query('select * from '.db_prefix().'customer_groups where customer_id = '.$client)->result_array();

			

		

			$list_group = '';
			foreach ($data_group as $key => $group) {
				$list_group .= 'find_in_set('.$group['groupid'].',group_clients) or '; 
			}


			$open = '';
			$close = '';
			if($list_group != ''){
				$open = '(';
				$close = ')';
			}

			$query = 'select * from '.db_prefix().'omni_trade_discount where ('.$open.''.$list_group.'find_in_set('.$client.',clients)'.$close.' or (group_clients="" and clients="")) and (start_time <= \''.$date.'\' and end_time >= \''.$date.'\') and channel = 2 and voucher = "" and (find_in_set('.$item_id.',items) or (items = "" or items is null))'.$group_product_query.' order by id desc limit 0,1';
			$data_dis = $this->db->query($query)->result_array();
			if($data_dis){
				if((isset($data_dis[0]['items']) && ($data_dis[0]['items'] != '' || $data_dis[0]['items'] != null)) || (isset($data_dis[0]['group_items']) && ($data_dis[0]['group_items'] != '' || $data_dis[0]['group_items'] != null))){
					$obj = new stdClass();
					$obj->discount = $data_dis[0]['discount'];
					return $obj;
				}
			}
			return null;
		}
		else{
			$query = 'select * from '.db_prefix().'omni_trade_discount where group_clients = "" and clients = "" and (start_time <= \''.$date.'\' and end_time >= \''.$date.'\') and channel = 2 and voucher = "" and (find_in_set('.$item_id.',items) or (items = "" or items is null))'.$group_product_query.' order by id desc limit 0,1';
			$data_dis = $this->db->query($query)->result_array();
			if($data_dis){
				if((isset($data_dis[0]['items']) && ($data_dis[0]['items'] != '' || $data_dis[0]['items'] != null)) || (isset($data_dis[0]['group_items']) && ($data_dis[0]['group_items'] != '' || $data_dis[0]['group_items'] != null))){
					$obj = new stdClass();
					$obj->discount = $data_dis[0]['discount'];
					return $obj;
				}
			}
			return null;
		}
	}

	/**
	 * get customer public
	 * @return object       
	 */
	public function get_customer_public(){
	
		$clients = $this->clients_model->get();

		foreach($clients as $client){
			$custom_field_value = get_custom_field_value($client['userid'], 'customers_is_public', 'customers');
			if($custom_field_value != '' && $custom_field_value == 'public'){
				return (object)$client;
			}
		}

		return false;
	}

	/**
	 * delete mass product pre order
	 * @param  array $data 
	 * @return boolean       
	 */
	public function delete_mass_product_pre_order_channel($data){
		$list_id = explode(',', $data['check_id']);
		$affected_rows = 0;
		foreach ($list_id as $key => $id) {
			$res = $this->delete_pre_order_product($id);
			if($res == true){
				$affected_rows++;
			}
		}
		if($affected_rows != 0){
			return true;
		}
		return false;
	}
}


