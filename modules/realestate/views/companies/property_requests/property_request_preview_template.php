	<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
	<?php echo form_hidden('_attachment_sale_id', $property_request->id); ?>
	<?php echo form_hidden('_attachment_sale_type', 'estimate'); ?>
	<div class="col-md-12 no-padding">
		<div class="panel_s">
			<div class="panel-body">
				<div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
					<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
					<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
					<div class="horizontal-tabs">
						<ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
							<li role="presentation" class="active">
								<a href="#tab_estimate" aria-controls="tab_estimate" role="tab" data-toggle="tab">
									<?php echo _l('real_request'); ?>
								</a>
							</li>
							<li role="presentation" class="hide">
								<a href="#tab_reminders"
								onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?php echo html_entity_decode($property_request->id) ; ?> + '/' + 'estimate', undefined, undefined, undefined,[1,'asc']); return false;"
								aria-controls="tab_reminders" role="tab" data-toggle="tab">
								<?php echo _l('estimate_reminders'); ?>
								<?php
								$total_reminders = total_rows(
									db_prefix() . 'reminders',
									[
										'isnotified' => 0,
										'staff'      => get_staff_user_id(),
										'rel_type'   => 'estimate',
										'rel_id'     => $property_request->id,
									]
								);
								if ($total_reminders > 0) {
									echo '<span class="badge">' . $total_reminders . '</span>';
								}
								?>
							</a>
						</li>

						<li role="presentation">
							<?php if(is_staff_logged_in()){ ?>
							<a href="#tab_notes" onclick="get_sales_notes(<?php echo html_entity_decode($property_request->id); ?>,'realestate'); return false"
								aria-controls="tab_notes" role="tab" data-toggle="tab">
								<?php echo _l('estimate_notes'); ?>
								<span class="notes-total">
									<?php if ($totalNotes > 0) { ?>
										<span class="badge"><?php echo html_entity_decode($totalNotes); ?></span>
									<?php } ?>
								</span>
							</a>
							<?php } ?>
						</li>

						<li class="hide" role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>"
							class="tab-separator">
							<a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab"
							data-toggle="tab">
							<?php if (!is_mobile()) { ?>
								<i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
							<?php } else { ?>
								<?php echo _l('emails_tracking'); ?>
							<?php } ?>
						</a>
					</li>
					<li class="hide" role="presentation" data-toggle="tooltip" data-title="<?php echo _l('view_tracking'); ?>"
						class="tab-separator">
						<a href="#tab_views" aria-controls="tab_views" role="tab" data-toggle="tab">
							<?php if (!is_mobile()) { ?>
								<i class="fa fa-eye"></i>
							<?php } else { ?>
								<?php echo _l('view_tracking'); ?>
							<?php } ?>
						</a>
					</li>
					<li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>"
						class="tab-separator toggle_view">
						<a href="#" onclick="small_table_full_view(); return false;">
							<i class="fa fa-expand"></i></a>
						</li>
					</ul>
				</div>
			</div>
			<div class="row mtop20">
				<div class="col-md-3">
					<?php echo render_property_request_status_html($property_request->id, 'order', $property_request->status, false); ?>
				</div>
				<div class="col-md-9">
					<div class="visible-xs">
						<div class="mtop10"></div>
					</div>
					<div class="pull-right _buttons">
						<?php if($property_request->request_type == 'rent'){ ?>
							<a href="<?php echo html_entity_decode($site_url) . ('renter_profile/' . $property_request->clientid.'/'.$property_request->id); ?>"
								class="btn btn-success btn-with-tooltip"><i
								class="fa-regular fa-eye"></i>
								<?php echo _l("real_renter_profile"); ?>
							</a>
						<?php } ?>

						<?php if (has_permission('real_buy_request', '', 'edit') || has_permission('real_rent_request', '', 'edit')) { ?>
							<a href="<?php echo html_entity_decode($site_url) . ('add_edit_property_request/' . $property_request->id); ?>"
								class="btn btn-default btn-with-tooltip" data-toggle="tooltip"
								title="<?php echo _l('real_edit_request'); ?>" data-placement="bottom"><i
								class="fa-regular fa-pen-to-square"></i></a>
							<?php } ?>
							<div class="btn-group">
								<a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
								aria-haspopup="true" aria-expanded="false"><i class="fa-regular fa-file-pdf"></i><?php if (is_mobile()) {
									echo ' PDF';
								} ?> <span class="caret"></span></a>
								<ul class="dropdown-menu dropdown-menu-right">
									<li class="hidden-xs"><a
										href="<?php echo html_entity_decode($site_url) . ('property_request_pdf/' . $property_request->id . '?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a>
									</li>
									<li class="hidden-xs"><a
										href="<?php echo html_entity_decode($site_url) . ('property_request_pdf/' . $property_request->id . '?output_type=I'); ?>"
										target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
										<li><a
											href="<?php echo html_entity_decode($site_url) . ('property_request_pdf/' . $property_request->id); ?>"><?php echo _l('download'); ?></a>
										</li>
										<li>
											<a href="<?php echo html_entity_decode($site_url) . ('property_request_pdf/' . $property_request->id . '?print=true'); ?>"
												target="_blank">
												<?php echo _l('print'); ?>
											</a>
										</li>
									</ul>
								</div>
								<?php
								$_tooltip              = _l('estimate_sent_to_email_tooltip');
								$_tooltip_already_send = '';
								if ($property_request->sent == 1) {
									$_tooltip_already_send = _l('estimate_already_send_to_client_tooltip', time_ago($property_request->datesend));
								}
								?>
								<?php if (!empty($property_request->clientid)) { ?>
									<a href="#" class="property-request-send-to-client btn btn-default btn-with-tooltip"
									data-toggle="tooltip" title="<?php echo html_entity_decode($_tooltip); ?>" data-placement="bottom"><span
									data-toggle="tooltip" data-title="<?php echo html_entity_decode($_tooltip_already_send); ?>"><i
									class="fa-regular fa-envelope"></i></span></a>
								<?php } ?>
								<div class="btn-group">
									<button type="button" class="btn btn-default pull-left dropdown-toggle"
									data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<?php echo _l('more'); ?> <span class="caret"></span>
								</button>
								<ul class="dropdown-menu dropdown-menu-right">
									<li>
										<a href="<?php echo site_url('realestate/client/request_detail/' . $property_request->id) ?>"
											target="_blank">
											<?php echo _l('view_request_as_client'); ?>
										</a>
									</li>

									<?php if ((has_permission('real_buy_request', 'create') || has_permission('real_buy_request', 'edit')) && $property_request->contract_id == 0 && $property_request->status == 2) { ?>
										<li>
											<a
											href="<?php echo html_entity_decode($site_url) . ("convert_to_contract/".$property_request->id) ?>">
											<?php echo _l('real_request_convert_to_contract'); ?>
										</a>
									</li>
								<?php } ?>

							</ul>
						</div>

					</ul>

					<?php if ($property_request->contract_id != null && $property_request->contract_id != 0) { ?>

						<?php if(is_staff_logged_in()){ 
							$contract_url = admin_url('contracts/contract/'.$property_request->contract_id);
						}else{ 
							$contract_url = $site_url . ('contract/'.$property_request->contract_id);
						} ?>

						<a href="<?php echo html_entity_decode($contract_url); ?>"
							data-placement="bottom" data-toggle="tooltip"
							title="<?php echo _l('real_request_contracted_date', _dt($property_request->contrated_date)); ?>"
							class="btn btn-primary mleft10"><?php echo get_contract_name($property_request->contract_id); ?></a>
						<?php } ?>

						<?php if ($property_request->invoice_id == null || $property_request->invoice_id == 0 && $property_request->status == 2) { ?>
							<?php if (staff_can('create', 'invoices') && !empty($property_request->clientid)) { ?>
								<div class="btn-group pull-right mleft5">
									<button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"
									aria-haspopup="true" aria-expanded="false">
									<?php echo _l('real_request_convert_to_invoice'); ?> <span class="caret"></span>
								</button>
								<ul class="dropdown-menu">
									<li><a
										href="<?php echo html_entity_decode($site_url) . ('convert_to_invoice/' . $property_request->id . '?save_as_draft=true'); ?>"><?php echo _l('convert_and_save_as_draft'); ?></a>
									</li>
									<li class="divider">
										<li><a
											href="<?php echo html_entity_decode($site_url) . ('convert_to_invoice/' . $property_request->id); ?>"><?php echo _l('convert'); ?></a>
										</li>
									</li>
								</ul>
							</div>
						<?php } ?>
					<?php } elseif($property_request->status == 2) { ?>

						<?php if(is_staff_logged_in()){ 
							$invoice_url = admin_url('invoices/list_invoices/'.$property_request->invoice_id);
						}else{ 
							$invoice_url = $site_url . ('list_invoices/'.$property_request->invoice_id);
						} ?>
						<a href="<?php echo html_entity_decode($invoice_url); ?>"
							data-placement="bottom" data-toggle="tooltip"
							title="<?php echo _l('real_request_invoiced_date', _dt($property_request->invoiced_date)); ?>"
							class="btn btn-primary mleft10 mtop5"><?php echo format_invoice_number($property_request->invoice->id); ?></a>
						<?php } ?>
					</div>
				</div>
			</div>
			<div class="clearfix"></div>
			<hr class="hr-panel-separator" />
			<div class="tab-content">
				<div role="tabpanel" class="tab-pane ptop10 active" id="tab_estimate">

					<div id="estimate-preview">
						<div class="row">
							<div class="col-md-6 col-sm-6">
								<h4 class="bold">
									<a href="<?php echo html_entity_decode($site_url) . ('add_edit_property_request/' . $property_request->id); ?>">
										<span id="estimate-number">
											<?php echo html_entity_decode($property_request->code); ?>
										</span>
									</a>
								</h4>
								<address class="tw-text-neutral-500">
									<?php if(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id == 0 ){ ?>
										<?php echo format_organization_info(); ?>
									<?php }elseif(($property_request->related_type == 'staff' || $property_request->related_type == 'company') && $property_request->company_id > 0){ ?>
										<?php echo real_get_company_name($property_request->company_id, true, false, true); ?>
									<?php }elseif($property_request->broker_id > 0){ ?>
										<?php echo real_get_company_name($property_request->broker_id, true, false, true); ?>
									<?php } ?>

								</address>
							</div>
							<div class="col-sm-6 text-right">
								<span class="bold"><?php echo _l('estimate_to'); ?></span>
								<address class="tw-text-neutral-500">
									<?php echo format_customer_info($property_request, 'estimate', 'billing', true); ?>
								</address>
								<?php if ($property_request->include_shipping == 1 && $property_request->show_shipping_on_estimate == 1) { ?>
									<span class="bold"><?php echo _l('ship_to'); ?></span>
									<address class="tw-text-neutral-500">
										<?php echo format_customer_info($property_request, 'estimate', 'shipping'); ?>
									</address>
								<?php } ?>
								<p class="no-mbot">
									<span class="bold">
										<?php echo _l('real_created_date'); ?>:
									</span>
									<?php echo html_entity_decode($property_request->datecreated); ?>
								</p>
								<?php if ($property_request->related_id) { ?>
									<p class="no-mbot">
										<span class="bold"><?php echo _l('sale_agent_string'); ?>:</span>
										<?php if($property_request->related_type == 'staff' || $property_request->related_type == 'company'){ ?>
											<?php echo get_staff_full_name($property_request->related_id); ?>
										<?php }else{ ?>
											<?php echo get_broker_name($property_request->related_id); ?>
										<?php } ?>
									</p>
								<?php } ?>
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
							<?php if ($property_request->clientnote != '') { ?>
								<div class="col-md-12 mtop15">
									<p class="bold text-muted"><?php echo _l('estimate_note'); ?></p>
									<p><?php echo new_html_entity_decode($property_request->clientnote); ?></p>
								</div>
							<?php } ?>
							<?php if ($property_request->terms != '') { ?>
								<div class="col-md-12 mtop15">
									<p class="bold text-muted"><?php echo _l('terms_and_conditions'); ?></p>
									<p><?php echo new_html_entity_decode($property_request->terms); ?></p>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>

				<div role="tabpanel" class="tab-pane" id="tab_reminders">
					<a href="#" data-toggle="modal" class="btn btn-primary"
					data-target=".reminder-modal-estimate-<?php echo new_html_entity_decode($property_request->id); ?>"><i
					class="fa-regular fa-bell"></i>
					<?php echo _l('estimate_set_reminder_title'); ?></a>
					<hr />
					<?php render_datatable([ _l('reminder_description'), _l('reminder_date'), _l('reminder_staff'), _l('reminder_is_notified')], 'reminders'); ?>
					<?php $this->load->view('admin/includes/modals/reminder', ['id' => $property_request->id, 'name' => 'estimate', 'members' => $members, 'reminder_title' => _l('estimate_set_reminder_title')]); ?>
				</div>
				<div role="tabpanel" class="tab-pane ptop10" id="tab_emails_tracking">
					<?php
					$this->load->view(
						'admin/includes/emails_tracking',
						[
							'tracked_emails' => get_tracked_emails($property_request->id, 'estimate'), ]
						);
						?>
					</div>
					<div role="tabpanel" class="tab-pane" id="tab_notes">
						<?php echo form_open(html_entity_decode($site_url) . ('add_note/' . $property_request->id), ['id' => 'property-request-notes', 'class' => 'property-request-notes-form']); ?>
						<?php echo render_textarea('description'); ?>
						<div class="text-right">
							<button type="submit"
							class="btn btn-primary mtop15 mbot15"><?php echo _l('estimate_add_note'); ?></button>
						</div>
						<?php echo form_close(); ?>
						<hr />
						<div class="mtop20" id="sales_notes_area">
						</div>
					</div>

					<div role="tabpanel" class="tab-pane ptop10" id="tab_views">
						<?php
						$views_activity = get_views_tracking('estimate', $property_request->id);
						if (count($views_activity) === 0) {
							echo '<h4 class="tw-m-0 tw-text-base tw-font-medium tw-text-neutral-500">' . _l('not_viewed_yet', _l('estimate_lowercase')) . '</h4>';
						}
						foreach ($views_activity as $activity) { ?>
							<p class="text-success no-margin">
								<?php echo _l('view_date') . ': ' . _dt($activity['date']); ?>
							</p>
							<p class="text-muted">
								<?php echo _l('view_ip') . ': ' . $activity['view_ip']; ?>
							</p>
							<hr />
						<?php } ?>
					</div>

				</div>
			</div>
		</div>
	</div>
	<script>
		init_selectpicker();
		<?php if ($send_later) { ?>
			schedule_estimate_send(<?php echo new_html_entity_decode($property_request->id); ?>);
		<?php } ?>
	</script>
	<?php $this->load->view('companies/property_requests/request_send_to_client'); ?>