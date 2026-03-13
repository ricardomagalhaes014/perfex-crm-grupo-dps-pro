<script>
	(function($) {
		"use strict";

		var company_id = $('input[name="company_id"]').val();
		var StaffServerParams={};

		var broker_staff_table = $('.table-broker_staff_table');
		initDataTable(broker_staff_table, admin_url+'realestate/broker_staff_table/'+company_id,[0],[0], StaffServerParams, [0,'desc']);

		var hidden_columns = [0];
		broker_staff_table.DataTable().columns(hidden_columns).visible(false, false);

	})(jQuery);

	function delete_staff_member(id) {
		"use strict";

		$('#delete_staff').modal('show');
		$('#transfer_data_to').find('option').prop('disabled', false);
		$('#transfer_data_to').find('option[value="' + id + '"]').prop('disabled', true);
		$('#delete_staff .delete_id input').val(id);
		$('#transfer_data_to').selectpicker('refresh');
	}

</script>