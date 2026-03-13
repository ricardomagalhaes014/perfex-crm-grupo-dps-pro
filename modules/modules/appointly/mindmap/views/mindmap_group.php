<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="mindmap-group-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('mindmap/group'),array('id'=>'mindmap-group-form')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="edit-title"><?php echo _l('edit_mindmap_group'); ?></span>
                    <span class="add-title"><?php echo _l('new_mindmap_group'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="additional"></div>
                        <?php echo render_input('name','mindmap_group_add_edit_name'); ?>
                        <?php echo render_textarea('description','mindmap_group_add_edit_description'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
        </div><!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<script>
    window.addEventListener('load',function(){
        appValidateForm($('#mindmap-group-form'),{name:'required'},manage_groups);
        $('#mindmap-group-modal').on('hidden.bs.modal', function(event) {
            $('#additional').html('');
            $('#mindmap-group-modal input[name="name"]').val('');
            $('#mindmap-group textarea').val('');
            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
        });

        $('#mindmap-group-modal').on('show.bs.modal', function(e) {
            var type_id = $('#mindmap-group-modal').find('input[type="hidden"][name="id"]').val();
            if (typeof(type_id) !== 'undefined') {
                $('#mindmap-group-modal .add-title').addClass('hide');
                $('#mindmap-group-modal .edit-title').removeClass('hide');
            }else{
                $('#mindmap-group-modal .add-title').removeClass('hide');
                $('#mindmap-group-modal .edit-title').addClass('hide');
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
                if($('body').hasClass('mindmap') && typeof(response.id) != 'undefined') {
                    var category = $('#mindmap_group_id');
                    category.find('option:first').after('<option value="'+response.id+'">'+response.name+'</option>');
                    category.selectpicker('val',response.id);
                    category.selectpicker('refresh');
                }
            }

            if($.fn.DataTable.isDataTable('.table-mindmap-group')){
                $('.table-mindmap-group').DataTable().ajax.reload();
            }

            $('#mindmap-group-modal').modal('hide');
        });
        return false;
    }

    function new_group(){
        $('.btn-group').toggleClass('open');
        $('#mindmap-group-modal').modal('show');
        $('#mindmap-group-form textarea').val('');
        $('.edit-title').addClass('hide');
    }

    function edit_group(invoker,id){
        var name = $(invoker).data('name');
        var description = $(invoker).data('description');
        $('#additional').append(hidden_input('id',id));
        $('#mindmap-group-modal input[name="name"]').val(name);
        $('#mindmap-group-modal textarea').val(description);
        $('#mindmap-group-modal').modal('show');
        $('.add-title').addClass('hide');
    }
</script>
