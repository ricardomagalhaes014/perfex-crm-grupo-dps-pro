<script type="text/javascript">
	var page_url = '<?php echo html_entity_decode($site_url); ?>';
	
	function preview_file(invoker){
		'use strict';

		var id = $(invoker).attr('id');
		var rel_id = $(invoker).attr('rel_id');
		view_file(id, rel_id);
	}

	function view_file(id, rel_id) {   
		'use strict';

		$('#pdf_file_data').empty();
		$("#pdf_file_data").load(page_url + 'listing_pdf_file/' + id + '/' + rel_id, function(response, status, xhr) {
			if (status == "error") {
				alert_float('danger', xhr.statusText);
			}
		});
	}

	function delete_listing_attachment_pdf_file(wrapper, id) {
		'use strict';

		if (confirm_delete()) {
			$.get(page_url + 'delete_listing_attachment_pdf_file/' + id, function (response) {
				if (response.success == true) {
					$(wrapper).parents('.pdf_attachment').remove();

					var totalAttachmentsIndicator = $('.attachments-indicator');
					var totalAttachments = totalAttachmentsIndicator.text().trim();
					if(totalAttachments == 1) {
						totalAttachmentsIndicator.remove();
					} else {
						totalAttachmentsIndicator.text(totalAttachments-1);
					}
				} else {
					alert_float('danger', response.message);
				}
			}, 'json');
		}
		return false;
	}

	function delete_property_video_attachment(wrapper, attachment_id) {
		"use strict";  

		if (confirm_delete()) {
			$.get(page_url + 'delete_property_listing_attachment/' +attachment_id + '/PROPERTY_VIDEO_UPLOAD', function (response) {
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

</script>