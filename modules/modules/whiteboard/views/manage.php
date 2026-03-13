<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style type="text/css">
    .literally.toolbar-at-top .lc-drawing {
    top: 0px !important;
}
.literally .lc-drawing.with-gui {
     left: 0 !important; 
}
</style>
<?php
$isGridView = 0;
if ($this->session->has_userdata('whiteboard_grid_view') && $this->session->userdata('whiteboard_grid_view') == 'true') {
    $isGridView = 1;
}
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="_filters _hidden_inputs hidden">
                        <?php
                        echo form_hidden('my_whiteboard');
                        foreach($staffs as $staff){
                            echo form_hidden('staffid_'.$staff['staffid']);
                        }
                        foreach($groups as $group){
                            echo form_hidden('whiteboard_group_id_'.$group['id']);
                        }

                        ?>
                    </div>

                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if(has_permission('whiteboard','','create')){ ?>


                        <div class="btn-group">
                           <button type="button" class="btn btn-info pull-left grid_view display-block mright5 grid_view dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                             <?php echo _l('whiteboard_create_new'); ?> <span class="caret"></span>
                             </button>
                             <ul class="dropdown-menu dropdown-menu-right">
                                <li>
                                    <a href="#" onclick="new_whiteboard();return false;"><?php echo _l('whiteboard_create_new'); ?></a>
                                </li>
                                <li>
                                    <a href="#" onclick="new_group();return false;"><?php echo _l('whiteboard_group'); ?></a>

                                </li>

                               </ul>
                         </div>
                               <!--  <a href="<?php echo admin_url('whiteboard/whiteboard_create'); ?>" class="btn btn-info pull-left display-block mright5"><?php echo _l('whiteboard_create_new'); ?></a> -->
                            <?php } ?>

                            <a href="<?php echo admin_url('whiteboard/switch_grid/'.$switch_grid); ?>" class="btn btn-default hidden-xs">
                                <?php if($switch_grid == 1){ echo _l('whiteboard_switch_to_list_view');}else{echo _l('whiteboard_switch_to_grid_view');}; ?>
                            </a>
                            <div class="visible-xs">
                                <div class="clearfix"></div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <div class="clearfix mtop20"></div>
                        <div class="row" id="whiteboard-table">
                            <?php if($isGridView ==0){ ?>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="bold"><?php echo _l('filter_by'); ?></p>
                                    </div>
                                    <?php if(has_permission('whiteboard','','view')){ ?>
                                        <div class="col-md-3 whiteboard-filter-column">
                                            <?php echo render_select('view_assigned',$staffs,array('staffid',array('firstname','lastname')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('whiteboard_staff')),array(),'no-mbot'); ?>
                                        </div>
                                    <?php } ?>
                                    <div class="col-md-3 whiteboard-filter-column">
                                        <?php echo render_select('view_group',$groups,array('id',array('name')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('whiteboard_group')),array(),'no-mbot'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <hr class="hr-panel-heading" />
                            <?php } ?>
                            <div class="col-md-12">
                        <?php if($this->session->has_userdata('whiteboard_grid_view') && $this->session->userdata('whiteboard_grid_view') == 'true') { ?>
                            <div class="grid-tab" id="grid-tab">
                                <div class="row">
                                    <div id="whiteboard-grid-view" class="container-fluid">

                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <?php render_datatable(array(
                                _l('whiteboard_title'),
                                _l('whiteboard_desc'),
                                _l('whiteboard_staff'),
                                _l('whiteboard_group'),
                                _l('whiteboard_created_at')
                            ),'whiteboard', array('customizable-table'),
                              array(
                                  'id'=>'table-whiteboard',
                                  'data-last-order-identifier'=>'whiteboard',
                                  'data-default-order'=>get_table_last_order('whiteboard'),
                              )); ?>
                        <?php } ?>
                        </div>
                        </div>

                        </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- whiteboard Modal-->
<div class="modal fade whiteboard-modal" id="whiteboard-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content data">

        </div>
    </div>
</div>


<div class="modal fade whiteboard-modal" id="whiteboard_create" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-lg">
        <?php echo form_open_multipart(admin_url('whiteboard/whiteboard_create'),array('id'=>'whiteboard-form')) ;?>
            <?php echo render_input('staffid','', get_staff_user_id(), 'hidden'); ?>
        <div class="modal-content data">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">
                    <span class="edit-title hide"><?php echo _l('whiteboard_create_new'); ?></span>
                    <span class="add-title"><?php echo _l('whiteboard_create_new'); ?></span>
                </h4>
            </div>
                <div class="panel-body">
                       
                        <hr>

                        
                       
                        <?php echo render_input('title','Title',''); ?>

                        <?php
                        $selected = '';
                        if(is_admin() || get_option('staff_members_create_inline_whiteboard_group') == '1'){
                            echo render_select_with_input_group('whiteboard_group_id',$whiteboard_groups,array('id','name'),'whiteboard_group',$selected,'<a href="#" onclick="new_group();return false;"><i class="fa fa-plus"></i></a>');
                        } else {
                            echo render_select('whiteboard_group_id',$whiteboard_groups,array('id','name'),'whiteboard_group',$selected);
                        }
                        ?>

                       
                        <?php echo render_textarea('description','Description','',array('rows'=>4),array()); 
                             $selected = '';
                        echo render_select_with_input_group('project_id',$projects,array('id','name'),'project_group',$selected);
                        ?>

                    </div>
                    <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info whiteboard-btn" data-loading-text="Please wait..." data-autocomplete="off" data-form="#whiteboard-form">Save</button>
            </div>
        </div>
        

       <?php echo form_close();?>
    </div>
</div>
<?php $this->load->view('whiteboard/whiteboard_group'); ?>
<?php init_tail(); ?>

<script>
function new_whiteboard(){
    $('.btn-group').toggleClass('open');
    $('#whiteboard_create').modal('show');
}
var _lnth = 12;
$(function(){
    var TblServerParams = {
        "assigned": "[name='view_assigned']",
        "group": "[name='view_group']",
    };

    if(<?php echo $isGridView ?> == 0) {
        var tAPI = initDataTable('.table-whiteboard', admin_url+'whiteboard/table', [2, 3], [2, 3], TblServerParams,[4, 'desc']);

        $.each(TblServerParams, function(i, obj) {
            $('select' + obj).on('change', function() {
                $('table.table-whiteboard').DataTable().ajax.reload()
                    .columns.adjust()
                    .responsive.recalc();
            });
        });

    }else{
         $('.grid_view').on('click',function(){
            console.log('asd');
           $('.btn-group').toggleClass('open');

        });
         $(document).ready(function(){
            $('.select-whiteboard_group_id').on('click',function(){
                $('.select-whiteboard_group_id .bootstrap-select').toggleClass('open');
            });
            $('.select-project_id').on('click',function(){
                $('.select-project_id .bootstrap-select').toggleClass('open');
            });

        });
        loadGridView();

        $(document).off().on('click','a.paginate',function(e){
            e.preventDefault();
            console.log("$(this)", $(this).data('ci-pagination-page'))
            var pageno = $(this).data('ci-pagination-page');
            var formData = {
                search: $("input#search").val(),
                start: (pageno-1),
                length: _lnth,
                draw: 1
            }
            gridViewDataCall(formData, function (resposne) {
                $('div#grid-tab').html(resposne)
            })
        });
    }
});


    $("button.whiteboard-btn").on('click', function (e) {

         if($('#title').val() == '' || $('#whiteboard_group_id').val() == '' || $('#description').val() == ''){
                validate_whiteboard_form();
            }else{
                $('#whiteboard-form').submit();
            }
    });
    function validate_whiteboard_form(){
    appValidateForm($('#whiteboard-form'), {
        title: 'required',
        whiteboard_group_id: 'required',
        description : 'required',
    });
    $('#whiteboard-form').submit();
  }
</script>
</body>
</html>
