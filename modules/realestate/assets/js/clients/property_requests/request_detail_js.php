<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	function property_request_status_mark_as(status, property_request_id, type) {
		"use strict"; 
		
		var url = page_url+'property_request_status_mark_as/' + status + '/' + property_request_id + '/' + type;
		var taskModalVisible = $('#task-modal').is(':visible');
		url += '?single_task=' + taskModalVisible;
		$("body").append('<div class="dt-loader"></div>');

		requestGetJSON(url).done(function (response) {
			$("body").find('.dt-loader').remove();
			if (response.success === true || response.success == 'true') {
				alert_float('success', response.message);
			}
			location.reload();
		});
	}

</script>