<script>
	$(function() {
		'use strict';

		var table_invoices = $("table.table-invoices");
		var Sales_table_ServerParams = {};
		var Sales_table_Filter = $("._hidden_inputs._filters input");

		$.each(Sales_table_Filter, function () {
			Sales_table_ServerParams[$(this).attr("name")] =
			'[name="' + $(this).attr("name") + '"]';
		});

		initDataTable('.table-invoices',site_url + "realestate/broker/invoice_table" +
			($("body").hasClass("recurring") ? "?recurring=1" : ""),
			"undefined",
			"undefined",
			Sales_table_ServerParams,
			!$("body").hasClass("recurring")
			? [
				[3, "desc"],
				[0, "desc"],
				]
			: [table_invoices.find("th.next-recurring-date").index(), "asc"]
			);

		init_invoice();
	});

	function init_invoice(id) {
		'use strict';

		load_small_table_item(
			id,
			"#invoice",
			"invoiceid",
			"get_invoice_data_ajax",
			".table-invoices"
			);
	}
</script>