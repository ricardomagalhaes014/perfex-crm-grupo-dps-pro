<script type="text/javascript">
	$(function() {
		"use strict";
		
		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');

		appValidateForm($('.staff-form'), {
			firstname: 'required',
			lastname: 'required',
			username: 'required',
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
					url: admin_url + "realestate/staff_email_exists",
					type: 'post',
					data: {
						email: function() {
							return $('input[name="email"]').val();
						},
						memberid: function() {
							return $('input[name="memberid"]').val();
						},
						related_type: function() {
							return 'business_broker';
						}
					}
				}
			}
		});

	});
</script>