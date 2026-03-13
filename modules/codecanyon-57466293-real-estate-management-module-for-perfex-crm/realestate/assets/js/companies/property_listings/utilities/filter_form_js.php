<script type="text/javascript">
	var property_listing_table = $('.table-property_listing_table');
	
	$(function(){
		'use strict';

		$("body").on('click', '.checkbox input[name="appliances_included"]', function() {
			filter_by_checkbox('appliances_included');
		});
		$("body").on('click', '.checkbox input[name="listing_type"]', function() {
			filter_by_checkbox('listing_type');
		});
		$("body").on('click', '.checkbox input[name="property_style"]', function() {
			filter_by_checkbox('property_style');
		});
		$("body").on('click', '.checkbox input[name="utilities"]', function() {
			filter_by_checkbox('utilities');
		});
		$("body").on('click', '.checkbox input[name="sewer"]', function() {
			filter_by_checkbox('sewer');
		});
		$("body").on('click', '.checkbox input[name="water"]', function() {
			filter_by_checkbox('water');
		});
		$("body").on('click', '.checkbox input[name="air_conditioning"]', function() {
			filter_by_checkbox('air_conditioning');
		});
		$("body").on('click', '.checkbox input[name="electrical_service"]', function() {
			filter_by_checkbox('electrical_service');
		});
		$("body").on('click', '.checkbox input[name="security_features"]', function() {
			filter_by_checkbox('security_features');
		});
		$("body").on('click', '.checkbox input[name="accessibility_features"]', function() {
			filter_by_checkbox('accessibility_features');
		});

		$("body").on('change', '#property_search input[name="search_input"]', function() {
			$('#filter_modal input[name="filter_input"]').val($(this).val());
			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);
		});

		$("body").on('change', '#filter_modal input[name="filter_input"]', function() {
			$('#property_search input[name="search_input"]').val($(this).val());
		});
		
		$("body").on('change', '#property_search select[name="transaction_type_search"]', function() {
			'use strict';

			$('#filter_modal input[id="transaction_type_1"]').prop('checked', false);
			$('#filter_modal input[id="transaction_type_2"]').prop('checked', false);
			$('#filter_modal input[id="transaction_type_3"]').prop('checked', false);
			$('#filter_modal input[id="transaction_type_4"]').prop('checked', false);
			var filter_value = [];

			var rows = $(this).val();
			if(rows.length > 0){
				$.each(rows, function(value) {
					if(rows[value] == 'Sale'){
						$('#filter_modal input[id="transaction_type_1"]').prop('checked', true);
						filter_value.push('Sale');

					}
					if(rows[value] == 'Rent'){
						$('#filter_modal input[id="transaction_type_2"]').prop('checked', true);
						filter_value.push('Rent');
					}
					if(rows[value] == 'sold'){
						$('#filter_modal input[id="transaction_type_3"]').prop('checked', true);
						filter_value.push('sold');
					}
					if(rows[value] == 'rented'){
						$('#filter_modal input[id="transaction_type_4"]').prop('checked', true);
						filter_value.push('rented');
					}
				});
			}else{
				$('#filter_modal input[id="transaction_type_1"]').prop('checked', true);
				$('#filter_modal input[id="transaction_type_2"]').prop('checked', true);
				$('#filter_modal input[id="transaction_type_3"]').prop('checked', true);
				$('#filter_modal input[id="transaction_type_4"]').prop('checked', true);
				filter_value = ['Sale', 'Rent', 'sold', 'rented'];

			}
			$('#filter_modal input[name="filter_transaction_type_value"]').val(filter_value);
			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);

			
		});

		$("body").on('change', '#filter_modal input[name="transaction_type"]', function() {
			'use strict';

			var rows = $('#filter_modal input[name="transaction_type"]');
			var filter_value = [];

			$.each(rows, function() {
				var checkbox = $(this).eq(0);
				if (checkbox.prop('checked') === true) {
					filter_value.push(checkbox.val());
				}
			});
			if(filter_value.length == 0){
				filter_value = ['Sale', 'Rent', 'sold', 'rented'];
			}

			$('#property_search select[name="transaction_type_search"]').val(filter_value).selectpicker("refresh");
			$('#filter_modal input[name="filter_transaction_type_value"]').val(filter_value);
		});


		$("body").on('change', '#property_search input[name="min_price_search"]', function() {
			$('#filter_modal input[name="min_price"]').val($(this).val());
			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);

		});

		$("body").on('change', '#filter_modal input[name="min_price"]', function() {
			$('#property_search input[name="min_price_search"]').val($(this).val());

		});

		$("body").on('change', '#property_search input[name="max_price_search"]', function() {
			$('#filter_modal input[name="max_price"]').val($(this).val());
			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);


		});

		$("body").on('change', '#filter_modal input[name="max_price"]', function() {
			$('#property_search input[name="max_price_search"]').val($(this).val());

		});

		$("body").on('change', '#property_search select[name="beds_search"]', function() {
			$('#filter_modal select[name="min_bed"]').val($(this).val()).selectpicker("refresh");
			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);


		});

		$("body").on('change', '#filter_modal select[name="min_bed"]', function() {
			$('#property_search select[name="beds_search"]').val($(this).val()).selectpicker("refresh");
		});

		$("body").on('change', '#property_search select[name="baths_search"]', function() {
			$('#filter_modal select[name="filter_bath"]').val($(this).val()).selectpicker("refresh");
			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);


		});

		$("body").on('change', '#filter_modal select[name="filter_bath"]', function() {
			$('#property_search select[name="baths_search"]').val($(this).val()).selectpicker("refresh");
		});

		$("body").on('click', '.clear_all_filter', function() {
			$('#filter_modal').find('input:text, input:hidden, input:password, input:file, select, textarea').val('');
			$('#filter_modal').find('select').selectpicker("refresh");
			$('#filter_modal').find('input:radio, input:checkbox').removeAttr('checked').removeAttr('selected');
			$('#filter_modal').find('input[name="min_price"], input[name="max_price"], input[name="min_total_of_floors"], input[name="min_total_of_floors"] ').val('');

			$('#property_search').find('input:text, input:hidden, input:password, input:file, select, textarea').val('');
			$('#property_search').find('select').selectpicker("refresh");
			$('#property_search').find('input[name="min_price_search"], input[name="max_price_search"]').val('');

			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);

		});

		$("body").on('change', '#property_search select[name="status"]', function() {
			$('#filter_modal select[name="status"]').val($(this).val()).selectpicker("refresh");
			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);


		});

		$("body").on('change', '#filter_modal select[name="status"]', function() {
			$('#property_search select[name="status"]').val($(this).val()).selectpicker("refresh");
		});

		$("body").on('click', '.property_filter', function() {

			property_listing_table.DataTable().ajax.reload();
			reloadMap();
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);

			$('#filter_modal').modal('hide');
		});

	});


function filter_by_checkbox(checkbox_name) {
	"use strict";

	var filter_value = [];
	var rows = $('input[name="'+checkbox_name+'"]');
	$.each(rows, function() {
		var checkbox = $(this).eq(0);
		if (checkbox.prop('checked') === true) {
			filter_value.push(checkbox.val());
		}
	});

	$('input[name="filter_'+checkbox_name+'_value"]').val(filter_value);
}

function changeReadMore(_this) {
	"use strict";

	var hide = $('#'+_this).hasClass('hide');
	var title = $('#'+_this+'_button').data('title');
	if(hide){
		$('#'+_this).removeClass('hide');
		$('#'+_this+'_button').html('<?php echo _l('real_show_fewer'); ?>'+' '+title);
	}else{
		$('#'+_this).addClass('hide');
		$('#'+_this+'_button').html('<?php echo _l('real_show_more'); ?>'+' '+title);
	}
}
</script>