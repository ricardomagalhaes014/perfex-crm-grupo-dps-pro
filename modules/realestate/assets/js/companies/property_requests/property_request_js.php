<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	$(function(){
		'use strict';
		var ProposalServerParams = {
			"client_filter": "[name='client_filter[]']",
		};

		var property_request_table = $('table.table-property_request_table');
		var _table_api = initDataTable(property_request_table, page_url+'property_request_table', [0], [0], ProposalServerParams,  [0, 'desc']);
		$.each(ProposalServerParams, function(i, obj) {
			$('select' + obj).on('change', function() {  
				property_request_table.DataTable().ajax.reload();
			});
		});
   		property_request_table.DataTable().columns([5,8]).visible(false, false);
   		
		init_property_request();
	});
	var hidden_columns = [7,8];


	function property_request_status_mark_as(status, property_request_id, type) {
		"use strict"; 
		
		var url = page_url+'property_request_status_mark_as/' + status + '/' + property_request_id + '/' + type;
		var taskModalVisible = $('#task-modal').is(':visible');
		url += '?single_task=' + taskModalVisible;
		$("body").append('<div class="dt-loader"></div>');

		requestGetJSON(url).done(function (response) {
			$("body").find('.dt-loader').remove();
			if (response.success === true || response.success == 'true') {

				var av_tasks_tables = ['.table-property_request_table'];
				$.each(av_tasks_tables, function (i, selector) {
					if ($.fn.DataTable.isDataTable(selector)) {
						$(selector).DataTable().ajax.reload(null, false);
					}
				});
				alert_float('success', response.message);
			}
		});
	}

	function init_property_request(id) {
		"use strict"; 
		
		<?php if(is_broker_logged_in()){ ?>
			load_small_table_item(
				id,
				"#property_request",
				"propertyrequestid",
				"get_property_request_data_ajax",
				".table-property_request_table"
				);
		<?php }else{ ?>

			load_small_table_item(
				id,
				"#property_request",
				"propertyrequestid",
				"realestate/get_property_request_data_ajax",
				".table-property_request_table"
				);
		<?php } ?>
	}

	$("body").on("submit", "#property-request-notes", function () {
		"use strict"; 

		var form = $(this);
		if (form.find('textarea[name="description"]').val() === "") {
			return;
		}

		$.post(form.attr("action"), $(form).serialize()).done(function (rel_id) {
      // Reset the note textarea value
			form.find('textarea[name="description"]').val("");
      // Reload the notes
			if (form.hasClass("property-request-notes-form")) {
				<?php if(is_broker_logged_in()){ ?>
					get_sales_notes(rel_id, "realestate/broker");
				<?php }else{ ?>
					get_sales_notes(rel_id, "realestate");
				<?php } ?>
			
			}
		});
		return false;
	});

	$("body").on("click", ".property-request-send-to-client", function (e) {
		e.preventDefault();
		"use strict"; 

		$("#property_request_send_to_client_modal").modal("show");
	});

	$("body").on("click", ".close-send-template-modal", function () {
		"use strict"; 

		$("#property_request_send_to_client_modal").modal("hide");
	});

	$("body").on("change", ".onoffswitch input", function (event, state) {
		"use strict"; 

		setTimeout(function () {
			$('table.table-property_request_table').DataTable().ajax.reload();
		}, 300);
	});

</script>