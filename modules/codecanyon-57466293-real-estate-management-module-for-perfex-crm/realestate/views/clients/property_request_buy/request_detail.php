<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="mtop15 preview-top-wrapper">
	<div class="row">
		<div class="col-md-3">
			<div class="mbot30">
				<div class="invoice-html-logo">
					<?php echo get_dark_company_logo(); ?>
				</div>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
	<div class="top" data-sticky data-sticky-class="preview-sticky-header">
		<div class="container preview-sticky-container">
			<div class="sm:tw-flex tw-justify-between -tw-mx-4">
				<div class="sm:tw-self-end">
					<h3 class="bold tw-my-0 invoice-html-number">
						<span class="sticky-visible hide tw-mb-2">
							<?php echo html_entity_decode($property_request->code); ?>
						</span>
					</h3>
					<span class="invoice-html-status">
						<?php echo render_property_request_status_html($property_request->id, 'order', $property_request->status, false); ?>
					</span>
				</div>
				<div class="tw-flex tw-items-end tw-space-x-2 tw-mt-3 sm:tw-mt-0">
					<?php if($property_request->status == 1){ ?>
						<a href="#" class="btn btn-success " onclick="property_request_status_mark_as('9',<?php echo html_entity_decode($property_request->id) ?>,'order'); return false;">
							<?php echo _l('real_submitted'); ?>
						</a>
					<?php } ?>

					<?php if($property_request->status == 9){ ?>
						<a href="#" class="btn btn-danger " onclick="property_request_status_mark_as('8',<?php echo html_entity_decode($property_request->id) ?>,'order'); return false;">
							<?php echo _l('real_cancelled'); ?>
						</a>
					<?php } ?>

					<?php if($property_request->request_type == 'buy'){ ?>
						<a href="<?php echo site_url('realestate/client/buy'); ?>"
							class="btn btn-default action-button go-to-portal">
							<?php echo _l('client_go_to_dashboard'); ?>
						</a>
					<?php }else{ ?>
						<a href="<?php echo site_url('realestate/client/rents'); ?>"
							class="btn btn-default action-button go-to-portal">
							<?php echo _l('client_go_to_dashboard'); ?>
						</a>
					<?php } ?>
					<a href="<?php echo site_url('realestate/client/property_request_pdf/'.$property_request->id); ?>"
						class="btn btn-default action-button go-to-portal">
						<?php echo _l('clients_invoice_html_btn_download'); ?>
					</a>

					<?php if($property_request->status == 1){ ?>
						<a href="<?php echo site_url('realestate/client/property_request/'.$property_request->id); ?>" class="btn btn-default ">
							<?php echo _l('edit'); ?>
						</a>
					<?php } ?>

				</div>
			</div>
		</div>
	</div>
</div>
<div class="clearfix"></div>

<div class="panel_s tw-mt-6">
	<div class="panel-body">

		<div class="col-md-10 col-md-offset-1">
			<div class="row mtop20">
				<div class="col-md-6 col-sm-6 transaction-html-info-col-left">
					<h4 class="tw-font-semibold tw-text-neutral-700 invoice-html-number">
						<?php echo e($property_request->code); ?>
					</h4>
					<address class="invoice-html-company-info tw-text-neutral-500 tw-text-normal">
						<?php if(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id == 0 ){ ?>
							<?php echo format_organization_info(); ?>
						<?php }elseif(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id > 0){ ?>
							<?php echo real_get_company_name($property_request->company_id, true, false, true); ?>
						<?php }elseif($property_request->broker_id > 0){ ?>
							<?php echo real_get_company_name($property_request->broker_id, true, false, true); ?>
						<?php } ?>
					</address>
					<?php hooks()->do_action('after_left_panel_requesthtml', $property_request); ?>
				</div>
				<div class="col-sm-6 text-right transaction-html-info-col-right">
					<span class="tw-font-medium tw-text-neutral-700 invoice-html-bill-to">
						<?php echo _l('invoice_bill_to'); ?>
					</span>
					<address class="invoice-html-customer-billing-info tw-text-neutral-500 tw-text-normal">
						<?php echo format_customer_info($property_request, 'estimate', 'billing', true); ?>
					</address>
					<!-- shipping details -->
					<?php if ($property_request->include_shipping == 1 && $property_request->show_shipping_on_estimate == 1) { ?>
						<span class="tw-font-medium tw-text-neutral-700 invoice-html-ship-to">
							<?php echo _l('ship_to'); ?>
						</span>
						<address class="invoice-html-customer-shipping-info tw-text-neutral-500 tw-text-normal">
							<?php echo format_customer_info($property_request, 'estimate', 'shipping'); ?>
						</address>
					<?php } ?>
					<p class="invoice-html-date tw-mb-0 tw-text-normal">
						<span class="tw-font-medium tw-text-neutral-700">
							<?php echo _l('real_created_date'); ?>
						</span>
						<?php echo e(_d($property_request->datecreated)); ?>
					</p>
					
					<?php if ($property_request->related_id) { ?>
						<p class="invoice-html-sale-agent tw-mb-0 tw-text-normal">
							<span class="tw-font-medium tw-text-neutral-700"><?php echo _l('sale_agent_string'); ?>:</span>
							<?php if($property_request->related_type == 'staff' || $property_request->related_type == 'company'){ ?>
								<?php echo get_staff_full_name($property_request->related_id); ?>
							<?php }else{ ?>
								<?php echo get_broker_name($property_request->related_id); ?>
							<?php } ?>
						</p>
					<?php } ?>
					
					
					<?php hooks()->do_action('after_right_panel_requesthtml', $property_request); ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="table-responsive">
						<table class="table items items-preview estimate-items-preview" data-type="estimate">
							<thead>
								<tr>
									<th align="center">#</th>
									<th align="left"  colspan="1"><?php echo _l('real_property_name') ?></th>
									<?php if($property_request->request_type == 'buy'){ ?>
										<th align="left" colspan="1"><?php echo _l('real_expected_buy_date') ?></th>
									<?php }else{ ?>
										<th align="left" colspan="1"><?php echo _l('real_preferred_lease_start_date') ?></th>
										<th align="left" colspan="1"><?php echo _l('real_term') ?></th>
										<th align="left" colspan="1"><?php echo _l('real_end_date') ?></th>
									<?php } ?>

									<th align="right" colspan="1"><?php echo _l('real_property_price') ?></th>
									<th align="right" colspan="1"><?php echo _l('real_contract_amount') ?></th>
									<th align="right" colspan="1"><?php echo _l('real_inspection_date') ?></th>
								</tr>
							</thead>
							<tbody class="ui-sortable">
								<?php 
								$rental_type = get_property_name($property_request->item_id, false, true);
								$rental_type_s = '';

								$_rental_type = '';
								if($rental_type != '' && $property_request->term_month > 1){
									$rental_type_s = $rental_type.'s';
									$_rental_type = ' per '.$rental_type;
								}
								?>
								<tr>
									<td ><?php echo new_html_entity_decode(1) ?></td>
									<td ><?php echo get_property_name($property_request->item_id) ?></td>
									<?php if($property_request->request_type == 'buy'){ ?>
										<td class="text-right"><?php echo _d($property_request->date) ?></td>

									<?php }else{ ?>
										<td class="text-right"><?php echo _d($property_request->date) ?></td>
										<td class="text-right"><?php echo new_html_entity_decode($property_request->term_month). $rental_type_s ?></td>
										<td class="text-right"><?php echo _d($property_request->duedate) ?></td>
									<?php } ?>

									<td class="text-right"><?php echo app_format_money((float)$property_request->property_price, $property_request->currency).$_rental_type ?></td>
									<td class="text-right"><?php echo app_format_money((float)$property_request->contract_total, $property_request->currency) ?></td>
									<?php 
									$inspect_property_date = _l('real_inspected_answer_yes');
									if($property_request->inspect_property == 1 && $property_request->inspection_date != null ){
										$inspect_property_date = _d($property_request->inspection_date);
									}elseif($property_request->inspect_property == 0){
										$inspect_property_date = _l('real_inspected_answer_no');
									}
									?>
									<td class="text-right"><?php echo new_html_entity_decode($inspect_property_date); ?></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				
				<?php if (!empty($property_request->clientnote)) { ?>
					<div class="col-md-12 invoice-html-note">
						<p>
							<b><?php echo _l('estimate_note'); ?></b>
						</p>
						<div class="tw-text-neutral-500 tw-mt-2.5">
							<?php echo process_text_content_for_display($property_request->clientnote); ?>
						</div>
					</div>
				<?php } ?>
				<?php if (!empty($property_request->terms)) { ?>
					<div class="col-md-12 invoice-html-terms-and-conditions">
						<hr />
						<p>
							<b>
								<?php echo _l('terms_and_conditions'); ?>
							</b>
						</p>
						<div class="tw-text-neutral-500 tw-mt-2.5">
							<?php echo process_text_content_for_display($property_request->terms); ?>
						</div>
					</div>
				<?php } ?>
				<div class="col-md-12">
					<hr />
				</div>

			</div>
		</div>
	</div>
</div>

<?php real_client_init_tail(); ?>
	<?php require 'modules/realestate/assets/js/clients/property_requests/request_detail_js.php';?>
