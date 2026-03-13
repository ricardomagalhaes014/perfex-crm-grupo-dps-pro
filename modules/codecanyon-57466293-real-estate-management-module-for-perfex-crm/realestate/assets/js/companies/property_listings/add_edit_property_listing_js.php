<script type="text/javascript">
	var page_url = '<?php echo html_entity_decode($site_url); ?>';
	var expenseDropzone, expenseDropzone1,expenseDropzone2,expenseDropzone3, x_point, y_point, zoom_scale = 14,lastAddedItemKey  ;
			// Initialize and add the map
	const drawing_search_history = <?php echo json_encode($drawing_search_history); ?>;

	$(function(){
		'use strict';

		appValidateForm($("body").find('#add_update_property_listing'), {
			rate: 'required',
			listing_type: 'required',
			listing_service_type: 'required',
			city: 'required',
			country: 'required',
			'new_construction': 'required',
			'property_style': 'required',
			'ownership': 'required',
			'status': 'required',
			'latitude': 'required',
			'longitude': 'required',
			'transaction_type': 'required',

		},productSubmitHandler);

		if($('#dropzoneDragArea').length > 0){
			expenseDropzone = new Dropzone("#add_update_property_listing", appCreateDropzoneOptions({
				autoProcessQueue: false,
				clickable: '#dropzoneDragArea',
				previewsContainer: '.dropzone-previews',
				addRemoveLinks: true,
				maxFiles: 20,

				success:function(file,response){
					response = JSON.parse(response);
					if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
						window.location.assign(response.url);
					}else{
						expenseDropzone.processQueue();
					}
				},
			}));
		}

		if($('#dropzoneDragArea1').length > 0){
			expenseDropzone1 = new Dropzone("#add_update_property_listing1", appCreateDropzoneOptions({
				autoProcessQueue: false,
				clickable: '#dropzoneDragArea1',
				previewsContainer: '.dropzone-previews1',
				addRemoveLinks: true,
				maxFiles: 5,

				success:function(file,response){
					response = JSON.parse(response);
					if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
						window.location.assign(response.url);
					}else{
						expenseDropzone1.processQueue();

					}
				},
			}));
		}

		if($('#dropzoneDragArea2').length > 0){
			expenseDropzone2 = new Dropzone("#add_update_property_listing2", appCreateDropzoneOptions({
				autoProcessQueue: false,
				clickable: '#dropzoneDragArea2',
				previewsContainer: '.dropzone-previews2',
				addRemoveLinks: true,
				maxFiles: 1,

				success:function(file,response){
					response = JSON.parse(response);
					if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
						window.location.assign(response.url);
					}else{
						autoProcessQueue.processQueue();
					}
				},
			}));
		}

		if($('#dropzoneDragArea3').length > 0){
			expenseDropzone3 = new Dropzone("#add_update_property_listing3", appCreateDropzoneOptions({
				autoProcessQueue: false,
				clickable: '#dropzoneDragArea3',
				previewsContainer: '.dropzone-previews3',
				addRemoveLinks: true,
				maxFiles: 5,
				acceptedFiles: '.mp4,.m4v,.webm,.flv',

				success:function(file,response){
					response = JSON.parse(response);
					if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
						window.location.assign(response.url);
					}else{
						expenseDropzone3.processQueue();
					}
				},
			}));
		}	

		$('#select_on_map .accept_handle').on('click', function(){

			$('#latitude').val(x_point);
			$('#longitude').val(y_point);
			$('#select_on_map').fadeOut(1000);
		});

		$('.close_handle').on('click', function(){
			
			$('#select_on_map').fadeOut(1000);
		});

		<?php if(!isset($property_listing)){ ?>
			get_my_country();
		<?php } ?>

		$('select[name="property_owner_id"]').on('change', function(){
			'use strict';
			var owner_id = $('select[name="property_owner_id"]').val();
			console.log('owner_id', owner_id);
			$.get(page_url + 'get_property_owner_information/' +owner_id, function (response) {
				if (response.owner_name) {
					$('#add_update_property_listing input[name="owner_name"]').val(response.owner_name);
					$('#add_update_property_listing input[name="owner_phone"]').val(response.owner_phone);
					$('#add_update_property_listing input[name="owner_email"]').val(response.owner_email);
				}
			}, 'json');
		});

	}); 

	
	Dropzone.options.expenseForm = false;

	function initMap() {
		'use strict';

		if($('#map_area').length > 0){
			var latitude = $('#select_on_map input[name="lat"]').val();
			var longitude = $('#select_on_map input[name="lng"]').val();
			if(latitude != '' && longitude != ''){
				x_point = parseFloat(latitude);
				y_point = parseFloat(longitude);
				zoom_scale = 14;
			}
			if (isNaN(x_point) || isNaN(y_point)) {
				return;
			}

			const uluru = { lat: parseFloat(x_point), lng: parseFloat(y_point) };
  			// The map, centered at Uluru
			const map = new google.maps.Map(document.getElementById("map_area"), {
				zoom: parseFloat(zoom_scale),
				center: uluru,
				styles: map_style,
			});
  			// The marker, positioned at Uluru
			var marker = new google.maps.Marker({
				position: uluru,
				map: map
			});  
			google.maps.event.addListener(map, 'zoom_changed', function(e) {
				zoom_scale = map.getZoom();
			});

			// Add change marker event
			google.maps.event.addListener(map, 'click', function(e) {
				var	latLng = e.latLng;
				x_point = latLng.lat();
				y_point = latLng.lng();
				// Clear marker
				if (marker && marker.setMap) {
					marker.setMap(null);
				}
				// Add new marker on map
				marker = new google.maps.Marker({
					position: latLng,
					map: map
				});
			});


			// Add search input
			const search_html = '<input type="text" id="map_search" class="form-control" placeholder="<?php echo _l('real_search_on_google_map'); ?>">';
			$('.map-input-search').html(search_html);
			// Create the search box and link it to the UI element.
			const input = document.getElementById("map_search");
			const searchBox = new google.maps.places.SearchBox(input);

			map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);
 			 // Bias the SearchBox results towards current map's viewport.
			map.addListener("bounds_changed", () => {
				searchBox.setBounds(map.getBounds());
			});
  			// Listen for the event fired when the user selects a prediction and retrieve
  			// more details for that place.
			searchBox.addListener("places_changed", () => {
				const places = searchBox.getPlaces();
				if (places.length == 0) {
					return;
				}
    			// For each place, get the icon, name and location.
				const bounds = new google.maps.LatLngBounds();
				places.forEach((place) => {
					if (!place.geometry || !place.geometry.location) {
						alert("Returned place contains no geometry");
						return;
					}
					var latLng = place.geometry.location;
					x_point = latLng.lat();
					y_point = latLng.lng();
					// Clear marker
					if (marker && marker.setMap) {
						marker.setMap(null);
					}
      				// Create a marker for each place.
					marker = new google.maps.Marker({
						position: latLng,
						map: map
					});

					if (place.geometry.viewport) {
        				// Only geocodes have viewport.
						bounds.union(place.geometry.viewport);
					} else {
						bounds.extend(place.geometry.location);
					}
				});
				map.fitBounds(bounds);
			});
		}
	}


	function rel_add_room_to_table(data, itemid) {
		"use strict";

		data = typeof (data) == 'undefined' || data == 'undefined' ? rel_get_room_preview_values() : data;
		if (data.room_type == "" || data.rooms_level == "" || data.room_demension_width == "" || data.room_demension_lenght == ""  || data.room_type == undefined || data.rooms_level == undefined || data.room_demension_width == undefined || data.room_demension_lenght == undefined || data.room_benefits == undefined ) {
			alert_float('warning', '<?php echo _l('real_please_fill_data') ?>');
			return;
		}
		var table_row = '';
		var item_key = lastAddedItemKey ? lastAddedItemKey += 1 : $("body").find('.invoice-items-table tbody .item').length + 1;
		lastAddedItemKey = item_key;
		$("body").append('<div class="dt-loader"></div>');
		rel_get_room_row_template('newitems[' + item_key + ']',data.room_type,data.rooms_level,data.room_demension_width, data.room_demension_lenght, data.room_benefits, itemid).done(function(output){
			table_row += output;

			$('.invoice-item table.invoice-items-table.items tbody').append(table_row);

			init_selectpicker();
			init_datepicker();
			rel_reorder_rooms('.invoice-item');
			rel_clear_room_preview_values('.invoice-item');
			$('body').find('#items-warning').remove();
			$("body").find('.dt-loader').remove();
			$('#item_select').selectpicker('val', '');

			return true;
		});
		return false;
	}

	function rel_get_room_preview_values() {
		"use strict";

		var response = {};
		response.room_type = $('.invoice-item .main select[name="room_type"]').val();
		response.rooms_level = $('.invoice-item .main select[name="rooms_level"]').val();
		response.room_demension_width = $('.invoice-item .main input[name="room_demension_width"]').val();
		response.room_demension_lenght = $('.invoice-item .main input[name="room_demension_lenght"]').val();
		response.room_benefits = $('.invoice-item .main select[name="room_benefits[]"]').val();
		return response;
	}

	function rel_clear_room_preview_values(parent) {
		"use strict";

		var previewArea = $(parent + ' .main');
		previewArea.find('input').val('');
		previewArea.find('textarea').val('');
		previewArea.find('select').val('').selectpicker('refresh');
	}

	function rel_get_room_row_template(name, room_type, rooms_level, room_demension_width, room_demension_lenght, room_benefits, item_key)  {
		"use strict";

		jQuery.ajaxSetup({
			async: false
		});

		var d = $.post(page_url + 'get_room_row_template', {
			name: name,
			room_type : room_type,
			rooms_level : rooms_level,
			room_demension_width : room_demension_width,
			room_demension_lenght : room_demension_lenght,
			room_benefits : room_benefits,
			item_key : item_key
		});
		jQuery.ajaxSetup({
			async: true
		});
		return d;
	}

	function rel_delete_room(row, itemid,parent) {
		"use strict";

		$(row).parents('tr').addClass('animated fadeOut', function () {
			setTimeout(function () {
				$(row).parents('tr').remove();
			}, 50);
		});
		if (itemid && $('input[name="isedit"]').length > 0) {
			$(parent+' #removed-items').append(hidden_input('removed_items[]', itemid));
		}
	}

	function rel_reorder_rooms(parent) {
		"use strict";

		var rows = $(parent + ' .table.has-calculations tbody tr.item');
		var i = 1;
		$.each(rows, function () {
			$(this).find('input.order').val(i);
			i++;
		});
	}

	function productSubmitHandler(form) {
		'use strict';	

		var data={};
		data.formdata = $(form).serializeArray();
		data.search_listing_id = check_lat_lng_in_search();
		data.long_description = tinymce.activeEditor.getContent();

		$('#box-loading').show();
		$('.submit_button').attr( "disabled", "disabled" );

		$.post(form.action, data).done(function(response1) {
			var response = JSON.parse(response1);
			if (response.commodityid) {
				if(typeof(expenseDropzone) !== 'undefined' && typeof(expenseDropzone1) !== 'undefined' && typeof(expenseDropzone2) !== 'undefined' && typeof(expenseDropzone3) !== 'undefined'){
					if (expenseDropzone.getQueuedFiles().length > 0 || expenseDropzone1.getQueuedFiles().length > 0 || expenseDropzone2.getQueuedFiles().length > 0 || expenseDropzone3.getQueuedFiles().length > 0) {
						
						expenseDropzone.options.url = page_url + 'add_property_listing_attachment/' + response.commodityid;
						expenseDropzone.processQueue();
						expenseDropzone1.options.url = page_url + 'add_property_listing_attachment1/' + response.commodityid;
						expenseDropzone1.processQueue();
						expenseDropzone2.options.url = page_url + 'add_property_listing_attachment2/' + response.commodityid;
						expenseDropzone2.processQueue();
						expenseDropzone3.options.url = page_url + 'add_property_listing_attachment3/' + response.commodityid;

						expenseDropzone3.processQueue();

					} else {
						window.location.assign(response.url);
					}
				} else {
					window.location.assign(response.url);
				}
			} else {
				window.location.assign(response.url);
			}
		});
		return false;
	}

	function delete_property_listing_attachment(wrapper, attachment_id) {
		"use strict";  

		if (confirm_delete()) {
			$.get(page_url + 'delete_property_listing_attachment/' +attachment_id, function (response) {
				if (response.success == true) {
					$(wrapper).parents('.dz-preview').remove();

					var totalAttachmentsIndicator = $('.dz-preview'+attachment_id);
					var totalAttachments = totalAttachmentsIndicator.text().trim();

					if(totalAttachments == 1) {
						totalAttachmentsIndicator.remove();
					} else {
						totalAttachmentsIndicator.text(totalAttachments-1);
					}
					alert_float('success', "<?php echo _l('deleted_product_image_successfully') ?>");

				} else {
					alert_float('danger', "<?php echo _l('deleted_product_image_failed') ?>");
				}
			}, 'json');
		}
		return false;
	}


	function select_on_map(){
		"use strict";  

		$('#select_on_map').fadeIn(1000);
		const search_html = '<input type="text" id="map_search" class="form-control" placeholder="<?php echo _l('rel_search_on_google_map'); ?>">';
		$('.map-input-search').html(search_html);
		if($('#latitude').val() != '' && $('#longitude').val() != ''){
			$('#select_on_map input[name="lat"]').val($('#latitude').val());
			$('#select_on_map input[name="lng"]').val($('#longitude').val());
		}else{
			getLocation();			
		}
		initMap();
	}

	function getLocation() {
		"use strict";

		$.get("https://ipinfo.io", function(response) {
			if(response.country){
				var coordinates = response.loc.split(",");
				$('#select_on_map input[name="lat"]').val(coordinates[0]);
				$('#select_on_map input[name="lng"]').val(coordinates[1]);

			}
		})
	}
	
	function check_lat_lng_in_search(){
		'use strict';

		var result = '';
		var lat = $('#latitude').val();
		var lng = $('#longitude').val();
		if(lat != '' && lng != ''){
			var array = [];
			var mk_lat = parseFloat(lat);
			var mk_lng = parseFloat(lng);
			let i = 0;
			for(i = 0; i < drawing_search_history.length; i++ ) {
				var draw_obj = drawing_search_history[i];
				var id = draw_obj.id;
				var drawing_data = draw_obj.drawing_data;
				let j = 0;
				while (j < drawing_data.length) {
					var draw_filter_obj = drawing_data[j];
					var type = draw_filter_obj.type;
					var data = draw_filter_obj.data;
					if(type == 'rectangle'){	
						if((mk_lat >= parseFloat(data[0].lat) && mk_lat <= parseFloat(data[1].lat)) && (mk_lng >= parseFloat(data[0].lng) && mk_lng <= parseFloat(data[1].lng))){
							array.push(id);
							break;
						}
					}
					if(type == 'polygon'){
						let polygon = new google.maps.Polygon({path: data});
						var coordinate = new google.maps.LatLng(mk_lat, mk_lng);
						if(google.maps.geometry.poly.containsLocation(coordinate, polygon)){
							array.push(id);
							break;
						} 
					}
					j++;
				}
			}
			if(array.length > 0){
				result = array.join(",");
			}
		}
		return result;
	}

	$('select[name="transaction_type"]').on('change', function(){
		'use strict';

		if($('select[name="transaction_type"]').val() == 'Rent' || $('select[name="transaction_type"]').val() == 'Sale_and_Rent'){
			$('.show_rental_price').removeClass('hide');
			$('.show_sell_price').addClass('hide');
		}else{
			$('.show_rental_price').addClass('hide');
			$('.show_sell_price').removeClass('hide');
		}
	});

	$('select[name="water_access_yn"]').on('change', function(){
		'use strict';

		if($('select[name="water_access_yn"]').val() == 'yes' ){
			$('.water_access_hide').removeClass('hide');
		}else{
			$('.water_access_hide').addClass('hide');
		}
	});
	
	$('select[name="water_view_yn"]').on('change', function(){
		'use strict';

		if($('select[name="water_view_yn"]').val() == 'yes' ){
			$('.water_view_hide').removeClass('hide');
		}else{
			$('.water_view_hide').addClass('hide');
		}
	});
	$('select[name="water_extras_yn"]').on('change', function(){
		'use strict';

		if($('select[name="water_extras_yn"]').val() == 'yes' ){
			$('.water_extras_hide').removeClass('hide');
		}else{
			$('.water_extras_hide').addClass('hide');
		}
	});
	$('select[name="water_frontage_yn"]').on('change', function(){
		'use strict';

		if($('select[name="water_frontage_yn"]').val() == 'yes' ){
			$('.water_frontage_hide').removeClass('hide');
		}else{
			$('.water_frontage_hide').addClass('hide');
		}
	});

	$('select[name="rent_availability"]').on('change', function(){
		'use strict';

		if($('select[name="rent_availability"]').val() == 'yes' ){
			$('.show_rent_status').removeClass('hide');
		}else{
			$('.show_rent_status').addClass('hide');
		}
	});

	appValidateForm($("body").find('#add_group_form_manage'), {
		name: 'required'
	});

	function add_group_form_manage(id){ 
		'use strict';

		$('#add_group_retire_manage').modal('show');
		$('.modal-title.add').removeClass('hide');
		$('.modal-title.edit').addClass('hide');
		$('input[name="id"]').val('');
		$('input[name="name"]').val('');
	}


	appValidateForm($("body").find('#add_school_form_manage'), {
		name: 'required'
	});

	function add_school_form_manage(id){ 
		'use strict';
		$('#add_school_retire_manage').modal('show');
		$('.modal-title.add').removeClass('hide');
		$('.modal-title.edit').addClass('hide');
		$('input[name="id"]').val('');
		$('input[name="name"]').val('');
	}

	appValidateForm($("body").find('#add_landmark_form_manage'), {
		name: 'required'
	});

	function add_landmark_form_manage(id){ 
		'use strict';
		$('#add_landmark_retire_manage').modal('show');
		$('.modal-title.add').removeClass('hide');
		$('.modal-title.edit').addClass('hide');
		$('input[name="id"]').val('');
		$('input[name="name"]').val('');
	}


	$('.add_group').on('click', function() {
		"use strict";

		var group_name = $('#add_group_form_manage input[name="group"]').val();

		var data={};
		data.name=$('#add_group_form_manage input[name="name"]').val();

		if(data.name != '' ){
			$.get(page_url+'real_estate_add_group_name', data, function (response) {
				$('select[name="group_id"] option:selected').removeAttr('selected');

				if(response.status == true || response.status == 'true'){
					$('select[name="group_id"]').append(response.option);
					$('.selectpicker').selectpicker('refresh');

					alert_float('success', '<?php echo _l('added_successfully') ?>');
				}
				$('#add_group_form_manage input[name="name"]').val('');

				$('#add_group_retire_manage').modal('hide');


			}, 'json');

		}else{
			alert_float('warning', '<?php echo _l('rel_please_choose_group_name') ?>');
		}

	});

	$('.add_school').on('click', function() {
		"use strict";

		var data={};
		data.name=$('#add_school_form_manage input[name="name"]').val();

		if(data.name != '' ){
			$.get(page_url+'real_estate_add_school_name', data, function (response) {
				$('select[name="school"] option:selected').removeAttr('selected');

				if(response.status == true || response.status == 'true'){
					$('select[name="school"]').append(response.option);
					$('.selectpicker').selectpicker('refresh');

					alert_float('success', '<?php echo _l('added_successfully') ?>');
				}
				$('#add_school_form_manage input[name="name"]').val('');

				$('#add_school_retire_manage').modal('hide');


			}, 'json');

		}else{
			alert_float('warning', '<?php echo _l('rel_please_choose_school_name') ?>');
		}

	});

	$('.add_landmark').on('click', function() {
		"use strict";

		var data={};
		data.name=$('#add_landmark_form_manage input[name="name"]').val();

		if(data.name != '' ){
			$.get(page_url+'real_estate_add_landmark_name', data, function (response) {
				$('select[name="landmarks"] option:selected').removeAttr('selected');

				if(response.status == true || response.status == 'true'){
					$('select[name="landmarks"]').append(response.option);
					$('.selectpicker').selectpicker('refresh');

					alert_float('success', '<?php echo _l('added_successfully') ?>');
				}
				$('#add_landmark_form_manage input[name="name"]').val('');
				
				$('#add_landmark_retire_manage').modal('hide');

			}, 'json');

		}else{
			alert_float('warning', '<?php echo _l('rel_please_choose_landmarks_name') ?>');
		}

	});

	appValidateForm($("body").find('#add_hopspital_form_manage'), {
		name: 'required'
	});

	function add_hopspital_form_manage(id){ 
		'use strict';

		$('#add_hopspital_retire_manage').modal('show');
		$('.modal-title.add').removeClass('hide');
		$('.modal-title.edit').addClass('hide');
		$('input[name="id"]').val('');
		$('input[name="name"]').val('');
	}

	
	$('.add_hopspital').on('click', function() {
		"use strict";

		var data={};
		data.name=$('#add_hopspital_form_manage input[name="name"]').val();

		if(data.name != '' ){
			$.get(page_url+'real_estate_add_hopspital_name', data, function (response) {
				$('select[name="hopspital"] option:selected').removeAttr('selected');

				if(response.status == true || response.status == 'true'){
					$('select[name="hopspital"]').append(response.option);
					$('.selectpicker').selectpicker('refresh');

					alert_float('success', '<?php echo _l('added_successfully') ?>');
				}
				$('#add_hopspital_form_manage input[name="name"]').val('');
				
				$('#add_hopspital_retire_manage').modal('hide');

			}, 'json');

		}else{
			alert_float('warning', '<?php echo _l('rel_please_choose_hopspitals_name') ?>');
		}

	});

	function rel_add_payment_plan_to_table(data, itemid) {
		"use strict";

		data = typeof (data) == 'undefined' || data == 'undefined' ? rel_get_payment_plan_preview_values() : data;
		if (data.payment_plan == "" || data.amount == "" || data.payment_plan == undefined || data.amount == undefined ) {
			alert_float('warning', '<?php echo _l('real_please_fill_data') ?>');
			return;
		}
		var table_row = '';
		var item_key = lastAddedItemKey ? lastAddedItemKey += 1 : $("body").find('.payment-items-table tbody .payment-plan').length + 1;
		lastAddedItemKey = item_key;
		$("body").append('<div class="dt-loader"></div>');
		rel_get_payment_plan_row_template('newpaymentitems[' + item_key + ']',data.payment_plan,data.amount, itemid).done(function(output){
			table_row += output;

			$('.payment-item table.payment-items-table.payment-plans tbody').append(table_row);

			init_selectpicker();
			init_datepicker();
			rel_reorder_payment_plans('.payment-item');
			rel_clear_payment_plan_preview_values('.payment-item');
			$('body').find('#items-warning').remove();
			$("body").find('.dt-loader').remove();
			$('#item_select').selectpicker('val', '');

			return true;
		});
		return false;
	}

	function rel_get_payment_plan_preview_values() {
		"use strict";

		var response = {};
		response.payment_plan = $('.payment-item .main select[name="payment_plan"]').val();
		response.amount = $('.payment-item .main input[name="amount"]').val();
		return response;
	}

	function rel_clear_payment_plan_preview_values(parent) {
		"use strict";

		var previewArea = $(parent + ' .main');
		previewArea.find('input').val('');
		previewArea.find('textarea').val('');
		previewArea.find('select').val('').selectpicker('refresh');
	}

	function rel_get_payment_plan_row_template(name, payment_plan, amount, item_key)  {
		"use strict";

		jQuery.ajaxSetup({
			async: false
		});

		var d = $.post(page_url + 'get_payment_plan_row_template', {
			name: name,
			payment_plan : payment_plan,
			amount : amount,
			item_key : item_key
		});
		jQuery.ajaxSetup({
			async: true
		});
		return d;
	}

	function rel_delete_payment_plan(row, itemid,parent) {
		"use strict";

		$(row).parents('tr').addClass('animated fadeOut', function () {
			setTimeout(function () {
				$(row).parents('tr').remove();
			}, 50);
		});
		if (itemid && $('input[name="isedit"]').length > 0) {
			$(parent+' #removed-payment-items').append(hidden_input('removed_payment_items[]', itemid));
		}
	}

	function rel_reorder_payment_plans(parent) {
		"use strict";

		var rows = $(parent + ' .table.has-calculations tbody tr.payment-plan');
		var i = 1;
		$.each(rows, function () {
			$(this).find('input.order').val(i);
			i++;
		});
	}

	function get_my_country(){
		'use strict';

		$.get("https://ipinfo.io", function(response) {
			if(response.country){
				var coordinates = response.loc.split(",");
				x_point = coordinates[0];
				y_point = coordinates[1];

				var latitude = $('#select_on_map input[name="lat"]').val();
				var longitude = $('#select_on_map input[name="lng"]').val();

				<?php if(!isset($property_listing) ){ ?>
					if((isNaN(latitude) || isNaN(longitude))){
						$('#select_on_map input[name="lat"]').val(x_point);
						$('#select_on_map input[name="lng"]').val(y_point);
					}
				<?php } ?>

				var url = page_url + 'get_country_id/' + response.country;
				requestGetJSON(url).done(function (response1) {
					$('#add_update_property_listing select[name="country"]').val(response1.country_id).change();
					$('#add_update_property_listing select[name="country"]').selectpicker("refresh");
				});
			}

		}, "jsonp");
	}

	$('#add_edit_request_broker input[name="broker_type"]').on('click', function() {
		"use strict";
		
		var broker_type = $(this).val();
		var data = {};
		data.broker_type = broker_type;
		data.property_id = <?php if(isset($property_listing) ){echo html_entity_decode($property_listing->id);}else{ echo '0';} ?>;

		$.get(page_url+'assign_property_to_broker', data, function (response) {

			if(response.status == true || response.status == 'true'){
				if(broker_type == 'staffs'){
					$('._my_staff').removeClass('hide');
					$('._real_agent').addClass('hide');
					$('._real_broker').addClass('hide');

					$('#add_edit_request_broker select[name="staff_id[]"]').html(response.staff_options);
					$('#add_edit_request_broker select[name="staff_id[]"]').selectpicker("refresh");

					$('#add_edit_request_broker select[name="company_id[]"]').html('');
					$('#add_edit_request_broker select[name="company_id[]"]').selectpicker("refresh");

				}else if(broker_type == 'agents'){
					$('._my_staff').addClass('hide');
					$('._real_agent').removeClass('hide');
					$('._real_broker').addClass('hide');

					$('#add_edit_request_broker select[name="company_id[]"]').html(response.agent_options);
					$('#add_edit_request_broker select[name="company_id[]"]').selectpicker("refresh");

					$('#add_edit_request_broker select[name="staff_id[]"]').html('');
					$('#add_edit_request_broker select[name="staff_id[]"]').selectpicker("refresh");

				}else if(broker_type == 'business_brokers'){
					$('._my_staff').addClass('hide');
					$('._real_agent').addClass('hide');
					$('._real_broker').removeClass('hide');
					$('#add_edit_request_broker select[name="broker_id[]"]').html(response.business_broker_options);
					$('#add_edit_request_broker select[name="broker_id[]"]').selectpicker("refresh");

					$('#add_edit_request_broker select[name="company_id[]"]').html('');
					$('#add_edit_request_broker select[name="company_id[]"]').selectpicker("refresh");

					$('#add_edit_request_broker select[name="staff_id[]"]').html('');
					$('#add_edit_request_broker select[name="staff_id[]"]').selectpicker("refresh");

				}
				init_selectpicker();
			}

		}, 'json');
	})


</script>