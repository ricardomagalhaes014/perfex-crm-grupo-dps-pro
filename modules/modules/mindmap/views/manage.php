<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isGridView = 0;
if ($this->session->has_userdata('mindmap_grid_view') && $this->session->userdata('mindmap_grid_view') == 'true') {
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
                        echo form_hidden('my_mindmap');
                        foreach($staffs as $staff){
                            echo form_hidden('staffid_'.$staff['staffid']);
                        }
                        foreach($groups as $group){
                            echo form_hidden('mindmap_group_id_'.$group['id']);
                        }
                        ?>
                    </div>

                    <div class="panel-body">
                        <div class="btn-group">
                           <button type="button" class="btn btn-info pull-left display-block mright5 grid_view dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                             <?php echo _l('mindmap_create_new'); ?> <span class="caret"></span>
                             </button>
                             <ul class="dropdown-menu dropdown-menu-right">
                                <li>
                                    <a href="#" onclick="new_mindmap();return false;"><?php echo _l('mindmap'); ?></a>
                                </li>
                                <li>
                                    <a href="#" onclick="new_group();return false;"><?php echo _l('new_mindmap_group'); ?></a>

                                </li>

                               </ul>
                      </div>
                        <div class="_buttons btn-group">
                            <?php if(has_permission('mindmap','','create')){ ?>
                               <!--  <a href="<?php echo admin_url('mindmap/mindmap_create'); ?>" class="btn btn-info pull-left display-block mright5"><?php echo _l('mindmap_create_new'); ?></a> -->
                                
                               
                            <?php } ?>

                            <a href="<?php echo admin_url('mindmap/switch_grid/'.$switch_grid); ?>" class="btn btn-default hidden-xs">
                                <?php if($switch_grid == 1){ echo _l('mindmap_switch_to_list_view');}else{echo _l('mindmap_switch_to_grid_view');}; ?>
                            </a>
                            <div class="visible-xs">
                                <div class="clearfix"></div>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <div class="clearfix mtop20"></div>
                        <div class="row" id="mindmap-table">
                            <?php if($isGridView ==0){ ?>
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-12">
                                        <p class="bold"><?php echo _l('filter_by'); ?></p>
                                    </div>
                                    <?php if(has_permission('mindmap','','view')){ ?>
                                        <div class="col-md-3 mindmap-filter-column">
                                            <?php echo render_select('view_assigned',$staffs,array('staffid',array('firstname','lastname')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('mindmap_staff')),array(),'no-mbot'); ?>
                                        </div>
                                    <?php } ?>
                                    <div class="col-md-3 mindmap-filter-column">
                                        <?php echo render_select('view_group',$groups,array('id',array('name')),'','',array('data-width'=>'100%','data-none-selected-text'=>_l('mindmap_group')),array(),'no-mbot'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <hr>
                            <?php } ?>
                            <div class="col-md-12">
                        <?php if($this->session->has_userdata('mindmap_grid_view') && $this->session->userdata('mindmap_grid_view') == 'true') { ?>
                            <div class="grid-tab" id="grid-tab">
                                <div class="row">
                                    <div id="mindmap-grid-view" class="container-fluid">

                                    </div>
                                </div>
                            </div>
                        <?php } else { ?>
                            <?php render_datatable(array(
                                _l('mindmap_title'),
                                _l('mindmap_desc'),
                                _l('mindmap_staff'),
                                _l('mindmap_group'),
                                _l('mindmap_created_at')
                            ),'mindmap', array('customizable-table'),
                              array(
                                  'id'=>'table-mindmap',
                                  'data-last-order-identifier'=>'mindmap',
                                  'data-default-order'=>get_table_last_order('mindmap'),
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
<!-- Mindmap Modal-->
<div class="modal fade mindmap-modal" id="mindmap-modal" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-lg">
        <div class="modal-content data">

        </div>
    </div>
</div>

<div class="modal fade mindmap-modal" id="mindmap_create" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel">
    <div class="modal-dialog modal-lg">
        <?php echo form_open_multipart(admin_url('mindmap/mindmap_create'),array('id'=>'mindmap-form')) ;?>
            <?php echo render_input('staffid','', get_staff_user_id(), 'hidden'); ?>
        <div class="modal-content data">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">
                    <span class="edit-title hide"><?php echo _l('mindmap'); ?></span>
                    <span class="add-title"><?php echo _l('mindmap'); ?></span>
                </h4>
            </div>
                <div class="panel-body">
                       
                        <hr>

                        
                        <?php echo render_input('title','Title',''); ?>

                        <?php
                        $selected = '';
                        if(is_admin() || get_option('staff_members_create_inline_mindmap_group') == '1'){
                            echo render_select_with_input_group('mindmap_group_id',$mindmap_groups,array('id','name'),'mindmap_group',$selected,'<a href="#" onclick="new_group();return false;"><i class="fa fa-plus"></i></a>');
                        } else {
                            echo render_select('mindmap_group_id',$mindmap_groups,array('id','name'),'mindmap_group',$selected);
                        }
                        ?>

                        
                        <?php echo render_textarea('description','Description','',array('rows'=>4),array()); ?>
                       
                        <?php //echo render_input('external_url','External Url',''); ?>
                        <?php
                        
                        echo render_select_with_input_group('project_id',$projects,array('id','name'),'project_group');
                        ?>

                    </div>
                    <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info mindmap-btn" data-loading-text="Please wait..." data-autocomplete="off" data-form="#mindmap-form">Save</button>
            </div>
        </div>
        

       <?php echo form_close();?>
    </div>
</div>
<?php init_tail(); ?>
<?php $this->load->view('mindmap/mindmap_group'); ?>
<script>

function new_mindmap(){
    $('.btn-group').toggleClass('open');
    $('#mindmap_create').modal('show');
}
 $("button.mindmap-btn").on('click', function (e) {
    console.log('click');
    if($('#title').val() == '' || $('#mindmap_group_id').val() == '' || $('#description').val() == ''){
        console.log('in');
        validate_mindmap_form();
    }else{
        console.log('out');
        $('#mindmap-form').submit();
    }
    
 })
function validate_mindmap_form(){
    appValidateForm($('#mindmap-form'), {
        title: 'required',
        mindmap_group_id: 'required',
        description : 'required',
    });
    $('#mindmap-form').submit();
}

    var _lnth = 12;
$(function(){
    var TblServerParams = {
        "assigned": "[name='view_assigned']",
        "group": "[name='view_group']",
    };

    if(<?php echo $isGridView ?> == 0) {
        var tAPI = initDataTable('.table-mindmap', admin_url+'mindmap/table', [2, 3], [2, 3], TblServerParams, [4, 'desc']);

        $.each(TblServerParams, function(i, obj) {
            $('select' + obj).on('change', function() {
                $('table.table-mindmap').DataTable().ajax.reload()
                    .columns.adjust()
                    .responsive.recalc();
            });
        });

    }else{
        $(document).ready(function(){
                $('.select-mindmap_group_id').on('click',function(){
                $('.select-mindmap_group_id .bootstrap-select').toggleClass('open');
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
</script>
</body>
</html>
