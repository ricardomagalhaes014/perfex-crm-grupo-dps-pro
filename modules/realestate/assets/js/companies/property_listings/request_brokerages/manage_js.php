<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	$(function(){
		'use strict';

		appValidateForm($('#add_edit_request_broker'),{commission:'required'},manage_request_broker);
		var request_broker_params = {
		};
		
		var request_broker_table = $('table.table-request_broker_table');
		var _table_api = initDataTable(request_broker_table, page_url + 'request_broker_table/'+<?php echo html_entity_decode($property_listing->id); ?>, [0], [0], request_broker_params);
		var hidden_columns = [0,1,5,7];
		$('.table-request_broker_table').DataTable().columns(hidden_columns).visible(false, false);
		
		$('#request_broker_modal').on('hidden.bs.modal', function(event) {
			$('#request_broker_modal select').selectpicker('val','');
			$('#request_broker_modal #request_broker_additional').html('');
			$('.add-title').removeClass('hide');
			$('.edit-title').removeClass('hide');
			$('.allow-edit-title').removeClass('hide');
			$('.allow-add-title').removeClass('hide');
		});
	});

	function manage_request_broker(form) {
		'use strict';

		var data = $(form).serialize();
		var url = form.action;
		$.post(page_url + 'request_broker', data).done(function(response) {

			response = JSON.parse(response);

			if (response.success) {
				var type = $('select[name="type"]').selectpicker('val');
				$('.table-request_broker_table').DataTable().ajax.reload();

				alert_float('success', response.message);
			}

			$(form).trigger('reinitialize.areYouSure');
			$('#request_broker_modal').modal('hide');

		});
		return false;
	}

	function new_request_broker(){
		'use strict';

		$('#request_broker_modal').modal('show');
		$('.edit-title').addClass('hide');

		$('#request_broker_modal input[name="commission"]').val(<?php echo html_entity_decode($property_listing->commission) ?>);
		$('#add_edit_request_broker select[name="broker_id"]').val('').change();
		$('#add_edit_request_broker select[name=""]').val('company_id').change();

		$('.edit-title').addClass('hide');
		$('.allow-add-title').addClass('hide');
		$('.allow-edit-title').addClass('hide');
		$('.agent_select').removeClass('hide');
		$('.broker_select').removeClass('hide');
	}

	function edit_request_broker(invoker,id){
		'use strict';

		var commission = $(invoker).data('commission');
		var broker_id = $(invoker).data('broker_id');
		var company_id = $(invoker).data('company_id');

		$('input[name="commission"]').val(commission);
		$('#add_edit_request_broker select[name="broker_id"]').val(broker_id).change();
		$('#add_edit_request_broker select[name="company_id"]').val(company_id).change();

		$('#request_broker_additional').append(hidden_input('id',id));
		$('#request_broker_modal').modal('show');
		$('.add-title').addClass('hide');

		if(broker_id == 0){
			$('.broker_select').addClass('hide');
			$('.agent_select').removeClass('hide');
		}
		if(company_id == 0){
			$('.agent_select').addClass('hide');
			$('.broker_select').removeClass('hide');
		}
	}

</script>