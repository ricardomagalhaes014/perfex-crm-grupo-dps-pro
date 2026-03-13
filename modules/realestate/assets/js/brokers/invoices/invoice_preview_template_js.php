<script>
	$(function(){
		'use strict';
		
		init_datepicker();
		init_selectpicker();
		<?php if ($record_payment) { ?>
			record_payment(<?php echo html_entity_decode($invoice->id); ?>);
		<?php } elseif ($send_later) { ?>
			schedule_invoice_send(<?php echo html_entity_decode($invoice->id); ?>);
		<?php } ?>

		$("#sales_attach_file").on("hidden.bs.modal", function (e) {
			$("#sales_uploaded_files_preview").empty();
			$(".dz-file-preview").empty();
		});


		$("body").on("click", ".invoice-send-to-client", function (e) {
			e.preventDefault();
			$("#invoice_send_to_client_modal").modal("show");
		});

	});

	function delete_invoice_attachment(id) {
		'use strict';

		if (confirm_delete()) {
			requestGet("realestate/broker/delete_invoice_attachment/" + id)
			.done(function (success) {
				if (success == 1) {
					$("body")
					.find('[data-attachment-id="' + id + '"]')
					.remove();
					init_invoice(
						$("body").find('input[name="_attachment_sale_id"]').val()
						);
				}
			})
			.fail(function (error) {
				alert_float("danger", error.responseText);
			});
		}
	}

	function toggle_file_visibility(attachment_id, rel_id, invoker) {
		'use strict';

		requestGet("realestate/broker/toggle_file_visibility/" + attachment_id).done(function (
			response
			) {
			if (response == 1) {
				$(invoker)
				.find("i")
				.removeClass("fa fa-toggle-off")
				.addClass("fa fa-toggle-on");
			} else {
				$(invoker)
				.find("i")
				.removeClass("fa fa-toggle-on")
				.addClass("fa fa-toggle-off");
			}
		});
	}

	// Record payment function
	function record_payment(id) {
		'use strict';
		
		if (typeof id == "undefined" || id === "") {
			return;
		}
		$("#invoice").load(site_url + "realestate/broker/record_invoice_payment_ajax/" + id);
	}
</script>