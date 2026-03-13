<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	$(function(){
		'use strict';
		var ProposalServerParams = {
			"client_filter": "[name='client_filter[]']",
		};

		var tenant_table = $('table.table-tenant_table');
		var _table_api = initDataTable(tenant_table, page_url+'tenant_table', [0], [0], ProposalServerParams,  [0, 'desc']);
		$.each(ProposalServerParams, function(i, obj) {
			$('select' + obj).on('change', function() {  
				tenant_table.DataTable().ajax.reload();
			});
		});
	});
	var hidden_columns = [7,8];


	function property_request_status_mark_as(status, property_request_id, type) {
		"use strict"; 
		
		var url = page_url+'property_request_status_mark_as/' + status + '/' + property_request_id + '/' + type;
		var taskModalVisible = $('#task-modal').is(':visible');
		url += '?single_task=' + taskModalVisible;
		$("body").append('<div class="dt-loader"></div>');

		requestGetJSON(url).done(function (response) {
			$("body").find('.dt-loader').remove();
			if (response.success === true || response.success == 'true') {

				var av_tasks_tables = ['.table-tenant_table'];
				$.each(av_tasks_tables, function (i, selector) {
					if ($.fn.DataTable.isDataTable(selector)) {
						$(selector).DataTable().ajax.reload(null, false);
					}
				});
				alert_float('success', response.message);
			}
		});
	}

</script>