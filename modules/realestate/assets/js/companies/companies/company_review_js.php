<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';
	var company_id = $('input[name="company_id"]').val();
	var StaffServerParams={},ProposalServerParams = {}, property_listing_table;

	(function($) {
		"use strict";

		$('#pagination-demo').twbsPagination({
			totalPages: <?php echo html_entity_decode($total_page); ?>,
			visiblePages: 7,
			onPageClick: function (event, page) {
				$('input[name="page_number"]').val(page);
				initGridview(site_url + 'realestate/client/company_property_grid_view/' + company_id, ProposalServerParams);
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
						initGridview(site_url + 'realestate/client/company_property_grid_view', ProposalServerParams);
					}
				});

			}
		});
	}

</script>