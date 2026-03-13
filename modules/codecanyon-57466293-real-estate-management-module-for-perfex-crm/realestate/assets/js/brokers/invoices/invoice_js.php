<script>
	var page_url = '<?php echo html_entity_decode($site_url); ?>';
	
	$(function() {
		'use strict';
		appValidateForm($('#invoice-form'), {
			clientid: {
				required: {
					depends: function () {
						var customerRemoved =
						$("select#clientid").hasClass("customer-removed");
						return !customerRemoved;
					},
				},
			},
			date: "required",
			currency: "required",
			repeat_every_custom: {
				min: 1,
			},
			number: {
				required: true,
			},
		});
		$("body")
		.find('input[name="number"]')
		.rules("add", {
			remote: {
				url: site_url + "realestate/broker/validate_invoice_number",
				type: "post",
				data: {
					number: function () {
						return $('input[name="number"]').val();
					},
					isedit: function () {
						return $('input[name="number"]').data("isedit");
					},
					original_number: function () {
						return $('input[name="number"]').data("original-number");
					},
					date: function () {
						return $('input[name="date"]').val();
					},
				},
			},
			messages: {
				remote: app.lang.invoice_number_exists,
			},
		});
	// Init accountacy currency symbol
		init_currency();
		real_init_ajax_search("customer", "#clientid.ajax-search");
	// Project ajax search
		init_ajax_project_search_by_customer_id();

		$("body").on("change", "#unlimited_cycles", function () {
			$(this)
			.parents(".recurring-cycles")
			.find("#cycles")
			.prop("disabled", $(this).prop("checked"));
		});

  // For expenses and recurring tasks
		$("body").on("change",'[name="repeat_every"], [name="recurring"]',
			function () {
				var val = $(this).val();
				val == "custom"
				? $(".recurring_custom").removeClass("hide")
				: $(".recurring_custom").addClass("hide");
				if (val !== "" && val != 0) {
					$("body").find("#cycles_wrapper").removeClass("hide");
				} else {
					$("body").find("#cycles_wrapper").addClass("hide");
					$("body").find("#cycles_wrapper #cycles").val(0);
					$("#unlimited_cycles").prop("checked", true).change();
				}
			}
			);

		$("body").on("change", ".f_client_id #clientid", function () {
			var val = $(this).val();
			var projectAjax = $('select[name="project_id"]');
			var clonedProjectsAjaxSearchSelect = projectAjax.html("").clone();
			var projectsWrapper = $(".projects-wrapper");
			projectAjax.selectpicker("destroy").remove();
			projectAjax = clonedProjectsAjaxSearchSelect;
			$("#project_ajax_search_wrapper").append(clonedProjectsAjaxSearchSelect);
			init_ajax_project_search_by_customer_id();
			clear_billing_and_shipping_details();
			if (!val) {
				$("#merge").empty();
				$("#expenses_to_bill").empty();
				$("#invoice_top_info").addClass("hide");
				projectsWrapper.addClass("hide");
				return false;
			}

			var currentInvoiceID = $("body")
			.find('input[name="merge_current_invoice"]')
			.val();
			currentInvoiceID =
			typeof currentInvoiceID == "undefined" ? "" : currentInvoiceID;

			requestGetJSON(
				"realestate/broker/invoice_client_change_data/" + val + "/" + currentInvoiceID
				).done(function (response) {
					$("#merge").html(response.merge_info);
					var $billExpenses = $("#expenses_to_bill");
	  // Invoice from project, in invoice_template this is not shown
					$billExpenses.length === 0
					? (response.expenses_bill_info = "")
					: $billExpenses.html(response.expenses_bill_info);
					response.merge_info !== "" || response.expenses_bill_info !== ""
					? $("#invoice_top_info").removeClass("hide")
					: $("#invoice_top_info").addClass("hide");

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
			});

	});


// Clear the items added to preview
function clear_item_preview_values(default_taxes) {
	'use strict';

  // Get the last taxes applied to be available for the next item
	var last_taxes_applied = $("table.items tbody")
	.find("tr:last-child")
	.find("select")
	.selectpicker("val");
	var previewArea = $(".main");

  previewArea.find("textarea").val(""); // includes cf
  previewArea
  .find('td.custom_field input[type="checkbox"]')
	.prop("checked", false); // cf
  previewArea.find("td.custom_field input:not(:checkbox):not(:hidden)").val(""); // cf // not hidden for chkbox hidden helpers
  previewArea.find("td.custom_field select").selectpicker("val", ""); // cf
  previewArea.find('input[name="quantity"]').val(1);
  previewArea.find("select.tax").selectpicker("val", last_taxes_applied);
  previewArea.find('input[name="rate"]').val("");
  previewArea.find('input[name="unit"]').val("");

  $('input[name="task_id"]').val("");
  $('input[name="expense_id"]').val("");
}


// Reoder the items in table edit for estimate and invoices
function reorder_items() {
	'use strict';

	var rows = $(".table.has-calculations tbody tr.item");
	var i = 1;
	$.each(rows, function () {
		$(this).find("input.order").val(i);
		i++;
	});
}

// Deletes invoice items
function delete_item(row, itemid) {
	'use strict';

	$(row)
	.parents("tr")
	.addClass("animated fadeOut", function () {
		setTimeout(function () {
			$(row).parents("tr").remove();
			calculate_total();
		}, 50);
	});

  // If is edit we need to add to input removed_items to track activity
	if (itemid && $('input[name="isedit"]').length > 0) {
		$("#removed-items").append(hidden_input("removed_items[]", itemid));
	}
}


// Append the added items to the preview to the table as items
function add_item_to_table(data, itemid, merge_invoice, bill_expense) {
	'use strict';

  // If not custom data passed get from the preview
	data =
	typeof data == "undefined" || data == "undefined"
	? get_item_preview_values()
	: data;
	if (
		data.description === "" &&
		data.long_description === "" &&
		data.rate === ""
		) {
		return;
}

var table_row = "";
var item_key = lastAddedItemKey
? (lastAddedItemKey += 1)
: $("body").find("tbody .item").length + 1;
lastAddedItemKey = item_key;

table_row +=
'<tr class="sortable item" data-merge-invoice="' +
merge_invoice +
'" data-bill-expense="' +
bill_expense +
'">';

table_row += '<td class="dragger">';

  // Check if quantity is number
if (isNaN(data.qty)) {
	data.qty = 1;
}

  // Check if rate is number
if (data.rate === "" || isNaN(data.rate)) {
	data.rate = 0;
}

var amount = data.rate * data.qty;

var tax_name = "newitems[" + item_key + "][taxname][]";
$("body").append('<div class="dt-loader"></div>');
var regex = /<br[^>]*>/gi;
get_taxes_dropdown_template(tax_name, data.taxname).done(function (
	tax_dropdown
	) {
	// order input
	table_row +=
	'<input type="hidden" class="order" name="newitems[' +
	item_key +
	'][order]">';

	table_row += "</td>";

	table_row +=
	'<td class="bold description"><textarea name="newitems[' +
	item_key +
	'][description]" class="form-control" rows="5">' +
	data.description +
	"</textarea></td>";

	table_row +=
	'<td><textarea name="newitems[' +
	item_key +
	'][long_description]" class="form-control item_long_description" rows="5">' +
	data.long_description.replace(regex, "\n") +
	"</textarea></td>";

	var custom_fields = $("tr.main td.custom_field");
	var cf_has_required = false;

	if (custom_fields.length > 0) {
		$.each(custom_fields, function () {
			var cf = $(this).clone();
			var cf_html = "";
			var cf_field = $(this).find("[data-fieldid]");
			var cf_name =
			"newitems[" +
			item_key +
			"][custom_fields][items][" +
			cf_field.attr("data-fieldid") +
			"]";

			if (cf_field.is(":checkbox")) {
				var checked = $(this).find('input[type="checkbox"]:checked');
				var checkboxes = cf.find('input[type="checkbox"]');

				$.each(checkboxes, function (i, e) {
					var random_key = Math.random().toString(20).slice(2);
					$(this)
					.attr("id", random_key)
					.attr("name", cf_name)
					.next("label")
					.attr("for", random_key);
					if ($(this).attr("data-custom-field-required") == "1") {
						cf_has_required = true;
					}
				});

				$.each(checked, function (i, e) {
					cf.find('input[value="' + $(e).val() + '"]').attr("checked", true);
				});

				cf_html = cf.html();
			} else if (cf_field.is("input") || cf_field.is("textarea")) {
				if (cf_field.is("input")) {
					cf.find("[data-fieldid]").attr("value", cf_field.val());
				} else {
					cf.find("[data-fieldid]").html(cf_field.val());
				}
				cf.find("[data-fieldid]").attr("name", cf_name);
				if (
					cf.find("[data-fieldid]").attr("data-custom-field-required") == "1"
					) {
					cf_has_required = true;
			}
			cf_html = cf.html();
		} else if (cf_field.is("select")) {
			if ($(this).attr("data-custom-field-required") == "1") {
				cf_has_required = true;
			}

			var selected = $(this)
			.find("select[data-fieldid]")
			.selectpicker("val");
			selected = typeof (selected != "array")
			? new Array(selected)
			: selected;

		  // Check if is multidimensional by multi-select customfield
			selected = selected[0].constructor === Array ? selected[0] : selected;

			var selectNow = cf.find("select");
			var $wrapper = $("<div/>");
			selectNow.attr("name", cf_name);

			var $select = selectNow.clone();
			$wrapper.append($select);
			$.each(selected, function (i, e) {
				$wrapper
				.find('select option[value="' + e + '"]')
				.attr("selected", true);
			});

			cf_html = $wrapper.html();
		}
		table_row += '<td class="custom_field">' + cf_html + "</td>";
	});
	}

	table_row +=
	'<td><input type="number" min="0" onblur="calculate_total();" onchange="calculate_total();" data-quantity name="newitems[' +
	item_key +
	'][qty]" value="' +
	data.qty +
	'" class="form-control">';

	if (!data.unit || typeof data.unit == "undefined") {
		data.unit = "";
	}

	table_row +=
	'<input type="text" placeholder="' +
	app.lang.unit +
	'" name="newitems[' +
	item_key +
	'][unit]" class="form-control input-transparent text-right" value="' +
	data.unit +
	'">';

	table_row += "</td>";

	table_row +=
	'<td class="rate"><input type="number" data-toggle="tooltip" title="' +
	app.lang.item_field_not_formatted +
	'" onblur="calculate_total();" onchange="calculate_total();" name="newitems[' +
	item_key +
	'][rate]" value="' +
	data.rate +
	'" class="form-control"></td>';

	table_row += '<td class="taxrate">' + tax_dropdown + "</td>";

	table_row +=
	'<td class="amount" align="right">' +
	format_money(amount, true) +
	"</td>";

	table_row +=
	'<td><a href="#" class="btn btn-danger pull-left" onclick="delete_item(this,' +
	itemid +
	'); return false;"><i class="fa fa-trash"></i></a></td>';

	table_row += "</tr>";

	$("table.items tbody").append(table_row);

	$(document).trigger({
		type: "item-added-to-table",
		data: data,
		row: table_row,
	});

	setTimeout(function () {
		calculate_total();
	}, 15);

	var billed_task = $('input[name="task_id"]').val();
	var billed_expense = $('input[name="expense_id"]').val();

	if (billed_task !== "" && typeof billed_task != "undefined") {
		billed_tasks = billed_task.split(",");
		$.each(billed_tasks, function (i, obj) {
			$("#billed-tasks").append(
				hidden_input("billed_tasks[" + item_key + "][]", obj)
				);
		});
	}

	if (billed_expense !== "" && typeof billed_expense != "undefined") {
		billed_expenses = billed_expense.split(",");
		$.each(billed_expenses, function (i, obj) {
			$("#billed-expenses").append(
				hidden_input("billed_expenses[" + item_key + "][]", obj)
				);
		});
	}

	if (
		$("#item_select").hasClass("ajax-search") &&
		$("#item_select").selectpicker("val") !== ""
		) {
		$("#item_select").prepend("<option></option>");
}

init_selectpicker();
init_datepicker();
init_color_pickers();
clear_item_preview_values();
reorder_items();

$("body").find("#items-warning").remove();
$("body").find(".dt-loader").remove();
$("#item_select").selectpicker("val", "");

if (cf_has_required && $(".invoice-form").length) {
	validate_invoice_form();
} else if (cf_has_required && $(".estimate-form").length) {
	validate_estimate_form();
} else if (cf_has_required && $(".proposal-form").length) {
	validate_proposal_form();
} else if (cf_has_required && $(".credit-note-form").length) {
	validate_credit_note_form();
}

if (bill_expense == "undefined" || !bill_expense) {
	$('select[name="task_select"]')
	.find('[value="' + billed_task + '"]')
	.remove();
	$('select[name="task_select"]').selectpicker("refresh");
}
return true;
});

return false;
}

// Get taxes dropdown selectpicker template / Causing problems with ajax becuase is fetching from server
function get_taxes_dropdown_template(name, taxname) {
	'use strict';

	jQuery.ajaxSetup({
		async: false,
	});
	var d = $.post(site_url + "realestate/broker/get_taxes_dropdown_template/", {
		name: name,
		taxname: taxname,
	});
	jQuery.ajaxSetup({
		async: true,
	});

	return d;
}

// Get the preview main values
function get_item_preview_values() {
	'use strict';

	var response = {};
	response.description = $('.main textarea[name="description"]').val();
	response.long_description = $(
		'.main textarea[name="long_description"]'
		).val();
	response.qty = $('.main input[name="quantity"]').val();
	response.taxname = $(".main select.tax").selectpicker("val");
	response.rate = $('.main input[name="rate"]').val();
	response.unit = $('.main input[name="unit"]').val();
	return response;
}

// Calculate invoice total - NOT RECOMENDING EDIT THIS FUNCTION BECUASE IS VERY SENSITIVE
function calculate_total() {
	'use strict';
	
	if ($("body").hasClass("no-calculate-total")) {
		return false;
	}

	var calculated_tax,
	taxrate,
	item_taxes,
	row,
	_amount,
	_tax_name,
	taxes = {},
	taxes_rows = [],
	subtotal = 0,
	total = 0,
	quantity = 1,
	total_discount_calculated = 0,
	rows = $(".table.has-calculations tbody tr.item"),
	discount_area = $("#discount_area"),
	adjustment = $('input[name="adjustment"]').val(),
	discount_percent = $('input[name="discount_percent"]').val(),
	discount_fixed = $('input[name="discount_total"]').val(),
	discount_total_type = $(".discount-total-type.selected"),
	discount_type = $('select[name="discount_type"]').val();

	$(".tax-area").remove();

	$.each(rows, function () {
		quantity = $(this).find("[data-quantity]").val();
		if (quantity === "") {
			quantity = 1;
			$(this).find("[data-quantity]").val(1);
		}

		_amount = accounting.toFixed(
			$(this).find("td.rate input").val() * quantity,
			app.options.decimal_places
			);
		_amount = parseFloat(_amount);

		$(this).find("td.amount").html(format_money(_amount, true));
		subtotal += _amount;
		row = $(this);
		item_taxes = $(this).find("select.tax").selectpicker("val");

		if (item_taxes) {
			$.each(item_taxes, function (i, taxname) {
				taxrate = row
				.find('select.tax [value="' + taxname + '"]')
				.data("taxrate");
				calculated_tax = (_amount / 100) * taxrate;
				if (!taxes.hasOwnProperty(taxname)) {
					if (taxrate != 0) {
						_tax_name = taxname.split("|");
						tax_row =
						'<tr class="tax-area"><td>' +
						_tax_name[0] +
						"(" +
						taxrate +
						'%)</td><td id="tax_id_' +
						slugify(taxname) +
						'"></td></tr>';
						$(discount_area).after(tax_row);
						taxes[taxname] = calculated_tax;
					}
				} else {
		  // Increment total from this tax
					taxes[taxname] = taxes[taxname] += calculated_tax;
				}
			});
		}
	});

  // Discount by percent
	if (
		discount_percent !== "" &&
		discount_percent != 0 &&
		discount_type == "before_tax" &&
		discount_total_type.hasClass("discount-type-percent")
		) {
		total_discount_calculated = (subtotal * discount_percent) / 100;
} else if (
	discount_fixed !== "" &&
	discount_fixed != 0 &&
	discount_type == "before_tax" &&
	discount_total_type.hasClass("discount-type-fixed")
	) {
	total_discount_calculated = discount_fixed;
}

$.each(taxes, function (taxname, total_tax) {
	if (
		discount_percent !== "" &&
		discount_percent != 0 &&
		discount_type == "before_tax" &&
		discount_total_type.hasClass("discount-type-percent")
		) {
		total_tax_calculated = (total_tax * discount_percent) / 100;
	total_tax = total_tax - total_tax_calculated;
} else if (
	discount_fixed !== "" &&
	discount_fixed != 0 &&
	discount_type == "before_tax" &&
	discount_total_type.hasClass("discount-type-fixed")
	) {
	var t = (discount_fixed / subtotal) * 100;
	total_tax = total_tax - (total_tax * t) / 100;
}

total += total_tax;
total_tax = format_money(total_tax);
$("#tax_id_" + slugify(taxname)).html(total_tax);
});

total = total + subtotal;

  // Discount by percent
if (
	discount_percent !== "" &&
	discount_percent != 0 &&
	discount_type == "after_tax" &&
	discount_total_type.hasClass("discount-type-percent")
	) {
	total_discount_calculated = (total * discount_percent) / 100;
} else if (
	discount_fixed !== "" &&
	discount_fixed != 0 &&
	discount_type == "after_tax" &&
	discount_total_type.hasClass("discount-type-fixed")
	) {
	total_discount_calculated = discount_fixed;
}

total = total - total_discount_calculated;
adjustment = parseFloat(adjustment);

  // Check if adjustment not empty
if (!isNaN(adjustment)) {
	total = total + adjustment;
}

var discount_html = "-" + format_money(total_discount_calculated);
$('input[name="discount_total"]').val(
	accounting.toFixed(total_discount_calculated, app.options.decimal_places)
	);

  // Append, format to html and display
$(".discount-total").html(discount_html);
$(".adjustment").html(format_money(adjustment));
$(".subtotal").html(
	format_money(subtotal) +
	hidden_input(
		"subtotal",
		accounting.toFixed(subtotal, app.options.decimal_places)
		)
	);
$(".total").html(
	format_money(total) +
	hidden_input(
		"total",
		accounting.toFixed(total, app.options.decimal_places)
		)
	);

$(document).trigger("sales-total-calculated");
}



</script>

