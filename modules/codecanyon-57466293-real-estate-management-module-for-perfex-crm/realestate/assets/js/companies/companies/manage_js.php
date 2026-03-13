<script type="text/javascript">
	(function($) {
		"use strict";
		
		var InvoiceServerParams={
		};

		var company_table = $('.table-company_table');
		initDataTable(company_table, admin_url+'realestate/company_table',[0],[0], InvoiceServerParams, [0,'desc']);

		$.each(InvoiceServerParams, function(i, obj) {
			$('select' + obj).on('change', function() {  
				company_table.DataTable().ajax.reload();
			});
		});

		set_hide_column('table-company_table', 'company_table_hide_column', true);
	})(jQuery);
</script>