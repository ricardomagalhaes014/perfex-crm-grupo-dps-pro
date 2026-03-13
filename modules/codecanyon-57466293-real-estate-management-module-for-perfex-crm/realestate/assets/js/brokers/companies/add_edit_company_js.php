<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';
	var addMoreAttachmentsInputKey = 1;

	init_selectpicker();
	$(".selectpicker").selectpicker('refresh');

	(function($) {
		"use strict";

		appValidateForm($("body").find('#add_edit_company'), {
			plan_id: 'required',
			name: 'required',
			firstname: 'required',
			lastname: 'required',
			password: 'required',
			company_email: {
				required: false,
				email: true,
				remote: {
					url: page_url + "company_email_exists",
					type: 'post',
					data: {
						email: function() {
							return $('input[name="company_email"]').val();
						},
						related_type: function() {
							return $('input[name="related_type"]').val();
						},
						company_id: function() {
							return $('input[name="company_id"]').val();
						}
					}
				}
			},

		});

		$('.sub-company-form-submiter').on('click', function() {
			"use strict"; 

			var form = $('#add_edit_company');
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

		$('select[name="role"]').on('change', function() {
			'use strict';

			var roleid = $(this).val();
			init_roles_permissions(roleid, true);
		});

		$('input[name="administrator"]').on('change', function() {
			'use strict';

			var checked = $(this).prop('checked');
			var isNotStaffMember = $('.is-not-staff');
			if (checked == true) {
				isNotStaffMember.addClass('hide');
				$('.roles').find('input').prop('disabled', true).prop('checked', false);
			} else {
				isNotStaffMember.removeClass('hide');
				isNotStaffMember.find('input').prop('checked', false);
				$('.roles').find('.capability').not('[data-not-applicable="true"]').prop('disabled',
					false)
			}
		});

		$('#is_not_staff').on('change', function() {
			'use strict';

			var checked = $(this).prop('checked');
			var row_permission_leads = $('tr[data-name="leads"]');
			if (checked == true) {
				row_permission_leads.addClass('hide');
				row_permission_leads.find('input').prop('checked', false);
			} else {
				row_permission_leads.removeClass('hide');
			}
		});

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

		$("body").on('click', '.remove_attachment_file', function() {
			'use strict';

			$(this).parents('.attachment').remove();
		}); 

		$('select[name="plan_id"]').on('change', function() {
			'use strict';

			var plan_id = $(this).val();
			if(plan_id != ''){
				$('input[name="is_approval_manager"]').prop('disabled', false);
			}else{
				$('input[name="is_approval_manager"]').prop('disabled', true);
			}

			$.get(admin_url + 'realestate/get_role_by_plan_id/' + plan_id, function (response) {
				if (response.role_id ) {
					<?php if($related_type == 'company'){ ?>
						$('select[name="role"]').val(response.role_id).change();
					<?php }else{ ?>
						$('select[name="role"]').val(response.role_id).change();
					<?php } ?>
				}
			}, 'json');

		});

		$('input[name="is_approval_manager"]').on('change', function() {
			'use strict';

			var is_approval_manager = $(this).prop('checked');
			var plan_id = $('select[name="plan_id"]').val();

			$.get(admin_url + 'realestate/get_role_by_plan_id/' + plan_id, function (response) {
				if(is_approval_manager){
					if (response.approval_role_id ) {
						$('select[name="role"]').val(response.approval_role_id).change();
					}
				}else{
					if (response.role_id ) {
						<?php if($related_type == 'company'){ ?>
							$('select[name="role"]').val(response.role_id).change();
						<?php }else{ ?>
							$('select[name="role"]').val(response.role_id).change();
						<?php } ?>
					}
				}

			}, 'json');
		});


	})(jQuery);

	var company_id = $('input[name="company_id"]').val();
	var contact_id = get_url_param('contactid');
	if (contact_id) {
		add_staff(company_id, contact_id, 'updated');
	}

	function add_staff(company_id, staff_id, slug) {
		"use strict";

		$("#modal_wrapper").load("<?php echo admin_url('realestate/staff_modal'); ?>", {
			slug: slug,
			staff_id: staff_id,
			company_id: company_id,
		}, function() {

			$("body").find('#appointmentModal').modal({ show: true, backdrop: 'static' });

		});

		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');
	}

	function preview_file(invoker){
		'use strict';

		var id = $(invoker).attr('id');
		var rel_id = $(invoker).attr('rel_id');
		view_file(id, rel_id);
	}

	function view_file(id, rel_id) {   
		'use strict';

		$('#contract_file_data').empty();
		$("#contract_file_data").load(page_url + 'preview_file/' + id + '/' + rel_id, function(response, status, xhr) {
			if (status == "error") {
				alert_float('danger', xhr.statusText);
			}
		});
	}

	function delete_company_attachment_pdf_file(wrapper, id, folder_name) {
		'use strict';

		if (confirm_delete()) {
			$.get(page_url + 'delete_realestate_attachment/' + id + '/' + folder_name, function (response) {
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

	function add_broker_staff(company_id, staff_id, slug) {
		"use strict";

		$("#modal_wrapper").load("<?php echo admin_url('realestate/broker_staff_modal'); ?>", {
			slug: slug,
			staff_id: staff_id,
			company_id: company_id,
		}, function() {

			$("body").find('#appointmentModal').modal({ show: true, backdrop: 'static' });

		});

		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');
	}

</script>