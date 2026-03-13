var billingAndShippingFields = ['billing_street', 'billing_city', 'billing_state', 'billing_zip', 'billing_country', 'shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
(function($) {
    "use strict";
    // Maybe in modal? Eq convert to invoice or convert proposal to estimate/invoice
    calculate_total();
    init_item_js();

    $('.create_pre_order_btn').on('click', function(){
        let length = $('table.items tbody tr.item').length;
        if(length == 0){
            alert_float('warning', 'Please select a item');
            return false;
        }

        if($('select[name="allowed_payment_modes[]"]').val() == ''){
           alert_float('warning', 'Please select payment modes');
           return false;
        }
   });

    $(document).on("change", 'input[type="number"]', function () {
        var max = $(this).attr('max');
        var obj = $(this);

        if(max != 'undefined'){
            if(obj.val() != ''){
                if(parseFloat(obj.val()) > parseFloat(max)){
                    obj.val(max);
                }
            }
        }
        if(obj.val() == '' || obj.val() == 0){
            obj.val(1);
        }
    });

})(jQuery);
// Items add/edit
function init_item_js() {
    "use strict";
    // Add item to preview from the dropdown for invoices estimates
    $("body").on('change', 'select[name="item_select"]', function() {
        var itemid = $(this).selectpicker('val');
        if (itemid != '') {
            add_item_to_preview(itemid);
        }
    });
}

function ReplaceNumberWithCommas(yourNumber) {
    //Seperates the components of the number
    var n= yourNumber.toString().split(".");
    //Comma-fies the first part
    n[0] = n[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    //Combines the two sections
    return n.join(".");
}

// Add item to preview
function add_item_to_preview(id) {
    "use strict";
    requestGetJSON('omni_sales/omni_sales_client/get_item_by_id/' + id).done(function(response) {
        clear_item_preview_values();
        $('.main input[name="product_id"]').val(response.id);
        $('.main textarea[name="description"]').val(response.description);
        if(response.long_description != null){
            $('.main textarea[name="long_description"]').val(response.long_description.replace(/(<|&lt;)br\s*\/*(>|&gt;)/g, " "));
        }
        $('.main input[name="quantity"]').val(1);
        if(response.without_checking_warehouse == 1){
            $('.main input[name="quantity"]').attr('max', 1000);
        }
        else{
            $('.main input[name="quantity"]').removeAttr('max');
        }
        $('.main input[name="rate"]').val(response.rate_text);
        $('.main input[name="tax"]').val(response.taxname);
        $('.main .quantity .unit').text(response.unitname);
        $('.main input[name="discount"]').val(response.discount_price);
        data = response;
    });
}
// General helper function for $.get ajax requests
function requestGet(uri, params) {
    "use strict";
    params = typeof(params) == 'undefined' ? {} : params;
    var options = {
        type: 'GET',
        url: uri.indexOf(site_url) > -1 ? uri : site_url + uri
    };
    return $.ajax($.extend({}, options, params));
}
// General helper function for $.get ajax requests with dataType JSON
function requestGetJSON(uri, params) {
    "use strict";
    params = typeof(params) == 'undefined' ? {} : params;
    params.dataType = 'json';
    return requestGet(uri, params);
}
// Clear the items added to preview
function clear_item_preview_values(default_taxes) {
    "use strict";
    // Get the last taxes applied to be available for the next item
    var last_taxes_applied = $('table.items tbody').find('tr:last-child').find('select').selectpicker('val');
    var previewArea = $('.main');
    previewArea.find('textarea').val(''); // includes cf
    previewArea.find('input[name="quantity"]').val(1);
    previewArea.find('input[name="rate"]').val('');
    previewArea.find('input[name="unit"]').val('');
}


// Append the added items to the preview to the table as items
function add_item_to_table() {
   "use strict";
   var description = $('.main textarea[name="description"]').val();
   var long_description = $('.main textarea[name="long_description"]').val();
   if(description.trim()){
    // If not custom data passed get from the preview

    var table_row = '';
    var item_key = $("body").find('tbody .item').length + 1;
    table_row += '<tr class="sortable item">';
    table_row += '<td class="dragger">';
    // Check if quantity is number
    if (isNaN(data.qty)) {
        data.qty = 1;
    }
    // Check if rate is number
    if (data.rate === '' || isNaN(data.rate)) {
        data.rate = 0;
    }


    var qty = $('.main input[name="quantity"]').val();
    var amount = data.rate * qty;
    var tax_name = 'newitems[' + item_key + '][taxname][]';
    $("body").append('<div class="dt-loader"></div>');
    var regex = /<br[^>]*>/gi;

        // order input
        table_row += '<input type="hidden" class="order" name="newitems[' + item_key + '][order]">';
        table_row += '</td>';

        table_row += '<td class="bold description"><input type="hidden" name="newitems[' + item_key + '][id]" value="">';
        table_row += '<input type="hidden" name="newitems[' + item_key + '][product_id]" value="' + data.id + '">';
        table_row += '<textarea name="newitems[' + item_key + '][description]" class="form-control" rows="5">' + data.description + '</textarea></td>';
        var long_description = '';
        if(data.long_description != null){
            long_description = data.long_description;
        }
        table_row += '<td><textarea name="newitems[' + item_key + '][long_description]" class="form-control item_long_description" rows="5">' + long_description + '</textarea></td>';

        table_row += '<td><div class="form-group">';
        table_row += '<div class="input-group quantity">';

        var max = '';
        if(data.without_checking_warehouse == 1){
            max = 'max="1000"';
        }

        table_row += '<input type="number" class="form-control quantity_item_row" data-quantity onblur="calculate_total();" onchange="calculate_total();" name="newitems[' + item_key + '][qty]" value="'+qty+'" min="1" '+max+'>';
        table_row += '<span class="input-group-addon unit">' + data.unit_name + '</span>';
        table_row += '</div>';
        table_row += '</div></td>';

        table_row += '<input type="text" name="newitems[' + item_key + '][unit]" class="form-control input-transparent text-right" value="' + data.unit_name + '">';
        table_row += '</td>';
        table_row += '<td class="rate"><input type="hidden" name="rate" class="rate_item_row" value="'+data.rate+'"><input data-toggle="tooltip" onblur="calculate_total();" onchange="calculate_total();" name="newitems[' + item_key + '][rate]" value="' + data.rate_text + '" class="form-control"></td>';
        table_row += '<td class="rate"><input type="hidden" name="tax_name" class="tax_name_item_row" value="'+data.taxname+'"><input type="hidden" name="tax_rate" class="tax_rate_item_row" value="'+data.taxrate+'"><input data-toggle="tooltip" name="newitems[' + item_key + '][rate]" value="' + data.taxname + '" class="form-control"></td>';

        

        table_row += '<td class="amount" align="right">' + ReplaceNumberWithCommas(amount) + '</td>';
        table_row += '<td><input type="hidden" name="discount" value="'+data.discount_price+'"><a href="#" class="btn btn-danger pull-right" onclick="delete_item(this,' + data.id + '); return false;"><i class="fa fa-trash"></i></a></td>';
        table_row += '</tr>';

        $('select.tax').removeAttr('multiple'); 

        $('table.items tbody').append(table_row);
        $(document).trigger({
            type: "item-added-to-table",
            data: data,
            row: table_row
        });
        setTimeout(function() {
           calculate_total();
       }, 15);
        $('.main textarea[name="description"]').val('');
        $('.main textarea[name="long_description"]').val('');
        $('.main input[name="product_id"]').val('');
        $('.main input[name="quantity"]').val(1);
        $('.main input[name="rate"]').val('');
        $('.main input[name="tax"]').val('');
        $('.main .unit').text($('input[name="unit_text"]').val());
        $('body').find('#items-warning').remove();
        $("body").find('.dt-loader').remove();
        return true;
    }
    else{
        alert_float('warning', 'Please select a item');
    }
}
// Reoder the items in table edit for estimate and invoices
function reorder_items() {
    "use strict";
    var rows = $('.table.has-calculations tbody tr.item');
    var i = 1;
    $.each(rows, function() {
        $(this).find('input.order').val(i);
        i++;
    });
}
var data = {};
// Calculate invoice total - NOT RECOMENDING EDIT THIS FUNCTION BECUASE IS VERY SENSITIVE

function calculate_total() {
    "use strict";
    $('.tax-area').remove();
    var qty = $('.quantity_item_row');
    var rate = $('.rate_item_row');
    var list_amount = $('.item .amount');
    var list_tax_name = $('.item .tax_name_item_row');
    var list_tax_rate = $('.item .tax_rate_item_row');
    var list_discount = $('.item input[name="discount"]');
    let subtotal = 0;
    let total_tax = 0;
    let discount = 0;
    var taxes = {};
    let i;
    for(i = 0; i < qty.length; i++){
        var quantity = parseFloat(qty.eq(i).val());
        var price = parseFloat(rate.eq(i).val());
        var discount_price = parseFloat(list_discount.eq(i).val());
        var amount = quantity * price;
        discount += quantity * discount_price;
        list_amount.eq(i).text(ReplaceNumberWithCommas(amount));
        subtotal += amount;
        var name = list_tax_name.eq(i).val();
        var tax_rate = list_tax_rate.eq(i).val();

        let calculated_tax = (tax_rate * amount / 100);
        total_tax += calculated_tax;
        var obj_key = tax_rate.replace(/\./g, '_');
        if (!taxes.hasOwnProperty(obj_key)) {
            if (tax_rate != 0) {
                var tax_row = '<tr class="tax-area"><td>'+name+'</td><td id="tax_id_' + obj_key + '">'+ReplaceNumberWithCommas(calculated_tax)+'</td></tr>';
                $('#discount_area').after(tax_row);
                taxes[obj_key] = calculated_tax;
            }
        } else {
            var new_val = taxes[obj_key] + calculated_tax;
            taxes[obj_key] = new_val;
            $('td#tax_id_'+obj_key+'').text(ReplaceNumberWithCommas(new_val));
        }
    }
    $('table td#sub_total').text(ReplaceNumberWithCommas(round(subtotal)));
    $('table td#discount').text('-'+ReplaceNumberWithCommas(round(discount)));
    $('table td#total').text(ReplaceNumberWithCommas(round(subtotal + total_tax - discount)));
}

// Deletes invoice items
function delete_item(row, itemid) {
    "use strict";
    $(row).parents('tr').addClass('animated fadeOut');
    
    setTimeout(function() {
        $(row).parents('tr').remove();
        calculate_total();
    }, 50);
    
    // If is edit we need to add to input removed_items to track activity
    if ($('input[name="isedit"]').length > 0) {
        $('#removed-items').append(hidden_input('removed_items[]', itemid));
    }
}
function formatNumber(n) {
  return n.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}
function round(val){
  "use strict";
  return Math.round(val * 100) / 100;
}