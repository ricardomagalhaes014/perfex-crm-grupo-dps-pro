<script>
	$(function(){
		'use strict';

		var address_history_table = $('table.table-address_history_table');
		var income_source_table = $('table.table-income_source_table');
		var person_table = $('table.table-person_table');
		var _table_api = initDataTable(address_history_table, site_url+'realestate/client/address_history_table', [0], [0], {});
		initDataTable(income_source_table, site_url+'realestate/client/income_source_table', [0], [0], {});
		initDataTable(person_table, site_url+'realestate/client/person_table', [0], [0], {});

		var hidden_columns = [0];
		$('.table-address_history_table').DataTable().columns(hidden_columns).visible(false, false);
		$('.table-income_source_table').DataTable().columns(hidden_columns).visible(false, false);
		$('.table-person_table').DataTable().columns(hidden_columns).visible(false, false);


		appValidateForm($('#add_edit_address_history'),{address:'required',move_out:'required'},manage_address_history);
		appValidateForm($('#add_edit_income_source'),{income_type:'required',income_frequency:'required',amount:'required'},manage_income_source);
		appValidateForm($('#add_edit_person'),{occupants_name:'required'},manage_person);

		$('#address_history_modal').on('hidden.bs.modal', function(event) {
			$('#address_history_modal select').selectpicker('val','');
			$('#address_history_modal #address_history_additional').html('');
			$('.add-title').removeClass('hide');
			$('.edit-title').removeClass('hide');
			$('.allow-edit-title').removeClass('hide');
			$('.allow-add-title').removeClass('hide');
		});
		$('#income_source_modal').on('hidden.bs.modal', function(event) {
			$('#income_source_modal select').selectpicker('val','');
			$('#income_source_modal #income_source_additional').html('');
			$('.add-title').removeClass('hide');
			$('.edit-title').removeClass('hide');
			$('.allow-edit-title').removeClass('hide');
			$('.allow-add-title').removeClass('hide');
		});
		
	});

	function manage_address_history(form) {
		'use strict';

		var data = $(form).serialize();
		var url = form.action;
		$.post(site_url + 'realestate/client/address_history', data).done(function(response) {
			response = JSON.parse(response);

			if (response.success) {
				$('.table-address_history_table').DataTable().ajax.reload();
				alert_float('success', response.message);
			}

			$('#address_history_modal').modal('hide');

		});
		return false;
	}

	function new_address_history(){
		'use strict';

		$('#address_history_modal').modal('show');
		$('.edit-title').addClass('hide');

		$('#add_edit_address_history input[name="address"]').val('');
		$('#add_edit_address_history input[name="move_in"]').val('');
		$('#add_edit_address_history input[name="move_out"]').val('');

		$('.edit-title').addClass('hide');
		$('.add-title').removeClass('hide');

	}

	function edit_address_history(invoker,id){
		'use strict';

		var address = $(invoker).data('address');
		var move_in = $(invoker).data('move_in');
		var move_out = $(invoker).data('move_out');

		$('#add_edit_address_history input[name="address"]').val(address);
		$('#add_edit_address_history input[name="move_in"]').val(move_in);
		$('#add_edit_address_history input[name="move_out"]').val(move_out);

		$('#address_history_additional').append(hidden_input('id',id));
		$('#address_history_modal').modal('show');
		$('.add-title').addClass('hide');
		$('.edit-title').removeClass('hide');
	}

	function delete_address_history(id){
		'use strict';

		$.post(site_url + 'realestate/client/delete_address_history/' + id).done(function(response) {
			response = JSON.parse(response);

			if (response.success) {
				$('.table-address_history_table').DataTable().ajax.reload();
				alert_float('success', response.message);
			}

			$('#address_history_modal').modal('hide');
		});
	}

	function manage_income_source(form) {
		'use strict';

		var data = $(form).serialize();
		var url = form.action;
		$.post(site_url + 'realestate/client/income_source', data).done(function(response) {

			response = JSON.parse(response);
			if (response.success) {
				$('.table-income_source_table').DataTable().ajax.reload();

				alert_float('success', response.message);
			}

			$('#income_source_modal').modal('hide');
		});
		return false;
	}

	function new_income_source(){
		'use strict';

		$('#income_source_modal').modal('show');
		$('.edit-title').addClass('hide');

		$('#add_edit_income_source select[name="income_type"]').val('').change();
		$('#add_edit_income_source select[name="income_frequency"]').val('').change();
		$('#add_edit_income_source input[name="amount"]').val('');

		$('.edit-title').addClass('hide');
		$('.add-title').removeClass('hide');
	}

	function edit_income_source(invoker,id){
		'use strict';

		var income_type = $(invoker).data('income_type');
		var income_frequency = $(invoker).data('income_frequency');
		var amount = $(invoker).data('amount');

		$('#add_edit_income_source select[name="income_type"]').val(income_type).change();
		$('#add_edit_income_source select[name="income_frequency"]').val(income_frequency).change();
		$('#add_edit_income_source input[name="amount"]').val(amount);

		$('#income_source_additional').append(hidden_input('id',id));
		$('#income_source_modal').modal('show');
		$('.add-title').addClass('hide');
		$('.edit-title').removeClass('hide');
	}

	function delete_income_source(id){
		'use strict';

		$.post(site_url + 'realestate/client/delete_income_source/' + id).done(function(response) {
			response = JSON.parse(response);

			if (response.success) {
				$('.table-income_source_table').DataTable().ajax.reload();
				alert_float('success', response.message);
			}
		});
	}


	function manage_person(form) {
		'use strict';

		var data = $(form).serialize();
		var url = form.action;
		$.post(site_url + 'realestate/client/person', data).done(function(response) {

			response = JSON.parse(response);

			if (response.success) {
				var type = $('select[name="type"]').selectpicker('val');
				$('.table-person_table').DataTable().ajax.reload();

				alert_float('success', response.message);
			}
			$('#person_modal').modal('hide');

		});
		return false;
	}

	function new_person(){
		'use strict';

		$('#person_modal').modal('show');
		$('.edit-title').addClass('hide');

		$('#add_edit_person input[name="occupants_name"]').val('');
		$('#add_edit_person input[name="occupants_age"]').val('');
	
		$('.edit-title').addClass('hide');
		$('.add-title').removeClass('hide');
	}

	function edit_person(invoker,id){
		'use strict';

		var occupants_name = $(invoker).data('occupants_name');
		var occupants_age = $(invoker).data('occupants_age');


		$('#add_edit_person input[name="occupants_name"]').val(occupants_name);
		$('#add_edit_person input[name="occupants_age"]').val(occupants_age);

		$('#person_additional').append(hidden_input('id',id));
		$('#person_modal').modal('show');
		$('.add-title').addClass('hide');
		$('.edit-title').removeClass('hide');
	}

	function delete_person(id){
		'use strict';

		$.post(site_url + 'realestate/client/delete_person/' + id).done(function(response) {
			response = JSON.parse(response);

			if (response.success) {
				$('.table-person_table').DataTable().ajax.reload();
				alert_float('success', response.message);
			}
		});
	}

	function preview_file(invoker){
		'use strict';

		var id = $(invoker).attr('id');
		var rel_id = $(invoker).attr('rel_id');
		view_file(id, rel_id);
	}

	function view_file(id, rel_id) {   
		'use strict';

		$('#rental_file_data').empty();
		$("#rental_file_data").load(site_url + 'realestate/client/preview_file/' + id + '/' + rel_id, function(response, status, xhr) {
			if (status == "error") {
				alert_float('danger', xhr.statusText);
			}
		});
	}

	function delete_renter_attachment_pdf_file(wrapper, id, folder_name) {
		'use strict';

		if (confirm_delete()) {
			$.get(site_url + 'realestate/client/delete_realestate_attachment/' + id + '/' + folder_name, function (response) {
				if (response.success == true) {
					$(wrapper).parents('.pdf_attachment').remove();

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