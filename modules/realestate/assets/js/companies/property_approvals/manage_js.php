<script type="text/javascript">
	var PropertyServerParams={
	};
	var pending_property_table = $('.table-pending_property_table');

	$(function(){
		'use strict';
		initDataTable(pending_property_table, admin_url + 'realestate/pendding_property_table',[0],[1], PropertyServerParams, [0,'desc']);
		set_hide_column('table-pending_property_table', 'property_hide_column', true);

		// On mass_select all select all the availble rows in the tables.
		$('#mass_select_all').on('click', function(){
			$('.checkbox_item').prop('checked', $(this).is(':checked'));			
		});

	});

	function change_status_in_bulk(){
		"use strict";
		
		var ids = [];
		var data = {};

		var rows = $('#table-pending_property_table').find('tbody tr');
		$.each(rows, function() {
			var checkbox = $($(this).find('td').eq(0)).find('input');
			if (checkbox.prop('checked') === true) {
				ids.push(checkbox.val());
			}
		});
		if(ids.length > 0){
			data.ids = ids;
			$(event).addClass('disabled');
			setTimeout(function() {
				$.post(admin_url + 'realestate/change_status_in_bulk', data).done(function(response) {
					response = JSON.parse(response);
					if(response.success == true){
						alert_float('success', "<?php echo _l("real_change_status_in_bulk_success") ?>");
					}else{
						alert_float('success', "<?php echo _l("real_change_status_in_bulk_false") ?>");

					}
					pending_property_table.DataTable().ajax.reload();
				}).fail(function(data) {

				});

				$(event).removeClass('disabled');
			}, 200);
		}else{
			alert_float('warning', "<?php echo _l("real_Please_selected_the_listings_you_want_to_approve") ?>");
		}
	}
</script>