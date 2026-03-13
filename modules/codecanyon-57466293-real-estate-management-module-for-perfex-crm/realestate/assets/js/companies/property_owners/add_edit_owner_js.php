<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	(function($) {
		"use strict";

		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');

		appValidateForm($('#add_edit_owner'), {
			name: 'required',
			firstname: 'required',
			lastname: 'required',
			password: 'required',
			role: 'required',
			company_email: {
				required: true,
				email: true,
				remote: {
					url: page_url + "owner_exists",
					type: 'post',
					data: {
						company_email: function() {
							return $('input[name="company_email"]').val();
						},
						memberid: function() {
							return $('input[name="owner_id"]').val();
						}
					}
				}
			},
		});

		$('.sub-company-form-submiter').on('click', function() {
			"use strict"; 
			var form = $('#add_edit_owner');
			if (form.valid()) {
				form.submit();
			}
		});

		$('.billing-same-as-customer').on('click', function(e) {
			"use strict"; 

			e.preventDefault();
			$('textarea[name="billing_street"]').val($('textarea[name="address"]').val());
			$('input[name="billing_city"]').val($('input[name="city"]').val());
			$('input[name="billing_state"]').val($('input[name="state"]').val());
			$('input[name="billing_zip"]').val($('input[name="zip"]').val());
			$('select[name="billing_country"]').selectpicker('val', $('select[name="country"]').selectpicker('val'));
		});

		$('.customer-copy-billing-address').on('click', function(e) {
			"use strict"; 

			e.preventDefault();
			$('textarea[name="shipping_street"]').val($('textarea[name="billing_street"]').val());
			$('input[name="shipping_city"]').val($('input[name="billing_city"]').val());
			$('input[name="shipping_state"]').val($('input[name="billing_state"]').val());
			$('input[name="shipping_zip"]').val($('input[name="billing_zip"]').val());
			$('select[name="shipping_country"]').selectpicker('val', $('select[name="billing_country"]').selectpicker('val'));
		});


	})(jQuery);

	var company_id = $('input[name="company_id"]').val();

	var addMoreAttachmentsInputKey = 1;
	//button for adding more attachment in project
	$("body").on('click', '.add_more_attachments_file', function() {
		'use strict';

		if ($(this).hasClass('disabled')) {
			return false;
		}

		var total_attachments = $('.attachments input[name*="file"]').length;
		if ($(this).data('max') && total_attachments >= $(this).data('max')) {
			return false;
		}

		var newattachment = $('.attachments').find('.attachment').eq(0).clone().appendTo('.attachments');
		newattachment.find('input').removeAttr('aria-describedby aria-invalid');
		newattachment.find('input').attr('name', 'file[' + addMoreAttachmentsInputKey + ']').val('');
		newattachment.find($.fn.appFormValidator.internal_options.error_element + '[id*="error"]').remove();
		newattachment.find('.' + $.fn.appFormValidator.internal_options.field_wrapper_class).removeClass($.fn.appFormValidator.internal_options.field_wrapper_error_class);
		newattachment.find('i').removeClass('fa-plus').addClass('fa-minus');
		newattachment.find('button').removeClass('add_more_attachments_file').addClass('remove_attachment_file').removeClass('btn-success').addClass('btn-danger');
		addMoreAttachmentsInputKey++;
	});

	// Remove attachment
	$("body").on('click', '.remove_attachment_file', function() {
		'use strict';

		$(this).parents('.attachment').remove();
	}); 

	function preview_file(invoker){
		'use strict';

		var id = $(invoker).attr('id');
		var rel_id = $(invoker).attr('rel_id');
		view_file(id, rel_id);
	}

	function view_file(id, rel_id) {   
		'use strict';

		$('#contract_file_data').empty();
		$("#contract_file_data").load(page_url + 'owner_pdf_file/' + id + '/' + rel_id, function(response, status, xhr) {
			if (status == "error") {
				alert_float('danger', xhr.statusText);
			}
		});
	}

	function delete_owner_attachment_pdf_file(wrapper, id) {
		'use strict';

		if (confirm_delete()) {
			$.get(page_url + 'delete_owner_attachment_pdf_file/' + id, function (response) {
				if (response.success == true) {
					$(wrapper).parents('.contract-attachment-wrapper').remove();

					var totalAttachmentsIndicator = $('.attachments-indicator');
					var totalAttachments = totalAttachmentsIndicator.text().trim();
					if(totalAttachments == 1) {
						totalAttachmentsIndicator.remove();
					} else {
						totalAttachmentsIndicator.text(totalAttachments-1);
					}
				} else {
					alert_float('danger', response.message);
				}
			}, 'json');
		}
		return false;
	}

</script>