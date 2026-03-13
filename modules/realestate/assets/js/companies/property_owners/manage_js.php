<script type="text/javascript">
	var page_url = '<?php echo html_entity_decode($site_url); ?>';
	(function($) {
		"use strict";

		var company_id = $('input[name="company_id"]').val() ?? 0;
		var InvoiceServerParams={
		};

		var owner_table = $('.table-owner_table');
		initDataTable(owner_table, page_url+'property_owner_table/'+ company_id,[0],[0], InvoiceServerParams, [0,'desc']);

		$.each(InvoiceServerParams, function(i, obj) {
			$('select' + obj).on('change', function() {  
				owner_table.DataTable().ajax.reload();
			});
		});

		set_hide_column('table-owner_table', 'owner_table_hide_column', true);

	})(jQuery);

</script>