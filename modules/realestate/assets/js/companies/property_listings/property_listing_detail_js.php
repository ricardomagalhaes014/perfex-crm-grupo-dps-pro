<script type="text/javascript">
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	(function(){
		"use strict";

		$('.scroll-nav-bar .left').on('click', function(){
			var scroll_frame = $('.scroll-nav-bar ul');
			var frame_width = scroll_frame.width() - 60;
			scroll_frame.animate({
				scrollLeft: "-="+frame_width+"px"
			}, "slow");
		});
		$('.scroll-nav-bar .right').on('click', function(){
			var scroll_frame = $('.scroll-nav-bar ul');
			var frame_width = scroll_frame.width() - 60;
			scroll_frame.animate({
				scrollLeft: "+="+frame_width+"px"
			}, "slow");
		});

		appValidateForm($('#send_listing-form'), {
			send_to: 'required',
			subject: 'required',
			fromname: 'required',
		});

		appValidateForm($('#send_listing_contact-form'), {
			subject: 'required',
			fromname: 'required',
		});

	})(jQuery);

	function initMap() {
		'use strict';

		// The location of Uluru
		
		const lat_s = $('input[name="lat"]').val();
		const lng_s = $('input[name="lng"]').val();

		if(lat_s != '' && lng_s != ''){
			const uluru = { lat: parseFloat(lat_s), lng: parseFloat(lng_s) };
		// The map, centered at Uluru
			const map = new google.maps.Map(document.getElementById("map"), {
				zoom: 18,
				center: uluru,
				zoomControl: false,
				mapTypeControl: true,
				scaleControl: true,
				streetViewControl: true,
				rotateControl: true,
				fullscreenControl: true
			});
		// The marker, positioned at Uluru
			const marker = new google.maps.Marker({
				position: uluru,
				map: map,
			});  	
		}
	}


	function close_modal_preview(){
		'use strict';

		$('._project_file').modal('hide'); 
	}



	function htmlEncode(value){
		"use strict";

		return $('<textarea/>').text(value).html();
	}


	function send_listing() {
		'use strict';

		html2canvas(document.getElementById("map"),
		{
			allowTaint: true,
			useCORS: true,
			taintTest: false,
			letterRendering: true
		}).then(function (canvas) {

			$('[name="capture_image"]').val(htmlEncode(canvas.toDataURL().replace('data:image/png;base64,','')));
			var data = {};
			data.imgbase64 = htmlEncode(canvas.toDataURL().replace('data:image/png;base64,',''));
			data.listing_id = <?php echo new_html_entity_decode($property_listing->id); ?>;
			$.post(page_url + 'save_image_from_base64', data).done(function(response1) {
				var response = JSON.parse(response1);
				if(response.status == true){
					$('#send_listing-form').submit();
				}
			});
		});
	}

	
	function send_request_quotation1(id) {
		"use strict"; 

		$('#additional_rqquo1').html('');
		$('#additional_rqquo1').append(hidden_input('property_listing_id', <?php echo new_html_entity_decode($property_listing->id); ?>));
		$('#request_quotation1').modal('show');
	}

	function send_listing_contact() {
		'use strict';

		$('#send_listing_contact-form').submit();
	}

	// Deletes activity for sales eq. invoices, estimates.
	function delete_property_activity(id) {
		'use strict';
		
		if (confirm_delete()) {
			requestGet(page_url + "delete_property_activity/" + id).done(function () {
				$("body")
				.find('[data-sale-activity-id="' + id + '"]')
				.remove();
			});
		}
	}

</script>