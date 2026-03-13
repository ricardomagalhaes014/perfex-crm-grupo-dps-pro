<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php 
$inv = '';
$inv_id = '';
$hash = '';
if(isset($invoice)){
	$inv_id = $invoice->id;
	$hash = $invoice->hash;
} 

?>
<div id="wrapper">
	<div class="content">
		<div class="panel_s">
			<div class="panel-body">

				<div class="row">
					<div class="<?php if($this->db->table_exists(db_prefix() . 'wh_goods_delivery_activity_log') && isset($order) && $order->stock_export_number != '') { echo 'col-md-8'; } else { echo 'col-md-12'; } ?>">

						<div class="row">
							<div class="col-md-6">
								<h5><?php echo _l('order_number');  ?>: #<?php  echo ( isset($order) ? $order->order_number : ''); ?></h5>
								<?php if(isset($order) && $order->seller > 0){ ?>
									<span class="mright15"><?php echo _l('seller');  ?>: <?php echo get_staff_full_name($order->seller); ?></span><br>
								<?php } ?>
								<span><?php echo _l('order_date');  ?>: <?php  echo ( isset($order) ? $order->datecreator : ''); ?></span><br>
								<?php if(isset($invoice)){ ?>
									<span><?php echo _l('invoice');  ?>: <a href="<?php echo admin_url('invoices#'.$invoice->id) ?>"><?php echo html_entity_decode($order->invoice); ?></a></span><br>

								<?php	} ?>
								<input type="hidden" name="order_number" value="<?php echo html_entity_decode($order->order_number); ?>">
								<?php 
								if(isset($order)){
									$payment_method =  $order->payment_method_title;
									if($payment_method == ''){
										$data_multi_payment = $this->omni_sales_model->get_order_multi_payment($order->id);
										if($data_multi_payment){
											foreach ($data_multi_payment as $key => $mtpayment) {
												$payment_method .= $mtpayment['payment_name'].', ';
											}
											$payment_method = rtrim($payment_method, ', ');
										}
										else{
											$this->load->model('payment_modes_model');	
											$data_payment = $this->payment_modes_model->get($order->allowed_payment_modes);
											if($data_payment){
												$name = isset($data_payment->name) ? $data_payment->name : '';
												if($name !=''){
													$payment_method = $name;              
												}            
											}
										}
									}	
									if($payment_method != ''){ ?>
										<span><?php echo _l('payment_method');  ?>: <span class="text-primary"><?php echo html_entity_decode($payment_method); ?></span></span><br>
									<?php }		
								}
								?>
							</div>
							<div class="col-md-6 status_order">
								<?php
								$currency_name = '';
								if(isset($base_currency)){
									$currency_name = $base_currency->name;
								}
								$status = get_status_by_index($order->status);    
								?>
								<div class="col-md-7 reasion text-danger">
									<?php 
									if($order->status == 8){ 
										if($order->admin_action == 0){
											echo _l('was_canceled_by_you_for_a_reason').': '._l($order->reason); 
										}
										else
										{
											echo _l('was_canceled_by_us_for_a_reason').': '._l($order->reason);  
										} 
									} ?> 
								</div>
								<div class="col-md-5">
									<!-- add hook display shipment -->
									<?php hooks()->do_action('omni_order_detail_header', $order); ?>

									<div class="btn-group pull-right">
											<button href="#" class="dropdown-toggle btn" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="true" >
												<?php echo _l($status); ?>  <span class="caret" data-toggle="" data-placement="top" data-original-title="<?php echo _l('change_status'); ?>"></span>
											</button>
											<ul class="dropdown-menu animated fadeIn">
												<li class="customers-nav-item-edit-profile">
													<a href="#" class="change_status" data-status="0">
														<?php echo _l('omni_draft'); ?>
													</a> 
													<a href="#" class="change_status" data-status="1">
														<?php echo _l('processing'); ?>
													</a>      
													<a href="#" class="change_status" data-status="2">
														<?php echo _l('pending_payment'); ?>
													</a>
													<a href="#" class="change_status" data-status="3">
														<?php echo _l('confirm'); ?>
													</a>
													<a href="#" class="change_status" data-status="4">
														<?php echo _l('shipping'); ?>
													</a>
													<a href="#" class="change_status" data-status="5">
														<?php echo _l('finish'); ?>
													</a>
													<a href="#" class="change_status" data-status="6">
														<?php echo _l('refund'); ?>
													</a>
													<a href="#" class="change_status" data-status="8">
														<?php echo _l('omni_canceled'); ?>
													</a>  
													<a href="#" class="change_status" data-status="9">
														<?php echo _l('omni_on_hold'); ?>
													</a> 
													<a href="#" class="change_status" data-status="10">
														<?php echo _l('omni_failed'); ?>
													</a>    
												</li> 

											</ul>
										</div>

								</div>
								<br>
							</div>
						</div>


						<div class="clearfix"></div>
						<div class="row">
							<div class="col-md-12">
								<hr>  
							</div>
						</div>
						<div class="clearfix"></div>
						<div class="row">
							<div class="col-md-4">
								<input type="hidden" name="userid" value="<?php echo html_entity_decode($order->userid); ?>">
								<h4 class="no-mtop">
									<i class="fa fa-user"></i>
									<?php echo _l('customer_details'); ?>
								</h4>
								<hr />
								<?php echo (isset($order) ? $order->company : ''); ?><br>
								<?php echo (isset($order) ? $order->phonenumber : ''); ?><br>
								<?php echo (isset($order) ? $order->address : ''); ?><br>
								<?php echo (isset($order) ? $order->city : ''); ?> <?php echo ( isset($order) ? $order->state : ''); ?><br>
								<?php echo isset($order) ? get_country_short_name($order->country) : ''; ?> <?php echo ( isset($order) ? $order->zip : ''); ?><br>
							</div>
							<div class="col-md-4">
								<h4 class="no-mtop">
									<i class="fa fa-map"></i>
									<?php echo _l('billing_address'); ?>
								</h4>
								<hr />
								<address class="invoice-html-customer-shipping-info">
									<?php echo isset($order) ? $order->billing_street : ''; ?>
									<br><?php echo isset($order) ? $order->billing_city : ''; ?> <?php echo isset($order) ? $order->billing_state : ''; ?>
									<br><?php echo isset($order) ? get_country_short_name($order->billing_country) : ''; ?> <?php echo isset($order) ? $order->billing_zip : ''; ?>
								</address>
							</div>
							<div class="col-md-4">
								<h4 class="no-mtop">
									<i class="fa fa-street-view"></i>
									<?php echo _l('shipping_address'); ?>
								</h4>
								<hr />
								<address class="invoice-html-customer-shipping-info">
									<?php echo isset($order) ? $order->shipping_street : ''; ?>
									<br><?php echo isset($order) ? $order->shipping_city : ''; ?> <?php echo isset($order) ? $order->shipping_state : ''; ?>
									<br><?php echo isset($order) ? get_country_short_name($order->shipping_country) : ''; ?> <?php echo isset($order) ? $order->shipping_zip : ''; ?>
								</address>
							</div>
						</div>
					</div>
					<?php if($this->db->table_exists(db_prefix() . 'wh_goods_delivery_activity_log') && isset($order) && $order->stock_export_number != '') { ?>
						<div class="col-md-4">
							<div class="panel-body">
								<div class="panel_s no-shadow activity_log_admin custom-bar mo-margin">
									<div class="activity-feed">
										<?php foreach($activity_log as $log){ ?>
											<div class="feed-item">
												<div class="date">
													<span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($log['date']); ?>">
														<?php echo time_ago($log['date']); ?>
													</span>
													<?php if($log['staffid'] == get_staff_user_id() || is_admin() || has_permission('warehouse','','delete()')){ ?>
														<a href="#" class="pull-right text-danger" onclick="delete_wh_activitylog(this,<?php echo html_entity_decode($log['id']); ?>);return false;"><i class="fa fa fa-times"></i></a>
													<?php } ?>
												</div>
												<div class="text">
													<?php if($log['staffid'] != 0){ ?>
														<a href="<?php echo admin_url('profile/'.$log["staffid"]); ?>">
															<?php echo staff_profile_image($log['staffid'],array('staff-profile-xs-image pull-left mright5'));
															?>
														</a>
														<?php
													}
													$additional_data = '';
													if(!empty($log['additional_data'])){
														$additional_data = unserialize($log['additional_data']);
														echo ($log['staffid'] == 0) ? _l($log['description'],$additional_data) : $log['full_name'] .' - '._l($log['description'],$additional_data);
													} else {
														echo $log['full_name'] . ' - ';
														echo _l($log['description']);
													}
													?>
												</div>
											</div>
										<?php } ?>
									</div>

									<div class="clearfix"></div>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>


				<div class="row">
					<?php
					$tax_total_array = [];
					$sub_total = 0;
					?>
					<div class="clearfix"></div>
					<br>       
					<div class="invoice accounting-template">
						<div class="row">
						</div>
						<div class="fr1">
							<div class="col-md-8">
							</div>
							<div class="col-md-4">
								<?php if(isset($order) && $order->stock_export_number != '') { ?>
									<div class="form-group">
										<div class="input-group">
											<textarea name="wh_activity_textarea" id="wh_activity_textarea" class="form-control" placeholder="<?php echo _l('enter_activity'); ?>" cols="30" rows="3"></textarea>
											<span class="input-group-addon btn" id="wh_enter_activity"><?php echo _l('submit'); ?></span>
										</div>
									</div>
								<?php } ?>
								<span class="pull-right mbot10 italic"><?php echo _l('omni_currency').': '.$currency_name; ?></span>																
							</div>
							<div class="table-responsive s_table">
								<table class="table invoice-items-table items table-main-invoice-edit has-calculations no-mtop">
									<thead>
										<tr>
											<th width="55%" align="center"><?php echo _l('invoice_table_item_heading'); ?></th>
											<th width="10%" align="center" class="qty"><?php echo _l('quantity'); ?></th>
											<th width="15%" align="center"  valign="center"><?php echo _l('price'); ?></th>
											<th width="15%" align="center"  valign="center"><?php echo _l('tax'); ?></th>
											<th width="15%" align="center"><?php echo _l('line_total'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php 
										$sub_total = 0; 
										$date = date('Y-m-d');
										?>

										<?php foreach ($order_detait as $key => $item_cart) { ?>
											<tr class="main">
												<td>
													<a href="#">
														<?php 
														$discount_price = 0;
														$discountss = $this->omni_sales_model->check_discount($item_cart['product_id'], $date);
														if($discountss){
															$discount_percent = $discountss->discount;
															$discount_price += ($discount_percent * $item_cart['prices']) / 100;
															$price_after_dc = $item_cart['prices']-(($discount_percent * $item_cart['prices']) / 100);
															echo form_hidden('discount_price', $discount_price);
														}else{
															$price_after_dc = $item_cart['prices'];
														}

														?>
														<img class="product pic" src="<?php echo $this->omni_sales_model->get_image_items($item_cart['product_id']); ?>">  
														<strong>
															<?php   
															echo html_entity_decode($item_cart['product_name']);
															?>
														</strong>
													</a>
												</td>
												<td align="center" class="middle">
													<?php echo html_entity_decode($item_cart['quantity']); ?>
												</td>
												<td align="center" class="middle">
													<?php if($discountss){ ?>
														<strong><?php 
														echo app_format_money($price_after_dc,'');
														?></strong>
														<p class="price">
															<span class="old-price"><?php echo app_format_money($item_cart['prices'], ''); ?></span>&nbsp;  
														</p>
													<?php }else{ ?>
														<strong><?php 
														echo app_format_money($price_after_dc,'');
														?></strong>
													<?php } ?>
												</td>
												<td align="center" class="middle">
													<?php 
													if($item_cart['tax']){
														$list_tax = json_decode($item_cart['tax']);
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
														echo html_entity_decode($tax_name);														
													}
													?>
												</td>
												<td align="center" class="middle">
													<strong class="line_total_<?php echo html_entity_decode($key); ?>">
														<?php
														$line_total = (int)$item_cart['quantity']*$item_cart['prices'];
														$sub_total += $line_total;
														echo app_format_money($line_total,''); ?>
													</strong>

												</td>
											</tr>
										<?php     } ?>
									</tbody>
								</table>
							</div>

							<div class="col-md-8 col-md-offset-4">
								<table class="table text-right">
									<tbody>
										<tr id="subtotal">
											<td width="50%"><span class="bold"><?php echo _l('invoice_subtotal'); ?> :</span>
											</td>
											<td class="subtotal_s" width="50%">
												<?php
												$sub_total = 0;
												if($order->sub_total){
													$sub_total = $order->sub_total;
												}
												echo app_format_money($sub_total,''); ?>
											</td>
										</tr>
										<?php if($order->discount){
											if($order->discount>0){
												if($order->discount_type == 1){
													$voucher = '';
													if($order->voucher){
														if($order->voucher!=''){
															$voucher = '<span class="text-danger">'.$order->voucher.'</span>';
														}
													}
													?>
													<tr>
														<td><span class="bold"><?php echo _l('discount').' ('.$voucher.' -'.$order->discount.'%)'; ?> :</span>
														</td>
														<td>
															<?php

															$price_discount = $order->sub_total * $order->discount/100;
															echo '-'.app_format_money($price_discount,''); ?>
														</td>
													</tr>


												<?php  }if($order->discount_type == 2){  ?>
													<tr>
														<td><span class="bold"><?php echo _l('discount'); ?> :</span>
														</td>
														<td>
															<?php
															$price_discount = $order->sub_total - $order->discount;
															echo '-'.app_format_money($order->discount,''); ?>
														</td>
													</tr>
													<?php 
												}
											}
										} ?>

										<?php if($order->channel == 'manual'){ ?>
											<?php if(is_sale_discount_applied($order)){ ?>
												<tr>
													<td>
														<span class="bold"><?php echo _l('invoice_discount').' :'; ?>
														<?php if(is_sale_discount($order,'percent')){ ?>
															(<?php echo app_format_number($order->discount_percent,true); ?>%)
															<?php } ?></span>
														</td>
														<td class="discount">
															<?php echo '-' . app_format_money($order->discount_total, ''); ?>
														</td>
													</tr>
												<?php } ?>
											<?php } ?>

											<?php foreach ($tax_total_array as $tax_item_row) {
												?>
												<tr>
													<td><span class="bold"><?php echo html_entity_decode($tax_item_row['name']); ?> :</span>
													</td>
													<td>
														<?php echo app_format_money($tax_item_row['value'],''); ?>
													</td>
												</tr>
												<?php 
											}
											?>

											<?php if((int)$order->adjustment != 0){ ?>
												<tr>
													<td>
														<span class="bold"><?php echo _l('invoice_adjustment').' :'; ?></span>
													</td>
													<td class="adjustment_t">
														<?php echo app_format_money($order->adjustment, ''); ?>
													</td>
												</tr>
											<?php } ?>

											<?php 
											if(isset($order->shipping)){
												if($order->shipping != "0.00"){ ?>
													<tr>
														<td><span class="bold"><?php echo _l('shipping'); ?> :</span>
														</td>
														<td>
															<?php echo app_format_money($order->shipping,''); ?>
														</td>
													</tr>
												<?php 	}
											}
											?>
											<?php 
											if(isset($order->shipping_tax)){
												if($order->shipping != "0.00"){ ?>
													<tr>
														<td><span class="bold"><?php echo _l('shipping_tax'); ?> :</span>
														</td>
														<td>
															<?php echo app_format_money($order->shipping_tax,''); ?>
														</td>
													</tr>
												<?php 	}
											}
											?>
											<?php 
											if(!$item_cart['tax']){ ?>
												<tr>
													<td><span class="bold"><?php echo _l('tax'); ?> :</span>
													</td>
													<td>
														<?php echo app_format_money($order->tax,''); ?>
													</td>
												</tr>
											<?php } ?>
											<tr>
												<td><span class="bold"><?php echo _l('invoice_total'); ?> :</span>
												</td>
												<td class="total_s">			                              	
													<?php echo app_format_money($order->total,''); ?>
												</td>
											</tr>
											<?php if($order->notes != ''){ ?>
												<tr>
													<td><span class="bold"><?php echo _l('note'); ?> :</span></td>
													<td><?php echo html_entity_decode($order->notes); ?></td>
												</tr>
											<?php } ?>
											<?php if($order->duedate != '' && $order->channel_id == 6){ ?>
												<tr>
													<td><span class="bold"><?php echo _l('omni_expiration_date'); ?> :</span></td>
													<td><?php echo _d($order->duedate); ?></td>
												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>		             

							</div>
							<div class="col-md-12 mtop15">
								<div class="row">
									<div class="panel-body bottom-transaction">
										<a href="<?php echo admin_url('omni_sales/order_list'); ?>" class="btn btn-default"><?php echo _l('close'); ?></a>
										<?php if($order->number_invoice == ""){ ?>
											<a href="<?php echo admin_url('omni_sales/create_invoice_detail_order/'.$id); ?>" class="btn btn-primary pull-right">
												<?php echo _l('create_invoice'); ?>
											</a>
										<?php }else{ ?>
											<a href="<?php echo admin_url('invoices#'.$invoice->id); ?>" class="btn pull-right"><?php echo _l('view_invoice'); ?></a>
										<?php } ?>

										<?php if ($order->stock_export_number == '' && $order->number_invoice != '') { 
												if(omni_get_status_modules('warehouse')){ ?>
													<a href="<?php echo admin_url('omni_sales/create_export_stock/'.$id); ?>" class="btn btn-warning pull-right mright15">
														<?php echo _l('create_export_stock'); ?>
													</a>
												<?php }	}else if($order->stock_export_number !=''){ ?>
											<a href="<?php echo admin_url('warehouse/manage_delivery#'.$order->stock_export_number); ?>" class="btn pull-right"><?php echo _l('view_export_stock'); ?></a>
										<?php } ?>								            
										<?php if($order->channel_id == 6){ 
											if(omni_get_status_modules('purchase') == true){ 
											if(omni_get_status_modules('warehouse') == true){ 
												?>
												<button class="btn btn-danger pull-right mright15 inventory_check" onclick="inventory_check('<?php echo html_entity_decode($order->order_number) ?>')">
													<?php 	echo _l('omni_inventory_check'); ?>
												</button>
											<?php 
												}
											}
											if($order->status == 0){ ?>
												<div class="pull-right">
													<?php echo form_open(admin_url('omni_sales/pre_order_hand_over'),array('id'=>'form_pre_order_hand_over')); ?>	            
													<input type="hidden" name="id" value="<?php echo html_entity_decode($id); ?>">
													<button class="btn btn-success pull-right mright15">
														<?php echo _l('omni_hand_over'); ?>
													</button>
													<div class="pull-right mright15">
														<div class="form-group hanover_option no-mbot">
															<select class="selectpicker display-block" required data-width="100%" name="seller" data-none-selected-text="<?php echo _l('staff'); ?>" data-live-search="true">
																<option value=""></option>
																<?php foreach ($staffs as $key => $value) { ?>
																	<option value="<?php echo html_entity_decode($value['staffid']); ?>"><?php echo html_entity_decode($value['firstname'].' '.$value['lastname']); ?></option>
																<?php } ?>
															</select>
														</div>
													</div>
													<?php echo form_close(); ?>	 
												</div>
											<?php } } ?>

										</div>
										<div class="btn-bottom-pusher"></div>
									</div>     
								</div>
							</div>
						</div>               
					</div>
				</div>

				<div class="modal fade" id="chosse" tabindex="-1" role="dialog">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title">
									<span class="add-title"><?php echo _l('please_let_us_know_the_reason_for_canceling_the_order') ?></span>
								</h4>
							</div>
							<div class="modal-body">
								<div class="col-md-12">
									<?php echo render_textarea('cancel_reason','cancel_reason',''); ?>
								</div>
							</div>
							<div class="clearfix">               
								<br>
								<br>
								<div class="clearfix">               
								</div>
								<div class="modal-footer">
									<button class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
									<button type="button" data-status="8" class="btn btn-danger cancell_order"><?php echo _l('cancell'); ?></button>
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->
				</div><!-- /.modal -->

				<div class="modal fade" id="inventory_check" tabindex="-1" role="dialog">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
								<h4 class="modal-title">
									<span class="add-title"><?php echo _l('omni_inventory_check') ?></span>
								</h4>
							</div>
							<div class="modal-body">
								<table class="table inventory_check_table">
									<thead>
										<tr>
											<th scope="col"></th>
											<th scope="col"><?php echo _l('omni_item'); ?></th>
											<th scope="col"><?php echo _l('omni_quantity'); ?></th>
											<th scope="col"><?php echo _l('omni_quantity_in_stock'); ?></th>
											<th scope="col"><?php echo _l('omni_difference'); ?></th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
							<div class="clearfix">               
								<br>
								<br>
								<div class="clearfix">               
								</div>
								<div class="modal-footer">
									<?php echo form_open(admin_url('omni_sales/create_purchase_request'),array('id'=>'form_create_purchase_request')); ?>	            
									<input type="hidden" name="id" value="<?php echo html_entity_decode($id); ?>">
									<button class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
									<button type="submit" class="btn btn-danger" ><?php echo _l('omni_create_purchase_request'); ?></button>
									<?php echo form_close(); ?>	 
								</div>
							</div><!-- /.modal-content -->
						</div><!-- /.modal-dialog -->
					</div><!-- /.modal -->
				</div><!-- /.modal -->
				<input type="hidden" name="goods_delivery_id" value="<?php echo html_entity_decode($order->stock_export_number) ?>">
				<?php init_tail(); ?>
			</body>
			</html>