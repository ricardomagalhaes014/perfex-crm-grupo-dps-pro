(function(){
  "use strict";
  $('.change_status').click(function(){
   var status = $(this).data('status'), order_number;
   order_number = $('input[name="order_number"]').val();
   if(status == 8){
    $('#chosse').modal();
    return false;
  }
  var data = {};
  data.cancelReason = '';
  data.status = status;
  change_status(order_number, data);      
}); 
  $('.cancell_order').click(function(){
    $('#chosse').modal('hide');
    var status = $(this).data('status'), order_number;
    order_number = $('input[name="order_number"]').val();
    var data = {};
    data.cancelReason = $('textarea[name="cancel_reason"]').val();
    data.status = status;
    change_status(order_number, data);
  });  

  // Manually add goods delivery activity
  $("#wh_enter_activity").on('click', function() {
    "use strict"; 
    var message = $('#wh_activity_textarea').val();
    var goods_delivery_id = $('input[name="goods_delivery_id"]').val();
    if (message === '') {
      alert_float('danger', 'Please enter activity');
      return; 
    }
    if (goods_delivery_id === '') { return; }
    $.post(admin_url + 'omni_sales/wh_add_activity', {
      goods_delivery_id: goods_delivery_id,
      activity: message,
      rel_type: 'omni_order',
    }).done(function(response) {
      response = JSON.parse(response);
      if(response.status == true){
        alert_float('success', response.message);
        location.reload();
      }else{
        alert_float('danger', response.message);
      }
    }).fail(function(data) {
      alert_float('danger', data.message);
    });
  });
})(jQuery);
function change_status(order_number,data){
  "use strict";
  $.post(admin_url+'omni_sales/admin_change_status/'+order_number,data).done(function(response){
   response = JSON.parse(response);
   if(response.success == true) {
    alert_float('success','Status changed');
    setTimeout(function(){location.reload();},1500);
  }

});
}
function inventory_check(order_number){
  "use strict";
  $.get(admin_url+'omni_sales/preview_inventory_check/'+order_number).done(function(response){
   response = JSON.parse(response);
   if(response.success == true) {
    $('#inventory_check').modal('show');
    $('.inventory_check_table tbody').html(response.html);
    if(response.active_convert_button == 1){
      $('#form_create_purchase_request button[type="submit"]').removeAttr('disabled');
    }
    else{
      $('#form_create_purchase_request button[type="submit"]').attr('disabled', 'disabled');
    }
  }
});
}



function delete_wh_activitylog(wrapper, id) {
  "use strict"; 

  if (confirm_delete()) {
    requestGetJSON('warehouse/delete_activitylog/' + id).done(function(response) {
      if (response.success === true || response.success == 'true') { $(wrapper).parents('.feed-item').remove(); }
    }).fail(function(data) {
      alert_float('danger', data.responseText);
    });
  }
}




