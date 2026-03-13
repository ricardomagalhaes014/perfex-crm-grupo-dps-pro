<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if ((credits_can_be_applied_to_invoice($invoice->status) && $credits_available > 0)) { ?>
	<div class="alert alert-warning mbot5">
		<?php echo _l('x_credits_available', app_format_money($credits_available, $customer_currency->name)); ?>
		<br />
		<a href="#" data-toggle="modal" data-target="#apply_credits"><?php echo _l('apply_credits'); ?></a>
	</div>
<?php } ?>
<?php if (count($invoices_to_merge) > 0) { ?>
	<div class="panel_s no-padding mbot5 mergeable-invoices">
		<div class="panel-heading">
			<h4 class="panel-title">
				<?php echo _l('invoices_available_for_merging'); ?>
			</h4>
		</div>
		<div class="panel-body">
			<?php foreach ($invoices_to_merge as $_inv) { ?>
				<div class="tw-flex tw-justify-between tw-items-center tw-mb-2 last:tw-mb-0">
					<div>
						<a href="<?php echo site_url('realestate/broker/list_invoices/' . $_inv->id); ?>" target="_blank"
							class="tw-font-medium"><?php echo format_invoice_number($_inv->id); ?></a> -
							<span class="tw-text-neutral-500">
								<?php echo app_format_money($_inv->total, $_inv->currency_name); ?>
							</span>
						</div>
						<?php echo format_invoice_status($_inv->status); ?>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } ?>
	<?php echo form_hidden('_attachment_sale_id', $invoice->id); ?>
	<?php echo form_hidden('_attachment_sale_type', 'invoice'); ?>
	<div class="col-md-12 no-padding">
		<div class="panel_s">
			<div class="panel-body">
				<div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
					<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
					<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
					<div class="horizontal-tabs">
						<ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
							<li role="presentation" class="active">
								<a href="#tab_invoice" aria-controls="tab_invoice" role="tab" data-toggle="tab">
									<?php echo _l('invoice'); ?>
								</a>
							</li>
							<?php if (count($invoice->payments) > 0) { ?>
								<li role="presentation">
									<a href="#invoice_payments_received" aria-controls="invoice_payments_received" role="tab"
									data-toggle="tab">
									<?php echo _l('payments'); ?>
									<span class="badge"><?php echo count($invoice->payments); ?>
								</span>
							</a>
						</li>
					<?php } ?>
					<?php if (count($invoice_recurring_invoices) > 0 || $invoice->recurring != 0) { ?>
						<li role="presentation">
							<a href="#tab_child_invoices" aria-controls="tab_child_invoices" role="tab"
							data-toggle="tab">
							<?php echo _l('child_invoices'); ?>
						</a>
					</li>
				<?php } ?>
				
				<li role="presentation">
					<a href="#tab_activity" aria-controls="tab_activity" role="tab" data-toggle="tab">
						<?php echo _l('invoice_view_activity_tooltip'); ?>
					</a>
				</li>

		<li role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>"
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
	<li role="presentation" data-toggle="tooltip" title="<?php echo _l('view_tracking'); ?>"
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
		<?php echo format_invoice_status($invoice->status, 'mtop5 inline-block'); ?>
	</div>
	<div class="col-md-9 _buttons">
		<div class="visible-xs">
			<div class="mtop10"></div>
		</div>
		<div class="pull-right">
			<?php
			$_tooltip              = _l('invoice_sent_to_email_tooltip');
			$_tooltip_already_send = '';
			if ($invoice->sent == 1 && is_date($invoice->datesend)) {
				$_tooltip_already_send = _l('invoice_already_send_to_client_tooltip', time_ago($invoice->datesend));
			}
			?>
			<a href="<?php echo site_url('realestate/broker/invoice/' . $invoice->id); ?>" data-toggle="tooltip"
				title="<?php echo _l('edit_invoice_tooltip'); ?>" class="btn btn-default btn-with-tooltip"
				data-placement="bottom"><i class="fa-regular fa-pen-to-square"></i></a>
				<div class="btn-group">
					<a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
					aria-haspopup="true" aria-expanded="false"><i class="fa-regular fa-file-pdf"></i><?php if (is_mobile()) {
						echo ' PDF';
					} ?> <span class="caret"></span></a>
					<ul class="dropdown-menu dropdown-menu-right">
						<li class="hidden-xs"><a
							href="<?php echo site_url('realestate/broker/invoice_pdf/' . $invoice->id . '?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a>
						</li>
						<li class="hidden-xs"><a
							href="<?php echo site_url('realestate/broker/invoice_pdf/' . $invoice->id . '?output_type=I'); ?>"
							target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
							<li><a
								href="<?php echo site_url('realestate/broker/invoice_pdf/' . $invoice->id); ?>"><?php echo _l('download'); ?></a>
							</li>
							<li>
								<a href="<?php echo site_url('realestate/broker/invoice_pdf/' . $invoice->id . '?print=true'); ?>"
									target="_blank">
									<?php echo _l('print'); ?>
								</a>
							</li>
						</ul>
					</div>
					<?php if (!empty($invoice->clientid)) { ?>
						<span<?php if ($invoice->status == Invoices_model::STATUS_CANCELLED) { ?> data-toggle="tooltip"
							data-title="<?php echo _l('invoice_cancelled_email_disabled'); ?>" <?php } ?>>
							<a href="#" class="invoice-send-to-client btn-with-tooltip btn btn-default<?php if ($invoice->status == Invoices_model::STATUS_CANCELLED) {
								echo ' disabled';
							} ?>" data-toggle="tooltip" title="<?php echo new_html_entity_decode($_tooltip); ?>" data-placement="bottom"><span
							data-toggle="tooltip" data-title="<?php echo new_html_entity_decode($_tooltip_already_send); ?>"><i
							class="fa-regular fa-envelope"></i></span></a>
						</span>
					<?php } ?>
					<!-- Single button -->
					<div class="btn-group">
						<button type="button" class="btn btn-default pull-left dropdown-toggle"
						data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?php echo _l('more'); ?> <span class="caret"></span>
					</button>
					<ul class="dropdown-menu dropdown-menu-right">
						<li><a href="<?php echo site_url('invoice/' . $invoice->id . '/' . $invoice->hash) ?>"
							target="_blank"><?php echo _l('view_invoice_as_customer_tooltip'); ?></a>
						</li>
						<li>
							<?php hooks()->do_action('after_invoice_view_as_client_link', $invoice); ?>
							<?php if (is_invoice_overdue($invoice) && is_invoices_overdue_reminders_enabled()) { ?>
								<a
								href="<?php echo site_url('realestate/broker/send_overdue_notice/' . $invoice->id); ?>">
								<?php echo _l('send_overdue_notice_tooltip'); ?>
							</a>
						<?php } ?>
					</li>

					<li>
						<a href="#" data-toggle="modal"
						data-target="#sales_attach_file"><?php echo _l('invoice_attach_file'); ?></a>
					</li>

					<?php if ($invoice->sent == 0) { ?>
						<li>
							<a
							href="<?php echo site_url('realestate/broker/mark_as_sent/' . $invoice->id); ?>"><?php echo _l('invoice_mark_as_sent'); ?></a>
						</li>
					<?php } ?>

					<li>
						<?php if ($invoice->status != Invoices_model::STATUS_CANCELLED
							&& $invoice->status != Invoices_model::STATUS_PAID
							&& $invoice->status != Invoices_model::STATUS_PARTIALLY) { ?>
								<a
								href="<?php echo site_url('realestate/broker/mark_as_cancelled/' . $invoice->id); ?>"><?php echo _l('invoice_mark_as', _l('invoice_status_cancelled')); ?></a>
							<?php } elseif ($invoice->status == Invoices_model::STATUS_CANCELLED) { ?>
								<a
								href="<?php echo site_url('realestate/broker/unmark_as_cancelled/' . $invoice->id); ?>"><?php echo _l('invoice_unmark_as', _l('invoice_status_cancelled')); ?></a>
							<?php } ?>
						</li>

							<?php
							if ((get_option('delete_only_on_last_invoice') == 1 && is_last_invoice($invoice->id)) || (get_option('delete_only_on_last_invoice') == 0)) { ?>
								<li data-toggle="tooltip" data-title="<?php echo _l('delete_invoice_tooltip'); ?>">
									<a href="<?php echo site_url('realestate/broker/delete/' . $invoice->id); ?>"
										class="text-danger delete-text _delete"><?php echo _l('delete_invoice'); ?></a>
									</li>
								<?php } ?>
								<?php hooks()->do_action('after_invoice_preview_more_menu'); ?>
							</ul>
						</div>
						<?php if (abs($invoice->total) > 0) { ?>
							<a href="#" onclick="record_payment(<?php echo new_html_entity_decode($invoice->id); ?>); return false;" class="mleft10 pull-right btn btn-success<?php if ($invoice->status == Invoices_model::STATUS_PAID || $invoice->status == Invoices_model::STATUS_CANCELLED) {
								echo ' disabled';
							} ?>">
							<i class="fa fa-plus-square"></i> <?php echo _l('payment'); ?></a>
						<?php } ?>
					</div>
				</div>
				<?php
				if (is_invoice_overdue($invoice)) { ?>
					<div class="col-md-12">
						<p class="text-danger tw-mt-2.5 tw-mb-0">
							<?php echo _l('invoice_is_overdue', get_total_days_overdue($invoice->duedate)); ?>
						</p>
					</div>
				<?php } ?>
			</div>
			<div class="clearfix"></div>
			<hr class="hr-panel-separator" />
			<div class="tab-content">
				<div role="tabpanel" class="tab-pane active" id="tab_invoice">
					<?php if ($invoice->status == Invoices_model::STATUS_CANCELLED && $invoice->recurring > 0) { ?>
						<div class="alert alert-info">
							Recurring invoice with status Cancelled <b>is still ongoing recurring invoice</b>. If you want
							to stop this recurring invoice you should update the invoice recurring field to <b>No</b>.
						</div>
					<?php } ?>
					<?php $this->load->view('brokers_portals/invoices/invoice_preview_html'); ?>
				</div>
				<?php if (count($invoice->payments) > 0) { ?>
					<div class="tab-pane" role="tabpanel" id="invoice_payments_received">
						<?php $this->load->view('brokers_portals/invoices/invoice_payments_table'); ?>
					</div>
				<?php } ?>

				<?php if (count($invoice_recurring_invoices) > 0 || $invoice->recurring != 0) { ?>
					<div role="tabpanel" class="tab-pane" id="tab_child_invoices">
						<?php if (count($invoice_recurring_invoices)) { ?>
							<p class="tw-text-lg tw-font-medium">
								<?php echo _l('invoice_add_edit_recurring_invoices_from_invoice'); ?></p>
								<ul class="list-group">
									<?php foreach ($invoice_recurring_invoices as $recurring) { ?>
										<li class="list-group-item">
											<a href="<?php echo site_url('realestate/broker/list_invoices/' . $recurring->id); ?>"
												class="tw-font-semibold"
												onclick="init_invoice(<?php echo new_html_entity_decode($recurring->id); ?>); return false;"
												target="_blank"><?php echo format_invoice_number($recurring->id); ?>
												<span
												class="pull-right bold"><?php echo app_format_money($recurring->total, $recurring->currency_name); ?></span>
											</a>
											<br />
											<span class="inline-block tw-mt-1">
												<?php echo '<span class="bold">' . _d($recurring->date) . '</span>'; ?><br />
												<?php echo format_invoice_status($recurring->status, '', false); ?>
											</span>
										</li>
									<?php } ?>
								</ul>
							<?php } else { ?>
								<p class="bold"><?php echo _l('no_child_found', _l('invoices')); ?></p>
							<?php } ?>
						</div>
					<?php } ?>
					<div role="tabpanel" class="tab-pane ptop10" id="tab_emails_tracking">
						<?php
						$this->load->view(
							'admin/includes/emails_tracking',
							[
								'tracked_emails' => get_tracked_emails($invoice->id, 'invoice'), ]
							);
							?>
						</div>

						<div role="tabpanel" class="tab-pane ptop10" id="tab_activity">
							<div class="row">
								<div class="col-md-12">
									<div class="activity-feed">
										<?php foreach ($activity as $activity) {
											$_custom_data = false; ?>
											<div class="feed-item" data-sale-activity-id="<?php echo new_html_entity_decode($activity['id']); ?>">
												<div class="date">
													<span class="text-has-action" data-toggle="tooltip"
													data-title="<?php echo _dt($activity['date']); ?>">
													<?php echo time_ago($activity['date']); ?>
												</span>
											</div>
											<div class="text">
												
												<?php
												$additional_data = '';

												if (!empty($activity['additional_data'])) {
													$additional_data = unserialize($activity['additional_data']);
													$i               = 0;

													foreach ($additional_data as $data) {
														if (strpos($data, '<original_status>') !== false) {
															$original_status     = get_string_between($data, '<original_status>', '</original_status>');
															$additional_data[$i] = format_invoice_status($original_status, '', false);
														} elseif (strpos($data, '<new_status>') !== false) {
															$new_status          = get_string_between($data, '<new_status>', '</new_status>');
															$additional_data[$i] = format_invoice_status($new_status, '', false);
														} elseif (strpos($data, '<custom_data>') !== false) {
															$_custom_data = get_string_between($data, '<custom_data>', '</custom_data>');
															unset($additional_data[$i]);
														}
														$i++;
													}
												}

												$additional_data = str_replace('admin/payments/payment', 'realestate/broker/payment', $additional_data);
												
												$_formatted_activity = _l($activity['description'], $additional_data);
												if ($_custom_data !== false) {
													$_formatted_activity .= ' - ' . $_custom_data;
												}
												if (!empty($activity['full_name'])) {
													$_formatted_activity = $activity['full_name'] . ' - ' . $_formatted_activity;
												}
												echo new_html_entity_decode($_formatted_activity);
												if (is_admin()) {
													echo '<a href="#" class="pull-right text-danger" onclick="delete_sale_activity(' . $activity['id'] . '); return false;"><i class="fa fa-remove"></i></a>';
												} ?>
											</div>
										</div>
										<?php
									} ?>
								</div>
							</div>
						</div>
					</div>
					<div role="tabpanel" class="tab-pane ptop10" id="tab_views">
						<?php
						$views_activity = get_views_tracking('invoice', $invoice->id);
						if (count($views_activity) === 0) {
							echo '<h4 class="tw-m-0 tw-text-base tw-font-medium tw-text-neutral-500">' . _l('not_viewed_yet', _l('invoice_lowercase')) . '</h4>';
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
	<?php $this->load->view('brokers_portals/invoices/invoice_send_to_client'); ?>
	<?php 
	require 'modules/realestate/assets/js/brokers/invoices/invoice_preview_template_js.php';
	?>
	<?php hooks()->do_action('after_invoice_preview_template_rendered', $invoice); ?>