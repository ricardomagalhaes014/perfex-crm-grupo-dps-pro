<script type="text/javascript">
	$(function() {
		"use strict";
		
		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');

		$('select[name="role"]').on('change', function() {
			var roleid = $(this).val();
			rel_init_roles_permissions(roleid, true);
		});

		$('input[name="administrator"]').on('change', function() {
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
			var checked = $(this).prop('checked');
			var row_permission_leads = $('tr[data-name="leads"]');
			if (checked == true) {
				row_permission_leads.addClass('hide');
				row_permission_leads.find('input').prop('checked', false);
			} else {
				row_permission_leads.removeClass('hide');
			}
		});

		appValidateForm($('.staff-form'), {
			firstname: 'required',
			lastname: 'required',
			username: 'required',
			role: 'required',
			password: {
				required: {
					depends: function(element) {
						return ($('input[name="isedit"]').length == 0) ? true : false
					}
				}
			},
			email: {
				required: true,
				email: true,
				remote: {
					url: admin_url + "misc/staff_email_exists",
					type: 'post',
					data: {
						email: function() {
							return $('input[name="email"]').val();
						},
						memberid: function() {
							return $('input[name="memberid"]').val();
						}
					}
				}
			}
		});

		// Permissions change, apply necessary action to disable OWN or VIEW OWN
		$('[data-can-view-own], [data-can-view]').on('change', function() {
			var is_own_attr = $(this).attr('data-can-view-own');
			var view_chk_selector = $(this).parents('tr').find('td input[' + (typeof is_own_attr !== typeof undefined && is_own_attr !== false ? 'data-can-view' : 'data-can-view-own') + ']');

			if (view_chk_selector.data('not-applicable') == true) {
				return;
			}

			view_chk_selector.prop('checked', false);
			view_chk_selector.prop('disabled', $(this).prop('checked') === true);
		});
		

		<?php if(isset($is_add)){ ?>
			$('select[name="role"]').val(<?php echo html_entity_decode($company_role_id); ?>).change();

		<?php } ?>

		$('input[name="is_approval_manager"]').on('change', function() {
			'use strict';

			var is_approval_manager = $(this).prop('checked');
			var plan_id = <?php echo html_entity_decode($plan_id); ?>;

			$.get(admin_url + 'realestate/get_role_by_plan_id/' + plan_id, function (response) {
				if(is_approval_manager){
					if (response.approval_role_id ) {
						$('select[name="role"]').val(response.approval_role_id).change();
					}
				}else{
					if (response.role_id ) {
						$('select[name="role"]').val(response.company_staff_role_id).change();
					}
				}

			}, 'json');
		});

	});
</script>