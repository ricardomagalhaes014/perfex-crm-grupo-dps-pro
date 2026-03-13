<script>
	var lastAddedItemKey = null;
	var page_url = '<?php echo html_entity_decode($site_url); ?>';

	(function($) {
		"use strict";  
		init_request_currency(<?php echo html_entity_decode($base_currency_id) ?>);
		appValidateForm($('#add_order'), {
			datecreated: 'required',
			clientid: 'required',
			sale_agent: 'required',
			item_id: 'required',
		});

		real_calculate_total();
	  // On document read check and init for client ajax-search
		real_init_ajax_search("customer", "#clientid.ajax-search");
		<?php if(!isset($property_request)){ ?>
		   // When customer_id is passed init the data
			client_change_data();
			get_property_by_id();
		<?php } ?>

	// Add item to preview from the dropdown for invoices estimates
		$("body").on('change', 'select[name="item_id"]', function () {
			get_property_by_id();
		});

		$("body").on('click', '.add_order', function () {
			submit_form(false);
		});

		$('.add_order_send').on('click', function() {
			submit_form(true);
		});

		$('input[name="term_month"]').on('change', function() {
			'use strict';

			real_calculate_total();
			calculate_estimated_request_duration();
		});

		$('input[name="date"]').on('change', function() {
			'use strict';

			real_calculate_total();
			calculate_estimated_request_duration()
		});

		$('input[name="inspect_property"]').on('change', function() {
			'use strict';

			if($(this).val() == 1){
				$('.inspection_date_hide').removeClass('hide');
			}else{
				$('.inspection_date_hide').addClass('hide');

			}
		});

	})(jQuery);

	function get_property_by_id(){
		'use strict';

		var itemid = $('select[name="item_id"]').val();
		var request_type = $('input[name="request_type"]').val();
		if(itemid){
			requestGetJSON(page_url + 'get_property_by_id/' + itemid ).done(function (response) {
				$('.property_container').html(response.property_html);
				var property_price;
				if(request_type == 'buy'){
					property_price = parseFloat(response.rate);
				}else{
					property_price = parseFloat(response.rent_price);
				}

				$('span.input-group-addon').html(response.rent_label);

				$('.wh-subtotal').html(format_money(property_price) + hidden_input('property_price', accounting.toFixed(property_price, app.options.decimal_places)));
				real_calculate_total();

			});
		}
	}

	function client_change_data(){
		'use strict';

		var val = $('select[name="clientid"]').val();
		var projectsWrapper = $(".projects-wrapper");
		clear_billing_and_shipping_details();
		if (!val) {
			$("#merge").empty();
			$("#expenses_to_bill").empty();
			$("#invoice_top_info").addClass("hide");
			projectsWrapper.addClass("hide");
			return false;
		}

		var currentInvoiceID = '';

		requestGetJSON(
			page_url + "client_change_data/" + val + "/" + currentInvoiceID
			).done(function (response) {

				for (var f in billingAndShippingFields) {
					if (billingAndShippingFields[f].indexOf("billing") > -1) {
						if (billingAndShippingFields[f].indexOf("country") > -1) {
							$(
								'select[name="' + billingAndShippingFields[f] + '"]'
								).selectpicker(
								"val",
								response["billing_shipping"][0][billingAndShippingFields[f]]
								);
							} else {
								if (billingAndShippingFields[f].indexOf("billing_street") > -1) {
									$('textarea[name="' + billingAndShippingFields[f] + '"]').val(
										response["billing_shipping"][0][billingAndShippingFields[f]]
										);
								} else {
									$('input[name="' + billingAndShippingFields[f] + '"]').val(
										response["billing_shipping"][0][billingAndShippingFields[f]]
										);
								}
							}
						}
					}

					if (!empty(response["billing_shipping"][0]["shipping_street"])) {
						$('input[name="include_shipping"]').prop("checked", true).change();
					}

					for (var fsd in billingAndShippingFields) {
						if (billingAndShippingFields[fsd].indexOf("shipping") > -1) {
							if (billingAndShippingFields[fsd].indexOf("country") > -1) {
								$(
									'select[name="' + billingAndShippingFields[fsd] + '"]'
									).selectpicker(
									"val",
									response["billing_shipping"][0][billingAndShippingFields[fsd]]
									);
								} else {
									if (billingAndShippingFields[fsd].indexOf("shipping_street") > -1) {
										$('textarea[name="' + billingAndShippingFields[fsd] + '"]').val(
											response["billing_shipping"][0][billingAndShippingFields[fsd]]
											);
									} else {
										$('input[name="' + billingAndShippingFields[fsd] + '"]').val(
											response["billing_shipping"][0][billingAndShippingFields[fsd]]
											);
									}
								}
							}
						}

						init_billing_and_shipping_details();

						var client_currency = response["client_currency"];
						var s_currency = $("body").find(
							'.accounting-template select[name="currency"]'
							);
						client_currency = parseInt(client_currency);
						client_currency != 0
						? s_currency.val(client_currency)
						: s_currency.val(s_currency.data("base"));

						s_currency.selectpicker("refresh");
						init_currency();
					});
		}

		function submit_form(save_and_send_request) {
			"use strict";

			$('input[name="duedate"]').prop("disabled", false);
			$(this).find('.add_order').prop('disabled', true);

			$('input[name="save_and_send_request"]').val(save_and_send_request);
			$('#add_order').submit();

			return true;
		}

// Set the currency for accounting
		function init_request_currency(id, callback) {
			"use strict"; 

			var $accountingTemplate = $("body").find('.accounting-template');

			if ($accountingTemplate.length || id) {
				var selectedCurrencyId = !id ? $accountingTemplate.find('select[name="currency"]').val() : id;
				requestGetJSON(page_url+'get_currency/' + selectedCurrencyId)
				.done(function (currency) {
					// Used for formatting money
					accounting.settings.currency.decimal = currency.decimal_separator;
					accounting.settings.currency.thousand = currency.thousand_separator;
					accounting.settings.currency.symbol = currency.symbol;
					accounting.settings.currency.format = currency.placement == 'after' ? '%v %s' : '%s%v';

					real_calculate_total();

					if(callback) {
						callback();
					}
				});
			}
		}

		function real_calculate_total(){
			"use strict";

			if ($('body').hasClass('no-calculate-total')) {
				return false;
			}

			var total = 0,
			_amount,
			quantity = $('input[name="term_month"]').val(),
			property_price = $('input[name="property_price"]').val();

			_amount = accounting.toFixed(parseFloat(property_price) * quantity, app.options.decimal_places);
			total = parseFloat(_amount);

			$('.wh-total').html(format_money(total) + hidden_input('total', accounting.toFixed(property_price, app.options.decimal_places))+ hidden_input('contract_total', accounting.toFixed(total, app.options.decimal_places)));
			$(document).trigger('real-request-total-calculated');
		}

		function calculate_estimated_request_duration() {
			'use strict';

			var data = {};
			data.contract_id = $('select[id="contract_id"]').val();
			data.term_month = $('input[name="term_month"]').val();
			data.start_date = $('input[id="date"]').val();
			data.end_date = $('input[id="duedate"]').val();
			data.item_id = $('select[id="item_id"]').val();

			$.post(page_url + 'calculate_estimated_request_duration', data).done(function(response){
				response = JSON.parse(response);
				$('input[name="term_month"]').val(response.term_month);
				$('input[name="duedate"]').val(response.end_date);
			});

		};

	</script>