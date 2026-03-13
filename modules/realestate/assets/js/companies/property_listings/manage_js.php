<script type="text/javascript">
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	var PropertyServerParams={
		"search_input": "[name='filter_input']",
		"status": "[name='status']",
		"transaction_type": "[name='filter_transaction_type_value']",
		"listing_type" : "[name='filter_listing_type_value']",
		"property_style" : "[name='filter_property_style_value']",
		"min_price": "[name='min_price']",
		"max_price": "[name='max_price']",
		"min_bed": "[name='min_bed']",
		"max_bed": "[name='max_bed']",
		"filter_bath": "[name='filter_bath']",
		"filter_garage": "[name='filter_garage']",
		"filter_land_min": "[name='filter_land_min']",
		"filter_land_max": "[name='filter_land_max']",
		"min_total_of_floors": "[name='min_total_of_floors']",
		"max_total_of_floors": "[name='max_total_of_floors']",
		"appliances_included": "[name='filter_appliances_included_value']",
		"utilities" : "[name='filter_utilities_value']",
		"sewer" : "[name='filter_sewer_value']",
		"water" : "[name='filter_water_value']",
		"air_conditioning" : "[name='filter_air_conditioning_value']",
		"electrical_service" : "[name='filter_electrical_service_value']",
		"security_features" : "[name='filter_security_features_value']",
		"accessibility_features" : "[name='filter_accessibility_features_value']",

	};
	var property_listing_table = $('.table-property_listing_table');

	$(function(){
		'use strict';

		initDataTable(property_listing_table, page_url + 'property_listing_table',[0],[1], PropertyServerParams, [0,'desc']);
		<?php if(!$isMap){ ?>
			set_hide_column('table-property_listing_table', 'property_hide_column', true);
		<?php } ?>
		
	});

	function reloadMap() {
		"use strict";
		
		<?php if(!$isMap){ ?>
			return;
		<?php } ?>

		$('#search_template_form input[id="search_radius"]').val($('input[id="radius"]').val());

		drawing_filter = [];
		var data={};
		data.formdata = $('#form_filter').serializeArray();
		data.my_favourite_filter = $('#my_favourite_filter').val();
		data.item_id = $('input[name="item_id_filter"]').val();
		data.radius_filter = $('input[id="radius"]').val();
		data.on_sale = $('input[name="on_sale_filter"]').val();
		data.pending_sale = $('input[name="pending_sale_filter"]').val();
		data.rented = $('input[name="rented_filter"]').val();
		data.expired = $('input[name="expired_filter"]').val();
		data.date_on_sale = $('input[name="date_on_sale"]').val();
		data.date_pending_sale = $('input[name="date_pending_sale"]').val();
		data.date_rented = $('input[name="date_rented"]').val();
		data.date_expired = $('input[name="date_expired"]').val();
		data.has_attachment = $('input[name="has_attachment_filter"]').val();
		data.my_listing = $('input[name="my_listing"]').val();

		$.post(page_url + 'reload_map', data).done(function(response1) {
			var response = JSON.parse(response1);
			//Multiple markers location, latitude, and longitude
			markers = response.map_property_listing;
			var map;
			var mapOptions = {
				zoom: zoom_scale,
				mapTypeId: 'roadmap',
				styles:map_style,
			};
			//Display a map on the web page
			map = new google.maps.Map(document.getElementById("map_area"), mapOptions);
			map.setTilt(50);
			show_map_marker(map, markers);		
		});
	}

	function filter_form(){
		"use strict";

		$('#filter_modal').modal('show');
	}
</script>