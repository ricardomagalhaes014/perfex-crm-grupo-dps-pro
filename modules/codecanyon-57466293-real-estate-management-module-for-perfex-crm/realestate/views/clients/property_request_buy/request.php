<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php hooks()->do_action('app_customers_portal_head'); ?>
<div class="col-md-12">
	<?php echo form_open_multipart('realestate/client/property_request',array('autocomplete'=>'off', 'id'=>'add_order')); ?>

	<h2 class="tw-mt-0 tw-font-semibold tw-text-neutral-700 section-heading section-heading-invoices">
		<?php echo e($title); ?>
	</h2>

	<div class="panel_s">
		<div class="panel-body">
			<div class="accordion" id="accordionExample">
				<div class="card">
					<div class="card-header" id="heading1">
						
					</div>

					<?php 
					$id = '';
					$current_day = date("Y-m-d");
					$term_month = 1;
					$term_month_hide = '';
					$recurring_custom_hide = '';
					$inspection_date_hide = 'hide';
					$duedate_hide = '';
					$item_id = isset($property_id) ? $property_id : '';

					$contract_recurring_value = 1;
					$frequency_id = '';
					$inspect_property = 0;
					$property_price = 0;
					$total = 0;

					$start_date_col = 'col-md-5';
					$term_month_col = 'col-md-7';
					$term_month_label = isset($rental_type) ? $rental_type : _l('real_months');

					if(isset($property_request)){
						$id = $property_request->id;
						echo form_hidden('isedit');
						$term_month = $property_request->term_month;
						$contract_recurring_value = $property_request->contract_recurring_value;
						$frequency_id = $property_request->frequency_id;
						$inspect_property = $property_request->inspect_property;
						$property_price = $property_request->property_price;
						$total = $property_request->total;
						$request_type = $property_request->request_type;
						$item_id = $property_request->item_id;
						if($property_request->inspect_property == 1){
							$inspection_date_hide = '';
						}else{
							$inspection_date_hide = 'hide';
						}
					}

					if($request_type == 'buy'){
						$term_month_hide = 'hide';
						$duedate_hide = 'hide';
						$start_date_col = 'col-md-12';
						$term_month_col = 'col-md-7';
						$real_start_date_label = _l('real_expected_buy_date');

					}else{
						$term_month_hide = '';
						$duedate_hide = '';
						$start_date_col = 'col-md-5';
						$term_month_col = 'col-md-7';
						$real_start_date_label = _l('real_preferred_lease_start_date');

					}

					?>
					<input type="hidden" name="id" value="<?php echo html_entity_decode($id); ?>">
					<input type="hidden" name="currency" value="<?php echo html_entity_decode($base_currency_id); ?>">
					<input type="hidden" name="request_type" value="<?php echo html_entity_decode($request_type); ?>">
					<?php if(isset($_broker_id)){ ?>
						<input type="hidden" name="broker_id" value="<?php echo html_entity_decode($_broker_id); ?>">
					<?php } ?>
					<?php if(isset($_broker_type)){ ?>
						<input type="hidden" name="broker_type" value="<?php echo html_entity_decode($_broker_type); ?>">
					<?php } ?>
					
					<div class="row" >
						<div class="col-md-4">
							<div class="row hide">
								<div class="col-md-6 bt_item_id">
									<?php echo render_select('item_id', $items, ['id', 'description'], 'real_property_name', $item_id); ?>
								</div>
								<div class="col-md-6 bt_client_id">
									<div class="form-group select-placeholder">
										<label for="clientid" class="control-label"><?php echo _l('expense_add_edit_customer'); ?></label>
										<select id="clientid" name="clientid" data-live-search="true" data-width="100%" class="ajax-search" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
											<?php $selected = (isset($property_request) ? $property_request->clientid : '');
											if ($selected == '') {
												$selected = (isset($customer_id) ? $customer_id: '');
											}
											if ($selected != '') {
												$rel_data = get_relation_data('customer', $selected);
												$rel_val  = get_relation_values($rel_data, 'customer');
												echo '<option value="' . $rel_val['id'] . '" selected>' . $rel_val['name'] . '</option>';
											} ?>
										</select>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12 property_container">
									<?php if(isset($property)){ ?>
										<?php $this->load->view('companies/property_listings/utilities/room_item', ['properties' => $property, 'property_col' => '']) ?>
									<?php } ?>
								</div>

								<div class="col-md-6 hide">
									<div class="col-md-12">
										<hr class="hr-10" />
										<a href="#" class="edit_shipping_billing_info" data-toggle="modal" data-target="#billing_and_shipping_details"><i class="fa-regular fa-pen-to-square"></i></a>
										<?php $this->load->view('realestate/companies/property_requests/billing_and_shipping_template'); ?>
									</div>
									<div class="col-md-6">
										<p class="bold"><?php echo _l('invoice_bill_to'); ?></p>
										<address>
											<span class="billing_street">
												<?php $billing_street = (isset($property_request) ? $property_request->billing_street : '--'); ?>
												<?php $billing_street = ($billing_street == '' ? '--' :$billing_street); ?>
												<?php echo html_entity_decode($billing_street); ?></span><br>
												<span class="billing_city">
													<?php $billing_city = (isset($property_request) ? $property_request->billing_city : '--'); ?>
													<?php $billing_city = ($billing_city == '' ? '--' :$billing_city); ?>
													<?php echo html_entity_decode($billing_city); ?></span>,
													<span class="billing_state">
														<?php $billing_state = (isset($property_request) ? $property_request->billing_state : '--'); ?>
														<?php $billing_state = ($billing_state == '' ? '--' :$billing_state); ?>
														<?php echo html_entity_decode($billing_state); ?></span>
														<br/>
														<span class="billing_country">
															<?php $billing_country = (isset($property_request) ? get_country_short_name($property_request->billing_country) : '--'); ?>
															<?php $billing_country = ($billing_country == '' ? '--' :$billing_country); ?>
															<?php echo html_entity_decode($billing_country); ?></span>,
															<span class="billing_zip">
																<?php $billing_zip = (isset($property_request) ? $property_request->billing_zip : '--'); ?>
																<?php $billing_zip = ($billing_zip == '' ? '--' :$billing_zip); ?>
																<?php echo html_entity_decode($billing_zip); ?></span>
															</address>
														</div>
														<div class="col-md-6">
															<p class="bold"><?php echo _l('ship_to'); ?></p>
															<address>
																<span class="shipping_street">
																	<?php $shipping_street = (isset($property_request) ? $property_request->shipping_street : '--'); ?>
																	<?php $shipping_street = ($shipping_street == '' ? '--' :$shipping_street); ?>
																	<?php echo html_entity_decode($shipping_street); ?></span><br>
																	<span class="shipping_city">
																		<?php $shipping_city = (isset($property_request) ? $property_request->shipping_city : '--'); ?>
																		<?php $shipping_city = ($shipping_city == '' ? '--' :$shipping_city); ?>
																		<?php echo html_entity_decode($shipping_city); ?></span>,
																		<span class="shipping_state">
																			<?php $shipping_state = (isset($property_request) ? $property_request->shipping_state : '--'); ?>
																			<?php $shipping_state = ($shipping_state == '' ? '--' :$shipping_state); ?>
																			<?php echo html_entity_decode($shipping_state); ?></span>
																			<br/>
																			<span class="shipping_country">
																				<?php $shipping_country = (isset($property_request) ? get_country_short_name($property_request->shipping_country) : '--'); ?>
																				<?php $shipping_country = ($shipping_country == '' ? '--' :$shipping_country); ?>
																				<?php echo html_entity_decode($shipping_country); ?></span>,
																				<span class="shipping_zip">
																					<?php $shipping_zip = (isset($property_request) ? $property_request->shipping_zip : '--'); ?>
																					<?php $shipping_zip = ($shipping_zip == '' ? '--' :$shipping_zip); ?>
																					<?php echo html_entity_decode($shipping_zip); ?></span>
																				</address>
																			</div>
																		</div>

																	</div>



																</div>
																<div class="col-md-8">
																	<div class="row">
																		<div class="col-md-5 hide">
																			<?php $code = isset($property_request)? $property_request->code: $code; ?>
																			<?php echo render_input('code', 'real_request_number',$code,'',array('readonly' => 'true')) ?>
																		</div>

																		<div class="col-md-12">
																			<p class="tw-font-semibold"><?php echo _l('real_inspected_question'); ?></p>

																			<div class="form-group clearfix mtop5">
																				<div class="radio radio-primary radio-inline pull-left ">
																					<input type="radio" id="inspect_property_yes" name="inspect_property" value="1" <?php echo (($inspect_property == '1') ? 'checked' : '') ?>>
																					<label for="inspect_property_yes"><?php echo _l('real_inspected_answer_yes'); ?></label>
																				</div>
																			</div>
																			<div class="form-group clearfix ">
																				<div class="radio radio-primary radio-inline pull-left ">
																					<input type="radio" id="inspect_property_no" name="inspect_property" value="0" <?php echo (($inspect_property == '0') ? 'checked' : '') ?>>
																					<label for="inspect_property_no"><?php echo _l('real_inspected_answer_no'); ?></label>
																				</div>
																			</div>

																		</div>
																	</div>
																	<div class="row inspection_date_hide <?php echo html_entity_decode($inspection_date_hide) ?>">
																		<div class="col-md-12">

																			<?php $inspection_date = isset($property_request) ? $property_request->inspection_date : null ;?>
																			<?php echo render_date_input('inspection_date','real_inspection_date', _d($inspection_date)) ?>
																		</div>
																	</div>

																	<div class="row">
																		<div class="<?php echo html_entity_decode($start_date_col); ?>">
																			<?php $date = isset($property_request) ? $property_request->date : date("Y-m-d") ;?>
																			<?php $disabled=[]; ?>

																			<?php echo render_date_input('date',$real_start_date_label, _d($date), $disabled) ?>
																		</div>

																		<div class="<?php echo html_entity_decode($term_month_col); ?> <?php echo html_entity_decode($term_month_hide); ?>">
																			<div class="form-group">
																				<label for="term_month"> <small class="req text-danger">* </small><?php echo _l('real_term'); ?></label>
																				<div class="input-group">
																					<input type="number" name="term_month" class="form-control" min=1 step=1 value="<?php echo html_entity_decode($term_month); ?>" data-isedit="false" data-original-number="false">
																					<span class="input-group-addon"><?php echo html_entity_decode($term_month_label); ?></span>
																				</div>
																			</div>
																		</div>
																	</div>
																	<div class="row <?php echo html_entity_decode($duedate_hide); ?>">
																		<div class="col-md-12">
																			<?php $duedate = isset($property_request) ? $property_request->duedate : date("Y-m-d", strtotime(date("Y-m-d") . ' +1 month')) ;?>
																			<?php $duedate_attr = ['disabled' => true]; ?>

																			<?php echo render_date_input('duedate','real_end_date', _d($duedate), $duedate_attr) ?>
																		</div>
																	</div>

																	<div class="row">
																		<div class="col-md-12">
																			<?php $datecreated = isset($property_request) ? $property_request->datecreated : date("Y-m-d H:i:s") ;?>
																			<?php $disabled=[]; ?>
																			<?php echo render_datetime_input('datecreated','real_created_date', _dt($datecreated), $disabled) ?>
																		</div>
																	</div>
																	<hr class="hr-10">
																	<div class="row">
																		<div class="col-md-12">
																			<table class="table text-right">
																				<tbody>
																					<tr id="subtotal">
																						<td><span class="tw-font-semibold"><?php echo _l('real_property_price'); ?> :</span>
																						</td>
																						<td class="wh-subtotal">
																							<?php if(isset($property_request)){ ?>
																								<?php echo app_format_money($property_request->property_price, $base_currency_id); ?>
																								<input type="hidden" name="property_price" value="<?php echo html_entity_decode($property_request->property_price); ?>">

																							<?php } ?>
																						</td>
																					</tr>

																					<tr id="totalmoney">
																						<td><span class="tw-font-semibold"><?php echo _l('real_contract_amount'); ?> :</span>
																						</td>
																						<td class="wh-total">
																						</td>
																					</tr>
																				</tbody>
																			</table>
																		</div>
																	</div>

																</div>

															</div>

														</div>

														<div class="row">
															<div class="col-md-12">
																<div class="panel-body bottom-transaction !tw-p-0">

																	<?php $clientnote = (isset($property_request) ? $property_request->clientnote : ''); ?>
																	<?php $adminnote = (isset($property_request) ? $property_request->adminnote : ''); ?>
																	<?php echo render_textarea('clientnote','real_client_note',$clientnote,array(),array(),'mtop15'); ?>
																	<?php echo render_textarea('adminnote','real_admin_note',$adminnote,array(),array(),'mtop15 hide'); ?>


																	<div class="btn-bottom-toolbar text-right">
																		<?php if($request_type == 'buy'){ ?>
																			<a href="<?php echo html_entity_decode($site_url) . ('buy'); ?>"class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>
																		<?php }else{ ?>
																			<a href="<?php echo html_entity_decode($site_url) . ('rents'); ?>"class="btn btn-default text-right mright5"><?php echo _l('close'); ?></a>
																		<?php } ?>


																		<?php if ( is_client_logged_in()) { ?>
																			<?php if(isset($property_request) && $property_request->status != 2){ ?>
																				<a href="javascript:void(0)"class="btn btn-info pull-right mright5 add_order" ><?php echo _l('save'); ?></a>
																			<?php } ?>

																			<?php if(!isset($property_request)){ ?>
																				<a href="javascript:void(0)"class="btn btn-info pull-right mright5 add_order" ><?php echo _l('save'); ?></a>
																			<?php } ?>

																		<?php } ?>

																	</div>
																</div>
																<div class="btn-bottom-pusher"></div>
															</div>
														</div>


													</div>


												</div>
											</div>
										</div>
										<?php echo form_close(); ?>


									</div>
									<div id="rental_file_data"></div>

									<?php real_client_init_tail(); ?>
									<?php require 'modules/realestate/assets/js/clients/property_requests/add_edit_property_request_js.php';?>

