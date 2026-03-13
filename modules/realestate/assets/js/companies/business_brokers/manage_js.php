<script type="text/javascript">
	(function($) {
		"use strict";
		
		var InvoiceServerParams={
			"products_filter": "[name='products_filter[]']",
			"bom_type_filter": "[name='bom_type_filter[]']",
			"routing_filter": "[name='routing_filter[]']",
		};

		var freelance_agent_table = $('.table-freelance_agent_table');
		initDataTable(freelance_agent_table, admin_url+'realestate/business_broker_table',[0],[0], InvoiceServerParams, [0,'desc']);

		$.each(InvoiceServerParams, function(i, obj) {
			$('select' + obj).on('change', function() {  
				freelance_agent_table.DataTable().ajax.reload();
			});
		});

		set_hide_column('table-freelance_agent_table', 'freelance_agent_table_hide_column', true);

	})(jQuery);
</script>