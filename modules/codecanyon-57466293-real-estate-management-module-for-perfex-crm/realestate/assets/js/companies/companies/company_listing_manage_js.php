<script>
	var company_id = $('input[name="company_id"]').val();
	var StaffServerParams={};

	(function($) {
		"use strict";
		
		var property_listing_table = $('.table-c_property_listing_table');
		initDataTable(property_listing_table, admin_url+'realestate/company_listing_table/'+company_id + '/'+related_type,[0],[0], StaffServerParams, [0,'desc']);
		set_hide_column('table-c_property_listing_table', 'cc_property_listing_hide_column', true);

	})(jQuery);

</script>