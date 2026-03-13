window.addEventListener('load',function(){
        appValidateForm($('#whiteboard-group-form'),{name:'required'},manage_groups);
        $('#whiteboard-group-modal').on('hidden.bs.modal', function(event) {
            $('#additional').html('');
            $('#whiteboard-group-modal input[name="name"]').val('');
            $('#whiteboard-group textarea').val('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

        $('#whiteboard-group-modal').on('show.bs.modal', function(e) {
            var type_id = $('#whiteboard-group-modal').find('input[type="hidden"][name="id"]').val();
            if (typeof(type_id) !== 'undefined') {
                $('#whiteboard-group-modal .add-title').addClass('hide');
                $('#whiteboard-group-modal .edit-title').removeClass('hide');
            }else{
                $('#whiteboard-group-modal .add-title').removeClass('hide');
                $('#whiteboard-group-modal .edit-title').addClass('hide');
            }
        });
    });
    function manage_groups(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function(response) {
            response = JSON.parse(response);

            if(response.success == true){
                alert_float('success',response.message);
                if($('body').hasClass('whiteboard') && typeof(response.id) != 'undefined') {
                    var category = $('#whiteboard_group_id');
                    category.find('option:first').after('<option value="'+response.id+'">'+response.name+'</option>');
                    category.selectpicker('val',response.id);
                    category.selectpicker('refresh');
                }
            }

            if($.fn.DataTable.isDataTable('.table-whiteboard-group')){
                $('.table-whiteboard-group').DataTable().ajax.reload();
            }

            $('#whiteboard-group-modal').modal('hide');
        });
        return false;
    }

    function new_group(){
        $('.btn-group').toggleClass('open');
        $('#whiteboard-group-modal').modal('show');
         $('#whiteboard-group-form textarea').val('');
        $('.edit-title').addClass('hide');
    }

    function edit_group(invoker,id){
        var name = $(invoker).data('name');
        var description = $(invoker).data('description');
        $('#additional').append(hidden_input('id',id));
        $('#whiteboard-group-modal input[name="name"]').val(name);
        $('#whiteboard-group-modal textarea').val(description);
        $('#whiteboard-group-modal').modal('show');
        $('.add-title').addClass('hide');
    }