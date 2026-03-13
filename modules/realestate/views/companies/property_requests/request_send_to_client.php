<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade email-template" data-editor-id=".<?php echo 'tinymce-' . $property_request->id; ?>" id="property_request_send_to_client_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog" role="document">
		<?php echo form_open($site_url . 'send_to_email/' . $property_request->id); ?>
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close close-send-template-modal"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">
					<span class="edit-title"><?php echo _l('estimate_send_to_client_modal_heading'); ?></span>
				</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<?php
                            if ($template_disabled) {
                                echo '<div class="alert alert-danger">';
                                echo 'The email template <b><a href="' . $site_url . ('emails/email_template/' . $template_id) . '" target="_blank">' . $template_system_name . '</a></b> is disabled. Click <a href="' . $site_url . ('emails/email_template/' . $template_id) . '" target="_blank">here</a> to enable the email template in order to be sent successfully.';
                                echo '</div>';
                            }
                            $selected = [];
                            $contacts = $this->clients_model->get_contacts($property_request->clientid, ['active' => 1, 'estimate_emails' => 1]);
                            foreach ($contacts as $contact) {
                                array_push($selected, $contact['id']);
                            }
                             if (count($selected) == 0) {
                                 echo '<p class="text-danger">' . _l('sending_email_contact_permissions_warning', _l('customer_permission_estimate')) . '</p><hr />';
                             }
                            echo render_select('sent_to[]', $contacts, ['id', 'email', 'firstname,lastname'], 'invoice_estimate_sent_to_email', $selected, ['multiple' => true], [], '', '', false);
                            ?>
						</div>
						<?php echo render_input('cc', 'CC'); ?>
						<hr />
						<div class="checkbox checkbox-primary">
							<input type="checkbox" name="attach_pdf" id="attach_pdf" checked>
							<label for="attach_pdf"><?php echo _l('estimate_send_to_client_attach_pdf'); ?></label>
						</div>
						<h5 class="bold"><?php echo _l('estimate_send_to_client_preview_template'); ?></h5>
						<hr />
						<?php echo render_textarea('email_template_custom', '', $template->message, [], [], '', 'tinymce-' . $property_request->id); ?>
						<?php echo form_hidden('template_name', $template_name); ?>
					</div>
				</div>

				
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default close-send-template-modal"><?php echo _l('close'); ?></button>
						<button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-primary"><?php echo _l('send'); ?></button>
					</div>
				</div>
				<?php echo form_close(); ?>
			</div>
		</div>
