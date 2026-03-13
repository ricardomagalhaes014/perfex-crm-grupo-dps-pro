<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php broker_init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<?php
			if (isset($contract) && $contract->signed == 1) { ?>
				<div class="col-md-12">
					<div class="alert alert-warning">
						<?php echo  _l('contract_signed_not_all_fields_editable'); ?>
					</div>
				</div>
			<?php } ?>
			<div class="col-md-5 left-column">
				<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
					<?php echo _l('contract_information') ?>
					<?php
					if (isset($contract) && $contract->trash > 0) {
						echo '<div class="label label-default"><span>' . _l('contract_trash') . '</span></div>';
					}
					?>
				</h4>
				<div class="panel_s">
					<div class="panel-body">
						<?php echo form_open($this->uri->uri_string(), ['id' => 'contract-form']); ?>
						<div class="form-group">
							<div class="checkbox checkbox-primary no-mtop checkbox-inline">
								<input type="checkbox" id="trash" name="trash" <?php echo isset($contract) && html_entity_decode($contract->trash) ?? false == 1 ? 'checked' : ''; ?>>
								<label for="trash"><i class="fa-regular fa-circle-question" data-toggle="tooltip" data-placement="right" title="<?php echo _l('contract_trash_tooltip'); ?>"></i>
									<?php echo _l('contract_trash'); ?>
								</label>
							</div>
							<div class="checkbox checkbox-primary checkbox-inline">
								<input type="checkbox" name="not_visible_to_client" id="not_visible_to_client"
								<?php echo isset($contract) && ($contract->not_visible_to_client) ?? false == 1 ? 'checked' : ''; ?>>
								<label for="not_visible_to_client">
									<?php echo _l('contract_not_visible_to_client'); ?>
								</label>
							</div>
						</div>
						<div class="form-group select-placeholder f_client_id">
							<label for="clientid" class="control-label"><span class="text-danger">* </span>
								<?php echo _l('contract_client_string'); ?>
							</label>
							<select id="clientid" name="client" data-live-search="true" data-width="100%" class="ajax-search" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" <?php echo isset($contract) && $contract->signed == 1 ? ' disabled' : ''; ?>>
								<?php $selected = (isset($contract) ? $contract->client : '');
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
						<div class="form-group select-placeholder projects-wrapper<?php if ((!isset($contract)) || (isset($contract) && !customer_has_projects($contract->client))) { echo ' hide'; } ?>">
							<label for="project_id"><?php echo _l('project'); ?></label>
							<div id="project_ajax_search_wrapper">
								<select name="project_id" id="project_id" class="projects ajax-search ays-ignore" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"
									<?php echo isset($contract) && $contract->signed == 1 ? ' disabled' : ''; ?>>
									<?php
									if (isset($contract) && $contract->project_id != 0) {
										echo '<option value="' . $contract->project_id . '" selected>' . get_project_name_by_id($contract->project_id) . '</option>';
									}
									?>
								</select>
							</div>
						</div>

						<?php $value = (isset($contract) ? $contract->subject : ''); ?>
						<i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
						title="<?php echo _l('contract_subject_tooltip'); ?>"></i>
						<?php echo render_input('subject', 'contract_subject', $value); ?>
						<div class="form-group">
							<label for="contract_value"><?php echo _l('contract_value'); ?></label>
							<div class="input-group" data-toggle="tooltip"
							title="<?php echo isset($contract) && $contract->signed == 1 ? '' : _l('contract_value_tooltip'); ?>">
							<input type="number" class="form-control" name="contract_value"
							value="<?php echo isset($contract) ? $contract->contract_value : ''; ?>"
							<?php echo isset($contract) && $contract->signed == 1 ? ' disabled' : ''; ?>>
							<div class="input-group-addon">
								<?php echo html_entity_decode($base_currency->symbol); ?>
							</div>
						</div>
					</div>
					<?php
					$selected = (isset($contract) ? $contract->contract_type : '');
					echo render_select_with_input_group('contract_type', $types, ['id', 'name'], 'contract_type', $selected, '<div class="input-group-btn"><a href="#" class="btn btn-default" onclick="new_type();return false;"><i class="fa fa-plus"></i></a></div>');

					?>
					<div class="row">
						<div class="col-md-6">
							<?php $value = (isset($contract) ? _d($contract->datestart) : _d(date('Y-m-d'))); ?>
							<?php echo render_date_input(
								'datestart',
								'contract_start_date',
								$value,
								isset($contract) && $contract->signed == 1 ? ['disabled' => true] : []
							); ?>
						</div>
						<div class="col-md-6">
							<?php $value = (isset($contract) ? _d($contract->dateend) : ''); ?>
							<?php echo render_date_input(
								'dateend',
								'contract_end_date',
								$value,
								isset($contract) && $contract->signed == 1 ? ['disabled' => true] : []
							); ?>
						</div>
					</div>
					<?php $value = (isset($contract) ? $contract->description : ''); ?>
					<?php echo render_textarea('description', 'contract_description', $value, ['rows' => 10]); ?>
					<?php $rel_id = (isset($contract) ? $contract->id : false); ?>
					<?php echo render_custom_fields('contracts', $rel_id); ?>

					<div class="btn-bottom-toolbar text-right">
						<button type="submit" class="btn btn-primary">
							<?php echo _l('submit'); ?>
						</button>
					</div>
					<?php echo form_close(); ?>
				</div>
			</div>
		</div>

		<?php if (isset($contract)) { ?>
			<div class="col-md-7 right-column">
				<div class="sm:tw-flex sm:tw-justify-between sm:tw-items-center tw-mb-1 -tw-mt-px">
					<h4 class="tw-my-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
						<?php echo html_entity_decode($contract->subject); ?>
					</h4>
					<div>
						<div class="_buttons tw-space-x-1">
							<a href="<?php echo site_url('contract/' . $contract->id . '/' . $contract->hash); ?>"
								target="_blank">
								<?php echo _l('view_contract'); ?>
							</a>
							<div class="btn-group">
								<a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
								aria-haspopup="true" aria-expanded="false"><i
								class="fa-regular fa-file-pdf"></i><?php echo is_mobile() ? 'PDF' : ''; ?> <span
								class="caret"></span></a>
								<ul class="dropdown-menu dropdown-menu-right">
									<li class="hidden-xs"><a
										href="<?php echo site_url('realestate/broker/contract_pdf/' . $contract->id . '?output_type=I'); ?>">
										<?php echo _l('view_pdf'); ?>
									</a>
								</li>
								<li class="hidden-xs">
									<a href="<?php echo site_url('realestate/broker/contract_pdf/' . $contract->id . '?output_type=I'); ?>"
										target="_blank"><?php echo _l('view_pdf_in_new_window'); ?>
									</a>
								</li>
								<li><a
									href="<?php echo site_url('realestate/broker/contract_pdf/' . $contract->id); ?>"><?php echo _l('download'); ?></a>
								</li>
								<li>
									<a href="<?php echo site_url('realestate/broker/contract_pdf/' . $contract->id . '?print=true'); ?>"
										target="_blank">
										<?php echo _l('print'); ?>
									</a>
								</li>
							</ul>
						</div>
						<a href="#" class="btn btn-default" data-target="#contract_send_to_client_modal"
						data-toggle="modal"><span class="btn-with-tooltip" data-toggle="tooltip"
						data-title="<?php echo _l('contract_send_to_email'); ?>" data-placement="bottom">
						<i class="fa-regular fa-envelope"></i></span>
					</a>
					<div class="btn-group">
						<button type="button" class="btn btn-default pull-left dropdown-toggle"
						data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?php echo _l('more'); ?> <span class="caret"></span>
					</button>
					<ul class="dropdown-menu dropdown-menu-right">
						<li>
							<a href="<?php echo site_url('contract/' . $contract->id . '/' . $contract->hash); ?>"
								target="_blank">
								<?php echo _l('view_contract'); ?>
							</a>
						</li>
						<?php
						if ($contract->signed == 0 && $contract->marked_as_signed == 0) { ?>
							<li>
								<a href="<?php echo site_url('realestate/broker/mark_as_signed/' . $contract->id); ?>">
									<?php echo _l('mark_as_signed'); ?>
								</a>
							</li>
						<?php } elseif ($contract->signed == 0 && $contract->marked_as_signed == 1) { ?>
							<li>
								<a
								href="<?php echo site_url('realestate/broker/unmark_as_signed/' . $contract->id); ?>">
								<?php echo _l('unmark_as_signed'); ?>
							</a>
						</li>
					<?php } ?>
					<?php hooks()->do_action('after_contract_view_as_client_link', $contract); ?>
					<li>
						<a href="<?php echo site_url('realestate/broker/copy/' . $contract->id); ?>">
							<?php echo _l('contract_copy'); ?>
						</a>
					</li>
					<?php if ($contract->signed == 1) { ?>
						<li>
							<a href="<?php echo site_url('realestate/broker/clear_signature/' . $contract->id); ?>"
								class="_delete">
								<?php echo _l('clear_signature'); ?>
							</a>
						</li>
					<?php } ?>
					<li>
						<a href="<?php echo site_url('realestate/broker/delete/' . $contract->id); ?>"
							class="_delete">
							<?php echo _l('delete'); ?>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>

</div>

<div class="panel_s">
	<div class="panel-body">
		<div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
			<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
			<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
			<div class="horizontal-tabs">
				<ul class="nav nav-tabs contract-tab nav-tabs-horizontal mbot15" role="tablist">
					<li role="presentation" class="<?php if (!$this->input->get('tab') || $this->input->get('tab') == 'tab_content') {
						echo 'active';
					} ?>">
					<a href="#tab_content" aria-controls="tab_content" role="tab" data-toggle="tab">
						<?php echo _l('contract_content'); ?>
					</a>
				</li>
				<li role="presentation" class="<?php if ($this->input->get('tab') == 'attachments') {
					echo 'active';
				} ?>">
				<a href="#attachments" aria-controls="attachments" role="tab" data-toggle="tab">
					<?php echo _l('contract_attachments'); ?>
					<?php if ($totalAttachments = count($contract->attachments)) { ?>
						<span
						class="badge attachments-indicator"><?php echo html_entity_decode($totalAttachments); ?></span>
					<?php } ?>
				</a>
			</li>
			<li role="presentation">
				<a href="#tab_comments" aria-controls="tab_comments" role="tab"
				data-toggle="tab" onclick="get_contract_comments(); return false;">
				<?php echo _l('contract_comments'); ?>
				<?php
				$totalComments = total_rows(db_prefix() . 'contract_comments', 'contract_id=' . $contract->id)
				?>
				<span
				class="badge comments-indicator<?php echo html_entity_decode($totalComments) == 0 ? ' hide' : ''; ?>"><?php echo html_entity_decode($totalComments); ?></span>
			</a>
		</li>
		<li role="presentation" class="<?php if ($this->input->get('tab') == 'renewals') {
			echo 'active';
		} ?>">
		<a href="#renewals" aria-controls="renewals" role="tab" data-toggle="tab">
			<?php echo _l('no_contract_renewals_history_heading'); ?>
			<?php if ($totalRenewals = count($contract_renewal_history)) { ?>
				<span class="badge"><?php echo html_entity_decode($totalRenewals); ?></span>
			<?php } ?>
		</a>
	</li>


	<li role="presentation" data-toggle="tooltip"
	title="<?php echo _l('emails_tracking'); ?>" class="tab-separator">
	<a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab"
	data-toggle="tab">
	<?php if (!is_mobile()) { ?>
		<i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
	<?php } else { ?>
		<?php echo _l('emails_tracking'); ?>
	<?php } ?>
</a>
</li>
<li role="presentation" class="tab-separator toggle_view">
	<a href="#" onclick="contract_full_view(); return false;" data-toggle="tooltip"
	data-title="<?php echo _l('toggle_full_view'); ?>">
	<i class="fa fa-expand"></i></a>
</li>
</ul>
</div>
</div>
<div class="tab-content">
	<div role="tabpanel" class="tab-pane<?php if (!$this->input->get('tab') || $this->input->get('tab') == 'tab_content') {
		echo ' active';
	} ?>" id="tab_content">
	<div class="row mtop20">
		<?php if ($contract->signed == 1) { ?>
			<div class="col-md-12">
				<div class="alert alert-success">
					<?php echo _l(
						'document_signed_info',
						[
							'<b>' . $contract->acceptance_firstname . ' ' . $contract->acceptance_lastname . '</b> (<a href="mailto:' . $contract->acceptance_email . '">' . $contract->acceptance_email . '</a>)',
							'<b>' . _dt($contract->acceptance_date) . '</b>',
							'<b>' . $contract->acceptance_ip . '</b>', ]
						); ?>
					</div>
				</div>
			<?php } elseif ($contract->marked_as_signed == 1) { ?>
				<div class="col-md-12">
					<div class="alert alert-info">
						<?php echo _l('contract_marked_as_signed_info'); ?>
					</div>
				</div>
			<?php } ?>

			<div class="col-md-12">
				<?php if (isset($contract_merge_fields)) { ?>
					<p class="bold text-right no-mbot"><a href="#"
						onclick="slideToggle('.avilable_merge_fields'); return false;"><?php echo _l('available_merge_fields'); ?></a>
					</p>
					<div class=" avilable_merge_fields mtop15 hide">
						<ul class="list-group">
							<?php
							foreach ($contract_merge_fields as $field) {
								foreach ($field as $f) {
									echo '<li class="list-group-item"><b>' . $f['name'] . '</b>  <a href="#" class="pull-right" onclick="insert_merge_field(this); return false">' . $f['key'] . '</a></li>';
								}
							}
							?>
						</ul>
					</div>
				<?php } ?>
			</div>
		</div>
		<hr class="hr-panel-separator" />

		<div class="contract-content tc-content<?php if (
			!($contract->signed == 1)) {
			echo ' editable';
		} ?>">
		<?php
		if (empty($contract->content)) {
			echo hooks()->apply_filters('new_contract_default_content', '<span class="text-danger text-uppercase mtop15 editor-add-content-notice"> ' . _l('click_to_add_content') . '</span>');
		} else {
			echo html_entity_decode($contract->content);
		}
		?>
	</div>
	<?php if (!empty($contract->signature)) { ?>
		<div class="row mtop25">
			<div class="col-md-6 col-md-offset-6 text-right">
				<div class="bold">
					<p class="no-mbot">
						<?php echo _l('contract_signed_by') . ": {$contract->acceptance_firstname} {$contract->acceptance_lastname}"?>
					</p>
					<p class="no-mbot">
						<?php echo _l('contract_signed_date') . ': ' . _dt($contract->acceptance_date) ?>
					</p>
					<p class="no-mbot">
						<?php echo _l('contract_signed_ip') . ": {$contract->acceptance_ip}"?>
					</p>
				</div>
				<p class="bold"><?php echo _l('document_customer_signature_text'); ?>
				<?php if ($contract->signed == 1 ) { ?>
					<a href="<?php echo site_url('realestate/broker/clear_signature/' . $contract->id); ?>"
						data-toggle="tooltip" title="<?php echo _l('clear_signature'); ?>"
						class="_delete text-danger">
						<i class="fa fa-remove"></i>
					</a>
				<?php } ?>
			</p>
			<div class="pull-right">
				<img src="<?php echo site_url('download/preview_image?path=' . protected_file_url_by_path(get_upload_path_by_type('contract') . $contract->id . '/' . $contract->signature)); ?>"
				class="img-responsive" alt="">
			</div>
		</div>
	</div>
<?php } ?>
</div>

<div role="tabpanel" class="tab-pane" id="tab_comments">
	<div class="row contract-comments mtop15">
		<div class="col-md-12">
			<div id="contract-comments"></div>
			<div class="clearfix"></div>
			<textarea name="content" id="comment" rows="4"
			class="form-control mtop15 contract-comment"></textarea>
			<button type="button" class="btn btn-primary mtop10 pull-right"
			onclick="add_contract_comment();"><?php echo _l('proposal_add_comment'); ?></button>
		</div>
	</div>
</div>
<div role="tabpanel" class="tab-pane<?php if ($this->input->get('tab') == 'attachments') {
	echo ' active';
} ?>" id="attachments">
<?php echo form_open(site_url('realestate/broker/add_contract_attachment/' . $contract->id), ['id' => 'contract-attachments-form', 'class' => 'dropzone mtop15']); ?>
<?php echo form_close(); ?>
<div class="tw-flex tw-justify-end tw-items-center tw-space-x-2 mtop15">
	<button class="gpicker" data-on-pick="contractGoogleDriveSave">
		<i class="fa-brands fa-google" aria-hidden="true"></i>
		<?php echo _l('choose_from_google_drive'); ?>
	</button>
	<div id="dropbox-chooser"></div>
</div>

<div id="contract_attachments" class="mtop30">
	<?php
	$data = '<div class="row">';
	foreach ($contract->attachments as $attachment) {
		$href_url = site_url('download/file/contract/' . $attachment['attachment_key']);
		if (!empty($attachment['external'])) {
			$href_url = $attachment['external_link'];
		}
		$data .= '<div class="display-block contract-attachment-wrapper">';
		$data .= '<div class="col-md-10">';
		$data .= '<div class="pull-left"><i class="' . get_mime_class($attachment['filetype']) . '"></i></div>';
		$data .= '<a href="' . $href_url . '"' . (!empty($attachment['external']) ? ' target="_blank"' : '') . '>' . $attachment['file_name'] . '</a>';
		$data .= '<p class="text-muted">' . $attachment['filetype'] . '</p>';
		$data .= '</div>';
		$data .= '<div class="col-md-2 text-right">';
		$data .= '<a href="#" class="text-danger" onclick="delete_contract_attachment(this,' . $attachment['id'] . '); return false;"><i class="fa fa fa-times"></i></a>';
		$data .= '</div>';
		$data .= '<div class="clearfix"></div><hr/>';
		$data .= '</div>';
	}
	$data .= '</div>';
	echo html_entity_decode($data);
	?>
</div>
</div>
<div role="tabpanel" class="tab-pane<?php if ($this->input->get('tab') == 'renewals') {
	echo ' active';
} ?>" id="renewals">
<div class="mtop20">
	<div class="_buttons">
		<a href="#" class="btn btn-default" data-toggle="modal"
		data-target="#renew_contract_modal">
		<i class="fa fa-refresh"></i> <?php echo _l('contract_renew_heading'); ?>
	</a>
</div>
<hr />
<div class="clearfix"></div>
<?php
if (count($contract_renewal_history) == 0) {
	echo '<p class="tw-m-0 tw-text-base tw-font-medium tw-text-neutral-500">' . _l('no_contract_renewals_found') . '</p>';
}
foreach ($contract_renewal_history as $renewal) { ?>
	<div class="display-block">
		<div class="media-body">
			<div class="display-block">
				<b>
					<?php
					echo _l('contract_renewed_by', $renewal['renewed_by']);
					?>
				</b>
				<a href="<?php echo site_url('realestate/broker/delete_renewal_contract/' . $renewal['id'] . '/' . $renewal['contractid']); ?>"
					class="pull-right _delete text-danger"><i
					class="fa fa-remove"></i></a>
					<br />
					<small
					class="text-muted"><?php echo _dt($renewal['date_renewed']); ?></small>
					<hr class="hr-10" />
					<span class="text-success bold" data-toggle="tooltip"
					title="<?php echo _l('contract_renewal_old_start_date', _d($renewal['old_start_date'])); ?>">
					<?php echo _l('contract_renewal_new_start_date', _d($renewal['new_start_date'])); ?>
				</span>
				<br />
				<?php if (is_date($renewal['new_end_date'])) {
					$tooltip = '';
					if (is_date($renewal['old_end_date'])) {
						$tooltip = _l('contract_renewal_old_end_date', _d($renewal['old_end_date']));
					} ?>
					<span class="text-success bold" data-toggle="tooltip"
					title="<?php echo html_entity_decode($tooltip); ?>">
					<?php echo _l('contract_renewal_new_end_date', _d($renewal['new_end_date'])); ?>
				</span>
				<br />
				<?php
			} ?>
			<?php if ($renewal['new_value'] > 0) {
				$contract_renewal_value_tooltip = '';
				if ($renewal['old_value'] > 0) {
					$contract_renewal_value_tooltip = ' data-toggle="tooltip" data-title="' . _l('contract_renewal_old_value', app_format_money($renewal['old_value'], $base_currency)) . '"';
				} ?>
				<span class="text-success bold"
				<?php echo html_entity_decode($contract_renewal_value_tooltip); ?>>
				<?php echo _l('contract_renewal_new_value', app_format_money($renewal['new_value'], $base_currency)); ?>
			</span>
			<br />
			<?php
		} ?>
	</div>
</div>
<hr />
</div>
<?php } ?>
</div>
</div>
<div role="tabpanel" class="tab-pane ptop10" id="tab_emails_tracking">
	<?php
	$this->load->view('admin/includes/emails_tracking', [
		'tracked_emails' => get_tracked_emails($contract->id, 'contract'),
	]);
	?>
</div>

</div>
</div>
</div>
</div>
<?php } ?>
</div>
<div class="btn-bottom-pusher"></div>
</div>
</div>
<div id="modal-wrapper"></div>
<?php broker_init_tail(); ?>
<?php if (isset($contract)) { ?>
	<!-- init table tasks -->
	<script>
		var contract_id = '<?php echo html_entity_decode($contract->id); ?>';
	</script>
	<?php $this->load->view('brokers_portals/contracts/send_to_client'); ?>
	<?php $this->load->view('brokers_portals/contracts/renew_contract'); ?>
<?php } ?>
<?php $this->load->view('brokers_portals/contracts/contract_type'); ?>
<?php 
require 'modules/realestate/assets/js/brokers/contracts/contract_js.php';
?>
</body>

</html>
