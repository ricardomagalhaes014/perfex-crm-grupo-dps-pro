<script type="text/javascript">
	var PropertyServerParams = {};
	var all_marker = <?php echo json_encode($map_property_listing); ?>;
	var zoom_scale = 10;
	var markers = [], all_overlays = [];

	(function($) {
		"use strict";

		PropertyServerParams={
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

	$('#pagination-demo').twbsPagination({
		totalPages: <?php echo html_entity_decode($total_page); ?>,
		visiblePages: 7,
		onPageClick: function (event, page) {
			$('input[name="page_number"]').val(page);
			initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);
		}
	});

	})(jQuery);

	function initGridview(url, fnserverparams, selection){
		"use strict";

		fnserverparams =
		fnserverparams == "undefined" || typeof fnserverparams == "undefined"
		? []
		: fnserverparams;

		$('#grid_dt_view').html('<div class="dt-loader"></div>');


		var d = {};
		for (var key in fnserverparams) {
			d[key] = $(fnserverparams[key]).val();
		}

		d.page_number = $('input[name="page_number"]').val();

		$.post(url, d).done(function (response) { 
			response = JSON.parse(response);
			$('#grid_dt_view').html(response.html)

			if(selection == 'itemPerPage'){
				$('#pagination-demo').twbsPagination('destroy');
				$('#pagination-demo').twbsPagination({
					totalPages: response.total_page,
					visiblePages: 7,
					onPageClick: function (event, page) {
						$('input[name="page_number"]').val(page);
						initGridview(site_url + 'realestate/client/client_property_grid_view', PropertyServerParams);
					}
				});

			}
		});
	}

	// Initialize and add the map
	function initMap() {
		'use strict';

		<?php if(!$isMap){ ?>
			return;
		<?php } ?>
		var map;
		var bounds = new google.maps.LatLngBounds();
		var mapOptions = {
			zoom: zoom_scale,
			mapTypeId: 'roadmap',
			styles: map_style,
		};
	// Display a map on the web page
		map = new google.maps.Map(document.getElementById("map_area"), mapOptions);
		map.setTilt(50);
	// Multiple markers location, latitude, and longitude
		markers = all_marker;
		show_map_marker(map, markers); 
	}

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

		$.post(site_url + 'realestate/client/reload_map', data).done(function(response1) {
			var response = JSON.parse(response1);
		/*Multiple markers location, latitude, and longitude*/
			markers = response.map_property_listing;
			var map;
			var mapOptions = {
				zoom: zoom_scale,
				mapTypeId: 'roadmap',
				styles:map_style,
			};
		/*Display a map on the web page*/
			map = new google.maps.Map(document.getElementById("map_area"), mapOptions);
			map.setTilt(50);
			show_map_marker(map, markers);		
		});
	}


	function show_map_marker(map, markers){
		"use strict";

		const iconBase =
		"https://developers.google.com/maps/documentation/javascript/examples/full/images/";
		const iconBase1 = "<?php echo site_url('modules/realestate/assets/images/') ?>";

		const icons = {
			parking: {
				icon: iconBase + "ranger_station.png",
			},
			library: {
				icon: iconBase + "library_maps.png",
			},
			info: {
				icon: iconBase + "info-i_maps.png",
			},
		};

		/*Add multiple markers to map*/
		var i, j, image_html, active;
		var bounds = new google.maps.LatLngBounds();
		/*Place each marker on the map  */
		for( i = 0; i < markers.length; i++ ) {
			var mk = markers[i];
			var position = new google.maps.LatLng(mk.lat, mk.lng);
			bounds.extend(position);

			const marker = new google.maps.Marker({
				position: position,
				map: map,
				title: mk.name + mk.address,
				visible: (typeof mk.visible == 'undefined' ? true : mk.visible)
			});

			var property_price;
			if(mk.transaction_type == 'Sale' || mk.transaction_type == 'sold'){
				property_price = '<br><h5 href="#" class="!tw-text-base"><span class="no-mleft"><?php echo _l('real_for_sale'); ?> '+mk.listing_price+'</span></h5>';
			}else{
				property_price = '<br><h5 href="#" class="!tw-text-base"><span class="no-mleft"><?php echo _l('real_for_rent'); ?> '+mk.listing_price+'</span></h5>';
			}
			
			const infowindow = new google.maps.InfoWindow({
				content: '<div class="room-items no-mbot">'+
				'<div class="room-img set-bg">'+
				'<a href="'+mk.property_url+'" class="">'+
				'<i class="flaticon-heart"></i>'+
				'<img class="room-img set-bg" src="'+mk.image+'" alt="'+mk.primary_image+'">'+
				'</a>'+
				'</div>'+
				'<div class="room-text  no-padding-bottom">'+
				'<div class="room-details no-mbot">'+
				'<div class="room-title no-padding">'+
				'<h5 class="!tw-text-base">'+mk.address+'</h5>'+
				'<a href="#"><span class="no-mleft">'+mk.sqFt_total+' . '+mk.property_style+'</span></a>'+property_price+
				'<a href="#" class="large-width"><span></span></a>'+
				'</div>'+
				'</div>'+
				'</div>'+
				'</div>',
				maxWidth: 500,
			});
			marker.addListener("click", () => {
				infowindow.open(map, marker);
			});
			map.fitBounds(bounds);
		}

		//Show drawing to map
		for(i = 0; i < drawing_filter.length; i++ ) {
			var filter_type = drawing_filter[i].type;
			var option = drawOption;
			if(filter_type == 'rectangle'){
				var data = drawing_filter[i].data;
				var bounds = {
					north: data[1].lat,
					south: data[0].lat,
					east: data[1].lng,
					west: data[0].lng,
				};
				option.bounds = bounds;
				let rectangle = new google.maps.Rectangle(option);
				rectangle.setMap(map);
			}
			if(filter_type == 'polygon'){
				option.path = drawing_filter[i].data;
				let polygon = new google.maps.Polygon(option);                                                                                  
				polygon.setMap(map);
			}
			map.fitBounds(bounds);
		}

		// Set zoom level
		var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
			this.setZoom(zoom_scale);
			google.maps.event.removeListener(boundsListener);
		});
		add_drawing_tool(map);
	}


	var drawing_filter = [];
	function add_drawing_tool(map){
		"use strict";

		const drawingManager = new google.maps.drawing.DrawingManager({
			drawingControl: true,
			drawingControlOptions: {
				position: google.maps.ControlPosition.TOP_CENTER,
				drawingModes: [

					google.maps.drawing.OverlayType.POLYGON
					],
			},

			polygonOptions: drawOption
		});

		drawingManager.setMap(map);

		//Add clear button
		var customControlDiv = document.createElement('div');
		var customControl = new CustomControl(customControlDiv, map);

		customControlDiv.index = 1;
		map.controls[google.maps.ControlPosition.TOP_CENTER].push(customControlDiv);

		google.maps.event.addListener(drawingManager, 'overlaycomplete', function(event) {
			all_overlays.push(event);
			var event_type = event.type;
			if (event_type == 'circle') {
				var radius = event.overlay.getRadius();
			}
			if (event_type == 'polygon') {
				const coords = event.overlay.getPath().getArray().map(coord => {
					return {
						lat: coord.lat(),
						lng: coord.lng()
					}
				});
				drawing_filter.push({
					type: 'polygon',
					data: coords
				});
			}
			if (event_type == 'rectangle') {
				var bounds = event.overlay.getBounds();
				var ne = bounds.getNorthEast();
				var sw = bounds.getSouthWest();
				drawing_filter.push({
					type: 'rectangle',
					data: [
						{lat: sw.lat(), lng: sw.lng()},
						{lat: ne.lat(), lng: ne.lng()}
						]
				});
			}
			changeMapMarker();
		});
	}

	function changeMapMarker(){
		"use strict";

		var map;
		var mapOptions = {
			mapTypeId: 'roadmap',
			styles: map_style,
		};
		var drawing_filter_length = drawing_filter.length;
		var new_marker_list = [];
		let i;
		for(i = 0; i < markers.length; i++ ) {
			var new_marker = markers[i];
			var mk_lat = parseFloat(new_marker.lat);
			var mk_lng = parseFloat(new_marker.lng);
			var visible = false;
			let j = 0;
			while (j < drawing_filter_length) {
				var type = drawing_filter[j].type;
				var data = drawing_filter[j].data;
				if(type == 'rectangle'){	
					if((mk_lat >= parseFloat(data[0].lat) && mk_lat <= parseFloat(data[1].lat)) && (mk_lng >= parseFloat(data[0].lng) && mk_lng <= parseFloat(data[1].lng))){
						visible = true;
						break;
					}
				}
				if(type == 'polygon'){
					let polygon = new google.maps.Polygon({path: data});
					var coordinate = new google.maps.LatLng(mk_lat, mk_lng);
					if(google.maps.geometry.poly.containsLocation(coordinate, polygon)){
						visible = true;
						break;
					} 
				}
				j++;
			}
			new_marker.visible = visible;
			new_marker_list.push(new_marker);
		}

/*Display a map on the web page*/
		map = new google.maps.Map(document.getElementById("map_area"), mapOptions);
		map.setTilt(50);
		show_map_marker(map, new_marker_list);		
	}

	function deleteAllShape() {
		"use strict";

		for (var i=0; i < all_overlays.length; i++)
		{
			all_overlays[i].overlay.setMap(null);
		}
		all_overlays = [];
		drawing_filter = [];
		var map;
		var mapOptions = {
			mapTypeId: 'roadmap',
			styles: map_style
		};
		map = new google.maps.Map(document.getElementById("map_area"), mapOptions);
		map.setTilt(50);
		var new_marker_list = [];
		for(var i = 0; i < markers.length; i++ ) {
			markers[i].visible = true;
			new_marker_list.push(markers[i]);
		}
		markers = new_marker_list;
		show_map_marker(map, markers);		
	}

	function CustomControl(controlDiv, map) {
		"use strict";
		
 	// Set CSS for the control border
		var controlUI = document.createElement('div');
		controlUI.style.backgroundColor = '#2563eb';
		controlUI.style.borderStyle = 'solid';
		controlUI.style.borderWidth = '2px';
		controlUI.style.borderRadius = '3px';
		controlUI.style.borderColor = '#2563EB';
		controlUI.style.color = "rgb(255 255 251)";
		controlUI.style.marginTop = '9px';
		controlUI.style.marginLeft = '0px';
		controlUI.style.cursor = 'pointer';
		controlUI.style.textAlign = 'center';
		controlUI.title = '<?php echo _l('real_clean'); ?>';
		controlDiv.appendChild(controlUI);
    // Set CSS for the control interior
		var controlText = document.createElement('div');
		controlText.style.fontFamily = 'Arial,sans-serif';
		controlText.style.fontSize = '14px';
		controlText.style.paddingLeft = '7px';
		controlText.style.paddingRight = '7px';
		controlText.style.paddingTop = '7px';
		controlText.style.paddingBottom = '7px';
		controlText.innerHTML = '<?php echo _l('real_clean'); ?>';
		controlUI.appendChild(controlText);

    // Setup the click event listeners
		google.maps.event.addDomListener(controlUI, 'click', function () {
			deleteAllShape();
		});
	}

	function filter_form(){
		"use strict";

		$('#filter_modal').modal('show');
	}


</script>