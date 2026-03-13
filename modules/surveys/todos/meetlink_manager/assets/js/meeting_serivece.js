
"use strict";

window.addEventListener('load',function(){
    appValidateForm($('#meeting-service-modal'), {
        m_service_name: 'required',
    }, manage_product_category);
    $('#m_service_modal').on('show.bs.modal', function(e) {
        var invoker = $(e.relatedTarget);
        var group_id = $(invoker).data('id');
        $('#m_service_modal .add-title').removeClass('hide');
        $('#m_service_modal .edit-title').addClass('hide');
        $('#m_service_modal input[name="m_service_id"]').val('');
        $('#m_service_modal input[name="service_name"]').val('');
        if (typeof(group_id) !== 'undefined') {
            $('#m_service_modal input[name="m_service_id"]').val(group_id);
            $('#m_service_modal .add-title').addClass('hide');
            $('#m_service_modal .edit-title').removeClass('hide');
            $('#m_service_modal input[name="service_name"]').val($(invoker).parents('tr').find('td').eq(0).text());
        }
    });
});
function manage_product_category(form) {
    var data = $(form).serialize();
    var url = form.action;
    $.post(url, data).done(function(response) {
        response = JSON.parse(response);
        if (response.success == true) {
            document.getElementById("meeting-service-modal").reset();

            if($.fn.DataTable.isDataTable('.table-meeting-services')){
                $('.table-meeting-services').DataTable().ajax.reload();
            }
            alert_float('success', response.message);
            $('#m_service_modal').modal('hide');
        } else {
            alert_float('danger', response.message);
        }
    });
    return false;
}