<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	var salesChart;
	var groupsChart;
	var paymentMethodsChart;
	var customersTable;
	var report_from = $('input[name="report-from"]');
	var report_to = $('input[name="report-to"]');

	var report_customers = $('#customers-report');

	var report_rent_paid = $('#rent-paid-report-data');
	var report_vacant_rental_property = $('#vacant-rental-property-report-data');
	var report_unit_property_rental = $('#unit-property-rental-report-data');
	var report_delinquent_tenants = $('#delinquent-tenants-report-data');
	var report_leases_ending = $('#leases-ending-repor-data');
	var report_vacant_sale_property = $('#vacant-sale-property-report-data');
	var report_unit_property_sold = $('#unit-property-sold-report-data');


	var date_range = $('#date-range');
	var report_from_choose = $('#report-time');
	var fnServerParams = {
		"report_months": '[name="months-report"]',
		"report_from": '[name="report-from"]',
		"report_to": '[name="report-to"]',
		"report_currency": '[name="currency"]',
		"invoice_status": '[name="invoice_status"]',
		"estimate_status": '[name="estimate_status"]',
		"sale_agent_invoices": '[name="sale_agent_invoices"]',
		"sale_agent_items": '[name="sale_agent_items"]',
		"sale_agent_estimates": '[name="sale_agent_estimates"]',
		"proposals_sale_agents": '[name="proposals_sale_agents"]',
		"proposal_status": '[name="proposal_status"]',
		"credit_note_status": '[name="credit_note_status"]',
	}

	$(function() {
		'use strict';

		$('select[name="currency"],select[name="invoice_status"],select[name="estimate_status"],select[name="sale_agent_invoices"],select[name="sale_agent_items"],select[name="sale_agent_estimates"],select[name="payments_years"],select[name="proposals_sale_agents"],select[name="proposal_status"],select[name="credit_note_status"]').on('change', function() {
			gen_reports();
		});

		report_from.on('change', function() {
			'use strict';

			var val = $(this).val();
			var report_to_val = report_to.val();
			if (val != '') {
				report_to.attr('disabled', false);
				if (report_to_val != '') {
					gen_reports();
				}
			} else {
				report_to.attr('disabled', true);
			}
		});

		report_to.on('change', function() {
			'use strict';

			var val = $(this).val();
			if (val != '') {
				gen_reports();
			}
		});

		$('select[name="months-report"]').on('change', function() {
			'use strict';

			var val = $(this).val();
			report_to.attr('disabled', true);
			report_to.val('');
			report_from.val('');
			if (val == 'custom') {
				date_range.addClass('fadeIn').removeClass('hide');
				return;
			} else {
				if (!date_range.hasClass('hide')) {
					date_range.removeClass('fadeIn').addClass('hide');
				}
			}
			gen_reports();
		});

	});

	function init_report(e, type) {
		'use strict';

		var report_wrapper = $('#report');

		if (report_wrapper.hasClass('hide')) {
			report_wrapper.removeClass('hide');
		}

		$('.reports').find('a').attr('data-active', false)
		$(e).attr('data-active', true)

		$('head title').html($(e).text());
		$('.customers-group-gen').addClass('hide');

		report_rent_paid.addClass('hide');
		report_vacant_rental_property.addClass('hide');
		report_unit_property_rental.addClass('hide');
		report_delinquent_tenants.addClass('hide');
		report_leases_ending.addClass('hide');
		report_vacant_sale_property.addClass('hide');
		report_unit_property_sold.addClass('hide');


		$('#income-years').addClass('hide');
		$('.chart-income').addClass('hide');
		$('.chart-payment-modes').addClass('hide');

		report_from_choose.addClass('hide');

		$('select[name="months-report"]').selectpicker('val', 'this_month');
   // Clear custom date picker
		report_to.val('');
		report_from.val('');
		$('#currency').removeClass('hide');

		if (type != 'total-income' && type != 'payment-modes') {
			report_from_choose.removeClass('hide');
		}

		if (type == 'rent-paid-report-data') {
			report_rent_paid.removeClass('hide');
		} else if (type == 'vacant-rental-property-report-data') {
			report_vacant_rental_property.removeClass('hide');
		} else if (type == 'unit-property-rental-report-data') {
			report_unit_property_rental.removeClass('hide');
		} else if (type == 'delinquent-tenants-report-data') {
			report_delinquent_tenants.removeClass('hide');
		} else if (type == 'leases-ending-repor-data') {
			report_leases_ending.removeClass('hide');
			report_from_choose.addClass('hide');

		} else if (type == 'vacant-sale-property-report-data') {
			report_vacant_sale_property.removeClass('hide');
		} else if (type == 'unit-property-sold-report-data') {
			report_unit_property_sold.removeClass('hide');
		}
		gen_reports();
	}

   // Main generate report function
	function gen_reports() {
		'use strict';
		
		if(!report_rent_paid.hasClass('hide')) {
			rent_paid_report();
		}else if(!report_vacant_rental_property.hasClass('hide')) {
			report_vacant_rental_property_report();
		}else if(!report_unit_property_rental.hasClass('hide')) {
			report_unit_property_rental_report();
		}else if(!report_delinquent_tenants.hasClass('hide')) {
			report_delinquent_tenants_report();
		}else if(!report_leases_ending.hasClass('hide')) {
			report_leases_ending_report();
		}else if(!report_vacant_sale_property.hasClass('hide')) {
			report_vacant_sale_property_report();
		}else if(!report_unit_property_sold.hasClass('hide')) {
			report_unit_property_sold_report();
		}
	}

	function rent_paid_report(){
		"use strict";

		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		$.post(page_url + 'rent_paid_report', data).done(function(response) {
			response = JSON.parse(response);
			$('#rent-paid-report-data').html('');
			$('#rent-paid-report-data').append(response.value);

		}).fail(function(data) {

		});
	}

	function report_vacant_rental_property_report(){
		"use strict";

		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		$.post(page_url + 'report_vacant_rental_property_report', data).done(function(response) {
			response = JSON.parse(response);
			$('#vacant-rental-property-report-data').html('');
			$('#vacant-rental-property-report-data').append(response.value);

		}).fail(function(data) {

		});
	}

	function report_unit_property_rental_report(){
		"use strict";

		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		$.post(page_url + 'report_unit_property_rental_report', data).done(function(response) {
			response = JSON.parse(response);
			$('#unit-property-rental-report-data').html('');
			$('#unit-property-rental-report-data').append(response.value);

		}).fail(function(data) {

		});
	}
	function report_delinquent_tenants_report(){
		"use strict";

		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		$.post(page_url + 'report_delinquent_tenants_report', data).done(function(response) {
			response = JSON.parse(response);
			$('#delinquent-tenants-report-data').html('');
			$('#delinquent-tenants-report-data').append(response.value);

		}).fail(function(data) {

		});
	}
	function report_leases_ending_report(){
		"use strict";

		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		$.post(page_url + 'report_leases_ending_report', data).done(function(response) {
			response = JSON.parse(response);
			$('#leases-ending-repor-data').html('');
			$('#leases-ending-repor-data').append(response.value);

		}).fail(function(data) {

		});
	}
	function report_vacant_sale_property_report(){
		"use strict";

		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		$.post(page_url + 'report_vacant_sale_property_report', data).done(function(response) {
			response = JSON.parse(response);
			$('#vacant-sale-property-report-data').html('');
			$('#vacant-sale-property-report-data').append(response.value);

		}).fail(function(data) {

		});
	}
	function report_unit_property_sold_report(){
		"use strict";

		var data = {};
		data.months_report = $('select[name="months-report"]').val();
		data.report_from = report_from.val();
		data.report_to = report_to.val();

		$.post(page_url + 'report_unit_property_sold_report', data).done(function(response) {
			response = JSON.parse(response);
			$('#unit-property-sold-report-data').html('');
			$('#unit-property-sold-report-data').append(response.value);

		}).fail(function(data) {

		});
	}
	
</script>
