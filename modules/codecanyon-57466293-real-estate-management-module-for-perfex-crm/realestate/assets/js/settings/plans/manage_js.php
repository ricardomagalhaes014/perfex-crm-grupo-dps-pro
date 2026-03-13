<script>
	$(function(){
		'use strict';

		appValidateForm($('#add_edit_plan'),{name:'required',monthly_listing_number:'required', plan_type:'required', role_id:'required', rate:'required'},manage_plan_filters);

		var company_plan_params = {
		};
		
		var company_plan_table = $('table.table-company_plan_table');
		var _table_api = initDataTable(company_plan_table, admin_url+'realestate/plan_table', [0], [0], company_plan_params);

		var hidden_columns = [0];
		$('.table-company_plan_table').DataTable().columns(hidden_columns).visible(false, false);
		
		$('#plan_filter').on('hidden.bs.modal', function(event) {
			'use strict';
			
			$('#plan_filter select').selectpicker('val','');
			$('#plan_filter textarea').val('');
			$('#plan_filter #plan_filter_additional').html('');
			$('.add-title').removeClass('hide');
			$('.edit-title').removeClass('hide');
			$('.allow-edit-title').removeClass('hide');
			$('.allow-add-title').removeClass('hide');
			
		});

		$('input[name="read_only"]').on('change', function() {
			"use strict";

			var val = $('input[name="read_only"]:checked').val();
			if(val == 1){
				$('input[name="monthly_listing_number"]').val(0);
				$('input[name="monthly_listing_number"]').attr('disabled',  true);
			}else{
				$('input[name="monthly_listing_number"]').val(0);
				$('input[name="monthly_listing_number"]').attr('disabled', false);
			}
		});

	});

	function manage_plan_filters(form) {
		'use strict';

		var original_type = $('input[name="original_type"]').val();

		$('input[name="original_type"]').remove();
		var data = $(form).serialize();
		var url = form.action;
		$.post(url, data).done(function(response) {

			response = JSON.parse(response);

			if (response.success) {
				var type = $('select[name="type"]').selectpicker('val');
				$('.table-company_plan_table').DataTable().ajax.reload();

				alert_float('success', response.message);
			}

			$(form).trigger('reinitialize.areYouSure');
			$('#plan_filter').modal('hide');

		});
		return false;
	}

	function new_plan(){
		'use strict';

		$('#plan_filter').modal('show');


		$('#plan_filter input[name="name"]').val('');
		$('#plan_filter input[name="monthly_listing_number"]').val('');
		$('#plan_filter input[name="rate"]').val('');
		$('textarea[name="description"]').val('');
		$('#add_edit_plan select[name="approval_role_id"]').val('').change();

		$('.add-title').removeClass('hide');
		$('.edit-title').addClass('hide');
		$('.allow-add-title').addClass('hide');
		$('.allow-edit-title').addClass('hide');
		$('.company_staff_role_hide').removeClass('hide');

		$('input:radio[name=read_only][value=0]').prop('checked', true);
		$('input:radio[name=read_only][value=1]').prop('checked', false);
		$('input[name="monthly_listing_number"]').attr('disabled', false);

		$('select[name="role_id"]').val('').change().selectpicker('refresh');
		tinyMCE.activeEditor.setContent('');
	}

	function edit_plan(invoker,id){
		'use strict';

		var plan_type = $(invoker).data('plan_type');
		var name = $(invoker).data('name');
		var monthly_listing_number = $(invoker).data('monthly_listing_number');
		var read_only = $(invoker).data('read_only');
		var rate = $(invoker).data('rate');
		var description = $(invoker).data('description');
		var role_id = $(invoker).data('role_id');
		$('.add-title').addClass('hide');
		$('.edit-title').removeClass('hide');
		
		$('#add_edit_plan select[name="role_id"]').val(role_id).change();

		$('input[name="rate"]').val(rate);
		$('textarea[name="description"]').val(description);


		$('#plan_filter_additional').append(hidden_input('id',id));
		$('#plan_filter input[name="name"]').val(name);
		$('#plan_filter input[name="monthly_listing_number"]').val(monthly_listing_number);
		$('#plan_filter select[name="plan_type"]').selectpicker('val',plan_type);
		$('#plan_filter').modal('show');

		if(read_only == 1){
			$('input[name="monthly_listing_number"]').attr('disabled',  true);
			$('input:radio[name=read_only][value=1]').prop('checked', true);
			$('input:radio[name=read_only][value=0]').prop('checked', false);

		}else{
			$('input[name="monthly_listing_number"]').attr('disabled', false);
			$('input:radio[name=read_only][value=0]').prop('checked', true);
			$('input:radio[name=read_only][value=1]').prop('checked', false);;
		}

		// get role value
		$.get(admin_url + 'realestate/get_plan/' +id, function (response) {
			if (response) {
				tinyMCE.activeEditor.setContent(response.long_description);
			}
		}, 'json');

	}

</script>